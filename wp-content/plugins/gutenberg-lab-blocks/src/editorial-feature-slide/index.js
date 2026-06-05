import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// The dynamic PHP render gives the parent one predictable slide shape while
	// the saved content remains regular nested Gutenberg blocks.
	save() {
		return <InnerBlocks.Content />;
	},
} );
