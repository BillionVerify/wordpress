<?php
/**
 * Settings storage and defaults.
 *
 * @package BillionVerify
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads and writes the single options row used by the plugin.
 */
class BVEV_Settings {

	const OPTION_KEY = 'bvev_settings';

	/**
	 * Cached options for the current request.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Default settings. New users start with "invalid" blocking only and a
	 * fail-open posture, matching the conservative behaviour customers expect.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'api_key'           => '',
			'check_smtp'        => 1,
			'timeout'           => 8,
			'cache_ttl'         => 21600, // 6 hours.
			'fail_open'         => 1,      // Accept email when the API is unreachable / out of credits.
			'block_message'     => __( 'Please enter a valid, reachable email address.', 'billionverify-email-validator' ),
			// Which verification statuses should block the submission.
			'block_statuses'    => array(
				'invalid'    => 1,
				'disposable' => 0,
				'catchall'   => 0,
				'role'       => 0,
				'risky'      => 0,
				'unknown'    => 0,
			),
			// Which form integrations are active.
			'integrations'      => array(
				'wp_registration'  => 1,
				'wp_comment'       => 0,
				'wp_lost_password' => 0,
				'woocommerce'      => 1,
				'cf7'              => 1,
				'wpforms'          => 1,
				'gravityforms'     => 1,
				'elementor'        => 1,
				'fluentforms'      => 1,
			),
		);
	}

	/**
	 * Full settings array merged over defaults.
	 *
	 * @return array
	 */
	public static function all() {
		if ( null === self::$cache ) {
			$stored = get_option( self::OPTION_KEY, array() );
			if ( ! is_array( $stored ) ) {
				$stored = array();
			}
			$defaults = self::defaults();
			$merged   = array_merge( $defaults, $stored );
			// Deep-merge the nested associative arrays so newly added keys appear.
			$merged['block_statuses'] = array_merge( $defaults['block_statuses'], (array) ( $stored['block_statuses'] ?? array() ) );
			$merged['integrations']   = array_merge( $defaults['integrations'], (array) ( $stored['integrations'] ?? array() ) );
			self::$cache              = $merged;
		}
		return self::$cache;
	}

	/**
	 * Get a single top-level setting.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback value.
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		$all = self::all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : $default;
	}

	/**
	 * Persist a full settings array (already sanitized).
	 *
	 * @param array $settings Settings to store.
	 * @return void
	 */
	public static function update( array $settings ) {
		update_option( self::OPTION_KEY, $settings );
		self::$cache = null;
	}

	/**
	 * Whether a status is configured to block submissions.
	 *
	 * @param string $status Verification status.
	 * @return bool
	 */
	public static function blocks_status( $status ) {
		$statuses = self::get( 'block_statuses', array() );
		return ! empty( $statuses[ $status ] );
	}

	/**
	 * Whether an integration is enabled.
	 *
	 * @param string $key Integration key.
	 * @return bool
	 */
	public static function integration_enabled( $key ) {
		$integrations = self::get( 'integrations', array() );
		return ! empty( $integrations[ $key ] );
	}

	/**
	 * Seed defaults on activation without clobbering an existing config.
	 *
	 * @return void
	 */
	public static function install_defaults() {
		if ( false === get_option( self::OPTION_KEY, false ) ) {
			add_option( self::OPTION_KEY, self::defaults() );
		}
	}
}
