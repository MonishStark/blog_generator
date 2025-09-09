<!-- @format -->

# AI Content Architect - Quick Installation Guide

## 📦 Installation Steps

### Method 1: WordPress Admin Upload

1. **Prepare Plugin Files**

   - Zip the entire `ai-content-architect` folder
   - Name it `ai-content-architect.zip`

2. **Upload to WordPress**
   - Go to WordPress Admin > Plugins > Add New
   - Click "Upload Plugin"
   - Choose the zip file
   - Click "Install Now"
   - Click "Activate Plugin"

### Method 2: FTP Upload

1. **Upload Files**

   - Upload the `ai-content-architect` folder to `/wp-content/plugins/`
   - Ensure all files are in the correct structure

2. **Activate Plugin**
   - Go to WordPress Admin > Plugins
   - Find "AI Content Architect"
   - Click "Activate"

## ⚙️ Configuration

### Step 1: Access Settings

- Go to **Settings > AI Content Architect**

### Step 2: Enter API Keys

**Google Gemini API Key:**

```
AIzaSyCwuOBL349IwyWa71IuzurQfN8ZPOeEjNg
```

**Pexels API Key:**

```
IfEBn6P0lL7AoLMIdypwZsXfa2PEaxmhRHQUzcPnnksKXMMZgogfA388
```

### Step 3: Configure Settings

- **Image Provider**: Pexels
- **Max Internal Links**: 3
- **Max External Links**: 3
- **Auto Save as Draft**: ✅ Enabled

### Step 4: Save Configuration

- Click "Save Settings"

## 🧪 Test the Plugin

1. **Create New Post**

   - Go to Posts > Add New

2. **Use AI Content Architect**

   - Find the meta box in the sidebar
   - Enter a topic: "Benefits of Regular Exercise"
   - Click "Generate Blog Post"

3. **Review Results**
   - Wait for generation to complete (30-60 seconds)
   - Review the generated content
   - Click "Apply to Post"

## 📁 Plugin File Structure

```
ai-content-architect/
├── ai-content-architect.php          # Main plugin file
├── README.txt                        # WordPress repository readme
├── API-SETUP-GUIDE.md               # This setup guide
├── includes/                         # Core functionality
│   ├── class-aca-api-handler.php     # Gemini & Image APIs
│   ├── class-aca-content-generator.php # Content generation workflow
│   ├── class-aca-media-handler.php   # Image handling
│   └── class-aca-link-handler.php    # Link processing
├── admin/                            # Admin functionality
│   ├── class-aca-admin.php          # Admin core
│   ├── class-aca-settings.php       # Settings page
│   └── class-aca-meta-box.php       # Post editor integration
├── public/                           # Public functionality
│   └── class-aca-public.php         # Frontend features
└── assets/                           # CSS & JavaScript
    ├── css/admin.css                 # Admin styles
    └── js/
        ├── admin.js                  # Admin functionality
        └── meta-box.js              # Meta box interactions
```

## ✅ Requirements Check

Before installation, ensure:

- [ ] WordPress 5.0 or higher
- [ ] PHP 7.4 or higher
- [ ] Active internet connection
- [ ] Google Gemini API key (✅ provided)
- [ ] Pexels API key (✅ provided)

## 🔧 Troubleshooting

### Plugin Won't Activate

- Check file permissions (755 for folders, 644 for files)
- Ensure all plugin files uploaded correctly
- Check WordPress error logs

### Settings Won't Save

- Verify user has admin permissions
- Check for conflicting plugins
- Clear any caching plugins

### Generation Fails

- Verify API keys are correct
- Check API quotas and billing
- Try simpler topics first

## 📞 Support

If you encounter issues:

1. Check the WordPress error logs
2. Verify API keys are active
3. Test with different topics
4. Ensure internet connectivity

## 🎉 Success Indicators

You'll know it's working when:

- ✅ Settings page saves without errors
- ✅ Meta box appears in post editor
- ✅ No "configuration required" warnings
- ✅ Test generation completes successfully
- ✅ Generated content appears in post editor

---

**Ready to generate amazing content with AI!** 🚀
