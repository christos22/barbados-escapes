import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// Persist the locked heading/paragraph pair so PHP can keep the icon shell
	// dynamic while Gutenberg still owns the authored copy.
	save() {
		return <InnerBlocks.Content />;
	},
} );
