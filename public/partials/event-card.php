<?php
/**
 * Individual event card template
 */

$event_date = !empty($event['date']) ? date('M j, Y', strtotime($event['date'])) : '';
$event_time = !empty($event['time']) ? date('g:i A', strtotime($event['time'])) : '';
$event_datetime = $event_date . (!empty($event_time) ? ' at ' . $event_time : '');
$source_class = 'source-' . sanitize_html_class($event['source']);
$has_image = !empty($event['image']);

// Generate schema markup for this event
$public = new AI_Events_Public('ai-events-pro', AI_EVENTS_PRO_VERSION);
$public->add_schema_markup($event);
?>

<article class="ai-event-card <?php echo esc_attr($source_class); ?>" data-event-id="<?php echo esc_attr($event['id']); ?>" data-source="<?php echo esc_attr($event['source']); ?>">
    <?php if ($has_image): ?>
    <div class="event-image">
        <img src="<?php echo esc_url($event['image']); ?>" alt="<?php echo esc_attr($event['title']); ?>" loading="lazy" />
        <div class="event-source-badge">
            <?php echo esc_html(ucfirst($event['source'])); ?>
        </div>
        <?php if (!empty($event['ai_score'])): ?>
        <div class="ai-score-badge" title="<?php _e('AI Relevance Score', 'ai-events-pro'); ?>">
            <span class="ai-icon">🤖</span>
            <?php echo esc_html($event['ai_score']); ?>%
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="event-content">
        <header class="event-header">
            <h3 class="event-title">
                <?php if (!empty($event['url'])): ?>
                    <a href="<?php echo esc_url($event['url']); ?>" target="_blank" rel="noopener noreferrer">
                        <?php echo esc_html($event['title']); ?>
                    </a>
                <?php else: ?>
                    <?php echo esc_html($event['title']); ?>
                <?php endif; ?>
            </h3>
            
            <?php if (!empty($event['category']) || !empty($event['ai_category'])): ?>
            <div class="event-category">
                <?php 
                $category = !empty($event['ai_category']) ? $event['ai_category'] : $event['category'];
                echo esc_html($category);
                ?>
            </div>
            <?php endif; ?>
        </header>

        <div class="event-meta">
            <div class="event-datetime">
                <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M19 3H5C3.89 3 3 3.89 3 5V19C3 20.1 3.89 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.89 20.1 3 19 3ZM19 19H5V8H19V19ZM7 10H9V12H7V10ZM11 10H13V12H11V10ZM15 10H17V12H15V10Z" fill="currentColor"/>
                </svg>
                <span><?php echo esc_html($event_datetime); ?></span>
            </div>

            <?php if (!empty($event['location'])): ?>
            <div class="event-location">
                <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M12 2C8.13 2 5 5.13 5 9C5 14.25 12 22 12 22C12 22 19 14.25 19 9C19 5.13 15.87 2 12 2ZM12 11.5C10.62 11.5 9.5 10.38 9.5 9C9.5 7.62 10.62 6.5 12 6.5C13.38 6.5 14.5 7.62 14.5 9C14.5 10.38 13.38 11.5 12 11.5Z" fill="currentColor"/>
                </svg>
                <span><?php echo esc_html($event['venue'] ?: $event['location']); ?></span>
            </div>
            <?php endif; ?>

            <?php if (!empty($event['price'])): ?>
            <div class="event-price">
                <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M11.8 10.9C9.53 10.31 8.8 9.7 8.8 8.75C8.8 7.66 9.81 6.9 11.5 6.9C13.28 6.9 13.94 7.75 14 9H16.21C16.14 7.28 15.09 5.7 13 5.19V3H10V5.16C8.06 5.58 6.5 6.84 6.5 8.77C6.5 11.08 8.41 12.23 11.2 12.9C13.7 13.5 14.2 14.38 14.2 15.31C14.2 16 13.71 17.1 11.5 17.1C9.44 17.1 8.63 16.18 8.5 15H6.32C6.44 17.19 8.08 18.42 10 18.83V21H13V18.85C14.95 18.5 16.5 17.35 16.5 15.3C16.5 12.46 14.07 11.5 11.8 10.9Z" fill="currentColor"/>
                </svg>
                <span class="price-value <?php echo $event['price'] === 'Free' ? 'free' : 'paid'; ?>">
                    <?php echo esc_html($event['price']); ?>
                </span>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($event['description']) || !empty($event['ai_summary'])): ?>
        <div class="event-description">
            <?php 
            $description = !empty($event['ai_summary']) ? $event['ai_summary'] : $event['description'];
            echo wp_kses_post(wp_trim_words($description, 30, '...'));
            ?>
        </div>
        <?php endif; ?>

        <footer class="event-footer">
            <div class="event-actions">
                <?php if (!empty($event['url'])): ?>
                <a href="<?php echo esc_url($event['url']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                    <?php _e('View Details', 'ai-events-pro'); ?>
                    <svg class="icon" width="14" height="14" viewBox="0 0 24 24" fill="none">
                        <path d="M7 17L17 7M17 7H7M17 7V17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <?php endif; ?>
                
                <button type="button" class="btn btn-secondary share-event" data-event-title="<?php echo esc_attr($event['title']); ?>" data-event-url="<?php echo esc_url($event['url']); ?>">
                    <?php _e('Share', 'ai-events-pro'); ?>
                    <svg class="icon" width="14" height="14" viewBox="0 0 24 24" fill="none">
                        <path d="M18 8C19.6569 8 21 6.65685 21 5C21 3.34315 19.6569 2 18 2C16.3431 2 15 3.34315 15 5C15 5.58296 15.1616 6.12953 15.4463 6.59482L8.55369 10.4052C8.12953 9.83839 7.58296 9.5 7 9.5C5.34315 9.5 4 10.8431 4 12.5C4 14.1569 5.34315 15.5 7 15.5C7.58296 15.5 8.12953 15.1616 8.55369 14.5948L15.4463 18.4052C15.1616 18.8705 15 19.417 15 20C15 21.6569 16.3431 23 18 23C19.6569 23 21 21.6569 21 20C21 18.3431 19.6569 17 18 17C17.417 17 16.8705 17.1616 16.4052 17.4463L9.51184 13.5948C9.83839 13.1295 10 12.583 10 12C10 11.417 9.83839 10.8705 9.51184 10.4052L16.4052 6.55369C16.8705 6.83839 17.417 7 18 7V8Z" stroke="currentColor" stroke-width="1.5"/>
                    </svg>
                </button>
            </div>

            <?php if (!empty($event['organizer'])): ?>
            <div class="event-organizer">
                <small><?php _e('by', 'ai-events-pro'); ?> <?php echo esc_html($event['organizer']); ?></small>
            </div>
            <?php endif; ?>
        </footer>
    </div>
</article>