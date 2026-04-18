import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import './style.scss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// Persist the authored slide blocks so PHP can rebuild the front-end rail.
	save() {
		return <InnerBlocks.Content />;
	},
} );
