<?php

/**
 * Eventbrite API Integration - Enhanced with better location handling and debugging
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
        
        // Try multiple search strategies
        $events = array();
        
        // Strategy 1: Location-based search
        if (!empty($location)) {
            $events = $this->search_by_location($location, $radius, $limit);
        }
        
        // Strategy 2: If no events found with location, try general search
        if (empty($events)) {
            error_log('Eventbrite API: No events found with location search, trying general search...');
            $events = $this->search_general($limit);
        }
        
        // Strategy 3: Try popular events if still no results
        if (empty($events)) {
            error_log('Eventbrite API: No events found with general search, trying popular events...');
            $events = $this->search_popular($limit);
        }
        
        return $events;
    }
    
    /**
     * Search events by location
     */
    private function search_by_location($location, $radius, $limit) {
        $location_param = $this->prepare_location_search($location);
        
        $params = array(
            'location.address' => $location_param,
            'location.within' => $radius . 'mi',
            'sort_by' => 'date',
            'page_size' => min($limit, 50),
            'expand' => 'venue,organizer,ticket_availability,category',
            'start_date.range_start' => date('c'),
            'status' => 'live',
            'include_all_series_instances' => 'true'
        );
        
        $url = $this->base_url . 'events/search/?' . http_build_query($params);
        error_log('Eventbrite API: Searching with URL: ' . $url);
        
        return $this->make_api_request($url, 'location search');
    }
    
    /**
     * General event search without location restriction
     */
    private function search_general($limit) {
        $params = array(
            'sort_by' => 'date',
            'page_size' => min($limit, 50),
            'expand' => 'venue,organizer,ticket_availability,category',
            'start_date.range_start' => date('c'),
            'status' => 'live',
            'include_all_series_instances' => 'true',
            'location.address' => 'United States' // Broad location
        );
        
        $url = $this->base_url . 'events/search/?' . http_build_query($params);
        error_log('Eventbrite API: General search URL: ' . $url);
        
        return $this->make_api_request($url, 'general search');
    }
    
    /**
     * Search for popular/featured events
     */
    private function search_popular($limit) {
        $params = array(
            'sort_by' => 'best',
            'page_size' => min($limit, 20),
            'expand' => 'venue,organizer,ticket_availability,category',
            'start_date.range_start' => date('c'),
            'status' => 'live',
            'price' => 'free' // Try free events first as they're more common
        );
        
        $url = $this->base_url . 'events/search/?' . http_build_query($params);
        error_log('Eventbrite API: Popular search URL: ' . $url);
        
        return $this->make_api_request($url, 'popular search');
    }
    
    /**
     * Make API request and handle response
     */
    private function make_api_request($url, $search_type) {
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->private_token,
                'Content-Type' => 'application/json'
            )
        ));
        
        if (is_wp_error($response)) {
            error_log('Eventbrite API Error (' . $search_type . '): ' . $response->get_error_message());
            return array();
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code !== 200) {
            error_log('Eventbrite API HTTP Error (' . $search_type . '): ' . $status_code . ' - ' . $body);
            return array();
        }
        
        $data = json_decode($body, true);
        
        if (!isset($data['events'])) {
            error_log('Eventbrite API (' . $search_type . '): No events in response - ' . print_r($data, true));
            return array();
        }
        
        error_log('Eventbrite API (' . $search_type . '): Found ' . count($data['events']) . ' events');
        
        return $this->format_events($data['events']);
    }
    
    /**
     * Prepare location string for Eventbrite search
     */
    private function prepare_location_search($location) {
        if (empty($location)) {
            return 'United States';
        }
        
        // Clean up the location string
        $location = trim($location);
        
        // Common location mappings
        $location_mappings = array(
            'NYC' => 'New York, NY',
            'LA' => 'Los Angeles, CA',
            'SF' => 'San Francisco, CA',
            'DC' => 'Washington, DC'
        );
        
        if (isset($location_mappings[$location])) {
            return $location_mappings[$location];
        }
        
        // If it's just a city, try to add state context for US cities
        if (strpos($location, ',') === false && strlen($location) < 20) {
            // List of major US cities that might need state context
            $major_cities = array(
                'New York' => 'New York, NY',
                'Los Angeles' => 'Los Angeles, CA',
                'Chicago' => 'Chicago, IL',
                'Houston' => 'Houston, TX',
                'Phoenix' => 'Phoenix, AZ',
                'Philadelphia' => 'Philadelphia, PA',
                'San Antonio' => 'San Antonio, TX',
                'San Diego' => 'San Diego, CA',
                'Dallas' => 'Dallas, TX',
                'San Jose' => 'San Jose, CA',
                'Miami' => 'Miami, FL',
                'Atlanta' => 'Atlanta, GA',
                'Boston' => 'Boston, MA',
                'Seattle' => 'Seattle, WA',
                'Denver' => 'Denver, CO'
            );
            
            if (isset($major_cities[$location])) {
                return $major_cities[$location];
            }
            
            // Default fallback
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
            // Skip cancelled or draft events
            if (isset($event['status']) && $event['status'] !== 'live') {
                continue;
            }
            
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
        } elseif (!empty($event['name']['text'])) {
            $description = $event['name']['text'];
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
        
        if (!empty($event['logo']['original']['url'])) {
            return $event['logo']['original']['url'];
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
        
        if (!empty($event['online_event']) && $event['online_event']) {
            return 'Online Event';
        }
        
        return 'Venue TBA';
    }
    
    /**
     * Get formatted venue location
     */
    private function get_venue_location($event) {
        if (!empty($event['online_event']) && $event['online_event']) {
            return 'Online';
        }
        
        if (empty($event['venue']['address'])) {
            return 'Location TBA';
        }
        
        $address = $event['venue']['address'];
        $parts = array_filter(array(
            $address['city'] ?? '',
            $address['region'] ?? '',
            $address['country'] ?? ''
        ));
        
        if (empty($parts)) {
            return 'Location TBA';
        }
        
        return implode(', ', $parts);
    }
    
    /**
     * Get price information
     */
    private function get_price_info($event) {
        // Check if event is free first
        if (!empty($event['is_free']) && $event['is_free'] === true) {
            return 'Free';
        }
        
        // Check ticket availability data
        if (!empty($event['ticket_availability'])) {
            $ticket_info = $event['ticket_availability'];
            
            if (!empty($ticket_info['minimum_ticket_price'])) {
                $min_price = $ticket_info['minimum_ticket_price'];
                $currency = $min_price['currency'] ?? 'USD';
                $amount = $min_price['value'] ?? 0;
                
                if ($amount == 0) {
                    return 'Free';
                }
                
                return $currency . ' ' . number_format($amount / 100, 2);
            }
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
     * Get user's organizations
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
     * Get event categories
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