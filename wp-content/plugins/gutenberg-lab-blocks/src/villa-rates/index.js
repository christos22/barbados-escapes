import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import './editor.scss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// Dynamic block: only the child rows are saved; render.php builds the table.
	save() {
		return <InnerBlocks.Content />;
	},
} );
