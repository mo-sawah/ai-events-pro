<?php

/**
 * Eventbrite API Integration - Enhanced with better location handling, pagination, and debugging
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
            $this->dbg('Eventbrite API: No private token configured');
            return new WP_Error('eventbrite_no_token', 'Eventbrite: No private token configured');
        }

        // Strategy 1: Location-based search
        if (!empty($location)) {
            $events = $this->search_by_location($location, $radius, $limit);
            if (!is_wp_error($events) && !empty($events)) {
                return $events;
            }
            if (is_wp_error($events)) {
                $this->dbg('Eventbrite location search error: ' . $events->get_error_message());
            } else {
                $this->dbg('Eventbrite: No events from location search, trying general search...');
            }
        }

        // Strategy 2: General search (broad US)
        $events = $this->search_general($limit);
        if (!is_wp_error($events) && !empty($events)) {
            return $events;
        }
        if (is_wp_error($events)) {
            $this->dbg('Eventbrite general search error: ' . $events->get_error_message());
        } else {
            $this->dbg('Eventbrite: No events from general search, trying popular search...');
        }

        // Strategy 3: Popular/free events fallback
        $events = $this->search_popular($limit);
        if (!is_wp_error($events) && !empty($events)) {
            return $events;
        }

        if (is_wp_error($events)) {
            return $events;
        }
        return array(); // nothing found after all strategies
    }

    /**
     * Search events by location
     */
    private function search_by_location($location, $radius, $limit) {
        $location_param = $this->prepare_location_search($location);

        $params = array(
            'location.address'            => $location_param,
            'location.within'             => absint($radius) . 'mi',
            'sort_by'                     => 'date',
            'expand'                      => 'venue,organizer,ticket_availability,category,subcategory',
            'start_date.range_start'      => gmdate('Y-m-d\TH:i:s\Z'),
            'status'                      => 'live',
            'include_all_series_instances'=> 'true',
        );

        return $this->fetch_events('location search', $params, $limit);
    }

    /**
     * General event search with broad US location
     */
    private function search_general($limit) {
        $params = array(
            'location.address'            => 'United States',
            'sort_by'                     => 'date',
            'expand'                      => 'venue,organizer,ticket_availability,category,subcategory',
            'start_date.range_start'      => gmdate('Y-m-d\TH:i:s\Z'),
            'status'                      => 'live',
            'include_all_series_instances'=> 'true',
        );

        return $this->fetch_events('general search', $params, $limit);
    }

    /**
     * Search for popular/featured events (free as a heuristic)
     */
    private function search_popular($limit) {
        $params = array(
            'sort_by'                     => 'best',
            'expand'                      => 'venue,organizer,ticket_availability,category,subcategory',
            'start_date.range_start'      => gmdate('Y-m-d\TH:i:s\Z'),
            'status'                      => 'live',
            'price'                       => 'free',
            'include_all_series_instances'=> 'true',
        );

        return $this->fetch_events('popular search', $params, $limit);
    }

    /**
     * Fetch events with pagination until $limit or no more items
     */
    private function fetch_events($label, $params, $limit) {
        $page_size = min(50, max(1, (int)$limit));
        $collected = array();
        $page = 1;

        do {
            $params_with_page = $params + array(
                'page'      => $page,
                'page_size' => $page_size,
            );
            $url = $this->base_url . 'events/search/?' . http_build_query($params_with_page);
            $this->dbg("Eventbrite {$label} URL (page {$page}): " . $url);

            $resp = $this->request($url, $label);
            if (is_wp_error($resp)) {
                return $resp; // bubble up error
            }

            $events = isset($resp['events']) && is_array($resp['events']) ? $resp['events'] : array();
            $this->dbg("Eventbrite {$label}: fetched " . count($events) . " events on page {$page}");

            if (!empty($events)) {
                $collected = array_merge($collected, $events);
            }

            $has_more = !empty($resp['pagination']['has_more_items']);
            $page++;

            if (count($collected) >= $limit) {
                break;
            }
        } while ($has_more && $page < 20); // reasonable upper bound

        $formatted = $this->format_events(array_slice($collected, 0, $limit));
        $this->dbg("Eventbrite {$label}: returning " . count($formatted) . " formatted events");
        return $formatted;
    }

    /**
     * Core HTTP request with detailed error handling
     */
    private function request($url, $context) {
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->private_token,
                'Content-Type'  => 'application/json'
            )
        ));

        if (is_wp_error($response)) {
            $this->dbg("Eventbrite {$context} WP_Error: " . $response->get_error_message());
            return new WP_Error('eventbrite_http_error', $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($code !== 200) {
            $decoded = json_decode($body, true);
            $msg = 'HTTP ' . $code;
            if (is_array($decoded)) {
                if (!empty($decoded['error_description'])) {
                    $msg .= ' - ' . $decoded['error_description'];
                } elseif (!empty($decoded['error'])) {
                    if (is_string($decoded['error'])) {
                        $msg .= ' - ' . $decoded['error'];
                    } elseif (is_array($decoded['error']) && !empty($decoded['error']['message'])) {
                        $msg .= ' - ' . $decoded['error']['message'];
                    }
                }
            }
            $this->dbg("Eventbrite {$context} error: {$msg}. Raw body: " . substr($body, 0, 500));
            return new WP_Error('eventbrite_bad_status', "Eventbrite {$context} failed: {$msg}");
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            $this->dbg("Eventbrite {$context} returned non-JSON body.");
            return new WP_Error('eventbrite_bad_json', 'Eventbrite returned invalid JSON.');
        }

        return $decoded;
    }

    /**
     * Prepare location string for Eventbrite search
     */
    private function prepare_location_search($location) {
        if (empty($location)) {
            return 'United States';
        }

        $location = trim($location);

        $location_mappings = array(
            'NYC' => 'New York, NY',
            'LA'  => 'Los Angeles, CA',
            'SF'  => 'San Francisco, CA',
            'DC'  => 'Washington, DC'
        );

        if (isset($location_mappings[$location])) {
            return $location_mappings[$location];
        }

        // If it's just a city name (likely US), give minimal context
        if (strpos($location, ',') === false && strlen($location) < 20) {
            // Common cities shorthand (light heuristic)
            $major_cities = array(
                'New York'      => 'New York, NY',
                'Los Angeles'   => 'Los Angeles, CA',
                'Chicago'       => 'Chicago, IL',
                'Houston'       => 'Houston, TX',
                'Phoenix'       => 'Phoenix, AZ',
                'Philadelphia'  => 'Philadelphia, PA',
                'San Antonio'   => 'San Antonio, TX',
                'San Diego'     => 'San Diego, CA',
                'Dallas'        => 'Dallas, TX',
                'San Jose'      => 'San Jose, CA',
                'Miami'         => 'Miami, FL',
                'Atlanta'       => 'Atlanta, GA',
                'Boston'        => 'Boston, MA',
                'Seattle'       => 'Seattle, WA',
                'Denver'        => 'Denver, CO'
            );
            if (isset($major_cities[$location])) {
                return $major_cities[$location];
            }
            return $location . ', USA';
        }

        return $location;
    }

    /**
     * Convert Eventbrite events to our standard format
     */
    private function format_events($events) {
        $formatted_events = array();

        foreach ($events as $event) {
            // Skip cancelled/draft/etc.; keep live by design
            if (isset($event['status']) && $event['status'] !== 'live') {
                continue;
            }

            $start_datetime = $event['start']['local'] ?? '';
            $start_date = '';
            $start_time = '';

            if (!empty($start_datetime)) {
                $ts = strtotime($start_datetime);
                if ($ts) {
                    $start_date = gmdate('Y-m-d', $ts);
                    $start_time = gmdate('H:i', $ts);
                }
            }

            $formatted_event = array(
                'id'          => 'eventbrite_' . ($event['id'] ?? ''),
                'title'       => $event['name']['text'] ?? '',
                'description' => $this->get_event_description($event),
                'date'        => $start_date,
                'time'        => $start_time,
                'url'         => $event['url'] ?? '',
                'image'       => $this->get_event_image($event),
                'source'      => 'eventbrite',
                'venue'       => $this->get_venue_name($event),
                'location'    => $this->get_venue_location($event),
                'price'       => $this->get_price_info($event),
                'category'    => $this->get_event_category($event),
                'organizer'   => $this->get_organizer_name($event),
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
        // Free?
        if (!empty($event['is_free']) && $event['is_free'] === true) {
            return 'Free';
        }

        if (!empty($event['ticket_availability'])) {
            $ticket_info = $event['ticket_availability'];

            if (!empty($ticket_info['minimum_ticket_price'])) {
                $min_price = $ticket_info['minimum_ticket_price'];
                $currency = $min_price['currency'] ?? 'USD';
                $amount   = $min_price['value'] ?? 0;

                if ((int)$amount === 0) {
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
     * Append to plugin debug log option (visible in admin)
     */
    private function dbg($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[AI Events Pro][Eventbrite] ' . $message);
        }
        $log = get_option('ai_events_debug_log', array());
        $log[] = array(
            'timestamp' => current_time('mysql'),
            'message'   => '[Eventbrite] ' . $message
        );
        // Keep last 100
        $log = array_slice($log, -100);
        update_option('ai_events_debug_log', $log);
    }
}