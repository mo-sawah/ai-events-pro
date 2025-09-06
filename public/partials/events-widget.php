<?php
/**
 * Events widget template
 */

$api_manager = new AI_Events_API_Manager();
$events = $api_manager->get_events($atts['location'], $atts['radius'], $atts['limit']);

// Apply category filter if specified
if (!empty($atts['category'])) {
    $events = array_filter($events, function($event) use ($atts) {
        return stripos($event['category'], $atts['category']) !== false;
    });
}
?>

<div class="ai-events-widget layout-<?php echo esc_attr($atts['layout']); ?>">
    <?php if (empty($events)): ?>
        <div class="widget-no-events">
            <p><?php _e('No events found.', 'ai-events-pro'); ?></p>
        </div>
    <?php else: ?>
        <div class="widget-events-list">
            <?php foreach ($events as $event): ?>
                <div class="widget-event-item">
                    <?php if ($atts['show_image'] === 'true' && !empty($event['image'])): ?>
                    <div class="widget-event-image">
                        <img src="<?php echo esc_url($event['image']); ?>" alt="<?php echo esc_attr($event['title']); ?>" loading="lazy" />
                    </div>
                    <?php endif; ?>

                    <div class="widget-event-content">
                        <h4 class="widget-event-title">
                            <?php if (!empty($event['url'])): ?>
                                <a href="<?php echo esc_url($event['url']); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php echo esc_html($event['title']); ?>
                                </a>
                            <?php else: ?>
                                <?php echo esc_html($event['title']); ?>
                            <?php endif; ?>
                        </h4>

                        <?php if ($atts['show_date'] === 'true' && !empty($event['date'])): ?>
                        <div class="widget-event-date">
                            <?php echo esc_html(date('M j, Y', strtotime($event['date']))); ?>
                            <?php if (!empty($event['time'])): ?>
                                <?php echo esc_html(date('g:i A', strtotime($event['time']))); ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($atts['show_location'] === 'true' && !empty($event['location'])): ?>
                        <div class="widget-event-location">
                            <?php echo esc_html($event['venue'] ?: $event['location']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="widget-view-all">
            <a href="<?php echo esc_url(site_url('/events')); ?>" class="view-all-link">
                <?php _e('View All Events', 'ai-events-pro'); ?>
            </a>
        </div>
    <?php endif; ?>
</div>