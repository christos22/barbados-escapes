<?php
/**
 * Server rendering for the manual Card Grid block.
 *
 * The block stores each card as a nested child block so editors keep a native
 * Gutenberg experience, while PHP owns the shared wrapper classes that the
 * future post-driven grid can reuse.
 */

if ( ! function_exists( 'gutenberg_lab_card_grid_normalize_spacing_preset_slug' ) ) {
	/**
	 * Convert Gutenberg spacing slugs like `2xl` into the CSS preset slug `2-xl`.
	 */
	function gutenberg_lab_card_grid_normalize_spacing_preset_slug( $spacing_slug ) {
		$spacing_slug = sanitize_title( $spacing_slug );

		return preg_replace( '/^([0-9]+)([a-z])/', '$1-$2', $spacing_slug );
	}
}

if ( ! function_exists( 'gutenberg_lab_card_grid_resolve_block_gap_value' ) ) {
	/**
	 * Resolve Gutenberg preset tokens into the CSS value used by the grid wrapper.
	 */
	function gutenberg_lab_card_grid_resolve_block_gap_value( $block_gap ) {
		if ( ! is_string( $block_gap ) || '' === $block_gap ) {
			return '';
		}

		if ( str_starts_with( $block_gap, 'var:preset|spacing|' ) ) {
			$spacing_slug = substr( $block_gap, strlen( 'var:preset|spacing|' ) );
			$spacing_slug = gutenberg_lab_card_grid_normalize_spacing_preset_slug( $spacing_slug );

			if ( '' !== $spacing_slug ) {
				return sprintf( 'var(--wp--preset--spacing--%s)', $spacing_slug );
			}
		}

		return $block_gap;
	}
}

$columns     = $attributes['columns'] ?? '2';
$media_ratio = $attributes['mediaRatio'] ?? 'landscape';
$block_gap   = $attributes['style']['spacing']['blockGap'] ?? '';

$allowed_columns = array( '2', '3' );
$allowed_ratios  = array(
	'landscape',
	'widescreen',
	'square',
	'portrait',
	'portrait-tall',
);

if ( ! in_array( $columns, $allowed_columns, true ) ) {
	$columns = '2';
}

if ( ! in_array( $media_ratio, $allowed_ratios, true ) ) {
	$media_ratio = 'landscape';
}

$styles = array();

if ( is_string( $block_gap ) && '' !== $block_gap ) {
	$block_gap = gutenberg_lab_card_grid_resolve_block_gap_value( $block_gap );
	$styles[] = '--wp--style--block-gap:' . esc_attr( $block_gap );
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode(
			' ',
			array(
				'vvm-card-grid',
				'vvm-card-grid--columns-' . sanitize_html_class( $columns ),
				'vvm-card-grid--ratio-' . sanitize_html_class( $media_ratio ),
				)
			),
		'style' => implode( ';', $styles ),
	)
);

if ( '' === trim( $content ) ) {
	return;
}
?>

<section <?php echo $wrapper_attributes; ?>>
	<div class="vvm-card-grid__items">
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</section>
