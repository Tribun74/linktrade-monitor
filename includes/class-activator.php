<?php
/**
 * Plugin Activation
 *
 * @package Linktrade_Monitor
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Linktrade_Activator
 */
class Linktrade_Activator {

    /**
     * Activate the plugin
     */
    public static function activate() {
        // Check if this is a fresh install or upgrade
        $current_version = get_option( 'linktrade_version', '0.0.0' );

        if ( version_compare( $current_version, LINKTRADE_VERSION, '<' ) ) {
            self::create_tables();
            self::run_migrations( $current_version );
        }

        self::set_default_options();
        self::schedule_crons();

        // Save version
        update_option( 'linktrade_version', LINKTRADE_VERSION );

        // Save install date for 14-day notice (only on first install).
        if ( ! get_option( 'linktrade_install_date' ) ) {
            update_option( 'linktrade_install_date', time() );
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Main links table
        $table_links = $wpdb->prefix . 'linktrade_links';
        $sql_links = "CREATE TABLE $table_links (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            /* Partner basic data */
            partner_name VARCHAR(255) NOT NULL,
            partner_contact VARCHAR(255),
            partner_website VARCHAR(255),

            /* Categorization */
            category ENUM('exchange', 'paid', 'free') NOT NULL DEFAULT 'exchange',

            /* URLs - Incoming link */
            partner_url TEXT NOT NULL,
            target_url TEXT NOT NULL,
            anchor_text VARCHAR(255),

            /* URLs - Reciprocal link (for exchanges) */
            backlink_url TEXT,
            backlink_target TEXT,
            backlink_anchor VARCHAR(255),

            /* Status incoming link */
            status ENUM('online', 'warning', 'offline', 'unchecked') DEFAULT 'unchecked',
            http_code SMALLINT,
            is_nofollow TINYINT(1) DEFAULT 0,
            is_noindex TINYINT(1) DEFAULT 0,
            is_sponsored TINYINT(1) DEFAULT 0,
            redirect_url TEXT,
            last_check DATETIME,

            /* Status reciprocal link */
            backlink_status ENUM('online', 'warning', 'offline', 'unchecked', 'not_applicable') DEFAULT 'not_applicable',
            backlink_http_code SMALLINT,
            backlink_is_nofollow TINYINT(1) DEFAULT 0,
            backlink_last_check DATETIME,
            fairness_score TINYINT DEFAULT 100,

            /* Partner metrics */
            domain_rating TINYINT UNSIGNED DEFAULT 0,
            my_domain_rating TINYINT UNSIGNED DEFAULT 0,
            domain_authority TINYINT UNSIGNED DEFAULT 0,
            monthly_traffic INT UNSIGNED DEFAULT 0,
            quality_score TINYINT UNSIGNED DEFAULT 0,
            metrics_updated DATETIME,

            /* Relevance */
            niche VARCHAR(100),
            relevance_score TINYINT UNSIGNED DEFAULT 50,

            /* Duration & Cost */
            start_date DATE,
            end_date DATE,
            price DECIMAL(10,2) DEFAULT 0,
            price_period ENUM('once', 'monthly', 'yearly') DEFAULT 'once',

            /* Expiration management */
            reminder_sent TINYINT(1) DEFAULT 0,
            reminder_sent_date DATETIME,
            auto_renew TINYINT(1) DEFAULT 0,

            /* Contact */
            last_contact_date DATE,
            contact_count INT UNSIGNED DEFAULT 0,

            /* Notes */
            notes TEXT,

            /* Indexes */
            INDEX idx_status (status),
            INDEX idx_backlink_status (backlink_status),
            INDEX idx_category (category),
            INDEX idx_end_date (end_date),
            INDEX idx_last_check (last_check),
            INDEX idx_quality_score (quality_score),
            INDEX idx_fairness_score (fairness_score),
            INDEX idx_domain_rating (domain_rating)
        ) $charset_collate;";

        // Change log
        $table_log = $wpdb->prefix . 'linktrade_log';
        $sql_log = "CREATE TABLE $table_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            link_id BIGINT UNSIGNED NOT NULL,
            action VARCHAR(50) NOT NULL,
            old_value TEXT,
            new_value TEXT,
            user_id BIGINT UNSIGNED,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

            INDEX idx_link_id (link_id),
            INDEX idx_action (action)
        ) $charset_collate;";

        // Check history
        $table_checks = $wpdb->prefix . 'linktrade_checks';
        $sql_checks = "CREATE TABLE $table_checks (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            link_id BIGINT UNSIGNED NOT NULL,
            check_type ENUM('incoming', 'outgoing') DEFAULT 'incoming',
            checked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            http_code SMALLINT,
            response_time INT,
            is_nofollow TINYINT(1),
            is_noindex TINYINT(1),
            is_sponsored TINYINT(1),
            redirect_url TEXT,
            anchor_found VARCHAR(255),
            error_message TEXT,

            INDEX idx_link_checked (link_id, checked_at),
            INDEX idx_check_type (check_type)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        // Check if tables exist before running dbDelta
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table creation check.
        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_links ) ) !== $table_links ) {
            dbDelta( $sql_links );
        }
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table creation check.
        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_log ) ) !== $table_log ) {
            dbDelta( $sql_log );
        }
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table creation check.
        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_checks ) ) !== $table_checks ) {
            dbDelta( $sql_checks );
        }
    }

    /**
     * Set default options
     */
    private static function set_default_options() {
        $defaults = array(
            'linktrade_check_frequency'      => 'monthly', // once a month
            'linktrade_email_notifications'  => true,
            'linktrade_notification_email'   => get_option( 'admin_email' ),
            'linktrade_batch_size'           => 50,
            'linktrade_request_delay'        => 3000,
            'linktrade_reminder_days'        => 14,
            'linktrade_reminder_enabled'     => true,
            'linktrade_fairness_alert'       => true,
            'linktrade_fairness_threshold'   => 50,
        );

        foreach ( $defaults as $key => $value ) {
            if ( get_option( $key ) === false ) {
                update_option( $key, $value );
            }
        }
    }

    /**
     * Schedule cron jobs - Monthly check (once a month)
     */
    private static function schedule_crons() {
        // Monthly link check (once a month)
        if ( ! wp_next_scheduled( 'linktrade_check_links' ) ) {
            wp_schedule_event( time(), 'monthly', 'linktrade_check_links' );
        }

        // Daily reminder check
        if ( ! wp_next_scheduled( 'linktrade_check_reminders' ) ) {
            wp_schedule_event( time(), 'daily', 'linktrade_check_reminders' );
        }
    }

    /**
     * Run database migrations for upgrades
     *
     * @param string $from_version Previous version.
     */
    private static function run_migrations( $from_version ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'linktrade_links';

        // Migration for v1.1.1: Add my_domain_rating column
        if ( version_compare( $from_version, '1.1.1', '<' ) ) {
            // Check if column exists.
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- One-time migration, table name is safe.
            $column_exists = $wpdb->get_results(
                $wpdb->prepare(
                    'SHOW COLUMNS FROM `' . esc_sql( $table_name ) . '` LIKE %s',
                    'my_domain_rating'
                )
            );
            // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

            if ( empty( $column_exists ) ) {
                // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- One-time migration, table name is safe.
                $wpdb->query( 'ALTER TABLE `' . esc_sql( $table_name ) . '` ADD COLUMN `my_domain_rating` TINYINT UNSIGNED DEFAULT 0 AFTER `domain_rating`' );
                // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
            }
        }
    }
}
