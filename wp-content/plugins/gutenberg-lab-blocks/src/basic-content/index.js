import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import './style.scss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// Persist the nested main/sidebar blocks so PHP can wrap them on render.
	save() {
		return <InnerBlocks.Content />;
	},
} );
