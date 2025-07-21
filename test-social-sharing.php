<?php
/**
 * TLDRWP Social Sharing Test File
 * 
 * This file tests the social sharing functionality.
 * Add this to your theme's functions.php or run it directly to test.
 */

// Test the social sharing class
if (class_exists('TLDRWP_Social_Sharing')) {
    echo "✅ TLDRWP_Social_Sharing class exists\n";
    
    // Test with a sample post
    $test_post = get_posts(array('numberposts' => 1))[0] ?? null;
    
    if ($test_post) {
        echo "✅ Found test post: " . $test_post->post_title . "\n";
        
        // Test share data preparation
        $share_data = $this->prepare_share_data($test_post);
        echo "✅ Share data prepared:\n";
        echo "  - URL: " . $share_data['raw_url'] . "\n";
        echo "  - Title: " . $share_data['raw_title'] . "\n";
        echo "  - Description: " . substr($share_data['raw_description'], 0, 100) . "...\n";
        echo "  - Site Name: " . $share_data['raw_site_name'] . "\n";
        
        // Test URL generation
        $share_urls = $this->generate_share_urls($share_data, "Test TL;DR content");
        echo "✅ Share URLs generated:\n";
        echo "  - Twitter: " . $share_urls['twitter'] . "\n";
        echo "  - Facebook: " . $share_urls['facebook'] . "\n";
        echo "  - LinkedIn: " . $share_urls['linkedin'] . "\n";
        
    } else {
        echo "❌ No posts found for testing\n";
    }
} else {
    echo "❌ TLDRWP_Social_Sharing class not found\n";
}

echo "\n🎉 Social sharing test completed!\n";
?> 