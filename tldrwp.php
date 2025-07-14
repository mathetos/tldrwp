<?php
/**
 * Plugin Name: TLDR WP
 * Plugin URI: https://github.com/mathetos/tldrwp
 * Description: A WordPress plugin to allow viewers of a post click an AI button to generate a TLDR summary with your custom CTA messaging.
 * Version: 1.0.0
 * Author: Mathetos
 * Text Domain: tldrwp
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network: false
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'TLDRWP_VERSION', '1.0.0' );
define( 'TLDRWP_PLUGIN_FILE', __FILE__ );
define( 'TLDRWP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TLDRWP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TLDRWP_TEXT_DOMAIN', 'tldrwp' );

/**
 * Main TLDR WP Class
 */
class TLDRWP {

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get single instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
        add_action( 'admin_init', array( $this, 'admin_init' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        
        // Register uninstall hook
        register_uninstall_hook( __FILE__, array( 'TLDRWP', 'uninstall' ) );
    }

    /**
     * Load plugin text domain for internationalization
     */
    public function load_textdomain() {
        load_plugin_textdomain( 
            TLDRWP_TEXT_DOMAIN, 
            false, 
            dirname( plugin_basename( __FILE__ ) ) . '/languages' 
        );
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route( 'tldrwp/v1', '/generate-summary', array(
            'methods' => 'POST',
            'callback' => array( $this, 'generate_summary_callback' ),
            'permission_callback' => array( $this, 'rest_permission_callback' ),
            'args' => array(
                'post_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ),
                'nonce' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ) );
    }

    /**
     * REST API permission callback with nonce verification and capability check
     */
    public function rest_permission_callback( $request ) {
        // Verify nonce
        $nonce = $request->get_param( 'nonce' );
        if ( ! wp_verify_nonce( $nonce, 'tldrwp_generate_summary' ) ) {
            return new WP_Error( 
                'invalid_nonce', 
                esc_html__( 'Invalid security token.', 'tldrwp' ), 
                array( 'status' => 403 ) 
            );
        }

        // Check user capability - users should be able to read posts to generate summaries
        if ( ! current_user_can( 'read' ) ) {
            return new WP_Error( 
                'insufficient_permissions', 
                esc_html__( 'You do not have permission to perform this action.', 'tldrwp' ), 
                array( 'status' => 403 ) 
            );
        }

        return true;
    }

    /**
     * Generate summary REST API callback
     */
    public function generate_summary_callback( $request ) {
        $post_id = $request->get_param( 'post_id' );
        
        // Get the post
        $post = get_post( $post_id );
        if ( ! $post ) {
            return new WP_Error( 
                'post_not_found', 
                esc_html__( 'Post not found.', 'tldrwp' ), 
                array( 'status' => 404 ) 
            );
        }

        // Check if post is published and user can read it
        if ( 'publish' !== $post->post_status && ! current_user_can( 'edit_post', $post_id ) ) {
            return new WP_Error( 
                'post_not_accessible', 
                esc_html__( 'Post is not accessible.', 'tldrwp' ), 
                array( 'status' => 403 ) 
            );
        }

        // Generate summary (placeholder implementation)
        $content = wp_strip_all_tags( $post->post_content );
        $summary = wp_trim_words( $content, 50, '...' );
        
        // Return properly escaped response
        return rest_ensure_response( array(
            'success' => true,
            'summary' => esc_html( $summary ),
            'post_title' => esc_html( get_the_title( $post_id ) ),
            'post_url' => esc_url( get_permalink( $post_id ) ),
        ) );
    }

    /**
     * Initialize admin functionality
     */
    public function admin_init() {
        // Show admin notice on plugin activation
        if ( get_transient( 'tldrwp_activation_notice' ) ) {
            add_action( 'admin_notices', array( $this, 'activation_admin_notice' ) );
            delete_transient( 'tldrwp_activation_notice' );
        }
    }

    /**
     * Display dismissible admin notice
     */
    public function activation_admin_notice() {
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php 
                echo esc_html__( 'TLDR WP plugin has been activated successfully!', 'tldrwp' ); 
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        // Only enqueue on single posts
        if ( ! is_single() ) {
            return;
        }

        wp_enqueue_script( 
            'tldrwp-frontend', 
            TLDRWP_PLUGIN_URL . 'assets/js/frontend.js', 
            array( 'jquery' ), 
            TLDRWP_VERSION, 
            true 
        );

        // Localize script with nonce and REST API URL
        wp_localize_script( 'tldrwp-frontend', 'tldrwp_ajax', array(
            'rest_url' => esc_url( rest_url( 'tldrwp/v1/' ) ),
            'nonce' => wp_create_nonce( 'tldrwp_generate_summary' ),
            'post_id' => get_the_ID(),
        ) );
    }

    /**
     * Enqueue admin scripts
     */
    public function admin_enqueue_scripts( $hook ) {
        // Only enqueue on post edit screens
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
            return;
        }

        wp_enqueue_script( 
            'tldrwp-admin', 
            TLDRWP_PLUGIN_URL . 'assets/js/admin.js', 
            array( 'jquery' ), 
            TLDRWP_VERSION, 
            true 
        );
    }

    /**
     * Uninstall cleanup - boilerplate only, no data deletion
     */
    public static function uninstall() {
        // Security check
        if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
            exit;
        }

        // Additional security check
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        // Boilerplate for future cleanup - no actual data deletion yet
        // as the plugin doesn't store persistent data currently
        
        // Example of what could be cleaned up in the future:
        // delete_option( 'tldrwp_settings' );
        // delete_transient( 'tldrwp_cache' );
        
        // Clean up any transients that might exist
        delete_transient( 'tldrwp_activation_notice' );
        
        // If we stored user meta in the future:
        // delete_metadata( 'user', 0, 'tldrwp_user_preference', '', true );
        
        // If we had custom tables in the future:
        // global $wpdb;
        // $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}tldrwp_summaries" );
    }
}

/**
 * Initialize the plugin
 */
function tldrwp_init() {
    return TLDRWP::get_instance();
}

// Hook initialization to plugins_loaded to ensure WordPress is fully loaded
add_action( 'plugins_loaded', 'tldrwp_init' );

/**
 * Activation hook
 */
function tldrwp_activate() {
    // Set transient for activation notice
    set_transient( 'tldrwp_activation_notice', true, 60 );
    
    // Flush rewrite rules to ensure REST API endpoints are available
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'tldrwp_activate' );

/**
 * Deactivation hook
 */
function tldrwp_deactivate() {
    // Clean up transients
    delete_transient( 'tldrwp_activation_notice' );
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'tldrwp_deactivate' );