<?php

/**
 * Fired during plugin deactivation.
 */
class AI_Events_Deactivator {

    public static function deactivate() {
        // Clear any scheduled cron jobs
        wp_clear_scheduled_hook('ai_events_pro_cleanup_cache');
        wp_clear_scheduled_hook('ai_events_pro_sync_events');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}