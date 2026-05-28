<?php
/**
 * Loads, exposes and registers form integrations.
 *
 * @package BillionVerify
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Owns the catalogue of integration objects.
 */
class BVEV_Integration_Manager {

	/**
	 * Verifier.
	 *
	 * @var BVEV_Verifier
	 */
	private $verifier;

	/**
	 * All integration instances keyed by their settings key.
	 *
	 * @var BVEV_Integration_Base[]
	 */
	private $integrations = array();

	/**
	 * Constructor.
	 *
	 * @param BVEV_Verifier $verifier Verifier.
	 */
	public function __construct( BVEV_Verifier $verifier ) {
		$this->verifier = $verifier;
		$this->load();
	}

	/**
	 * Require and instantiate every integration.
	 *
	 * @return void
	 */
	private function load() {
		$dir = BVEV_PLUGIN_DIR . 'includes/integrations/';
		require_once $dir . 'class-bvev-wp-core.php';
		require_once $dir . 'class-bvev-woocommerce.php';
		require_once $dir . 'class-bvev-cf7.php';
		require_once $dir . 'class-bvev-wpforms.php';
		require_once $dir . 'class-bvev-gravity-forms.php';
		require_once $dir . 'class-bvev-elementor.php';
		require_once $dir . 'class-bvev-fluent-forms.php';

		$classes = array(
			'BVEV_Integration_WP_Registration',
			'BVEV_Integration_WP_Comment',
			'BVEV_Integration_WP_Lost_Password',
			'BVEV_Integration_WooCommerce',
			'BVEV_Integration_CF7',
			'BVEV_Integration_WPForms',
			'BVEV_Integration_Gravity_Forms',
			'BVEV_Integration_Elementor',
			'BVEV_Integration_Fluent_Forms',
		);

		/**
		 * Filter the list of integration class names so add-ons can extend the
		 * plugin without modifying core files.
		 *
		 * @param string[] $classes Integration class names.
		 */
		$classes = (array) apply_filters( 'bvev_integration_classes', $classes );

		foreach ( $classes as $class ) {
			if ( class_exists( $class ) ) {
				$instance = new $class( $this->verifier );
				if ( $instance instanceof BVEV_Integration_Base ) {
					$this->integrations[ $instance->key() ] = $instance;
				}
			}
		}
	}

	/**
	 * All integrations.
	 *
	 * @return BVEV_Integration_Base[]
	 */
	public function all() {
		return $this->integrations;
	}

	/**
	 * Attach hooks for every available + enabled integration.
	 *
	 * @return void
	 */
	public function register() {
		foreach ( $this->integrations as $key => $integration ) {
			if ( $integration->is_available() && BVEV_Settings::integration_enabled( $key ) ) {
				$integration->hooks();
			}
		}
	}
}
