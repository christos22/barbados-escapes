<?php
/**
 * Server rendering for the Villa Gallery Hero block.
 *
 * The parent block owns the final synced-slider markup so the hero can stay
 * visually precise on the front end while the editor keeps native nested blocks
 * for media and overlay content authoring.
 *
 * @package GutenbergLabBlocks
 */

if ( ! function_exists( 'gutenberg_lab_blocks_villa_gallery_hero_render_nested_blocks' ) ) {
	/**
	 * Renders one parsed block list into HTML.
	 *
	 * @param array $inner_blocks Parsed block array.
	 * @return string
	 */
	function gutenberg_lab_blocks_villa_gallery_hero_render_nested_blocks( $inner_blocks ) {
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

if ( ! function_exists( 'gutenberg_lab_blocks_villa_gallery_hero_get_current_post_id' ) ) {
	/**
	 * Returns the current singular post ID when the hero sits inside a template.
	 *
	 * @param WP_Block|null $block Current block instance.
	 * @return int
	 */
	function gutenberg_lab_blocks_villa_gallery_hero_get_current_post_id( $block ) {
		if ( $block instanceof WP_Block && isset( $block->context['postId'] ) ) {
			return (int) $block->context['postId'];
		}

		if ( is_singular() ) {
			return (int) get_queried_object_id();
		}

		return 0;
	}
}

if ( ! function_exists( 'gutenberg_lab_blocks_villa_gallery_hero_get_featured_image_slide' ) ) {
	/**
	 * Returns a featured-image-backed fallback slide for the current post.
	 *
	 * @param WP_Block|null $block Current block instance.
	 * @return array<string, mixed>|null
	 */
	function gutenberg_lab_blocks_villa_gallery_hero_get_featured_image_slide( $block ) {
		$current_post_id = gutenberg_lab_blocks_villa_gallery_hero_get_current_post_id( $block );

		if ( ! $current_post_id || ! has_post_thumbnail( $current_post_id ) ) {
			return null;
		}

		$image_id  = (int) get_post_thumbnail_id( $current_post_id );
		$image_url = get_the_post_thumbnail_url( $current_post_id, 'full' );
		$image_alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );

		if ( '' === $image_alt ) {
			$image_alt = get_the_title( $current_post_id );
		}

		if ( ! $image_url ) {
			return null;
		}

		return array(
			'media_type'      => 'image',
			'image_url'       => esc_url_raw( $image_url ),
			'image_alt'       => sanitize_text_field( $image_alt ),
			'video_url'       => '',
			'poster_url'      => '',
			'poster_alt'      => '',
			'thumb_media_url' => esc_url_raw( $image_url ),
			'thumb_media_alt' => sanitize_text_field( $image_alt ),
			'thumb_label'     => get_the_title( $current_post_id )
				? sanitize_text_field( get_the_title( $current_post_id ) )
				: __( 'Featured image', 'gutenberg-lab-blocks' ),
		);
	}
}

if ( ! function_exists( 'gutenberg_lab_blocks_villa_gallery_hero_get_default_content_markup' ) ) {
	/**
	 * Builds a minimal title/excerpt overlay when the nested content is blank.
	 *
	 * The template-level hero defaults to native post title/excerpt blocks, but
	 * content-authored heroes may keep empty placeholders until editors add copy.
	 * This fallback keeps single-villa pages presentable while still allowing the
	 * nested content area to fully override the default output later.
	 *
	 * @param WP_Block|null $block Current block instance.
	 * @return string
	 */
	function gutenberg_lab_blocks_villa_gallery_hero_get_default_content_markup( $block ) {
		$current_post_id = gutenberg_lab_blocks_villa_gallery_hero_get_current_post_id( $block );

		if ( ! $current_post_id ) {
			return '';
		}

		$title   = trim( (string) get_the_title( $current_post_id ) );
		$excerpt = trim( wp_strip_all_tags( (string) get_the_excerpt( $current_post_id ) ) );
		$markup  = '';

		if ( '' !== $title ) {
			$markup .= sprintf(
				'<h1 class="wp-block-post-title">%s</h1>',
				esc_html( $title )
			);
		}

		if ( '' !== $excerpt ) {
			$markup .= sprintf(
				'<div class="wp-block-post-excerpt"><p class="wp-block-post-excerpt__excerpt">%s</p></div>',
				esc_html( $excerpt )
			);
		}

		return $markup;
	}
}

if ( ! function_exists( 'gutenberg_lab_blocks_villa_gallery_hero_normalize_slide' ) ) {
	/**
	 * Normalizes one parsed slide block into the shared hero media shape.
	 *
	 * @param array $slide_block Parsed child slide block.
	 * @param int   $slide_index Zero-based slide index.
	 * @return array<string, mixed>|null
	 */
	function gutenberg_lab_blocks_villa_gallery_hero_normalize_slide( $slide_block, $slide_index ) {
		if ( ! is_array( $slide_block ) ) {
			return null;
		}

		$attrs          = is_array( $slide_block['attrs'] ?? null ) ? $slide_block['attrs'] : array();
		$media_type     = 'video' === ( $attrs['mediaType'] ?? 'image' ) ? 'video' : 'image';
		$image_url      = trim( (string) ( $attrs['imageUrl'] ?? '' ) );
		$image_alt      = trim( (string) ( $attrs['imageAlt'] ?? '' ) );
		$video_url      = trim( (string) ( $attrs['videoUrl'] ?? '' ) );
		$poster_url     = trim( (string) ( $attrs['posterImageUrl'] ?? '' ) );
		$poster_alt     = trim( (string) ( $attrs['posterImageAlt'] ?? '' ) );
		$thumb_label    = trim( (string) ( $attrs['thumbnailLabel'] ?? '' ) );
		$slide_number   = (int) $slide_index + 1;
		$thumb_media_url = '';
		$thumb_media_alt = '';

		if ( 'video' === $media_type ) {
			if ( '' === $video_url ) {
				return null;
			}

			$thumb_media_url = $poster_url;
			$thumb_media_alt = '' !== $poster_alt ? $poster_alt : $thumb_label;
		} else {
			if ( '' === $image_url ) {
				return null;
			}

			$thumb_media_url = $image_url;
			$thumb_media_alt = $image_alt;
		}

		if ( '' === $thumb_label ) {
			$thumb_label = sprintf(
				/* translators: %d slide number. */
				__( 'Slide %d', 'gutenberg-lab-blocks' ),
				$slide_number
			);
		}

		return array(
			'media_type'      => $media_type,
			'image_url'       => esc_url_raw( $image_url ),
			'image_alt'       => sanitize_text_field( $image_alt ),
			'video_url'       => esc_url_raw( $video_url ),
			'poster_url'      => esc_url_raw( $poster_url ),
			'poster_alt'      => sanitize_text_field( $poster_alt ),
			'thumb_media_url' => esc_url_raw( $thumb_media_url ),
			'thumb_media_alt' => sanitize_text_field( $thumb_media_alt ),
			'thumb_label'     => sanitize_text_field( $thumb_label ),
		);
	}
}

if ( ! function_exists( 'gutenberg_lab_blocks_villa_gallery_hero_block_has_valid_slides' ) ) {
	/**
	 * Determines whether a parsed hero block contains at least one valid slide.
	 *
	 * @param array $hero_block Parsed hero block.
	 * @return bool
	 */
	function gutenberg_lab_blocks_villa_gallery_hero_block_has_valid_slides( $hero_block ) {
		if ( ! is_array( $hero_block ) ) {
			return false;
		}

		$hero_children = is_array( $hero_block['innerBlocks'] ?? null ) ? $hero_block['innerBlocks'] : array();

		foreach ( $hero_children as $hero_child ) {
			if (
				! is_array( $hero_child ) ||
				'gutenberg-lab-blocks/villa-gallery-hero-media' !== ( $hero_child['blockName'] ?? '' )
			) {
				continue;
			}

			$slide_blocks = is_array( $hero_child['innerBlocks'] ?? null ) ? $hero_child['innerBlocks'] : array();

			foreach ( $slide_blocks as $slide_index => $slide_block ) {
				if (
					is_array( $slide_block ) &&
					'gutenberg-lab-blocks/villa-gallery-hero-slide' === ( $slide_block['blockName'] ?? '' ) &&
					gutenberg_lab_blocks_villa_gallery_hero_normalize_slide( $slide_block, $slide_index )
				) {
					return true;
				}
			}
		}

		return false;
	}
}

if ( ! function_exists( 'gutenberg_lab_blocks_villa_gallery_hero_post_content_has_authored_gallery' ) ) {
	/**
	 * Checks whether the current post content already contains a real gallery hero.
	 *
	 * This lets the template-level featured-image fallback quietly step aside when
	 * editors have authored a richer villa hero directly in post content.
	 *
	 * @param WP_Block|null $block Current block instance.
	 * @return bool
	 */
	function gutenberg_lab_blocks_villa_gallery_hero_post_content_has_authored_gallery( $block ) {
		$current_post_id = gutenberg_lab_blocks_villa_gallery_hero_get_current_post_id( $block );

		if ( ! $current_post_id ) {
			return false;
		}

		$post_content = get_post_field( 'post_content', $current_post_id );

		if ( ! is_string( $post_content ) || '' === trim( $post_content ) || ! has_blocks( $post_content ) ) {
			return false;
		}

		$parsed_blocks = parse_blocks( $post_content );

		foreach ( $parsed_blocks as $parsed_block ) {
			if (
				is_array( $parsed_block ) &&
				'gutenberg-lab-blocks/villa-gallery-hero' === ( $parsed_block['blockName'] ?? '' ) &&
				gutenberg_lab_blocks_villa_gallery_hero_block_has_valid_slides( $parsed_block )
			) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'gutenberg_lab_blocks_villa_gallery_hero_get_regions' ) ) {
	/**
	 * Extracts the parsed media slides and overlay content from the parent tree.
	 *
	 * @param WP_Block|null $block Current block instance.
	 * @return array<string, mixed>
	 */
	function gutenberg_lab_blocks_villa_gallery_hero_get_regions( $block ) {
		$regions = array(
			'content_markup' => '',
			'slides'         => array(),
		);

		if ( ! $block instanceof WP_Block ) {
			return $regions;
		}

		$parsed_inner_blocks = $block->parsed_block['innerBlocks'] ?? array();
		$parsed_inner_blocks = is_array( $parsed_inner_blocks ) ? $parsed_inner_blocks : array();

		foreach ( $parsed_inner_blocks as $inner_block ) {
			if ( ! is_array( $inner_block ) ) {
				continue;
			}

			$block_name = $inner_block['blockName'] ?? '';

			if ( 'gutenberg-lab-blocks/villa-gallery-hero-content' === $block_name ) {
				$regions['content_markup'] = gutenberg_lab_blocks_villa_gallery_hero_render_nested_blocks(
					$inner_block['innerBlocks'] ?? array()
				);
				continue;
			}

			if ( 'gutenberg-lab-blocks/villa-gallery-hero-media' !== $block_name ) {
				continue;
			}

			$slide_blocks = array_values(
				array_filter(
					(array) ( $inner_block['innerBlocks'] ?? array() ),
					static function ( $slide_block ) {
						return is_array( $slide_block ) &&
							isset( $slide_block['blockName'] ) &&
							'gutenberg-lab-blocks/villa-gallery-hero-slide' === $slide_block['blockName'];
					}
				)
			);

			foreach ( $slide_blocks as $slide_index => $slide_block ) {
				$normalized_slide = gutenberg_lab_blocks_villa_gallery_hero_normalize_slide(
					$slide_block,
					$slide_index
				);

				if ( ! $normalized_slide ) {
					continue;
				}

				$regions['slides'][] = $normalized_slide;
			}
		}

		return $regions;
	}
}

$hero_height   = in_array( $attributes['heroHeight'] ?? 'full', array( 'large', 'full' ), true )
	? $attributes['heroHeight']
	: 'full';
$overlay_style = in_array( $attributes['overlayStyle'] ?? 'brand-green', array( 'brand-green', 'dark-vignette' ), true )
	? $attributes['overlayStyle']
	: 'brand-green';
$show_arrows   = ! empty( $attributes['showArrows'] );
$hero_regions  = gutenberg_lab_blocks_villa_gallery_hero_get_regions( $block ?? null );
$slides        = $hero_regions['slides'];

if (
	empty( $slides ) &&
	gutenberg_lab_blocks_villa_gallery_hero_post_content_has_authored_gallery( $block ?? null )
) {
	return '';
}

if ( empty( $slides ) ) {
	$featured_fallback = gutenberg_lab_blocks_villa_gallery_hero_get_featured_image_slide( $block ?? null );

	if ( $featured_fallback ) {
		$slides[] = $featured_fallback;
	}
}

$content_markup      = $hero_regions['content_markup'];

if ( '' === trim( wp_strip_all_tags( $content_markup ) ) ) {
	$content_markup = gutenberg_lab_blocks_villa_gallery_hero_get_default_content_markup( $block ?? null );
}

$slide_count         = count( $slides );
$has_thumb_navigation = $slide_count > 1;
$hero_id_base        = ! empty( $attributes['anchor'] )
	? sanitize_html_class( $attributes['anchor'] )
	: sanitize_html_class( wp_unique_id( 'villa-gallery-hero-' ) );
$classes             = array(
	'vvm-villa-gallery-hero',
	'vvm-villa-gallery-hero--height-' . sanitize_html_class( $hero_height ),
	'vvm-villa-gallery-hero--overlay-' . sanitize_html_class( $overlay_style ),
	empty( $attributes['align'] ) ? 'alignfull' : '',
	$has_thumb_navigation ? 'vvm-villa-gallery-hero--has-navigation' : 'vvm-villa-gallery-hero--static',
);
$wrapper_attributes  = get_block_wrapper_attributes(
	array(
		'class'                  => implode( ' ', array_filter( $classes ) ),
		'data-villa-gallery-hero' => '',
		'data-villa-gallery-id'  => $hero_id_base,
	)
);
?>

<section <?php echo $wrapper_attributes; ?>>
	<div class="vvm-villa-gallery-hero__stage-shell">
		<?php if ( $has_thumb_navigation ) : ?>
			<div
				class="vvm-villa-gallery-hero__stage splide"
				data-villa-gallery-stage
				aria-label="<?php esc_attr_e( 'Villa gallery hero', 'gutenberg-lab-blocks' ); ?>"
			>
				<div class="splide__track">
					<ul class="splide__list">
						<?php foreach ( $slides as $slide ) : ?>
							<li class="splide__slide">
								<figure class="vvm-villa-gallery-hero__stage-slide">
									<?php if ( 'video' === $slide['media_type'] ) : ?>
										<video
											class="vvm-villa-gallery-hero__stage-media vvm-villa-gallery-hero__stage-media--video"
											src="<?php echo esc_url( $slide['video_url'] ); ?>"
											<?php if ( '' !== $slide['poster_url'] ) : ?>
												poster="<?php echo esc_url( $slide['poster_url'] ); ?>"
											<?php endif; ?>
											muted
											loop
											playsinline
											preload="metadata"
											data-villa-gallery-video
										></video>
										<button
											type="button"
											class="vvm-villa-gallery-hero__video-toggle"
											data-villa-gallery-video-toggle
											hidden
											aria-label="<?php esc_attr_e( 'Play video', 'gutenberg-lab-blocks' ); ?>"
										>
											<span
												class="vvm-villa-gallery-hero__video-toggle-icon"
												aria-hidden="true"
											>▶</span>
											<span class="screen-reader-text">
												<?php esc_html_e( 'Play video', 'gutenberg-lab-blocks' ); ?>
											</span>
										</button>
									<?php else : ?>
										<img
											class="vvm-villa-gallery-hero__stage-media vvm-villa-gallery-hero__stage-media--image"
											src="<?php echo esc_url( $slide['image_url'] ); ?>"
											alt="<?php echo esc_attr( $slide['image_alt'] ); ?>"
										/>
									<?php endif; ?>
								</figure>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
			<?php if ( $show_arrows ) : ?>
				<div class="vvm-villa-gallery-hero__controls">
					<button
						type="button"
						class="vvm-villa-gallery-hero__button vvm-slider-button vvm-slider-button--overlay vvm-slider-button--prev"
						data-villa-gallery-prev
						aria-label="<?php esc_attr_e( 'Previous slide', 'gutenberg-lab-blocks' ); ?>"
					>
						<?php echo gutenberg_lab_blocks_get_slider_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
					<button
						type="button"
						class="vvm-villa-gallery-hero__button vvm-slider-button vvm-slider-button--overlay vvm-slider-button--next"
						data-villa-gallery-next
						aria-label="<?php esc_attr_e( 'Next slide', 'gutenberg-lab-blocks' ); ?>"
					>
						<?php echo gutenberg_lab_blocks_get_slider_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
				</div>
			<?php endif; ?>
		<?php else : ?>
			<div class="vvm-villa-gallery-hero__stage-static">
				<?php if ( ! empty( $slides ) ) : ?>
					<?php $slide = $slides[0]; ?>
					<div class="vvm-villa-gallery-hero__stage-static-media">
						<?php if ( 'video' === $slide['media_type'] ) : ?>
							<video
								class="vvm-villa-gallery-hero__stage-media vvm-villa-gallery-hero__stage-media--video"
								src="<?php echo esc_url( $slide['video_url'] ); ?>"
								<?php if ( '' !== $slide['poster_url'] ) : ?>
									poster="<?php echo esc_url( $slide['poster_url'] ); ?>"
								<?php endif; ?>
								muted
								loop
								playsinline
								preload="metadata"
								data-villa-gallery-static-video
							></video>
							<button
								type="button"
								class="vvm-villa-gallery-hero__video-toggle"
								data-villa-gallery-video-toggle
								hidden
								aria-label="<?php esc_attr_e( 'Play video', 'gutenberg-lab-blocks' ); ?>"
							>
								<span
									class="vvm-villa-gallery-hero__video-toggle-icon"
									aria-hidden="true"
								>▶</span>
								<span class="screen-reader-text">
									<?php esc_html_e( 'Play video', 'gutenberg-lab-blocks' ); ?>
								</span>
							</button>
						<?php else : ?>
							<img
								class="vvm-villa-gallery-hero__stage-media vvm-villa-gallery-hero__stage-media--image"
								src="<?php echo esc_url( $slide['image_url'] ); ?>"
								alt="<?php echo esc_attr( $slide['image_alt'] ); ?>"
							/>
						<?php endif; ?>
					</div>
				<?php else : ?>
					<div class="vvm-villa-gallery-hero__stage-static-placeholder">
						<?php esc_html_e( 'Add hero slides or set a featured image to complete this villa hero.', 'gutenberg-lab-blocks' ); ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="vvm-villa-gallery-hero__content">
			<div class="vvm-villa-gallery-hero__content-inner">
				<?php echo $content_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
	</div>

		<?php if ( $has_thumb_navigation ) : ?>
			<div class="vvm-villa-gallery-hero__thumbs-shell">
				<button
					type="button"
					class="vvm-villa-gallery-hero__thumb-rail-button vvm-villa-gallery-hero__thumb-rail-button--prev vvm-slider-button--prev"
					data-villa-gallery-thumbs-prev
					aria-label="<?php esc_attr_e( 'Scroll gallery thumbnails backward', 'gutenberg-lab-blocks' ); ?>"
					hidden
				>
					<?php echo gutenberg_lab_blocks_get_slider_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>

				<div
					class="vvm-villa-gallery-hero__thumbs splide is-rendered"
					data-villa-gallery-thumbs
					aria-label="<?php esc_attr_e( 'Villa gallery navigation', 'gutenberg-lab-blocks' ); ?>"
				>
					<div class="splide__track">
						<ul class="splide__list">
							<?php foreach ( $slides as $slide ) : ?>
								<li class="splide__slide">
									<div class="vvm-villa-gallery-hero__thumb-card">
										<div class="vvm-villa-gallery-hero__thumb-media">
											<?php if ( '' !== $slide['thumb_media_url'] ) : ?>
												<img
													src="<?php echo esc_url( $slide['thumb_media_url'] ); ?>"
													alt="<?php echo esc_attr( $slide['thumb_media_alt'] ); ?>"
												/>
											<?php else : ?>
												<div class="vvm-villa-gallery-hero__thumb-media-placeholder"></div>
											<?php endif; ?>

											<?php if ( 'video' === $slide['media_type'] ) : ?>
												<span class="vvm-villa-gallery-hero__thumb-play-badge" aria-hidden="true">▶</span>
											<?php endif; ?>
										</div>

										<div class="vvm-villa-gallery-hero__thumb-copy">
											<p class="vvm-villa-gallery-hero__thumb-label">
												<?php echo esc_html( $slide['thumb_label'] ); ?>
											</p>
										</div>
									</div>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				</div>

				<button
					type="button"
					class="vvm-villa-gallery-hero__thumb-rail-button vvm-villa-gallery-hero__thumb-rail-button--next vvm-slider-button--next"
					data-villa-gallery-thumbs-next
					aria-label="<?php esc_attr_e( 'Scroll gallery thumbnails forward', 'gutenberg-lab-blocks' ); ?>"
					hidden
				>
					<?php echo gutenberg_lab_blocks_get_slider_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
			</div>
		<?php endif; ?>
	</section>
