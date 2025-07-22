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
        add_action( 'wp_ajax_tldrwp_get_models', array( $this, 'ajax_get_models' ) );
        add_action( 'wp_ajax_tldrwp_save_meta', array( $this, 'ajax_save_meta' ) );
        
        // Block Editor integration
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
        add_action( 'init', array( $this, 'register_post_meta' ) );
        add_action( 'save_post', array( $this, 'save_tldr_meta' ), 10, 2 );
        add_action( 'rest_api_init', array( $this, 'register_rest_fields' ) );
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
        if ( ! $this->plugin->ai_service->check_ai_services() ) {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'TLDRWP requires the AI Services plugin to be installed and active.', 'tldrwp' ) . '</p></div>';
            return;
        }

        // Check if AI platform is selected
        $selected_platform = $this->plugin->ai_service->get_selected_ai_platform();
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
     * Test AI connection via AJAX.
     */
    public function test_ai_connection() {
        $this->plugin->ai_service->test_ai_connection();
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
        $models = $this->plugin->ai_service->get_available_ai_models( $platform );
        
        $model_names = array();
        foreach ( $models as $model_slug => $model_data ) {
            $model_names[ $model_slug ] = isset( $model_data['name'] ) ? $model_data['name'] : $model_slug;
        }
        
        wp_send_json_success( $model_names );
    }

    /**
     * AJAX handler for saving TL;DR meta field.
     */
    public function ajax_save_meta() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wp_rest' ) ) {
            wp_send_json_error( __( 'Security check failed', 'tldrwp' ) );
        }

        if ( ! isset( $_POST['post_id'] ) ) {
            wp_send_json_error( __( 'Post ID is required.', 'tldrwp' ) );
        }

        $post_id = intval( $_POST['post_id'] );
        $tldr_disabled = isset( $_POST['tldr_disabled'] ) ? (bool) $_POST['tldr_disabled'] : false;

        // Check permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error( __( 'Permission denied.', 'tldrwp' ) );
        }

        // Save the meta field
        $result = update_post_meta( $post_id, '_tldrwp_disabled', $tldr_disabled );

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'TLDRWP Debug - AJAX meta save: ' . ( $tldr_disabled ? 'true' : 'false' ) . ' for post ' . $post_id . ' - Result: ' . ( $result ? 'success' : 'failed' ) );
        }

        if ( $result ) {
            wp_send_json_success( array(
                'message' => __( 'Meta field saved successfully.', 'tldrwp' ),
                'tldr_disabled' => $tldr_disabled
            ) );
        } else {
            wp_send_json_error( __( 'Failed to save meta field.', 'tldrwp' ) );
        }
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

        // Get the built asset file
        $asset_file = TLDRWP_PLUGIN_PATH . 'admin/js/tldrwp-editor.asset.php';
        
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
            TLDRWP_PLUGIN_URL . 'admin/js/tldrwp-editor.js',
            $dependencies,
            $version,
            true
        );

        // Localize script with necessary data
        wp_localize_script(
            'tldrwp-editor',
            'tldrwpEditor',
            array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
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
        // Register for posts
        register_post_meta(
            'post',
            '_tldrwp_disabled',
            array(
                'show_in_rest' => true,
                'single' => true,
                'type' => 'boolean',
                'default' => false,
                'auth_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
                'sanitize_callback' => function( $value ) {
                    return (bool) $value;
                }
            )
        );

        // Register for pages
        register_post_meta(
            'page',
            '_tldrwp_disabled',
            array(
                'show_in_rest' => true,
                'single' => true,
                'type' => 'boolean',
                'default' => false,
                'auth_callback' => function() {
                    return current_user_can( 'edit_pages' );
                },
                'sanitize_callback' => function( $value ) {
                    return (bool) $value;
                }
            )
        );

        // Debug: Log registration
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'TLDRWP Debug - Meta fields registered for post and page types' );
        }
    }

    /**
     * Save TL;DR meta field when post is saved.
     *
     * @param int $post_id Post ID.
     * @param WP_Post $post Post object.
     */
    public function save_tldr_meta( $post_id, $post ) {
        // Don't save on autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Check if this is a supported post type
        $supported_types = array( 'post', 'page' );
        if ( ! in_array( $post->post_type, $supported_types, true ) ) {
            return;
        }

        // Debug: Log the save attempt
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'TLDRWP Debug - Save attempt for post ' . $post_id . ' of type ' . $post->post_type );
        }

        // Check for meta field in POST data (traditional form submission)
        if ( isset( $_POST['_tldrwp_disabled'] ) ) {
            $tldr_disabled = (bool) $_POST['_tldrwp_disabled'];
            update_post_meta( $post_id, '_tldrwp_disabled', $tldr_disabled );
            
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'TLDRWP Debug - Meta saved via POST: ' . ( $tldr_disabled ? 'true' : 'false' ) );
            }
        }

        // Also check for meta field in the post data (REST API)
        if ( isset( $post->meta_input ) && isset( $post->meta_input['_tldrwp_disabled'] ) ) {
            $tldr_disabled = (bool) $post->meta_input['_tldrwp_disabled'];
            update_post_meta( $post_id, '_tldrwp_disabled', $tldr_disabled );
            
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'TLDRWP Debug - Meta saved via meta_input: ' . ( $tldr_disabled ? 'true' : 'false' ) );
            }
        }
    }

    /**
     * Register REST API fields for better meta field handling.
     */
    public function register_rest_fields() {
        $post_types = array( 'post', 'page' );
        
        foreach ( $post_types as $post_type ) {
            register_rest_field(
                $post_type,
                '_tldrwp_disabled',
                array(
                    'get_callback' => function( $post_arr ) {
                        $value = get_post_meta( $post_arr['id'], '_tldrwp_disabled', true );
                        return (bool) $value;
                    },
                    'update_callback' => function( $value, $post ) {
                        if ( ! current_user_can( 'edit_post', $post->ID ) ) {
                            return false;
                        }
                        
                        $bool_value = (bool) $value;
                        update_post_meta( $post->ID, '_tldrwp_disabled', $bool_value );
                        
                        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                            error_log( 'TLDRWP Debug - REST API meta update: ' . ( $bool_value ? 'true' : 'false' ) . ' for post ' . $post->ID );
                        }
                        
                        return true;
                    },
                    'schema' => array(
                        'description' => 'Whether TL;DR functionality is disabled for this post.',
                        'type' => 'boolean',
                        'default' => false,
                    ),
                )
            );
        }
    }
} 