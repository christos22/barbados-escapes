<?php
/**
 * Server rendering for the Split Content block.
 *
 * The block stores content as regular nested blocks so typography and button
 * styling stay Gutenberg-native, while PHP owns the media wrapper and layout
 * classes that mirror the original VVM component.
 *
 * @package GutenbergLabBlocks
 */

$media_position_desktop = $attributes['mediaPositionDesktop'] ?? 'right';
$media_position_mobile  = $attributes['mediaPositionMobile'] ?? 'top';
$media_width            = $attributes['mediaWidth'] ?? '50';
$media_type             = $attributes['mediaType'] ?? 'image';
$media_on_edge          = ! empty( $attributes['mediaOnEdge'] );
$image_url              = $attributes['imageUrl'] ?? '';
$image_alt              = $attributes['imageAlt'] ?? '';
$video_url              = $attributes['videoUrl'] ?? '';
$gallery_images         = $attributes['galleryImages'] ?? array();

$classes = array(
	'split-content',
	'split-content--desktop-' . sanitize_html_class( $media_position_desktop ),
	'split-content--mobile-' . sanitize_html_class( $media_position_mobile ),
	'split-content--width-' . sanitize_html_class( $media_width ),
	$media_on_edge ? 'split-content--edge' : 'split-content--contained',
);

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', $classes ),
	)
);
$gallery_images = is_array( $gallery_images ) ? $gallery_images : array();
$gallery_images = array_values(
	array_filter(
		array_map(
			static function ( $item ) {
				if ( ! is_array( $item ) || empty( $item['url'] ) ) {
					return null;
				}

				return array(
					'id'  => isset( $item['id'] ) ? (int) $item['id'] : 0,
					'url' => esc_url_raw( $item['url'] ),
					'alt' => isset( $item['alt'] ) ? sanitize_text_field( $item['alt'] ) : '',
				);
			},
			$gallery_images
		)
	)
);
?>

<section <?php echo $wrapper_attributes; ?>>
	<div class="split-content__grid">
		<div class="split-content__content">
			<div class="split-content__content-flow">
				<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
		<div class="split-content__media">
			<?php if ( 'video' === $media_type && '' !== $video_url ) : ?>
				<video
					class="split-content__media-asset"
					src="<?php echo esc_url( $video_url ); ?>"
					autoplay
					muted
					loop
					playsinline
					preload="metadata"
				></video>
			<?php elseif ( 'slider' === $media_type && ! empty( $gallery_images ) ) : ?>
				<div class="split-content__slider" data-split-content-slider>
					<div class="split-content__slides">
						<?php foreach ( $gallery_images as $gallery_image ) : ?>
							<figure class="split-content__slide">
								<img
									class="split-content__media-asset"
									src="<?php echo esc_url( $gallery_image['url'] ); ?>"
									alt="<?php echo esc_attr( $gallery_image['alt'] ); ?>"
								/>
							</figure>
						<?php endforeach; ?>
					</div>
					<?php if ( count( $gallery_images ) > 1 ) : ?>
						<div class="split-content__slider-controls">
							<button
								type="button"
								class="split-content__slider-button"
								data-split-content-prev
								aria-label="<?php esc_attr_e( 'Previous slide', 'gutenberg-lab-blocks' ); ?>"
							>
								&larr;
							</button>
							<button
								type="button"
								class="split-content__slider-button"
								data-split-content-next
								aria-label="<?php esc_attr_e( 'Next slide', 'gutenberg-lab-blocks' ); ?>"
							>
								&rarr;
							</button>
						</div>
					<?php endif; ?>
				</div>
			<?php elseif ( '' !== $image_url ) : ?>
				<img
					class="split-content__media-asset"
					src="<?php echo esc_url( $image_url ); ?>"
					alt="<?php echo esc_attr( $image_alt ); ?>"
				/>
			<?php else : ?>
				<div class="split-content__media-placeholder">
					<p><?php esc_html_e( 'Select a media item to complete this layout.', 'gutenberg-lab-blocks' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
