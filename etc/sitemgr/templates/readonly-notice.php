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
    <div id="readonly-banner" style="position: fixed; top: 32px; left: 0; right: 0; z-index: 99999; background: linear-gradient(90deg, #dc3545 0%, #c82333 100%); color: white; padding: 10px 20px; text-align: center; font-weight: 600; font-size: 13px; line-height: 1.4; box-shadow: 0 3px 6px rgba(0,0,0,0.3); border-bottom: 2px solid #a01e2a;">
        🔒 <strong>READ-ONLY MODE</strong> |
        To update WP/plugins/themes: ask <strong><a href="mailto:it@torva.ee" style="color: #fff; text-decoration: underline;">it@torva.ee</a></strong> to add your SSH public key.
        Then in Terminal, run <code style="background: rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 3px;">ssh user@domain</code> and
        <code style="background: rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 3px;">site -w</code> to make the site writable.
        When done, run <code style="background: rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 3px;">site -r</code> to re-secure it from hacking.
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
    });
    </script>
    <?php
});