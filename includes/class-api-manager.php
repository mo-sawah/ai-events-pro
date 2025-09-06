<?php

/**
 * API integration manager.
 */
class AI_Events_API_Manager {

    private $eventbrite_api;
    private $ticketmaster_api;
    private $openrouter_api_key;
    
    public function __construct() {
        $this->eventbrite_api = new AI_Events_Eventbrite_API();
        $this->ticketmaster_api = new AI_Events_Ticketmaster_API();
        $this->openrouter_api_key = get_option('ai_events_pro_openrouter_key', '');
    }
    
    public function get_events($location = '', $radius = 25, $limit = 20) {
        $all_events = array();
        $settings = get_option('ai_events_pro_settings', array());
        
        // Get events from Eventbrite
        if (!empty($settings['enable_eventbrite'])) {
            $eventbrite_events = $this->eventbrite_api->get_events($location, $radius, $limit);
            if ($eventbrite_events) {
                $all_events = array_merge($all_events, $eventbrite_events);
            }
        }
        
        // Get events from Ticketmaster
        if (!empty($settings['enable_ticketmaster'])) {
            $ticketmaster_events = $this->ticketmaster_api->get_events($location, $radius, $limit);
            if ($ticketmaster_events) {
                $all_events = array_merge($all_events, $ticketmaster_events);
            }
        }
        
        // Get custom events
        $custom_events = $this->get_custom_events($location);
        if ($custom_events) {
            $all_events = array_merge($all_events, $custom_events);
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
                'description' => $event->post_content,
                'date' => get_post_meta($event->ID, '_event_date', true),
                'time' => get_post_meta($event->ID, '_event_time', true),
                'location' => get_post_meta($event->ID, '_event_location', true),
                'venue' => get_post_meta($event->ID, '_event_venue', true),
                'price' => get_post_meta($event->ID, '_event_price', true),
                'url' => get_post_meta($event->ID, '_event_url', true),
                'image' => get_the_post_thumbnail_url($event->ID, 'large'),
                'source' => 'custom',
                'category' => get_the_terms($event->ID, 'event_category')
            );
        }
        
        return $formatted_events;
    }
    
    public function enhance_with_ai($events) {
        if (empty($this->openrouter_api_key) || empty($events)) {
            return $events;
        }
        
        foreach ($events as &$event) {
            // AI-powered event categorization
            $event['ai_category'] = $this->categorize_event($event);
            
            // AI-powered event recommendations
            $event['ai_score'] = $this->calculate_relevance_score($event);
            
            // AI-generated event summary
            if (strlen($event['description']) > 500) {
                $event['ai_summary'] = $this->generate_summary($event['description']);
            }
        }
        
        return $events;
    }
    
    private function categorize_event($event) {
        $prompt = "Categorize this event into one of these categories: Music, Sports, Arts & Culture, Food & Drink, Business, Health & Wellness, Technology, Education, Family, Other. Event: " . $event['title'] . " - " . substr($event['description'], 0, 200);
        
        return $this->call_openrouter_api($prompt, 'category');
    }
    
    private function calculate_relevance_score($event) {
        // Simple scoring algorithm - can be enhanced with AI
        $score = 50; // Base score
        
        // Boost score for events happening soon
        $days_until = (strtotime($event['date']) - time()) / (24 * 60 * 60);
        if ($days_until <= 7) {
            $score += 20;
        } elseif ($days_until <= 30) {
            $score += 10;
        }
        
        // Boost score for events with images
        if (!empty($event['image'])) {
            $score += 10;
        }
        
        return min(100, $score);
    }
    
    private function generate_summary($description) {
        $prompt = "Summarize this event description in 2-3 sentences: " . substr($description, 0, 1000);
        
        return $this->call_openrouter_api($prompt, 'summary');
    }
    
    private function call_openrouter_api($prompt, $type = 'general') {
        $url = 'https://openrouter.ai/api/v1/chat/completions';
        
        $data = array(
            'model' => 'openai/gpt-3.5-turbo',
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => $type === 'category' ? 50 : 200,
            'temperature' => 0.7
        );
        
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->openrouter_api_key,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => site_url(),
                'X-Title' => 'AI Events Pro'
            ),
            'body' => json_encode($data),
            'timeout' => 30
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            return '';
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        if (isset($decoded['choices'][0]['message']['content'])) {
            return trim($decoded['choices'][0]['message']['content']);
        }
        
        return '';
    }
    
    public function cache_events($events, $location = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_events_cache';
        $cache_duration = get_option('ai_events_pro_settings')['cache_duration'] ?? 3600;
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