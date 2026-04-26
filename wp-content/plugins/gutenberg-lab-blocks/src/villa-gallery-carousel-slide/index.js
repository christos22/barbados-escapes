import { registerBlockType } from '@wordpress/blocks';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	save() {
		// The parent carousel PHP renderer reads this child block's attributes.
		// Saving no HTML keeps the child valid as compact, attribute-only markup.
		return null;
	},
} );
