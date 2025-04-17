<?php
/*
Plugin Name: Advanced WooCommerce Sliders
Description: Create multiple full-width sliders for different categories
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit;
}

// Include necessary files - ensure this order is maintained
require_once plugin_dir_path(__FILE__) . 'includes/slider-cache.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/slider-admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/frontend/slider-frontend.php';

// Register activation hook to create database table
register_activation_hook(__FILE__, 'aws_create_sliders_table');

function aws_create_sliders_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aws_sliders';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        slug varchar(255) NOT NULL,
        settings longtext NOT NULL,
        slides longtext NOT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY slug (slug)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Add default settings option
    add_option('aws_default_settings', json_encode(array(
        'autoplay' => true,
        'autoplay_speed' => 5000,
        'animation_type' => 'slide',
        'animation_speed' => 500,
        'pause_on_hover' => true,
        'infinite_loop' => true,
        'adaptive_height' => false,
        'show_arrows' => true,
        'show_dots' => true,
        'aria_labels' => array(
            'next' => 'Next slide',
            'prev' => 'Previous slide',
            'first' => 'First slide',
            'last' => 'Last slide',
            'slide' => 'Slide %d of %d',
            'play' => 'Play slideshow',
            'pause' => 'Pause slideshow'
        )
    )));
}

// Initialize the plugin with proper timing
function aws_init() {
    // Check if WooCommerce is loaded if this is a WooCommerce dependency
    if (function_exists('WC') || !class_exists('WooCommerce')) {
        // Initialize admin functionality
        if (is_admin() && class_exists('AWS_Admin')) {
            new AWS_Admin();
        }
        
        // Initialize frontend functionality
        if (class_exists('AWS_Frontend')) {
            new AWS_Frontend();
        }
        
        // Initialize cache functionality after init hook
        add_action('init', function() {
            if (class_exists('AWS_Slider_Cache')) {
                AWS_Slider_Cache::register_cache_clear_hooks();
                
                // Only preload in admin or when debugging
                if (is_admin() || (defined('WP_DEBUG') && WP_DEBUG)) {
                    AWS_Slider_Cache::preload_sliders();
                }
            }
        });
    } else {
        // WooCommerce not active - handle this case
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            _e('Advanced WooCommerce Sliders requires WooCommerce to be installed and active.', 'your-text-domain');
            echo '</p></div>';
        });
    }
}

// Hook initialization at the right time with proper priority
add_action('plugins_loaded', 'aws_init', 15); // Higher priority to ensure dependencies are loaded