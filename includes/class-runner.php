<?php
/**
 * Check runner
 *
 * The one place where links are checked. The weekly schedule, the "check now"
 * button, saving a link and the import all go through check_link(), so every
 * path writes the same history, the same change log and the same fairness.
 *
 * @package Linktrade_Monitor
 * @since 1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Linktrade_Runner
 */
class Linktrade_Runner {

	/**
	 * Weekly schedule hook.
	 */
	const HOOK = 'linktrade_check_links';

	/**
	 * Continuation hook: finishes a run in small portions.
	 */
	const CONTINUE_HOOK = 'linktrade_continue_run';

	/**
	 * Seconds one request may spend checking before it hands over.
	 */
	const TIME_BUDGET = 20;

	/**
	 * Days the check history is kept.
	 */
	const HISTORY_DAYS = 180;

	/**
	 * Statuses that are a finding. Everything else means "not known yet".
	 *
	 * @var array
	 */
	private static $known = array( 'online', 'warning', 'offline' );

	/**
	 * Make sure the weekly check is scheduled, and say so if it is not.
	 *
	 * A schedule call that fails silently means no link is ever checked and
	 * nobody notices. The result is therefore stored and shown.
	 */
	public static function ensure_schedule() {
		$event = wp_get_scheduled_event( self::HOOK );

		// Older versions scheduled a monthly run. Replace it.
		if ( $event && 'weekly' !== $event->schedule ) {
			wp_clear_scheduled_hook( self::HOOK );
			$event = false;
		}

		if ( ! $event ) {
			$result = wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', self::HOOK, array(), true );

			if ( is_wp_error( $result ) ) {
				update_option( 'linktrade_schedule_error', $result->get_error_message(), false );
			} elseif ( false === $result ) {
				update_option( 'linktrade_schedule_error', 'wp_schedule_event returned false', false );
			} else {
				delete_option( 'linktrade_schedule_error' );
			}
		} else {
			delete_option( 'linktrade_schedule_error' );
		}

		if ( ! wp_next_scheduled( 'linktrade_check_reminders' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'linktrade_check_reminders' );
		}

		// A run that was interrupted (host killed the request, cron did not
		// fire) is picked up again instead of waiting for next week.
		if ( self::get_run() && ! wp_next_scheduled( self::CONTINUE_HOOK ) ) {
			wp_schedule_single_event( time() + MINUTE_IN_SECONDS, self::CONTINUE_HOOK );
		}
	}

	/**
	 * State of the schedule, for the dashboard and Site Health.
	 *
	 * @return array
	 */
	public static function schedule_state() {
		$last = get_option( 'linktrade_last_run', array() );
		$run  = self::get_run();

		return array(
			'next'      => wp_next_scheduled( self::HOOK ),
			'error'     => (string) get_option( 'linktrade_schedule_error', '' ),
			'running'   => ! empty( $run ),
			'remaining' => $run ? self::count_remaining( $run ) : 0,
			'last'      => is_array( $last ) ? $last : array(),
			'cron_off'  => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
		);
	}

	/**
	 * Weekly hook: start a full run.
	 */
	public static function start_full_run() {
		$run    = self::get_run();
		$events = array();

		if ( $run && empty( $run['only_new'] ) ) {
			// The previous run is still open. If it is recent, finish it
			// instead of starting over. Its findings are kept either way: the
			// statuses are already written, so a fresh run would not see them
			// as changes any more.
			if ( strtotime( $run['started'] ) > strtotime( current_time( 'mysql' ) ) - ( 6 * DAY_IN_SECONDS ) ) {
				self::process();
				return;
			}
			$events = isset( $run['events'] ) && is_array( $run['events'] ) ? $run['events'] : array();
		}

		self::begin_run( false, $events );
		self::process();
	}

	/**
	 * Start a run over links that were never checked (after an import).
	 */
	public static function start_new_links_run() {
		if ( self::get_run() ) {
			return;
		}
		self::begin_run( true );

		if ( ! wp_next_scheduled( self::CONTINUE_HOOK ) ) {
			wp_schedule_single_event( time() + 5, self::CONTINUE_HOOK );
		}
	}

	/**
	 * Open a run.
	 *
	 * @param bool  $only_new Only links that were never checked.
	 * @param array $events   Events carried over from an abandoned run.
	 */
	private static function begin_run( $only_new, $events = array() ) {
		// After an update that widened the detection, the first run only takes
		// stock: what it newly recognises was already there and is not news.
		// The marker is only removed once such a run has really checked links.
		$silent = ( ! $only_new && get_option( 'linktrade_baseline_run' ) );

		update_option(
			'linktrade_run',
			array(
				'started'  => current_time( 'mysql' ),
				'only_new' => (bool) $only_new,
				'silent'   => $silent,
				'cursor'   => 0,
				'checked'  => 0,
				'events'   => $events,
			),
			false
		);
	}

	/**
	 * Current run or false.
	 *
	 * @return array|false
	 */
	private static function get_run() {
		$run = get_option( 'linktrade_run', false );

		return ( is_array( $run ) && ! empty( $run['started'] ) ) ? $run : false;
	}

	/**
	 * Links still to be checked in this run.
	 *
	 * @param array $run Run state.
	 * @return int
	 */
	private static function count_remaining( $run ) {
		global $wpdb;
		$table  = $wpdb->prefix . 'linktrade_links';
		$cursor = isset( $run['cursor'] ) ? absint( $run['cursor'] ) : 0;

		if ( ! empty( $run['only_new'] ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fresh count on custom table.
			return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . esc_sql( $table ) . '` WHERE id > %d AND last_check IS NULL', $cursor ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fresh count on custom table.
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . esc_sql( $table ) . '` WHERE id > %d', $cursor ) );
	}

	/**
	 * Work on the current run for a limited time, then hand over.
	 *
	 * Hosts end long requests. A run therefore never tries to do everything at
	 * once: it checks links until the time budget is used up and schedules its
	 * own continuation until nothing is left.
	 */
	public static function process() {
		$run = self::get_run();
		if ( ! $run ) {
			return;
		}

		// Safety net first: WordPress removes a single event before it runs. If
		// the host ends this request half way, this event brings the run back
		// without anybody having to open the admin.
		if ( ! wp_next_scheduled( self::CONTINUE_HOOK ) ) {
			wp_schedule_single_event( time() + ( 5 * MINUTE_IN_SECONDS ), self::CONTINUE_HOOK );
		}

		// One worker at a time.
		if ( get_transient( 'linktrade_run_lock' ) ) {
			return;
		}
		set_transient( 'linktrade_run_lock', 1, 5 * MINUTE_IN_SECONDS );

		global $wpdb;
		$table = $wpdb->prefix . 'linktrade_links';
		$delay = min( 5000, absint( get_option( 'linktrade_request_delay', 1000 ) ) );
		$start = microtime( true );

		// The run walks through the links by their ID and remembers how far it
		// got. That needs no comparison of times, so a clock change, a check
		// in the same second or a manual check in between cannot confuse it.
		$cursor = isset( $run['cursor'] ) ? absint( $run['cursor'] ) : 0;

		if ( ! empty( $run['only_new'] ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cron needs fresh rows from custom table.
			$links = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM `' . esc_sql( $table ) . '` WHERE id > %d AND last_check IS NULL ORDER BY id ASC LIMIT 25', $cursor ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cron needs fresh rows from custom table.
			$links = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM `' . esc_sql( $table ) . '` WHERE id > %d ORDER BY id ASC LIMIT 25', $cursor ) );
		}

		foreach ( (array) $links as $link ) {
			$events = self::check_link( $link, ! empty( $run['silent'] ) );

			++$run['checked'];
			$run['cursor'] = absint( $link->id );
			foreach ( $events as $event ) {
				$run['events'][] = $event;
			}
			// Save after every link: if the host ends the request here, nothing is lost.
			update_option( 'linktrade_run', $run, false );

			if ( ( microtime( true ) - $start ) > self::TIME_BUDGET ) {
				break;
			}

			if ( $delay > 0 ) {
				usleep( $delay * 1000 );
			}
		}

		if ( self::count_remaining( $run ) > 0 ) {
			// Continue soon instead of waiting for the safety net.
			wp_clear_scheduled_hook( self::CONTINUE_HOOK );
			wp_schedule_single_event( time() + MINUTE_IN_SECONDS, self::CONTINUE_HOOK );
			delete_transient( 'linktrade_run_lock' );
			return;
		}

		self::finish_run( $run );
		delete_transient( 'linktrade_run_lock' );
	}

	/**
	 * Close a run: remember the result, send the report, tidy the history.
	 *
	 * @param array $run Run state.
	 */
	private static function finish_run( $run ) {
		wp_clear_scheduled_hook( self::CONTINUE_HOOK );

		// The stock-taking run is done once it has looked at the links.
		if ( ! empty( $run['silent'] ) && (int) $run['checked'] > 0 ) {
			delete_option( 'linktrade_baseline_run' );
		}

		if ( ! empty( $run['only_new'] ) ) {
			delete_option( 'linktrade_run' );
			return;
		}

		// Send first, close afterwards: if the request dies while mailing, the
		// run is still open and its findings are not lost.
		$mail = self::send_report( $run );

		$last = array(
			'finished' => current_time( 'mysql' ),
			'checked'  => (int) $run['checked'],
			'changes'  => count( $run['events'] ),
			'mail'     => $mail,
		);
		// A report that could not be mailed stays readable on the dashboard.
		if ( 'failed' === $mail ) {
			$last['events'] = array_slice( $run['events'], 0, 100 );
		}

		update_option( 'linktrade_last_run', $last, false );
		delete_option( 'linktrade_run' );

		wp_cache_delete( 'linktrade_quick_stats' );
		wp_cache_delete( 'linktrade_full_stats' );

		self::prune_history();
	}

	/**
	 * Check one link in both directions and write everything that follows.
	 *
	 * @param object $link   Row of the links table.
	 * @param bool   $silent Take stock only: do not report newly recognised
	 *                       attributes (nofollow, sponsored, noindex) as changes.
	 * @return array Events (changes worth telling the owner about).
	 */
	public static function check_link( $link, $silent = false ) {
		require_once LINKTRADE_PLUGIN_DIR . 'includes/checker/class-link-checker.php';

		global $wpdb;
		$table   = $wpdb->prefix . 'linktrade_links';
		$checker = new Linktrade_Link_Checker();
		$now     = current_time( 'mysql' );
		$events  = array();
		$id      = absint( $link->id );

		$update = array( 'last_check' => $now );

		// Incoming: the partner's link to us.
		$in          = $checker->check( $link->partner_url, $link->target_url );
		$in_status   = $link->status;
		$in_nofollow = (bool) $link->is_nofollow;

		self::record_check( $id, 'incoming', $in );
		$update['http_code'] = (int) $in['http_code'];

		if ( empty( $in['unreadable'] ) ) {
			self::set_unreadable( $id, 'incoming', null );

			$in_status              = $in['status'];
			$in_nofollow            = (bool) $in['is_nofollow'];
			$update['status']       = $in['status'];
			$update['is_nofollow']  = $in['is_nofollow'] ? 1 : 0;
			$update['is_noindex']   = $in['is_noindex'] ? 1 : 0;
			$update['is_sponsored'] = $in['is_sponsored'] ? 1 : 0;
			$update['redirect_url'] = $in['redirect_url'];

			// The agreed anchor text is the owner's entry. Only fill an empty field.
			$found_anchor = ! empty( $in['anchor_text'] ) ? mb_substr( sanitize_text_field( $in['anchor_text'] ), 0, 255 ) : '';
			// A link that points to another page is a different link: its text
			// is neither the agreement nor a change of it.
			$same_link = empty( $in['moved'] );

			if ( $same_link && empty( $link->anchor_text ) && '' !== $found_anchor ) {
				$update['anchor_text'] = $found_anchor;
			}

			// What is actually on the page, to compare against the agreement.
			// An image link has no text: keep what was seen last.
			if ( $same_link && property_exists( $link, 'found_anchor' ) && 'offline' !== $in['status'] && '' !== $found_anchor ) {
				$update['found_anchor'] = $found_anchor;

				if ( ! empty( $link->anchor_text ) && '' !== $found_anchor
					&& ! empty( $link->found_anchor )
					&& self::same_text( $link->found_anchor, $link->anchor_text )
					&& ! self::same_text( $found_anchor, $link->anchor_text ) ) {
					$events[] = self::event( $link, 'anchor' );
				}
			}

			// nofollow and friends only break an agreement that promised a followed link.
			$follow_agreed = self::follow_agreed( $link );

			// A first check is not a change.
			if ( in_array( $link->status, self::$known, true ) ) {
				if ( 'offline' === $in['status'] && 'offline' !== $link->status ) {
					$events[] = self::event( $link, 'lost' );
				} elseif ( 'offline' !== $in['status'] && 'offline' === $link->status ) {
					$events[] = self::event( $link, 'back' );
				} elseif ( 'offline' !== $in['status'] && ! $silent ) {
					if ( ! empty( $in['moved'] ) && 'online' === $link->status ) {
						$events[] = self::event( $link, 'moved' );
					}
				}

				if ( 'offline' !== $in['status'] && 'offline' !== $link->status && ! $silent && $follow_agreed ) {
					if ( $in['is_nofollow'] && ! $link->is_nofollow ) {
						$events[] = self::event( $link, 'nofollow' );
					}
					if ( $in['is_sponsored'] && empty( $link->is_sponsored ) ) {
						$events[] = self::event( $link, 'sponsored' );
					}
					if ( $in['is_noindex'] && ! $link->is_noindex ) {
						$events[] = self::event( $link, 'noindex' );
					}
				}
			}

			if ( $in['status'] !== $link->status ) {
				self::log_change( $id, 'status', $link->status, $in['status'] );
			}
		} else {
			self::set_unreadable( $id, 'incoming', (string) $in['error_message'] );
		}

		// Outgoing: our link to the partner, for exchanges with both addresses on record.
		$out_status   = empty( $link->backlink_url ) ? 'not_applicable' : $link->backlink_status;
		$out_nofollow = (bool) $link->backlink_is_nofollow;

		if ( 'exchange' === $link->category && ! empty( $link->backlink_url ) && ! empty( $link->backlink_target ) ) {
			$out = $checker->check( $link->backlink_url, $link->backlink_target );

			self::record_check( $id, 'outgoing', $out );
			$update['backlink_http_code']  = (int) $out['http_code'];
			$update['backlink_last_check'] = $now;

			if ( empty( $out['unreadable'] ) ) {
				self::set_unreadable( $id, 'outgoing', null );

				$out_status                     = $out['status'];
				$out_nofollow                   = (bool) $out['is_nofollow'];
				$update['backlink_status']      = $out['status'];
				$update['backlink_is_nofollow'] = $out['is_nofollow'] ? 1 : 0;

				if ( in_array( $link->backlink_status, self::$known, true ) ) {
					if ( 'offline' === $out['status'] && 'offline' !== $link->backlink_status ) {
						$events[] = self::event( $link, 'our_lost' );
					} elseif ( 'offline' !== $out['status'] && 'offline' === $link->backlink_status ) {
						$events[] = self::event( $link, 'our_back' );
					}
				}

				if ( $out['status'] !== $link->backlink_status ) {
					self::log_change( $id, 'backlink_status', $link->backlink_status, $out['status'] );
				}
			} else {
				self::set_unreadable( $id, 'outgoing', (string) $out['error_message'] );
			}
		} else {
			// No reciprocal link on record (any more): nothing can be unreadable.
			self::set_unreadable( $id, 'outgoing', null );
		}

		if ( 'exchange' === $link->category ) {
			$update['fairness_score'] = Linktrade::fairness(
				$in_status,
				$out_status,
				$in_nofollow,
				$out_nofollow,
				isset( $link->domain_rating ) ? (int) $link->domain_rating : 0,
				isset( $link->my_domain_rating ) ? (int) $link->my_domain_rating : 0
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Update on custom table.
		$wpdb->update( $table, $update, array( 'id' => $id ) );

		wp_cache_delete( 'linktrade_quick_stats' );
		wp_cache_delete( 'linktrade_full_stats' );
		delete_transient( 'linktrade_attention_count' );

		return $events;
	}

	/**
	 * Are two anchor texts the same, ignoring case and spacing?
	 *
	 * @param string $a First text.
	 * @param string $b Second text.
	 * @return bool
	 */
	public static function same_text( $a, $b ) {
		$normalise = function ( $text ) {
			$text = preg_replace( '/\s+/u', ' ', trim( (string) $text ) );
			return function_exists( 'mb_strtolower' ) ? mb_strtolower( (string) $text ) : strtolower( (string) $text );
		};

		return $normalise( $a ) === $normalise( $b );
	}

	/**
	 * Was a normal, followed link agreed for this row?
	 *
	 * @param object $link Link row.
	 * @return bool
	 */
	public static function follow_agreed( $link ) {
		return ! property_exists( $link, 'follow_agreed' ) || null === $link->follow_agreed || (bool) (int) $link->follow_agreed;
	}

	/**
	 * Does the anchor text on the page differ from the agreed one?
	 *
	 * @param object $link Link row.
	 * @return bool
	 */
	public static function anchor_differs( $link ) {
		return 'offline' !== $link->status
			&& ! empty( $link->anchor_text )
			&& ! empty( $link->found_anchor )
			&& ! self::same_text( $link->anchor_text, $link->found_anchor );
	}

	/**
	 * Is the link devalued in a way that breaks the agreement? nofollow,
	 * sponsored and noindex only count when a followed link was agreed; a link
	 * that points to another page always counts.
	 *
	 * @param object $link Link row.
	 * @return bool
	 */
	public static function is_devalued( $link ) {
		if ( 'warning' !== $link->status ) {
			return false;
		}

		$flagged = ! empty( $link->is_nofollow ) || ! empty( $link->is_sponsored ) || ! empty( $link->is_noindex );

		return $flagged ? self::follow_agreed( $link ) : true;
	}

	/**
	 * Is there a problem with this link the owner should act on? The one
	 * definition used by the menu counter, the widget and the dashboard.
	 *
	 * @param object $link Link row.
	 * @return bool
	 */
	public static function needs_attention( $link ) {
		if ( 'offline' === $link->status || self::is_devalued( $link ) || self::anchor_differs( $link ) ) {
			return true;
		}

		return 'exchange' === $link->category && ! empty( $link->backlink_url ) && 'offline' === $link->backlink_status;
	}

	/**
	 * Number of links with a problem, for the menu and the dashboard widget.
	 * Cached for a short time.
	 *
	 * @return int
	 */
	public static function attention_count() {
		$count = get_transient( 'linktrade_attention_count' );
		if ( false !== $count ) {
			return (int) $count;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'linktrade_links';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Rows of the custom table, result cached in a transient.
		$rows  = $wpdb->get_results( 'SELECT * FROM `' . esc_sql( $table ) . '`' );
		$count = 0;

		foreach ( (array) $rows as $row ) {
			if ( self::needs_attention( $row ) ) {
				++$count;
			}
		}

		set_transient( 'linktrade_attention_count', $count, 10 * MINUTE_IN_SECONDS );

		return $count;
	}

	/**
	 * Build an event record.
	 *
	 * @param object $link Link row.
	 * @param string $type Event type.
	 * @return array
	 */
	private static function event( $link, $type ) {
		return array(
			'type'    => $type,
			'id'      => absint( $link->id ),
			'partner' => (string) $link->partner_name,
			'page'    => in_array( $type, array( 'our_lost', 'our_back' ), true ) ? (string) $link->backlink_url : (string) $link->partner_url,
		);
	}

	/**
	 * Write one row of check history.
	 *
	 * @param int    $link_id Link ID.
	 * @param string $type    incoming or outgoing.
	 * @param array  $result  Checker result.
	 */
	private static function record_check( $link_id, $type, $result ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Insert on custom table.
		$wpdb->insert(
			$wpdb->prefix . 'linktrade_checks',
			array(
				'link_id'       => absint( $link_id ),
				'check_type'    => ( 'outgoing' === $type ) ? 'outgoing' : 'incoming',
				'checked_at'    => current_time( 'mysql' ),
				'http_code'     => (int) $result['http_code'],
				'response_time' => (int) $result['response_time'],
				'is_nofollow'   => $result['is_nofollow'] ? 1 : 0,
				'is_noindex'    => $result['is_noindex'] ? 1 : 0,
				'is_sponsored'  => $result['is_sponsored'] ? 1 : 0,
				'redirect_url'  => $result['redirect_url'],
				'anchor_found'  => ! empty( $result['anchor_text'] ) ? mb_substr( sanitize_text_field( $result['anchor_text'] ), 0, 255 ) : null,
				'error_message' => empty( $result['unreadable'] ) ? ( 'offline' === $result['status'] ? (string) $result['error_message'] : null ) : '[unreadable] ' . (string) $result['error_message'],
			)
		);
	}

	/**
	 * Write a status change to the change log.
	 *
	 * @param int    $link_id Link ID.
	 * @param string $action  status or backlink_status.
	 * @param string $old     Old value.
	 * @param string $new     New value.
	 */
	private static function log_change( $link_id, $action, $old, $new ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Insert on custom table.
		$wpdb->insert(
			$wpdb->prefix . 'linktrade_log',
			array(
				'link_id'    => absint( $link_id ),
				'action'     => $action,
				'old_value'  => (string) $old,
				'new_value'  => (string) $new,
				'user_id'    => get_current_user_id(),
				'created_at' => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Remember since when a page cannot be read, or forget it again.
	 *
	 * @param int         $link_id   Link ID.
	 * @param string      $direction incoming or outgoing.
	 * @param string|null $message   Reason, or null when the page was readable.
	 */
	private static function set_unreadable( $link_id, $direction, $message ) {
		$all = get_option( 'linktrade_unreadable', array() );
		if ( ! is_array( $all ) ) {
			$all = array();
		}
		$key = $link_id . ':' . $direction;

		if ( null === $message ) {
			if ( isset( $all[ $key ] ) ) {
				unset( $all[ $key ] );
				update_option( 'linktrade_unreadable', $all, false );
			}
			return;
		}

		if ( ! isset( $all[ $key ] ) ) {
			$all[ $key ] = array( 'since' => current_time( 'mysql' ) );
		}
		$all[ $key ]['message'] = $message;
		update_option( 'linktrade_unreadable', $all, false );
	}

	/**
	 * Pages that could not be read at the last check.
	 *
	 * @return array Keyed "linkid:direction" with since and message.
	 */
	public static function get_unreadable() {
		$all = get_option( 'linktrade_unreadable', array() );

		return is_array( $all ) ? $all : array();
	}

	/**
	 * Forget everything stored for a deleted link.
	 *
	 * @param int $link_id Link ID.
	 */
	public static function forget_link( $link_id ) {
		global $wpdb;
		$link_id = absint( $link_id );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cleanup on custom tables.
		$wpdb->delete( $wpdb->prefix . 'linktrade_checks', array( 'link_id' => $link_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'linktrade_log', array( 'link_id' => $link_id ), array( '%d' ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		self::set_unreadable( $link_id, 'incoming', null );
		self::set_unreadable( $link_id, 'outgoing', null );
		delete_transient( 'linktrade_attention_count' );
	}

	/**
	 * A written record of what the checks found, for a complaint to the seller
	 * of a link or to an exchange partner.
	 *
	 * @param object $link Link row.
	 * @return string Empty when the link is fine.
	 */
	public static function proof_text( $link ) {
		if ( 'offline' !== $link->status && ! self::is_devalued( $link ) ) {
			return '';
		}

		global $wpdb;
		$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Display queries on custom table.
		$last = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM `' . esc_sql( $wpdb->prefix . 'linktrade_checks' ) . "` WHERE link_id = %d AND check_type = 'incoming' ORDER BY checked_at DESC, id DESC LIMIT 1", $link->id ) );
		$seen = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM `' . esc_sql( $wpdb->prefix . 'linktrade_checks' ) . "` WHERE link_id = %d AND check_type = 'incoming' AND ( error_message IS NULL OR error_message = '' ) ORDER BY checked_at DESC, id DESC LIMIT 1", $link->id ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$lines = array();
		/* translators: %s: URL */
		$lines[] = sprintf( __( 'Page checked: %s', 'linktrade-monitor' ), $link->partner_url );
		/* translators: %s: URL */
		$lines[] = sprintf( __( 'Link expected to: %s', 'linktrade-monitor' ), $link->target_url );

		if ( $last ) {
			/* translators: 1: date and time, 2: HTTP status code */
			$lines[] = sprintf( __( 'Last check: %1$s, HTTP status %2$d', 'linktrade-monitor' ), mysql2date( $format, $last->checked_at ), (int) $last->http_code );
		}

		if ( 'offline' === $link->status ) {
			$lines[] = __( 'Result: no link to the expected page was found.', 'linktrade-monitor' );

			$since = self::since_map( 'status', 'offline' );
			if ( isset( $since[ (int) $link->id ] ) ) {
				/* translators: %s: date and time */
				$lines[] = sprintf( __( 'First reported missing: %s', 'linktrade-monitor' ), mysql2date( $format, $since[ (int) $link->id ] ) );
			}
			if ( $seen ) {
				$anchor = $seen->anchor_found ? ' ("' . $seen->anchor_found . '")' : '';
				/* translators: 1: date and time, 2: anchor text in brackets or empty */
				$lines[] = sprintf( __( 'Last seen on the page: %1$s%2$s', 'linktrade-monitor' ), mysql2date( $format, $seen->checked_at ), $anchor );
			}
		} else {
			$flags = array();
			if ( $link->is_nofollow ) {
				$flags[] = 'nofollow';
			}
			if ( ! empty( $link->is_sponsored ) ) {
				$flags[] = 'sponsored';
			}
			if ( $link->is_noindex ) {
				$flags[] = 'noindex';
			}
			if ( $flags ) {
				/* translators: %s: list such as "nofollow, noindex" */
				$lines[] = sprintf( __( 'Result: the link is there, but set to: %s', 'linktrade-monitor' ), implode( ', ', $flags ) );
			} elseif ( ! empty( $link->redirect_url ) ) {
				/* translators: %s: URL */
				$lines[] = sprintf( __( 'Result: the link points to another page: %s', 'linktrade-monitor' ), $link->redirect_url );
			}
		}

		return implode( "\n", $lines );
	}

	/**
	 * Drop history older than HISTORY_DAYS.
	 */
	private static function prune_history() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Maintenance on custom table.
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM `' . esc_sql( $wpdb->prefix . 'linktrade_checks' ) . '` WHERE checked_at < %s',
				gmdate( 'Y-m-d H:i:s', time() - ( self::HISTORY_DAYS * DAY_IN_SECONDS ) )
			)
		);
	}

	/**
	 * Date of the latest change to a given status, per link.
	 *
	 * @param string $action status or backlink_status.
	 * @param string $value  New value to look for, e.g. offline.
	 * @return array link_id => mysql datetime.
	 */
	public static function since_map( $action, $value ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Display query on custom table.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT link_id, MAX(created_at) AS since FROM `' . esc_sql( $wpdb->prefix . 'linktrade_log' ) . '` WHERE action = %s AND new_value = %s GROUP BY link_id',
				$action,
				$value
			)
		);

		$map = array();
		foreach ( (array) $rows as $row ) {
			$map[ (int) $row->link_id ] = $row->since;
		}

		return $map;
	}

	/**
	 * Where the notification mail goes.
	 *
	 * @return string Valid address or empty string.
	 */
	public static function recipient() {
		$email = sanitize_email( (string) get_option( 'linktrade_notification_email', '' ) );

		if ( ! is_email( $email ) ) {
			$email = sanitize_email( (string) get_option( 'admin_email', '' ) );
		}

		return is_email( $email ) ? $email : '';
	}

	/**
	 * Send the report of a finished weekly run.
	 *
	 * One mail per run, never one per link. Without changes nothing is sent,
	 * unless the owner asked for the weekly summary.
	 *
	 * @param array $run Run state.
	 * @return string sent, failed, skipped or off.
	 */
	private static function send_report( $run ) {
		if ( ! get_option( 'linktrade_email_notifications', 1 ) ) {
			return 'off';
		}

		$events = isset( $run['events'] ) && is_array( $run['events'] ) ? $run['events'] : array();

		if ( empty( $events ) && ! get_option( 'linktrade_weekly_summary', 0 ) ) {
			return 'skipped';
		}

		$to = self::recipient();
		if ( '' === $to ) {
			return 'failed';
		}

		$labels = array(
			'lost'      => __( 'Partner removed the link to you', 'linktrade-monitor' ),
			'moved'     => __( 'Link to you now points to another page', 'linktrade-monitor' ),
			'anchor'    => __( 'Anchor text of the link to you has changed', 'linktrade-monitor' ),
			'nofollow'  => __( 'Link to you is now nofollow', 'linktrade-monitor' ),
			'sponsored' => __( 'Link to you is now marked sponsored', 'linktrade-monitor' ),
			'noindex'   => __( 'Partner page is now noindex', 'linktrade-monitor' ),
			'our_lost'  => __( 'Your link to the partner is missing', 'linktrade-monitor' ),
			'back'      => __( 'Link to you is back', 'linktrade-monitor' ),
			'our_back'  => __( 'Your link to the partner is back', 'linktrade-monitor' ),
		);

		$site  = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$lines = array();

		if ( empty( $events ) ) {
			/* translators: %s: site name */
			$subject = sprintf( __( '[%s] Link check: no changes', 'linktrade-monitor' ), $site );
			$lines[] = __( 'The weekly link check found no changes.', 'linktrade-monitor' );
		} else {
			$subject = sprintf(
				/* translators: 1: site name, 2: number of changes */
				_n( '[%1$s] Link check: %2$d change', '[%1$s] Link check: %2$d changes', count( $events ), 'linktrade-monitor' ),
				$site,
				count( $events )
			);
			$lines[] = __( 'The weekly link check found these changes:', 'linktrade-monitor' );

			foreach ( $labels as $type => $label ) {
				$group = array();
				foreach ( $events as $event ) {
					if ( $type === $event['type'] ) {
						$group[] = '  - ' . $event['partner'] . ': ' . $event['page'];
					}
				}
				if ( $group ) {
					$lines[] = '';
					$lines[] = $label . ':';
					$lines   = array_merge( $lines, $group );
				}
			}
		}

		global $wpdb;
		$table = $wpdb->prefix . 'linktrade_links';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fresh totals on custom table.
		$rows   = $wpdb->get_results( 'SELECT status, COUNT(*) AS amount FROM `' . esc_sql( $table ) . '` GROUP BY status' );
		$totals = array(
			'online'  => 0,
			'warning' => 0,
			'offline' => 0,
		);
		foreach ( (array) $rows as $row ) {
			if ( isset( $totals[ $row->status ] ) ) {
				$totals[ $row->status ] = (int) $row->amount;
			}
		}

		$lines[] = '';
		$lines[] = sprintf(
			/* translators: 1: links checked, 2: online, 3: warnings, 4: offline, 5: not verifiable */
			__( 'Checked: %1$d. Online: %2$d. Warnings: %3$d. Offline: %4$d. Could not be read: %5$d.', 'linktrade-monitor' ),
			(int) $run['checked'],
			$totals['online'],
			$totals['warning'],
			$totals['offline'],
			count( self::get_unreadable() )
		);
		$lines[] = '';
		$lines[] = admin_url( 'admin.php?page=linktrade-monitor' );

		$sent = wp_mail( $to, $subject, implode( "\n", $lines ) );

		return $sent ? 'sent' : 'failed';
	}

	/**
	 * Daily: remind of links that are about to expire.
	 *
	 * The reminder is only marked as sent after the mail really went out.
	 */
	public static function send_reminders() {
		if ( ! get_option( 'linktrade_email_notifications', 1 ) ) {
			return;
		}

		$to = self::recipient();
		if ( '' === $to ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'linktrade_links';
		$days  = max( 1, min( 90, absint( get_option( 'linktrade_reminder_days', 14 ) ) ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cron needs fresh rows from custom table.
		$expiring = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM `' . esc_sql( $table ) . '` WHERE end_date IS NOT NULL AND end_date <= %s AND end_date >= %s AND reminder_sent = 0',
				wp_date( 'Y-m-d', time() + ( $days * DAY_IN_SECONDS ) ),
				wp_date( 'Y-m-d' )
			)
		);

		if ( empty( $expiring ) ) {
			return;
		}

		$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

		$subject = sprintf(
			/* translators: 1: site name, 2: number of expiring links */
			_n( '[%1$s] %2$d link agreement expires soon', '[%1$s] %2$d link agreements expire soon', count( $expiring ), 'linktrade-monitor' ),
			$site,
			count( $expiring )
		);

		/* translators: %d: number of days */
		$message = sprintf( __( 'These link agreements end within the next %d days:', 'linktrade-monitor' ), $days ) . "\n\n";

		foreach ( $expiring as $link ) {
			$days_left = max( 0, (int) ceil( ( strtotime( $link->end_date ) - time() ) / DAY_IN_SECONDS ) );
			/* translators: 1: partner name, 2: end date, 3: days remaining */
			$message .= sprintf( __( '- %1$s: %2$s (%3$d days left)', 'linktrade-monitor' ), $link->partner_name, $link->end_date, $days_left ) . "\n";
		}

		$message .= "\n" . admin_url( 'admin.php?page=linktrade-monitor' );

		if ( ! wp_mail( $to, $subject, $message ) ) {
			return;
		}

		foreach ( $expiring as $link ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Update on custom table.
			$wpdb->update(
				$table,
				array(
					'reminder_sent'      => 1,
					'reminder_sent_date' => current_time( 'mysql' ),
				),
				array( 'id' => absint( $link->id ) ),
				array( '%d', '%s' ),
				array( '%d' )
			);
		}
	}

	/**
	 * Site Health: is the automatic check able to run?
	 *
	 * @param array $tests Registered tests.
	 * @return array
	 */
	public static function register_site_health( $tests ) {
		$tests['direct']['linktrade_schedule'] = array(
			'label' => __( 'Linktrade Monitor link check', 'linktrade-monitor' ),
			'test'  => array( __CLASS__, 'site_health_test' ),
		);

		return $tests;
	}

	/**
	 * Site Health test callback.
	 *
	 * @return array
	 */
	public static function site_health_test() {
		$state = self::schedule_state();

		$result = array(
			'label'       => __( 'The weekly link check is scheduled', 'linktrade-monitor' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => __( 'Linktrade Monitor', 'linktrade-monitor' ),
				'color' => 'blue',
			),
			'description' => '<p>' . esc_html__( 'Linktrade Monitor checks your links once a week in the background.', 'linktrade-monitor' ) . '</p>',
			'actions'     => '',
			'test'        => 'linktrade_schedule',
		);

		if ( '' !== $state['error'] || ! $state['next'] ) {
			$result['status']      = 'critical';
			$result['label']       = __( 'The weekly link check is not scheduled', 'linktrade-monitor' );
			$result['description'] = '<p>' . esc_html__( 'WordPress could not schedule the weekly link check, so your links are not being checked. Deactivate and reactivate Linktrade Monitor. If that does not help, ask your host whether WP-Cron is available.', 'linktrade-monitor' ) . '</p>';
		} elseif ( $state['next'] < time() - DAY_IN_SECONDS ) {
			$result['status']      = 'recommended';
			$result['label']       = __( 'The weekly link check is overdue', 'linktrade-monitor' );
			$result['description'] = '<p>' . esc_html__( 'The link check was due more than a day ago and has not run. WP-Cron only runs when somebody visits the site. On a site with few visitors, set up a real cron job that calls wp-cron.php.', 'linktrade-monitor' ) . '</p>';
		}

		return $result;
	}
}
