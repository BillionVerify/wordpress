<?php
/**
 * WPForms integration.
 *
 * @package BillionVerify
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates WPForms email fields during submission processing.
 */
class BVEV_Integration_WPForms extends BVEV_Integration_Base {

	public function key() {
		return 'wpforms';
	}

	public function label() {
		return __( 'WPForms', 'billionverify-email-validator' );
	}

	public function is_available() {
		return function_exists( 'wpforms' );
	}

	public function hooks() {
		add_action( 'wpforms_process_validate_email', array( $this, 'validate' ), 20, 3 );
	}

	/**
	 * Validate an email field.
	 *
	 * @param int   $field_id     Field ID.
	 * @param mixed $field_submit Submitted value (string or array for multiple).
	 * @param array $form_data    Form data.
	 * @return void
	 */
	public function validate( $field_id, $field_submit, $form_data ) {
		$email = is_array( $field_submit ) ? ( isset( $field_submit['primary'] ) ? $field_submit['primary'] : reset( $field_submit ) ) : $field_submit;
		$message = $this->block_message_for( (string) $email );
		if ( '' === $message ) {
			return;
		}
		$form_id = isset( $form_data['id'] ) ? $form_data['id'] : 0;
		$processor = wpforms();
		if ( $processor && isset( $processor->process ) ) {
			$processor->process->errors[ $form_id ][ $field_id ] = esc_html( $message );
		}
	}
}
