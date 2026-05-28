<?php
/**
 * Base class for form integrations.
 *
 * @package BillionVerify
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A form integration hooks a specific plugin's validation pipeline and asks the
 * shared verifier whether an email should be rejected.
 */
abstract class BVEV_Integration_Base {

	/**
	 * Shared verifier.
	 *
	 * @var BVEV_Verifier
	 */
	protected $verifier;

	/**
	 * Constructor.
	 *
	 * @param BVEV_Verifier $verifier Verifier.
	 */
	public function __construct( BVEV_Verifier $verifier ) {
		$this->verifier = $verifier;
	}

	/**
	 * Stable settings key for this integration (e.g. "woocommerce").
	 *
	 * @return string
	 */
	abstract public function key();

	/**
	 * Human-readable label.
	 *
	 * @return string
	 */
	abstract public function label();

	/**
	 * Whether the target plugin is present on this site.
	 *
	 * @return bool
	 */
	abstract public function is_available();

	/**
	 * Attach hooks. Only called when the integration is available and enabled.
	 *
	 * @return void
	 */
	abstract public function hooks();

	/**
	 * Run the verifier and return the block message, or empty string to allow.
	 *
	 * @param string $email Email address.
	 * @return string
	 */
	protected function block_message_for( $email ) {
		$decision = $this->verifier->check( $email );
		return ! empty( $decision['blocked'] ) ? (string) $decision['message'] : '';
	}
}
