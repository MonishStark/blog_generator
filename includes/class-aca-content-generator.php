<?php
/**
 * Content Generator Class
 * Orchestrates the entire content generation process
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACA_Content_Generator {
    
    private static $instance = null;
    private $api_handler;
    private $media_handler;
    private $link_handler;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->api_handler = ACA_API_Handler::get_instance();
        $this->media_handler = ACA_Media_Handler::get_instance();
        $this->link_handler = ACA_Link_Handler::get_instance();
    }
    
    /**
     * Generate complete blog post from topic
     */
    public function generate_post($topic) {
        $result = array(
            'success' => false,
            'data' => array(),
            'errors' => array()
        );
        
        try {
            error_log('ACA Content Generator - Starting generation for topic: ' . $topic);
            
            // Use the original topic as primary keyword instead of generating keywords
            $primary_keyword = $topic;
            $keywords = array($topic); // Just use the topic as the only keyword
            
            error_log('ACA Content Generator - Using direct topic as primary keyword: ' . $primary_keyword);
            error_log('ACA Content Generator - Keywords array: ' . print_r($keywords, true));
            
            $result['data']['keywords'] = $keywords;
            $result['data']['primary_keyword'] = $primary_keyword;
            
            // Step 2: Generate title and slug
            error_log('ACA Content Generator - Step 2: Generating title and slug');
            $title = $this->generate_title($primary_keyword);
            $slug = $this->generate_slug($primary_keyword);
            
            error_log('ACA Content Generator - Generated title: ' . $title);
            error_log('ACA Content Generator - Generated slug: ' . $slug);
            
            $result['data']['title'] = $title;
            $result['data']['slug'] = $slug;
            
            // Step 3: Generate outline
            error_log('ACA Content Generator - Step 3: Generating outline');
            $outline = $this->api_handler->generate_outline($title, $keywords);
            if (is_wp_error($outline)) {
                error_log('ACA Content Generator - Outline generation failed: ' . $outline->get_error_message());
                throw new Exception('Failed to generate outline: ' . $outline->get_error_message());
            }
            
            error_log('ACA Content Generator - Generated outline: ' . print_r($outline, true));
            $result['data']['outline'] = $outline;
            
            // Step 4: Generate content
            error_log('ACA Content Generator - Step 4: Generating content');
            $content = $this->api_handler->generate_content($outline, $primary_keyword, $keywords);
            if (is_wp_error($content)) {
                error_log('ACA Content Generator - Content generation failed: ' . $content->get_error_message());
                throw new Exception('Failed to generate content: ' . $content->get_error_message());
            }
            
            // Step 5: Process links
            $content = $this->link_handler->process_internal_links($content);
            $content = $this->link_handler->process_external_links($content);
            
            $result['data']['content'] = $content;
            
            // Step 5.5: Generate excerpt
            $excerpt = $this->generate_excerpt($content, $primary_keyword);
            $result['data']['excerpt'] = $excerpt;
            
            // Step 6: Get featured image
            error_log('ACA: Starting featured image generation for keyword: ' . $primary_keyword);
            $featured_image = $this->media_handler->get_featured_image($primary_keyword, $title);
            if (!is_wp_error($featured_image)) {
                $result['data']['featured_image'] = $featured_image;
                error_log('ACA: Featured image generated successfully: ' . print_r($featured_image, true));
            } else {
                error_log('ACA: Featured image failed: ' . $featured_image->get_error_message());
            }
            
            // Step 7: Get content images
            error_log('ACA: Starting content images generation');
            $content_images = $this->media_handler->get_content_images($outline, $content);
            if (!is_wp_error($content_images)) {
                $result['data']['content_images'] = $content_images;
                // Insert images into content
                $content = $this->insert_images_into_content($content, $content_images);
                $result['data']['content'] = $content;
                error_log('ACA: Content images processed successfully: ' . count($content_images) . ' images');
            } else {
                error_log('ACA: Content images failed: ' . $content_images->get_error_message());
            }
            
            $result['success'] = true;
            
        } catch (Exception $e) {
            $result['errors'][] = $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Generate compelling title from primary keyword
     */
    private function generate_title($primary_keyword) {
        // Capitalize each word and make it title-case, keeping the original topic intact
        $title = ucwords(str_replace('-', ' ', $primary_keyword));
        
        // Only add year if the topic seems to need it (contains year-related terms)
        if (strpos(strtolower($primary_keyword), '2025') === false && 
            (strpos($primary_keyword, 'guide') !== false || strpos($primary_keyword, 'tips') !== false)) {
            $title .= ' ' . date('Y');
        }
        
        // Add compelling prefixes based on keyword type, but preserve the original meaning
        if (strpos(strtolower($primary_keyword), 'how to') === false && 
            strpos(strtolower($primary_keyword), 'guide to') === false &&
            strpos(strtolower($primary_keyword), 'everything') === false) {
            $prefixes = array(
                'The Ultimate Guide to',
                'Complete Guide to',
                'Everything You Need to Know About',
                'Comprehensive Overview of',
                'Understanding'
            );
            $title = $prefixes[array_rand($prefixes)] . ' ' . $title;
        }
        
        return $title;
    }
    
    /**
     * Generate post excerpt from content
     */
    private function generate_excerpt($content, $primary_keyword) {
        // Strip HTML tags and get plain text
        $plain_text = strip_tags($content);
        
        // Get first paragraph or first 160 characters
        $sentences = explode('.', $plain_text);
        $excerpt = '';
        
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (empty($sentence)) continue;
            
            // Add sentence if it won't make excerpt too long
            if (strlen($excerpt . $sentence) < 150) {
                $excerpt .= $sentence . '. ';
            } else {
                break;
            }
        }
        
        // Ensure excerpt includes the primary keyword if possible
        if (!empty($excerpt) && stripos($excerpt, $primary_keyword) === false) {
            // Try to add keyword naturally
            $excerpt = trim($excerpt);
            if (strlen($excerpt) < 120) {
                $excerpt .= " Learn more about " . str_replace('-', ' ', $primary_keyword) . ".";
            }
        }
        
        return trim($excerpt);
    }
    
    /**
     * Generate URL-friendly slug
     */
    private function generate_slug($primary_keyword) {
        $slug = sanitize_title($primary_keyword);
        
        // Add year if not present
        if (!preg_match('/\d{4}/', $slug)) {
            $slug .= '-' . date('Y');
        }
        
        return $slug;
    }
    
    /**
     * Insert images into content at appropriate locations
     */
    private function insert_images_into_content($content, $images) {
        if (empty($images)) {
            return $content;
        }
        
        // Split content by H2 headings
        $sections = preg_split('/(<h2[^>]*>.*?<\/h2>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        $processed_content = '';
        $image_index = 0;
        
        for ($i = 0; $i < count($sections); $i++) {
            $processed_content .= $sections[$i];
            
            // After every H2 heading, consider adding an image
            if (preg_match('/<h2[^>]*>.*?<\/h2>/i', $sections[$i]) && $image_index < count($images)) {
                // Add next section content first
                if (isset($sections[$i + 1])) {
                    $processed_content .= $sections[$i + 1];
                    $i++; // Skip the next section since we already added it
                }
                
                // Insert image
                $image = $images[$image_index];
                $processed_content .= "\n\n" . $image['html'] . "\n\n";
                $image_index++;
            }
        }
        
        return $processed_content;
    }
    
    /**
     * Create WordPress post from generated data
     */
    public function create_post($generated_data, $save_as_draft = true) {
        $post_data = array(
            'post_title' => $generated_data['title'],
            'post_content' => $generated_data['content'],
            'post_status' => $save_as_draft ? 'draft' : 'publish',
            'post_type' => 'post',
            'post_name' => $generated_data['slug']
        );
        
        // Insert post
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Set featured image
        if (isset($generated_data['featured_image']) && !empty($generated_data['featured_image'])) {
            set_post_thumbnail($post_id, $generated_data['featured_image']);
        }
        
        // Add meta data
        update_post_meta($post_id, '_aca_generated', true);
        update_post_meta($post_id, '_aca_primary_keyword', $generated_data['primary_keyword']);
        update_post_meta($post_id, '_aca_keywords', $generated_data['keywords']);
        update_post_meta($post_id, '_aca_generation_date', current_time('mysql'));
        
        return $post_id;
    }
    
    /**
     * Update existing post with generated content
     */
    public function update_post($post_id, $generated_data) {
        $post_data = array(
            'ID' => $post_id,
            'post_title' => $generated_data['title'],
            'post_content' => $generated_data['content'],
            'post_name' => $generated_data['slug']
        );
        
        // Update post
        $result = wp_update_post($post_data);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Set featured image
        if (isset($generated_data['featured_image']) && !empty($generated_data['featured_image'])) {
            set_post_thumbnail($post_id, $generated_data['featured_image']);
        }
        
        // Update meta data
        update_post_meta($post_id, '_aca_generated', true);
        update_post_meta($post_id, '_aca_primary_keyword', $generated_data['primary_keyword']);
        update_post_meta($post_id, '_aca_keywords', $generated_data['keywords']);
        update_post_meta($post_id, '_aca_generation_date', current_time('mysql'));
        
        return $post_id;
    }
}
