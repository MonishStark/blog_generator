<?php
/**
 * API Handler Class
 * Handles all external API calls (Google Gemini, Pexels, Unsplash)
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACA_API_Handler {
    
    private static $instance = null;
    private $gemini_api_key;
    private $pexels_api_key;
    private $unsplash_api_key;
    private $perplexity_api_key;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->gemini_api_key = get_option('aca_gemini_api_key', '');
        $this->pexels_api_key = get_option('aca_pexels_api_key', '');
        $this->unsplash_api_key = get_option('aca_unsplash_api_key', '');
        $this->perplexity_api_key = get_option('aca_perplexity_api_key', '');
    }
    
    /**
     * Get the default content generation prompt
     */
    public function get_default_prompt() {
        return <<<PROMPT
Act as an expert web content creator, developer and SEO specialist. Your task is to transform the following raw text content(Research Context) into a polished, comprehensive, and well-structured single HTML file suitable for a high-quality blog.
Apply the following set of rules and conditions meticulously:
1. Structure & HTML Formatting
Main Title: The article's main title must be enclosed in an <h1> tag.
Headings: Use <h2> for primary section titles (e.g., "Key Developments," "Potential and Benefits") and <h3> for sub-section titles (e.g., "AI-Integrated Diagnostics").
Readability: Break the content into short, easy-to-read paragraphs using <p> tags.
Lists: Convert any lists of items, such as benefits or challenges, into bulleted lists using <ul> and <li> tags.
Semantic HTML: Use semantic tags like <article>, <section>, <figure>, and <figcaption> to structure the content logically.
2. Content & Media Integration
Featured Image: After the <h1> title, insert a relevant, high-quality featured image. Place it within a <figure> tag.
Video Embedding: If a YouTube URL is present in the source data, embed the video directly into the page using an <iframe>. Ensure the video is responsive to fit different screen sizes.
External Links: All other external links from the source data should be included as standard <a> tags.

3. Authoritative Sourcing & Citations

Identify Claims: Find the key statistical and factual claims within the text (e.g., "nearly 400 FDA-approved algorithms," "90% sensitivity," "survey of over 3,700 experts").

Add Direct In-Text Citations: For each identified claim, add a superscript number that links directly to the external authoritative source (e.g., <sup><a href="https://source.com/article" target="_blank">[1]</a></sup>) (href to directly to to the source dont use #ref use orginal source).

Create a References Section: At the bottom of the article, create a final <h2>References</h2> section. Inside, use an ordered list (<ol>) and provide an external authoritative link (like a research paper, a reputable news article, or an official report) that supports each claim. Each list item (<li>) must have an id that matches its in-text citation link (e.g., <li id="ref1">...</li>).

4. Styling & Polish

Internal CSS: Include a <style> block in the <head> of the HTML to apply clean, modern, and professional styling. The design should prioritize readability with good font choices, spacing, and a clear visual hierarchy.

Tone and Flow: Make minor edits to the text for improved flow and clarity, ensuring it reads like a polished blog post.

5. Linking Strategy

External Links: Add at least 4 external, authoritative links within the body of the article or in the references section. Use placeholders or real links, ensuring they are formatted correctly (e.g., <a href="https://authority-site.com/relevant-article" target="_blank" rel="noopener noreferrer">anchor text</a>).

        ### Research Context
        {research_context}

        ### Available Images
        {formatted_images}
PROMPT;
    }
    
    /**
     * Make Google Gemini API call
     */
    public function call_gemini($prompt, $max_tokens = 2000, $temperature = 0.7) {
        if (empty($this->gemini_api_key)) {
            return new WP_Error('missing_api_key', 'Google Gemini API key is required');
        }
        
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $this->gemini_api_key;
        
        $data = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array(
                            'text' => $prompt
                        )
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => $temperature,
                'maxOutputTokens' => $max_tokens,
                'topP' => 0.8,
                'topK' => 10
            )
        );
        
        $args = array(
            'body' => wp_json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 60
        );
        
        $response = wp_remote_post($url, $args);
        
        // Log the full request details
        error_log('ACA Gemini API Request URL: ' . $url);
        error_log('ACA Gemini API Request Body: ' . wp_json_encode($args['body']));
        
        if (is_wp_error($response)) {
            error_log('ACA Gemini API Error: ' . $response->get_error_message());
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log('ACA Gemini API Response Code: ' . $response_code);
        error_log('ACA Gemini API Raw Response: ' . substr($body, 0, 1000) . '...');
        
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            error_log('ACA Gemini API Error Response: ' . print_r($data['error'], true));
            return new WP_Error('gemini_error', $data['error']['message']);
        }
        
        // Check for different response structures
        $generated_text = null;
        
        // Standard Gemini 1.5 format
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $generated_text = $data['candidates'][0]['content']['parts'][0]['text'];
            error_log('ACA Gemini API Generated Text (1.5 format): ' . substr($generated_text, 0, 500) . '...');
        }
        // Gemini 2.0/2.5 format - text directly in content
        elseif (isset($data['candidates'][0]['content']['text'])) {
            $generated_text = $data['candidates'][0]['content']['text'];
            error_log('ACA Gemini API Generated Text (2.0+ format): ' . substr($generated_text, 0, 500) . '...');
        }
        // Alternative format with parts but different structure
        elseif (isset($data['candidates'][0]['content']['parts']) && is_array($data['candidates'][0]['content']['parts'])) {
            foreach ($data['candidates'][0]['content']['parts'] as $part) {
                if (isset($part['text'])) {
                    $generated_text = $part['text'];
                    error_log('ACA Gemini API Generated Text (parts array): ' . substr($generated_text, 0, 500) . '...');
                    break;
                }
            }
        }
        
        if (!empty($generated_text)) {
            return $generated_text;
        }
        
        // Check if response was truncated due to MAX_TOKENS
        if (isset($data['candidates'][0]['finishReason']) && $data['candidates'][0]['finishReason'] === 'MAX_TOKENS') {
            error_log('ACA Gemini API Hit Max Tokens - Response may be incomplete');
            return new WP_Error('max_tokens_reached', 'Response was truncated due to token limit. Try reducing prompt length or increasing max tokens.');
        }
        
        // Check if content exists but is empty (thinking tokens used up)
        if (isset($data['candidates'][0]['content']) && isset($data['usageMetadata']['thoughtsTokenCount'])) {
            $thoughts_tokens = $data['usageMetadata']['thoughtsTokenCount'];
            error_log('ACA Gemini API used ' . $thoughts_tokens . ' thinking tokens but produced no text output');
            return new WP_Error('no_text_output', 'AI used all tokens for thinking but produced no text. Try simplifying the prompt or increasing max tokens.');
        }
        
        error_log('ACA Gemini API Unexpected Response Structure: ' . print_r($data, true));
        return new WP_Error('unexpected_response', 'Unexpected API response structure');
    }
    
    /**
     * Call Perplexity API for research
     */
    public function call_perplexity($query) {
        if (empty($this->perplexity_api_key)) {
            return new WP_Error('no_api_key', 'Perplexity API key not configured');
        }

        $url = 'https://api.perplexity.ai/chat/completions';

        $headers = array(
            'Authorization' => 'Bearer ' . $this->perplexity_api_key,
            'Content-Type' => 'application/json'
        );

        $body = array(
            'model' => 'sonar-pro',
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'Act as a professional news researcher who is capable of finding detailed summaries about a news topic from highly reputable sources.'
                ),
                array(
                    'role' => 'user',
                    'content' => "Research the following topic and return everything you can find about: 'AI in medical diagnostics 2025'. And give me urls for related image infographics and video videos for it too."
                )
            ),
        );

        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => json_encode($body),
            'timeout' => 30
        ));

        // Log the full request details
        error_log('ACA Perplexity API Request URL: ' . $url);
        error_log('ACA Perplexity API Request Body: ' . json_encode($body));

        if (is_wp_error($response)) {
            error_log('ACA Perplexity API Error: ' . $response->get_error_message());
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        error_log('ACA Perplexity API Response Code: ' . $response_code);
        error_log('ACA Perplexity API Raw Response: ' . substr($response_body, 0, 1000) . '...');

        $data = json_decode($response_body, true);

        if (isset($data['error'])) {
            error_log('ACA Perplexity API Error Response: ' . print_r($data['error'], true));
            return new WP_Error('perplexity_error', $data['error']['message']);
        }

        // Return the full raw response as a string
        return $response_body;
    }
    
    /**
     * Generate long-tail keywords
     */
    public function generate_keywords($topic) {
        $prompt = "Based on the topic '{$topic}', generate a list of 5 related long-tail keywords that are SPECIFICALLY about this topic. The first keyword should be the primary focus keyword that captures the main topic exactly. 

IMPORTANT: Stay focused on '{$topic}' - do not generate keywords about unrelated topics.

Format the output as a JSON array with no additional text or explanation. Example format: [\"primary keyword about topic\", \"related keyword 1\", \"related keyword 2\", \"related keyword 3\", \"related keyword 4\"]";
        
        error_log('ACA Keyword generation topic: ' . $topic);
        
        $response = $this->call_gemini($prompt, 500, 0.5);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Log the raw response for debugging
        error_log('ACA Keywords Raw Response: ' . $response);
        
        // Parse JSON response
        $keywords = json_decode(trim($response), true);
        
        error_log('ACA Keywords JSON decode result: ' . print_r($keywords, true));
        error_log('ACA Keywords JSON last error: ' . json_last_error_msg());
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('ACA Keywords JSON parsing failed, using fallback method');
            // Fallback: extract keywords from text response
            $lines = explode("\n", $response);
            $keywords = array();
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line) && !preg_match('/^[0-9]+\.?\s*$/', $line)) {
                    // Clean up the line
                    $cleaned = preg_replace('/^[0-9]+\.?\s*/', '', $line);
                    $cleaned = trim($cleaned, '"-');
                    if (!empty($cleaned)) {
                        $keywords[] = $cleaned;
                    }
                }
            }
            error_log('ACA Keywords fallback extraction result: ' . print_r($keywords, true));
        }
        
        // Ensure we always return an array
        if (!is_array($keywords)) {
            error_log('ACA Keywords not array, using topic fallback');
            $keywords = array($topic); // fallback to the original topic
        }
        
        // Ensure we have at least one keyword
        if (empty($keywords)) {
            error_log('ACA Keywords empty, using topic fallback');
            $keywords = array($topic);
        }
        
        $final_keywords = array_slice($keywords, 0, 5);
        error_log('ACA Final Keywords: ' . print_r($final_keywords, true));
        
        return $final_keywords;
    }
    
    /**
     * Generate blog post outline
     */
    public function generate_outline($title, $keywords) {
        $primary_topic = $keywords[0]; // This is now the original user input
        
        $prompt = "Create a detailed blog post outline for the topic: '{$primary_topic}'

The outline should include:
- An engaging introduction about {$primary_topic}
- 4-6 main section headings that would work as H2 tags, all directly related to {$primary_topic}
- A strong conclusion about {$primary_topic}

IMPORTANT: Every section must be directly related to '{$primary_topic}'. Stay focused on this exact topic.

Format as a simple numbered list:
1. Introduction
2. [Main section 1 about {$primary_topic}]
3. [Main section 2 about {$primary_topic}]
4. [Main section 3 about {$primary_topic}]
5. [Main section 4 about {$primary_topic}]
6. Conclusion

Write only the outline headings about '{$primary_topic}', no explanations or additional text.";
        
        $response = $this->call_gemini($prompt, 1000, 0.6);
        
        if (is_wp_error($response)) {
            error_log('ACA Outline Generation Error: ' . $response->get_error_message());
            return $response;
        }
        
        error_log('ACA Outline Raw Response: ' . $response);
        
        // Parse the response into an array
        $lines = explode("\n", trim($response));
        $outline = array();
        
        error_log('ACA Outline Lines Count: ' . count($lines));
        
        foreach ($lines as $index => $line) {
            $line = trim($line);
            error_log('ACA Outline Line ' . $index . ': ' . $line);
            if (!empty($line)) {
                // Remove numbering (1., 2., etc.) and clean up
                $heading = preg_replace('/^\d+\.\s*/', '', $line);
                $heading = trim($heading, '[]');
                if (!empty($heading)) {
                    $outline[] = $heading;
                    error_log('ACA Outline Added Heading: ' . $heading);
                }
            }
        }
        
        error_log('ACA Outline Final Result: ' . print_r($outline, true));
        
        // Fallback if parsing fails
        if (empty($outline)) {
            error_log('ACA Outline empty, using fallback');
            $topic_for_fallback = str_replace('-', ' ', $primary_topic);
            $outline = array(
                'Introduction to ' . $topic_for_fallback,
                'Understanding the Basics of ' . $topic_for_fallback,
                'Key Benefits and Applications of ' . $topic_for_fallback, 
                'Best Practices and Implementation of ' . $topic_for_fallback,
                'Common Challenges and Solutions in ' . $topic_for_fallback,
                'Future Outlook and Conclusion for ' . $topic_for_fallback
            );
            error_log('ACA Outline fallback created: ' . print_r($outline, true));
        }
        
        return $outline;
    }
    
    /**
     * Generate full blog post content
     */
    public function generate_content($outline, $primary_keyword, $all_keywords) {
        // Log input parameters for debugging
        error_log('ACA Generate Content - Primary topic (original input): ' . $primary_keyword);
        error_log('ACA Generate Content - All keywords: ' . print_r($all_keywords, true));
        
        // First, do research using Perplexity (if configured) with the original topic
        $research_context = '';
        $image_array = array();
        $image_array = $this->search_pexels_images($primary_keyword, 3);
        $formatted_images = $this->format_image_array_for_prompt($image_array);
        if (!empty($this->perplexity_api_key)) {
            $research_query = "Research comprehensive information about '{$primary_keyword}'. Include current trends, statistics, best practices, expert opinions, and recent developments. Focus on factual, up-to-date information that would be valuable for a blog post. Also find relevant YouTube video links, infographics, charts, and visual content related to this topic.";
            
            error_log('ACA Perplexity research query: ' . $research_query);
            
            $research_data = $this->call_perplexity($research_query);
            
            if (is_wp_error($research_data)) {
                error_log('Perplexity research failed: ' . $research_data->get_error_message());
            } else {
                error_log('Perplexity research successful, data length: ' . strlen($research_data));
                $research_context = "\n\nBased on current research:\n" . $research_data . "\n\n";
            }
        } else {
            error_log('Perplexity API key not configured, skipping research step');
        }
        
        // Convert outline array to readable format instead of JSON
        $outline_text = '';
        if (is_array($outline)) {
            foreach ($outline as $index => $section) {
                $outline_text .= ($index + 1) . ". " . $section . "\n";
            }
        } else {
            $outline_text = $outline;
        }
        
        // Get custom prompt from settings or use default
        $custom_prompt = get_option('aca_custom_content_prompt', '');
        
        error_log('ACA Custom prompt from settings: ' . substr($custom_prompt, 0, 500) . '...');
        error_log('ACA Primary keyword value: ' . $primary_keyword);
        error_log('ACA Research context length: ' . strlen($research_context));
        error_log('ACA Research context start: ' . substr($research_context, 0, 10000) . '...');
        error_log('ACA Formatted images: ' . substr($formatted_images, 0, 200) . '...');
        
        // Define standard formatting requirements that should always be appended
        $formatting_requirements = "

FORMATTING REQUIREMENTS - VERY IMPORTANT:
- Use ONLY clean HTML tags: <h2>, <h3>, <p>, <ul>, <li>, <strong>, <em>, <a>, <img>
- DO NOT use markdown syntax like ```html or ``` 
- DO NOT use code blocks or backticks
- DO NOT wrap the content in any formatting
- End with the last </p> tag
- Return only raw HTML content
- **CRITICAL:** Never use <code> tags in your response - use <strong> for emphasis instead


Return ONLY the clean HTML content. Start immediately with HTML tags, no markdown formatting. And make sure it looks like a complete modern blog post.";
        
        if (!empty($custom_prompt)) {
            // Use custom prompt and replace placeholders (handle both {placeholder} and {$placeholder} formats)
            $prompt = str_replace(
                array(
                    '{primary_keyword}', '{$primary_keyword}',
                    '{research_context}', '{$research_context}', 
                    '{formatted_images}', '{$formatted_images}'
                ),
                array(
                    $primary_keyword, $primary_keyword,
                    $research_context, $research_context,
                    $formatted_images, $formatted_images
                ),
                $custom_prompt
            );
            // Always append formatting requirements to custom prompts
            $prompt .= $formatting_requirements;
            error_log('ACA Using custom content prompt from settings with formatting requirements');
            error_log('ACA Custom prompt after placeholder replacement: ' . substr($prompt, 0, 500) . '...');
        } else {
            // Use default prompt and replace placeholders
            $prompt = str_replace(
                array(
                    '{primary_keyword}', '{$primary_keyword}',
                    '{research_context}', '{$research_context}', 
                    '{formatted_images}', '{$formatted_images}'
                ),
                array(
                    $primary_keyword, $primary_keyword,
                    $research_context, $research_context,
                    $formatted_images, $formatted_images
                ),
                $this->get_default_prompt()
            );
            // Always append formatting requirements to default prompts too
            $prompt .= $formatting_requirements;
            error_log('ACA Using default content prompt');
        }
        
        error_log('ACA Content Generation - Prompt Length: ' . strlen($prompt) . ' characters');
        error_log('ACA Content Generation - Prompt Start: ' . substr($prompt, 0, 50000) . '...');
        error_log('ACA Content Generation - Prompt End: ...' . substr($prompt, -500));
        error_log('ACA Content Generation - Outline Text: ' . $outline_text);
        error_log('ACA Content Generation - Research Context Length: ' . strlen($research_context));
        error_log('ACA Content Generation - Research Context Start: ' . substr($research_context, 0, 10000) . '...');
        
        $response = $this->call_gemini($prompt, 3000, 0.7);
        
        if (is_wp_error($response)) {
            error_log('ACA Content Generation Error: ' . $response->get_error_message());
        } else {
            error_log('ACA Content Generation Success - Content Length: ' . strlen($response));
            error_log('ACA Content Generation - First 10000 chars: ' . substr($response, 0, 10000));

            // Clean up any markdown artifacts that might have slipped through
            $response = $this->clean_markdown_artifacts($response);
            error_log('ACA Content Generation - After cleanup: ' . substr($response, 0, 10000));
        }
        
        return $response;
    }
    
    /**
     * Clean up markdown artifacts from generated content
     */
    private function clean_markdown_artifacts($content) {
        // Remove markdown code blocks
        $content = preg_replace('/```html\s*/', '', $content);
        $content = preg_replace('/```\s*$/', '', $content);
        $content = preg_replace('/```/', '', $content);
        
        // Remove any backticks
        $content = str_replace('`', '', $content);
        
        
        // Remove any leading/trailing whitespace
        $content = trim($content);
        
        

        error_log('ACA Content after markdown cleanup: ' . substr($content, 0, 10000) . '...');
        
        return $content;
    }
    
    /**
     * Format image array for use in AI prompt
     */
    private function format_image_array_for_prompt($images) {
        if (is_wp_error($images) || empty($images)) {
            return 'No images available';
        }
        
        $formatted_images = array();
        
        foreach ($images as $image) {
            $image_info = array();
            
            // For Pexels images
            if (isset($image['src']['large'])) {
                $image_info['url'] = $image['src']['large'];
                $image_info['alt'] = isset($image['alt']) ? $image['alt'] : 'Image';
                $image_info['photographer'] = isset($image['photographer']) ? $image['photographer'] : 'Unknown';
            }
            // For Unsplash images
            elseif (isset($image['urls']['regular'])) {
                $image_info['url'] = $image['urls']['regular'];
                $image_info['alt'] = isset($image['alt_description']) ? $image['alt_description'] : 'Image';
                $image_info['photographer'] = isset($image['user']['name']) ? $image['user']['name'] : 'Unknown';
            }
            
            if (!empty($image_info)) {
                $formatted_images[] = $image_info;
            }
        }
        
        if (empty($formatted_images)) {
            return 'No images available';
        }
        
        // Format as readable text for the AI prompt
        $image_text = "Available images:\n";
        foreach ($formatted_images as $index => $img) {
            $image_text .= ($index + 1) . ". URL: {$img['url']}\n";
            $image_text .= "   Description: {$img['alt']}\n";
            $image_text .= "   Photographer: {$img['photographer']}\n\n";
        }
        
        return $image_text;
    }
    
    /**
     * Search for images using Pexels API
     */
    public function search_pexels_images($query, $per_page = 5) {
        if (empty($this->pexels_api_key)) {
            return new WP_Error('missing_api_key', 'Pexels API key is required');
        }

        $url = 'https://api.pexels.com/v1/search';
        $url = add_query_arg(array(
            'query' => urlencode($query),
            'per_page' => $per_page,
            'orientation' => 'landscape'
        ), $url);
        
        $args = array(
            'headers' => array(
                'Authorization' => $this->pexels_api_key
            ),
            'timeout' => 30
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['photos']) && !empty($data['photos'])) {
            return $data['photos'];
        }
        
        return array();
    }
    
    /**
     * Search for images using Unsplash API
     */
    public function search_unsplash_images($query, $per_page = 5) {
        if (empty($this->unsplash_api_key)) {
            return new WP_Error('missing_api_key', 'Unsplash API key is required');
        }
        
        $url = 'https://api.unsplash.com/search/photos';
        $url = add_query_arg(array(
            'query' => urlencode($query),
            'per_page' => $per_page,
            'orientation' => 'landscape'
        ), $url);
        
        $args = array(
            'headers' => array(
                'Authorization' => 'Client-ID ' . $this->unsplash_api_key
            ),
            'timeout' => 30
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['results']) && !empty($data['results'])) {
            return $data['results'];
        }
        
        return array();
    }
    
    /**
     * Search for images based on configured provider
     */
    public function search_images($query, $per_page = 5) {
        $provider = get_option('aca_image_provider', 'pexels');
        
        if ($provider === 'unsplash') {
            return $this->search_unsplash_images($query, $per_page);
        } else {
            return $this->search_pexels_images($query, $per_page);
        }
    }
}
