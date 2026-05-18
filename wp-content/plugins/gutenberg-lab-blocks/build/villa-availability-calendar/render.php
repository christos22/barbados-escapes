<?php
/**
 * Dynamic render for the Villa Availability Calendar block.
 *
 * @package GutenbergLabBlocks
 */

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'vvm-villa-availability-calendar',
	)
);

echo gutenberg_lab_blocks_render_villa_availability_calendar( $attributes, $wrapper_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
