import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import './style.scss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// Persist the child tab blocks so PHP can build the accessible tab shell.
	save() {
		return <InnerBlocks.Content />;
	},
} );
