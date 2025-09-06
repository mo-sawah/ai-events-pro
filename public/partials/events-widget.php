<?php
/**
 * Compact Events Widget
 * Uses the same styles/colors as the main page, scaled for sidebars.
 *
 * Expects $atts from the shortcode context.
 */
if (!isset($atts)) $atts = array();
$atts = shortcode_atts(array(
    'location' => '',
    'radius'   => 25,
    'limit'    => 5,
    'category' => '',
    'layout'   => 'list',
    'show_image'    => 'true',
    'show_date'     => 'true',
    'show_location' => 'true'
), $atts);

$api_manager = new AI_Events_API_Manager();
$events = $api_manager->get_events($atts['location'], intval($atts['radius']), intval($atts['limit']));
?>
<div class="aiep aiep-widget">
  <div class="aiep-grid" style="grid-template-columns: 1fr;">
    <?php if (empty($events)): ?>
      <div class="no-events-message">
        <div>📅</div>
        <p><?php _e('No events found.', 'ai-events-pro'); ?></p>
      </div>
    <?php else: ?>
      <?php foreach ($events as $event): ?>
        <?php include AI_EVENTS_PRO_PLUGIN_DIR . 'public/partials/event-card.php'; ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>