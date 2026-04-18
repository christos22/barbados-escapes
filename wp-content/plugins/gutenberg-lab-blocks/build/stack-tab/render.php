<?php
/**
 * Server rendering for one Stack Tab panel.
 *
 * Each child tab now acts as a simple content container. Editors add any
 * Gutenberg blocks they want inside the panel, and the parent block handles
 * the interactive tab switching.
 *
 * @package GutenbergLabBlocks
 */

if ( ! function_exists( 'gutenberg_lab_stack_tabs_render_nested_blocks' ) ) {
	/**
	 * Render parsed inner blocks into one markup string.
	 *
	 * @param array $inner_blocks Parsed nested block array.
	 * @return string
	 */
	function gutenberg_lab_stack_tabs_render_nested_blocks( $inner_blocks ) {
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

$parsed_inner_blocks = $block->parsed_block['innerBlocks'] ?? array();
$parsed_inner_blocks = is_array( $parsed_inner_blocks ) ? $parsed_inner_blocks : array();
$panel_markup        = gutenberg_lab_stack_tabs_render_nested_blocks( $parsed_inner_blocks );

if ( '' === trim( wp_strip_all_tags( $panel_markup ) ) ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'vvm-stack-tabs__tab-content',
	)
);
?>

<div <?php echo $wrapper_attributes; ?>>
	<?php echo $panel_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
