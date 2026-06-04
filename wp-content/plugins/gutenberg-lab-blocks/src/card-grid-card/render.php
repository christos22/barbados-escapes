<?php
/**
 * Server rendering for a single Card Grid Card block.
 *
 * @package GutenbergLabBlocks
 */

if ( ! function_exists( 'gutenberg_lab_card_grid_card_render_nested_blocks' ) ) {
	/**
	 * Renders the card's nested editor content.
	 *
	 * @param array $inner_blocks Parsed block array.
	 * @return string
	 */
	function gutenberg_lab_card_grid_card_render_nested_blocks( $inner_blocks ) {
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

$image_id  = gutenberg_lab_blocks_get_image_id_from_attributes( $attributes );
$image_url = trim( (string) ( $attributes['imageUrl'] ?? '' ) );
$image_alt = trim( (string) ( $attributes['imageAlt'] ?? '' ) );
$card_content = $block instanceof WP_Block
	? gutenberg_lab_card_grid_card_render_nested_blocks( $block->parsed_block['innerBlocks'] ?? array() )
	: '';

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'vvm-card-grid__card',
	)
);
?>

<article <?php echo $wrapper_attributes; ?>>
	<div class="vvm-card-grid__card-media">
		<?php if ( '' !== $image_url ) : ?>
			<?php
			echo gutenberg_lab_blocks_render_responsive_image(
				array(
					'alt'           => $image_alt,
					'attachment_id' => $image_id,
					'class'         => 'vvm-card-grid__card-image',
					'fallback_url'  => $image_url,
					'size'          => 'gutenberg-lab-card-landscape',
					'sizes'         => '(max-width: 782px) 100vw, 33vw',
				)
			); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		<?php endif; ?>
	</div>

	<div class="vvm-card-grid__card-content">
		<?php echo $card_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</article>
