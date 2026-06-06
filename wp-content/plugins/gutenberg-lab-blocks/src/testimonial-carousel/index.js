import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import Edit from './edit';
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	edit: Edit,

	// PHP renders the carousel shell, but Gutenberg still needs the saved child
	// quote blocks so moving this block around does not strip its content.
	save() {
		return <InnerBlocks.Content />;
	},
} );
