<?php
/**
 * Plugin-Deaktivierung
 *
 * @package Linktrade_Monitor
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Linktrade_Deactivator {

    /**
     * Deaktivierung durchführen
     */
    public static function deactivate() {
        // Geplante Cron-Jobs entfernen
        wp_clear_scheduled_hook('linktrade_check_links');
        wp_clear_scheduled_hook('linktrade_send_notifications');

        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
