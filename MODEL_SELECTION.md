# AI Model Selection Feature

## Overview

The TLDRWP plugin now supports selecting which AI model to use for generating TL;DR summaries. This feature works in conjunction with the platform selection and allows users to choose specific models from their selected AI platform.

## How It Works

### Model Detection
- The plugin automatically detects all available models for each AI platform
- Uses the AI Services plugin's built-in capability filtering system
- Only models with `TEXT_GENERATION` capability are shown (properly filtered for TL;DR use case)
- Models are displayed with their user-friendly names when available
- Ensures only models actually designed for text generation are listed

### Model Selection Logic
- Users can choose a specific model or leave it as "Auto-select best model"
- When "Auto-select" is chosen, the AI Services plugin uses its built-in model preference logic
- The selected model is saved in WordPress options and persists across sessions

### Dynamic Model Loading
- When the platform selection changes, the model dropdown automatically updates
- Models are loaded via AJAX to ensure real-time availability
- Loading states are shown during model fetching

## Settings Interface

### Model Selection Field
- **Location**: Settings → Reading → TL;DR Settings
- **Visibility**: Only appears when at least one AI platform is available
- **Behavior**: Updates dynamically when platform selection changes

### Field Options
- **Auto-select best model**: Uses the platform's recommended model (default)
- **Specific models**: Lists all available text generation models for the selected platform

## Technical Implementation

### Proper Model Filtering
The plugin now uses the AI Services plugin's built-in capability filtering system to ensure only models designed for text generation are shown:

- **Primary Method**: Uses `AI_Capabilities::get_model_slugs_for_capabilities()` with `AI_Capability::TEXT_GENERATION`
- **Fallback Method**: Manual filtering if AI Services capability system is unavailable
- **Validation**: Ensures all returned models actually have text generation capability
- **Exclusion**: Properly excludes image generation models (DALL-E, Imagen) and other non-text models

This prevents users from selecting models that aren't suitable for TL;DR generation, such as:
- Image generation models (DALL-E, Imagen)
- Audio processing models
- Embedding models
- Other specialized models not designed for text generation

### Key Functions

#### `tldrwp_get_available_ai_models( $platform_slug )`
- Retrieves all available models for a specific platform
- Uses AI Services plugin's `AI_Capabilities::get_model_slugs_for_capabilities()` for proper filtering
- Only includes models with `TEXT_GENERATION` capability (not image generation or other types)
- Falls back to manual filtering if AI Services capability system is unavailable
- Returns array of model data with slug, name, and capabilities

#### `tldrwp_get_selected_ai_model()`
- Gets the currently selected model for the active platform
- Falls back to first available model if selection is invalid
- Returns empty string if no models are available

#### `tldrwp_ajax_get_models()`
- AJAX handler for fetching models when platform changes
- Returns JSON response with available models
- Includes security nonce verification

### Model Data Structure
```php
array(
    'model_slug' => array(
        'slug' => 'gpt-4',
        'name' => 'GPT-4',
        'capabilities' => array('text_generation', 'chat_history', 'function_calling')
    )
)
```

### Integration with AI Service Call
The selected model is passed to the AI Services plugin when generating summaries:

```php
$model_params = array(
    'feature' => 'tldrwp-summary'
);

// Use proper AI capability constants if available
if ( class_exists( 'Felix_Arntz\AI_Services\Services\API\Enums\AI_Capability' ) ) {
    $ai_capability = 'Felix_Arntz\AI_Services\Services\API\Enums\AI_Capability';
    $model_params['capabilities'] = array( $ai_capability::TEXT_GENERATION );
} else {
    // Fallback to string capability
    $model_params['capabilities'] = array( 'text_generation' );
}

if ( ! empty( $selected_model ) ) {
    $model_params['model'] = $selected_model;
}

$model = $service->get_model( $model_params );
```

## User Experience

### Settings Page
- Model selection field appears below platform selection
- Shows current selection and available options
- Provides helpful descriptions and guidance

### Dynamic Updates
- Platform changes trigger automatic model list refresh
- Loading indicators show during AJAX requests
- Error handling for failed model fetches

### Test Connection
- Test connection now shows which model was used
- Displays both platform and model information
- Helps verify configuration is working correctly

## Error Handling

### Model Availability
- If selected model becomes unavailable, falls back to auto-selection
- Shows appropriate error messages when no models are available
- Graceful degradation when API calls fail

### AJAX Failures
- Network errors are caught and displayed to user
- Fallback to static model list if dynamic loading fails
- Console logging for debugging purposes

## Security Considerations

### Input Validation
- Model selection is sanitized before saving
- Only allows selection of available models for the selected platform
- Nonce verification for all AJAX requests

### API Security
- Model parameters are validated before API calls
- Error messages don't expose sensitive information
- Proper capability checks for admin functions

## Testing

### Test File
A comprehensive test file is included: `test-model-selection.php`

This file tests:
- AI Services plugin availability
- Platform detection and availability
- Model listing for each platform
- Current selection validation
- AI service call functionality
- AJAX endpoint functionality

### Manual Testing
1. Configure multiple AI platforms in AI Services
2. Visit Settings → Reading → TL;DR Settings
3. Select different platforms and verify model lists update
4. Test AI connection to verify model selection works
5. Generate TL;DR summaries to confirm model usage

## Future Enhancements

### Potential Improvements
- Model capability filtering (e.g., only show models with specific features)
- Model performance metrics or recommendations
- Bulk model testing functionality
- Model usage statistics and analytics

### Compatibility
- Works with all AI Services plugin platforms (Anthropic, Google, OpenAI)
- Compatible with future AI Services plugin updates
- Maintains backward compatibility with existing configurations 