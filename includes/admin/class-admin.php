<?php
/**
 * Admin Area
 *
 * @package Linktrade_Monitor
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Linktrade_Admin
 */
if ( ! class_exists( 'Linktrade_Admin' ) ) {
	class Linktrade_Admin {

		/**
		 * Add admin menu
		 */
		public function add_admin_menu() {
			add_menu_page(
				__( 'Linktrade Monitor', 'linktrade-monitor' ),
				__( 'Linktrade', 'linktrade-monitor' ),
				'manage_options',
				'linktrade-monitor',
				array( $this, 'render_admin_page' ),
				'dashicons-admin-links',
				30
			);
		}

		/**
		 * Enqueue assets
		 *
		 * @param string $hook Current admin page hook.
		 */
		public function enqueue_assets( $hook ) {
			if ( 'toplevel_page_linktrade-monitor' !== $hook ) {
				return;
			}

			$css_version = LINKTRADE_VERSION . '.' . filemtime( LINKTRADE_PLUGIN_DIR . 'assets/css/admin.css' );
			$js_version  = LINKTRADE_VERSION . '.' . filemtime( LINKTRADE_PLUGIN_DIR . 'assets/js/admin.js' );

			wp_enqueue_style( 'linktrade-admin', LINKTRADE_PLUGIN_URL . 'assets/css/admin.css', array(), $css_version );
			wp_enqueue_script( 'linktrade-admin', LINKTRADE_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), $js_version, true );

			wp_localize_script(
				'linktrade-admin',
				'linktrade',
				array(
					'ajax_url'  => admin_url( 'admin-ajax.php' ),
					'links_url' => admin_url( 'admin.php?page=linktrade-monitor&tab=links' ),
					'add_url'   => admin_url( 'admin.php?page=linktrade-monitor&tab=add' ),
					'nonce'     => wp_create_nonce( 'linktrade_nonce' ),
					'strings'   => array(
						'confirm_delete'  => __( 'Really delete this link?', 'linktrade-monitor' ),
						'saving'          => __( 'Saving...', 'linktrade-monitor' ),
						'saved'           => __( 'Saved!', 'linktrade-monitor' ),
						'error'           => __( 'An error occurred', 'linktrade-monitor' ),
						'session'         => __( 'The request failed. Your session may have expired: reload the page and try again.', 'linktrade-monitor' ),
						'checking'        => __( 'Checking...', 'linktrade-monitor' ),
						'searching'       => __( 'Searching...', 'linktrade-monitor' ),
						'exporting'       => __( 'Exporting...', 'linktrade-monitor' ),
						'importing'       => __( 'Importing...', 'linktrade-monitor' ),
						'export_failed'   => __( 'Export failed.', 'linktrade-monitor' ),
						'import_failed'   => __( 'Import failed. Please try again.', 'linktrade-monitor' ),
						'select_file'     => __( 'Please select a CSV file.', 'linktrade-monitor' ),
						/* translators: %d: number of links */
						'exported'        => __( 'Exported %d links.', 'linktrade-monitor' ),
						'copied'          => __( 'Copied', 'linktrade-monitor' ),
						'copy'            => __( 'Copy text', 'linktrade-monitor' ),
						'use_this'        => __( 'Use this', 'linktrade-monitor' ),
						'no_changes'      => __( 'No status changes recorded yet.', 'linktrade-monitor' ),
						'no_checks'       => __( 'No checks recorded yet.', 'linktrade-monitor' ),
						'changes'         => __( 'Status changes', 'linktrade-monitor' ),
						'checks'          => __( 'Recent checks', 'linktrade-monitor' ),
						'history_for'     => __( 'History', 'linktrade-monitor' ),
						'message_for'     => __( 'Message to partner', 'linktrade-monitor' ),
						'message_hint'    => __( 'Nothing is sent from here. Copy the text into your own email and adjust it.', 'linktrade-monitor' ),
						'contact'         => __( 'Contact on record', 'linktrade-monitor' ),
						'partner_info'    => __( 'Partner Information', 'linktrade-monitor' ),
						'partner_name'    => __( 'Partner Name', 'linktrade-monitor' ),
						'partner_contact' => __( 'Contact (Email)', 'linktrade-monitor' ),
						'category'        => __( 'Category', 'linktrade-monitor' ),
						'cat_exchange'    => __( 'Link Exchange', 'linktrade-monitor' ),
						'cat_paid'        => __( 'Paid Link', 'linktrade-monitor' ),
						'cat_free'        => __( 'Free', 'linktrade-monitor' ),
						'incoming'        => __( 'Incoming Link', 'linktrade-monitor' ),
						'partner_url'     => __( 'Partner Page URL', 'linktrade-monitor' ),
						'target_url'      => __( 'Your Linked URL', 'linktrade-monitor' ),
						'anchor'          => __( 'Anchor Text', 'linktrade-monitor' ),
						'outgoing'        => __( 'Outgoing Link', 'linktrade-monitor' ),
						'backlink_url'    => __( 'Your Page URL', 'linktrade-monitor' ),
						'backlink_target' => __( 'Partner Target URL', 'linktrade-monitor' ),
						'more'            => __( 'Additional Info', 'linktrade-monitor' ),
						'start_date'      => __( 'Start Date', 'linktrade-monitor' ),
						'end_date'        => __( 'Expiration Date', 'linktrade-monitor' ),
						'partner_dr'      => __( 'Partner DR', 'linktrade-monitor' ),
						'my_dr'           => __( 'My DR', 'linktrade-monitor' ),
						'notes'           => __( 'Notes', 'linktrade-monitor' ),
						'save_changes'    => __( 'Save Changes', 'linktrade-monitor' ),
						'agreed_anchor'   => __( 'Agreed anchor text', 'linktrade-monitor' ),
						'follow_agreed'   => __( 'A normal, followed link was agreed', 'linktrade-monitor' ),
						'proof'           => __( 'Record for a complaint', 'linktrade-monitor' ),
						'proof_hint'      => __( 'What the checks found, with dates. Copy it into your message to the partner or seller.', 'linktrade-monitor' ),
						'none_selected'   => __( 'Select at least one link first.', 'linktrade-monitor' ),
						'choose_action'   => __( 'Choose what to do with the selected links.', 'linktrade-monitor' ),
						/* translators: %d: number of links */
						'confirm_bulk'    => __( 'Really delete %d links? Their history is deleted too.', 'linktrade-monitor' ),
						/* translators: 1: current number, 2: total */
						'bulk_progress'   => __( 'Checking %1$d of %2$d ...', 'linktrade-monitor' ),
						'bulk_done'       => __( 'All selected links were checked.', 'linktrade-monitor' ),
						/* translators: 1: current batch, 2: number of batches */
						'scan_progress'   => __( 'Searching, part %1$d of %2$d ...', 'linktrade-monitor' ),
						'scan_none'       => __( 'No links to other websites found that are not monitored yet.', 'linktrade-monitor' ),
						'scan_domain'     => __( 'Domain', 'linktrade-monitor' ),
						'scan_count'      => __( 'Pages', 'linktrade-monitor' ),
						'scan_example'    => __( 'Example', 'linktrade-monitor' ),
						'scan_add'        => __( 'Monitor', 'linktrade-monitor' ),
						'sending'         => __( 'Sending...', 'linktrade-monitor' ),
						'checking_file'   => __( 'Reading the file...', 'linktrade-monitor' ),
						'preview_title'   => __( 'First lines of the file', 'linktrade-monitor' ),
						'import_now'      => __( 'Import these links now', 'linktrade-monitor' ),
						'import_cancel'   => __( 'Cancel', 'linktrade-monitor' ),
						'import_nothing'  => __( 'There is nothing to import in this file.', 'linktrade-monitor' ),
					),
				)
			);
		}

		/**
		 * Render admin page
		 */
		public function render_admin_page() {
			// Sanitize tab parameter - no nonce needed for simple page navigation.
			$current_tab = 'dashboard';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab navigation only, no data modification.
			if ( isset( $_GET['tab'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab navigation only, no data modification.
				$current_tab = sanitize_key( wp_unslash( $_GET['tab'] ) );
			}

			$tabs  = $this->get_tabs();
			$stats = $this->get_stats();
			?>
		<div class="wrap linktrade-wrap">
			<!-- Animated Gradient Header -->
			<div class="linktrade-header">
				<div class="linktrade-header-content">
					<div class="linktrade-header-left">
						<div class="linktrade-admin-icon">
							<img src="<?php echo esc_url( LINKTRADE_PLUGIN_URL . 'assets/images/icon-128.png' ); ?>" alt="Linktrade Monitor" width="48" height="48">
						</div>
						<div class="linktrade-title-text">
							<h1><?php esc_html_e( 'Linktrade Monitor', 'linktrade-monitor' ); ?></h1>
							<div class="linktrade-title-meta">
								<span class="linktrade-version-badge"><?php echo esc_html( 'v' . LINKTRADE_VERSION ); ?></span>
								<span class="linktrade-status-dot"><?php esc_html_e( 'Active', 'linktrade-monitor' ); ?></span>
							</div>
						</div>
					</div>
					<div class="linktrade-header-right">
						<a href="https://wordpress.org/plugins/linktrade-monitor/" target="_blank" class="linktrade-header-btn">
							<span class="dashicons dashicons-book"></span>
							<?php esc_html_e( 'Docs', 'linktrade-monitor' ); ?>
						</a>
						<a href="https://wordpress.org/support/plugin/linktrade-monitor/" target="_blank" class="linktrade-header-btn">
							<span class="dashicons dashicons-format-chat"></span>
							<?php esc_html_e( 'Support', 'linktrade-monitor' ); ?>
						</a>
					</div>
				</div>
			</div>

			<nav class="linktrade-tabs">
				<?php foreach ( $tabs as $tab_id => $tab_data ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'tab', $tab_id, admin_url( 'admin.php?page=linktrade-monitor' ) ) ); ?>"
						class="linktrade-tab <?php echo $current_tab === $tab_id ? 'active' : ''; ?>">
						<span class="tab-icon dashicons dashicons-<?php echo esc_attr( $tab_data['icon'] ); ?>"></span>
						<?php echo esc_html( $tab_data['label'] ); ?>
						<?php if ( ! empty( $tab_data['badge'] ) ) : ?>
							<span class="tab-badge <?php echo esc_attr( isset( $tab_data['badge_class'] ) ? $tab_data['badge_class'] : '' ); ?>"><?php echo esc_html( $tab_data['badge'] ); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="linktrade-content">
				<?php if ( Linktrade_Extras::show_review_hint() ) : ?>
					<div class="linktrade-review" id="linktrade-review">
						<p>
							<?php
							printf(
								/* translators: %1$s: opening link tag, %2$s: closing link tag */
								esc_html__( 'Linktrade Monitor has been watching your links for a month now. If it is useful to you, a %1$sshort review on WordPress.org%2$s helps other site owners find it.', 'linktrade-monitor' ),
								'<a href="https://wordpress.org/support/plugin/linktrade-monitor/reviews/#new-post" target="_blank" rel="noopener">',
								'</a>'
							);
							?>
						</p>
						<button type="button" class="button-link" id="linktrade-review-dismiss"><?php esc_html_e( 'Do not show again', 'linktrade-monitor' ); ?></button>
					</div>
				<?php endif; ?>
					<?php
					switch ( $current_tab ) {
						case 'links':
							$this->render_links_tab();
							break;
						case 'add':
							$this->render_add_tab();
							break;
						case 'fairness':
							$this->render_fairness_tab();
							break;
						case 'import':
							$this->render_import_export_tab();
							break;
						case 'settings':
							$this->render_settings_tab();
							break;
						default:
							$this->render_dashboard_tab( $stats );
					}
					?>
			</div>
		</div>

			<?php $this->render_modals(); ?>
			<?php
		}


		/**
		 * Get link count
		 *
		 * @return int Number of links.
		 */
		private function get_link_count() {
			global $wpdb;
			$table_name = $wpdb->prefix . 'linktrade_links';
			$count      = wp_cache_get( 'linktrade_link_count' );
			if ( false === $count ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table with caching.
				$count = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $table_name ) . '`' );
				wp_cache_set( 'linktrade_link_count', $count, '', 300 );
			}
			return $count;
		}

		/**
		 * Get tabs
		 *
		 * @return array Tab configuration.
		 */
		private function get_tabs() {
			$stats = $this->get_quick_stats();

			return array(
				'dashboard' => array(
					'label' => __( 'Dashboard', 'linktrade-monitor' ),
					'icon'  => 'chart-bar',
				),
				'links'     => array(
					'label' => __( 'All Links', 'linktrade-monitor' ),
					'icon'  => 'admin-links',
					'badge' => $stats['total'],
				),
				'fairness'  => array(
					'label'       => __( 'Fairness', 'linktrade-monitor' ),
					'icon'        => 'image-flip-horizontal',
					'badge'       => $stats['unfair'] > 0 ? $stats['unfair'] : '',
					'badge_class' => $stats['unfair'] > 0 ? 'badge-warning' : '',
				),
				'add'       => array(
					'label' => __( 'New', 'linktrade-monitor' ),
					'icon'  => 'plus-alt2',
				),
				'import'    => array(
					'label' => __( 'Import/Export', 'linktrade-monitor' ),
					'icon'  => 'database-import',
				),
				'settings'  => array(
					'label' => __( 'Settings', 'linktrade-monitor' ),
					'icon'  => 'admin-generic',
				),
			);
		}

		/**
		 * Get quick stats
		 *
		 * @return array Quick statistics.
		 */
		private function get_quick_stats() {
			global $wpdb;
			$table_name = $wpdb->prefix . 'linktrade_links';

			$cache_key = 'linktrade_quick_stats';
			$stats     = wp_cache_get( $cache_key );

			if ( false === $stats ) {
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table with caching.
				$stats = array(
					'total'  => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $table_name ) . '`' ),
					'unfair' => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $table_name ) . "` WHERE category = 'exchange' AND fairness_score < 100" ),
				);
				// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
				wp_cache_set( $cache_key, $stats, '', 300 );
			}

			return $stats;
		}

		/**
		 * Get full stats
		 *
		 * @return array Full statistics.
		 */
		private function get_stats() {
			global $wpdb;
			$table_name = $wpdb->prefix . 'linktrade_links';

			$cache_key = 'linktrade_full_stats';
			$stats     = wp_cache_get( $cache_key );

			if ( false === $stats ) {
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table with caching.
				$stats = array(
					'total'     => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $table_name ) . '`' ),
					'online'    => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $table_name ) . "` WHERE status = 'online'" ),
					'warning'   => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $table_name ) . "` WHERE status = 'warning'" ),
					'offline'   => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $table_name ) . "` WHERE status = 'offline'" ),
					'unchecked' => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $table_name ) . "` WHERE status = 'unchecked'" ),
					'exchange'  => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $table_name ) . "` WHERE category = 'exchange'" ),
					'paid'      => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $table_name ) . "` WHERE category = 'paid'" ),
					'free'      => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $table_name ) . "` WHERE category = 'free'" ),
					'avg_dr'    => (float) $wpdb->get_var( 'SELECT AVG(domain_rating) FROM `' . esc_sql( $table_name ) . '` WHERE domain_rating > 0' ),
				);
				// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
				wp_cache_set( $cache_key, $stats, '', 300 );
			}

			return $stats;
		}

		/**
		 * Everything that needs the owner's attention, grouped by what to do.
		 *
		 * @return array Groups with label, hint and rows.
		 */
		private function get_attention_groups() {
			global $wpdb;
			$table_name = $wpdb->prefix . 'linktrade_links';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Display query on custom table.
			$links = $wpdb->get_results( 'SELECT * FROM `' . esc_sql( $table_name ) . '` ORDER BY partner_name ASC' );

			$unreadable = Linktrade_Runner::get_unreadable();
			$lost_since = Linktrade_Runner::since_map( 'status', 'offline' );
			$our_since  = Linktrade_Runner::since_map( 'backlink_status', 'offline' );
			$today      = strtotime( wp_date( 'Y-m-d' ) );

			$groups = array(
				'lost'       => array(
					'label' => __( 'Partner removed the link to you', 'linktrade-monitor' ),
					'hint'  => __( 'Ask the partner, or remove your link to them.', 'linktrade-monitor' ),
					'class' => 'red',
					'rows'  => array(),
				),
				'our'        => array(
					'label' => __( 'Your link to the partner is missing', 'linktrade-monitor' ),
					'hint'  => __( 'You owe this link. Put it back before the partner notices.', 'linktrade-monitor' ),
					'class' => 'red',
					'rows'  => array(),
				),
				'devalued'   => array(
					'label' => __( 'Link is there, but devalued', 'linktrade-monitor' ),
					'hint'  => __( 'nofollow, sponsored, a noindex page or a link to a different page pass little or no value.', 'linktrade-monitor' ),
					'class' => 'orange',
					'rows'  => array(),
				),
				'anchor'     => array(
					'label' => __( 'Anchor text differs from the agreement', 'linktrade-monitor' ),
					'hint'  => __( 'Ask the partner to restore the agreed text, or accept the new one by editing the link.', 'linktrade-monitor' ),
					'class' => 'orange',
					'rows'  => array(),
				),
				'expiring'   => array(
					'label' => __( 'Agreement ends soon or has ended', 'linktrade-monitor' ),
					'hint'  => __( 'Renew it or let it run out on purpose.', 'linktrade-monitor' ),
					'class' => 'orange',
					'rows'  => array(),
				),
				'unreadable' => array(
					'label' => __( 'Could not be checked', 'linktrade-monitor' ),
					'hint'  => __( 'The page blocks automated requests or the server did not answer. Look at it in your browser.', 'linktrade-monitor' ),
					'class' => 'grey',
					'rows'  => array(),
				),
			);

			foreach ( (array) $links as $link ) {
				$id = (int) $link->id;

				if ( 'offline' === $link->status ) {
					$groups['lost']['rows'][] = array(
						'link'  => $link,
						'page'  => $link->partner_url,
						'since' => isset( $lost_since[ $id ] ) ? $lost_since[ $id ] : '',
						'note'  => '',
					);
				} elseif ( Linktrade_Runner::is_devalued( $link ) ) {
					$groups['devalued']['rows'][] = array(
						'link'  => $link,
						'page'  => $link->partner_url,
						'since' => '',
						'note'  => $this->get_warning_reason( $link ),
					);
				}

				if ( Linktrade_Runner::anchor_differs( $link ) ) {
					$groups['anchor']['rows'][] = array(
						'link'  => $link,
						'page'  => $link->partner_url,
						'since' => '',
						/* translators: 1: anchor text found on the page, 2: agreed anchor text */
						'note'  => sprintf( __( 'found "%1$s", agreed "%2$s"', 'linktrade-monitor' ), $link->found_anchor, $link->anchor_text ),
					);
				}

				if ( 'exchange' === $link->category && ! empty( $link->backlink_url ) && 'offline' === $link->backlink_status ) {
					$groups['our']['rows'][] = array(
						'link'  => $link,
						'page'  => $link->backlink_url,
						'since' => isset( $our_since[ $id ] ) ? $our_since[ $id ] : '',
						'note'  => '',
					);
				}

				if ( ! empty( $link->end_date ) && '0000-00-00' !== $link->end_date ) {
					$days = (int) round( ( strtotime( $link->end_date ) - $today ) / DAY_IN_SECONDS );
					if ( $days <= 30 ) {
						$groups['expiring']['rows'][] = array(
							'link'  => $link,
							'page'  => $link->partner_url,
							'since' => '',
							/* translators: %d: number of days */
							'note'  => $days < 0 ? __( 'ended', 'linktrade-monitor' ) : sprintf( _n( '%d day left', '%d days left', $days, 'linktrade-monitor' ), $days ),
						);
					}
				}

				foreach ( array(
					'incoming' => $link->partner_url,
					'outgoing' => $link->backlink_url,
				) as $direction => $page ) {
					if ( isset( $unreadable[ $id . ':' . $direction ] ) ) {
						$groups['unreadable']['rows'][] = array(
							'link'  => $link,
							'page'  => $page,
							'since' => $unreadable[ $id . ':' . $direction ]['since'],
							'note'  => $unreadable[ $id . ':' . $direction ]['message'],
						);
					}
				}
			}

			return $groups;
		}

		/**
		 * Why a link carries a warning, in words.
		 *
		 * @param object $link Link row.
		 * @return string
		 */
		private function get_warning_reason( $link ) {
			$reasons = array();

			if ( ! empty( $link->is_nofollow ) ) {
				$reasons[] = 'nofollow';
			}
			if ( ! empty( $link->is_sponsored ) ) {
				$reasons[] = 'sponsored';
			}
			if ( ! empty( $link->is_noindex ) ) {
				$reasons[] = 'noindex';
			}
			if ( empty( $reasons ) && ! empty( $link->redirect_url ) ) {
				$reasons[] = __( 'points to another page', 'linktrade-monitor' );
			}

			return implode( ', ', $reasons );
		}

		/**
		 * A date as "3 days ago", or an empty string.
		 *
		 * @param string $mysql_date Local MySQL datetime.
		 * @return string
		 */
		private function time_ago( $mysql_date ) {
			if ( empty( $mysql_date ) ) {
				return '';
			}

			/* translators: %s: human readable time difference */
			return sprintf( __( '%s ago', 'linktrade-monitor' ), human_time_diff( strtotime( $mysql_date ), strtotime( current_time( 'mysql' ) ) ) );
		}

		/**
		 * A date as the time that has passed since, e.g. "3 days".
		 *
		 * @param string $mysql_date Local MySQL datetime.
		 * @return string
		 */
		private function duration( $mysql_date ) {
			return empty( $mysql_date ) ? '' : human_time_diff( strtotime( $mysql_date ), strtotime( current_time( 'mysql' ) ) );
		}

		/**
		 * Render the box that says whether and when links are checked.
		 */
		private function render_schedule_box() {
			$state = Linktrade_Runner::schedule_state();
			?>
		<div class="linktrade-card lt-schedule">
			<h2><span class="dashicons dashicons-clock card-icon"></span> <?php esc_html_e( 'Automatic check', 'linktrade-monitor' ); ?></h2>
			<?php if ( '' !== $state['error'] || ! $state['next'] ) : ?>
				<p class="lt-schedule-error"><strong><?php esc_html_e( 'The weekly check is not scheduled, so your links are not being checked.', 'linktrade-monitor' ); ?></strong>
				<?php esc_html_e( 'Deactivate and reactivate the plugin. If that does not help, ask your host whether WP-Cron is available.', 'linktrade-monitor' ); ?></p>
			<?php else : ?>
				<ul class="lt-schedule-list">
					<li>
						<span><?php esc_html_e( 'Next check', 'linktrade-monitor' ); ?></span>
						<strong><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $state['next'] ) ); ?></strong>
					</li>
					<li>
						<span><?php esc_html_e( 'Last complete check', 'linktrade-monitor' ); ?></span>
						<strong>
						<?php
						if ( ! empty( $state['last']['finished'] ) ) {
							echo esc_html( $this->time_ago( $state['last']['finished'] ) );
							/* translators: 1: number of links, 2: number of changes */
							echo ' <small>' . esc_html( sprintf( __( '(%1$d links, %2$d changes)', 'linktrade-monitor' ), (int) $state['last']['checked'], (int) $state['last']['changes'] ) ) . '</small>';
						} else {
							esc_html_e( 'not yet', 'linktrade-monitor' );
						}
						?>
						</strong>
					</li>
					<?php if ( $state['running'] ) : ?>
						<li>
							<span><?php esc_html_e( 'Check in progress', 'linktrade-monitor' ); ?></span>
							<?php /* translators: %d: number of links */ ?>
							<strong><?php echo esc_html( sprintf( _n( '%d link to go', '%d links to go', $state['remaining'], 'linktrade-monitor' ), $state['remaining'] ) ); ?></strong>
						</li>
					<?php endif; ?>
				</ul>
				<?php if ( ! empty( $state['last']['mail'] ) && 'failed' === $state['last']['mail'] ) : ?>
					<p class="lt-schedule-error"><?php esc_html_e( 'The last report could not be sent by email. Check the notification address in the settings and whether this site can send mail.', 'linktrade-monitor' ); ?></p>
				<?php endif; ?>
				<?php if ( $state['cron_off'] ) : ?>
					<p class="description"><?php esc_html_e( 'WP-Cron is switched off on this site (DISABLE_WP_CRON). The check only runs if a real cron job calls wp-cron.php.', 'linktrade-monitor' ); ?></p>
				<?php endif; ?>
			<?php endif; ?>
			<p class="description"><?php esc_html_e( 'Links are checked once a week, a few at a time, so the check also finishes on small hosting plans.', 'linktrade-monitor' ); ?></p>
		</div>
			<?php
		}

		/**
		 * Render dashboard tab
		 *
		 * @param array $stats Statistics array.
		 */
		private function render_dashboard_tab( $stats ) {
			if ( 0 === $stats['total'] ) {
				$this->render_first_steps();
				return;
			}

			$groups    = $this->get_attention_groups();
			$attention = 0;
			foreach ( $groups as $group ) {
				$attention += count( $group['rows'] );
			}
			?>
		<div class="lt-stats-grid">
			<div class="lt-stat-card stat-blue">
				<div class="lt-stat-value"><?php echo esc_html( $stats['total'] ); ?></div>
				<div class="lt-stat-label"><?php esc_html_e( 'Total Links', 'linktrade-monitor' ); ?></div>
			</div>
			<div class="lt-stat-card stat-green">
				<div class="lt-stat-value"><?php echo esc_html( $stats['online'] ); ?></div>
				<div class="lt-stat-label"><?php esc_html_e( 'Online', 'linktrade-monitor' ); ?></div>
			</div>
			<div class="lt-stat-card stat-orange">
				<div class="lt-stat-value"><?php echo esc_html( $stats['warning'] ); ?></div>
				<div class="lt-stat-label"><?php esc_html_e( 'Warnings', 'linktrade-monitor' ); ?></div>
			</div>
			<div class="lt-stat-card stat-red">
				<div class="lt-stat-value"><?php echo esc_html( $stats['offline'] ); ?></div>
				<div class="lt-stat-label"><?php esc_html_e( 'Offline', 'linktrade-monitor' ); ?></div>
			</div>
		</div>

		<div class="linktrade-card lt-attention">
			<h2><span class="dashicons dashicons-flag card-icon"></span> <?php esc_html_e( 'Needs your attention', 'linktrade-monitor' ); ?></h2>

			<?php if ( 0 === $attention ) : ?>
				<p class="lt-attention-ok"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Nothing to do. Every link is where it should be.', 'linktrade-monitor' ); ?></p>
			<?php else : ?>
				<?php foreach ( $groups as $key => $group ) : ?>
					<?php
					if ( empty( $group['rows'] ) ) {
						continue;
					}
					?>
					<div class="lt-attention-group lt-attention-<?php echo esc_attr( $group['class'] ); ?>">
						<h3><?php echo esc_html( $group['label'] ); ?> <span class="lt-attention-count"><?php echo esc_html( count( $group['rows'] ) ); ?></span></h3>
						<p class="description"><?php echo esc_html( $group['hint'] ); ?></p>
						<ul>
							<?php foreach ( array_slice( $group['rows'], 0, 15 ) as $row ) : ?>
								<li data-id="<?php echo esc_attr( $row['link']->id ); ?>">
									<span class="lt-attention-main">
										<strong><?php echo esc_html( $row['link']->partner_name ); ?></strong>
										<a href="<?php echo esc_url( $row['page'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $this->truncate_url( $row['page'], 55 ) ); ?></a>
										<?php if ( '' !== $row['note'] ) : ?>
											<em><?php echo esc_html( $row['note'] ); ?></em>
										<?php endif; ?>
										<?php if ( '' !== $row['since'] ) : ?>
											<?php /* translators: %s: duration, e.g. "3 days" */ ?>
											<small><?php echo esc_html( sprintf( __( 'for %s', 'linktrade-monitor' ), $this->duration( $row['since'] ) ) ); ?></small>
										<?php endif; ?>
									</span>
									<span class="lt-attention-actions">
										<?php if ( in_array( $key, array( 'lost', 'devalued', 'anchor' ), true ) ) : ?>
											<button type="button" class="button button-small linktrade-message" data-id="<?php echo esc_attr( $row['link']->id ); ?>"><?php esc_html_e( 'Message to partner', 'linktrade-monitor' ); ?></button>
										<?php endif; ?>
										<button type="button" class="button button-small linktrade-check-now" data-id="<?php echo esc_attr( $row['link']->id ); ?>"><?php esc_html_e( 'Check now', 'linktrade-monitor' ); ?></button>
									</span>
								</li>
							<?php endforeach; ?>
							<?php if ( count( $group['rows'] ) > 15 ) : ?>
								<li class="lt-attention-more">
									<?php
									$more_status = array(
										'lost'       => 'offline',
										'devalued'   => 'warning',
										'unreadable' => 'unreadable',
									);
									$more_url    = admin_url( 'admin.php?page=linktrade-monitor&tab=links' . ( isset( $more_status[ $key ] ) ? '&f_status=' . $more_status[ $key ] : '' ) );
									?>
									<a href="<?php echo esc_url( $more_url ); ?>">
										<?php
										/* translators: %d: number of further links */
										echo esc_html( sprintf( _n( 'and %d more in the list', 'and %d more in the list', count( $group['rows'] ) - 15, 'linktrade-monitor' ), count( $group['rows'] ) - 15 ) );
										?>
									</a>
								</li>
							<?php endif; ?>
						</ul>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>

		<div class="lt-content-grid">
				<?php $this->render_schedule_box(); ?>

			<div class="linktrade-card">
				<h2><span class="dashicons dashicons-category card-icon"></span> <?php esc_html_e( 'By Category', 'linktrade-monitor' ); ?></h2>
				<div class="lt-category-list">
					<div class="lt-category-item">
						<div class="lt-category-info">
							<h3 class="lt-category-title"><?php esc_html_e( 'Link Exchanges', 'linktrade-monitor' ); ?></h3>
							<span class="lt-category-meta"><?php esc_html_e( 'Reciprocal links', 'linktrade-monitor' ); ?></span>
						</div>
						<div class="lt-category-count"><?php echo esc_html( $stats['exchange'] ); ?></div>
					</div>
					<div class="lt-category-item">
						<div class="lt-category-info">
							<h3 class="lt-category-title"><?php esc_html_e( 'Paid Links', 'linktrade-monitor' ); ?></h3>
							<span class="lt-category-meta"><?php esc_html_e( 'Purchased backlinks', 'linktrade-monitor' ); ?></span>
						</div>
						<div class="lt-category-count"><?php echo esc_html( $stats['paid'] ); ?></div>
					</div>
					<div class="lt-category-item">
						<div class="lt-category-info">
							<h3 class="lt-category-title"><?php esc_html_e( 'Free Backlinks', 'linktrade-monitor' ); ?></h3>
							<span class="lt-category-meta"><?php esc_html_e( 'Guest posts, directories', 'linktrade-monitor' ); ?></span>
						</div>
						<div class="lt-category-count"><?php echo esc_html( $stats['free'] ); ?></div>
					</div>
				</div>
			</div>
		</div>

		<p class="lt-pro-line">
				<?php
				printf(
				/* translators: %1$s: opening link tag, %2$s: closing link tag */
					esc_html__( 'Managing several websites or buying links regularly? %1$sLinktrade Monitor Pro%2$s adds daily checks, projects for several sites and cost tracking.', 'linktrade-monitor' ),
					'<a href="' . esc_url( linktrade_pro_url() ) . '" target="_blank" rel="noopener">',
					'</a>'
				);
				?>
		</p>
			<?php
		}

		/**
		 * First screen for a site without links: three fields, one result.
		 */
		private function render_first_steps() {
			?>
		<div class="linktrade-card lt-first-steps">
			<h2><?php esc_html_e( 'Check your first link', 'linktrade-monitor' ); ?></h2>
			<p><?php esc_html_e( 'Enter the page that links to you and the page of yours it should link to. The link is checked right away, and from then on once a week.', 'linktrade-monitor' ); ?></p>

			<form id="linktrade-add-form" class="linktrade-form">
				<input type="hidden" name="category" value="free">
				<input type="hidden" name="follow_agreed" value="1">
				<input type="hidden" name="start_date" value="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>">
				<div class="form-row">
					<label for="partner_name"><?php esc_html_e( 'Partner Name', 'linktrade-monitor' ); ?> *</label>
					<input type="text" id="partner_name" name="partner_name" required placeholder="<?php esc_attr_e( 'e.g. Partner Name', 'linktrade-monitor' ); ?>">
				</div>
				<div class="form-row">
					<label for="partner_url"><?php esc_html_e( 'Partner Page URL', 'linktrade-monitor' ); ?> *</label>
					<input type="url" id="partner_url" name="partner_url" required placeholder="https://partner-site.com/page">
					<span class="lt-field-hint"><?php esc_html_e( 'The exact page on which the link to you stands.', 'linktrade-monitor' ); ?></span>
				</div>
				<div class="form-row">
					<label for="target_url"><?php esc_html_e( 'Your Linked URL', 'linktrade-monitor' ); ?> *</label>
					<input type="url" id="target_url" name="target_url" required value="<?php echo esc_attr( home_url( '/' ) ); ?>">
				</div>
				<div class="form-actions">
					<button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save and check', 'linktrade-monitor' ); ?></button>
				</div>
			</form>

			<p class="description">
				<?php
				printf(
					/* translators: %1$s, %3$s: opening link tags, %2$s, %4$s: closing link tags */
					esc_html__( 'A link exchange with a link back, an expiry date or notes? Use the %1$sfull form%2$s. Many links at once? %3$sImport a CSV file%4$s.', 'linktrade-monitor' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=linktrade-monitor&tab=add' ) ) . '">',
					'</a>',
					'<a href="' . esc_url( admin_url( 'admin.php?page=linktrade-monitor&tab=import' ) ) . '">',
					'</a>'
				);
				?>
			</p>
		</div>
			<?php
		}

		/**
		 * Filters, search, sorting and page of the links list, from the address bar.
		 *
		 * @return array
		 */
		private function get_list_args() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only list navigation, no data is changed.
			$args = array(
				's'        => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
				'category' => isset( $_GET['f_category'] ) ? sanitize_key( wp_unslash( $_GET['f_category'] ) ) : '',
				'status'   => isset( $_GET['f_status'] ) ? sanitize_key( wp_unslash( $_GET['f_status'] ) ) : '',
				'orderby'  => isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'created',
				'order'    => ( isset( $_GET['order'] ) && 'asc' === strtolower( sanitize_key( wp_unslash( $_GET['order'] ) ) ) ) ? 'asc' : 'desc',
				'paged'    => isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1,
			);
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			if ( ! in_array( $args['category'], array( 'exchange', 'paid', 'free' ), true ) ) {
				$args['category'] = '';
			}
			if ( ! in_array( $args['status'], array( 'online', 'warning', 'offline', 'unchecked', 'unreadable' ), true ) ) {
				$args['status'] = '';
			}
			if ( ! isset( $this->sortable_columns()[ $args['orderby'] ] ) ) {
				$args['orderby'] = 'created';
			}

			return $args;
		}

		/**
		 * Columns the list can be sorted by: key in the address bar => table column.
		 *
		 * @return array
		 */
		private function sortable_columns() {
			return array(
				'created'  => 'created_at',
				'partner'  => 'partner_name',
				'category' => 'category',
				'status'   => 'status',
				'start'    => 'start_date',
				'dr'       => 'domain_rating',
				'check'    => 'last_check',
			);
		}

		/**
		 * A column heading that sorts the list when clicked.
		 *
		 * @param string $key   Sort key.
		 * @param string $label Heading.
		 * @param array  $args  Current list arguments.
		 */
		private function sort_heading( $key, $label, $args ) {
			$active = ( $args['orderby'] === $key );
			$next   = ( $active && 'asc' === $args['order'] ) ? 'desc' : 'asc';
			$url    = add_query_arg(
				array(
					'orderby' => $key,
					'order'   => $next,
					'paged'   => false,
				)
			);
			$sort   = 'none';
			$arrow  = '';
			if ( $active ) {
				$sort  = ( 'asc' === $args['order'] ) ? 'ascending' : 'descending';
				$arrow = ( 'asc' === $args['order'] ) ? "\u{25B2}" : "\u{25BC}";
			}

			printf(
				'<th scope="col" aria-sort="%1$s"><a class="%2$s" href="%3$s">%4$s<span class="lt-sort-arrow" aria-hidden="true">%5$s</span></a></th>',
				esc_attr( $sort ),
				esc_attr( $active ? 'lt-sort is-active' : 'lt-sort' ),
				esc_url( $url ),
				esc_html( $label ),
				esc_html( $arrow )
			);
		}

		/**
		 * Render links tab
		 */
		private function render_links_tab() {
			$args = $this->get_list_args();
			?>
		<div class="linktrade-card">
			<div class="linktrade-table-header">
				<h3><?php esc_html_e( 'Link Overview', 'linktrade-monitor' ); ?></h3>
				<form method="get" class="linktrade-filters" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
					<input type="hidden" name="page" value="linktrade-monitor">
					<input type="hidden" name="tab" value="links">
					<input type="hidden" name="orderby" value="<?php echo esc_attr( $args['orderby'] ); ?>">
					<input type="hidden" name="order" value="<?php echo esc_attr( $args['order'] ); ?>">
					<label class="screen-reader-text" for="linktrade-search"><?php esc_html_e( 'Search links', 'linktrade-monitor' ); ?></label>
					<input type="search" id="linktrade-search" name="s" class="linktrade-search" value="<?php echo esc_attr( $args['s'] ); ?>"
							placeholder="<?php esc_attr_e( 'Search links...', 'linktrade-monitor' ); ?>">
					<label class="screen-reader-text" for="linktrade-filter-category"><?php esc_html_e( 'Category', 'linktrade-monitor' ); ?></label>
					<select id="linktrade-filter-category" name="f_category" class="linktrade-select">
						<option value=""><?php esc_html_e( 'All Categories', 'linktrade-monitor' ); ?></option>
						<option value="exchange" <?php selected( $args['category'], 'exchange' ); ?>><?php esc_html_e( 'Link Exchange', 'linktrade-monitor' ); ?></option>
						<option value="paid" <?php selected( $args['category'], 'paid' ); ?>><?php esc_html_e( 'Paid Links', 'linktrade-monitor' ); ?></option>
						<option value="free" <?php selected( $args['category'], 'free' ); ?>><?php esc_html_e( 'Free', 'linktrade-monitor' ); ?></option>
					</select>
					<label class="screen-reader-text" for="linktrade-filter-status"><?php esc_html_e( 'Status', 'linktrade-monitor' ); ?></label>
					<select id="linktrade-filter-status" name="f_status" class="linktrade-select">
						<option value=""><?php esc_html_e( 'All Status', 'linktrade-monitor' ); ?></option>
						<option value="online" <?php selected( $args['status'], 'online' ); ?>><?php esc_html_e( 'Online', 'linktrade-monitor' ); ?></option>
						<option value="warning" <?php selected( $args['status'], 'warning' ); ?>><?php esc_html_e( 'Warning', 'linktrade-monitor' ); ?></option>
						<option value="offline" <?php selected( $args['status'], 'offline' ); ?>><?php esc_html_e( 'Offline', 'linktrade-monitor' ); ?></option>
						<option value="unchecked" <?php selected( $args['status'], 'unchecked' ); ?>><?php esc_html_e( 'Unchecked', 'linktrade-monitor' ); ?></option>
						<option value="unreadable" <?php selected( $args['status'], 'unreadable' ); ?>><?php esc_html_e( 'Not verifiable', 'linktrade-monitor' ); ?></option>
					</select>
					<button type="submit" class="button"><?php esc_html_e( 'Filter', 'linktrade-monitor' ); ?></button>
				</form>
			</div>
			<?php $this->render_links_table( $args ); ?>
		</div>
			<?php
		}

		/**
		 * Render links table
		 *
		 * @param array $args List arguments from get_list_args().
		 */
		private function render_links_table( $args ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'linktrade_links';
			$per_page   = 50;

			$unreadable = Linktrade_Runner::get_unreadable();
			$lost_since = Linktrade_Runner::since_map( 'status', 'offline' );

			// Build the WHERE part from fixed fragments and prepared values only.
			$where  = array( '1=1' );
			$values = array();

			if ( '' !== $args['s'] ) {
				$like     = '%' . $wpdb->esc_like( $args['s'] ) . '%';
				$where[]  = '( partner_name LIKE %s OR partner_url LIKE %s OR target_url LIKE %s OR notes LIKE %s )';
				$values[] = $like;
				$values[] = $like;
				$values[] = $like;
				$values[] = $like;
			}
			if ( '' !== $args['category'] ) {
				$where[]  = 'category = %s';
				$values[] = $args['category'];
			}
			if ( 'unreadable' === $args['status'] ) {
				$ids = array( 0 );
				foreach ( array_keys( $unreadable ) as $key ) {
					$ids[] = (int) $key;
				}
				$where[] = 'id IN (' . implode( ',', array_map( 'absint', array_unique( $ids ) ) ) . ')';
			} elseif ( '' !== $args['status'] ) {
				$where[]  = 'status = %s';
				$values[] = $args['status'];
			}

			$columns   = $this->sortable_columns();
			$order_sql = '`' . esc_sql( $columns[ $args['orderby'] ] ) . '` ' . ( 'asc' === $args['order'] ? 'ASC' : 'DESC' ) . ', id DESC';
			$where_sql = implode( ' AND ', $where );

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, PluginCheck.Security.DirectDB.UnescapedDBParameter -- List query on the plugin's own table. The WHERE and ORDER parts are assembled from fixed fragments and whitelisted column names, every value goes through prepare().
			$total = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM `' . esc_sql( $table_name ) . '` WHERE 1 = %d AND ' . $where_sql,
					array_merge( array( 1 ), $values )
				)
			);
			// A page number beyond the end shows the last page, not an empty list.
			$pages         = max( 1, (int) ceil( $total / $per_page ) );
			$args['paged'] = min( $args['paged'], $pages );
			$offset        = ( $args['paged'] - 1 ) * $per_page;

			$links = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM `' . esc_sql( $table_name ) . '` WHERE ' . $where_sql . ' ORDER BY ' . $order_sql . ' LIMIT %d OFFSET %d',
					array_merge( $values, array( $per_page, $offset ) )
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, PluginCheck.Security.DirectDB.UnescapedDBParameter

			$filtered = ( '' !== $args['s'] || '' !== $args['category'] || '' !== $args['status'] );
			?>
		<div class="linktrade-bulk">
			<label class="screen-reader-text" for="linktrade-bulk-action"><?php esc_html_e( 'Action for selected links', 'linktrade-monitor' ); ?></label>
			<select id="linktrade-bulk-action">
				<option value=""><?php esc_html_e( 'Selected links ...', 'linktrade-monitor' ); ?></option>
				<option value="check"><?php esc_html_e( 'Check now', 'linktrade-monitor' ); ?></option>
				<option value="delete"><?php esc_html_e( 'Delete', 'linktrade-monitor' ); ?></option>
			</select>
			<button type="button" class="button" id="linktrade-bulk-apply"><?php esc_html_e( 'Apply', 'linktrade-monitor' ); ?></button>
			<span class="linktrade-bulk-info">
				<?php
				/* translators: %d: number of links */
				echo esc_html( sprintf( _n( '%d link', '%d links', $total, 'linktrade-monitor' ), $total ) );
				?>
				<?php if ( $filtered ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=linktrade-monitor&tab=links' ) ); ?>"><?php esc_html_e( 'Show all', 'linktrade-monitor' ); ?></a>
				<?php endif; ?>
			</span>
		</div>
		<div class="linktrade-table-scroll">
		<table class="linktrade-table">
			<thead>
				<tr>
					<td class="lt-check-col"><input type="checkbox" id="linktrade-select-all" aria-label="<?php esc_attr_e( 'Select all links on this page', 'linktrade-monitor' ); ?>"></td>
					<?php $this->sort_heading( 'partner', __( 'Partner', 'linktrade-monitor' ), $args ); ?>
					<?php $this->sort_heading( 'category', __( 'Category', 'linktrade-monitor' ), $args ); ?>
					<?php $this->sort_heading( 'status', __( 'Status', 'linktrade-monitor' ), $args ); ?>
					<th><?php esc_html_e( 'Health', 'linktrade-monitor' ); ?></th>
					<?php $this->sort_heading( 'start', __( 'Start / Expiration', 'linktrade-monitor' ), $args ); ?>
					<?php $this->sort_heading( 'dr', __( 'DR', 'linktrade-monitor' ), $args ); ?>
					<?php $this->sort_heading( 'check', __( 'Last Check', 'linktrade-monitor' ), $args ); ?>
					<th><?php esc_html_e( 'Actions', 'linktrade-monitor' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $links ) ) : ?>
					<tr>
						<td colspan="9" class="linktrade-empty">
							<?php $filtered ? esc_html_e( 'No links match this search.', 'linktrade-monitor' ) : esc_html_e( 'No links found. Add your first link!', 'linktrade-monitor' ); ?>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $links as $link ) : ?>
						<?php
						$health_score = $this->calculate_link_health_score( $link );
						$health_class = $this->get_health_score_class( $health_score );
						?>
						<tr data-id="<?php echo esc_attr( $link->id ); ?>">
							<td class="lt-check-col">
								<?php
								/* translators: %s: partner name */
								$select_label = sprintf( __( 'Select %s', 'linktrade-monitor' ), $link->partner_name );
								?>
								<input type="checkbox" class="linktrade-row-check" value="<?php echo esc_attr( $link->id ); ?>" aria-label="<?php echo esc_attr( $select_label ); ?>">
							</td>
							<td>
								<strong><?php echo esc_html( $link->partner_name ); ?></strong>
								<br><small><?php echo esc_html( $this->truncate_url( $link->partner_url ) ); ?></small>
								<?php if ( 'online' === $link->status && ! empty( $link->redirect_url ) ) : ?>
									<?php /* translators: %s: URL the partner page redirects to */ ?>
									<br><small class="lt-redirect" title="<?php echo esc_attr( $link->redirect_url ); ?>"><?php echo esc_html( sprintf( __( 'redirects to %s', 'linktrade-monitor' ), $this->truncate_url( $link->redirect_url, 34 ) ) ); ?></small>
								<?php endif; ?>
							</td>
							<td>
								<span class="category-tag <?php echo esc_attr( $link->category ); ?>">
									<?php echo esc_html( $this->get_category_label( $link->category ) ); ?>
								</span>
							</td>
							<td>
								<?php echo wp_kses_post( $this->render_status_badge( $link ) ); ?>
								<?php if ( 'offline' === $link->status && isset( $lost_since[ (int) $link->id ] ) ) : ?>
									<?php /* translators: %s: duration, e.g. "3 days" */ ?>
									<br><small><?php echo esc_html( sprintf( __( 'for %s', 'linktrade-monitor' ), $this->duration( $lost_since[ (int) $link->id ] ) ) ); ?></small>
								<?php endif; ?>
								<?php if ( isset( $unreadable[ $link->id . ':incoming' ] ) ) : ?>
									<br><span class="status unreadable" title="<?php echo esc_attr( $unreadable[ $link->id . ':incoming' ]['message'] ); ?>"><span class="status-dot"></span><?php esc_html_e( 'Not verifiable', 'linktrade-monitor' ); ?></span>
									<?php /* translators: %s: duration, e.g. "3 days" */ ?>
									<br><small><?php echo esc_html( sprintf( __( 'for %s', 'linktrade-monitor' ), $this->duration( $unreadable[ $link->id . ':incoming' ]['since'] ) ) ); ?></small>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( 'unchecked' === $link->status ) : ?>
									<span aria-hidden="true">-</span>
								<?php else : ?>
								<div class="health-score <?php echo esc_attr( $health_class ); ?>" title="<?php esc_attr_e( 'Link health from 0 to 100: status, nofollow and noindex, Domain Rating, age and fairness', 'linktrade-monitor' ); ?>">
									<span class="health-value"><?php echo esc_html( $health_score ); ?></span>
								</div>
								<?php endif; ?>
							</td>
							<td>
								<?php echo wp_kses_post( $this->render_date_info( $link ) ); ?>
							</td>
							<td>
								<?php if ( $link->domain_rating ) : ?>
									<span class="dr-badge"><?php echo esc_html( $link->domain_rating ); ?></span>
								<?php else : ?>
									-
								<?php endif; ?>
							</td>
							<td>
								<?php
								if ( $link->last_check ) {
									echo esc_html( $this->time_ago( $link->last_check ) );
								} else {
									esc_html_e( 'Never', 'linktrade-monitor' );
								}
								?>
							</td>
							<td class="actions">
								<button type="button" class="action-btn linktrade-check-now" data-id="<?php echo esc_attr( $link->id ); ?>" title="<?php esc_attr_e( 'Check now', 'linktrade-monitor' ); ?>" aria-label="<?php esc_attr_e( 'Check now', 'linktrade-monitor' ); ?>">
									<span class="dashicons dashicons-update"></span>
								</button>
								<button type="button" class="action-btn linktrade-history" data-id="<?php echo esc_attr( $link->id ); ?>" title="<?php esc_attr_e( 'History', 'linktrade-monitor' ); ?>" aria-label="<?php esc_attr_e( 'History', 'linktrade-monitor' ); ?>">
									<span class="dashicons dashicons-backup"></span>
								</button>
								<?php if ( 'offline' === $link->status || Linktrade_Runner::is_devalued( $link ) || Linktrade_Runner::anchor_differs( $link ) ) : ?>
								<button type="button" class="action-btn linktrade-message" data-id="<?php echo esc_attr( $link->id ); ?>" title="<?php esc_attr_e( 'Message to partner', 'linktrade-monitor' ); ?>" aria-label="<?php esc_attr_e( 'Message to partner', 'linktrade-monitor' ); ?>">
									<span class="dashicons dashicons-email"></span>
								</button>
								<?php endif; ?>
								<button type="button" class="action-btn linktrade-edit" data-id="<?php echo esc_attr( $link->id ); ?>" title="<?php esc_attr_e( 'Edit', 'linktrade-monitor' ); ?>">
									<span class="dashicons dashicons-edit"></span>
								</button>
								<button type="button" class="action-btn danger linktrade-delete" data-id="<?php echo esc_attr( $link->id ); ?>" title="<?php esc_attr_e( 'Delete', 'linktrade-monitor' ); ?>">
									<span class="dashicons dashicons-trash"></span>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
		</div>
			<?php if ( $pages > 1 ) : ?>
			<nav class="linktrade-pagination" aria-label="<?php esc_attr_e( 'Pages of the link list', 'linktrade-monitor' ); ?>">
				<?php
				echo wp_kses_post(
					paginate_links(
						array(
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'current'   => $args['paged'],
							'total'     => $pages,
							'prev_text' => '&lsaquo;',
							'next_text' => '&rsaquo;',
						)
					)
				);
				?>
			</nav>
		<?php endif; ?>
			<?php
		}

		/**
		 * Render fairness tab
		 */
		private function render_fairness_tab() {
			global $wpdb;
			$table_name = $wpdb->prefix . 'linktrade_links';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Display query for exchange links.
			$links = $wpdb->get_results(
				'SELECT * FROM `' . esc_sql( $table_name ) . "` WHERE category = 'exchange' ORDER BY fairness_score ASC, partner_name ASC"
			);
			?>
		<div class="linktrade-card">
			<div class="linktrade-table-header">
				<h3><?php esc_html_e( 'Reciprocity Tracker', 'linktrade-monitor' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Monitor if both sides of link exchanges are being fair.', 'linktrade-monitor' ); ?></p>
			</div>

			<div class="fairness-legend">
				<span class="legend-item"><span class="fairness-dot fair"></span> <?php esc_html_e( 'Fair (both online)', 'linktrade-monitor' ); ?></span>
				<span class="legend-item"><span class="fairness-dot unfair"></span> <?php esc_html_e( 'Unfair (one link is missing)', 'linktrade-monitor' ); ?></span>
				<span class="legend-item"><span class="fairness-dot warning"></span> <?php esc_html_e( 'Warning (nofollow etc.)', 'linktrade-monitor' ); ?></span>
			</div>

			<div class="linktrade-table-scroll">
			<table class="linktrade-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Partner', 'linktrade-monitor' ); ?></th>
						<th><?php esc_html_e( 'Their Link to You', 'linktrade-monitor' ); ?></th>
						<th><?php esc_html_e( 'Your Link to Them', 'linktrade-monitor' ); ?></th>
						<th><?php esc_html_e( 'DR', 'linktrade-monitor' ); ?></th>
						<th><?php esc_html_e( 'Fairness', 'linktrade-monitor' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $links ) ) : ?>
						<tr>
							<td colspan="5" class="linktrade-empty"><?php esc_html_e( 'No link exchange partners found.', 'linktrade-monitor' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $links as $link ) : ?>
							<?php
							// A side that was never read or is not on record cannot be rated.
							$fairness_rated = in_array( $link->status, array( 'online', 'warning', 'offline' ), true )
								&& ! empty( $link->backlink_url )
								&& in_array( $link->backlink_status, array( 'online', 'warning', 'offline' ), true );

							$fairness_class = 'fair';
							if ( ! $fairness_rated ) {
								$fairness_class = 'unrated';
							} elseif ( $link->fairness_score < 50 ) {
								$fairness_class = 'unfair';
							} elseif ( $link->fairness_score < 100 ) {
								$fairness_class = 'warning';
							}
							// DR comparison (per link).
							$partner_dr = (int) $link->domain_rating;
							$my_dr      = isset( $link->my_domain_rating ) ? (int) $link->my_domain_rating : 0;
							$dr_diff    = $partner_dr - $my_dr;
							?>
							<tr data-id="<?php echo esc_attr( $link->id ); ?>" class="fairness-row fairness-<?php echo esc_attr( $fairness_class ); ?>">
								<td>
									<strong><?php echo esc_html( $link->partner_name ); ?></strong>
								</td>
								<td>
									<?php echo wp_kses_post( $this->render_status_badge( $link ) ); ?>
									<br><small><?php echo esc_html( $this->truncate_url( $link->partner_url ) ); ?></small>
								</td>
								<td>
									<?php if ( $link->backlink_url ) : ?>
										<?php echo wp_kses_post( $this->render_backlink_status_badge( $link ) ); ?>
										<br><small><?php echo esc_html( $this->truncate_url( $link->backlink_url ) ); ?></small>
									<?php else : ?>
										<span class="status unchecked"><?php esc_html_e( 'Not set', 'linktrade-monitor' ); ?></span>
									<?php endif; ?>
								</td>
								<td class="dr-comparison">
									<?php if ( $partner_dr > 0 && $my_dr > 0 ) : ?>
										<span class="dr-badge"><?php echo esc_html( $partner_dr ); ?></span>
										<span class="dr-vs">:</span>
										<span class="dr-badge dr-mine"><?php echo esc_html( $my_dr ); ?></span>
										<?php if ( $dr_diff > 0 ) : ?>
											<span class="dr-diff dr-positive">+<?php echo esc_html( $dr_diff ); ?></span>
										<?php elseif ( $dr_diff < 0 ) : ?>
											<span class="dr-diff dr-negative"><?php echo esc_html( $dr_diff ); ?></span>
										<?php else : ?>
											<span class="dr-diff dr-neutral">=</span>
										<?php endif; ?>
									<?php elseif ( $partner_dr > 0 ) : ?>
										<span class="dr-badge"><?php echo esc_html( $partner_dr ); ?></span>
										<span class="dr-vs">:</span>
										<span class="dr-badge dr-mine">?</span>
									<?php elseif ( $my_dr > 0 ) : ?>
										<span class="dr-badge">?</span>
										<span class="dr-vs">:</span>
										<span class="dr-badge dr-mine"><?php echo esc_html( $my_dr ); ?></span>
									<?php else : ?>
										-
									<?php endif; ?>
								</td>
								<td>
									<?php if ( $fairness_rated ) : ?>
									<div class="fairness-score fairness-<?php echo esc_attr( $fairness_class ); ?>">
										<span class="score-value"><?php echo esc_html( $link->fairness_score ); ?>%</span>
										<div class="score-bar">
											<div class="score-fill" style="width: <?php echo esc_attr( $link->fairness_score ); ?>%"></div>
										</div>
									</div>
									<?php else : ?>
										<span class="status unchecked"><?php esc_html_e( 'Not rated yet', 'linktrade-monitor' ); ?></span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
			</div>
		</div>
			<?php
		}

		/**
		 * Render add tab
		 */
		private function render_add_tab() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Prefills a form from a link inside this admin page, nothing is saved.
			$prefill = array(
				'partner_name'    => isset( $_GET['lt_name'] ) ? sanitize_text_field( wp_unslash( $_GET['lt_name'] ) ) : '',
				'backlink_url'    => isset( $_GET['lt_page'] ) ? esc_url_raw( wp_unslash( $_GET['lt_page'] ) ) : '',
				'backlink_target' => isset( $_GET['lt_target'] ) ? esc_url_raw( wp_unslash( $_GET['lt_target'] ) ) : '',
			);
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			?>
		<div class="linktrade-card lt-compact-form">
			<h3><?php esc_html_e( 'Add New Link', 'linktrade-monitor' ); ?></h3>
			<form id="linktrade-add-form" class="linktrade-form">

				<!-- Row 1: Partner Info (3 columns) -->
				<div class="lt-form-grid lt-grid-3">
					<div class="form-row">
						<label for="partner_name"><?php esc_html_e( 'Partner Name', 'linktrade-monitor' ); ?> *</label>
						<input type="text" id="partner_name" name="partner_name" required value="<?php echo esc_attr( $prefill['partner_name'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. Partner Name', 'linktrade-monitor' ); ?>">
					</div>
					<div class="form-row">
						<label for="partner_contact"><?php esc_html_e( 'Contact (Email)', 'linktrade-monitor' ); ?></label>
						<input type="email" id="partner_contact" name="partner_contact" placeholder="partner@example.com">
					</div>
					<div class="form-row">
						<label for="category"><?php esc_html_e( 'Category', 'linktrade-monitor' ); ?> *</label>
						<select id="category" name="category" required>
							<option value="exchange"><?php esc_html_e( 'Link Exchange', 'linktrade-monitor' ); ?></option>
							<option value="paid"><?php esc_html_e( 'Paid Link', 'linktrade-monitor' ); ?></option>
							<option value="free"><?php esc_html_e( 'Free', 'linktrade-monitor' ); ?></option>
						</select>
					</div>
				</div>

				<!-- Row 2: Timing (2 columns) -->
				<div class="lt-form-grid lt-grid-2">
					<div class="form-row">
						<label for="start_date"><?php esc_html_e( 'Start Date', 'linktrade-monitor' ); ?></label>
						<input type="date" id="start_date" name="start_date" value="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>">
					</div>
					<div class="form-row">
						<label for="end_date"><?php esc_html_e( 'Expiration Date', 'linktrade-monitor' ); ?></label>
						<input type="date" id="end_date" name="end_date">
						<span class="lt-field-hint"><?php esc_html_e( 'Only for time-limited agreements', 'linktrade-monitor' ); ?></span>
					</div>
				</div>

				<!-- Row 3: Link Columns (2 side-by-side) -->
				<div class="lt-link-columns">
					<!-- LEFT: Incoming Link (what you GET) -->
					<div class="lt-link-column lt-incoming">
						<div class="lt-column-header">
							<span class="lt-column-icon dashicons dashicons-arrow-left-alt"></span>
							<div class="lt-column-title">
								<strong><?php esc_html_e( 'Incoming Link', 'linktrade-monitor' ); ?></strong>
								<small><?php esc_html_e( 'Backlink you receive', 'linktrade-monitor' ); ?></small>
							</div>
						</div>
						<div class="form-row">
							<label for="partner_url"><?php esc_html_e( 'Partner Page URL', 'linktrade-monitor' ); ?> *</label>
							<input type="url" id="partner_url" name="partner_url" required placeholder="https://partner-site.com/page">
						</div>
						<div class="form-row">
							<label for="target_url"><?php esc_html_e( 'Your Linked URL', 'linktrade-monitor' ); ?> *</label>
							<input type="url" id="target_url" name="target_url" required placeholder="https://your-site.com/page">
						</div>
						<div class="form-row">
							<label for="anchor_text"><?php esc_html_e( 'Agreed anchor text', 'linktrade-monitor' ); ?></label>
							<input type="text" id="anchor_text" name="anchor_text" placeholder="Your Brand">
							<span class="lt-field-hint"><?php esc_html_e( 'Leave empty to take the text found at the first check. You are told when it changes.', 'linktrade-monitor' ); ?></span>
						</div>
						<div class="form-row checkbox-row">
							<label>
								<input type="checkbox" id="follow_agreed" name="follow_agreed" value="1" checked>
								<?php esc_html_e( 'A normal, followed link was agreed', 'linktrade-monitor' ); ?>
							</label>
							<span class="lt-field-hint"><?php esc_html_e( 'Untick for links where nofollow or sponsored is fine, for example directory entries.', 'linktrade-monitor' ); ?></span>
						</div>
						<div class="form-row lt-dr-field">
							<label for="domain_rating"><?php esc_html_e( 'Partner DR', 'linktrade-monitor' ); ?></label>
							<input type="number" id="domain_rating" name="domain_rating" min="0" max="100" placeholder="0-100">
						</div>
					</div>

					<!-- RIGHT: Outgoing Link (what you GIVE) - only for exchanges -->
					<div class="lt-link-column lt-outgoing exchange-fields">
						<div class="lt-column-header">
							<span class="lt-column-icon dashicons dashicons-arrow-right-alt"></span>
							<div class="lt-column-title">
								<strong><?php esc_html_e( 'Outgoing Link', 'linktrade-monitor' ); ?></strong>
								<small><?php esc_html_e( 'Link you give back', 'linktrade-monitor' ); ?></small>
							</div>
						</div>
						<div class="form-row lt-find-row">
							<button type="button" class="button linktrade-find-backlink"><?php esc_html_e( 'Find my link to this partner', 'linktrade-monitor' ); ?></button>
							<span class="lt-field-hint"><?php esc_html_e( 'Searches your published posts, pages, menus and widgets for links to the partner\'s domain.', 'linktrade-monitor' ); ?></span>
							<div class="linktrade-find-result"></div>
						</div>
						<div class="form-row">
							<label for="backlink_url"><?php esc_html_e( 'Your Page URL', 'linktrade-monitor' ); ?></label>
							<input type="url" id="backlink_url" name="backlink_url" value="<?php echo esc_attr( $prefill['backlink_url'] ); ?>" placeholder="https://your-site.com/partners">
						</div>
						<div class="form-row">
							<label for="backlink_target"><?php esc_html_e( 'Partner Target URL', 'linktrade-monitor' ); ?></label>
							<input type="url" id="backlink_target" name="backlink_target" value="<?php echo esc_attr( $prefill['backlink_target'] ); ?>" placeholder="https://partner-site.com/">
						</div>
						<div class="form-row">
							<label for="backlink_anchor"><?php esc_html_e( 'Anchor Text', 'linktrade-monitor' ); ?></label>
							<input type="text" id="backlink_anchor" name="backlink_anchor" placeholder="Partner Name">
						</div>
						<div class="form-row lt-dr-field">
							<label for="my_domain_rating"><?php esc_html_e( 'My DR', 'linktrade-monitor' ); ?></label>
							<input type="number" id="my_domain_rating" name="my_domain_rating" min="0" max="100" placeholder="0-100">
						</div>
					</div>
				</div>

				<!-- Row 4: Notes (full width) -->
				<div class="form-row lt-notes-full">
					<label for="notes"><?php esc_html_e( 'Notes', 'linktrade-monitor' ); ?></label>
					<textarea id="notes" name="notes" rows="3" placeholder="<?php esc_attr_e( 'Additional notes about this link partnership...', 'linktrade-monitor' ); ?>"></textarea>
				</div>

				<!-- Submit Button -->
				<div class="form-actions">
					<button type="submit" class="button button-primary button-large">
						<?php esc_html_e( 'Save Link', 'linktrade-monitor' ); ?>
					</button>
				</div>
			</form>
		</div>

		<div class="linktrade-card lt-outgoing-scan">
			<h3><?php esc_html_e( 'Who do you already link to?', 'linktrade-monitor' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Searches all published posts, pages, menus and widgets for links to other websites and lists the domains. Pick a partner and the form above is filled in with your page and their address.', 'linktrade-monitor' ); ?></p>
			<p><button type="button" class="button" id="linktrade-scan-outgoing"><?php esc_html_e( 'Search my site for outgoing links', 'linktrade-monitor' ); ?></button> <span id="linktrade-scan-progress" role="status"></span></p>
			<div id="linktrade-scan-result"></div>
		</div>
			<?php
		}


		/**
		 * Render settings tab
		 */
		private function render_settings_tab() {
			// Handle form submission with nonce verification.
			if ( isset( $_POST['linktrade_save_settings'] ) ) {
				if ( ! isset( $_POST['linktrade_settings_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['linktrade_settings_nonce'] ), 'linktrade_settings' ) ) {
					wp_die( esc_html__( 'Security check failed.', 'linktrade-monitor' ) );
				}

				$notification_email  = isset( $_POST['notification_email'] ) ? sanitize_email( wp_unslash( $_POST['notification_email'] ) ) : '';
				$email_notifications = isset( $_POST['email_notifications'] ) ? 1 : 0;
				$weekly_summary      = isset( $_POST['weekly_summary'] ) ? 1 : 0;
				$reminder_days       = isset( $_POST['reminder_days'] ) ? max( 1, min( 90, absint( $_POST['reminder_days'] ) ) ) : 14;

				update_option( 'linktrade_email_notifications', $email_notifications );
				update_option( 'linktrade_weekly_summary', $weekly_summary );
				update_option( 'linktrade_reminder_days', $reminder_days );

				if ( is_email( $notification_email ) ) {
					update_option( 'linktrade_notification_email', $notification_email );
					echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'linktrade-monitor' ) . '</p></div>';
				} else {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'The notification address is not a valid email address and was not saved. The other settings were saved.', 'linktrade-monitor' ) . '</p></div>';
				}
			}

			$notification_email  = get_option( 'linktrade_notification_email', get_option( 'admin_email' ) );
			$email_notifications = get_option( 'linktrade_email_notifications', true );
			$reminder_days       = get_option( 'linktrade_reminder_days', 14 );
			$weekly_summary      = get_option( 'linktrade_weekly_summary', 0 );
			?>
		<div class="linktrade-card">
			<h3><?php esc_html_e( 'Settings', 'linktrade-monitor' ); ?></h3>

			<form method="post">
				<?php wp_nonce_field( 'linktrade_settings', 'linktrade_settings_nonce' ); ?>

				<div class="form-section">
					<h4><?php esc_html_e( 'Notifications', 'linktrade-monitor' ); ?></h4>

					<div class="form-row">
						<label for="notification_email"><?php esc_html_e( 'Notification Email', 'linktrade-monitor' ); ?></label>
						<input type="email" id="notification_email" name="notification_email" value="<?php echo esc_attr( $notification_email ); ?>">
					</div>

					<div class="form-row checkbox-row">
						<label>
							<input type="checkbox" name="email_notifications" value="1" <?php checked( $email_notifications ); ?>>
							<?php esc_html_e( 'Email me when a link disappears, turns nofollow or an agreement is about to end', 'linktrade-monitor' ); ?>
						</label>
					</div>

					<div class="form-row checkbox-row">
						<label>
							<input type="checkbox" name="weekly_summary" value="1" <?php checked( $weekly_summary ); ?>>
							<?php esc_html_e( 'Also send the weekly summary when nothing has changed', 'linktrade-monitor' ); ?>
						</label>
					</div>

					<div class="form-row">
						<label for="reminder_days"><?php esc_html_e( 'Remind me X days before expiration', 'linktrade-monitor' ); ?></label>
						<input type="number" id="reminder_days" name="reminder_days" value="<?php echo esc_attr( $reminder_days ); ?>" min="1" max="90">
					</div>
				</div>

				<div class="form-actions">
					<button type="submit" name="linktrade_save_settings" class="button button-primary">
						<?php esc_html_e( 'Save Settings', 'linktrade-monitor' ); ?>
					</button>
					<button type="button" class="button" id="linktrade-test-mail"><?php esc_html_e( 'Send a test email', 'linktrade-monitor' ); ?></button>
				</div>
			</form>

			<p class="description"><?php esc_html_e( 'The plugin uses the language of your WordPress site.', 'linktrade-monitor' ); ?></p>
		</div>
			<?php
		}

		/**
		 * Render import/export tab
		 */
		private function render_import_export_tab() {
			?>
		<div class="linktrade-import-export">
			<!-- Where the data is stored: information only -->
			<div class="linktrade-card lt-data-home">
				<h3><span class="dashicons dashicons-database" aria-hidden="true"></span> <?php esc_html_e( 'Where your data is stored', 'linktrade-monitor' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Everything you enter is stored in the database of this site: links, partners, notes and the history of every check. A link check requests only the pages you entered. Apart from the notification emails to your own address, nothing is sent to 3task or anyone else.', 'linktrade-monitor' ); ?></p>
			</div>

			<!-- Export Section -->
			<div class="linktrade-card">
				<h3><?php esc_html_e( 'Export Links', 'linktrade-monitor' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Download all your links as a CSV file. You can use this for backup or to import into other tools.', 'linktrade-monitor' ); ?></p>
				<div class="form-actions" style="margin-top: 20px;">
					<button type="button" id="linktrade-export-csv" class="button button-primary">
						<span class="dashicons dashicons-download" style="margin-top: 4px;"></span>
						<?php esc_html_e( 'Export to CSV', 'linktrade-monitor' ); ?>
					</button>
				</div>
			</div>

			<!-- Import Section -->
			<div class="linktrade-card">
				<h3><?php esc_html_e( 'Import Links', 'linktrade-monitor' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Import links from a CSV file. The file must use the exact column names shown below.', 'linktrade-monitor' ); ?></p>

				<form id="linktrade-import-form" enctype="multipart/form-data" style="margin-top: 20px;">
					<?php wp_nonce_field( 'linktrade_import', 'linktrade_import_nonce' ); ?>
					<div class="form-row">
						<label for="import_file"><?php esc_html_e( 'CSV File', 'linktrade-monitor' ); ?></label>
						<input type="file" id="import_file" name="import_file" accept=".csv" required>
					</div>
					<div class="form-row checkbox-row">
						<label>
							<input type="checkbox" name="skip_duplicates" value="1" checked>
							<?php esc_html_e( 'Skip duplicate entries (based on partner_url)', 'linktrade-monitor' ); ?>
						</label>
					</div>
					<div class="form-actions">
						<button type="submit" class="button button-primary">
							<span class="dashicons dashicons-upload" style="margin-top: 4px;"></span>
							<?php esc_html_e( 'Check file', 'linktrade-monitor' ); ?>
						</button>
					</div>
				</form>
				<div id="linktrade-import-result" style="margin-top: 15px;"></div>
			</div>

			<!-- Field Documentation -->
			<div class="linktrade-card">
				<h3><?php esc_html_e( 'CSV Field Reference', 'linktrade-monitor' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Your CSV file must include a header row with these exact column names. Required fields are marked with *.', 'linktrade-monitor' ); ?></p>

				<div class="linktrade-table-scroll">
				<table class="linktrade-table field-reference" style="margin-top: 20px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Column Name', 'linktrade-monitor' ); ?></th>
							<th><?php esc_html_e( 'Required', 'linktrade-monitor' ); ?></th>
							<th><?php esc_html_e( 'Description', 'linktrade-monitor' ); ?></th>
							<th><?php esc_html_e( 'Example', 'linktrade-monitor' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>partner_name</code></td>
							<td><span class="required-badge">*</span></td>
							<td><?php esc_html_e( 'Name of the link partner or website', 'linktrade-monitor' ); ?></td>
							<td>Example Blog</td>
						</tr>
						<tr>
							<td><code>partner_url</code></td>
							<td><span class="required-badge">*</span></td>
							<td><?php esc_html_e( 'URL of the page containing the backlink to you', 'linktrade-monitor' ); ?></td>
							<td>https://example.com/links</td>
						</tr>
						<tr>
							<td><code>target_url</code></td>
							<td><span class="required-badge">*</span></td>
							<td><?php esc_html_e( 'Your URL that receives the backlink', 'linktrade-monitor' ); ?></td>
							<td>https://yoursite.com/page</td>
						</tr>
						<tr>
							<td><code>category</code></td>
							<td></td>
							<td><?php esc_html_e( 'Link type: exchange, paid, or free', 'linktrade-monitor' ); ?></td>
							<td>exchange</td>
						</tr>
						<tr>
							<td><code>partner_contact</code></td>
							<td></td>
							<td><?php esc_html_e( 'Contact email of the partner', 'linktrade-monitor' ); ?></td>
							<td>contact@example.com</td>
						</tr>
						<tr>
							<td><code>anchor_text</code></td>
							<td></td>
							<td><?php esc_html_e( 'The clickable text of the backlink', 'linktrade-monitor' ); ?></td>
							<td>Visit our site</td>
						</tr>
						<tr>
							<td><code>backlink_url</code></td>
							<td></td>
							<td><?php esc_html_e( 'Your page containing the reciprocal link (for exchanges)', 'linktrade-monitor' ); ?></td>
							<td>https://yoursite.com/partners</td>
						</tr>
						<tr>
							<td><code>backlink_target</code></td>
							<td></td>
							<td><?php esc_html_e( 'Partner URL you link to (for exchanges)', 'linktrade-monitor' ); ?></td>
							<td>https://example.com</td>
						</tr>
						<tr>
							<td><code>domain_rating</code></td>
							<td></td>
							<td><?php esc_html_e( 'Partner Domain Rating (0-100, from Ahrefs)', 'linktrade-monitor' ); ?></td>
							<td>45</td>
						</tr>
						<tr>
							<td><code>my_domain_rating</code></td>
							<td></td>
							<td><?php esc_html_e( 'Your Domain Rating (0-100)', 'linktrade-monitor' ); ?></td>
							<td>52</td>
						</tr>
						<tr>
							<td><code>start_date</code></td>
							<td></td>
							<td><?php esc_html_e( 'Date the link was placed (YYYY-MM-DD)', 'linktrade-monitor' ); ?></td>
							<td>2026-01-15</td>
						</tr>
						<tr>
							<td><code>end_date</code></td>
							<td></td>
							<td><?php esc_html_e( 'Expiration date for paid/timed links (YYYY-MM-DD)', 'linktrade-monitor' ); ?></td>
							<td>2027-01-15</td>
						</tr>
						<tr>
							<td><code>notes</code></td>
							<td></td>
							<td><?php esc_html_e( 'Additional notes about this link', 'linktrade-monitor' ); ?></td>
							<td>Guest post agreement</td>
						</tr>
					</tbody>
				</table>
				</div>

				<div class="csv-example" style="margin-top: 25px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
					<h4 style="margin-top: 0;"><?php esc_html_e( 'Example CSV', 'linktrade-monitor' ); ?></h4>
					<pre style="margin: 0; overflow-x: auto; font-size: 12px;">partner_name,partner_url,target_url,category,domain_rating,start_date
Example Blog,https://example.com/links,https://yoursite.com,exchange,45,2026-01-15
SEO Partner,https://seosite.com/resources,https://yoursite.com/tools,paid,62,2026-02-01</pre>
				</div>
			</div>
		</div>
			<?php
		}

		/**
		 * Render modals
		 */
		private function render_modals() {
			?>
		<!-- Edit Modal -->
		<div id="linktrade-edit-modal" class="linktrade-modal" style="display: none;" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Edit Link', 'linktrade-monitor' ); ?>">
			<div class="linktrade-modal-content">
				<div class="linktrade-modal-header">
					<h2><?php esc_html_e( 'Edit Link', 'linktrade-monitor' ); ?></h2>
					<button type="button" class="linktrade-modal-close" aria-label="<?php esc_attr_e( 'Close', 'linktrade-monitor' ); ?>">&times;</button>
				</div>
				<div class="linktrade-modal-body">
					<form id="linktrade-edit-form" class="linktrade-form">
						<input type="hidden" id="edit_id" name="id">
						<!-- Form fields will be populated via JS -->
					</form>
				</div>
			</div>
		</div>

		<!-- Info modal: history and message to partner -->
		<div id="linktrade-info-modal" class="linktrade-modal" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="linktrade-info-title">
			<div class="linktrade-modal-content">
				<div class="linktrade-modal-header">
					<h2 id="linktrade-info-title"></h2>
					<button type="button" class="linktrade-modal-close" aria-label="<?php esc_attr_e( 'Close', 'linktrade-monitor' ); ?>">&times;</button>
				</div>
				<div class="linktrade-modal-body" id="linktrade-info-body"></div>
			</div>
		</div>

			<?php
		}

		/**
		 * Helper: Truncate URL
		 *
		 * @param string $url    URL to truncate.
		 * @param int    $length Maximum length.
		 * @return string Truncated URL.
		 */
		private function truncate_url( $url, $length = 40 ) {
			$url = preg_replace( '#^https?://#', '', (string) $url );
			$url = rtrim( $url, '/' );
			if ( strlen( $url ) > $length ) {
				return substr( $url, 0, $length ) . '...';
			}
			return $url;
		}

		/**
		 * Helper: Get category label
		 *
		 * @param string $category Category key.
		 * @return string Category label.
		 */
		private function get_category_label( $category ) {
			$labels = array(
				'exchange' => __( 'Exchange', 'linktrade-monitor' ),
				'paid'     => __( 'Paid', 'linktrade-monitor' ),
				'free'     => __( 'Free', 'linktrade-monitor' ),
			);
			return isset( $labels[ $category ] ) ? $labels[ $category ] : $category;
		}

		/**
		 * Helper: Render status badge
		 *
		 * @param object $link Link object.
		 * @return string HTML status badge.
		 */
		private function render_status_badge( $link ) {
			$status_labels = array(
				'online'    => __( 'Online', 'linktrade-monitor' ),
				'warning'   => __( 'Warning', 'linktrade-monitor' ),
				'offline'   => __( 'Offline', 'linktrade-monitor' ),
				'unchecked' => __( 'Unchecked', 'linktrade-monitor' ),
			);

			$label = isset( $status_labels[ $link->status ] ) ? $status_labels[ $link->status ] : $link->status;

			if ( 'warning' === $link->status ) {
				$reason = $this->get_warning_reason( $link );
				if ( '' !== $reason ) {
					$label = $reason;
				}
			}

			return sprintf(
				'<span class="status %s"><span class="status-dot"></span>%s</span>',
				esc_attr( $link->status ),
				esc_html( $label )
			);
		}

		/**
		 * Helper: Render date info (start date + expiration)
		 *
		 * @param object $link Link object.
		 * @return string HTML date info.
		 */
		private function render_date_info( $link ) {
			$output = '';

			// Show start date.
			if ( ! empty( $link->start_date ) && '0000-00-00' !== $link->start_date ) {
				$start_formatted = wp_date( get_option( 'date_format' ), strtotime( $link->start_date ) );
				$output         .= '<span class="date-start">' . esc_html( $start_formatted ) . '</span>';
			} else {
				$output .= '<span class="date-start">-</span>';
			}

			// Show expiration status if end_date is set.
			if ( ! empty( $link->end_date ) && '0000-00-00' !== $link->end_date ) {
				$end_timestamp     = strtotime( $link->end_date );
				$now               = strtotime( current_time( 'mysql' ) );
				$days_until_expiry = (int) ceil( ( $end_timestamp - $now ) / DAY_IN_SECONDS );

				if ( $days_until_expiry < 0 ) {
					// Already expired.
					$output .= '<br><span class="expiry-badge expired">' . esc_html__( 'Expired', 'linktrade-monitor' ) . '</span>';
				} elseif ( $days_until_expiry <= 30 ) {
					// Expiring soon (within 30 days).
					$output .= '<br><span class="expiry-badge expiring">';
					/* translators: %d: number of days until expiration */
					$output .= sprintf( esc_html__( '%d days left', 'linktrade-monitor' ), $days_until_expiry );
					$output .= '</span>';
				} else {
					// Not expiring soon - show end date.
					$end_formatted = wp_date( get_option( 'date_format' ), $end_timestamp );
					$output       .= '<br><small>' . esc_html__( 'until', 'linktrade-monitor' ) . ' ' . esc_html( $end_formatted ) . '</small>';
				}
			}

			return $output;
		}

		/**
		 * Helper: Render backlink status badge
		 *
		 * @param object $link Link object.
		 * @return string HTML status badge.
		 */
		private function render_backlink_status_badge( $link ) {
			$status_labels = array(
				'online'         => __( 'Online', 'linktrade-monitor' ),
				'warning'        => __( 'Warning', 'linktrade-monitor' ),
				'offline'        => __( 'Offline', 'linktrade-monitor' ),
				'unchecked'      => __( 'Unchecked', 'linktrade-monitor' ),
				'not_applicable' => __( 'N/A', 'linktrade-monitor' ),
			);

			$status = $link->backlink_status;
			$label  = isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : $status;

			if ( 'warning' === $status && $link->backlink_is_nofollow ) {
				$label = 'nofollow';
			}

			return sprintf(
				'<span class="status %s"><span class="status-dot"></span>%s</span>',
				esc_attr( $status ),
				esc_html( $label )
			);
		}

		/**
		 * AJAX: Save link
		 */
		public function ajax_save_link() {
			check_ajax_referer( 'linktrade_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'linktrade-monitor' ) ) );
			}

			$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

			global $wpdb;
			$table_name = $wpdb->prefix . 'linktrade_links';

			// Sanitize all POST data with wp_unslash.
			$data = array(
				'partner_name'     => isset( $_POST['partner_name'] ) ? sanitize_text_field( wp_unslash( $_POST['partner_name'] ) ) : '',
				'partner_contact'  => isset( $_POST['partner_contact'] ) ? sanitize_email( wp_unslash( $_POST['partner_contact'] ) ) : '',
				'category'         => isset( $_POST['category'] ) ? sanitize_key( wp_unslash( $_POST['category'] ) ) : 'exchange',
				'partner_url'      => isset( $_POST['partner_url'] ) ? esc_url_raw( wp_unslash( $_POST['partner_url'] ) ) : '',
				'target_url'       => isset( $_POST['target_url'] ) ? esc_url_raw( wp_unslash( $_POST['target_url'] ) ) : '',
				'anchor_text'      => isset( $_POST['anchor_text'] ) ? sanitize_text_field( wp_unslash( $_POST['anchor_text'] ) ) : '',
				'backlink_url'     => isset( $_POST['backlink_url'] ) ? esc_url_raw( wp_unslash( $_POST['backlink_url'] ) ) : '',
				'backlink_target'  => isset( $_POST['backlink_target'] ) ? esc_url_raw( wp_unslash( $_POST['backlink_target'] ) ) : '',
				'backlink_anchor'  => isset( $_POST['backlink_anchor'] ) ? sanitize_text_field( wp_unslash( $_POST['backlink_anchor'] ) ) : '',
				'domain_rating'    => isset( $_POST['domain_rating'] ) ? absint( $_POST['domain_rating'] ) : 0,
				'my_domain_rating' => isset( $_POST['my_domain_rating'] ) ? absint( $_POST['my_domain_rating'] ) : 0,
				'notes'            => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
				'start_date'       => isset( $_POST['start_date'] ) && ! empty( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : null,
				'end_date'         => isset( $_POST['end_date'] ) && ! empty( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : null,
			);

			if ( $this->has_agreement_columns() ) {
				$data['follow_agreed'] = isset( $_POST['follow_agreed'] ) ? 1 : 0;
			}

			// Validate on the server: the browser check alone can be bypassed.
			if ( '' === $data['partner_name'] ) {
				wp_send_json_error( array( 'message' => __( 'Please enter a partner name.', 'linktrade-monitor' ) ) );
			}
			if ( ! $this->is_web_url( $data['partner_url'] ) || ! $this->is_web_url( $data['target_url'] ) ) {
				wp_send_json_error( array( 'message' => __( 'Please enter the partner page and your target page as full web addresses (http or https).', 'linktrade-monitor' ) ) );
			}
			foreach ( array( 'backlink_url', 'backlink_target' ) as $optional_url ) {
				if ( '' !== $data[ $optional_url ] && ! $this->is_web_url( $data[ $optional_url ] ) ) {
					wp_send_json_error( array( 'message' => __( 'The reciprocal link fields need full web addresses (http or https).', 'linktrade-monitor' ) ) );
				}
			}
			if ( ! in_array( $data['category'], array( 'exchange', 'paid', 'free' ), true ) ) {
				$data['category'] = 'exchange';
			}
			foreach ( array( 'start_date', 'end_date' ) as $date_field ) {
				if ( null !== $data[ $date_field ] && ! $this->is_valid_date( $data[ $date_field ] ) ) {
					wp_send_json_error( array( 'message' => __( 'Please enter dates as YYYY-MM-DD.', 'linktrade-monitor' ) ) );
				}
			}
			$data['domain_rating']    = min( 100, $data['domain_rating'] );
			$data['my_domain_rating'] = min( 100, $data['my_domain_rating'] );

			// Clear cache.
			wp_cache_delete( 'linktrade_link_count' );
			wp_cache_delete( 'linktrade_quick_stats' );
			wp_cache_delete( 'linktrade_full_stats' );

			$is_new_link = ( 0 === $id );
			$recheck     = $is_new_link;

			if ( $id > 0 ) {
				$before = $this->get_link_row( $id );
				if ( ! $before ) {
					wp_send_json_error( array( 'message' => __( 'Link not found.', 'linktrade-monitor' ) ) );
				}

				// A changed address makes the stored result worthless: check again.
				foreach ( array( 'partner_url', 'target_url', 'backlink_url', 'backlink_target' ) as $url_field ) {
					if ( (string) $before->$url_field !== (string) $data[ $url_field ] ) {
						$recheck = true;
					}
				}
				if ( $before->category !== $data['category'] ) {
					$recheck = true;
				}

				// A reciprocal link that is no longer on record is not "offline".
				if ( 'exchange' !== $data['category'] || '' === $data['backlink_url'] ) {
					$data['backlink_status'] = 'not_applicable';
				} elseif ( (string) $before->backlink_url !== (string) $data['backlink_url'] || (string) $before->backlink_target !== (string) $data['backlink_target'] ) {
					$data['backlink_status'] = 'unchecked';
				}

				// The stored findings belong to the old page.
				if ( (string) $before->partner_url !== (string) $data['partner_url'] || (string) $before->target_url !== (string) $data['target_url'] ) {
					$data['status']       = 'unchecked';
					$data['is_nofollow']  = 0;
					$data['is_noindex']   = 0;
					$data['is_sponsored'] = 0;
					$data['redirect_url'] = null;
					if ( $this->has_agreement_columns() ) {
						$data['found_anchor'] = null;
					}
				}

				// A new end date deserves a new reminder.
				if ( (string) $before->end_date !== (string) $data['end_date'] ) {
					$data['reminder_sent'] = 0;
				}

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Update operation on custom table.
				$saved   = $wpdb->update( $table_name, $data, array( 'id' => $id ) );
				$message = __( 'Link updated successfully.', 'linktrade-monitor' );
			} else {
				if ( 'exchange' === $data['category'] && '' !== $data['backlink_url'] ) {
					$data['backlink_status'] = 'unchecked';
				}

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Insert operation on custom table.
				$saved   = $wpdb->insert( $table_name, $data );
				$id      = $wpdb->insert_id;
				$message = __( 'Link saved successfully.', 'linktrade-monitor' );
			}

			// Never report success for something the database refused.
			if ( false === $saved || ! $id ) {
				wp_send_json_error( array( 'message' => __( 'The link could not be saved. Please deactivate and reactivate the plugin once, then try again.', 'linktrade-monitor' ) ) );
			}

			$check_result = null;
			delete_transient( 'linktrade_attention_count' );

			if ( $recheck ) {
				Linktrade_Runner::check_link( $this->get_link_row( $id ) );
				$message .= ' ' . $this->describe_result( $this->get_link_row( $id ) );
			} elseif ( 'exchange' === $data['category'] ) {
				// Only the ratings may have changed.
				$this->recalculate_fairness( $id, $data['domain_rating'], $data['my_domain_rating'] );
			}

			wp_send_json_success(
				array(
					'message'      => $message,
					'id'           => $id,
					'check_result' => $check_result,
				)
			);
		}

		/**
		 * Is this a full http or https address?
		 *
		 * @param string $url Address to test.
		 * @return bool
		 */
		private function is_web_url( $url ) {
			if ( ! is_string( $url ) || '' === $url ) {
				return false;
			}
			$scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );
			$host   = (string) wp_parse_url( $url, PHP_URL_HOST );

			return ( 'http' === $scheme || 'https' === $scheme ) && '' !== $host;
		}

		/**
		 * Is this a real calendar date in the form YYYY-MM-DD?
		 *
		 * @param string $date Date to test.
		 * @return bool
		 */
		private function is_valid_date( $date ) {
			if ( ! is_string( $date ) || ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts ) ) {
				return false;
			}

			return checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] );
		}

		/**
		 * Make a value safe for a CSV cell. Spreadsheet programs run a cell that
		 * starts with = + - or @ as a formula, and the anchor text comes from the
		 * partner's page, so such cells get a leading apostrophe.
		 *
		 * @param mixed $value Cell value.
		 * @return string
		 */
		private function csv_cell( $value ) {
			$value = (string) $value;

			if ( '' !== $value && false !== strpos( "=+-@\t\r", $value[0] ) && ! is_numeric( $value ) ) {
				$value = "'" . $value;
			}

			$value = str_replace( '"', '""', $value );
			if ( false !== strpos( $value, ',' ) || false !== strpos( $value, '"' ) || false !== strpos( $value, "\n" ) ) {
				$value = '"' . $value . '"';
			}

			return $value;
		}

		/**
		 * Recalculate fairness score for a link based on current status and DR values.
		 *
		 * @param int $link_id    The link ID.
		 * @param int $partner_dr Partner's Domain Rating.
		 * @param int $my_dr      My Domain Rating.
		 */
		private function recalculate_fairness( $link_id, $partner_dr, $my_dr ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'linktrade_links';

			// Get current link status.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Query on custom table.
			$link = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT status, is_nofollow, backlink_status, backlink_url, backlink_is_nofollow FROM `' . esc_sql( $table_name ) . '` WHERE id = %d',
					$link_id
				)
			);

			if ( ! $link ) {
				return;
			}

			// Calculate fairness with status and DR values.
			$fairness = Linktrade::fairness(
				$link->status,
				empty( $link->backlink_url ) ? 'not_applicable' : $link->backlink_status,
				(bool) $link->is_nofollow,
				(bool) $link->backlink_is_nofollow,
				(int) $partner_dr,
				(int) $my_dr
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Update operation on custom table.
			$wpdb->update(
				$table_name,
				array( 'fairness_score' => $fairness ),
				array( 'id' => $link_id ),
				array( '%d' ),
				array( '%d' )
			);
		}

		/**
		 * AJAX: Delete link
		 */
		public function ajax_delete_link() {
			check_ajax_referer( 'linktrade_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'linktrade-monitor' ) ) );
			}

			$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
			if ( ! $id ) {
				wp_send_json_error( array( 'message' => __( 'Invalid link ID.', 'linktrade-monitor' ) ) );
			}

			global $wpdb;
			$table_name = $wpdb->prefix . 'linktrade_links';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Delete operation on custom table.
			$deleted = $wpdb->delete( $table_name, array( 'id' => $id ), array( '%d' ) );

			if ( false === $deleted ) {
				wp_send_json_error( array( 'message' => __( 'The link could not be deleted.', 'linktrade-monitor' ) ) );
			}

			// History, change log and "not verifiable" marker go with the link.
			Linktrade_Runner::forget_link( $id );

			// Clear cache.
			wp_cache_delete( 'linktrade_link_count' );
			wp_cache_delete( 'linktrade_quick_stats' );
			wp_cache_delete( 'linktrade_full_stats' );

			wp_send_json_success( array( 'message' => __( 'Link deleted.', 'linktrade-monitor' ) ) );
		}

		/**
		 * AJAX: Get single link
		 */
		public function ajax_get_link() {
			check_ajax_referer( 'linktrade_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'linktrade-monitor' ) ) );
			}

			$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
			if ( ! $id ) {
				wp_send_json_error( array( 'message' => __( 'Invalid link ID.', 'linktrade-monitor' ) ) );
			}

			global $wpdb;
			$table_name = $wpdb->prefix . 'linktrade_links';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- AJAX fetch single item.
			$link = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM `' . esc_sql( $table_name ) . '` WHERE id = %d', $id ) );

			if ( ! $link ) {
				wp_send_json_error( array( 'message' => __( 'Link not found.', 'linktrade-monitor' ) ) );
			}

			wp_send_json_success( array( 'link' => $link ) );
		}

		/**
		 * Calculate Link Health Score (0-100)
		 *
		 * Factors:
		 * - HTTP Status (40%): online = 40, warning = 20, offline = 0
		 * - Attributes (20%): no nofollow/noindex = 20, nofollow only = 10, noindex = 0
		 * - DR (20%): Based on manually entered Domain Rating (0-100 scaled to 0-20)
		 * - Link Age (10%): Older links = more valuable, max at 365 days
		 * - Fairness (10%): For exchanges only, otherwise full points
		 *
		 * @param object $link Link object from database.
		 * @return int Health score 0-100.
		 */
		public function calculate_link_health_score( $link ) {
			// A link that is gone has no health, however old or strong it was.
			if ( 'offline' === $link->status ) {
				return 0;
			}

			$score = 0;

			// Status (40%)
			if ( 'online' === $link->status ) {
				$score += 40;
			} elseif ( 'warning' === $link->status ) {
				$score += 20;
			}
			// offline/unchecked = 0

			// Attributes (20%)
			$is_nofollow = ! empty( $link->is_nofollow ) || ! empty( $link->is_sponsored );
			$is_noindex  = isset( $link->is_noindex ) ? (bool) $link->is_noindex : false;

			if ( ! $is_nofollow && ! $is_noindex ) {
				$score += 20;
			} elseif ( ! $is_noindex ) {
				$score += 10;
			}
			// noindex = 0

			// DR (20%) - based on manually entered value
			$dr = isset( $link->domain_rating ) ? (int) $link->domain_rating : 0;
			if ( $dr > 0 ) {
				$score += min( 20, (int) ( $dr / 5 ) ); // DR 100 = 20 points
			} else {
				// No DR entered - give average points to not penalize
				$score += 10;
			}

			// Link Age (10%) - older = better, max at 365 days
			if ( ! empty( $link->start_date ) && '0000-00-00' !== $link->start_date ) {
				$days   = ( time() - strtotime( $link->start_date ) ) / DAY_IN_SECONDS;
				$days   = max( 0, $days );
				$score += min( 10, (int) ( $days / 36.5 ) ); // 365 days = 10 points
			} else {
				// No start date - give average points
				$score += 5;
			}

			// Fairness (10%) - only for exchange links
			if ( 'exchange' === $link->category ) {
				$fairness = isset( $link->fairness_score ) ? (int) $link->fairness_score : 100;
				$score   += (int) ( $fairness / 10 ); // 100% fairness = 10 points
			} else {
				// Non-exchange links get full points
				$score += 10;
			}

			return min( 100, max( 0, $score ) );
		}

		/**
		 * Get health score class for styling
		 *
		 * @param int $score Health score 0-100.
		 * @return string CSS class name.
		 */
		private function get_health_score_class( $score ) {
			if ( $score >= 80 ) {
				return 'health-excellent';
			} elseif ( $score >= 60 ) {
				return 'health-good';
			} elseif ( $score >= 40 ) {
				return 'health-fair';
			} else {
				return 'health-poor';
			}
		}

		/**
		 * AJAX: Export links to CSV
		 */
		public function ajax_export_csv() {
			check_ajax_referer( 'linktrade_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'linktrade-monitor' ) ) );
			}

			global $wpdb;
			$table_name = $wpdb->prefix . 'linktrade_links';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Export requires fresh data.
			$links = $wpdb->get_results( 'SELECT * FROM `' . esc_sql( $table_name ) . '` ORDER BY created_at DESC', ARRAY_A );

			if ( empty( $links ) ) {
				wp_send_json_error( array( 'message' => __( 'No links to export.', 'linktrade-monitor' ) ) );
			}

			// Define columns for export (exclude internal fields).
			$columns = array(
				'partner_name',
				'partner_url',
				'target_url',
				'category',
				'partner_contact',
				'anchor_text',
				'backlink_url',
				'backlink_target',
				'backlink_anchor',
				'domain_rating',
				'my_domain_rating',
				'start_date',
				'end_date',
				'notes',
				'follow_agreed',
				'status',
				'http_code',
				'is_nofollow',
				'is_noindex',
				'fairness_score',
				'last_check',
			);

			// Build CSV content.
			$csv_lines   = array();
			$csv_lines[] = implode( ',', $columns );

			foreach ( $links as $link ) {
				$row = array();
				foreach ( $columns as $col ) {
					$row[] = $this->csv_cell( isset( $link[ $col ] ) ? $link[ $col ] : '' );
				}
				$csv_lines[] = implode( ',', $row );
			}

			$csv_content = implode( "\n", $csv_lines );

			wp_send_json_success(
				array(
					'csv'      => $csv_content,
					'filename' => 'linktrade-export-' . gmdate( 'Y-m-d' ) . '.csv',
					'count'    => count( $links ),
				)
			);
		}

		/**
		 * Does the links table have the columns added in 1.4.0?
		 *
		 * @return bool
		 */
		private function has_agreement_columns() {
			static $has = null;

			if ( null === $has ) {
				global $wpdb;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema check on the plugin's own table.
				$columns = (array) $wpdb->get_col( 'SHOW COLUMNS FROM `' . esc_sql( $wpdb->prefix . 'linktrade_links' ) . '`', 0 );
				$has     = in_array( 'follow_agreed', $columns, true ) && in_array( 'found_anchor', $columns, true );

				if ( ! $has ) {
					require_once LINKTRADE_PLUGIN_DIR . 'includes/class-activator.php';
					Linktrade_Activator::ensure_columns();
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema check on the plugin's own table.
					$columns = (array) $wpdb->get_col( 'SHOW COLUMNS FROM `' . esc_sql( $wpdb->prefix . 'linktrade_links' ) . '`', 0 );
					$has     = in_array( 'follow_agreed', $columns, true ) && in_array( 'found_anchor', $columns, true );
				}
			}

			return $has;
		}

		/**
		 * AJAX: delete several links at once.
		 */
		public function ajax_bulk_delete() {
			check_ajax_referer( 'linktrade_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'linktrade-monitor' ) ) );
			}

			$ids = isset( $_POST['ids'] ) && is_array( $_POST['ids'] ) ? array_filter( array_map( 'absint', wp_unslash( $_POST['ids'] ) ) ) : array();
			if ( empty( $ids ) ) {
				wp_send_json_error( array( 'message' => __( 'No links selected.', 'linktrade-monitor' ) ) );
			}

			global $wpdb;
			$table_name = $wpdb->prefix . 'linktrade_links';
			$deleted    = 0;

			foreach ( array_slice( $ids, 0, 200 ) as $id ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Delete operation on custom table.
				if ( $wpdb->delete( $table_name, array( 'id' => $id ), array( '%d' ) ) ) {
					Linktrade_Runner::forget_link( $id );
					++$deleted;
				}
			}

			wp_cache_delete( 'linktrade_link_count' );
			wp_cache_delete( 'linktrade_quick_stats' );
			wp_cache_delete( 'linktrade_full_stats' );

			wp_send_json_success(
				array(
					/* translators: %d: number of links */
					'message' => sprintf( _n( '%d link deleted.', '%d links deleted.', $deleted, 'linktrade-monitor' ), $deleted ),
				)
			);
		}

		/**
		 * Hosts that are linked from almost every site and are no link partners.
		 *
		 * @return array
		 */
		private function ignored_hosts() {
			$hosts = array(
				'facebook.com',
				'instagram.com',
				'twitter.com',
				'x.com',
				'youtube.com',
				'youtu.be',
				'linkedin.com',
				'pinterest.com',
				'tiktok.com',
				'wa.me',
				'whatsapp.com',
				't.me',
				'google.com',
				'g.page',
				'goo.gl',
				'maps.app.goo.gl',
				'apple.com',
				'amazon.com',
				'amazon.de',
				'amzn.to',
				'wikipedia.org',
				'wordpress.org',
				'wordpress.com',
				'gravatar.com',
				'github.com',
				'paypal.com',
				'w3.org',
				'schema.org',
			);

			/**
			 * Filters the hosts the outgoing link search leaves out.
			 *
			 * @param array $hosts Host names without www.
			 */
			return (array) apply_filters( 'linktrade_ignored_hosts', $hosts );
		}

		/**
		 * AJAX: collect the external domains this site links to, one batch of
		 * posts per request, so the owner can turn them into monitored links.
		 */
		public function ajax_scan_outgoing() {
			check_ajax_referer( 'linktrade_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'linktrade-monitor' ) ) );
			}

			$page  = isset( $_POST['batch'] ) ? max( 1, absint( $_POST['batch'] ) ) : 1;
			$types = get_post_types( array( 'public' => true ) );
			unset( $types['attachment'] );

			$query = new WP_Query(
				array(
					'post_type'              => array_values( $types ),
					'post_status'            => 'publish',
					'posts_per_page'         => 100,
					'paged'                  => $page,
					'orderby'                => 'ID',
					'order'                  => 'ASC',
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			$own = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
			$own = 0 === strpos( $own, 'www.' ) ? substr( $own, 4 ) : $own;

			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fresh list from custom table.
			$known_urls = (array) $wpdb->get_col( 'SELECT backlink_target FROM `' . esc_sql( $wpdb->prefix . 'linktrade_links' ) . "` WHERE backlink_target <> ''" );
			$known      = array();
			foreach ( $known_urls as $url ) {
				$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
				$known[ 0 === strpos( $host, 'www.' ) ? substr( $host, 4 ) : $host ] = true;
			}

			$ignored = array_flip( $this->ignored_hosts() );
			$found   = array();

			// Menus and widgets once, with the first batch.
			if ( 1 === $page ) {
				foreach ( $this->sitewide_links() as $entry ) {
					$host = $this->bare_host( $entry['target'] );
					$skip = ( '' === $host || $host === $own || isset( $ignored[ $host ] ) || isset( $known[ $host ] ) );
					foreach ( array_keys( $ignored ) as $ignored_host ) {
						if ( substr( $host, -strlen( '.' . $ignored_host ) ) === '.' . $ignored_host ) {
							$skip = true;
						}
					}
					if ( $skip ) {
						continue;
					}
					if ( ! isset( $found[ $host ] ) ) {
						$found[ $host ] = array(
							'host'   => $host,
							'count'  => 0,
							'page'   => $entry['page'],
							'title'  => $entry['title'],
							'target' => $entry['target'],
						);
					}
					++$found[ $host ]['count'];
				}
			}

			foreach ( $query->posts as $post ) {
				if ( false === stripos( $post->post_content, 'href' ) || ! preg_match_all( '/<a\b[^>]*\bhref\s*=\s*["\'](https?:\/\/[^"\']+)["\']/i', $post->post_content, $matches ) ) {
					continue;
				}

				$in_this_post = array();

				foreach ( array_unique( $matches[1] ) as $href ) {
					$host = strtolower( (string) wp_parse_url( $href, PHP_URL_HOST ) );
					$host = 0 === strpos( $host, 'www.' ) ? substr( $host, 4 ) : $host;

					// Counted once per page, however many links the page has.
					if ( '' === $host || $host === $own || isset( $ignored[ $host ] ) || isset( $known[ $host ] ) || isset( $in_this_post[ $host ] ) ) {
						continue;
					}
					$in_this_post[ $host ] = true;
					// Sub-domains of ignored hosts, e.g. de.wikipedia.org.
					foreach ( array_keys( $ignored ) as $skip ) {
						if ( substr( $host, -strlen( '.' . $skip ) ) === '.' . $skip ) {
							continue 2;
						}
					}

					if ( ! isset( $found[ $host ] ) ) {
						$found[ $host ] = array(
							'host'   => $host,
							'count'  => 0,
							'page'   => get_permalink( $post ),
							'title'  => html_entity_decode( wp_strip_all_tags( get_the_title( $post ) ), ENT_QUOTES, 'UTF-8' ),
							'target' => esc_url_raw( $href ),
						);
					}
					++$found[ $host ]['count'];
				}
			}

			wp_send_json_success(
				array(
					'domains' => array_values( $found ),
					'batch'   => $page,
					'more'    => $page < (int) $query->max_num_pages,
					'pages'   => (int) $query->max_num_pages,
				)
			);
		}

		/**
		 * Load one link row.
		 *
		 * @param int $id Link ID.
		 * @return object|null
		 */
		private function get_link_row( $id ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'linktrade_links';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fresh row from custom table.
			return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM `' . esc_sql( $table_name ) . '` WHERE id = %d', absint( $id ) ) );
		}

		/**
		 * Common entry check of the AJAX handlers: nonce, capability, link.
		 *
		 * @return object Link row. Ends the request with an error otherwise.
		 */
		private function ajax_require_link() {
			check_ajax_referer( 'linktrade_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'linktrade-monitor' ) ) );
			}

			$link = $this->get_link_row( isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0 );
			if ( ! $link ) {
				wp_send_json_error( array( 'message' => __( 'Link not found.', 'linktrade-monitor' ) ) );
			}

			return $link;
		}

		/**
		 * What a check found, in one sentence.
		 *
		 * @param object $link Link row after the check.
		 * @return string
		 */
		private function describe_result( $link ) {
			$unreadable = Linktrade_Runner::get_unreadable();

			if ( isset( $unreadable[ $link->id . ':incoming' ] ) ) {
				return __( 'The partner page could not be read, so the link is not verified yet.', 'linktrade-monitor' );
			}

			switch ( $link->status ) {
				case 'online':
					$text = __( 'The link to you is online.', 'linktrade-monitor' );
					break;
				case 'warning':
					/* translators: %s: reason, e.g. nofollow */
					$text = sprintf( __( 'The link to you is there, but devalued: %s.', 'linktrade-monitor' ), $this->get_warning_reason( $link ) );
					break;
				case 'offline':
					$text = __( 'No link to your page was found on the partner page.', 'linktrade-monitor' );
					break;
				default:
					$text = __( 'Not checked yet.', 'linktrade-monitor' );
			}

			if ( 'exchange' === $link->category && ! empty( $link->backlink_url ) ) {
				if ( isset( $unreadable[ $link->id . ':outgoing' ] ) ) {
					$text .= ' ' . __( 'Your own page could not be read.', 'linktrade-monitor' );
				} elseif ( 'offline' === $link->backlink_status ) {
					$text .= ' ' . __( 'Your link to the partner is missing.', 'linktrade-monitor' );
				} elseif ( in_array( $link->backlink_status, array( 'online', 'warning' ), true ) ) {
					$text .= ' ' . __( 'Your link to the partner is online.', 'linktrade-monitor' );
				}
			}

			return $text;
		}

		/**
		 * AJAX: check one link now.
		 */
		public function ajax_check_now() {
			$link = $this->ajax_require_link();

			Linktrade_Runner::check_link( $link );

			$link = $this->get_link_row( $link->id );

			wp_send_json_success( array( 'message' => $link->partner_name . ': ' . $this->describe_result( $link ) ) );
		}

		/**
		 * AJAX: history of one link (changes and recent checks).
		 */
		public function ajax_get_history() {
			$link = $this->ajax_require_link();

			global $wpdb;

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Display queries on custom tables.
			$changes = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT action, old_value, new_value, created_at FROM `' . esc_sql( $wpdb->prefix . 'linktrade_log' ) . '` WHERE link_id = %d ORDER BY created_at DESC, id DESC LIMIT 30',
					$link->id
				)
			);
			$checks  = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT check_type, checked_at, http_code, is_nofollow, is_noindex, is_sponsored, anchor_found, error_message FROM `' . esc_sql( $wpdb->prefix . 'linktrade_checks' ) . '` WHERE link_id = %d ORDER BY checked_at DESC, id DESC LIMIT 30',
					$link->id
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			$format    = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
			$direction = array(
				'status'          => __( 'Their link to you', 'linktrade-monitor' ),
				'backlink_status' => __( 'Your link to them', 'linktrade-monitor' ),
				'incoming'        => __( 'Their link to you', 'linktrade-monitor' ),
				'outgoing'        => __( 'Your link to them', 'linktrade-monitor' ),
			);
			$status    = array(
				'online'         => __( 'Online', 'linktrade-monitor' ),
				'warning'        => __( 'Warning', 'linktrade-monitor' ),
				'offline'        => __( 'Offline', 'linktrade-monitor' ),
				'unchecked'      => __( 'Unchecked', 'linktrade-monitor' ),
				'not_applicable' => __( 'Not set', 'linktrade-monitor' ),
			);

			$out_changes = array();
			foreach ( (array) $changes as $row ) {
				$out_changes[] = array(
					'date' => mysql2date( $format, $row->created_at ),
					'what' => isset( $direction[ $row->action ] ) ? $direction[ $row->action ] : $row->action,
					'from' => isset( $status[ $row->old_value ] ) ? $status[ $row->old_value ] : $row->old_value,
					'to'   => isset( $status[ $row->new_value ] ) ? $status[ $row->new_value ] : $row->new_value,
				);
			}

			$out_checks = array();
			foreach ( (array) $checks as $row ) {
				$flags = array();
				if ( $row->is_nofollow ) {
					$flags[] = 'nofollow';
				}
				if ( $row->is_sponsored ) {
					$flags[] = 'sponsored';
				}
				if ( $row->is_noindex ) {
					$flags[] = 'noindex';
				}

				$note = (string) $row->error_message;
				if ( 0 === strpos( $note, '[unreadable] ' ) ) {
					$note = __( 'Could not be read', 'linktrade-monitor' ) . ': ' . substr( $note, 13 );
				} elseif ( '' === $note ) {
					$note = $row->anchor_found ? __( 'Link found', 'linktrade-monitor' ) . ': ' . $row->anchor_found : __( 'Link found', 'linktrade-monitor' );
				}

				$out_checks[] = array(
					'date'  => mysql2date( $format, $row->checked_at ),
					'what'  => isset( $direction[ $row->check_type ] ) ? $direction[ $row->check_type ] : $row->check_type,
					'code'  => (int) $row->http_code,
					'flags' => implode( ', ', $flags ),
					'note'  => $note,
				);
			}

			wp_send_json_success(
				array(
					'partner' => $link->partner_name,
					'changes' => $out_changes,
					'checks'  => $out_checks,
					'proof'   => Linktrade_Runner::proof_text( $link ),
				)
			);
		}

		/**
		 * AJAX: a ready-to-send message to the partner about what was found.
		 *
		 * Nothing is sent. The text is shown for copying.
		 */
		public function ajax_get_message() {
			$link = $this->ajax_require_link();

			$since_map = Linktrade_Runner::since_map( 'status', 'offline' );
			$since     = isset( $since_map[ (int) $link->id ] ) ? mysql2date( get_option( 'date_format' ), $since_map[ (int) $link->id ] ) : '';
			$lines     = array();

			$lines[] = __( 'Hello,', 'linktrade-monitor' );
			$lines[] = '';

			if ( 'offline' === $link->status ) {
				/* translators: 1: partner page URL, 2: own target URL */
				$lines[] = sprintf( __( 'I noticed that the link on %1$s to %2$s is no longer there.', 'linktrade-monitor' ), $link->partner_url, $link->target_url );
				if ( '' !== $since ) {
					/* translators: %s: date */
					$lines[] = sprintf( __( 'My check has been reporting it as missing since %s.', 'linktrade-monitor' ), $since );
				}
			} elseif ( Linktrade_Runner::is_devalued( $link ) && ( ! empty( $link->is_nofollow ) || ! empty( $link->is_sponsored ) || ! empty( $link->is_noindex ) ) ) {
				/* translators: 1: partner page URL, 2: own target URL, 3: reason such as nofollow */
				$lines[] = sprintf( __( 'I noticed that the link on %1$s to %2$s is now set to: %3$s.', 'linktrade-monitor' ), $link->partner_url, $link->target_url, $this->get_warning_reason( $link ) );
				$lines[] = __( 'We had agreed on a normal, followed link on a page that search engines may index.', 'linktrade-monitor' );
			} elseif ( Linktrade_Runner::is_devalued( $link ) && ! empty( $link->redirect_url ) ) {
				/* translators: 1: partner page URL, 2: URL the link points to now, 3: agreed target URL */
				$lines[] = sprintf( __( 'I noticed that the link on %1$s now points to %2$s. We had agreed on %3$s.', 'linktrade-monitor' ), $link->partner_url, $link->redirect_url, $link->target_url );
			} elseif ( Linktrade_Runner::anchor_differs( $link ) ) {
				/* translators: 1: partner page URL, 2: anchor text found, 3: agreed anchor text */
				$lines[] = sprintf( __( 'I noticed that the link on %1$s now reads "%2$s". We had agreed on the text "%3$s".', 'linktrade-monitor' ), $link->partner_url, $link->found_anchor, $link->anchor_text );
			} else {
				wp_send_json_error( array( 'message' => __( 'There is nothing to complain about for this link.', 'linktrade-monitor' ) ) );
			}

			if ( 'exchange' === $link->category && ! empty( $link->backlink_url ) && in_array( $link->backlink_status, array( 'online', 'warning' ), true ) ) {
				/* translators: %s: own page URL */
				$lines[] = sprintf( __( 'My link to you is still in place: %s', 'linktrade-monitor' ), $link->backlink_url );
			}

			$lines[] = '';
			$lines[] = __( 'Was that intended, or did it happen during a change to your site? It would be great if you could restore it.', 'linktrade-monitor' );
			$lines[] = '';
			$lines[] = __( 'Thank you and best regards', 'linktrade-monitor' );

			wp_send_json_success(
				array(
					'partner' => $link->partner_name,
					'contact' => (string) $link->partner_contact,
					'message' => implode( "\n", $lines ),
				)
			);
		}

		/**
		 * AJAX: find links to a partner's domain in the published content of this
		 * site, so the owner does not have to look up the page by hand.
		 */
		public function ajax_find_backlink() {
			check_ajax_referer( 'linktrade_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'linktrade-monitor' ) ) );
			}

			$partner_url = isset( $_POST['partner_url'] ) ? esc_url_raw( wp_unslash( $_POST['partner_url'] ) ) : '';
			$host        = strtolower( (string) wp_parse_url( $partner_url, PHP_URL_HOST ) );
			$host        = 0 === strpos( $host, 'www.' ) ? substr( $host, 4 ) : $host;

			if ( '' === $host ) {
				wp_send_json_error( array( 'message' => __( 'Enter the partner page first.', 'linktrade-monitor' ) ) );
			}

			$types = get_post_types( array( 'public' => true ) );
			unset( $types['attachment'] );

			$query = new WP_Query(
				array(
					'post_type'              => array_values( $types ),
					'post_status'            => 'publish',
					's'                      => $host,
					'search_columns'         => array( 'post_content' ),
					'posts_per_page'         => 20,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			$found = array();
			foreach ( $query->posts as $post ) {
				if ( ! preg_match_all( '/<a\b[^>]*\bhref\s*=\s*["\']([^"\']+)["\']/i', $post->post_content, $matches ) ) {
					continue;
				}
				foreach ( array_unique( $matches[1] ) as $href ) {
					$href_host = strtolower( (string) wp_parse_url( $href, PHP_URL_HOST ) );
					$href_host = 0 === strpos( $href_host, 'www.' ) ? substr( $href_host, 4 ) : $href_host;
					if ( $href_host === $host ) {
						$found[] = array(
							'title'  => html_entity_decode( wp_strip_all_tags( get_the_title( $post ) ), ENT_QUOTES, 'UTF-8' ),
							'page'   => get_permalink( $post ),
							'target' => esc_url_raw( $href ),
						);
					}
				}
			}

			foreach ( $this->sitewide_links() as $entry ) {
				if ( $this->bare_host( $entry['target'] ) === $host ) {
					$found[] = $entry;
				}
			}

			if ( empty( $found ) ) {
				wp_send_json_error(
					array(
						/* translators: %s: domain name */
						'message' => sprintf( __( 'No link to %s found in your published posts, pages, menus and widgets. A link that your theme writes into a template is not searched: enter it by hand.', 'linktrade-monitor' ), $host ),
					)
				);
			}

			wp_send_json_success( array( 'links' => array_slice( $found, 0, 20 ) ) );
		}

		/**
		 * AJAX: send a test mail to the notification address, so the owner knows
		 * the alerts can reach them before the first real one is due.
		 */
		public function ajax_test_mail() {
			check_ajax_referer( 'linktrade_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'linktrade-monitor' ) ) );
			}

			$to = Linktrade_Runner::recipient();
			if ( '' === $to ) {
				wp_send_json_error( array( 'message' => __( 'There is no valid notification address. Enter one and save the settings first.', 'linktrade-monitor' ) ) );
			}

			$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
			$sent = wp_mail(
				$to,
				/* translators: %s: site name */
				sprintf( __( '[%s] Linktrade Monitor test email', 'linktrade-monitor' ), $site ),
				__( 'This is a test. If you can read it, Linktrade Monitor can reach you when a link disappears or changes.', 'linktrade-monitor' ) . "\n\n" . admin_url( 'admin.php?page=linktrade-monitor' )
			);

			if ( ! $sent ) {
				wp_send_json_error( array( 'message' => __( 'WordPress could not hand the email over for sending. This site may not be set up to send mail: ask your host or use an SMTP plugin.', 'linktrade-monitor' ) ) );
			}

			/* translators: %s: email address */
			wp_send_json_success( array( 'message' => sprintf( __( 'Test email sent to %s. Look in the spam folder too if it does not arrive.', 'linktrade-monitor' ), $to ) ) );
		}

		/**
		 * Links that sit outside posts and pages: custom links in menus and the
		 * content of text, HTML and block widgets. They appear on every page, so
		 * the home page stands for them.
		 *
		 * @return array List of arrays with title, page and target.
		 */
		private function sitewide_links() {
			$found = array();

			$items = get_posts(
				array(
					'post_type'      => 'nav_menu_item',
					'posts_per_page' => 500,
					'post_status'    => 'publish',
					'meta_key'       => '_menu_item_type', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Small, admin-only lookup of custom menu links.
				'meta_value'         => 'custom', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- See above.
				'no_found_rows'      => true,
				)
			);
			foreach ( $items as $item ) {
				$url = (string) get_post_meta( $item->ID, '_menu_item_url', true );
				if ( preg_match( '#^https?://#i', $url ) ) {
					$found[] = array(
						'title'  => __( 'Menu', 'linktrade-monitor' ) . ': ' . html_entity_decode( wp_strip_all_tags( $item->post_title ), ENT_QUOTES, 'UTF-8' ),
						'page'   => home_url( '/' ),
						'target' => esc_url_raw( $url ),
					);
				}
			}

			foreach ( array(
				'widget_block'       => 'content',
				'widget_text'        => 'text',
				'widget_custom_html' => 'content',
			) as $option => $field ) {
				foreach ( (array) get_option( $option, array() ) as $widget ) {
					if ( ! is_array( $widget ) || empty( $widget[ $field ] ) ) {
						continue;
					}
					if ( preg_match_all( '/<a\b[^>]*\bhref\s*=\s*["\'](https?:\/\/[^"\']+)["\']/i', (string) $widget[ $field ], $matches ) ) {
						foreach ( array_unique( $matches[1] ) as $href ) {
							$found[] = array(
								'title'  => __( 'Widget or footer block', 'linktrade-monitor' ),
								'page'   => home_url( '/' ),
								'target' => esc_url_raw( $href ),
							);
						}
					}
				}
			}

			return $found;
		}

		/**
		 * Host of a URL without www, lowercased.
		 *
		 * @param string $url URL.
		 * @return string
		 */
		private function bare_host( $url ) {
			$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );

			return 0 === strpos( $host, 'www.' ) ? substr( $host, 4 ) : $host;
		}

		/**
		 * Read an uploaded CSV file into rows.
		 *
		 * Handles a UTF-8 byte order mark, comma or semicolon as separator (the
		 * latter is what spreadsheet programs write in many countries) and quoted
		 * fields that span several lines.
		 *
		 * @param string $file Path of the uploaded file.
		 * @return array|WP_Error Rows, first row is the header.
		 */
		private function read_csv( $file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading the uploaded temp file.
			$content = file_get_contents( $file );
			if ( false === $content || '' === trim( $content ) ) {
				return new WP_Error( 'linktrade_csv', __( 'Could not read file.', 'linktrade-monitor' ) );
			}

			if ( 0 === strpos( $content, "\xEF\xBB\xBF" ) ) {
				$content = substr( $content, 3 );
			}

			$first     = strtok( $content, "\r\n" );
			$delimiter = ( substr_count( (string) $first, ';' ) > substr_count( (string) $first, ',' ) ) ? ';' : ',';

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- In-memory stream, no file system access.
			$stream = fopen( 'php://temp', 'r+' );
			if ( ! $stream ) {
				return new WP_Error( 'linktrade_csv', __( 'Could not read file.', 'linktrade-monitor' ) );
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- In-memory stream.
			fwrite( $stream, $content );
			rewind( $stream );

			$rows = array();
			// phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition -- Standard fgetcsv loop.
			while ( false !== ( $row = fgetcsv( $stream, 0, $delimiter, '"', '\\' ) ) ) {
				if ( array( null ) === $row ) {
					continue;
				}
				$rows[] = $row;
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- In-memory stream.
			fclose( $stream );

			return $rows;
		}

		/**
		 * AJAX: Import links from CSV
		 */
		public function ajax_import_csv() {
			// Verify nonce from POST data.
			if ( ! isset( $_POST['linktrade_import_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['linktrade_import_nonce'] ), 'linktrade_import' ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed.', 'linktrade-monitor' ) ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'linktrade-monitor' ) ) );
			}

			if ( ! isset( $_FILES['import_file'] ) || empty( $_FILES['import_file']['tmp_name'] ) ) {
				wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'linktrade-monitor' ) ) );
			}

			$skip_duplicates = isset( $_POST['skip_duplicates'] ) && '1' === $_POST['skip_duplicates'];

			// First pass shows what would happen, the second one writes.
			$preview      = isset( $_POST['preview'] ) && '1' === $_POST['preview'];
			$preview_rows = array();

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- tmp_name is a server-generated path, not user input.
			$tmp_file = $_FILES['import_file']['tmp_name'];
			if ( ! is_uploaded_file( $tmp_file ) ) {
				wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'linktrade-monitor' ) ) );
			}

			$rows = $this->read_csv( $tmp_file );
			if ( is_wp_error( $rows ) ) {
				wp_send_json_error( array( 'message' => $rows->get_error_message() ) );
			}
			if ( count( $rows ) < 2 ) {
				wp_send_json_error( array( 'message' => __( 'CSV file must contain a header row and at least one data row.', 'linktrade-monitor' ) ) );
			}

			$header = array_map( 'strtolower', array_map( 'trim', array_shift( $rows ) ) );

			foreach ( array( 'partner_name', 'partner_url', 'target_url' ) as $field ) {
				if ( ! in_array( $field, $header, true ) ) {
					wp_send_json_error(
						array(
							/* translators: %s: field name */
							'message' => sprintf( __( 'Required field missing: %s', 'linktrade-monitor' ), $field ),
						)
					);
				}
			}

			global $wpdb;
			$table_name = $wpdb->prefix . 'linktrade_links';

			$existing_urls = array();
			if ( $skip_duplicates ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Need fresh data for import.
				$existing_urls = array_map( 'strtolower', (array) $wpdb->get_col( 'SELECT partner_url FROM `' . esc_sql( $table_name ) . '`' ) );
			}

			$text_fields = array( 'partner_name', 'anchor_text', 'backlink_anchor' );
			$url_fields  = array( 'partner_url', 'target_url', 'backlink_url', 'backlink_target' );

			$imported = 0;
			$skipped  = 0;
			$problems = array();
			$line     = 1;

			foreach ( $rows as $values ) {
				++$line;

				if ( count( $values ) !== count( $header ) ) {
					/* translators: 1: line number, 2: columns found, 3: columns expected */
					$problems[] = sprintf( __( 'Line %1$d: %2$d columns instead of %3$d.', 'linktrade-monitor' ), $line, count( $values ), count( $header ) );
					continue;
				}

				$row  = array_combine( $header, $values );
				$data = array();

				foreach ( $text_fields as $field ) {
					if ( isset( $row[ $field ] ) ) {
						// A leading apostrophe is the formula guard of our own export.
						$data[ $field ] = sanitize_text_field( ltrim( trim( $row[ $field ] ), "'" ) );
					}
				}
				foreach ( $url_fields as $field ) {
					if ( isset( $row[ $field ] ) ) {
						$data[ $field ] = esc_url_raw( trim( $row[ $field ] ) );
					}
				}
				if ( isset( $row['partner_contact'] ) ) {
					$data['partner_contact'] = sanitize_email( ltrim( trim( $row['partner_contact'] ), "'" ) );
				}
				if ( isset( $row['notes'] ) ) {
					$data['notes'] = sanitize_textarea_field( ltrim( trim( $row['notes'] ), "'" ) );
				}
				foreach ( array( 'domain_rating', 'my_domain_rating' ) as $field ) {
					if ( isset( $row[ $field ] ) ) {
						$data[ $field ] = min( 100, absint( $row[ $field ] ) );
					}
				}
				foreach ( array( 'start_date', 'end_date' ) as $field ) {
					if ( isset( $row[ $field ] ) ) {
						$value          = trim( $row[ $field ] );
						$data[ $field ] = $this->is_valid_date( $value ) ? $value : null;
					}
				}

				// Absent or empty means the usual case: a followed link was agreed.
				if ( $this->has_agreement_columns() ) {
					$data['follow_agreed'] = ( isset( $row['follow_agreed'] ) && '0' === trim( $row['follow_agreed'] ) ) ? 0 : 1;
				}

				$category         = isset( $row['category'] ) ? strtolower( trim( $row['category'] ) ) : '';
				$data['category'] = in_array( $category, array( 'exchange', 'paid', 'free' ), true ) ? $category : 'exchange';

				if ( empty( $data['partner_name'] ) || empty( $data['partner_url'] ) || ! $this->is_web_url( $data['partner_url'] ) || empty( $data['target_url'] ) || ! $this->is_web_url( $data['target_url'] ) ) {
					/* translators: %d: line number */
					$problems[] = sprintf( __( 'Line %d: name, partner page or target page is missing or not a web address.', 'linktrade-monitor' ), $line );
					continue;
				}

				if ( $skip_duplicates && in_array( strtolower( $data['partner_url'] ), $existing_urls, true ) ) {
					++$skipped;
					continue;
				}

				$data['status'] = 'unchecked';

				if ( $preview ) {
					++$imported;
					$existing_urls[] = strtolower( $data['partner_url'] );
					if ( count( $preview_rows ) < 10 ) {
						$preview_rows[] = array(
							'name'     => $data['partner_name'],
							'page'     => $data['partner_url'],
							'target'   => $data['target_url'],
							'category' => $data['category'],
						);
					}
					continue;
				}

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Insert operation.
				if ( $wpdb->insert( $table_name, $data ) ) {
					++$imported;
					$existing_urls[] = strtolower( $data['partner_url'] );
				} else {
					/* translators: %d: line number */
					$problems[] = sprintf( __( 'Line %d: could not be saved.', 'linktrade-monitor' ), $line );
				}
			}

			if ( $preview ) {
				wp_send_json_success(
					array(
						'preview'  => true,
						'message'  => sprintf(
							/* translators: %1$d: links that would be imported, %2$d: duplicates, %3$d: lines with problems */
							__( 'New links in this file: %1$d. Already in your list and skipped: %2$d. Lines with a problem, left out: %3$d.', 'linktrade-monitor' ),
							$imported,
							$skipped,
							count( $problems )
						),
						'imported' => $imported,
						'skipped'  => $skipped,
						'errors'   => count( $problems ),
						'rows'     => $preview_rows,
						'problems' => array_slice( $problems, 0, 15 ),
					)
				);
			}

			wp_cache_delete( 'linktrade_link_count' );
			wp_cache_delete( 'linktrade_quick_stats' );
			wp_cache_delete( 'linktrade_full_stats' );

			$message = sprintf(
			/* translators: %1$d: number of imported links, %2$d: number of skipped duplicates, %3$d: number of errors */
				__( 'Import complete: %1$d imported, %2$d skipped (duplicates), %3$d errors.', 'linktrade-monitor' ),
				$imported,
				$skipped,
				count( $problems )
			);

			if ( $imported > 0 ) {
				// Imported links are checked in the background, a few at a time.
				delete_transient( 'linktrade_attention_count' );
				Linktrade_Runner::start_new_links_run();
				$message .= ' ' . __( 'The new links are being checked in the background.', 'linktrade-monitor' );
			}

			wp_send_json_success(
				array(
					'message'  => $message,
					'imported' => $imported,
					'skipped'  => $skipped,
					'errors'   => count( $problems ),
					'problems' => array_slice( $problems, 0, 15 ),
				)
			);
		}
	}
} // End class_exists check
