<?php
/**
 * Add custom event form template
 */

if (!current_user_can('manage_options')) {
    return;
}

// Handle form submission
if (isset($_POST['submit_event']) && wp_verify_nonce($_POST['ai_events_nonce'], 'add_event')) {
    $event_data = array(
        'post_title' => sanitize_text_field($_POST['event_title']),
        'post_content' => wp_kses_post($_POST['event_description']),
        'post_status' => sanitize_text_field($_POST['event_status']),
        'post_type' => 'ai_event',
        'post_author' => get_current_user_id()
    );
    
    $post_id = wp_insert_post($event_data);
    
    if ($post_id && !is_wp_error($post_id)) {
        // Save meta fields
        $meta_fields = array(
            'event_date' => sanitize_text_field($_POST['event_date']),
            'event_time' => sanitize_text_field($_POST['event_time']),
            'event_end_date' => sanitize_text_field($_POST['event_end_date']),
            'event_end_time' => sanitize_text_field($_POST['event_end_time']),
            'event_location' => sanitize_text_field($_POST['event_location']),
            'event_venue' => sanitize_text_field($_POST['event_venue']),
            'event_price' => sanitize_text_field($_POST['event_price']),
            'event_url' => esc_url_raw($_POST['event_url']),
            'event_organizer' => sanitize_text_field($_POST['event_organizer']),
            'event_contact_email' => sanitize_email($_POST['event_contact_email']),
            'event_contact_phone' => sanitize_text_field($_POST['event_contact_phone']),
            'event_capacity' => absint($_POST['event_capacity']),
            'event_registration_required' => isset($_POST['event_registration_required']) ? 1 : 0
        );
        
        foreach ($meta_fields as $key => $value) {
            if (!empty($value)) {
                update_post_meta($post_id, '_' . $key, $value);
            }
        }
        
        // Handle categories
        if (!empty($_POST['event_categories'])) {
            $categories = array_map('intval', $_POST['event_categories']);
            wp_set_post_terms($post_id, $categories, 'event_category');
        }
        
        // Handle tags
        if (!empty($_POST['event_tags'])) {
            $tags = explode(',', sanitize_text_field($_POST['event_tags']));
            $tags = array_map('trim', $tags);
            wp_set_post_terms($post_id, $tags, 'event_tag');
        }
        
        // Handle featured image
        if (!empty($_POST['event_image_url'])) {
            $this->set_featured_image_from_url($post_id, $_POST['event_image_url']);
        }
        
        $success_message = __('Event created successfully!', 'ai-events-pro');
        
        // AI Enhancement - Generate AI summary and category if enabled
        $settings = get_option('ai_events_pro_settings', array());
        if (!empty($settings['enable_ai_features'])) {
            $api_manager = new AI_Events_API_Manager();
            $event_array = array(
                'title' => $_POST['event_title'],
                'description' => $_POST['event_description']
            );
            $enhanced = $api_manager->enhance_with_ai(array($event_array));
            
            if (!empty($enhanced[0]['ai_category'])) {
                update_post_meta($post_id, '_event_ai_category', $enhanced[0]['ai_category']);
            }
            if (!empty($enhanced[0]['ai_summary'])) {
                update_post_meta($post_id, '_event_ai_summary', $enhanced[0]['ai_summary']);
            }
        }
        
    } else {
        $error_message = __('Failed to create event. Please try again.', 'ai-events-pro');
    }
}

// Get event categories for dropdown
$event_categories = get_terms(array(
    'taxonomy' => 'event_category',
    'hide_empty' => false
));
?>

<div class="wrap">
    <h1><?php _e('Add New Event', 'ai-events-pro'); ?></h1>
    
    <?php if (isset($success_message)): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($success_message); ?></p>
            <p>
                <a href="<?php echo get_edit_post_link($post_id); ?>" class="button">
                    <?php _e('Edit Event', 'ai-events-pro'); ?>
                </a>
                <a href="<?php echo get_permalink($post_id); ?>" class="button" target="_blank">
                    <?php _e('View Event', 'ai-events-pro'); ?>
                </a>
            </p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error_message); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" class="ai-events-add-form">
        <?php wp_nonce_field('add_event', 'ai_events_nonce'); ?>
        
        <div class="form-sections">
            <!-- Basic Information -->
            <div class="form-section">
                <h2><?php _e('Basic Information', 'ai-events-pro'); ?></h2>
                
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="event_title"><?php _e('Event Title', 'ai-events-pro'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" id="event_title" name="event_title" class="large-text" required 
                                   value="<?php echo isset($_POST['event_title']) ? esc_attr($_POST['event_title']) : ''; ?>" />
                            <p class="description"><?php _e('Enter a clear, descriptive title for your event.', 'ai-events-pro'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="event_description"><?php _e('Description', 'ai-events-pro'); ?></label>
                        </th>
                        <td>
                            <?php
                            wp_editor(
                                isset($_POST['event_description']) ? wp_kses_post($_POST['event_description']) : '',
                                'event_description',
                                array(
                                    'textarea_rows' => 8,
                                    'media_buttons' => false,
                                    'teeny' => true,
                                    'quicktags' => true
                                )
                            );
                            ?>
                            <p class="description"><?php _e('Provide a detailed description of your event.', 'ai-events-pro'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="event_status"><?php _e('Status', 'ai-events-pro'); ?></label>
                        </th>
                        <td>
                            <select id="event_status" name="event_status">
                                <option value="publish" <?php selected(isset($_POST['event_status']) ? $_POST['event_status'] : 'publish', 'publish'); ?>>
                                    <?php _e('Published', 'ai-events-pro'); ?>
                                </option>
                                <option value="draft" <?php selected(isset($_POST['event_status']) ? $_POST['event_status'] : '', 'draft'); ?>>
                                    <?php _e('Draft', 'ai-events-pro'); ?>
                                </option>
                                <option value="private" <?php selected(isset($_POST['event_status']) ? $_POST['event_status'] : '', 'private'); ?>>
                                    <?php _e('Private', 'ai-events-pro'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Date & Time -->
            <div class="form-section">
                <h2><?php _e('Date & Time', 'ai-events-pro'); ?></h2>
                
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="event_date"><?php _e('Start Date', 'ai-events-pro'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="date" id="event_date" name="event_date" required
                                   value="<?php echo isset($_POST['event_date']) ? esc_attr($_POST['event_date']) : ''; ?>" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="event_time"><?php _e('Start Time', 'ai-events-pro'); ?></label>
                        </th>
                        <td>
                            <input type="time" id="event_time" name="event_time"
                                   value="<?php echo isset($_POST['event_time']) ? esc_attr($_POST['event_time']) : ''; ?>" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="event_end_date"><?php _e('End Date', 'ai-events-pro'); ?></label>
                        </th>
                        <td>
                            <input type="date" id="event_end_date" name="event_end_date"
                                   value="<?php echo isset($_POST['event_end_date']) ? esc_attr($_POST['event_end_date']) : ''; ?>" />
                            <p class="description"><?php _e('Leave empty for single-day events.', 'ai-events-pro'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="event_end_time"><?php _e('End Time', 'ai-events-pro'); ?></label>
                        </th>
                        <td>
                            <input type="time" id="event_end_time" name="event_end_time"
                                   value="<?php echo isset($_POST['event_end_time']) ? esc_attr($_POST['event_end_time']) : ''; ?>" />
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Location Details -->
            <div class="form-section">
                <h2><?php _e('Location Details', 'ai-events-pro'); ?></h2>
                
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="event_venue"><?php _e('Venue Name', 'ai-events-pro'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="event_venue" name="event_venue" class="regular-text"
                                   value="<?php echo isset($_POST['event_venue']) ? esc_attr($_POST['event_venue']) : ''; ?>" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="event_location"><?php _e('Address/Location', 'ai-events-pro'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <textarea id="event_location" name="event_location" class="large-text" rows="3" required><?php echo isset($_POST['event_location']) ? esc_textarea($_POST['event_location']) : ''; ?></textarea>
                            <p class="description"><?php _e('Enter the full address or location description.', 'ai-events-pro'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Event Details -->
            <div class="form-section">
                <h2><?php _e('Event Details', 'ai-events-pro'); ?></h2>
                
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="event_price"><?php _e('Price', 'ai-events-pro'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="event_price" name="event_price" class="regular-text"
                                   value="<?php echo isset($_POST['event_price']) ? esc_attr($_POST['event_price']) : ''; ?>"
                                   placeholder="<?php _e('Free, $10, $10-20, etc.', 'ai-events-pro'); ?>" />
                            <p class="description"><?php _e('Enter pricing information or "Free" for free events.', 'ai-events-pro'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="event_capacity"><?php _e('Capacity', 'ai-events-pro'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="event_capacity" name="event_capacity" class="small-text" min="1"
                                   value="<?php echo isset($_POST['event_capacity']) ? esc_attr($_POST['event_capacity']) : ''; ?>" />
                            <p class="description"><?php _e('Maximum number of attendees (optional).', 'ai-events-pro'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="event_organizer"><?php _e('Organizer', 'ai-events-pro'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="event_organizer" name="event_organizer" class="regular-text"
                                   value="<?php echo isset($_POST['event_organizer']) ? esc_attr($_POST['event_organizer']) : ''; ?>" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="event_url"><?php _e('Event Website/Registration URL', 'ai-events-pro'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="event_url" name="event_url" class="large-text"
                                   value="<?php echo isset($_POST['event_url']) ? esc_attr($_POST['event_url']) : ''; ?>" />
                            <p class="description"><?php _e('Link to registration page or event website.', 'ai-events-pro'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label><?php _e('Registration Required', 'ai-events-pro'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="event_registration_required" value="1"
                                       <?php checked(isset($_POST['event_registration_required'])); ?> />
                                <?php _e('This event requires registration', 'ai-events-pro'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Contact Information -->
            <div class="form-section">
                <h2><?php _e('Contact Information', 'ai-events-pro'); ?></h2>
                
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="event_contact_email"><?php _e('Contact Email', 'ai-events-pro'); ?></label>
                        </th>
                        <td>
                            <input type="email" id="event_contact_email" name="event_contact_email" class="regular-text"
                                   value="<?php echo isset($_POST['event_contact_email']) ? esc_attr($_POST['event_contact_email']) : ''; ?>" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="event_contact_phone"><?php _e('Contact Phone', 'ai-events-pro'); ?></label>
                        </th>
                        <td>
                            <input type="tel" id="event_contact_phone" name="event_contact_phone" class="regular-text"
                                   value="<?php echo isset($_POST['event_contact_phone']) ? esc_attr($_POST['event_contact_phone']) : ''; ?>" />
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Categories & Tags -->
            <div class="form-section">
                <h2><?php _e('Categories & Tags', 'ai-events-pro'); ?></h2>
                
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="event_categories"><?php _e('Categories', 'ai-events-pro'); ?></label>
                        </th>
                        <td>
                            <?php if (!empty($event_categories)): ?>
                                <div class="categories-checklist">
                                    <?php foreach ($event_categories as $category): ?>
                                        <label style="display: block; margin-bottom: 8px;">
                                            <input type="checkbox" name="event_categories[]" value="<?php echo esc_attr($category->term_id); ?>"
                                                   <?php checked(in_array($category->term_id, isset($_POST['event_categories']) ? $_POST['event_categories'] : array())); ?> />
                                            <?php echo esc_html($category->name); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p><?php _e('No categories available.', 'ai-events-pro'); ?> 
                                   <a href="<?php echo admin_url('edit-tags.php?taxonomy=event_category&post_type=ai_event'); ?>">
                                       <?php _e('Create categories', 'ai-events-pro'); ?>
                                   </a>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="event_tags"><?php _e('Tags', 'ai-events-pro'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="event_tags" name="event_tags" class="large-text"
                                   value="<?php echo isset($_POST['event_tags']) ? esc_attr($_POST['event_tags']) : ''; ?>"
                                   placeholder="<?php _e('music, concert, live, entertainment', 'ai-events-pro'); ?>" />
                            <p class="description"><?php _e('Separate tags with commas.', 'ai-events-pro'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Featured Image -->
            <div class="form-section">
                <h2><?php _e('Featured Image', 'ai-events-pro'); ?></h2>
                
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="event_image_url"><?php _e('Image URL', 'ai-events-pro'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="event_image_url" name="event_image_url" class="large-text"
                                   value="<?php echo isset($_POST['event_image_url']) ? esc_attr($_POST['event_image_url']) : ''; ?>" />
                            <p class="description"><?php _e('Enter an image URL to set as featured image.', 'ai-events-pro'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <p class="submit">
            <input type="submit" name="submit_event" class="button-primary" value="<?php _e('Create Event', 'ai-events-pro'); ?>" />
            <a href="<?php echo admin_url('edit.php?post_type=ai_event'); ?>" class="button">
                <?php _e('Cancel', 'ai-events-pro'); ?>
            </a>
        </p>
    </form>
</div>

<style>
.ai-events-add-form {
    max-width: 800px;
}

.form-sections {
    background: #fff;
    border: 1px solid #c3c4c7;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
}

.form-section {
    padding: 20px;
    border-bottom: 1px solid #c3c4c7;
}

.form-section:last-child {
    border-bottom: none;
}

.form-section h2 {
    margin: 0 0 20px 0;
    padding: 0;
    font-size: 18px;
    font-weight: 600;
    color: #1d2327;
    border-bottom: 1px solid #e0e0e0;
    padding-bottom: 8px;
}

.required {
    color: #d63638;
}

.categories-checklist {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #ddd;
    padding: 10px;
    background: #fafafa;
}

.form-table th {
    width: 200px;
    vertical-align: top;
    padding-top: 15px;
}

.form-table td {
    vertical-align: top;
}

.description {
    font-style: italic;
    color: #646970;
    margin-top: 5px !important;
}

#event_description_ifr {
    min-height: 150px;
}

.wp-editor-container {
    border: 1px solid #ddd;
}

@media (max-width: 782px) {
    .form-table th,
    .form-table td {
        display: block;
        width: 100%;
        padding: 10px 0;
    }
    
    .form-table th {
        border-bottom: 1px solid #e1e1e1;
        padding-bottom: 5px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Auto-fill end date when start date is selected
    $('#event_date').on('change', function() {
        const startDate = $(this).val();
        const endDateField = $('#event_end_date');
        
        if (startDate && !endDateField.val()) {
            endDateField.val(startDate);
        }
    });
    
    // Validate form before submission
    $('.ai-events-add-form').on('submit', function(e) {
        let isValid = true;
        
        // Check required fields
        $(this).find('[required]').each(function() {
            if (!$(this).val().trim()) {
                $(this).css('border-color', '#d63638');
                isValid = false;
            } else {
                $(this).css('border-color', '');
            }
        });
        
        // Validate date logic
        const startDate = $('#event_date').val();
        const endDate = $('#event_end_date').val();
        
        if (startDate && endDate && new Date(endDate) < new Date(startDate)) {
            alert('<?php _e('End date cannot be before start date.', 'ai-events-pro'); ?>');
            $('#event_end_date').css('border-color', '#d63638');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            alert('<?php _e('Please fill in all required fields correctly.', 'ai-events-pro'); ?>');
        }
    });
    
    // Real-time validation
    $('[required]').on('blur', function() {
        if ($(this).val().trim()) {
            $(this).css('border-color', '');
        }
    });
});
</script>

<?php
// Helper function to set featured image from URL
function set_featured_image_from_url($post_id, $image_url) {
    if (empty($image_url)) return false;
    
    $upload_dir = wp_upload_dir();
    $image_data = wp_remote_get($image_url);
    
    if (is_wp_error($image_data)) return false;
    
    $filename = basename($image_url);
    
    // Ensure we have a valid filename
    if (empty($filename) || strpos($filename, '.') === false) {
        $filename = 'event-image-' . $post_id . '.jpg';
    }
    
    $file = $upload_dir['path'] . '/' . $filename;
    
    // Save image to uploads directory
    $saved = file_put_contents($file, wp_remote_retrieve_body($image_data));
    
    if (!$saved) return false;
    
    // Create attachment
    $attachment = array(
        'post_mime_type' => wp_check_filetype($filename, null)['type'],
        'post_title' => sanitize_file_name($filename),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    
    $attach_id = wp_insert_attachment($attachment, $file, $post_id);
    
    if (!$attach_id) return false;
    
    // Generate metadata and set as featured image
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $file);
    wp_update_attachment_metadata($attach_id, $attach_data);
    
    return set_post_thumbnail($post_id, $attach_id);
}
?>