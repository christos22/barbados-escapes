import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// The PHP callback adds the quote wrapper/mark; the authored quote and
	// author paragraphs still need to be serialized for reliable reordering.
	save() {
		return <InnerBlocks.Content />;
	},
} );
