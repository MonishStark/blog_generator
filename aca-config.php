<?php
/**
 * AI Blog Generator - Quick Configuration Helper
 * 
 * IMPORTANT: This file is for one-time setup only!
 * Delete this file after configuration is complete for security.
 * 
 * Instructions:
 * 1. Upload this file to your WordPress root directory
 * 2. Visit: yourdomain.com/aca-config.php
 * 3. Follow the instructions
 * 4. Delete this file when done
 */

// Security check - only run if WordPress is loaded
if (!defined('ABSPATH')) {
    // Try to load WordPress
    require_once('./wp-config.php');
    require_once('./wp-load.php');
}

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied. Admin privileges required.');
}

// Handle configuration
if (isset($_POST['configure'])) {
    $gemini_key = sanitize_text_field($_POST['gemini_key']);
    $pexels_key = sanitize_text_field($_POST['pexels_key']);
    
    if (!empty($gemini_key) && !empty($pexels_key)) {
        update_option('aca_gemini_api_key', $gemini_key);
        update_option('aca_pexels_api_key', $pexels_key);
        update_option('aca_image_provider', 'pexels');
        update_option('aca_max_internal_links', 3);
        update_option('aca_max_external_links', 3);
        update_option('aca_auto_save_draft', 1);
        
        $success = true;
    } else {
        $error = "Please provide both API keys.";
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>AI Blog Generator - Quick Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .container { background: #f9f9f9; padding: 30px; border-radius: 8px; border: 1px solid #ddd; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .form-group { margin: 20px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .btn { background: #0073aa; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #005a87; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 AI Blog Generator - Quick Setup</h1>
        
        <div class="warning">
            <strong>⚠️ Security Notice:</strong> Delete this file after configuration is complete!
        </div>

        <?php if (isset($success)): ?>
            <div class="success">
                <strong>✅ Configuration Successful!</strong><br>
                Your API keys have been saved. You can now:
                <ul>
                    <li>Go to <a href="<?php echo admin_url('options-general.php?page=aca-settings'); ?>">Settings > AI Content Architect</a></li>
                    <li>Create a new post and test the plugin</li>
                    <li><strong>Delete this configuration file for security</strong></li>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error">
                <strong>❌ Error:</strong> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (!isset($success)): ?>
            <form method="post">
                <div class="form-group">
                    <label for="gemini_key">Google Gemini API Key:</label>
                    <input type="text" id="gemini_key" name="gemini_key" placeholder="Enter your Gemini API key" required>
                    <small>Get it from: <a href="https://ai.google.dev/gemini-api/docs/api-key" target="_blank">Google AI Studio</a></small>
                </div>

                <div class="form-group">
                    <label for="pexels_key">Pexels API Key:</label>
                    <input type="text" id="pexels_key" name="pexels_key" placeholder="Enter your Pexels API key" required>
                    <small>Get it from: <a href="https://www.pexels.com/api/" target="_blank">Pexels API</a></small>
                </div>

                <div class="form-group">
                    <button type="submit" name="configure" class="btn">Configure Plugin</button>
                </div>
            </form>

            <h3>📋 Pre-filled Values (for your reference):</h3>
            <div class="code">
                <strong>Gemini API Key:</strong> gemini_api_key<br>
                <strong>Pexels API Key:</strong> pexels_api_key
            </div>
        <?php endif; ?>

        <h3>📚 Next Steps:</h3>
        <ol>
            <li>Complete this configuration</li>
            <li>Go to Posts > Add New</li>
            <li>Find "AI Content Architect" meta box</li>
            <li>Enter a topic and click "Generate Blog Post"</li>
            <li>Watch the magic happen! ✨</li>
        </ol>

        <h3>🛠️ Plugin Features:</h3>
        <ul>
            <li>✅ AI-powered content generation with Google Gemini</li>
            <li>✅ Automatic keyword research and SEO optimization</li>
            <li>✅ Royalty-free image integration from Pexels</li>
            <li>✅ Smart internal and external linking</li>
            <li>✅ Complete blog post workflow automation</li>
        </ul>
    </div>
</body>
</html>
