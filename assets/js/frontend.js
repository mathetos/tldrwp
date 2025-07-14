/**
 * TLDR WP Frontend JavaScript
 */
(function($) {
    'use strict';

    // Add TLDR button to posts
    function addTldrButton() {
        if (!tldrwp_ajax || !tldrwp_ajax.post_id) {
            return;
        }

        var button = $('<button class="tldrwp-button">Generate TLDR Summary</button>');
        var container = $('<div class="tldrwp-container"></div>');
        
        container.append(button);
        
        // Add after post content
        $('.entry-content, .post-content, .content').first().after(container);
        
        // Handle button click
        button.on('click', function(e) {
            e.preventDefault();
            generateSummary(button);
        });
    }

    // Generate summary via REST API
    function generateSummary(button) {
        button.prop('disabled', true).text('Generating...');
        
        $.ajax({
            url: tldrwp_ajax.rest_url + 'generate-summary',
            method: 'POST',
            data: {
                post_id: tldrwp_ajax.post_id,
                nonce: tldrwp_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    displaySummary(response, button);
                } else {
                    showError('Failed to generate summary', button);
                }
            },
            error: function() {
                showError('Error generating summary', button);
            }
        });
    }

    // Display the generated summary
    function displaySummary(data, button) {
        var summaryHtml = '<div class="tldrwp-summary">' +
            '<h3>TLDR Summary</h3>' +
            '<p>' + data.summary + '</p>' +
            '</div>';
        
        button.parent().append(summaryHtml);
        button.text('Summary Generated').prop('disabled', true);
    }

    // Show error message
    function showError(message, button) {
        button.parent().append('<div class="tldrwp-error">' + message + '</div>');
        button.text('Try Again').prop('disabled', false);
    }

    // Initialize when document is ready
    $(document).ready(function() {
        addTldrButton();
    });

})(jQuery);