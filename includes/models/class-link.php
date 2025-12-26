<?php
/**
 * Link Model
 *
 * @package Linktrade_Monitor
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Linktrade_Link {

    /**
     * Tabellen-Name
     */
    private static function get_table() {
        global $wpdb;
        return $wpdb->prefix . 'linktrade_links';
    }

    /**
     * Alle Links holen
     */
    public static function get_all($args = []) {
        global $wpdb;
        $table = self::get_table();

        $defaults = [
            'category' => '',
            'status' => '',
            'search' => '',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 0,
            'offset' => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $where = [];
        $params = [];

        if (!empty($args['category'])) {
            $where[] = 'category = %s';
            $params[] = $args['category'];
        }

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $params[] = $args['status'];
        }

        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = '(partner_name LIKE %s OR partner_url LIKE %s OR target_url LIKE %s)';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $sql = "SELECT * FROM $table";

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= sprintf(' ORDER BY %s %s',
            sanitize_sql_orderby($args['orderby']),
            $args['order'] === 'ASC' ? 'ASC' : 'DESC'
        );

        if ($args['limit'] > 0) {
            $sql .= $wpdb->prepare(' LIMIT %d OFFSET %d', $args['limit'], $args['offset']);
        }

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Einzelnen Link holen
     */
    public static function get($id) {
        global $wpdb;
        $table = self::get_table();

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    /**
     * Link erstellen
     */
    public static function create($data) {
        global $wpdb;
        $table = self::get_table();

        $data = self::sanitize_data($data);
        $data['status'] = 'unchecked';

        $result = $wpdb->insert($table, $data);

        if ($result) {
            self::log($wpdb->insert_id, 'created');
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Link aktualisieren
     */
    public static function update($id, $data) {
        global $wpdb;
        $table = self::get_table();

        $data = self::sanitize_data($data);

        $result = $wpdb->update($table, $data, ['id' => $id]);

        if ($result !== false) {
            self::log($id, 'updated');
            return true;
        }

        return false;
    }

    /**
     * Link löschen
     */
    public static function delete($id) {
        global $wpdb;
        $table = self::get_table();

        self::log($id, 'deleted');

        return $wpdb->delete($table, ['id' => $id]);
    }

    /**
     * Daten sanitizen
     */
    private static function sanitize_data($data) {
        return [
            'partner_name' => sanitize_text_field($data['partner_name'] ?? ''),
            'partner_contact' => sanitize_text_field($data['partner_contact'] ?? ''),
            'category' => sanitize_key($data['category'] ?? 'tausch'),
            'anchor_text' => sanitize_text_field($data['anchor_text'] ?? ''),
            'partner_url' => esc_url_raw($data['partner_url'] ?? ''),
            'target_url' => esc_url_raw($data['target_url'] ?? ''),
            'backlink_url' => esc_url_raw($data['backlink_url'] ?? ''),
            'backlink_target' => esc_url_raw($data['backlink_target'] ?? ''),
            'start_date' => sanitize_text_field($data['start_date'] ?? '') ?: null,
            'end_date' => sanitize_text_field($data['end_date'] ?? '') ?: null,
            'price' => floatval($data['price'] ?? 0),
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
        ];
    }

    /**
     * Änderung loggen
     */
    private static function log($link_id, $action, $old_value = null, $new_value = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'linktrade_log';

        $wpdb->insert($table, [
            'link_id' => $link_id,
            'action' => $action,
            'old_value' => $old_value ? wp_json_encode($old_value) : null,
            'new_value' => $new_value ? wp_json_encode($new_value) : null,
            'user_id' => get_current_user_id(),
        ]);
    }

    /**
     * Links mit ablaufendem Datum holen
     */
    public static function get_expiring($days = 30) {
        global $wpdb;
        $table = self::get_table();

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table
             WHERE end_date IS NOT NULL
             AND end_date <= %s
             AND end_date >= CURDATE()
             ORDER BY end_date ASC",
            wp_date('Y-m-d', strtotime("+$days days"))
        ));
    }

    /**
     * Problemhafte Links holen
     */
    public static function get_problems() {
        global $wpdb;
        $table = self::get_table();

        return $wpdb->get_results(
            "SELECT * FROM $table
             WHERE status IN ('warning', 'offline')
             ORDER BY last_check DESC"
        );
    }

    /**
     * Links für nächsten Check holen
     */
    public static function get_for_check($limit = 50) {
        global $wpdb;
        $table = self::get_table();

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table
             ORDER BY last_check ASC, created_at ASC
             LIMIT %d",
            $limit
        ));
    }

    /**
     * Status aktualisieren
     */
    public static function update_status($id, $status_data) {
        global $wpdb;
        $table = self::get_table();

        return $wpdb->update($table, [
            'status' => $status_data['status'],
            'http_code' => $status_data['http_code'],
            'is_nofollow' => $status_data['is_nofollow'],
            'is_noindex' => $status_data['is_noindex'],
            'is_sponsored' => $status_data['is_sponsored'] ?? 0,
            'redirect_url' => $status_data['redirect_url'],
            'last_check' => current_time('mysql'),
        ], ['id' => $id]);
    }

    /**
     * Anzahl Links zählen
     */
    public static function count($args = []) {
        global $wpdb;
        $table = self::get_table();

        $where = [];
        $params = [];

        if (!empty($args['category'])) {
            $where[] = 'category = %s';
            $params[] = $args['category'];
        }

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $params[] = $args['status'];
        }

        $sql = "SELECT COUNT(*) FROM $table";

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return (int) $wpdb->get_var($sql);
    }
}
