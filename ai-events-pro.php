<?php
/**
 * Plugin Name: AI Events Pro
 * Plugin URI: https://github.com/mo-sawah/ai-events-pro
 * Description: Advanced WordPress events management plugin with AI-powered features, Eventbrite and Ticketmaster integration.
 * Version: 2.0.0
 * Author: Mo Sawah
 * Author URI: https://github.com/mo-sawah
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-events-pro
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('AI_EVENTS_PRO_VERSION', '2.0.0');
define('AI_EVENTS_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_EVENTS_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_EVENTS_PRO_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class AI_Events_Pro {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->version = AI_EVENTS_PRO_VERSION;
        $this->plugin_name = 'ai-events-pro';
        
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        // Core classes
        require_once AI_EVENTS_PRO_PLUGIN_DIR . 'includes/class-loader.php';
        require_once AI_EVENTS_PRO_PLUGIN_DIR . 'includes/class-i18n.php';
        require_once AI_EVENTS_PRO_PLUGIN_DIR . 'includes/class-activator.php';
        require_once AI_EVENTS_PRO_PLUGIN_DIR . 'includes/class-deactivator.php';
        
        // API classes
        require_once AI_EVENTS_PRO_PLUGIN_DIR . 'includes/class-api-manager.php';
        require_once AI_EVENTS_PRO_PLUGIN_DIR . 'includes/class-eventbrite-api.php';
        require_once AI_EVENTS_PRO_PLUGIN_DIR . 'includes/class-ticketmaster-api.php';
        
        // Admin classes
        require_once AI_EVENTS_PRO_PLUGIN_DIR . 'admin/class-admin.php';
        
        // Public classes
        require_once AI_EVENTS_PRO_PLUGIN_DIR . 'public/class-public.php';
        require_once AI_EVENTS_PRO_PLUGIN_DIR . 'public/class-shortcodes.php';
        
        $this->loader = new AI_Events_Loader();
    }

    private function set_locale() {
        $plugin_i18n = new AI_Events_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    private function define_admin_hooks() {
        $plugin_admin = new AI_Events_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'init_settings');
    }

    private function define_public_hooks() {
        $plugin_public = new AI_Events_Public($this->get_plugin_name(), $this->get_version());
        $plugin_shortcodes = new AI_Events_Shortcodes();

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('init', $plugin_public, 'register_post_types');
        $this->loader->add_action('init', $plugin_shortcodes, 'register_shortcodes');
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_loader() {
        return $this->loader;
    }

    public function get_version() {
        return $this->version;
    }
}

// Plugin activation/deactivation hooks
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

// Initialize the plugin
function run_ai_events_pro() {
    $plugin = new AI_Events_Pro();
    $plugin->run();
}
run_ai_events_pro();