<?php
/**
 * Complete Settings Page with debug panel and separate forms
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
            
            <!-- Debug Panel -->
            <div class="debug-panel">
                <h4><?php _e('Debug Information', 'ai-events-pro'); ?></h4>
                <div class="debug-actions">
                    <button type="button" id="show-debug-btn" class="button">
                        <?php _e('Show Debug Info', 'ai-events-pro'); ?>
                    </button>
                    <button type="button" id="clear-debug-btn" class="button">
                        <?php _e('Clear Debug Log', 'ai-events-pro'); ?>
                    </button>
                </div>
                <div id="debug-info" style="display: none;">
                    <h5>Current Settings Status:</h5>
                    <div id="settings-status"></div>
                    
                    <h5>Recent Debug Log:</h5>
                    <div id="debug-log"></div>
                </div>
            </div>
            
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
                    
                    <?php if (!empty($enabled_apis['custom'])): ?>
                    <div class="api-status-item">
                        <span class="status-dot connected"></span>
                        <span>Custom Events: <?php _e('Always Ready', 'ai-events-pro'); ?></span>
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
            <div class="sync-form-section">
                <h4><?php _e('Manual Event Sync', 'ai-events-pro'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="sync_location"><?php _e('Location', 'ai-events-pro'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="sync_location" name="sync_location" value="" class="regular-text" 
                                   placeholder="<?php _e('e.g., New York, NY', 'ai-events-pro'); ?>" required />
                            <p class="description"><?php _e('Enter a city, state, or specific location to find events nearby.', 'ai-events-pro'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="sync_radius"><?php _e('Search Radius (miles)', 'ai-events-pro'); ?></label>
                        </th>
                        <td>
                            <select id="sync_radius" name="sync_radius">
                                <option value="5">5 miles</option>
                                <option value="10">10 miles</option>
                                <option value="25" selected>25 miles</option>
                                <option value="50">50 miles</option>
                                <option value="100">100 miles</option>
                                <option value="250">250 miles</option>
                                <option value="500">500 miles</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="sync_limit"><?php _e('Max Events', 'ai-events-pro'); ?></label>
                        </th>
                        <td>
                            <select id="sync_limit" name="sync_limit">
                                <option value="25">25 events</option>
                                <option value="50" selected>50 events</option>
                                <option value="100">100 events</option>
                                <option value="200">200 events</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="button" id="sync-events-btn" class="button button-primary">
                        <?php _e('Sync Events Now', 'ai-events-pro'); ?>
                    </button>
                    <button type="button" id="clear-cache-btn" class="button">
                        <?php _e('Clear Cache', 'ai-events-pro'); ?>
                    </button>
                </p>
                
                <div id="sync-results" class="sync-results"></div>
            </div>
        </div>
    </div>
</div>

<style>
.nav-tab-wrapper {
    border-bottom: 1px solid #ccc;
    margin-bottom: 20px;
}

.tab-content {
    display: none;
    padding: 20px 0;
}

.tab-content.active {
    display: block;
}

.debug-panel {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.debug-actions {
    margin-bottom: 15px;
}

.debug-actions button {
    margin-right: 10px;
}

#debug-log {
    background: #fff;
    padding: 10px;
    border: 1px solid #ccc;
    max-height: 300px;
    overflow-y: auto;
    font-family: monospace;
    font-size: 12px;
    white-space: pre-line;
}

.api-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin: 15px 0;
}

.api-status-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 4px;
}

.status-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
}

.status-dot.connected {
    background-color: #46b450;
}

.status-dot.disconnected {
    background-color: #dc3232;
}

.sync-results {
    margin-top: 20px;
    padding: 15px;
    border-radius: 4px;
}

.sync-results.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.sync-results.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.sync-success, .sync-error {
    padding: 15px;
    border-radius: 4px;
    margin-top: 15px;
}

.sync-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.sync-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.events-preview {
    margin-top: 15px;
    padding: 10px;
    background: rgba(255, 255, 255, 0.8);
    border-radius: 4px;
}

.events-preview h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
}

.events-preview ul {
    margin: 0;
    padding-left: 20px;
}

.events-preview li {
    margin-bottom: 5px;
    font-size: 13px;
}

.api-info {
    background: #e7f3ff;
    border-left: 4px solid #0073aa;
    padding: 15px;
    margin: 10px 0;
}

.ai-features-info {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 15px;
    margin: 15px 0;
}

details {
    margin-top: 10px;
}

summary {
    cursor: pointer;
    font-weight: bold;
}

details ul {
    margin-top: 10px;
    padding-left: 20px;
}

#settings-status table {
    margin-bottom: 15px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var target = $(this).attr('href');
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
        
        // Save active tab to localStorage
        localStorage.setItem('ai-events-active-tab', target);
    });
    
    // Restore active tab
    var activeTab = localStorage.getItem('ai-events-active-tab');
    if (activeTab && $(activeTab).length) {
        $('a[href="' + activeTab + '"]').click();
    }
    
    // Debug panel functionality
    $('#show-debug-btn').click(function() {
        var $debugInfo = $('#debug-info');
        var $button = $(this);
        
        if ($debugInfo.is(':visible')) {
            $debugInfo.hide();
            $button.text('<?php _e('Show Debug Info', 'ai-events-pro'); ?>');
        } else {
            $button.text('<?php _e('Loading...', 'ai-events-pro'); ?>').prop('disabled', true);
            
            // Load current settings status
            loadSettingsStatus();
            
            // Load debug log
            $.post(ajaxurl, {
                action: 'get_debug_log',
                nonce: '<?php echo wp_create_nonce('ai_events_admin_nonce'); ?>'
            }).done(function(response) {
                if (response.success) {
                    var logHtml = '';
                    if (response.data.debug_log.length > 0) {
                        response.data.debug_log.forEach(function(entry) {
                            logHtml += '[' + entry.timestamp + '] ' + entry.message + '\n';
                        });
                    } else {
                        logHtml = 'No debug entries found. Try syncing events to generate debug information.';
                    }
                    $('#debug-log').text(logHtml);
                }
            }).always(function() {
                $debugInfo.show();
                $button.text('<?php _e('Hide Debug Info', 'ai-events-pro'); ?>').prop('disabled', false);
            });
        }
    });
    
    $('#clear-debug-btn').click(function() {
        if (!confirm('<?php _e('Clear debug log?', 'ai-events-pro'); ?>')) return;
        
        var $button = $(this);
        $button.prop('disabled', true).text('<?php _e('Clearing...', 'ai-events-pro'); ?>');
        
        $.post(ajaxurl, {
            action: 'clear_debug_log',
            nonce: '<?php echo wp_create_nonce('ai_events_admin_nonce'); ?>'
        }).done(function(response) {
            if (response.success) {
                $('#debug-log').text('Debug log cleared.');
            }
        }).always(function() {
            $button.prop('disabled', false).text('<?php _e('Clear Debug Log', 'ai-events-pro'); ?>');
        });
    });
    
    // Sync events
    $('#sync-events-btn').click(function() {
        var $button = $(this);
        var $results = $('#sync-results');
        
        var location = $('#sync_location').val().trim();
        var radius = $('#sync_radius').val();
        var limit = $('#sync_limit').val();
        
        if (!location) {
            alert('<?php _e('Please enter a location to sync events.', 'ai-events-pro'); ?>');
            $('#sync_location').focus();
            return;
        }
        
        // Show loading state
        $button.prop('disabled', true).text('<?php _e('Syncing Events...', 'ai-events-pro'); ?>');
        $results.removeClass('success error').hide();
        
        $.post(ajaxurl, {
            action: 'sync_events',
            nonce: '<?php echo wp_create_nonce('ai_events_admin_nonce'); ?>',
            location: location,
            radius: radius,
            limit: limit
        }).done(function(response) {
            if (response.success) {
                var html = '<div class="sync-success">';
                html += '<strong>✅ ' + response.data.message + '</strong>';
                html += '<p>Total events synced: ' + response.data.events_count + '</p>';
                
                if (response.data.events_preview && response.data.events_preview.length > 0) {
                    html += '<div class="events-preview"><h4>Preview of synced events:</h4><ul>';
                    response.data.events_preview.forEach(function(event) {
                        html += '<li><strong>' + event.title + '</strong> - ' + event.date + ' (' + event.source + ')</li>';
                    });
                    html += '</ul></div>';
                }
                html += '</div>';
                
                $results.addClass('success').html(html).slideDown();
                
                // Update debug info if panel is open
                if ($('#debug-info').is(':visible')) {
                    updateDebugInfo(response.data.debug_info, response.data.sources_used);
                }
            } else {
                // Enhanced error display with debug info
                var errorHtml = '<div class="sync-error"><strong>❌ Sync Failed</strong>';
                
                if (response.data.debug_info) {
                    errorHtml += '<h4>Debug Information:</h4>';
                    errorHtml += '<pre style="background: #fff; padding: 10px; overflow-x: auto;">' + 
                               JSON.stringify(response.data.debug_info, null, 2) + '</pre>';
                }
                
                if (response.data.suggestions) {
                    errorHtml += '<h4>Suggestions:</h4><ul>';
                    response.data.suggestions.forEach(function(suggestion) {
                        errorHtml += '<li>' + suggestion + '</li>';
                    });
                    errorHtml += '</ul>';
                }
                
                if (response.data.debug_log) {
                    errorHtml += '<details><summary>Recent Debug Log</summary><pre style="background: #fff; padding: 10px; max-height: 200px; overflow-y: auto;">';
                    response.data.debug_log.forEach(function(entry) {
                        errorHtml += '[' + entry.timestamp + '] ' + entry.message + '\n';
                    });
                    errorHtml += '</pre></details>';
                }
                
                errorHtml += '</div>';
                $results.addClass('error').html(errorHtml).slideDown();
            }
        }).fail(function(xhr, status, error) {
            $results.addClass('error')
                    .html('<strong>❌ Network Error:</strong> ' + error)
                    .slideDown();
        }).always(function() {
            $button.prop('disabled', false).text('<?php _e('Sync Events Now', 'ai-events-pro'); ?>');
        });
    });
    
    // Clear cache
    $('#clear-cache-btn').click(function() {
        if (!confirm('<?php _e('Are you sure you want to clear all cached events? This cannot be undone.', 'ai-events-pro'); ?>')) {
            return;
        }
        
        var $button = $(this);
        var $results = $('#sync-results');
        
        $button.prop('disabled', true).text('<?php _e('Clearing Cache...', 'ai-events-pro'); ?>');
        
        $.post(ajaxurl, {
            action: 'clear_events_cache',
            nonce: '<?php echo wp_create_nonce('ai_events_admin_nonce'); ?>'
        }).done(function(response) {
            if (response.success) {
                $results.removeClass('error')
                        .addClass('success')
                        .html('<strong>✅ ' + response.data + '</strong>')
                        .slideDown();
            } else {
                $results.removeClass('success')
                        .addClass('error')
                        .html('<strong>❌ Error:</strong> ' + response.data)
                        .slideDown();
            }
        }).always(function() {
            $button.prop('disabled', false).text('<?php _e('Clear Cache', 'ai-events-pro'); ?>');
        });
    });
    
    function loadSettingsStatus() {
        var statusHtml = '<table class="widefat"><thead><tr><th>Source</th><th>Enabled</th><th>Configured</th><th>Status</th></tr></thead><tbody>';
        statusHtml += '<tr><td colspan="4">Click "Sync Events Now" to see current status</td></tr>';
        statusHtml += '</tbody></table>';
        $('#settings-status').html(statusHtml);
    }
    
    function updateDebugInfo(debugInfo, sourcesUsed) {
        if (!debugInfo) return;
        
        var statusHtml = '<table class="widefat"><thead><tr><th>Source</th><th>Enabled</th><th>Configured</th><th>Status</th></tr></thead><tbody>';
        
        // Eventbrite
        statusHtml += '<tr>';
        statusHtml += '<td>Eventbrite</td>';
        statusHtml += '<td>' + (debugInfo.api_status.eventbrite.enabled ? '✅ Yes' : '❌ No') + '</td>';
        statusHtml += '<td>' + (debugInfo.api_status.eventbrite.configured ? '✅ Yes' : '❌ No') + '</td>';
        statusHtml += '<td>' + (sourcesUsed.eventbrite ? sourcesUsed.eventbrite + ' events' : 'No events') + '</td>';
        statusHtml += '</tr>';
        
        // Ticketmaster
        statusHtml += '<tr>';
        statusHtml += '<td>Ticketmaster</td>';
        statusHtml += '<td>' + (debugInfo.api_status.ticketmaster.enabled ? '✅ Yes' : '❌ No') + '</td>';
        statusHtml += '<td>' + (debugInfo.api_status.ticketmaster.configured ? '✅ Yes' : '❌ No') + '</td>';
        statusHtml += '<td>' + (sourcesUsed.ticketmaster ? sourcesUsed.ticketmaster + ' events' : 'No events') + '</td>';
        statusHtml += '</tr>';
        
        // Custom
        statusHtml += '<tr>';
        statusHtml += '<td>Custom Events</td>';
        statusHtml += '<td>' + (debugInfo.api_status.custom.enabled ? '✅ Yes' : '❌ No') + '</td>';
        statusHtml += '<td>✅ Always</td>';
        statusHtml += '<td>' + (sourcesUsed.custom ? sourcesUsed.custom + ' events' : 'No events') + '</td>';
        statusHtml += '</tr>';
        
        statusHtml += '</tbody></table>';
        $('#settings-status').html(statusHtml);
    }
});
</script>