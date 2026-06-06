<?php
/**
 * Server rendering for the Editorial Feature block.
 *
 * Editors author normal nested Gutenberg blocks. PHP owns the section wrapper
 * and swaps the same child slide markup between static and slider layouts.
 */

if ( ! function_exists( 'gutenberg_lab_editorial_feature_is_editor_preview' ) ) {
	/**
	 * Returns whether this render is for the block editor or REST preview.
	 *
	 * @return bool
	 */
	function gutenberg_lab_editorial_feature_is_editor_preview() {
		return is_admin() ||
			( function_exists( 'wp_is_serving_rest_request' ) && wp_is_serving_rest_request() ) ||
			( defined( 'REST_REQUEST' ) && REST_REQUEST );
	}
}

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

if ( ! function_exists( 'gutenberg_lab_editorial_feature_slide_has_content' ) ) {
	/**
	 * Returns whether a child editorial item has meaningful authored content.
	 *
	 * @param array $slide_block Parsed child block.
	 * @return bool
	 */
	function gutenberg_lab_editorial_feature_slide_has_content( $slide_block ) {
		if ( ! is_array( $slide_block ) ) {
			return false;
		}

		$markup = gutenberg_lab_editorial_feature_render_nested_blocks(
			$slide_block['innerBlocks'] ?? array()
		);

		return '' !== trim( wp_strip_all_tags( $markup ) );
	}
}

$slide_blocks = array();

if ( $block instanceof WP_Block ) {
	$parsed_inner_blocks = $block->parsed_block['innerBlocks'] ?? array();
	$slide_blocks        = is_array( $parsed_inner_blocks )
		? array_values(
			array_filter(
				$parsed_inner_blocks,
				static function ( $inner_block ) {
					return is_array( $inner_block ) &&
						'gutenberg-lab-blocks/editorial-feature-slide' === ( $inner_block['blockName'] ?? '' ) &&
						gutenberg_lab_editorial_feature_slide_has_content( $inner_block );
				}
			)
		)
		: array();
}

if ( empty( $slide_blocks ) ) {
	if ( ! gutenberg_lab_editorial_feature_is_editor_preview() ) {
		return;
	}

	$empty_wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'vvm-editorial-feature alignfull',
		)
	);
	?>
	<section <?php echo $empty_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<p class="vvm-editorial-feature__empty-state">
			<?php esc_html_e( 'Add editorial items to build this section.', 'gutenberg-lab-blocks' ); ?>
		</p>
	</section>
	<?php
	return;
}

$enable_slider       = ! empty( $attributes['enableSlider'] );
$use_slider          = $enable_slider && count( $slide_blocks ) > 1;
$accent_border       = $attributes['accentBorder'] ?? 'none';
$slide_markup        = '';
$wrapper_class_names = array(
	'vvm-editorial-feature',
	$enable_slider ? 'vvm-editorial-feature--slider-enabled' : 'vvm-editorial-feature--slider-disabled',
	$use_slider ? 'vvm-editorial-feature--display-slider' : 'vvm-editorial-feature--display-static',
	'none' !== $accent_border ? 'vvm-editorial-feature--accent-border-' . sanitize_html_class( $accent_border ) : '',
	empty( $attributes['align'] ) ? 'alignfull' : '',
);

foreach ( $slide_blocks as $slide_block ) {
	$slide_markup .= render_block( $slide_block );
}

$wrapper_extra_attributes = array(
	'class' => implode( ' ', array_filter( $wrapper_class_names ) ),
);

if ( $use_slider ) {
	$wrapper_extra_attributes['data-editorial-feature-root'] = '';
}

$wrapper_attributes = get_block_wrapper_attributes( $wrapper_extra_attributes );
?>

<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="vvm-editorial-feature__inner">
		<?php if ( $use_slider ) : ?>
			<div class="vvm-editorial-feature__carousel vvm-slider-surface" data-editorial-feature-slider>
				<div class="vvm-editorial-feature__viewport" data-editorial-feature-viewport>
					<div class="vvm-editorial-feature__track" data-editorial-feature-track>
						<?php echo $slide_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>
				<div
					<?php
					echo gutenberg_lab_blocks_get_slider_controls_attributes(
						$attributes,
						array(
							'class_name' => 'vvm-editorial-feature__controls',
						)
					); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				>
					<button
						type="button"
						class="vvm-editorial-feature__button vvm-slider-button vvm-slider-button--prev"
						data-editorial-feature-prev
						aria-label="<?php esc_attr_e( 'Previous editorial feature', 'gutenberg-lab-blocks' ); ?>"
					>
						<?php echo gutenberg_lab_blocks_get_slider_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
					<button
						type="button"
						class="vvm-editorial-feature__button vvm-slider-button vvm-slider-button--next"
						data-editorial-feature-next
						aria-label="<?php esc_attr_e( 'Next editorial feature', 'gutenberg-lab-blocks' ); ?>"
					>
						<?php echo gutenberg_lab_blocks_get_slider_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
				</div>
			</div>
		<?php else : ?>
			<div class="vvm-editorial-feature__items">
				<?php echo $slide_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		<?php endif; ?>
	</div>
</section>
