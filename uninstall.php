<?php
/**
 * Uninstall TLDRWP Plugin
 *
 * This file is executed when the plugin is deleted from WordPress.
 * It cleans up all plugin data, options, and settings.
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options
delete_option( 'tldrwp_settings' );

// Delete any post meta that might have been added
global $wpdb;
$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_tldrwp_custom_prompt' ) );

// Clear any cached data that might have been stored
wp_cache_flush(); 