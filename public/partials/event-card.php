<?php
/**
 * Single Event Card (Compact)
 * Expects $event array.
 */

if (!isset($event) || !is_array($event)) return;

$title       = trim($event['title'] ?? '');
$desc        = trim($event['description'] ?? '');
$url         = esc_url($event['url'] ?? '#');
$image       = esc_url($event['image'] ?? '');
$source      = strtolower($event['source'] ?? 'custom');
$category    = trim($event['ai_category'] ?? ($event['category'] ?? ''));
$location    = trim($event['venue'] ?? ($event['location'] ?? ''));
$price       = trim($event['price'] ?? '');
$date_raw    = trim($event['date'] ?? '');
$time_raw    = trim($event['time'] ?? '');

$date_label = '';
if (!empty($date_raw)) {
    $ts = strtotime(($date_raw ?: '') . ' ' . ($time_raw ?: '00:00'));
    if ($ts) $date_label = date_i18n('M j, Y', $ts);
}
$badge_class = in_array($source, array('ticketmaster','eventbrite','custom')) ? $source : 'custom';
?>
<article class="aiep-card">
  <?php if ($image): ?>
  <div class="aiep-card__media">
    <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>"/>
    <span class="aiep-badge <?php echo esc_attr($badge_class); ?>">
      <?php echo esc_html(ucfirst($source)); ?>
    </span>
  </div>
  <?php endif; ?>

  <div class="aiep-card__head">
    <?php if ($date_label): ?>
      <span class="aiep-datepill"><?php echo esc_html($date_label); ?></span>
    <?php endif; ?>
    <h3 class="aiep-card__title"><?php echo esc_html($title); ?></h3>
    <?php if ($desc): ?>
      <p class="aiep-card__desc"><?php echo esc_html(wp_trim_words(wp_strip_all_tags($desc), 28, '…')); ?></p>
    <?php endif; ?>
  </div>

  <div class="aiep-card__meta">
    <?php if ($location): ?>
      <div class="aiep-meta"><span class="aiep-dot">📍</span> <?php echo esc_html($location); ?></div>
    <?php endif; ?>
    <?php if ($category): ?>
      <div class="aiep-meta"><span class="aiep-dot">🏷</span> <span class="aiep-chip"><?php echo esc_html($category); ?></span></div>
    <?php endif; ?>
    <?php if ($price && strtolower($price) !== 'check website'): ?>
      <div class="aiep-meta"><span class="aiep-dot">💲</span> <?php echo esc_html($price); ?></div>
    <?php endif; ?>
  </div>

  <div class="aiep-card__foot">
    <a href="<?php echo $url; ?>" class="ae-btn ae-btn--primary" target="_blank" rel="nofollow noopener"><?php _e('View Details', 'ai-events-pro'); ?> ↗</a>
  </div>
</article>