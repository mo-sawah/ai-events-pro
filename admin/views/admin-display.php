<?php
/**
 * Modern, clean, futuristic Dashboard
 * Styling provided by admin/css/ai-events-admin.css
 */

if (!current_user_can('manage_options')) {
    return;
}

$event_manager = new AI_Events_Event_Manager();
$stats = $event_manager->get_events_statistics();

$eventbrite_settings   = get_option('ai_events_pro_eventbrite_settings', array());
$ticketmaster_settings = get_option('ai_events_pro_ticketmaster_settings', array());
$ai_settings           = get_option('ai_events_pro_ai_settings', array());

$eventbrite_ok   = !empty($eventbrite_settings['private_token']);
$ticketmaster_ok = !empty($ticketmaster_settings['consumer_key']);
$ai_ok           = !empty($ai_settings['enable_ai_features']) && !empty($ai_settings['openrouter_api_key']);
?>
<div class="wrap ai-e-wrap">
  <div class="ai-e-hero">
    <h1><?php esc_html_e('Welcome to AI Events Pro', 'ai-events-pro'); ?></h1>
    <p><?php esc_html_e('A futuristic events engine with AI enhancements and multi-source sync.', 'ai-events-pro'); ?></p>
    <div class="ai-e-cta">
      <a class="ai-e-btn primary" href="<?php echo esc_url(admin_url('admin.php?page=' . AI_EVENTS_PRO_PLUGIN_NAME . '-settings')); ?>">
        ⚙️ <?php esc_html_e('Configure APIs', 'ai-events-pro'); ?>
      </a>
      <a class="ai-e-btn" href="<?php echo esc_url(admin_url('post-new.php?post_type=ai_event')); ?>">
        ➕ <?php esc_html_e('Add Event', 'ai-events-pro'); ?>
      </a>
      <a class="ai-e-btn" href="<?php echo esc_url(admin_url('admin.php?page=' . AI_EVENTS_PRO_PLUGIN_NAME . '-shortcodes')); ?>">
        🔗 <?php esc_html_e('Shortcodes', 'ai-events-pro'); ?>
      </a>
      <a class="ai-e-btn" href="https://sawahsolutions.com/docs/ai-events-pro" target="_blank" rel="noreferrer">
        📖 <?php esc_html_e('Docs', 'ai-events-pro'); ?>
      </a>
    </div>
  </div>

  <div class="ai-e-kpis">
    <div class="ai-e-card ai-e-kpi">
      <span class="label"><?php esc_html_e('Custom Events', 'ai-events-pro'); ?></span>
      <span class="value"><?php echo esc_html($stats['custom_events']); ?></span>
    </div>
    <div class="ai-e-card ai-e-kpi">
      <span class="label"><?php esc_html_e('API Events', 'ai-events-pro'); ?></span>
      <span class="value"><?php echo esc_html($stats['cached_events']); ?></span>
    </div>
    <div class="ai-e-card ai-e-kpi">
      <span class="label"><?php esc_html_e('This Month', 'ai-events-pro'); ?></span>
      <span class="value"><?php echo esc_html($stats['recent_events']); ?></span>
    </div>
  </div>

  <div class="ai-e-actions">
    <div class="ai-e-card ai-e-action">
      <div class="icon">📅</div>
      <h3><?php esc_html_e('Add Event', 'ai-events-pro'); ?></h3>
      <p class="desc"><?php esc_html_e('Create a new custom event with all the details.', 'ai-events-pro'); ?></p>
      <div>
        <a class="ai-e-btn primary" href="<?php echo esc_url(admin_url('post-new.php?post_type=ai_event')); ?>">
          <?php esc_html_e('Add Event', 'ai-events-pro'); ?>
        </a>
      </div>
    </div>
    <div class="ai-e-card ai-e-action">
      <div class="icon">🔄</div>
      <h3><?php esc_html_e('Sync Events', 'ai-events-pro'); ?></h3>
      <p class="desc"><?php esc_html_e('Import from Eventbrite and Ticketmaster. Use the settings to control sources.', 'ai-events-pro'); ?></p>
      <div>
        <a class="ai-e-btn" href="<?php echo esc_url(admin_url('admin.php?page=' . AI_EVENTS_PRO_PLUGIN_NAME . '-settings') . '#sync'); ?>">
          <?php esc_html_e('Open Sync', 'ai-events-pro'); ?>
        </a>
      </div>
    </div>
    <div class="ai-e-card ai-e-action">
      <div class="icon">🎛️</div>
      <h3><?php esc_html_e('Shortcodes', 'ai-events-pro'); ?></h3>
      <p class="desc"><?php esc_html_e('Generate, preview, and save shortcode presets.', 'ai-events-pro'); ?></p>
      <div>
        <a class="ai-e-btn" href="<?php echo esc_url(admin_url('admin.php?page=' . AI_EVENTS_PRO_PLUGIN_NAME . '-shortcodes')); ?>">
          <?php esc_html_e('Open Shortcodes', 'ai-events-pro'); ?>
        </a>
      </div>
    </div>
  </div>

  <div class="ai-e-card" style="margin-top:16px;">
    <h3 style="margin-top:0;"><?php esc_html_e('System Status', 'ai-events-pro'); ?></h3>
    <div class="ai-e-status">
      <div class="ai-e-status-item">
        <span class="dot <?php echo $eventbrite_ok ? 'ok' : 'bad'; ?>"></span>
        <strong><?php esc_html_e('Eventbrite API', 'ai-events-pro'); ?></strong>
        <span style="margin-left:auto;color:var(--aiep-muted)"><?php echo $eventbrite_ok ? esc_html__('Connected','ai-events-pro') : esc_html__('Not Connected','ai-events-pro'); ?></span>
      </div>
      <div class="ai-e-status-item">
        <span class="dot <?php echo $ticketmaster_ok ? 'ok' : 'bad'; ?>"></span>
        <strong><?php esc_html_e('Ticketmaster API', 'ai-events-pro'); ?></strong>
        <span style="margin-left:auto;color:var(--aiep-muted)"><?php echo $ticketmaster_ok ? esc_html__('Connected','ai-events-pro') : esc_html__('Not Connected','ai-events-pro'); ?></span>
      </div>
      <div class="ai-e-status-item">
        <span class="dot <?php echo $ai_ok ? 'ok' : 'warn'; ?>"></span>
        <strong><?php esc_html_e('AI Features', 'ai-events-pro'); ?></strong>
        <span style="margin-left:auto;color:var(--aiep-muted)"><?php echo $ai_ok ? esc_html__('Enabled','ai-events-pro') : esc_html__('Disabled','ai-events-pro'); ?></span>
      </div>
      <div class="ai-e-status-item">
        <span class="dot ok"></span>
        <strong><?php esc_html_e('Cache System', 'ai-events-pro'); ?></strong>
        <span style="margin-left:auto;color:var(--aiep-muted)"><?php esc_html_e('Active','ai-events-pro'); ?></span>
      </div>
    </div>
  </div>
</div>