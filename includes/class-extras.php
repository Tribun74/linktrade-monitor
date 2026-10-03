<?php
/**
 * Menu counter, dashboard widget, review hint and privacy tools.
 *
 * @package Linktrade_Monitor
 * @since 1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Linktrade_Extras
 */
class Linktrade_Extras {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu_counter' ), 99 );
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'register_widget' ) );
		add_action( 'wp_ajax_linktrade_dismiss_review', array( __CLASS__, 'ajax_dismiss_review' ) );

		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_eraser' ) );
		add_action( 'admin_init', array( __CLASS__, 'privacy_policy_text' ) );
	}

	/**
	 * Show the number of links that need attention next to the menu entry.
	 */
	public static function menu_counter() {
		global $menu;

		if ( ! current_user_can( 'manage_options' ) || ! is_array( $menu ) ) {
			return;
		}

		$count = Linktrade_Runner::attention_count();
		if ( $count < 1 ) {
			return;
		}

		foreach ( $menu as $index => $item ) {
			if ( isset( $item[2] ) && 'linktrade-monitor' === $item[2] ) {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The documented way to add a counter to an own menu entry.
				$menu[ $index ][0] .= sprintf(
					' <span class="awaiting-mod count-%1$d"><span class="pending-count" aria-hidden="true">%1$d</span><span class="screen-reader-text">%2$s</span></span>',
					$count,
					/* translators: %d: number of links */
					esc_html( sprintf( _n( '%d link needs attention', '%d links need attention', $count, 'linktrade-monitor' ), $count ) )
				);
				break;
			}
		}
	}

	/**
	 * Register the widget on the WordPress dashboard.
	 */
	public static function register_widget() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_add_dashboard_widget( 'linktrade_widget', __( 'Linktrade Monitor', 'linktrade-monitor' ), array( __CLASS__, 'render_widget' ) );
	}

	/**
	 * Render the dashboard widget.
	 */
	public static function render_widget() {
		global $wpdb;
		$table = $wpdb->prefix . 'linktrade_links';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Small count on custom table.
		$total = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $table ) . '`' );
		$count = Linktrade_Runner::attention_count();
		$state = Linktrade_Runner::schedule_state();
		$url   = admin_url( 'admin.php?page=linktrade-monitor' );

		if ( 0 === $total ) {
			echo '<p>' . esc_html__( 'No links yet. Add the first page that links to you and it is checked right away.', 'linktrade-monitor' ) . '</p>';
			echo '<p><a class="button button-primary" href="' . esc_url( $url ) . '">' . esc_html__( 'Check your first link', 'linktrade-monitor' ) . '</a></p>';
			return;
		}

		if ( $count > 0 ) {
			/* translators: %d: number of links */
			echo '<p><strong>' . esc_html( sprintf( _n( '%d link needs attention', '%d links need attention', $count, 'linktrade-monitor' ), $count ) ) . '</strong></p>';
		} else {
			echo '<p><strong>' . esc_html__( 'No link is missing or devalued.', 'linktrade-monitor' ) . '</strong></p>';
		}

		echo '<p>';
		/* translators: %d: number of links */
		echo esc_html( sprintf( _n( '%d link is being monitored.', '%d links are being monitored.', $total, 'linktrade-monitor' ), $total ) ) . ' ';
		if ( $state['next'] && '' === $state['error'] ) {
			/* translators: %s: date and time */
			echo esc_html( sprintf( __( 'Next check: %s.', 'linktrade-monitor' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $state['next'] ) ) );
		} else {
			echo esc_html__( 'The weekly check is not scheduled, so your links are not being checked.', 'linktrade-monitor' );
		}
		echo '</p>';

		echo '<p><a class="button" href="' . esc_url( $url ) . '">' . esc_html__( 'Open Linktrade Monitor', 'linktrade-monitor' ) . '</a></p>';
	}

	/**
	 * Should the one-time review hint be shown?
	 *
	 * Only on the plugin's own page, only after 30 days of use with at least
	 * three links, and never again once it was closed.
	 *
	 * @return bool
	 */
	public static function show_review_hint() {
		if ( get_option( 'linktrade_notice_dismissed' ) ) {
			return false;
		}

		$installed = (int) get_option( 'linktrade_install_date', 0 );
		if ( ! $installed || $installed > time() - ( 30 * DAY_IN_SECONDS ) ) {
			return false;
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Small count on custom table.
		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $wpdb->prefix . 'linktrade_links' ) . '`' ) >= 3;
	}

	/**
	 * AJAX: close the review hint for good.
	 */
	public static function ajax_dismiss_review() {
		check_ajax_referer( 'linktrade_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		update_option( 'linktrade_notice_dismissed', 1, false );
		wp_send_json_success();
	}

	/**
	 * Register the personal data exporter.
	 *
	 * @param array $exporters Exporters.
	 * @return array
	 */
	public static function register_exporter( $exporters ) {
		$exporters['linktrade-monitor'] = array(
			'exporter_friendly_name' => __( 'Linktrade Monitor', 'linktrade-monitor' ),
			'callback'               => array( __CLASS__, 'export_personal_data' ),
		);

		return $exporters;
	}

	/**
	 * Register the personal data eraser.
	 *
	 * @param array $erasers Erasers.
	 * @return array
	 */
	public static function register_eraser( $erasers ) {
		$erasers['linktrade-monitor'] = array(
			'eraser_friendly_name' => __( 'Linktrade Monitor', 'linktrade-monitor' ),
			'callback'             => array( __CLASS__, 'erase_personal_data' ),
		);

		return $erasers;
	}

	/**
	 * Export what is stored about a partner contact address.
	 *
	 * @param string $email Email address.
	 * @param int    $page  Page (unused, the data set is small).
	 * @return array
	 */
	public static function export_personal_data( $email, $page = 1 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'linktrade_links';
		$items = array();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Privacy export on custom table.
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT id, partner_name, partner_contact, partner_url, notes FROM `' . esc_sql( $table ) . '` WHERE partner_contact = %s', $email ) );

		foreach ( (array) $rows as $row ) {
			$items[] = array(
				'group_id'    => 'linktrade-monitor',
				'group_label' => __( 'Linktrade Monitor: link partners', 'linktrade-monitor' ),
				'item_id'     => 'linktrade-' . (int) $row->id,
				'data'        => array(
					array(
						'name'  => __( 'Partner Name', 'linktrade-monitor' ),
						'value' => $row->partner_name,
					),
					array(
						'name'  => __( 'Contact (Email)', 'linktrade-monitor' ),
						'value' => $row->partner_contact,
					),
					array(
						'name'  => __( 'Partner Page URL', 'linktrade-monitor' ),
						'value' => $row->partner_url,
					),
					array(
						'name'  => __( 'Notes', 'linktrade-monitor' ),
						'value' => $row->notes,
					),
				),
			);
		}

		return array(
			'data' => $items,
			'done' => true,
		);
	}

	/**
	 * Remove a partner contact address. The link itself stays: it is a
	 * business record about a web page, not about the person.
	 *
	 * @param string $email Email address.
	 * @param int    $page  Page (unused).
	 * @return array
	 */
	public static function erase_personal_data( $email, $page = 1 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'linktrade_links';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Privacy erasure on custom table.
		$removed = $wpdb->update( $table, array( 'partner_contact' => '' ), array( 'partner_contact' => $email ), array( '%s' ), array( '%s' ) );

		if ( $removed ) {
			delete_transient( 'linktrade_attention_count' );
		}

		// The address is gone, the record of the link (partner name, web
		// addresses, notes) stays: say so instead of claiming full erasure.
		return array(
			'items_removed'  => (bool) $removed,
			'items_retained' => (bool) $removed,
			'messages'       => $removed ? array( __( 'Linktrade Monitor: the contact address was removed. The monitored links, the partner name and your notes about them were kept, because they describe a web page and an agreement.', 'linktrade-monitor' ) ) : array(),
			'done'           => true,
		);
	}

	/**
	 * Suggested text for the site's privacy policy.
	 */
	public static function privacy_policy_text() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$text = '<p>' . esc_html__( 'Linktrade Monitor stores the link partners you enter (name, optional contact email address, web addresses, notes) in the database of this website. It does not collect data about visitors and sets no cookies. To check a link, this website requests the partner page you entered. No data is sent to any other service.', 'linktrade-monitor' ) . '</p>';

		wp_add_privacy_policy_content( 'Linktrade Monitor', wp_kses_post( $text ) );
	}
}
