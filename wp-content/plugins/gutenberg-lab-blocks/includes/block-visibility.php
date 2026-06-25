<?php
/**
 * Adds a global "hide this block" ability to Gutenberg blocks.
 *
 * The editor stores a small block attribute. PHP owns the frontend decision so
 * hidden blocks are removed before their markup is sent to the browser.
 *
 * @package GutenbergLabBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const GUTENBERG_LAB_BLOCKS_VISIBILITY_ATTRIBUTE = 'vvmHidden';

/**
 * Adds the hidden flag to every registered block type.
 *
 * The matching editor script adds the same attribute in JavaScript. Keeping the
 * PHP registration in sync makes the attribute visible to WordPress server APIs
 * and avoids treating the saved flag as unknown block data.
 *
 * @param array<string, mixed> $args       Block type registration arguments.
 * @param string               $block_name Registered block name.
 * @return array<string, mixed>
 */
function gutenberg_lab_blocks_register_visibility_attribute( $args, $block_name ) {
	if ( empty( $block_name ) || ! is_string( $block_name ) ) {
		return $args;
	}

	if ( empty( $args['attributes'] ) || ! is_array( $args['attributes'] ) ) {
		$args['attributes'] = array();
	}

	if ( ! isset( $args['attributes'][ GUTENBERG_LAB_BLOCKS_VISIBILITY_ATTRIBUTE ] ) ) {
		$args['attributes'][ GUTENBERG_LAB_BLOCKS_VISIBILITY_ATTRIBUTE ] = array(
			'type'    => 'boolean',
			'default' => false,
		);
	}

	return $args;
}
add_filter( 'register_block_type_args', 'gutenberg_lab_blocks_register_visibility_attribute', 10, 2 );

/**
 * Checks whether a parsed block has the project hidden flag enabled.
 *
 * @param array<string, mixed> $parsed_block Parsed Gutenberg block data.
 * @return bool
 */
function gutenberg_lab_blocks_is_block_hidden_on_frontend( $parsed_block ) {
	if ( ! is_array( $parsed_block ) || empty( $parsed_block['attrs'] ) || ! is_array( $parsed_block['attrs'] ) ) {
		return false;
	}

	return ! empty( $parsed_block['attrs'][ GUTENBERG_LAB_BLOCKS_VISIBILITY_ATTRIBUTE ] );
}

/**
 * Short-circuits hidden blocks before WordPress renders their markup.
 *
 * This runs earlier than the normal `render_block` output filter, which matters
 * for dynamic blocks because expensive queries/render callbacks do not need to
 * run when the editor has explicitly hidden the block.
 *
 * @param string|null          $pre_render   Existing pre-rendered markup.
 * @param array<string, mixed> $parsed_block Parsed Gutenberg block data.
 * @return string|null
 */
function gutenberg_lab_blocks_prevent_hidden_block_render( $pre_render, $parsed_block ) {
	if ( null !== $pre_render ) {
		return $pre_render;
	}

	if ( gutenberg_lab_blocks_is_block_hidden_on_frontend( $parsed_block ) ) {
		return '';
	}

	return $pre_render;
}
add_filter( 'pre_render_block', 'gutenberg_lab_blocks_prevent_hidden_block_render', 10, 2 );

/**
 * Enqueues editor-only controls for the global block visibility setting.
 */
function gutenberg_lab_blocks_enqueue_block_visibility_editor_assets() {
	wp_enqueue_script(
		'gutenberg-lab-blocks-block-visibility-editor',
		plugins_url( 'assets/js/block-visibility-editor.js', dirname( __DIR__ ) . '/gutenberg-lab-blocks.php' ),
		array(
			'wp-block-editor',
			'wp-blocks',
			'wp-components',
			'wp-compose',
			'wp-element',
			'wp-hooks',
			'wp-i18n',
		),
		gutenberg_lab_blocks_asset_version( 'assets/js/block-visibility-editor.js' ),
		array(
			'in_footer' => true,
		)
	);

	wp_enqueue_style(
		'gutenberg-lab-blocks-block-visibility-editor',
		plugins_url( 'assets/css/block-visibility-editor.css', dirname( __DIR__ ) . '/gutenberg-lab-blocks.php' ),
		array( 'wp-edit-blocks' ),
		gutenberg_lab_blocks_asset_version( 'assets/css/block-visibility-editor.css' )
	);
}
add_action( 'enqueue_block_editor_assets', 'gutenberg_lab_blocks_enqueue_block_visibility_editor_assets' );
