<?php
/**
 * Plugin Name: Read-Only Mode Notice
 * Description: Displays a warning when the site is in read-only mode
 * Version: 1.0
 *
 * This is a Must-Use plugin that automatically loads.
 * Place in: wp-content/mu-plugins/readonly-notice.php
 */

// Only run in admin area
if (!is_admin()) {
    return;
}

/**
 * Check if site is in read-only mode
 */
function sitemgr_check_readonly() {
    // Check wp-config.php permissions (this file is always read-only in read-only mode)
    $wp_config = ABSPATH . 'wp-config.php';
    if (file_exists($wp_config)) {
        $perms = substr(sprintf('%o', fileperms($wp_config)), -4);
        // In read-only mode, wp-config.php is 440
        return ($perms === '0440' || $perms === '440');
    }

    // Fallback: check a core WordPress file
    $index = ABSPATH . 'index.php';
    if (file_exists($index)) {
        $perms = substr(sprintf('%o', fileperms($index)), -4);
        return ($perms === '0440' || $perms === '440');
    }

    return false;
}

// Removed admin notice - all info now in sticky banner

/**
 * Add visual indicators
 */
add_action('admin_head', function() {
    if (!sitemgr_check_readonly()) {
        return;
    }
    ?>
    <style>
        /* Red admin bar */
        #wpadminbar {
            background: linear-gradient(90deg, #dc3545 0%, #c82333 100%) !important;
        }

        /* Remove ::before pseudo-element and use real HTML div */
    </style>
    <?php
    ?>
    <div id="readonly-banner" style="position: fixed; top: 32px; left: 0; right: 0; z-index: 99999; background: linear-gradient(90deg, #dc3545 0%, #c82333 100%); color: white; padding: 8px 15px; text-align: center; font-weight: 600; font-size: 13px; line-height: 1.2; box-shadow: 0 3px 6px rgba(0,0,0,0.3); border-bottom: 2px solid #a01e2a; cursor: help;">
        🔒 <strong style="font-weight: 800;">READ-ONLY MODE</strong> <span style="display: inline-block; width: 16px; height: 16px; border-radius: 50%; background: rgba(255,255,255,0.3); border: 1px solid white; line-height: 14px; font-size: 11px; font-weight: bold; vertical-align: middle; margin-left: 5px;">i</span>
        <div id="readonly-tooltip" style="display: none; position: absolute; top: 100%; left: 50%; transform: translateX(-50%); margin-top: 10px; background: white; color: #333; padding: 15px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); width: 450px; text-align: left; font-weight: normal; font-size: 12px; z-index: 100000;">
            <div style="position: absolute; top: -8px; left: 50%; transform: translateX(-50%); width: 0; height: 0; border-left: 8px solid transparent; border-right: 8px solid transparent; border-bottom: 8px solid white;"></div>
            <strong style="color: #dc3545;">To update WP/plugins/themes:</strong><br><br>
            1. Ask <a href="mailto:it@torva.ee" style="color: #0073aa; text-decoration: underline;">it@torva.ee</a> to add your SSH public key<br><br>
            2. In Terminal, run: <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">ssh user@domain</code><br><br>
            3. Make site writeable: <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">site -w</code><br><br>
            4. Update WordPress, plugins or themes<br><br>
            5. Re-secure the site: <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">site -r</code>
        </div>
    </div>
    <?php
    ?>
    <style>

        @media screen and (max-width: 782px) {
            #readonly-banner { top: 46px !important; }
        }

        /* Adjust content for banner */
        #wpbody { margin-top: 30px; }

        /* Mark update/save buttons */
        .button-primary:not(.readonly-checked) {
            position: relative;
        }

        .button-primary:not(.readonly-checked)::after {
            content: " 🔒";
        }
    </style>
    <script>
    jQuery(document).ready(function($) {
        // Add warnings to update buttons
        $('.button-primary').each(function() {
            var text = $(this).text().toLowerCase();
            if (text.includes('update') || text.includes('save') ||
                text.includes('publish') || text.includes('install')) {
                $(this).addClass('readonly-checked');
                $(this).attr('title', 'Site is in read-only mode - this action will fail');
            }
        });

        // Tooltip functionality
        var banner = $('#readonly-banner');
        var tooltip = $('#readonly-tooltip');
        var hideTimeout;

        banner.on('mouseenter', function() {
            clearTimeout(hideTimeout);
            tooltip.stop(true, true).fadeIn(200);
        });

        banner.on('mouseleave', function() {
            hideTimeout = setTimeout(function() {
                tooltip.stop(true, true).fadeOut(200);
            }, 300);
        });

        tooltip.on('mouseenter', function() {
            clearTimeout(hideTimeout);
        });

        tooltip.on('mouseleave', function() {
            tooltip.stop(true, true).fadeOut(200);
        });
    });
    </script>
    <?php
});