import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import './style.scss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// Save the nested spec items into post content so PHP can keep control of
	// the outer layout shell while Gutenberg remains the source of truth.
	save() {
		return <InnerBlocks.Content />;
	},
} );
