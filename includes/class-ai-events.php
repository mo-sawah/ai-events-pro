<?php

/**
 * The core plugin class.
 */
class AI_Events {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->version = AI_EVENTS_PRO_VERSION;
        $this->plugin_name = AI_EVENTS_PRO_PLUGIN_NAME;

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once AI_EVENTS_PRO_PLUGIN_DIR . 'includes/class-activator.php';
        require_once AI_EVENTS_PRO_PLUGIN_DIR . 'includes/class-deactivator.php';
        require_once AI_EVENTS_PRO_PLUGIN_DIR . 'includes/class-api-manager.php';
        require_once AI_EVENTS_PRO_PLUGIN_DIR . 'includes/class-eventbrite-api.php';
        require_once AI_EVENTS_PRO_PLUGIN_DIR . 'includes/class-ticketmaster-api.php';
        require_once AI_EVENTS_PRO_PLUGIN_DIR . 'includes/class-shortcode.php';
        require_once AI_EVENTS_PRO_PLUGIN_DIR . 'includes/class-event-post-type.php';
        require_once AI_EVENTS_PRO_PLUGIN_DIR . 'admin/class-admin.php';
        require_once AI_EVENTS_PRO_PLUGIN_DIR . 'admin/class-event-manager.php';
        require_once AI_EVENTS_PRO_PLUGIN_DIR . 'public/class-public.php';
    }

    private function set_locale() {
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
    }

    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'ai-events-pro',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }

    private function define_admin_hooks() {
        $plugin_admin = new AI_Events_Admin($this->plugin_name, $this->version);
        $event_manager = new AI_Events_Event_Manager();

        add_action('admin_enqueue_scripts', array($plugin_admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($plugin_admin, 'enqueue_scripts'));
        add_action('admin_menu', array($plugin_admin, 'add_plugin_admin_menu'));
        add_action('admin_init', array($plugin_admin, 'init_settings'));
        
        // Custom post type
        $post_type = new AI_Events_Post_Type();
        add_action('init', array($post_type, 'register_post_type'));
        add_action('init', array($post_type, 'register_taxonomies'));
    }

    private function define_public_hooks() {
        $plugin_public = new AI_Events_Public($this->plugin_name, $this->version);
        $shortcode = new AI_Events_Shortcode();

        add_action('wp_enqueue_scripts', array($plugin_public, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($plugin_public, 'enqueue_scripts'));
        add_action('init', array($shortcode, 'init_shortcodes'));
        add_action('template_redirect', array($plugin_public, 'template_redirect'));
        add_action('wp_ajax_get_events', array($plugin_public, 'ajax_get_events'));
        add_action('wp_ajax_nopriv_get_events', array($plugin_public, 'ajax_get_events'));
        add_action('wp_ajax_toggle_theme_mode', array($plugin_public, 'ajax_toggle_theme_mode'));
        add_action('wp_ajax_nopriv_toggle_theme_mode', array($plugin_public, 'ajax_toggle_theme_mode'));
    }

    public function run() {
        // Plugin is running
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }
}