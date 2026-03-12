<?php
/**
 * Uninstall Linktrade Monitor
 *
 * Removes all plugin data when uninstalled.
 *
 * @package Linktrade_Monitor
 * @since 1.0.0
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Delete database tables.
$linktrade_tables = array(
	$wpdb->prefix . 'linktrade_links',
	$wpdb->prefix . 'linktrade_log',
	$wpdb->prefix . 'linktrade_checks',
);

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Uninstall cleanup requires dropping custom tables.
foreach ( $linktrade_tables as $linktrade_table ) {
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS `%1s`', $linktrade_table ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange

// Delete options.
$linktrade_options = array(
	'linktrade_version',
	'linktrade_check_frequency',
	'linktrade_email_notifications',
	'linktrade_notification_email',
	'linktrade_batch_size',
	'linktrade_request_delay',
	'linktrade_reminder_days',
	'linktrade_reminder_enabled',
	'linktrade_fairness_alert',
	'linktrade_fairness_threshold',
	'linktrade_language',
	'linktrade_install_date',
	'linktrade_notice_dismissed',
);

foreach ( $linktrade_options as $linktrade_option ) {
	delete_option( $linktrade_option );
}

// Clear scheduled cron events.
wp_clear_scheduled_hook( 'linktrade_check_links' );
wp_clear_scheduled_hook( 'linktrade_check_reminders' );
