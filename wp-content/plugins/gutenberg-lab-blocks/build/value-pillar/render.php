<?php
/**
 * Server rendering for one Value Pillar child block.
 *
 * Each child block stores a semantic icon slug plus its locked heading/body
 * content. PHP turns that slug into the current temporary Dashicon output.
 *
 * @package GutenbergLabBlocks
 */

$icon_slug      = $attributes['iconSlug'] ?? 'curated';
$dashicon_class = gutenberg_lab_blocks_get_value_pillar_dashicon_class( $icon_slug );

if ( '' === trim( wp_strip_all_tags( $content ) ) ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'vvm-value-pillars__item',
	)
);
?>

<article <?php echo $wrapper_attributes; ?>>
	<div class="vvm-value-pillars__icon-wrap" aria-hidden="true">
		<span class="vvm-value-pillars__icon dashicons <?php echo esc_attr( $dashicon_class ); ?>"></span>
	</div>

	<div class="vvm-value-pillars__body">
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</article>
