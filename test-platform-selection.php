<?php
/**
 * Test file for TLDRWP platform selection functionality
 * 
 * This file can be accessed directly to test the platform selection features
 * without needing to go through the WordPress admin interface.
 */

// Load WordPress
require_once( dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) . '/wp-load.php' );

// Check if user is admin
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Access denied' );
}

echo '<h1>TLDRWP Platform Selection Test</h1>';

// Test 1: Check if AI Services plugin is active
echo '<h2>Test 1: AI Services Plugin Status</h2>';
if ( function_exists( 'ai_services' ) ) {
    echo '<p style="color: green;">✅ AI Services plugin is active</p>';
} else {
    echo '<p style="color: red;">❌ AI Services plugin is not active</p>';
    exit;
}

// Test 2: Get available platforms
echo '<h2>Test 2: Available AI Platforms</h2>';
$available_platforms = tldrwp_get_available_ai_platforms();
if ( ! empty( $available_platforms ) ) {
    echo '<p style="color: green;">✅ Found ' . count( $available_platforms ) . ' available platform(s):</p>';
    echo '<ul>';
    foreach ( $available_platforms as $slug => $name ) {
        echo '<li><strong>' . esc_html( $slug ) . '</strong>: ' . esc_html( $name ) . '</li>';
    }
    echo '</ul>';
} else {
    echo '<p style="color: orange;">⚠️ No AI platforms are available (no API keys configured)</p>';
}

// Test 3: Get selected platform
echo '<h2>Test 3: Selected AI Platform</h2>';
$selected_platform = tldrwp_get_selected_ai_platform();
if ( ! empty( $selected_platform ) ) {
    $platform_name = isset( $available_platforms[ $selected_platform ] ) ? $available_platforms[ $selected_platform ] : 'Unknown';
    echo '<p style="color: green;">✅ Selected platform: <strong>' . esc_html( $selected_platform ) . '</strong> (' . esc_html( $platform_name ) . ')</p>';
} else {
    echo '<p style="color: red;">❌ No platform is selected</p>';
}

// Test 4: Test AI service call
echo '<h2>Test 4: AI Service Call Test</h2>';
if ( ! empty( $selected_platform ) ) {
    $test_prompt = 'Please respond with "Platform test successful" if you can read this message.';
    $response = tldrwp_call_ai_service( $test_prompt );
    
    if ( ! empty( $response ) ) {
        echo '<p style="color: green;">✅ AI service call successful</p>';
        echo '<p><strong>Response:</strong> ' . esc_html( substr( $response, 0, 100 ) ) . '...</p>';
    } else {
        echo '<p style="color: red;">❌ AI service call failed (empty response)</p>';
    }
} else {
    echo '<p style="color: orange;">⚠️ Skipping AI service call test (no platform selected)</p>';
}

// Test 5: Settings display
echo '<h2>Test 5: Settings Display</h2>';
$settings = tldrwp_get_settings();
echo '<p><strong>Current settings:</strong></p>';
echo '<ul>';
echo '<li>Selected AI Platform: ' . esc_html( $settings['selected_ai_platform'] ) . '</li>';
echo '<li>Enable Social Sharing: ' . ( $settings['enable_social_sharing'] ? 'Yes' : 'No' ) . '</li>';
echo '<li>Enabled Post Types: ' . esc_html( implode( ', ', $settings['enabled_post_types'] ) ) . '</li>';
echo '</ul>';

// Test 6: Platform selection field rendering
echo '<h2>Test 6: Platform Selection Field</h2>';
if ( count( $available_platforms ) > 1 ) {
    echo '<p style="color: green;">✅ Multiple platforms available - platform selection field would be shown</p>';
    echo '<p><strong>Available options:</strong></p>';
    echo '<select disabled>';
    echo '<option>-- Select AI Platform --</option>';
    foreach ( $available_platforms as $slug => $name ) {
        $selected = ( $selected_platform === $slug ) ? 'selected' : '';
        echo '<option value="' . esc_attr( $slug ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
    }
    echo '</select>';
} else {
    echo '<p style="color: blue;">ℹ️ Only ' . count( $available_platforms ) . ' platform(s) available - platform selection field would be hidden</p>';
}

echo '<hr>';
echo '<p><em>Test completed. You can now configure multiple AI platforms in the AI Services plugin settings to test the platform selection feature.</em></p>';
?> 