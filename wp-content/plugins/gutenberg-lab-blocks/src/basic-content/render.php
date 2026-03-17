<?php
/**
 * Server-rendered wrapper for the Basic Content block.
 *
 * The nested content is stored as regular blocks so editors still get a native
 * Gutenberg experience, while PHP owns the outer wrapper and layout classes.
 */

$with_sidebar           = ! empty( $attributes['withSidebar'] );
$content_width          = $attributes['contentWidth'] ?? '100_percent';
$content_alignment      = $attributes['contentAlignment'] ?? 'left';
$content_text_alignment = $attributes['contentTextAlignment'] ?? 'left';
$sidebar_position       = $attributes['sidebarPosition'] ?? 'right';
$spacing_top            = $attributes['spacingTop'] ?? 'medium';
$spacing_bottom         = $attributes['spacingBottom'] ?? 'medium';
$background_type        = $attributes['backgroundType'] ?? 'no_background';
$background_color       = $attributes['backgroundColor'] ?? '';
$background_image       = $attributes['backgroundImageUrl'] ?? '';
$hide_section           = ! empty( $attributes['hideSection'] );
$native_styles          = $attributes['style'] ?? array();

if ( $hide_section ) {
	return;
}

$classes = array(
	'vvm-basic-content',
	$with_sidebar ? 'vvm-basic-content--with-sidebar' : 'vvm-basic-content--no-sidebar',
	'vvm-basic-content--content-width-' . sanitize_html_class( str_replace( '_percent', '', $content_width ) ),
	'vvm-basic-content--content-align-' . sanitize_html_class( $content_alignment ),
	'vvm-basic-content--text-align-' . sanitize_html_class( $content_text_alignment ),
	'vvm-basic-content--sidebar-' . sanitize_html_class( $sidebar_position ),
);

$styles = array();

// Preserve old spacing choices for blocks that have not been resaved yet.
$legacy_padding_map = array(
	'no_spacing'  => '0',
	'extra_small' => 'var(--wp--preset--spacing--section-xs)',
	'small'       => 'var(--wp--preset--spacing--section-sm)',
	'medium'      => 'var(--wp--preset--spacing--section-md)',
	'large'       => 'var(--wp--preset--spacing--section-lg)',
	'extra_large' => 'var(--wp--preset--spacing--section-xl)',
);
$native_padding_top    = $native_styles['spacing']['padding']['top'] ?? '';
$native_padding_bottom = $native_styles['spacing']['padding']['bottom'] ?? '';
$native_background     = $native_styles['color']['background'] ?? '';

if (
	'medium' !== $spacing_top &&
	'' === $native_padding_top &&
	isset( $legacy_padding_map[ $spacing_top ] )
) {
	$styles[] = 'padding-top:' . $legacy_padding_map[ $spacing_top ];
}

if (
	'medium' !== $spacing_bottom &&
	'' === $native_padding_bottom &&
	isset( $legacy_padding_map[ $spacing_bottom ] )
) {
	$styles[] = 'padding-bottom:' . $legacy_padding_map[ $spacing_bottom ];
}

if ( 'color' === $background_type && '' !== $background_color && '' === $native_background ) {
	$styles[] = 'background-color:' . esc_attr( $background_color );
}

// Background images still need a custom inline style because this block does
// not get a native Gutenberg background-image control.
if ( '' !== $background_image ) {
	$styles[] = 'background-image:url(' . esc_url( $background_image ) . ')';
	$styles[] = 'background-position:center';
	$styles[] = 'background-repeat:no-repeat';
	$styles[] = 'background-size:cover';
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', $classes ),
		'style' => implode( ';', $styles ),
	)
);
?>

<section <?php echo $wrapper_attributes; ?>>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</section>
