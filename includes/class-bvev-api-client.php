<?php
/**
 * BillionVerify HTTP API client.
 *
 * @package BillionVerify
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thin wrapper around the BillionVerify REST API using the WordPress HTTP API.
 */
class BVEV_Api_Client {

	/**
	 * API key.
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Request timeout in seconds.
	 *
	 * @var int
	 */
	private $timeout;

	/**
	 * Constructor.
	 *
	 * @param string $api_key API key.
	 * @param int    $timeout Timeout in seconds.
	 */
	public function __construct( $api_key, $timeout = 8 ) {
		$this->api_key = trim( (string) $api_key );
		$this->timeout = max( 1, (int) $timeout );
	}

	/**
	 * Resolve the API base URL (filterable for staging).
	 *
	 * @return string
	 */
	public function base_url() {
		return untrailingslashit( apply_filters( 'bvev_api_base', BVEV_API_BASE ) );
	}

	/**
	 * Verify a single email address.
	 *
	 * @param string $email     Email address.
	 * @param bool   $check_smtp Whether to request SMTP verification.
	 * @return array|WP_Error The `data` object on success, WP_Error on failure.
	 */
	public function verify_single( $email, $check_smtp = true ) {
		$body = array(
			'email'      => $email,
			'check_smtp' => (bool) $check_smtp,
		);
		$response = $this->request( 'POST', '/v1/verify/single', $body );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( ! isset( $response['data'] ) || ! is_array( $response['data'] ) ) {
			return new WP_Error( 'bvev_bad_response', __( 'Unexpected response from the verification API.', 'billionverify-email-validator' ) );
		}
		return $response['data'];
	}

	/**
	 * Fetch the account credit balance.
	 *
	 * @return array|WP_Error The `data` object on success.
	 */
	public function get_credits() {
		$response = $this->request( 'GET', '/v1/credits' );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( ! isset( $response['data'] ) || ! is_array( $response['data'] ) ) {
			return new WP_Error( 'bvev_bad_response', __( 'Unexpected response from the verification API.', 'billionverify-email-validator' ) );
		}
		return $response['data'];
	}

	/**
	 * Perform an authenticated request and decode the standard envelope.
	 *
	 * @param string     $method HTTP method.
	 * @param string     $path   Path beginning with a slash.
	 * @param array|null $body   Optional JSON body.
	 * @return array|WP_Error Decoded envelope on success.
	 */
	private function request( $method, $path, $body = null ) {
		if ( '' === $this->api_key ) {
			return new WP_Error( 'bvev_no_api_key', __( 'No BillionVerify API key is configured.', 'billionverify-email-validator' ) );
		}

		$args = array(
			'method'  => $method,
			'timeout' => $this->timeout,
			'headers' => array(
				'BV-API-KEY' => $this->api_key,
				'Accept'     => 'application/json',
				'User-Agent' => 'BillionVerify-WP/' . BVEV_VERSION . '; ' . home_url( '/' ),
			),
		);

		if ( null !== $body ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body']                    = wp_json_encode( $body );
		}

		$response = wp_remote_request( $this->base_url() . $path, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$json = json_decode( $raw, true );

		if ( ! is_array( $json ) ) {
			return new WP_Error(
				'bvev_invalid_json',
				/* translators: %d: HTTP status code. */
				sprintf( __( 'Invalid response from the verification API (HTTP %d).', 'billionverify-email-validator' ), $code )
			);
		}

		// The API returns success:false envelopes for business errors (auth,
		// credits, rate limit). Surface those as WP_Error with the API code.
		$is_ok = ( $code >= 200 && $code < 300 ) && ! empty( $json['success'] );
		if ( ! $is_ok ) {
			$message  = isset( $json['message'] ) ? (string) $json['message'] : __( 'The verification API returned an error.', 'billionverify-email-validator' );
			$err_code = isset( $json['code'] ) ? (string) $json['code'] : (string) $code;
			$wp_error = new WP_Error( 'bvev_api_' . $err_code, $message, array( 'http_status' => $code ) );
			return $wp_error;
		}

		return $json;
	}
}
