<?php
/**
 * Dynamic render for the Villa Specs wrapper block.
 *
 * The parent owns the structural shell and shared styling hooks, while the
 * child blocks keep the authored value/label content in post content.
 *
 * @package GutenbergLabBlocks
 */

if ( '' === trim( wp_strip_all_tags( $content ) ) ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'vvm-villa-specs',
	)
);
?>

<section <?php echo $wrapper_attributes; ?>>
	<div class="vvm-villa-specs__shell">
		<div class="vvm-villa-specs__items">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>
</section>
