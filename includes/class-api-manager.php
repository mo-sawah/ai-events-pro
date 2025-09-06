<?php

/**
 * AI Events API Manager with comprehensive debugging and proper settings handling
 */
class AI_Events_API_Manager {

    private $eventbrite_api;
    private $ticketmaster_api;
    
    public function __construct() {
        $this->eventbrite_api = new AI_Events_Eventbrite_API();
        $this->ticketmaster_api = new AI_Events_Ticketmaster_API();
    }
    
    public function get_events($location = '', $radius = 25, $limit = 20) {
        $this->debug_log("=== STARTING EVENT SYNC ===");
        $this->debug_log("Location: $location, Radius: $radius, Limit: $limit");
        
        $all_events = array();
        
        // Get settings
        $general_settings = get_option('ai_events_pro_settings', array());
        $eventbrite_settings = get_option('ai_events_pro_eventbrite_settings', array());
        $ticketmaster_settings = get_option('ai_events_pro_ticketmaster_settings', array());
        $ai_settings = get_option('ai_events_pro_ai_settings', array());
        
        $this->debug_log("General settings found: " . (!empty($general_settings) ? 'Yes' : 'No'));
        $this->debug_log("Eventbrite settings found: " . (!empty($eventbrite_settings) ? 'Yes' : 'No'));
        $this->debug_log("Ticketmaster settings found: " . (!empty($ticketmaster_settings) ? 'Yes' : 'No'));
        
        $enabled_apis = $general_settings['enabled_apis'] ?? array('custom' => true);
        $this->debug_log("Enabled APIs: " . print_r($enabled_apis, true));
        
        // Get events from Eventbrite if enabled and configured
        if (!empty($enabled_apis['eventbrite']) && !empty($eventbrite_settings['private_token'])) {
            $this->debug_log("Fetching Eventbrite events...");
            try {
                $eventbrite_events = $this->eventbrite_api->get_events($location, $radius, $limit);
                $this->debug_log("Eventbrite returned " . count($eventbrite_events) . " events");
                if ($eventbrite_events) {
                    $all_events = array_merge($all_events, $eventbrite_events);
                }
            } catch (Exception $e) {
                $this->debug_log("Eventbrite error: " . $e->getMessage());
            }
        } else {
            if (empty($enabled_apis['eventbrite'])) {
                $this->debug_log("Eventbrite is disabled in settings");
            } else {
                $this->debug_log("Eventbrite is enabled but no private token found");
            }
        }
        
        // Get events from Ticketmaster if enabled and configured
        if (!empty($enabled_apis['ticketmaster']) && !empty($ticketmaster_settings['consumer_key'])) {
            $this->debug_log("Fetching Ticketmaster events...");
            try {
                $ticketmaster_events = $this->ticketmaster_api->get_events($location, $radius, $limit);
                $this->debug_log("Ticketmaster returned " . count($ticketmaster_events) . " events");
                if ($ticketmaster_events) {
                    $all_events = array_merge($all_events, $ticketmaster_events);
                }
            } catch (Exception $e) {
                $this->debug_log("Ticketmaster error: " . $e->getMessage());
            }
        } else {
            if (empty($enabled_apis['ticketmaster'])) {
                $this->debug_log("Ticketmaster is disabled in settings");
            } else {
                $this->debug_log("Ticketmaster is enabled but no consumer key found");
            }
        }
        
        // Get custom events if enabled
        if (!empty($enabled_apis['custom'])) {
            $this->debug_log("Fetching custom events...");
            $custom_events = $this->get_custom_events($location, $radius);
            $this->debug_log("Custom events returned " . count($custom_events) . " events");
            if ($custom_events) {
                $all_events = array_merge($all_events, $custom_events);
            }
        } else {
            $this->debug_log("Custom events are disabled in settings");
        }
        
        $this->debug_log("Total events before AI processing: " . count($all_events));
        
        // Apply AI enhancements if enabled
        if (!empty($ai_settings['enable_ai_features']) && !empty($ai_settings['openrouter_api_key'])) {
            $this->debug_log("Applying AI enhancements...");
            $all_events = $this->enhance_with_ai($all_events, $ai_settings);
        } else {
            $this->debug_log("AI features are disabled or not configured");
        }
        
        // Sort events by date
        if (!empty($all_events)) {
            usort($all_events, function($a, $b) {
                $date_a = !empty($a['date']) ? strtotime($a['date']) : 0;
                $date_b = !empty($b['date']) ? strtotime($b['date']) : 0;
                return $date_a - $date_b;
            });
        }
        
        $final_events = array_slice($all_events, 0, $limit);
        $this->debug_log("Final events count: " . count($final_events));
        $this->debug_log("=== EVENT SYNC COMPLETE ===");
        
        return $final_events;
    }
    
    public function get_custom_events($location = '', $radius = 25) {
        $args = array(
            'post_type' => 'ai_event',
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'meta_query' => array()
        );
        
        // Add location filter if provided
        if (!empty($location)) {
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key' => '_event_location',
                    'value' => $location,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_event_venue',
                    'value' => $location,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_event_city',
                    'value' => $location,
                    'compare' => 'LIKE'
                )
            );
        }
        
        $this->debug_log("Custom events query args: " . print_r($args, true));
        
        $events = get_posts($args);
        $formatted_events = array();
        
        foreach ($events as $event) {
            $event_date = get_post_meta($event->ID, '_event_date', true);
            $event_time = get_post_meta($event->ID, '_event_time', true);
            
            // Skip past events
            if (!empty($event_date) && strtotime($event_date) < strtotime('-1 day')) {
                continue;
            }
            
            $formatted_events[] = array(
                'id' => 'custom_' . $event->ID,
                'title' => $event->post_title,
                'description' => wp_strip_all_tags($event->post_content),
                'date' => $event_date,
                'time' => $event_time,
                'location' => get_post_meta($event->ID, '_event_location', true),
                'venue' => get_post_meta($event->ID, '_event_venue', true),
                'price' => get_post_meta($event->ID, '_event_price', true) ?: 'Free',
                'url' => get_post_meta($event->ID, '_event_url', true) ?: get_permalink($event->ID),
                'image' => get_the_post_thumbnail_url($event->ID, 'large') ?: '',
                'source' => 'custom',
                'category' => $this->get_event_categories($event->ID),
                'organizer' => get_post_meta($event->ID, '_event_organizer', true)
            );
        }
        
        return $formatted_events;
    }
    
    private function get_event_categories($post_id) {
        $categories = get_the_terms($post_id, 'event_category');
        if ($categories && !is_wp_error($categories)) {
            return $categories[0]->name;
        }
        return 'Other';
    }
    
    public function enhance_with_ai($events, $ai_settings) {
        if (empty($ai_settings['openrouter_api_key']) || empty($events)) {
            return $events;
        }
        
        foreach ($events as &$event) {
            if (!empty($ai_settings['ai_categorization'])) {
                $event['ai_category'] = $this->categorize_event($event, $ai_settings['openrouter_api_key']);
            }
            
            if (!empty($ai_settings['ai_summaries']) && strlen($event['description']) > 300) {
                $event['ai_summary'] = $this->generate_summary($event['description'], $ai_settings['openrouter_api_key']);
            }
            
            $event['ai_score'] = $this->calculate_relevance_score($event);
        }
        
        return $events;
    }
    
    private function categorize_event($event, $api_key) {
        $prompt = "Categorize this event into one category: Music, Sports, Arts, Food, Business, Health, Technology, Education, Family, or Other. Event: " . $event['title'];
        return $this->call_openrouter_api($prompt, $api_key, 20);
    }
    
    private function generate_summary($description, $api_key) {
        $prompt = "Summarize this event description in 1-2 sentences: " . substr($description, 0, 500);
        return $this->call_openrouter_api($prompt, $api_key, 150);
    }
    
    private function calculate_relevance_score($event) {
        $score = 50;
        
        if (!empty($event['image'])) $score += 10;
        if (!empty($event['price'])) $score += 5;
        if (!empty($event['venue'])) $score += 5;
        if (!empty($event['organizer'])) $score += 5;
        
        if (!empty($event['date'])) {
            $days_until = (strtotime($event['date']) - time()) / (24 * 60 * 60);
            if ($days_until <= 7) $score += 20;
            elseif ($days_until <= 30) $score += 10;
        }
        
        return min(100, max(0, $score));
    }
    
    private function call_openrouter_api($prompt, $api_key, $max_tokens = 100) {
        $url = 'https://openrouter.ai/api/v1/chat/completions';
        
        $data = array(
            'model' => 'openai/gpt-3.5-turbo',
            'messages' => array(
                array('role' => 'user', 'content' => $prompt)
            ),
            'max_tokens' => $max_tokens,
            'temperature' => 0.7
        );
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return '';
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        return isset($decoded['choices'][0]['message']['content']) 
            ? trim($decoded['choices'][0]['message']['content']) 
            : '';
    }
    
    public function cache_events($events, $location = '') {
        if (empty($events)) {
            $this->debug_log("No events to cache");
            return;
        }
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_events_cache';
        $general_settings = get_option('ai_events_pro_settings', array());
        $cache_duration = $general_settings['cache_duration'] ?? 3600;
        $expires_at = date('Y-m-d H:i:s', time() + $cache_duration);
        
        $this->debug_log("Caching " . count($events) . " events for location: $location");
        
        foreach ($events as $event) {
            $result = $wpdb->replace(
                $table_name,
                array(
                    'event_id' => $event['id'],
                    'source' => $event['source'],
                    'data' => json_encode($event),
                    'location' => $location,
                    'expires_at' => $expires_at,
                    'cached_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s')
            );
            
            if ($result === false) {
                $this->debug_log("Failed to cache event: " . $event['id'] . " - " . $wpdb->last_error);
            }
        }
    }
    
    public function get_cached_events($location = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_events_cache';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT data FROM $table_name WHERE location = %s AND expires_at > NOW() ORDER BY cached_at DESC",
            $location
        ));
        
        $events = array();
        foreach ($results as $result) {
            $event = json_decode($result->data, true);
            if ($event) {
                $events[] = $event;
            }
        }
        
        $this->debug_log("Retrieved " . count($events) . " cached events for location: $location");
        return $events;
    }
    
    // Debug logging method
    private function debug_log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[AI Events Pro] ' . $message);
        }
        
        // Also store in option for admin viewing
        $debug_log = get_option('ai_events_debug_log', array());
        $debug_log[] = array(
            'timestamp' => current_time('mysql'),
            'message' => $message
        );
        
        // Keep only last 50 entries
        $debug_log = array_slice($debug_log, -50);
        update_option('ai_events_debug_log', $debug_log);
    }
    
    // Get debug log for admin
    public function get_debug_log() {
        return get_option('ai_events_debug_log', array());
    }
    
    // Clear debug log
    public function clear_debug_log() {
        delete_option('ai_events_debug_log');
    }
}