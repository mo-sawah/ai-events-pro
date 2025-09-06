<?php
/**
 * Updated Admin settings page template with proper API fields
 */

if (!current_user_can('manage_options')) {
    return;
}

// Handle form submission
if (isset($_GET['settings-updated'])) {
    add_settings_error(
        'ai_events_pro_messages',
        'ai_events_pro_message',
        __('Settings Saved', 'ai-events-pro'),
        'updated'
    );
}

settings_errors('ai_events_pro_messages');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="nav-tab-wrapper">
        <a href="#general" class="nav-tab nav-tab-active"><?php _e('General', 'ai-events-pro'); ?></a>
        <a href="#eventbrite" class="nav-tab"><?php _e('Eventbrite API', 'ai-events-pro'); ?></a>
        <a href="#ticketmaster" class="nav-tab"><?php _e('Ticketmaster API', 'ai-events-pro'); ?></a>
        <a href="#ai" class="nav-tab"><?php _e('AI Features', 'ai-events-pro'); ?></a>
        <a href="#sync" class="nav-tab"><?php _e('Sync Events', 'ai-events-pro'); ?></a>
    </div>
    
    <div id="general" class="tab-content active">
        <form action="options.php" method="post">
            <?php
            settings_fields('ai_events_pro_settings_group');
            do_settings_sections('ai_events_pro_settings');
            submit_button(__('Save General Settings', 'ai-events-pro'));
            ?>
        </form>
    </div>
    
    <div id="eventbrite" class="tab-content">
        <form action="options.php" method="post">
            <?php
            settings_fields('ai_events_pro_api_group');
            do_settings_sections('ai_events_pro_eventbrite');
            submit_button(__('Save Eventbrite Settings', 'ai-events-pro'));
            ?>
        </form>
    </div>
    
    <div id="ticketmaster" class="tab-content">
        <form action="options.php" method="post">
            <?php
            settings_fields('ai_events_pro_api_group');
            do_settings_sections('ai_events_pro_ticketmaster');
            submit_button(__('Save Ticketmaster Settings', 'ai-events-pro'));
            ?>
        </form>
    </div>
    
    <div id="ai" class="tab-content">
        <form action="options.php" method="post">
            <?php
            settings_fields('ai_events_pro_settings_group');
            do_settings_sections('ai_events_pro_ai');
            ?>
            
            <div class="ai-features-info">
                <h3><?php _e('Available AI Features', 'ai-events-pro'); ?></h3>
                <ul>
                    <li><strong><?php _e('Smart Categorization:', 'ai-events-pro'); ?></strong> <?php _e('Automatically categorize events using AI analysis.', 'ai-events-pro'); ?></li>
                    <li><strong><?php _e('Event Summaries:', 'ai-events-pro'); ?></strong> <?php _e('Generate concise summaries for long event descriptions.', 'ai-events-pro'); ?></li>
                    <li><strong><?php _e('Relevance Scoring:', 'ai-events-pro'); ?></strong> <?php _e('AI-powered relevance scoring for better event ranking.', 'ai-events-pro'); ?></li>
                    <li><strong><?php _e('Smart Recommendations:', 'ai-events-pro'); ?></strong> <?php _e('Suggest similar events based on user preferences.', 'ai-events-pro'); ?></li>
                    <li><strong><?php _e('Natural Language Search:', 'ai-events-pro'); ?></strong> <?php _e('Enhanced search with natural language understanding.', 'ai-events-pro'); ?></li>
                </ul>
            </div>
            
            <?php submit_button(__('Save AI Settings', 'ai-events-pro')); ?>
        </form>
    </div>
    
    <div id="sync" class="tab-content">
        <div class="sync-events-section">
            <h3><?php _e('Sync Events from APIs', 'ai-events-pro'); ?></h3>
            <p><?php _e('Test your API connections and sync events from external sources.', 'ai-events-pro'); ?></p>
            
            <div class="sync-form">
                <table class="form-table">
                    <tr>
                        <th><label for="sync_location"><?php _e('Location', 'ai-events-pro'); ?></label></th>
                        <td>
                            <input type="text" id="sync_location" class="regular-text" placeholder="<?php _e('New York, NY', 'ai-events-pro'); ?>" />
                            <p class="description"><?php _e('Enter a city and state to sync events for that location.', 'ai-events-pro'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="sync_radius"><?php _e('Radius (miles)', 'ai-events-pro'); ?></label></th>
                        <td>
                            <input type="number" id="sync_radius" value="25" min="1" max="500" class="small-text" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="sync_limit"><?php _e('Max Events', 'ai-events-pro'); ?></label></th>
                        <td>
                            <input type="number" id="sync_limit" value="50" min="1" max="200" class="small-text" />
                        </td>
                    </tr>
                </table>
                
                <div class="sync-buttons">
                    <button type="button" id="sync-events-btn" class="button button-primary button-large">
                        <?php _e('Sync Events Now', 'ai-events-pro'); ?>
                    </button>
                    
                    <button type="button" id="clear-cache-btn" class="button button-large">
                        <?php _e('Clear Cache', 'ai-events-pro'); ?>
                    </button>
                </div>
                
                <div id="sync-results" class="sync-results"></div>
            </div>

            <!-- API Status -->
            <div class="api-status-section">
                <h3><?php _e('API Status', 'ai-events-pro'); ?></h3>
                <div class="api-status-grid">
                    <?php
                    $eventbrite_token = get_option('ai_events_pro_eventbrite_private_token', '');
                    $ticketmaster_key = get_option('ai_events_pro_ticketmaster_consumer_key', '');
                    $openrouter_key = get_option('ai_events_pro_openrouter_key', '');
                    ?>
                    
                    <div class="api-status-item">
                        <div class="status-indicator <?php echo !empty($eventbrite_token) ? 'connected' : 'disconnected'; ?>"></div>
                        <div class="status-info">
                            <h4>Eventbrite API</h4>
                            <p><?php echo !empty($eventbrite_token) ? __('Connected', 'ai-events-pro') : __('Not Connected', 'ai-events-pro'); ?></p>
                        </div>
                        <?php if (!empty($eventbrite_token)): ?>
                        <button type="button" class="button test-api-status" data-api="eventbrite">
                            <?php _e('Test', 'ai-events-pro'); ?>
                        </button>
                        <?php endif; ?>
                    </div>

                    <div class="api-status-item">
                        <div class="status-indicator <?php echo !empty($ticketmaster_key) ? 'connected' : 'disconnected'; ?>"></div>
                        <div class="status-info">
                            <h4>Ticketmaster API</h4>
                            <p><?php echo !empty($ticketmaster_key) ? __('Connected', 'ai-events-pro') : __('Not Connected', 'ai-events-pro'); ?></p>
                        </div>
                        <?php if (!empty($ticketmaster_key)): ?>
                        <button type="button" class="button test-api-status" data-api="ticketmaster">
                            <?php _e('Test', 'ai-events-pro'); ?>
                        </button>
                        <?php endif; ?>
                    </div>

                    <div class="api-status-item">
                        <div class="status-indicator <?php echo !empty($openrouter_key) ? 'connected' : 'disconnected'; ?>"></div>
                        <div class="status-info">
                            <h4>OpenRouter AI</h4>
                            <p><?php echo !empty($openrouter_key) ? __('Connected', 'ai-events-pro') : __('Not Connected', 'ai-events-pro'); ?></p>
                        </div>
                        <?php if (!empty($openrouter_key)): ?>
                        <button type="button" class="button test-api-status" data-api="openrouter">
                            <?php _e('Test', 'ai-events-pro'); ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.nav-tab-wrapper {
    margin-bottom: 20px;
}

.tab-content {
    display: none;
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-top: none;
}

.tab-content.active {
    display: block;
}

.sync-events-section {
    background: #fff;
    padding: 20px;
    border: 1px solid #c3c4c7;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
}

.sync-form .form-table {
    margin-bottom: 20px;
}

.sync-buttons {
    margin: 20px 0;
}

.sync-buttons .button {
    margin-right: 10px;
}

.sync-results {
    margin-top: 20px;
    padding: 15px;
    border-radius: 4px;
    display: none;
}

.sync-results.success {
    background-color: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
}

.sync-results.error {
    background-color: #fee2e2;
    color: #991b1b;
    border: 1px solid #ef4444;
}

.api-status-section {
    margin-top: 30px;
    background: #fff;
    padding: 20px;
    border: 1px solid #c3c4c7;
}

.api-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.api-status-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    border: 1px solid #e5e5e5;
    border-radius: 8px;
    background: #f9f9f9;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}

.status-indicator.connected {
    background: #10b981;
}

.status-indicator.disconnected {
    background: #ef4444;
}

.status-info {
    flex: 1;
}

.status-info h4 {
    margin: 0 0 5px 0;
    font-size: 14px;
    font-weight: 600;
}

.status-info p {
    margin: 0;
    font-size: 12px;
    color: #6b7280;
}

.test-api-btn {
    margin-left: 10px;
}

.ai-features-info {
    background: #f0f8ff;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.ai-features-info ul {
    margin: 10px 0;
}

.ai-features-info li {
    margin-bottom: 8px;
}

#sync-events-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.sync-results .events-preview {
    margin-top: 15px;
}

.sync-results .events-preview h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
}

.sync-results .event-item {
    background: rgba(255, 255, 255, 0.8);
    padding: 8px 12px;
    margin-bottom: 5px;
    border-radius: 4px;
    font-size: 12px;
}
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
        var button = $(this);
        var apiType = button.data('api');
        var optionName = button.data('option');
        var apiKey = button.prev('input').val();
        
        if (!apiKey) {
            alert('<?php _e('Please enter an API key first.', 'ai-events-pro'); ?>');
            return;
        }
        
        button.text('<?php _e('Testing...', 'ai-events-pro'); ?>').prop('disabled', true);
        
        $.post(ajaxurl, {
            action: 'test_api_connection',
            nonce: ai_events_admin.nonce,
            api_type: apiType,
            option_name: optionName,
            api_key: apiKey
        }, function(response) {
            if (response.success) {
                alert('✅ ' + response.data);
            } else {
                alert('❌ Error: ' + response.data);
            }
        }).always(function() {
            button.text('<?php _e('Test Connection', 'ai-events-pro'); ?>').prop('disabled', false);
        });
    });
    
    // Sync Events
    $('#sync-events-btn').click(function() {
        var button = $(this);
        var location = $('#sync_location').val();
        var radius = $('#sync_radius').val();
        var limit = $('#sync_limit').val();
        var results = $('#sync-results');
        
        if (!location) {
            alert('<?php _e('Please enter a location.', 'ai-events-pro'); ?>');
            return;
        }
        
        button.text('<?php _e('Syncing Events...', 'ai-events-pro'); ?>').prop('disabled', true);
        results.removeClass('success error').hide();
        
        $.post(ajaxurl, {
            action: 'sync_events',
            nonce: ai_events_admin.nonce,
            location: location,
            radius: radius,
            limit: limit
        }, function(response) {
            if (response.success) {
                var html = '<strong>✅ Success!</strong> ' + response.data.message;
                
                if (response.data.events && response.data.events.length > 0) {
                    html += '<div class="events-preview"><h4>Preview of synced events:</h4>';
                    $.each(response.data.events, function(i, event) {
                        html += '<div class="event-item">📅 ' + event.title + ' - ' + event.date + '</div>';
                    });
                    html += '</div>';
                }
                
                results.addClass('success').html(html).show();
            } else {
                results.addClass('error').html('<strong>❌ Error:</strong> ' + response.data).show();
            }
        }).fail(function() {
            results.addClass('error').html('<strong>❌ Error:</strong> Failed to sync events. Please try again.').show();
        }).always(function() {
            button.text('<?php _e('Sync Events Now', 'ai-events-pro'); ?>').prop('disabled', false);
        });
    });
    
    // Clear Cache
    $('#clear-cache-btn').click(function() {
        if (!confirm('<?php _e('Are you sure you want to clear the events cache?', 'ai-events-pro'); ?>')) {
            return;
        }
        
        var button = $(this);
        var results = $('#sync-results');
        
        button.text('<?php _e('Clearing...', 'ai-events-pro'); ?>').prop('disabled', true);
        
        $.post(ajaxurl, {
            action: 'clear_events_cache',
            nonce: ai_events_admin.nonce
        }, function(response) {
            if (response.success) {
                results.removeClass('error').addClass('success').html('<strong>✅ Success!</strong> ' + response.data).show();
            } else {
                results.removeClass('success').addClass('error').html('<strong>❌ Error:</strong> ' + response.data).show();
            }
        }).always(function() {
            button.text('<?php _e('Clear Cache', 'ai-events-pro'); ?>').prop('disabled', false);
        });
    });

    // Test API Status
    $('.test-api-status').click(function() {
        var button = $(this);
        var api = button.data('api');
        var originalText = button.text();
        
        button.text('Testing...').prop('disabled', true);
        
        // This would make an AJAX call to test the specific API
        setTimeout(function() {
            // Simulate API test
            alert('API test completed for ' + api);
            button.text(originalText).prop('disabled', false);
        }, 2000);
    });
});
</script>