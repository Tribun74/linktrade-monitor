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

// The Pro version works on the same tables and settings. As long as it is
// installed, active or not, this uninstall must not touch any data.
if ( file_exists( WP_PLUGIN_DIR . '/linktrade-monitor-pro/linktrade-monitor-pro.php' ) ) {
	return;
}

/**
 * Remove everything the plugin stored for the current site.
 */
function linktrade_uninstall_site() {
	global $wpdb;

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Uninstall cleanup requires dropping custom tables.
	foreach ( array( 'linktrade_links', 'linktrade_log', 'linktrade_checks' ) as $linktrade_table ) {
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS `%1s`', $wpdb->prefix . $linktrade_table ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder
	}
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange

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
		'linktrade_weekly_summary',
		'linktrade_run',
		'linktrade_last_run',
		'linktrade_unreadable',
		'linktrade_schedule_error',
		'linktrade_baseline_run',
		'linktrade_columns',
	);

	foreach ( $linktrade_options as $linktrade_option ) {
		delete_option( $linktrade_option );
	}

	wp_clear_scheduled_hook( 'linktrade_check_links' );
	wp_clear_scheduled_hook( 'linktrade_check_reminders' );
	wp_clear_scheduled_hook( 'linktrade_continue_run' );
	delete_transient( 'linktrade_run_lock' );
	delete_transient( 'linktrade_attention_count' );
}

if ( is_multisite() ) {
	// Every site of the network has its own tables and settings.
	$linktrade_sites = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);
	foreach ( $linktrade_sites as $linktrade_site_id ) {
		switch_to_blog( $linktrade_site_id );
		linktrade_uninstall_site();
		restore_current_blog();
	}
} else {
	linktrade_uninstall_site();
}
