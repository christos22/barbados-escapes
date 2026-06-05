import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import './style.scss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// PHP owns the outer section and slider shell. Saving only the child slides
	// keeps the authored content portable and easy for Gutenberg to parse.
	save() {
		return <InnerBlocks.Content />;
	},
} );
