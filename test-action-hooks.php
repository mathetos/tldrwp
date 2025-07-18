<?php
/**
 * TLDRWP Action Hooks Test File
 * 
 * This file demonstrates how to use the TLDRWP action hooks.
 * Add this code to your theme's functions.php or a custom plugin to test the hooks.
 */

// Test action hook: Add a banner before the heading
add_action('tldr_before_summary_heading', function($response, $article_id, $article_title) {
    echo '<div style="background: #e3f2fd; border: 1px solid #2196f3; padding: 8px; border-radius: 4px; margin-bottom: 10px; font-size: 12px; color: #1976d2;">
        🤖 AI-Generated Summary - Article ID: ' . $article_id . '
    </div>';
}, 10, 3);

// Test action hook: Add reading time after heading
add_action('tldr_after_summary_heading', function($response, $article_id, $article_title) {
    $word_count = str_word_count(strip_tags($response));
    $reading_time = ceil($word_count / 200); // 200 words per minute
    
    echo '<div style="font-size: 12px; color: #666; margin-bottom: 15px;">
        📖 Estimated reading time: ' . $reading_time . ' minute' . ($reading_time > 1 ? 's' : '') . '
    </div>';
}, 10, 3);

// Test action hook: Add disclaimer before content
add_action('tldr_before_summary_copy', function($response, $article_id, $article_title) {
    echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 8px; border-radius: 4px; margin-bottom: 15px; font-size: 12px; color: #856404;">
        ⚠️ This is an AI-generated summary. Please read the full article for complete context.
    </div>';
}, 10, 3);

// Test action hook: Add feedback buttons after content
add_action('tldr_after_summary_copy', function($response, $article_id, $article_title) {
    echo '<div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
        <p style="margin: 0; font-size: 14px;">Was this summary helpful?</p>
        <div style="margin-top: 8px;">
            <button onclick="alert(\'Thank you for your positive feedback!\')" style="background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 3px; margin-right: 10px; cursor: pointer;">👍 Yes</button>
            <button onclick="alert(\'Thank you for your feedback. We\'ll work to improve!\')" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">👎 No</button>
        </div>
    </div>';
}, 10, 3);

// Test action hook: Add footer with timestamp
add_action('tldr_summary_footer', function($response, $article_id, $article_title) {
    echo '<div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #eee; font-size: 11px; color: #999;">
        Generated on ' . date('F j, Y \a\t g:i A') . ' | 
        <a href="#" style="color: #999;">Privacy Policy</a>
    </div>';
}, 10, 3);

// Test filter: Modify the heading text
add_filter('tldrwp_heading', function($heading) {
    return '📋 Quick Summary';
}, 10, 1);

// Test filter: Modify the response
add_filter('tldrwp_response', function($response, $article_id, $article_title) {
    // Add a custom wrapper around the response
    return '<div style="border-left: 3px solid #2196f3; padding-left: 15px;">' . $response . '</div>';
}, 10, 3);

echo "TLDRWP Action Hooks Test File Loaded Successfully!\n";
echo "The following hooks are now active:\n";
echo "- tldr_before_summary_heading\n";
echo "- tldr_after_summary_heading\n";
echo "- tldr_before_summary_copy\n";
echo "- tldr_after_summary_copy\n";
echo "- tldr_summary_footer\n";
echo "- tldrwp_heading (filter)\n";
echo "- tldrwp_response (filter)\n";
echo "\nTo test these hooks, generate a TL;DR summary on any post.\n"; 