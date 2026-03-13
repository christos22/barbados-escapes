import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import './style.scss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// Save only the nested card blocks. PHP owns the section wrapper so the
	// future post-driven variant can reuse the same outer markup and classes.
	save() {
		return <InnerBlocks.Content />;
	},
} );
