import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import './style.scss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// Save only the nested manual cards. PHP owns the shared section wrapper
	// and the selected-villa rendering path.
	save() {
		return <InnerBlocks.Content />;
	},
} );
