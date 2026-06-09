<?php
/**
 * Dynamic render for one Villa Spec Item.
 *
 * The editor stores the selected icon as a small semantic slug. PHP resolves
 * that slug to the current trusted SVG helper, keeping icon art out of saved
 * post content and aligned with the amenity taxonomy icon selector.
 *
 * @package GutenbergLabBlocks
 */

$value     = (string) ( $attributes['value'] ?? '' );
$label     = (string) ( $attributes['label'] ?? '' );
$icon_slug = (string) ( $attributes['iconSlug'] ?? '' );

if ( function_exists( 'gutenberg_lab_blocks_sanitize_villa_amenity_icon_key' ) ) {
	$icon_slug = '' !== $icon_slug
		? gutenberg_lab_blocks_sanitize_villa_amenity_icon_key( $icon_slug )
		: '';
} else {
	$icon_slug = sanitize_key( $icon_slug );
}

if ( '' === $value && '' === $label && '' === $icon_slug ) {
	return '';
}

$wrapper_classes    = array( 'vvm-villa-specs__item' );
$has_resolved_icon  = '' !== $icon_slug && function_exists( 'gutenberg_lab_blocks_get_villa_amenity_icon_svg' );
$wrapper_classes[]  = $has_resolved_icon ? 'vvm-villa-specs__item--has-icon' : '';
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', array_filter( $wrapper_classes ) ),
	)
);
$icon_style         = function_exists( 'gutenberg_lab_blocks_get_icon_size_css_var_style' )
	? gutenberg_lab_blocks_get_icon_size_css_var_style(
		'--vvm-villa-spec-icon-size',
		$attributes['iconSize'] ?? 0,
		1.0,
		6.0
	)
	: '';
?>

<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $has_resolved_icon ) : ?>
		<span class="vvm-villa-specs__icon" aria-hidden="true"<?php echo '' !== $icon_style ? ' style="' . esc_attr( $icon_style ) . '"' : ''; ?>>
			<?php echo gutenberg_lab_blocks_get_villa_amenity_icon_svg( $icon_slug ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</span>
	<?php endif; ?>

	<?php if ( '' !== $value ) : ?>
		<p class="vvm-villa-specs__value"><?php echo wp_kses_post( $value ); ?></p>
	<?php endif; ?>

	<?php if ( '' !== $label ) : ?>
		<p class="vvm-villa-specs__label"><?php echo wp_kses_post( $label ); ?></p>
	<?php endif; ?>
</div>
