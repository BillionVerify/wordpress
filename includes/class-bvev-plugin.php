<?php
/**
 * Plugin bootstrap / service container.
 *
 * @package BillionVerify
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singleton that owns the verifier and wires up integrations and admin.
 */
class BVEV_Plugin {

	/**
	 * Instance.
	 *
	 * @var BVEV_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Verifier.
	 *
	 * @var BVEV_Verifier
	 */
	private $verifier;

	/**
	 * Integration manager.
	 *
	 * @var BVEV_Integration_Manager
	 */
	private $integrations;

	/**
	 * Get the singleton instance.
	 *
	 * @return BVEV_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->boot();
		}
		return self::$instance;
	}

	/**
	 * Boot services and hooks.
	 *
	 * @return void
	 */
	private function boot() {
		$this->verifier     = new BVEV_Verifier();
		$this->integrations = new BVEV_Integration_Manager( $this->verifier );

		// Register form integrations late so target plugins have loaded.
		add_action( 'init', array( $this->integrations, 'register' ), 20 );

		if ( is_admin() ) {
			new BVEV_Admin( $this->verifier, $this->integrations );
		}

		add_filter( 'plugin_action_links_' . BVEV_PLUGIN_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * The shared verifier.
	 *
	 * @return BVEV_Verifier
	 */
	public function verifier() {
		return $this->verifier;
	}

	/**
	 * The integration manager.
	 *
	 * @return BVEV_Integration_Manager
	 */
	public function integrations() {
		return $this->integrations;
	}

	/**
	 * Add a Settings link on the plugins screen.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function action_links( $links ) {
		$settings = '<a href="' . esc_url( admin_url( 'options-general.php?page=billionverify-email-validator' ) ) . '">' . esc_html__( 'Settings', 'billionverify-email-validator' ) . '</a>';
		array_unshift( $links, $settings );
		return $links;
	}
}
