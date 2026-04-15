wp.domReady( () => {
	// The theme ships its own button design system, so hide Gutenberg's stock
	// Outline style to keep editors on the supported branded variants.
	if ( wp.blocks && wp.blocks.unregisterBlockStyle ) {
		wp.blocks.unregisterBlockStyle( 'core/button', 'outline' );
	}
} );
