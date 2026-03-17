<?php
/**
 * Dynamic render for the packages display block.
 *
 * @package GutenbergLabBlocks
 */

$display_mode = 'carousel' === ( $attributes['displayMode'] ?? 'grid' ) ? 'carousel' : 'grid';
$columns      = in_array( (string) ( $attributes['columns'] ?? '3' ), array( '2', '3' ), true ) ? (string) $attributes['columns'] : '3';

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => sprintf(
			'vvm-packages-display vvm-packages-display--display-%1$s vvm-packages-display--columns-%2$s',
			sanitize_html_class( $display_mode ),
			sanitize_html_class( $columns )
		),
	)
);

echo gutenberg_lab_blocks_render_packages_display_markup( $attributes, $block ?? null, $wrapper_attributes, $content ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
