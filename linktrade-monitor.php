<?php
/**
 * Plugin Name: Linktrade Monitor
 * Plugin URI: https://github.com/Tribun74/linktrade-monitor
 * Description: Backlink-Verwaltung und -Überwachung für WordPress. Tracking von Linktausch, Linkkauf und kostenlosen Verlinkungen.
 * Version: 1.2.1
 * Author: Frank Stemmler
 * Author URI: https://frank-stemmler.de
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: linktrade-monitor
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * GitHub Plugin URI: https://github.com/Tribun74/linktrade-monitor
 */

// Direktzugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Konstanten
define('LINKTRADE_VERSION', '1.2.1');
define('LINKTRADE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LINKTRADE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LINKTRADE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Auto-Update via GitHub
 *
 * Nutzt Plugin Update Checker Library von YahnisElsts
 * @see https://github.com/YahnisElsts/plugin-update-checker
 */
require_once LINKTRADE_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$linktrade_update_checker = PucFactory::buildUpdateChecker(
    'https://github.com/Tribun74/linktrade-monitor/',
    __FILE__,
    'linktrade-monitor'
);

// Authentifizierung für privates Repo (Read-Only Token)
$linktrade_update_checker->setAuthentication('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');

// GitHub Releases für Updates verwenden (statt Tags)
$linktrade_update_checker->getVcsApi()->enableReleaseAssets();

/**
 * Aktivierung
 */
function linktrade_activate() {
    require_once LINKTRADE_PLUGIN_DIR . 'includes/class-activator.php';
    Linktrade_Activator::activate();
}
register_activation_hook(__FILE__, 'linktrade_activate');

/**
 * Deaktivierung
 */
function linktrade_deactivate() {
    require_once LINKTRADE_PLUGIN_DIR . 'includes/class-deactivator.php';
    Linktrade_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'linktrade_deactivate');

/**
 * Plugin laden
 */
function linktrade_init() {
    require_once LINKTRADE_PLUGIN_DIR . 'includes/class-linktrade.php';
    $plugin = new Linktrade();
    $plugin->run();
}
add_action('plugins_loaded', 'linktrade_init');
