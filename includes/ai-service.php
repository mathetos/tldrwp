<?php
/**
 * TLDRWP AI Service Integration Class
 *
 * @package TLDRWP
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * TLDRWP AI Service Integration Class
 *
 * Handles AI platform detection, model management, API calls, and response formatting.
 */
class TLDRWP_AI_Service {

    /**
     * Plugin instance.
     *
     * @var TLDRWP
     */
    private $plugin;

    /**
     * Constructor.
     *
     * @param TLDRWP $plugin Plugin instance.
     */
    public function __construct( $plugin ) {
        $this->plugin = $plugin;
    }

    /**
     * Get available AI platforms that have API keys configured.
     *
     * @return array
     */
    public function get_available_ai_platforms() {
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
     * Get the selected AI platform or fallback to first available.
     *
     * @return string
     */
    public function get_selected_ai_platform() {
        $available_platforms = $this->get_available_ai_platforms();
        
        // If no platform is selected or the selected platform is no longer available
        if ( empty( $this->plugin->settings['selected_ai_platform'] ) || ! isset( $available_platforms[ $this->plugin->settings['selected_ai_platform'] ] ) ) {
            // Return the first available platform, or empty string if none available
            return ! empty( $available_platforms ) ? array_keys( $available_platforms )[0] : '';
        }
        
        return $this->plugin->settings['selected_ai_platform'];
    }

    /**
     * Get available AI models for a specific platform.
     *
     * @param string $platform_slug Platform slug.
     * @return array
     */
    public function get_available_ai_models( $platform_slug = null ) {
        if ( ! function_exists( 'ai_services' ) ) {
            return array();
        }

        // If no platform specified, get the selected platform
        if ( empty( $platform_slug ) ) {
            $platform_slug = $this->get_selected_ai_platform();
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
     * Get the selected AI model or fallback to first available.
     *
     * @return string
     */
    public function get_selected_ai_model() {
        $selected_platform = $this->get_selected_ai_platform();
        
        if ( empty( $selected_platform ) ) {
            return '';
        }
        
        $available_models = $this->get_available_ai_models( $selected_platform );
        
        // If no model is selected or the selected model is no longer available
        if ( empty( $this->plugin->settings['selected_ai_model'] ) || ! isset( $available_models[ $this->plugin->settings['selected_ai_model'] ] ) ) {
            // Return the first available model, or empty string if none available
            return ! empty( $available_models ) ? array_keys( $available_models )[0] : '';
        }
        
        return $this->plugin->settings['selected_ai_model'];
    }

    /**
     * Check if AI Services plugin is active.
     *
     * @return bool
     */
    public function check_ai_services() {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return is_plugin_active( 'ai-services/ai-services.php' );
    }

    /**
     * Test AI connection via AJAX.
     */
    public function test_ai_connection() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tldrwp_ajax_nonce' ) ) {
            wp_send_json_error( __( 'Security check failed', 'tldrwp' ) );
        }

        if ( ! $this->check_ai_services() ) {
            wp_send_json_error( __( 'AI Services plugin is not active.', 'tldrwp' ) );
        }

        $this->plugin->refresh_settings();
        $selected_platform = $this->get_selected_ai_platform();
        if ( empty( $selected_platform ) ) {
            wp_send_json_error( __( 'No AI platform is selected.', 'tldrwp' ) );
        }

        $selected_model = $this->get_selected_ai_model();
        if ( empty( $selected_model ) ) {
            wp_send_json_error( __( 'No AI model is selected.', 'tldrwp' ) );
        }

        $test_prompt = 'Please respond with "Connection successful" if you can read this message.';
        $response = $this->call_ai_service( $test_prompt );

        if ( ! empty( $response ) ) {
            wp_send_json_success( __( 'Connection successful!', 'tldrwp' ) );
        } else {
            wp_send_json_error( __( 'Connection failed. Please check your API configuration.', 'tldrwp' ) );
        }
    }

    /**
     * Call AI service with the given prompt.
     *
     * @param string $prompt The prompt to send to the AI service.
     * @return string|false The AI response or false on failure.
     */
    public function call_ai_service( $prompt ) {
        if ( ! function_exists( 'ai_services' ) ) {
            return false;
        }

        $this->plugin->refresh_settings();
        $selected_platform = $this->get_selected_ai_platform();
        $selected_model = $this->get_selected_ai_model();

        if ( empty( $selected_platform ) || empty( $selected_model ) ) {
            return false;
        }

        try {
            $ai_services = ai_services();
            
            // Check if the platform is available
            if ( ! $ai_services->is_service_available( $selected_platform ) ) {
                return false;
            }

            // Get the service instance
            $service = $ai_services->get_available_service( $selected_platform );
            
            // Check if the model is available for this service
            $available_models = $service->list_models();
            if ( ! isset( $available_models[ $selected_model ] ) ) {
                return false;
            }

            // Use the AI Services plugin's capability constants
            if ( class_exists( 'Felix_Arntz\AI_Services\Services\API\Enums\AI_Capability' ) ) {
                $ai_capability = 'Felix_Arntz\AI_Services\Services\API\Enums\AI_Capability';
                
                try {
                    // Get the model instance
                    $model = $service->get_model( array(
                        'model' => $selected_model,
                        'capabilities' => array( $ai_capability::TEXT_GENERATION ),
                        'feature' => 'tldrwp-summary',
                    ) );
                    
                    // Generate content using the model
                    $candidates = $model->generate_text( $prompt );
                    
                    // Extract text from candidates using AI Services helpers
                    if ( class_exists( 'Felix_Arntz\AI_Services\Services\API\Helpers' ) ) {
                        $helpers = 'Felix_Arntz\AI_Services\Services\API\Helpers';
                        $candidate_contents = $helpers::get_candidate_contents( $candidates );
                        $text = $helpers::get_text_from_contents( $candidate_contents );
                        return $text;
                    } else {
                        // Fallback: try to extract text manually
                        if ( is_object( $candidates ) && method_exists( $candidates, 'to_array' ) ) {
                            $candidates_array = $candidates->to_array();
                            if ( isset( $candidates_array[0]['content']['parts'][0]['text'] ) ) {
                                return $candidates_array[0]['content']['parts'][0]['text'];
                            }
                        }
                        return false;
                    }
                } catch ( Exception $e ) {
                    return false;
                }
            } else {
                // Fallback to manual capability specification
                try {
                    $model = $service->get_model( array(
                        'model' => $selected_model,
                        'capabilities' => array( 'text_generation' ),
                        'feature' => 'tldrwp-summary',
                    ) );
                    
                    // Generate content using the model
                    $candidates = $model->generate_text( $prompt );
                    
                    // Extract text from candidates using AI Services helpers
                    if ( class_exists( 'Felix_Arntz\AI_Services\Services\API\Helpers' ) ) {
                        $helpers = 'Felix_Arntz\AI_Services\Services\API\Helpers';
                        $candidate_contents = $helpers::get_candidate_contents( $candidates );
                        $text = $helpers::get_text_from_contents( $candidate_contents );
                        return $text;
                    } else {
                        // Fallback: try to extract text manually
                        if ( is_object( $candidates ) && method_exists( $candidates, 'to_array' ) ) {
                            $candidates_array = $candidates->to_array();
                            if ( isset( $candidates_array[0]['content']['parts'][0]['text'] ) ) {
                                return $candidates_array[0]['content']['parts'][0]['text'];
                            }
                        }
                        return false;
                    }
                } catch ( Exception $e ) {
                    return false;
                }
            }
            
        } catch ( Exception $e ) {
            return false;
        }
    }

    /**
     * Format AI response for display.
     *
     * @param string $raw_response Raw AI response.
     * @return string Formatted response.
     */
    public function format_ai_response( $raw_response ) {
        if ( empty( $raw_response ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'TLDRWP: Raw response is empty' );
            }
            return '';
        }

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'TLDRWP: Formatting response: ' . substr( $raw_response, 0, 200 ) . '...' );
        }

        // Remove markdown code blocks
        $response = $this->remove_markdown_code_blocks( $raw_response );

        // Convert plain text to HTML
        $response = $this->convert_plain_text_to_html( $response );

        // Sanitize and format using WordPress functions
        $response = wp_kses_post( $response );
        $response = wpautop( $response );

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'TLDRWP: Final formatted response: ' . substr( $response, 0, 200 ) . '...' );
        }

        return $response;
    }

    /**
     * Convert plain text to HTML or handle existing HTML.
     *
     * @param string $text Text that may be plain text or HTML.
     * @return string HTML formatted text.
     */
    private function convert_plain_text_to_html( $text ) {
        // Check if the text already contains HTML tags
        if ( preg_match( '/<[^>]+>/', $text ) ) {
            // Text already contains HTML, just clean it up
            return $text;
        }

        // Convert plain text bullet points to HTML lists
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
     * @param string $text Text containing markdown code blocks.
     * @return string Text with code blocks removed.
     */
    private function remove_markdown_code_blocks( $text ) {
        // Extract content from ```code blocks``` (keep the content, remove the markers)
        $text = preg_replace( '/```(?:html)?\s*\n?(.*?)\n?```/s', '$1', $text );
        
        // Remove `inline code` markers but keep the content
        $text = preg_replace( '/`([^`]+)`/', '$1', $text );
        
        return $text;
    }
} 