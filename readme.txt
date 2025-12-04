=== TLDRWP - AI-Powered Post Summaries ===
Contributors: mattcromwell
Tags: ai, artificial intelligence, content, summary, tldr, reading, automation, seo
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically generate AI-powered TL;DR summaries for your WordPress posts using the WordPress AI plugin. Perfect for improving reader engagement and content accessibility.

== Description ==

**TLDRWP** automatically generates concise, AI-powered summaries for your WordPress posts, helping readers quickly understand your content and improving engagement rates.

= Key Features =

* **AI-Powered Summaries**: Leverages the WordPress AI plugin to generate intelligent, contextual summaries
* **Automatic Generation**: Creates TL;DR summaries automatically when posts are published or updated
* **Customizable Display**: Choose where and how summaries appear on your site
* **Multiple AI Platforms**: Works with any AI platform supported by the WordPress AI plugin (OpenAI, Anthropic, Google AI, etc.)
* **Automatic Provider Selection**: WordPress AI plugin automatically selects the best available provider and model
* **Clean Integration**: Seamlessly integrates with your existing WordPress workflow
* **Performance Optimized**: Lightweight and efficient with conditional loading
* **Developer-Friendly**: Comprehensive action hooks and filters for customization

= How It Works =

1. **Install & Activate**: Install TLDRWP and the WordPress AI plugin
2. **Configure AI**: Set up your AI credentials in WordPress AI plugin settings
3. **Generate Summaries**: TL;DR summaries are automatically created for new and updated posts
4. **Display**: Summaries appear in your chosen location with customizable styling

= Perfect For =

* **Bloggers** who want to improve reader engagement
* **Content Creators** looking to increase time-on-page
* **News Sites** needing quick content overviews
* **Educational Sites** requiring content summaries
* **Any WordPress site** wanting to enhance content accessibility

= Requirements =

* WordPress 6.0 or higher
* PHP 7.4 or higher
* WordPress AI plugin - [Download here](https://github.com/WordPress/ai)

= Installation =

1. Upload the `tldrwp` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Install and activate the WordPress AI plugin
4. Configure your AI credentials in Settings → AI Credentials
5. Go to Settings → Reading to configure TLDRWP options
6. Test the connection and start generating summaries!

= Frequently Asked Questions =

= What is a TL;DR summary? =

TL;DR stands for "Too Long; Didn't Read." It's a brief summary that captures the main points of a longer piece of content, helping readers quickly understand what the content is about.

= Do I need the WordPress AI plugin? =

Yes, TLDRWP requires the WordPress AI plugin to function. This plugin provides the AI integration capabilities that TLDRWP uses to generate summaries.

= Which AI platforms are supported? =

TLDRWP works with any AI platform supported by the WordPress AI plugin, including OpenAI, Anthropic, Google AI, and many others. The WordPress AI plugin automatically selects the best available provider and model for optimal performance.

= Can I customize how summaries appear? =

Yes! You can choose where summaries are displayed and customize their appearance through the plugin settings.

= Is my content sent to AI services? =

Yes, your post content is sent to the AI service you've configured to generate summaries. Please review your chosen AI service's privacy policy.

= Can I edit generated summaries? =

Currently, summaries are generated automatically. Future versions may include manual editing capabilities.

= Can developers customize the TL;DR output? =

Yes! TLDRWP provides comprehensive action hooks and filters for developers to customize the output. See the ACTION_HOOKS.md file in the plugin directory for complete documentation and examples.

**Available Action Hooks:**
* `tldr_before_summary_heading` - Add content before the heading
* `tldr_after_summary_heading` - Add content after the heading  
* `tldr_before_summary_copy` - Add content before the summary
* `tldr_after_summary_copy` - Add content after the summary
* `tldr_summary_footer` - Add content at the bottom

**Available Filter Hooks:**
* `tldrwp_response` - Modify the AI response
* `tldrwp_heading` - Change the heading text
* `tldrwp_summary_html` - Customize the entire HTML structure

= How can I track TL;DR generations in analytics? =

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
    plausible('TLDR Generated', {
        props: {
            article_id: e.detail.articleId,
            article_title: e.detail.articleTitle
        }
    });
});
```

The event includes: `articleId`, `articleTitle`, `timestamp`, and `platform` data.

= Changelog =

= 0.1.0 =
* Initial release
* AI-powered summary generation
* Integration with WordPress AI plugin
* Customizable display options
* Admin settings page
* Test connection functionality
* Clean, modular architecture
* Automatic provider and model selection via WordPress AI Client
* Styled settings wrapper for better UI

= Upgrade Notice =

= 0.1.0 =
Initial release of TLDRWP - AI-powered post summaries for WordPress.

== Installation ==

1. Upload the `tldrwp` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Install and activate the WordPress AI plugin
4. Configure your AI credentials in Settings → AI Credentials
5. Go to Settings → Reading to configure TLDRWP options
6. Test the connection and start generating summaries!

== Frequently Asked Questions ==

= What is a TL;DR summary? =

TL;DR stands for "Too Long; Didn't Read." It's a brief summary that captures the main points of a longer piece of content, helping readers quickly understand what the content is about.

= Do I need the WordPress AI plugin? =

Yes, TLDRWP requires the WordPress AI plugin to function. This plugin provides the AI integration capabilities that TLDRWP uses to generate summaries.

= Which AI platforms are supported? =

TLDRWP works with any AI platform supported by the WordPress AI plugin, including OpenAI, Anthropic, Google AI, and many others. The WordPress AI plugin automatically selects the best available provider and model for optimal performance.

= Can I customize how summaries appear? =

Yes! You can choose where summaries are displayed and customize their appearance through the plugin settings.

= Is my content sent to AI services? =

Yes, your post content is sent to the AI service you've configured to generate summaries. Please review your chosen AI service's privacy policy.

= Can I edit generated summaries? =

Currently, summaries are generated automatically. Future versions may include manual editing capabilities.

== Screenshots ==

1. TLDRWP settings page in WordPress admin
2. Example of a TL;DR summary displayed on a blog post
3. WordPress AI plugin integration configuration

== Changelog ==

= 0.1.0 =
* Initial release
* AI-powered summary generation
* Integration with WordPress AI plugin
* Customizable display options
* Admin settings page
* Test connection functionality
* Clean, modular architecture
* Automatic provider and model selection via WordPress AI Client
* Styled settings wrapper for better UI

== Upgrade Notice ==

= 0.1.0 =
Initial release of TLDRWP - AI-powered post summaries for WordPress. 