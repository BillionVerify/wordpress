<?php
/**
 * WooCommerce integration: checkout and account registration.
 *
 * @package BillionVerify
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates the billing email at checkout and the email on My Account signup.
 */
class BVEV_Integration_WooCommerce extends BVEV_Integration_Base {

	public function key() {
		return 'woocommerce';
	}

	public function label() {
		return __( 'WooCommerce', 'billionverify-email-validator' );
	}

	public function is_available() {
		return class_exists( 'WooCommerce' );
	}

	public function hooks() {
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_checkout' ), 10, 2 );
		add_filter( 'woocommerce_registration_errors', array( $this, 'validate_registration' ), 10, 3 );
	}

	/**
	 * Validate the billing email at checkout.
	 *
	 * @param array    $data   Posted checkout data.
	 * @param WP_Error $errors Errors object.
	 * @return void
	 */
	public function validate_checkout( $data, $errors ) {
		$email = isset( $data['billing_email'] ) ? $data['billing_email'] : '';
		$message = $this->block_message_for( $email );
		if ( '' !== $message && is_wp_error( $errors ) ) {
			$errors->add( 'billing_email', $message );
		}
	}

	/**
	 * Validate the email on account registration.
	 *
	 * @param WP_Error $errors   Errors object.
	 * @param string   $username Username.
	 * @param string   $email    Email.
	 * @return WP_Error
	 */
	public function validate_registration( $errors, $username, $email ) {
		$message = $this->block_message_for( $email );
		if ( '' !== $message ) {
			$errors->add( 'bvev_email', $message );
		}
		return $errors;
	}
}
