import {
	bindNativeVideoControls,
	initializeStandaloneVimeoShells,
} from '../shared/vimeo-player-shell';

window.addEventListener( 'DOMContentLoaded', () => {
	document
		.querySelectorAll( '.wp-block-gutenberg-lab-blocks-media-panel' )
		.forEach( ( block ) => {
			bindNativeVideoControls( block );
			initializeStandaloneVimeoShells( block, {
				autoplayStrategy: 'intent',
			} );
		} );
} );
