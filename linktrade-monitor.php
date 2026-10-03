<?php
/**
 * Plugin Name: Linktrade Monitor: Backlink Tracker for Link Exchanges
 * Plugin URI: https://www.3task.de/en/linktrade-monitor/
 * Description: Monitors your backlinks and link exchanges in both directions. Weekly checks, email alerts when a link disappears or turns nofollow, check history. Self-hosted.
 * Version: 1.4.0
 * Author: 3task
 * Author URI: https://www.3task.de
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: linktrade-monitor
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Tested up to: 7.1
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Is Linktrade Monitor Pro active on this site?
 *
 * Pro contains everything this plugin does and uses the same classes and
 * tables. Loading both would end in a fatal error, so this plugin steps
 * aside whenever Pro is active, whichever of the two is loaded first.
 *
 * @return bool
 */
function linktrade_pro_is_active() {
	if ( defined( 'LINKTRADE_PRO_VERSION' ) ) {
		return true;
	}

	$pro = 'linktrade-monitor-pro/linktrade-monitor-pro.php';

	if ( in_array( $pro, (array) get_option( 'active_plugins', array() ), true ) ) {
		return true;
	}

	if ( is_multisite() ) {
		$network = (array) get_site_option( 'active_sitewide_plugins', array() );
		return isset( $network[ $pro ] );
	}

	return false;
}

if ( linktrade_pro_is_active() ) {
	add_action(
		'admin_notices',
		function () {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			if ( ! current_user_can( 'activate_plugins' ) || ! $screen || 'plugins' !== $screen->id ) {
				return;
			}
			echo '<div class="notice notice-info"><p>';
			echo esc_html__( 'Linktrade Monitor Pro is active and includes everything the free plugin does. The free plugin is switched off while Pro runs. Your links are kept, whichever of the two you remove.', 'linktrade-monitor' );
			echo '</p></div>';
		}
	);
	return;
}

// Plugin constants.
define( 'LINKTRADE_VERSION', '1.4.0' );
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

	// The table can also come from the Pro plugin or from a version number
	// that is already current: make sure the columns of 1.4.0 are there.
	if ( '1.4.0' !== get_option( 'linktrade_columns' ) ) {
		require_once LINKTRADE_PLUGIN_DIR . 'includes/class-activator.php';
		Linktrade_Activator::ensure_columns();
		update_option( 'linktrade_columns', '1.4.0', false );

		// Links that were checked by an older version or by the Pro plugin:
		// 1.4.0 recognises more (sponsored, ugc, robots variants). The first
		// run records that without mailing it as if it had just happened.
		global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time check on the plugin's own table.
		if ( $wpdb->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $wpdb->prefix . 'linktrade_links' ) . '` WHERE last_check IS NOT NULL' ) ) {
			update_option( 'linktrade_baseline_run', 1, false );
		}
	}
}
add_action( 'admin_init', 'linktrade_check_version' );

/**
 * Should the bundled German texts be used?
 *
 * The plugin follows the language of the site (and of the logged-in user).
 * Translations from translate.wordpress.org are loaded by WordPress itself.
 * Until a language pack exists, German sites get the bundled German texts.
 * Who had switched the plugin to German by hand before 1.4.0 keeps German.
 *
 * @return bool
 */
function linktrade_use_bundled_german() {
	static $use = null;

	if ( null === $use ) {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		$use    = ( 0 === strpos( (string) $locale, 'de' ) ) || ( 'de' === get_option( 'linktrade_language', '' ) );
	}

	return $use;
}

/**
 * Hook the bundled German texts in.
 */
function linktrade_load_textdomain() {
	if ( linktrade_use_bundled_german() ) {
		add_filter( 'gettext', 'linktrade_translate_fallback', 10, 3 );
		add_filter( 'ngettext', 'linktrade_translate_ngettext_fallback', 10, 5 );
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
		$file         = LINKTRADE_PLUGIN_DIR . 'languages/translations-de.php';
		$translations = file_exists( $file ) ? include $file : array();
	}

	// A translation that WordPress already loaded (language pack) wins.
	if ( $translation !== $text ) {
		return $translation;
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

	$text = ( 1 === (int) $number ) ? $single : $plural;

	if ( $translation !== $text ) {
		return $translation;
	}

	return linktrade_translate_fallback( $text, $text, $domain );
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
 * Address of the Pro page, German for German sites and English for everyone else.
 *
 * @return string
 */
function linktrade_pro_url() {
	$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
	if ( 0 === strpos( (string) $locale, 'de' ) ) {
		return 'https://www.3task.de/linktrade-monitor-pro/';
	}
	return 'https://www.3task.de/en/linktrade-monitor/';
}

/**
 * Add settings link on plugins page.
 *
 * @param array $links Existing links.
 * @return array Modified links.
 */
function linktrade_plugin_action_links( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=linktrade-monitor' ) ) . '">' . esc_html__( 'Settings', 'linktrade-monitor' ) . '</a>';
	array_unshift( $links, $settings_link );
	$links[] = '<a href="' . esc_url( linktrade_pro_url() ) . '" target="_blank" rel="noopener">' . esc_html__( 'Pro version', 'linktrade-monitor' ) . '</a>';
	return $links;
}
add_filter( 'plugin_action_links_' . LINKTRADE_PLUGIN_BASENAME, 'linktrade_plugin_action_links' );
