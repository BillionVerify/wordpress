<?php
/**
 * Gravity Forms integration.
 *
 * @package BillionVerify
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates Gravity Forms email-type fields.
 */
class BVEV_Integration_Gravity_Forms extends BVEV_Integration_Base {

	public function key() {
		return 'gravityforms';
	}

	public function label() {
		return __( 'Gravity Forms', 'billionverify-email-validator' );
	}

	public function is_available() {
		return class_exists( 'GFForms' );
	}

	public function hooks() {
		add_filter( 'gform_field_validation', array( $this, 'validate' ), 20, 4 );
	}

	/**
	 * Validate an email field.
	 *
	 * @param array  $result Result with is_valid/message.
	 * @param mixed  $value  Submitted value (string or array).
	 * @param array  $form   Form object.
	 * @param object $field  Field object.
	 * @return array
	 */
	public function validate( $result, $value, $form, $field ) {
		if ( ! isset( $field->type ) || 'email' !== $field->type ) {
			return $result;
		}
		if ( empty( $result['is_valid'] ) ) {
			return $result; // Already failed GF's own checks.
		}
		$email = is_array( $value ) ? reset( $value ) : $value;
		$message = $this->block_message_for( (string) $email );
		if ( '' !== $message ) {
			$result['is_valid'] = false;
			$result['message']  = $message;
		}
		return $result;
	}
}
