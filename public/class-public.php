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

        // Enqueue both styles and scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function enqueue_styles() {
        // Base UI CSS
        wp_enqueue_style(
            $this->plugin_name,
            AI_EVENTS_PRO_PLUGIN_URL . 'public/css/ai-events-public.css',
            array(),
            $this->version,
            'all'
        );

        // Optional theme modes CSS (if you keep separate)
        wp_enqueue_style(
            $this->plugin_name . '-theme-modes',
            AI_EVENTS_PRO_PLUGIN_URL . 'public/css/theme-modes.css',
            array($this->plugin_name),
            $this->version,
            'all'
        );

        // Inject color variables (light and dark) from settings so the whole UI is themeable
        $settings = get_option('ai_events_pro_settings', array());
        $light = isset($settings['colors_light']) ? (array)$settings['colors_light'] : array();
        $dark  = isset($settings['colors_dark'])  ? (array)$settings['colors_dark']  : array();

        $css = '
        .aiep{
          --ae-primary:      ' . esc_attr($light['primary']      ?? '#2563eb') . ';
          --ae-primary-600:  ' . esc_attr($light['primary_600']  ?? '#1d4ed8') . ';
          --ae-text:         ' . esc_attr($light['text']         ?? '#0f172a') . ';
          --ae-text-soft:    ' . esc_attr($light['text_soft']    ?? '#475569') . ';
          --ae-bg:           ' . esc_attr($light['bg']           ?? '#f5f7fb') . ';
          --ae-surface:      ' . esc_attr($light['surface']      ?? '#ffffff') . ';
          --ae-surface-alt:  ' . esc_attr($light['surface_alt']  ?? '#f8fafc') . ';
          --ae-border:       ' . esc_attr($light['border']       ?? '#e5e7eb') . ';
        }
        body.ai-events-theme-dark .aiep{
          --ae-primary:      ' . esc_attr($dark['primary']       ?? '#60a5fa') . ';
          --ae-primary-600:  ' . esc_attr($dark['primary_600']   ?? '#3b82f6') . ';
          --ae-text:         ' . esc_attr($dark['text']          ?? '#e5e7eb') . ';
          --ae-text-soft:    ' . esc_attr($dark['text_soft']     ?? '#cbd5e1') . ';
          --ae-bg:           ' . esc_attr($dark['bg']            ?? '#0f172a') . ';
          --ae-surface:      ' . esc_attr($dark['surface']       ?? '#111827') . ';
          --ae-surface-alt:  ' . esc_attr($dark['surface_alt']   ?? '#0b1220') . ';
          --ae-border:       ' . esc_attr($dark['border']        ?? '#273244') . ';
        }';

        wp_add_inline_style($this->plugin_name, $css);
    }

    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            AI_EVENTS_PRO_PLUGIN_URL . 'public/js/ai-events-public.js',
            array('jquery'),
            $this->version,
            true // load in footer
        );

        wp_enqueue_script(
            $this->plugin_name . '-theme-switcher',
            AI_EVENTS_PRO_PLUGIN_URL . 'public/js/theme-switcher.js',
            array('jquery'),
            $this->version,
            true // load in footer
        );

        $general_settings = get_option('ai_events_pro_settings', array());
        $events_per_page  = isset($general_settings['events_per_page']) ? max(1, (int)$general_settings['events_per_page']) : 12;
        $default_mode     = isset($general_settings['default_theme_mode']) ? $general_settings['default_theme_mode'] : 'auto';

        wp_localize_script($this->plugin_name, 'ai_events_public', array(
            'ajax_url'            => admin_url('admin-ajax.php'),
            'nonce'               => wp_create_nonce('ai_events_public_nonce'),
            'geolocation_enabled' => $general_settings['enable_geolocation'] ?? true,
            'default_radius'      => $general_settings['default_radius'] ?? 25,
            'per_page'            => $events_per_page,
            'default_mode'        => $default_mode,
            'strings'             => array(
                'loading'        => __('Loading events...', 'ai-events-pro'),
                'no_events'      => __('No events found.', 'ai-events-pro'),
                'location_error' => __('Unable to get your location. Please enter a location manually.', 'ai-events-pro'),
                'load_more'      => __('Load More Events', 'ai-events-pro'),
                'show_less'      => __('Show Less', 'ai-events-pro')
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
        $radius   = absint($_POST['radius'] ?? 25);
        $limit    = absint($_POST['limit'] ?? 12);
        $offset   = absint($_POST['offset'] ?? 0);
        $category = sanitize_text_field($_POST['category'] ?? '');
        $search   = sanitize_text_field($_POST['search'] ?? '');
        $source   = sanitize_text_field($_POST['source'] ?? 'all');

        // Always fetch one extra record so we can detect "has more"
        $need_count = $offset + $limit + 1;

        $api_manager = new AI_Events_API_Manager();

        // Try cache first
        $events = $api_manager->get_cached_events($location);

        // If no cache or not enough cached items for this page, fetch fresh enough to answer
        if (empty($events) || count($events) < $need_count) {
            $fetched = $api_manager->get_events($location, $radius, $need_count);
            if (!empty($fetched)) {
                $events = $fetched;
                $api_manager->cache_events($events, $location);
            }
        }

        if (empty($events)) {
            wp_send_json_error(__('No events found.', 'ai-events-pro'));
        }

        // Apply filters to the items we have
        $filtered = array_values(array_filter($events, function ($event) use ($category, $search, $source) {

            if (!empty($source) && $source !== 'all') {
                if (!isset($event['source']) || strtolower($event['source']) !== strtolower($source)) {
                    return false;
                }
            }

            if (!empty($category) && $category !== 'all') {
                $cat1 = isset($event['category']) ? (string)$event['category'] : '';
                $cat2 = isset($event['ai_category']) ? (string)$event['ai_category'] : '';
                if (stripos($cat1, $category) === false && stripos($cat2, $category) === false) {
                    return false;
                }
            }

            if (!empty($search)) {
                $title = isset($event['title']) ? (string)$event['title'] : '';
                $desc  = isset($event['description']) ? (string)$event['description'] : '';
                if (stripos($title, $search) === false && stripos($desc, $search) === false) {
                    return false;
                }
            }

            return true;
        }));

        // Optional AI enhancement
        $ai_settings = get_option('ai_events_pro_ai_settings', array());
        if (!empty($ai_settings['enable_ai_features']) && !empty($ai_settings['openrouter_api_key'])) {
            $filtered = $api_manager->enhance_with_ai($filtered, $ai_settings);
        }

        $total_filtered = count($filtered);

        // Slice for this page
        $page_events = array_slice($filtered, $offset, $limit);

        if (!empty($page_events)) {
            ob_start();
            foreach ($page_events as $event) {
                include AI_EVENTS_PRO_PLUGIN_DIR . 'public/partials/event-card.php';
            }
            $html = ob_get_clean();

            // With one extra fetched, "has more" is true if there are more filtered items beyond this page.
            $has_more = ($offset + $limit) < $total_filtered;

            wp_send_json_success(array(
                'html'     => $html,
                'total'    => $total_filtered,
                'has_more' => $has_more,
            ));
        } else {
            wp_send_json_error(__('No events found.', 'ai-events-pro'));
        }
    }

    public function ajax_toggle_theme_mode() {
        check_ajax_referer('ai_events_public_nonce', 'nonce');

        $mode = sanitize_text_field($_POST['mode'] ?? 'auto');

        if (!in_array($mode, array('light', 'dark', 'auto'), true)) {
            wp_send_json_error(__('Invalid theme mode.', 'ai-events-pro'));
        }

        // Store preference in cookie (30 days)
        setcookie('ai_events_theme_mode', $mode, time() + (30 * DAY_IN_SECONDS), '/');

        wp_send_json_success(array('mode' => $mode));
    }

    public function get_user_location() {
        // Use cookie-based caching rather than PHP sessions
        if (!empty($_COOKIE['ai_events_user_location'])) {
            return sanitize_text_field(wp_unslash($_COOKIE['ai_events_user_location']));
        }

        // Try to get location from IP (basic implementation)
        $ip = $this->get_user_ip();
        if (!empty($ip)) {
            $location = $this->get_location_from_ip($ip);
            if (!empty($location)) {
                // Cache to cookie for 7 days
                setcookie('ai_events_user_location', $location, time() + (7 * DAY_IN_SECONDS), '/');
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
            // Can contain multiple IPs - take the first public one
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            foreach ($ips as $candidate) {
                $candidate = trim($candidate);
                if (filter_var($candidate, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    $ip = $candidate;
                    break;
                }
            }
        }

        if (empty($ip) && !empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }

    private function get_location_from_ip($ip) {
        // Use HTTPS provider
        // ipapi.co supports /json/{ip}
        $url = "https://ipapi.co/{$ip}/json/";

        $response = wp_remote_get($url, array('timeout' => 5));

        if (is_wp_error($response)) {
            return '';
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!is_array($data)) {
            return '';
        }

        $city = $data['city'] ?? '';
               $region = $data['region'] ?? ($data['region_code'] ?? '');

        if (!empty($city) && !empty($region)) {
            return $city . ', ' . $region;
        }

        if (!empty($city)) {
            return $city;
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
            // Try to derive currency from price string (e.g., "USD 10.00 - 20.00")
            $currency = 'USD';
            if (preg_match('/^([A-Z]{3})\b/', (string)$event['price'], $m)) {
                $currency = $m[1];
            }

            $schema['offers'] = array(
                '@type' => 'Offer',
                'price' => $event['price'],
                'priceCurrency' => $currency,
                'availability' => 'https://schema.org/InStock'
            );
        }

        echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
    }
}