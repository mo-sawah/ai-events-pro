<?php
/**
 * Single Event Card (Compact, solid icons, full-width CTA)
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
      <p class="aiep-card__desc"><?php echo esc_html(wp_strip_all_tags(wp_trim_words($desc, 36, '…'))); ?></p>
    <?php endif; ?>
  </div>

  <div class="aiep-card__meta">
    <?php if ($location): ?>
      <div class="aiep-meta">
        <span class="aiep-dot" aria-hidden="true">
          <svg class="ae-icon" viewBox="0 0 24 24"><path d="M12 2a7 7 0 0 1 7 7c0 5-7 13-7 13S5 14 5 9a7 7 0 0 1 7-7zm0 9.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/></svg>
        </span>
        <?php echo esc_html($location); ?>
      </div>
    <?php endif; ?>
    <?php if ($category): ?>
      <div class="aiep-meta">
        <span class="aiep-dot" aria-hidden="true">
          <svg class="ae-icon" viewBox="0 0 24 24"><path d="M5 4h14a1 1 0 0 1 .8 1.6L13.6 14a1 1 0 0 1-.8.4H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1zm1 2v6h5.8L18 6H6z"/></svg>
        </span>
        <span class="aiep-chip"><?php echo esc_html($category); ?></span>
      </div>
    <?php endif; ?>
    <?php if ($price && strtolower($price) !== 'check website'): ?>
      <div class="aiep-meta">
        <span class="aiep-dot" aria-hidden="true">
          <svg class="ae-icon" viewBox="0 0 24 24"><path d="M12 2a1 1 0 0 1 1 1v1.06c1.96.23 3.5 1.37 3.5 3.34 0 1.96-1.54 3.1-3.5 3.34V13h3a1 1 0 1 1 0 2h-3v2h3a1 1 0 1 1 0 2h-3v1a1 1 0 1 1-2 0v-1H9a1 1 0 1 1 0-2h2v-2H9a1 1 0 1 1 0-2h2V9.68C9.93 9.45 8.5 8.31 8.5 6.4c0-1.97 1.43-3.11 3.5-3.34V3a1 1 0 0 1 1-1z"/></svg>
        </span>
        <?php echo esc_html($price); ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="aiep-card__foot">
    <a href="<?php echo $url; ?>" class="ae-btn ae-btn--primary" target="_blank" rel="nofollow noopener">
      <?php _e('View Details', 'ai-events-pro'); ?>
      <svg class="ae-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 17a1 1 0 0 1 0-2h7.586L6.293 6.707a1 1 0 1 1 1.414-1.414L16 13.586V6a1 1 0 1 1 2 0v11a1 1 0 0 1-1 1H7z"/></svg>
    </a>
  </div>
</article>