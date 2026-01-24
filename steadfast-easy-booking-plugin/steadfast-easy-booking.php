<?php
/**
 * Plugin Name: Steadfast Easy Booking
 * Plugin URI: https://github.com/almahmudbd/steadfast-easy-booking/
 * Description: Steadfast courier booking tool for WordPress admin panel with single and bulk entry support
 * Version: 1.98
 * Author: Sukkar Shop
 * Author URI: https://sukkarshop.com
 * Text Domain: steadfast-booking
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH'))
    exit;

// Define plugin constants
define('SFB_VERSION', '1.98');
define('SFB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SFB_PLUGIN_URL', plugin_dir_url(__FILE__));

class Steadfast_Easy_Booking
{

    public function __construct()
    {
        // Add actions
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Include AJAX handlers
        require_once SFB_PLUGIN_DIR . 'includes/ajax-handlers.php';
    }

    public function add_admin_menu()
    {
        // Main menu (defaults to Single Booking)
        add_menu_page(
            'Steadfast Booking',
            'Steadfast Booking',
            'manage_options',
            'steadfast-booking',
            array($this, 'single_booking_page'),
            'dashicons-location',
            30
        );

        // Single booking submenu
        add_submenu_page(
            'steadfast-booking',
            'Single Booking',
            'সিঙ্গেল এন্ট্রি',
            'manage_options',
            'steadfast-booking',
            array($this, 'single_booking_page')
        );

        // Bulk booking submenu
        add_submenu_page(
            'steadfast-booking',
            'Bulk Booking',
            'বাল্ক এন্ট্রি',
            'manage_options',
            'steadfast-bulk-booking',
            array($this, 'bulk_booking_page')
        );

        // AI Booking submenu
        add_submenu_page(
            'steadfast-booking',
            'AI Booking',
            'AI এন্ট্রি',
            'manage_options',
            'steadfast-ai-booking',
            array($this, 'ai_booking_page')
        );

        // Settings submenu
        add_submenu_page(
            'steadfast-booking',
            'Settings',
            'সেটিংস',
            'manage_options',
            'steadfast-settings',
            array($this, 'settings_page')
        );
    }

    public function enqueue_admin_assets($hook)
    {
        if (
            strpos($hook, 'steadfast-booking') === false &&
            strpos($hook, 'steadfast-settings') === false &&
            strpos($hook, 'steadfast-ai-booking') === false
        ) {
            return;
        }

        // Enqueue styles
        wp_enqueue_style('sfb-admin-style', SFB_PLUGIN_URL . 'assets/admin-style.css', array(), SFB_VERSION);

        // Enqueue scripts
        wp_enqueue_script('sfb-admin-script', SFB_PLUGIN_URL . 'assets/admin-script.js', array('jquery'), SFB_VERSION, true);

        // Localize script
        wp_localize_script('sfb-admin-script', 'sfbAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sfb_nonce')
        ));
    }

    public function settings_page()
    {
        require_once SFB_PLUGIN_DIR . 'includes/settings-page.php';
    }

    public function single_booking_page()
    {
        require_once SFB_PLUGIN_DIR . 'includes/single-booking-page.php';
    }

    public function bulk_booking_page()
    {
        require_once SFB_PLUGIN_DIR . 'includes/bulk-booking-page.php';
    }

    public function ai_booking_page()
    {
        require_once SFB_PLUGIN_DIR . 'includes/ai-booking-page.php';
    }
}

// Initialize the plugin
new Steadfast_Easy_Booking();

/**
 * folder structure:
 * 
 * steadfast-easy-booking/
 * ├── steadfast-easy-booking.php (this file)
 * ├── includes/
 * │   ├── ajax-handlers.php
 * │   ├── ai-booking-page.php
 * │   ├── settings-page.php
 * │   ├── single-booking-page.php
 * │   └── bulk-booking-page.php
 * └── assets/
 *     ├── css/
 *     │   └── admin-style.css
 *     └── js/
 *         └── admin-script.js
 */