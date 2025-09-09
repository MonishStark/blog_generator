<?php
/**
 * Plugin Name: AI Blog Generator
 * Plugin URI: https://your-website.com/ai-blog-generator
 * Description: Automates the creation of SEO-optimized blog posts using Google Gemini AI. Generates complete, well-structured, and media-rich articles from simple topics.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://your-website.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-blog-generator
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ACA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ACA_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ACA_VERSION', '1.0.0');

/**
 * Main AI Blog Generator Class
 */
class AI_Content_Architect {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Get instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load required files
        $this->load_dependencies();
        
        // Initialize admin functionality
        if (is_admin()) {
            $this->init_admin();
        }
        
        // Initialize public functionality
        $this->init_public();
        
        // Load text domain
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once ACA_PLUGIN_PATH . 'includes/class-aca-api-handler.php';
        require_once ACA_PLUGIN_PATH . 'includes/class-aca-content-generator.php';
        require_once ACA_PLUGIN_PATH . 'includes/class-aca-media-handler.php';
        require_once ACA_PLUGIN_PATH . 'includes/class-aca-link-handler.php';
        
        if (is_admin()) {
            require_once ACA_PLUGIN_PATH . 'admin/class-aca-admin.php';
            require_once ACA_PLUGIN_PATH . 'admin/class-aca-settings.php';
            require_once ACA_PLUGIN_PATH . 'admin/class-aca-meta-box.php';
        }
        
        require_once ACA_PLUGIN_PATH . 'public/class-aca-public.php';
    }
    
    /**
     * Initialize admin functionality
     */
    private function init_admin() {
        ACA_Admin::get_instance();
        ACA_Settings::get_instance();
        ACA_Meta_Box::get_instance();
    }
    
    /**
     * Initialize public functionality
     */
    private function init_public() {
        ACA_Public::get_instance();
    }
    
    /**
     * Load text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain('ai-blog-generator', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
}

/**
 * Activation hook
 */
function aca_activate() {
    // Create default options
    add_option('aca_gemini_api_key', '');
    add_option('aca_pexels_api_key', '');
    add_option('aca_unsplash_api_key', '');
    add_option('aca_image_provider', 'pexels');
    add_option('aca_max_internal_links', 3);
    add_option('aca_max_external_links', 3);
    add_option('aca_auto_save_draft', 1);
}
register_activation_hook(__FILE__, 'aca_activate');

/**
 * Deactivation hook
 */
function aca_deactivate() {
    // Clean up if needed
}
register_deactivation_hook(__FILE__, 'aca_deactivate');

/**
 * Uninstall hook
 */
function aca_uninstall() {
    // Remove options if needed
    delete_option('aca_gemini_api_key');
    delete_option('aca_pexels_api_key');
    delete_option('aca_unsplash_api_key');
    delete_option('aca_image_provider');
    delete_option('aca_max_internal_links');
    delete_option('aca_max_external_links');
    delete_option('aca_auto_save_draft');
}
register_uninstall_hook(__FILE__, 'aca_uninstall');

// Initialize plugin
AI_Content_Architect::get_instance();
