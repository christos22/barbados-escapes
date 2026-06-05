<?php
/**
 * Server rendering for one Editorial Feature Item.
 *
 * The child block keeps one consistent two-column wrapper while the inner
 * content remains ordinary Gutenberg heading, paragraph, list, and button blocks.
 */

if ( ! function_exists( 'gutenberg_lab_editorial_feature_render_nested_blocks' ) ) {
	/**
	 * Renders a parsed nested block list.
	 *
	 * @param array $inner_blocks Parsed block list.
	 * @return string
	 */
	function gutenberg_lab_editorial_feature_render_nested_blocks( $inner_blocks ) {
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

$slide_content = $block instanceof WP_Block
	? gutenberg_lab_editorial_feature_render_nested_blocks( $block->parsed_block['innerBlocks'] ?? array() )
	: '';

if ( '' === trim( $slide_content ) ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'vvm-editorial-feature__slide',
	)
);
?>

<article <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="vvm-editorial-feature__slide-grid">
		<?php echo $slide_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</article>
