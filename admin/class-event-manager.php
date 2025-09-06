<?php

/**
 * Updated Event Manager with proper AJAX handlers
 */
class AI_Events_Event_Manager {

    public function __construct() {
        // AJAX handlers are now in the Admin class
        // This class handles business logic for events
    }

    public function get_events_statistics() {
        global $wpdb;
        
        $stats = array();
        
        // Total custom events
        $custom_events_query = new WP_Query(array(
            'post_type' => 'ai_event',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids'
        ));
        $stats['custom_events'] = $custom_events_query->found_posts;
        
        // Cached events from APIs
        $table_name = $wpdb->prefix . 'ai_events_cache';
        $cached_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE expires_at > NOW()");
        $stats['cached_events'] = $cached_count ? $cached_count : 0;
        
        // Events by source
        $eventbrite_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE source = 'eventbrite' AND expires_at > NOW()");
        $stats['eventbrite_events'] = $eventbrite_count ? $eventbrite_count : 0;
        
        $ticketmaster_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE source = 'ticketmaster' AND expires_at > NOW()");
        $stats['ticketmaster_events'] = $ticketmaster_count ? $ticketmaster_count : 0;
        
        // Recent events (last 30 days)
        $recent_events_query = new WP_Query(array(
            'post_type' => 'ai_event',
            'post_status' => 'publish',
            'date_query' => array(
                array(
                    'after' => '30 days ago'
                )
            ),
            'posts_per_page' => 1,
            'fields' => 'ids'
        ));
        $stats['recent_events'] = $recent_events_query->found_posts;
        
        return $stats;
    }

    public function create_event_from_csv($event_data) {
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
                $category = trim($category);
                $term = get_term_by('name', $category, 'event_category');
                if (!$term) {
                    $term_result = wp_insert_term($category, 'event_category');
                    if (!is_wp_error($term_result)) {
                        $term_ids[] = $term_result['term_id'];
                    }
                } else {
                    $term_ids[] = $term->term_id;
                }
            }
            
            if (!empty($term_ids)) {
                wp_set_post_terms($post_id, $term_ids, 'event_category');
            }
        }
        
        return $post_id;
    }

    public function bulk_import_events($csv_file_path) {
        if (!file_exists($csv_file_path)) {
            return array('success' => false, 'message' => 'CSV file not found');
        }

        $csv_data = file_get_contents($csv_file_path);
        if (empty($csv_data)) {
            return array('success' => false, 'message' => 'CSV file is empty');
        }

        $lines = explode("\n", $csv_data);
        if (count($lines) < 2) {
            return array('success' => false, 'message' => 'CSV file must have at least a header and one data row');
        }

        $headers = str_getcsv($lines[0]);
        $imported_count = 0;
        $errors = array();

        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;
            
            $row = str_getcsv($line);
            if (count($row) !== count($headers)) {
                $errors[] = "Line " . ($i + 1) . ": Column count mismatch";
                continue;
            }
            
            $event_data = array_combine($headers, $row);
            
            $result = $this->create_event_from_csv($event_data);
            if ($result) {
                $imported_count++;
            } else {
                $errors[] = "Line " . ($i + 1) . ": Failed to create event";
            }
        }

        $message = sprintf('Successfully imported %d events.', $imported_count);
        if (!empty($errors)) {
            $message .= ' ' . count($errors) . ' errors occurred.';
        }

        return array(
            'success' => true,
            'message' => $message,
            'imported' => $imported_count,
            'errors' => $errors
        );
    }

    public function export_events_csv() {
        $events = get_posts(array(
            'post_type' => 'ai_event',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        $csv_data = array();
        $headers = array(
            'title', 'description', 'date', 'time', 'location', 
            'venue', 'price', 'url', 'organizer', 'category'
        );
        
        $csv_data[] = $headers;

        foreach ($events as $event) {
            $categories = get_the_terms($event->ID, 'event_category');
            $category_names = array();
            if ($categories && !is_wp_error($categories)) {
                foreach ($categories as $category) {
                    $category_names[] = $category->name;
                }
            }

            $row = array(
                $event->post_title,
                wp_strip_all_tags($event->post_content),
                get_post_meta($event->ID, '_event_date', true),
                get_post_meta($event->ID, '_event_time', true),
                get_post_meta($event->ID, '_event_location', true),
                get_post_meta($event->ID, '_event_venue', true),
                get_post_meta($event->ID, '_event_price', true),
                get_post_meta($event->ID, '_event_url', true),
                get_post_meta($event->ID, '_event_organizer', true),
                implode(', ', $category_names)
            );
            
            $csv_data[] = $row;
        }

        return $csv_data;
    }

    public function clean_expired_cache() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_events_cache';
        $deleted = $wpdb->query("DELETE FROM $table_name WHERE expires_at <= NOW()");
        
        return $deleted !== false ? $deleted : 0;
    }

    public function get_api_status() {
        $status = array();

        // General settings for enabled flags
        $general_settings = get_option('ai_events_pro_settings', array());
        $enabled_apis = $general_settings['enabled_apis'] ?? array();

        // Eventbrite
        $eventbrite_settings = get_option('ai_events_pro_eventbrite_settings', array());
        $eventbrite_token = $eventbrite_settings['private_token'] ?? '';
        $status['eventbrite'] = array(
            'configured' => !empty($eventbrite_token),
            'status' => !empty($eventbrite_token) ? 'connected' : 'not_configured',
            'enabled' => !empty($enabled_apis['eventbrite'])
        );

        // Ticketmaster
        $ticketmaster_settings = get_option('ai_events_pro_ticketmaster_settings', array());
        $ticketmaster_key = $ticketmaster_settings['consumer_key'] ?? '';
        $status['ticketmaster'] = array(
            'configured' => !empty($ticketmaster_key),
            'status' => !empty($ticketmaster_key) ? 'connected' : 'not_configured',
            'enabled' => !empty($enabled_apis['ticketmaster'])
        );

        // OpenRouter / AI
        $ai_settings = get_option('ai_events_pro_ai_settings', array());
        $openrouter_key = $ai_settings['openrouter_api_key'] ?? '';
        $status['openrouter'] = array(
            'configured' => !empty($openrouter_key),
            'status' => !empty($openrouter_key) ? 'connected' : 'not_configured',
            'enabled' => !empty($ai_settings['enable_ai_features'])
        );

        // Custom
        $status['custom'] = array(
            'configured' => true,
            'status' => 'connected',
            'enabled' => !empty($enabled_apis['custom'])
        );
        
        return $status;
    }

    public function schedule_cleanup_job() {
        if (!wp_next_scheduled('ai_events_pro_cleanup_cache')) {
            wp_schedule_event(time(), 'daily', 'ai_events_pro_cleanup_cache');
        }
    }

    public function unschedule_cleanup_job() {
        wp_clear_scheduled_hook('ai_events_pro_cleanup_cache');
    }
}

// Schedule cleanup on activation
add_action('wp', function() {
    $event_manager = new AI_Events_Event_Manager();
    $event_manager->schedule_cleanup_job();
});

// Handle cleanup cron job
add_action('ai_events_pro_cleanup_cache', function() {
    $event_manager = new AI_Events_Event_Manager();
    $cleaned = $event_manager->clean_expired_cache();
    error_log("AI Events Pro: Cleaned {$cleaned} expired cache entries");
});
