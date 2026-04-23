<?php
/**
 * Server rendering for the Two-Up Carousel block.
 *
 * Unlike the editorial Feature Carousel, this block owns a card-based rail that
 * shows two slides at a time with neighboring cards peeking into view.
 */

if ( ! function_exists( 'gutenberg_lab_two_up_carousel_render_slide' ) ) {
	/**
	 * Renders one authored slide into the front-end two-up card shell.
	 *
	 * @param array $slide_block Parsed child block.
	 * @param array $args Render arguments.
	 * @return string
	 */
	function gutenberg_lab_two_up_carousel_render_slide( $slide_block, $args = array() ) {
		if ( ! is_array( $slide_block ) ) {
			return '';
		}

		$args = wp_parse_args(
			$args,
			array(
				'is_active'   => false,
				'is_clone'    => false,
				'is_priority' => false,
			)
		);

		$image_url = trim( (string) ( $slide_block['attrs']['imageUrl'] ?? '' ) );
		$image_alt = trim( (string) ( $slide_block['attrs']['imageAlt'] ?? '' ) );
		$has_image = '' !== $image_url;
		$loading   = $args['is_active'] ? 'eager' : 'lazy';
		$content   = gutenberg_lab_peeking_carousel_render_nested_blocks( $slide_block['innerBlocks'] ?? array() );
		$has_copy  = '' !== trim( wp_strip_all_tags( $content ) );
		$classes   = array( 'vvm-two-up-carousel__slide' );

		if ( $args['is_active'] ) {
			$classes[] = 'is-active';
		}

		if ( $args['is_clone'] ) {
			$classes[] = 'is-clone';
		}

		ob_start();
		?>
		<article
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			data-two-up-carousel-slide
			<?php if ( $args['is_clone'] ) : ?>
				aria-hidden="true"
				inert
			<?php endif; ?>
		>
			<div class="vvm-two-up-carousel__slide-media<?php echo $has_image ? '' : ' vvm-two-up-carousel__slide-media--placeholder'; ?>">
				<?php if ( $has_image ) : ?>
					<img
						class="vvm-two-up-carousel__slide-image"
						src="<?php echo esc_url( $image_url ); ?>"
						alt="<?php echo esc_attr( $image_alt ); ?>"
						loading="<?php echo esc_attr( $loading ); ?>"
						decoding="async"
						fetchpriority="<?php echo esc_attr( $args['is_priority'] ? 'high' : 'auto' ); ?>"
					/>
				<?php else : ?>
					<span class="vvm-two-up-carousel__slide-placeholder-label">
						<?php esc_html_e( 'Slide image coming soon', 'gutenberg-lab-blocks' ); ?>
					</span>
				<?php endif; ?>
			</div>

			<?php if ( $has_copy ) : ?>
				<div class="vvm-two-up-carousel__slide-content">
					<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>
		</article>
		<?php

		return trim( (string) ob_get_clean() );
	}
}

$slide_blocks = gutenberg_lab_peeking_carousel_get_slide_blocks(
	$block ?? null,
	'gutenberg-lab-blocks/two-up-carousel-slide'
);

if ( empty( $slide_blocks ) ) {
	if ( ! gutenberg_lab_peeking_carousel_is_editor_preview() ) {
		return;
	}

	$empty_wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => implode(
				' ',
				array_filter(
					array(
						'vvm-two-up-carousel',
						empty( $attributes['align'] ) ? 'alignfull' : '',
					)
				)
			),
		)
	);
	?>
	<section <?php echo $empty_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<p class="vvm-two-up-carousel__empty-state">
			<?php esc_html_e( 'Add slides to build this two-up carousel.', 'gutenberg-lab-blocks' ); ?>
		</p>
	</section>
	<?php
	return;
}

// Prime the first pair so the no-JS state still reads like a two-up rail.
$real_slide_count    = count( $slide_blocks );
$active_count        = $real_slide_count > 1 ? 2 : 1;
$use_loop_clones     = $real_slide_count > $active_count;
$has_multiple_slides = $real_slide_count > 1;
$render_queue        = array();
$slides_markup       = '';

if ( $use_loop_clones ) {
	$render_queue[] = array(
		'slide' => $slide_blocks[ $real_slide_count - 1 ],
		'args'  => array(
			'is_clone' => true,
		),
	);
}

foreach ( $slide_blocks as $index => $slide_block ) {
	$render_queue[] = array(
		'slide' => $slide_block,
		'args'  => array(
			'is_active'   => $index < $active_count,
			'is_priority' => $index < $active_count,
		),
	);
}

if ( $use_loop_clones ) {
	$render_queue[] = array(
		'slide' => $slide_blocks[0],
		'args'  => array(
			'is_clone' => true,
		),
	);
}

foreach ( $render_queue as $render_item ) {
	$slides_markup .= gutenberg_lab_two_up_carousel_render_slide(
		$render_item['slide'],
		$render_item['args']
	);
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode(
			' ',
			array_filter(
				array(
					'vvm-two-up-carousel',
					empty( $attributes['align'] ) ? 'alignfull' : '',
				)
			)
		),
		'data-two-up-carousel-root' => '',
	)
);
?>

<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="vvm-two-up-carousel__shell vvm-slider-surface<?php echo $has_multiple_slides ? ' vvm-two-up-carousel__shell--has-controls' : ''; ?>">
		<div
			class="vvm-two-up-carousel__carousel<?php echo $has_multiple_slides ? ' has-overflow' : ''; ?>"
			data-two-up-carousel
			aria-label="<?php esc_attr_e( 'Two-Up Carousel', 'gutenberg-lab-blocks' ); ?>"
		>
			<div class="vvm-two-up-carousel__viewport" data-two-up-carousel-viewport>
				<div class="vvm-two-up-carousel__track" data-two-up-carousel-track>
					<?php echo $slides_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</div>

		<?php if ( $has_multiple_slides ) : ?>
			<div
				<?php
				echo gutenberg_lab_blocks_get_slider_controls_attributes(
					$attributes,
					array(
						'class_name'     => 'vvm-two-up-carousel__controls',
						'default_preset' => 'bottom-center',
					)
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			>
				<button
					type="button"
					class="vvm-two-up-carousel__button vvm-slider-button vvm-slider-button--prev"
					data-two-up-carousel-prev
					aria-label="<?php esc_attr_e( 'Previous slides', 'gutenberg-lab-blocks' ); ?>"
				>
					<?php echo gutenberg_lab_blocks_get_slider_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
				<button
					type="button"
					class="vvm-two-up-carousel__button vvm-slider-button vvm-slider-button--next"
					data-two-up-carousel-next
					aria-label="<?php esc_attr_e( 'Next slides', 'gutenberg-lab-blocks' ); ?>"
				>
					<?php echo gutenberg_lab_blocks_get_slider_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
			</div>
		<?php endif; ?>
	</div>
</section>
