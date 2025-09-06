<?php

/**
 * Ticketmaster API integration.
 */
class AI_Events_Ticketmaster_API {

    private $api_key;
    private $base_url = 'https://app.ticketmaster.com/discovery/v2/';
    
    public function __construct() {
        $this->api_key = get_option('ai_events_pro_ticketmaster_key', '');
    }
    
    public function get_events($location = '', $radius = 25, $limit = 20) {
        if (empty($this->api_key)) {
            return array();
        }
        
        $params = array(
            'apikey' => $this->api_key,
            'city' => $location,
            'radius' => $radius,
            'unit' => 'miles',
            'sort' => 'date,asc',
            'size' => $limit,
            'includeTBA' => 'no',
            'includeTest' => 'no'
        );
        
        $url = $this->base_url . 'events.json?' . http_build_query($params);
        
        $response = wp_remote_get($url, array(
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('Ticketmaster API Error: ' . $response->get_error_message());
            return array();
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['_embedded']['events'])) {
            return array();
        }
        
        return $this->format_events($data['_embedded']['events']);
    }
    
    private function format_events($events) {
        $formatted_events = array();
        
        foreach ($events as $event) {
            $start_date = $event['dates']['start']['localDate'] ?? '';
            $start_time = $event['dates']['start']['localTime'] ?? '';
            
            $formatted_event = array(
                'id' => $event['id'],
                'title' => $event['name'] ?? '',
                'description' => $event['info'] ?? $event['pleaseNote'] ?? '',
                'date' => $start_date,
                'time' => $start_time,
                'url' => $event['url'] ?? '',
                'image' => $this->get_event_image($event),
                'source' => 'ticketmaster',
                'venue' => '',
                'location' => '',
                'price' => $this->get_event_price($event),
                'category' => $this->get_event_category($event),
                'organizer' => $this->get_event_promoter($event)
            );
            
            // Get venue information
            if (isset($event['_embedded']['venues'][0])) {
                $venue = $event['_embedded']['venues'][0];
                $formatted_event['venue'] = $venue['name'] ?? '';
                $formatted_event['location'] = $this->format_address($venue);
            }
            
            $formatted_events[] = $formatted_event;
        }
        
        return $formatted_events;
    }
    
    private function get_event_image($event) {
        if (isset($event['images']) && !empty($event['images'])) {
            // Get the largest available image
            usort($event['images'], function($a, $b) {
                return ($b['width'] ?? 0) - ($a['width'] ?? 0);
            });
            return $event['images'][0]['url'] ?? '';
        }
        return '';
    }
    
    private function get_event_price($event) {
        if (isset($event['priceRanges']) && !empty($event['priceRanges'])) {
            $price_range = $event['priceRanges'][0];
            $min = $price_range['min'] ?? 0;
            $max = $price_range['max'] ?? 0;
            $currency = $price_range['currency'] ?? 'USD';
            
            if ($min == $max) {
                return $currency . ' ' . number_format($min, 2);
            } else {
                return $currency . ' ' . number_format($min, 2) . ' - ' . number_format($max, 2);
            }
        }
        return 'Check website';
    }
    
    private function get_event_category($event) {
        if (isset($event['classifications'][0]['segment']['name'])) {
            return $event['classifications'][0]['segment']['name'];
        }
        return 'Other';
    }
    
    private function get_event_promoter($event) {
        if (isset($event['promoter']['name'])) {
            return $event['promoter']['name'];
        }
        return '';
    }
    
    private function format_address($venue) {
        $parts = array();
        
        if (isset($venue['address']['line1'])) {
            $parts[] = $venue['address']['line1'];
        }
        if (isset($venue['city']['name'])) {
            $parts[] = $venue['city']['name'];
        }
        if (isset($venue['state']['stateCode'])) {
            $parts[] = $venue['state']['stateCode'];
        }
        
        return implode(', ', $parts);
    }
}