<?php
/**
 * Uninstall - Wird bei Deinstallation des Plugins ausgeführt
 *
 * @package Linktrade_Monitor
 * @since 1.0.0
 */

// Sicherheitscheck
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Tabellen löschen
$tables = [
    $wpdb->prefix . 'linktrade_links',
    $wpdb->prefix . 'linktrade_log',
    $wpdb->prefix . 'linktrade_checks',
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

// Optionen löschen
$options = [
    'linktrade_version',
    'linktrade_check_frequency',
    'linktrade_email_notifications',
    'linktrade_notification_email',
    'linktrade_batch_size',
    'linktrade_request_delay',
];

foreach ($options as $option) {
    delete_option($option);
}

// Cron-Jobs entfernen
wp_clear_scheduled_hook('linktrade_check_links');
wp_clear_scheduled_hook('linktrade_send_notifications');
