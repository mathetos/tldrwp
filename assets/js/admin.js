/**
 * TLDR WP Admin JavaScript
 */
(function($) {
    'use strict';

    // Admin functionality can be added here
    $(document).ready(function() {
        // Handle dismissible notices
        $(document).on('click', '.notice-dismiss', function() {
            $(this).parent().fadeOut();
        });
    });

})(jQuery);