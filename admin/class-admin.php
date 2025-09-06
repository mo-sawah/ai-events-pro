<?php

/**
 * The admin-specific functionality of the plugin.
 */
class AI_Events_Admin {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            AI_EVENTS_PRO_PLUGIN_URL . 'admin/css/ai-events-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            AI_EVENTS_PRO_PLUGIN_URL . 'admin/js/ai-events-admin.js',
            array('jquery'),
            $this->version,
            false
        );

        wp_localize_script($this->plugin_name, 'ai_events_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_events_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this event?', 'ai-events-pro'),
                'testing_api' => __('Testing API connection...', 'ai-events-pro'),
                'api_success' => __('API connection successful!', 'ai-events-pro'),
                'api_error' => __('API connection failed. Please check your credentials.', 'ai-events-pro'),
            )
        ));
    }

    public function add_plugin_admin_menu() {
        add_menu_page(
            __('AI Events Pro', 'ai-events-pro'),
            __('AI Events Pro', 'ai-events-pro'),
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_admin_page'),
            'dashicons-calendar-alt',
            30
        );

        add_submenu_page(
            $this->plugin_name,
            __('Settings', 'ai-events-pro'),
            __('Settings', 'ai-events-pro'),
            'manage_options',
            $this->plugin_name . '-settings',
            array($this, 'display_plugin_settings_page')
        );

        add_submenu_page(
            $this->plugin_name,
            __('Add Event', 'ai-events-pro'),
            __('Add Event', 'ai-events-pro'),
            'manage_options',
            'post-new.php?post_type=ai_event'
        );

        add_submenu_page(
            $this->plugin_name,
            __('All Events', 'ai-events-pro'),
            __('All Events', 'ai-events-pro'),
            'manage_options',
            'edit.php?post_type=ai_event'
        );

        add_submenu_page(
            $this->plugin_name,
            __('Analytics', 'ai-events-pro'),
            __('Analytics', 'ai-events-pro'),
            'manage_options',
            $this->plugin_name . '-analytics',
            array($this, 'display_analytics_page')
        );
    }

    public function display_plugin_admin_page() {
        include_once AI_EVENTS_PRO_PLUGIN_DIR . 'admin/views/admin-display.php';
    }

    public function display_plugin_settings_page() {
        include_once AI_EVENTS_PRO_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    public function display_analytics_page() {
        include_once AI_EVENTS_PRO_PLUGIN_DIR . 'admin/views/analytics-page.php';
    }

    public function init_settings() {
        register_setting(
            'ai_events_pro_settings_group',
            'ai_events_pro_settings',
            array($this, 'validate_settings')
        );

        register_setting(
            'ai_events_pro_api_group',
            'ai_events_pro_eventbrite_token'
        );

        register_setting(
            'ai_events_pro_api_group',
            'ai_events_pro_ticketmaster_key'
        );

        register_setting(
            'ai_events_pro_api_group',
            'ai_events_pro_openrouter_key'
        );

        // General Settings Section
        add_settings_section(
            'ai_events_pro_general_section',
            __('General Settings', 'ai-events-pro'),
            array($this, 'general_section_callback'),
            'ai_events_pro_settings'
        );

        // API Settings Section
        add_settings_section(
            'ai_events_pro_api_section',
            __('API Configuration', 'ai-events-pro'),
            array($this, 'api_section_callback'),
            'ai_events_pro_api'
        );

        // AI Settings Section
        add_settings_section(
            'ai_events_pro_ai_section',
            __('AI Features', 'ai-events-pro'),
            array($this, 'ai_section_callback'),
            'ai_events_pro_ai'
        );

        $this->add_settings_fields();
    }

    private function add_settings_fields() {
        $settings = get_option('ai_events_pro_settings', array());

        // General settings fields
        add_settings_field(
            'events_per_page',
            __('Events Per Page', 'ai-events-pro'),
            array($this, 'number_field_callback'),
            'ai_events_pro_settings',
            'ai_events_pro_general_section',
            array(
                'field' => 'events_per_page',
                'value' => $settings['events_per_page'] ?? 12,
                'min' => 1,
                'max' => 50
            )
        );

        add_settings_field(
            'enable_geolocation',
            __('Enable Geolocation', 'ai-events-pro'),
            array($this, 'checkbox_field_callback'),
            'ai_events_pro_settings',
            'ai_events_pro_general_section',
            array(
                'field' => 'enable_geolocation',
                'value' => $settings['enable_geolocation'] ?? true
            )
        );

        add_settings_field(
            'default_radius',
            __('Default Search Radius (miles)', 'ai-events-pro'),
            array($this, 'number_field_callback'),
            'ai_events_pro_settings',
            'ai_events_pro_general_section',
            array(
                'field' => 'default_radius',
                'value' => $settings['default_radius'] ?? 25,
                'min' => 1,
                'max' => 500
            )
        );

        add_settings_field(
            'cache_duration',
            __('Cache Duration (seconds)', 'ai-events-pro'),
            array($this, 'number_field_callback'),
            'ai_events_pro_settings',
            'ai_events_pro_general_section',
            array(
                'field' => 'cache_duration',
                'value' => $settings['cache_duration'] ?? 3600,
                'min' => 300,
                'max' => 86400
            )
        );

        // API settings fields
        add_settings_field(
            'eventbrite_token',
            __('Eventbrite Private Token', 'ai-events-pro'),
            array($this, 'password_field_callback'),
            'ai_events_pro_api',
            'ai_events_pro_api_section',
            array(
                'field' => 'eventbrite_token',
                'value' => get_option('ai_events_pro_eventbrite_token', ''),
                'description' => __('Get your token from <a href="https://www.eventbrite.com/platform/api#/introduction/authentication" target="_blank">Eventbrite API</a>', 'ai-events-pro')
            )
        );

        add_settings_field(
            'ticketmaster_key',
            __('Ticketmaster API Key', 'ai-events-pro'),
            array($this, 'password_field_callback'),
            'ai_events_pro_api',
            'ai_events_pro_api_section',
            array(
                'field' => 'ticketmaster_key',
                'value' => get_option('ai_events_pro_ticketmaster_key', ''),
                'description' => __('Get your key from <a href="https://developer.ticketmaster.com/products-and-docs/apis/getting-started/" target="_blank">Ticketmaster Developer Portal</a>', 'ai-events-pro')
            )
        );

        add_settings_field(
            'openrouter_key',
            __('OpenRouter API Key', 'ai-events-pro'),
            array($this, 'password_field_callback'),
            'ai_events_pro_api',
            'ai_events_pro_api_section',
            array(
                'field' => 'openrouter_key',
                'value' => get_option('ai_events_pro_openrouter_key', ''),
                'description' => __('Get your key from <a href="https://openrouter.ai/" target="_blank">OpenRouter</a> for AI features', 'ai-events-pro')
            )
        );

        // AI settings fields
        add_settings_field(
            'enable_ai_features',
            __('Enable AI Features', 'ai-events-pro'),
            array($this, 'checkbox_field_callback'),
            'ai_events_pro_ai',
            'ai_events_pro_ai_section',
            array(
                'field' => 'enable_ai_features',
                'value' => $settings['enable_ai_features'] ?? true
            )
        );

        add_settings_field(
            'ai_categorization',
            __('AI Event Categorization', 'ai-events-pro'),
            array($this, 'checkbox_field_callback'),
            'ai_events_pro_ai',
            'ai_events_pro_ai_section',
            array(
                'field' => 'ai_categorization',
                'value' => $settings['ai_categorization'] ?? true
            )
        );

        add_settings_field(
            'ai_summaries',
            __('AI Event Summaries', 'ai-events-pro'),
            array($this, 'checkbox_field_callback'),
            'ai_events_pro_ai',
            'ai_events_pro_ai_section',
            array(
                'field' => 'ai_summaries',
                'value' => $settings['ai_summaries'] ?? true
            )
        );
    }

    public function general_section_callback() {
        echo '<p>' . __('Configure general plugin settings.', 'ai-events-pro') . '</p>';
    }

    public function api_section_callback() {
        echo '<p>' . __('Configure API keys for event sources and AI features.', 'ai-events-pro') . '</p>';
    }

    public function ai_section_callback() {
        echo '<p>' . __('Configure AI-powered features for enhanced event discovery.', 'ai-events-pro') . '</p>';
    }

    public function number_field_callback($args) {
        $field = $args['field'];
        $value = $args['value'];
        $min = $args['min'] ?? '';
        $max = $args['max'] ?? '';
        
        printf(
            '<input type="number" id="%s" name="ai_events_pro_settings[%s]" value="%s" min="%s" max="%s" class="regular-text" />',
            esc_attr($field),
            esc_attr($field),
            esc_attr($value),
            esc_attr($min),
            esc_attr($max)
        );
    }

    public function checkbox_field_callback($args) {
        $field = $args['field'];
        $value = $args['value'];
        
        printf(
            '<input type="checkbox" id="%s" name="ai_events_pro_settings[%s]" value="1" %s />',
            esc_attr($field),
            esc_attr($field),
            checked(1, $value, false)
        );
    }

    public function password_field_callback($args) {
        $field = $args['field'];
        $value = $args['value'];
        $description = $args['description'] ?? '';
        $option_name = $field === 'eventbrite_token' ? 'ai_events_pro_eventbrite_token' : 
                      ($field === 'ticketmaster_key' ? 'ai_events_pro_ticketmaster_key' : 'ai_events_pro_openrouter_key');
        
        printf(
            '<input type="password" id="%s" name="%s" value="%s" class="regular-text" />
            <button type="button" class="button test-api-btn" data-api="%s">%s</button>',
            esc_attr($field),
            esc_attr($option_name),
            esc_attr($value),
            esc_attr($field),
            __('Test Connection', 'ai-events-pro')
        );
        
        if (!empty($description)) {
            echo '<p class="description">' . wp_kses_post($description) . '</p>';
        }
    }

    public function validate_settings($input) {
        $sanitized = array();
        
        if (isset($input['events_per_page'])) {
            $sanitized['events_per_page'] = absint($input['events_per_page']);
            if ($sanitized['events_per_page'] < 1 || $sanitized['events_per_page'] > 50) {
                $sanitized['events_per_page'] = 12;
            }
        }
        
        if (isset($input['enable_geolocation'])) {
            $sanitized['enable_geolocation'] = (bool) $input['enable_geolocation'];
        }
        
        if (isset($input['default_radius'])) {
            $sanitized['default_radius'] = absint($input['default_radius']);
            if ($sanitized['default_radius'] < 1 || $sanitized['default_radius'] > 500) {
                $sanitized['default_radius'] = 25;
            }
        }
        
        if (isset($input['cache_duration'])) {
            $sanitized['cache_duration'] = absint($input['cache_duration']);
            if ($sanitized['cache_duration'] < 300 || $sanitized['cache_duration'] > 86400) {
                $sanitized['cache_duration'] = 3600;
            }
        }
        
        $sanitized['enable_ai_features'] = isset($input['enable_ai_features']) ? (bool) $input['enable_ai_features'] : false;
        $sanitized['ai_categorization'] = isset($input['ai_categorization']) ? (bool) $input['ai_categorization'] : false;
        $sanitized['ai_summaries'] = isset($input['ai_summaries']) ? (bool) $input['ai_summaries'] : false;
        $sanitized['theme_mode'] = isset($input['theme_mode']) ? sanitize_text_field($input['theme_mode']) : 'auto';
        $sanitized['enable_eventbrite'] = isset($input['enable_eventbrite']) ? (bool) $input['enable_eventbrite'] : false;
        $sanitized['enable_ticketmaster'] = isset($input['enable_ticketmaster']) ? (bool) $input['enable_ticketmaster'] : false;
        
        return $sanitized;
    }
}