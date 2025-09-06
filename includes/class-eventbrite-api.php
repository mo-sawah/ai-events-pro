<?php

/**
 * Updated Eventbrite API integration with proper authentication.
 */
class AI_Events_Eventbrite_API {

    private $private_token;
    private $api_key;
    private $client_secret;
    private $public_token;
    private $base_url = 'https://www.eventbriteapi.com/v3/';
    
    public function __construct() {
        $this->private_token = get_option('ai_events_pro_eventbrite_private_token', '');
        $this->api_key = get_option('ai_events_pro_eventbrite_api_key', '');
        $this->client_secret = get_option('ai_events_pro_eventbrite_client_secret', '');
        $this->public_token = get_option('ai_events_pro_eventbrite_public_token', '');
    }
    
    public function get_events($location = '', $radius = 25, $limit = 20) {
        // Use private token as primary authentication method
        $auth_token = $this->private_token ?: $this->public_token;
        
        if (empty($auth_token)) {
            error_log('Eventbrite API Error: No authentication token available');
            return array();
        }
        
        $params = array(
            'location.address' => $location,
            'location.within' => $radius . 'mi',
            'sort_by' => 'date',
            'status' => 'live',
            'page_size' => min($limit, 50), // API limit
            'expand' => 'venue,organizer,category,ticket_availability'
        );
        
        $url = $this->base_url . 'events/search/?' . http_build_query($params);
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $auth_token,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('Eventbrite API Error: ' . $response->get_error_message());
            return array();
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code !== 200) {
            error_log('Eventbrite API Error: HTTP ' . $status_code . ' - ' . $body);
            return array();
        }
        
        $data = json_decode($body, true);
        
        if (!isset($data['events'])) {
            error_log('Eventbrite API Error: No events data in response');
            return array();
        }
        
        return $this->format_events($data['events']);
    }
    
    private function format_events($events) {
        $formatted_events = array();
        
        foreach ($events as $event) {
            $start_date = $event['start']['local'] ?? '';
            $end_date = $event['end']['local'] ?? '';
            
            $formatted_event = array(
                'id' => $event['id'],
                'title' => $event['name']['text'] ?? '',
                'description' => wp_strip_all_tags($event['description']['text'] ?? ''),
                'date' => $start_date ? date('Y-m-d', strtotime($start_date)) : '',
                'time' => $start_date ? date('H:i', strtotime($start_date)) : '',
                'end_date' => $end_date ? date('Y-m-d H:i', strtotime($end_date)) : '',
                'url' => $event['url'] ?? '',
                'image' => $this->get_event_image($event),
                'source' => 'eventbrite',
                'venue' => '',
                'location' => '',
                'price' => $this->get_event_price($event),
                'category' => $this->get_event_category($event),
                'organizer' => $this->get_event_organizer($event)
            );
            
            // Get venue information
            if (isset($event['venue'])) {
                $venue = $event['venue'];
                $formatted_event['venue'] = $venue['name'] ?? '';
                $formatted_event['location'] = $this->format_address($venue['address'] ?? array());
            }
            
            $formatted_events[] = $formatted_event;
        }
        
        return $formatted_events;
    }
    
    private function get_event_image($event) {
        if (isset($event['logo']['url'])) {
            return $event['logo']['url'];
        }
        
        // Try other image fields
        if (isset($event['logo']['original']['url'])) {
            return $event['logo']['original']['url'];
        }
        
        return '';
    }
    
    private function get_event_price($event) {
        // Check if event is free
        if (isset($event['is_free']) && $event['is_free']) {
            return 'Free';
        }
        
        // Check ticket availability data
        if (isset($event['ticket_availability'])) {
            if (isset($event['ticket_availability']['is_free']) && $event['ticket_availability']['is_free']) {
                return 'Free';
            }
            
            if (isset($event['ticket_availability']['minimum_ticket_price'])) {
                $price = $event['ticket_availability']['minimum_ticket_price'];
                return $price['display'] ?? 'Paid';
            }
        }
        
        return 'Check website';
    }
    
    private function get_event_category($event) {
        if (isset($event['category']['name'])) {
            return $event['category']['name'];
        }
        
        if (isset($event['subcategory']['name'])) {
            return $event['subcategory']['name'];
        }
        
        return 'Other';
    }
    
    private function get_event_organizer($event) {
        if (isset($event['organizer']['name'])) {
            return $event['organizer']['name'];
        }
        
        return '';
    }
    
    private function format_address($address) {
        if (empty($address)) {
            return '';
        }
        
        $parts = array();
        
        if (!empty($address['address_1'])) {
            $parts[] = $address['address_1'];
        }
        if (!empty($address['city'])) {
            $parts[] = $address['city'];
        }
        if (!empty($address['region'])) {
            $parts[] = $address['region'];
        }
        
        return implode(', ', $parts);
    }
    
    // Test connection method
    public function test_connection() {
        $auth_token = $this->private_token ?: $this->public_token;
        
        if (empty($auth_token)) {
            return array('success' => false, 'message' => 'No authentication token provided');
        }
        
        $url = $this->base_url . 'users/me/';
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $auth_token
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
            $user_name = isset($data['name']) ? $data['name'] : 'Unknown User';
            return array('success' => true, 'message' => "Connected successfully as: {$user_name}");
        }
        
        return array('success' => false, 'message' => 'Authentication failed');
    }
}