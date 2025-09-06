<?php
/**
 * List of events admin template
 */

if (!current_user_can('manage_options')) {
    return;
}

// Handle bulk actions
if (isset($_POST['action']) && $_POST['action'] !== '-1') {
    $action = sanitize_text_field($_POST['action']);
    $event_ids = isset($_POST['event_ids']) ? array_map('intval', $_POST['event_ids']) : array();
    
    if (!empty($event_ids) && wp_verify_nonce($_POST['_wpnonce'], 'bulk-events')) {
        switch ($action) {
            case 'delete':
                foreach ($event_ids as $event_id) {
                    wp_delete_post($event_id, true);
                }
                $success_message = sprintf(__('%d events deleted successfully.', 'ai-events-pro'), count($event_ids));
                break;
                
            case 'publish':
                foreach ($event_ids as $event_id) {
                    wp_update_post(array('ID' => $event_id, 'post_status' => 'publish'));
                }
                $success_message = sprintf(__('%d events published successfully.', 'ai-events-pro'), count($event_ids));
                break;
                
            case 'draft':
                foreach ($event_ids as $event_id) {
                    wp_update_post(array('ID' => $event_id, 'post_status' => 'draft'));
                }
                $success_message = sprintf(__('%d events moved to draft.', 'ai-events-pro'), count($event_ids));
                break;
        }
    }
}

// Get events with pagination
$paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$status_filter = isset($_GET['post_status']) ? sanitize_text_field($_GET['post_status']) : 'all';
$category_filter = isset($_GET['event_category']) ? sanitize_text_field($_GET['event_category']) : '';

$args = array(
    'post_type' => 'ai_event',
    'posts_per_page' => 20,
    'paged' => $paged,
    'orderby' => 'date',
    'order' => 'DESC'
);

if (!empty($search)) {
    $args['s'] = $search;
}

if ($status_filter !== 'all') {
    $args['post_status'] = $status_filter;
} else {
    $args['post_status'] = array('publish', 'draft', 'private');
}

if (!empty($category_filter)) {
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'event_category',
            'field' => 'slug',
            'terms' => $category_filter
        )
    );
}

$events_query = new WP_Query($args);
$events = $events_query->posts;

// Get status counts
$status_counts = array();
foreach (array('publish', 'draft', 'private') as $status) {
    $count_query = new WP_Query(array(
        'post_type' => 'ai_event',
        'post_status' => $status,
        'posts_per_page' => 1,
        'fields' => 'ids'
    ));
    $status_counts[$status] = $count_query->found_posts;
}

// Get categories for filter
$categories = get_terms(array(
    'taxonomy' => 'event_category',
    'hide_empty' => false
));
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Events', 'ai-events-pro'); ?></h1>
    
    <a href="<?php echo admin_url('admin.php?page=ai-events-pro-settings&tab=add-event'); ?>" class="page-title-action">
        <?php _e('Add New', 'ai-events-pro'); ?>
    </a>
    
    <a href="<?php echo admin_url('post-new.php?post_type=ai_event'); ?>" class="page-title-action">
        <?php _e('Add New (Advanced)', 'ai-events-pro'); ?>
    </a>
    
    <hr class="wp-header-end">

    <?php if (isset($success_message)): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($success_message); ?></p>
        </div>
    <?php endif; ?>

    <!-- Filters and Search -->
    <div class="events-filters">
        <div class="subsubsub">
            <a href="<?php echo remove_query_arg('post_status'); ?>" 
               class="<?php echo $status_filter === 'all' ? 'current' : ''; ?>">
                <?php _e('All', 'ai-events-pro'); ?> 
                <span class="count">(<?php echo array_sum($status_counts); ?>)</span>
            </a> |
            
            <a href="<?php echo add_query_arg('post_status', 'publish'); ?>" 
               class="<?php echo $status_filter === 'publish' ? 'current' : ''; ?>">
                <?php _e('Published', 'ai-events-pro'); ?> 
                <span class="count">(<?php echo $status_counts['publish']; ?>)</span>
            </a> |
            
            <a href="<?php echo add_query_arg('post_status', 'draft'); ?>" 
               class="<?php echo $status_filter === 'draft' ? 'current' : ''; ?>">
                <?php _e('Draft', 'ai-events-pro'); ?> 
                <span class="count">(<?php echo $status_counts['draft']; ?>)</span>
            </a> |
            
            <a href="<?php echo add_query_arg('post_status', 'private'); ?>" 
               class="<?php echo $status_filter === 'private' ? 'current' : ''; ?>">
                <?php _e('Private', 'ai-events-pro'); ?> 
                <span class="count">(<?php echo $status_counts['private']; ?>)</span>
            </a>
        </div>

        <div class="search-form">
            <form method="get" style="margin-bottom: 20px;">
                <input type="hidden" name="page" value="ai-events-pro-events" />
                <?php if ($status_filter !== 'all'): ?>
                    <input type="hidden" name="post_status" value="<?php echo esc_attr($status_filter); ?>" />
                <?php endif; ?>
                
                <div class="filter-controls">
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" 
                           placeholder="<?php _e('Search events...', 'ai-events-pro'); ?>" class="search-input" />
                    
                    <?php if (!empty($categories)): ?>
                        <select name="event_category">
                            <option value=""><?php _e('All Categories', 'ai-events-pro'); ?></option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo esc_attr($category->slug); ?>" 
                                        <?php selected($category_filter, $category->slug); ?>>
                                    <?php echo esc_html($category->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                    
                    <input type="submit" class="button" value="<?php _e('Filter', 'ai-events-pro'); ?>" />
                    
                    <?php if (!empty($search) || !empty($category_filter)): ?>
                        <a href="<?php echo remove_query_arg(array('s', 'event_category')); ?>" class="button">
                            <?php _e('Clear', 'ai-events-pro'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Events Table -->
    <form method="post" id="events-filter">
        <?php wp_nonce_field('bulk-events'); ?>
        
        <!-- Bulk Actions -->
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <select name="action" id="bulk-action-selector-top">
                    <option value="-1"><?php _e('Bulk Actions', 'ai-events-pro'); ?></option>
                    <option value="delete"><?php _e('Delete', 'ai-events-pro'); ?></option>
                    <option value="publish"><?php _e('Publish', 'ai-events-pro'); ?></option>
                    <option value="draft"><?php _e('Move to Draft', 'ai-events-pro'); ?></option>
                </select>
                <input type="submit" class="button action" value="<?php _e('Apply', 'ai-events-pro'); ?>" />
            </div>
            
            <!-- Pagination -->
            <?php
            $pagination = paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => $events_query->max_num_pages,
                'current' => $paged,
                'type' => 'array'
            ));
            
            if ($pagination): ?>
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php printf(__('%d items', 'ai-events-pro'), $events_query->found_posts); ?>
                    </span>
                    <span class="pagination-links">
                        <?php echo implode(' ', $pagination); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <table class="wp-list-table widefat fixed striped events">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all-1" />
                    </td>
                    <th class="manage-column column-title"><?php _e('Title', 'ai-events-pro'); ?></th>
                    <th class="manage-column column-date"><?php _e('Event Date', 'ai-events-pro'); ?></th>
                    <th class="manage-column column-location"><?php _e('Location', 'ai-events-pro'); ?></th>
                    <th class="manage-column column-category"><?php _e('Category', 'ai-events-pro'); ?></th>
                    <th class="manage-column column-status"><?php _e('Status', 'ai-events-pro'); ?></th>
                    <th class="manage-column column-actions"><?php _e('Actions', 'ai-events-pro'); ?></th>
                </tr>
            </thead>
            
            <tbody>
                <?php if (empty($events)): ?>
                    <tr class="no-items">
                        <td class="colspanchange" colspan="7">
                            <?php _e('No events found.', 'ai-events-pro'); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($events as $event): 
                        $event_date = get_post_meta($event->ID, '_event_date', true);
                        $event_time = get_post_meta($event->ID, '_event_time', true);
                        $event_location = get_post_meta($event->ID, '_event_location', true);
                        $event_venue = get_post_meta($event->ID, '_event_venue', true);
                        $categories = get_the_terms($event->ID, 'event_category');
                        $status_class = 'status-' . $event->post_status;
                        ?>
                        <tr class="<?php echo esc_attr($status_class); ?>">
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="event_ids[]" value="<?php echo $event->ID; ?>" />
                            </th>
                            
                            <td class="title column-title">
                                <strong>
                                    <a href="<?php echo get_edit_post_link($event->ID); ?>" class="row-title">
                                        <?php echo esc_html($event->post_title); ?>
                                    </a>
                                </strong>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo get_edit_post_link($event->ID); ?>">
                                            <?php _e('Edit', 'ai-events-pro'); ?>
                                        </a> |
                                    </span>
                                    <span class="view">
                                        <a href="<?php echo get_permalink($event->ID); ?>" target="_blank">
                                            <?php _e('View', 'ai-events-pro'); ?>
                                        </a> |
                                    </span>
                                    <span class="trash">
                                        <a href="<?php echo get_delete_post_link($event->ID); ?>" class="delete-event">
                                            <?php _e('Delete', 'ai-events-pro'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            
                            <td class="date column-date">
                                <?php if (!empty($event_date)): ?>
                                    <strong><?php echo esc_html(date('M j, Y', strtotime($event_date))); ?></strong><br>
                                    <?php if (!empty($event_time)): ?>
                                        <span class="time"><?php echo esc_html(date('g:i A', strtotime($event_time))); ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="na">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="location column-location">
                                <?php if (!empty($event_venue)): ?>
                                    <strong><?php echo esc_html($event_venue); ?></strong><br>
                                <?php endif; ?>
                                <?php if (!empty($event_location)): ?>
                                    <span class="address"><?php echo esc_html(wp_trim_words($event_location, 8)); ?></span>
                                <?php else: ?>
                                    <span class="na">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="category column-category">
                                <?php if (!empty($categories)): ?>
                                    <?php
                                    $category_names = array();
                                    foreach ($categories as $category) {
                                        $category_names[] = $category->name;
                                    }
                                    echo esc_html(implode(', ', $category_names));
                                    ?>
                                <?php else: ?>
                                    <span class="na">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="status column-status">
                                <span class="status-badge status-<?php echo esc_attr($event->post_status); ?>">
                                    <?php
                                    switch ($event->post_status) {
                                        case 'publish':
                                            _e('Published', 'ai-events-pro');
                                            break;
                                        case 'draft':
                                            _e('Draft', 'ai-events-pro');
                                            break;
                                        case 'private':
                                            _e('Private', 'ai-events-pro');
                                            break;
                                        default:
                                            echo esc_html(ucfirst($event->post_status));
                                    }
                                    ?>
                                </span>
                            </td>
                            
                            <td class="actions column-actions">
                                <div class="action-buttons">
                                    <a href="<?php echo get_edit_post_link($event->ID); ?>" class="button button-small">
                                        <?php _e('Edit', 'ai-events-pro'); ?>
                                    </a>
                                    <?php if ($event->post_status === 'draft'): ?>
                                        <a href="<?php echo wp_nonce_url(admin_url('post.php?action=edit&post=' . $event->ID . '&publish=1'), 'publish-post_' . $event->ID); ?>" 
                                           class="button button-small button-primary">
                                            <?php _e('Publish', 'ai-events-pro'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Bottom Pagination -->
        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <select name="action2" id="bulk-action-selector-bottom">
                    <option value="-1"><?php _e('Bulk Actions', 'ai-events-pro'); ?></option>
                    <option value="delete"><?php _e('Delete', 'ai-events-pro'); ?></option>
                    <option value="publish"><?php _e('Publish', 'ai-events-pro'); ?></option>
                    <option value="draft"><?php _e('Move to Draft', 'ai-events-pro'); ?></option>
                </select>
                <input type="submit" class="button action" value="<?php _e('Apply', 'ai-events-pro'); ?>" />
            </div>
            
            <?php if ($pagination): ?>
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php printf(__('%d items', 'ai-events-pro'), $events_query->found_posts); ?>
                    </span>
                    <span class="pagination-links">
                        <?php echo implode(' ', $pagination); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </form>
    
    <!-- Quick Stats -->
    <div class="events-quick-stats">
        <div class="stats-row">
            <div class="stat-item">
                <span class="stat-label"><?php _e('Total Events:', 'ai-events-pro'); ?></span>
                <span class="stat-value"><?php echo array_sum($status_counts); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label"><?php _e('Published:', 'ai-events-pro'); ?></span>
                <span class="stat-value"><?php echo $status_counts['publish']; ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label"><?php _e('Upcoming Events:', 'ai-events-pro'); ?></span>
                <span class="stat-value">
                    <?php
                    $upcoming_query = new WP_Query(array(
                        'post_type' => 'ai_event',
                        'post_status' => 'publish',
                        'meta_query' => array(
                            array(
                                'key' => '_event_date',
                                'value' => date('Y-m-d'),
                                'compare' => '>='
                            )
                        ),
                        'fields' => 'ids'
                    ));
                    echo $upcoming_query->found_posts;
                    ?>
                </span>
            </div>
        </div>
    </div>
</div>

<style>
.events-filters {
    margin: 20px 0;
}

.filter-controls {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.search-input {
    min-width: 250px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-publish {
    background: #d1fae5;
    color: #065f46;
}

.status-draft {
    background: #fef3c7;
    color: #92400e;
}

.status-private {
    background: #e5e7eb;
    color: #374151;
}

.column-actions {
    width: 120px;
}

.action-buttons {
    display: flex;
    gap: 5px;
    flex-direction: column;
}

.column-date {
    width: 120px;
}

.column-location {
    width: 180px;
}

.column-category {
    width: 120px;
}

.column-status {
    width: 80px;
}

.time {
    color: #666;
    font-size: 12px;
}

.address {
    color: #666;
    font-size: 12px;
}

.na {
    color: #999;
    font-style: italic;
}

.events-quick-stats {
    margin-top: 30px;
    background: #fff;
    border: 1px solid #c3c4c7;
    padding: 15px 20px;
}

.stats-row {
    display: flex;
    gap: 30px;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    gap: 8px;
    align-items: center;
}

.stat-label {
    color: #646970;
    font-weight: 500;
}

.stat-value {
    font-weight: 600;
    color: #1d2327;
}

.delete-event {
    color: #a00 !important;
}

.delete-event:hover {
    color: #dc3232 !important;
}

@media (max-width: 782px) {
    .filter-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-input {
        min-width: auto;
        width: 100%;
    }
    
    .stats-row {
        flex-direction: column;
        gap: 10px;
    }
    
    .action-buttons {
        flex-direction: row;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Select all checkbox functionality
    $('#cb-select-all-1').on('change', function() {
        $('input[name="event_ids[]"]').prop('checked', $(this).prop('checked'));
    });
    
    // Bulk actions confirmation
    $('#events-filter').on('submit', function(e) {
        const action = $('select[name="action"]').val();
        const checkedBoxes = $('input[name="event_ids[]"]:checked');
        
        if (action === '-1') {
            alert('<?php _e('Please select an action.', 'ai-events-pro'); ?>');
            e.preventDefault();
            return;
        }
        
        if (checkedBoxes.length === 0) {
            alert('<?php _e('Please select at least one event.', 'ai-events-pro'); ?>');
            e.preventDefault();
            return;
        }
        
        if (action === 'delete') {
            if (!confirm('<?php _e('Are you sure you want to delete the selected events? This cannot be undone.', 'ai-events-pro'); ?>')) {
                e.preventDefault();
            }
        }
    });
    
    // Individual delete confirmation
    $('.delete-event').on('click', function(e) {
        if (!confirm('<?php _e('Are you sure you want to delete this event?', 'ai-events-pro'); ?>')) {
            e.preventDefault();
        }
    });
    
    // Auto-submit filter form when category changes
    $('select[name="event_category"]').on('change', function() {
        $(this).closest('form').submit();
    });
});
</script>