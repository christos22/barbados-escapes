import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import './style.scss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// Persist the authored content blocks so the PHP renderer can wrap them with
	// the media/layout markup on the frontend.
	save() {
		return <InnerBlocks.Content />;
	},
} );
