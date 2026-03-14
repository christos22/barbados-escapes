<?php
/**
 * Server-side rendering of the `gutenberg-lab-blocks/media-panel` block.
 *
 * @package GutenbergLabBlocks
 */

$media_type       = $attributes['mediaType'] ?? 'image';
$image_url        = $attributes['imageUrl'] ?? '';
$image_alt        = $attributes['imageAlt'] ?? '';
$video_url        = $attributes['videoUrl'] ?? '';
$fallback_image_url = $attributes['fallbackImageUrl'] ?? '';
$fallback_image_alt = $attributes['fallbackImageAlt'] ?? '';
$dark_overlay     = ! empty( $attributes['darkOverlay'] );
$container_height = $attributes['containerHeight'] ?? 'medium';
$content_position = $attributes['contentPosition'] ?? 'center-center';
$content_width    = $attributes['contentWidth'] ?? 'md';
$align            = $attributes['align'] ?? '';

$classes = array(
	'media-panel',
	'media-panel--height-' . sanitize_html_class( $container_height ),
	'media-panel--position-' . sanitize_html_class( $content_position ),
	'media-panel--content-width-' . sanitize_html_class( $content_width ),
	$align ? 'align' . sanitize_html_class( $align ) : 'alignfull',
);

if ( $dark_overlay ) {
	$classes[] = 'media-panel--dark-overlay';
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', $classes ),
	)
);
?>

<section <?php echo $wrapper_attributes; ?>>
	<div class="media-panel__media">
		<?php if ( 'video' === $media_type && $video_url ) : ?>
			<video
				class="media-panel__video"
				autoplay
				muted
				loop
				playsinline
				<?php if ( $fallback_image_url ) : ?>
					poster="<?php echo esc_url( $fallback_image_url ); ?>"
				<?php endif; ?>
			>
				<source src="<?php echo esc_url( $video_url ); ?>" type="video/mp4" />
			</video>
		<?php elseif ( 'video' === $media_type && $fallback_image_url ) : ?>
			<img
				class="media-panel__image"
				src="<?php echo esc_url( $fallback_image_url ); ?>"
				alt="<?php echo esc_attr( $fallback_image_alt ); ?>"
			/>
		<?php elseif ( $image_url ) : ?>
			<img
				class="media-panel__image"
				src="<?php echo esc_url( $image_url ); ?>"
				alt="<?php echo esc_attr( $image_alt ); ?>"
			/>
		<?php endif; ?>
	</div>

	<div class="media-panel__overlay">
		<div class="media-panel__overlay-content">
			<?php echo $content; ?>
		</div>
	</div>
</section>
