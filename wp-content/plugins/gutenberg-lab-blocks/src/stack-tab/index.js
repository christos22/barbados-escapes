import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// Save the nested reveal items so the child PHP renderer can build the
	// stacked left-column buttons and right-column media stage.
	save() {
		return <InnerBlocks.Content />;
	},
} );
