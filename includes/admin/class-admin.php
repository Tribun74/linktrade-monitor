<?php
/**
 * Admin-Bereich - Erweiterte Version
 *
 * @package Linktrade_Monitor
 * @since 1.0.0
 * @updated 1.1.0 - Partner-Bewertung, Gegenseitigkeit, Kontakt-Historie, Verfalls-Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class Linktrade_Admin {

    /**
     * Admin-Menü hinzufügen
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Linktrade Monitor', 'linktrade-monitor'),
            __('Linktrade', 'linktrade-monitor'),
            'manage_options',
            'linktrade-monitor',
            [$this, 'render_admin_page'],
            'dashicons-admin-links',
            30
        );
    }

    /**
     * Assets laden
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_linktrade-monitor') {
            return;
        }

        wp_enqueue_style(
            'linktrade-admin',
            LINKTRADE_PLUGIN_URL . 'assets/css/admin.css',
            [],
            LINKTRADE_VERSION
        );

        wp_enqueue_script(
            'linktrade-admin',
            LINKTRADE_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            LINKTRADE_VERSION,
            true
        );

        wp_localize_script('linktrade-admin', 'linktrade', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('linktrade_nonce'),
            'strings' => [
                'confirm_delete' => __('Diesen Link wirklich löschen?', 'linktrade-monitor'),
                'confirm_delete_contact' => __('Diesen Kontakt-Eintrag löschen?', 'linktrade-monitor'),
                'saving' => __('Speichere...', 'linktrade-monitor'),
                'saved' => __('Gespeichert!', 'linktrade-monitor'),
                'error' => __('Fehler aufgetreten', 'linktrade-monitor'),
                'checking' => __('Prüfe...', 'linktrade-monitor'),
                'checking_both' => __('Prüfe beide Links...', 'linktrade-monitor'),
            ]
        ]);
    }

    /**
     * Admin-Seite rendern
     */
    public function render_admin_page() {
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'dashboard';
        $tabs = $this->get_tabs();
        $stats = $this->get_stats();
        $alerts = $this->get_alerts();
        ?>
        <div class="wrap linktrade-wrap">
            <div class="linktrade-header">
                <div class="linktrade-logo">
                    <span class="linktrade-logo-icon">LT</span>
                    <h1><?php _e('Linktrade Monitor', 'linktrade-monitor'); ?></h1>
                    <span class="linktrade-version">v<?php echo LINKTRADE_VERSION; ?></span>
                </div>
                <div class="linktrade-support-hint">
                    <div class="support-hint-toggle" title="<?php _e('Unterstützen', 'linktrade-monitor'); ?>">
                        <span class="dashicons dashicons-heart"></span>
                    </div>
                    <div class="support-hint-content">
                        <p><strong>Hinter diesem Plugin stecken viele Stunden Tüftelei bis es perfekt war.</strong></p>
                        <p>Mein Dank? Ein Backlink zu <a href="https://frank-stemmler.de" target="_blank">frank-stemmler.de</a> – ich freue mich über jede Unterstützung!</p>
                        <div class="backlink-code">
                            <label><?php _e('Code zum Kopieren:', 'linktrade-monitor'); ?></label>
                            <input type="text" readonly value='<a href="https://frank-stemmler.de" target="_blank">frank-stemmler.de</a>' onclick="this.select();" />
                            <button type="button" class="button button-small copy-backlink-code" title="<?php _e('Kopieren', 'linktrade-monitor'); ?>">
                                <span class="dashicons dashicons-clipboard"></span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="linktrade-header-actions">
                    <button type="button" class="button" id="linktrade-export-csv">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('CSV Export', 'linktrade-monitor'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="linktrade-add-link">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <?php _e('Neuer Link', 'linktrade-monitor'); ?>
                    </button>
                </div>
            </div>

            <nav class="linktrade-tabs">
                <?php foreach ($tabs as $tab_id => $tab_data): ?>
                    <a href="<?php echo esc_url(add_query_arg('tab', $tab_id, admin_url('admin.php?page=linktrade-monitor'))); ?>"
                       class="linktrade-tab <?php echo $current_tab === $tab_id ? 'active' : ''; ?>">
                        <span class="dashicons <?php echo esc_attr($tab_data['icon']); ?>"></span>
                        <?php echo esc_html($tab_data['label']); ?>
                        <?php if (!empty($tab_data['badge'])): ?>
                            <span class="tab-badge <?php echo esc_attr($tab_data['badge_class'] ?? ''); ?>"><?php echo $tab_data['badge']; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="linktrade-content">
                <?php
                switch ($current_tab) {
                    case 'links':
                        $this->render_links_tab();
                        break;
                    case 'alerts':
                        $this->render_alerts_tab($alerts);
                        break;
                    case 'add':
                        $this->render_add_tab();
                        break;
                    case 'fairness':
                        $this->render_fairness_tab();
                        break;
                    case 'anchors':
                        $this->render_anchors_tab();
                        break;
                    case 'expiring':
                        $this->render_expiring_tab();
                        break;
                    case 'contacts':
                        $this->render_contacts_tab();
                        break;
                    case 'settings':
                        $this->render_settings_tab();
                        break;
                    default:
                        $this->render_dashboard_tab($stats);
                }
                ?>
            </div>
        </div>

        <?php $this->render_modals(); ?>
        <?php
    }

    /**
     * Tabs definieren
     */
    private function get_tabs() {
        $stats = $this->get_quick_stats();
        $alert_count = count($this->get_alerts());

        return [
            'dashboard' => [
                'label' => __('Dashboard', 'linktrade-monitor'),
                'icon' => 'dashicons-dashboard',
            ],
            'links' => [
                'label' => __('Alle Links', 'linktrade-monitor'),
                'icon' => 'dashicons-admin-links',
                'badge' => $stats['total'],
            ],
            'alerts' => [
                'label' => __('Alerts', 'linktrade-monitor'),
                'icon' => 'dashicons-warning',
                'badge' => $alert_count > 0 ? $alert_count : '',
                'badge_class' => $alert_count > 0 ? 'badge-danger' : '',
            ],
            'fairness' => [
                'label' => __('Fairness', 'linktrade-monitor'),
                'icon' => 'dashicons-image-flip-horizontal',
                'badge' => $stats['unfair'] > 0 ? $stats['unfair'] : '',
                'badge_class' => $stats['unfair'] > 0 ? 'badge-warning' : '',
            ],
            'anchors' => [
                'label' => __('Anchors', 'linktrade-monitor'),
                'icon' => 'dashicons-tag',
            ],
            'expiring' => [
                'label' => __('Ablaufend', 'linktrade-monitor'),
                'icon' => 'dashicons-calendar-alt',
                'badge' => $stats['expiring'] > 0 ? $stats['expiring'] : '',
                'badge_class' => $stats['expiring'] > 0 ? 'badge-danger' : '',
            ],
            'contacts' => [
                'label' => __('Kontakte', 'linktrade-monitor'),
                'icon' => 'dashicons-email-alt',
            ],
            'add' => [
                'label' => __('+ Neu', 'linktrade-monitor'),
                'icon' => 'dashicons-plus',
            ],
            'settings' => [
                'label' => __('Einstellungen', 'linktrade-monitor'),
                'icon' => 'dashicons-admin-generic',
            ],
        ];
    }

    /**
     * Schnelle Statistiken für Tabs
     */
    private function get_quick_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'linktrade_links';

        return [
            'total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table"),
            'unfair' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE category = 'tausch' AND fairness_score < 100 AND fairness_score > 0"),
            'expiring' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE end_date IS NOT NULL AND end_date <= %s AND end_date >= CURDATE()",
                wp_date('Y-m-d', strtotime('+30 days'))
            )),
        ];
    }

    /**
     * Vollständige Statistiken
     */
    private function get_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'linktrade_links';

        $stats = [
            'total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table"),
            'online' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'online'"),
            'warning' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'warning'"),
            'offline' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'offline'"),
            'unchecked' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'unchecked'"),
            'expiring' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE end_date IS NOT NULL AND end_date <= %s AND end_date >= CURDATE()",
                wp_date('Y-m-d', strtotime('+30 days'))
            )),
            'tausch' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE category = 'tausch'"),
            'kauf' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE category = 'kauf'"),
            'kostenlos' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE category = 'kostenlos'"),
            // Neue Stats
            'avg_dr' => (float) $wpdb->get_var("SELECT AVG(domain_rating) FROM $table WHERE domain_rating > 0"),
            'avg_quality' => (float) $wpdb->get_var("SELECT AVG(quality_score) FROM $table WHERE quality_score > 0"),
            'total_investment' => (float) $wpdb->get_var("SELECT SUM(price) FROM $table WHERE category = 'kauf'"),
            'fairness_issues' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE category = 'tausch' AND fairness_score < 100 AND fairness_score > 0"),
        ];

        return $stats;
    }

    /**
     * Warnungen/Alerts sammeln
     */
    private function get_alerts() {
        global $wpdb;
        $table = $wpdb->prefix . 'linktrade_links';
        $alerts = [];

        // Partner hat Link entfernt (Fairness-Problem)
        $unfair = $wpdb->get_results(
            "SELECT * FROM $table WHERE category = 'tausch' AND status = 'online' AND backlink_status = 'offline'"
        );
        foreach ($unfair as $link) {
            $alerts[] = [
                'type' => 'fairness',
                'severity' => 'danger',
                'message' => sprintf(__('%s hat seinen Link entfernt, deiner ist aber noch online!', 'linktrade-monitor'), $link->partner_name),
                'link_id' => $link->id,
            ];
        }

        // Links laufen bald ab
        $expiring = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE end_date IS NOT NULL AND end_date <= %s AND end_date >= CURDATE() AND reminder_sent = 0",
            wp_date('Y-m-d', strtotime('+14 days'))
        ));
        foreach ($expiring as $link) {
            $days = (strtotime($link->end_date) - time()) / 86400;
            $alerts[] = [
                'type' => 'expiring',
                'severity' => $days <= 7 ? 'danger' : 'warning',
                'message' => sprintf(__('Link von %s läuft in %d Tagen ab', 'linktrade-monitor'), $link->partner_name, ceil($days)),
                'link_id' => $link->id,
            ];
        }

        // Lange nicht kontaktierte Partner
        $no_contact = $wpdb->get_results(
            "SELECT * FROM $table WHERE category = 'tausch' AND (last_contact_date IS NULL OR last_contact_date < DATE_SUB(CURDATE(), INTERVAL 6 MONTH))"
        );
        foreach ($no_contact as $link) {
            $alerts[] = [
                'type' => 'contact',
                'severity' => 'info',
                'message' => sprintf(__('%s seit über 6 Monaten nicht kontaktiert', 'linktrade-monitor'), $link->partner_name),
                'link_id' => $link->id,
            ];
        }

        return $alerts;
    }

    /**
     * Dashboard-Tab rendern
     */
    private function render_dashboard_tab($stats) {
        $problems = $stats['warning'] + $stats['offline'];
        ?>
        <!-- Neueste Links zuerst -->
        <div class="linktrade-card">
            <h3><?php _e('Neueste Links', 'linktrade-monitor'); ?></h3>
            <?php $this->render_links_table(5); ?>
        </div>

        <!-- Stats Grid -->
        <div class="linktrade-stats-grid">
            <div class="linktrade-stat-card">
                <div class="stat-label"><?php _e('Gesamte Links', 'linktrade-monitor'); ?></div>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
            </div>
            <div class="linktrade-stat-card success">
                <div class="stat-label"><?php _e('Online', 'linktrade-monitor'); ?></div>
                <div class="stat-value"><?php echo $stats['online']; ?></div>
                <?php if ($stats['total'] > 0): ?>
                    <div class="stat-change"><?php echo round(($stats['online'] / $stats['total']) * 100, 1); ?>% aktiv</div>
                <?php endif; ?>
            </div>
            <div class="linktrade-stat-card warning">
                <div class="stat-label"><?php _e('Fairness-Probleme', 'linktrade-monitor'); ?></div>
                <div class="stat-value"><?php echo $stats['fairness_issues']; ?></div>
                <div class="stat-change"><?php _e('Partner ohne Gegenlink', 'linktrade-monitor'); ?></div>
            </div>
            <div class="linktrade-stat-card danger">
                <div class="stat-label"><?php _e('Offline / Probleme', 'linktrade-monitor'); ?></div>
                <div class="stat-value"><?php echo $problems; ?></div>
            </div>
        </div>

        <!-- Zweite Stats-Zeile -->
        <div class="linktrade-stats-grid stats-secondary">
            <div class="linktrade-stat-card">
                <div class="stat-label"><?php _e('Ø Domain Rating', 'linktrade-monitor'); ?></div>
                <div class="stat-value"><?php echo $stats['avg_dr'] ? round($stats['avg_dr'], 1) : '-'; ?></div>
            </div>
            <div class="linktrade-stat-card">
                <div class="stat-label"><?php _e('Ø Qualitäts-Score', 'linktrade-monitor'); ?></div>
                <div class="stat-value"><?php echo $stats['avg_quality'] ? round($stats['avg_quality']) : '-'; ?></div>
            </div>
            <div class="linktrade-stat-card">
                <div class="stat-label"><?php _e('Link-Investment', 'linktrade-monitor'); ?></div>
                <div class="stat-value"><?php echo $stats['total_investment'] ? number_format($stats['total_investment'], 0, ',', '.') . ' €' : '-'; ?></div>
            </div>
            <div class="linktrade-stat-card">
                <div class="stat-label"><?php _e('Ablaufend (30 Tage)', 'linktrade-monitor'); ?></div>
                <div class="stat-value"><?php echo $stats['expiring']; ?></div>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="linktrade-dashboard-grid">
            <div class="linktrade-card">
                <h3><?php _e('Nach Kategorie', 'linktrade-monitor'); ?></h3>
                <div class="linktrade-category-stats">
                    <div class="category-stat">
                        <span class="category-tag tausch"><?php _e('Linktausch', 'linktrade-monitor'); ?></span>
                        <span class="category-count"><?php echo $stats['tausch']; ?></span>
                    </div>
                    <div class="category-stat">
                        <span class="category-tag kauf"><?php _e('Linkkauf', 'linktrade-monitor'); ?></span>
                        <span class="category-count"><?php echo $stats['kauf']; ?></span>
                    </div>
                    <div class="category-stat">
                        <span class="category-tag kostenlos"><?php _e('Kostenlos', 'linktrade-monitor'); ?></span>
                        <span class="category-count"><?php echo $stats['kostenlos']; ?></span>
                    </div>
                </div>
            </div>

            <div class="linktrade-card">
                <h3><?php _e('Top Partner (nach DR)', 'linktrade-monitor'); ?></h3>
                <?php $this->render_top_partners(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Alerts-Tab rendern
     */
    private function render_alerts_tab($alerts) {
        ?>
        <div class="linktrade-card">
            <div class="linktrade-table-header">
                <h3><span class="dashicons dashicons-warning"></span> <?php _e('Handlungsbedarf', 'linktrade-monitor'); ?></h3>
                <p class="description"><?php _e('Hier siehst du alle Punkte die deine Aufmerksamkeit erfordern.', 'linktrade-monitor'); ?></p>
            </div>

            <?php if (empty($alerts)): ?>
                <div class="linktrade-empty-state">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <p><?php _e('Alles in Ordnung! Keine Warnungen vorhanden.', 'linktrade-monitor'); ?></p>
                </div>
            <?php else: ?>
                <div class="linktrade-alerts-list">
                    <?php foreach ($alerts as $alert): ?>
                        <div class="linktrade-alert alert-<?php echo esc_attr($alert['severity']); ?>">
                            <div class="alert-icon">
                                <span class="dashicons <?php echo $alert['severity'] === 'danger' ? 'dashicons-warning' : 'dashicons-info'; ?>"></span>
                            </div>
                            <div class="alert-content">
                                <span class="alert-message"><?php echo esc_html($alert['message']); ?></span>
                            </div>
                            <div class="alert-actions">
                                <a href="<?php echo esc_url(add_query_arg(['tab' => 'links', 'edit' => $alert['link_id']], admin_url('admin.php?page=linktrade-monitor'))); ?>" class="button button-small">
                                    <?php _e('Anzeigen', 'linktrade-monitor'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Top Partner nach DR
     */
    private function render_top_partners() {
        global $wpdb;
        $table = $wpdb->prefix . 'linktrade_links';

        $partners = $wpdb->get_results(
            "SELECT partner_name, domain_rating, quality_score, status
             FROM $table
             WHERE domain_rating > 0
             ORDER BY domain_rating DESC
             LIMIT 5"
        );

        if (empty($partners)) {
            echo '<p class="linktrade-empty-small">' . __('Noch keine DR-Werte eingetragen.', 'linktrade-monitor') . '</p>';
            return;
        }
        ?>
        <div class="linktrade-top-partners">
            <?php foreach ($partners as $partner): ?>
                <div class="top-partner">
                    <div class="partner-info">
                        <span class="partner-name"><?php echo esc_html($partner->partner_name); ?></span>
                        <span class="status-dot status-<?php echo esc_attr($partner->status); ?>"></span>
                    </div>
                    <div class="partner-metrics">
                        <span class="metric dr">DR <?php echo $partner->domain_rating; ?></span>
                        <?php if ($partner->quality_score): ?>
                            <span class="metric quality">Q<?php echo $partner->quality_score; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Fairness-Tab rendern
     */
    private function render_fairness_tab() {
        global $wpdb;
        $table = $wpdb->prefix . 'linktrade_links';

        $links = $wpdb->get_results(
            "SELECT * FROM $table WHERE category = 'tausch' ORDER BY fairness_score ASC, partner_name ASC"
        );
        ?>
        <div class="linktrade-card">
            <div class="linktrade-table-header">
                <h3><?php _e('Gegenseitigkeits-Tracker', 'linktrade-monitor'); ?></h3>
                <p class="description"><?php _e('Überwacht ob beide Seiten beim Linktausch fair bleiben.', 'linktrade-monitor'); ?></p>
            </div>

            <div class="fairness-legend">
                <span class="legend-item"><span class="fairness-dot fair"></span> <?php _e('Fair (beide online)', 'linktrade-monitor'); ?></span>
                <span class="legend-item"><span class="fairness-dot unfair"></span> <?php _e('Unfair (Partner offline)', 'linktrade-monitor'); ?></span>
                <span class="legend-item"><span class="fairness-dot warning"></span> <?php _e('Warnung (nofollow etc.)', 'linktrade-monitor'); ?></span>
            </div>

            <table class="linktrade-table">
                <thead>
                    <tr>
                        <th><?php _e('Partner', 'linktrade-monitor'); ?></th>
                        <th><?php _e('Sein Link zu dir', 'linktrade-monitor'); ?></th>
                        <th><?php _e('Dein Gegenlink', 'linktrade-monitor'); ?></th>
                        <th><?php _e('Fairness', 'linktrade-monitor'); ?></th>
                        <th><?php _e('Aktionen', 'linktrade-monitor'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($links)): ?>
                        <tr>
                            <td colspan="5" class="linktrade-empty"><?php _e('Keine Linktausch-Partner vorhanden.', 'linktrade-monitor'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($links as $link): ?>
                            <?php
                            $fairness_class = 'fair';
                            if ($link->fairness_score < 50) {
                                $fairness_class = 'unfair';
                            } elseif ($link->fairness_score < 100) {
                                $fairness_class = 'warning';
                            }
                            ?>
                            <tr data-id="<?php echo esc_attr($link->id); ?>" class="fairness-row fairness-<?php echo $fairness_class; ?>">
                                <td>
                                    <strong><?php echo esc_html($link->partner_name); ?></strong>
                                    <?php if ($link->domain_rating): ?>
                                        <span class="dr-badge">DR <?php echo $link->domain_rating; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $this->render_status_badge($link); ?>
                                    <br><small><?php echo esc_html($this->truncate_url($link->partner_url)); ?></small>
                                </td>
                                <td>
                                    <?php if ($link->backlink_url): ?>
                                        <?php echo $this->render_backlink_status_badge($link); ?>
                                        <br><small><?php echo esc_html($this->truncate_url($link->backlink_url)); ?></small>
                                    <?php else: ?>
                                        <span class="status unchecked"><?php _e('Nicht eingetragen', 'linktrade-monitor'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fairness-score fairness-<?php echo $fairness_class; ?>">
                                        <span class="score-value"><?php echo $link->fairness_score; ?>%</span>
                                        <div class="score-bar">
                                            <div class="score-fill" style="width: <?php echo $link->fairness_score; ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="actions">
                                    <button type="button" class="action-btn linktrade-check-both" data-id="<?php echo esc_attr($link->id); ?>" title="<?php esc_attr_e('Beide prüfen', 'linktrade-monitor'); ?>">
                                        <span class="dashicons dashicons-image-flip-horizontal"></span>
                                    </button>
                                    <button type="button" class="action-btn linktrade-edit" data-id="<?php echo esc_attr($link->id); ?>" title="<?php esc_attr_e('Bearbeiten', 'linktrade-monitor'); ?>">
                                        <span class="dashicons dashicons-edit"></span>
                                    </button>
                                    <?php if ($link->fairness_score < 100): ?>
                                        <button type="button" class="action-btn linktrade-contact-partner" data-id="<?php echo esc_attr($link->id); ?>" title="<?php esc_attr_e('Partner kontaktieren', 'linktrade-monitor'); ?>">
                                            <span class="dashicons dashicons-email"></span>
                                        </button>
                                    <?php endif; ?>
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
     * Anchor-Text Tab rendern
     */
    private function render_anchors_tab() {
        global $wpdb;
        $table = $wpdb->prefix . 'linktrade_links';

        // Anchor-Verteilung
        $anchors = $wpdb->get_results(
            "SELECT anchor_text, COUNT(*) as count
             FROM $table
             WHERE anchor_text IS NOT NULL AND anchor_text != ''
             GROUP BY anchor_text
             ORDER BY count DESC"
        );

        $total_anchors = array_sum(array_column($anchors, 'count'));

        // Warnungs-Schwelle
        $exact_threshold = get_option('linktrade_anchor_exact_threshold', 30);
        ?>
        <div class="linktrade-card">
            <div class="linktrade-table-header">
                <h3><?php _e('Anchor-Text Diversität', 'linktrade-monitor'); ?></h3>
                <p class="description"><?php _e('Analysiert die Verteilung deiner Ankertexte für ein natürliches Linkprofil.', 'linktrade-monitor'); ?></p>
            </div>

            <?php
            // Warnung bei zu vielen Exact-Match Anchors
            $exact_count = 0;
            foreach ($anchors as $anchor) {
                // Vereinfachte Erkennung: Wenn es wie ein Keyword aussieht
                if (!filter_var($anchor->anchor_text, FILTER_VALIDATE_URL) &&
                    !preg_match('/^(hier|klick|mehr|website|seite|link)/i', $anchor->anchor_text)) {
                    $exact_count += $anchor->count;
                }
            }
            $exact_percent = $total_anchors > 0 ? ($exact_count / $total_anchors) * 100 : 0;

            if ($exact_percent > $exact_threshold):
            ?>
                <div class="linktrade-notice notice-warning">
                    <p>
                        <strong><?php _e('Achtung:', 'linktrade-monitor'); ?></strong>
                        <?php printf(
                            __('%.1f%% deiner Anchors sind Exact-Match Keywords. Empfohlen sind max. %d%% für ein natürliches Linkprofil.', 'linktrade-monitor'),
                            $exact_percent,
                            $exact_threshold
                        ); ?>
                    </p>
                </div>
            <?php endif; ?>

            <div class="anchor-overview">
                <div class="anchor-chart">
                    <h4><?php _e('Verteilung', 'linktrade-monitor'); ?></h4>
                    <?php if (!empty($anchors)): ?>
                        <div class="anchor-bars">
                            <?php foreach (array_slice($anchors, 0, 10) as $anchor):
                                $percent = ($anchor->count / $total_anchors) * 100;
                                $bar_class = $percent > 20 ? 'bar-warning' : 'bar-normal';
                            ?>
                                <div class="anchor-bar-row">
                                    <span class="anchor-text" title="<?php echo esc_attr($anchor->anchor_text); ?>">
                                        <?php echo esc_html($this->truncate_text($anchor->anchor_text, 30)); ?>
                                    </span>
                                    <div class="anchor-bar <?php echo $bar_class; ?>">
                                        <div class="bar-fill" style="width: <?php echo min($percent * 2, 100); ?>%"></div>
                                    </div>
                                    <span class="anchor-count"><?php echo $anchor->count; ?> (<?php echo round($percent, 1); ?>%)</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="linktrade-empty-small"><?php _e('Noch keine Anchor-Texte eingetragen.', 'linktrade-monitor'); ?></p>
                    <?php endif; ?>
                </div>

                <div class="anchor-recommendations">
                    <h4><?php _e('Empfehlungen', 'linktrade-monitor'); ?></h4>
                    <ul class="recommendations-list">
                        <li><strong>Brand (30-40%):</strong> <?php _e('Dein Markenname', 'linktrade-monitor'); ?></li>
                        <li><strong>Generic (20-30%):</strong> <?php _e('"hier klicken", "mehr erfahren"', 'linktrade-monitor'); ?></li>
                        <li><strong>Naked URL (10-20%):</strong> <?php _e('Die nackte URL', 'linktrade-monitor'); ?></li>
                        <li><strong>Exact Match (max 10-15%):</strong> <?php _e('Exaktes Keyword', 'linktrade-monitor'); ?></li>
                        <li><strong>Partial Match (15-25%):</strong> <?php _e('Keyword-Variationen', 'linktrade-monitor'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Ablaufende Links Tab
     */
    private function render_expiring_tab() {
        global $wpdb;
        $table = $wpdb->prefix . 'linktrade_links';

        $links = $wpdb->get_results(
            "SELECT *, DATEDIFF(end_date, CURDATE()) as days_left
             FROM $table
             WHERE end_date IS NOT NULL AND end_date >= CURDATE()
             ORDER BY end_date ASC"
        );
        ?>
        <div class="linktrade-card">
            <div class="linktrade-table-header">
                <h3><?php _e('Auslaufende Links', 'linktrade-monitor'); ?></h3>
                <p class="description"><?php _e('Links mit Ablaufdatum, sortiert nach Dringlichkeit.', 'linktrade-monitor'); ?></p>
            </div>

            <table class="linktrade-table">
                <thead>
                    <tr>
                        <th><?php _e('Partner', 'linktrade-monitor'); ?></th>
                        <th><?php _e('Kategorie', 'linktrade-monitor'); ?></th>
                        <th><?php _e('Ablaufdatum', 'linktrade-monitor'); ?></th>
                        <th><?php _e('Verbleibend', 'linktrade-monitor'); ?></th>
                        <th><?php _e('Preis', 'linktrade-monitor'); ?></th>
                        <th><?php _e('Aktionen', 'linktrade-monitor'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($links)): ?>
                        <tr>
                            <td colspan="6" class="linktrade-empty"><?php _e('Keine Links mit Ablaufdatum.', 'linktrade-monitor'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($links as $link):
                            $urgency_class = '';
                            if ($link->days_left <= 7) {
                                $urgency_class = 'urgency-critical';
                            } elseif ($link->days_left <= 14) {
                                $urgency_class = 'urgency-high';
                            } elseif ($link->days_left <= 30) {
                                $urgency_class = 'urgency-medium';
                            }
                        ?>
                            <tr data-id="<?php echo esc_attr($link->id); ?>" class="<?php echo $urgency_class; ?>">
                                <td>
                                    <strong><?php echo esc_html($link->partner_name); ?></strong>
                                    <?php if ($link->domain_rating): ?>
                                        <span class="dr-badge">DR <?php echo $link->domain_rating; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="category-tag <?php echo esc_attr($link->category); ?>">
                                        <?php echo esc_html(ucfirst($link->category)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($link->end_date))); ?></td>
                                <td>
                                    <span class="days-left <?php echo $urgency_class; ?>">
                                        <?php echo $link->days_left; ?> <?php _e('Tage', 'linktrade-monitor'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($link->price > 0): ?>
                                        <?php echo number_format($link->price, 2, ',', '.'); ?> €
                                        <?php if ($link->price_period !== 'once'): ?>
                                            <small>/<?php echo $link->price_period === 'monthly' ? 'Monat' : 'Jahr'; ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <button type="button" class="action-btn linktrade-renew" data-id="<?php echo esc_attr($link->id); ?>" title="<?php esc_attr_e('Verlängern', 'linktrade-monitor'); ?>">
                                        <span class="dashicons dashicons-update"></span>
                                    </button>
                                    <button type="button" class="action-btn linktrade-contact-partner" data-id="<?php echo esc_attr($link->id); ?>" title="<?php esc_attr_e('Partner kontaktieren', 'linktrade-monitor'); ?>">
                                        <span class="dashicons dashicons-email"></span>
                                    </button>
                                    <button type="button" class="action-btn linktrade-edit" data-id="<?php echo esc_attr($link->id); ?>" title="<?php esc_attr_e('Bearbeiten', 'linktrade-monitor'); ?>">
                                        <span class="dashicons dashicons-edit"></span>
                                    </button>
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
     * Kontakte-Tab rendern
     */
    private function render_contacts_tab() {
        global $wpdb;
        $contacts_table = $wpdb->prefix . 'linktrade_contacts';
        $links_table = $wpdb->prefix . 'linktrade_links';

        $contacts = $wpdb->get_results(
            "SELECT c.*, l.partner_name, l.partner_contact
             FROM $contacts_table c
             LEFT JOIN $links_table l ON c.link_id = l.id
             ORDER BY c.contact_date DESC
             LIMIT 50"
        );

        // Reminder
        $reminders = $wpdb->get_results(
            "SELECT c.*, l.partner_name
             FROM $contacts_table c
             LEFT JOIN $links_table l ON c.link_id = l.id
             WHERE c.reminder_date IS NOT NULL AND c.reminder_date <= CURDATE() AND c.reminder_done = 0
             ORDER BY c.reminder_date ASC"
        );
        ?>
        <?php if (!empty($reminders)): ?>
            <div class="linktrade-card reminder-card">
                <h3><span class="dashicons dashicons-bell"></span> <?php _e('Offene Erinnerungen', 'linktrade-monitor'); ?></h3>
                <div class="reminder-list">
                    <?php foreach ($reminders as $reminder): ?>
                        <div class="reminder-item">
                            <span class="reminder-partner"><?php echo esc_html($reminder->partner_name); ?></span>
                            <span class="reminder-subject"><?php echo esc_html($reminder->subject); ?></span>
                            <span class="reminder-date"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($reminder->reminder_date))); ?></span>
                            <button type="button" class="button button-small linktrade-reminder-done" data-id="<?php echo esc_attr($reminder->id); ?>">
                                <?php _e('Erledigt', 'linktrade-monitor'); ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="linktrade-card">
            <div class="linktrade-table-header">
                <h3><?php _e('Kontakt-Historie', 'linktrade-monitor'); ?></h3>
                <button type="button" class="button" id="linktrade-add-contact">
                    <span class="dashicons dashicons-plus"></span>
                    <?php _e('Kontakt hinzufügen', 'linktrade-monitor'); ?>
                </button>
            </div>

            <table class="linktrade-table">
                <thead>
                    <tr>
                        <th><?php _e('Datum', 'linktrade-monitor'); ?></th>
                        <th><?php _e('Partner', 'linktrade-monitor'); ?></th>
                        <th><?php _e('Typ', 'linktrade-monitor'); ?></th>
                        <th><?php _e('Betreff', 'linktrade-monitor'); ?></th>
                        <th><?php _e('Aktionen', 'linktrade-monitor'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contacts)): ?>
                        <tr>
                            <td colspan="5" class="linktrade-empty"><?php _e('Noch keine Kontakte erfasst.', 'linktrade-monitor'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($contacts as $contact): ?>
                            <tr data-id="<?php echo esc_attr($contact->id); ?>">
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($contact->contact_date))); ?></td>
                                <td>
                                    <strong><?php echo esc_html($contact->partner_name); ?></strong>
                                    <br><small><?php echo esc_html($contact->partner_contact); ?></small>
                                </td>
                                <td>
                                    <span class="contact-type contact-<?php echo esc_attr($contact->contact_type); ?>">
                                        <?php
                                        $types = ['email' => 'E-Mail', 'phone' => 'Telefon', 'meeting' => 'Meeting', 'other' => 'Sonstiges'];
                                        echo esc_html($types[$contact->contact_type] ?? $contact->contact_type);
                                        ?>
                                    </span>
                                    <span class="contact-direction"><?php echo $contact->direction === 'incoming' ? '←' : '→'; ?></span>
                                </td>
                                <td><?php echo esc_html($contact->subject); ?></td>
                                <td class="actions">
                                    <button type="button" class="action-btn linktrade-view-contact" data-id="<?php echo esc_attr($contact->id); ?>" title="<?php esc_attr_e('Details', 'linktrade-monitor'); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </button>
                                    <button type="button" class="action-btn danger linktrade-delete-contact" data-id="<?php echo esc_attr($contact->id); ?>" title="<?php esc_attr_e('Löschen', 'linktrade-monitor'); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
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
     * Links-Tab rendern
     */
    private function render_links_tab() {
        ?>
        <div class="linktrade-card">
            <div class="linktrade-table-header">
                <h3><?php _e('Linkübersicht', 'linktrade-monitor'); ?></h3>
                <div class="linktrade-filters">
                    <input type="text" id="linktrade-search" class="linktrade-search"
                           placeholder="<?php esc_attr_e('Links durchsuchen...', 'linktrade-monitor'); ?>">
                    <select id="linktrade-filter-category" class="linktrade-select">
                        <option value=""><?php _e('Alle Kategorien', 'linktrade-monitor'); ?></option>
                        <option value="tausch"><?php _e('Linktausch', 'linktrade-monitor'); ?></option>
                        <option value="kauf"><?php _e('Linkkauf', 'linktrade-monitor'); ?></option>
                        <option value="kostenlos"><?php _e('Kostenlos', 'linktrade-monitor'); ?></option>
                    </select>
                    <select id="linktrade-filter-status" class="linktrade-select">
                        <option value=""><?php _e('Alle Status', 'linktrade-monitor'); ?></option>
                        <option value="online"><?php _e('Online', 'linktrade-monitor'); ?></option>
                        <option value="warning"><?php _e('Warnung', 'linktrade-monitor'); ?></option>
                        <option value="offline"><?php _e('Offline', 'linktrade-monitor'); ?></option>
                        <option value="unchecked"><?php _e('Ungeprüft', 'linktrade-monitor'); ?></option>
                    </select>
                    <select id="linktrade-sort" class="linktrade-select">
                        <option value="created_desc"><?php _e('Neueste zuerst', 'linktrade-monitor'); ?></option>
                        <option value="dr_desc"><?php _e('Höchster DR', 'linktrade-monitor'); ?></option>
                        <option value="quality_desc"><?php _e('Beste Qualität', 'linktrade-monitor'); ?></option>
                        <option value="name_asc"><?php _e('Name A-Z', 'linktrade-monitor'); ?></option>
                    </select>
                </div>
            </div>
            <?php $this->render_links_table(); ?>
        </div>
        <?php
    }

    /**
     * Link-Tabelle rendern (erweitert)
     */
    private function render_links_table($limit = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'linktrade_links';

        $sql = "SELECT * FROM $table ORDER BY created_at DESC";
        if ($limit > 0) {
            $sql .= $wpdb->prepare(" LIMIT %d", $limit);
        }

        $links = $wpdb->get_results($sql);
        ?>
        <table class="linktrade-table linktrade-table-full">
            <thead>
                <tr>
                    <th><?php _e('Partner', 'linktrade-monitor'); ?></th>
                    <th><?php _e('Kat.', 'linktrade-monitor'); ?></th>
                    <th><?php _e('DR', 'linktrade-monitor'); ?></th>
                    <th><?php _e('Q-Score', 'linktrade-monitor'); ?></th>
                    <th><?php _e('Linkpartner URL', 'linktrade-monitor'); ?></th>
                    <th><?php _e('Status', 'linktrade-monitor'); ?></th>
                    <th><?php _e('Laufzeit', 'linktrade-monitor'); ?></th>
                    <th><?php _e('Wert', 'linktrade-monitor'); ?></th>
                    <th><?php _e('Aktionen', 'linktrade-monitor'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($links)): ?>
                    <tr>
                        <td colspan="9" class="linktrade-empty">
                            <?php _e('Noch keine Links vorhanden.', 'linktrade-monitor'); ?>
                            <a href="<?php echo esc_url(add_query_arg('tab', 'add', admin_url('admin.php?page=linktrade-monitor'))); ?>">
                                <?php _e('Ersten Link eintragen', 'linktrade-monitor'); ?>
                            </a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($links as $link): ?>
                        <tr data-id="<?php echo esc_attr($link->id); ?>">
                            <td>
                                <strong><?php echo esc_html($link->partner_name); ?></strong>
                                <?php if ($link->partner_contact): ?>
                                    <br><small><?php echo esc_html($link->partner_contact); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="category-tag <?php echo esc_attr($link->category); ?>">
                                    <?php echo esc_html(substr(ucfirst($link->category), 0, 1)); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($link->domain_rating): ?>
                                    <span class="dr-value"><?php echo $link->domain_rating; ?></span>
                                <?php else: ?>
                                    <span class="no-value">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($link->quality_score): ?>
                                    <span class="quality-badge quality-<?php echo $this->get_quality_class($link->quality_score); ?>">
                                        <?php echo $link->quality_score; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="no-value">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="link-cell">
                                <a href="<?php echo esc_url($link->partner_url); ?>" target="_blank" class="link-url">
                                    <?php echo esc_html($this->truncate_url($link->partner_url)); ?>
                                </a>
                                <?php if ($link->anchor_text): ?>
                                    <br><small class="anchor-preview">"<?php echo esc_html($this->truncate_text($link->anchor_text, 20)); ?>"</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $this->render_status_badge($link); ?>
                                <?php if ($link->category === 'tausch' && $link->fairness_score < 100): ?>
                                    <br><small class="fairness-warning"><?php echo $link->fairness_score; ?>% fair</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                if ($link->end_date) {
                                    $days_left = (strtotime($link->end_date) - time()) / 86400;
                                    echo esc_html(date_i18n('d.m.Y', strtotime($link->end_date)));
                                    if ($days_left <= 30 && $days_left > 0) {
                                        echo '<br><small class="days-warning">' . ceil($days_left) . ' Tage</small>';
                                    }
                                } else {
                                    echo '<span class="no-value">∞</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($link->value_score > 0): ?>
                                    <span class="value-score"><?php echo number_format($link->value_score, 1); ?></span>
                                <?php elseif ($link->price > 0): ?>
                                    <span class="price-value"><?php echo number_format($link->price, 0); ?>€</span>
                                <?php else: ?>
                                    <span class="no-value">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <button type="button" class="action-btn linktrade-edit" data-id="<?php echo esc_attr($link->id); ?>" title="<?php esc_attr_e('Bearbeiten', 'linktrade-monitor'); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button type="button" class="action-btn linktrade-check" data-id="<?php echo esc_attr($link->id); ?>" title="<?php esc_attr_e('Prüfen', 'linktrade-monitor'); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                                <button type="button" class="action-btn danger linktrade-delete" data-id="<?php echo esc_attr($link->id); ?>" title="<?php esc_attr_e('Löschen', 'linktrade-monitor'); ?>">
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
     * Link-hinzufügen Tab
     */
    private function render_add_tab() {
        ?>
        <div class="linktrade-card linktrade-form-card">
            <h3><?php _e('Neuen Link eintragen', 'linktrade-monitor'); ?></h3>
            <?php $this->render_link_form(); ?>
        </div>
        <?php
    }

    /**
     * Link-Formular rendern (erweitert)
     */
    private function render_link_form($link = null) {
        $is_edit = !empty($link);
        ?>
        <form id="linktrade-link-form" class="linktrade-form">
            <input type="hidden" name="id" value="<?php echo $is_edit ? esc_attr($link->id) : ''; ?>">

            <!-- Partner-Grunddaten -->
            <div class="form-section">
                <h4><?php _e('Partner-Informationen', 'linktrade-monitor'); ?></h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="partner_name"><?php _e('Partner-Name', 'linktrade-monitor'); ?> *</label>
                        <input type="text" name="partner_name" id="partner_name" required
                               value="<?php echo $is_edit ? esc_attr($link->partner_name) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="partner_contact"><?php _e('Kontakt', 'linktrade-monitor'); ?></label>
                        <input type="text" name="partner_contact" id="partner_contact"
                               placeholder="<?php _e('E-Mail, Facebook, Instagram, etc.', 'linktrade-monitor'); ?>"
                               value="<?php echo $is_edit ? esc_attr($link->partner_contact) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="category"><?php _e('Kategorie', 'linktrade-monitor'); ?> *</label>
                        <select name="category" id="category" required>
                            <option value="tausch" <?php selected($is_edit && $link->category === 'tausch'); ?>><?php _e('Linktausch', 'linktrade-monitor'); ?></option>
                            <option value="kauf" <?php selected($is_edit && $link->category === 'kauf'); ?>><?php _e('Linkkauf', 'linktrade-monitor'); ?></option>
                            <option value="kostenlos" <?php selected($is_edit && $link->category === 'kostenlos'); ?>><?php _e('Kostenlos', 'linktrade-monitor'); ?></option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- URLs -->
            <div class="form-section">
                <h4><?php _e('Link-Details (Eingehend)', 'linktrade-monitor'); ?></h4>
                <div class="form-group">
                    <label for="partner_url"><?php _e('URL auf Partnerseite', 'linktrade-monitor'); ?> *</label>
                    <input type="url" name="partner_url" id="partner_url" required
                           placeholder="https://partner-seite.de/artikel-mit-link"
                           value="<?php echo $is_edit ? esc_attr($link->partner_url) : ''; ?>">
                    <small><?php _e('Die Seite, auf der der Backlink zu dir platziert ist.', 'linktrade-monitor'); ?></small>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="target_url"><?php _e('Meine verlinkte URL', 'linktrade-monitor'); ?> *</label>
                        <input type="url" name="target_url" id="target_url" required
                               placeholder="https://deine-seite.de/zielseite"
                               value="<?php echo $is_edit ? esc_attr($link->target_url) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="anchor_text"><?php _e('Ankertext', 'linktrade-monitor'); ?></label>
                        <input type="text" name="anchor_text" id="anchor_text"
                               value="<?php echo $is_edit ? esc_attr($link->anchor_text) : ''; ?>">
                    </div>
                </div>
            </div>

            <!-- Gegenlink (nur bei Tausch) -->
            <div class="form-section tausch-fields" style="<?php echo (!$is_edit || $link->category === 'tausch') ? '' : 'display:none;'; ?>">
                <h4><?php _e('Gegenlink (Ausgehend)', 'linktrade-monitor'); ?></h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="backlink_url"><?php _e('Mein Gegenlink (deine Seite)', 'linktrade-monitor'); ?></label>
                        <input type="url" name="backlink_url" id="backlink_url"
                               placeholder="https://deine-seite.de/artikel-mit-partnerlink"
                               value="<?php echo $is_edit ? esc_attr($link->backlink_url) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="backlink_target"><?php _e('Ziel des Gegenlinks', 'linktrade-monitor'); ?></label>
                        <input type="url" name="backlink_target" id="backlink_target"
                               placeholder="https://partner-seite.de/zielseite"
                               value="<?php echo $is_edit ? esc_attr($link->backlink_target) : ''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="backlink_anchor"><?php _e('Ankertext Gegenlink', 'linktrade-monitor'); ?></label>
                    <input type="text" name="backlink_anchor" id="backlink_anchor"
                           value="<?php echo $is_edit ? esc_attr($link->backlink_anchor ?? '') : ''; ?>">
                </div>
            </div>

            <!-- Partner-Bewertung -->
            <div class="form-section">
                <h4><?php _e('Partner-Bewertung', 'linktrade-monitor'); ?></h4>
                <div class="form-row form-row-metrics">
                    <div class="form-group">
                        <label for="domain_rating"><?php _e('Domain Rating (DR)', 'linktrade-monitor'); ?></label>
                        <input type="number" name="domain_rating" id="domain_rating" min="0" max="100"
                               value="<?php echo $is_edit ? esc_attr($link->domain_rating) : ''; ?>">
                        <small>Ahrefs</small>
                    </div>
                    <div class="form-group">
                        <label for="domain_authority"><?php _e('Domain Authority (DA)', 'linktrade-monitor'); ?></label>
                        <input type="number" name="domain_authority" id="domain_authority" min="0" max="100"
                               value="<?php echo $is_edit ? esc_attr($link->domain_authority) : ''; ?>">
                        <small>Moz</small>
                    </div>
                    <div class="form-group">
                        <label for="monthly_traffic"><?php _e('Traffic / Monat', 'linktrade-monitor'); ?></label>
                        <input type="number" name="monthly_traffic" id="monthly_traffic" min="0"
                               value="<?php echo $is_edit ? esc_attr($link->monthly_traffic) : ''; ?>">
                        <small>Semrush/Similarweb</small>
                    </div>
                    <div class="form-group">
                        <label for="spam_score"><?php _e('Spam Score', 'linktrade-monitor'); ?></label>
                        <input type="number" name="spam_score" id="spam_score" min="0" max="100"
                               value="<?php echo $is_edit ? esc_attr($link->spam_score) : ''; ?>">
                        <small>Moz (niedriger = besser)</small>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="niche"><?php _e('Nische/Thema', 'linktrade-monitor'); ?></label>
                        <input type="text" name="niche" id="niche"
                               placeholder="z.B. Film, SEO, Tech..."
                               value="<?php echo $is_edit ? esc_attr($link->niche ?? '') : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="relevance_score"><?php _e('Themenrelevanz (0-100)', 'linktrade-monitor'); ?></label>
                        <input type="number" name="relevance_score" id="relevance_score" min="0" max="100"
                               value="<?php echo $is_edit ? esc_attr($link->relevance_score) : '50'; ?>">
                    </div>
                </div>
            </div>

            <!-- Laufzeit & Kosten -->
            <div class="form-section">
                <h4><?php _e('Laufzeit & Kosten', 'linktrade-monitor'); ?></h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date"><?php _e('Startdatum', 'linktrade-monitor'); ?></label>
                        <input type="date" name="start_date" id="start_date"
                               value="<?php echo $is_edit && $link->start_date ? esc_attr($link->start_date) : wp_date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_date"><?php _e('Enddatum', 'linktrade-monitor'); ?></label>
                        <input type="date" name="end_date" id="end_date"
                               value="<?php echo $is_edit ? esc_attr($link->end_date) : ''; ?>">
                        <small><?php _e('Leer = unbegrenzt', 'linktrade-monitor'); ?></small>
                    </div>
                    <div class="form-group kauf-fields" style="<?php echo ($is_edit && $link->category === 'kauf') ? '' : 'display:none;'; ?>">
                        <label for="price"><?php _e('Preis (EUR)', 'linktrade-monitor'); ?></label>
                        <input type="number" name="price" id="price" step="0.01" min="0"
                               value="<?php echo $is_edit ? esc_attr($link->price) : ''; ?>">
                    </div>
                    <div class="form-group kauf-fields" style="<?php echo ($is_edit && $link->category === 'kauf') ? '' : 'display:none;'; ?>">
                        <label for="price_period"><?php _e('Zahlungsintervall', 'linktrade-monitor'); ?></label>
                        <select name="price_period" id="price_period">
                            <option value="once" <?php selected($is_edit && ($link->price_period ?? 'once') === 'once'); ?>><?php _e('Einmalig', 'linktrade-monitor'); ?></option>
                            <option value="monthly" <?php selected($is_edit && ($link->price_period ?? '') === 'monthly'); ?>><?php _e('Monatlich', 'linktrade-monitor'); ?></option>
                            <option value="yearly" <?php selected($is_edit && ($link->price_period ?? '') === 'yearly'); ?>><?php _e('Jährlich', 'linktrade-monitor'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="auto_renew" value="1" <?php checked($is_edit && !empty($link->auto_renew)); ?>>
                        <?php _e('Automatische Verlängerung (Erinnerung trotzdem senden)', 'linktrade-monitor'); ?>
                    </label>
                </div>
            </div>

            <!-- Notizen -->
            <div class="form-group">
                <label for="notes"><?php _e('Notizen', 'linktrade-monitor'); ?></label>
                <textarea name="notes" id="notes" rows="3"><?php echo $is_edit ? esc_textarea($link->notes) : ''; ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-primary">
                    <?php echo $is_edit ? __('Link aktualisieren', 'linktrade-monitor') : __('Link speichern', 'linktrade-monitor'); ?>
                </button>
                <?php if (!$is_edit): ?>
                    <button type="button" class="button" id="linktrade-save-and-new">
                        <?php _e('Speichern & weiteren anlegen', 'linktrade-monitor'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </form>
        <?php
    }

    /**
     * Einstellungen-Tab
     */
    private function render_settings_tab() {
        if (isset($_POST['linktrade_save_settings'], $_POST['_wpnonce']) &&
            wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'linktrade_settings')) {
            $this->save_settings();
            echo '<div class="notice notice-success"><p>' . esc_html__('Einstellungen gespeichert.', 'linktrade-monitor') . '</p></div>';
        }
        ?>
        <div class="linktrade-card">
            <h3><?php _e('Allgemeine Einstellungen', 'linktrade-monitor'); ?></h3>
            <form method="post" class="linktrade-form">
                <?php wp_nonce_field('linktrade_settings'); ?>

                <div class="form-section">
                    <h4><?php _e('Link-Prüfung', 'linktrade-monitor'); ?></h4>
                    <div class="form-group">
                        <label for="check_frequency"><?php _e('Prüf-Intervall', 'linktrade-monitor'); ?></label>
                        <select name="check_frequency" id="check_frequency">
                            <option value="daily" <?php selected(get_option('linktrade_check_frequency'), 'daily'); ?>><?php _e('Täglich', 'linktrade-monitor'); ?></option>
                            <option value="weekly" <?php selected(get_option('linktrade_check_frequency'), 'weekly'); ?>><?php _e('Wöchentlich', 'linktrade-monitor'); ?></option>
                            <option value="monthly" <?php selected(get_option('linktrade_check_frequency'), 'monthly'); ?>><?php _e('Monatlich', 'linktrade-monitor'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <h4><?php _e('Benachrichtigungen', 'linktrade-monitor'); ?></h4>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="email_notifications" value="1"
                                <?php checked(get_option('linktrade_email_notifications'), true); ?>>
                            <?php _e('E-Mail-Benachrichtigungen aktivieren', 'linktrade-monitor'); ?>
                        </label>
                    </div>
                    <div class="form-group">
                        <label for="notification_email"><?php _e('Benachrichtigungs-E-Mail', 'linktrade-monitor'); ?></label>
                        <input type="email" name="notification_email" id="notification_email"
                               value="<?php echo esc_attr(get_option('linktrade_notification_email')); ?>">
                    </div>
                    <div class="form-group">
                        <label for="reminder_days"><?php _e('Erinnerung vor Ablauf (Tage)', 'linktrade-monitor'); ?></label>
                        <input type="number" name="reminder_days" id="reminder_days" min="1" max="60"
                               value="<?php echo esc_attr(get_option('linktrade_reminder_days', 14)); ?>">
                    </div>
                </div>

                <div class="form-section">
                    <h4><?php _e('Fairness-Warnung', 'linktrade-monitor'); ?></h4>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="fairness_alert" value="1"
                                <?php checked(get_option('linktrade_fairness_alert'), true); ?>>
                            <?php _e('Bei Fairness-Problemen warnen', 'linktrade-monitor'); ?>
                        </label>
                    </div>
                </div>

                <div class="form-section">
                    <h4><?php _e('Anchor-Text Warnung', 'linktrade-monitor'); ?></h4>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="anchor_warning_enabled" value="1"
                                <?php checked(get_option('linktrade_anchor_warning_enabled'), true); ?>>
                            <?php _e('Bei zu vielen Exact-Match Anchors warnen', 'linktrade-monitor'); ?>
                        </label>
                    </div>
                    <div class="form-group">
                        <label for="anchor_exact_threshold"><?php _e('Schwellenwert Exact-Match (%)', 'linktrade-monitor'); ?></label>
                        <input type="number" name="anchor_exact_threshold" id="anchor_exact_threshold" min="10" max="100"
                               value="<?php echo esc_attr(get_option('linktrade_anchor_exact_threshold', 30)); ?>">
                    </div>
                </div>

                <div class="form-section">
                    <h4><?php _e('Qualitäts-Score Gewichtung', 'linktrade-monitor'); ?></h4>
                    <p class="description"><?php _e('Gewichtung für die automatische Qualitäts-Berechnung (Summe = 100%)', 'linktrade-monitor'); ?></p>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="weight_dr"><?php _e('Domain Rating (%)', 'linktrade-monitor'); ?></label>
                            <input type="number" name="weight_dr" id="weight_dr" min="0" max="100"
                                   value="<?php echo esc_attr(get_option('linktrade_weight_dr', 40)); ?>">
                        </div>
                        <div class="form-group">
                            <label for="weight_traffic"><?php _e('Traffic (%)', 'linktrade-monitor'); ?></label>
                            <input type="number" name="weight_traffic" id="weight_traffic" min="0" max="100"
                                   value="<?php echo esc_attr(get_option('linktrade_weight_traffic', 30)); ?>">
                        </div>
                        <div class="form-group">
                            <label for="weight_relevance"><?php _e('Themenrelevanz (%)', 'linktrade-monitor'); ?></label>
                            <input type="number" name="weight_relevance" id="weight_relevance" min="0" max="100"
                                   value="<?php echo esc_attr(get_option('linktrade_weight_relevance', 30)); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="linktrade_save_settings" class="button button-primary">
                        <?php _e('Einstellungen speichern', 'linktrade-monitor'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Einstellungen speichern
     */
    private function save_settings() {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in render_settings_tab()
        $post_data = wp_unslash($_POST);

        update_option('linktrade_check_frequency', sanitize_key($post_data['check_frequency'] ?? 'weekly'));
        update_option('linktrade_email_notifications', !empty($post_data['email_notifications']));
        update_option('linktrade_notification_email', sanitize_email($post_data['notification_email'] ?? ''));
        update_option('linktrade_reminder_days', intval($post_data['reminder_days'] ?? 14));
        update_option('linktrade_fairness_alert', !empty($post_data['fairness_alert']));
        update_option('linktrade_anchor_warning_enabled', !empty($post_data['anchor_warning_enabled']));
        update_option('linktrade_anchor_exact_threshold', intval($post_data['anchor_exact_threshold'] ?? 30));
        update_option('linktrade_weight_dr', intval($post_data['weight_dr'] ?? 40));
        update_option('linktrade_weight_traffic', intval($post_data['weight_traffic'] ?? 30));
        update_option('linktrade_weight_relevance', intval($post_data['weight_relevance'] ?? 30));
    }

    /**
     * Modals rendern
     */
    private function render_modals() {
        ?>
        <!-- Link Modal -->
        <div id="linktrade-modal" class="linktrade-modal" style="display: none;">
            <div class="linktrade-modal-content linktrade-modal-large">
                <div class="linktrade-modal-header">
                    <h3 id="linktrade-modal-title"><?php _e('Link bearbeiten', 'linktrade-monitor'); ?></h3>
                    <button type="button" class="linktrade-modal-close">&times;</button>
                </div>
                <div class="linktrade-modal-body">
                    <?php $this->render_link_form(); ?>
                </div>
            </div>
        </div>

        <!-- Kontakt Modal -->
        <div id="linktrade-contact-modal" class="linktrade-modal" style="display: none;">
            <div class="linktrade-modal-content">
                <div class="linktrade-modal-header">
                    <h3><?php _e('Kontakt hinzufügen', 'linktrade-monitor'); ?></h3>
                    <button type="button" class="linktrade-modal-close">&times;</button>
                </div>
                <div class="linktrade-modal-body">
                    <form id="linktrade-contact-form" class="linktrade-form">
                        <input type="hidden" name="link_id" id="contact_link_id">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="contact_type"><?php _e('Typ', 'linktrade-monitor'); ?></label>
                                <select name="contact_type" id="contact_type">
                                    <option value="email"><?php _e('E-Mail', 'linktrade-monitor'); ?></option>
                                    <option value="phone"><?php _e('Telefon', 'linktrade-monitor'); ?></option>
                                    <option value="meeting"><?php _e('Meeting', 'linktrade-monitor'); ?></option>
                                    <option value="other"><?php _e('Sonstiges', 'linktrade-monitor'); ?></option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="contact_direction"><?php _e('Richtung', 'linktrade-monitor'); ?></label>
                                <select name="direction" id="contact_direction">
                                    <option value="outgoing"><?php _e('Ausgehend (du → Partner)', 'linktrade-monitor'); ?></option>
                                    <option value="incoming"><?php _e('Eingehend (Partner → du)', 'linktrade-monitor'); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="contact_subject"><?php _e('Betreff', 'linktrade-monitor'); ?></label>
                            <input type="text" name="subject" id="contact_subject">
                        </div>

                        <div class="form-group">
                            <label for="contact_content"><?php _e('Inhalt/Notizen', 'linktrade-monitor'); ?></label>
                            <textarea name="content" id="contact_content" rows="4"></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="contact_date"><?php _e('Datum', 'linktrade-monitor'); ?></label>
                                <input type="date" name="contact_date" id="contact_date" value="<?php echo wp_date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group">
                                <label for="reminder_date"><?php _e('Erinnerung', 'linktrade-monitor'); ?></label>
                                <input type="date" name="reminder_date" id="reminder_date">
                                <small><?php _e('Optional: Wann nachfassen?', 'linktrade-monitor'); ?></small>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="button button-primary"><?php _e('Speichern', 'linktrade-monitor'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    // === Helper-Methoden ===

    private function render_status_badge($link) {
        $status_class = $link->status;
        $status_text = ucfirst($link->status);

        if ($link->status === 'warning') {
            if ($link->is_nofollow) $status_text = 'nofollow';
            elseif ($link->is_noindex) $status_text = 'noindex';
        } elseif ($link->status === 'unchecked') {
            $status_text = __('Ungeprüft', 'linktrade-monitor');
        }

        return sprintf(
            '<span class="status %s"><span class="status-dot"></span>%s</span>',
            esc_attr($status_class),
            esc_html($status_text)
        );
    }

    private function render_backlink_status_badge($link) {
        $status = $link->backlink_status ?? 'unchecked';
        $status_class = $status;
        $status_text = ucfirst($status);

        if ($status === 'not_applicable') {
            return '<span class="status unchecked">-</span>';
        }

        if ($status === 'warning' && $link->backlink_is_nofollow) {
            $status_text = 'nofollow';
        } elseif ($status === 'unchecked') {
            $status_text = __('Ungeprüft', 'linktrade-monitor');
        }

        return sprintf(
            '<span class="status %s"><span class="status-dot"></span>%s</span>',
            esc_attr($status_class),
            esc_html($status_text)
        );
    }

    private function truncate_url($url, $max = 35) {
        $url = preg_replace('#^https?://(www\.)?#', '', $url);
        if (strlen($url) > $max) {
            return substr($url, 0, $max) . '...';
        }
        return $url;
    }

    private function truncate_text($text, $max = 30) {
        if (strlen($text) > $max) {
            return substr($text, 0, $max) . '...';
        }
        return $text;
    }

    private function get_quality_class($score) {
        if ($score >= 80) return 'excellent';
        if ($score >= 60) return 'good';
        if ($score >= 40) return 'average';
        return 'poor';
    }

    // === AJAX Handlers (werden in class-linktrade.php aufgerufen) ===

    public function ajax_save_link() {
        check_ajax_referer('linktrade_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'linktrade-monitor')]);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'linktrade_links';

        $data = $this->sanitize_link_data($_POST);

        // Qualitäts-Score berechnen
        $data['quality_score'] = $this->calculate_quality_score($data);

        // Value-Score berechnen (bei Kauf)
        if ($data['category'] === 'kauf' && $data['price'] > 0 && $data['domain_rating'] > 0) {
            $data['value_score'] = round($data['domain_rating'] / $data['price'] * 10, 2);
        }

        $id = intval($_POST['id'] ?? 0);

        if ($id > 0) {
            $wpdb->update($table, $data, ['id' => $id]);
            wp_send_json_success(['message' => __('Link aktualisiert.', 'linktrade-monitor'), 'id' => $id]);
        } else {
            $data['status'] = 'unchecked';
            if ($data['category'] === 'tausch') {
                $data['backlink_status'] = 'unchecked';
                $data['fairness_score'] = 100;
            }
            $wpdb->insert($table, $data);
            wp_send_json_success(['message' => __('Link gespeichert.', 'linktrade-monitor'), 'id' => $wpdb->insert_id]);
        }
    }

    private function sanitize_link_data($post) {
        return [
            'partner_name' => sanitize_text_field($post['partner_name'] ?? ''),
            'partner_contact' => sanitize_text_field($post['partner_contact'] ?? ''),
            'category' => sanitize_key($post['category'] ?? 'tausch'),
            'anchor_text' => sanitize_text_field($post['anchor_text'] ?? ''),
            'partner_url' => esc_url_raw($post['partner_url'] ?? ''),
            'target_url' => esc_url_raw($post['target_url'] ?? ''),
            'backlink_url' => esc_url_raw($post['backlink_url'] ?? ''),
            'backlink_target' => esc_url_raw($post['backlink_target'] ?? ''),
            'backlink_anchor' => sanitize_text_field($post['backlink_anchor'] ?? ''),
            'start_date' => sanitize_text_field($post['start_date'] ?? '') ?: null,
            'end_date' => sanitize_text_field($post['end_date'] ?? '') ?: null,
            'price' => floatval($post['price'] ?? 0),
            'price_period' => sanitize_key($post['price_period'] ?? 'once'),
            'auto_renew' => !empty($post['auto_renew']) ? 1 : 0,
            'domain_rating' => intval($post['domain_rating'] ?? 0),
            'domain_authority' => intval($post['domain_authority'] ?? 0),
            'monthly_traffic' => intval($post['monthly_traffic'] ?? 0),
            'spam_score' => intval($post['spam_score'] ?? 0),
            'niche' => sanitize_text_field($post['niche'] ?? ''),
            'relevance_score' => intval($post['relevance_score'] ?? 50),
            'notes' => sanitize_textarea_field($post['notes'] ?? ''),
        ];
    }

    private function calculate_quality_score($data) {
        $weight_dr = get_option('linktrade_weight_dr', 40) / 100;
        $weight_traffic = get_option('linktrade_weight_traffic', 30) / 100;
        $weight_relevance = get_option('linktrade_weight_relevance', 30) / 100;

        $dr_score = min($data['domain_rating'], 100);
        $traffic_score = min(log10(max($data['monthly_traffic'], 1) + 1) * 20, 100);
        $relevance_score = $data['relevance_score'];

        // Spam-Abzug
        $spam_penalty = $data['spam_score'] * 0.5;

        $score = ($dr_score * $weight_dr) + ($traffic_score * $weight_traffic) + ($relevance_score * $weight_relevance) - $spam_penalty;

        return max(0, min(100, round($score)));
    }

    public function ajax_delete_link() {
        check_ajax_referer('linktrade_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'linktrade-monitor')]);
        }

        global $wpdb;
        $id = intval($_POST['id'] ?? 0);

        if ($id > 0) {
            $wpdb->delete($wpdb->prefix . 'linktrade_links', ['id' => $id]);
            $wpdb->delete($wpdb->prefix . 'linktrade_contacts', ['link_id' => $id]);
            wp_send_json_success(['message' => __('Link gelöscht.', 'linktrade-monitor')]);
        }

        wp_send_json_error(['message' => __('Ungültige Link-ID.', 'linktrade-monitor')]);
    }

    public function ajax_get_links() {
        check_ajax_referer('linktrade_nonce', 'nonce');

        global $wpdb;
        $table = $wpdb->prefix . 'linktrade_links';
        $id = intval($_POST['id'] ?? 0);

        if ($id > 0) {
            $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
            wp_send_json_success(['link' => $link]);
        }

        wp_send_json_error(['message' => __('Link nicht gefunden.', 'linktrade-monitor')]);
    }

    /**
     * Einzelnen Link per AJAX laden
     */
    public function ajax_get_link() {
        check_ajax_referer('linktrade_nonce', 'nonce');

        global $wpdb;
        $table = $wpdb->prefix . 'linktrade_links';
        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            wp_send_json_error(['message' => __('Ungültige Link-ID.', 'linktrade-monitor')]);
        }

        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));

        if (!$link) {
            wp_send_json_error(['message' => __('Link nicht gefunden.', 'linktrade-monitor')]);
        }

        wp_send_json_success(['link' => $link]);
    }

    public function ajax_check_link() {
        check_ajax_referer('linktrade_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'linktrade-monitor')]);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'linktrade_links';
        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            wp_send_json_error(['message' => __('Ungültige Link-ID.', 'linktrade-monitor')]);
        }

        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));

        if (!$link) {
            wp_send_json_error(['message' => __('Link nicht gefunden.', 'linktrade-monitor')]);
        }

        require_once LINKTRADE_PLUGIN_DIR . 'includes/checker/class-link-checker.php';
        $checker = new Linktrade_Link_Checker();

        // Eingehenden Link prüfen (Partner → Meine Seite)
        $result = $checker->check($link->partner_url, $link->target_url);

        $update_data = [
            'status' => $result['status'],
            'http_code' => $result['http_code'],
            'is_nofollow' => $result['is_nofollow'],
            'is_noindex' => $result['is_noindex'],
            'redirect_url' => $result['redirect_url'],
            'last_check' => current_time('mysql'),
        ];

        // Check-Historie für eingehenden Link
        $wpdb->insert($wpdb->prefix . 'linktrade_checks', [
            'link_id' => $id,
            'check_type' => 'incoming',
            'http_code' => $result['http_code'],
            'response_time' => $result['response_time'],
            'is_nofollow' => $result['is_nofollow'],
            'is_noindex' => $result['is_noindex'],
            'redirect_url' => $result['redirect_url'],
            'anchor_found' => $result['anchor_text'] ?? null,
            'error_message' => $result['error_message'],
        ]);

        $backlink_result = null;

        // Bei Linktausch IMMER auch ausgehenden Gegenlink prüfen (Meine Seite → Partner)
        if ($link->category === 'tausch' && $link->backlink_url) {
            $backlink_result = $checker->check($link->backlink_url, $link->backlink_target);

            $update_data['backlink_status'] = $backlink_result['status'];
            $update_data['backlink_http_code'] = $backlink_result['http_code'];
            $update_data['backlink_is_nofollow'] = $backlink_result['is_nofollow'];
            $update_data['backlink_last_check'] = current_time('mysql');

            // Fairness-Score berechnen
            $update_data['fairness_score'] = $this->calculate_fairness_score(
                $result['status'],
                $backlink_result['status'],
                $result['is_nofollow'],
                $backlink_result['is_nofollow']
            );

            // Check-Historie für ausgehenden Link
            $wpdb->insert($wpdb->prefix . 'linktrade_checks', [
                'link_id' => $id,
                'check_type' => 'outgoing',
                'http_code' => $backlink_result['http_code'],
                'response_time' => $backlink_result['response_time'],
                'is_nofollow' => $backlink_result['is_nofollow'],
                'is_noindex' => $backlink_result['is_noindex'],
                'redirect_url' => $backlink_result['redirect_url'],
                'anchor_found' => $backlink_result['anchor_text'] ?? null,
                'error_message' => $backlink_result['error_message'],
            ]);
        }

        $wpdb->update($table, $update_data, ['id' => $id]);

        // Nachricht je nach Ergebnis
        $message = __('Link geprüft.', 'linktrade-monitor');
        if ($link->category === 'tausch' && $backlink_result) {
            $message = __('Beide Links geprüft (eingehend + ausgehend).', 'linktrade-monitor');
        }

        wp_send_json_success([
            'message' => $message,
            'result' => $result,
            'backlink_result' => $backlink_result,
            'fairness_score' => $update_data['fairness_score'] ?? null,
        ]);
    }

    private function calculate_fairness_score($my_status, $their_status, $my_nofollow, $their_nofollow) {
        $score = 100;

        // Partner-Link offline = großes Problem
        if ($my_status === 'online' && $their_status === 'offline') {
            $score = 0;
        }
        // Beide offline = neutral
        elseif ($my_status === 'offline' && $their_status === 'offline') {
            $score = 50;
        }
        // Partner hat nofollow, ich nicht
        elseif (!$my_nofollow && $their_nofollow) {
            $score = 60;
        }
        // Beide nofollow = fair
        elseif ($my_nofollow && $their_nofollow) {
            $score = 100;
        }
        // Beide online, beide dofollow = perfekt
        elseif ($my_status === 'online' && $their_status === 'online') {
            $score = 100;
        }

        return $score;
    }

    public function ajax_save_contact() {
        check_ajax_referer('linktrade_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'linktrade-monitor')]);
        }

        global $wpdb;
        $post_data = wp_unslash($_POST);

        $data = [
            'link_id' => intval($post_data['link_id'] ?? 0),
            'contact_type' => sanitize_key($post_data['contact_type'] ?? 'email'),
            'direction' => sanitize_key($post_data['direction'] ?? 'outgoing'),
            'subject' => sanitize_text_field($post_data['subject'] ?? ''),
            'content' => sanitize_textarea_field($post_data['content'] ?? ''),
            'contact_date' => sanitize_text_field($post_data['contact_date'] ?? '') ?: current_time('mysql'),
            'reminder_date' => sanitize_text_field($post_data['reminder_date'] ?? '') ?: null,
            'user_id' => get_current_user_id(),
        ];

        $wpdb->insert($wpdb->prefix . 'linktrade_contacts', $data);

        // Last-Contact bei Link aktualisieren
        if ($data['link_id'] > 0) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}linktrade_links
                 SET last_contact_date = %s, contact_count = contact_count + 1
                 WHERE id = %d",
                wp_date('Y-m-d'),
                $data['link_id']
            ));
        }

        wp_send_json_success(['message' => __('Kontakt gespeichert.', 'linktrade-monitor')]);
    }
}
