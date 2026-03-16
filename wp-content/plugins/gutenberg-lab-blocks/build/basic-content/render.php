<?php
/**
 * Server-rendered wrapper for the Basic Content block.
 *
 * The nested content is stored as regular blocks so editors still get a native
 * Gutenberg experience, while PHP owns the outer wrapper and layout classes.
 */

$with_sidebar     = ! empty( $attributes['withSidebar'] );
$content_width    = $attributes['contentWidth'] ?? '100_percent';
$content_alignment = $attributes['contentAlignment'] ?? 'left';
$content_text_alignment = $attributes['contentTextAlignment'] ?? 'left';
$sidebar_position = $attributes['sidebarPosition'] ?? 'right';
$spacing_top      = $attributes['spacingTop'] ?? 'medium';
$spacing_bottom   = $attributes['spacingBottom'] ?? 'medium';
$background_type  = $attributes['backgroundType'] ?? 'no_background';
$background_color = $attributes['backgroundColor'] ?? '';
$background_image = $attributes['backgroundImageUrl'] ?? '';
$hide_section     = ! empty( $attributes['hideSection'] );

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
	'vvm-basic-content--spacing-top-' . sanitize_html_class( str_replace( '_', '-', $spacing_top ) ),
	'vvm-basic-content--spacing-bottom-' . sanitize_html_class( str_replace( '_', '-', $spacing_bottom ) ),
);

$styles = array();

if ( 'color' === $background_type && '' !== $background_color ) {
	$styles[] = 'background-color:' . esc_attr( $background_color );
}

if ( 'image' === $background_type && '' !== $background_image ) {
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
