<?php
/**
 * Dynamic render for the Villa Hero Search block.
 *
 * @package GutenbergLabBlocks
 */

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'vvm-villa-hero-search',
	)
);

echo gutenberg_lab_blocks_render_villa_hero_search_markup( $attributes, $wrapper_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
