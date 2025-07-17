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
define( 'TLDRWP_PLUGIN_FILE', __FILE__ );

// Load the main plugin class
require_once TLDRWP_PLUGIN_PATH . 'includes/class-tldrwp.php';

/**
 * Initialize the plugin
 */
function tldrwp_init() {
    // Initialize the main plugin class
    TLDRWP::get_instance();
}
add_action( 'plugins_loaded', 'tldrwp_init' );

// ============================================================================
// BACKWARD COMPATIBILITY FUNCTIONS
// These functions are kept for backward compatibility and will be removed
// in future versions as we complete the migration to class-based architecture.
// ============================================================================

/**
 * Get default settings for TLDRWP
 * 
 * @deprecated Use TLDRWP::get_instance()->get_default_settings() instead
 */
function tldrwp_get_default_settings() {
    $plugin = TLDRWP::get_instance();
    return $plugin->get_default_settings();
}

/**
 * Get current TLDRWP settings
 * 
 * @deprecated Use TLDRWP::get_instance()->get_settings() instead
 */
function tldrwp_get_settings() {
    $plugin = TLDRWP::get_instance();
    return $plugin->get_settings();
}

/**
 * Get available AI platforms that have API keys configured
 * 
 * @deprecated Use TLDRWP::get_instance()->get_available_ai_platforms() instead
 */
function tldrwp_get_available_ai_platforms() {
    $plugin = TLDRWP::get_instance();
    return $plugin->get_available_ai_platforms();
}

/**
 * Get the selected AI platform or fallback to first available
 * 
 * @deprecated Use TLDRWP::get_instance()->get_selected_ai_platform() instead
 */
function tldrwp_get_selected_ai_platform() {
    $plugin = TLDRWP::get_instance();
    return $plugin->get_selected_ai_platform();
}

/**
 * Get available AI models for a specific platform
 * 
 * @deprecated Use TLDRWP::get_instance()->get_available_ai_models() instead
 */
function tldrwp_get_available_ai_models( $platform_slug = null ) {
    $plugin = TLDRWP::get_instance();
    return $plugin->get_available_ai_models( $platform_slug );
}

/**
 * Get the selected AI model or fallback to first available
 * 
 * @deprecated Use TLDRWP::get_instance()->get_selected_ai_model() instead
 */
function tldrwp_get_selected_ai_model() {
    $plugin = TLDRWP::get_instance();
    return $plugin->get_selected_ai_model();
}

/**
 * Check if the AI Services plugin is active.
 * 
 * @deprecated Use TLDRWP::get_instance()->check_ai_services() instead
 */
function tldrwp_check_ai_services() {
    $plugin = TLDRWP::get_instance();
    return $plugin->check_ai_services();
}

/**
 * Check AI configuration and show admin notices.
 * 
 * @deprecated Use TLDRWP::get_instance()->check_ai_configuration() instead
 */
function tldrwp_check_ai_configuration() {
    $plugin = TLDRWP::get_instance();
    $plugin->check_ai_configuration();
}

/**
 * Test AI connection via AJAX.
 * 
 * @deprecated Use TLDRWP::get_instance()->test_ai_connection() instead
 */
function tldrwp_test_ai_connection() {
    $plugin = TLDRWP::get_instance();
    $plugin->test_ai_connection();
}

/**
 * Call AI service with the given prompt.
 * 
 * @deprecated Use TLDRWP::get_instance()->call_ai_service() instead
 */
function tldrwp_call_ai_service( $prompt ) {
    $plugin = TLDRWP::get_instance();
    return $plugin->call_ai_service( $prompt );
}

/**
 * Format AI response for display.
 * 
 * @deprecated Use TLDRWP::get_instance()->format_ai_response() instead
 */
function tldrwp_format_ai_response( $raw_response ) {
    $plugin = TLDRWP::get_instance();
    return $plugin->format_ai_response( $raw_response );
}

/**
 * Convert plain text to HTML.
 * 
 * @deprecated This is now a private method in the TLDRWP class
 */
function tldrwp_convert_plain_text_to_html( $text ) {
    // This function is now private in the class, so we'll keep a simple version here
    $lines = explode( "\n", $text );
    $html_lines = array();
    $in_list = false;

    foreach ( $lines as $line ) {
        $line = trim( $line );
        
        if ( empty( $line ) ) {
            if ( $in_list ) {
                $html_lines[] = '</ul>';
                $in_list = false;
            }
            continue;
        }

        // Check for bullet points (various formats)
        if ( preg_match( '/^[\-\*•]\s+(.+)$/', $line, $matches ) ) {
            if ( ! $in_list ) {
                $html_lines[] = '<ul>';
                $in_list = true;
            }
            $html_lines[] = '<li>' . esc_html( $matches[1] ) . '</li>';
        } else {
            if ( $in_list ) {
                $html_lines[] = '</ul>';
                $in_list = false;
            }
            $html_lines[] = '<p>' . esc_html( $line ) . '</p>';
        }
    }

    if ( $in_list ) {
        $html_lines[] = '</ul>';
    }

    return implode( "\n", $html_lines );
}

/**
 * Remove markdown code blocks from text.
 * 
 * @deprecated This is now a private method in the TLDRWP class
 */
function tldrwp_remove_markdown_code_blocks( $text ) {
    // Remove ```code blocks```
    $text = preg_replace( '/```.*?```/s', '', $text );
    
    // Remove `inline code`
    $text = preg_replace( '/`([^`]+)`/', '$1', $text );
    
    return $text;
}

/**
 * Initialize settings page.
 * 
 * @deprecated This will be moved to the Admin class in Phase 2
 */
function tldrwp_init_settings() {
    // This function will be moved to the Admin class in Phase 2
    // For now, we'll keep the existing implementation
    add_action( 'admin_init', 'tldrwp_init_settings_callback' );
}

/**
 * Initialize settings page callback.
 */
function tldrwp_init_settings_callback() {
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
        'default' => tldrwp_get_default_settings()
    ) );

    // Add settings fields
    add_settings_field(
        'tldrwp_enabled_post_types',
        __( 'Enable on Post Types', 'tldrwp' ),
        'tldrwp_enabled_post_types_callback',
        'reading',
        'tldrwp_settings_section'
    );

    add_settings_field(
        'tldrwp_default_prompt',
        __( 'Default Prompt', 'tldrwp' ),
        'tldrwp_default_prompt_callback',
        'reading',
        'tldrwp_settings_section'
    );

    add_settings_field(
        'tldrwp_button_text',
        __( 'Button Text', 'tldrwp' ),
        'tldrwp_button_text_callback',
        'reading',
        'tldrwp_settings_section'
    );

    add_settings_field(
        'tldrwp_enable_social_sharing',
        __( 'Social Sharing', 'tldrwp' ),
        'tldrwp_enable_social_sharing_callback',
        'reading',
        'tldrwp_settings_section'
    );

    add_settings_field(
        'tldrwp_ai_platform',
        __( 'AI Platform', 'tldrwp' ),
        'tldrwp_ai_platform_callback',
        'reading',
        'tldrwp_settings_section'
    );

    add_settings_field(
        'tldrwp_ai_model',
        __( 'AI Model', 'tldrwp' ),
        'tldrwp_ai_model_callback',
        'reading',
        'tldrwp_settings_section'
    );

    add_settings_field(
        'tldrwp_test_connection',
        __( 'Test Connection', 'tldrwp' ),
        'tldrwp_test_connection_callback',
        'reading',
        'tldrwp_settings_section'
    );
}

/**
 * Settings section callback.
 */
function tldrwp_settings_section_callback() {
    echo '<p>' . esc_html__( 'Configure TL;DR settings for your content. The TL;DR button will appear on enabled post types.', 'tldrwp' ) . '</p>';
}

/**
 * Enabled post types callback.
 */
function tldrwp_enabled_post_types_callback() {
    $settings = tldrwp_get_settings();
    $enabled_post_types = $settings['enabled_post_types'];
    
    $post_types = get_post_types( array( 'public' => true ), 'objects' );
    
    foreach ( $post_types as $post_type ) {
        $checked = in_array( $post_type->name, $enabled_post_types ) ? 'checked' : '';
        echo '<label style="display: block; margin-bottom: 5px;">';
        echo '<input type="checkbox" name="tldrwp_settings[enabled_post_types][]" value="' . esc_attr( $post_type->name ) . '" ' . $checked . '> ';
        echo esc_html( $post_type->labels->name );
        echo '</label>';
    }
}

/**
 * Default prompt callback.
 */
function tldrwp_default_prompt_callback() {
    $settings = tldrwp_get_settings();
    echo '<textarea name="tldrwp_settings[default_prompt]" rows="3" cols="50" style="width: 100%;">' . esc_textarea( $settings['default_prompt'] ) . '</textarea>';
    echo '<p class="description">' . esc_html__( 'Default prompt used for TL;DR generation. You can customize this per post using the post meta field.', 'tldrwp' ) . '</p>';
}

/**
 * Button text callback.
 */
function tldrwp_button_text_callback() {
    $settings = tldrwp_get_settings();
    echo '<p><label>' . esc_html__( 'Button Title:', 'tldrwp' ) . '</label><br>';
    echo '<input type="text" name="tldrwp_settings[button_title]" value="' . esc_attr( $settings['button_title'] ) . '" style="width: 100%;">';
    echo '</p>';
    echo '<p><label>' . esc_html__( 'Button Description:', 'tldrwp' ) . '</label><br>';
    echo '<input type="text" name="tldrwp_settings[button_description]" value="' . esc_attr( $settings['button_description'] ) . '" style="width: 100%;">';
    echo '</p>';
}

/**
 * Enable social sharing callback.
 */
function tldrwp_enable_social_sharing_callback() {
    $settings = tldrwp_get_settings();
    $checked = $settings['enable_social_sharing'] ? 'checked' : '';
    echo '<label>';
    echo '<input type="checkbox" name="tldrwp_settings[enable_social_sharing]" value="1" ' . $checked . '> ';
    echo esc_html__( 'Enable social sharing buttons on TL;DR summaries', 'tldrwp' );
    echo '</label>';
}

/**
 * AI platform callback.
 */
function tldrwp_ai_platform_callback() {
    $settings = tldrwp_get_settings();
    $available_platforms = tldrwp_get_available_ai_platforms();
    
    if ( empty( $available_platforms ) ) {
        echo '<p class="description">' . esc_html__( 'No AI platforms available. Please configure API keys in the AI Services plugin settings.', 'tldrwp' ) . '</p>';
        return;
    }
    
    echo '<select name="tldrwp_settings[selected_ai_platform]" id="tldrwp_ai_platform">';
    echo '<option value="">' . esc_html__( 'Select AI Platform', 'tldrwp' ) . '</option>';
    
    foreach ( $available_platforms as $slug => $name ) {
        $selected = ( $settings['selected_ai_platform'] === $slug ) ? 'selected' : '';
        echo '<option value="' . esc_attr( $slug ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
    }
    
    echo '</select>';
    echo '<p class="description">' . esc_html__( 'Select which AI platform to use for TL;DR generation.', 'tldrwp' ) . '</p>';
}

/**
 * AI model callback.
 */
function tldrwp_ai_model_callback() {
    $settings = tldrwp_get_settings();
    $selected_platform = tldrwp_get_selected_ai_platform();
    
    if ( empty( $selected_platform ) ) {
        echo '<p class="description">' . esc_html__( 'Please select an AI platform first.', 'tldrwp' ) . '</p>';
        return;
    }
    
    $available_models = tldrwp_get_available_ai_models( $selected_platform );
    
    if ( empty( $available_models ) ) {
        echo '<p class="description">' . esc_html__( 'No models available for the selected platform.', 'tldrwp' ) . '</p>';
        return;
    }
    
    echo '<select name="tldrwp_settings[selected_ai_model]" id="tldrwp_ai_model">';
    echo '<option value="">' . esc_html__( 'Select AI Model', 'tldrwp' ) . '</option>';
    
    foreach ( $available_models as $model_slug => $model_data ) {
        $selected = ( $settings['selected_ai_model'] === $model_slug ) ? 'selected' : '';
        $model_name = isset( $model_data['name'] ) ? $model_data['name'] : $model_slug;
        echo '<option value="' . esc_attr( $model_slug ) . '" ' . $selected . '>' . esc_html( $model_name ) . '</option>';
    }
    
    echo '</select>';
    echo '<p class="description">' . esc_html__( 'Select which AI model to use for TL;DR generation.', 'tldrwp' ) . '</p>';
}

/**
 * Test connection callback.
 */
function tldrwp_test_connection_callback() {
    $selected_platform = tldrwp_get_selected_ai_platform();
    
    if ( empty( $selected_platform ) ) {
        echo '<p class="description">' . esc_html__( 'Please select an AI platform first.', 'tldrwp' ) . '</p>';
        return;
    }
    
    echo '<button type="button" id="tldrwp_test_connection" class="button button-secondary">' . esc_html__( 'Test Connection', 'tldrwp' ) . '</button>';
    echo '<span id="tldrwp_test_result" style="margin-left: 10px;"></span>';
    echo '<p class="description">' . esc_html__( 'Test the connection to your selected AI platform.', 'tldrwp' ) . '</p>';
    
    // Add JavaScript for AJAX test
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#tldrwp_test_connection').on('click', function() {
            var button = $(this);
            var resultSpan = $('#tldrwp_test_result');
            
            button.prop('disabled', true).text('<?php echo esc_js( __( 'Testing...', 'tldrwp' ) ); ?>');
            resultSpan.html('');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'tldrwp_test_ai',
                    nonce: '<?php echo wp_create_nonce( 'tldrwp_ajax_nonce' ); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        resultSpan.html('<span style="color: green;">✓ ' + response.data + '</span>');
                    } else {
                        resultSpan.html('<span style="color: red;">✗ ' + response.data + '</span>');
                    }
                },
                error: function() {
                    resultSpan.html('<span style="color: red;">✗ <?php echo esc_js( __( 'Connection failed', 'tldrwp' ) ); ?></span>');
                },
                complete: function() {
                    button.prop('disabled', false).text('<?php echo esc_js( __( 'Test Connection', 'tldrwp' ) ); ?>');
                }
            });
        });
        
        // Update models when platform changes
        $('#tldrwp_ai_platform').on('change', function() {
            var platform = $(this).val();
            var modelSelect = $('#tldrwp_ai_model');
            
            if (platform) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'tldrwp_get_models',
                        platform: platform,
                        nonce: '<?php echo wp_create_nonce( 'tldrwp_ajax_nonce' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            modelSelect.html('<option value=""><?php echo esc_js( __( 'Select AI Model', 'tldrwp' ) ); ?></option>');
                            $.each(response.data, function(slug, name) {
                                modelSelect.append('<option value="' + slug + '">' + name + '</option>');
                            });
                        }
                    }
                });
            } else {
                modelSelect.html('<option value=""><?php echo esc_js( __( 'Please select an AI platform first.', 'tldrwp' ) ); ?></option>');
            }
        });
    });
    </script>
    <?php
}

/**
 * Sanitize settings.
 */
function tldrwp_sanitize_settings( $input ) {
    $sanitized = array();
    
    // Enabled post types
    if ( isset( $input['enabled_post_types'] ) && is_array( $input['enabled_post_types'] ) ) {
        $sanitized['enabled_post_types'] = array_map( 'sanitize_text_field', $input['enabled_post_types'] );
    } else {
        $sanitized['enabled_post_types'] = array( 'post' );
    }
    
    // Default prompt
    if ( isset( $input['default_prompt'] ) ) {
        $sanitized['default_prompt'] = sanitize_textarea_field( $input['default_prompt'] );
    }
    
    // Button title
    if ( isset( $input['button_title'] ) ) {
        $sanitized['button_title'] = sanitize_text_field( $input['button_title'] );
    }
    
    // Button description
    if ( isset( $input['button_description'] ) ) {
        $sanitized['button_description'] = sanitize_text_field( $input['button_description'] );
    }
    
    // Enable social sharing
    $sanitized['enable_social_sharing'] = isset( $input['enable_social_sharing'] ) ? true : false;
    
    // Selected AI platform
    if ( isset( $input['selected_ai_platform'] ) ) {
        $sanitized['selected_ai_platform'] = sanitize_text_field( $input['selected_ai_platform'] );
    }
    
    // Selected AI model
    if ( isset( $input['selected_ai_model'] ) ) {
        $sanitized['selected_ai_model'] = sanitize_text_field( $input['selected_ai_model'] );
    }
    
    return $sanitized;
}

/**
 * AJAX handler for getting models.
 * 
 * @deprecated This will be moved to the Admin class in Phase 2
 */
function tldrwp_ajax_get_models() {
    $plugin = TLDRWP::get_instance();
    
    // Verify nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tldrwp_ajax_nonce' ) ) {
        wp_send_json_error( __( 'Security check failed', 'tldrwp' ) );
    }

    if ( ! isset( $_POST['platform'] ) ) {
        wp_send_json_error( __( 'Platform parameter is required.', 'tldrwp' ) );
    }

    $platform = sanitize_text_field( wp_unslash( $_POST['platform'] ) );
    $models = $plugin->get_available_ai_models( $platform );
    
    $model_names = array();
    foreach ( $models as $model_slug => $model_data ) {
        $model_names[ $model_slug ] = isset( $model_data['name'] ) ? $model_data['name'] : $model_slug;
    }
    
    wp_send_json_success( $model_names );
}

/**
 * Enqueue frontend assets.
 * 
 * @deprecated This will be moved to the Public class in Phase 2
 */
function tldrwp_enqueue_frontend_assets() {
    // Only enqueue on single posts/pages where the button might appear
    if ( ! is_singular() ) {
        return;
    }

    $settings = tldrwp_get_settings();
    $current_post_type = get_post_type();
    
    if ( ! in_array( $current_post_type, $settings['enabled_post_types'] ) ) {
        return;
    }

    wp_enqueue_script(
        'tldrwp-frontend',
        TLDRWP_PLUGIN_URL . 'public/js/frontend.js',
        array( 'jquery' ),
        TLDRWP_VERSION,
        true
    );

    wp_enqueue_style(
        'tldrwp-frontend',
        TLDRWP_PLUGIN_URL . 'public/css/frontend.css',
        array(),
        TLDRWP_VERSION
    );

    // Localize script with settings and nonce
    wp_localize_script( 'tldrwp-frontend', 'tldrwp_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'tldrwp_ajax_nonce' ),
        'enable_social_sharing' => $settings['enable_social_sharing']
    ) );
}

/**
 * Check if TLDR button should be shown.
 * 
 * @deprecated Use TLDRWP::get_instance()->should_show_button() instead
 */
function tldrwp_should_show_button() {
    $plugin = TLDRWP::get_instance();
    return $plugin->should_show_button();
}

/**
 * Inject TLDR button into content.
 * 
 * @deprecated Use TLDRWP::get_instance()->inject_tldr_button() instead
 */
function tldrwp_inject_tldr_button( $content ) {
    $plugin = TLDRWP::get_instance();
    return $plugin->inject_tldr_button( $content );
}

/**
 * Handle AJAX request for TLDR generation.
 * 
 * @deprecated Use TLDRWP::get_instance()->handle_ajax_request() instead
 */
function tldrwp_handle_ajax_request() {
    $plugin = TLDRWP::get_instance();
    $plugin->handle_ajax_request();
}

/**
 * Register the dynamic block and scripts.
 * 
 * @deprecated Use TLDRWP::get_instance()->register_block() instead
 */
function tldrwp_register_block() {
    $plugin = TLDRWP::get_instance();
    $plugin->register_block();
}

/**
 * Render callback for the dynamic block.
 * 
 * @deprecated Use TLDRWP::get_instance()->render_ai_chat_block() instead
 */
function tldrwp_render_ai_chat_block( $attributes, $content ) {
    $plugin = TLDRWP::get_instance();
    return $plugin->render_ai_chat_block( $attributes, $content );
}

/**
 * Register REST API routes.
 * 
 * @deprecated Use TLDRWP::get_instance()->register_rest_routes() instead
 */
function tldrwp_register_rest_routes() {
    $plugin = TLDRWP::get_instance();
    $plugin->register_rest_routes();
}

/**
 * Handle the AI chat request via AI Services plugin.
 * 
 * @deprecated Use TLDRWP::get_instance()->handle_chat_request() instead
 */
function tldrwp_handle_chat_request( WP_REST_Request $request ) {
    $plugin = TLDRWP::get_instance();
    return $plugin->handle_chat_request( $request );
}
