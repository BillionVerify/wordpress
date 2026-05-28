<?php
/**
 * Fluent Forms integration.
 *
 * @package BillionVerify
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates email fields in Fluent Forms submissions.
 */
class BVEV_Integration_Fluent_Forms extends BVEV_Integration_Base {

	public function key() {
		return 'fluentforms';
	}

	public function label() {
		return __( 'Fluent Forms', 'billionverify-email-validator' );
	}

	public function is_available() {
		return defined( 'FLUENTFORM_VERSION' ) || function_exists( 'wpFluentForm' );
	}

	public function hooks() {
		// v5 namespace and v4 legacy namespace.
		add_filter( 'fluentform/validation_errors', array( $this, 'validate' ), 20, 4 );
		add_filter( 'fluentform_validation_errors', array( $this, 'validate' ), 20, 4 );
	}

	/**
	 * Validate email fields.
	 *
	 * @param array  $errors    Existing validation errors keyed by field name.
	 * @param array  $form_data Submitted data keyed by field name.
	 * @param object $form      Form object.
	 * @param array  $fields    Form field definitions.
	 * @return array
	 */
	public function validate( $errors, $form_data, $form, $fields ) {
		$list = isset( $fields['fields'] ) && is_array( $fields['fields'] ) ? $fields['fields'] : array();
		foreach ( $list as $field ) {
			$element = isset( $field['element'] ) ? $field['element'] : '';
			if ( 'input_email' !== $element ) {
				continue;
			}
			$name = isset( $field['attributes']['name'] ) ? $field['attributes']['name'] : '';
			if ( '' === $name || ! isset( $form_data[ $name ] ) ) {
				continue;
			}
			if ( ! empty( $errors[ $name ] ) ) {
				continue; // Already invalid.
			}
			$message = $this->block_message_for( (string) $form_data[ $name ] );
			if ( '' !== $message ) {
				$errors[ $name ] = array( $message );
			}
		}
		return $errors;
	}
}
