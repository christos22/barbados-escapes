<?php
/**
 * Server rendering for the Testimonial Carousel block.
 *
 * The parent block controls the section shell, faint image treatment and
 * carousel controls. Child blocks own the quote content.
 */

if ( ! function_exists( 'gutenberg_lab_testimonial_carousel_is_editor_preview' ) ) {
	/**
	 * Returns whether this render is for the block editor or REST preview.
	 *
	 * @return bool
	 */
	function gutenberg_lab_testimonial_carousel_is_editor_preview() {
		return is_admin() ||
			( function_exists( 'wp_is_serving_rest_request' ) && wp_is_serving_rest_request() ) ||
			( defined( 'REST_REQUEST' ) && REST_REQUEST );
	}
}

if ( ! function_exists( 'gutenberg_lab_testimonial_carousel_render_nested_blocks' ) ) {
	/**
	 * Renders a parsed nested block list.
	 *
	 * @param array $inner_blocks Parsed block list.
	 * @return string
	 */
	function gutenberg_lab_testimonial_carousel_render_nested_blocks( $inner_blocks ) {
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

if ( ! function_exists( 'gutenberg_lab_testimonial_carousel_clamp_opacity' ) ) {
	/**
	 * Clamps a stored percent value and returns it as a CSS alpha number.
	 *
	 * @param mixed $value Raw block attribute value.
	 * @param int   $fallback Fallback percent.
	 * @return string
	 */
	function gutenberg_lab_testimonial_carousel_clamp_opacity( $value, $fallback ) {
		$value = is_numeric( $value ) ? (int) $value : (int) $fallback;
		$value = max( 0, min( 100, $value ) );

		return rtrim( rtrim( number_format( $value / 100, 2, '.', '' ), '0' ), '.' );
	}
}

if ( ! function_exists( 'gutenberg_lab_testimonial_carousel_slide_has_content' ) ) {
	/**
	 * Returns whether one testimonial slide has meaningful quote content.
	 *
	 * @param array $slide_block Parsed child block.
	 * @return bool
	 */
	function gutenberg_lab_testimonial_carousel_slide_has_content( $slide_block ) {
		if ( ! is_array( $slide_block ) ) {
			return false;
		}

		$markup = gutenberg_lab_testimonial_carousel_render_nested_blocks(
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
						'gutenberg-lab-blocks/testimonial-carousel-slide' === ( $inner_block['blockName'] ?? '' ) &&
						gutenberg_lab_testimonial_carousel_slide_has_content( $inner_block );
				}
			)
		)
		: array();
}

if ( empty( $slide_blocks ) ) {
	if ( ! gutenberg_lab_testimonial_carousel_is_editor_preview() ) {
		return;
	}

	$empty_wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'vvm-testimonial-carousel alignfull',
		)
	);
	?>
	<section <?php echo $empty_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<p class="vvm-testimonial-carousel__empty-state">
			<?php esc_html_e( 'Add quotes to build this testimonial carousel.', 'gutenberg-lab-blocks' ); ?>
		</p>
	</section>
	<?php
	return;
}

$use_slider           = count( $slide_blocks ) > 1;
$background_image_url = isset( $attributes['backgroundImageUrl'] ) && is_string( $attributes['backgroundImageUrl'] )
	? esc_url_raw( $attributes['backgroundImageUrl'] )
	: '';
$slide_markup         = '';
$wrapper_class_names  = array(
	'vvm-testimonial-carousel',
	$use_slider ? 'vvm-testimonial-carousel--display-slider' : 'vvm-testimonial-carousel--display-static',
	'' !== $background_image_url ? 'vvm-testimonial-carousel--has-background-image' : '',
	empty( $attributes['align'] ) ? 'alignfull' : '',
);
$style_vars           = array(
	'--vvm-testimonial-image-opacity:' . gutenberg_lab_testimonial_carousel_clamp_opacity( $attributes['backgroundImageOpacity'] ?? 35, 35 ),
	'--vvm-testimonial-overlay-opacity:' . gutenberg_lab_testimonial_carousel_clamp_opacity( $attributes['overlayOpacity'] ?? 70, 70 ),
);

foreach ( $slide_blocks as $slide_block ) {
	$slide_markup .= render_block( $slide_block );
}

$wrapper_extra_attributes = array(
	'class' => implode( ' ', array_filter( $wrapper_class_names ) ),
	'style' => safecss_filter_attr( implode( ';', $style_vars ) . ';' ),
);

if ( $use_slider ) {
	$wrapper_extra_attributes['data-testimonial-carousel-root'] = '';
}

$wrapper_attributes = get_block_wrapper_attributes( $wrapper_extra_attributes );
?>

<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( '' !== $background_image_url ) : ?>
		<img
			class="vvm-testimonial-carousel__background-image"
			src="<?php echo esc_url( $background_image_url ); ?>"
			alt=""
			aria-hidden="true"
		/>
	<?php endif; ?>
	<span class="vvm-testimonial-carousel__overlay" aria-hidden="true"></span>
	<div class="vvm-testimonial-carousel__inner">
		<?php if ( $use_slider ) : ?>
			<div class="vvm-testimonial-carousel__carousel vvm-slider-surface" data-testimonial-carousel-slider>
				<div class="vvm-testimonial-carousel__viewport" data-testimonial-carousel-viewport>
					<div class="vvm-testimonial-carousel__track" data-testimonial-carousel-track>
						<?php echo $slide_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>
				<div
					<?php
					echo gutenberg_lab_blocks_get_slider_controls_attributes(
						$attributes,
						array(
							'class_name' => 'vvm-testimonial-carousel__controls',
						)
					); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				>
					<button
						type="button"
						class="vvm-testimonial-carousel__button vvm-slider-button vvm-slider-button--prev"
						data-testimonial-carousel-prev
						aria-label="<?php esc_attr_e( 'Previous testimonial', 'gutenberg-lab-blocks' ); ?>"
					>
						<?php echo gutenberg_lab_blocks_get_slider_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
					<button
						type="button"
						class="vvm-testimonial-carousel__button vvm-slider-button vvm-slider-button--next"
						data-testimonial-carousel-next
						aria-label="<?php esc_attr_e( 'Next testimonial', 'gutenberg-lab-blocks' ); ?>"
					>
						<?php echo gutenberg_lab_blocks_get_slider_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
				</div>
			</div>
		<?php else : ?>
			<div class="vvm-testimonial-carousel__items">
				<?php echo $slide_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		<?php endif; ?>
	</div>
</section>
