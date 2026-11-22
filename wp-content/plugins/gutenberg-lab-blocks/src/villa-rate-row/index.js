import { registerBlockType } from '@wordpress/blocks';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// Attributes only: the parent's render.php turns rows into table markup.
	save() {
		return null;
	},
} );
