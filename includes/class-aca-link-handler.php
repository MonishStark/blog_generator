<?php
/**
 * Link Handler Class
 * Handles internal and external link processing
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACA_Link_Handler {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Constructor
    }
    
    /**
     * Process internal links in content
     */
    public function process_internal_links($content) {
        $max_internal_links = get_option('aca_max_internal_links', 3);
        
        if ($max_internal_links <= 0) {
            return $content;
        }
        
        // Get all published posts
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => 50,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if (empty($posts)) {
            return $content;
        }
        
        $links_added = 0;
        
        foreach ($posts as $post) {
            if ($links_added >= $max_internal_links) {
                break;
            }
            
            $post_title = get_the_title($post->ID);
            $post_url = get_permalink($post->ID);
            
            // Look for mentions of the post title or keywords in content
            $patterns = array(
                $post_title,
                $this->extract_keywords_from_title($post_title)
            );
            
            foreach ($patterns as $pattern) {
                if (empty($pattern) || strlen($pattern) < 3) {
                    continue;
                }
                
                // Escape pattern for regex
                $escaped_pattern = preg_quote($pattern, '/');
                
                // Check if pattern exists in content and is not already linked
                if (preg_match('/(?<!<a[^>]*>)' . $escaped_pattern . '(?![^<]*<\/a>)/i', $content)) {
                    // Replace first occurrence with link
                    $link_html = '<a href="' . esc_url($post_url) . '">' . $pattern . '</a>';
                    $content = preg_replace('/(?<!<a[^>]*>)(' . $escaped_pattern . ')(?![^<]*<\/a>)/i', $link_html, $content, 1);
                    $links_added++;
                    break; // Move to next post
                }
            }
        }
        
        return $content;
    }
    
    /**
     * Process external links in content
     */
    public function process_external_links($content) {
        $max_external_links = get_option('aca_max_external_links', 3);
        
        if ($max_external_links <= 0) {
            return $content;
        }
        
        // Find all [LINK: description] placeholders
        $pattern = '/\[LINK:\s*([^\]]+)\]/i';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
        
        $links_processed = 0;
        
        foreach ($matches as $match) {
            if ($links_processed >= $max_external_links) {
                // Remove remaining placeholders
                $content = str_replace($match[0], '', $content);
                continue;
            }
            
            $description = trim($match[1]);
            $external_url = $this->find_external_url($description);
            
            if ($external_url) {
                $link_html = '<a href="' . esc_url($external_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($description) . '</a>';
                $content = str_replace($match[0], $link_html, $content);
                $links_processed++;
            } else {
                // Remove placeholder if no URL found
                $content = str_replace($match[0], $description, $content);
            }
        }
        
        return $content;
    }
    
    /**
     * Extract keywords from post title for matching
     */
    private function extract_keywords_from_title($title) {
        // Remove common words
        $stop_words = array('the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'how', 'what', 'why', 'when', 'where', 'who', 'guide', 'ultimate', 'complete', 'best');
        
        $words = explode(' ', strtolower($title));
        $keywords = array_diff($words, $stop_words);
        
        // Return longest meaningful keyword
        $longest = '';
        foreach ($keywords as $keyword) {
            if (strlen($keyword) > strlen($longest) && strlen($keyword) >= 4) {
                $longest = $keyword;
            }
        }
        
        return $longest;
    }
    
    /**
     * Find external URL for given description/topic
     */
    private function find_external_url($description) {
        // Predefined high-authority sources based on common topics
        $authority_sources = array(
            'wikipedia' => array(
                'base_url' => 'https://en.wikipedia.org/wiki/',
                'keywords' => array('definition', 'history', 'overview', 'about', 'what is')
            ),
            'github' => array(
                'base_url' => 'https://github.com/',
                'keywords' => array('code', 'repository', 'github', 'source', 'development')
            ),
            'mdn' => array(
                'base_url' => 'https://developer.mozilla.org/en-US/docs/',
                'keywords' => array('javascript', 'css', 'html', 'web', 'browser')
            ),
            'w3schools' => array(
                'base_url' => 'https://www.w3schools.com/',
                'keywords' => array('tutorial', 'learn', 'programming', 'web development')
            )
        );
        
        $description_lower = strtolower($description);
        
        // Try to match with authority sources
        foreach ($authority_sources as $source => $config) {
            foreach ($config['keywords'] as $keyword) {
                if (strpos($description_lower, $keyword) !== false) {
                    return $this->build_url_for_source($source, $description, $config['base_url']);
                }
            }
        }
        
        // Fallback to Google search for credible sources
        return $this->get_google_search_result($description);
    }
    
    /**
     * Build URL for specific authority source
     */
    private function build_url_for_source($source, $description, $base_url) {
        switch ($source) {
            case 'wikipedia':
                $search_term = str_replace(' ', '_', ucwords($description));
                return $base_url . $search_term;
                
            case 'github':
                $search_term = str_replace(' ', '+', $description);
                return 'https://github.com/search?q=' . urlencode($search_term);
                
            case 'mdn':
                $search_term = str_replace(' ', '/', strtolower($description));
                return $base_url . $search_term;
                
            case 'w3schools':
                $search_term = str_replace(' ', '_', strtolower($description));
                return $base_url . $search_term . '.asp';
                
            default:
                return $base_url;
        }
    }
    
    /**
     * Get credible search result (simplified approach)
     */
    private function get_google_search_result($description) {
        // For production, you might want to integrate with Google Custom Search API
        // For now, we'll return some common high-authority domains based on topic
        
        $domain_mapping = array(
            'research' => 'https://scholar.google.com/scholar?q=' . urlencode($description),
            'news' => 'https://news.google.com/search?q=' . urlencode($description),
            'health' => 'https://www.who.int/news-room/fact-sheets',
            'technology' => 'https://techcrunch.com/search/' . urlencode($description),
            'business' => 'https://www.businessinsider.com/search?q=' . urlencode($description),
            'education' => 'https://www.edx.org/search?q=' . urlencode($description)
        );
        
        $description_lower = strtolower($description);
        
        foreach ($domain_mapping as $topic => $url) {
            if (strpos($description_lower, $topic) !== false) {
                return $url;
            }
        }
        
        // Default fallback
        return 'https://en.wikipedia.org/wiki/' . str_replace(' ', '_', ucwords($description));
    }
    
    /**
     * Validate URL accessibility
     */
    private function validate_url($url) {
        $response = wp_remote_head($url, array('timeout' => 10));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        return $response_code >= 200 && $response_code < 400;
    }
}
