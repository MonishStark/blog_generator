<?php
/**
 * Media Handler Class
 * Handles image search, download, and WordPress media library integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACA_Media_Handler {
    
    private static $instance = null;
    private $api_handler;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->api_handler = ACA_API_Handler::get_instance();
    }
    
    /**
     * Get and download featured image
     */
    public function get_featured_image($keyword, $alt_text) {
        $images = $this->api_handler->search_images($keyword, 1);
        
        if (is_wp_error($images) || empty($images)) {
            return new WP_Error('no_images', 'No images found for keyword: ' . $keyword);
        }
        
        $image = $images[0];
        $image_url = $this->get_image_url($image);
        
        if (!$image_url) {
            return new WP_Error('invalid_image', 'Invalid image URL');
        }
        
        // Download and add to media library
        $attachment_id = $this->download_image_to_media_library($image_url, $alt_text);
        
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }
        
        // Return structured image data
        $attachment_url = wp_get_attachment_url($attachment_id);
        $attachment_metadata = wp_get_attachment_metadata($attachment_id);
        
        return array(
            'id' => $attachment_id,
            'url' => $attachment_url,
            'alt' => $alt_text,
            'caption' => $this->get_image_attribution($image),
            'width' => isset($attachment_metadata['width']) ? $attachment_metadata['width'] : null,
            'height' => isset($attachment_metadata['height']) ? $attachment_metadata['height'] : null
        );
    }
    
    /**
     * Get content images based on outline headings
     */
    public function get_content_images($outline, $content) {
        $images = array();
        
        if (!isset($outline['headings']) || empty($outline['headings'])) {
            return $images;
        }
        
        $max_images = 2; // Limit to 2 content images
        $processed = 0;
        
        foreach ($outline['headings'] as $heading) {
            if ($processed >= $max_images) {
                break;
            }
            
            // Extract keywords from heading for image search
            $search_term = $this->extract_image_search_term($heading);
            
            $search_results = $this->api_handler->search_images($search_term, 1);
            
            if (!is_wp_error($search_results) && !empty($search_results)) {
                $image = $search_results[0];
                $image_url = $this->get_image_url($image);
                
                if ($image_url) {
                    $alt_text = strip_tags($heading);
                    $attachment_id = $this->download_image_to_media_library($image_url, $alt_text);
                    
                    if (!is_wp_error($attachment_id)) {
                        $images[] = array(
                            'attachment_id' => $attachment_id,
                            'heading' => $heading,
                            'alt_text' => $alt_text,
                            'html' => $this->generate_image_html($attachment_id, $alt_text)
                        );
                        $processed++;
                    }
                }
            }
        }
        
        return $images;
    }
    
    /**
     * Extract search term from heading for image search
     */
    private function extract_image_search_term($heading) {
        // Remove HTML tags
        $heading = strip_tags($heading);
        
        // Remove common words that don't help with image search
        $stop_words = array('the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'how', 'what', 'why', 'when', 'where', 'who');
        $words = explode(' ', strtolower($heading));
        $filtered_words = array_diff($words, $stop_words);
        
        // Take first 2-3 meaningful words
        $search_words = array_slice($filtered_words, 0, 3);
        
        return implode(' ', $search_words);
    }
    
    /**
     * Get image URL based on provider
     */
    private function get_image_url($image) {
        $provider = get_option('aca_image_provider', 'pexels');
        
        if ($provider === 'unsplash') {
            return isset($image['urls']['regular']) ? $image['urls']['regular'] : null;
        } else {
            // Pexels
            return isset($image['src']['large']) ? $image['src']['large'] : null;
        }
    }
    
    /**
     * Download image and add to WordPress media library
     */
    public function download_image_to_media_library($image_url, $alt_text) {
        // Include WordPress media functions
        if (!function_exists('media_handle_sideload')) {
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
        }
        
        // Download image
        $tmp = download_url($image_url);
        
        if (is_wp_error($tmp)) {
            return $tmp;
        }
        
        // Prepare file array
        $file_array = array(
            'name' => basename($image_url) . '.jpg',
            'tmp_name' => $tmp
        );
        
        // If error storing temporarily, cleanup
        if (is_wp_error($tmp)) {
            wp_delete_file($file_array['tmp_name']);
            return $tmp;
        }
        
        // Upload to media library
        $attachment_id = media_handle_sideload($file_array, 0);
        
        // If error storing permanently, cleanup
        if (is_wp_error($attachment_id)) {
            wp_delete_file($file_array['tmp_name']);
            return $attachment_id;
        }
        
        // Set alt text
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
        
        // Set description
        wp_update_post(array(
            'ID' => $attachment_id,
            'post_content' => $alt_text
        ));
        
        return $attachment_id;
    }
    
    /**
     * Generate HTML for image insertion
     */
    private function generate_image_html($attachment_id, $alt_text) {
        $image_url = wp_get_attachment_image_url($attachment_id, 'large');
        
        if (!$image_url) {
            return '';
        }
        
        $html = '<figure class="wp-block-image size-large">';
        $html .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($alt_text) . '" class="wp-image-' . $attachment_id . '"/>';
        $html .= '</figure>';
        
        return $html;
    }
    
    /**
     * Get image attribution if required
     */
    private function get_image_attribution($image) {
        $provider = get_option('aca_image_provider', 'pexels');
        
        if ($provider === 'unsplash') {
            if (isset($image['user']['name'])) {
                return 'Photo by ' . $image['user']['name'] . ' on Unsplash';
            }
        } else {
            // Pexels
            if (isset($image['photographer'])) {
                return 'Photo by ' . $image['photographer'] . ' from Pexels';
            }
        }
        
        return '';
    }
    
    /**
     * Apply generated content to a WordPress post
     */
    public function apply_content_to_post($post_id, $generated_data) {
        $result = array(
            'success' => false,
            'errors' => array()
        );
        
        try {
            // For new posts (post_id = 0), we'll just return success
            // The content will be applied via JavaScript to the editor
            if ($post_id == 0) {
                $result['success'] = true;
                $result['message'] = 'Content ready for new post';
                return $result;
            }
            
            // For existing posts, update the post
            $post_data = array(
                'ID' => $post_id,
                'post_title' => $generated_data['title'],
                'post_content' => $generated_data['content'],
                'post_status' => 'draft' // Keep as draft for safety
            );
            
            $updated_post_id = wp_update_post($post_data);
            
            if (is_wp_error($updated_post_id)) {
                throw new Exception('Failed to update post: ' . $updated_post_id->get_error_message());
            }
            
            // Set featured image if available
            if (isset($generated_data['featured_image']) && !empty($generated_data['featured_image'])) {
                set_post_thumbnail($post_id, $generated_data['featured_image']);
            }
            
            // Store metadata
            update_post_meta($post_id, '_aca_generated', true);
            update_post_meta($post_id, '_aca_keywords', $generated_data['keywords']);
            update_post_meta($post_id, '_aca_generation_date', current_time('mysql'));
            
            $result['success'] = true;
            $result['message'] = 'Content applied successfully';
            
        } catch (Exception $e) {
            $result['errors'][] = $e->getMessage();
        }
        
        return $result;
    }
}
