<?php

/**
 * Eventbrite API integration.
 */
class AI_Events_Eventbrite_API {

    private $api_token;
    private $base_url = 'https://www.eventbriteapi.com/v3/';
    
    public function __construct() {
        $this->api_token = get_option('ai_events_pro_eventbrite_token', '');
    }
    
    public function get_events($location = '', $radius = 25, $limit = 20) {
        if (empty($this->api_token)) {
            return array();
        }
        
        $params = array(
            'location.address' => $location,
            'location.within' => $radius . 'mi',
            'sort_by' => 'date',
            'status' => 'live',
            'page_size' => $limit,
            'expand' => 'venue,organizer,category'
        );
        
        $url = $this->base_url . 'events/search/?' . http_build_query($params);
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_token
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('Eventbrite API Error: ' . $response->get_error_message());
            return array();
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['events'])) {
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
                'description' => strip_tags($event['description']['text'] ?? ''),
                'date' => $start_date ? date('Y-m-d', strtotime($start_date)) : '',
                'time' => $start_date ? date('H:i', strtotime($start_date)) : '',
                'end_date' => $end_date ? date('Y-m-d H:i', strtotime($end_date)) : '',
                'url' => $event['url'] ?? '',
                'image' => $event['logo']['url'] ?? '',
                'source' => 'eventbrite',
                'venue' => '',
                'location' => '',
                'price' => $this->get_event_price($event),
                'category' => $event['category']['name'] ?? 'Other',
                'organizer' => $event['organizer']['name'] ?? ''
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
    
    private function get_event_price($event) {
        if (isset($event['ticket_availability']['is_free']) && $event['ticket_availability']['is_free']) {
            return 'Free';
        }
        
        // For detailed pricing, you would need to make another API call to get ticket classes
        return 'Paid';
    }
    
    private function format_address($address) {
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
}