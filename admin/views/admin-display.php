<?php
/**
 * Main admin display page
 */

if (!current_user_can('manage_options')) {
    return;
}

$event_manager = new AI_Events_Event_Manager();
$stats = $event_manager->get_events_statistics();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="ai-events-dashboard">
        <!-- Welcome Section -->
        <div class="welcome-panel">
            <div class="welcome-panel-content">
                <h2><?php _e('Welcome to AI Events Pro!', 'ai-events-pro'); ?></h2>
                <p class="about-description">
                    <?php _e('Manage your events with AI-powered features, sync with major platforms, and provide an amazing experience for your visitors.', 'ai-events-pro'); ?>
                </p>
                
                <div class="welcome-panel-column-container">
                    <div class="welcome-panel-column">
                        <h3><?php _e('Quick Setup', 'ai-events-pro'); ?></h3>
                        <a class="button button-primary" href="<?php echo admin_url('admin.php?page=' . AI_EVENTS_PRO_PLUGIN_NAME . '-settings'); ?>">
                            <?php _e('Configure APIs', 'ai-events-pro'); ?>
                        </a>
                        <a class="button" href="<?php echo admin_url('post-new.php?post_type=ai_event'); ?>">
                            <?php _e('Add Your First Event', 'ai-events-pro'); ?>
                        </a>
                    </div>
                    
                    <div class="welcome-panel-column">
                        <h3><?php _e('Documentation', 'ai-events-pro'); ?></h3>
                        <ul>
                            <li><a href="https://sawahsolutions.com/docs/ai-events-pro" target="_blank"><?php _e('Getting Started Guide', 'ai-events-pro'); ?></a></li>
                            <li><a href="https://sawahsolutions.com/docs/ai-events-pro/shortcodes" target="_blank"><?php _e('Shortcode Reference', 'ai-events-pro'); ?></a></li>
                            <li><a href="https://sawahsolutions.com/support" target="_blank"><?php _e('Support Center', 'ai-events-pro'); ?></a></li>
                        </ul>
                    </div>
                    
                    <div class="welcome-panel-column welcome-panel-last">
                        <h3><?php _e('Need Help?', 'ai-events-pro'); ?></h3>
                        <p><?php _e('Contact our support team for assistance with setup or customization.', 'ai-events-pro'); ?></p>
                        <a class="button" href="https://sawahsolutions.com/contact" target="_blank">
                            <?php _e('Get Support', 'ai-events-pro'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="ai-events-quick-stats">
            <div class="stats-container">
                <div class="stat-box">
                    <span class="stat-number"><?php echo esc_html($stats['custom_events']); ?></span>
                    <span class="stat-label"><?php _e('Custom Events', 'ai-events-pro'); ?></span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?php echo esc_html($stats['cached_events']); ?></span>
                    <span class="stat-label"><?php _e('API Events', 'ai-events-pro'); ?></span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?php echo esc_html($stats['recent_events']); ?></span>
                    <span class="stat-label"><?php _e('This Month', 'ai-events-pro'); ?></span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="ai-events-quick-actions">
            <h3><?php _e('Quick Actions', 'ai-events-pro'); ?></h3>
            <div class="actions-grid">
                <div class="action-card">
                    <div class="action-icon">📅</div>
                    <h4><?php _e('Add Event', 'ai-events-pro'); ?></h4>
                    <p><?php _e('Create a new custom event with all the details.', 'ai-events-pro'); ?></p>
                    <a href="<?php echo admin_url('post-new.php?post_type=ai_event'); ?>" class="button button-primary">
                        <?php _e('Add Event', 'ai-events-pro'); ?>
                    </a>
                </div>

                <div class="action-card">
                    <div class="action-icon">🔄</div>
                    <h4><?php _e('Sync Events', 'ai-events-pro'); ?></h4>
                    <p><?php _e('Import events from Eventbrite and Ticketmaster APIs.', 'ai-events-pro'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=' . AI_EVENTS_PRO_PLUGIN_NAME . '-settings'); ?>" class="button">
                        <?php _e('Sync Now', 'ai-events-pro'); ?>
                    </a>
                </div>

                <div class="action-card">
                    <div class="action-icon">⚙️</div>
                    <h4><?php _e('Settings', 'ai-events-pro'); ?></h4>
                    <p><?php _e('Configure APIs, AI features, and display options.', 'ai-events-pro'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=' . AI_EVENTS_PRO_PLUGIN_NAME . '-settings'); ?>" class="button">
                        <?php _e('Settings', 'ai-events-pro'); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="ai-events-system-status">
            <h3><?php _e('System Status', 'ai-events-pro'); ?></h3>
            <div class="status-grid">
                <?php
                $eventbrite_settings = get_option('ai_events_pro_eventbrite_settings', array());
                $ticketmaster_settings = get_option('ai_events_pro_ticketmaster_settings', array());
                $ai_settings = get_option('ai_events_pro_ai_settings', array());

                $eventbrite_token = $eventbrite_settings['private_token'] ?? '';
                $ticketmaster_key = $ticketmaster_settings['consumer_key'] ?? '';
                $openrouter_key = $ai_settings['openrouter_api_key'] ?? '';
                ?>
                
                <div class="status-item">
                    <span class="status-indicator <?php echo !empty($eventbrite_token) ? 'active' : 'inactive'; ?>"></span>
                    <span class="status-label"><?php _e('Eventbrite API', 'ai-events-pro'); ?></span>
                    <span class="status-value">
                        <?php echo !empty($eventbrite_token) ? __('Connected', 'ai-events-pro') : __('Not Connected', 'ai-events-pro'); ?>
                    </span>
                </div>

                <div class="status-item">
                    <span class="status-indicator <?php echo !empty($ticketmaster_key) ? 'active' : 'inactive'; ?>"></span>
                    <span class="status-label"><?php _e('Ticketmaster API', 'ai-events-pro'); ?></span>
                    <span class="status-value">
                        <?php echo !empty($ticketmaster_key) ? __('Connected', 'ai-events-pro') : __('Not Connected', 'ai-events-pro'); ?>
                    </span>
                </div>

                <div class="status-item">
                    <span class="status-indicator <?php echo !empty($openrouter_key) ? 'active' : 'inactive'; ?>"></span>
                    <span class="status-label"><?php _e('AI Features', 'ai-events-pro'); ?></span>
                    <span class="status-value">
                        <?php echo !empty($openrouter_key) ? __('Enabled', 'ai-events-pro') : __('Disabled', 'ai-events-pro'); ?>
                    </span>
                </div>

                <div class="status-item">
                    <span class="status-indicator active"></span>
                    <span class="status-label"><?php _e('Cache System', 'ai-events-pro'); ?></span>
                    <span class="status-value"><?php _e('Active', 'ai-events-pro'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.ai-events-dashboard {
    max-width: 1200px;
}

.welcome-panel {
    background: #fff;
    border: 1px solid #c3c4c7;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
    margin: 20px 0;
    padding: 23px 10px 0;
    position: relative;
    overflow: hidden;
}

.welcome-panel-content {
    max-width: none;
}

.ai-events-quick-stats {
    margin: 30px 0;
}

.stats-container {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.stat-box {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    flex: 1;
    min-width: 150px;
}

.stat-number {
    display: block;
    font-size: 32px;
    font-weight: 600;
    color: #1d2327;
    margin-bottom: 8px;
}

.stat-label {
    color: #646970;
    font-size: 14px;
}

.ai-events-quick-actions {
    background: #fff;
    border: 1px solid #c3c4c7;
    padding: 20px;
    margin: 30px 0;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.action-card {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    transition: border-color 0.2s ease;
}

.action-card:hover {
    border-color: #0073aa;
}

.action-icon {
    font-size: 32px;
    margin-bottom: 12px;
}

.action-card h4 {
    margin: 0 0 8px 0;
    font-size: 16px;
}

.action-card p {
    margin: 0 0 16px 0;
    color: #646970;
    font-size: 14px;
}

.ai-events-system-status {
    background: #fff;
    border: 1px solid #c3c4c7;
    padding: 20px;
    margin: 30px 0;
}

.status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-top: 20px;
}

.status-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 4px;
    background: #f6f7f7;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}

.status-indicator.active {
    background: #00a32a;
}

.status-indicator.inactive {
    background: #d63638;
}

.status-label {
    font-weight: 500;
    flex: 1;
}

.status-value {
    font-size: 14px;
    color: #646970;
}
</style>