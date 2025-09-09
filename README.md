<!-- @format -->

# AI Blog Generator - WordPress Plugin

A powerful WordPress plugin that automates the creation of SEO-optimized blog posts using AI technology. Generate complete, well-structured, and media-rich articles from simple topics.

## 🚀 Features

- **AI-Powered Content Generation**: Leverages OpenAI's GPT models for high-quality content
- **SEO Optimization**: Automatic keyword research, title optimization, and content structure
- **Image Integration**: Sources royalty-free images from Pexels or Unsplash
- **Smart Linking**: Automatic internal and external link insertion
- **WordPress Integration**: Seamless meta box in post editor
- **Progress Tracking**: Real-time generation progress with detailed steps
- **Draft Management**: Auto-saves as drafts for review before publishing

## 📋 Requirements

- WordPress 5.0+
- PHP 7.4+
- OpenAI API Key
- Pexels OR Unsplash API Key

## 🛠 Installation

### From WordPress Admin

1. Download the plugin zip file
2. Go to **Plugins > Add New > Upload Plugin**
3. Select the zip file and click **Install Now**
4. Activate the plugin
5. Configure API keys in **Settings > AI Blog Generator**

### Manual Installation

1. Extract plugin files to `/wp-content/plugins/ai-blog-generator/`
2. Activate through the WordPress admin
3. Configure your API keys

## ⚙️ Configuration

### API Keys Setup

1. **OpenAI API Key**

   - Visit: https://platform.openai.com/api-keys
   - Create new secret key
   - Copy to plugin settings

2. **Pexels API Key** (if using Pexels)

   - Visit: https://www.pexels.com/api/
   - Register for free API access
   - Copy API key to plugin settings

3. **Unsplash API Key** (if using Unsplash)
   - Visit: https://unsplash.com/developers
   - Create new application
   - Copy access key to plugin settings

### Plugin Settings

Navigate to **Settings > AI Content Architect** to configure:

- **API Configuration**

  - OpenAI API Key
  - Image provider (Pexels/Unsplash)
  - Respective image API keys

- **Content Settings**
  - Maximum internal links (0-10)
  - Maximum external links (0-10)
  - Auto-save as draft option

## 🎯 Usage

### Generating Content

1. Create a new post or edit existing
2. Locate the **AI Content Architect** meta box (sidebar)
3. Enter your blog post topic
4. Click **Generate Blog Post**
5. Monitor progress through 7 steps
6. Review generated content summary
7. Click **Apply to Post** to insert content

### Generation Process

The plugin follows a comprehensive workflow:

1. **Keyword Research**: Analyzes topic and generates 5 related long-tail keywords
2. **Title & Outline**: Creates SEO-optimized title and content structure
3. **Content Creation**: Writes comprehensive, engaging article content
4. **Internal Linking**: Scans existing posts and adds relevant internal links
5. **External Linking**: Adds authoritative external references
6. **Featured Image**: Sources and sets appropriate featured image
7. **Content Images**: Adds relevant images throughout the article

## 🏗 Plugin Architecture

```
ai-content-architect/
├── ai-content-architect.php     # Main plugin file
├── README.txt                   # WordPress repository readme
├── includes/                    # Core functionality
│   ├── class-aca-api-handler.php
│   ├── class-aca-content-generator.php
│   ├── class-aca-media-handler.php
│   └── class-aca-link-handler.php
├── admin/                       # Admin functionality
│   ├── class-aca-admin.php
│   ├── class-aca-settings.php
│   └── class-aca-meta-box.php
├── public/                      # Public functionality
│   └── class-aca-public.php
└── assets/                      # Static assets
    ├── css/
    │   └── admin.css
    └── js/
        ├── admin.js
        └── meta-box.js
```

## 🔧 Development

### Class Structure

#### Core Classes

- **`ACA_API_Handler`**: Manages all external API calls (OpenAI, Pexels, Unsplash)
- **`ACA_Content_Generator`**: Orchestrates the content generation workflow
- **`ACA_Media_Handler`**: Handles image search, download, and WordPress integration
- **`ACA_Link_Handler`**: Processes internal and external link insertion

#### Admin Classes

- **`ACA_Admin`**: Main admin functionality and AJAX handlers
- **`ACA_Settings`**: Settings page and configuration management
- **`ACA_Meta_Box`**: Post editor meta box and UI

#### Public Classes

- **`ACA_Public`**: Frontend functionality and content filters

### Key Methods

#### API Handler

```php
// Generate keywords from topic
$keywords = $api_handler->generate_keywords($topic);

// Create content outline
$outline = $api_handler->generate_outline($title, $keywords);

// Generate full content
$content = $api_handler->generate_content($outline, $primary_keyword, $keywords);

// Search for images
$images = $api_handler->search_images($query);
```

#### Content Generator

```php
// Complete generation workflow
$result = $content_generator->generate_post($topic);

// Create new post
$post_id = $content_generator->create_post($data, $is_draft);

// Update existing post
$post_id = $content_generator->update_post($post_id, $data);
```

### Hooks and Filters

#### Admin Hooks

- `add_meta_boxes`: Adds meta box to post editor
- `wp_ajax_aca_generate_content`: Handles content generation AJAX
- `wp_ajax_aca_apply_content`: Handles content application AJAX
- `admin_enqueue_scripts`: Enqueues admin scripts/styles

#### Public Hooks

- `the_content`: Adds generation notice to AI-generated posts

### AJAX Endpoints

#### Content Generation

```javascript
// Endpoint: wp_ajax_aca_generate_content
// Purpose: Generate complete blog post from topic
// Response: Generated content data and transient key
```

#### Content Application

```javascript
// Endpoint: wp_ajax_aca_apply_content
// Purpose: Apply generated content to WordPress post
// Response: Success status and post ID
```

## 🔒 Security Features

- **Nonce Verification**: All AJAX requests use WordPress nonces
- **Capability Checks**: User permission validation
- **Input Sanitization**: All user inputs are sanitized
- **File Upload Security**: Secure image downloading and validation
- **API Key Encryption**: Secure storage of API credentials

## 🎨 Customization

### Custom Hooks

```php
// Filter generated content before saving
add_filter('aca_generated_content', 'my_custom_content_filter');

// Modify keyword generation
add_filter('aca_generated_keywords', 'my_keyword_modifier');

// Customize image selection
add_filter('aca_selected_images', 'my_image_selector');
```

### CSS Customization

Override plugin styles by adding to your theme:

```css
/* Customize meta box appearance */
#aca-meta-box {
	/* Your custom styles */
}

/* Style generation progress */
.aca-progress-bar {
	/* Your custom styles */
}
```

## 📊 Performance Considerations

- **Caching**: Generated content is cached in transients
- **Lazy Loading**: Images are processed asynchronously
- **Rate Limiting**: Built-in delays to respect API limits
- **Error Handling**: Comprehensive error recovery
- **Timeout Management**: Configurable request timeouts

## 🧪 Testing

### Manual Testing Checklist

- [ ] Plugin activation/deactivation
- [ ] Settings page functionality
- [ ] API key validation
- [ ] Content generation workflow
- [ ] Image integration
- [ ] Link processing
- [ ] Error handling
- [ ] Mobile responsiveness

### API Testing

Test with various topics:

- Technical subjects
- Creative topics
- How-to guides
- Industry-specific content

## 🐛 Troubleshooting

### Common Issues

1. **"No keywords generated"**

   - Check OpenAI API key validity
   - Verify internet connection
   - Try simpler topic

2. **"No images found"**

   - Verify image provider API key
   - Check topic specificity
   - Try alternative keywords

3. **"Generation timeout"**
   - Check server timeout settings
   - Verify API response times
   - Reduce content complexity

### Debug Mode

Enable WordPress debug mode:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check logs in `/wp-content/debug.log`

## 📝 Changelog

### Version 1.0.0

- Initial release
- Complete AI content generation workflow
- OpenAI integration
- Pexels/Unsplash image integration
- WordPress post editor meta box
- Settings page with API configuration

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## 🙏 Credits

- **OpenAI** - AI content generation
- **Pexels** - Royalty-free images
- **Unsplash** - Royalty-free images
- **WordPress Community** - Framework and inspiration

## 📞 Support

- **Documentation**: Full documentation available
- **Issues**: Report bugs via GitHub issues
- **Feature Requests**: Submit enhancement requests
- **Community**: WordPress plugin support forum

---

**AI Content Architect** - Revolutionizing WordPress content creation with AI technology.
