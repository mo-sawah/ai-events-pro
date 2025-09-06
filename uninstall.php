<?php
/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete all plugin options
delete_option('ai_events_pro_settings');
delete_option('ai_events_pro_eventbrite_token');
delete_option('ai_events_pro_ticketmaster_key');
delete_option('ai_events_pro_openrouter_key');

// Delete all custom events
$events = get_posts(array(
    'post_type' => 'ai_event',
    'numberposts' => -1,
    'post_status' => 'any'
));

foreach ($events as $event) {
    wp_delete_post($event->ID, true);
}

// Drop custom tables if any
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ai_events_cache");

// Clear any cached data
wp_cache_flush();