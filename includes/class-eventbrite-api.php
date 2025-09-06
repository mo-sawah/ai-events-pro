<?php

/**
 * Eventbrite API Integration - Using Private Token (Personal Access Token)
 * Complete implementation with proper error handling and data formatting
 */
class AI_Events_Eventbrite_API {

    private $private_token;
    private $base_url = 'https://www.eventbriteapi.com/v3/';
    
    public function __construct() {
        $settings = get_option('ai_events_pro_eventbrite_settings', array());
        $this->private_token = $settings['private_token'] ?? '';
    }
    
    /**
     * Get events from Eventbrite API
     */
    public function get_events($location = '', $radius = 25, $limit = 20) {
        if (empty($this->private_token)) {
            error_log('Eventbrite API: No private token configured');
            return array();
        }
        
        // First, we need to search for events by location
        $location_param = $this->prepare_location_search($location);
        
        $params = array(
            'location.address' => $location_param,
            'location.within' => $radius . 'mi',
            'sort_by' => 'date',
            'page_size' => min($limit, 50), // Eventbrite max is 50 per page
            'expand' => 'venue,organizer,ticket_availability,category',
            'start_date.range_start' => date('c'), // Only future events
            'status' => 'live'
        );
        
        $url = $this->base_url . 'events/search/?' . http_build_query($params);
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->private_token,
                'Content-Type' => 'application/json'
            )
        ));
        
        if (is_wp_error($response)) {
            error_log('Eventbrite API Error: ' . $response->get_error_message());
            return array();
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code !== 200) {
            error_log('Eventbrite API HTTP Error: ' . $status_code . ' - ' . $body);
            return array();
        }
        
        $data = json_decode($body, true);
        
        if (!isset($data['events'])) {
            error_log('Eventbrite API: No events in response - ' . print_r($data, true));
            return array();
        }
        
        return $this->format_events($data['events']);
    }
    
    /**
     * Prepare location string for Eventbrite search
     */
    private function prepare_location_search($location) {
        if (empty($location)) {
            return 'United States'; // Default fallback
        }
        
        // Eventbrite works better with full addresses
        // If it's just a city, add state/country context
        if (strpos($location, ',') === false && strlen($location) < 20) {
            // Looks like just a city name, try to enhance it
            return $location . ', USA';
        }
        
        return $location;
    }
    
    /**
     * Format Eventbrite events to our standard format
     */
    private function format_events($events) {
        $formatted_events = array();
        
        foreach ($events as $event) {
            $start_datetime = $event['start']['local'] ?? '';
            $start_date = '';
            $start_time = '';
            
            if (!empty($start_datetime)) {
                $date_obj = DateTime::createFromFormat('Y-m-d\TH:i:s', $start_datetime);
                if ($date_obj) {
                    $start_date = $date_obj->format('Y-m-d');
                    $start_time = $date_obj->format('H:i');
                }
            }
            
            $formatted_event = array(
                'id' => 'eventbrite_' . $event['id'],
                'title' => $event['name']['text'] ?? '',
                'description' => $this->get_event_description($event),
                'date' => $start_date,
                'time' => $start_time,
                'url' => $event['url'] ?? '',
                'image' => $this->get_event_image($event),
                'source' => 'eventbrite',
                'venue' => $this->get_venue_name($event),
                'location' => $this->get_venue_location($event),
                'price' => $this->get_price_info($event),
                'category' => $this->get_event_category($event),
                'organizer' => $this->get_organizer_name($event)
            );
            
            $formatted_events[] = $formatted_event;
        }
        
        return $formatted_events;
    }
    
    /**
     * Get event description, with HTML stripped
     */
    private function get_event_description($event) {
        $description = '';
        
        if (!empty($event['description']['text'])) {
            $description = $event['description']['text'];
        } elseif (!empty($event['summary'])) {
            $description = $event['summary'];
        }
        
        return wp_strip_all_tags($description);
    }
    
    /**
     * Get event image URL
     */
    private function get_event_image($event) {
        if (!empty($event['logo']['url'])) {
            return $event['logo']['url'];
        }
        
        return '';
    }
    
    /**
     * Get venue name
     */
    private function get_venue_name($event) {
        if (!empty($event['venue']['name'])) {
            return $event['venue']['name'];
        }
        
        return 'Online Event';
    }
    
    /**
     * Get formatted venue location
     */
    private function get_venue_location($event) {
        if (empty($event['venue']['address'])) {
            return 'Online';
        }
        
        $address = $event['venue']['address'];
        $parts = array_filter(array(
            $address['city'] ?? '',
            $address['region'] ?? '',
            $address['country'] ?? ''
        ));
        
        return implode(', ', $parts);
    }
    
    /**
     * Get price information
     */
    private function get_price_info($event) {
        // Check if ticket availability data is present
        if (!empty($event['ticket_availability'])) {
            $ticket_info = $event['ticket_availability'];
            
            if (!empty($ticket_info['minimum_ticket_price'])) {
                $min_price = $ticket_info['minimum_ticket_price'];
                $currency = $min_price['currency'] ?? 'USD';
                $amount = $min_price['value'] ?? 0;
                
                if ($amount == 0) {
                    return 'Free';
                }
                
                return $currency . ' ' . number_format($amount / 100, 2); // Eventbrite prices are in cents
            }
        }
        
        // Check if event is free
        if (!empty($event['is_free']) && $event['is_free'] === true) {
            return 'Free';
        }
        
        return 'Check website';
    }
    
    /**
     * Get event category
     */
    private function get_event_category($event) {
        if (!empty($event['category']['name'])) {
            return $event['category']['name'];
        }
        
        if (!empty($event['subcategory']['name'])) {
            return $event['subcategory']['name'];
        }
        
        return 'Other';
    }
    
    /**
     * Get organizer name
     */
    private function get_organizer_name($event) {
        if (!empty($event['organizer']['name'])) {
            return $event['organizer']['name'];
        }
        
        return '';
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        if (empty($this->private_token)) {
            return array(
                'success' => false, 
                'message' => 'No private token configured'
            );
        }
        
        $url = $this->base_url . 'users/me/';
        
        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->private_token,
                'Content-Type' => 'application/json'
            )
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false, 
                'message' => $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            $name = $data['name'] ?? 'Unknown User';
            
            return array(
                'success' => true, 
                'message' => sprintf('Eventbrite connected successfully as: %s', $name)
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $error_data = json_decode($body, true);
        
        // Try to get specific error message
        $error_message = 'Connection failed';
        if (isset($error_data['error_description'])) {
            $error_message = $error_data['error_description'];
        } elseif (isset($error_data['error'])) {
            $error_message = $error_data['error'];
        }
        
        return array(
            'success' => false, 
            'message' => $error_message . ' (HTTP ' . $status_code . ')'
        );
    }
    
    /**
     * Get user's organizations (for future use)
     */
    public function get_organizations() {
        if (empty($this->private_token)) {
            return array();
        }
        
        $url = $this->base_url . 'users/me/organizations/';
        
        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->private_token,
                'Content-Type' => 'application/json'
            )
        ));
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return array();
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return $data['organizations'] ?? array();
    }
    
    /**
     * Get event categories (for future use)
     */
    public function get_categories() {
        if (empty($this->private_token)) {
            return array();
        }
        
        $url = $this->base_url . 'categories/';
        
        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->private_token,
                'Content-Type' => 'application/json'
            )
        ));
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return array();
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return $data['categories'] ?? array();
    }
}