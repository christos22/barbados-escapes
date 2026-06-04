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
			'video_source'    => 'uploaded',
			'image_id'        => $image_id,
			'image_url'       => esc_url_raw( $image_url ),
			'image_alt'       => sanitize_text_field( $image_alt ),
			'video_url'       => '',
			'vimeo_url'       => '',
			'vimeo_id'        => '',
			'poster_id'       => 0,
			'poster_url'      => '',
			'poster_alt'      => '',
			'thumb_media_id'  => $image_id,
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

if ( ! function_exists( 'gutenberg_lab_blocks_villa_gallery_hero_normalize_cta_text' ) ) {
	/**
	 * Normalizes rendered button text before matching the gallery CTA label.
	 *
	 * @param string $text Raw link text.
	 * @return string
	 */
	function gutenberg_lab_blocks_villa_gallery_hero_normalize_cta_text( $text ) {
		$charset = get_bloginfo( 'charset' ) ? get_bloginfo( 'charset' ) : 'UTF-8';

		return trim(
			preg_replace(
				'/\s+/',
				' ',
				html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES, $charset )
			)
		);
	}
}

if ( ! function_exists( 'gutenberg_lab_blocks_villa_gallery_hero_is_gallery_cta_text' ) ) {
	/**
	 * Determines whether a rendered link is the hero gallery CTA.
	 *
	 * Editors sometimes add a decorative arrow to the native button label, so
	 * match the CTA intent by its leading words instead of an exact string.
	 *
	 * @param string $text Normalized link text.
	 * @return bool
	 */
	function gutenberg_lab_blocks_villa_gallery_hero_is_gallery_cta_text( $text ) {
		return 0 === stripos( $text, 'Explore Gallery' );
	}
}

if ( ! function_exists( 'gutenberg_lab_blocks_villa_gallery_hero_link_gallery_cta' ) ) {
	/**
	 * Points authored "Explore Gallery" buttons to the hero thumbnail rail.
	 *
	 * Editors keep using native core buttons. The dynamic parent block adds the
	 * frontend href so every villa hero follows the same gallery-scroll contract.
	 *
	 * @param string $content_markup Rendered hero content HTML.
	 * @param string $target_id Gallery rail anchor ID.
	 * @return string
	 */
	function gutenberg_lab_blocks_villa_gallery_hero_link_gallery_cta( $content_markup, $target_id ) {
		if (
			'' === trim( $content_markup ) ||
			'' === trim( $target_id ) ||
			false === stripos( $content_markup, 'Explore Gallery' )
		) {
			return $content_markup;
		}

		$target_href = '#' . sanitize_html_class( $target_id );

		if ( class_exists( 'DOMDocument' ) ) {
			$document = new DOMDocument();
			$previous = libxml_use_internal_errors( true );
			$charset  = get_bloginfo( 'charset' ) ? get_bloginfo( 'charset' ) : 'UTF-8';

			$document->loadHTML(
				'<?xml encoding="' . esc_attr( $charset ) . '">' .
				'<!doctype html><html><body><div id="vvm-gallery-hero-content-root">' .
				$content_markup .
				'</div></body></html>'
			);
			libxml_clear_errors();
			libxml_use_internal_errors( $previous );

			$root = $document->getElementById( 'vvm-gallery-hero-content-root' );

			if ( $root ) {
				foreach ( $root->getElementsByTagName( 'a' ) as $link ) {
					$link_text = gutenberg_lab_blocks_villa_gallery_hero_normalize_cta_text( $link->textContent );

					if ( ! gutenberg_lab_blocks_villa_gallery_hero_is_gallery_cta_text( $link_text ) ) {
						continue;
					}

					$link->setAttribute( 'href', $target_href );
					$link->setAttribute( 'data-villa-gallery-hero-cta', 'gallery' );
				}

				$updated_markup = '';

				foreach ( $root->childNodes as $child_node ) {
					$updated_markup .= $document->saveHTML( $child_node );
				}

				return $updated_markup;
			}
		}

		return preg_replace_callback(
			'/<a\b([^>]*)>(\s*Explore Gallery[^<]*)<\/a>/i',
			static function ( $matches ) use ( $target_href ) {
				$attributes = preg_replace(
					'/\s(?:href|data-villa-gallery-hero-cta)=(["\']).*?\1/i',
					'',
					$matches[1]
				);

				return sprintf(
					'<a%s href="%s" data-villa-gallery-hero-cta="gallery">%s</a>',
					$attributes,
					esc_attr( $target_href ),
					$matches[2]
				);
			},
			$content_markup,
			1
		);
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
		$image_id       = gutenberg_lab_blocks_get_image_id_from_attributes( $attrs );
		$image_url      = trim( (string) ( $attrs['imageUrl'] ?? '' ) );
		$image_alt      = trim( (string) ( $attrs['imageAlt'] ?? '' ) );
		$thumb_label    = trim( (string) ( $attrs['thumbnailLabel'] ?? '' ) );
		$slide_number   = (int) $slide_index + 1;
		$thumb_media_id = 0;
		$thumb_media_url = '';
		$thumb_media_alt = '';
		$video_data     = gutenberg_lab_blocks_get_video_data( $attrs );

		if ( 'video' === $media_type ) {
			if ( ! $video_data['is_complete'] ) {
				return null;
			}

			$thumb_media_url = $video_data['poster_url'];
			$thumb_media_alt = '' !== $video_data['poster_alt'] ? $video_data['poster_alt'] : $thumb_label;
			$thumb_media_id  = (int) $video_data['poster_id'];
		} else {
			if ( '' === $image_url ) {
				return null;
			}

			$thumb_media_url = $image_url;
			$thumb_media_alt = $image_alt;
			$thumb_media_id  = $image_id;
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
			'video_source'    => $video_data['source'],
			'image_id'        => $image_id,
			'image_url'       => esc_url_raw( $image_url ),
			'image_alt'       => sanitize_text_field( $image_alt ),
			'video_url'       => $video_data['uploaded_video_url'],
			'vimeo_url'       => $video_data['vimeo_url'],
			'vimeo_id'        => $video_data['vimeo_id'],
			'vimeo_hash'      => $video_data['vimeo_hash'] ?? '',
			'poster_id'       => (int) $video_data['poster_id'],
			'poster_url'      => $video_data['poster_url'],
			'poster_alt'      => $video_data['poster_alt'],
			'thumb_media_id'  => $thumb_media_id,
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

$slide_count         = count( $slides );
$has_thumb_navigation = $slide_count > 1;
$hero_id_base        = ! empty( $attributes['anchor'] )
	? sanitize_html_class( $attributes['anchor'] )
	: sanitize_html_class( wp_unique_id( 'villa-gallery-hero-' ) );
$gallery_anchor_id   = $has_thumb_navigation ? $hero_id_base . '-gallery' : '';
$content_markup      = $hero_regions['content_markup'];

if ( '' === trim( wp_strip_all_tags( $content_markup ) ) ) {
	$content_markup = gutenberg_lab_blocks_villa_gallery_hero_get_default_content_markup( $block ?? null );
}

$content_markup      = gutenberg_lab_blocks_villa_gallery_hero_link_gallery_cta( $content_markup, $gallery_anchor_id );
$classes             = array(
	'vvm-villa-gallery-hero',
	'vvm-villa-gallery-hero--height-' . sanitize_html_class( $hero_height ),
	'vvm-villa-gallery-hero--overlay-' . sanitize_html_class( $overlay_style ),
	empty( $attributes['align'] ) ? 'alignfull' : '',
	$has_thumb_navigation ? 'vvm-villa-gallery-hero--has-navigation' : 'vvm-villa-gallery-hero--static',
);
$wrapper_args        = array(
	'class'                   => implode( ' ', array_filter( $classes ) ),
	'data-villa-gallery-hero' => '',
	'data-villa-gallery-id'   => $hero_id_base,
);

$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
?>

<section <?php echo $wrapper_attributes; ?>>
	<div class="vvm-villa-gallery-hero__stage-shell vvm-slider-surface">
		<?php if ( $has_thumb_navigation ) : ?>
			<div
				class="vvm-villa-gallery-hero__stage splide"
				data-villa-gallery-stage
				aria-label="<?php esc_attr_e( 'Villa gallery hero', 'gutenberg-lab-blocks' ); ?>"
			>
				<div class="splide__track">
					<ul class="splide__list">
						<?php foreach ( $slides as $slide_index => $slide ) : ?>
							<li class="splide__slide">
								<figure class="vvm-villa-gallery-hero__stage-slide">
									<?php if ( 'video' === $slide['media_type'] ) : ?>
										<?php if ( 'vimeo' === $slide['video_source'] ) : ?>
											<?php
												echo gutenberg_lab_blocks_render_vimeo_shell(
													array(
														'autoplay_url'     => gutenberg_lab_blocks_get_vimeo_embed_url(
															$slide['vimeo_id'],
															'autoplay',
															$slide['vimeo_hash'] ?? ''
														),
														'manual_url'       => gutenberg_lab_blocks_get_vimeo_embed_url(
															$slide['vimeo_id'],
															'manual',
															$slide['vimeo_hash'] ?? ''
														),
														'iframe_class'     => 'vvm-villa-gallery-hero__stage-media vvm-villa-gallery-hero__stage-media--video',
														'lazy_load'        => true,
														'poster_alt'       => $slide['poster_alt'],
														'poster_attrs'     => array_filter(
															array(
																'decoding'      => 'async',
																'fetchpriority' => 0 === $slide_index ? 'high' : '',
																'loading'       => 0 === $slide_index ? 'eager' : 'lazy',
															)
														),
														'poster_class'     => 'vvm-villa-gallery-hero__stage-media vvm-villa-gallery-hero__stage-media--image',
														'poster_id'        => $slide['poster_id'],
														'poster_size'      => 'gutenberg-lab-hero',
														'poster_sizes'     => '100vw',
														'poster_url'       => $slide['poster_url'],
														'shell_class'      => 'vvm-villa-gallery-hero__stage-media-shell',
														'title'            => __( 'Villa gallery Vimeo video', 'gutenberg-lab-blocks' ),
														'wrapper_attrs'    => array(
															'data-villa-gallery-vimeo' => '',
														),
													)
												); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											?>
										<?php else : ?>
											<video
												class="vvm-villa-gallery-hero__stage-media vvm-villa-gallery-hero__stage-media--video"
												src="<?php echo esc_url( $slide['video_url'] ); ?>"
												<?php if ( '' !== $slide['poster_url'] ) : ?>
													poster="<?php echo esc_url( $slide['poster_url'] ); ?>"
												<?php endif; ?>
												autoplay
												muted
												loop
												playsinline
												preload="auto"
												data-villa-gallery-video
											></video>
											<?php
											echo gutenberg_lab_blocks_get_native_video_control_button(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											?>
										<?php endif; ?>
										<?php else : ?>
										<?php
										echo gutenberg_lab_blocks_render_responsive_image(
											array(
												'alt'           => $slide['image_alt'],
												'attachment_id' => $slide['image_id'],
												'class'         => 'vvm-villa-gallery-hero__stage-media vvm-villa-gallery-hero__stage-media--image',
												'fallback_url'  => $slide['image_url'],
												'fetchpriority' => 0 === $slide_index ? 'high' : '',
												'loading'       => 0 === $slide_index ? 'eager' : 'lazy',
												'size'          => 'gutenberg-lab-hero',
												'sizes'         => '100vw',
											)
										); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										?>
									<?php endif; ?>
								</figure>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
			<?php if ( $show_arrows ) : ?>
				<div
					<?php
					echo gutenberg_lab_blocks_get_slider_controls_attributes(
						$attributes,
						array(
							'class_name' => 'vvm-villa-gallery-hero__controls',
						)
					); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				>
					<button
						type="button"
						class="vvm-villa-gallery-hero__button vvm-slider-button vvm-slider-button--prev"
						data-villa-gallery-prev
						aria-label="<?php esc_attr_e( 'Previous slide', 'gutenberg-lab-blocks' ); ?>"
					>
						<?php echo gutenberg_lab_blocks_get_slider_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
					<button
						type="button"
						class="vvm-villa-gallery-hero__button vvm-slider-button vvm-slider-button--next"
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
							<?php if ( 'vimeo' === $slide['video_source'] ) : ?>
								<?php
									echo gutenberg_lab_blocks_render_vimeo_shell(
										array(
											'autoplay_url'     => gutenberg_lab_blocks_get_vimeo_embed_url(
												$slide['vimeo_id'],
												'autoplay',
												$slide['vimeo_hash'] ?? ''
											),
											'manual_url'       => gutenberg_lab_blocks_get_vimeo_embed_url(
												$slide['vimeo_id'],
												'manual',
												$slide['vimeo_hash'] ?? ''
											),
											'iframe_class'     => 'vvm-villa-gallery-hero__stage-media vvm-villa-gallery-hero__stage-media--video',
											'lazy_load'        => true,
											'poster_alt'       => $slide['poster_alt'],
											'poster_attrs'     => array(
												'decoding'      => 'async',
												'fetchpriority' => 'high',
												'loading'       => 'eager',
											),
											'poster_class'     => 'vvm-villa-gallery-hero__stage-media vvm-villa-gallery-hero__stage-media--image',
											'poster_id'        => $slide['poster_id'],
											'poster_size'      => 'gutenberg-lab-hero',
											'poster_sizes'     => '100vw',
											'poster_url'       => $slide['poster_url'],
											'shell_class'      => 'vvm-villa-gallery-hero__stage-media-shell',
											'title'            => __( 'Villa gallery Vimeo video', 'gutenberg-lab-blocks' ),
											'wrapper_attrs'    => array(
												'data-villa-gallery-vimeo' => '',
											),
										)
									); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								?>
							<?php else : ?>
								<video
									class="vvm-villa-gallery-hero__stage-media vvm-villa-gallery-hero__stage-media--video"
									src="<?php echo esc_url( $slide['video_url'] ); ?>"
									<?php if ( '' !== $slide['poster_url'] ) : ?>
										poster="<?php echo esc_url( $slide['poster_url'] ); ?>"
									<?php endif; ?>
									autoplay
									muted
									loop
									playsinline
									preload="auto"
									data-villa-gallery-static-video
								></video>
								<?php
								echo gutenberg_lab_blocks_get_native_video_control_button(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								?>
							<?php endif; ?>
						<?php else : ?>
							<?php
							echo gutenberg_lab_blocks_render_responsive_image(
								array(
									'alt'           => $slide['image_alt'],
									'attachment_id' => $slide['image_id'],
									'class'         => 'vvm-villa-gallery-hero__stage-media vvm-villa-gallery-hero__stage-media--image',
									'fallback_url'  => $slide['image_url'],
									'fetchpriority' => 'high',
									'loading'       => 'eager',
									'size'          => 'gutenberg-lab-hero',
									'sizes'         => '100vw',
								)
							); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
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
			<div
				id="<?php echo esc_attr( $gallery_anchor_id ); ?>"
				class="vvm-villa-gallery-hero__thumbs-shell vvm-slider-surface"
				data-villa-gallery-hero-target="gallery"
			>
				<div
					<?php
					echo gutenberg_lab_blocks_get_slider_controls_attributes(
						$attributes,
						array(
							'class_name'     => 'vvm-villa-gallery-hero__thumb-controls',
							'position_key'   => 'thumbArrowPositionPreset',
							'offset_x_key'   => 'thumbArrowOffsetX',
							'offset_y_key'   => 'thumbArrowOffsetY',
						)
					); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				>
					<button
						type="button"
						class="vvm-villa-gallery-hero__thumb-rail-button vvm-slider-button vvm-slider-button--prev"
						data-villa-gallery-thumbs-prev
						aria-label="<?php esc_attr_e( 'Scroll gallery thumbnails backward', 'gutenberg-lab-blocks' ); ?>"
						hidden
					>
						<?php echo gutenberg_lab_blocks_get_slider_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
					<button
						type="button"
						class="vvm-villa-gallery-hero__thumb-rail-button vvm-slider-button vvm-slider-button--next"
						data-villa-gallery-thumbs-next
						aria-label="<?php esc_attr_e( 'Scroll gallery thumbnails forward', 'gutenberg-lab-blocks' ); ?>"
						hidden
					>
						<?php echo gutenberg_lab_blocks_get_slider_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
				</div>

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
												<?php
												echo gutenberg_lab_blocks_render_responsive_image(
													array(
														'alt'           => $slide['thumb_media_alt'],
														'attachment_id' => $slide['thumb_media_id'],
														'fallback_url'  => $slide['thumb_media_url'],
														'size'          => 'gutenberg-lab-thumb',
														'sizes'         => '(max-width: 782px) 42vw, 240px',
													)
												); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
												?>
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
			</div>
		<?php endif; ?>
	</section>
