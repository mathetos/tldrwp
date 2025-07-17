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
 * initialization and orchestration of component classes.
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
    public $settings = array();

    /**
     * Settings component.
     *
     * @var TLDRWP_Settings
     */
    public $settings_manager;

    /**
     * AI Service component.
     *
     * @var TLDRWP_AI_Service
     */
    public $ai_service;

    /**
     * Admin component.
     *
     * @var TLDRWP_Admin
     */
    public $admin;

    /**
     * Public component.
     *
     * @var TLDRWP_Public
     */
    public $public;

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

        // Initialize component classes
        $this->init_components();

        // Initialize hooks
        $this->init_hooks();
    }

    /**
     * Load plugin settings.
     */
    private function load_settings() {
        // This will be replaced by the settings manager once components are initialized
        $this->settings = $this->get_default_settings();
        $settings = get_option( 'tldrwp_settings', array() );
        $this->settings = wp_parse_args( $settings, $this->settings );
    }

    /**
     * Initialize component classes.
     */
    private function init_components() {
        // Core components (always needed)
        $this->settings_manager = new TLDRWP_Settings( $this );
        $this->ai_service = new TLDRWP_AI_Service( $this );
        
        // Admin component (only in admin context)
        if ( is_admin() && class_exists( 'TLDRWP_Admin' ) ) {
            $this->admin = new TLDRWP_Admin( $this );
        }
        
        // Public component (needed for frontend and AJAX)
        if ( class_exists( 'TLDRWP_Public' ) ) {
            $this->public = new TLDRWP_Public( $this );
        }
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
        // Core hooks that remain in the main class
        // Component-specific hooks are handled by their respective classes
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
     * Refresh settings from database.
     */
    public function refresh_settings() {
        if ( isset( $this->settings_manager ) ) {
            $this->settings = $this->settings_manager->get_settings();
        } else {
            $this->settings = $this->get_settings();
        }
    }

    /**
     * Admin notice for AI Services requirement.
     */
    public function admin_notice_ai_services_required() {
        // Only show on the reading settings page
        global $pagenow;
        if ( ! is_admin() || 'options-reading.php' !== $pagenow ) {
            return;
        }
        
        echo '<div class="notice notice-error"><p>' . esc_html__( 'TLDRWP requires the AI Services plugin to be installed and active.', 'tldrwp' ) . '</p></div>';
    }

    // ============================================================================
    // DELEGATION METHODS
    // These methods delegate to the appropriate component classes
    // ============================================================================

    /**
     * Get available AI platforms.
     *
     * @return array
     */
    public function get_available_ai_platforms() {
        return isset( $this->ai_service ) ? $this->ai_service->get_available_ai_platforms() : array();
    }

    /**
     * Get the selected AI platform.
     *
     * @return string
     */
    public function get_selected_ai_platform() {
        return isset( $this->ai_service ) ? $this->ai_service->get_selected_ai_platform() : '';
    }

    /**
     * Get available AI models.
     *
     * @param string $platform_slug Platform slug.
     * @return array
     */
    public function get_available_ai_models( $platform_slug = null ) {
        return isset( $this->ai_service ) ? $this->ai_service->get_available_ai_models( $platform_slug ) : array();
    }

    /**
     * Get the selected AI model.
     *
     * @return string
     */
    public function get_selected_ai_model() {
        return isset( $this->ai_service ) ? $this->ai_service->get_selected_ai_model() : '';
    }

    /**
     * Check if AI Services plugin is active.
     *
     * @return bool
     */
    public function check_ai_services() {
        return isset( $this->ai_service ) ? $this->ai_service->check_ai_services() : false;
    }

    /**
     * Test AI connection.
     */
    public function test_ai_connection() {
        if ( isset( $this->ai_service ) ) {
            $this->ai_service->test_ai_connection();
        }
    }

    /**
     * Call AI service.
     *
     * @param string $prompt The prompt to send to the AI service.
     * @return string|false The AI response or false on failure.
     */
    public function call_ai_service( $prompt ) {
        return isset( $this->ai_service ) ? $this->ai_service->call_ai_service( $prompt ) : false;
    }

    /**
     * Format AI response.
     *
     * @param string $raw_response Raw AI response.
     * @return string Formatted response.
     */
    public function format_ai_response( $raw_response ) {
        return isset( $this->ai_service ) ? $this->ai_service->format_ai_response( $raw_response ) : '';
    }
} 