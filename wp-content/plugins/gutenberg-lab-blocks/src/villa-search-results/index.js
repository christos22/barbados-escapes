import { registerBlockType } from '@wordpress/blocks';

import './style.scss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// PHP owns filtering, availability and card rendering.
	save() {
		return null;
	},
} );
