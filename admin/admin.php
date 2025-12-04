<?php
/**
 * TLDRWP Admin Management Class
 *
 * @package TLDRWP
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * TLDRWP Admin Management Class
 *
 * Handles admin notices, configuration UI, and test connection functionality.
 */
class TLDRWP_Admin {

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
     * Initialize admin functionality.
     */
    private function init() {
        add_action( 'admin_notices', array( $this, 'admin_notice_ai_configuration' ) );
        add_action( 'wp_ajax_tldrwp_test_ai', array( $this, 'test_ai_connection' ) );
        add_action( 'wp_ajax_nopriv_tldrwp_test_ai', array( $this, 'test_ai_connection' ) );
        
        // Block Editor integration - simplified
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
        add_action( 'init', array( $this, 'register_post_meta' ) );
        
        // Debug: Add save_post hook to track meta saves
        add_action( 'save_post', array( $this, 'debug_save_post' ), 10, 2 );
        
        // Debug: Add REST API filter to see what's being returned
        add_filter( 'rest_prepare_post', array( $this, 'debug_rest_response' ), 10, 3 );
    }

    /**
     * Admin notice for AI configuration.
     */
    public function admin_notice_ai_configuration() {
        // Only show on the reading settings page
        if ( ! $this->is_reading_settings_page() ) {
            return;
        }
        
        // Check if WordPress AI plugin is active and has credentials
        if ( ! $this->plugin->check_ai_plugin() ) {
            // Try to find the AI plugin settings page URL
            $settings_url = admin_url( 'options-general.php?page=ai-experiments' );
            
            // Check if the AI plugin has a different settings page
            if ( function_exists( 'WordPress\AI\get_settings_page_url' ) ) {
                $settings_url = \WordPress\AI\get_settings_page_url();
            } elseif ( class_exists( '\WordPress\AI\Admin\Settings' ) ) {
                // Try to get the page slug from the AI plugin
                $settings_url = admin_url( 'options-general.php?page=ai-experiments' );
            }
            
            echo '<div class="notice notice-error"><p>';
            echo esc_html__( 'TLDRWP requires the WordPress AI plugin to be installed and active with AI credentials configured.', 'tldrwp' );
            echo ' <a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Configure AI credentials', 'tldrwp' ) . '</a>';
            echo '</p></div>';
            return;
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
     * Test AI connection via AJAX.
     */
    public function test_ai_connection() {
        $this->plugin->ai_service->test_ai_connection();
    }




    /**
     * Enqueue Block Editor assets.
     */
    public function enqueue_block_editor_assets() {
        // Only enqueue on supported post types
        $post_type = get_post_type();
        $supported_types = array( 'post', 'page' );
        
        if ( ! in_array( $post_type, $supported_types, true ) ) {
            return;
        }

        // Get the built asset file - @wordpress/scripts builds to build/ directory
        $asset_file = TLDRWP_PLUGIN_PATH . 'build/index.asset.php';
        
        if ( file_exists( $asset_file ) ) {
            $asset = require $asset_file;
            $dependencies = $asset['dependencies'];
            $version = $asset['version'];
        } else {
            // Fallback dependencies if asset file doesn't exist
            $dependencies = array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n' );
            $version = TLDRWP_VERSION;
        }

        wp_enqueue_script(
            'tldrwp-editor',
            TLDRWP_PLUGIN_URL . 'build/index.js',
            $dependencies,
            $version,
            true
        );

        // Localize script with necessary data
        wp_localize_script(
            'tldrwp-editor',
            'tldrwpEditor',
            array(
                'strings' => array(
                    'saving' => __( 'Saving...', 'tldrwp' ),
                    'saved' => __( 'Saved!', 'tldrwp' ),
                    'error' => __( 'Error saving setting.', 'tldrwp' ),
                ),
            )
        );
    }

    /**
     * Register post meta for TL;DR toggle.
     */
    public function register_post_meta() {
        // register for all post types in one go
        register_post_meta( '', '_tldrwp_disabled', [
            // Expose in REST with a proper JSON Schema
            'show_in_rest'     => [
                'schema' => [
                    'type'    => 'boolean',
                    'default' => false,
                ],
            ],
            'single'           => true,
            'type'             => 'boolean',
            // sanitize into a true boolean
            'sanitize_callback'=> 'rest_sanitize_boolean',
            'auth_callback'    => function() {
                return current_user_can( 'edit_posts' );
            },
        ] );
    }
    

    /**
     * Debug function to log post meta changes.
     */
    public function debug_save_post( $post_id, $post ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $current_meta = get_post_meta( $post_id, '_tldrwp_disabled', true );
            error_log( 'TLDRWP Debug - Post ID: ' . $post_id . ', Post Type: ' . $post->post_type . ', Meta Value: ' . ( $current_meta ? 'true' : 'false' ) );
        }
    }

    /**
     * Debug function to log REST API response.
     */
    public function debug_rest_response( $response, $post, $request ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $data = $response->get_data();
            error_log( 'TLDRWP Debug - REST API Response for Post ID: ' . $post->ID . ', Post Type: ' . $post->post_type . ', Data: ' . print_r( $data, true ) );
        }
        return $response;
    }

} 