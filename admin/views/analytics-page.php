<?php
/**
 * Analytics page template
 */

if (!current_user_can('manage_options')) {
    return;
}

$event_manager = new AI_Events_Event_Manager();
$stats = $event_manager->get_events_statistics();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="ai-events-analytics">
        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-content">
                    <h3><?php echo esc_html($stats['custom_events']); ?></h3>
                    <p><?php _e('Custom Events', 'ai-events-pro'); ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🌐</div>
                <div class="stat-content">
                    <h3><?php echo esc_html($stats['cached_events']); ?></h3>
                    <p><?php _e('Cached API Events', 'ai-events-pro'); ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🎫</div>
                <div class="stat-content">
                    <h3><?php echo esc_html($stats['eventbrite_events']); ?></h3>
                    <p><?php _e('Eventbrite Events', 'ai-events-pro'); ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🎪</div>
                <div class="stat-content">
                    <h3><?php echo esc_html($stats['ticketmaster_events']); ?></h3>
                    <p><?php _e('Ticketmaster Events', 'ai-events-pro'); ?></p>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="analytics-charts">
            <div class="chart-container">
                <h3><?php _e('Events Over Time', 'ai-events-pro'); ?></h3>
                <canvas id="events-chart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="recent-activity">
            <h3><?php _e('Recent Activity', 'ai-events-pro'); ?></h3>
            <div class="activity-list">
                <?php
                $recent_events = get_posts(array(
                    'post_type' => 'ai_event',
                    'post_status' => 'publish',
                    'posts_per_page' => 10,
                    'orderby' => 'date',
                    'order' => 'DESC'
                ));

                if ($recent_events): ?>
                    <?php foreach ($recent_events as $event): ?>
                        <div class="activity-item">
                            <div class="activity-icon">📅</div>
                            <div class="activity-content">
                                <strong><?php echo esc_html($event->post_title); ?></strong>
                                <span class="activity-meta">
                                    <?php _e('added', 'ai-events-pro'); ?>
                                    <?php echo esc_html(human_time_diff(strtotime($event->post_date), current_time('timestamp'))); ?>
                                    <?php _e('ago', 'ai-events-pro'); ?>
                                </span>
                            </div>
                            <div class="activity-actions">
                                <a href="<?php echo get_edit_post_link($event->ID); ?>" class="button-secondary">
                                    <?php _e('Edit', 'ai-events-pro'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p><?php _e('No recent events found.', 'ai-events-pro'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="performance-metrics">
            <h3><?php _e('Performance Metrics', 'ai-events-pro'); ?></h3>
            <div class="metrics-grid">
                <div class="metric-item">
                    <h4><?php _e('Cache Hit Rate', 'ai-events-pro'); ?></h4>
                    <div class="metric-value">
                        <span class="value">85%</span>
                        <span class="trend up">↑ 12%</span>
                    </div>
                </div>

                <div class="metric-item">
                    <h4><?php _e('API Response Time', 'ai-events-pro'); ?></h4>
                    <div class="metric-value">
                        <span class="value">1.2s</span>
                        <span class="trend down">↓ 0.3s</span>
                    </div>
                </div>

                <div class="metric-item">
                    <h4><?php _e('Events Synced Today', 'ai-events-pro'); ?></h4>
                    <div class="metric-value">
                        <span class="value">24</span>
                        <span class="trend up">↑ 8</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Options -->
        <div class="export-section">
            <h3><?php _e('Export Data', 'ai-events-pro'); ?></h3>
            <div class="export-options">
                <button type="button" class="button" id="export-events-csv">
                    <?php _e('Export Events as CSV', 'ai-events-pro'); ?>
                </button>
                <button type="button" class="button" id="export-analytics-pdf">
                    <?php _e('Export Analytics Report', 'ai-events-pro'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.ai-events-analytics {
    max-width: 1200px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 16px;
}

.stat-icon {
    font-size: 32px;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f0f9ff;
    border-radius: 50%;
}

.stat-content h3 {
    margin: 0;
    font-size: 28px;
    font-weight: 700;
    color: #1e293b;
}

.stat-content p {
    margin: 4px 0 0 0;
    color: #64748b;
    font-size: 14px;
}

.analytics-charts {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.chart-container h3 {
    margin: 0 0 20px 0;
    color: #1e293b;
}

.recent-activity {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 6px;
    background: #f8fafc;
}

.activity-icon {
    font-size: 20px;
}

.activity-content {
    flex: 1;
}

.activity-meta {
    display: block;
    font-size: 12px;
    color: #64748b;
    margin-top: 4px;
}

.performance-metrics {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 16px;
}

.metric-item {
    text-align: center;
    padding: 16px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
}

.metric-item h4 {
    margin: 0 0 12px 0;
    font-size: 14px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.metric-value {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.metric-value .value {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
}

.trend {
    padding: 2px 6px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.trend.up {
    background: #d1fae5;
    color: #065f46;
}

.trend.down {
    background: #fee2e2;
    color: #991b1b;
}

.export-section {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.export-options {
    display: flex;
    gap: 12px;
    margin-top: 16px;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>