<?php
/**
 * Dynamic render for the package meta block.
 *
 * @package GutenbergLabBlocks
 */

$package_id = gutenberg_lab_blocks_resolve_package_id( $block ?? null, true );
$variant    = $attributes['variant'] ?? 'hero';
$markup     = gutenberg_lab_blocks_render_package_meta_markup( $package_id, $variant );

if ( '' === $markup ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes(); ?>>
	<?php echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
