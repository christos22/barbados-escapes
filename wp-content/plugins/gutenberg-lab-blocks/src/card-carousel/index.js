import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

// Import the shared front-end stylesheet here so the build produces
// `style-index.css`, which block.json enqueues on the front end.
import './style.scss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	save() {
		return <InnerBlocks.Content />;
	},
} );
