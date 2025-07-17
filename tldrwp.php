<?php
/**
 * Plugin Name: TLDRWP AI Block
 * Description: Let your readers generate a TL;DR of your content with AI.
 * Version: 0.1.0
 * Author: Matt Cromwell
 * Requires Plugins: ai-services
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define( 'TLDRWP_VERSION', '0.1.0' );
define( 'TLDRWP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TLDRWP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );



/**
 * Get default settings for TLDRWP
 */
function tldrwp_get_default_settings() {
    return array(
        'enabled_post_types' => array( 'post' ),
        'default_prompt'     => 'Please provide a concise TL;DR summary of this article with a call-to-action at the end.',
        'button_title'       => 'Short on time?',
        'button_description' => 'Click here to generate a TL;DR of this article',
        'enable_social_sharing' => true,
        'selected_ai_platform' => '',
        'selected_ai_model' => ''
    );
}

/**
 * Get current TLDRWP settings
 */
function tldrwp_get_settings() {
    $defaults = tldrwp_get_default_settings();
    $settings = get_option( 'tldrwp_settings', array() );
    
    return wp_parse_args( $settings, $defaults );
}

/**
 * Get available AI platforms that have API keys configured
 */
function tldrwp_get_available_ai_platforms() {
    if ( ! function_exists( 'ai_services' ) ) {
        return array();
    }

    $ai_services = ai_services();
    $available_platforms = array();
    
    // Get all registered service slugs
    $registered_slugs = $ai_services->get_registered_service_slugs();
    
    foreach ( $registered_slugs as $slug ) {
        if ( $ai_services->is_service_available( $slug ) ) {
            $name = $ai_services->get_service_name( $slug );
            $available_platforms[ $slug ] = $name;
        }
    }
    
    return $available_platforms;
}

/**
 * Get the selected AI platform or fallback to first available
 */
function tldrwp_get_selected_ai_platform() {
    $settings = tldrwp_get_settings();
    $available_platforms = tldrwp_get_available_ai_platforms();
    
    // If no platform is selected or the selected platform is no longer available
    if ( empty( $settings['selected_ai_platform'] ) || ! isset( $available_platforms[ $settings['selected_ai_platform'] ] ) ) {
        // Return the first available platform, or empty string if none available
        return ! empty( $available_platforms ) ? array_keys( $available_platforms )[0] : '';
    }
    
    return $settings['selected_ai_platform'];
}

/**
 * Get available AI models for a specific platform
 */
function tldrwp_get_available_ai_models( $platform_slug = null ) {
    if ( ! function_exists( 'ai_services' ) ) {
        return array();
    }

    // If no platform specified, get the selected platform
    if ( empty( $platform_slug ) ) {
        $platform_slug = tldrwp_get_selected_ai_platform();
    }

    if ( empty( $platform_slug ) ) {
        return array();
    }

    $ai_services = ai_services();
    
    // Check if the platform is available
    if ( ! $ai_services->is_service_available( $platform_slug ) ) {
        return array();
    }

    try {
        // Get the service instance
        $service = $ai_services->get_available_service( $platform_slug );
        
        // Get all models for this service
        $all_models = $service->list_models();
        
        // Use the AI Services plugin's built-in capability filtering
        // This ensures we only get models that are actually designed for text generation
        if ( class_exists( 'Felix_Arntz\AI_Services\Services\Util\AI_Capabilities' ) && 
             class_exists( 'Felix_Arntz\AI_Services\Services\API\Enums\AI_Capability' ) ) {
            
            $ai_capabilities = 'Felix_Arntz\AI_Services\Services\Util\AI_Capabilities';
            $ai_capability = 'Felix_Arntz\AI_Services\Services\API\Enums\AI_Capability';
            
            try {
                // Get model slugs that support text generation capability
                $text_generation_model_slugs = $ai_capabilities::get_model_slugs_for_capabilities(
                    $all_models,
                    array( $ai_capability::TEXT_GENERATION )
                );
                
                // Filter the models array to only include text generation models
                $text_generation_models = array();
                foreach ( $text_generation_model_slugs as $model_slug ) {
                    if ( isset( $all_models[ $model_slug ] ) ) {
                        $text_generation_models[ $model_slug ] = $all_models[ $model_slug ];
                    }
                }
                
                return $text_generation_models;
                
            } catch ( Exception $capability_exception ) {
                // Fallback to manual filtering if capability filtering fails
                // Silently continue with manual filtering
            }
        }
        
        // Fallback: Manual filtering using the TEXT_GENERATION capability constant
        $text_generation_models = array();
        foreach ( $all_models as $model_slug => $model_data ) {
            if ( isset( $model_data['capabilities'] ) && is_array( $model_data['capabilities'] ) ) {
                if ( in_array( 'text_generation', $model_data['capabilities'] ) ) {
                    $text_generation_models[ $model_slug ] = $model_data;
                }
            }
        }
        
        return $text_generation_models;
        
    } catch ( Exception $e ) {
        return array();
    }
}

/**
 * Get the selected AI model or fallback to first available
 */
function tldrwp_get_selected_ai_model() {
    $settings = tldrwp_get_settings();
    $selected_platform = tldrwp_get_selected_ai_platform();
    
    if ( empty( $selected_platform ) ) {
        return '';
    }
    
    $available_models = tldrwp_get_available_ai_models( $selected_platform );
    
    // If no model is selected or the selected model is no longer available
    if ( empty( $settings['selected_ai_model'] ) || ! isset( $available_models[ $settings['selected_ai_model'] ] ) ) {
        // Return the first available model, or empty string if none available
        return ! empty( $available_models ) ? array_keys( $available_models )[0] : '';
    }
    
    return $settings['selected_ai_model'];
}

/**
 * Check if the AI Services plugin is active.
 */
function tldrwp_check_ai_services() {
    if ( ! function_exists( 'is_plugin_active' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    if ( ! is_plugin_active( 'ai-services/ai-services.php' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'TLDRWP requires the AI Services plugin to be installed and active.', 'tldrwp' ) . '</p></div>';
        } );
        return false;
    }
    return true;
}

/**
 * Check AI Services configuration and show admin notices
 */
function tldrwp_check_ai_configuration() {
    if ( ! tldrwp_check_ai_services() ) {
        return;
    }

    // Check if any AI service is configured by checking for API keys
    $openai_key = get_option( 'ais_openai_api_key' );
    $anthropic_key = get_option( 'ais_anthropic_api_key' );
    $google_key = get_option( 'ais_google_api_key' );
    
    if ( empty( $openai_key ) && empty( $anthropic_key ) && empty( $google_key ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-warning">
                <p><strong>TLDRWP:</strong> ' . esc_html__( 'No AI service is configured. Please set up an AI provider in the AI Services plugin settings.', 'tldrwp' ) . '</p>
                <p><a href="' . esc_url( admin_url( 'options-general.php?page=ais_services' ) ) . '" class="button button-primary">' . esc_html__( 'Configure AI Services', 'tldrwp' ) . '</a></p>
            </div>';
        } );
    }
}

// Check configuration on admin pages
add_action( 'admin_init', 'tldrwp_check_ai_configuration' );

// Add test endpoint for debugging
add_action( 'wp_ajax_tldrwp_test_ai', 'tldrwp_test_ai_connection' );
add_action( 'wp_ajax_nopriv_tldrwp_test_ai', 'tldrwp_test_ai_connection' );

/**
 * Test AI connection for debugging
 */
function tldrwp_test_ai_connection() {
    // Verify nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tldrwp_ajax_nonce' ) ) {
        wp_send_json_error( 'Security check failed' );
    }

    // Check if AI Services is active
    if ( ! tldrwp_check_ai_services() ) {
        wp_send_json_error( 'AI Services plugin is not active' );
    }

    // Get selected platform
    $selected_platform = tldrwp_get_selected_ai_platform();
    if ( empty( $selected_platform ) ) {
        wp_send_json_error( 'No AI platform is selected or available' );
    }

    // Get platform name
    $ai_services = ai_services();
    $platform_name = $ai_services->get_service_name( $selected_platform );

    // Test with a simple prompt
    $test_prompt = 'Please respond with "AI connection test successful" if you can read this message.';
    $response = tldrwp_call_ai_service( $test_prompt );

    if ( empty( $response ) ) {
        wp_send_json_error( 'AI service returned empty response' );
    }

    // Get selected model info
    $selected_model = tldrwp_get_selected_ai_model();
    $model_info = '';
    if ( ! empty( $selected_model ) ) {
        $model_info = ' with model: ' . $selected_model;
    } else {
        $model_info = ' (auto-selected model)';
    }

    wp_send_json_success( array(
        'message' => 'AI connection test successful',
        'response' => $response,
        'service_used' => $platform_name,
        'platform_slug' => $selected_platform,
        'model_used' => $selected_model,
        'model_info' => $model_info
    ) );
}



/**
 * Call AI service using AI Services plugin
 */
function tldrwp_call_ai_service( $prompt ) {
    // Check if AI Services plugin is available
    if ( ! function_exists( 'ai_services' ) ) {
        return '';
    }

    try {
        // Get the AI Services API instance
        $ai_services = ai_services();
        
        // Get the selected platform
        $selected_platform = tldrwp_get_selected_ai_platform();
        
        if ( empty( $selected_platform ) ) {
            return '';
        }
        
        // Check if the selected service is available
        if ( ! $ai_services->is_service_available( $selected_platform ) ) {
            return '';
        }

        // Get the selected service
        $service = $ai_services->get_available_service( $selected_platform );
        
        // Get the selected model
        $selected_model = tldrwp_get_selected_ai_model();
        
        // Prepare model parameters using proper AI capability constants
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
        
        // If a specific model is selected, use it
        if ( ! empty( $selected_model ) ) {
            $model_params['model'] = $selected_model;
        }
        
        // Get a text generation model
        $model = $service->get_model( $model_params );
        
        // Generate text
        $candidates = $model->generate_text( $prompt );
        
        // Extract the text from the response using the Helpers class
        if ( class_exists( 'Felix_Arntz\AI_Services\Services\API\Helpers' ) ) {
            $helpers = 'Felix_Arntz\AI_Services\Services\API\Helpers';
            $contents = $helpers::get_candidate_contents( $candidates );
            $text = $helpers::get_text_from_contents( $contents );
            return trim( $text );
        }
        
        // Fallback method if Helpers class is not available
        if ( $candidates->count() > 0 ) {
            $candidate = $candidates->get( 0 );
            $content = $candidate->get_content();
            if ( $content ) {
                $parts = $content->get_parts();
                if ( $parts->count() > 0 ) {
                    $part = $parts->get( 0 );
                    if ( $part instanceof \Felix_Arntz\AI_Services\Services\API\Types\Parts\Text_Part ) {
                        return trim( $part->get_text() );
                    }
                }
            }
        }
        
        return '';
        
    } catch ( Exception $e ) {
        return '';
    }
}

/**
 * Format AI response using WordPress native functions
 */
function tldrwp_format_ai_response( $raw_response ) {
    if ( empty( $raw_response ) ) {
        return '';
    }

    // Remove markdown code blocks if present
    $cleaned_response = tldrwp_remove_markdown_code_blocks( $raw_response );

    // Define allowed HTML tags for security
    $allowed_html = array(
        'ul' => array(),
        'li' => array(),
        'ol' => array(),
        'p' => array(),
        'strong' => array(),
        'em' => array(),
        'br' => array(),
        'h1' => array(),
        'h2' => array(),
        'h3' => array(),
        'h4' => array(),
        'h5' => array(),
        'h6' => array(),
        'blockquote' => array(),
        'code' => array(),
        'pre' => array(),
    );

    // First, safely sanitize any HTML that might be in the response
    $sanitized_response = wp_kses( $cleaned_response, $allowed_html );

    // Check if the response already contains HTML tags
    if ( strpos( $sanitized_response, '<' ) !== false && strpos( $sanitized_response, '>' ) !== false ) {
        // Response already contains HTML, just apply wpautop for paragraph formatting
        $formatted_response = wpautop( $sanitized_response );
    } else {
        // Response is plain text, convert bullet points to HTML lists
        $formatted_response = tldrwp_convert_plain_text_to_html( $sanitized_response );
    }

    return $formatted_response;
}

/**
 * Convert plain text with bullet points to HTML
 */
function tldrwp_convert_plain_text_to_html( $text ) {
    // Split text into lines
    $lines = explode( "\n", $text );
    $formatted_lines = array();
    $in_list = false;
    $list_items = array();

    foreach ( $lines as $line ) {
        $line = trim( $line );
        
        // Skip empty lines
        if ( empty( $line ) ) {
            continue;
        }

        // Check if line starts with bullet point indicators
        if ( preg_match( '/^[•\-\*]\s+/', $line ) ) {
            // Extract the content after the bullet point
            $content = preg_replace( '/^[•\-\*]\s+/', '', $line );
            
            if ( ! $in_list ) {
                $in_list = true;
            }
            
            $list_items[] = '<li>' . esc_html( $content ) . '</li>';
        } else {
            // If we were in a list, close it
            if ( $in_list && ! empty( $list_items ) ) {
                $formatted_lines[] = '<ul>' . implode( '', $list_items ) . '</ul>';
                $list_items = array();
                $in_list = false;
            }
            
            // Add regular paragraph
            $formatted_lines[] = '<p>' . esc_html( $line ) . '</p>';
        }
    }

    // Close any remaining list
    if ( $in_list && ! empty( $list_items ) ) {
        $formatted_lines[] = '<ul>' . implode( '', $list_items ) . '</ul>';
    }

    return implode( "\n", $formatted_lines );
}

/**
 * Remove markdown code blocks from AI response
 */
function tldrwp_remove_markdown_code_blocks( $text ) {
    // Remove opening markdown code blocks (```html, ```, etc.)
    $text = preg_replace( '/^```[a-zA-Z]*\s*\n?/', '', $text );
    
    // Remove closing markdown code blocks (```)
    $text = preg_replace( '/\n?```\s*$/', '', $text );
    
    // Also handle cases where there might be multiple code blocks
    $text = preg_replace( '/```[a-zA-Z]*\s*\n?/', '', $text );
    $text = preg_replace( '/\n?```\s*/', '', $text );
    
    return trim( $text );
}

// Initialize settings
add_action( 'admin_init', 'tldrwp_init_settings' );

/**
 * Initialize TLDRWP settings
 */
function tldrwp_init_settings() {
    // Add settings section to Reading page
    add_settings_section(
        'tldrwp_settings_section',
        __( 'TL;DR Settings', 'tldrwp' ),
        'tldrwp_settings_section_callback',
        'reading'
    );

    // Register settings
    register_setting( 'reading', 'tldrwp_settings', array(
        'sanitize_callback' => 'tldrwp_sanitize_settings',
        'default'          => tldrwp_get_default_settings()
    ) );

    // Add settings fields
    add_settings_field(
        'tldrwp_enabled_post_types',
        __( 'Enable TL;DR on Post Types', 'tldrwp' ),
        'tldrwp_enabled_post_types_callback',
        'reading',
        'tldrwp_settings_section'
    );

    add_settings_field(
        'tldrwp_default_prompt',
        __( 'Default TL;DR Prompt', 'tldrwp' ),
        'tldrwp_default_prompt_callback',
        'reading',
        'tldrwp_settings_section'
    );

    add_settings_field(
        'tldrwp_button_text',
        __( 'TL;DR Button Text', 'tldrwp' ),
        'tldrwp_button_text_callback',
        'reading',
        'tldrwp_settings_section'
    );

    add_settings_field(
        'tldrwp_enable_social_sharing',
        __( 'Enable Social Sharing', 'tldrwp' ),
        'tldrwp_enable_social_sharing_callback',
        'reading',
        'tldrwp_settings_section'
    );

    // Only show AI platform selection if multiple platforms are available
    $available_platforms = tldrwp_get_available_ai_platforms();
    if ( count( $available_platforms ) > 1 ) {
        add_settings_field(
            'tldrwp_ai_platform',
            __( 'AI Platform for TL;DR', 'tldrwp' ),
            'tldrwp_ai_platform_callback',
            'reading',
            'tldrwp_settings_section'
        );
    }

    // Show AI model selection if any platform is available
    if ( ! empty( $available_platforms ) ) {
        add_settings_field(
            'tldrwp_ai_model',
            __( 'AI Model for TL;DR', 'tldrwp' ),
            'tldrwp_ai_model_callback',
            'reading',
            'tldrwp_settings_section'
        );
    }

    add_settings_field(
        'tldrwp_test_connection',
        __( 'Test AI Connection', 'tldrwp' ),
        'tldrwp_test_connection_callback',
        'reading',
        'tldrwp_settings_section'
    );
}

/**
 * Settings section callback
 */
function tldrwp_settings_section_callback() {
    echo '<p>' . esc_html__( 'Configure TL;DR settings for your content. The TL;DR button will appear on selected post types.', 'tldrwp' ) . '</p>';
    
    // Show troubleshooting information
    if ( ! tldrwp_check_ai_services() ) {
        echo '<div class="notice notice-error inline"><p>' . esc_html__( '❌ AI Services plugin is not active. Please install and activate it.', 'tldrwp' ) . '</p></div>';
    } else {
        $available_platforms = tldrwp_get_available_ai_platforms();
        
        if ( empty( $available_platforms ) ) {
            echo '<div class="notice notice-warning inline"><p>' . esc_html__( '⚠️ No AI service is configured. Please set up an AI provider in the AI Services plugin settings.', 'tldrwp' ) . '</p></div>';
        } else {
            $selected_platform = tldrwp_get_selected_ai_platform();
            $platform_name = isset( $available_platforms[ $selected_platform ] ) ? $available_platforms[ $selected_platform ] : '';
            
            if ( count( $available_platforms ) > 1 ) {
                echo '<div class="notice notice-success inline"><p>' . esc_html__( '✅ Multiple AI platforms are available. You can choose which one to use for TL;DR generation below.', 'tldrwp' ) . '</p></div>';
                if ( ! empty( $platform_name ) ) {
                    echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Currently selected:', 'tldrwp' ) . ' <strong>' . esc_html( $platform_name ) . '</strong></p></div>';
                    
                    // Show selected model information
                    $selected_model = tldrwp_get_selected_ai_model();
                    if ( ! empty( $selected_model ) ) {
                        echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Model:', 'tldrwp' ) . ' <strong>' . esc_html( $selected_model ) . '</strong></p></div>';
                    } else {
                        echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Model:', 'tldrwp' ) . ' <strong>' . esc_html__( 'Auto-selected', 'tldrwp' ) . '</strong></p></div>';
                    }
                }
            } else {
                echo '<div class="notice notice-success inline"><p>' . esc_html__( '✅ AI service is configured and ready to use.', 'tldrwp' ) . ' (' . esc_html( $platform_name ) . ')</p></div>';
                
                // Show selected model information for single platform
                $selected_model = tldrwp_get_selected_ai_model();
                if ( ! empty( $selected_model ) ) {
                    echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Model:', 'tldrwp' ) . ' <strong>' . esc_html( $selected_model ) . '</strong></p></div>';
                } else {
                    echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Model:', 'tldrwp' ) . ' <strong>' . esc_html__( 'Auto-selected', 'tldrwp' ) . '</strong></p></div>';
                }
            }
        }
    }
}

/**
 * Enabled post types field callback
 */
function tldrwp_enabled_post_types_callback() {
    $settings = tldrwp_get_settings();
    $enabled_types = $settings['enabled_post_types'];
    $post_types = get_post_types( array( 'public' => true ), 'objects' );

    foreach ( $post_types as $post_type ) {
        $checked = in_array( $post_type->name, $enabled_types ) ? 'checked' : '';
        echo '<label style="display: block; margin-bottom: 5px;">';
        echo '<input type="checkbox" name="tldrwp_settings[enabled_post_types][]" value="' . esc_attr( $post_type->name ) . '" ' . esc_attr( $checked ) . '>';
        echo ' ' . esc_html( $post_type->labels->name );
        echo '</label>';
    }
    echo '<p class="description">' . esc_html__( 'Select which post types should display the TL;DR button.', 'tldrwp' ) . '</p>';
}

/**
 * Default prompt field callback
 */
function tldrwp_default_prompt_callback() {
    $settings = tldrwp_get_settings();
    $prompt = $settings['default_prompt'];
    
    echo '<textarea name="tldrwp_settings[default_prompt]" rows="3" cols="50" class="large-text">' . esc_textarea( $prompt ) . '</textarea>';
    echo '<p class="description">' . esc_html__( 'Default prompt used to generate TL;DR summaries. You can customize this per page in the post editor.', 'tldrwp' ) . '</p>';
}

/**
 * Button text field callback
 */
function tldrwp_button_text_callback() {
    $settings = tldrwp_get_settings();
    
    echo '<label style="display: block; margin-bottom: 10px;">';
    echo '<strong>' . esc_html__( 'Button Title:', 'tldrwp' ) . '</strong><br>';
    echo '<input type="text" name="tldrwp_settings[button_title]" value="' . esc_attr( $settings['button_title'] ) . '" class="regular-text">';
    echo '</label>';
    
    echo '<label style="display: block;">';
    echo '<strong>' . esc_html__( 'Button Description:', 'tldrwp' ) . '</strong><br>';
    echo '<input type="text" name="tldrwp_settings[button_description]" value="' . esc_attr( $settings['button_description'] ) . '" class="regular-text">';
    echo '</label>';
    
    echo '<p class="description">' . esc_html__( 'Text displayed on the TL;DR button to inform users what to expect.', 'tldrwp' ) . '</p>';
}

/**
 * Enable social sharing field callback
 */
function tldrwp_enable_social_sharing_callback() {
    $settings = tldrwp_get_settings();
    $enabled = isset( $settings['enable_social_sharing'] ) ? $settings['enable_social_sharing'] : true;
    
    echo '<label style="display: block;">';
    echo '<input type="checkbox" name="tldrwp_settings[enable_social_sharing]" value="1" ' . checked( $enabled, true, false ) . '>';
    echo ' ' . esc_html__( 'Enable social sharing buttons on TL;DR summaries', 'tldrwp' );
    echo '</label>';
    
    echo '<p class="description">' . esc_html__( 'When enabled, users can share TL;DR summaries on social media platforms.', 'tldrwp' ) . '</p>';
}

/**
 * AI Platform selection field callback
 */
function tldrwp_ai_platform_callback() {
    $settings = tldrwp_get_settings();
    $available_platforms = tldrwp_get_available_ai_platforms();
    $selected_platform = $settings['selected_ai_platform'];
    
    if ( empty( $available_platforms ) ) {
        echo '<p class="description">' . esc_html__( 'No AI platforms are currently available. Please configure at least one AI service in the AI Services plugin settings.', 'tldrwp' ) . '</p>';
        return;
    }
    
    echo '<select name="tldrwp_settings[selected_ai_platform]" class="regular-text">';
    echo '<option value="">' . esc_html__( '-- Select AI Platform --', 'tldrwp' ) . '</option>';
    
    foreach ( $available_platforms as $slug => $name ) {
        $selected = ( $selected_platform === $slug ) ? 'selected' : '';
        echo '<option value="' . esc_attr( $slug ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $name ) . '</option>';
    }
    
    echo '</select>';
    echo '<p class="description">' . esc_html__( 'Choose which AI platform to use for generating TL;DR summaries. This setting only appears when multiple AI platforms have API keys configured.', 'tldrwp' ) . '</p>';
}

/**
 * AI Model selection field callback
 */
function tldrwp_ai_model_callback() {
    $settings = tldrwp_get_settings();
    $selected_platform = tldrwp_get_selected_ai_platform();
    $available_models = tldrwp_get_available_ai_models( $selected_platform );
    $selected_model = $settings['selected_ai_model'];
    
    if ( empty( $selected_platform ) ) {
        echo '<p class="description">' . esc_html__( 'Please select an AI platform first.', 'tldrwp' ) . '</p>';
        return;
    }
    
    if ( empty( $available_models ) ) {
        echo '<p class="description">' . esc_html__( 'No text generation models are available for the selected platform.', 'tldrwp' ) . '</p>';
        return;
    }
    
    echo '<select name="tldrwp_settings[selected_ai_model]" id="tldrwp-ai-model-select" class="regular-text">';
    echo '<option value="">' . esc_html__( '-- Auto-select best model --', 'tldrwp' ) . '</option>';
    
    foreach ( $available_models as $model_slug => $model_data ) {
        $selected = ( $selected_model === $model_slug ) ? 'selected' : '';
        $model_name = isset( $model_data['name'] ) ? $model_data['name'] : $model_slug;
        echo '<option value="' . esc_attr( $model_slug ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $model_name ) . '</option>';
    }
    
    echo '</select>';
    echo '<p class="description">' . esc_html__( 'Choose which AI model to use for generating TL;DR summaries. Leave as "Auto-select" to use the platform\'s recommended model.', 'tldrwp' ) . '</p>';
    
    // Add JavaScript to update model dropdown when platform changes
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const platformSelect = document.querySelector('select[name="tldrwp_settings[selected_ai_platform]"]');
        const modelSelect = document.getElementById('tldrwp-ai-model-select');
        
        if (platformSelect && modelSelect) {
            platformSelect.addEventListener('change', function() {
                const selectedPlatform = this.value;
                
                // Clear current model options
                modelSelect.innerHTML = '<option value="">-- Loading models...</option>';
                
                if (selectedPlatform) {
                    // Fetch available models for the selected platform
                                            fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'tldrwp_get_models',
                            platform: selectedPlatform,
                            nonce: '<?php echo esc_js( wp_create_nonce( 'tldrwp_ajax_nonce' ) ); ?>'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data.models) {
                            modelSelect.innerHTML = '<option value="">-- Auto-select best model --</option>';
                            data.data.models.forEach(function(model) {
                                const option = document.createElement('option');
                                option.value = model.slug;
                                option.textContent = model.name;
                                modelSelect.appendChild(option);
                            });
                        } else {
                            modelSelect.innerHTML = '<option value="">-- No models available --</option>';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching models:', error);
                        modelSelect.innerHTML = '<option value="">-- Error loading models --</option>';
                    });
                } else {
                    modelSelect.innerHTML = '<option value="">-- Please select a platform first --</option>';
                }
            });
        }
    });
    </script>
    <?php
}

/**
 * Test connection field callback
 */
function tldrwp_test_connection_callback() {
    echo '<button type="button" id="tldrwp-test-ai" class="button button-secondary">' . esc_html__( 'Test AI Connection', 'tldrwp' ) . '</button>';
    echo '<div id="tldrwp-test-result" style="margin-top: 10px;"></div>';
    echo '<p class="description">' . esc_html__( 'Click to test if your AI service is properly configured.', 'tldrwp' ) . '</p>';
    
    // Add JavaScript for the test button
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const testButton = document.getElementById('tldrwp-test-ai');
        const resultDiv = document.getElementById('tldrwp-test-result');
        
        if (testButton && resultDiv) {
            testButton.addEventListener('click', async function() {
                testButton.disabled = true;
                testButton.textContent = 'Testing...';
                resultDiv.innerHTML = '';
                
                try {
                    const formData = new FormData();
                    formData.append('action', 'tldrwp_test_ai');
                    formData.append('nonce', '<?php echo esc_js( wp_create_nonce( 'tldrwp_ajax_nonce' ) ); ?>');
                    
                    const response = await fetch(ajaxurl, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        let modelInfo = '';
                        if (data.data.model_used) {
                            modelInfo = ' with model: ' + data.data.model_used;
                        } else {
                            modelInfo = ' (auto-selected model)';
                        }
                        resultDiv.innerHTML = '<div class="notice notice-success inline"><p>✅ ' + data.data.message + ' (Using: ' + data.data.service_used + modelInfo + ')</p></div>';
                    } else {
                        resultDiv.innerHTML = '<div class="notice notice-error inline"><p>❌ ' + data.data + '</p></div>';
                    }
                } catch (error) {
                    resultDiv.innerHTML = '<div class="notice notice-error inline"><p>❌ Test failed: ' + error.message + '</p></div>';
                } finally {
                    testButton.disabled = false;
                    testButton.textContent = '<?php esc_attr_e( 'Test AI Connection', 'tldrwp' ); ?>';
                }
            });
        }
    });
    </script>
    <?php
}

/**
 * Sanitize settings
 */
function tldrwp_sanitize_settings( $input ) {
    $sanitized = array();
    
    // Sanitize enabled post types
    if ( isset( $input['enabled_post_types'] ) && is_array( $input['enabled_post_types'] ) ) {
        $sanitized['enabled_post_types'] = array_map( 'sanitize_text_field', $input['enabled_post_types'] );
    } else {
        $sanitized['enabled_post_types'] = array( 'post' );
    }
    
    // Sanitize default prompt
    if ( isset( $input['default_prompt'] ) ) {
        $sanitized['default_prompt'] = sanitize_textarea_field( $input['default_prompt'] );
    }
    
    // Sanitize button title
    if ( isset( $input['button_title'] ) ) {
        $sanitized['button_title'] = sanitize_text_field( $input['button_title'] );
    }
    
    // Sanitize button description
    if ( isset( $input['button_description'] ) ) {
        $sanitized['button_description'] = sanitize_text_field( $input['button_description'] );
    }
    
    // Sanitize social sharing setting
    $sanitized['enable_social_sharing'] = isset( $input['enable_social_sharing'] ) ? true : false;
    
    // Sanitize selected AI platform
    if ( isset( $input['selected_ai_platform'] ) ) {
        $available_platforms = tldrwp_get_available_ai_platforms();
        $selected_platform = sanitize_text_field( $input['selected_ai_platform'] );
        
        // Only allow selection of available platforms
        if ( empty( $selected_platform ) || isset( $available_platforms[ $selected_platform ] ) ) {
            $sanitized['selected_ai_platform'] = $selected_platform;
        } else {
            $sanitized['selected_ai_platform'] = '';
        }
    } else {
        $sanitized['selected_ai_platform'] = '';
    }
    
    // Sanitize selected AI model
    if ( isset( $input['selected_ai_model'] ) ) {
        $selected_platform = $sanitized['selected_ai_platform'];
        $available_models = tldrwp_get_available_ai_models( $selected_platform );
        $selected_model = sanitize_text_field( $input['selected_ai_model'] );
        
        // Only allow selection of available models for the selected platform
        if ( empty( $selected_model ) || isset( $available_models[ $selected_model ] ) ) {
            $sanitized['selected_ai_model'] = $selected_model;
        } else {
            $sanitized['selected_ai_model'] = '';
        }
    } else {
        $sanitized['selected_ai_model'] = '';
    }
    
    return $sanitized;
}

// Add AJAX handler for getting models
add_action( 'wp_ajax_tldrwp_get_models', 'tldrwp_ajax_get_models' );

/**
 * AJAX handler to get available models for a platform
 */
function tldrwp_ajax_get_models() {
    // Verify nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tldrwp_ajax_nonce' ) ) {
        wp_send_json_error( 'Security check failed' );
    }

    // Validate and sanitize platform parameter
    if ( ! isset( $_POST['platform'] ) ) {
        wp_send_json_error( 'Platform parameter is required' );
    }
    
    $platform = sanitize_text_field( wp_unslash( $_POST['platform'] ) );
    
    if ( empty( $platform ) ) {
        wp_send_json_error( 'Platform is required' );
    }
    
    $available_models = tldrwp_get_available_ai_models( $platform );
    
    if ( empty( $available_models ) ) {
        wp_send_json_error( 'No models available for this platform' );
    }
    
    // Format models for JSON response
    $models_data = array();
    foreach ( $available_models as $model_slug => $model_data ) {
        $models_data[] = array(
            'slug' => $model_slug,
            'name' => isset( $model_data['name'] ) ? $model_data['name'] : $model_slug
        );
    }
    
    wp_send_json_success( array( 'models' => $models_data ) );
}

// Frontend assets and content injection
add_action( 'wp_enqueue_scripts', 'tldrwp_enqueue_frontend_assets' );
add_filter( 'the_content', 'tldrwp_inject_tldr_button' );
add_action( 'wp_ajax_tldrwp_generate_summary', 'tldrwp_handle_ajax_request' );
add_action( 'wp_ajax_nopriv_tldrwp_generate_summary', 'tldrwp_handle_ajax_request' );

/**
 * Enqueue frontend assets
 */
function tldrwp_enqueue_frontend_assets() {
    if ( ! tldrwp_check_ai_services() ) {
        return;
    }

    // Only load on enabled post types and single posts/pages
    if ( ! tldrwp_should_show_button() ) {
        return;
    }

    wp_enqueue_script(
        'tldrwp-frontend',
        plugins_url( 'assets/js/frontend.js', __FILE__ ),
        array(),
        filemtime( plugin_dir_path( __FILE__ ) . 'assets/js/frontend.js' ),
        true
    );

    wp_enqueue_style(
        'tldrwp-frontend',
        plugins_url( 'assets/css/frontend.css', __FILE__ ),
        array(),
        filemtime( plugin_dir_path( __FILE__ ) . 'assets/css/frontend.css' )
    );

    // Get settings for localization
    $settings = tldrwp_get_settings();

    // Localize script with AJAX data
    wp_localize_script( 'tldrwp-frontend', 'tldrwp_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'tldrwp_ajax_nonce' ),
        'enable_social_sharing' => $settings['enable_social_sharing'],
        'selected_platform' => tldrwp_get_selected_ai_platform(),
        'selected_model' => tldrwp_get_selected_ai_model(),
    ) );
}

/**
 * Check if TL;DR button should be shown
 */
function tldrwp_should_show_button() {
    // Only show on single posts/pages
    if ( ! is_singular() ) {
        return false;
    }

    $settings = tldrwp_get_settings();
    $current_post_type = get_post_type();
    
    return in_array( $current_post_type, $settings['enabled_post_types'] );
}

/**
 * Inject TL;DR button into content
 */
function tldrwp_inject_tldr_button( $content ) {
    // Don't inject on admin pages or if button shouldn't be shown
    if ( is_admin() || ! tldrwp_should_show_button() ) {
        return $content;
    }

    $settings = tldrwp_get_settings();
    $post_id = get_the_ID();
    
    // Get page-specific prompt or use default
    $page_prompt = get_post_meta( $post_id, '_tldrwp_custom_prompt', true );
    $prompt = ! empty( $page_prompt ) ? $page_prompt : $settings['default_prompt'];
    
    // Build button HTML with description
    $button_html = sprintf(
        '<div class="tldrwp-container" data-default-prompt="%s">
            <div class="tldrwp-button-wrapper">
                <button class="tldrwp-button" data-prompt="%s" data-original-text="%s">
                    <svg class="tldrwp-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path>
                        <path d="M20 3v4"></path>
                        <path d="M22 5h-4"></path>
                        <path d="M4 17v2"></path>
                        <path d="M5 18H3"></path>
                    </svg>
                    <span class="tldrwp-button-text">
                        <span class="tldrwp-button-title">%s</span>
                        <span class="tldrwp-button-desc">%s</span>
                    </span>
                </button>
            </div>
            <div class="tldrwp-content" style="display: none;"></div>
        </div>',
        esc_attr( $settings['default_prompt'] ),
        esc_attr( $prompt ),
        esc_attr( $settings['button_title'] ),
        esc_html( $settings['button_title'] ),
        esc_html( $settings['button_description'] )
    );
    
    // Insert button at the beginning of content
    $content = $button_html . $content;
    
    return $content;
}

/**
 * Handle AJAX request for TL;DR generation
 */
function tldrwp_handle_ajax_request() {
    // Verify nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tldrwp_ajax_nonce' ) ) {
        wp_send_json_error( __( 'Security check failed', 'tldrwp' ) );
    }

    // Check if AI Services is active
    if ( ! tldrwp_check_ai_services() ) {
        wp_send_json_error( __( 'AI Services plugin is not active. Please install and configure the AI Services plugin.', 'tldrwp' ) );
    }

    // Validate and sanitize input parameters
    if ( ! isset( $_POST['prompt'] ) ) {
        wp_send_json_error( __( 'Prompt parameter is required.', 'tldrwp' ) );
    }
    
    if ( ! isset( $_POST['content'] ) ) {
        wp_send_json_error( __( 'Content parameter is required.', 'tldrwp' ) );
    }

    $prompt = sanitize_textarea_field( wp_unslash( $_POST['prompt'] ) );
    $content = sanitize_textarea_field( wp_unslash( $_POST['content'] ) );

    if ( empty( $prompt ) || empty( $content ) ) {
        wp_send_json_error( __( 'Missing required data.', 'tldrwp' ) );
    }

    // Append HTML formatting instructions to the user's prompt
    $html_instructions = "\n\nPlease format your response as HTML with proper <ul> and <li> tags for any bullet points or lists. Use <p> tags for paragraphs and <strong> for emphasis.";
    $formatted_prompt = $prompt . $html_instructions . "\n\nContent to summarize:\n" . $content;

    // Call AI service directly
    $raw_response = tldrwp_call_ai_service( $formatted_prompt );
    
    // Format the response using WordPress native functions
    $response = tldrwp_format_ai_response( $raw_response );

    if ( empty( $response ) ) {
        // Check if selected platform is available
        $selected_platform = tldrwp_get_selected_ai_platform();
        
        if ( empty( $selected_platform ) ) {
            wp_send_json_error( __( 'No AI platform is selected or available. Please configure an AI provider in the AI Services plugin settings.', 'tldrwp' ) );
        } else {
            wp_send_json_error( __( 'AI service returned an empty response. Please check your API configuration and try again.', 'tldrwp' ) );
        }
    }

    wp_send_json_success( $response );
}

add_action( 'init', 'tldrwp_register_block' );

/**
 * Register the dynamic block and scripts.
 */
function tldrwp_register_block() {
    if ( ! tldrwp_check_ai_services() ) {
        return;
    }

    wp_register_script(
        'tldrwp-block',
        plugins_url( 'blocks/ai-chat/index.js', __FILE__ ),
        array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-api-fetch' ),
        filemtime( plugin_dir_path( __FILE__ ) . 'blocks/ai-chat/index.js' ),
        true
    );

    wp_register_style(
        'tldrwp-block',
        plugins_url( 'blocks/ai-chat/style.css', __FILE__ ),
        array(),
        filemtime( plugin_dir_path( __FILE__ ) . 'blocks/ai-chat/style.css' )
    );

    register_block_type( 'tldrwp/ai-chat', array(
        'editor_script'   => 'tldrwp-block',
        'editor_style'    => 'tldrwp-block',
        'style'           => 'tldrwp-block',
        'render_callback' => 'tldrwp_render_ai_chat_block',
    ) );
}

/**
 * Render callback for the dynamic block.
 */
function tldrwp_render_ai_chat_block( $attributes, $content ) {
    // Return empty content for deprecated block
    // TL;DR functionality is now automatically available on all posts
    return '';
}

add_action( 'rest_api_init', 'tldrwp_register_rest_routes' );

/**
 * Register REST API endpoint for AI interaction.
 */
function tldrwp_register_rest_routes() {
    register_rest_route( 'tldrwp/v1', '/chat', array(
        'methods'             => 'POST',
        'callback'            => 'tldrwp_handle_chat_request',
        'permission_callback' => function() { 
        return isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tldrwp_nonce' ); 
    },
    ) );
}

/**
 * Handle the AI chat request via AI Services plugin.
 */
function tldrwp_handle_chat_request( WP_REST_Request $request ) {
    if ( ! tldrwp_check_ai_services() ) {
        return new WP_REST_Response( array( 'error' => __( 'AI Services plugin is not active.', 'tldrwp' ) ), 400 );
    }

    $prompt = sanitize_text_field( $request->get_param( 'prompt' ) );
    $content = sanitize_textarea_field( $request->get_param( 'content' ) );

    if ( empty( $prompt ) ) {
        return new WP_REST_Response( array( 'error' => __( 'Prompt is required.', 'tldrwp' ) ), 400 );
    }

    // Combine prompt with content if provided
    $full_prompt = ! empty( $content ) ? $prompt . "\n\nContent to summarize:\n" . $content : $prompt;

    /**
     * Filter to generate content using AI Services.
     * Developers using different AI services can hook into this filter.
     */
    $response = apply_filters( 'tldrwp_generate_ai_response', '', $full_prompt );

    if ( empty( $response ) ) {
        return new WP_REST_Response( array( 'error' => __( 'Unable to generate response. Please try again.', 'tldrwp' ) ), 500 );
    }

    return rest_ensure_response( array( 'response' => $response ) );
}
