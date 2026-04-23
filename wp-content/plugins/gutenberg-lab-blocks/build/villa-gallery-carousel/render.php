<?php
/**
 * Server rendering for the Villa Gallery Carousel block.
 *
 * The parent block owns the Splide shell and caption line while each child
 * slide stores the authored image and compact overlay copy.
 */

if ( ! function_exists( 'gutenberg_lab_villa_gallery_carousel_is_editor_preview' ) ) {
	/**
	 * Returns whether the current render is happening inside the editor preview.
	 *
	 * @return bool
	 */
	function gutenberg_lab_villa_gallery_carousel_is_editor_preview() {
		return is_admin() ||
			( function_exists( 'wp_is_serving_rest_request' ) && wp_is_serving_rest_request() ) ||
			( defined( 'REST_REQUEST' ) && REST_REQUEST );
	}
}

if ( ! function_exists( 'gutenberg_lab_villa_gallery_carousel_slide_has_content' ) ) {
	/**
	 * Returns whether one parsed child slide contains any meaningful content.
	 *
	 * @param array $slide_block Parsed child block.
	 * @return bool
	 */
	function gutenberg_lab_villa_gallery_carousel_slide_has_content( $slide_block ) {
		if ( ! is_array( $slide_block ) ) {
			return false;
		}

		$attrs = is_array( $slide_block['attrs'] ?? null ) ? $slide_block['attrs'] : array();
		$scalar_values = array(
			$attrs['imageUrl'] ?? '',
			$attrs['eyebrow'] ?? '',
			$attrs['title'] ?? '',
			$attrs['detail'] ?? '',
		);

		foreach ( $scalar_values as $value ) {
			if ( is_string( $value ) && '' !== trim( $value ) ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'gutenberg_lab_villa_gallery_carousel_get_slide_blocks' ) ) {
	/**
	 * Returns the meaningful child slide blocks from the current parent block.
	 *
	 * @param WP_Block|null $block Current parent block.
	 * @return array<int, array>
	 */
	function gutenberg_lab_villa_gallery_carousel_get_slide_blocks( $block ) {
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
						'gutenberg-lab-blocks/villa-gallery-carousel-slide' === ( $inner_block['blockName'] ?? '' ) &&
						gutenberg_lab_villa_gallery_carousel_slide_has_content( $inner_block );
				}
			)
		);
	}
}

$slide_blocks = gutenberg_lab_villa_gallery_carousel_get_slide_blocks( $block ?? null );

if ( empty( $slide_blocks ) ) {
	if ( ! gutenberg_lab_villa_gallery_carousel_is_editor_preview() ) {
		return;
	}

	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			// Let Gutenberg add align classes only when the editor explicitly sets one.
			'class' => 'vvm-villa-gallery-carousel',
		)
	);
	?>
	<section <?php echo $wrapper_attributes; ?>>
		<div class="vvm-villa-gallery-carousel__shell">
			<p class="vvm-villa-gallery-carousel__caption vvm-villa-gallery-carousel__caption-empty">
				<?php esc_html_e( 'Add gallery slides to build this villa rail.', 'gutenberg-lab-blocks' ); ?>
			</p>
		</div>
	</section>
	<?php
	return;
}

$first_slide = $slide_blocks[0]['attrs'] ?? array();
$first_title = trim( (string) ( $first_slide['title'] ?? '' ) );
$first_detail = trim( (string) ( $first_slide['detail'] ?? '' ) );
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		// A default `alignfull` breakout causes nested uses, such as tab panels,
		// to overflow their container. Only apply alignment when authored.
		'class' => 'vvm-villa-gallery-carousel',
		'data-villa-gallery-carousel-root' => '',
	)
);
?>

<section <?php echo $wrapper_attributes; ?>>
	<div class="vvm-villa-gallery-carousel__shell">
		<div
			class="vvm-villa-gallery-carousel__carousel splide is-rendered vvm-slider-surface"
			data-villa-gallery-carousel
		>
			<div
				<?php
				echo gutenberg_lab_blocks_get_slider_controls_attributes(
					$attributes,
					array(
						'class_name' => 'vvm-villa-gallery-carousel__controls',
					)
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			>
				<button
					type="button"
					class="vvm-villa-gallery-carousel__rail-button vvm-slider-button vvm-slider-button--prev"
					data-villa-gallery-carousel-rail-prev
					aria-label="<?php esc_attr_e( 'Scroll gallery thumbnails backward', 'gutenberg-lab-blocks' ); ?>"
					hidden
				>
					<?php echo gutenberg_lab_blocks_get_slider_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
				<button
					type="button"
					class="vvm-villa-gallery-carousel__rail-button vvm-slider-button vvm-slider-button--next"
					data-villa-gallery-carousel-rail-next
					aria-label="<?php esc_attr_e( 'Scroll gallery thumbnails forward', 'gutenberg-lab-blocks' ); ?>"
					hidden
				>
					<?php echo gutenberg_lab_blocks_get_slider_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
			</div>
			<div class="splide__track">
				<div class="splide__list">
					<?php foreach ( $slide_blocks as $index => $slide_block ) : ?>
						<?php
						$slide_attributes = is_array( $slide_block['attrs'] ?? null ) ? $slide_block['attrs'] : array();
						$image_url = trim( (string) ( $slide_attributes['imageUrl'] ?? '' ) );
						$image_alt = trim( (string) ( $slide_attributes['imageAlt'] ?? '' ) );
						$eyebrow = trim( (string) ( $slide_attributes['eyebrow'] ?? '' ) );
						$title = trim( (string) ( $slide_attributes['title'] ?? '' ) );
						$detail = trim( (string) ( $slide_attributes['detail'] ?? '' ) );
						$caption_title = '' !== $title ? $title : __( 'Gallery image', 'gutenberg-lab-blocks' );
						$card_label = trim( $caption_title . ( '' !== $detail ? ': ' . $detail : '' ) );
						?>
						<article class="splide__slide vvm-villa-gallery-carousel__slide<?php echo 0 === $index ? ' is-active' : ''; ?>">
							<button
								type="button"
								class="vvm-villa-gallery-carousel__card"
								data-villa-gallery-card
								data-caption-title="<?php echo esc_attr( $caption_title ); ?>"
								data-caption-detail="<?php echo esc_attr( $detail ); ?>"
								aria-pressed="<?php echo 0 === $index ? 'true' : 'false'; ?>"
								aria-current="<?php echo 0 === $index ? 'true' : 'false'; ?>"
								aria-label="<?php echo esc_attr( $card_label ); ?>"
							>
								<div class="vvm-villa-gallery-carousel__slide-media<?php echo '' !== $image_url ? '' : ' vvm-villa-gallery-carousel__slide-media--placeholder'; ?>">
									<?php if ( '' !== $image_url ) : ?>
										<img
											class="vvm-villa-gallery-carousel__slide-image"
											src="<?php echo esc_url( $image_url ); ?>"
											alt="<?php echo esc_attr( $image_alt ); ?>"
										/>
									<?php else : ?>
										<span class="vvm-villa-gallery-carousel__slide-placeholder">
											<?php esc_html_e( 'Add image', 'gutenberg-lab-blocks' ); ?>
										</span>
									<?php endif; ?>
								</div>

								<div class="vvm-villa-gallery-carousel__copy">
									<?php if ( '' !== $eyebrow ) : ?>
										<p class="vvm-villa-gallery-carousel__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
									<?php endif; ?>

									<?php if ( '' !== $title ) : ?>
										<h3 class="vvm-villa-gallery-carousel__title"><?php echo esc_html( $title ); ?></h3>
									<?php endif; ?>

									<?php if ( '' !== $detail ) : ?>
										<p class="vvm-villa-gallery-carousel__detail"><?php echo esc_html( $detail ); ?></p>
									<?php endif; ?>
								</div>
							</button>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<p class="vvm-villa-gallery-carousel__caption" data-villa-gallery-carousel-caption aria-live="polite">
			<span class="vvm-villa-gallery-carousel__caption-title" data-villa-gallery-carousel-caption-title>
				<?php echo esc_html( '' !== $first_title ? $first_title : __( 'Gallery image', 'gutenberg-lab-blocks' ) ); ?>
			</span>
			<span
				class="vvm-villa-gallery-carousel__caption-separator"
				data-villa-gallery-carousel-caption-separator
				<?php echo '' === $first_detail ? 'hidden' : ''; ?>
				aria-hidden="true"
			>
				&mdash;
			</span>
			<span
				class="vvm-villa-gallery-carousel__caption-detail"
				data-villa-gallery-carousel-caption-detail
				<?php echo '' === $first_detail ? 'hidden' : ''; ?>
			>
				<?php echo esc_html( $first_detail ); ?>
			</span>
		</p>
	</div>
</section>
