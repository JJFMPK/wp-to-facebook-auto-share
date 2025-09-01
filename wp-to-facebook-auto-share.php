<?php
/*
Plugin Name: WP Auto Share to Facebook
Plugin URI: https://yourwebsite.com
Description: Automatically shares WordPress posts (Title + Link + Featured Image if available) to your Facebook Page.
Version: 1.2
Author: JJFMPK
Author URI: https://yourwebsite.com
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Add settings menu
 */
add_action('admin_menu', 'wpas_add_menu');
function wpas_add_menu() {
    add_options_page(
        'WP Auto Share Settings',
        'WP Auto Share',
        'manage_options',
        'wp-auto-share',
        'wpas_settings_page'
    );
}

/**
 * Register settings
 */
add_action('admin_init', 'wpas_register_settings');
function wpas_register_settings() {
    register_setting('wpas_settings_group', 'wpas_fb_page_id', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('wpas_settings_group', 'wpas_fb_access_token', ['sanitize_callback' => 'sanitize_text_field']);
}

/**
 * Settings page HTML
 */
function wpas_settings_page() {
    ?>
    <div class="wrap">
        <h1>WP Auto Share Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('wpas_settings_group');
            do_settings_sections('wpas_settings_group');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Facebook Page ID</th>
                    <td>
                        <input type="text" name="wpas_fb_page_id" 
                               value="<?php echo esc_attr(get_option('wpas_fb_page_id')); ?>" 
                               style="width: 400px;" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Facebook Access Token</th>
                    <td>
                        <input type="text" name="wpas_fb_access_token" 
                               value="<?php echo esc_attr(get_option('wpas_fb_access_token')); ?>" 
                               style="width: 400px;" />
                        <p class="description">⚠️ Enter your Page Access Token (Long-Lived preferred).</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Share post to Facebook (with fallback if no featured image)
 */
add_action('publish_post', 'wpas_share_to_facebook', 10, 2);
function wpas_share_to_facebook($post_ID, $post) {
    $page_id = get_option('wpas_fb_page_id');
    $access_token = get_option('wpas_fb_access_token');

    if (!$page_id || !$access_token) {
        return;
    }

    $title = get_the_title($post_ID);
    $link  = get_permalink($post_ID);
    $image_url = get_the_post_thumbnail_url($post_ID, 'full');

    if ($image_url) {
        // اگر فیچرڈ امیج ہے → امیج اپلوڈ کریں
        $url = "https://graph.facebook.com/{$page_id}/photos";
        $data = [
            'url'          => $image_url,
            'caption'      => $title . "\n\n" . $link,
            'access_token' => $access_token
        ];
    } else {
        // اگر امیج نہیں ہے → صرف ٹائٹل + لنک شئیر کریں
        $url = "https://graph.facebook.com/{$page_id}/feed";
        $data = [
            'message'      => $title . "\n\n" . $link,
            'access_token' => $access_token
        ];
    }

    $options = [
        'http' => [
            'method'  => 'POST',
            'content' => http_build_query($data),
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n"
        ]
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) {
        error_log("WP Auto Share: Failed to post to Facebook for Post ID {$post_ID}");
    } else {
        error_log("WP Auto Share: Successfully posted Post ID {$post_ID} to Facebook.");
    }

    return $result;
}
