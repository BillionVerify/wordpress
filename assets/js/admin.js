/* global jQuery, bvevAdmin */
( function ( $ ) {
	'use strict';

	function esc( value ) {
		return $( '<div/>' ).text( value === null || value === undefined ? '' : String( value ) ).html();
	}

	function pill( truthy ) {
		return truthy
			? '<span class="bvev-pill yes">yes</span>'
			: '<span class="bvev-pill no">no</span>';
	}

	$( function () {
		var $connBtn = $( '#bvev-test-connection' );
		var $connResult = $( '#bvev-connection-result' );

		$connBtn.on( 'click', function () {
			$connResult.removeClass( 'is-ok is-error' ).text( bvevAdmin.i18n.testing );
			$.post( bvevAdmin.ajaxUrl, {
				action: 'bvev_test_connection',
				nonce: bvevAdmin.nonce,
				api_key: $( '#bvev_api_key' ).val()
			} ).done( function ( res ) {
				if ( res && res.success ) {
					$connResult.addClass( 'is-ok' ).text( res.data.message );
				} else {
					$connResult.addClass( 'is-error' ).text( ( res && res.data && res.data.message ) || bvevAdmin.i18n.error );
				}
			} ).fail( function () {
				$connResult.addClass( 'is-error' ).text( bvevAdmin.i18n.error );
			} );
		} );

		var $testBtn = $( '#bvev-run-test' );
		var $testResult = $( '#bvev-test-result' );

		$testBtn.on( 'click', function () {
			var email = $( '#bvev-test-email' ).val();
			$testResult.show().removeClass( 'is-valid is-block is-neutral' ).html( esc( bvevAdmin.i18n.verifying ) );
			$.post( bvevAdmin.ajaxUrl, {
				action: 'bvev_test_email',
				nonce: bvevAdmin.nonce,
				email: email
			} ).done( function ( res ) {
				if ( ! res || ! res.success ) {
					$testResult.addClass( 'is-block' ).html( esc( ( res && res.data && res.data.message ) || bvevAdmin.i18n.error ) );
					return;
				}
				renderResult( res.data );
			} ).fail( function () {
				$testResult.addClass( 'is-block' ).html( esc( bvevAdmin.i18n.error ) );
			} );
		} );

		function renderResult( payload ) {
			var d = payload.data || {};
			var status = payload.status || 'unknown';
			var cls = 'is-neutral';
			if ( status === 'valid' ) {
				cls = 'is-valid';
			} else if ( payload.would_block ) {
				cls = 'is-block';
			}

			var rows = [
				[ 'Status', esc( status ) ],
				[ 'Would be blocked', payload.would_block ? '<strong>Yes — submission rejected</strong>' : 'No — submission accepted' ],
				[ 'Deliverable', pill( d.is_deliverable ) ],
				[ 'Disposable', pill( d.is_disposable ) ],
				[ 'Catch-all', pill( d.is_catchall ) ],
				[ 'Role address', pill( d.is_role ) ],
				[ 'Free provider', pill( d.is_free ) ],
				[ 'Score', d.score !== undefined ? esc( d.score ) : '—' ],
				[ 'Reason', esc( d.reason || '—' ) ],
				[ 'Domain', esc( d.domain || '—' ) ],
				[ 'Credits used', d.credits_used !== undefined ? esc( d.credits_used ) : '—' ]
			];

			if ( d.domain_suggestion ) {
				rows.push( [ 'Did you mean', esc( d.domain_suggestion ) ] );
			}

			var html = '<h3>' + esc( status ) + '</h3><table><tbody>';
			rows.forEach( function ( row ) {
				html += '<tr><th>' + row[ 0 ] + '</th><td>' + row[ 1 ] + '</td></tr>';
			} );
			html += '</tbody></table>';

			$testResult.removeClass( 'is-valid is-block is-neutral' ).addClass( cls ).html( html );
		}
	} );
}( jQuery ) );
