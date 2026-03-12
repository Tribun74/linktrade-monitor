<?php
/**
 * Link Model
 *
 * @package Linktrade_Monitor
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Linktrade_Link
 */
class Linktrade_Link {

    /**
     * Link ID
     *
     * @var int
     */
    public $id;

    /**
     * Partner name
     *
     * @var string
     */
    public $partner_name;

    /**
     * Partner contact email
     *
     * @var string
     */
    public $partner_contact;

    /**
     * Category (exchange, paid, free)
     *
     * @var string
     */
    public $category;

    /**
     * Partner URL (page with backlink)
     *
     * @var string
     */
    public $partner_url;

    /**
     * Target URL (our page being linked)
     *
     * @var string
     */
    public $target_url;

    /**
     * Anchor text
     *
     * @var string
     */
    public $anchor_text;

    /**
     * Status (online, warning, offline, unchecked)
     *
     * @var string
     */
    public $status = 'unchecked';

    /**
     * HTTP response code
     *
     * @var int
     */
    public $http_code;

    /**
     * Is nofollow
     *
     * @var bool
     */
    public $is_nofollow = false;

    /**
     * Is noindex
     *
     * @var bool
     */
    public $is_noindex = false;

    /**
     * Domain rating
     *
     * @var int
     */
    public $domain_rating = 0;

    /**
     * Fairness score
     *
     * @var int
     */
    public $fairness_score = 100;

    /**
     * Last check timestamp
     *
     * @var string
     */
    public $last_check;

    /**
     * Notes
     *
     * @var string
     */
    public $notes;

    /**
     * Get all links
     *
     * @return array Array of links.
     */
    public static function get_all() {
        global $wpdb;
        $table = $wpdb->prefix . 'linktrade_links';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom table, safe table name.
        return $wpdb->get_results( 'SELECT * FROM `' . esc_sql( $table ) . '` ORDER BY created_at DESC' );
    }

    /**
     * Get link by ID
     *
     * @param int $id Link ID.
     * @return object|null Link object or null.
     */
    public static function get( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'linktrade_links';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom table, safe table name.
        return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM `' . esc_sql( $table ) . '` WHERE id = %d', $id ) );
    }

    /**
     * Get links count
     *
     * @return int Number of links.
     */
    public static function count() {
        global $wpdb;
        $table = $wpdb->prefix . 'linktrade_links';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom table, safe table name.
        return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $table ) . '`' );
    }
}
