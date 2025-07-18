# TLDRWP - AI-Powered Post Summaries

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/tldrwp.svg)](https://wordpress.org/plugins/tldrwp/)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/tldrwp.svg)](https://wordpress.org/plugins/tldrwp/)
[![WordPress Plugin Rating](https://img.shields.io/wordpress/plugin/r/tldrwp.svg)](https://wordpress.org/plugins/tldrwp/)
[![WordPress](https://img.shields.io/badge/WordPress-5.0+-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4+-green.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-orange.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

> Automatically generate AI-powered TL;DR summaries for your WordPress posts using the AI Services plugin. Perfect for improving reader engagement and content accessibility.

## 🚀 Features

- **🤖 AI-Powered Summaries**: Leverages the AI Services plugin to generate intelligent, contextual summaries
- **⚡ Automatic Generation**: Creates TL;DR summaries automatically when posts are published or updated
- **🎨 Customizable Display**: Choose where and how summaries appear on your site
- **🔌 Multiple AI Platforms**: Works with any AI platform supported by the AI Services plugin
- **🔧 Clean Integration**: Seamlessly integrates with your existing WordPress workflow
- **⚡ Performance Optimized**: Lightweight and efficient with conditional loading

## 📋 Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **AI Services Plugin**: [Download here](https://wordpress.org/plugins/ai-services/)

## 🛠️ Installation

### Method 1: WordPress Admin (Recommended)
1. Go to **Plugins → Add New** in your WordPress admin
2. Search for "TLDRWP"
3. Click **Install Now** and then **Activate**
4. Install and activate the [AI Services plugin](https://wordpress.org/plugins/ai-services/)
5. Configure your AI platform in AI Services settings
6. Go to **Settings → Reading** to configure TLDRWP options
7. Test the connection and start generating summaries!

### Method 2: Manual Installation
1. Download the plugin ZIP file from this repository
2. Upload the `tldrwp` folder to `/wp-content/plugins/`
3. Activate the plugin through **Plugins → Installed Plugins**
4. Follow steps 4-7 from Method 1

## 🔧 Configuration

### Setting Up AI Services
1. Install and activate the [AI Services plugin](https://wordpress.org/plugins/ai-services/)
2. Go to **AI Services → Settings**
3. Choose your preferred AI platform (OpenAI, Anthropic, Google AI, etc.)
4. Enter your API credentials
5. Save settings

### Configuring TLDRWP
1. Navigate to **Settings → Reading**
2. Scroll down to the **TL;DR Settings** section
3. Choose where to display summaries:
   - Before content
   - After content
   - Both locations
4. Customize the summary title and styling
5. Test your AI connection
6. Save settings

## 📖 How It Works

```
User Publishes Post → TLDRWP Detects New Content → AI Services Plugin Processes Content → AI Platform Generates Summary → Summary Stored in Database → Summary Displayed on Frontend
```

## 🎯 Use Cases

| Use Case | Description | Benefits |
|----------|-------------|----------|
| **Bloggers** | Improve reader engagement | Higher time-on-page, better retention |
| **Content Creators** | Increase content accessibility | Better user experience, wider audience |
| **News Sites** | Quick content overviews | Faster information consumption |
| **Educational Sites** | Content summaries | Better learning outcomes |
| **E-commerce** | Product description summaries | Improved conversion rates |

## 🔍 Screenshots

### Admin Settings
![TLDRWP Settings](screenshots/admin-settings.png)

### Frontend Display
![Frontend Summary](screenshots/frontend-display.png)

### AI Services Integration
![AI Services Config](screenshots/ai-services.png)

## ❓ Frequently Asked Questions

### What is a TL;DR summary?
TL;DR stands for "Too Long; Didn't Read." It's a brief summary that captures the main points of a longer piece of content, helping readers quickly understand what the content is about.

### Do I need the AI Services plugin?
Yes, TLDRWP requires the AI Services plugin to function. This plugin provides the AI integration capabilities that TLDRWP uses to generate summaries.

### Which AI platforms are supported?
TLDRWP works with any AI platform supported by the AI Services plugin, including:
- OpenAI (GPT-3.5, GPT-4)
- Anthropic (Claude)
- Google AI (Gemini)
- And many others

### Can I customize how summaries appear?
Yes! You can choose where summaries are displayed and customize their appearance through the plugin settings.

### Is my content sent to AI services?
Yes, your post content is sent to the AI service you've configured to generate summaries. Please review your chosen AI service's privacy policy.

### Can I edit generated summaries?
Currently, summaries are generated automatically. Future versions may include manual editing capabilities.

### How can I track TL;DR generations in analytics?
TLDRWP dispatches a custom JavaScript event `tldrwp_generated` when a summary is successfully generated. You can listen for this event to track generations in Google Analytics, Plausible, or any other analytics tool.

**Google Analytics 4:**
```javascript
document.addEventListener('tldrwp_generated', function(e) {
    gtag('event', 'tldr_generated', {
        article_id: e.detail.articleId,
        article_title: e.detail.articleTitle
    });
});
```

**Plausible Analytics:**
```javascript
document.addEventListener('tldrwp_generated', function(e) {
    plausible('TL;DR Generated', {
        props: {
            article_id: e.detail.articleId,
            article_title: e.detail.articleTitle
        }
    });
});
```

The event includes: `articleId`, `articleTitle`, `timestamp`, and `platform` data.

## 🏗️ Architecture

TLDRWP follows a clean, modular architecture:

```
tldrwp/
├── includes/
│   ├── class-tldrwp.php          # Main plugin class
│   ├── class-tldrwp-admin.php    # Admin functionality
│   ├── class-tldrwp-public.php   # Frontend functionality
│   ├── class-tldrwp-ai-service.php # AI service integration
│   └── class-tldrwp-settings.php # Settings management
├── assets/
│   ├── css/
│   └── js/
├── tldrwp.php                    # Main plugin file
└── readme.txt                    # WordPress.org readme
```

## 🧪 Testing

To test the plugin:

1. **Enable Debug Mode**: Add `define('WP_DEBUG', true);` to your `wp-config.php`
2. **Check Logs**: Monitor `wp-content/debug.log` for any errors
3. **Test Connection**: Use the "Test Connection" button in settings
4. **Create Test Post**: Publish a new post and check for summary generation

## 🤝 Contributing

We welcome contributions! Here's how you can help:

1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/amazing-feature`)
3. **Commit** your changes (`git commit -m 'Add amazing feature'`)
4. **Push** to the branch (`git push origin feature/amazing-feature`)
5. **Open** a Pull Request

### Development Setup
```bash
# Clone the repository
git clone https://github.com/mattcromwell/tldrwp.git

# Navigate to the plugin directory
cd tldrwp

# Install dependencies (if any)
composer install
```

## 📝 Changelog

### [1.0.0] - 2024-01-XX
#### Added
- Initial release
- AI-powered summary generation
- Integration with AI Services plugin
- Customizable display options
- Admin settings page
- Test connection functionality
- Clean, modular architecture

#### Technical
- Class-based architecture with component separation
- Conditional loading for performance optimization
- Proper WordPress coding standards compliance
- Comprehensive error handling and debugging

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- [AI Services Plugin](https://wordpress.org/plugins/ai-services/) for AI integration capabilities
- WordPress community for best practices and standards
- All contributors and beta testers

## 📞 Support

- **Documentation**: [Plugin Documentation](https://github.com/mattcromwell/tldrwp/wiki)
- **Issues**: [GitHub Issues](https://github.com/mattcromwell/tldrwp/issues)
- **WordPress.org**: [Plugin Page](https://wordpress.org/plugins/tldrwp/)
- **Email**: support@mattcromwell.com

---

**Made with ❤️ by [Matt Cromwell](https://mattcromwell.com)**

[![WordPress](https://img.shields.io/badge/WordPress-Plugin-blue.svg)](https://wordpress.org/plugins/tldrwp/)
[![GitHub](https://img.shields.io/badge/GitHub-Repository-black.svg)](https://github.com/mattcromwell/tldrwp)
[![Website](https://img.shields.io/badge/Website-mattcromwell.com-green.svg)](https://mattcromwell.com)
