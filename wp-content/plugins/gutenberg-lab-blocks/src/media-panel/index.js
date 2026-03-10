/**
 * Registers a new block provided a unique name and an object defining its behavior.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * All files containing `style` keyword are bundled together. The code used
 * gets applied both to the front of your site and to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './style.scss';

/**
 * Internal dependencies.
 *
 * `InnerBlocks.Content` is used in `save()` to persist the nested blocks
 * (heading, paragraph, buttons) into post content.
 */
import { InnerBlocks } from '@wordpress/block-editor';
import Edit from './edit';
import metadata from './block.json';

/**
 * Every block starts by registering a new block type definition.
 *
 * `metadata.name` comes from `block.json`, so the JS registration stays in sync
 * with the block's PHP and metadata configuration.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
registerBlockType( metadata.name, {
	/**
	 * `edit` controls what the block looks like inside the editor.
	 *
	 * @see ./edit.js
	 */
	edit: Edit,

	/**
	 * This is a dynamic container block.
	 *
	 * The outer frontend wrapper is rendered by PHP in `render.php`, but the
	 * nested blocks still need to be saved into post content so PHP receives them
	 * later as `$content`.
	 */
	save() {
		return <InnerBlocks.Content />;
	},
} );
