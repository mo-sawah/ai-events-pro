<?php

/**
 * Custom post type for events.
 */
class AI_Events_Post_Type {

    public function register_post_type() {
        $args = array(
            'labels' => array(
                'name' => __('Events', 'ai-events-pro'),
                'singular_name' => __('Event', 'ai-events-pro'),
                'add_new' => __('Add New Event', 'ai-events-pro'),
                'add_new_item' => __('Add New Event', 'ai-events-pro'),
                'edit_item' => __('Edit Event', 'ai-events-pro'),
                'new_item' => __('New Event', 'ai-events-pro'),
                'view_item' => __('View Event', 'ai-events-pro'),
                'search_items' => __('Search Events', 'ai-events-pro'),
                'not_found' => __('No events found', 'ai-events-pro'),
                'not_found_in_trash' => __('No events found in Trash', 'ai-events-pro'),
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'events'),
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'menu_icon' => 'dashicons-calendar-alt',
            'show_in_rest' => true,
        );
        
        register_post_type('ai_event', $args);
        
        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
    }
    
    public function register_taxonomies() {
        // Event Categories
        register_taxonomy('event_category', 'ai_event', array(
            'labels' => array(
                'name' => __('Event Categories', 'ai-events-pro'),
                'singular_name' => __('Event Category', 'ai-events-pro'),
            ),
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'event-category'),
        ));
        
        // Event Tags
        register_taxonomy('event_tag', 'ai_event', array(
            'labels' => array(
                'name' => __('Event Tags', 'ai-events-pro'),
                'singular_name' => __('Event Tag', 'ai-events-pro'),
            ),
            'hierarchical' => false,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'event-tag'),
        ));
    }
    
    public function add_meta_boxes() {
        add_meta_box(
            'ai_event_details',
            __('Event Details', 'ai-events-pro'),
            array($this, 'event_details_meta_box'),
            'ai_event',
            'normal',
            'high'
        );
    }
    
    public function event_details_meta_box($post) {
        wp_nonce_field('ai_event_meta_box', 'ai_event_meta_box_nonce');
        
        $date = get_post_meta($post->ID, '_event_date', true);
        $time = get_post_meta($post->ID, '_event_time', true);
        $end_date = get_post_meta($post->ID, '_event_end_date', true);
        $end_time = get_post_meta($post->ID, '_event_end_time', true);
        $location = get_post_meta($post->ID, '_event_location', true);
        $venue = get_post_meta($post->ID, '_event_venue', true);
        $price = get_post_meta($post->ID, '_event_price', true);
        $url = get_post_meta($post->ID, '_event_url', true);
        $organizer = get_post_meta($post->ID, '_event_organizer', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="event_date"><?php _e('Event Date', 'ai-events-pro'); ?></label></th>
                <td><input type="date" name="event_date" id="event_date" value="<?php echo esc_attr($date); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="event_time"><?php _e('Event Time', 'ai-events-pro'); ?></label></th>
                <td><input type="time" name="event_time" id="event_time" value="<?php echo esc_attr($time); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="event_end_date"><?php _e('End Date', 'ai-events-pro'); ?></label></th>
                <td><input type="date" name="event_end_date" id="event_end_date" value="<?php echo esc_attr($end_date); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="event_end_time"><?php _e('End Time', 'ai-events-pro'); ?></label></th>
                <td><input type="time" name="event_end_time" id="event_end_time" value="<?php echo esc_attr($end_time); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="event_venue"><?php _e('Venue', 'ai-events-pro'); ?></label></th>
                <td><input type="text" name="event_venue" id="event_venue" value="<?php echo esc_attr($venue); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="event_location"><?php _e('Location', 'ai-events-pro'); ?></label></th>
                <td><input type="text" name="event_location" id="event_location" value="<?php echo esc_attr($location); ?>" class="regular-text" placeholder="City, State or Address" /></td>
            </tr>
            <tr>
                <th><label for="event_price"><?php _e('Price', 'ai-events-pro'); ?></label></th>
                <td><input type="text" name="event_price" id="event_price" value="<?php echo esc_attr($price); ?>" class="regular-text" placeholder="Free, $10, $10-20, etc." /></td>
            </tr>
            <tr>
                <th><label for="event_url"><?php _e('Event URL', 'ai-events-pro'); ?></label></th>
                <td><input type="url" name="event_url" id="event_url" value="<?php echo esc_attr($url); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="event_organizer"><?php _e('Organizer', 'ai-events-pro'); ?></label></th>
                <td><input type="text" name="event_organizer" id="event_organizer" value="<?php echo esc_attr($organizer); ?>" class="regular-text" /></td>
            </tr>
        </table>
        <?php
    }
    
    public function save_meta_boxes($post_id) {
        if (!isset($_POST['ai_event_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['ai_event_meta_box_nonce'], 'ai_event_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $fields = array(
            'event_date',
            'event_time',
            'event_end_date',
            'event_end_time',
            'event_location',
            'event_venue',
            'event_price',
            'event_url',
            'event_organizer'
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                if ($field === 'event_url') {
                    update_post_meta($post_id, '_' . $field, esc_url_raw($_POST[$field]));
                } else {
                    update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
                }
            }
        }
    }
}
