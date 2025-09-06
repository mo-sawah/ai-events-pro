<?php
/**
 * Shortcodes Controller Page
 * - Generator form builds a shortcode string
 * - Copy, Preview (AJAX-rendered), and Presets (save/delete)
 */

if (!current_user_can('manage_options')) return;
$presets = get_option('ai_events_pro_shortcode_presets', array());
?>
<div class="wrap ai-e-wrap ai-e-shortcodes-wrap">
  <div class="ai-e-hero">
    <h1><?php esc_html_e('Shortcodes', 'ai-events-pro'); ?></h1>
    <p><?php esc_html_e('Generate and manage shortcode presets for pages, posts, or builders.', 'ai-events-pro'); ?></p>
    <div class="ai-e-cta">
      <a class="ai-e-btn" href="https://sawahsolutions.com/docs/ai-events-pro/shortcodes" target="_blank" rel="noreferrer">📖 <?php esc_html_e('Shortcode Reference', 'ai-events-pro'); ?></a>
    </div>
  </div>

  <div class="ai-e-grid" style="margin-top:16px;">
    <div class="ai-e-card" style="grid-column: span 7;">
      <h2 style="margin-top:0;"><?php esc_html_e('Generator', 'ai-events-pro'); ?></h2>

      <div class="row">
        <label><?php esc_html_e('Shortcode Type', 'ai-events-pro'); ?></label>
        <select id="sc-type">
          <option value="ai_events_page">[ai_events_page]</option>
          <option value="ai_events_list">[ai_events_list]</option>
          <option value="ai_events_widget">[ai_events_widget]</option>
        </select>
      </div>

      <div class="row">
        <label><?php esc_html_e('Location', 'ai-events-pro'); ?></label>
        <input id="sc-location" type="text" placeholder="<?php esc_attr_e('e.g., New York, NY', 'ai-events-pro'); ?>" />
      </div>

      <div class="row">
        <label><?php esc_html_e('Radius (miles)', 'ai-events-pro'); ?></label>
        <input id="sc-radius" type="number" min="1" step="1" value="25" />
      </div>

      <div class="row">
        <label><?php esc_html_e('Limit', 'ai-events-pro'); ?></label>
        <input id="sc-limit" type="number" min="1" step="1" value="12" />
      </div>

      <div class="row">
        <label><?php esc_html_e('Category', 'ai-events-pro'); ?></label>
        <input id="sc-category" type="text" placeholder="<?php esc_attr_e('e.g., music', 'ai-events-pro'); ?>" />
      </div>

      <div class="row">
        <label><?php esc_html_e('Source', 'ai-events-pro'); ?></label>
        <select id="sc-source">
          <option value="">—</option>
          <option value="eventbrite">eventbrite</option>
          <option value="ticketmaster">ticketmaster</option>
          <option value="custom">custom</option>
        </select>
      </div>

      <div class="row">
        <label><?php esc_html_e('Layout', 'ai-events-pro'); ?></label>
        <select id="sc-layout">
          <option value="grid">grid</option>
          <option value="list">list</option>
        </select>
      </div>

      <div class="row" id="row-search-filters">
        <label><?php esc_html_e('Show Search & Filters (page/widget)', 'ai-events-pro'); ?></label>
        <div style="display:flex;gap:8px;">
          <label><input type="checkbox" id="sc-show-search" checked /> <?php esc_html_e('Search', 'ai-events-pro'); ?></label>
          <label><input type="checkbox" id="sc-show-filters" checked /> <?php esc_html_e('Filters', 'ai-events-pro'); ?></label>
        </div>
      </div>

      <div class="row">
        <label><?php esc_html_e('Order by', 'ai-events-pro'); ?></label>
        <div style="display:flex;gap:8px;">
          <select id="sc-orderby">
            <option value="date">date</option>
            <option value="title">title</option>
            <option value="ai_score">ai_score</option>
          </select>
          <select id="sc-order">
            <option value="ASC">ASC</option>
            <option value="DESC">DESC</option>
          </select>
        </div>
      </div>

      <div class="toolbar">
        <button id="btn-preview" class="ai-e-btn">👁️ <?php esc_html_e('Preview', 'ai-events-pro'); ?></button>
        <input id="preset-name" type="text" placeholder="<?php esc_attr_e('Preset name (optional)', 'ai-events-pro'); ?>" />
        <button id="btn-save-preset" class="ai-e-btn primary">💾 <?php esc_html_e('Save Preset', 'ai-events-pro'); ?></button>
      </div>

      <div class="ai-e-code" id="sc-output">[ai_events_page]</div>
      <div class="toolbar">
        <button id="btn-copy" class="ai-e-btn">📋 <?php esc_html_e('Copy', 'ai-events-pro'); ?></button>
      </div>
    </div>

    <div class="ai-e-card" style="grid-column: span 5;">
      <h2 style="margin-top:0;"><?php esc_html_e('Presets', 'ai-events-pro'); ?></h2>
      <div class="ai-e-presets" id="presets-list">
        <?php if (empty($presets)): ?>
          <p style="color:var(--aiep-muted);"><?php esc_html_e('No presets yet. Create one from the generator.', 'ai-events-pro'); ?></p>
        <?php else: ?>
          <?php foreach ($presets as $preset): ?>
            <div class="ai-e-preset" data-id="<?php echo esc_attr($preset['id']); ?>">
              <h4><?php echo esc_html($preset['name']); ?></h4>
              <div class="ai-e-code" style="margin-bottom:8px;"><?php echo esc_html($preset['shortcode']); ?></div>
              <div class="buttons">
                <button class="ai-e-btn btn-copy-preset">📋 <?php esc_html_e('Copy', 'ai-events-pro'); ?></button>
                <button class="ai-e-btn btn-preview-preset">👁️ <?php esc_html_e('Preview', 'ai-events-pro'); ?></button>
                <button class="ai-e-btn btn-delete-preset" style="border-color:rgba(255,107,107,.4)">🗑️ <?php esc_html_e('Delete', 'ai-events-pro'); ?></button>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="ai-e-modal" id="preview-modal" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Shortcode Preview', 'ai-events-pro'); ?>">
    <div class="content">
      <span class="close" id="preview-close">✖</span>
      <div id="preview-inner"><?php esc_html_e('Loading...', 'ai-events-pro'); ?></div>
    </div>
  </div>
</div>