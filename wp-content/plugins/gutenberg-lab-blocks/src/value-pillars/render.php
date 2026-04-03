<?php
/**
 * Server rendering for the Value Pillars parent block.
 *
 * Gutenberg keeps the intro content and child pillar blocks in post content,
 * while PHP owns the outer section shell used on the front end.
 *
 * @package GutenbergLabBlocks
 */

if ( '' === trim( wp_strip_all_tags( $content ) ) ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		// Keep the content shell constrained inside, but let the outer wrapper
		// default to full-width so section backgrounds reach edge to edge.
		'class' => implode(
			' ',
			array_filter(
				array(
					'vvm-value-pillars',
					empty( $attributes['align'] ) ? 'alignfull' : '',
				)
			)
		),
	)
);
?>

<section <?php echo $wrapper_attributes; ?>>
	<div class="vvm-value-pillars__shell">
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</section>
