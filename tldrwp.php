<?php
/**
 * Plugin Name: TLDRWP AI Block
 * Description: Let your readers generate a TL;DR of your content with AI.
 * Version: 0.1.0
 * Author: Matt Cromwell
 * Requires Plugins: ai-services
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define( 'TLDRWP_VERSION', '0.1.0' );
define( 'TLDRWP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TLDRWP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'TLDRWP_PLUGIN_FILE', __FILE__ );

// Load component classes
require_once TLDRWP_PLUGIN_PATH . 'includes/settings.php';
require_once TLDRWP_PLUGIN_PATH . 'includes/ai-service.php';
require_once TLDRWP_PLUGIN_PATH . 'admin/admin.php';
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
