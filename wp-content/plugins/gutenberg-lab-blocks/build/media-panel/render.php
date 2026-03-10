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
$dark_overlay     = ! empty( $attributes['darkOverlay'] );
$container_height = $attributes['containerHeight'] ?? 'medium';
$content_position = $attributes['contentPosition'] ?? 'center-center';

$classes = array(
	'media-panel',
	'media-panel--height-' . sanitize_html_class( $container_height ),
	'media-panel--position-' . sanitize_html_class( $content_position ),
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
			<video class="media-panel__video" autoplay muted loop playsinline>
				<source src="<?php echo esc_url( $video_url ); ?>" type="video/mp4" />
			</video>
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
