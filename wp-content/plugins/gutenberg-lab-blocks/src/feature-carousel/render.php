<?php
/**
 * Server rendering for the Feature Carousel block.
 *
 * The parent owns the Splide shell and parses the nested slide blocks so the
 * front end always receives one controlled editorial layout.
 */

if ( ! function_exists( 'gutenberg_lab_feature_carousel_is_editor_preview' ) ) {
	/**
	 * Returns whether the current render is happening inside the editor preview.
	 *
	 * @return bool
	 */
	function gutenberg_lab_feature_carousel_is_editor_preview() {
		return is_admin() ||
			( function_exists( 'wp_is_serving_rest_request' ) && wp_is_serving_rest_request() ) ||
			( defined( 'REST_REQUEST' ) && REST_REQUEST );
	}
}

if ( ! function_exists( 'gutenberg_lab_feature_carousel_render_nested_blocks' ) ) {
	/**
	 * Renders a parsed block list into markup.
	 *
	 * @param array $inner_blocks Parsed block list.
	 * @return string
	 */
	function gutenberg_lab_feature_carousel_render_nested_blocks( $inner_blocks ) {
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

if ( ! function_exists( 'gutenberg_lab_feature_carousel_slide_has_content' ) ) {
	/**
	 * Returns whether a parsed child slide has anything meaningful to render.
	 *
	 * @param array $slide_block Parsed child slide block.
	 * @return bool
	 */
	function gutenberg_lab_feature_carousel_slide_has_content( $slide_block ) {
		if ( ! is_array( $slide_block ) ) {
			return false;
		}

		$image_url = trim( (string) ( $slide_block['attrs']['imageUrl'] ?? '' ) );

		if ( '' !== $image_url ) {
			return true;
		}

		$content_markup = gutenberg_lab_feature_carousel_render_nested_blocks( $slide_block['innerBlocks'] ?? array() );

		return '' !== trim( wp_strip_all_tags( $content_markup ) );
	}
}

if ( ! function_exists( 'gutenberg_lab_feature_carousel_get_slide_blocks' ) ) {
	/**
	 * Returns the meaningful child slide blocks from the current parent block.
	 *
	 * @param WP_Block|null $block Current parent block.
	 * @return array<int, array>
	 */
	function gutenberg_lab_feature_carousel_get_slide_blocks( $block ) {
		if ( ! $block instanceof WP_Block ) {
			return array();
		}

		$parsed_inner_blocks = $block->parsed_block['innerBlocks'] ?? array();

		if ( ! is_array( $parsed_inner_blocks ) ) {
			return array();
		}

		return array_values(
			array_filter(
				$parsed_inner_blocks,
				static function ( $inner_block ) {
					return is_array( $inner_block ) &&
						'gutenberg-lab-blocks/feature-carousel-slide' === ( $inner_block['blockName'] ?? '' ) &&
						gutenberg_lab_feature_carousel_slide_has_content( $inner_block );
				}
			)
		);
	}
}

if ( ! function_exists( 'gutenberg_lab_feature_carousel_render_slide' ) ) {
	/**
	 * Renders one parsed slide block into the shared Splide slide shell.
	 *
	 * @param array $slide_block Parsed child slide block.
	 * @param int   $index Slide index.
	 * @return string
	 */
	function gutenberg_lab_feature_carousel_render_slide( $slide_block, $index ) {
		if ( ! is_array( $slide_block ) ) {
			return '';
		}

		$image_url = trim( (string) ( $slide_block['attrs']['imageUrl'] ?? '' ) );
		$image_alt = trim( (string) ( $slide_block['attrs']['imageAlt'] ?? '' ) );
		$has_image = '' !== $image_url;
		$loading   = 0 === $index ? 'eager' : 'lazy';
		$content   = gutenberg_lab_feature_carousel_render_nested_blocks( $slide_block['innerBlocks'] ?? array() );

		ob_start();
		?>
		<li class="splide__slide vvm-feature-carousel__slide-shell<?php echo 0 === $index ? ' is-active' : ''; ?>" data-feature-carousel-slide>
			<article class="vvm-feature-carousel__slide">
				<div class="vvm-feature-carousel__media<?php echo $has_image ? '' : ' vvm-feature-carousel__media--placeholder'; ?>">
					<?php if ( $has_image ) : ?>
						<img
							class="vvm-feature-carousel__image"
							src="<?php echo esc_url( $image_url ); ?>"
							alt="<?php echo esc_attr( $image_alt ); ?>"
							loading="<?php echo esc_attr( $loading ); ?>"
							decoding="async"
							fetchpriority="<?php echo esc_attr( 0 === $index ? 'high' : 'auto' ); ?>"
						/>
					<?php else : ?>
						<span class="vvm-feature-carousel__placeholder-label">
							<?php esc_html_e( 'Slide image coming soon', 'gutenberg-lab-blocks' ); ?>
						</span>
					<?php endif; ?>
				</div>

				<div class="vvm-feature-carousel__panel">
					<div class="vvm-feature-carousel__panel-flow">
						<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>
			</article>
		</li>
		<?php

		return trim( (string) ob_get_clean() );
	}
}

$slide_blocks = gutenberg_lab_feature_carousel_get_slide_blocks( $block ?? null );

if ( empty( $slide_blocks ) ) {
	if ( ! gutenberg_lab_feature_carousel_is_editor_preview() ) {
		return;
	}

	$empty_wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'vvm-feature-carousel alignfull',
		)
	);
	?>
	<section <?php echo $empty_wrapper_attributes; ?>>
		<p class="vvm-feature-carousel__empty-state">
			<?php esc_html_e( 'Add feature slides to build this carousel.', 'gutenberg-lab-blocks' ); ?>
		</p>
	</section>
	<?php
	return;
}

$carousel_id = wp_unique_id( 'vvm-feature-carousel-' );
$has_multiple_slides = count( $slide_blocks ) > 1;
$slides_markup = '';

foreach ( $slide_blocks as $index => $slide_block ) {
	$slides_markup .= gutenberg_lab_feature_carousel_render_slide( $slide_block, $index );
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode(
			' ',
			array_filter(
				array(
					'vvm-feature-carousel',
					empty( $attributes['align'] ) ? 'alignfull' : '',
				)
			)
		),
		'data-feature-carousel-root' => '',
	)
);
?>

<section <?php echo $wrapper_attributes; ?>>
	<div class="vvm-feature-carousel__shell">
		<div
			id="<?php echo esc_attr( $carousel_id ); ?>"
			class="vvm-feature-carousel__carousel splide"
			data-feature-carousel
			aria-label="<?php esc_attr_e( 'Feature carousel', 'gutenberg-lab-blocks' ); ?>"
		>
			<div class="splide__track">
				<ul class="splide__list">
					<?php echo $slides_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</ul>
			</div>
		</div>

		<?php if ( $has_multiple_slides ) : ?>
			<div class="vvm-feature-carousel__chrome">
				<div class="vvm-feature-carousel__chrome-spacer" aria-hidden="true"></div>

				<div class="vvm-feature-carousel__controls">
					<button
						type="button"
						class="vvm-feature-carousel__button vvm-feature-carousel__button--prev vvm-slider-button--prev"
						data-feature-carousel-prev
						aria-controls="<?php echo esc_attr( $carousel_id ); ?>"
						aria-label="<?php esc_attr_e( 'Previous feature', 'gutenberg-lab-blocks' ); ?>"
					>
						<?php echo gutenberg_lab_blocks_get_slider_line_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>

					<button
						type="button"
						class="vvm-feature-carousel__button vvm-feature-carousel__button--next vvm-slider-button--next"
						data-feature-carousel-next
						aria-controls="<?php echo esc_attr( $carousel_id ); ?>"
						aria-label="<?php esc_attr_e( 'Next feature', 'gutenberg-lab-blocks' ); ?>"
					>
						<?php echo gutenberg_lab_blocks_get_slider_line_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
				</div>
			</div>
		<?php endif; ?>
	</div>
</section>
