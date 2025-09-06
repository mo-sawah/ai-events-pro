<?php
/**
 * Admin settings page template
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
        <a href="#api" class="nav-tab"><?php _e('API Settings', 'ai-events-pro'); ?></a>
        <a href="#ai" class="nav-tab"><?php _e('AI Features', 'ai-events-pro'); ?></a>
        <a href="#display" class="nav-tab"><?php _e('Display', 'ai-events-pro'); ?></a>
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
    
    <div id="api" class="tab-content">
        <form action="options.php" method="post">
            <?php
            settings_fields('ai_events_pro_api_group');
            do_settings_sections('ai_events_pro_api');
            submit_button(__('Save API Settings', 'ai-events-pro'));
            ?>
        </form>
        
        <div class="api-testing-section">
            <h3><?php _e('API Testing Tools', 'ai-events-pro'); ?></h3>
            <div class="sync-events-form">
                <h4><?php _e('Sync Events', 'ai-events-pro'); ?></h4>
                <p><?php _e('Test your API connections and sync events from external sources.', 'ai-events-pro'); ?></p>
                
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
                
                <button type="button" id="sync-events-btn" class="button button-secondary">
                    <?php _e('Sync Events Now', 'ai-events-pro'); ?>
                </button>
                
                <button type="button" id="clear-cache-btn" class="button">
                    <?php _e('Clear Cache', 'ai-events-pro'); ?>
                </button>
                
                <div id="sync-results" style="margin-top: 15px;"></div>
            </div>
        </div>
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
    
    <div id="display" class="tab-content">
        <form action="options.php" method="post">
            <?php
            $settings = get_option('ai_events_pro_settings', array());
            ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Default Theme Mode', 'ai-events-pro'); ?></th>
                    <td>
                        <select name="ai_events_pro_settings[theme_mode]">
                            <option value="auto" <?php selected($settings['theme_mode'] ?? 'auto', 'auto'); ?>><?php _e('Auto (follows system preference)', 'ai-events-pro'); ?></option>
                            <option value="light" <?php selected($settings['theme_mode'] ?? 'auto', 'light'); ?>><?php _e('Light Mode', 'ai-events-pro'); ?></option>
                            <option value="dark" <?php selected($settings['theme_mode'] ?? 'auto', 'dark'); ?>><?php _e('Dark Mode', 'ai-events-pro'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Show Event Images', 'ai-events-pro'); ?></th>
                    <td>
                        <input type="checkbox" name="ai_events_pro_settings[show_images]" value="1" <?php checked(1, $settings['show_images'] ?? 1); ?> />
                        <label><?php _e('Display event images when available', 'ai-events-pro'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Show Event Categories', 'ai-events-pro'); ?></th>
                    <td>
                        <input type="checkbox" name="ai_events_pro_settings[show_categories]" value="1" <?php checked(1, $settings['show_categories'] ?? 1); ?> />
                        <label><?php _e('Display event categories', 'ai-events-pro'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Enable Event Sharing', 'ai-events-pro'); ?></th>
                    <td>
                        <input type="checkbox" name="ai_events_pro_settings[enable_sharing]" value="1" <?php checked(1, $settings['enable_sharing'] ?? 1); ?> />
                        <label><?php _e('Show social sharing buttons on events', 'ai-events-pro'); ?></label>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Save Display Settings', 'ai-events-pro')); ?>
        </form>
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

.api-testing-section {
    margin-top: 30px;
    padding: 20px;
    background: #f9f9f9;
    border: 1px solid #e1e1e1;
}

.sync-events-form h4 {
    margin-top: 0;
}

#sync-results {
    padding: 10px;
    border-radius: 4px;
}

#sync-results.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

#sync-results.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
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

.test-api-btn {
    margin-left: 10px;
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
            api_key: apiKey
        }, function(response) {
            if (response.success) {
                alert(response.data);
            } else {
                alert('Error: ' + response.data);
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
        
        button.text('<?php _e('Syncing...', 'ai-events-pro'); ?>').prop('disabled', true);
        results.removeClass('success error').html('');
        
        $.post(ajaxurl, {
            action: 'sync_events',
            nonce: ai_events_admin.nonce,
            location: location,
            radius: radius,
            limit: limit
        }, function(response) {
            if (response.success) {
                results.addClass('success').html(response.data.message);
            } else {
                results.addClass('error').html('Error: ' + response.data);
            }
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
                results.removeClass('error').addClass('success').html(response.data);
            } else {
                results.removeClass('success').addClass('error').html('Error: ' + response.data);
            }
        }).always(function() {
            button.text('<?php _e('Clear Cache', 'ai-events-pro'); ?>').prop('disabled', false);
        });
    });
});
</script>