( function( blocks, element ) {
    var el = element.createElement;
    blocks.registerBlockType( 'tldrwp/ai-chat', {
        title: 'AI Chat',
        icon: 'robot',
        category: 'widgets',
        edit: function() {
            return el( 'p', {}, 'AI Chat block – displays on the front end.' );
        },
        save: function() {
            return null; // Dynamic block
        }
    } );
} )( window.wp.blocks, window.wp.element );

document.addEventListener( 'click', function( e ) {
    if ( ! e.target.classList.contains( 'tldrwp-submit' ) ) {
        return;
    }

    var container = e.target.closest( '#tldrwp-ai-chat' );
    var prompt    = container.querySelector( '.tldrwp-prompt' ).value;
    var output    = container.querySelector( '.tldrwp-output' );

    wp.apiFetch( {
        path: '/tldrwp/v1/chat',
        method: 'POST',
        data: {
            prompt: prompt,
            nonce: e.target.dataset.nonce
        }
    } ).then( function( res ) {
        output.textContent = res.response || 'No response';
    } ).catch( function( err ) {
        output.textContent = 'Error: ' + err.message;
    } );
} );
