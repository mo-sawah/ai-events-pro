<?php

/**
 * Updated Ticketmaster API integration with proper authentication.
 */
class AI_Events_Ticketmaster_API {

    private $consumer_key;
    private $consumer_secret;
    private $base_url = 'https://app.ticketmaster.com/discovery/v2/';
    
    public function __construct() {
        $this->consumer_key = get_option('ai_events_pro_ticketmaster_consumer_key', '');
        $this->consumer_secret = get_option('ai_events_pro_ticketmaster_consumer_secret', '');
    }
    
    public function get_events($location = '', $radius = 25, $limit = 20) {
        if (empty($this->consumer_key)) {
            error_log('Ticketmaster API Error: No consumer key available');
            return array();
        }
        
        $params = array(
            'apikey' => $this->consumer_key,
            'city' => $location,
            'radius' => $radius,
            'unit' => 'miles',
            'sort' => 'date,asc',
            'size' => min($limit, 200), // API limit
            'includeTBA' => 'no',
            'includeTest' => 'no'
        );
        
        $url = $this->base_url . 'events.json?' . http_build_query($params);
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json'
            )
        ));
        
        if (is_wp_error($response)) {
            error_log('Ticketmaster API Error: ' . $response->get_error_message());
            return array();
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code !== 200) {
            error_log('Ticketmaster API Error: HTTP ' . $status_code . ' - ' . $body);
            return array();
        }
        
        $data = json_decode($body, true);
        
        if (!isset($data['_embedded']['events'])) {
            error_log('Ticketmaster API Error: No events data in response');
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
    
    private function get_event_description($event) {
        // Try different description fields
        if (!empty($event['info'])) {
            return wp_strip_all_tags($event['info']);
        }
        
        if (!empty($event['pleaseNote'])) {
            return wp_strip_all_tags($event['pleaseNote']);
        }
        
        if (!empty($event['additionalInfo'])) {
            return wp_strip_all_tags($event['additionalInfo']);
        }
        
        return '';
    }
    
    private function get_event_image($event) {
        if (isset($event['images']) && !empty($event['images'])) {
            // Get the largest available image
            $images = $event['images'];
            usort($images, function($a, $b) {
                return ($b['width'] ?? 0) - ($a['width'] ?? 0);
            });
            return $images[0]['url'] ?? '';
        }
        return '';
    }
    
    private function get_event_price($event) {
        if (isset($event['priceRanges']) && !empty($event['priceRanges'])) {
            $price_range = $event['priceRanges'][0];
            $min = $price_range['min'] ?? 0;
            $max = $price_range['max'] ?? 0;
            $currency = $price_range['currency'] ?? 'USD';
            
            if ($min == 0 && $max == 0) {
                return 'Free';
            } elseif ($min == $max) {
                return $currency . ' ' . number_format($min, 2);
            } else {
                return $currency . ' ' . number_format($min, 2) . ' - ' . number_format($max, 2);
            }
        }
        return 'Check website';
    }
    
    private function get_event_category($event) {
        if (isset($event['classifications'][0])) {
            $classification = $event['classifications'][0];
            
            if (isset($classification['segment']['name'])) {
                return $classification['segment']['name'];
            }
            
            if (isset($classification['genre']['name'])) {
                return $classification['genre']['name'];
            }
            
            if (isset($classification['subGenre']['name'])) {
                return $classification['subGenre']['name'];
            }
        }
        
        return 'Other';
    }
    
    private function get_event_promoter($event) {
        if (isset($event['promoter']['name'])) {
            return $event['promoter']['name'];
        }
        
        // Try getting organizer from embedded data
        if (isset($event['_embedded']['attractions'][0]['name'])) {
            return $event['_embedded']['attractions'][0]['name'];
        }
        
        return '';
    }
    
    private function format_address($venue) {
        if (empty($venue)) {
            return '';
        }
        
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
        if (isset($venue['country']['countryCode'])) {
            $parts[] = $venue['country']['countryCode'];
        }
        
        return implode(', ', $parts);
    }
    
    // Test connection method
    public function test_connection() {
        if (empty($this->consumer_key)) {
            return array('success' => false, 'message' => 'No consumer key provided');
        }
        
        $url = $this->base_url . 'events.json?apikey=' . $this->consumer_key . '&size=1';
        
        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/json'
            )
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code === 200) {
            return array('success' => true, 'message' => 'Connected successfully to Ticketmaster API');
        }
        
        // Try to get error details
        $error_data = json_decode($body, true);
        $error_message = 'Authentication failed';
        
        if (isset($error_data['fault']['faultstring'])) {
            $error_message = $error_data['fault']['faultstring'];
        } elseif (isset($error_data['errors'][0]['detail'])) {
            $error_message = $error_data['errors'][0]['detail'];
        }
        
        return array('success' => false, 'message' => $error_message);
    }
    
    // Get event details by ID
    public function get_event_details($event_id) {
        if (empty($this->consumer_key) || empty($event_id)) {
            return null;
        }
        
        $url = $this->base_url . 'events/' . $event_id . '.json?apikey=' . $this->consumer_key;
        
        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/json'
            )
        ));
        
        if (is_wp_error($response)) {
            error_log('Ticketmaster API Error: ' . $response->get_error_message());
            return null;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            $body = wp_remote_retrieve_body($response);
            return json_decode($body, true);
        }
        
        return null;
    }
    
    // Get venues by location
    public function get_venues($location = '', $radius = 25) {
        if (empty($this->consumer_key)) {
            return array();
        }
        
        $params = array(
            'apikey' => $this->consumer_key,
            'city' => $location,
            'radius' => $radius,
            'unit' => 'miles',
            'size' => 50
        );
        
        $url = $this->base_url . 'venues.json?' . http_build_query($params);
        
        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/json'
            )
        ));
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (isset($data['_embedded']['venues'])) {
                return $data['_embedded']['venues'];
            }
        }
        
        return array();
    }
    
    // Get classifications (categories)
    public function get_classifications() {
        if (empty($this->consumer_key)) {
            return array();
        }
        
        $url = $this->base_url . 'classifications.json?apikey=' . $this->consumer_key;
        
        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/json'
            )
        ));
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (isset($data['_embedded']['classifications'])) {
                return $data['_embedded']['classifications'];
            }
        }
        
        return array();
    }
}