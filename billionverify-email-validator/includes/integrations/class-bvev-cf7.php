<?php
/**
 * Contact Form 7 integration.
 *
 * @package BillionVerify
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates every email field in a Contact Form 7 submission.
 */
class BVEV_Integration_CF7 extends BVEV_Integration_Base {

	public function key() {
		return 'cf7';
	}

	public function label() {
		return __( 'Contact Form 7', 'billionverify-email-validator' );
	}

	public function is_available() {
		return defined( 'WPCF7_VERSION' );
	}

	public function hooks() {
		add_filter( 'wpcf7_validate_email', array( $this, 'validate' ), 20, 2 );
		add_filter( 'wpcf7_validate_email*', array( $this, 'validate' ), 20, 2 );
	}

	/**
	 * Validate an email tag.
	 *
	 * @param WPCF7_Validation $result Validation result object.
	 * @param WPCF7_FormTag    $tag    The form tag.
	 * @return WPCF7_Validation
	 */
	public function validate( $result, $tag ) {
		$name = isset( $tag->name ) ? $tag->name : '';
		if ( '' === $name || ! isset( $_POST[ $name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return $result;
		}
		$email = sanitize_text_field( wp_unslash( $_POST[ $name ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$message = $this->block_message_for( $email );
		if ( '' !== $message ) {
			$result->invalidate( $tag, $message );
		}
		return $result;
	}
}
