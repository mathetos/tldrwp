<?php
/**
 * Test file for TLDRWP model selection functionality
 * 
 * This file can be accessed directly to test the model selection features
 * without needing to go through the WordPress admin interface.
 */

// Load WordPress
require_once( dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) . '/wp-load.php' );

// Check if user is admin
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Access denied' );
}

echo '<h1>TLDRWP Model Selection Test</h1>';

// Test 1: Check if AI Services plugin is active
echo '<h2>Test 1: AI Services Plugin Status</h2>';
if ( function_exists( 'ai_services' ) ) {
    echo '<p style="color: green;">✅ AI Services plugin is active</p>';
} else {
    echo '<p style="color: red;">❌ AI Services plugin is not active</p>';
    exit;
}

// Test 2: Check available platforms
echo '<h2>Test 2: Available AI Platforms</h2>';
$available_platforms = tldrwp_get_available_ai_platforms();
if ( ! empty( $available_platforms ) ) {
    echo '<p style="color: green;">✅ Found ' . count( $available_platforms ) . ' available platform(s):</p>';
    echo '<ul>';
    foreach ( $available_platforms as $slug => $name ) {
        echo '<li><strong>' . esc_html( $name ) . '</strong> (' . esc_html( $slug ) . ')</li>';
    }
    echo '</ul>';
} else {
    echo '<p style="color: red;">❌ No AI platforms are available</p>';
    exit;
}

// Test 3: Check available models for each platform
echo '<h2>Test 3: Available Models by Platform</h2>';

// Test 3a: Check if AI Services capability filtering classes are available
echo '<h3>Test 3a: AI Services Capability Filtering</h3>';
if ( class_exists( 'Felix_Arntz\AI_Services\Services\Util\AI_Capabilities' ) ) {
    echo '<p style="color: green;">✅ AI_Capabilities class is available</p>';
} else {
    echo '<p style="color: red;">❌ AI_Capabilities class is not available</p>';
}

if ( class_exists( 'Felix_Arntz\AI_Services\Services\API\Enums\AI_Capability' ) ) {
    echo '<p style="color: green;">✅ AI_Capability enum is available</p>';
} else {
    echo '<p style="color: red;">❌ AI_Capability enum is not available</p>';
}

// Test 3b: Check available models for each platform
echo '<h3>Test 3b: Available Models by Platform</h3>';
foreach ( $available_platforms as $platform_slug => $platform_name ) {
    echo '<h4>Platform: ' . esc_html( $platform_name ) . ' (' . esc_html( $platform_slug ) . ')</h4>';
    
    $available_models = tldrwp_get_available_ai_models( $platform_slug );
    
    if ( ! empty( $available_models ) ) {
        echo '<p style="color: green;">✅ Found ' . count( $available_models ) . ' text generation model(s):</p>';
        echo '<ul>';
        foreach ( $available_models as $model_slug => $model_data ) {
            $model_name = isset( $model_data['name'] ) ? $model_data['name'] : $model_slug;
            $capabilities = isset( $model_data['capabilities'] ) ? implode( ', ', $model_data['capabilities'] ) : 'none';
            $has_text_generation = isset( $model_data['capabilities'] ) && in_array( 'text_generation', $model_data['capabilities'] );
            $capability_status = $has_text_generation ? '✅' : '❌';
            echo '<li>' . esc_html( $capability_status ) . ' <strong>' . esc_html( $model_name ) . '</strong> (' . esc_html( $model_slug ) . ') - Capabilities: ' . esc_html( $capabilities ) . '</li>';
        }
        echo '</ul>';
        
        // Verify that all returned models actually have text generation capability
        $all_have_text_generation = true;
        foreach ( $available_models as $model_data ) {
            if ( ! isset( $model_data['capabilities'] ) || ! in_array( 'text_generation', $model_data['capabilities'] ) ) {
                $all_have_text_generation = false;
                break;
            }
        }
        
        if ( $all_have_text_generation ) {
            echo '<p style="color: green;">✅ All returned models have text generation capability (filtering working correctly)</p>';
        } else {
            echo '<p style="color: red;">❌ Some returned models do not have text generation capability (filtering issue)</p>';
        }
    } else {
        echo '<p style="color: red;">❌ No text generation models available for this platform</p>';
    }
}

// Test 4: Test selected platform and model
echo '<h2>Test 4: Current Selection</h2>';
$selected_platform = tldrwp_get_selected_ai_platform();
$selected_model = tldrwp_get_selected_ai_model();

if ( ! empty( $selected_platform ) ) {
    $platform_name = $available_platforms[ $selected_platform ] ?? $selected_platform;
    echo '<p style="color: green;">✅ Selected Platform: <strong>' . esc_html( $platform_name ) . '</strong> (' . esc_html( $selected_platform ) . ')</p>';
    
    if ( ! empty( $selected_model ) ) {
        echo '<p style="color: green;">✅ Selected Model: <strong>' . esc_html( $selected_model ) . '</strong></p>';
    } else {
        echo '<p style="color: orange;">⚠️ No specific model selected (will use auto-selection)</p>';
    }
} else {
    echo '<p style="color: red;">❌ No platform is currently selected</p>';
}

// Test 5: Test AI service call with current selection
echo '<h2>Test 5: AI Service Call Test</h2>';
if ( ! empty( $selected_platform ) ) {
    $test_prompt = 'Please respond with "Model selection test successful" if you can read this message.';
    $response = tldrwp_call_ai_service( $test_prompt );
    
    if ( ! empty( $response ) ) {
        echo '<p style="color: green;">✅ AI service call successful!</p>';
        echo '<p><strong>Response:</strong> ' . esc_html( $response ) . '</p>';
        echo '<p><strong>Platform used:</strong> ' . esc_html( $selected_platform ) . '</p>';
        echo '<p><strong>Model used:</strong> ' . esc_html( $selected_model ?: 'Auto-selected' ) . '</p>';
    } else {
        echo '<p style="color: red;">❌ AI service call failed or returned empty response</p>';
    }
} else {
    echo '<p style="color: red;">❌ Cannot test AI service call - no platform selected</p>';
}

// Test 6: Test AJAX endpoint for getting models
echo '<h2>Test 6: AJAX Endpoint Test</h2>';
if ( ! empty( $selected_platform ) ) {
    // Simulate AJAX request
    $_POST['action'] = 'tldrwp_get_models';
    $_POST['platform'] = $selected_platform;
    $_POST['nonce'] = wp_create_nonce( 'tldrwp_ajax_nonce' );
    
    // Capture output
    ob_start();
    tldrwp_ajax_get_models();
    $ajax_response = ob_get_clean();
    
    if ( ! empty( $ajax_response ) ) {
        echo '<p style="color: green;">✅ AJAX endpoint responded</p>';
        echo '<p><strong>Response:</strong> ' . esc_html( $ajax_response ) . '</p>';
    } else {
        echo '<p style="color: red;">❌ AJAX endpoint returned empty response</p>';
    }
} else {
    echo '<p style="color: red;">❌ Cannot test AJAX endpoint - no platform selected</p>';
}

echo '<hr>';
echo '<p><em>Test completed. Check the results above to verify model selection functionality.</em></p>';
?> 