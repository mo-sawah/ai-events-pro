<?php

/**
 * Fired during plugin activation.
 */
class AI_Events_Activator {

    public static function activate() {
        global $wpdb;
        
        // Create events cache table
        $table_name = $wpdb->prefix . 'ai_events_cache';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_id varchar(255) NOT NULL,
            source varchar(50) NOT NULL,
            data longtext NOT NULL,
            location varchar(255) DEFAULT '',
            cached_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY event_source (event_id, source),
            KEY location (location),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Set default options (align with settings structure)
        $default_settings = array(
            'events_per_page' => 12,
            'enable_geolocation' => true,
            'default_radius' => 25,
            'cache_duration' => 3600,
            'theme_mode' => 'auto',
            'enabled_apis' => array(
                'custom' => true,
                'eventbrite' => false,
                'ticketmaster' => false,
            ),
        );
        add_option('ai_events_pro_settings', $default_settings);

        // AI settings default in a separate option
        $default_ai_settings = array(
            'enable_ai_features' => false,
            'ai_categorization' => true,
            'ai_summaries' => true,
            'openrouter_api_key' => '',
        );
        add_option('ai_events_pro_ai_settings', $default_ai_settings);
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Create events page
        self::create_events_page();
    }
    
    private static function create_events_page() {
        $page_title = 'Events';
        $page_content = '[ai_events_page]';
        $page_slug = 'events';
        
        $page = get_page_by_path($page_slug);
        
        if (!$page) {
            wp_insert_post(array(
                'post_title' => $page_title,
                'post_content' => $page_content,
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => $page_slug,
                'comment_status' => 'closed',
                'ping_status' => 'closed'
            ));
        }
    }
}
