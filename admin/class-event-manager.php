<?php

/**
 * Custom event management functionality.
 */
class AI_Events_Event_Manager {

    public function __construct() {
        add_action('wp_ajax_sync_events', array($this, 'sync_events'));
        add_action('wp_ajax_test_api_connection', array($this, 'test_api_connection'));
        add_action('wp_ajax_clear_events_cache', array($this, 'clear_events_cache'));
        add_action('wp_ajax_bulk_import_events', array($this, 'bulk_import_events'));
    }

    public function sync_events() {
        check_ajax_referer('ai_events_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $location = sanitize_text_field($_POST['location'] ?? '');
        $radius = absint($_POST['radius'] ?? 25);
        $limit = absint($_POST['limit'] ?? 50);
        
        $api_manager = new AI_Events_API_Manager();
        $events = $api_manager->get_events($location, $radius, $limit);
        
        if (!empty($events)) {
            // Cache the events
            $api_manager->cache_events($events, $location);
            
            wp_send_json_success(array(
                'message' => sprintf(__('Successfully synced %d events.', 'ai-events-pro'), count($events)),
                'events_count' => count($events)
            ));
        } else {
            wp_send_json_error(__('No events found or API error occurred.', 'ai-events-pro'));
        }
    }

    public function test_api_connection() {
        check_ajax_referer('ai_events_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $api_type = sanitize_text_field($_POST['api_type']);
        $api_key = sanitize_text_field($_POST['api_key']);
        
        switch ($api_type) {
            case 'eventbrite_token':
                $result = $this->test_eventbrite_connection($api_key);
                break;
            case 'ticketmaster_key':
                $result = $this->test_ticketmaster_connection($api_key);
                break;
            case 'openrouter_key':
                $result = $this->test_openrouter_connection($api_key);
                break;
            default:
                wp_send_json_error(__('Invalid API type.', 'ai-events-pro'));
                return;
        }
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
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
        
        if ($status_code === 200) {
            return array('success' => true, 'message' => __('Eventbrite connection successful!', 'ai-events-pro'));
        } else {
            return array('success' => false, 'message' => __('Eventbrite connection failed. Please check your token.', 'ai-events-pro'));
        }
    }

    private function test_ticketmaster_connection($api_key) {
        $url = 'https://app.ticketmaster.com/discovery/v2/events.json?apikey=' . $api_key . '&size=1';
        
        $response = wp_remote_get($url, array('timeout' => 15));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            return array('success' => true, 'message' => __('Ticketmaster connection successful!', 'ai-events-pro'));
        } else {
            return array('success' => false, 'message' => __('Ticketmaster connection failed. Please check your API key.', 'ai-events-pro'));
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

    public function clear_events_cache() {
        check_ajax_referer('ai_events_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
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

    public function bulk_import_events() {
        check_ajax_referer('ai_events_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        if (!isset($_FILES['csv_file'])) {
            wp_send_json_error(__('No file uploaded.', 'ai-events-pro'));
        }
        
        $file = $_FILES['csv_file'];
        
        if ($file['type'] !== 'text/csv' && $file['type'] !== 'application/vnd.ms-excel') {
            wp_send_json_error(__('Please upload a CSV file.', 'ai-events-pro'));
        }
        
        $csv_data = file_get_contents($file['tmp_name']);
        $lines = explode("\n", $csv_data);
        
        if (empty($lines)) {
            wp_send_json_error(__('The CSV file is empty.', 'ai-events-pro'));
        }
        
        $headers = str_getcsv($lines[0]);
        $imported_count = 0;
        
        for ($i = 1; $i < count($lines); $i++) {
            if (empty(trim($lines[$i]))) continue;
            
            $row = str_getcsv($lines[$i]);
            $event_data = array_combine($headers, $row);
            
            if ($this->create_event_from_csv($event_data)) {
                $imported_count++;
            }
        }
        
        wp_send_json_success(sprintf(__('Successfully imported %d events.', 'ai-events-pro'), $imported_count));
    }

    private function create_event_from_csv($event_data) {
        $required_fields = array('title', 'date');
        
        foreach ($required_fields as $field) {
            if (empty($event_data[$field])) {
                return false;
            }
        }
        
        $post_data = array(
            'post_title' => sanitize_text_field($event_data['title']),
            'post_content' => wp_kses_post($event_data['description'] ?? ''),
            'post_status' => 'publish',
            'post_type' => 'ai_event',
            'post_author' => get_current_user_id()
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (!$post_id || is_wp_error($post_id)) {
            return false;
        }
        
        // Add meta fields
        $meta_fields = array(
            'event_date' => sanitize_text_field($event_data['date'] ?? ''),
            'event_time' => sanitize_text_field($event_data['time'] ?? ''),
            'event_location' => sanitize_text_field($event_data['location'] ?? ''),
            'event_venue' => sanitize_text_field($event_data['venue'] ?? ''),
            'event_price' => sanitize_text_field($event_data['price'] ?? ''),
            'event_url' => esc_url_raw($event_data['url'] ?? ''),
            'event_organizer' => sanitize_text_field($event_data['organizer'] ?? '')
        );
        
        foreach ($meta_fields as $key => $value) {
            if (!empty($value)) {
                update_post_meta($post_id, '_' . $key, $value);
            }
        }
        
        // Set categories if provided
        if (!empty($event_data['category'])) {
            $categories = explode(',', $event_data['category']);
            $term_ids = array();
            
            foreach ($categories as $category) {
                $term = get_term_by('name', trim($category), 'event_category');
                if (!$term) {
                    $term = wp_insert_term(trim($category), 'event_category');
                    if (!is_wp_error($term)) {
                        $term_ids[] = $term['term_id'];
                    }
                } else {
                    $term_ids[] = $term->term_id;
                }
            }
            
            if (!empty($term_ids)) {
                wp_set_post_terms($post_id, $term_ids, 'event_category');
            }
        }
        
        return true;
    }

    public function get_events_statistics() {
        global $wpdb;
        
        $stats = array();
        
        // Total custom events
        $stats['custom_events'] = wp_count_posts('ai_event')->publish;
        
        // Cached events from APIs
        $table_name = $wpdb->prefix . 'ai_events_cache';
        $stats['cached_events'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE expires_at > NOW()");
        
        // Events by source
        $stats['eventbrite_events'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE source = 'eventbrite' AND expires_at > NOW()");
        $stats['ticketmaster_events'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE source = 'ticketmaster' AND expires_at > NOW()");
        
        // Recent events (last 30 days)
        $stats['recent_events'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ai_event' AND post_status = 'publish' AND post_date >= %s",
            date('Y-m-d', strtotime('-30 days'))
        ));
        
        return $stats;
    }
}