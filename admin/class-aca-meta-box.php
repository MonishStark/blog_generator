<?php
/**
 * Meta Box Class
 * Handles the AI Content Architect meta box in the post editor
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACA_Meta_Box {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        // Add AJAX actions
        add_action('wp_ajax_aca_generate_content', array($this, 'ajax_generate_content'));
        add_action('wp_ajax_aca_apply_content', array($this, 'ajax_apply_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Add meta box to post editor
     */
    public function add_meta_box() {
        add_meta_box(
            'aca-content-generator',
            __('AI Blog Generator', 'ai-blog-generator'),
            array($this, 'meta_box_html'),
            'post',
            'side',
            'high'
        );
    }
    
    /**
     * Meta box HTML content
     */
    public function meta_box_html($post) {
        // Add nonce for security
        wp_nonce_field('aca_generate_content', 'aca_nonce');
        
        // Check if API keys are configured
        $gemini_key = get_option('aca_gemini_api_key', '');
        $image_provider = get_option('aca_image_provider', 'pexels');
        $pexels_key = get_option('aca_pexels_api_key', '');
        $unsplash_key = get_option('aca_unsplash_api_key', '');
        
        $has_gemini = !empty($gemini_key);
        $has_image_key = ($image_provider === 'pexels' && !empty($pexels_key)) || 
                        ($image_provider === 'unsplash' && !empty($unsplash_key));
        
        ?>
        <div id="aca-meta-box">
            <?php if (!$has_gemini || !$has_image_key): ?>
                <div class="aca-notice aca-notice-warning">
                    <p><strong><?php _e('Configuration Required', 'ai-blog-generator'); ?></strong></p>
                    <p><?php _e('Please configure your API keys in the plugin settings to use AI Blog Generator.', 'ai-blog-generator'); ?></p>
                    <a href="<?php echo admin_url('options-general.php?page=aca-settings'); ?>" class="button button-small">
                        <?php _e('Go to Settings', 'ai-blog-generator'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="aca-input-section">
                    <p class="description">
                        <?php _e('The blog topic will be taken from the main post title. Click Generate to create a complete SEO-optimized blog post.', 'ai-blog-generator'); ?>
                    </p>
                </div>
                
                <div class="aca-button-section">
                    <button type="button" id="aca-generate-btn" class="button button-primary button-large">
                        <span class="aca-btn-text"><?php _e('Generate Blog Post', 'ai-blog-generator'); ?></span>
                        <span class="aca-btn-loading" style="display: none;">
                            <span class="spinner is-active"></span>
                            <?php _e('Generating...', 'ai-blog-generator'); ?>
                        </span>
                    </button>
                </div>
                
                <div id="aca-status-area" class="aca-status-hidden">
                    <div class="aca-progress-bar">
                        <div class="aca-progress-fill"></div>
                    </div>
                    <div class="aca-status-text">
                        <?php _e('Ready to generate...', 'ai-blog-generator'); ?>
                    </div>
                    <div class="aca-step-details"></div>
                </div>
                
                <div id="aca-result-area" class="aca-result-hidden">
                    <h4><?php _e('Generation Complete!', 'ai-blog-generator'); ?></h4>
                    <div class="aca-result-summary"></div>
                    <div class="aca-result-actions">
                        <button type="button" id="aca-apply-content" class="button button-primary">
                            <?php _e('Apply to Post', 'ai-blog-generator'); ?>
                        </button>
                        <button type="button" id="aca-generate-new" class="button">
                            <?php _e('Generate New', 'ai-blog-generator'); ?>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
        #aca-meta-box {
            padding: 0;
        }
        
        .aca-notice {
            padding: 10px;
            border-left: 4px solid #ffba00;
            background: #fff3cd;
            margin-bottom: 15px;
        }
        
        .aca-notice-warning {
            border-left-color: #dc3232;
            background: #fbeaea;
        }
        
        .aca-input-section {
            margin-bottom: 15px;
        }
        
        .aca-input-section label {
            display: block;
            margin-bottom: 5px;
        }
        
        .aca-button-section {
            margin-bottom: 15px;
        }
        
        #aca-generate-btn {
            width: 100%;
            position: relative;
        }
        
        .aca-btn-loading .spinner {
            float: none;
            margin: 0 5px 0 0;
        }
        
        #aca-status-area {
            margin-bottom: 15px;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .aca-status-hidden {
            display: none;
        }
        
        .aca-progress-bar {
            width: 100%;
            height: 10px;
            background: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .aca-progress-fill {
            height: 100%;
            background: #0073aa;
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .aca-status-text {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .aca-step-details {
            font-size: 12px;
            color: #666;
        }
        
        #aca-result-area {
            padding: 10px;
            background: #e7f7e7;
            border: 1px solid #4caf50;
            border-radius: 4px;
        }
        
        .aca-result-hidden {
            display: none;
        }
        
        .aca-result-summary {
            margin-bottom: 10px;
            font-size: 12px;
        }
        
        .aca-result-actions button {
            margin-right: 5px;
        }
        </style>
        <?php
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        
        global $post_type;
        if ('post' !== $post_type) {
            return;
        }
        
        wp_enqueue_script(
            'aca-meta-box',
            ACA_PLUGIN_URL . 'assets/js/meta-box.js',
            array('jquery'),
            ACA_VERSION,
            true
        );
        
        wp_localize_script('aca-meta-box', 'aca_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aca_ajax_nonce'),
            'strings' => array(
                'step1' => __('Step 1/7: Researching keywords...', 'ai-blog-generator'),
                'step2' => __('Step 2/7: Generating title and outline...', 'ai-blog-generator'),
                'step3' => __('Step 3/7: Creating content...', 'ai-blog-generator'),
                'step4' => __('Step 4/7: Processing internal links...', 'ai-blog-generator'),
                'step5' => __('Step 5/7: Adding external links...', 'ai-blog-generator'),
                'step6' => __('Step 6/7: Sourcing featured image...', 'ai-blog-generator'),
                'step7' => __('Step 7/7: Adding content images...', 'ai-blog-generator'),
                'complete' => __('Content generation complete!', 'ai-blog-generator'),
                'error' => __('An error occurred during generation.', 'ai-blog-generator'),
                'empty_topic' => __('Please enter a topic.', 'ai-blog-generator'),
                'applying' => __('Applying content to post...', 'ai-blog-generator')
            )
        ));
    }
    
    /**
     * AJAX handler for applying generated content to post
     */
    public function ajax_apply_content() {
        // Log that the function is being called
        error_log('ACA: ajax_apply_content() function called in meta-box class');
        
        // Debug logging
        error_log('ACA Apply Content - POST data: ' . print_r($_POST, true));
        error_log('ACA Apply Content - Nonce received: ' . ($_POST['nonce'] ?? 'MISSING'));
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'aca_ajax_nonce')) {
            error_log('ACA Apply Content - Nonce verification failed');
            wp_send_json_error('Security check failed');
            return;
        }
        
        error_log('ACA Apply Content - Nonce verification passed');
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            error_log('ACA Apply Content - Insufficient permissions');
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $transient_key = sanitize_text_field($_POST['transient_key'] ?? '');
        $post_id = intval($_POST['post_id'] ?? 0);
        
        error_log('ACA Apply Content - Transient key: ' . $transient_key);
        error_log('ACA Apply Content - Post ID: ' . $post_id);
        
        if (empty($transient_key)) {
            error_log('ACA Apply Content - Missing transient key');
            wp_send_json_error('Missing transient key');
            return;
        }
        
        // Get generated content from transient
        $generated_data = get_transient($transient_key);
        
        if (!$generated_data) {
            wp_send_json_error('Generated content not found or expired');
            return;
        }
        
        error_log('ACA: Applying content to post ID: ' . $post_id);
        
        // Apply content to post
        $media_handler = ACA_Media_Handler::get_instance();
        $final_result = $media_handler->apply_content_to_post($post_id, $generated_data);
        
        if ($final_result['success']) {
            // Clean up transient
            delete_transient($transient_key);
            
            error_log('ACA: Content successfully applied to post');
            
            wp_send_json_success(array(
                'message' => 'Content applied successfully',
                'post_id' => $post_id,
                'edit_url' => get_edit_post_link($post_id, 'raw')
            ));
        } else {
            error_log('ACA: Failed to apply content: ' . print_r($final_result['errors'], true));
            wp_send_json_error(array(
                'message' => 'Failed to apply content to post',
                'errors' => $final_result['errors']
            ));
        }
    }

    /**
     * AJAX handler for content generation
     */
    public function ajax_generate_content() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'aca_ajax_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $topic = sanitize_text_field($_POST['topic']);
        $post_id = intval($_POST['post_id']);
        
        if (empty($topic)) {
            wp_send_json_error('Topic is required');
            return;
        }
        
        // Log the generation request
        error_log('ACA: Starting content generation for topic: ' . $topic);
        
        // Generate content
        $content_generator = ACA_Content_Generator::get_instance();
        $result = $content_generator->generate_post($topic);
        
        if ($result['success']) {
            // Store result in transient for applying later
            $transient_key = 'aca_generated_' . get_current_user_id() . '_' . time();
            set_transient($transient_key, $result['data'], 3600); // 1 hour
            
            error_log('ACA: Content generation successful, transient key: ' . $transient_key);
            
            wp_send_json_success(array(
                'message' => 'Content generated successfully',
                'data' => array(
                    'title' => $result['data']['title'],
                    'content' => $result['data']['content'],
                    'word_count' => str_word_count(strip_tags($result['data']['content'])),
                    'keywords' => $result['data']['keywords'],
                    'has_featured_image' => isset($result['data']['featured_image']),
                    'content_images_count' => isset($result['data']['content_images']) ? count($result['data']['content_images']) : 0,
                    'transient_key' => $transient_key
                )
            ));
        } else {
            error_log('ACA: Content generation failed: ' . print_r($result['errors'], true));
            wp_send_json_error(array(
                'message' => 'Content generation failed',
                'errors' => $result['errors']
            ));
        }
    }
}
