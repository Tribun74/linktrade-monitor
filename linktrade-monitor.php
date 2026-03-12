<?php
/**
 * Plugin Name: Linktrade Monitor
 * Plugin URI: https://wordpress.org/plugins/linktrade-monitor/
 * Description: Backlink management and monitoring for WordPress. Track link exchanges, paid links, and free backlinks.
 * Version: 1.3.1
 * Author: 3task
 * Author URI: https://www.3task.de
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: linktrade-monitor
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Tested up to: 6.9
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants.
define( 'LINKTRADE_VERSION', '1.3.1' );
define( 'LINKTRADE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LINKTRADE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LINKTRADE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );


/**
 * Activation.
 */
function linktrade_activate() {
    require_once LINKTRADE_PLUGIN_DIR . 'includes/class-activator.php';
    Linktrade_Activator::activate();
}
register_activation_hook( __FILE__, 'linktrade_activate' );

/**
 * Deactivation.
 */
function linktrade_deactivate() {
    require_once LINKTRADE_PLUGIN_DIR . 'includes/class-deactivator.php';
    Linktrade_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'linktrade_deactivate' );

/**
 * Check for plugin updates and run migrations if needed.
 */
function linktrade_check_version() {
    $current_version = get_option( 'linktrade_version', '0.0.0' );

    if ( version_compare( $current_version, LINKTRADE_VERSION, '<' ) ) {
        require_once LINKTRADE_PLUGIN_DIR . 'includes/class-activator.php';
        Linktrade_Activator::activate();
    }
}
add_action( 'admin_init', 'linktrade_check_version' );

/**
 * Load plugin textdomain based on manual language setting.
 */
function linktrade_load_textdomain() {
	$language = get_option( 'linktrade_language', 'en' );

	// Only load translation if German is selected.
	if ( 'de' === $language ) {
		$mo_file = LINKTRADE_PLUGIN_DIR . 'languages/linktrade-monitor-de_DE.mo';

		// Try to load .mo file first.
		if ( file_exists( $mo_file ) ) {
			load_textdomain( 'linktrade-monitor', $mo_file );
		} else {
			// Fallback: Use PHP translations array.
			add_filter( 'gettext', 'linktrade_translate_fallback', 10, 3 );
			add_filter( 'ngettext', 'linktrade_translate_ngettext_fallback', 10, 5 );
		}
	}
}
add_action( 'init', 'linktrade_load_textdomain', 1 );

/**
 * Fallback translation function when .mo file is not available.
 *
 * @param string $translation Translated text.
 * @param string $text        Original text.
 * @param string $domain      Text domain.
 * @return string Translated text.
 */
function linktrade_translate_fallback( $translation, $text, $domain ) {
	if ( 'linktrade-monitor' !== $domain ) {
		return $translation;
	}

	static $translations = null;
	if ( null === $translations ) {
		$file = LINKTRADE_PLUGIN_DIR . 'languages/translations-de.php';
		$translations = file_exists( $file ) ? include $file : array();
	}

	return isset( $translations[ $text ] ) ? $translations[ $text ] : $translation;
}

/**
 * Fallback for plural translations.
 *
 * @param string $translation Translated text.
 * @param string $single      Singular form.
 * @param string $plural      Plural form.
 * @param int    $number      Number for plural.
 * @param string $domain      Text domain.
 * @return string Translated text.
 */
function linktrade_translate_ngettext_fallback( $translation, $single, $plural, $number, $domain ) {
	if ( 'linktrade-monitor' !== $domain ) {
		return $translation;
	}

	$text = ( 1 === $number ) ? $single : $plural;
	return linktrade_translate_fallback( $translation, $text, $domain );
}

/**
 * Initialize plugin.
 */
function linktrade_init() {
	require_once LINKTRADE_PLUGIN_DIR . 'includes/class-linktrade.php';
	$linktrade_plugin = new Linktrade();
	$linktrade_plugin->run();
}
add_action( 'plugins_loaded', 'linktrade_init' );

/**
 * Add settings link on plugins page.
 *
 * @param array $links Existing links.
 * @return array Modified links.
 */
function linktrade_plugin_action_links( $links ) {
    $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=linktrade-monitor' ) ) . '">' . esc_html__( 'Settings', 'linktrade-monitor' ) . '</a>';
    $pro_link = '<a href="https://www.3task.de/linktrade-monitor-pro/" target="_blank" style="color:#0073aa;font-weight:600;">' . esc_html__( 'Go Pro', 'linktrade-monitor' ) . '</a>';
    array_unshift( $links, $pro_link );
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . LINKTRADE_PLUGIN_BASENAME, 'linktrade_plugin_action_links' );

/**
 * Register custom cron intervals.
 *
 * @param array $schedules Existing schedules.
 * @return array Modified schedules.
 */
function linktrade_add_cron_interval( $schedules ) {
    $schedules['monthly'] = array(
        'interval' => 30 * DAY_IN_SECONDS,
        'display'  => esc_html__( 'Once a Month', 'linktrade-monitor' ),
    );
    return $schedules;
}
add_filter( 'cron_schedules', 'linktrade_add_cron_interval' );

