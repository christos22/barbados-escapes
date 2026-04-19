import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

// Import the shared front-end CSS here so Gutenberg emits `style-index.css`
// for the block's Splide shell and slide layout.
import './style.scss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// Persist the authored child slide blocks so PHP can rebuild the final
	// carousel shell with one controlled front-end structure.
	save() {
		return <InnerBlocks.Content />;
	},
} );
