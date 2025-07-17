<?php
/**
 * Main TLDRWP Plugin Class
 *
 * @package TLDRWP
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Main TLDRWP Plugin Class
 *
 * Handles the core functionality of the TLDRWP plugin including
 * initialization, settings management, and AI service integration.
 */
class TLDRWP {

    /**
     * Plugin version.
     *
     * @var string
     */
    const VERSION = '0.1.0';

    /**
     * Plugin instance.
     *
     * @var TLDRWP
     */
    private static $instance = null;

    /**
     * Plugin settings.
     *
     * @var array
     */
    private $settings = array();

    /**
     * Get plugin instance.
     *
     * @return TLDRWP
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Initialize the plugin.
     */
    private function init() {
        // Load settings
        $this->load_settings();

        // Check dependencies
        $this->check_dependencies();

        // Initialize hooks
        $this->init_hooks();
    }

    /**
     * Load plugin settings.
     */
    private function load_settings() {
        $this->settings = $this->get_settings();
    }

    /**
     * Refresh settings from database.
     */
    public function refresh_settings() {
        $this->settings = $this->get_settings();
    }

    /**
     * Check plugin dependencies.
     */
    private function check_dependencies() {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        if ( ! is_plugin_active( 'ai-services/ai-services.php' ) ) {
            add_action( 'admin_notices', array( $this, 'admin_notice_ai_services_required' ) );
        }
    }

    /**
     * Initialize WordPress hooks.
     */
    private function init_hooks() {
        // Admin hooks
        add_action( 'admin_init', array( $this, 'init_settings' ) );
        add_action( 'admin_notices', array( $this, 'admin_notice_ai_configuration' ) );

        // AJAX hooks
        add_action( 'wp_ajax_tldrwp_test_ai', array( $this, 'test_ai_connection' ) );
        add_action( 'wp_ajax_nopriv_tldrwp_test_ai', array( $this, 'test_ai_connection' ) );
        add_action( 'wp_ajax_tldrwp_get_models', array( $this, 'ajax_get_models' ) );
        add_action( 'wp_ajax_tldrwp_generate_summary', array( $this, 'handle_ajax_request' ) );
        add_action( 'wp_ajax_nopriv_tldrwp_generate_summary', array( $this, 'handle_ajax_request' ) );

        // Frontend hooks
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        add_filter( 'the_content', array( $this, 'inject_tldr_button' ) );

        // Block registration
        add_action( 'init', array( $this, 'register_block' ) );

        // REST API
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
    }

    /**
     * Get default settings for TLDRWP.
     *
     * @return array
     */
    public function get_default_settings() {
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
     * Get current TLDRWP settings.
     *
     * @return array
     */
    public function get_settings() {
        $defaults = $this->get_default_settings();
        $settings = get_option( 'tldrwp_settings', array() );
        
        return wp_parse_args( $settings, $defaults );
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
        if ( empty( $this->settings['selected_ai_platform'] ) || ! isset( $available_platforms[ $this->settings['selected_ai_platform'] ] ) ) {
            // Return the first available platform, or empty string if none available
            return ! empty( $available_platforms ) ? array_keys( $available_platforms )[0] : '';
        }
        
        return $this->settings['selected_ai_platform'];
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
        if ( empty( $this->settings['selected_ai_model'] ) || ! isset( $available_models[ $this->settings['selected_ai_model'] ] ) ) {
            // Return the first available model, or empty string if none available
            return ! empty( $available_models ) ? array_keys( $available_models )[0] : '';
        }
        
        return $this->settings['selected_ai_model'];
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

        $this->refresh_settings();
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

        $this->refresh_settings();
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

    /**
     * Admin notice for AI configuration.
     */
    public function admin_notice_ai_configuration() {
        // Only show on the reading settings page
        if ( ! $this->is_reading_settings_page() ) {
            return;
        }
        
        // Check if AI Services plugin is active
        if ( ! $this->check_ai_services() ) {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'TLDRWP requires the AI Services plugin to be installed and active.', 'tldrwp' ) . '</p></div>';
            return;
        }

        // Check if AI platform is selected
        $selected_platform = $this->get_selected_ai_platform();
        if ( empty( $selected_platform ) ) {
            echo '<div class="notice notice-warning"><p>' . esc_html__( 'TLDRWP: No AI platform is selected. Please configure an AI provider in the AI Services plugin settings.', 'tldrwp' ) . '</p></div>';
        }
    }

    /**
     * Check if we're on the reading settings page.
     *
     * @return bool
     */
    private function is_reading_settings_page() {
        global $pagenow;
        return is_admin() && 'options-reading.php' === $pagenow;
    }

    /**
     * Initialize settings page.
     */
    public function init_settings() {
        // Add settings section to Reading page
        add_settings_section(
            'tldrwp_settings_section',
            __( 'TL;DR Settings', 'tldrwp' ),
            array( $this, 'settings_section_callback' ),
            'reading'
        );

        // Register settings
        register_setting( 'reading', 'tldrwp_settings', array(
            'sanitize_callback' => array( $this, 'sanitize_settings' ),
            'default' => $this->get_default_settings()
        ) );

        // Add settings fields
        add_settings_field(
            'tldrwp_enabled_post_types',
            __( 'Enable on Post Types', 'tldrwp' ),
            array( $this, 'enabled_post_types_callback' ),
            'reading',
            'tldrwp_settings_section'
        );

        add_settings_field(
            'tldrwp_default_prompt',
            __( 'Default Prompt', 'tldrwp' ),
            array( $this, 'default_prompt_callback' ),
            'reading',
            'tldrwp_settings_section'
        );

        add_settings_field(
            'tldrwp_button_text',
            __( 'Button Text', 'tldrwp' ),
            array( $this, 'button_text_callback' ),
            'reading',
            'tldrwp_settings_section'
        );

        add_settings_field(
            'tldrwp_enable_social_sharing',
            __( 'Social Sharing', 'tldrwp' ),
            array( $this, 'enable_social_sharing_callback' ),
            'reading',
            'tldrwp_settings_section'
        );

        add_settings_field(
            'tldrwp_ai_platform',
            __( 'AI Platform', 'tldrwp' ),
            array( $this, 'ai_platform_callback' ),
            'reading',
            'tldrwp_settings_section'
        );

        add_settings_field(
            'tldrwp_ai_model',
            __( 'AI Model', 'tldrwp' ),
            array( $this, 'ai_model_callback' ),
            'reading',
            'tldrwp_settings_section'
        );

        add_settings_field(
            'tldrwp_test_connection',
            __( 'Test Connection', 'tldrwp' ),
            array( $this, 'test_connection_callback' ),
            'reading',
            'tldrwp_settings_section'
        );
    }

    /**
     * AJAX handler for getting models.
     */
    public function ajax_get_models() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tldrwp_ajax_nonce' ) ) {
            wp_send_json_error( __( 'Security check failed', 'tldrwp' ) );
        }

        if ( ! isset( $_POST['platform'] ) ) {
            wp_send_json_error( __( 'Platform parameter is required.', 'tldrwp' ) );
        }

        $platform = sanitize_text_field( wp_unslash( $_POST['platform'] ) );
        $models = $this->get_available_ai_models( $platform );
        
        $model_names = array();
        foreach ( $models as $model_slug => $model_data ) {
            $model_names[ $model_slug ] = isset( $model_data['name'] ) ? $model_data['name'] : $model_slug;
        }
        
        wp_send_json_success( $model_names );
    }

    /**
     * Enqueue frontend assets.
     */
    public function enqueue_frontend_assets() {
        // Only enqueue on single posts/pages where the button might appear
        if ( ! is_singular() ) {
            return;
        }

        $current_post_type = get_post_type();
        
        if ( ! in_array( $current_post_type, $this->settings['enabled_post_types'] ) ) {
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
            'enable_social_sharing' => $this->settings['enable_social_sharing']
        ) );
    }

    /**
     * Check if TLDR button should be shown.
     *
     * @return bool
     */
    public function should_show_button() {
        if ( is_admin() || is_feed() || is_404() || is_search() || is_archive() ) {
            return false;
        }

        $current_post_type = get_post_type();
        return in_array( $current_post_type, $this->settings['enabled_post_types'] );
    }

    /**
     * Inject TLDR button into content.
     *
     * @param string $content Post content.
     * @return string Modified content.
     */
    public function inject_tldr_button( $content ) {
        // Don't inject on admin pages or if button shouldn't be shown
        if ( is_admin() || ! $this->should_show_button() ) {
            return $content;
        }

        $post_id = get_the_ID();
        
        // Get page-specific prompt or use default
        $page_prompt = get_post_meta( $post_id, '_tldrwp_custom_prompt', true );
        $prompt = ! empty( $page_prompt ) ? $page_prompt : $this->settings['default_prompt'];
        
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
            esc_attr( $this->settings['default_prompt'] ),
            esc_attr( $prompt ),
            esc_attr( $this->settings['button_title'] ),
            esc_html( $this->settings['button_title'] ),
            esc_html( $this->settings['button_description'] )
        );
        
        // Insert button at the beginning of content
        $content = $button_html . $content;
        
        return $content;
    }

    /**
     * Handle AJAX request for TLDR generation.
     */
    public function handle_ajax_request() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tldrwp_ajax_nonce' ) ) {
            wp_send_json_error( __( 'Security check failed', 'tldrwp' ) );
        }

        // Check if AI Services is active
        if ( ! $this->check_ai_services() ) {
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
        $raw_response = $this->call_ai_service( $formatted_prompt );
        
        // Debug: Log the raw response
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'TLDRWP Raw Response: ' . print_r( $raw_response, true ) );
        }
        
        // Format the response using WordPress native functions
        $response = $this->format_ai_response( $raw_response );

        if ( empty( $response ) ) {
            // Check if selected platform is available
            $selected_platform = $this->get_selected_ai_platform();
            
            if ( empty( $selected_platform ) ) {
                wp_send_json_error( __( 'No AI platform is selected or available. Please configure an AI provider in the AI Services plugin settings.', 'tldrwp' ) );
            } else {
                wp_send_json_error( __( 'AI service returned an empty response. Please check your API configuration and try again.', 'tldrwp' ) );
            }
        }

        wp_send_json_success( $response );
    }

    /**
     * Register the dynamic block and scripts.
     */
    public function register_block() {
        if ( ! $this->check_ai_services() ) {
            return;
        }

        wp_register_script(
            'tldrwp-block',
            plugins_url( 'blocks/ai-chat/index.js', TLDRWP_PLUGIN_FILE ),
            array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-api-fetch' ),
            filemtime( plugin_dir_path( TLDRWP_PLUGIN_FILE ) . 'blocks/ai-chat/index.js' ),
            true
        );

        wp_register_style(
            'tldrwp-block',
            plugins_url( 'blocks/ai-chat/style.css', TLDRWP_PLUGIN_FILE ),
            array(),
            filemtime( plugin_dir_path( TLDRWP_PLUGIN_FILE ) . 'blocks/ai-chat/style.css' )
        );

        register_block_type( 'tldrwp/ai-chat', array(
            'editor_script'   => 'tldrwp-block',
            'editor_style'    => 'tldrwp-block',
            'style'           => 'tldrwp-block',
            'render_callback' => array( $this, 'render_ai_chat_block' ),
        ) );
    }

    /**
     * Render callback for the dynamic block.
     *
     * @param array  $attributes Block attributes.
     * @param string $content Block content.
     * @return string
     */
    public function render_ai_chat_block( $attributes, $content ) {
        // Return empty content for deprecated block
        // TL;DR functionality is now automatically available on all posts
        return '';
    }

    /**
     * Register REST API routes.
     */
    public function register_rest_routes() {
        register_rest_route( 'tldrwp/v1', '/chat', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'handle_chat_request' ),
            'permission_callback' => function() { 
                return isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tldrwp_nonce' ); 
            },
        ) );
    }

    /**
     * Handle the AI chat request via AI Services plugin.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function handle_chat_request( WP_REST_Request $request ) {
        if ( ! $this->check_ai_services() ) {
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

    // ============================================================================
    // SETTINGS CALLBACK METHODS
    // These will be moved to the Admin class in Phase 2
    // ============================================================================

    /**
     * Settings section callback.
     */
    public function settings_section_callback() {
        echo '<p>' . esc_html__( 'Configure TL;DR settings for your content. The TL;DR button will appear on enabled post types.', 'tldrwp' ) . '</p>';
    }

    /**
     * Enabled post types callback.
     */
    public function enabled_post_types_callback() {
        $this->refresh_settings();
        $enabled_post_types = $this->settings['enabled_post_types'];
        
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
    public function default_prompt_callback() {
        $this->refresh_settings();
        echo '<textarea name="tldrwp_settings[default_prompt]" rows="3" cols="50" style="width: 100%;">' . esc_textarea( $this->settings['default_prompt'] ) . '</textarea>';
        echo '<p class="description">' . esc_html__( 'Default prompt used for TL;DR generation. You can customize this per post using the post meta field.', 'tldrwp' ) . '</p>';
    }

    /**
     * Button text callback.
     */
    public function button_text_callback() {
        $this->refresh_settings();
        echo '<p><label>' . esc_html__( 'Button Title:', 'tldrwp' ) . '</label><br>';
        echo '<input type="text" name="tldrwp_settings[button_title]" value="' . esc_attr( $this->settings['button_title'] ) . '" style="width: 100%;">';
        echo '</p>';
        echo '<p><label>' . esc_html__( 'Button Description:', 'tldrwp' ) . '</label><br>';
        echo '<input type="text" name="tldrwp_settings[button_description]" value="' . esc_attr( $this->settings['button_description'] ) . '" style="width: 100%;">';
        echo '</p>';
    }

    /**
     * Enable social sharing callback.
     */
    public function enable_social_sharing_callback() {
        $this->refresh_settings();
        $checked = $this->settings['enable_social_sharing'] ? 'checked' : '';
        echo '<label>';
        echo '<input type="checkbox" name="tldrwp_settings[enable_social_sharing]" value="1" ' . $checked . '> ';
        echo esc_html__( 'Enable social sharing buttons on TL;DR summaries', 'tldrwp' );
        echo '</label>';
    }

    /**
     * AI platform callback.
     */
    public function ai_platform_callback() {
        $this->refresh_settings();
        $available_platforms = $this->get_available_ai_platforms();
        
        if ( empty( $available_platforms ) ) {
            echo '<p class="description">' . esc_html__( 'No AI platforms available. Please configure API keys in the AI Services plugin settings.', 'tldrwp' ) . '</p>';
            return;
        }
        
        echo '<select name="tldrwp_settings[selected_ai_platform]" id="tldrwp_ai_platform">';
        echo '<option value="">' . esc_html__( 'Select AI Platform', 'tldrwp' ) . '</option>';
        
        foreach ( $available_platforms as $slug => $name ) {
            $selected = ( $this->settings['selected_ai_platform'] === $slug ) ? 'selected' : '';
            echo '<option value="' . esc_attr( $slug ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">' . esc_html__( 'Select which AI platform to use for TL;DR generation.', 'tldrwp' ) . '</p>';
    }

    /**
     * AI model callback.
     */
    public function ai_model_callback() {
        $this->refresh_settings();
        $selected_platform = $this->get_selected_ai_platform();
        
        if ( empty( $selected_platform ) ) {
            echo '<p class="description">' . esc_html__( 'Please select an AI platform first.', 'tldrwp' ) . '</p>';
            return;
        }
        
        $available_models = $this->get_available_ai_models( $selected_platform );
        
        if ( empty( $available_models ) ) {
            echo '<p class="description">' . esc_html__( 'No models available for the selected platform.', 'tldrwp' ) . '</p>';
            return;
        }
        
        echo '<select name="tldrwp_settings[selected_ai_model]" id="tldrwp_ai_model">';
        echo '<option value="">' . esc_html__( 'Select AI Model', 'tldrwp' ) . '</option>';
        
        foreach ( $available_models as $model_slug => $model_data ) {
            $selected = ( $this->settings['selected_ai_model'] === $model_slug ) ? 'selected' : '';
            $model_name = isset( $model_data['name'] ) ? $model_data['name'] : $model_slug;
            echo '<option value="' . esc_attr( $model_slug ) . '" ' . $selected . '>' . esc_html( $model_name ) . '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">' . esc_html__( 'Select which AI model to use for TL;DR generation.', 'tldrwp' ) . '</p>';
    }

    /**
     * Test connection callback.
     */
    public function test_connection_callback() {
        $this->refresh_settings();
        $selected_platform = $this->get_selected_ai_platform();
        
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
     *
     * @param array $input Input settings.
     * @return array Sanitized settings.
     */
    public function sanitize_settings( $input ) {
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
} 