<?php
/**
 * Core verification decision engine.
 *
 * @package BillionVerify
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns an email address into an allow/block decision, with caching and a
 * configurable fail-open posture so forms keep working when the API is down.
 */
class BVEV_Verifier {

	/**
	 * Result of a verification decision.
	 *
	 * @var array Map of email => decision array for the current request.
	 */
	private $request_cache = array();

	/**
	 * Decide whether an email should be blocked.
	 *
	 * The returned array always contains:
	 *  - blocked  (bool)   Whether the submission should be rejected.
	 *  - status   (string) The verification status, or 'skipped' / 'error'.
	 *  - message  (string) Customer-facing message when blocked.
	 *  - data     (array)  Raw API data when available.
	 *
	 * @param string $email Email address to check.
	 * @return array
	 */
	public function check( $email ) {
		$email = trim( (string) $email );

		// Basic syntax gate first — never spend a credit on obvious garbage,
		// and let WordPress' own validation handle empty values.
		if ( '' === $email || ! is_email( $email ) ) {
			return $this->result( false, 'skipped' );
		}

		$key = strtolower( $email );
		if ( isset( $this->request_cache[ $key ] ) ) {
			return $this->request_cache[ $key ];
		}

		$api_key = (string) BVEV_Settings::get( 'api_key', '' );
		if ( '' === trim( $api_key ) ) {
			// Not configured yet: behave as if the plugin were inactive.
			return $this->result( false, 'skipped' );
		}

		$data = $this->get_verification( $email );

		if ( is_wp_error( $data ) ) {
			$decision = $this->result( $this->fail_open() ? false : true, 'error', '', array() );
			$decision['error'] = $data->get_error_message();
			$this->request_cache[ $key ] = $decision;
			return $decision;
		}

		$status  = isset( $data['status'] ) ? (string) $data['status'] : 'unknown';
		$blocked = BVEV_Settings::blocks_status( $status );

		/**
		 * Filter the final block decision.
		 *
		 * @param bool   $blocked Whether the email is blocked.
		 * @param string $status  Verification status.
		 * @param array  $data    Raw API data.
		 * @param string $email   The email address.
		 */
		$blocked = (bool) apply_filters( 'bvev_should_block', $blocked, $status, $data, $email );

		$decision                     = $this->result( $blocked, $status, '', $data );
		$this->request_cache[ $key ] = $decision;
		return $decision;
	}

	/**
	 * Convenience helper returning just the boolean block decision.
	 *
	 * @param string $email Email address.
	 * @return bool
	 */
	public function is_blocked( $email ) {
		$decision = $this->check( $email );
		return ! empty( $decision['blocked'] );
	}

	/**
	 * Get verification data, using a persistent transient cache to save credits.
	 *
	 * @param string $email Email address.
	 * @return array|WP_Error
	 */
	private function get_verification( $email ) {
		$ttl = (int) BVEV_Settings::get( 'cache_ttl', 0 );
		$transient_key = 'bvev_v_' . md5( strtolower( $email ) );

		if ( $ttl > 0 ) {
			$cached = get_transient( $transient_key );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$client = new BVEV_Api_Client(
			(string) BVEV_Settings::get( 'api_key', '' ),
			(int) BVEV_Settings::get( 'timeout', 8 )
		);
		$data = $client->verify_single( $email, (bool) BVEV_Settings::get( 'check_smtp', 1 ) );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		if ( $ttl > 0 ) {
			set_transient( $transient_key, $data, $ttl );
		}

		return $data;
	}

	/**
	 * Whether to accept emails when the API cannot be reached.
	 *
	 * @return bool
	 */
	private function fail_open() {
		return (bool) BVEV_Settings::get( 'fail_open', 1 );
	}

	/**
	 * Build a normalized decision array.
	 *
	 * @param bool   $blocked Whether blocked.
	 * @param string $status  Status string.
	 * @param string $message Optional override message.
	 * @param array  $data    Raw data.
	 * @return array
	 */
	private function result( $blocked, $status, $message = '', $data = array() ) {
		if ( '' === $message && $blocked ) {
			$message = (string) BVEV_Settings::get( 'block_message', '' );
			if ( '' === $message ) {
				$message = __( 'Please enter a valid, reachable email address.', 'billionverify-email-validator' );
			}
		}
		return array(
			'blocked' => (bool) $blocked,
			'status'  => $status,
			'message' => $message,
			'data'    => is_array( $data ) ? $data : array(),
		);
	}
}
