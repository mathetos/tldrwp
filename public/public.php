<?php
/**
 * TLDRWP Public/Frontend Management Class
 *
 * @package TLDRWP
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * TLDRWP Public/Frontend Management Class
 *
 * Handles frontend button injection, AJAX handlers, asset enqueuing, and content filtering.
 */
class TLDRWP_Public {

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
        $this->init();
    }

    /**
     * Initialize public functionality.
     */
    private function init() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        add_filter( 'the_content', array( $this, 'inject_tldr_button' ) );
        add_action( 'wp_ajax_tldrwp_generate_summary', array( $this, 'handle_ajax_request' ) );
        add_action( 'wp_ajax_nopriv_tldrwp_generate_summary', array( $this, 'handle_ajax_request' ) );
        add_action( 'init', array( $this, 'register_block' ) );
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
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
        
        if ( ! in_array( $current_post_type, $this->plugin->settings['enabled_post_types'] ) ) {
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
            'enable_social_sharing' => $this->plugin->settings['enable_social_sharing'],
            'success_message' => $this->plugin->settings['success_message'],
            'article_id' => get_the_ID(),
            'post_title' => get_the_title(),
            'post_url' => get_permalink()
        ) );

        // Output social sharing data as hidden JSON
        if ( $this->plugin->settings['enable_social_sharing'] ) {
            $post_id = get_the_ID();
            $post = get_post( $post_id );
            
            // Get excerpt from post meta or content
            $excerpt = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
            if ( ! $excerpt ) {
                $excerpt = get_the_excerpt( $post_id );
            }
            if ( ! $excerpt ) {
                $content = wp_strip_all_tags( $post->post_content );
                $excerpt = wp_trim_words( $content, 25, '...' );
            }
            
            $share_data = array(
                'title' => get_the_title( $post_id ),
                'url' => get_permalink( $post_id ),
                'excerpt' => $excerpt
            );
            
            echo '<script type="application/json" id="tldrwp-share-data" style="display:none;">' . 
                 wp_json_encode( $share_data ) . 
                 '</script>';
        }
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
        return in_array( $current_post_type, $this->plugin->settings['enabled_post_types'] );
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
        $prompt = ! empty( $page_prompt ) ? $page_prompt : $this->plugin->settings['default_prompt'];
        
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
            esc_attr( $this->plugin->settings['default_prompt'] ),
            esc_attr( $prompt ),
            esc_attr( $this->plugin->settings['button_title'] ),
            esc_html( $this->plugin->settings['button_title'] ),
            esc_html( $this->plugin->settings['button_description'] )
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
        if ( ! $this->plugin->ai_service->check_ai_services() ) {
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
        $raw_response = $this->plugin->ai_service->call_ai_service( $formatted_prompt );
        
        // Debug: Log the raw response
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'TLDRWP Raw Response: ' . print_r( $raw_response, true ) );
        }
        
        // Format the response using WordPress native functions
        $response = $this->plugin->ai_service->format_ai_response( $raw_response );

        if ( empty( $response ) ) {
            // Check if selected platform is available
            $selected_platform = $this->plugin->ai_service->get_selected_ai_platform();
            
            if ( empty( $selected_platform ) ) {
                wp_send_json_error( __( 'No AI platform is selected or available. Please configure an AI provider in the AI Services plugin settings.', 'tldrwp' ) );
            } else {
                wp_send_json_error( __( 'AI service returned an empty response. Please check your API configuration and try again.', 'tldrwp' ) );
            }
        }

        // Get current post information for hooks
        $article_id = get_the_ID();
        $article_title = get_the_title();

        // Apply filters to the response
        $response = tldrwp_filter_response( $response, $article_id, $article_title );

        // Generate social sharing data if enabled
        $social_sharing_data = null;
        if ( $this->plugin->settings['enable_social_sharing'] ) {
            $social_sharing_data = $this->plugin->social_sharing->prepare_share_data( get_post( $article_id ) );
        }

        // Build the complete response with action hooks
        $response_data = array(
            'response' => $response,
            'article_id' => $article_id,
            'article_title' => $article_title,
            'social_sharing_data' => $social_sharing_data,
            'action_hooks' => array(
                'tldr_before_summary_heading' => $this->get_action_hook_output( 'tldr_before_summary_heading', $response, $article_id, $article_title ),
                'tldr_after_summary_heading' => $this->get_action_hook_output( 'tldr_after_summary_heading', $response, $article_id, $article_title ),
                'tldr_before_summary_copy' => $this->get_action_hook_output( 'tldr_before_summary_copy', $response, $article_id, $article_title ),
                'tldr_after_summary_copy' => $this->get_action_hook_output( 'tldr_after_summary_copy', $response, $article_id, $article_title ),
                'tldr_summary_footer' => $this->get_action_hook_output( 'tldr_summary_footer', $response, $article_id, $article_title )
            )
        );

        wp_send_json_success( $response_data );
    }

    /**
     * Register the dynamic block and scripts.
     */
    public function register_block() {
        if ( ! $this->plugin->ai_service->check_ai_services() ) {
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
        // Check rate limiting
        $user_id = get_current_user_id();
        if ( $this->plugin->is_rate_limit_exceeded( $user_id ) ) {
            return new WP_REST_Response( array( 'error' => __( 'Rate limit exceeded. Please wait before generating another TL;DR summary.', 'tldrwp' ) ), 429 );
        }

        if ( ! $this->plugin->ai_service->check_ai_services() ) {
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

    /**
     * Get output from action hooks
     *
     * @param string $hook_name The hook name
     * @param string $response The AI response
     * @param int $article_id The article ID
     * @param string $article_title The article title
     * @return string The hook output
     */
    private function get_action_hook_output( $hook_name, $response, $article_id, $article_title ) {
        ob_start();
        do_action( $hook_name, $response, $article_id, $article_title );
        return ob_get_clean();
    }
} 