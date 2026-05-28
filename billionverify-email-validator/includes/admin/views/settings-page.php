<?php
/**
 * Settings page markup.
 *
 * @package BillionVerify
 *
 * @var array                   $settings     Current settings.
 * @var BVEV_Integration_Base[] $integrations Integration instances.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$status_labels = array(
	'invalid'    => __( 'Invalid (undeliverable mailbox)', 'billionverify-email-validator' ),
	'disposable' => __( 'Disposable / temporary domains', 'billionverify-email-validator' ),
	'catchall'   => __( 'Catch-all domains', 'billionverify-email-validator' ),
	'role'       => __( 'Role addresses (info@, support@…)', 'billionverify-email-validator' ),
	'risky'      => __( 'Risky', 'billionverify-email-validator' ),
	'unknown'    => __( 'Unknown (could not determine)', 'billionverify-email-validator' ),
);
?>
<div class="wrap bvev-wrap">
	<h1><?php esc_html_e( 'BillionVerify Email Validator', 'billionverify-email-validator' ); ?></h1>
	<?php settings_errors( 'bvev' ); ?>

	<form method="post" action="">
		<?php wp_nonce_field( BVEV_Admin::NONCE ); ?>

		<h2 class="title"><?php esc_html_e( 'API Connection', 'billionverify-email-validator' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="bvev_api_key"><?php esc_html_e( 'API Key', 'billionverify-email-validator' ); ?></label></th>
				<td>
					<input name="api_key" id="bvev_api_key" type="text" class="regular-text" autocomplete="off"
						value="<?php echo esc_attr( $settings['api_key'] ); ?>" />
					<button type="button" class="button" id="bvev-test-connection"><?php esc_html_e( 'Test connection', 'billionverify-email-validator' ); ?></button>
					<span id="bvev-connection-result" class="bvev-inline-result"></span>
					<p class="description">
						<?php
						printf(
							/* translators: %s: dashboard URL. */
							wp_kses_post( __( 'Find your API key in the <a href="%s" target="_blank" rel="noopener">BillionVerify dashboard</a>.', 'billionverify-email-validator' ) ),
							'https://app.billionverify.com/'
						);
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'SMTP check', 'billionverify-email-validator' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="check_smtp" value="1" <?php checked( $settings['check_smtp'] ); ?> />
						<?php esc_html_e( 'Perform a full SMTP mailbox check (most accurate, slightly slower).', 'billionverify-email-validator' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Blocking Rules', 'billionverify-email-validator' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Block these statuses', 'billionverify-email-validator' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( $status_labels as $status => $label ) : ?>
							<label class="bvev-block">
								<input type="checkbox" name="block_statuses[<?php echo esc_attr( $status ); ?>]" value="1"
									<?php checked( ! empty( $settings['block_statuses'][ $status ] ) ); ?> />
								<?php echo esc_html( $label ); ?>
							</label><br />
						<?php endforeach; ?>
						<p class="description"><?php esc_html_e( 'A submission is rejected when its email resolves to a checked status. "valid" is always accepted.', 'billionverify-email-validator' ); ?></p>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="bvev_block_message"><?php esc_html_e( 'Error message', 'billionverify-email-validator' ); ?></label></th>
				<td>
					<input name="block_message" id="bvev_block_message" type="text" class="large-text"
						value="<?php echo esc_attr( $settings['block_message'] ); ?>" />
					<p class="description"><?php esc_html_e( 'Shown to the visitor when their email is blocked.', 'billionverify-email-validator' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'When the API is unavailable', 'billionverify-email-validator' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="fail_open" value="1" <?php checked( $settings['fail_open'] ); ?> />
						<?php esc_html_e( 'Accept the email (recommended). Uncheck to block submissions when verification fails or credits run out.', 'billionverify-email-validator' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Protected Forms', 'billionverify-email-validator' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable validation for', 'billionverify-email-validator' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( $integrations as $key => $integration ) : ?>
							<?php $available = $integration->is_available(); ?>
							<label class="bvev-integration<?php echo $available ? '' : ' bvev-unavailable'; ?>">
								<input type="checkbox" name="integrations[<?php echo esc_attr( $key ); ?>]" value="1"
									<?php checked( ! empty( $settings['integrations'][ $key ] ) ); ?>
									<?php disabled( ! $available ); ?> />
								<?php echo esc_html( $integration->label() ); ?>
								<?php if ( ! $available ) : ?>
									<span class="bvev-badge"><?php esc_html_e( 'not installed', 'billionverify-email-validator' ); ?></span>
								<?php endif; ?>
							</label><br />
						<?php endforeach; ?>
					</fieldset>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Advanced', 'billionverify-email-validator' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="bvev_timeout"><?php esc_html_e( 'API timeout (seconds)', 'billionverify-email-validator' ); ?></label></th>
				<td><input name="timeout" id="bvev_timeout" type="number" min="1" max="60" class="small-text" value="<?php echo esc_attr( $settings['timeout'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="bvev_cache_ttl"><?php esc_html_e( 'Cache lifetime (seconds)', 'billionverify-email-validator' ); ?></label></th>
				<td>
					<input name="cache_ttl" id="bvev_cache_ttl" type="number" min="0" class="regular-text" value="<?php echo esc_attr( $settings['cache_ttl'] ); ?>" />
					<p class="description"><?php esc_html_e( 'Reuse a result for the same email to save credits. Set to 0 to disable caching.', 'billionverify-email-validator' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Save Changes', 'billionverify-email-validator' ), 'primary', 'bvev_save' ); ?>
	</form>

	<hr />

	<h2 class="title"><?php esc_html_e( 'Test an Email', 'billionverify-email-validator' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Run a live verification with your saved API key. This consumes one credit unless the result is cached.', 'billionverify-email-validator' ); ?></p>
	<p>
		<input type="email" id="bvev-test-email" class="regular-text" placeholder="name@example.com" />
		<button type="button" class="button button-secondary" id="bvev-run-test"><?php esc_html_e( 'Verify', 'billionverify-email-validator' ); ?></button>
	</p>
	<div id="bvev-test-result" class="bvev-test-result" style="display:none;"></div>
</div>
