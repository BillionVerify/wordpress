<?php
/**
 * Uninstall cleanup.
 *
 * @package BillionVerify
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'bvev_settings' );

// Remove cached verification transients.
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_bvev\_v\_%' OR option_name LIKE '\_transient\_timeout\_bvev\_v\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
