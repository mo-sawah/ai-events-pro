<?php

/**
 * Updated API manager with proper settings handling
 */
class AI_Events_API_Manager {

    private $eventbrite_api;
    private $ticketmaster_api;
    
    public function __construct() {
        $this->eventbrite_api = new AI_Events_Eventbrite_API();
        $this->ticketmaster_api = new AI_Events_Ticketmaster_API();
    }
    
    public function get_events($location = '', $radius = 25, $limit = 20) {
        $all_events = array();
        $general_settings = get_option('ai_events_pro_settings', array());
        $enabled_apis = $general_settings['enabled_apis'] ?? array();
        
        // Get events from Eventbrite if enabled
        if (!empty($enabled_apis['eventbrite'])) {
            $eventbrite_events = $this->eventbrite_api->get_events($location, $radius, $limit);
            if ($eventbrite_events) {
                $all_events = array_merge($all_events, $eventbrite_events);
            }
        }
        
        // Get events from Ticketmaster if enabled
        if (!empty($enabled_apis['ticketmaster'])) {
            $ticketmaster_events = $this->ticketmaster_api->get_events($location, $radius, $limit);
            if ($ticketmaster_events) {
                $all_events = array_merge($all_events, $ticketmaster_events);
            }
        }
        
        // Get custom events if enabled
        if (!empty($enabled_apis['custom'])) {
            $custom_events = $this->get_custom_events($location);
            if ($custom_events) {
                $all_events = array_merge($all_events, $custom_events);
            }
        }
        
        // Apply AI enhancements if enabled
        $ai_settings = get_option('ai_events_pro_ai_settings', array());
        if (!empty($ai_settings['enable_ai_features']) && !empty($ai_settings['openrouter_api_key'])) {
            $all_events = $this->enhance_with_ai($all_events, $ai_settings);
        }
        
        // Sort events by date
        usort($all_events, function($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });
        
        return array_slice($all_events, 0, $limit);
    }
    
    public function get_custom_events($location = '') {
        $args = array(
            'post_type' => 'ai_event',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array()
        );
        
        if (!empty($location)) {
            $args['meta_query'][] = array(
                'key' => '_event_location',
                'value' => $location,
                'compare' => 'LIKE'
            );
        }
        
        $events = get_posts($args);
        $formatted_events = array();
        
        foreach ($events as $event) {
            $formatted_events[] = array(
                'id' => $event->ID,
                'title' => $event->post_title,
                'description' => wp_strip_all_tags($event->post_content),
                'date' => get_post_meta($event->ID, '_event_date', true),
                'time' => get_post_meta($event->ID, '_event_time', true),
                'location' => get_post_meta($event->ID, '_event_location', true),
                'venue' => get_post_meta($event->ID, '_event_venue', true),
                'price' => get_post_meta($event->ID, '_event_price', true),
                'url' => get_post_meta($event->ID, '_event_url', true),
                'image' => get_the_post_thumbnail_url($event->ID, 'large'),
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
        
        $days_until = (strtotime($event['date']) - time()) / (24 * 60 * 60);
        if ($days_until <= 7) $score += 20;
        elseif ($days_until <= 30) $score += 10;
        
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
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_events_cache';
        $general_settings = get_option('ai_events_pro_settings', array());
        $cache_duration = $general_settings['cache_duration'] ?? 3600;
        $expires_at = date('Y-m-d H:i:s', time() + $cache_duration);
        
        foreach ($events as $event) {
            $wpdb->replace(
                $table_name,
                array(
                    'event_id' => $event['id'],
                    'source' => $event['source'],
                    'data' => json_encode($event),
                    'location' => $location,
                    'expires_at' => $expires_at
                ),
                array('%s', '%s', '%s', '%s', '%s')
            );
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
        
        return $events;
    }
}