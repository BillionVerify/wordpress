<?php
/**
 * Elementor Pro Forms integration.
 *
 * @package BillionVerify
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates email fields in Elementor Pro forms.
 */
class BVEV_Integration_Elementor extends BVEV_Integration_Base {

	public function key() {
		return 'elementor';
	}

	public function label() {
		return __( 'Elementor Forms', 'billionverify-email-validator' );
	}

	public function is_available() {
		return did_action( 'elementor_pro/init' ) || defined( 'ELEMENTOR_PRO_VERSION' );
	}

	public function hooks() {
		add_action( 'elementor_pro/forms/validation', array( $this, 'validate' ), 20, 2 );
	}

	/**
	 * Validate all email fields in the record.
	 *
	 * @param object $record       Form record.
	 * @param object $ajax_handler Ajax handler.
	 * @return void
	 */
	public function validate( $record, $ajax_handler ) {
		if ( ! is_object( $record ) || ! method_exists( $record, 'get_field' ) ) {
			return;
		}
		$email_fields = $record->get_field( array( 'type' => 'email' ) );
		if ( empty( $email_fields ) ) {
			return;
		}
		foreach ( $email_fields as $id => $field ) {
			$value   = isset( $field['value'] ) ? $field['value'] : '';
			$message = $this->block_message_for( (string) $value );
			if ( '' !== $message && method_exists( $ajax_handler, 'add_error' ) ) {
				$ajax_handler->add_error( $id, $message );
			}
		}
	}
}
