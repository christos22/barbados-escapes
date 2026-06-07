<?php
/**
 * Shared helpers for the large peeking carousel family of blocks.
 *
 * The paired editorial carousels use the same shell, slide rendering, and
 * progressive enhancement. Keeping that logic here avoids forking the PHP
 * layout every time we need a differently named block registration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'gutenberg_lab_peeking_carousel_is_editor_preview' ) ) {
	/**
	 * Returns whether the current render is happening inside the editor preview.
	 *
	 * @return bool
	 */
	function gutenberg_lab_peeking_carousel_is_editor_preview() {
		return is_admin() ||
			( function_exists( 'wp_is_serving_rest_request' ) && wp_is_serving_rest_request() ) ||
			( defined( 'REST_REQUEST' ) && REST_REQUEST );
	}
}

if ( ! function_exists( 'gutenberg_lab_peeking_carousel_render_nested_blocks' ) ) {
	/**
	 * Renders a parsed block list into markup.
	 *
	 * @param array $inner_blocks Parsed block list.
	 * @return string
	 */
	function gutenberg_lab_peeking_carousel_render_nested_blocks( $inner_blocks ) {
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

if ( ! function_exists( 'gutenberg_lab_peeking_carousel_slide_has_content' ) ) {
	/**
	 * Returns whether a parsed child slide has anything meaningful to render.
	 *
	 * @param array $slide_block Parsed child slide block.
	 * @return bool
	 */
	function gutenberg_lab_peeking_carousel_slide_has_content( $slide_block ) {
		if ( ! is_array( $slide_block ) ) {
			return false;
		}

		$attrs     = is_array( $slide_block['attrs'] ?? null ) ? $slide_block['attrs'] : array();
		$image_url = trim( (string) ( $attrs['imageUrl'] ?? '' ) );

		if ( '' !== $image_url ) {
			return true;
		}

		$content_markup = gutenberg_lab_peeking_carousel_render_nested_blocks( $slide_block['innerBlocks'] ?? array() );

		return '' !== trim( wp_strip_all_tags( $content_markup ) );
	}
}

if ( ! function_exists( 'gutenberg_lab_peeking_carousel_get_slide_blocks' ) ) {
	/**
	 * Returns the meaningful child slide blocks from the current parent block.
	 *
	 * @param WP_Block|null $block Current parent block.
	 * @param string        $child_block_name Supported child block name.
	 * @return array<int, array>
	 */
	function gutenberg_lab_peeking_carousel_get_slide_blocks( $block, $child_block_name ) {
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
				static function ( $inner_block ) use ( $child_block_name ) {
					return is_array( $inner_block ) &&
						$child_block_name === ( $inner_block['blockName'] ?? '' ) &&
						gutenberg_lab_peeking_carousel_slide_has_content( $inner_block );
				}
			)
		);
	}
}

if ( ! function_exists( 'gutenberg_lab_peeking_carousel_render_media_slide' ) ) {
	/**
	 * Renders one parsed slide block into the moving media rail.
	 *
	 * @param array  $slide_block Parsed child slide block.
	 * @param int    $index Slide index.
	 * @param string $slide_data_attribute Data attribute used by the view script.
	 * @return string
	 */
	function gutenberg_lab_peeking_carousel_render_media_slide( $slide_block, $index, $slide_data_attribute ) {
		if ( ! is_array( $slide_block ) ) {
			return '';
		}

		$attrs     = is_array( $slide_block['attrs'] ?? null ) ? $slide_block['attrs'] : array();
		$image_id  = gutenberg_lab_blocks_get_image_id_from_attributes( $attrs );
		$image_url = trim( (string) ( $attrs['imageUrl'] ?? '' ) );
		$image_alt = trim( (string) ( $attrs['imageAlt'] ?? '' ) );
		$has_image = '' !== $image_url;
		$should_prioritize_image = 0 === $index && $has_image && function_exists( 'gutenberg_lab_blocks_claim_frontend_priority_image' )
			? gutenberg_lab_blocks_claim_frontend_priority_image()
			: false;
		$loading   = $should_prioritize_image ? 'eager' : 'lazy';

		ob_start();
		?>
		<li class="splide__slide vvm-feature-carousel__media-slide<?php echo 0 === $index ? ' is-active' : ''; ?>" <?php echo esc_attr( $slide_data_attribute ); ?>>
			<div class="vvm-feature-carousel__media<?php echo $has_image ? '' : ' vvm-feature-carousel__media--placeholder'; ?>">
				<?php if ( $has_image ) : ?>
					<?php
					echo gutenberg_lab_blocks_render_responsive_image(
						array(
							'alt'           => $image_alt,
							'attachment_id' => $image_id,
							'class'         => 'vvm-feature-carousel__image',
							'fallback_url'  => $image_url,
							'fetchpriority' => $should_prioritize_image ? 'high' : '',
							'loading'       => $loading,
							'size'          => 'gutenberg-lab-card-landscape',
							'sizes'         => '(max-width: 782px) 100vw, 72vw',
						)
					); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				<?php else : ?>
					<span class="vvm-feature-carousel__placeholder-label">
						<?php esc_html_e( 'Slide image coming soon', 'gutenberg-lab-blocks' ); ?>
					</span>
				<?php endif; ?>
			</div>
		</li>
		<?php

		return trim( (string) ob_get_clean() );
	}
}

if ( ! function_exists( 'gutenberg_lab_peeking_carousel_render_content_slide' ) ) {
	/**
	 * Renders one parsed slide block into the fixed content panel.
	 *
	 * @param array  $slide_block Parsed child slide block.
	 * @param int    $index Slide index.
	 * @param string $content_data_attribute Data attribute used by the view script.
	 * @return string
	 */
	function gutenberg_lab_peeking_carousel_render_content_slide( $slide_block, $index, $content_data_attribute ) {
		if ( ! is_array( $slide_block ) ) {
			return '';
		}

		$content = gutenberg_lab_peeking_carousel_render_nested_blocks( $slide_block['innerBlocks'] ?? array() );

		ob_start();
		?>
		<div
			class="vvm-feature-carousel__content<?php echo 0 === $index ? ' is-active' : ''; ?>"
			<?php echo esc_attr( $content_data_attribute ); ?>
			aria-hidden="<?php echo esc_attr( 0 === $index ? 'false' : 'true' ); ?>"
		>
			<div class="vvm-feature-carousel__panel-flow">
				<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
		<?php

		return trim( (string) ob_get_clean() );
	}
}

if ( ! function_exists( 'gutenberg_lab_peeking_carousel_get_static_content_slide' ) ) {
	/**
	 * Returns the first slide with authored panel content for static text mode.
	 *
	 * @param array<int, array> $slide_blocks Parsed child slide blocks.
	 * @return array|null
	 */
	function gutenberg_lab_peeking_carousel_get_static_content_slide( $slide_blocks ) {
		foreach ( $slide_blocks as $slide_block ) {
			$content = gutenberg_lab_peeking_carousel_render_nested_blocks( $slide_block['innerBlocks'] ?? array() );

			if ( '' !== trim( wp_strip_all_tags( $content ) ) ) {
				return $slide_block;
			}
		}

		return $slide_blocks[0] ?? null;
	}
}

if ( ! function_exists( 'gutenberg_lab_peeking_carousel_get_panel_style' ) ) {
	/**
	 * Builds CSS custom properties for the fixed text panel.
	 *
	 * These are block-level controls because the panel wrapper is generated by the
	 * dynamic render callback, not by a native editable inner block.
	 *
	 * @param array $attributes Current block attributes.
	 * @return string
	 */
	function gutenberg_lab_peeking_carousel_get_panel_style( $attributes ) {
		$background_options = array(
			'white'      => 'var(--wp--preset--color--white, #fff)',
			'light-gold' => 'var(--wp--preset--color--light-gold, #f5ecd7)',
			'ivory'      => 'var(--wp--preset--color--ivory, #fbf8ef)',
			'dark-green' => 'var(--wp--preset--color--dark-green, #1e3d2f)',
		);
		$panel_styles       = array();
		$panel_background   = (string) ( $attributes['panelBackground'] ?? '' );

		if ( isset( $background_options[ $panel_background ] ) ) {
			$panel_styles[] = '--vvm-feature-carousel-panel-surface:' . $background_options[ $panel_background ];
		}

		foreach (
			array(
				'panelPaddingBlock'  => '--vvm-feature-carousel-panel-padding-block',
				'panelPaddingInline' => '--vvm-feature-carousel-panel-padding-inline',
			) as $attribute_name => $css_property
		) {
			if ( ! isset( $attributes[ $attribute_name ] ) || ! is_numeric( $attributes[ $attribute_name ] ) ) {
				continue;
			}

			$padding_value = (float) $attributes[ $attribute_name ];

			if ( $padding_value < 0 ) {
				continue;
			}

			$panel_styles[] = $css_property . ':' . $padding_value . 'rem';
		}

		return empty( $panel_styles ) ? '' : implode( ';', $panel_styles ) . ';';
	}
}

if ( ! function_exists( 'gutenberg_lab_peeking_carousel_render' ) ) {
	/**
	 * Renders one peeking carousel block instance.
	 *
	 * @param array         $attributes Current block attributes.
	 * @param WP_Block|null $block Current block instance.
	 * @param array         $args Block-specific configuration.
	 * @return string
	 */
	function gutenberg_lab_peeking_carousel_render( $attributes, $block, $args = array() ) {
		$config = wp_parse_args(
			$args,
			array(
				'wrapper_class'                  => 'vvm-feature-carousel',
				'child_block_name'               => 'gutenberg-lab-blocks/feature-carousel-slide',
				'root_data_attribute'            => 'data-feature-carousel-root',
				'carousel_data_attribute'        => 'data-feature-carousel',
				'previous_button_data_attribute' => 'data-feature-carousel-prev',
				'next_button_data_attribute'     => 'data-feature-carousel-next',
				'slide_data_attribute'           => 'data-feature-carousel-slide',
				'content_data_attribute'         => 'data-feature-carousel-content',
				'empty_state_message'            => __( 'Add feature slides to build this carousel.', 'gutenberg-lab-blocks' ),
				'carousel_label'                 => __( 'Feature carousel', 'gutenberg-lab-blocks' ),
				'previous_button_label'          => __( 'Previous feature', 'gutenberg-lab-blocks' ),
				'next_button_label'              => __( 'Next feature', 'gutenberg-lab-blocks' ),
				'id_prefix'                      => 'vvm-feature-carousel-',
			)
		);

		$slide_blocks = gutenberg_lab_peeking_carousel_get_slide_blocks(
			$block,
			$config['child_block_name']
		);

		if ( empty( $slide_blocks ) ) {
			if ( ! gutenberg_lab_peeking_carousel_is_editor_preview() ) {
				return '';
			}

			$empty_wrapper_attributes = get_block_wrapper_attributes(
				array(
					'class' => $config['wrapper_class'] . ' alignfull',
				)
			);

			ob_start();
			?>
			<section <?php echo $empty_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<p class="vvm-feature-carousel__empty-state">
					<?php echo esc_html( $config['empty_state_message'] ); ?>
				</p>
			</section>
			<?php
			return trim( (string) ob_get_clean() );
		}

		$carousel_id         = wp_unique_id( $config['id_prefix'] );
		$media_slides_markup = '';
		$content_markup      = '';
		$transition_style    = 'fade' === ( $attributes['transitionStyle'] ?? '' ) ? 'fade' : 'slide';
		$text_mode           = 'static' === ( $attributes['textMode'] ?? '' ) ? 'static' : 'per-slide';
		$accent_border       = in_array( $attributes['accentBorder'] ?? 'none', array( 'top', 'bottom', 'both' ), true )
			? $attributes['accentBorder']
			: 'none';
		$media_slide_blocks  = $slide_blocks;

		if ( 'static' === $text_mode ) {
			// In static mode, text-only child slides should not become extra
			// placeholder media slides. They can still provide the shared panel.
			$image_slide_blocks = array_values(
				array_filter(
					$slide_blocks,
					static function ( $slide_block ) {
						return '' !== trim( (string) ( $slide_block['attrs']['imageUrl'] ?? '' ) );
					}
				)
			);

			if ( ! empty( $image_slide_blocks ) ) {
				$media_slide_blocks = $image_slide_blocks;
			}
		}

		$has_multiple_slides = count( $media_slide_blocks ) > 1;

		foreach ( $media_slide_blocks as $index => $slide_block ) {
			// The media belongs to the moving rail; the copy belongs to the fixed panel.
			$media_slides_markup .= gutenberg_lab_peeking_carousel_render_media_slide(
				$slide_block,
				$index,
				$config['slide_data_attribute']
			);
			if ( 'per-slide' === $text_mode ) {
				$content_markup .= gutenberg_lab_peeking_carousel_render_content_slide(
					$slide_block,
					$index,
					$config['content_data_attribute']
				);
			}
		}

		if ( 'static' === $text_mode ) {
			// Static text mode lets all child slides contribute images while one
			// authored panel stays visible for the whole carousel.
			$static_content_slide = gutenberg_lab_peeking_carousel_get_static_content_slide( $slide_blocks );

			if ( null !== $static_content_slide ) {
				$content_markup = gutenberg_lab_peeking_carousel_render_content_slide(
					$static_content_slide,
					0,
					$config['content_data_attribute']
				);
			}
		}

		$wrapper_extra_attributes = array(
			'class' => implode(
				' ',
				array_filter(
					array(
						$config['wrapper_class'],
						$config['wrapper_class'] . '--transition-' . sanitize_html_class( $transition_style ),
						$config['wrapper_class'] . '--text-' . sanitize_html_class( $text_mode ),
						// Keep this class parallel to Media Panel's accent border API.
						'none' !== $accent_border ? $config['wrapper_class'] . '--accent-border-' . sanitize_html_class( $accent_border ) : '',
						empty( $attributes['align'] ) ? 'alignfull' : '',
					)
				)
			)
		);
		$panel_style              = gutenberg_lab_peeking_carousel_get_panel_style( $attributes );

		if ( '' !== $panel_style ) {
			$wrapper_extra_attributes['style'] = $panel_style;
		}

		$wrapper_attributes = get_block_wrapper_attributes(
			array_merge(
				$wrapper_extra_attributes,
				array(
					$config['root_data_attribute'] => '',
					'data-carousel-transition'     => $transition_style,
					'data-carousel-text-mode'      => $text_mode,
				)
			)
		);

		ob_start();
		?>
		<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="vvm-feature-carousel__shell vvm-slider-surface">
				<div
					id="<?php echo esc_attr( $carousel_id ); ?>"
					class="vvm-feature-carousel__carousel splide"
					<?php echo esc_attr( $config['carousel_data_attribute'] ); ?>
					aria-label="<?php echo esc_attr( $config['carousel_label'] ); ?>"
				>
					<div class="splide__track">
						<ul class="splide__list">
							<?php echo $media_slides_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</ul>
					</div>
				</div>

				<div class="vvm-feature-carousel__panel" aria-live="polite">
					<?php echo $content_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>

				<?php if ( $has_multiple_slides ) : ?>
					<div
						<?php
						echo gutenberg_lab_blocks_get_slider_controls_attributes(
							$attributes,
							array(
								'class_name'     => 'vvm-feature-carousel__controls',
								'default_preset' => 'bottom-center',
							)
						); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					>
						<button
							type="button"
							class="vvm-feature-carousel__button vvm-slider-button vvm-slider-button--prev"
							<?php echo esc_attr( $config['previous_button_data_attribute'] ); ?>
							aria-controls="<?php echo esc_attr( $carousel_id ); ?>"
							aria-label="<?php echo esc_attr( $config['previous_button_label'] ); ?>"
						>
							<?php echo gutenberg_lab_blocks_get_slider_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</button>

						<button
							type="button"
							class="vvm-feature-carousel__button vvm-slider-button vvm-slider-button--next"
							<?php echo esc_attr( $config['next_button_data_attribute'] ); ?>
							aria-controls="<?php echo esc_attr( $carousel_id ); ?>"
							aria-label="<?php echo esc_attr( $config['next_button_label'] ); ?>"
						>
							<?php echo gutenberg_lab_blocks_get_slider_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</button>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php

		return trim( (string) ob_get_clean() );
	}
}
