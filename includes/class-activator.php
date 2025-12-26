<?php
/**
 * Plugin-Aktivierung
 *
 * @package Linktrade_Monitor
 * @since 1.0.0
 * @updated 1.1.0 - Erweiterte Felder für Partner-Bewertung, Gegenseitigkeit, Kontakt-Historie
 */

if (!defined('ABSPATH')) {
    exit;
}

class Linktrade_Activator {

    /**
     * Aktivierung durchführen
     */
    public static function activate() {
        self::create_tables();
        self::set_default_options();
        self::schedule_crons();

        // Version speichern
        update_option('linktrade_version', LINKTRADE_VERSION);

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Datenbank-Tabellen erstellen
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Haupt-Linktabelle (erweitert)
        $table_links = $wpdb->prefix . 'linktrade_links';
        $sql_links = "CREATE TABLE $table_links (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            /* Partner-Grunddaten */
            partner_name VARCHAR(255) NOT NULL,
            partner_contact VARCHAR(255),
            partner_website VARCHAR(255),

            /* Kategorisierung */
            category ENUM('tausch', 'kauf', 'kostenlos') NOT NULL DEFAULT 'tausch',

            /* URLs - Eingehender Link */
            partner_url TEXT NOT NULL,
            target_url TEXT NOT NULL,
            anchor_text VARCHAR(255),

            /* URLs - Gegenlink (bei Tausch) */
            backlink_url TEXT,
            backlink_target TEXT,
            backlink_anchor VARCHAR(255),

            /* Status eingehender Link */
            status ENUM('online', 'warning', 'offline', 'unchecked') DEFAULT 'unchecked',
            http_code SMALLINT,
            is_nofollow TINYINT(1) DEFAULT 0,
            is_noindex TINYINT(1) DEFAULT 0,
            is_sponsored TINYINT(1) DEFAULT 0,
            redirect_url TEXT,
            last_check DATETIME,

            /* Status Gegenlink (Gegenseitigkeits-Tracker) */
            backlink_status ENUM('online', 'warning', 'offline', 'unchecked', 'not_applicable') DEFAULT 'not_applicable',
            backlink_http_code SMALLINT,
            backlink_is_nofollow TINYINT(1) DEFAULT 0,
            backlink_last_check DATETIME,
            fairness_score TINYINT DEFAULT 100,

            /* Partner-Bewertung */
            domain_rating TINYINT UNSIGNED DEFAULT 0,
            domain_authority TINYINT UNSIGNED DEFAULT 0,
            monthly_traffic INT UNSIGNED DEFAULT 0,
            trust_flow TINYINT UNSIGNED DEFAULT 0,
            citation_flow TINYINT UNSIGNED DEFAULT 0,
            spam_score TINYINT UNSIGNED DEFAULT 0,
            quality_score TINYINT UNSIGNED DEFAULT 0,
            metrics_updated DATETIME,

            /* Themenrelevanz */
            niche VARCHAR(100),
            relevance_score TINYINT UNSIGNED DEFAULT 50,

            /* Laufzeit & Kosten */
            start_date DATE,
            end_date DATE,
            price DECIMAL(10,2) DEFAULT 0,
            price_period ENUM('once', 'monthly', 'yearly') DEFAULT 'once',
            value_score DECIMAL(10,2) DEFAULT 0,

            /* Verfalls-Management */
            reminder_sent TINYINT(1) DEFAULT 0,
            reminder_sent_date DATETIME,
            auto_renew TINYINT(1) DEFAULT 0,

            /* Kontakt */
            last_contact_date DATE,
            contact_count INT UNSIGNED DEFAULT 0,

            /* Notizen */
            notes TEXT,

            /* Indizes */
            INDEX idx_status (status),
            INDEX idx_backlink_status (backlink_status),
            INDEX idx_category (category),
            INDEX idx_end_date (end_date),
            INDEX idx_last_check (last_check),
            INDEX idx_quality_score (quality_score),
            INDEX idx_fairness_score (fairness_score),
            INDEX idx_domain_rating (domain_rating)
        ) $charset_collate;";

        // Änderungs-Log
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

        // Check-Historie
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

        // Kontakt-Historie (NEU)
        $table_contacts = $wpdb->prefix . 'linktrade_contacts';
        $sql_contacts = "CREATE TABLE $table_contacts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            link_id BIGINT UNSIGNED NOT NULL,
            contact_type ENUM('email', 'phone', 'meeting', 'other') DEFAULT 'email',
            direction ENUM('outgoing', 'incoming') DEFAULT 'outgoing',
            subject VARCHAR(255),
            content TEXT,
            contact_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            user_id BIGINT UNSIGNED,
            reminder_date DATE,
            reminder_done TINYINT(1) DEFAULT 0,

            INDEX idx_link_id (link_id),
            INDEX idx_contact_date (contact_date),
            INDEX idx_reminder (reminder_date, reminder_done)
        ) $charset_collate;";

        // Anchor-Text Statistik (NEU)
        $table_anchors = $wpdb->prefix . 'linktrade_anchors';
        $sql_anchors = "CREATE TABLE $table_anchors (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            anchor_text VARCHAR(255) NOT NULL,
            anchor_type ENUM('brand', 'exact', 'partial', 'generic', 'naked_url', 'other') DEFAULT 'other',
            usage_count INT UNSIGNED DEFAULT 1,
            first_used DATE,
            last_used DATE,

            UNIQUE KEY unique_anchor (anchor_text),
            INDEX idx_anchor_type (anchor_type),
            INDEX idx_usage_count (usage_count)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_links);
        dbDelta($sql_log);
        dbDelta($sql_checks);
        dbDelta($sql_contacts);
        dbDelta($sql_anchors);
    }

    /**
     * Standard-Optionen setzen
     */
    private static function set_default_options() {
        $defaults = [
            'linktrade_check_frequency' => 'weekly',
            'linktrade_email_notifications' => true,
            'linktrade_notification_email' => get_option('admin_email'),
            'linktrade_batch_size' => 50,
            'linktrade_request_delay' => 3000,
            // Verfalls-Erinnerungen
            'linktrade_reminder_days' => 14,
            'linktrade_reminder_enabled' => true,
            // Fairness-Warnung
            'linktrade_fairness_alert' => true,
            'linktrade_fairness_threshold' => 50,
            // Anchor-Warnungen
            'linktrade_anchor_warning_enabled' => true,
            'linktrade_anchor_exact_threshold' => 30,
            // Qualitäts-Score Gewichtung
            'linktrade_weight_dr' => 40,
            'linktrade_weight_traffic' => 30,
            'linktrade_weight_relevance' => 30,
        ];

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                update_option($key, $value);
            }
        }
    }

    /**
     * Cron-Jobs einrichten
     */
    private static function schedule_crons() {
        // Wöchentlicher Link-Check
        if (!wp_next_scheduled('linktrade_check_links')) {
            wp_schedule_event(time(), 'weekly', 'linktrade_check_links');
        }

        // Tägliche Erinnerungs-Prüfung
        if (!wp_next_scheduled('linktrade_check_reminders')) {
            wp_schedule_event(time(), 'daily', 'linktrade_check_reminders');
        }

        // Tägliche Fairness-Prüfung
        if (!wp_next_scheduled('linktrade_check_fairness')) {
            wp_schedule_event(time(), 'daily', 'linktrade_check_fairness');
        }
    }
}
