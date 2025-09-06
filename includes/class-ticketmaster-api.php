<?php

/**
 * Ticketmaster API - Using Consumer Key (API Key)
 */
class AI_Events_Ticketmaster_API {

    private $consumer_key;
    private $base_url = 'https://app.ticketmaster.com/discovery/v2/';
    
    public function __construct() {
        $settings = get_option('ai_events_pro_ticketmaster_settings', array());
        $this->consumer_key = $settings['consumer_key'] ?? '';
    }
    
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
            'size' => min($limit, 200),
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
            error_log('Ticketmaster API: No events in response');
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
    
    private function get_event_description($event) {
        return wp_strip_all_tags($event['info'] ?? $event['pleaseNote'] ?? '');
    }
    
    private function get_best_image($event) {
        if (empty($event['images'])) return '';
        
        // Sort by width descending to get largest image
        $images = $event['images'];
        usort($images, function($a, $b) {
            return ($b['width'] ?? 0) - ($a['width'] ?? 0);
        });
        
        return $images[0]['url'] ?? '';
    }
    
    private function get_venue_name($event) {
        return $event['_embedded']['venues'][0]['name'] ?? '';
    }
    
    private function get_venue_location($event) {
        if (empty($event['_embedded']['venues'][0])) return '';
        
        $venue = $event['_embedded']['venues'][0];
        $parts = array_filter(array(
            $venue['city']['name'] ?? '',
            $venue['state']['stateCode'] ?? ''
        ));
        
        return implode(', ', $parts);
    }
    
    private function get_price_range($event) {
        if (empty($event['priceRanges'])) return 'Check website';
        
        $price = $event['priceRanges'][0];
        $min = $price['min'] ?? 0;
        $max = $price['max'] ?? 0;
        $currency = $price['currency'] ?? 'USD';
        
        if ($min == 0 && $max == 0) return 'Free';
        if ($min == $max) return $currency . ' ' . number_format($min, 2);
        
        return $currency . ' ' . number_format($min, 2) . ' - ' . number_format($max, 2);
    }
    
    private function get_event_category($event) {
        if (empty($event['classifications'][0])) return 'Other';
        
        $classification = $event['classifications'][0];
        return $classification['segment']['name'] ?? 
               $classification['genre']['name'] ?? 
               'Other';
    }
    
    private function get_promoter($event) {
        return $event['promoter']['name'] ?? 
               $event['_embedded']['attractions'][0]['name'] ?? '';
    }
}