<?php
/**
 * Main Plugin Class
 *
 * @package Linktrade_Monitor
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Linktrade
 */
class Linktrade {

	/**
	 * Admin instance
	 *
	 * @var Linktrade_Admin
	 */
	private $admin;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->load_dependencies();
	}

	/**
	 * Load dependencies
	 */
	private function load_dependencies() {
		// Models.
		require_once LINKTRADE_PLUGIN_DIR . 'includes/models/class-link.php';

		// Admin.
		if ( is_admin() ) {
			require_once LINKTRADE_PLUGIN_DIR . 'includes/admin/class-admin.php';
			$this->admin = new Linktrade_Admin();
		}
	}

	/**
	 * Run the plugin
	 */
	public function run() {
		// Admin hooks.
		if ( is_admin() && $this->admin ) {
			add_action( 'admin_menu', array( $this->admin, 'add_admin_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_assets' ) );
			add_action( 'wp_ajax_linktrade_save_link', array( $this->admin, 'ajax_save_link' ) );
			add_action( 'wp_ajax_linktrade_delete_link', array( $this->admin, 'ajax_delete_link' ) );
			add_action( 'wp_ajax_linktrade_get_links', array( $this->admin, 'ajax_get_links' ) );
			add_action( 'wp_ajax_linktrade_get_link', array( $this->admin, 'ajax_get_link' ) );
			add_action( 'wp_ajax_linktrade_export_csv', array( $this->admin, 'ajax_export_csv' ) );
			add_action( 'wp_ajax_linktrade_import_csv', array( $this->admin, 'ajax_import_csv' ) );
		}

		// Cron hooks.
		add_action( 'linktrade_check_links', array( $this, 'cron_check_links' ) );
		add_action( 'linktrade_check_reminders', array( $this, 'cron_check_reminders' ) );
	}

	/**
	 * Cron: Check all links (biweekly)
	 */
	public function cron_check_links() {
		require_once LINKTRADE_PLUGIN_DIR . 'includes/checker/class-link-checker.php';

		global $wpdb;
		$table_name = $wpdb->prefix . 'linktrade_links';
		$batch_size = absint( get_option( 'linktrade_batch_size', 50 ) );
		$delay      = absint( get_option( 'linktrade_request_delay', 3000 ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cron job requires fresh data from custom table.
		$links = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM `' . esc_sql( $table_name ) . '` ORDER BY last_check ASC, created_at ASC LIMIT %d',
				$batch_size
			)
		);

		if ( empty( $links ) ) {
			return;
		}

		$checker = new Linktrade_Link_Checker();

		foreach ( $links as $link ) {
			$result = $checker->check( $link->partner_url, $link->target_url );

			$update_data = array(
				'status'       => $result['status'],
				'http_code'    => $result['http_code'],
				'is_nofollow'  => $result['is_nofollow'],
				'is_noindex'   => $result['is_noindex'],
				'redirect_url' => $result['redirect_url'],
				'last_check'   => current_time( 'mysql' ),
			);

			// For exchanges, also check reciprocal link.
			if ( 'exchange' === $link->category && ! empty( $link->backlink_url ) ) {
				usleep( $delay * 1000 );
				$backlink_result = $checker->check( $link->backlink_url, $link->backlink_target );

				$update_data['backlink_status']      = $backlink_result['status'];
				$update_data['backlink_http_code']   = $backlink_result['http_code'];
				$update_data['backlink_is_nofollow'] = $backlink_result['is_nofollow'];
				$update_data['backlink_last_check']  = current_time( 'mysql' );

				// Calculate fairness.
				$update_data['fairness_score'] = $this->calculate_fairness(
					$result['status'],
					$backlink_result['status'],
					$result['is_nofollow'],
					$backlink_result['is_nofollow'],
					isset( $link->domain_rating ) ? (int) $link->domain_rating : 0,
					isset( $link->my_domain_rating ) ? (int) $link->my_domain_rating : 0
				);
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Update operation on custom table.
			$wpdb->update(
				$table_name,
				$update_data,
				array( 'id' => absint( $link->id ) ),
				array( '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%d' ),
				array( '%d' )
			);

			usleep( $delay * 1000 );
		}
	}

	/**
	 * Cron: Send expiration reminders
	 */
	public function cron_check_reminders() {
		if ( ! get_option( 'linktrade_email_notifications' ) ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'linktrade_links';
		$days       = absint( get_option( 'linktrade_reminder_days', 14 ) );
		$email      = sanitize_email( get_option( 'linktrade_notification_email', get_option( 'admin_email' ) ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cron job requires fresh data from custom table.
		$expiring = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM `' . esc_sql( $table_name ) . '`
				 WHERE end_date IS NOT NULL
				 AND end_date <= %s
				 AND end_date >= CURDATE()
				 AND reminder_sent = 0',
				wp_date( 'Y-m-d', strtotime( '+' . $days . ' days' ) )
			)
		);

		if ( empty( $expiring ) ) {
			return;
		}

		/* translators: %d: number of expiring links */
		$subject = sprintf( __( '[Linktrade Monitor] %d links expiring soon', 'linktrade-monitor' ), count( $expiring ) );

		/* translators: %d: number of days */
		$message = sprintf( __( 'The following links will expire in the next %d days:', 'linktrade-monitor' ), $days ) . "\n\n";

		foreach ( $expiring as $link ) {
			$days_left = ceil( ( strtotime( $link->end_date ) - time() ) / 86400 );
			/* translators: 1: partner name, 2: end date, 3: days remaining */
			$message .= sprintf( __( '- %1$s: %2$s (%3$d days remaining)', 'linktrade-monitor' ), $link->partner_name, $link->end_date, $days_left ) . "\n";

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Update operation on custom table.
			$wpdb->update(
				$table_name,
				array(
					'reminder_sent'      => 1,
					'reminder_sent_date' => current_time( 'mysql' ),
				),
				array( 'id' => absint( $link->id ) ),
				array( '%d', '%s' ),
				array( '%d' )
			);
		}

		wp_mail( $email, $subject, $message );
	}

	/**
	 * Calculate fairness score
	 *
	 * @param string $my_status      My link status (their link to me).
	 * @param string $their_status   Their link status (my link to them).
	 * @param bool   $my_nofollow    My incoming link nofollow.
	 * @param bool   $their_nofollow My outgoing link nofollow.
	 * @param int    $partner_dr     Partner's Domain Rating.
	 * @param int    $my_dr          My Domain Rating.
	 * @return int Fairness score (0-100).
	 */
	private function calculate_fairness( $my_status, $their_status, $my_nofollow, $their_nofollow, $partner_dr = 0, $my_dr = 0 ) {
		// Base fairness from link status.
		if ( 'online' === $my_status && 'offline' === $their_status ) {
			return 0; // Partner removed their link but I still link to them.
		}
		if ( 'offline' === $my_status && 'offline' === $their_status ) {
			return 50; // Both links offline.
		}

		$base_score = 100;

		// Nofollow penalty.
		if ( ! $my_nofollow && $their_nofollow ) {
			$base_score = 60; // I give dofollow, partner gives nofollow.
		}

		// DR comparison adjustment (only if both values are set).
		if ( $partner_dr > 0 && $my_dr > 0 ) {
			$dr_diff = $my_dr - $partner_dr;

			// If my DR is higher, I'm giving more value than I receive.
			if ( $dr_diff > 0 ) {
				// Reduce fairness: -2 points per DR difference, max -40.
				$dr_penalty = min( 40, $dr_diff * 2 );
				$base_score = max( 0, $base_score - $dr_penalty );
			}
		}

		return $base_score;
	}
}
