<?php
/**
 * AJAX Handlers for Steadfast Easy Booking
 */

if (!defined('ABSPATH'))
    exit;

// Save settings
add_action('wp_ajax_sfb_save_settings', 'sfb_save_settings');
function sfb_save_settings()
{
    check_ajax_referer('sfb_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $api_key = sanitize_text_field($_POST['api_key']);
    $secret_key = sanitize_text_field($_POST['secret_key']);

    if ($api_key && $api_key !== '**********') {
        update_option('sfb_api_key', $api_key);
    }

    if ($secret_key && $secret_key !== '**********') {
        update_option('sfb_secret_key', $secret_key);
    }

    wp_send_json_success('Settings saved successfully');
}

// Hide tip
add_action('wp_ajax_sfb_hide_tip', 'sfb_hide_tip');
function sfb_hide_tip()
{
    check_ajax_referer('sfb_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    update_option('sfb_hide_tip', 'yes');
    wp_send_json_success('Tip hidden');
}

// Reset plugin
add_action('wp_ajax_sfb_reset_plugin', 'sfb_reset_plugin');
function sfb_reset_plugin()
{
    check_ajax_referer('sfb_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    delete_option('sfb_api_key');
    delete_option('sfb_secret_key');
    delete_option('sfb_hide_tip');

    wp_send_json_success('Plugin reset successfully');
}

// Create order
add_action('wp_ajax_sfb_create_order', 'sfb_create_order');
function sfb_create_order()
{
    check_ajax_referer('sfb_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $api_key = get_option('sfb_api_key');
    $secret_key = get_option('sfb_secret_key');

    if (empty($api_key) || empty($secret_key)) {
        wp_send_json_error('API keys not configured');
    }

    $order_data = array(
        'invoice' => sanitize_text_field($_POST['invoice']),
        'recipient_name' => sanitize_text_field($_POST['recipient_name']),
        'recipient_address' => sanitize_text_field($_POST['recipient_address']),
        'recipient_phone' => sanitize_text_field($_POST['recipient_phone']),
        'cod_amount' => floatval($_POST['cod_amount']),
        'note' => sanitize_text_field($_POST['note']),
        'delivery_type' => intval($_POST['delivery_type'])
    );

    $response = wp_remote_post('https://portal.packzy.com/api/v1/create_order', array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Api-Key' => $api_key,
            'Secret-Key' => $secret_key
        ),
        'body' => json_encode($order_data),
        'timeout' => 30
    ));

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    wp_send_json_success($body);
}

// Bulk create orders
add_action('wp_ajax_sfb_bulk_create_orders', 'sfb_bulk_create_orders');
function sfb_bulk_create_orders()
{
    check_ajax_referer('sfb_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $api_key = get_option('sfb_api_key');
    $secret_key = get_option('sfb_secret_key');

    if (empty($api_key) || empty($secret_key)) {
        wp_send_json_error('API keys not configured');
    }

    $order_data = json_decode(stripslashes($_POST['order_data']), true);

    $response = wp_remote_post('https://portal.packzy.com/api/v1/create_order', array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Api-Key' => $api_key,
            'Secret-Key' => $secret_key
        ),
        'body' => json_encode($order_data),
        'timeout' => 30
    ));

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    wp_send_json_success($body);
}