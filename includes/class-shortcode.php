<?php

/**
 * Shortcode functionality.
 */
class AI_Events_Shortcode {

    public function init_shortcodes() {
        add_shortcode('ai_events_page', array($this, 'events_page_shortcode'));
        add_shortcode('ai_events_widget', array($this, 'events_widget_shortcode'));
        add_shortcode('ai_events_list', array($this, 'events_list_shortcode'));
    }
    
    public function events_page_shortcode($atts) {
        $atts = shortcode_atts(array(
            'location' => '',
            'radius' => 25,
            'limit' => 12,
            'category' => '',
            'show_filters' => 'true',
            'show_search' => 'true',
            'layout' => 'grid'
        ), $atts);
        
        ob_start();
        include AI_EVENTS_PRO_PLUGIN_DIR . 'public/partials/events-page.php';
        return ob_get_clean();
    }
    
    public function events_widget_shortcode($atts) {
        $atts = shortcode_atts(array(
            'location' => '',
            'radius' => 25,
            'limit' => 5,
            'category' => '',
            'layout' => 'list',
            'show_image' => 'true',
            'show_date' => 'true',
            'show_location' => 'true'
        ), $atts);
        
        ob_start();
        include AI_EVENTS_PRO_PLUGIN_DIR . 'public/partials/events-widget.php';
        return ob_get_clean();
    }
    
    public function events_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'location' => '',
            'radius' => 25,
            'limit' => 10,
            'category' => '',
            'source' => 'all', // all, eventbrite, ticketmaster, custom
            'orderby' => 'date', // date, title, ai_score
            'order' => 'ASC'
        ), $atts);
        
        $api_manager = new AI_Events_API_Manager();
        $events = $api_manager->get_events($atts['location'], $atts['radius'], $atts['limit']);
        
        // Filter by source if specified
        if ($atts['source'] !== 'all') {
            $events = array_filter($events, function($event) use ($atts) {
                return $event['source'] === $atts['source'];
            });
        }
        
        // Filter by category if specified
        if (!empty($atts['category'])) {
            $events = array_filter($events, function($event) use ($atts) {
                return stripos($event['category'], $atts['category']) !== false
                    || (!empty($event['ai_category']) && stripos($event['ai_category'], $atts['category']) !== false);
            });
        }

        // Sort if applicable
        $orderby = strtolower($atts['orderby']);
        $order = strtoupper($atts['order']) === 'DESC' ? 'DESC' : 'ASC';

        usort($events, function($a, $b) use ($orderby, $order) {
            $result = 0;
            switch ($orderby) {
                case 'title':
                    $ta = $a['title'] ?? '';
                    $tb = $b['title'] ?? '';
                    $result = strcasecmp($ta, $tb);
                    break;
                case 'ai_score':
                    $sa = intval($a['ai_score'] ?? 0);
                    $sb = intval($b['ai_score'] ?? 0);
                    $result = $sa <=> $sb;
                    break;
                case 'date':
                default:
                    $da = !empty($a['date']) ? strtotime(($a['date'] ?? '') . ' ' . ($a['time'] ?? '00:00')) : 0;
                    $db = !empty($b['date']) ? strtotime(($b['date'] ?? '') . ' ' . ($b['time'] ?? '00:00')) : 0;
                    $result = $da <=> $db;
                    break;
            }
            return $order === 'DESC' ? -$result : $result;
        });
        
        ob_start();
        ?>
        <div class="ai-events-list">
            <?php if (empty($events)): ?>
                <p class="no-events"><?php _e('No events found.', 'ai-events-pro'); ?></p>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <?php include AI_EVENTS_PRO_PLUGIN_DIR . 'public/partials/event-card.php'; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
