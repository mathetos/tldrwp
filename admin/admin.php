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
} 