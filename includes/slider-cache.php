<?php
class AWS_Slider_Cache {
    private static $cache_group = 'aws_sliders';
    private static $cache_time = WEEK_IN_SECONDS;
    
    public static function get_slider($slug) {
        $cache_key = 'slider_' . $slug;
        $slider = wp_cache_get($cache_key, self::$cache_group);
        
        if (false === $slider) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'aws_sliders';
            $slider = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE slug = %s", $slug
            ));
            
            if ($slider) {
                wp_cache_set($cache_key, $slider, self::$cache_group, self::$cache_time);
            }
        }
        
        return $slider;
    }
    
     public static function get_slider_html($slug) {
        $cache_key = 'slider_html_' . $slug;
        $html = wp_cache_get($cache_key, self::$cache_group);
        
        if (false === $html) {
            // Get slider data
            $slider = self::get_slider($slug);
            if (!$slider) {
                return '<p>Slider not found</p>';
            }
            
            // Initialize frontend class
            $frontend = new AWS_Frontend();
            
            // Generate HTML content
            ob_start();
            echo $frontend->render_slider_content($slider);
            $html = ob_get_clean();
            
            // Cache the output
            wp_cache_set($cache_key, $html, self::$cache_group, HOUR_IN_SECONDS);
        }
        
        return $html;
    }
    
    public static function clear_cache($slug) {
        $cache_keys = [
            'slider_html_' . $slug,
            'slider_' . $slug
        ];
        
        foreach ($cache_keys as $key) {
            wp_cache_delete($key, self::$cache_group);
        }
    }
    
    public static function preload_sliders() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aws_sliders';
        $sliders = $wpdb->get_results("SELECT slug FROM $table_name");
        
        foreach ($sliders as $slider) {
            self::get_slider($slider->slug);
        }
    }
    
    public static function register_cache_clear_hooks() {
        add_action('save_post', function($post_id) {
            $post = get_post($post_id);
            if ($post && has_shortcode($post->post_content, 'aws_slider')) {
                preg_match_all('/\[aws_slider slug="([^"]+)"\]/', $post->post_content, $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $slug) {
                        self::clear_cache($slug);
                    }
                }
            }
        });
        
        add_action('aws_slider_updated', function($slider_id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'aws_sliders';
            $slider = $wpdb->get_row($wpdb->prepare(
                "SELECT slug FROM $table_name WHERE id = %d", $slider_id
            ));
            if ($slider) {
                self::clear_cache($slider->slug);
            }
        });
    }
}