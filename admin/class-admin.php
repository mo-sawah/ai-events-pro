<?php
/**
 * Admin bootstrap for AI Events Pro
 *
 * - Top-level menu + submenus: Dashboard, Add Event, Event List, Shortcodes, Settings
 * - Enqueues modern admin styles and page-specific scripts
 * - AJAX for: sync events, API tests, clear cache, debug log, shortcode presets, shortcode preview
 */

if (!class_exists('AI_Events_Admin')) {
class AI_Events_Admin {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name; // expected: 'ai-events-pro'
        $this->version     = $version;

        // AJAX handlers (kept from your current version and expanded)
        add_action('wp_ajax_sync_events', array($this, 'ajax_sync_events'));
        add_action('wp_ajax_test_api_connection', array($this, 'ajax_test_api_connection'));
        add_action('wp_ajax_clear_events_cache', array($this, 'ajax_clear_events_cache'));
        add_action('wp_ajax_get_debug_log', array($this, 'ajax_get_debug_log'));
        add_action('wp_ajax_clear_debug_log', array($this, 'ajax_clear_debug_log'));

        // Shortcodes manager AJAX
        add_action('wp_ajax_save_shortcode_preset', array($this, 'ajax_save_shortcode_preset'));
        add_action('wp_ajax_delete_shortcode_preset', array($this, 'ajax_delete_shortcode_preset'));
        add_action('wp_ajax_preview_shortcode', array($this, 'ajax_preview_shortcode'));

        // Register all settings (General, APIs, AI, and Appearance) on the same Settings page
        add_action('admin_init', array($this, 'init_settings'));

        // Enqueue admin assets (color picker, etc.)
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function enqueue_admin_assets($hook) {
        // Load WordPress color picker on your plugin pages
        if (strpos($hook, 'ai-events') !== false || strpos($hook, 'ai_events') !== false) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
        }
    }

    /**
     * Settings registration (existing + Appearance on the same 'ai_events_pro_general' page)
     * Hooked via admin_init.
     */
    public function init_settings() {
        // Option groups
        register_setting('ai_events_pro_general',      'ai_events_pro_settings',              array($this, 'validate_general_settings'));
        register_setting('ai_events_pro_eventbrite',   'ai_events_pro_eventbrite_settings',   array($this, 'validate_eventbrite_settings'));
        register_setting('ai_events_pro_ticketmaster', 'ai_events_pro_ticketmaster_settings', array($this, 'validate_ticketmaster_settings'));
        register_setting('ai_events_pro_ai',           'ai_events_pro_ai_settings',           array($this, 'validate_ai_settings'));

        $this->add_settings_sections();
        $this->add_settings_fields();
    }

    private function add_settings_sections() {
        // General page sections
        add_settings_section(
            'ai_events_pro_general_section',
            __('General Settings', 'ai-events-pro'),
            array($this, 'general_section_callback'),
            'ai_events_pro_general'
        );

        // Appearance section on the same General page so it actually renders on your current Settings view
        add_settings_section(
            'ai_events_pro_appearance',
            __('Appearance', 'ai-events-pro'),
            '__return_false',
            'ai_events_pro_general'
        );

        // Provider sections (other pages)
        add_settings_section(
            'ai_events_pro_eventbrite_section',
            __('Eventbrite API Configuration', 'ai-events-pro'),
            array($this, 'eventbrite_section_callback'),
            'ai_events_pro_eventbrite'
        );

        add_settings_section(
            'ai_events_pro_ticketmaster_section',
            __('Ticketmaster API Configuration', 'ai-events-pro'),
            array($this, 'ticketmaster_section_callback'),
            'ai_events_pro_ticketmaster'
        );

        add_settings_section(
            'ai_events_pro_ai_section',
            __('AI Features Configuration', 'ai-events-pro'),
            array($this, 'ai_section_callback'),
            'ai_events_pro_ai'
        );
    }

    private function add_settings_fields() {
        $general_settings      = get_option('ai_events_pro_settings', array());
        $eventbrite_settings   = get_option('ai_events_pro_eventbrite_settings', array());
        $ticketmaster_settings = get_option('ai_events_pro_ticketmaster_settings', array());
        $ai_settings           = get_option('ai_events_pro_ai_settings', array());

        // General
        add_settings_field('events_per_page', __('Events Per Page', 'ai-events-pro'),
            array($this, 'number_field_callback'), 'ai_events_pro_general', 'ai_events_pro_general_section',
            array('option' => 'ai_events_pro_settings', 'field' => 'events_per_page', 'value' => $general_settings['events_per_page'] ?? 12, 'min' => 1, 'max' => 50));

        add_settings_field('default_radius', __('Default Search Radius (miles)', 'ai-events-pro'),
            array($this, 'number_field_callback'), 'ai_events_pro_general', 'ai_events_pro_general_section',
            array('option' => 'ai_events_pro_settings', 'field' => 'default_radius', 'value' => $general_settings['default_radius'] ?? 25, 'min' => 1, 'max' => 500));

        add_settings_field('cache_duration', __('Cache Duration (hours)', 'ai-events-pro'),
            array($this, 'number_field_callback'), 'ai_events_pro_general', 'ai_events_pro_general_section',
            array('option' => 'ai_events_pro_settings', 'field' => 'cache_duration', 'value' => max(1, intval(($general_settings['cache_duration'] ?? 3600) / 3600)), 'min' => 1, 'max' => 24));

        add_settings_field('enable_geolocation', __('Enable Auto-Location Detection', 'ai-events-pro'),
            array($this, 'checkbox_field_callback'), 'ai_events_pro_general', 'ai_events_pro_general_section',
            array('option' => 'ai_events_pro_settings', 'field' => 'enable_geolocation', 'value' => !empty($general_settings['enable_geolocation'])));

        add_settings_field('enabled_apis', __('Enabled Event Sources', 'ai-events-pro'),
            array($this, 'api_selection_callback'), 'ai_events_pro_general', 'ai_events_pro_general_section',
            array('option' => 'ai_events_pro_settings', 'value' => $general_settings));

        // Appearance (on General page)
        add_settings_field(
            'default_theme_mode',
            __('Default Theme Mode', 'ai-events-pro'),
            function () use ($general_settings) {
                $val = isset($general_settings['default_theme_mode']) ? $general_settings['default_theme_mode'] : 'auto';
                ?>
                <select name="ai_events_pro_settings[default_theme_mode]">
                    <option value="auto"  <?php selected($val, 'auto');  ?>><?php _e('Auto (Follow system)', 'ai-events-pro'); ?></option>
                    <option value="light" <?php selected($val, 'light'); ?>><?php _e('Light', 'ai-events-pro'); ?></option>
                    <option value="dark"  <?php selected($val, 'dark');  ?>><?php _e('Dark', 'ai-events-pro'); ?></option>
                </select>
                <?php
            },
            'ai_events_pro_general',
            'ai_events_pro_appearance'
        );

        add_settings_field(
            'colors_light',
            __('Colors (Light Mode)', 'ai-events-pro'),
            array($this, 'render_colors_light'),
            'ai_events_pro_general',
            'ai_events_pro_appearance'
        );

        add_settings_field(
            'colors_dark',
            __('Colors (Dark Mode)', 'ai-events-pro'),
            array($this, 'render_colors_dark'),
            'ai_events_pro_general',
            'ai_events_pro_appearance'
        );

        // Eventbrite
        add_settings_field('eventbrite_private_token', __('Private Token', 'ai-events-pro'),
            array($this, 'password_field_callback'), 'ai_events_pro_eventbrite', 'ai_events_pro_eventbrite_section',
            array(
                'option'      => 'ai_events_pro_eventbrite_settings',
                'field'       => 'private_token',
                'value'       => $eventbrite_settings['private_token'] ?? '',
                'description' => __('Your Eventbrite Private Token (Personal OAuth token)', 'ai-events-pro'),
                'api_type'    => 'eventbrite'
            ));

        // Ticketmaster
        add_settings_field('ticketmaster_consumer_key', __('Consumer Key (API Key)', 'ai-events-pro'),
            array($this, 'password_field_callback'), 'ai_events_pro_ticketmaster', 'ai_events_pro_ticketmaster_section',
            array(
                'option'      => 'ai_events_pro_ticketmaster_settings',
                'field'       => 'consumer_key',
                'value'       => $ticketmaster_settings['consumer_key'] ?? '',
                'description' => __('Your Ticketmaster Consumer Key from Developer Portal', 'ai-events-pro'),
                'api_type'    => 'ticketmaster'
            ));

        // AI
        add_settings_field('enable_ai_features', __('Enable AI Features', 'ai-events-pro'),
            array($this, 'checkbox_field_callback'), 'ai_events_pro_ai', 'ai_events_pro_ai_section',
            array('option' => 'ai_events_pro_ai_settings', 'field' => 'enable_ai_features', 'value' => !empty($ai_settings['enable_ai_features'])));

        add_settings_field('openrouter_api_key', __('OpenRouter API Key', 'ai-events-pro'),
            array($this, 'password_field_callback'), 'ai_events_pro_ai', 'ai_events_pro_ai_section',
            array(
                'option'      => 'ai_events_pro_ai_settings',
                'field'       => 'openrouter_api_key',
                'value'       => $ai_settings['openrouter_api_key'] ?? '',
                'description' => __('Required for AI features like categorization and summaries', 'ai-events-pro'),
                'api_type'    => 'openrouter'
            ));

        add_settings_field('ai_categorization', __('AI Event Categorization', 'ai-events-pro'),
            array($this, 'checkbox_field_callback'), 'ai_events_pro_ai', 'ai_events_pro_ai_section',
            array('option' => 'ai_events_pro_ai_settings', 'field' => 'ai_categorization', 'value' => !empty($ai_settings['ai_categorization'])));

        add_settings_field('ai_summaries', __('AI Event Summaries', 'ai-events-pro'),
            array($this, 'checkbox_field_callback'), 'ai_events_pro_ai', 'ai_events_pro_ai_section',
            array('option' => 'ai_events_pro_ai_settings', 'field' => 'ai_summaries', 'value' => !empty($ai_settings['ai_summaries'])));
    }

    // Helpers for color fields
    private function color_input($name, $label, $value, $fallback) {
        $val = $value ?: $fallback;
        ?>
        <label style="display:inline-flex;align-items:center;gap:.5rem;margin:.25rem .75rem .25rem 0;">
            <span style="min-width:140px;display:inline-block;"><?php echo esc_html($label); ?></span>
            <input type="text" class="ai-ep-color-field" data-default-color="<?php echo esc_attr($fallback); ?>"
                   name="ai_events_pro_settings[<?php echo esc_attr($name); ?>]" value="<?php echo esc_attr($val); ?>"/>
        </label>
        <?php
    }

    public function render_colors_light() {
        $s = get_option('ai_events_pro_settings', array());
        echo '<div>';
        $this->color_input('colors_light[primary]',       __('Primary', 'ai-events-pro'),       $s['colors_light']['primary']       ?? '', '#2563eb');
        $this->color_input('colors_light[primary_600]',   __('Primary 600', 'ai-events-pro'),   $s['colors_light']['primary_600']   ?? '', '#1d4ed8');
        $this->color_input('colors_light[text]',          __('Text', 'ai-events-pro'),          $s['colors_light']['text']          ?? '', '#0f172a');
        $this->color_input('colors_light[text_soft]',     __('Text (Soft)', 'ai-events-pro'),   $s['colors_light']['text_soft']     ?? '', '#475569');
        $this->color_input('colors_light[bg]',            __('Background', 'ai-events-pro'),    $s['colors_light']['bg']            ?? '', '#f5f7fb');
        $this->color_input('colors_light[surface]',       __('Surface', 'ai-events-pro'),       $s['colors_light']['surface']       ?? '', '#ffffff');
        $this->color_input('colors_light[surface_alt]',   __('Surface Alt', 'ai-events-pro'),   $s['colors_light']['surface_alt']   ?? '', '#f8fafc');
        $this->color_input('colors_light[border]',        __('Border', 'ai-events-pro'),        $s['colors_light']['border']        ?? '', '#e5e7eb');
        echo '</div>';
        ?>
        <script>
          jQuery(function($){ $('.ai-ep-color-field').wpColorPicker(); });
        </script>
        <?php
    }

    public function render_colors_dark() {
        $s = get_option('ai_events_pro_settings', array());
        echo '<div>';
        $this->color_input('colors_dark[primary]',       __('Primary', 'ai-events-pro'),       $s['colors_dark']['primary']       ?? '', '#60a5fa');
        $this->color_input('colors_dark[primary_600]',   __('Primary 600', 'ai-events-pro'),   $s['colors_dark']['primary_600']   ?? '', '#3b82f6');
        $this->color_input('colors_dark[text]',          __('Text', 'ai-events-pro'),          $s['colors_dark']['text']          ?? '', '#e5e7eb');
        $this->color_input('colors_dark[text_soft]',     __('Text (Soft)', 'ai-events-pro'),   $s['colors_dark']['text_soft']     ?? '', '#cbd5e1');
        $this->color_input('colors_dark[bg]',            __('Background', 'ai-events-pro'),    $s['colors_dark']['bg']            ?? '', '#0f172a');
        $this->color_input('colors_dark[surface]',       __('Surface', 'ai-events-pro'),       $s['colors_dark']['surface']       ?? '', '#111827');
        $this->color_input('colors_dark[surface_alt]',   __('Surface Alt', 'ai-events-pro'),   $s['colors_dark']['surface_alt']   ?? '', '#0b1220');
        $this->color_input('colors_dark[border]',        __('Border', 'ai-events-pro'),        $s['colors_dark']['border']        ?? '', '#273244');
        echo '</div>';
        ?>
        <script>
          jQuery(function($){ $('.ai-ep-color-field').wpColorPicker(); });
        </script>
        <?php
    }

    /**
     * Hook this via admin_menu in your loader.
     */
    public function add_plugin_admin_menu() {
        // Top-level (Dashboard)
        add_menu_page(
            __('AI Events Pro', 'ai-events-pro'),
            __('AI Events Pro', 'ai-events-pro'),
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_admin_page'),
            'dashicons-calendar-alt',
            26
        );

        // Dashboard anchor submenu
        add_submenu_page(
            $this->plugin_name,
            __('Dashboard', 'ai-events-pro'),
            __('Dashboard', 'ai-events-pro'),
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_admin_page')
        );

        // Add Event (redirect)
        add_submenu_page(
            $this->plugin_name,
            __('Add Event', 'ai-events-pro'),
            __('Add Event', 'ai-events-pro'),
            'edit_posts',
            $this->plugin_name . '-add-event',
            array($this, 'redirect_add_event')
        );

        // Event List (redirect)
        add_submenu_page(
            $this->plugin_name,
            __('Event List', 'ai-events-pro'),
            __('Event List', 'ai-events-pro'),
            'edit_posts',
            $this->plugin_name . '-event-list',
            array($this, 'redirect_event_list')
        );

        // Shortcodes controller
        add_submenu_page(
            $this->plugin_name,
            __('Shortcodes', 'ai-events-pro'),
            __('Shortcodes', 'ai-events-pro'),
            'manage_options',
            $this->plugin_name . '-shortcodes',
            array($this, 'display_plugin_shortcodes_page')
        );

        // Settings
        add_submenu_page(
            $this->plugin_name,
            __('Settings', 'ai-events-pro'),
            __('Settings', 'ai-events-pro'),
            'manage_options',
            $this->plugin_name . '-settings',
            array($this, 'display_plugin_settings_page')
        );
    }

    /**
     * Hook this via admin_enqueue_scripts in your loader.
     * We keep your existing admin js and add conditional shortcodes js.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name . '-admin',
            AI_EVENTS_PRO_PLUGIN_URL . 'admin/css/ai-events-admin.css',
            array(),
            $this->version
        );
    }

    public function enqueue_scripts() {
        // Main admin JS (existing)
        wp_enqueue_script(
            $this->plugin_name . '-admin',
            AI_EVENTS_PRO_PLUGIN_URL . 'admin/js/ai-events-admin.js',
            array('jquery'),
            $this->version,
            true
        );

        wp_localize_script($this->plugin_name . '-admin', 'ai_events_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('ai_events_admin_nonce'),
            'strings'  => array(
                'testing_api'    => __('Testing API...', 'ai-events-pro'),
                'api_success'    => __('Connection successful!', 'ai-events-pro'),
                'api_error'      => __('Connection failed!', 'ai-events-pro'),
                'syncing_events' => __('Syncing events...', 'ai-events-pro'),
                'sync_success'   => __('Events synced successfully!', 'ai-events-pro'),
            )
        ));

        // Conditionally enqueue shortcodes manager JS
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && strpos($screen->id, $this->plugin_name . '-shortcodes') !== false) {
            wp_enqueue_script(
                $this->plugin_name . '-shortcodes',
                AI_EVENTS_PRO_PLUGIN_URL . 'admin/js/shortcodes.js',
                array('jquery'),
                $this->version,
                true
            );
            // shortcodes.js expects same localized object name
            wp_localize_script($this->plugin_name . '-shortcodes', 'ai_events_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('ai_events_admin_nonce'),
                'i18n'     => array(
                    'copied'   => __('Copied!', 'ai-events-pro'),
                    'copy'     => __('Copy', 'ai-events-pro'),
                    'saving'   => __('Saving...', 'ai-events-pro'),
                    'deleting' => __('Deleting...', 'ai-events-pro'),
                    'preview'  => __('Preview', 'ai-events-pro'),
                )
            ));
        }
    }

    // Views
    public function display_plugin_admin_page() {
        include AI_EVENTS_PRO_PLUGIN_DIR . 'admin/views/admin-display.php';
    }

    public function display_plugin_settings_page() {
        include AI_EVENTS_PRO_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    public function display_plugin_shortcodes_page() {
        // This view provides the generator/presets/preview UI
        include AI_EVENTS_PRO_PLUGIN_DIR . 'admin/views/shortcodes-page.php';
    }

    // Redirect submenus
    public function redirect_add_event() {
        wp_safe_redirect(admin_url('post-new.php?post_type=ai_event'));
        exit;
    }
    public function redirect_event_list() {
        wp_safe_redirect(admin_url('edit.php?post_type=ai_event'));
        exit;
    }

    // Settings callbacks
    public function general_section_callback() {
        echo '<p>' . esc_html__('Configure general plugin settings and choose which event sources to enable.', 'ai-events-pro') . '</p>';
    }
    public function eventbrite_section_callback() {
        echo '<div class="api-info"><p>' . esc_html__('To get your Eventbrite Private Token:', 'ai-events-pro') . '</p><ol>';
        echo '<li>' . wp_kses_post(__('Go to <a href="https://www.eventbrite.com/platform/api#/introduction/authentication" target="_blank">Eventbrite API</a>', 'ai-events-pro')) . '</li>';
        echo '<li>' . esc_html__('Click "Create Private Token"', 'ai-events-pro') . '</li>';
        echo '<li>' . esc_html__('Copy the generated token and paste it below', 'ai-events-pro') . '</li>';
        echo '</ol></div>';
    }
    public function ticketmaster_section_callback() {
        echo '<div class="api-info"><p>' . esc_html__('To get your Ticketmaster Consumer Key:', 'ai-events-pro') . '</p><ol>';
        echo '<li>' . wp_kses_post(__('Go to <a href="https://developer.ticketmaster.com/products-and-docs/apis/getting-started/" target="_blank">Ticketmaster Developer Portal</a>', 'ai-events-pro')) . '</li>';
        echo '<li>' . esc_html__('Create a new app or use existing one', 'ai-events-pro') . '</li>';
        echo '<li>' . esc_html__('Copy the Consumer Key and paste it below', 'ai-events-pro') . '</li>';
        echo '</ol></div>';
    }
    public function ai_section_callback() {
        echo '<p>' . esc_html__('Configure AI-powered features to enhance your events with smart categorization and summaries.', 'ai-events-pro') . '</p>';
    }

    // Field renderers
    public function number_field_callback($args) {
        $option = $args['option'];
        $field  = $args['field'];
        $value  = $args['value'];
        $min    = isset($args['min']) ? intval($args['min']) : '';
        $max    = isset($args['max']) ? intval($args['max']) : '';
        printf(
            '<input type="number" id="%1$s_%2$s" name="%1$s[%2$s]" value="%3$s" min="%4$s" max="%5$s" class="regular-text" />',
            esc_attr($option), esc_attr($field), esc_attr($value), esc_attr($min), esc_attr($max)
        );
    }

    public function checkbox_field_callback($args) {
        $option = $args['option'];
        $field  = $args['field'];
        $value  = !empty($args['value']);
        printf(
            '<input type="checkbox" id="%1$s_%2$s" name="%1$s[%2$s]" value="1" %3$s />',
            esc_attr($option), esc_attr($field), checked(true, $value, false)
        );
    }

    public function password_field_callback($args) {
        $option      = $args['option'];
        $field       = $args['field'];
        $value       = $args['value'];
        $description = $args['description'] ?? '';
        $api_type    = $args['api_type'] ?? '';

        printf(
            '<input type="password" id="%1$s_%2$s" name="%1$s[%2$s]" value="%3$s" class="regular-text" />
             <button type="button" class="button test-api-btn" data-api="%4$s" data-option="%1$s" data-field="%2$s">%5$s</button>',
            esc_attr($option), esc_attr($field), esc_attr($value), esc_attr($api_type), esc_html__('Test Connection', 'ai-events-pro')
        );

        if (!empty($description)) {
            echo '<p class="description">' . wp_kses_post($description) . '</p>';
        }
    }

    public function api_selection_callback($args) {
        $option   = $args['option'];
        $settings = $args['value'];
        $apis = array(
            'eventbrite'   => __('Eventbrite', 'ai-events-pro'),
            'ticketmaster' => __('Ticketmaster', 'ai-events-pro'),
            'custom'       => __('Custom Events', 'ai-events-pro')
        );

        echo '<fieldset>';
        foreach ($apis as $key => $label) {
            $checked = isset($settings['enabled_apis'][$key]) ? (bool) $settings['enabled_apis'][$key] : ($key === 'custom');
            printf(
                '<label><input type="checkbox" name="%1$s[enabled_apis][%2$s]" value="1" %3$s /> %4$s</label><br>',
                esc_attr($option), esc_attr($key), checked(true, $checked, false), esc_html($label)
            );
        }
        echo '</fieldset><p class="description">' . esc_html__('Select which event sources to use for importing events.', 'ai-events-pro') . '</p>';
    }

    // Validation
    public function validate_general_settings($input) {
        $out = array();
        $out['events_per_page']      = max(1, min(50, absint($input['events_per_page'] ?? 12)));
        $out['default_radius']       = max(1, min(500, absint($input['default_radius'] ?? 25)));
        $out['cache_duration']       = max(1, absint($input['cache_duration'] ?? 1)) * 3600;
        $out['enable_geolocation']   = !empty($input['enable_geolocation']);
        $out['default_theme_mode']   = in_array(($input['default_theme_mode'] ?? 'auto'), array('auto','light','dark'), true) ? $input['default_theme_mode'] : 'auto';
        $out['colors_light']         = is_array($input['colors_light'] ?? null) ? array_map('sanitize_text_field', $input['colors_light']) : array();
        $out['colors_dark']          = is_array($input['colors_dark'] ?? null) ? array_map('sanitize_text_field', $input['colors_dark']) : array();
        $out['enabled_apis']         = array();
        if (!empty($input['enabled_apis']) && is_array($input['enabled_apis'])) {
            foreach ($input['enabled_apis'] as $api => $enabled) {
                $out['enabled_apis'][$api] = !empty($enabled);
            }
        } else {
            $out['enabled_apis']['custom'] = true;
        }
        return $out;
    }

    public function validate_eventbrite_settings($input) {
        return array('private_token' => sanitize_text_field($input['private_token'] ?? ''));
    }
    public function validate_ticketmaster_settings($input) {
        return array('consumer_key' => sanitize_text_field($input['consumer_key'] ?? ''));
    }
    public function validate_ai_settings($input) {
        return array(
            'enable_ai_features' => !empty($input['enable_ai_features']),
            'openrouter_api_key' => sanitize_text_field($input['openrouter_api_key'] ?? ''),
            'ai_categorization'  => !empty($input['ai_categorization']),
            'ai_summaries'       => !empty($input['ai_summaries']),
        );
    }

    // AJAX: API tests
    public function ajax_test_api_connection() {
        check_ajax_referer('ai_events_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(__('Insufficient permissions.', 'ai-events-pro'));

        $api_type    = sanitize_text_field($_POST['api_type'] ?? '');
        $api_key_raw = sanitize_text_field($_POST['api_key'] ?? '');

        if (empty($api_key_raw)) {
            wp_send_json_error(__('Please enter an API key.', 'ai-events-pro'));
        }

        switch ($api_type) {
            case 'eventbrite':
                $res = $this->test_eventbrite_connection($api_key_raw);
                break;
            case 'ticketmaster':
                $res = $this->test_ticketmaster_connection($api_key_raw);
                break;
            case 'openrouter':
                $res = $this->test_openrouter_connection($api_key_raw);
                break;
            default:
                wp_send_json_error(__('Unknown API type.', 'ai-events-pro'));
        }

        $res['success'] ? wp_send_json_success($res['message']) : wp_send_json_error($res['message']);
    }

    private function test_eventbrite_connection($token) {
        $resp = wp_remote_get('https://www.eventbriteapi.com/v3/users/me/', array(
            'headers' => array('Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'),
            'timeout' => 15
        ));
        if (is_wp_error($resp)) return array('success' => false, 'message' => $resp->get_error_message());
        if (wp_remote_retrieve_response_code($resp) === 200) {
            $data = json_decode(wp_remote_retrieve_body($resp), true);
            $name = $data['name'] ?? 'OK';
            return array('success' => true, 'message' => sprintf(__('Eventbrite connected as %s', 'ai-events-pro'), $name));
        }
        return array('success' => false, 'message' => __('Eventbrite connection failed. Check your token.', 'ai-events-pro'));
    }

    private function test_ticketmaster_connection($key) {
        $resp = wp_remote_get('https://app.ticketmaster.com/discovery/v2/events.json?apikey=' . rawurlencode($key) . '&size=1', array('timeout' => 15));
        if (is_wp_error($resp)) return array('success' => false, 'message' => $resp->get_error_message());
        if (wp_remote_retrieve_response_code($resp) === 200) return array('success' => true, 'message' => __('Ticketmaster connected successfully!', 'ai-events-pro'));
        return array('success' => false, 'message' => __('Ticketmaster connection failed. Check your consumer key.', 'ai-events-pro'));
    }

    private function test_openrouter_connection($key) {
        $resp = wp_remote_get('https://openrouter.ai/api/v1/models', array(
            'headers' => array('Authorization' => 'Bearer ' . $key),
            'timeout' => 15
        ));
        if (is_wp_error($resp)) return array('success' => false, 'message' => $resp->get_error_message());
        if (wp_remote_retrieve_response_code($resp) === 200) return array('success' => true, 'message' => __('OpenRouter connected successfully!', 'ai-events-pro'));
        return array('success' => false, 'message' => __('OpenRouter connection failed. Check your API key.', 'ai-events-pro'));
    }

    // AJAX: Sync/Caching/Debug log
    public function ajax_sync_events() {
        check_ajax_referer('ai_events_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(__('Insufficient permissions.', 'ai-events-pro'));

        $location = sanitize_text_field($_POST['location'] ?? '');
        $radius   = absint($_POST['radius'] ?? 25);
        $limit    = absint($_POST['limit'] ?? 50);

        if ($location === '') wp_send_json_error(__('Please enter a location.', 'ai-events-pro'));

        try {
            $api = new AI_Events_API_Manager();
            $events = $api->get_events($location, $radius, $limit);

            if (!empty($events)) {
                $api->cache_events($events, $location);
                wp_send_json_success(array(
                    'message'        => sprintf(__('Successfully synced %d events from enabled sources.', 'ai-events-pro'), count($events)),
                    'events_count'   => count($events),
                    'events_preview' => array_slice($events, 0, 3),
                    'sources_used'   => $this->get_sources_from_events($events),
                    'debug_info'     => $this->get_sync_debug_info(),
                ));
            } else {
                $debug_log = $api->get_debug_log();
                wp_send_json_error(array(
                    'message'    => __('No events found. Check debug info below.', 'ai-events-pro'),
                    'debug_info' => $this->get_sync_debug_info(),
                    'debug_log'  => array_slice($debug_log, -10),
                    'suggestions'=> $this->get_troubleshooting_suggestions($this->get_sync_debug_info())
                ));
            }
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message'     => __('Error: ', 'ai-events-pro') . $e->getMessage(),
                'debug_info'  => $this->get_sync_debug_info(),
                'error_details' => array(
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                )
            ));
        }
    }

    public function ajax_clear_events_cache() {
        check_ajax_referer('ai_events_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(__('Insufficient permissions.', 'ai-events-pro'));

        global $wpdb;
        $table = $wpdb->prefix . 'ai_events_cache';
        $deleted = $wpdb->query("DELETE FROM $table");
        if ($deleted !== false) {
            wp_send_json_success(sprintf(__('Cache cleared. %d entries removed.', 'ai-events-pro'), intval($deleted)));
        }
        wp_send_json_error(__('Failed to clear cache.', 'ai-events-pro'));
    }

    public function ajax_get_debug_log() {
        check_ajax_referer('ai_events_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(__('Insufficient permissions.', 'ai-events-pro'));
        $api = new AI_Events_API_Manager();
        $log = $api->get_debug_log();
        wp_send_json_success(array('debug_log' => $log, 'total_entries' => count($log)));
    }

    public function ajax_clear_debug_log() {
        check_ajax_referer('ai_events_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(__('Insufficient permissions.', 'ai-events-pro'));
        $api = new AI_Events_API_Manager();
        $api->clear_debug_log();
        wp_send_json_success(__('Debug log cleared.', 'ai-events-pro'));
    }

    private function get_sync_debug_info() {
        $general  = get_option('ai_events_pro_settings', array());
        $eb       = get_option('ai_events_pro_eventbrite_settings', array());
        $tm       = get_option('ai_events_pro_ticketmaster_settings', array());
        $ai       = get_option('ai_events_pro_ai_settings', array());

        return array(
            'enabled_apis' => $general['enabled_apis'] ?? array(),
            'api_status'   => array(
                'eventbrite' => array(
                    'enabled'    => !empty($general['enabled_apis']['eventbrite']),
                    'configured' => !empty($eb['private_token']),
                    'token_length' => !empty($eb['private_token']) ? strlen($eb['private_token']) : 0
                ),
                'ticketmaster' => array(
                    'enabled'    => !empty($general['enabled_apis']['ticketmaster']),
                    'configured' => !empty($tm['consumer_key']),
                    'key_length' => !empty($tm['consumer_key']) ? strlen($tm['consumer_key']) : 0
                ),
                'custom' => array(
                    'enabled'      => !empty($general['enabled_apis']['custom']),
                    'events_count' => (int) (wp_count_posts('ai_event')->publish ?? 0)
                ),
                'ai' => array(
                    'enabled'    => !empty($ai['enable_ai_features']),
                    'configured' => !empty($ai['openrouter_api_key'])
                )
            )
        );
    }

    private function get_sources_from_events($events) {
        $sources = array();
        foreach ($events as $e) {
            $s = $e['source'] ?? 'unknown';
            $sources[$s] = isset($sources[$s]) ? $sources[$s] + 1 : 1;
        }
        return $sources;
    }

    private function get_troubleshooting_suggestions($info) {
        $s = array();
        $enabled = $info['enabled_apis'] ?? array();
        if (!array_filter($enabled)) {
            $s[] = "❌ No event sources are enabled. Enable at least one (Eventbrite, Ticketmaster, or Custom).";
        }
        if (!empty($enabled['eventbrite'])) {
            $eb = $info['api_status']['eventbrite'];
            if (!$eb['configured'])        $s[] = "❌ Eventbrite enabled but no Private Token configured.";
            elseif ($eb['token_length']<20)$s[] = "⚠️ Eventbrite token looks short; re-check.";
        }
        if (!empty($enabled['ticketmaster'])) {
            $tm = $info['api_status']['ticketmaster'];
            if (!$tm['configured'])        $s[] = "❌ Ticketmaster enabled but no Consumer Key configured.";
            elseif ($tm['key_length']<10)  $s[] = "⚠️ Ticketmaster key looks short; re-check.";
        }
        if (!empty($enabled['custom'])) {
            $cnt = $info['api_status']['custom']['events_count'];
            if ($cnt === 0) $s[] = "ℹ️ Custom events enabled but none found. Add an event or broaden the location.";
        }
        if (empty($s)) {
            $s[] = "Try testing each API via the Test buttons in Settings.";
            $s[] = "Try a broader location (e.g., 'New York, NY') and increase the radius.";
        }
        return $s;
    }

    // Shortcodes presets + preview
    public function ajax_save_shortcode_preset() {
        check_ajax_referer('ai_events_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Unauthorized'), 403);

        $name = sanitize_text_field($_POST['name'] ?? '');
        $code = wp_kses_post($_POST['shortcode'] ?? '');
        if (!$name || !$code) wp_send_json_error(array('message' => __('Missing name or shortcode', 'ai-events-pro')));

        $presets = get_option('ai_events_pro_shortcode_presets', array());
        $id = 'preset_' . wp_generate_password(8, false, false);
        $presets[$id] = array(
            'id'         => $id,
            'name'       => $name,
            'shortcode'  => $code,
            'created_at' => current_time('mysql')
        );
        update_option('ai_events_pro_shortcode_presets', $presets);
        wp_send_json_success(array('preset' => $presets[$id]));
    }

    public function ajax_delete_shortcode_preset() {
        check_ajax_referer('ai_events_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Unauthorized'), 403);

        $id = sanitize_text_field($_POST['id'] ?? '');
        $presets = get_option('ai_events_pro_shortcode_presets', array());
        if (isset($presets[$id])) {
            unset($presets[$id]);
            update_option('ai_events_pro_shortcode_presets', $presets);
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => __('Preset not found', 'ai-events-pro')));
        }
    }

    public function ajax_preview_shortcode() {
        check_ajax_referer('ai_events_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Unauthorized'), 403);

        $shortcode = wp_kses_post($_POST['shortcode'] ?? '');
        if (!$shortcode) wp_send_json_error(array('message' => __('No shortcode provided', 'ai-events-pro')));

        $html = do_shortcode($shortcode);
        wp_send_json_success(array('html' => $html));
    }
}
}