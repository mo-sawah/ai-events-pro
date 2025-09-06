<?php
/**
 * Plugin Name: AI Events Pro
 * Plugin URI: https://sawahsolutions.com
 * Description: A comprehensive WordPress events plugin with modern features, AI integration, and event aggregation from major APIs.
 * Version: 2.0.18
 * Author: Mohamed Sawah
 * Author URI: https://sawahsolutions.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-events-pro
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('AI_EVENTS_PRO_VERSION', '2.0.18');
define('AI_EVENTS_PRO_PLUGIN_NAME', 'ai-events-pro');
define('AI_EVENTS_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_EVENTS_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_EVENTS_PRO_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Activation and deactivation hooks
function activate_ai_events_pro() {
    require_once AI_EVENTS_PRO_PLUGIN_DIR . 'includes/class-activator.php';
    AI_Events_Activator::activate();
}

function deactivate_ai_events_pro() {
    require_once AI_EVENTS_PRO_PLUGIN_DIR . 'includes/class-deactivator.php';
    AI_Events_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_ai_events_pro');
register_deactivation_hook(__FILE__, 'deactivate_ai_events_pro');

// The core plugin class
require AI_EVENTS_PRO_PLUGIN_DIR . 'includes/class-ai-events.php';

// Begin execution of the plugin
function run_ai_events_pro() {
    $plugin = new AI_Events();
    $plugin->run();
}
run_ai_events_pro();