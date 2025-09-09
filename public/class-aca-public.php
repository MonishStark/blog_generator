<?php
/**
 * Public Class
 * Handles public-facing functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACA_Public {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Add any public-facing hooks here
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
        add_filter('the_content', array($this, 'add_generation_notice'));
    }
    
    /**
     * Enqueue public scripts and styles
     */
    public function enqueue_public_scripts() {
        // Only enqueue if needed on frontend
        // Currently no public scripts needed
    }
    
    /**
     * Add generation notice to AI-generated posts
     */
    public function add_generation_notice($content) {
        if (!is_single() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        $is_generated = get_post_meta(get_the_ID(), '_aca_generated', true);
        
        
        
        return $content;
    }
}
