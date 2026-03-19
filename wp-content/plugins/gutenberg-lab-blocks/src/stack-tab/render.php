<?php
/**
 * Server rendering for one Stack Tab panel.
 *
 * Each child tab turns its nested `stack-tab-item` blocks into a left-column
 * reveal list and a matching right-column media stage.
 *
 * @package GutenbergLabBlocks
 */

if ( ! function_exists( 'gutenberg_lab_stack_tabs_render_nested_blocks' ) ) {
	/**
	 * Render the nested content blocks inside one reveal item.
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
$item_blocks         = array_values(
	array_filter(
		$parsed_inner_blocks,
		static function ( $inner_block ) {
			return isset( $inner_block['blockName'] ) && 'gutenberg-lab-blocks/stack-tab-item' === $inner_block['blockName'];
		}
	)
);

if ( empty( $item_blocks ) ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'vvm-stack-tabs__tab-content',
	)
);
?>

<div <?php echo $wrapper_attributes; ?>>
	<div class="vvm-stack-tabs__items" data-stack-tabs-item-list>
		<?php foreach ( $item_blocks as $index => $item_block ) : ?>
			<?php
			$item_label     = trim( (string) ( $item_block['attrs']['label'] ?? '' ) );
			$item_label     = '' !== $item_label ? $item_label : sprintf( __( 'Reveal Item %d', 'gutenberg-lab-blocks' ), $index + 1 );
			$item_markup    = gutenberg_lab_stack_tabs_render_nested_blocks( $item_block['innerBlocks'] ?? array() );
			$item_button_id = sanitize_html_class( wp_unique_id( 'vvm-stack-tabs-item-button-' ) );
			$item_panel_id  = sanitize_html_class( wp_unique_id( 'vvm-stack-tabs-item-panel-' ) );
			$is_active      = 0 === $index;
			?>
			<article class="vvm-stack-tabs__item<?php echo $is_active ? ' is-active' : ''; ?>" data-stack-tabs-item>
				<button
					type="button"
					id="<?php echo esc_attr( $item_button_id ); ?>"
					class="vvm-stack-tabs__item-button<?php echo $is_active ? ' is-active' : ''; ?>"
					aria-controls="<?php echo esc_attr( $item_panel_id ); ?>"
					aria-expanded="<?php echo $is_active ? 'true' : 'false'; ?>"
					data-stack-tabs-item-button
				>
					<span class="vvm-stack-tabs__item-index"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></span>
					<span class="vvm-stack-tabs__item-label"><?php echo esc_html( $item_label ); ?></span>
				</button>

				<div
					id="<?php echo esc_attr( $item_panel_id ); ?>"
					class="vvm-stack-tabs__item-body<?php echo $is_active ? ' is-active' : ''; ?>"
					role="region"
					aria-labelledby="<?php echo esc_attr( $item_button_id ); ?>"
					data-stack-tabs-item-body
				>
					<div class="vvm-stack-tabs__item-content">
						<?php echo $item_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>
			</article>
		<?php endforeach; ?>
	</div>

	<div class="vvm-stack-tabs__media-stage" aria-live="polite" data-stack-tabs-stage>
		<?php foreach ( $item_blocks as $index => $item_block ) : ?>
			<?php
			$item_label = trim( (string) ( $item_block['attrs']['label'] ?? '' ) );
			$item_label = '' !== $item_label ? $item_label : sprintf( __( 'Reveal Item %d', 'gutenberg-lab-blocks' ), $index + 1 );
			$media_url  = $item_block['attrs']['mediaUrl'] ?? '';
			$media_alt  = $item_block['attrs']['mediaAlt'] ?? '';
			$is_active  = 0 === $index;
			?>
			<figure class="vvm-stack-tabs__media-panel<?php echo $is_active ? ' is-active' : ''; ?>" data-stack-tabs-stage-panel>
				<?php if ( '' !== $media_url ) : ?>
					<img
						class="vvm-stack-tabs__media-image"
						src="<?php echo esc_url( $media_url ); ?>"
						alt="<?php echo esc_attr( $media_alt ); ?>"
					/>
				<?php else : ?>
					<div class="vvm-stack-tabs__media-placeholder">
						<p><?php echo esc_html( sprintf( __( 'Add an image for %s.', 'gutenberg-lab-blocks' ), $item_label ) ); ?></p>
					</div>
				<?php endif; ?>
			</figure>
		<?php endforeach; ?>
	</div>
</div>
