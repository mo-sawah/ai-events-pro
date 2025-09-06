<?php

/**
 * Ticketmaster API Integration - Using Consumer Key (API Key)
 * Complete implementation with proper error handling and data formatting
 */
class AI_Events_Ticketmaster_API {

    private $consumer_key;
    private $base_url = 'https://app.ticketmaster.com/discovery/v2/';
    
    public function __construct() {
        $settings = get_option('ai_events_pro_ticketmaster_settings', array());
        $this->consumer_key = $settings['consumer_key'] ?? '';
    }
    
    /**
     * Get events from Ticketmaster API
     */
    public function get_events($location = '', $radius = 25, $limit = 20) {
        if (empty($this->consumer_key)) {
            error_log('Ticketmaster API: No consumer key configured');
            return array();
        }
        
        $params = array(
            'apikey' => $this->consumer_key,
            'city' => $location,
            'radius' => $radius,
            'unit' => 'miles',
            'sort' => 'date,asc',
            'size' => min($limit, 200), // Ticketmaster max is 200
            'includeTBA' => 'no',
            'includeTest' => 'no'
        );
        
        $url = $this->base_url . 'events.json?' . http_build_query($params);
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array('Accept' => 'application/json')
        ));
        
        if (is_wp_error($response)) {
            error_log('Ticketmaster API Error: ' . $response->get_error_message());
            return array();
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code !== 200) {
            error_log('Ticketmaster API HTTP Error: ' . $status_code . ' - ' . $body);
            return array();
        }
        
        $data = json_decode($body, true);
        
        if (!isset($data['_embedded']['events'])) {
            error_log('Ticketmaster API: No events in response - ' . print_r($data, true));
            return array();
        }
        
        return $this->format_events($data['_embedded']['events']);
    }
    
    /**
     * Format Ticketmaster events to our standard format
     */
    private function format_events($events) {
        $formatted_events = array();
        
        foreach ($events as $event) {
            $start_date = $event['dates']['start']['localDate'] ?? '';
            $start_time = $event['dates']['start']['localTime'] ?? '';
            
            $formatted_event = array(
                'id' => 'ticketmaster_' . $event['id'],
                'title' => $event['name'] ?? '',
                'description' => $this->get_event_description($event),
                'date' => $start_date,
                'time' => $start_time,
                'url' => $event['url'] ?? '',
                'image' => $this->get_best_image($event),
                'source' => 'ticketmaster',
                'venue' => $this->get_venue_name($event),
                'location' => $this->get_venue_location($event),
                'price' => $this->get_price_range($event),
                'category' => $this->get_event_category($event),
                'organizer' => $this->get_promoter($event)
            );
            
            $formatted_events[] = $formatted_event;
        }
        
        return $formatted_events;
    }
    
    /**
     * Get event description from various possible fields
     */
    private function get_event_description($event) {
        $description = '';
        
        if (!empty($event['info'])) {
            $description = $event['info'];
        } elseif (!empty($event['pleaseNote'])) {
            $description = $event['pleaseNote'];
        } elseif (!empty($event['additionalInfo'])) {
            $description = $event['additionalInfo'];
        }
        
        return wp_strip_all_tags($description);
    }
    
    /**
     * Get the best quality image from event images
     */
    private function get_best_image($event) {
        if (empty($event['images'])) {
            return '';
        }
        
        // Sort by width descending to get largest image
        $images = $event['images'];
        usort($images, function($a, $b) {
            return ($b['width'] ?? 0) - ($a['width'] ?? 0);
        });
        
        return $images[0]['url'] ?? '';
    }
    
    /**
     * Get venue name
     */
    private function get_venue_name($event) {
        return $event['_embedded']['venues'][0]['name'] ?? '';
    }
    
    /**
     * Get formatted venue location
     */
    private function get_venue_location($event) {
        if (empty($event['_embedded']['venues'][0])) {
            return '';
        }
        
        $venue = $event['_embedded']['venues'][0];
        $parts = array_filter(array(
            $venue['city']['name'] ?? '',
            $venue['state']['stateCode'] ?? '',
            $venue['country']['countryCode'] ?? ''
        ));
        
        return implode(', ', $parts);
    }
    
    /**
     * Get formatted price range
     */
    private function get_price_range($event) {
        if (empty($event['priceRanges'])) {
            return 'Check website';
        }
        
        $price = $event['priceRanges'][0];
        $min = $price['min'] ?? 0;
        $max = $price['max'] ?? 0;
        $currency = $price['currency'] ?? 'USD';
        
        if ($min == 0 && $max == 0) {
            return 'Free';
        }
        
        if ($min == $max) {
            return $currency . ' ' . number_format($min, 2);
        }
        
        return $currency . ' ' . number_format($min, 2) . ' - ' . number_format($max, 2);
    }
    
    /**
     * Get event category from classifications
     */
    private function get_event_category($event) {
        if (empty($event['classifications'][0])) {
            return 'Other';
        }
        
        $classification = $event['classifications'][0];
        
        // Try different classification levels
        if (!empty($classification['segment']['name'])) {
            return $classification['segment']['name'];
        }
        
        if (!empty($classification['genre']['name'])) {
            return $classification['genre']['name'];
        }
        
        if (!empty($classification['subGenre']['name'])) {
            return $classification['subGenre']['name'];
        }
        
        return 'Other';
    }
    
    /**
     * Get promoter or attraction name
     */
    private function get_promoter($event) {
        // Try promoter first
        if (!empty($event['promoter']['name'])) {
            return $event['promoter']['name'];
        }
        
        // Try attractions
        if (!empty($event['_embedded']['attractions'][0]['name'])) {
            return $event['_embedded']['attractions'][0]['name'];
        }
        
        return '';
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        if (empty($this->consumer_key)) {
            return array(
                'success' => false, 
                'message' => 'No consumer key configured'
            );
        }
        
        $url = $this->base_url . 'events.json?apikey=' . $this->consumer_key . '&size=1';
        
        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => array('Accept' => 'application/json')
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false, 
                'message' => $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            return array(
                'success' => true, 
                'message' => 'Ticketmaster API connected successfully!'
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $error_data = json_decode($body, true);
        
        // Try to get specific error message
        $error_message = 'Connection failed';
        if (isset($error_data['fault']['faultstring'])) {
            $error_message = $error_data['fault']['faultstring'];
        } elseif (isset($error_data['errors'])) {
            $error_message = $error_data['errors'][0]['detail'] ?? $error_message;
        }
        
        return array(
            'success' => false, 
            'message' => $error_message . ' (HTTP ' . $status_code . ')'
        );
    }
    
    /**
     * Get available markets (for future use)
     */
    public function get_markets() {
        if (empty($this->consumer_key)) {
            return array();
        }
        
        $url = $this->base_url . 'classifications/markets.json?apikey=' . $this->consumer_key;
        
        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => array('Accept' => 'application/json')
        ));
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return array();
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return $data['_embedded']['markets'] ?? array();
    }
    
    /**
     * Search venues (for future use)
     */
    public function search_venues($location = '') {
        if (empty($this->consumer_key) || empty($location)) {
            return array();
        }
        
        $params = array(
            'apikey' => $this->consumer_key,
            'city' => $location,
            'size' => 20
        );
        
        $url = $this->base_url . 'venues.json?' . http_build_query($params);
        
        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => array('Accept' => 'application/json')
        ));
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return array();
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return $data['_embedded']['venues'] ?? array();
    }
}