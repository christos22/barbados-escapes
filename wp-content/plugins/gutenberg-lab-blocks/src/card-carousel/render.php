<?php
/**
 * Server rendering for the Card Carousel block.
 *
 * The block stores its editable intro content and manual slides as nested
 * blocks, while PHP owns the villa query mode and the final rail markup.
 */

if ( ! function_exists( 'gutenberg_lab_card_carousel_is_editor_preview' ) ) {
	/**
	 * Returns whether the current render is happening inside the editor preview.
	 *
	 * @return bool
	 */
	function gutenberg_lab_card_carousel_is_editor_preview() {
		return is_admin() ||
			( function_exists( 'wp_is_serving_rest_request' ) && wp_is_serving_rest_request() ) ||
			( defined( 'REST_REQUEST' ) && REST_REQUEST );
	}
}

if ( ! function_exists( 'gutenberg_lab_card_carousel_render_nested_blocks' ) ) {
	/**
	 * Renders one parsed block list into HTML.
	 *
	 * @param array $inner_blocks Parsed block array.
	 * @return string
	 */
	function gutenberg_lab_card_carousel_render_nested_blocks( $inner_blocks ) {
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

if ( ! function_exists( 'gutenberg_lab_card_carousel_render_manual_slide' ) ) {
	/**
	 * Renders one parsed manual slide block into the shared carousel shell.
	 *
	 * The parent is dynamic, so it cannot rely on the child block's saved HTML
	 * once the editor has already parsed the tree into nested block arrays.
	 *
	 * @param array $slide_block Parsed child slide block.
	 * @return string
	 */
	function gutenberg_lab_card_carousel_render_manual_slide( $slide_block ) {
		if ( ! is_array( $slide_block ) ) {
			return '';
		}

		$image_url = trim( (string) ( $slide_block['attrs']['imageUrl'] ?? '' ) );
		$image_alt = trim( (string) ( $slide_block['attrs']['imageAlt'] ?? '' ) );
		$content   = gutenberg_lab_card_carousel_render_nested_blocks( $slide_block['innerBlocks'] ?? array() );

		ob_start();
		?>
		<article class="vvm-card-carousel__slide">
			<div class="vvm-card-carousel__slide-media<?php echo '' !== $image_url ? '' : ' vvm-card-carousel__slide-media--placeholder'; ?>">
				<?php if ( '' !== $image_url ) : ?>
					<img
						class="vvm-card-carousel__slide-image"
						src="<?php echo esc_url( $image_url ); ?>"
						alt="<?php echo esc_attr( $image_alt ); ?>"
					/>
				<?php else : ?>
					<span class="vvm-card-carousel__slide-placeholder-label">
						<?php esc_html_e( 'Slide image coming soon', 'gutenberg-lab-blocks' ); ?>
					</span>
				<?php endif; ?>
			</div>

			<div class="vvm-card-carousel__slide-content">
				<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</article>
		<?php

		return trim( (string) ob_get_clean() );
	}
}

if ( ! function_exists( 'gutenberg_lab_card_carousel_get_manual_regions' ) ) {
	/**
	 * Extracts the intro content and manual slides from the parent block tree.
	 *
	 * The editor keeps a locked two-slot template made of core/group blocks.
	 * Parsing those groups here keeps the front-end markup lean while still
	 * letting editors use native blocks inside the intro region.
	 *
	 * @param WP_Block|null $block Current block instance.
	 * @return array<string, mixed>
	 */
	function gutenberg_lab_card_carousel_get_manual_regions( $block ) {
		$regions = array(
			'intro_markup' => '',
			'slides_markup' => '',
			'slide_count'  => 0,
		);

		if ( ! $block instanceof WP_Block ) {
			return $regions;
		}

		$parsed_inner_blocks = $block->parsed_block['innerBlocks'] ?? array();
		$parsed_inner_blocks = is_array( $parsed_inner_blocks ) ? $parsed_inner_blocks : array();

		foreach ( $parsed_inner_blocks as $inner_block ) {
			if ( ! is_array( $inner_block ) || 'core/group' !== ( $inner_block['blockName'] ?? '' ) ) {
				continue;
			}

			$class_name = (string) ( $inner_block['attrs']['className'] ?? '' );

			if ( str_contains( $class_name, 'vvm-card-carousel__intro' ) ) {
				$regions['intro_markup'] = gutenberg_lab_card_carousel_render_nested_blocks( $inner_block['innerBlocks'] ?? array() );
				continue;
			}

			if ( ! str_contains( $class_name, 'vvm-card-carousel__slides' ) ) {
				continue;
			}

			$slide_blocks = array_values(
				array_filter(
					(array) ( $inner_block['innerBlocks'] ?? array() ),
					static function ( $slide_block ) {
						return is_array( $slide_block ) &&
							isset( $slide_block['blockName'] ) &&
							'gutenberg-lab-blocks/card-carousel-slide' === $slide_block['blockName'];
					}
				)
			);

			$regions['slide_count'] = count( $slide_blocks );

			foreach ( $slide_blocks as $slide_block ) {
				$regions['slides_markup'] .= gutenberg_lab_card_carousel_render_manual_slide( $slide_block );
			}
		}

		return $regions;
	}
}

$content_source = 'villas' === ( $attributes['contentSource'] ?? 'manual' ) ? 'villas' : 'manual';
$villa_count    = max( 1, (int) ( $attributes['villaCount'] ?? 3 ) );
$manual_regions = gutenberg_lab_card_carousel_get_manual_regions( $block ?? null );
$intro_markup   = $manual_regions['intro_markup'];
$slides_markup  = '';
$slide_count    = 0;

if ( 'villas' === $content_source ) {
	$villas_query = new WP_Query(
		array(
			'post_type'           => 'villa',
			'post_status'         => 'publish',
			'posts_per_page'      => $villa_count,
			'ignore_sticky_posts' => true,
			'orderby'             => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
		)
	);

	if ( $villas_query->have_posts() ) {
		while ( $villas_query->have_posts() ) {
			$villas_query->the_post();
			$slides_markup .= gutenberg_lab_blocks_render_villa_carousel_slide( get_the_ID() );
		}

		$slide_count = (int) $villas_query->post_count;
	}

	wp_reset_postdata();
} else {
	$slides_markup = $manual_regions['slides_markup'];
	$slide_count   = (int) $manual_regions['slide_count'];
}

if ( 0 === $slide_count ) {
	if ( ! gutenberg_lab_card_carousel_is_editor_preview() ) {
		return;
	}

	$slides_markup = sprintf(
		'<p class="vvm-card-carousel__empty-state">%s</p>',
		esc_html(
			'villas' === $content_source
				? __( 'Add published villa posts to populate this carousel.', 'gutenberg-lab-blocks' )
				: __( 'Add slide blocks to build this carousel.', 'gutenberg-lab-blocks' )
		)
	);
}

$has_intro      = '' !== trim( wp_strip_all_tags( $intro_markup ) );
$has_overflow_ui = $slide_count > 1;
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode(
			' ',
			array(
				'vvm-card-carousel',
				empty( $attributes['align'] ) ? 'alignfull' : '',
				$has_intro ? 'vvm-card-carousel--has-intro' : 'vvm-card-carousel--no-intro',
				'vvm-card-carousel--source-' . sanitize_html_class( $content_source ),
			)
		),
		'data-card-carousel-root' => '',
	)
);
?>

<section <?php echo $wrapper_attributes; ?>>
	<div class="vvm-card-carousel__shell">
		<?php if ( $has_intro ) : ?>
			<div class="vvm-card-carousel__intro">
				<?php echo $intro_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		<?php endif; ?>

		<div class="vvm-card-carousel__rail">
			<div class="vvm-card-carousel__carousel<?php echo $has_overflow_ui ? ' has-overflow-ui' : ''; ?>" data-card-carousel>
				<?php if ( $has_overflow_ui ) : ?>
					<div class="vvm-card-carousel__controls">
						<button
							type="button"
							class="vvm-card-carousel__button vvm-slider-button vvm-slider-button--overlay vvm-slider-button--prev"
							data-card-carousel-prev
							aria-label="<?php esc_attr_e( 'Previous slides', 'gutenberg-lab-blocks' ); ?>"
						>
							<?php echo gutenberg_lab_blocks_get_slider_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</button>
						<button
							type="button"
							class="vvm-card-carousel__button vvm-slider-button vvm-slider-button--overlay vvm-slider-button--next"
							data-card-carousel-next
							aria-label="<?php esc_attr_e( 'Next slides', 'gutenberg-lab-blocks' ); ?>"
						>
							<?php echo gutenberg_lab_blocks_get_slider_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</button>
					</div>
				<?php endif; ?>

				<div class="vvm-card-carousel__viewport" data-card-carousel-viewport>
					<div class="vvm-card-carousel__track" data-card-carousel-track>
						<?php echo $slides_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
