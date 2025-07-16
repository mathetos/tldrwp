( function( blocks, element ) {
    var el = element.createElement;
    blocks.registerBlockType( 'tldrwp/ai-chat', {
        title: 'AI Chat (Deprecated)',
        icon: 'robot',
        category: 'widgets',
        edit: function() {
            return el( 'p', {}, 'This block is deprecated. TL;DR functionality is now automatically available on all posts.' );
        },
        save: function() {
            return null; // Dynamic block
        }
    } );
} )( window.wp.blocks, window.wp.element );

// Remove old event listener - functionality moved to frontend.js
