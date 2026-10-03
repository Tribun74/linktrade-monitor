<?php
/**
 * WP-CLI commands
 *
 * @package Linktrade_Monitor
 * @since 1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check links from the command line, for example from a real cron job.
 */
class Linktrade_CLI {

	/**
	 * Check links now.
	 *
	 * Without options all links are checked and the report is sent as after
	 * the weekly check.
	 *
	 * ## OPTIONS
	 *
	 * [--id=<id>]
	 * : Check only the link with this ID. No report is sent.
	 *
	 * ## EXAMPLES
	 *
	 *     wp linktrade check
	 *     wp linktrade check --id=12
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function check( $args, $assoc_args ) {
		global $wpdb;
		$table = $wpdb->prefix . 'linktrade_links';

		if ( isset( $assoc_args['id'] ) ) {
			if ( absint( $assoc_args['id'] ) < 1 ) {
				WP_CLI::error( 'Please pass a link ID, for example --id=12.' );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fresh row from custom table.
			$link = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM `' . esc_sql( $table ) . '` WHERE id = %d', absint( $assoc_args['id'] ) ) );
			if ( ! $link ) {
				WP_CLI::error( 'Link not found.' );
			}

			Linktrade_Runner::check_link( $link );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fresh row from custom table.
			$link = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM `' . esc_sql( $table ) . '` WHERE id = %d', absint( $assoc_args['id'] ) ) );
			WP_CLI::success( sprintf( '%s: %s (HTTP %d)', $link->partner_name, $link->status, (int) $link->http_code ) );
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fresh count on custom table.
		$total = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $table ) . '`' );
		if ( 0 === $total ) {
			WP_CLI::success( 'No links to check.' );
			return;
		}

		if ( get_transient( 'linktrade_run_lock' ) ) {
			WP_CLI::error( 'A check is running right now. Try again in a few minutes.' );
		}

		WP_CLI::log( sprintf( 'Checking %d links ...', $total ) );

		Linktrade_Runner::start_full_run();

		$rounds = 0;
		$state  = Linktrade_Runner::schedule_state();
		while ( $state['running'] && $rounds < 2000 ) {
			if ( get_transient( 'linktrade_run_lock' ) ) {
				// The scheduled worker took over. It finishes the run.
				WP_CLI::warning( 'The scheduled check took over and will finish the run.' );
				return;
			}
			Linktrade_Runner::process();
			$state = Linktrade_Runner::schedule_state();
			++$rounds;
		}

		$last = get_option( 'linktrade_last_run', array() );
		WP_CLI::success(
			sprintf(
				'Done. Checked: %d, changes: %d, report: %s.',
				isset( $last['checked'] ) ? (int) $last['checked'] : 0,
				isset( $last['changes'] ) ? (int) $last['changes'] : 0,
				isset( $last['mail'] ) ? $last['mail'] : '-'
			)
		);
	}

	/**
	 * Show the state of the automatic check and the link counts.
	 *
	 * ## EXAMPLES
	 *
	 *     wp linktrade status
	 */
	public function status() {
		global $wpdb;
		$table = $wpdb->prefix . 'linktrade_links';
		$state = Linktrade_Runner::schedule_state();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fresh counts on custom table.
		$rows = $wpdb->get_results( 'SELECT status, COUNT(*) AS amount FROM `' . esc_sql( $table ) . '` GROUP BY status' );
		foreach ( (array) $rows as $row ) {
			WP_CLI::log( sprintf( '%-10s %d', $row->status, (int) $row->amount ) );
		}

		WP_CLI::log( 'Needs attention: ' . Linktrade_Runner::attention_count() );
		WP_CLI::log( 'Next check: ' . ( $state['next'] ? gmdate( 'Y-m-d H:i', $state['next'] ) . ' UTC' : 'not scheduled' ) );
		WP_CLI::log( 'Last complete check: ' . ( ! empty( $state['last']['finished'] ) ? $state['last']['finished'] : 'none' ) );

		if ( '' !== $state['error'] ) {
			WP_CLI::warning( 'Schedule error: ' . $state['error'] );
		}
	}
}
