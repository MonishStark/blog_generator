<?php
/**
 * Admin Class
 * Handles admin-specific functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACA_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on post edit pages and plugin settings
        if (!in_array($hook, array('post.php', 'post-new.php', 'settings_page_aca-settings'))) {
            return;
        }
        
        wp_enqueue_style(
            'aca-admin',
            ACA_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ACA_VERSION
        );
        
        if (in_array($hook, array('post.php', 'post-new.php'))) {
            wp_enqueue_script(
                'aca-admin',
                ACA_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                ACA_VERSION,
                true
            );
            
            wp_localize_script('aca-admin', 'aca_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aca_admin_nonce')
            ));
        }
    }
    
    /**
     * Display admin notices
     */
    public function admin_notices() {
        $screen = get_current_screen();
        
        // Show setup notice on post edit pages if not configured
        if (in_array($screen->id, array('post', 'edit-post'))) {
            $gemini_key = get_option('aca_gemini_api_key', '');
            
            if (empty($gemini_key)) {
                ?>
                <div class="notice notice-warning is-dismissible">
                    <p>
                        <strong><?php _e('AI Blog Generator', 'ai-blog-generator'); ?></strong> - 
                        <?php _e('Please configure your API keys to start generating AI-powered content.', 'ai-blog-generator'); ?>
                        <a href="<?php echo admin_url('options-general.php?page=aca-settings'); ?>" class="button button-small" style="margin-left: 10px;">
                            <?php _e('Configure Now', 'ai-blog-generator'); ?>
                        </a>
                    </p>
                </div>
                <?php
            }
        }
    }
}
