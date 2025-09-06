<?php

/**
 * Eventbrite API - Using Private Token (OAuth Personal Access Token)
 */
class AI_Events_Eventbrite_API {

    private $private_token;
    private $base_url = 'https://www.eventbriteapi.com/v3/';
    
    public function __construct() {
        $settings = get_option('ai_events_pro_eventbrite_settings', array());
        $this->private_token = $settings['private_token'] ?? '';
    }
    
    public function get_events($location = '', $radius = 25, $limit = 20) {
        if (empty($this->private_token)) {
            error_log('Eventbrite API: No private token configured');
            return array();
        }
        
        $params = array(
            'location.address' => $location,
            'location.within' => $radius . 'mi',
            'sort_by' => 'date',
            'status' => 'live',
            'page_size' => min($limit, 50),
            'expand' => 'venue,organizer,category,format'
        );
        
        $url = $this->base_url . 'events/search/?' . http_build_query($params);
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->private_token,
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
            error_log('Eventbrite API HTTP Error: ' . $status_code . ' - ' . $body);
            return array();
        }
        
        $data = json_decode($body, true);
        
        if (!isset($data['events'])) {
            error_log('Eventbrite API: No events in response');
            return array();
        }
        
        return $this->format_events($data['events']);
    }
    
    private function format_events($events) {
        $formatted_events = array();
        
        foreach ($events as $event) {
            $start = $event['start']['local'] ?? '';
            $end = $event['end']['local'] ?? '';
            
            $formatted_event = array(
                'id' => $event['id'],
                'title' => $event['name']['text'] ?? '',
                'description' => wp_strip_all_tags($event['description']['text'] ?? ''),
                'date' => $start ? date('Y-m-d', strtotime($start)) : '',
                'time' => $start ? date('H:i', strtotime($start)) : '',
                'end_date' => $end ? date('Y-m-d H:i', strtotime($end)) : '',
                'url' => $event['url'] ?? '',
                'image' => $event['logo']['url'] ?? '',
                'source' => 'eventbrite',
                'venue' => $event['venue']['name'] ?? '',
                'location' => $this->format_venue_address($event['venue'] ?? array()),
                'price' => $this->determine_price($event),
                'category' => $event['category']['name'] ?? 'Other',
                'organizer' => $event['organizer']['name'] ?? ''
            );
            
            $formatted_events[] = $formatted_event;
        }
        
        return $formatted_events;
    }
    
    private function format_venue_address($venue) {
        if (empty($venue['address'])) return '';
        
        $address = $venue['address'];
        $parts = array_filter(array(
            $address['address_1'] ?? '',
            $address['city'] ?? '',
            $address['region'] ?? ''
        ));
        
        return implode(', ', $parts);
    }
    
    private function determine_price($event) {
        if (isset($event['is_free']) && $event['is_free']) {
            return 'Free';
        }
        
        // Check if we have ticket classes expanded
        if (isset($event['ticket_classes']) && !empty($event['ticket_classes'])) {
            $prices = array();
            foreach ($event['ticket_classes'] as $ticket_class) {
                if (isset($ticket_class['cost']['display'])) {
                    $prices[] = $ticket_class['cost']['display'];
                }
            }
            if (!empty($prices)) {
                return implode(' - ', array_unique($prices));
            }
        }
        
        return 'Check website';
    }
}