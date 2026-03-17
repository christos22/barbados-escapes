<?php
/**
 * Dynamic render for the related packages block.
 *
 * @package GutenbergLabBlocks
 */

$columns = in_array( (string) ( $attributes['columns'] ?? '3' ), array( '2', '3' ), true ) ? (string) $attributes['columns'] : '3';

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => sprintf( 'vvm-related-packages vvm-related-packages--columns-%s', $columns ),
	)
);

echo gutenberg_lab_blocks_render_packages_display_markup(
	array(
		'heading'         => $attributes['heading'] ?? '',
		'introText'       => $attributes['introText'] ?? '',
		'count'           => $attributes['count'] ?? 3,
		'columns'         => $columns,
		'displayMode'     => 'grid',
		'excludeCurrent'  => ! empty( $attributes['excludeCurrent'] ),
		'showPackageType' => true,
		'showExcerpt'     => true,
		'showPrice'       => true,
		'showCta'         => false,
	),
	$block ?? null,
	$wrapper_attributes,
	$content ?? ''
); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
