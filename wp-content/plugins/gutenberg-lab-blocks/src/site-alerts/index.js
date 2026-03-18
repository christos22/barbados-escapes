import { registerBlockType } from '@wordpress/blocks';

import './style.scss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// Dynamic block: PHP owns the frontend query, filtering, and markup.
	save() {
		return null;
	},
} );
