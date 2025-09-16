<?php
/**
 * WordPress Read-Only Mode Notice
 *
 * This file should be included in wp-config.php to show a warning
 * when the site is in read-only mode.
 *
 * Add to wp-config.php after the database settings:
 * if (file_exists(ABSPATH . '../config/wp-readonly-notice.php')) {
 *     require_once(ABSPATH . '../config/wp-readonly-notice.php');
 * }
 */

// Only run in admin area
if (is_admin()) {

    /**
     * Check if site is in read-only mode by checking directory permissions
     */
    function check_site_readonly_status() {
        // Check the uploads directory permissions
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'];

        if (file_exists($base_dir)) {
            $perms = substr(sprintf('%o', fileperms($base_dir)), -4);
            // 2550 or 0550 = read-only, 2750 or 0750 = writable
            if ($perms == '2550' || $perms == '0550') {
                return true; // Site is read-only
            }
        }

        return false;
    }

    /**
     * Display admin notice if site is in read-only mode
     */
    function display_readonly_admin_notice() {
        if (!check_site_readonly_status()) {
            return; // Site is writable, no notice needed
        }

        $current_screen = get_current_screen();

        // Show more urgent notice on pages where users typically make changes
        $urgent_screens = array(
            'upload', 'media', 'plugin-install', 'theme-install',
            'update-core', 'plugins', 'themes', 'post', 'page'
        );

        $is_urgent = in_array($current_screen->id, $urgent_screens) ||
                     in_array($current_screen->base, $urgent_screens);

        $notice_class = $is_urgent ? 'notice notice-error' : 'notice notice-warning';
        ?>
        <div class="<?php echo esc_attr($notice_class); ?> is-dismissible" style="background: #f8d7da; border-left-color: #dc3545;">
            <p style="font-size: 14px;">
                <strong style="color: #721c24;">⚠️ SITE IS IN READ-ONLY MODE</strong><br>
                <span style="color: #721c24;">
                    Updates, uploads, and plugin/theme installations will fail.<br>
                    To enable updates, SSH into the server and run: <code style="background: #f5c6cb; padding: 2px 4px;">site --writable</code><br>
                    Remember to run <code style="background: #f5c6cb; padding: 2px 4px;">site --readonly</code> when updates are complete.
                </span>
            </p>
        </div>
        <?php
    }

    /**
     * Display notice on media upload page
     */
    function display_readonly_media_notice() {
        if (!check_site_readonly_status()) {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Add notice to media library
            if ($('.media-frame-content').length) {
                $('.media-frame-content').prepend(
                    '<div class="notice notice-error" style="margin: 10px; background: #f8d7da; border-left: 4px solid #dc3545; padding: 12px;">' +
                    '<p><strong>⚠️ Uploads Disabled - Site is Read-Only</strong><br>' +
                    'File uploads will fail. Enable write mode first: <code>site --writable</code></p>' +
                    '</div>'
                );
            }
        });
        </script>
        <?php
    }

    /**
     * Disable certain capabilities when in read-only mode
     */
    function disable_capabilities_readonly($allcaps, $caps, $args) {
        if (!check_site_readonly_status()) {
            return $allcaps;
        }

        // Capabilities to disable in read-only mode
        $disabled_caps = array(
            'upload_files',
            'edit_themes',
            'install_themes',
            'update_themes',
            'delete_themes',
            'install_plugins',
            'update_plugins',
            'delete_plugins',
            'update_core'
        );

        foreach ($disabled_caps as $cap) {
            if (isset($allcaps[$cap])) {
                // Don't actually remove the capability, but we could if needed
                // $allcaps[$cap] = false;
            }
        }

        return $allcaps;
    }

    // Add hooks
    add_action('admin_notices', 'display_readonly_admin_notice');
    add_action('admin_footer', 'display_readonly_media_notice');
    // Optionally disable capabilities (commented out to not break UI)
    // add_filter('user_has_cap', 'disable_capabilities_readonly', 10, 3);

    /**
     * Add CSS to make the notice more prominent
     */
    function add_readonly_admin_styles() {
        if (!check_site_readonly_status()) {
            return;
        }
        ?>
        <style>
            /* Make the admin bar red when in read-only mode */
            #wpadminbar {
                background: #dc3545 !important;
            }

            /* Add a persistent banner */
            #wpbody-content::before {
                content: "🔒 READ-ONLY MODE - Updates Disabled";
                display: block;
                background: #dc3545;
                color: white;
                padding: 10px;
                text-align: center;
                font-weight: bold;
                margin: -20px -20px 20px -20px;
                position: sticky;
                top: 32px;
                z-index: 1000;
            }

            /* Style the update buttons to show they're disabled */
            .button.button-primary[value="Update"],
            .button.button-primary[name="save"],
            .button.button-primary[name="publish"],
            #publish {
                opacity: 0.7;
                position: relative;
            }

            .button.button-primary[value="Update"]::after,
            .button.button-primary[name="save"]::after,
            .button.button-primary[name="publish"]::after,
            #publish::after {
                content: " (Read-Only)";
                color: #ffcccc;
            }
        </style>
        <?php
    }

    add_action('admin_head', 'add_readonly_admin_styles');
}