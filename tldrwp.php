<?php
/**
 * Plugin Name: TLDRWP
 * Plugin URI: https://github.com/mathetos/tldrwp
 * Description: Let your readers generate a TL;DR of your content with AI.
 * Version: 0.1.0
 * Author: Matt Cromwell
 * Author URI: https://mattcromwell.com
 * Requires Plugins: ai
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tldrwp
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define( 'TLDRWP_VERSION', '0.1.0' );
define( 'TLDRWP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TLDRWP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'TLDRWP_PLUGIN_FILE', __FILE__ );

// Load core components (always needed)
require_once TLDRWP_PLUGIN_PATH . 'includes/settings.php';
require_once TLDRWP_PLUGIN_PATH . 'includes/ai-service.php';
require_once TLDRWP_PLUGIN_PATH . 'includes/social-sharing.php';

// Load admin components conditionally
if ( is_admin() ) {
    require_once TLDRWP_PLUGIN_PATH . 'admin/admin.php';
}

// Load public components (needed for frontend and AJAX)
require_once TLDRWP_PLUGIN_PATH . 'public/public.php';

// Load the main plugin class
require_once TLDRWP_PLUGIN_PATH . 'includes/class-tldrwp.php';

/**
 * Initialize the plugin
 */
function tldrwp_init() {
    // Initialize the main plugin class
    TLDRWP::get_instance();
}
add_action( 'plugins_loaded', 'tldrwp_init' );

/**
 * Action Hooks for TL;DR Customization
 * 
 * These hooks allow developers to customize the TL;DR output at different points:
 * 
 * @param string $response The AI-generated TL;DR response
 * @param int $article_id The current article/post ID
 * @param string $article_title The current article title
 */
function tldrwp_action_hooks() {
    // Hook: Before the summary heading
    do_action( 'tldrwp_before_summary_heading' );
    
    // Hook: After the summary heading
    do_action( 'tldrwp_after_summary_heading' );
    
    // Hook: Before the summary content
    do_action( 'tldrwp_before_summary_copy' );
    
    // Hook: After the summary content
    do_action( 'tldrwp_after_summary_copy' );
    
    // Hook: Summary footer (after social sharing)
    do_action( 'tldrwp_summary_footer' );
}

/**
 * Filter Hooks for TL;DR Content Customization
 * 
 * These filters allow developers to modify the TL;DR content:
 * 
 * @param string $content The content to be filtered
 * @param string $response The AI-generated TL;DR response
 * @param int $article_id The current article/post ID
 * @param string $article_title The current article title
 */

/**
 * Filter the TL;DR response before display
 */
function tldrwp_filter_response( $response, $article_id = null, $article_title = null ) {
    return apply_filters( 'tldrwp_response', $response, $article_id, $article_title );
}

/**
 * Filter the TL;DR heading text
 */
function tldrwp_filter_heading( $heading = 'Key Insights' ) {
    return apply_filters( 'tldrwp_heading', $heading );
}

/**
 * Filter the TL;DR summary HTML structure
 */
function tldrwp_filter_summary_html( $html, $response, $social_sharing_html = '' ) {
    return apply_filters( 'tldrwp_summary_html', $html, $response, $social_sharing_html );
}
