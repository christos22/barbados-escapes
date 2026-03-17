import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import './style.scss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// Save the nested heading/intro blocks so PHP can render them dynamically.
	save() {
		return <InnerBlocks.Content />;
	},
} );
