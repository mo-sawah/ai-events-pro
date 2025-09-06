<?php
/**
 * Events page template
 */

$api_manager = new AI_Events_API_Manager();
$settings = get_option('ai_events_pro_settings', array());
$user_location = '';

// Try to get user location
if (!empty($settings['enable_geolocation'])) {
    $public = new AI_Events_Public('ai-events-pro', AI_EVENTS_PRO_VERSION);
    $user_location = $public->get_user_location();
}
?>

<div class="ai-events-container" data-user-location="<?php echo esc_attr($user_location); ?>">
    <!-- Theme Toggle -->
    <div class="ai-events-header">
        <div class="ai-events-theme-toggle">
            <button id="theme-toggle" class="theme-toggle-btn" aria-label="<?php _e('Toggle theme', 'ai-events-pro'); ?>">
                <span class="theme-icon light-icon">☀️</span>
                <span class="theme-icon dark-icon">🌙</span>
            </button>
        </div>
    </div>

    <!-- Search and Filters -->
    <?php if ($atts['show_search'] === 'true' || $atts['show_filters'] === 'true'): ?>
    <div class="ai-events-controls">
        <?php if ($atts['show_search'] === 'true'): ?>
        <div class="ai-events-search">
            <div class="search-input-container">
                <input type="text" id="event-search" placeholder="<?php _e('Search events...', 'ai-events-pro'); ?>" />
                <button type="button" id="search-btn" class="search-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M21 21L16.514 16.506L21 21ZM19 10.5C19 15.194 15.194 19 10.5 19C5.806 19 2 15.194 2 10.5C2 5.806 5.806 2 10.5 2C15.194 2 19 5.806 19 10.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($atts['show_filters'] === 'true'): ?>
        <div class="ai-events-filters">
            <div class="filter-group">
                <label for="location-filter"><?php _e('Location:', 'ai-events-pro'); ?></label>
                <div class="location-input-container">
                    <input type="text" id="location-filter" placeholder="<?php _e('Enter location...', 'ai-events-pro'); ?>" value="<?php echo esc_attr($user_location); ?>" />
                    <button type="button" id="get-location-btn" class="location-btn" title="<?php _e('Use my location', 'ai-events-pro'); ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <path d="M12 8C13.1 8 14 8.9 14 10C14 11.1 13.1 12 12 12C10.9 12 10 11.1 10 10C10 8.9 10.9 8 12 8ZM21 10C21 7 19 5 12 5C5 5 3 7 3 10C3 13 12 22 12 22C12 22 21 13 21 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="filter-group">
                <label for="radius-filter"><?php _e('Radius:', 'ai-events-pro'); ?></label>
                <select id="radius-filter">
                    <option value="5">5 <?php _e('miles', 'ai-events-pro'); ?></option>
                    <option value="10">10 <?php _e('miles', 'ai-events-pro'); ?></option>
                    <option value="25" selected>25 <?php _e('miles', 'ai-events-pro'); ?></option>
                    <option value="50">50 <?php _e('miles', 'ai-events-pro'); ?></option>
                    <option value="100">100 <?php _e('miles', 'ai-events-pro'); ?></option>
                </select>
            </div>

            <div class="filter-group">
                <label for="category-filter"><?php _e('Category:', 'ai-events-pro'); ?></label>
                <select id="category-filter">
                    <option value="all"><?php _e('All Categories', 'ai-events-pro'); ?></option>
                    <option value="music"><?php _e('Music', 'ai-events-pro'); ?></option>
                    <option value="sports"><?php _e('Sports', 'ai-events-pro'); ?></option>
                    <option value="arts"><?php _e('Arts & Culture', 'ai-events-pro'); ?></option>
                    <option value="food"><?php _e('Food & Drink', 'ai-events-pro'); ?></option>
                    <option value="business"><?php _e('Business', 'ai-events-pro'); ?></option>
                    <option value="health"><?php _e('Health & Wellness', 'ai-events-pro'); ?></option>
                    <option value="technology"><?php _e('Technology', 'ai-events-pro'); ?></option>
                    <option value="education"><?php _e('Education', 'ai-events-pro'); ?></option>
                    <option value="family"><?php _e('Family', 'ai-events-pro'); ?></option>
                </select>
            </div>

            <div class="filter-group">
                <label for="source-filter"><?php _e('Source:', 'ai-events-pro'); ?></label>
                <select id="source-filter">
                    <option value="all"><?php _e('All Sources', 'ai-events-pro'); ?></option>
                    <option value="eventbrite"><?php _e('Eventbrite', 'ai-events-pro'); ?></option>
                    <option value="ticketmaster"><?php _e('Ticketmaster', 'ai-events-pro'); ?></option>
                    <option value="custom"><?php _e('Local Events', 'ai-events-pro'); ?></option>
                </select>
            </div>

            <button type="button" id="apply-filters-btn" class="apply-filters-btn">
                <?php _e('Apply Filters', 'ai-events-pro'); ?>
            </button>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Loading State -->
    <div id="events-loading" class="events-loading" style="display: none;">
        <div class="loading-spinner"></div>
        <p><?php _e('Loading events...', 'ai-events-pro'); ?></p>
    </div>

    <!-- Events Grid -->
    <div id="events-container" class="ai-events-grid layout-<?php echo esc_attr($atts['layout']); ?>">
        <!-- Events will be loaded here via AJAX -->
    </div>

    <!-- Load More Button -->
    <div class="ai-events-pagination">
        <button type="button" id="load-more-btn" class="load-more-btn" style="display: none;">
            <?php _e('Load More Events', 'ai-events-pro'); ?>
        </button>
    </div>

    <!-- No Events Message -->
    <div id="no-events-message" class="no-events-message" style="display: none;">
        <div class="no-events-icon">📅</div>
        <h3><?php _e('No events found', 'ai-events-pro'); ?></h3>
        <p><?php _e('Try adjusting your search criteria or check back later for new events.', 'ai-events-pro'); ?></p>
    </div>
</div>