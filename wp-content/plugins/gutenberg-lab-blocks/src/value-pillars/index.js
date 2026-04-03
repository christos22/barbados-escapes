import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import './style.scss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// Keep the intro/group scaffold in post content so PHP can wrap the section
	// without taking block authoring away from Gutenberg.
	save() {
		return <InnerBlocks.Content />;
	},
} );
