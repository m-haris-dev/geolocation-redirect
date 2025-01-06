<?php
/*
Plugin Name: Clicky Geolocation Redirect
Description: Redirect users to the correct domain based on their geolocation.
Version: 1.0
Author: ClickySoft
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Enqueue script to display IP in an alert
add_action( 'wp_enqueue_scripts', 'show_ip_alert_script' );

function show_ip_alert_script() {
    // Get the user's IP address
    $user_ip = $_SERVER['REMOTE_ADDR'];

    // Pass the IP address to the JavaScript
    wp_enqueue_script( 'show-ip-script', plugin_dir_url( __FILE__ ) . 'show-my-ip.js', array(), '1.0', true );
    wp_localize_script( 'show-ip-script', 'ipData', array( 'ip' => esc_js( $user_ip ) ) );
}

function country_specific_redirect() {
    // Avoid redirecting in the admin area or during AJAX requests
    if (is_admin() || wp_doing_ajax()) {
        return;
    }

    // Get the current URL
    $current_url = (is_ssl() ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

    // Get the user's IP address
    $user_ip = $_SERVER['REMOTE_ADDR'];

    // Call IP-API to get geolocation data
    $api_url = 'http://ip-api.com/json/' . $user_ip;
    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
        error_log('IP-API request failed: ' . $response->get_error_message());
        return; // Exit if the API request fails
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    // Ensure the API returned valid data
    if (empty($data['countryCode'])) {
        error_log('IP-API returned invalid data: ' . wp_remote_retrieve_body($response));
        return; // Exit if no country code is returned
    }

    $country_code = strtolower($data['countryCode']); // Convert country code to lowercase

    // Map country codes to specific base URLs
    $redirect_urls = [
        'us' => 'https://almustafa.clickysoft.us/usa/', // United States
        'gb' => 'https://almustafa.clickysoft.us/united-kingdom/', // United Kingdom
        'ca' => 'https://almustafa.clickysoft.us/canada/', // Canada
    ];

    // Determine the target base URL for the user's country
    $target_base_url = isset($redirect_urls[$country_code]) ? $redirect_urls[$country_code] : 'https://almustafa.clickysoft.us/';

    // Allow inner pages by checking if the current URL starts with the target base URL
    if (strpos($current_url, $target_base_url) === 0) {
        return; // Allow access to inner pages
    }

    // Redirect to the target base URL
    wp_redirect($target_base_url);
    exit;
}

add_action('template_redirect', 'country_specific_redirect');

