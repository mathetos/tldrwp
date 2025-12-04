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
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'TLDRWP: Getting available AI platforms for user: ' . ( is_user_logged_in() ? 'logged-in' : 'non-logged-in' ) );
            error_log( 'TLDRWP: Current user capabilities: ' . implode( ', ', array_keys( wp_get_current_user()->allcaps ) ) );
        }
        
        if ( ! function_exists( 'ai_services' ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'TLDRWP: ai_services function not available in get_available_ai_platforms' );
            }
            return array();
        }

        $ai_services = ai_services();
        $available_platforms = array();
        
        // Get all registered service slugs
        $registered_slugs = $ai_services->get_registered_service_slugs();
        
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'TLDRWP: Registered service slugs: ' . implode( ', ', $registered_slugs ) );
        }
        
        foreach ( $registered_slugs as $slug ) {
            // For TL;DR generation, bypass capability checks and check if service has API key
            $is_available = false;
            
            if ( wp_doing_ajax() && isset( $_POST['action'] ) && 'tldrwp_generate_summary' === $_POST['action'] ) {
                // For TL;DR generation, check if service has API key configured
                $api_key_option = 'ais_' . $slug . '_api_key';
                $api_key = get_option( $api_key_option, '' );
                $is_available = ! empty( $api_key );
            } else {
                // For admin context, use normal availability check
                $is_available = $ai_services->is_service_available( $slug );
            }
            
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'TLDRWP: Checking platform ' . $slug . ' - is_available: ' . ( $is_available ? 'true' : 'false' ) );
            }
            
            if ( $is_available ) {
                // Get service name from metadata
                $metadata = $ai_services->get_service_metadata( $slug );
                $name = $metadata ? $metadata->get_name() : ucwords( str_replace( '-', ' ', $slug ) );
                $available_platforms[ $slug ] = $name;
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'TLDRWP: Available platform: ' . $slug . ' -> ' . $name );
                }
            }
        }
        
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'TLDRWP: Total available platforms: ' . count( $available_platforms ) );
        }
        
        return $available_platforms;
    }

    /**
     * Get the selected AI platform or fallback to first available.
     *
     * @return string
     */
    public function get_selected_ai_platform() {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'TLDRWP: Getting selected AI platform' );
            error_log( 'TLDRWP: Plugin settings keys: ' . implode( ', ', array_keys( $this->plugin->settings ) ) );
            error_log( 'TLDRWP: Selected platform from settings: ' . ( isset( $this->plugin->settings['selected_ai_platform'] ) ? $this->plugin->settings['selected_ai_platform'] : 'NOT SET' ) );
        }
        
        // For frontend users, try to use the selected platform directly if it's set
        if ( ! is_admin() && ! empty( $this->plugin->settings['selected_ai_platform'] ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'TLDRWP: Frontend user - using selected platform directly: ' . $this->plugin->settings['selected_ai_platform'] );
            }
            return $this->plugin->settings['selected_ai_platform'];
        }
        
        // For admin users, check availability
        $available_platforms = $this->get_available_ai_platforms();
        
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'TLDRWP: Available platforms: ' . implode( ', ', array_keys( $available_platforms ) ) );
        }
        
        // If no platform is selected or the selected platform is no longer available
        if ( empty( $this->plugin->settings['selected_ai_platform'] ) || ! isset( $available_platforms[ $this->plugin->settings['selected_ai_platform'] ] ) ) {
            // Return the first available platform, or empty string if none available
            $fallback_platform = ! empty( $available_platforms ) ? array_keys( $available_platforms )[0] : '';
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'TLDRWP: Using fallback platform: ' . $fallback_platform );
            }
            return $fallback_platform;
        }
        
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'TLDRWP: Returning selected platform: ' . $this->plugin->settings['selected_ai_platform'] );
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
        
        // For TL;DR generation, bypass capability checks and check if service has API key
        $is_available = false;
        
        if ( wp_doing_ajax() && isset( $_POST['action'] ) && 'tldrwp_generate_summary' === $_POST['action'] ) {
            // Check if API key exists for this platform
            $api_key_option = 'ais_' . $platform_slug . '_api_key';
            $api_key = get_option( $api_key_option, '' );
            $is_available = ! empty( $api_key );
            
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'TLDRWP: Bypassing capability check for TL;DR generation - Platform: ' . $platform_slug . ', API key exists: ' . ( $is_available ? 'yes' : 'no' ) );
            }
        } else {
            // Use normal capability check for admin
            $is_available = $ai_services->is_service_available( $platform_slug );
        }
        
        if ( ! $is_available ) {
            return array();
        }

        try {
            // Get the service instance - bypass capability check for TL;DR generation
            if ( wp_doing_ajax() && isset( $_POST['action'] ) && 'tldrwp_generate_summary' === $_POST['action'] ) {
                // Use reflection to access the private service_registrations property
                $reflection = new ReflectionClass( $ai_services );
                $property = $reflection->getProperty( 'service_registrations' );
                $property->setAccessible( true );
                $service_registrations = $property->getValue( $ai_services );
                
                if ( isset( $service_registrations[ $platform_slug ] ) ) {
                    $service = $service_registrations[ $platform_slug ]->create_instance();
                } else {
                    return array();
                }
            } else {
                $service = $ai_services->get_available_service( $platform_slug );
            }
            
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
        // Method 1: Check if the ai_services function exists (most reliable)
        if ( function_exists( 'ai_services' ) ) {
            return true;
        }
        
        // Method 2: Check if the plugin file exists and is active
        if ( ! function_exists( 'is_plugin_active' ) ) {
            // Only try to load admin functions if we're in admin context
            if ( is_admin() ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            } else {
                // For frontend, check if the plugin file exists and is in active plugins
                $active_plugins = get_option( 'active_plugins', array() );
                return in_array( 'ai-services/ai-services.php', $active_plugins );
            }
        }
        
        // Method 3: Use is_plugin_active if available
        if ( function_exists( 'is_plugin_active' ) ) {
            return is_plugin_active( 'ai-services/ai-services.php' );
        }
        
        return false;
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
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'TLDRWP: Starting AI service call for user: ' . ( is_user_logged_in() ? 'logged-in' : 'non-logged-in' ) );
        }
        
        if ( ! function_exists( 'ai_services' ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'TLDRWP: ai_services function not available' );
            }
            return false;
        }

        $this->plugin->refresh_settings();
        
        // For frontend users, get platform and model directly from settings
        if ( ! is_admin() ) {
            $selected_platform = $this->plugin->settings['selected_ai_platform'];
            $selected_model = $this->plugin->settings['selected_ai_model'];
            
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'TLDRWP: Frontend user - using settings directly - Platform: ' . $selected_platform . ', Model: ' . $selected_model );
            }
        } else {
            // For admin users, use the availability check
            $selected_platform = $this->get_selected_ai_platform();
            $selected_model = $this->get_selected_ai_model();
            
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'TLDRWP: Admin user - using availability check - Platform: ' . $selected_platform . ', Model: ' . $selected_model );
            }
        }

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'TLDRWP: Final selected platform: ' . $selected_platform . ', Selected model: ' . $selected_model );
        }

        if ( empty( $selected_platform ) || empty( $selected_model ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'TLDRWP: Platform or model is empty - Platform: ' . ( empty( $selected_platform ) ? 'EMPTY' : $selected_platform ) . ', Model: ' . ( empty( $selected_model ) ? 'EMPTY' : $selected_model ) );
            }
            return false;
        }

        try {
            $ai_services = ai_services();
            
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'TLDRWP: AI Services instance created successfully' );
            }
            
            // For TL;DR generation, bypass capability checks
            $service = null;
            if ( wp_doing_ajax() && isset( $_POST['action'] ) && 'tldrwp_generate_summary' === $_POST['action'] ) {
                // For TL;DR generation, create service directly without capability check
                try {
                    // Use reflection to access the private service_registrations property
                    $reflection = new ReflectionClass( $ai_services );
                    $property = $reflection->getProperty( 'service_registrations' );
                    $property->setAccessible( true );
                    $service_registrations = $property->getValue( $ai_services );
                    
                    if ( isset( $service_registrations[ $selected_platform ] ) ) {
                        $service = $service_registrations[ $selected_platform ]->create_instance();
                        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                            error_log( 'TLDRWP: Created service instance directly for TL;DR generation' );
                        }
                    } else {
                        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                            error_log( 'TLDRWP: Service registration not found for platform: ' . $selected_platform );
                        }
                        return false;
                    }
                } catch ( Exception $e ) {
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'TLDRWP: Failed to create service instance directly: ' . $e->getMessage() );
                    }
                    return false;
                }
            } else {
                // For admin context, use normal availability check
                if ( ! $ai_services->is_service_available( $selected_platform ) ) {
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'TLDRWP: Platform ' . $selected_platform . ' is not available' );
                    }
                    return false;
                }
                
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'TLDRWP: Platform ' . $selected_platform . ' is available' );
                }

                // Get the service instance
                $service = $ai_services->get_available_service( $selected_platform );
            }
            
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
                    
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'TLDRWP: Model instance created successfully' );
                    }
                    
                    // Generate content using the model
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'TLDRWP: Calling model->generate_text with prompt length: ' . strlen( $prompt ) );
                    }
                    
                    $candidates = $model->generate_text( $prompt );
                    
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'TLDRWP: Model response received, candidates type: ' . gettype( $candidates ) );
                        if ( is_object( $candidates ) ) {
                            error_log( 'TLDRWP: Candidates class: ' . get_class( $candidates ) );
                        }
                    }
                    
                    // Extract text from candidates using AI Services helpers
                    if ( class_exists( 'Felix_Arntz\AI_Services\Services\API\Helpers' ) ) {
                        $helpers = 'Felix_Arntz\AI_Services\Services\API\Helpers';
                        $candidate_contents = $helpers::get_candidate_contents( $candidates );
                        $text = $helpers::get_text_from_contents( $candidate_contents );
                        
                        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                            error_log( 'TLDRWP: Extracted text using helpers, length: ' . strlen( $text ) );
                        }
                        
                        return $text;
                    } else {
                        // Fallback: try to extract text manually
                        if ( is_object( $candidates ) && method_exists( $candidates, 'to_array' ) ) {
                            $candidates_array = $candidates->to_array();
                            if ( isset( $candidates_array[0]['content']['parts'][0]['text'] ) ) {
                                $text = $candidates_array[0]['content']['parts'][0]['text'];
                                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                                    error_log( 'TLDRWP: Extracted text manually, length: ' . strlen( $text ) );
                                }
                                return $text;
                            }
                        }
                        
                        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                            error_log( 'TLDRWP: Failed to extract text from candidates' );
                        }
                        return false;
                    }
                } catch ( Exception $e ) {
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'TLDRWP: Exception in AI service call (capability method): ' . $e->getMessage() );
                    }
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
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'TLDRWP: Exception in AI service call (fallback method): ' . $e->getMessage() );
                    }
                    return false;
                }
            }
            
        } catch ( Exception $e ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'TLDRWP: Exception in AI service call (outer try-catch): ' . $e->getMessage() );
            }
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