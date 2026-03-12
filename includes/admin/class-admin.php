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
if (!class_exists('Linktrade_Admin')) {
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

        wp_enqueue_style(
            'linktrade-admin',
            LINKTRADE_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            LINKTRADE_VERSION
        );

        wp_enqueue_script(
            'linktrade-admin',
            LINKTRADE_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            LINKTRADE_VERSION,
            true
        );

        wp_localize_script(
            'linktrade-admin',
            'linktrade',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'linktrade_nonce' ),
                'strings'  => array(
                    'confirm_delete' => __( 'Really delete this link?', 'linktrade-monitor' ),
                    'saving'         => __( 'Saving...', 'linktrade-monitor' ),
                    'saved'          => __( 'Saved!', 'linktrade-monitor' ),
                    'error'          => __( 'An error occurred', 'linktrade-monitor' ),
                    // Removed limit_reached for WordPress.org compliance
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
                            <span>📖</span>
                            <?php esc_html_e( 'Docs', 'linktrade-monitor' ); ?>
                        </a>
                        <a href="https://wordpress.org/support/plugin/linktrade-monitor/" target="_blank" class="linktrade-header-btn">
                            <span>💬</span>
                            <?php esc_html_e( 'Support', 'linktrade-monitor' ); ?>
                        </a>
                    </div>
                </div>
            </div>

            <nav class="linktrade-tabs">
                <?php foreach ( $tabs as $tab_id => $tab_data ) : ?>
                    <a href="<?php echo esc_url( add_query_arg( 'tab', $tab_id, admin_url( 'admin.php?page=linktrade-monitor' ) ) ); ?>"
                       class="linktrade-tab <?php echo $current_tab === $tab_id ? 'active' : ''; ?>">
                        <span class="tab-icon"><?php echo esc_html( $tab_data['icon'] ); ?></span>
                        <?php echo esc_html( $tab_data['label'] ); ?>
                        <?php if ( ! empty( $tab_data['badge'] ) ) : ?>
                            <span class="tab-badge <?php echo esc_attr( isset( $tab_data['badge_class'] ) ? $tab_data['badge_class'] : '' ); ?>"><?php echo esc_html( $tab_data['badge'] ); ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="linktrade-content">
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
     * Check if link limit reached
     *
     * @return bool Always false - no limits.
     */
    private function is_limit_reached() {
        return false;
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
                'icon'  => '📊',
            ),
            'links'     => array(
                'label' => __( 'All Links', 'linktrade-monitor' ),
                'icon'  => '🔗',
                'badge' => $stats['total'],
            ),
            'fairness'  => array(
                'label'       => __( 'Fairness', 'linktrade-monitor' ),
                'icon'        => '⚖️',
                'badge'       => $stats['unfair'] > 0 ? $stats['unfair'] : '',
                'badge_class' => $stats['unfair'] > 0 ? 'badge-warning' : '',
            ),
            'add'       => array(
                'label' => __( 'New', 'linktrade-monitor' ),
                'icon'  => '➕',
            ),
            'import'    => array(
                'label' => __( 'Import/Export', 'linktrade-monitor' ),
                'icon'  => '📥',
            ),
            'settings'  => array(
                'label' => __( 'Settings', 'linktrade-monitor' ),
                'icon'  => '⚙️',
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
                'unfair' => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $table_name ) . "` WHERE category = 'exchange' AND fairness_score < 100 AND fairness_score > 0" ),
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
     * Render dashboard tab
     *
     * @param array $stats Statistics array.
     */
    private function render_dashboard_tab( $stats ) {
        $problems    = $stats['warning'] + $stats['offline'];
        $is_new_user = 0 === $stats['total'];
        ?>

        <?php if ( $is_new_user ) : ?>
            <!-- Onboarding Banner -->
            <div class="lt-onboarding">
                <span class="lt-onboarding-icon">👋</span>
                <div class="lt-onboarding-content">
                    <h3><?php esc_html_e( 'Welcome to Linktrade Monitor!', 'linktrade-monitor' ); ?></h3>
                    <p><?php esc_html_e( 'Start tracking your backlinks and link exchanges. Add your first link to get started.', 'linktrade-monitor' ); ?></p>
                </div>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=linktrade-monitor&tab=add' ) ); ?>" class="lt-onboarding-btn">
                    <span>✨</span>
                    <?php esc_html_e( 'Add First Link', 'linktrade-monitor' ); ?>
                </a>
            </div>
        <?php endif; ?>

        <!-- Stats Grid with Icons -->
        <div class="lt-stats-grid">
            <div class="lt-stat-card stat-blue">
                <div class="lt-stat-header">
                    <span class="lt-stat-icon">🔗</span>
                    <?php if ( $stats['total'] > 0 ) : ?>
                        <span class="lt-stat-trend">+<?php echo esc_html( $stats['total'] ); ?></span>
                    <?php endif; ?>
                </div>
                <div class="lt-stat-value"><?php echo esc_html( $stats['total'] ); ?></div>
                <div class="lt-stat-label"><?php esc_html_e( 'Total Links', 'linktrade-monitor' ); ?></div>
            </div>

            <div class="lt-stat-card stat-green">
                <div class="lt-stat-header">
                    <span class="lt-stat-icon">✅</span>
                    <?php if ( $stats['online'] > 0 ) : ?>
                        <span class="lt-stat-trend">+<?php echo esc_html( $stats['online'] ); ?></span>
                    <?php endif; ?>
                </div>
                <div class="lt-stat-value"><?php echo esc_html( $stats['online'] ); ?></div>
                <div class="lt-stat-label"><?php esc_html_e( 'Online', 'linktrade-monitor' ); ?></div>
            </div>

            <div class="lt-stat-card stat-orange">
                <div class="lt-stat-header">
                    <span class="lt-stat-icon">⚠️</span>
                </div>
                <div class="lt-stat-value"><?php echo esc_html( $stats['warning'] ); ?></div>
                <div class="lt-stat-label"><?php esc_html_e( 'Warnings', 'linktrade-monitor' ); ?></div>
            </div>

            <div class="lt-stat-card stat-red">
                <div class="lt-stat-header">
                    <span class="lt-stat-icon">❌</span>
                    <span class="lt-stat-trend trend-neutral"><?php esc_html_e( 'v1.3', 'linktrade-monitor' ); ?></span>
                </div>
                <div class="lt-stat-value"><?php echo esc_html( $problems ); ?></div>
                <div class="lt-stat-label"><?php esc_html_e( 'Problems', 'linktrade-monitor' ); ?></div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="lt-content-grid">
            <!-- Left Column: Category Overview -->
            <div class="linktrade-card">
                <h2>
                    <span class="card-icon">📊</span>
                    <?php esc_html_e( 'By Category', 'linktrade-monitor' ); ?>
                </h2>

                <div class="lt-category-list">
                    <div class="lt-category-item">
                        <div class="lt-category-avatar exchange">🔄</div>
                        <div class="lt-category-info">
                            <h3 class="lt-category-title"><?php esc_html_e( 'Link Exchanges', 'linktrade-monitor' ); ?></h3>
                            <span class="lt-category-meta"><?php esc_html_e( 'Reciprocal links', 'linktrade-monitor' ); ?></span>
                        </div>
                        <div class="lt-category-count"><?php echo esc_html( $stats['exchange'] ); ?></div>
                    </div>

                    <div class="lt-category-item">
                        <div class="lt-category-avatar paid">💰</div>
                        <div class="lt-category-info">
                            <h3 class="lt-category-title"><?php esc_html_e( 'Paid Links', 'linktrade-monitor' ); ?></h3>
                            <span class="lt-category-meta"><?php esc_html_e( 'Purchased backlinks', 'linktrade-monitor' ); ?></span>
                        </div>
                        <div class="lt-category-count"><?php echo esc_html( $stats['paid'] ); ?></div>
                    </div>

                    <div class="lt-category-item">
                        <div class="lt-category-avatar free">🎁</div>
                        <div class="lt-category-info">
                            <h3 class="lt-category-title"><?php esc_html_e( 'Free Backlinks', 'linktrade-monitor' ); ?></h3>
                            <span class="lt-category-meta"><?php esc_html_e( 'Guest posts, directories', 'linktrade-monitor' ); ?></span>
                        </div>
                        <div class="lt-category-count"><?php echo esc_html( $stats['free'] ); ?></div>
                    </div>
                </div>

                <!-- Average DR -->
                <div class="lt-dr-display">
                    <span class="lt-dr-label"><?php esc_html_e( 'Average DR', 'linktrade-monitor' ); ?></span>
                    <span class="lt-dr-value"><?php echo $stats['avg_dr'] ? esc_html( round( $stats['avg_dr'], 1 ) ) : '—'; ?></span>
                </div>
            </div>

            <!-- Right Column: Quick Actions + Feature Showcase -->
            <div>
                <!-- Quick Actions -->
                <div class="linktrade-card">
                    <h2>
                        <span class="card-icon">⚡</span>
                        <?php esc_html_e( 'Quick Actions', 'linktrade-monitor' ); ?>
                    </h2>
                    <div class="lt-quick-actions">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=linktrade-monitor&tab=add' ) ); ?>" class="lt-quick-action">
                            <div class="lt-quick-action-icon">➕</div>
                            <span class="lt-quick-action-label"><?php esc_html_e( 'Add Link', 'linktrade-monitor' ); ?></span>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=linktrade-monitor&tab=fairness' ) ); ?>" class="lt-quick-action">
                            <div class="lt-quick-action-icon">⚖️</div>
                            <span class="lt-quick-action-label"><?php esc_html_e( 'Check Fairness', 'linktrade-monitor' ); ?></span>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=linktrade-monitor&tab=import' ) ); ?>" class="lt-quick-action">
                            <div class="lt-quick-action-icon">📥</div>
                            <span class="lt-quick-action-label"><?php esc_html_e( 'Import/Export', 'linktrade-monitor' ); ?></span>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=linktrade-monitor&tab=settings' ) ); ?>" class="lt-quick-action">
                            <div class="lt-quick-action-icon">⚙️</div>
                            <span class="lt-quick-action-label"><?php esc_html_e( 'Settings', 'linktrade-monitor' ); ?></span>
                        </a>
                    </div>
                </div>

                <!-- Feature Showcase -->
                <div class="lt-feature-showcase">
                    <div class="lt-feature-showcase-header">
                        <span class="lt-feature-badge"><?php esc_html_e( 'v1.3 Features', 'linktrade-monitor' ); ?></span>
                    </div>
                    <h3><?php esc_html_e( 'Included in Free', 'linktrade-monitor' ); ?></h3>
                    <div class="lt-feature-tags">
                        <span class="lt-feature-tag">
                            <span class="tag-icon">💯</span>
                            <?php esc_html_e( 'Health Score', 'linktrade-monitor' ); ?>
                        </span>
                        <span class="lt-feature-tag">
                            <span class="tag-icon">⚖️</span>
                            <?php esc_html_e( 'Fairness Tracker', 'linktrade-monitor' ); ?>
                        </span>
                        <span class="lt-feature-tag">
                            <span class="tag-icon">📥</span>
                            <?php esc_html_e( 'CSV Import/Export', 'linktrade-monitor' ); ?>
                        </span>
                        <span class="lt-feature-tag">
                            <span class="tag-icon">📧</span>
                            <?php esc_html_e( 'Email Alerts', 'linktrade-monitor' ); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pro Card -->
        <div class="lt-pro-section">
            <div class="linktrade-card lt-pro-card">
                <div class="lt-pro-badge"><?php esc_html_e( 'PRO', 'linktrade-monitor' ); ?></div>
                <h2><?php esc_html_e( 'Need More Power?', 'linktrade-monitor' ); ?></h2>
                <p class="lt-pro-subtitle"><?php esc_html_e( 'Upgrade to Pro for 10+ advanced features', 'linktrade-monitor' ); ?></p>

                <ul class="lt-pro-features">
                    <li><span class="pro-check">✓</span> <?php esc_html_e( 'Project Management', 'linktrade-monitor' ); ?></li>
                    <li><span class="pro-check">✓</span> <?php esc_html_e( 'ROI Tracking & Analytics', 'linktrade-monitor' ); ?></li>
                    <li><span class="pro-check">✓</span> <?php esc_html_e( 'Anchor Text Analysis', 'linktrade-monitor' ); ?></li>
                    <li><span class="pro-check">✓</span> <?php esc_html_e( 'Webhook & Slack Notifications', 'linktrade-monitor' ); ?></li>
                    <li><span class="pro-check">✓</span> <?php esc_html_e( 'Configurable Check Frequency', 'linktrade-monitor' ); ?></li>
                    <li><span class="pro-check">✓</span> <?php esc_html_e( 'Tags & Link Organization', 'linktrade-monitor' ); ?></li>
                </ul>

                <a href="https://www.3task.de/linktrade-monitor-pro/" target="_blank" class="lt-pro-btn">
                    <?php esc_html_e( 'Learn More About Pro', 'linktrade-monitor' ); ?> →
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Render links tab
     */
    private function render_links_tab() {
        ?>
        <div class="linktrade-card">
            <div class="linktrade-table-header">
                <h3><?php esc_html_e( 'Link Overview', 'linktrade-monitor' ); ?></h3>
                <div class="linktrade-filters">
                    <input type="text" id="linktrade-search" class="linktrade-search"
                           placeholder="<?php esc_attr_e( 'Search links...', 'linktrade-monitor' ); ?>">
                    <select id="linktrade-filter-category" class="linktrade-select">
                        <option value=""><?php esc_html_e( 'All Categories', 'linktrade-monitor' ); ?></option>
                        <option value="exchange"><?php esc_html_e( 'Link Exchange', 'linktrade-monitor' ); ?></option>
                        <option value="paid"><?php esc_html_e( 'Paid Links', 'linktrade-monitor' ); ?></option>
                        <option value="free"><?php esc_html_e( 'Free', 'linktrade-monitor' ); ?></option>
                    </select>
                    <select id="linktrade-filter-status" class="linktrade-select">
                        <option value=""><?php esc_html_e( 'All Status', 'linktrade-monitor' ); ?></option>
                        <option value="online"><?php esc_html_e( 'Online', 'linktrade-monitor' ); ?></option>
                        <option value="warning"><?php esc_html_e( 'Warning', 'linktrade-monitor' ); ?></option>
                        <option value="offline"><?php esc_html_e( 'Offline', 'linktrade-monitor' ); ?></option>
                        <option value="unchecked"><?php esc_html_e( 'Unchecked', 'linktrade-monitor' ); ?></option>
                    </select>
                </div>
            </div>
            <?php $this->render_links_table(); ?>
        </div>
        <?php
    }

    /**
     * Render links table
     *
     * @param int $limit Maximum number of links to show.
     */
    private function render_links_table( $limit = 0 ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'linktrade_links';

        if ( $limit > 0 ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Display query, caching not beneficial.
            $links = $wpdb->get_results(
                $wpdb->prepare(
                    'SELECT * FROM `' . esc_sql( $table_name ) . '` ORDER BY created_at DESC LIMIT %d',
                    $limit
                )
            );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Display query, caching not beneficial.
            $links = $wpdb->get_results( 'SELECT * FROM `' . esc_sql( $table_name ) . '` ORDER BY created_at DESC' );
        }
        ?>
        <table class="linktrade-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Partner', 'linktrade-monitor' ); ?></th>
                    <th><?php esc_html_e( 'Category', 'linktrade-monitor' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'linktrade-monitor' ); ?></th>
                    <th><?php esc_html_e( 'Health', 'linktrade-monitor' ); ?></th>
                    <th><?php esc_html_e( 'Start / Expiration', 'linktrade-monitor' ); ?></th>
                    <th><?php esc_html_e( 'DR', 'linktrade-monitor' ); ?></th>
                    <th><?php esc_html_e( 'Last Check', 'linktrade-monitor' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'linktrade-monitor' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $links ) ) : ?>
                    <tr>
                        <td colspan="8" class="linktrade-empty">
                            <?php esc_html_e( 'No links found. Add your first link!', 'linktrade-monitor' ); ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $links as $link ) : ?>
                        <?php
                        $health_score = $this->calculate_link_health_score( $link );
                        $health_class = $this->get_health_score_class( $health_score );
                        ?>
                        <tr data-id="<?php echo esc_attr( $link->id ); ?>">
                            <td>
                                <strong><?php echo esc_html( $link->partner_name ); ?></strong>
                                <br><small><?php echo esc_html( $this->truncate_url( $link->partner_url ) ); ?></small>
                            </td>
                            <td>
                                <span class="category-tag <?php echo esc_attr( $link->category ); ?>">
                                    <?php echo esc_html( $this->get_category_label( $link->category ) ); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo wp_kses_post( $this->render_status_badge( $link ) ); ?>
                            </td>
                            <td>
                                <div class="health-score <?php echo esc_attr( $health_class ); ?>" title="<?php esc_attr_e( 'Link Health Score', 'linktrade-monitor' ); ?>">
                                    <span class="health-value"><?php echo esc_html( $health_score ); ?></span>
                                </div>
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
                                    /* translators: %s: human readable time difference */
                                    printf( esc_html__( '%s ago', 'linktrade-monitor' ), esc_html( human_time_diff( strtotime( $link->last_check ), current_time( 'timestamp' ) ) ) );
                                } else {
                                    esc_html_e( 'Never', 'linktrade-monitor' );
                                }
                                ?>
                            </td>
                            <td class="actions">
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
                <span class="legend-item"><span class="fairness-dot unfair"></span> <?php esc_html_e( 'Unfair (partner offline)', 'linktrade-monitor' ); ?></span>
                <span class="legend-item"><span class="fairness-dot warning"></span> <?php esc_html_e( 'Warning (nofollow etc.)', 'linktrade-monitor' ); ?></span>
            </div>

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
                            $fairness_class = 'fair';
                            if ( $link->fairness_score < 50 ) {
                                $fairness_class = 'unfair';
                            } elseif ( $link->fairness_score < 100 ) {
                                $fairness_class = 'warning';
                            }
                            // DR comparison (per link).
                            $partner_dr = (int) $link->domain_rating;
                            $my_dr = isset( $link->my_domain_rating ) ? (int) $link->my_domain_rating : 0;
                            $dr_diff = $partner_dr - $my_dr;
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
                                        <span class="dr-vs">vs</span>
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
                                        <span class="dr-vs">vs</span>
                                        <span class="dr-badge dr-mine">?</span>
                                    <?php elseif ( $my_dr > 0 ) : ?>
                                        <span class="dr-badge">?</span>
                                        <span class="dr-vs">vs</span>
                                        <span class="dr-badge dr-mine"><?php echo esc_html( $my_dr ); ?></span>
                                    <?php else : ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fairness-score fairness-<?php echo esc_attr( $fairness_class ); ?>">
                                        <span class="score-value"><?php echo esc_html( $link->fairness_score ); ?>%</span>
                                        <div class="score-bar">
                                            <div class="score-fill" style="width: <?php echo esc_attr( $link->fairness_score ); ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render add tab
     */
    private function render_add_tab() {
        // Removed limit check for WordPress.org compliance
        ?>
        <div class="linktrade-card lt-compact-form">
            <h3><?php esc_html_e( 'Add New Link', 'linktrade-monitor' ); ?></h3>
            <form id="linktrade-add-form" class="linktrade-form">

                <!-- Row 1: Partner Info (3 columns) -->
                <div class="lt-form-grid lt-grid-3">
                    <div class="form-row">
                        <label for="partner_name"><?php esc_html_e( 'Partner Name', 'linktrade-monitor' ); ?> *</label>
                        <input type="text" id="partner_name" name="partner_name" required placeholder="<?php esc_attr_e( 'e.g. Partner Name', 'linktrade-monitor' ); ?>">
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
                        <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>">
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
                            <span class="lt-column-icon">←</span>
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
                            <label for="anchor_text"><?php esc_html_e( 'Anchor Text', 'linktrade-monitor' ); ?></label>
                            <input type="text" id="anchor_text" name="anchor_text" placeholder="Your Brand">
                        </div>
                        <div class="form-row lt-dr-field">
                            <label for="domain_rating"><?php esc_html_e( 'Partner DR', 'linktrade-monitor' ); ?></label>
                            <input type="number" id="domain_rating" name="domain_rating" min="0" max="100" placeholder="0-100">
                        </div>
                    </div>

                    <!-- RIGHT: Outgoing Link (what you GIVE) - only for exchanges -->
                    <div class="lt-link-column lt-outgoing exchange-fields">
                        <div class="lt-column-header">
                            <span class="lt-column-icon">→</span>
                            <div class="lt-column-title">
                                <strong><?php esc_html_e( 'Outgoing Link', 'linktrade-monitor' ); ?></strong>
                                <small><?php esc_html_e( 'Link you give back', 'linktrade-monitor' ); ?></small>
                            </div>
                        </div>
                        <div class="form-row">
                            <label for="backlink_url"><?php esc_html_e( 'Your Page URL', 'linktrade-monitor' ); ?></label>
                            <input type="url" id="backlink_url" name="backlink_url" placeholder="https://your-site.com/partners">
                        </div>
                        <div class="form-row">
                            <label for="backlink_target"><?php esc_html_e( 'Partner Target URL', 'linktrade-monitor' ); ?></label>
                            <input type="url" id="backlink_target" name="backlink_target" placeholder="https://partner-site.com/">
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

            $notification_email = isset( $_POST['notification_email'] ) ? sanitize_email( wp_unslash( $_POST['notification_email'] ) ) : '';
            $email_notifications = isset( $_POST['email_notifications'] ) ? 1 : 0;
            $reminder_days = isset( $_POST['reminder_days'] ) ? absint( $_POST['reminder_days'] ) : 14;
            $plugin_language = isset( $_POST['plugin_language'] ) ? sanitize_key( wp_unslash( $_POST['plugin_language'] ) ) : 'en';

            update_option( 'linktrade_notification_email', $notification_email );
            update_option( 'linktrade_email_notifications', $email_notifications );
            update_option( 'linktrade_reminder_days', $reminder_days );
            update_option( 'linktrade_language', $plugin_language );

            echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'linktrade-monitor' ) . '</p></div>';
        }

        $notification_email  = get_option( 'linktrade_notification_email', get_option( 'admin_email' ) );
        $email_notifications = get_option( 'linktrade_email_notifications', true );
        $reminder_days       = get_option( 'linktrade_reminder_days', 14 );
        $plugin_language     = get_option( 'linktrade_language', 'en' );
        ?>
        <div class="linktrade-card">
            <h3><?php esc_html_e( 'Settings', 'linktrade-monitor' ); ?></h3>

            <form method="post">
                <?php wp_nonce_field( 'linktrade_settings', 'linktrade_settings_nonce' ); ?>

                <div class="form-section">
                    <h4><?php esc_html_e( 'Language', 'linktrade-monitor' ); ?></h4>

                    <div class="form-row">
                        <label for="plugin_language"><?php esc_html_e( 'Plugin Language', 'linktrade-monitor' ); ?></label>
                        <select id="plugin_language" name="plugin_language">
                            <option value="en" <?php selected( $plugin_language, 'en' ); ?>>English</option>
                            <option value="de" <?php selected( $plugin_language, 'de' ); ?>>Deutsch</option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Choose the language for this plugin interface.', 'linktrade-monitor' ); ?></p>
                    </div>
                </div>

                <div class="form-section">
                    <h4><?php esc_html_e( 'Notifications', 'linktrade-monitor' ); ?></h4>

                    <div class="form-row">
                        <label for="notification_email"><?php esc_html_e( 'Notification Email', 'linktrade-monitor' ); ?></label>
                        <input type="email" id="notification_email" name="notification_email" value="<?php echo esc_attr( $notification_email ); ?>">
                    </div>

                    <div class="form-row checkbox-row">
                        <label>
                            <input type="checkbox" name="email_notifications" value="1" <?php checked( $email_notifications ); ?>>
                            <?php esc_html_e( 'Send email notifications for expiring links', 'linktrade-monitor' ); ?>
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
                </div>
            </form>

            <div class="linktrade-pro-hint" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 13px;">
                <?php
                printf(
                    /* translators: %1$s: opening link tag, %2$s: closing link tag */
                    esc_html__( 'Need more features? Check out %1$sLinktrade Monitor Pro%2$s for project management, ROI tracking, webhooks, and 10+ advanced features.', 'linktrade-monitor' ),
                    '<a href="https://www.3task.de/linktrade-monitor-pro/" target="_blank" style="color: #0073aa;">',
                    '</a>'
                );
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render import/export tab
     */
    private function render_import_export_tab() {
        ?>
        <div class="linktrade-import-export">
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
                            <?php esc_html_e( 'Import CSV', 'linktrade-monitor' ); ?>
                        </button>
                    </div>
                </form>
                <div id="linktrade-import-result" style="margin-top: 15px;"></div>
            </div>

            <!-- Field Documentation -->
            <div class="linktrade-card">
                <h3><?php esc_html_e( 'CSV Field Reference', 'linktrade-monitor' ); ?></h3>
                <p class="description"><?php esc_html_e( 'Your CSV file must include a header row with these exact column names. Required fields are marked with *.', 'linktrade-monitor' ); ?></p>

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
        <div id="linktrade-edit-modal" class="linktrade-modal" style="display: none;">
            <div class="linktrade-modal-content">
                <div class="linktrade-modal-header">
                    <h2><?php esc_html_e( 'Edit Link', 'linktrade-monitor' ); ?></h2>
                    <button type="button" class="linktrade-modal-close">&times;</button>
                </div>
                <div class="linktrade-modal-body">
                    <form id="linktrade-edit-form" class="linktrade-form">
                        <input type="hidden" id="edit_id" name="id">
                        <!-- Form fields will be populated via JS -->
                    </form>
                </div>
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
        $url = preg_replace( '#^https?://#', '', $url );
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
            if ( $link->is_nofollow ) {
                $label = 'nofollow';
            } elseif ( $link->is_noindex ) {
                $label = 'noindex';
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
            $now               = current_time( 'timestamp' );
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
            'partner_name'    => isset( $_POST['partner_name'] ) ? sanitize_text_field( wp_unslash( $_POST['partner_name'] ) ) : '',
            'partner_contact' => isset( $_POST['partner_contact'] ) ? sanitize_email( wp_unslash( $_POST['partner_contact'] ) ) : '',
            'category'        => isset( $_POST['category'] ) ? sanitize_key( wp_unslash( $_POST['category'] ) ) : 'exchange',
            'partner_url'     => isset( $_POST['partner_url'] ) ? esc_url_raw( wp_unslash( $_POST['partner_url'] ) ) : '',
            'target_url'      => isset( $_POST['target_url'] ) ? esc_url_raw( wp_unslash( $_POST['target_url'] ) ) : '',
            'anchor_text'     => isset( $_POST['anchor_text'] ) ? sanitize_text_field( wp_unslash( $_POST['anchor_text'] ) ) : '',
            'backlink_url'    => isset( $_POST['backlink_url'] ) ? esc_url_raw( wp_unslash( $_POST['backlink_url'] ) ) : '',
            'backlink_target' => isset( $_POST['backlink_target'] ) ? esc_url_raw( wp_unslash( $_POST['backlink_target'] ) ) : '',
            'backlink_anchor' => isset( $_POST['backlink_anchor'] ) ? sanitize_text_field( wp_unslash( $_POST['backlink_anchor'] ) ) : '',
            'domain_rating'    => isset( $_POST['domain_rating'] ) ? absint( $_POST['domain_rating'] ) : 0,
            'my_domain_rating' => isset( $_POST['my_domain_rating'] ) ? absint( $_POST['my_domain_rating'] ) : 0,
            'notes'            => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
            'start_date'      => isset( $_POST['start_date'] ) && ! empty( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : null,
            'end_date'        => isset( $_POST['end_date'] ) && ! empty( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : null,
        );

        // Clear cache.
        wp_cache_delete( 'linktrade_link_count' );
        wp_cache_delete( 'linktrade_quick_stats' );
        wp_cache_delete( 'linktrade_full_stats' );

        $is_new_link = ( 0 === $id );

        if ( $id > 0 ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Update operation on custom table.
            $wpdb->update( $table_name, $data, array( 'id' => $id ) );
            $message = __( 'Link updated successfully.', 'linktrade-monitor' );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Insert operation on custom table.
            $wpdb->insert( $table_name, $data );
            $id      = $wpdb->insert_id;
            $message = __( 'Link saved successfully.', 'linktrade-monitor' );
        }

        // Immediate check for new links.
        $check_result = null;
        if ( $is_new_link && ! empty( $data['partner_url'] ) && ! empty( $data['target_url'] ) ) {
            $check_result = $this->check_single_link( $id, $data['partner_url'], $data['target_url'] );
            if ( $check_result ) {
                $message .= ' ' . __( 'Link checked.', 'linktrade-monitor' );
            }

            // For exchanges, also check the backlink (your link to partner).
            if ( 'exchange' === $data['category'] && ! empty( $data['backlink_url'] ) && ! empty( $data['backlink_target'] ) ) {
                $backlink_result = $this->check_backlink( $id, $data['backlink_url'], $data['backlink_target'] );
                if ( $backlink_result ) {
                    $message .= ' ' . __( 'Backlink checked.', 'linktrade-monitor' );
                }
            }
        }

        // Recalculate fairness for exchange links when DR values change.
        if ( 'exchange' === $data['category'] ) {
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
     * Check a single link immediately
     *
     * @param int    $link_id    The link ID.
     * @param string $page_url   The page URL to check.
     * @param string $target_url The target URL to find.
     * @return array|null Check result or null on failure.
     */
    private function check_single_link( $link_id, $page_url, $target_url ) {
        require_once LINKTRADE_PLUGIN_DIR . 'includes/checker/class-link-checker.php';

        $checker = new Linktrade_Link_Checker();
        $result  = $checker->check( $page_url, $target_url );

        if ( ! $result ) {
            return null;
        }

        // Update link in database with check result.
        global $wpdb;
        $table_name = $wpdb->prefix . 'linktrade_links';

        $update_data = array(
            'status'       => $result['status'],
            'last_check'   => current_time( 'mysql' ),
            'http_code'    => $result['http_code'],
            'is_nofollow'  => $result['is_nofollow'] ? 1 : 0,
            'is_noindex'   => $result['is_noindex'] ? 1 : 0,
            'is_sponsored' => $result['is_sponsored'] ? 1 : 0,
        );

        // Update anchor text if found.
        if ( ! empty( $result['anchor_text'] ) ) {
            $update_data['anchor_text'] = $result['anchor_text'];
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Update operation on custom table.
        $wpdb->update( $table_name, $update_data, array( 'id' => $link_id ) );

        return $result;
    }

    /**
     * Check the backlink (your link to partner) immediately.
     *
     * @param int    $link_id     The link ID.
     * @param string $page_url    Your page URL where you placed the link.
     * @param string $target_url  Partner's URL you link to.
     * @return array|null Check result or null on failure.
     */
    private function check_backlink( $link_id, $page_url, $target_url ) {
        require_once LINKTRADE_PLUGIN_DIR . 'includes/checker/class-link-checker.php';

        $checker = new Linktrade_Link_Checker();
        $result  = $checker->check( $page_url, $target_url );

        if ( ! $result ) {
            return null;
        }

        // Update backlink fields in database.
        global $wpdb;
        $table_name = $wpdb->prefix . 'linktrade_links';

        $update_data = array(
            'backlink_status'      => $result['status'],
            'backlink_last_check'  => current_time( 'mysql' ),
            'backlink_http_code'   => $result['http_code'],
            'backlink_is_nofollow' => $result['is_nofollow'] ? 1 : 0,
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Update operation on custom table.
        $wpdb->update( $table_name, $update_data, array( 'id' => $link_id ) );

        return $result;
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
                'SELECT status, is_nofollow, backlink_status, backlink_is_nofollow FROM `' . esc_sql( $table_name ) . '` WHERE id = %d',
                $link_id
            )
        );

        if ( ! $link ) {
            return;
        }

        // Calculate fairness with status and DR values.
        $fairness = $this->calculate_fairness_score(
            $link->status,
            $link->backlink_status,
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
     * Calculate fairness score.
     *
     * @param string $my_status      My link status (their link to me).
     * @param string $their_status   Their link status (my link to them).
     * @param bool   $my_nofollow    My incoming link nofollow.
     * @param bool   $their_nofollow My outgoing link nofollow.
     * @param int    $partner_dr     Partner's Domain Rating.
     * @param int    $my_dr          My Domain Rating.
     * @return int Fairness score (0-100).
     */
    private function calculate_fairness_score( $my_status, $their_status, $my_nofollow, $their_nofollow, $partner_dr = 0, $my_dr = 0 ) {
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
        $wpdb->delete( $table_name, array( 'id' => $id ), array( '%d' ) );

        // Clear cache.
        wp_cache_delete( 'linktrade_link_count' );
        wp_cache_delete( 'linktrade_quick_stats' );
        wp_cache_delete( 'linktrade_full_stats' );

        wp_send_json_success( array( 'message' => __( 'Link deleted.', 'linktrade-monitor' ) ) );
    }

    /**
     * AJAX: Get links
     */
    public function ajax_get_links() {
        check_ajax_referer( 'linktrade_nonce', 'nonce' );

        global $wpdb;
        $table_name = $wpdb->prefix . 'linktrade_links';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- AJAX fetch requires fresh data.
        $links = $wpdb->get_results( 'SELECT * FROM `' . esc_sql( $table_name ) . '` ORDER BY created_at DESC' );

        wp_send_json_success( array( 'links' => $links ) );
    }

    /**
     * AJAX: Get single link
     */
    public function ajax_get_link() {
        check_ajax_referer( 'linktrade_nonce', 'nonce' );

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
        $score = 0;

        // Status (40%)
        if ( 'online' === $link->status ) {
            $score += 40;
        } elseif ( 'warning' === $link->status ) {
            $score += 20;
        }
        // offline/unchecked = 0

        // Attributes (20%)
        $is_nofollow = isset( $link->is_nofollow ) ? (bool) $link->is_nofollow : false;
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
            $days = ( time() - strtotime( $link->start_date ) ) / DAY_IN_SECONDS;
            $days = max( 0, $days );
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
                $value = isset( $link[ $col ] ) ? $link[ $col ] : '';
                // Escape quotes and wrap in quotes if contains comma or quote.
                $value = str_replace( '"', '""', $value );
                if ( strpos( $value, ',' ) !== false || strpos( $value, '"' ) !== false || strpos( $value, "\n" ) !== false ) {
                    $value = '"' . $value . '"';
                }
                $row[] = $value;
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

        // Read and parse CSV file.
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- tmp_name is a server-generated path, not user input.
        $tmp_file = isset( $_FILES['import_file']['tmp_name'] ) ? $_FILES['import_file']['tmp_name'] : '';
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading uploaded temp file.
        $csv_content = file_get_contents( $tmp_file );
        if ( false === $csv_content ) {
            wp_send_json_error( array( 'message' => __( 'Could not read file.', 'linktrade-monitor' ) ) );
        }

        $lines = preg_split( '/\r\n|\r|\n/', $csv_content );
        if ( count( $lines ) < 2 ) {
            wp_send_json_error( array( 'message' => __( 'CSV file must contain a header row and at least one data row.', 'linktrade-monitor' ) ) );
        }

        // Parse header row.
        $header = str_getcsv( array_shift( $lines ) );
        $header = array_map( 'trim', $header );
        $header = array_map( 'strtolower', $header );

        // Check required fields.
        $required = array( 'partner_name', 'partner_url', 'target_url' );
        foreach ( $required as $field ) {
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

        // Get existing partner_urls for duplicate check.
        $existing_urls = array();
        if ( $skip_duplicates ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Need fresh data for import.
            $existing = $wpdb->get_col( 'SELECT partner_url FROM `' . esc_sql( $table_name ) . '`' );
            $existing_urls = array_map( 'strtolower', $existing );
        }

        // Allowed fields for import.
        $allowed_fields = array(
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
        );

        $imported = 0;
        $skipped  = 0;
        $errors   = 0;

        foreach ( $lines as $line ) {
            if ( empty( trim( $line ) ) ) {
                continue;
            }

            $values = str_getcsv( $line );
            if ( count( $values ) !== count( $header ) ) {
                $errors++;
                continue;
            }

            $row = array_combine( $header, $values );

            // Check duplicates.
            if ( $skip_duplicates && in_array( strtolower( $row['partner_url'] ), $existing_urls, true ) ) {
                $skipped++;
                continue;
            }

            // Build insert data.
            $data = array();
            foreach ( $allowed_fields as $field ) {
                if ( isset( $row[ $field ] ) ) {
                    $value = trim( $row[ $field ] );

                    // Sanitize based on field type.
                    if ( in_array( $field, array( 'partner_url', 'target_url', 'backlink_url', 'backlink_target' ), true ) ) {
                        $data[ $field ] = esc_url_raw( $value );
                    } elseif ( 'partner_contact' === $field ) {
                        $data[ $field ] = sanitize_email( $value );
                    } elseif ( in_array( $field, array( 'domain_rating', 'my_domain_rating' ), true ) ) {
                        $data[ $field ] = absint( $value );
                    } elseif ( 'category' === $field ) {
                        $data[ $field ] = in_array( $value, array( 'exchange', 'paid', 'free' ), true ) ? $value : 'exchange';
                    } elseif ( in_array( $field, array( 'start_date', 'end_date' ), true ) ) {
                        $data[ $field ] = ! empty( $value ) ? sanitize_text_field( $value ) : null;
                    } elseif ( 'notes' === $field ) {
                        $data[ $field ] = sanitize_textarea_field( $value );
                    } else {
                        $data[ $field ] = sanitize_text_field( $value );
                    }
                }
            }

            // Validate required fields have values.
            if ( empty( $data['partner_name'] ) || empty( $data['partner_url'] ) || empty( $data['target_url'] ) ) {
                $errors++;
                continue;
            }

            // Set default category if not provided.
            if ( empty( $data['category'] ) ) {
                $data['category'] = 'exchange';
            }

            // Set default status.
            $data['status'] = 'unchecked';

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Insert operation.
            $result = $wpdb->insert( $table_name, $data );

            if ( $result ) {
                $imported++;
                $existing_urls[] = strtolower( $data['partner_url'] );
            } else {
                $errors++;
            }
        }

        // Clear cache.
        wp_cache_delete( 'linktrade_link_count' );
        wp_cache_delete( 'linktrade_quick_stats' );
        wp_cache_delete( 'linktrade_full_stats' );

        $message = sprintf(
            /* translators: %1$d: number of imported links, %2$d: number of skipped duplicates, %3$d: number of errors */
            __( 'Import complete: %1$d imported, %2$d skipped (duplicates), %3$d errors.', 'linktrade-monitor' ),
            $imported,
            $skipped,
            $errors
        );

        wp_send_json_success(
            array(
                'message'  => $message,
                'imported' => $imported,
                'skipped'  => $skipped,
                'errors'   => $errors,
            )
        );
    }
}
} // End class_exists check
