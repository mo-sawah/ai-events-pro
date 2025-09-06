<?php

/**
 * Fixed admin class with proper settings management and debugging
 */
class AI_Events_Admin {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Add AJAX handlers
        add_action('wp_ajax_sync_events', array($this, 'ajax_sync_events'));
        add_action('wp_ajax_test_api_connection', array($this, 'ajax_test_api_connection'));
        add_action('wp_ajax_clear_events_cache', array($this, 'ajax_clear_events_cache'));
        add_action('wp_ajax_get_debug_log', array($this, 'ajax_get_debug_log'));
        add_action('wp_ajax_clear_debug_log', array($this, 'ajax_clear_debug_log'));
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
                'testing_api' => __('Testing API...', 'ai-events-pro'),
                'api_success' => __('Connection successful!', 'ai-events-pro'),
                'api_error' => __('Connection failed!', 'ai-events-pro'),
                'syncing_events' => __('Syncing events...', 'ai-events-pro'),
                'sync_success' => __('Events synced successfully!', 'ai-events-pro'),
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
    }

    public function display_plugin_admin_page() {
        include_once AI_EVENTS_PRO_PLUGIN_DIR . 'admin/views/admin-display.php';
    }

    public function display_plugin_settings_page() {
        include_once AI_EVENTS_PRO_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    public function init_settings() {
        // Register all settings as separate options to prevent overwriting
        register_setting('ai_events_pro_general', 'ai_events_pro_settings', array($this, 'validate_general_settings'));
        register_setting('ai_events_pro_eventbrite', 'ai_events_pro_eventbrite_settings', array($this, 'validate_eventbrite_settings'));
        register_setting('ai_events_pro_ticketmaster', 'ai_events_pro_ticketmaster_settings', array($this, 'validate_ticketmaster_settings'));
        register_setting('ai_events_pro_ai', 'ai_events_pro_ai_settings', array($this, 'validate_ai_settings'));
        
        $this->add_settings_sections();
        $this->add_settings_fields();
    }

    private function add_settings_sections() {
        // General Settings Section
        add_settings_section(
            'ai_events_pro_general_section',
            __('General Settings', 'ai-events-pro'),
            array($this, 'general_section_callback'),
            'ai_events_pro_general'
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
            __('AI Features Configuration', 'ai-events-pro'),
            array($this, 'ai_section_callback'),
            'ai_events_pro_ai'
        );
    }

    private function add_settings_fields() {
        $general_settings = get_option('ai_events_pro_settings', array());
        $eventbrite_settings = get_option('ai_events_pro_eventbrite_settings', array());
        $ticketmaster_settings = get_option('ai_events_pro_ticketmaster_settings', array());
        $ai_settings = get_option('ai_events_pro_ai_settings', array());

        // General settings
        add_settings_field('events_per_page', __('Events Per Page', 'ai-events-pro'), 
            array($this, 'number_field_callback'), 'ai_events_pro_general', 'ai_events_pro_general_section',
            array('option' => 'ai_events_pro_settings', 'field' => 'events_per_page', 'value' => $general_settings['events_per_page'] ?? 12, 'min' => 1, 'max' => 50));

        add_settings_field('default_radius', __('Default Search Radius (miles)', 'ai-events-pro'), 
            array($this, 'number_field_callback'), 'ai_events_pro_general', 'ai_events_pro_general_section',
            array('option' => 'ai_events_pro_settings', 'field' => 'default_radius', 'value' => $general_settings['default_radius'] ?? 25, 'min' => 1, 'max' => 500));

        add_settings_field('cache_duration', __('Cache Duration (hours)', 'ai-events-pro'), 
            array($this, 'number_field_callback'), 'ai_events_pro_general', 'ai_events_pro_general_section',
            array('option' => 'ai_events_pro_settings', 'field' => 'cache_duration', 'value' => ($general_settings['cache_duration'] ?? 3600) / 3600, 'min' => 1, 'max' => 24));

        add_settings_field('enable_geolocation', __('Enable Auto-Location Detection', 'ai-events-pro'), 
            array($this, 'checkbox_field_callback'), 'ai_events_pro_general', 'ai_events_pro_general_section',
            array('option' => 'ai_events_pro_settings', 'field' => 'enable_geolocation', 'value' => $general_settings['enable_geolocation'] ?? true));

        // API Selection
        add_settings_field('enabled_apis', __('Enabled Event Sources', 'ai-events-pro'), 
            array($this, 'api_selection_callback'), 'ai_events_pro_general', 'ai_events_pro_general_section',
            array('option' => 'ai_events_pro_settings', 'value' => $general_settings));

        // Eventbrite settings - Using only Private Token
        add_settings_field('eventbrite_private_token', __('Private Token', 'ai-events-pro'), 
            array($this, 'password_field_callback'), 'ai_events_pro_eventbrite', 'ai_events_pro_eventbrite_section',
            array('option' => 'ai_events_pro_eventbrite_settings', 'field' => 'private_token', 'value' => $eventbrite_settings['private_token'] ?? '', 
                  'description' => __('Your Eventbrite Private Token (Personal OAuth token)', 'ai-events-pro'), 'api_type' => 'eventbrite'));

        // Ticketmaster settings - Using Consumer Key
        add_settings_field('ticketmaster_consumer_key', __('Consumer Key (API Key)', 'ai-events-pro'), 
            array($this, 'password_field_callback'), 'ai_events_pro_ticketmaster', 'ai_events_pro_ticketmaster_section',
            array('option' => 'ai_events_pro_ticketmaster_settings', 'field' => 'consumer_key', 'value' => $ticketmaster_settings['consumer_key'] ?? '', 
                  'description' => __('Your Ticketmaster Consumer Key from Developer Portal', 'ai-events-pro'), 'api_type' => 'ticketmaster'));

        // AI settings
        add_settings_field('enable_ai_features', __('Enable AI Features', 'ai-events-pro'), 
            array($this, 'checkbox_field_callback'), 'ai_events_pro_ai', 'ai_events_pro_ai_section',
            array('option' => 'ai_events_pro_ai_settings', 'field' => 'enable_ai_features', 'value' => $ai_settings['enable_ai_features'] ?? false));

        add_settings_field('openrouter_api_key', __('OpenRouter API Key', 'ai-events-pro'), 
            array($this, 'password_field_callback'), 'ai_events_pro_ai', 'ai_events_pro_ai_section',
            array('option' => 'ai_events_pro_ai_settings', 'field' => 'openrouter_api_key', 'value' => $ai_settings['openrouter_api_key'] ?? '', 
                  'description' => __('Required for AI features like categorization and summaries', 'ai-events-pro'), 'api_type' => 'openrouter'));

        add_settings_field('ai_categorization', __('AI Event Categorization', 'ai-events-pro'), 
            array($this, 'checkbox_field_callback'), 'ai_events_pro_ai', 'ai_events_pro_ai_section',
            array('option' => 'ai_events_pro_ai_settings', 'field' => 'ai_categorization', 'value' => $ai_settings['ai_categorization'] ?? false));

        add_settings_field('ai_summaries', __('AI Event Summaries', 'ai-events-pro'), 
            array($this, 'checkbox_field_callback'), 'ai_events_pro_ai', 'ai_events_pro_ai_section',
            array('option' => 'ai_events_pro_ai_settings', 'field' => 'ai_summaries', 'value' => $ai_settings['ai_summaries'] ?? false));
    }

    public function general_section_callback() {
        echo '<p>' . __('Configure general plugin settings and choose which event sources to enable.', 'ai-events-pro') . '</p>';
    }

    public function eventbrite_section_callback() {
        echo '<div class="api-info">';
        echo '<p>' . __('To get your Eventbrite Private Token:', 'ai-events-pro') . '</p>';
        echo '<ol>';
        echo '<li>' . __('Go to <a href="https://www.eventbrite.com/platform/api#/introduction/authentication" target="_blank">Eventbrite API</a>', 'ai-events-pro') . '</li>';
        echo '<li>' . __('Click "Create Private Token"', 'ai-events-pro') . '</li>';
        echo '<li>' . __('Copy the generated token and paste it below', 'ai-events-pro') . '</li>';
        echo '</ol>';
        echo '</div>';
    }

    public function ticketmaster_section_callback() {
        echo '<div class="api-info">';
        echo '<p>' . __('To get your Ticketmaster Consumer Key:', 'ai-events-pro') . '</p>';
        echo '<ol>';
        echo '<li>' . __('Go to <a href="https://developer.ticketmaster.com/products-and-docs/apis/getting-started/" target="_blank">Ticketmaster Developer Portal</a>', 'ai-events-pro') . '</li>';
        echo '<li>' . __('Create a new app or use existing one', 'ai-events-pro') . '</li>';
        echo '<li>' . __('Copy the Consumer Key and paste it below', 'ai-events-pro') . '</li>';
        echo '</ol>';
        echo '</div>';
    }

    public function ai_section_callback() {
        echo '<p>' . __('Configure AI-powered features to enhance your events with smart categorization and summaries.', 'ai-events-pro') . '</p>';
    }

    public function number_field_callback($args) {
        $option = $args['option'];
        $field = $args['field'];
        $value = $args['value'];
        $min = $args['min'] ?? '';
        $max = $args['max'] ?? '';
        
        printf(
            '<input type="number" id="%s_%s" name="%s[%s]" value="%s" min="%s" max="%s" class="regular-text" />',
            esc_attr($option), esc_attr($field), esc_attr($option), esc_attr($field), 
            esc_attr($value), esc_attr($min), esc_attr($max)
        );
    }

    public function checkbox_field_callback($args) {
        $option = $args['option'];
        $field = $args['field'];
        $value = $args['value'];
        
        printf(
            '<input type="checkbox" id="%s_%s" name="%s[%s]" value="1" %s />',
            esc_attr($option), esc_attr($field), esc_attr($option), esc_attr($field), 
            checked(1, $value, false)
        );
    }

    public function password_field_callback($args) {
        $option = $args['option'];
        $field = $args['field'];
        $value = $args['value'];
        $description = $args['description'] ?? '';
        $api_type = $args['api_type'] ?? '';
        
        printf(
            '<input type="password" id="%s_%s" name="%s[%s]" value="%s" class="regular-text" />
            <button type="button" class="button test-api-btn" data-api="%s" data-option="%s" data-field="%s">%s</button>',
            esc_attr($option), esc_attr($field), esc_attr($option), esc_attr($field), 
            esc_attr($value), esc_attr($api_type), esc_attr($option), esc_attr($field),
            __('Test Connection', 'ai-events-pro')
        );
        
        if (!empty($description)) {
            echo '<p class="description">' . wp_kses_post($description) . '</p>';
        }
    }

    public function api_selection_callback($args) {
        $option = $args['option'];
        $settings = $args['value'];
        
        $apis = array(
            'eventbrite' => __('Eventbrite', 'ai-events-pro'),
            'ticketmaster' => __('Ticketmaster', 'ai-events-pro'),
            'custom' => __('Custom Events', 'ai-events-pro')
        );
        
        echo '<fieldset>';
        foreach ($apis as $api_key => $api_name) {
            $checked = isset($settings['enabled_apis'][$api_key]) ? $settings['enabled_apis'][$api_key] : ($api_key === 'custom');
            printf(
                '<label><input type="checkbox" name="%s[enabled_apis][%s]" value="1" %s /> %s</label><br>',
                esc_attr($option), esc_attr($api_key), checked(1, $checked, false), esc_html($api_name)
            );
        }
        echo '</fieldset>';
        echo '<p class="description">' . __('Select which event sources to use for importing events.', 'ai-events-pro') . '</p>';
    }

    // Validation methods
    public function validate_general_settings($input) {
        $sanitized = array();
        
        $sanitized['events_per_page'] = absint($input['events_per_page'] ?? 12);
        if ($sanitized['events_per_page'] < 1 || $sanitized['events_per_page'] > 50) {
            $sanitized['events_per_page'] = 12;
        }
        
        $sanitized['default_radius'] = absint($input['default_radius'] ?? 25);
        if ($sanitized['default_radius'] < 1 || $sanitized['default_radius'] > 500) {
            $sanitized['default_radius'] = 25;
        }
        
        $sanitized['cache_duration'] = absint($input['cache_duration'] ?? 1) * 3600;
        
        $sanitized['enable_geolocation'] = !empty($input['enable_geolocation']);
        
        // Handle enabled APIs
        $sanitized['enabled_apis'] = array();
        if (isset($input['enabled_apis']) && is_array($input['enabled_apis'])) {
            foreach ($input['enabled_apis'] as $api => $enabled) {
                $sanitized['enabled_apis'][$api] = !empty($enabled);
            }
        } else {
            $sanitized['enabled_apis']['custom'] = true;
        }
        
        return $sanitized;
    }

    public function validate_eventbrite_settings($input) {
        $sanitized = array();
        $sanitized['private_token'] = sanitize_text_field($input['private_token'] ?? '');
        return $sanitized;
    }

    public function validate_ticketmaster_settings($input) {
        $sanitized = array();
        $sanitized['consumer_key'] = sanitize_text_field($input['consumer_key'] ?? '');
        return $sanitized;
    }

    public function validate_ai_settings($input) {
        $sanitized = array();
        $sanitized['enable_ai_features'] = !empty($input['enable_ai_features']);
        $sanitized['openrouter_api_key'] = sanitize_text_field($input['openrouter_api_key'] ?? '');
        $sanitized['ai_categorization'] = !empty($input['ai_categorization']);
        $sanitized['ai_summaries'] = !empty($input['ai_summaries']);
        return $sanitized;
    }

    // AJAX Handlers
    public function ajax_test_api_connection() {
        check_ajax_referer('ai_events_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'ai-events-pro'));
        }
        
        $api_type = sanitize_text_field($_POST['api_type'] ?? '');
        $option_name = sanitize_text_field($_POST['option_name'] ?? '');
        $field_name = sanitize_text_field($_POST['field_name'] ?? '');
        
        $field_id = $option_name . '_' . $field_name;
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (empty($api_key)) {
            wp_send_json_error(__('Please enter an API key.', 'ai-events-pro'));
        }
        
        switch ($api_type) {
            case 'eventbrite':
                $result = $this->test_eventbrite_connection($api_key);
                break;
            case 'ticketmaster':
                $result = $this->test_ticketmaster_connection($api_key);
                break;
            case 'openrouter':
                $result = $this->test_openrouter_connection($api_key);
                break;
            default:
                wp_send_json_error(__('Unknown API type.', 'ai-events-pro'));
        }
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    public function ajax_sync_events() {
        check_ajax_referer('ai_events_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'ai-events-pro'));
        }
        
        $location = sanitize_text_field($_POST['location'] ?? '');
        $radius = absint($_POST['radius'] ?? 25);
        $limit = absint($_POST['limit'] ?? 50);
        
        if (empty($location)) {
            wp_send_json_error(__('Please enter a location.', 'ai-events-pro'));
        }
        
        try {
            $api_manager = new AI_Events_API_Manager();
            
            // Get debug info
            $debug_info = $this->get_sync_debug_info();
            
            // Try to get events
            $events = $api_manager->get_events($location, $radius, $limit);
            
            if (!empty($events)) {
                // Cache the events
                $api_manager->cache_events($events, $location);
                
                wp_send_json_success(array(
                    'message' => sprintf(__('Successfully synced %d events from enabled sources.', 'ai-events-pro'), count($events)),
                    'events_count' => count($events),
                    'events_preview' => array_slice($events, 0, 3),
                    'debug_info' => $debug_info,
                    'sources_used' => $this->get_sources_from_events($events)
                ));
            } else {
                // No events found - provide detailed debug info
                $debug_log = $api_manager->get_debug_log();
                $last_log_entries = array_slice($debug_log, -10);
                
                wp_send_json_error(array(
                    'message' => __('No events found. Check debug info below.', 'ai-events-pro'),
                    'debug_info' => $debug_info,
                    'debug_log' => $last_log_entries,
                    'suggestions' => $this->get_troubleshooting_suggestions($debug_info)
                ));
            }
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Error: ', 'ai-events-pro') . $e->getMessage(),
                'debug_info' => $this->get_sync_debug_info(),
                'error_details' => array(
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                )
            ));
        }
    }

    public function ajax_clear_events_cache() {
        check_ajax_referer('ai_events_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'ai-events-pro'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_events_cache';
        
        $deleted = $wpdb->query("DELETE FROM $table_name");
        
        if ($deleted !== false) {
            wp_send_json_success(sprintf(__('Cache cleared. %d entries removed.', 'ai-events-pro'), $deleted));
        } else {
            wp_send_json_error(__('Failed to clear cache.', 'ai-events-pro'));
        }
    }

    public function ajax_get_debug_log() {
        check_ajax_referer('ai_events_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'ai-events-pro'));
        }
        
        $api_manager = new AI_Events_API_Manager();
        $debug_log = $api_manager->get_debug_log();
        
        wp_send_json_success(array(
            'debug_log' => $debug_log,
            'total_entries' => count($debug_log)
        ));
    }

    public function ajax_clear_debug_log() {
        check_ajax_referer('ai_events_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'ai-events-pro'));
        }
        
        $api_manager = new AI_Events_API_Manager();
        $api_manager->clear_debug_log();
        
        wp_send_json_success(__('Debug log cleared.', 'ai-events-pro'));
    }

    private function get_sync_debug_info() {
        // Get all settings
        $general_settings = get_option('ai_events_pro_settings', array());
        $eventbrite_settings = get_option('ai_events_pro_eventbrite_settings', array());
        $ticketmaster_settings = get_option('ai_events_pro_ticketmaster_settings', array());
        $ai_settings = get_option('ai_events_pro_ai_settings', array());
        
        $debug_info = array(
            'enabled_apis' => $general_settings['enabled_apis'] ?? array(),
            'api_status' => array(
                'eventbrite' => array(
                    'enabled' => !empty($general_settings['enabled_apis']['eventbrite']),
                    'configured' => !empty($eventbrite_settings['private_token']),
                    'token_length' => !empty($eventbrite_settings['private_token']) ? strlen($eventbrite_settings['private_token']) : 0
                ),
                'ticketmaster' => array(
                    'enabled' => !empty($general_settings['enabled_apis']['ticketmaster']),
                    'configured' => !empty($ticketmaster_settings['consumer_key']),
                    'key_length' => !empty($ticketmaster_settings['consumer_key']) ? strlen($ticketmaster_settings['consumer_key']) : 0
                ),
                'custom' => array(
                    'enabled' => !empty($general_settings['enabled_apis']['custom']),
                    'events_count' => wp_count_posts('ai_event')->publish ?? 0
                ),
                'ai' => array(
                    'enabled' => !empty($ai_settings['enable_ai_features']),
                    'configured' => !empty($ai_settings['openrouter_api_key'])
                )
            ),
            'settings_saved' => array(
                'general' => !empty($general_settings),
                'eventbrite' => !empty($eventbrite_settings),
                'ticketmaster' => !empty($ticketmaster_settings),
                'ai' => !empty($ai_settings)
            )
        );
        
        return $debug_info;
    }

    private function get_sources_from_events($events) {
        $sources = array();
        foreach ($events as $event) {
            $source = $event['source'] ?? 'unknown';
            if (!isset($sources[$source])) {
                $sources[$source] = 0;
            }
            $sources[$source]++;
        }
        return $sources;
    }

    private function get_troubleshooting_suggestions($debug_info) {
        $suggestions = array();
        
        // Check if any APIs are enabled
        $enabled_apis = $debug_info['enabled_apis'] ?? array();
        $has_enabled_apis = array_filter($enabled_apis);
        
        if (empty($has_enabled_apis)) {
            $suggestions[] = "❌ No event sources are enabled. Go to General settings and enable at least one source (Eventbrite, Ticketmaster, or Custom Events).";
        }
        
        // Check Eventbrite
        if (!empty($enabled_apis['eventbrite'])) {
            if (!$debug_info['api_status']['eventbrite']['configured']) {
                $suggestions[] = "❌ Eventbrite is enabled but no Private Token is configured. Add your Eventbrite Private Token in the Eventbrite settings tab.";
            } elseif ($debug_info['api_status']['eventbrite']['token_length'] < 20) {
                $suggestions[] = "⚠️ Eventbrite Private Token seems too short. Make sure you copied the complete token.";
            } else {
                $suggestions[] = "✅ Eventbrite appears configured correctly.";
            }
        }
        
        // Check Ticketmaster
        if (!empty($enabled_apis['ticketmaster'])) {
            if (!$debug_info['api_status']['ticketmaster']['configured']) {
                $suggestions[] = "❌ Ticketmaster is enabled but no Consumer Key is configured. Add your Ticketmaster Consumer Key in the Ticketmaster settings tab.";
            } elseif ($debug_info['api_status']['ticketmaster']['key_length'] < 10) {
                $suggestions[] = "⚠️ Ticketmaster Consumer Key seems too short. Make sure you copied the complete key.";
            } else {
                $suggestions[] = "✅ Ticketmaster appears configured correctly.";
            }
        }
        
        // Check Custom Events
        if (!empty($enabled_apis['custom'])) {
            if ($debug_info['api_status']['custom']['events_count'] == 0) {
                $suggestions[] = "ℹ️ Custom Events is enabled but no published events found. Create some events or try a broader location search.";
            } else {
                $suggestions[] = "✅ Custom Events: Found " . $debug_info['api_status']['custom']['events_count'] . " published events.";
            }
        }
        
        if (empty($suggestions)) {
            $suggestions[] = "Try testing your API connections individually using the 'Test Connection' buttons.";
            $suggestions[] = "Try a different location (e.g., 'New York, NY' instead of just 'New York').";
            $suggestions[] = "Increase the search radius to find more events.";
        }
        
        return $suggestions;
    }

    // API Test Methods
    private function test_eventbrite_connection($token) {
        $url = 'https://www.eventbriteapi.com/v3/users/me/';
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            $name = $data['name'] ?? 'Unknown';
            return array('success' => true, 'message' => sprintf(__('Eventbrite connected successfully as: %s', 'ai-events-pro'), $name));
        }
        
        return array('success' => false, 'message' => __('Eventbrite connection failed. Check your token.', 'ai-events-pro'));
    }

    private function test_ticketmaster_connection($api_key) {
        $url = 'https://app.ticketmaster.com/discovery/v2/events.json?apikey=' . $api_key . '&size=1';
        
        $response = wp_remote_get($url, array('timeout' => 15));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            return array('success' => true, 'message' => __('Ticketmaster connected successfully!', 'ai-events-pro'));
        }
        
        return array('success' => false, 'message' => __('Ticketmaster connection failed. Check your consumer key.', 'ai-events-pro'));
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
            return array('success' => true, 'message' => __('OpenRouter connected successfully!', 'ai-events-pro'));
        }
        
        return array('success' => false, 'message' => __('OpenRouter connection failed. Check your API key.', 'ai-events-pro'));
    }
}