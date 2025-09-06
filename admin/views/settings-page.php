<?php
/**
 * Fixed Settings Page with separate forms
 */

if (!current_user_can('manage_options')) {
    return;
}

// Handle form submission messages
if (isset($_GET['settings-updated'])) {
    add_settings_error('ai_events_pro_messages', 'ai_events_pro_message', __('Settings Saved', 'ai-events-pro'), 'updated');
}

settings_errors('ai_events_pro_messages');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="nav-tab-wrapper">
        <a href="#general" class="nav-tab nav-tab-active"><?php _e('General', 'ai-events-pro'); ?></a>
        <a href="#eventbrite" class="nav-tab"><?php _e('Eventbrite', 'ai-events-pro'); ?></a>
        <a href="#ticketmaster" class="nav-tab"><?php _e('Ticketmaster', 'ai-events-pro'); ?></a>
        <a href="#ai" class="nav-tab"><?php _e('AI Features', 'ai-events-pro'); ?></a>
        <a href="#sync" class="nav-tab"><?php _e('Sync Events', 'ai-events-pro'); ?></a>
    </div>
    
    <div id="general" class="tab-content active">
        <form action="options.php" method="post">
            <?php
            settings_fields('ai_events_pro_general');
            do_settings_sections('ai_events_pro_general');
            submit_button(__('Save General Settings', 'ai-events-pro'));
            ?>
        </form>
    </div>
    
    <div id="eventbrite" class="tab-content">
        <form action="options.php" method="post">
            <?php
            settings_fields('ai_events_pro_eventbrite');
            do_settings_sections('ai_events_pro_eventbrite');
            submit_button(__('Save Eventbrite Settings', 'ai-events-pro'));
            ?>
        </form>
    </div>
    
    <div id="ticketmaster" class="tab-content">
        <form action="options.php" method="post">
            <?php
            settings_fields('ai_events_pro_ticketmaster');
            do_settings_sections('ai_events_pro_ticketmaster');
            submit_button(__('Save Ticketmaster Settings', 'ai-events-pro'));
            ?>
        </form>
    </div>
    
    <div id="ai" class="tab-content">
        <form action="options.php" method="post">
            <?php
            settings_fields('ai_events_pro_ai');
            do_settings_sections('ai_events_pro_ai');
            ?>
            
            <div class="ai-features-info">
                <h3><?php _e('Available AI Features', 'ai-events-pro'); ?></h3>
                <ul>
                    <li><strong><?php _e('Smart Categorization:', 'ai-events-pro'); ?></strong> <?php _e('Automatically categorize events using AI.', 'ai-events-pro'); ?></li>
                    <li><strong><?php _e('Event Summaries:', 'ai-events-pro'); ?></strong> <?php _e('Generate concise summaries for long descriptions.', 'ai-events-pro'); ?></li>
                    <li><strong><?php _e('Relevance Scoring:', 'ai-events-pro'); ?></strong> <?php _e('AI-powered relevance scoring for better ranking.', 'ai-events-pro'); ?></li>
                </ul>
            </div>
            
            <?php submit_button(__('Save AI Settings', 'ai-events-pro')); ?>
        </form>
    </div>
    
    <div id="sync" class="tab-content">
        <div class="sync-events-section">
            <h3><?php _e('Sync Events', 'ai-events-pro'); ?></h3>
            
            <!-- API Status Check -->
            <div class="api-status-check">
                <h4><?php _e('API Status', 'ai-events-pro'); ?></h4>
                <?php
                $general_settings = get_option('ai_events_pro_settings', array());
                $eventbrite_settings = get_option('ai_events_pro_eventbrite_settings', array());
                $ticketmaster_settings = get_option('ai_events_pro_ticketmaster_settings', array());
                $ai_settings = get_option('ai_events_pro_ai_settings', array());
                
                $enabled_apis = $general_settings['enabled_apis'] ?? array();
                ?>
                
                <div class="api-status-grid">
                    <?php if (!empty($enabled_apis['eventbrite'])): ?>
                    <div class="api-status-item">
                        <span class="status-dot <?php echo !empty($eventbrite_settings['private_token']) ? 'connected' : 'disconnected'; ?>"></span>
                        <span>Eventbrite: <?php echo !empty($eventbrite_settings['private_token']) ? __('Ready', 'ai-events-pro') : __('Not Configured', 'ai-events-pro'); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($enabled_apis['ticketmaster'])): ?>
                    <div class="api-status-item">
                        <span class="status-dot <?php echo !empty($ticketmaster_settings['consumer_key']) ? 'connected' : 'disconnected'; ?>"></span>
                        <span>Ticketmaster: <?php echo !empty($ticketmaster_settings['consumer_key']) ? __('Ready', 'ai-events-pro') : __('Not Configured', 'ai-events-pro'); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($ai_settings['enable_ai_features'])): ?>
                    <div class="api-status-item">
                        <span class="status-dot <?php echo !empty($ai_settings['openrouter_api_key']) ? 'connected' : 'disconnected'; ?>"></span>
                        <span>AI Features: <?php echo !empty($ai_settings['openrouter_api_key']) ? __('Ready', 'ai-events-pro') : __('Not Configured', 'ai-events-pro'); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Sync Form -->
            <div class="sync-form">
                <table class="form-table">
                    <tr>
                        <th><label for="sync_location"><?php _e('Location', 'ai-events-pro'); ?></label></th>
                        <td>
                            <input type="text" id="sync_location" class="regular-text" placeholder="<?php _e('New York, NY', 'ai-events-pro'); ?>" />
                            <p class="description"><?php _e('Enter a city and state to find events.', 'ai-events-pro'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="sync_radius"><?php _e('Radius (miles)', 'ai-events-pro'); ?></label></th>
                        <td><input type="number" id="sync_radius" value="25" min="1" max="500" class="small-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="sync_limit"><?php _e('Max Events', 'ai-events-pro'); ?></label></th>
                        <td><input type="number" id="sync_limit" value="50" min="1" max="200" class="small-text" /></td>
                    </tr>
                </table>
                
                <div class="sync-buttons">
                    <button type="button" id="sync-events-btn" class="button button-primary">
                        <?php _e('Sync Events Now', 'ai-events-pro'); ?>
                    </button>
                    <button type="button" id="clear-cache-btn" class="button">
                        <?php _e('Clear Cache', 'ai-events-pro'); ?>
                    </button>
                </div>
                
                <div id="sync-results"></div>
            </div>
        </div>
    </div>
</div>

<style>
.nav-tab-wrapper { margin-bottom: 20px; }
.tab-content { display: none; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-top: none; }
.tab-content.active { display: block; }

.api-info { background: #f0f8ff; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
.api-info ol { margin-left: 20px; }

.api-status-check { margin-bottom: 30px; }
.api-status-grid { display: flex; flex-direction: column; gap: 10px; margin-top: 15px; }
.api-status-item { display: flex; align-items: center; gap: 10px; }
.status-dot { width: 10px; height: 10px; border-radius: 50%; }
.status-dot.connected { background: #28a745; }
.status-dot.disconnected { background: #dc3545; }

.sync-form .form-table { margin-bottom: 20px; }
.sync-buttons { margin: 20px 0; }
.sync-buttons .button { margin-right: 10px; }

#sync-results { margin-top: 20px; padding: 15px; border-radius: 4px; display: none; }
#sync-results.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
#sync-results.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

.ai-features-info { background: #f0f8ff; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').click(function(e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.tab-content').removeClass('active');
        $($(this).attr('href')).addClass('active');
    });
    
    // API Testing
    $('.test-api-btn').click(function() {
        var $btn = $(this);
        var apiType = $btn.data('api');
        var optionName = $btn.data('option');
        var fieldName = $btn.data('field');
        var $input = $btn.prev('input');
        var apiKey = $input.val();
        
        if (!apiKey.trim()) {
            alert('<?php _e('Please enter an API key first.', 'ai-events-pro'); ?>');
            return;
        }
        
        $btn.prop('disabled', true).text('<?php _e('Testing...', 'ai-events-pro'); ?>');
        
        $.post(ajaxurl, {
            action: 'test_api_connection',
            nonce: ai_events_admin.nonce,
            api_type: apiType,
            option_name: optionName,
            field_name: fieldName,
            api_key: apiKey
        }).done(function(response) {
            if (response.success) {
                alert('✅ ' + response.data);
            } else {
                alert('❌ ' + response.data);
            }
        }).always(function() {
            $btn.prop('disabled', false).text('<?php _e('Test Connection', 'ai-events-pro'); ?>');
        });
    });
    
    // Sync Events
    $('#sync-events-btn').click(function() {
        var $btn = $(this);
        var location = $('#sync_location').val().trim();
        var radius = $('#sync_radius').val();
        var limit = $('#sync_limit').val();
        var $results = $('#sync-results');
        
        if (!location) {
            alert('<?php _e('Please enter a location.', 'ai-events-pro'); ?>');
            return;
        }
        
        $btn.prop('disabled', true).text('<?php _e('Syncing...', 'ai-events-pro'); ?>');
        $results.removeClass('success error').hide();
        
        $.post(ajaxurl, {
            action: 'sync_events',
            nonce: ai_events_admin.nonce,
            location: location,
            radius: radius,
            limit: limit
        }).done(function(response) {
            if (response.success) {
                var html = '<strong>✅ ' + response.data.message + '</strong>';
                if (response.data.events_preview) {
                    html += '<div style="margin-top: 10px;"><strong>Preview:</strong>';
                    $.each(response.data.events_preview, function(i, event) {
                        html += '<br>• ' + event.title + ' (' + event.source + ')';
                    });
                    html += '</div>';
                }
                $results.addClass('success').html(html).show();
            } else {
                $results.addClass('error').html('<strong>❌ Error:</strong> ' + response.data).show();
            }
        }).always(function() {
            $btn.prop('disabled', false).text('<?php _e('Sync Events Now', 'ai-events-pro'); ?>');
        });
    });
    
    // Clear Cache
    $('#clear-cache-btn').click(function() {
        if (!confirm('<?php _e('Clear all cached events?', 'ai-events-pro'); ?>')) return;
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php _e('Clearing...', 'ai-events-pro'); ?>');
        
        $.post(ajaxurl, {
            action: 'clear_events_cache',
            nonce: ai_events_admin.nonce
        }).done(function(response) {
            var $results = $('#sync-results');
            if (response.success) {
                $results.removeClass('error').addClass('success').html('<strong>✅ ' + response.data + '</strong>').show();
            } else {
                $results.removeClass('success').addClass('error').html('<strong>❌ Error:</strong> ' + response.data).show();
            }
        }).always(function() {
            $btn.prop('disabled', false).text('<?php _e('Clear Cache', 'ai-events-pro'); ?>');
        });
    });
});
</script>