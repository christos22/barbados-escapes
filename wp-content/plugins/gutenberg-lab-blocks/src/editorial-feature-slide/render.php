<?php
/**
 * Server rendering for one Editorial Feature Item.
 *
 * The child block keeps one consistent two-column wrapper while the inner
 * content remains ordinary Gutenberg heading, paragraph, list, and button blocks.
 */

if ( ! function_exists( 'gutenberg_lab_editorial_feature_render_nested_blocks' ) ) {
	/**
	 * Renders a parsed nested block list.
	 *
	 * @param array $inner_blocks Parsed block list.
	 * @return string
	 */
	function gutenberg_lab_editorial_feature_render_nested_blocks( $inner_blocks ) {
		if ( ! is_array( $inner_blocks ) || empty( $inner_blocks ) ) {
			return '';
		}

		$markup = '';

		foreach ( $inner_blocks as $inner_block ) {
			if ( ! is_array( $inner_block ) ) {
				continue;
			}

			$markup .= render_block( $inner_block );
		}

		return $markup;
	}
}

$slide_content = $block instanceof WP_Block
	? gutenberg_lab_editorial_feature_render_nested_blocks( $block->parsed_block['innerBlocks'] ?? array() )
	: '';

if ( '' === trim( $slide_content ) ) {
	return;
}

$icon_slug = (string) ( $attributes['iconSlug'] ?? '' );

if ( function_exists( 'gutenberg_lab_blocks_sanitize_villa_amenity_icon_key' ) ) {
	$icon_slug = '' !== $icon_slug
		? gutenberg_lab_blocks_sanitize_villa_amenity_icon_key( $icon_slug )
		: '';
} else {
	$icon_slug = sanitize_key( $icon_slug );
}

$icon_markup = '';

if ( '' !== $icon_slug && function_exists( 'gutenberg_lab_blocks_get_villa_amenity_icon_svg' ) ) {
	$icon_markup = gutenberg_lab_blocks_get_villa_amenity_icon_svg( $icon_slug );
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode(
			' ',
			array_filter(
				array(
					'vvm-editorial-feature__slide',
					'' !== $icon_markup ? 'vvm-editorial-feature__slide--has-icon' : '',
				)
			)
		),
	)
);
?>

<article <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="vvm-editorial-feature__slide-grid">
		<?php if ( '' !== $icon_markup ) : ?>
			<span class="vvm-editorial-feature__icon" aria-hidden="true">
				<?php echo $icon_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</span>
		<?php endif; ?>

		<?php echo $slide_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</article>
