<?php
/**
 * Admin Settings Class
 * Handles the plugin settings page in WordPress admin
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACA_Settings {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_options_page(
            __('AI Blog Generator Settings', 'ai-blog-generator'),
            __('AI Blog Generator', 'ai-blog-generator'),
            'manage_options',
            'aca-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        // API Settings Section
        add_settings_section(
            'aca_api_settings',
            __('API Configuration', 'ai-blog-generator'),
            array($this, 'api_settings_callback'),
            'aca-settings'
        );
        
        // OpenAI API Key
        add_settings_field(
            'aca_gemini_api_key',
            __('Google Gemini API Key', 'ai-blog-generator'),
            array($this, 'gemini_api_key_callback'),
            'aca-settings',
            'aca_api_settings'
        );
        
        // Pexels API Key
        add_settings_field(
            'aca_pexels_api_key',
            __('Pexels API Key', 'ai-blog-generator'),
            array($this, 'pexels_api_key_callback'),
            'aca-settings',
            'aca_api_settings'
        );
        
        // Perplexity API Key
        add_settings_field(
            'aca_perplexity_api_key',
            __('Perplexity API Key', 'ai-blog-generator'),
            array($this, 'perplexity_api_key_callback'),
            'aca-settings',
            'aca_api_settings'
        );
        
        // Unsplash API Key
        add_settings_field(
            'aca_unsplash_api_key',
            __('Unsplash API Key', 'ai-blog-generator'),
            array($this, 'unsplash_api_key_callback'),
            'aca-settings',
            'aca_api_settings'
        );
        
        // Image Provider Selection
        add_settings_field(
            'aca_image_provider',
            __('Image Provider', 'ai-blog-generator'),
            array($this, 'image_provider_callback'),
            'aca-settings',
            'aca_api_settings'
        );
        
        // Content Settings Section
        add_settings_section(
            'aca_content_settings',
            __('Content Configuration', 'ai-blog-generator'),
            array($this, 'content_settings_callback'),
            'aca-settings'
        );
        
        // Max Internal Links
        add_settings_field(
            'aca_max_internal_links',
            __('Max Internal Links', 'ai-blog-generator'),
            array($this, 'max_internal_links_callback'),
            'aca-settings',
            'aca_content_settings'
        );
        
        // Max External Links
        add_settings_field(
            'aca_max_external_links',
            __('Max External Links', 'ai-blog-generator'),
            array($this, 'max_external_links_callback'),
            'aca-settings',
            'aca_content_settings'
        );
        
        // Auto Save as Draft
        add_settings_field(
            'aca_auto_save_draft',
            __('Auto Save as Draft', 'ai-blog-generator'),
            array($this, 'auto_save_draft_callback'),
            'aca-settings',
            'aca_content_settings'
        );
        
        // Custom Content Prompt
        add_settings_field(
            'aca_custom_content_prompt',
            __('Custom Content Generation Prompt', 'ai-blog-generator'),
            array($this, 'custom_content_prompt_callback'),
            'aca-settings',
            'aca_content_settings'
        );
        
        // Register settings
        register_setting('aca_settings_group', 'aca_gemini_api_key', array($this, 'sanitize_api_key'));
        register_setting('aca_settings_group', 'aca_pexels_api_key', array($this, 'sanitize_api_key'));
        register_setting('aca_settings_group', 'aca_perplexity_api_key', array($this, 'sanitize_api_key'));
        register_setting('aca_settings_group', 'aca_unsplash_api_key', array($this, 'sanitize_api_key'));
        register_setting('aca_settings_group', 'aca_image_provider', array($this, 'sanitize_image_provider'));
        register_setting('aca_settings_group', 'aca_max_internal_links', array($this, 'sanitize_number'));
        register_setting('aca_settings_group', 'aca_max_external_links', array($this, 'sanitize_number'));
        register_setting('aca_settings_group', 'aca_auto_save_draft', array($this, 'sanitize_checkbox'));
        register_setting('aca_settings_group', 'aca_custom_content_prompt', array($this, 'sanitize_textarea'));
    }
    
    /**
     * Settings page HTML
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('aca_settings_group');
                do_settings_sections('aca-settings');
                submit_button(__('Save Settings', 'ai-blog-generator'));
                ?>
            </form>
            
            <div class="aca-info-section" style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-left: 4px solid #0073aa;">
                <h3><?php _e('Getting Started', 'ai-blog-generator'); ?></h3>
                <p><?php _e('To use AI Blog Generator, you need to obtain API keys from the following services:', 'ai-blog-generator'); ?></p>
                <ul>
                    <li><strong>Google Gemini:</strong> <a href="https://ai.google.dev/gemini-api/docs/api-key" target="_blank">Get your API key</a></li>
                    <li><strong>Pexels:</strong> <a href="https://www.pexels.com/api/" target="_blank">Get your API key</a></li>
                    <li><strong>Perplexity:</strong> <a href="https://www.perplexity.ai/settings/api" target="_blank">Get your API key</a> (optional - for enhanced research)</li>
                    <li><strong>Unsplash:</strong> <a href="https://unsplash.com/developers" target="_blank">Get your API key</a></li>
                </ul>
                <p><?php _e('Once configured, you can generate AI-powered blog posts from the post editor using the "AI Blog Generator" meta box.', 'ai-blog-generator'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * API Settings section callback
     */
    public function api_settings_callback() {
        echo '<p>' . __('Configure your API keys for content generation and image sourcing.', 'ai-blog-generator') . '</p>';
    }
    
    /**
     * Content Settings section callback
     */
    public function content_settings_callback() {
        echo '<p>' . __('Configure content generation preferences.', 'ai-blog-generator') . '</p>';
    }
    
    /**
     * Gemini API Key field
     */
    public function gemini_api_key_callback() {
        $value = get_option('aca_gemini_api_key', '');
        echo '<input type="password" id="aca_gemini_api_key" name="aca_gemini_api_key" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Required for AI content generation. Get your key from Google AI Studio.', 'ai-content-architect') . '</p>';
    }
    
    /**
     * Pexels API Key field
     */
    public function pexels_api_key_callback() {
        $value = get_option('aca_pexels_api_key', '');
        echo '<input type="password" id="aca_pexels_api_key" name="aca_pexels_api_key" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Required if using Pexels as image provider.', 'ai-content-architect') . '</p>';
    }
    
    /**
     * Perplexity API Key field
     */
    public function perplexity_api_key_callback() {
        $value = get_option('aca_perplexity_api_key', '');
        echo '<input type="password" id="aca_perplexity_api_key" name="aca_perplexity_api_key" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Used for research data to enhance content quality. Get your API key from <a href="https://www.perplexity.ai/settings/api" target="_blank">Perplexity</a>.', 'ai-content-architect') . '</p>';
    }
    
    /**
     * Unsplash API Key field
     */
    public function unsplash_api_key_callback() {
        $value = get_option('aca_unsplash_api_key', '');
        echo '<input type="password" id="aca_unsplash_api_key" name="aca_unsplash_api_key" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Required if using Unsplash as image provider.', 'ai-content-architect') . '</p>';
    }
    
    /**
     * Image Provider field
     */
    public function image_provider_callback() {
        $value = get_option('aca_image_provider', 'pexels');
        echo '<select id="aca_image_provider" name="aca_image_provider">';
        echo '<option value="pexels"' . selected($value, 'pexels', false) . '>Pexels</option>';
        echo '<option value="unsplash"' . selected($value, 'unsplash', false) . '>Unsplash</option>';
        echo '</select>';
        echo '<p class="description">' . __('Choose your preferred image provider.', 'ai-content-architect') . '</p>';
    }
    
    /**
     * Max Internal Links field
     */
    public function max_internal_links_callback() {
        $value = get_option('aca_max_internal_links', 3);
        echo '<input type="number" id="aca_max_internal_links" name="aca_max_internal_links" value="' . esc_attr($value) . '" min="0" max="10" />';
        echo '<p class="description">' . __('Maximum number of internal links to add to generated content.', 'ai-content-architect') . '</p>';
    }
    
    /**
     * Max External Links field
     */
    public function max_external_links_callback() {
        $value = get_option('aca_max_external_links', 3);
        echo '<input type="number" id="aca_max_external_links" name="aca_max_external_links" value="' . esc_attr($value) . '" min="0" max="10" />';
        echo '<p class="description">' . __('Maximum number of external links to add to generated content.', 'ai-content-architect') . '</p>';
    }
    
    /**
     * Auto Save as Draft field
     */
    public function auto_save_draft_callback() {
        $value = get_option('aca_auto_save_draft', 1);
        echo '<input type="checkbox" id="aca_auto_save_draft" name="aca_auto_save_draft" value="1"' . checked($value, 1, false) . ' />';
        echo '<label for="aca_auto_save_draft">' . __('Automatically save generated posts as drafts', 'ai-content-architect') . '</label>';
    }
    
    /**
     * Custom Content Prompt field
     */
    public function custom_content_prompt_callback() {
        $custom_value = get_option('aca_custom_content_prompt', '');
        $api_handler = ACA_API_Handler::get_instance();
        $default_prompt = $api_handler->get_default_prompt();
        
        // Show custom prompt if set, otherwise show default prompt for reference
        $display_value = !empty($custom_value) ? $custom_value : $default_prompt;
        
        echo '<textarea id="aca_custom_content_prompt" name="aca_custom_content_prompt" rows="15" cols="80" class="large-text">' . esc_textarea($display_value) . '</textarea>';
        
        // Add helpful buttons
        echo '<div class="aca-prompt-buttons">';
        if (!empty($custom_value)) {
            echo '<button type="button" id="aca-show-default" class="button">' . __('Show Default Prompt', 'ai-content-architect') . '</button>';
            echo '<button type="button" id="aca-restore-custom" class="button">' . __('Restore My Custom Prompt', 'ai-content-architect') . '</button>';
        } else {
            echo '<button type="button" id="aca-clear-to-custom" class="button">' . __('Clear to Create Custom Prompt', 'ai-content-architect') . '</button>';
        }
        echo '<button type="button" id="aca-reset-to-default" class="button">' . __('Reset to Default', 'ai-content-architect') . '</button>';
        echo '</div>';
        
        // Status indicator
        echo '<div id="aca-prompt-status" style="margin-top: 10px; padding: 8px; border-radius: 4px;">';
        if (!empty($custom_value)) {
            echo '<span style="color: #046b38; font-weight: bold;">⚡ Using Custom Prompt</span>';
        } else {
            echo '<span style="color: #0073aa; font-weight: bold;">📄 Showing Default Prompt (no custom prompt saved)</span>';
        }
        echo '</div>';
        
        echo '<p class="description" style="margin-top: 15px;">' . __('The textarea above shows the current active prompt. When no custom prompt is set, the default prompt is displayed for reference. You can use these placeholders:', 'ai-content-architect') . '</p>';
        echo '<p class="description"><strong>Available placeholders (use either format):</strong></p>';
        echo '<ul style="margin-left: 20px;">';
        echo '<li><code>{primary_keyword}</code> or <code>{$primary_keyword}</code> - The main topic/keyword</li>';
        echo '<li><code>{research_context}</code> or <code>{$research_context}</code> - Research data from Perplexity API</li>';
        echo '<li><code>{formatted_images}</code> or <code>{$formatted_images}</code> - Available images from image APIs</li>';
        echo '</ul>';
        echo '<p class="description"><em>' . __('Example: "Write a detailed blog post about {primary_keyword}. Use this research: {research_context}"', 'ai-content-architect') . '</em></p>';
        echo '<p class="description"><strong>Note:</strong> Formatting requirements for clean HTML output will be automatically added to your custom prompt.</p>';
        
        // Add JavaScript for the functionality
        echo '<script type="text/javascript">
        jQuery(document).ready(function($) {
            var defaultPrompt = ' . json_encode($default_prompt) . ';
            var customPrompt = ' . json_encode($custom_value) . ';
            var hasCustomPrompt = ' . (!empty($custom_value) ? 'true' : 'false') . ';
            
            function updateStatus(isCustom, message) {
                var statusDiv = $("#aca-prompt-status");
                if (isCustom) {
                    statusDiv.html("<span style=\"color: #046b38; font-weight: bold;\">⚡ " + message + "</span>");
                } else {
                    statusDiv.html("<span style=\"color: #0073aa; font-weight: bold;\">📄 " + message + "</span>");
                }
            }
            
            $("#aca-show-default").click(function(e) {
                e.preventDefault();
                $("#aca_custom_content_prompt").val(defaultPrompt);
                updateStatus(false, "Viewing Default Prompt (not saved yet)");
            });
            
            $("#aca-restore-custom").click(function(e) {
                e.preventDefault();
                $("#aca_custom_content_prompt").val(customPrompt);
                updateStatus(true, "Restored Custom Prompt");
            });
            
            $("#aca-clear-to-custom").click(function(e) {
                e.preventDefault();
                $("#aca_custom_content_prompt").val("");
                updateStatus(true, "Ready for Custom Prompt");
            });
            
            $("#aca-reset-to-default").click(function(e) {
                e.preventDefault();
                if (confirm("' . esc_js(__('Reset to default prompt? This will clear any custom prompt.', 'ai-content-architect')) . '")) {
                    $("#aca_custom_content_prompt").val("");
                    updateStatus(false, "Reset to Default (save settings to apply)");
                }
            });
        });
        </script>';
    }
    
    /**
     * Sanitize API key
     */
    public function sanitize_api_key($input) {
        return sanitize_text_field($input);
    }
    
    /**
     * Sanitize image provider
     */
    public function sanitize_image_provider($input) {
        $valid_providers = array('pexels', 'unsplash');
        return in_array($input, $valid_providers) ? $input : 'pexels';
    }
    
    /**
     * Sanitize number
     */
    public function sanitize_number($input) {
        $number = intval($input);
        return max(0, min(10, $number));
    }
    
    /**
     * Sanitize checkbox
     */
    public function sanitize_checkbox($input) {
        return $input ? 1 : 0;
    }
    
    /**
     * Sanitize textarea
     */
    public function sanitize_textarea($input) {
        return sanitize_textarea_field($input);
    }
}
