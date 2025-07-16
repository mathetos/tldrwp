# AI Platform Selection Feature

## Overview

The TLDRWP plugin now supports selecting which AI platform to use for generating TL;DR summaries when multiple AI platforms have API keys configured in the AI Services plugin.

## How It Works

### Automatic Detection
- The plugin automatically detects which AI platforms have valid API keys configured
- If only one platform is available, no selection field is shown (uses the available platform)
- If multiple platforms are available, a dropdown selection field appears in Settings → Reading

### Platform Selection
- Users can choose between available platforms: Anthropic (Claude), Google (Gemini), or OpenAI (GPT)
- The selection is saved in the WordPress options table
- If the selected platform becomes unavailable, the plugin falls back to the first available platform

### Settings Location
- **Settings → Reading → TL;DR Settings**
- The "AI Platform for TL;DR" field only appears when multiple platforms have API keys

## Technical Implementation

### New Functions Added

#### `tldrwp_get_available_ai_platforms()`
Returns an array of available AI platforms with their slugs and user-friendly names.

```php
$platforms = tldrwp_get_available_ai_platforms();
// Returns: ['anthropic' => 'Anthropic (Claude)', 'google' => 'Google (Gemini, Imagen)', ...]
```

#### `tldrwp_get_selected_ai_platform()`
Returns the currently selected AI platform slug, or the first available platform if none is selected.

```php
$platform = tldrwp_get_selected_ai_platform();
// Returns: 'anthropic', 'google', 'openai', or empty string
```

### Settings Integration

#### New Setting
- **Option Name**: `tldrwp_settings['selected_ai_platform']`
- **Default Value**: Empty string (auto-selects first available)
- **Validation**: Only allows selection of platforms that have API keys configured

#### Settings Display Logic
```php
$available_platforms = tldrwp_get_available_ai_platforms();
if ( count( $available_platforms ) > 1 ) {
    // Show platform selection field
    add_settings_field( 'tldrwp_ai_platform', ... );
}
```

### AI Service Integration

The `tldrwp_call_ai_service()` function now uses the selected platform:

```php
$selected_platform = tldrwp_get_selected_ai_platform();
$service = $ai_services->get_available_service( $selected_platform );
```

## User Experience

### Single Platform Scenario
- When only one AI platform has API keys configured
- No platform selection field is shown
- Plugin automatically uses the available platform
- Settings page shows: "✅ AI service is configured and ready to use. (Platform Name)"

### Multiple Platforms Scenario
- When multiple AI platforms have API keys configured
- Platform selection dropdown appears in settings
- Settings page shows: "✅ Multiple AI platforms are available. You can choose which one to use for TL;DR generation below."
- Currently selected platform is displayed: "Currently selected: **Platform Name**"

### Platform Unavailable Scenario
- If the selected platform becomes unavailable (API key removed/invalid)
- Plugin automatically falls back to the first available platform
- Settings are updated to reflect the change

## Testing

### Test File
Use the included test file to verify functionality:
```
wp-content/plugins/tldrwp/test-platform-selection.php
```

### Test Scenarios
1. **Single Platform**: Configure only one AI platform and verify no selection field appears
2. **Multiple Platforms**: Configure multiple AI platforms and verify selection field appears
3. **Platform Selection**: Change the selected platform and verify TL;DR generation uses the correct platform
4. **Fallback**: Remove API key for selected platform and verify fallback to available platform

## Compatibility

- **Backward Compatible**: Existing installations continue to work without changes
- **AI Services Plugin**: Requires AI Services plugin to be installed and active
- **WordPress Version**: Compatible with WordPress 6.0+
- **PHP Version**: Requires PHP 7.4+

## Security

- Platform selection is sanitized using `sanitize_text_field()`
- Only allows selection of platforms that have valid API keys
- Validates platform availability before using it
- Uses WordPress nonces for AJAX requests

## Future Enhancements

Potential improvements for future versions:
- Platform-specific prompt customization
- Platform performance metrics
- Automatic platform switching based on availability
- Platform-specific model selection 