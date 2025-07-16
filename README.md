# TLDRWP - AI-Powered TL;DR Generator

A WordPress plugin that automatically adds a beautiful TL;DR button to your content, generating AI-powered summaries for your readers. Perfect for long-form content, blog posts, and articles.

## Features

- **Automatic Button Injection**: TL;DR buttons appear automatically on enabled post types
- **Smart Content Detection**: Automatically extracts and summarizes your post content
- **Customizable Prompts**: Set default prompts and customize per-page
- **Beautiful UI**: Modern, responsive design with smooth animations
- **Loading States**: Visual feedback during AI processing
- **Settings Integration**: Configure everything from WordPress Reading settings
- **AI Services Integration**: Works with any AI provider supported by the AI Services plugin

## Installation

1. Install and activate the **AI Services** plugin
2. Upload this plugin to your WordPress `wp-content/plugins` directory
3. Activate the plugin
4. Configure settings in **Settings > Reading > TL;DR Settings**

## Configuration

### Settings Page
- **Enable TL;DR on Post Types**: Choose which post types should display the TL;DR button
- **Default TL;DR Prompt**: Set the default prompt used for AI generation
- **Button Text**: Customize the button title and description

### Per-Page Customization
- Add custom prompts for individual posts/pages (coming soon)
- Override default settings on a per-content basis

## Usage

Once configured, the TL;DR button will automatically appear at the top of your content on enabled post types. Users can:

1. Click the "Generate TL;DR" button
2. Watch the loading animation while AI processes the content
3. View the generated summary with smooth transitions
4. Read the concise summary of your content

## Developer Integration

Developers can hook into the `tldrwp_generate_ai_response` filter to integrate with custom AI services:

```php
add_filter( 'tldrwp_generate_ai_response', function( $response, $prompt ) {
    // Your AI service integration here
    return $ai_response;
}, 10, 2 );
```

## File Structure

```
tldrwp/
├── assets/
│   ├── js/frontend.js      # Frontend JavaScript
│   └── css/frontend.css    # Frontend styles
├── blocks/                 # Legacy block (maintained for compatibility)
├── tldrwp.php             # Main plugin file
└── README.md
```

## Requirements

- WordPress 6.0+
- PHP 7.4+
- AI Services plugin (for AI functionality)

## Troubleshooting

### Common Issues

**1. "No AI service is configured" Error**
- **Cause**: The AI Services plugin is active but no AI provider is set up
- **Solution**: Go to **Settings > AI Services** and configure your preferred AI provider (OpenAI, Claude, etc.)

**2. "AI Services plugin is not active" Error**
- **Cause**: The AI Services plugin is not installed or activated
- **Solution**: Install and activate the AI Services plugin from the WordPress plugin directory

**3. "AI service returned an empty response" Error**
- **Cause**: API key is invalid, quota exceeded, or network issues
- **Solution**: 
  - Check your API key in AI Services settings
  - Verify your account has sufficient credits
  - Check your server's internet connection

**4. Button doesn't appear on posts**
- **Cause**: Post type not enabled in settings
- **Solution**: Go to **Settings > Reading > TL;DR Settings** and enable the desired post types

**5. "Security check failed" Error**
- **Cause**: Nonce verification failed
- **Solution**: Refresh the page and try again

### Debug Mode

Enable WordPress debug mode to see detailed error logs:

1. Add to `wp-config.php`:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

2. Check the debug log at `wp-content/debug.log` for TLDRWP-specific errors

### Getting Help

If you're still experiencing issues:

1. Check the browser console for JavaScript errors
2. Verify your AI Services plugin configuration
3. Test with a simple prompt to isolate the issue
4. Check your server's error logs
