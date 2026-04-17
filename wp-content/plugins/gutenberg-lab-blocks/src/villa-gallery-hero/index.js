import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import './style.scss';
import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// The parent renders on the server, but the nested region blocks still need
	// to be serialized so PHP can parse the media/content tree later.
	save() {
		return <InnerBlocks.Content />;
	},
} );
