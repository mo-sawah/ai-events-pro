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
        
        // Add AJAX handlers for sync events
        add_action('wp_ajax_sync_events', array($this, 'ajax_sync_events'));
        add_action('wp_ajax_test_api_connection', array($this, 'ajax_test_api_connection'));
        add_action('wp_ajax_clear_events_cache', array($this, 'ajax_clear_events_cache'));
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
                'syncing_events' => __('Syncing events...', 'ai-events-pro'),
                'sync_success' => __('Events synced successfully!', 'ai-events-pro'),
                'sync_error' => __('Failed to sync events.', 'ai-events-pro'),
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

        // Eventbrite API settings
        register_setting('ai_events_pro_api_group', 'ai_events_pro_eventbrite_api_key');
        register_setting('ai_events_pro_api_group', 'ai_events_pro_eventbrite_client_secret');
        register_setting('ai_events_pro_api_group', 'ai_events_pro_eventbrite_private_token');
        register_setting('ai_events_pro_api_group', 'ai_events_pro_eventbrite_public_token');

        // Ticketmaster API settings
        register_setting('ai_events_pro_api_group', 'ai_events_pro_ticketmaster_consumer_key');
        register_setting('ai_events_pro_api_group', 'ai_events_pro_ticketmaster_consumer_secret');

        // OpenRouter API
        register_setting('ai_events_pro_api_group', 'ai_events_pro_openrouter_key');

        // General Settings Section
        add_settings_section(
            'ai_events_pro_general_section',
            __('General Settings', 'ai-events-pro'),
            array($this, 'general_section_callback'),
            'ai_events_pro_settings'
        );

        // Eventbrite API Section
        add_settings_section(
            'ai_events_pro_eventbrite_section',
            __('Eventbrite API Configuration', 'ai-events-pro'),
            array($this, 'eventbrite_section_callback'),
            'ai_events_pro_eventbrite'
        );

        // Ticketmaster API Section
        add_settings_section(
            'ai_events_pro_ticketmaster_section',
            __('Ticketmaster API Configuration', 'ai-events-pro'),
            array($this, 'ticketmaster_section_callback'),
            'ai_events_pro_ticketmaster'
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

        // Eventbrite API fields
        add_settings_field(
            'eventbrite_api_key',
            __('API Key', 'ai-events-pro'),
            array($this, 'password_field_callback'),
            'ai_events_pro_eventbrite',
            'ai_events_pro_eventbrite_section',
            array(
                'field' => 'eventbrite_api_key',
                'option_name' => 'ai_events_pro_eventbrite_api_key',
                'value' => get_option('ai_events_pro_eventbrite_api_key', ''),
                'description' => __('Your Eventbrite API Key', 'ai-events-pro')
            )
        );

        add_settings_field(
            'eventbrite_client_secret',
            __('Client Secret', 'ai-events-pro'),
            array($this, 'password_field_callback'),
            'ai_events_pro_eventbrite',
            'ai_events_pro_eventbrite_section',
            array(
                'field' => 'eventbrite_client_secret',
                'option_name' => 'ai_events_pro_eventbrite_client_secret',
                'value' => get_option('ai_events_pro_eventbrite_client_secret', ''),
                'description' => __('Your Eventbrite Client Secret', 'ai-events-pro')
            )
        );

        add_settings_field(
            'eventbrite_private_token',
            __('Private Token', 'ai-events-pro'),
            array($this, 'password_field_callback'),
            'ai_events_pro_eventbrite',
            'ai_events_pro_eventbrite_section',
            array(
                'field' => 'eventbrite_private_token',
                'option_name' => 'ai_events_pro_eventbrite_private_token',
                'value' => get_option('ai_events_pro_eventbrite_private_token', ''),
                'description' => __('Your Eventbrite Private Token (most commonly used)', 'ai-events-pro')
            )
        );

        add_settings_field(
            'eventbrite_public_token',
            __('Public Token', 'ai-events-pro'),
            array($this, 'password_field_callback'),
            'ai_events_pro_eventbrite',
            'ai_events_pro_eventbrite_section',
            array(
                'field' => 'eventbrite_public_token',
                'option_name' => 'ai_events_pro_eventbrite_public_token',
                'value' => get_option('ai_events_pro_eventbrite_public_token', ''),
                'description' => __('Your Eventbrite Public Token', 'ai-events-pro')
            )
        );

        // Ticketmaster API fields
        add_settings_field(
            'ticketmaster_consumer_key',
            __('Consumer Key', 'ai-events-pro'),
            array($this, 'password_field_callback'),
            'ai_events_pro_ticketmaster',
            'ai_events_pro_ticketmaster_section',
            array(
                'field' => 'ticketmaster_consumer_key',
                'option_name' => 'ai_events_pro_ticketmaster_consumer_key',
                'value' => get_option('ai_events_pro_ticketmaster_consumer_key', ''),
                'description' => __('Your Ticketmaster Consumer Key', 'ai-events-pro')
            )
        );

        add_settings_field(
            'ticketmaster_consumer_secret',
            __('Consumer Secret', 'ai-events-pro'),
            array($this, 'password_field_callback'),
            'ai_events_pro_ticketmaster',
            'ai_events_pro_ticketmaster_section',
            array(
                'field' => 'ticketmaster_consumer_secret',
                'option_name' => 'ai_events_pro_ticketmaster_consumer_secret',
                'value' => get_option('ai_events_pro_ticketmaster_consumer_secret', ''),
                'description' => __('Your Ticketmaster Consumer Secret', 'ai-events-pro')
            )
        );

        // OpenRouter API field
        add_settings_field(
            'openrouter_key',
            __('OpenRouter API Key', 'ai-events-pro'),
            array($this, 'password_field_callback'),
            'ai_events_pro_ai',
            'ai_events_pro_ai_section',
            array(
                'field' => 'openrouter_key',
                'option_name' => 'ai_events_pro_openrouter_key',
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

    public function eventbrite_section_callback() {
        echo '<p>' . __('Configure your Eventbrite API credentials. Get your credentials from <a href="https://www.eventbrite.com/platform/api#/introduction/authentication" target="_blank">Eventbrite API</a>. The Private Token is most commonly used for API access.', 'ai-events-pro') . '</p>';
    }

    public function ticketmaster_section_callback() {
        echo '<p>' . __('Configure your Ticketmaster API credentials. Get your credentials from <a href="https://developer.ticketmaster.com/products-and-docs/apis/getting-started/" target="_blank">Ticketmaster Developer Portal</a>.', 'ai-events-pro') . '</p>';
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
        $option_name = $args['option_name'];
        $value = $args['value'];
        $description = $args['description'] ?? '';
        
        printf(
            '<input type="password" id="%s" name="%s" value="%s" class="regular-text" />
            <button type="button" class="button test-api-btn" data-api="%s" data-option="%s">%s</button>',
            esc_attr($field),
            esc_attr($option_name),
            esc_attr($value),
            esc_attr($field),
            esc_attr($option_name),
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

    // AJAX Handlers
    public function ajax_sync_events() {
        check_ajax_referer('ai_events_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have sufficient permissions.', 'ai-events-pro'));
        }
        
        $location = sanitize_text_field($_POST['location'] ?? '');
        $radius = absint($_POST['radius'] ?? 25);
        $limit = absint($_POST['limit'] ?? 50);
        
        if (empty($location)) {
            wp_send_json_error(__('Please enter a location.', 'ai-events-pro'));
        }
        
        try {
            $api_manager = new AI_Events_API_Manager();
            $events = $api_manager->get_events($location, $radius, $limit);
            
            if (!empty($events)) {
                // Cache the events
                $api_manager->cache_events($events, $location);
                
                wp_send_json_success(array(
                    'message' => sprintf(__('Successfully synced %d events.', 'ai-events-pro'), count($events)),
                    'events_count' => count($events),
                    'events' => array_slice($events, 0, 5) // Return first 5 for preview
                ));
            } else {
                wp_send_json_error(__('No events found. Please check your API credentials and location.', 'ai-events-pro'));
            }
        } catch (Exception $e) {
            wp_send_json_error(__('API Error: ', 'ai-events-pro') . $e->getMessage());
        }
    }

    public function ajax_test_api_connection() {
        check_ajax_referer('ai_events_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have sufficient permissions.', 'ai-events-pro'));
        }
        
        $api_type = sanitize_text_field($_POST['api_type']);
        $option_name = sanitize_text_field($_POST['option_name']);
        $api_key = sanitize_text_field($_POST['api_key']);
        
        if (empty($api_key)) {
            wp_send_json_error(__('Please enter an API key.', 'ai-events-pro'));
        }
        
        $result = $this->test_api_connection_by_type($api_type, $api_key, $option_name);
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    private function test_api_connection_by_type($api_type, $api_key, $option_name) {
        switch ($option_name) {
            case 'ai_events_pro_eventbrite_private_token':
                return $this->test_eventbrite_connection($api_key);
                
            case 'ai_events_pro_ticketmaster_consumer_key':
                return $this->test_ticketmaster_connection($api_key);
                
            case 'ai_events_pro_openrouter_key':
                return $this->test_openrouter_connection($api_key);
                
            default:
                return array('success' => false, 'message' => __('Unknown API type.', 'ai-events-pro'));
        }
    }

    private function test_eventbrite_connection($token) {
        $url = 'https://www.eventbriteapi.com/v3/users/me/';
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token
            ),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code === 200) {
            $data = json_decode($body, true);
            $user_name = isset($data['name']) ? $data['name'] : 'Unknown';
            return array('success' => true, 'message' => sprintf(__('Eventbrite connection successful! Connected as: %s', 'ai-events-pro'), $user_name));
        } else {
            $error_data = json_decode($body, true);
            $error_message = isset($error_data['error_description']) ? $error_data['error_description'] : __('Invalid token', 'ai-events-pro');
            return array('success' => false, 'message' => __('Eventbrite connection failed: ', 'ai-events-pro') . $error_message);
        }
    }

    private function test_ticketmaster_connection($api_key) {
        $url = 'https://app.ticketmaster.com/discovery/v2/events.json?apikey=' . $api_key . '&size=1';
        
        $response = wp_remote_get($url, array('timeout' => 15));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code === 200) {
            return array('success' => true, 'message' => __('Ticketmaster connection successful!', 'ai-events-pro'));
        } else {
            $error_data = json_decode($body, true);
            $error_message = isset($error_data['fault']['faultstring']) ? $error_data['fault']['faultstring'] : __('Invalid API key', 'ai-events-pro');
            return array('success' => false, 'message' => __('Ticketmaster connection failed: ', 'ai-events-pro') . $error_message);
        }
    }

    private function test_openrouter_connection($api_key) {
        $url = 'https://openrouter.ai/api/v1/models';
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key
            ),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            return array('success' => true, 'message' => __('OpenRouter connection successful!', 'ai-events-pro'));
        } else {
            return array('success' => false, 'message' => __('OpenRouter connection failed. Please check your API key.', 'ai-events-pro'));
        }
    }

    public function ajax_clear_events_cache() {
        check_ajax_referer('ai_events_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have sufficient permissions.', 'ai-events-pro'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_events_cache';
        
        $deleted = $wpdb->query("DELETE FROM $table_name");
        
        if ($deleted !== false) {
            wp_send_json_success(sprintf(__('Cache cleared successfully. %d entries removed.', 'ai-events-pro'), $deleted));
        } else {
            wp_send_json_error(__('Failed to clear cache.', 'ai-events-pro'));
        }
    }
}