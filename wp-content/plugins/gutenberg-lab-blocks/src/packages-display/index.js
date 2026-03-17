import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import './style.scss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// Persist the nested heading/paragraph/buttons so PHP can render them later.
	save() {
		return <InnerBlocks.Content />;
	},
} );
