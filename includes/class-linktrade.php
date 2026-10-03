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

		// Check runner (schedule, history, notifications).
		require_once LINKTRADE_PLUGIN_DIR . 'includes/class-runner.php';

		// Menu counter, dashboard widget, privacy tools.
		require_once LINKTRADE_PLUGIN_DIR . 'includes/class-extras.php';
		Linktrade_Extras::init();

		// Command line.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once LINKTRADE_PLUGIN_DIR . 'includes/class-cli.php';
			WP_CLI::add_command( 'linktrade', 'Linktrade_CLI' );
		}

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
			add_action( 'wp_ajax_linktrade_get_link', array( $this->admin, 'ajax_get_link' ) );
			add_action( 'wp_ajax_linktrade_export_csv', array( $this->admin, 'ajax_export_csv' ) );
			add_action( 'wp_ajax_linktrade_import_csv', array( $this->admin, 'ajax_import_csv' ) );
			add_action( 'wp_ajax_linktrade_check_now', array( $this->admin, 'ajax_check_now' ) );
			add_action( 'wp_ajax_linktrade_get_history', array( $this->admin, 'ajax_get_history' ) );
			add_action( 'wp_ajax_linktrade_get_message', array( $this->admin, 'ajax_get_message' ) );
			add_action( 'wp_ajax_linktrade_find_backlink', array( $this->admin, 'ajax_find_backlink' ) );
			add_action( 'wp_ajax_linktrade_bulk_delete', array( $this->admin, 'ajax_bulk_delete' ) );
			add_action( 'wp_ajax_linktrade_test_mail', array( $this->admin, 'ajax_test_mail' ) );
			add_action( 'wp_ajax_linktrade_scan_outgoing', array( $this->admin, 'ajax_scan_outgoing' ) );
			add_action( 'admin_init', array( 'Linktrade_Runner', 'ensure_schedule' ) );
		}

		// Scheduled work.
		add_action( Linktrade_Runner::HOOK, array( 'Linktrade_Runner', 'start_full_run' ) );
		add_action( Linktrade_Runner::CONTINUE_HOOK, array( 'Linktrade_Runner', 'process' ) );
		add_action( 'linktrade_check_reminders', array( 'Linktrade_Runner', 'send_reminders' ) );

		// Site Health: tell the owner when the automatic check cannot run.
		add_filter( 'site_status_tests', array( 'Linktrade_Runner', 'register_site_health' ) );
	}

	/**
	 * Calculate the fairness score of an exchange.
	 *
	 * The one place this is computed: the scheduled check, the save handler
	 * and the upgrade routine all call it.
	 *
	 * A side that has never been read ("unchecked") or has no reciprocal link
	 * on record ("not_applicable") is unknown, not removed. Unknown is never
	 * scored against anybody, so the result stays neutral.
	 *
	 * @param string $incoming     Status of the partner's link to us.
	 * @param string $outgoing     Status of our link to the partner.
	 * @param bool   $in_nofollow  Partner's link is nofollow.
	 * @param bool   $out_nofollow Our link is nofollow.
	 * @param int    $partner_dr   Partner's Domain Rating.
	 * @param int    $my_dr        Our Domain Rating.
	 * @return int Fairness score (0-100).
	 */
	public static function fairness( $incoming, $outgoing, $in_nofollow, $out_nofollow, $partner_dr = 0, $my_dr = 0 ) {
		$known = array( 'online', 'warning', 'offline' );
		if ( ! in_array( $incoming, $known, true ) || ! in_array( $outgoing, $known, true ) ) {
			return 100;
		}

		$in_ok  = ( 'offline' !== $incoming );
		$out_ok = ( 'offline' !== $outgoing );

		// Partner dropped our link while we still link to them.
		if ( ! $in_ok && $out_ok ) {
			return 0;
		}
		// We dropped their link, our own debt.
		if ( $in_ok && ! $out_ok ) {
			return 25;
		}
		// Both links gone.
		if ( ! $in_ok && ! $out_ok ) {
			return 50;
		}

		$base_score = 100;

		// We give a followed link, the partner only a nofollow.
		if ( ! $out_nofollow && $in_nofollow ) {
			$base_score = 60;
		}

		// Their link is there but on a devalued (noindex) page.
		if ( 'warning' === $incoming && 100 === $base_score ) {
			$base_score = 70;
		}

		// We are the stronger domain and give away more than we receive.
		if ( $partner_dr > 0 && $my_dr > 0 && $my_dr > $partner_dr ) {
			$base_score = max( 0, $base_score - min( 40, ( $my_dr - $partner_dr ) * 2 ) );
		}

		return (int) $base_score;
	}

	/**
	 * Recalculate the stored fairness score of every exchange from the
	 * statuses on record. Used once after an update that changed the formula.
	 *
	 * @return int Number of rows rewritten.
	 */
	public static function recalculate_all_fairness() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'linktrade_links';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time maintenance on custom table.
		$links = $wpdb->get_results( 'SELECT id, status, backlink_status, backlink_url, is_nofollow, backlink_is_nofollow, domain_rating, my_domain_rating, fairness_score FROM `' . esc_sql( $table_name ) . "` WHERE category = 'exchange'" );

		if ( empty( $links ) ) {
			return 0;
		}

		$changed = 0;
		foreach ( $links as $link ) {
			$outgoing = empty( $link->backlink_url ) ? 'not_applicable' : $link->backlink_status;
			$score    = self::fairness(
				$link->status,
				$outgoing,
				(bool) $link->is_nofollow,
				(bool) $link->backlink_is_nofollow,
				(int) $link->domain_rating,
				(int) $link->my_domain_rating
			);

			if ( (int) $link->fairness_score === $score ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time maintenance on custom table.
			$wpdb->update( $table_name, array( 'fairness_score' => $score ), array( 'id' => absint( $link->id ) ), array( '%d' ), array( '%d' ) );
			++$changed;
		}

		wp_cache_delete( 'linktrade_quick_stats' );
		wp_cache_delete( 'linktrade_full_stats' );

		return $changed;
	}
}
