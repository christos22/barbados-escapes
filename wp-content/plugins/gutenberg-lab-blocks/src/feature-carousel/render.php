<?php
/**
 * Server rendering for the Feature Carousel block.
 *
 * The heavy lifting lives in the shared peeking-carousel helper so the paired
 * Two-Up variant can reuse the same rendering path without duplicating markup.
 */

echo gutenberg_lab_peeking_carousel_render(
	$attributes,
	$block ?? null,
	array(
		'child_block_name'               => 'gutenberg-lab-blocks/feature-carousel-slide',
		'root_data_attribute'            => 'data-feature-carousel-root',
		'carousel_data_attribute'        => 'data-feature-carousel',
		'previous_button_data_attribute' => 'data-feature-carousel-prev',
		'next_button_data_attribute'     => 'data-feature-carousel-next',
		'slide_data_attribute'           => 'data-feature-carousel-slide',
		'empty_state_message'            => __( 'Add feature slides to build this carousel.', 'gutenberg-lab-blocks' ),
		'carousel_label'                 => __( 'Feature carousel', 'gutenberg-lab-blocks' ),
		'previous_button_label'          => __( 'Previous feature', 'gutenberg-lab-blocks' ),
		'next_button_label'              => __( 'Next feature', 'gutenberg-lab-blocks' ),
		'id_prefix'                      => 'vvm-feature-carousel-',
	)
); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
