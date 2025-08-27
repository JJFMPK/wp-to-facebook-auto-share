<?php
/*
Plugin Name: WP to Facebook Auto Share
Description: نئی پوسٹ پبلش ہوتے ہی خودکار طور پر فیس بک پیج پر شیئر کرے۔
Version: 1.0
Author: آپ کا نام
*/

if ( ! defined( 'ABSPATH' ) ) exit;

// جب نئی پوسٹ پبلش ہو
add_action('publish_post', 'wp_fb_auto_share');

function wp_fb_auto_share($post_ID) {
    
    // پوسٹ کا ڈیٹا لیں
    $post = get_post($post_ID);
    $title = $post->post_title;
    $link  = get_permalink($post_ID);

    // فیس بک API ڈیٹا
    $page_id       = "YOUR_PAGE_ID"; // یہاں اپنا پیج آئی ڈی ڈالیں
    $access_token  = "YOUR_LONG_LIVED_PAGE_ACCESS_TOKEN"; // یہاں ٹوکن ڈالیں
    $fb_api_url    = "https://graph.facebook.com/$page_id/feed";

    // پیغام تیار کریں
    $message = $title . " 🔗 " . $link;

    // ڈیٹا بھیجیں
    $response = wp_remote_post($fb_api_url, array(
        'body' => array(
            'message'      => $message,
            'access_token' => $access_token
        )
    ));

    // ایرر لاگ کریں (اگر کوئی مسئلہ آئے)
    if (is_wp_error($response)) {
        error_log("Facebook Auto Share Error: " . $response->get_error_message());
    }
}
