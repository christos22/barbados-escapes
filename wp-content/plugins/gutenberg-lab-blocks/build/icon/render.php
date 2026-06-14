<?php
/**
 * Dynamic render for the standalone Icon block.
 *
 * @package GutenbergLabBlocks
 */

$icon_slug = (string) ( $attributes['iconSlug'] ?? 'default' );

if ( function_exists( 'gutenberg_lab_blocks_sanitize_villa_amenity_icon_key' ) ) {
	$icon_slug = '' !== $icon_slug
		? gutenberg_lab_blocks_sanitize_villa_amenity_icon_key( $icon_slug )
		: '';
} else {
	$icon_slug = sanitize_key( $icon_slug );
}

if ( '' === $icon_slug || ! function_exists( 'gutenberg_lab_blocks_get_villa_amenity_icon_svg' ) ) {
	return;
}

$icon_markup = gutenberg_lab_blocks_get_villa_amenity_icon_svg( $icon_slug );

if ( '' === $icon_markup ) {
	return;
}

$saved_attributes = array();

if ( isset( $block ) && $block instanceof WP_Block && isset( $block->parsed_block['attrs'] ) && is_array( $block->parsed_block['attrs'] ) ) {
	$saved_attributes = $block->parsed_block['attrs'];
}

$allowed_size_presets       = array( 'xs', 'sm', 'md', 'lg', 'xl', 'custom' );
$allowed_alignments         = array( 'left', 'center', 'right' );
$has_saved_size_preset_attr = array_key_exists( 'sizePreset', $saved_attributes );
$size_preset                = sanitize_key( (string) ( $attributes['sizePreset'] ?? 'md' ) );
$alignment                  = sanitize_key( (string) ( $attributes['alignment'] ?? 'left' ) );
$aria_label                 = sanitize_text_field( (string) ( $attributes['ariaLabel'] ?? '' ) );
$preset_size_styles         = array(
	'xs' => '--vvm-icon-size-md:3.5rem;--vvm-icon-block-size:1.75rem;',
	'sm' => '--vvm-icon-size-md:3.5rem;--vvm-icon-block-size:2.625rem;',
	'md' => '--vvm-icon-size-md:3.5rem;--vvm-icon-block-size:var(--vvm-icon-size-md);',
	'lg' => '--vvm-icon-size-md:3.5rem;--vvm-icon-block-size:5.25rem;',
	'xl' => '--vvm-icon-size-md:3.5rem;--vvm-icon-block-size:7rem;',
);
$alignment_styles           = array(
	'left'   => 'justify-content:flex-start;',
	'center' => 'justify-content:center;',
	'right'  => 'justify-content:flex-end;',
);

if ( ! in_array( $size_preset, $allowed_size_presets, true ) ) {
	$size_preset = 'md';
}

if ( ! in_array( $alignment, $allowed_alignments, true ) ) {
	$alignment = 'left';
}

$custom_size_style = function_exists( 'gutenberg_lab_blocks_get_icon_size_css_var_style' )
	? gutenberg_lab_blocks_get_icon_size_css_var_style(
		'--vvm-icon-block-size',
		$attributes['customSize'] ?? 0,
		0.75,
		12.0
	)
	: '';

if ( 'custom' === $size_preset || ( ! $has_saved_size_preset_attr && '' !== $custom_size_style ) ) {
	$size_preset = 'custom';
	$size_style  = '' !== $custom_size_style ? $custom_size_style : $preset_size_styles['md'];
} else {
	$size_style = $preset_size_styles[ $size_preset ] ?? $preset_size_styles['md'];
}

$wrapper_classes = array(
	'vvm-icon',
	'vvm-icon--has-icon',
	'vvm-icon--size-' . $size_preset,
	'vvm-icon--align-' . $alignment,
);
$wrapper_args    = array(
	'class' => implode( ' ', $wrapper_classes ),
	'style' => implode(
		'',
		array(
			'box-sizing:border-box;',
			'display:flex;',
			'inline-size:100%;',
			'line-height:0;',
			$alignment_styles[ $alignment ],
			$size_style,
		)
	),
);

$glyph_attributes = '' !== $aria_label
	? sprintf( ' role="img" aria-label="%s"', esc_attr( $aria_label ) )
	: ' aria-hidden="true"';
$glyph_style      = 'block-size:var(--vvm-icon-block-size);color:inherit;display:grid;flex:0 0 auto;inline-size:var(--vvm-icon-block-size);place-items:center;';
$svg_style        = 'block-size:100%;display:block;fill:none;inline-size:100%;stroke:currentColor;stroke-linecap:round;stroke-linejoin:round;stroke-width:1.35;';

if ( ! preg_match( '/<svg\b[^>]*\bstyle=/i', $icon_markup ) ) {
	$icon_markup = preg_replace(
		'/<svg\b/i',
		'<svg style="' . esc_attr( $svg_style ) . '"',
		$icon_markup,
		1
	);
}
?>

<div <?php echo get_block_wrapper_attributes( $wrapper_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<span class="vvm-icon__glyph"<?php echo $glyph_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> style="<?php echo esc_attr( $glyph_style ); ?>">
		<?php echo $icon_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</span>
</div>
