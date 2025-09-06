<?php
/**
 * Events page template (Compact White UI)
 * - Keeps original element IDs so existing JS continues to work.
 */

$api_manager = new AI_Events_API_Manager();
$settings = get_option('ai_events_pro_settings', array());
$user_location = '';

if (!empty($settings['enable_geolocation'])) {
    $public = new AI_Events_Public('ai-events-pro', AI_EVENTS_PRO_VERSION);
    $user_location = $public->get_user_location();
}

// Shortcode atts are available via $atts from the caller.
$layout = isset($atts['layout']) ? $atts['layout'] : 'grid';
?>
<div class="aiep ai-events-container" data-user-location="<?php echo esc_attr($user_location); ?>">

  <!-- Theme Toggle -->
  <div class="ae-theme-toggle">
    <button id="theme-toggle" class="ae-toggle" type="button" aria-label="<?php esc_attr_e('Toggle theme', 'ai-events-pro'); ?>">
      <span class="ae-toggle__icon">☀️</span>
      <span class="ae-toggle__label"><?php esc_html_e('Light', 'ai-events-pro'); ?></span>
    </button>
  </div>

  <!-- Filters -->
  <section class="ae-filters" aria-labelledby="ae-filters-title">
    <div class="ae-filters__head">
      <span class="ae-bubble">🔎</span>
      <div>
        <div id="ae-filters-title" class="ae-filters__title"><?php _e('Event Filters', 'ai-events-pro'); ?></div>
        <div class="ae-filters__desc"><?php _e('Use location, radius, category, and source to refine your results', 'ai-events-pro'); ?></div>
      </div>
    </div>

    <div class="ae-fields">
      <!-- Location text (same ID used by JS) -->
      <div class="ae-field ae-field--span-4">
        <label class="ae-label" for="location-filter"><?php _e('Location', 'ai-events-pro'); ?></label>
        <div class="ae-input-wrap">
          <input type="text" id="location-filter" class="ae-input" placeholder="<?php esc_attr_e('Type a city (e.g., New York, NY)', 'ai-events-pro'); ?>" value="<?php echo esc_attr($user_location); ?>"/>
          <button id="get-location-btn" type="button" class="ae-geo-btn" title="<?php esc_attr_e('Use my location', 'ai-events-pro'); ?>">📍</button>
        </div>
      </div>

      <!-- Radius -->
      <div class="ae-field ae-field--span-3">
        <label class="ae-label" for="radius-filter"><?php _e('Radius', 'ai-events-pro'); ?></label>
        <select id="radius-filter" class="ae-select">
          <?php
          $radius_default = isset($settings['default_radius']) ? intval($settings['default_radius']) : 25;
          $radii = array(5, 10, 25, 50, 100);
          foreach ($radii as $r) {
              printf('<option value="%1$d"%2$s>%1$d %3$s</option>',
                  $r,
                  selected($r, $radius_default, false),
                  esc_html__('miles', 'ai-events-pro')
              );
          }
          ?>
        </select>
      </div>

      <!-- Category -->
      <div class="ae-field ae-field--span-3">
        <label class="ae-label" for="category-filter"><?php _e('Category', 'ai-events-pro'); ?></label>
        <select id="category-filter" class="ae-select">
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

      <!-- Source -->
      <div class="ae-field ae-field--span-3">
        <label class="ae-label" for="source-filter"><?php _e('Source', 'ai-events-pro'); ?></label>
        <select id="source-filter" class="ae-select">
          <option value="all"><?php _e('All Sources', 'ai-events-pro'); ?></option>
          <option value="eventbrite"><?php _e('Eventbrite', 'ai-events-pro'); ?></option>
          <option value="ticketmaster"><?php _e('Ticketmaster', 'ai-events-pro'); ?></option>
          <option value="custom"><?php _e('Local Events', 'ai-events-pro'); ?></option>
        </select>
      </div>

      <!-- Apply -->
      <div class="ae-field ae-field--submit">
        <button id="apply-filters-btn" class="ae-btn ae-btn--primary" type="button"><?php _e('Apply Filters', 'ai-events-pro'); ?></button>
      </div>
    </div>
  </section>

  <!-- Search -->
  <section class="ae-search">
    <div class="ae-search__wrap">
      <span class="ae-search__icon">🔎</span>
      <input id="event-search" type="search" placeholder="<?php esc_attr_e('Search events by name, keyword, or description…', 'ai-events-pro'); ?>"/>
    </div>
  </section>

  <!-- Loading State -->
  <div id="events-loading" class="events-loading" style="display:none;">
    <div class="loading-spinner"></div>
    <p><?php _e('Loading events…', 'ai-events-pro'); ?></p>
  </div>

  <!-- Results -->
  <div id="events-container" class="aiep-grid layout-<?php echo esc_attr($layout); ?>">
    <!-- Event cards inserted via AJAX -->
  </div>

  <!-- Pagination -->
  <div class="aiep-pager">
    <button type="button" id="load-more-btn" class="ae-btn ae-btn--primary" style="display:none;"><?php _e('Load More', 'ai-events-pro'); ?></button>
  </div>

  <!-- No Events -->
  <div id="no-events-message" class="no-events-message" style="display:none;">
    <div>📅</div>
    <h3><?php _e('No events found', 'ai-events-pro'); ?></h3>
    <p><?php _e('Try adjusting your search criteria or check back later for new events.', 'ai-events-pro'); ?></p>
  </div>
</div>