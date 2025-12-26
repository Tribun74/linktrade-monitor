<?php
/**
 * Haupt-Plugin-Klasse
 *
 * @package Linktrade_Monitor
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Linktrade {

    /**
     * Admin-Instanz
     */
    private $admin;

    /**
     * Konstruktor
     */
    public function __construct() {
        $this->load_dependencies();
    }

    /**
     * Abhängigkeiten laden
     */
    private function load_dependencies() {
        // Models
        require_once LINKTRADE_PLUGIN_DIR . 'includes/models/class-link.php';

        // Admin
        if (is_admin()) {
            require_once LINKTRADE_PLUGIN_DIR . 'includes/admin/class-admin.php';
            $this->admin = new Linktrade_Admin();
        }
    }

    /**
     * Plugin starten
     */
    public function run() {
        // Admin-Hooks
        if (is_admin() && $this->admin) {
            add_action('admin_menu', [$this->admin, 'add_admin_menu']);
            add_action('admin_enqueue_scripts', [$this->admin, 'enqueue_assets']);
            add_action('wp_ajax_linktrade_save_link', [$this->admin, 'ajax_save_link']);
            add_action('wp_ajax_linktrade_delete_link', [$this->admin, 'ajax_delete_link']);
            add_action('wp_ajax_linktrade_get_links', [$this->admin, 'ajax_get_links']);
            add_action('wp_ajax_linktrade_get_link', [$this->admin, 'ajax_get_link']);
            add_action('wp_ajax_linktrade_check_link', [$this->admin, 'ajax_check_link']);
            add_action('wp_ajax_linktrade_save_contact', [$this->admin, 'ajax_save_contact']);
        }

        // Cron-Hooks
        add_action('linktrade_check_links', [$this, 'cron_check_links']);
        add_action('linktrade_check_reminders', [$this, 'cron_check_reminders']);
        add_action('linktrade_check_fairness', [$this, 'cron_check_fairness']);
    }

    /**
     * Cron: Alle Links prüfen
     */
    public function cron_check_links() {
        require_once LINKTRADE_PLUGIN_DIR . 'includes/checker/class-link-checker.php';

        global $wpdb;
        $table = $wpdb->prefix . 'linktrade_links';
        $batch_size = get_option('linktrade_batch_size', 50);
        $delay = get_option('linktrade_request_delay', 3000);

        $links = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY last_check ASC, created_at ASC LIMIT %d",
            $batch_size
        ));

        $checker = new Linktrade_Link_Checker();

        foreach ($links as $link) {
            $result = $checker->check($link->partner_url, $link->target_url);

            $update_data = [
                'status' => $result['status'],
                'http_code' => $result['http_code'],
                'is_nofollow' => $result['is_nofollow'],
                'is_noindex' => $result['is_noindex'],
                'redirect_url' => $result['redirect_url'],
                'last_check' => current_time('mysql'),
            ];

            // Bei Linktausch auch Gegenlink prüfen
            if ($link->category === 'tausch' && $link->backlink_url) {
                usleep($delay * 1000);
                $backlink_result = $checker->check($link->backlink_url, $link->backlink_target);

                $update_data['backlink_status'] = $backlink_result['status'];
                $update_data['backlink_http_code'] = $backlink_result['http_code'];
                $update_data['backlink_is_nofollow'] = $backlink_result['is_nofollow'];
                $update_data['backlink_last_check'] = current_time('mysql');

                // Fairness berechnen
                $update_data['fairness_score'] = $this->calculate_fairness(
                    $result['status'], $backlink_result['status'],
                    $result['is_nofollow'], $backlink_result['is_nofollow']
                );
            }

            $wpdb->update($table, $update_data, ['id' => $link->id]);

            usleep($delay * 1000);
        }
    }

    /**
     * Cron: Ablauf-Erinnerungen senden
     */
    public function cron_check_reminders() {
        if (!get_option('linktrade_email_notifications')) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'linktrade_links';
        $days = get_option('linktrade_reminder_days', 14);
        $email = get_option('linktrade_notification_email', get_option('admin_email'));

        $expiring = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table
             WHERE end_date IS NOT NULL
             AND end_date <= %s
             AND end_date >= CURDATE()
             AND reminder_sent = 0",
            wp_date('Y-m-d', strtotime("+$days days"))
        ));

        if (empty($expiring)) {
            return;
        }

        $subject = sprintf('[Linktrade Monitor] %d Links laufen bald ab', count($expiring));
        $message = "Folgende Links laufen in den nächsten $days Tagen ab:\n\n";

        foreach ($expiring as $link) {
            $days_left = ceil((strtotime($link->end_date) - time()) / 86400);
            $message .= sprintf("- %s: %s (noch %d Tage)\n", $link->partner_name, $link->end_date, $days_left);

            $wpdb->update($table, [
                'reminder_sent' => 1,
                'reminder_sent_date' => current_time('mysql'),
            ], ['id' => $link->id]);
        }

        wp_mail($email, $subject, $message);
    }

    /**
     * Cron: Fairness-Probleme melden
     */
    public function cron_check_fairness() {
        if (!get_option('linktrade_fairness_alert')) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'linktrade_links';
        $email = get_option('linktrade_notification_email', get_option('admin_email'));

        // Partner hat Link entfernt, meiner ist noch da
        $unfair = $wpdb->get_results(
            "SELECT * FROM $table
             WHERE category = 'tausch'
             AND status = 'online'
             AND backlink_status = 'offline'
             AND last_check >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );

        if (empty($unfair)) {
            return;
        }

        $subject = sprintf('[Linktrade Monitor] ACHTUNG: %d Partner haben Links entfernt!', count($unfair));
        $message = "Folgende Partner haben ihre Links entfernt, während deine noch online sind:\n\n";

        foreach ($unfair as $link) {
            $message .= sprintf("- %s: %s\n  Dein Gegenlink: %s\n\n",
                $link->partner_name,
                $link->partner_url,
                $link->backlink_url
            );
        }

        $message .= "\nBitte prüfe diese Partnerschaften und kontaktiere die Partner.";

        wp_mail($email, $subject, $message);
    }

    /**
     * Fairness-Score berechnen
     */
    private function calculate_fairness($my_status, $their_status, $my_nofollow, $their_nofollow) {
        if ($my_status === 'online' && $their_status === 'offline') return 0;
        if ($my_status === 'offline' && $their_status === 'offline') return 50;
        if (!$my_nofollow && $their_nofollow) return 60;
        return 100;
    }
}
