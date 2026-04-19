import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

// Keep Two-Up on its own front-end stylesheet so the block can diverge from the
// editorial Feature Carousel without leaking selectors across both variants.
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
