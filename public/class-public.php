<?php

/**
 * The public-facing functionality of the plugin.
 */
class AI_Events_Public {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            AI_EVENTS_PRO_PLUGIN_URL . 'public/css/ai-events-public.css',
            array(),
            $this->version,
            'all'
        );

        wp_enqueue_style(
            $this->plugin_name . '-theme-modes',
            AI_EVENTS_PRO_PLUGIN_URL . 'public/css/theme-modes.css',
            array($this->plugin_name),
            $this->version,
            'all'
        );
    }

    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            AI_EVENTS_PRO_PLUGIN_URL . 'public/js/ai-events-public.js',
            array('jquery'),
            $this->version,
            false
        );

        wp_enqueue_script(
            $this->plugin_name . '-theme-switcher',
            AI_EVENTS_PRO_PLUGIN_URL . 'public/js/theme-switcher.js',
            array('jquery'),
            $this->version,
            false
        );

        wp_localize_script($this->plugin_name, 'ai_events_public', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_events_public_nonce'),
            'geolocation_enabled' => get_option('ai_events_pro_settings')['enable_geolocation'] ?? true,
            'default_radius' => get_option('ai_events_pro_settings')['default_radius'] ?? 25,
            'strings' => array(
                'loading' => __('Loading events...', 'ai-events-pro'),
                'no_events' => __('No events found.', 'ai-events-pro'),
                'location_error' => __('Unable to get your location. Please enter a location manually.', 'ai-events-pro'),
                'load_more' => __('Load More Events', 'ai-events-pro'),
                'show_less' => __('Show Less', 'ai-events-pro')
            )
        ));
    }

    public function template_redirect() {
        if (is_page('events') || is_singular('ai_event')) {
            add_filter('body_class', array($this, 'add_body_classes'));
        }
    }

    public function add_body_classes($classes) {
        $classes[] = 'ai-events-page';
        
        $settings = get_option('ai_events_pro_settings', array());
        $theme_mode = $settings['theme_mode'] ?? 'auto';
        
        if ($theme_mode !== 'auto') {
            $classes[] = 'ai-events-theme-' . $theme_mode;
        }
        
        return $classes;
    }

    public function ajax_get_events() {
        check_ajax_referer('ai_events_public_nonce', 'nonce');
        
        $location = sanitize_text_field($_POST['location'] ?? '');
        $radius = absint($_POST['radius'] ?? 25);
        $limit = absint($_POST['limit'] ?? 12);
        $offset = absint($_POST['offset'] ?? 0);
        $category = sanitize_text_field($_POST['category'] ?? '');
        $search = sanitize_text_field($_POST['search'] ?? '');
        $source = sanitize_text_field($_POST['source'] ?? 'all');
        
        $api_manager = new AI_Events_API_Manager();
        
        // Try to get cached events first
        $events = $api_manager->get_cached_events($location);
        
        if (empty($events)) {
            // Get fresh events from APIs
            $events = $api_manager->get_events($location, $radius, $limit + $offset);
            
            // Cache the events
            if (!empty($events)) {
                $api_manager->cache_events($events, $location);
            }
        }
        
        // Apply filters
        if (!empty($category) && $category !== 'all') {
            $events = array_filter($events, function($event) use ($category) {
                return stripos($event['category'], $category) !== false;
            });
        }
        
        if (!empty($search)) {
            $events = array_filter($events, function($event) use ($search) {
                return stripos($event['title'], $search) !== false || 
                       stripos($event['description'], $search) !== false;
            });
        }
        
        if (!empty($source) && $source !== 'all') {
            $events = array_filter($events, function($event) use ($source) {
                return $event['source'] === $source;
            });
        }
        
        // Apply AI enhancements if enabled
        $settings = get_option('ai_events_pro_settings', array());
        if (!empty($settings['enable_ai_features'])) {
            $events = $api_manager->enhance_with_ai($events);
        }
        
        // Paginate results
        $total_events = count($events);
        $events = array_slice($events, $offset, $limit);
        
        if (!empty($events)) {
            ob_start();
            foreach ($events as $event) {
                include AI_EVENTS_PRO_PLUGIN_DIR . 'public/partials/event-card.php';
            }
            $html = ob_get_clean();
            
            wp_send_json_success(array(
                'html' => $html,
                'total' => $total_events,
                'has_more' => ($offset + $limit) < $total_events
            ));
        } else {
            wp_send_json_error(__('No events found.', 'ai-events-pro'));
        }
    }

    public function ajax_toggle_theme_mode() {
        check_ajax_referer('ai_events_public_nonce', 'nonce');
        
        $mode = sanitize_text_field($_POST['mode'] ?? 'auto');
        
        if (!in_array($mode, array('light', 'dark', 'auto'))) {
            wp_send_json_error(__('Invalid theme mode.', 'ai-events-pro'));
        }
        
        // Store preference in cookie
        setcookie('ai_events_theme_mode', $mode, time() + (30 * 24 * 60 * 60), '/');
        
        wp_send_json_success(array('mode' => $mode));
    }

    public function get_user_location() {
        // Try to get location from various sources
        $location = '';
        
        // Check if location is stored in session
        if (isset($_SESSION['ai_events_user_location'])) {
            return $_SESSION['ai_events_user_location'];
        }
        
        // Try to get location from IP (basic implementation)
        $ip = $this->get_user_ip();
        if (!empty($ip)) {
            $location = $this->get_location_from_ip($ip);
            
            if (!empty($location)) {
                $_SESSION['ai_events_user_location'] = $location;
                return $location;
            }
        }
        
        return '';
    }

    private function get_user_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }

    private function get_location_from_ip($ip) {
        // Simple IP-based location detection
        // In production, you might want to use a service like MaxMind or IP-API
        
        $url = "http://ip-api.com/json/{$ip}";
        
        $response = wp_remote_get($url, array('timeout' => 5));
        
        if (is_wp_error($response)) {
            return '';
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['status']) && $data['status'] === 'success') {
            return $data['city'] . ', ' . $data['regionName'];
        }
        
        return '';
    }

    public function add_schema_markup($event) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $event['title'],
            'description' => $event['description'],
            'startDate' => $event['date'] . 'T' . ($event['time'] ?? '00:00:00'),
            'url' => $event['url'],
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode'
        );
        
        if (!empty($event['image'])) {
            $schema['image'] = $event['image'];
        }
        
        if (!empty($event['venue']) || !empty($event['location'])) {
            $schema['location'] = array(
                '@type' => 'Place',
                'name' => $event['venue'] ?? $event['location'],
                'address' => $event['location']
            );
        }
        
        if (!empty($event['organizer'])) {
            $schema['organizer'] = array(
                '@type' => 'Organization',
                'name' => $event['organizer']
            );
        }
        
        if (!empty($event['price']) && $event['price'] !== 'Free') {
            $schema['offers'] = array(
                '@type' => 'Offer',
                'price' => $event['price'],
                'priceCurrency' => 'USD',
                'availability' => 'https://schema.org/InStock'
            );
        }
        
        echo '<script type="application/ld+json">' . json_encode($schema) . '</script>';
    }
}