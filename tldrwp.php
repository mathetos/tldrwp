<?php
/**
 * Plugin Name: TLDRWP AI Block
 * Description: Basic WordPress plugin boilerplate with a dynamic Gutenberg block that interacts with the AI Services plugin.
 * Version: 0.1.0
 * Author: OpenAI Codex
 * Requires Plugins: ai-services
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
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
        filemtime( plugin_dir_path( __FILE__ ) . 'blocks/ai-chat/index.js' )
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
    ob_start();
    ?>
    <div id="tldrwp-ai-chat" class="tldrwp-ai-chat">
        <div class="tldrwp-output"></div>
        <input type="text" class="tldrwp-prompt" placeholder="<?php esc_attr_e( 'Ask me anything...', 'tldrwp' ); ?>">
        <button class="tldrwp-submit" data-nonce="<?php echo esc_attr( wp_create_nonce( 'tldrwp_nonce' ) ); ?>">
            <?php esc_html_e( 'Ask AI', 'tldrwp' ); ?>
        </button>
    </div>
    <?php
    return ob_get_clean();
}

add_action( 'rest_api_init', 'tldrwp_register_rest_routes' );

/**
 * Register REST API endpoint for AI interaction.
 */
function tldrwp_register_rest_routes() {
    register_rest_route( 'tldrwp/v1', '/chat', array(
        'methods'             => 'POST',
        'callback'            => 'tldrwp_handle_chat_request',
        'permission_callback' => function() { return wp_verify_nonce( $_POST['nonce'] ?? '', 'tldrwp_nonce' ); },
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

    /**
     * Filter to generate content using AI Services.
     * Developers using different AI services can hook into this filter.
     */
    $response = apply_filters( 'tldrwp_generate_ai_response', '', $prompt );

    return rest_ensure_response( array( 'response' => $response ) );
}
