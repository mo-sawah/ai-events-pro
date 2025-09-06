<?php
/**
 * Events page template (Compact White UI, updated top filters + solid icons)
 */
$api_manager = new AI_Events_API_Manager();
$settings = get_option('ai_events_pro_settings', array());
$user_location = '';

if (!empty($settings['enable_geolocation'])) {
    $public = new AI_Events_Public('ai-events-pro', AI_EVENTS_PRO_VERSION);
    $user_location = $public->get_user_location();
}

$layout = isset($atts['layout']) ? $atts['layout'] : 'grid';
?>
<div class="aiep ai-events-container" data-user-location="<?php echo esc_attr($user_location); ?>">

  <!-- Filters -->
  <section class="ae-filters" aria-labelledby="ae-filters-title">
    <div class="ae-filters__head">
      <div class="ae-filters__head-inner">
        <span class="ae-bubble" aria-hidden="true">
          <!-- Stroke-based magnifier (matches search input icon) -->
          <svg class="ae-icon ae-icon--24" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <circle cx="11" cy="11" r="7"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
        </span>
        <div class="ae-filters__titlewrap">
          <div id="ae-filters-title" class="ae-filters__title"><?php _e('Event Filters', 'ai-events-pro'); ?></div>
          <div class="ae-filters__desc"><?php _e('Use location, radius, category, and source to refine your results', 'ai-events-pro'); ?></div>
        </div>

        <!-- Theme toggle inside the filter box -->
        <div class="ae-mode-toggle">
          <button id="theme-toggle" class="ae-toggle" type="button" aria-label="<?php esc_attr_e('Toggle theme', 'ai-events-pro'); ?>">
            <span class="ae-toggle__label">DAY MODE</span>
            <span class="ae-toggle__iconwrap">
              <svg class="ae-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4a1 1 0 0 1 1 1v1a1 1 0 1 1-2 0V5a1 1 0 0 1 1-1zm0 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10zm7-5a1 1 0 0 1 1 1h1a1 1 0 1 1 0 2h-1a1 1 0 1 1-2 0 1 1 0 0 1 1-1zm-14 0a1 1 0 0 1 1 1 1 1 0 1 1-2 0H3a1 1 0 1 1 0-2h1a1 1 0 0 1 1-1zm10.95 6.364a1 1 0 0 1 1.414 0l.707.707a1 1 0 0 1-1.414 1.414l-.707-.707a1 1 0 0 1 0-1.414zM5.343 5.343a1 1 0 0 1 1.414 0l.707.707A1 1 0 0 1 6.05 7.464l-.707-.707a1 1 0 0 1 0-1.414zm0 12.728a1 1 0 0 1 1.414 0l.707.707A1 1 0 1 1 6.05 20.19l-.707-.707a1 1 0 0 1 0-1.414zm12.728-12.728a1 1 0 0 1 0 1.414l-.707.707A1 1 0 1 1 16.95 6.05l.707-.707a1 1 0 0 1 1.414 0z"/></svg>
            </span>
          </button>
        </div>
      </div>
    </div>

    <div class="ae-fields">
      <!-- Location -->
      <div class="ae-field ae-field--span-4">
        <label class="ae-label" for="location-filter"><?php _e('Location', 'ai-events-pro'); ?></label>
        <div class="ae-input-wrap">
          <input type="text" id="location-filter" class="ae-input" placeholder="<?php esc_attr_e('Type a city (e.g., New York, NY)', 'ai-events-pro'); ?>" value="<?php echo esc_attr($user_location); ?>"/>
          <button id="get-location-btn" type="button" class="ae-geo-btn" title="<?php esc_attr_e('Use my location', 'ai-events-pro'); ?>" aria-label="<?php esc_attr_e('Use my location', 'ai-events-pro'); ?>">
            <svg class="ae-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a7 7 0 0 1 7 7c0 5-7 13-7 13S5 14 5 9a7 7 0 0 1 7-7zm0 9.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/></svg>
          </button>
        </div>
      </div>

      <!-- Radius (span-2 so all 4 filters fit one row) -->
      <div class="ae-field ae-field--span-2">
        <label class="ae-label" for="radius-filter"><?php _e('Radius', 'ai-events-pro'); ?></label>
        <select id="radius-filter" class="ae-select">
          <?php
          $radius_default = isset($settings['default_radius']) ? intval($settings['default_radius']) : 25;
          foreach (array(5,10,25,50,100) as $r) {
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

      <!-- Full-width Apply button row -->
      <div class="ae-field ae-field--full">
        <div class="ae-apply-row">
          <button id="apply-filters-btn" class="ae-btn ae-btn--primary" type="button">
            <?php _e('APPLY FILTERS', 'ai-events-pro'); ?>
          </button>
        </div>
      </div>
    </div>
  </section>

  <!-- Search -->
  <section class="ae-search">
    <div class="ae-search__wrap">
      <span class="ae-search__icon" aria-hidden="true">
        <!-- Stroke-based magnifier (previous icon) -->
        <svg class="ae-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
          <circle cx="11" cy="11" r="7"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
      </span>
      <input id="event-search" type="search" placeholder="<?php esc_attr_e('Search events by name, keyword, or description…', 'ai-events-pro'); ?>"/>
    </div>
  </section>

  <!-- Loading -->
  <div id="events-loading" class="events-loading" style="display:none;">
    <div class="loading-spinner"></div>
    <p><?php _e('Loading events…', 'ai-events-pro'); ?></p>
  </div>

  <!-- Results -->
  <div id="events-container" class="aiep-grid layout-<?php echo esc_attr($layout); ?>">
    <!-- Event cards injected via AJAX -->
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