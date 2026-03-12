<?php
/**
 * Plugin Deactivation
 *
 * @package Linktrade_Monitor
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Linktrade_Deactivator
 */
class Linktrade_Deactivator {

    /**
     * Deactivate the plugin
     */
    public static function deactivate() {
        // Clear scheduled cron events
        wp_clear_scheduled_hook( 'linktrade_check_links' );
        wp_clear_scheduled_hook( 'linktrade_check_reminders' );
    }
}
