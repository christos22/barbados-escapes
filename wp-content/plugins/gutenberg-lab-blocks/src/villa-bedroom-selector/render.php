<?php
/**
 * Dynamic render for the Villa Bedroom Selector block.
 *
 * @package GutenbergLabBlocks
 */

$context_id = isset( $block->context['postId'] )
	? absint( $block->context['postId'] )
	: 0;
$villa_id   = gutenberg_lab_blocks_resolve_villa_booking_post_id( $context_id );

$minimum = max( 1, absint( $attributes['minimumBedrooms'] ?? 1 ) );

echo gutenberg_lab_blocks_render_villa_bedroom_selector(
	$villa_id,
	$minimum,
	get_block_wrapper_attributes(
		array(
			'class'                          => 'vvm-villa-bedroom-selector',
			'data-vvm-bedroom-selector-root' => '',
		)
	)
);
