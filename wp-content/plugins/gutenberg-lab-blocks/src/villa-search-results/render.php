<?php
/**
 * Dynamic render for the Villa Search Results block.
 *
 * @package GutenbergLabBlocks
 */

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode(
			' ',
			array(
				// The results reuse the exact card-grid presentation contract.
				'wp-block-gutenberg-lab-blocks-card-grid',
				'vvm-card-grid',
				'vvm-card-grid--display-grid',
				'vvm-card-grid--source-villas',
				'vvm-card-grid--villa-presentation-collection',
				'vvm-card-grid--columns-2',
				'vvm-card-grid--ratio-landscape',
				'vvm-villa-search-results',
			)
		),
		'style' => '--vvm-card-grid-gap:var(--wp--preset--spacing--lg);',
	)
);

echo gutenberg_lab_blocks_render_villa_search_results_markup( $wrapper_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
