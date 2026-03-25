import { registerBlockType } from '@wordpress/blocks';

import './style.scss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// PHP owns the live term list and selected query-state rendering.
	save() {
		return null;
	},
} );
