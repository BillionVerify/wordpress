<?php
/**
 * Plugin Name:       BillionVerify Email Validator
 * Plugin URI:        https://billionverify.com/wordpress
 * Description:       Real-time email verification for your WordPress forms. Blocks invalid, disposable and risky email addresses on registration, comments, WooCommerce checkout, Contact Form 7, WPForms, Gravity Forms, Elementor and Fluent Forms using the BillionVerify API.
 * Version:           1.0.0
 * Requires at least: 4.7
 * Requires PHP:      7.0
 * Author:            BillionVerify
 * Author URI:        https://billionverify.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       billionverify-email-validator
 * Domain Path:       /languages
 *
 * @package BillionVerify
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BVEV_VERSION', '1.0.0' );
define( 'BVEV_PLUGIN_FILE', __FILE__ );
define( 'BVEV_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BVEV_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BVEV_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Production API base URL. Override with the BVEV_API_BASE constant or the
 * "bvev_api_base" filter for staging environments.
 */
if ( ! defined( 'BVEV_API_BASE' ) ) {
	define( 'BVEV_API_BASE', 'https://api.billionverify.com' );
}

require_once BVEV_PLUGIN_DIR . 'includes/class-bvev-settings.php';
require_once BVEV_PLUGIN_DIR . 'includes/class-bvev-api-client.php';
require_once BVEV_PLUGIN_DIR . 'includes/class-bvev-verifier.php';
require_once BVEV_PLUGIN_DIR . 'includes/integrations/class-bvev-integration-base.php';
require_once BVEV_PLUGIN_DIR . 'includes/integrations/class-bvev-integration-manager.php';
require_once BVEV_PLUGIN_DIR . 'includes/class-bvev-plugin.php';

if ( is_admin() ) {
	require_once BVEV_PLUGIN_DIR . 'includes/admin/class-bvev-admin.php';
}

/**
 * Boot the plugin.
 *
 * @return BVEV_Plugin
 */
function bvev() {
	return BVEV_Plugin::instance();
}

add_action( 'plugins_loaded', 'bvev', 5 );

register_activation_hook( __FILE__, array( 'BVEV_Settings', 'install_defaults' ) );
