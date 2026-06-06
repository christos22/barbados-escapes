<?php
/**
 * Server-side rendering of the `gutenberg-lab-blocks/media-panel` block.
 *
 * @package GutenbergLabBlocks
 */

$media_type       = $attributes['mediaType'] ?? 'image';
$image_id         = gutenberg_lab_blocks_get_image_id_from_attributes( $attributes );
$image_url        = $attributes['imageUrl'] ?? '';
$image_alt        = $attributes['imageAlt'] ?? '';
$fallback_image_id = gutenberg_lab_blocks_get_image_id_from_attributes( $attributes, 'fallbackImageId', 'fallbackImageUrl' );
$fallback_image_url = $attributes['fallbackImageUrl'] ?? '';
$fallback_image_alt = $attributes['fallbackImageAlt'] ?? '';
$dark_overlay     = ! empty( $attributes['darkOverlay'] );
$overlay_gradient_style = $attributes['overlayGradientStyle'] ?? 'brand-green';
$content_style    = $attributes['contentStyle'] ?? 'overlay';
$container_height = $attributes['containerHeight'] ?? 'medium';
$content_position = $attributes['contentPosition'] ?? 'center-center';
$content_width    = $attributes['contentWidth'] ?? 'md';
$accent_border    = $attributes['accentBorder'] ?? 'none';
$atmosphere_edge  = $attributes['atmosphereEdge'] ?? 'none';
$curtain_parallax = ! empty( $attributes['curtainParallax'] );
$align            = $attributes['align'] ?? '';
$video_data       = gutenberg_lab_blocks_get_video_data(
	$attributes,
	array(
		'poster_url_key' => 'fallbackImageUrl',
		'poster_alt_key' => 'fallbackImageAlt',
		'poster_id_key'  => 'fallbackImageId',
	)
);

// When the block is used in a singular template, let the current post's
// featured image act as the hero media if the block itself has no media picked.
//
// We intentionally avoid a raw get_the_ID() fallback on archives because the
// global post can point at the first queried item, which would make an archive
// hero accidentally inherit a package/post image instead of staying editorial.
$current_post_id = 0;

if ( isset( $block->context['postId'] ) ) {
	$current_post_id = (int) $block->context['postId'];
} elseif ( is_singular() ) {
	$current_post_id = (int) get_queried_object_id();
}

if ( $current_post_id && ( ! $image_url || ! $fallback_image_url ) && has_post_thumbnail( $current_post_id ) ) {
	$featured_image_id = (int) get_post_thumbnail_id( $current_post_id );
	$featured_image_url = get_the_post_thumbnail_url( $current_post_id, 'full' );
	$featured_image_alt = get_post_meta( $featured_image_id, '_wp_attachment_image_alt', true );

	if ( '' === $featured_image_alt ) {
		$featured_image_alt = get_the_title( $featured_image_id );
	}

	if ( ! $image_url && $featured_image_url ) {
		$image_id  = $featured_image_id;
		$image_url = $featured_image_url;
		$image_alt = $featured_image_alt;
	}

	if ( ! $fallback_image_url && $featured_image_url ) {
		$fallback_image_id  = $featured_image_id;
		$fallback_image_url = $featured_image_url;
		$fallback_image_alt = $featured_image_alt;
	}
}

$has_priority_candidate = ( 'video' === $media_type && ! empty( $video_data['has_vimeo_video'] ) && ! empty( $video_data['poster_url'] ) ) ||
	( 'video' === $media_type && empty( $video_data['has_uploaded_video'] ) && ! empty( $fallback_image_url ) ) ||
	( 'image' === $media_type && ! empty( $image_url ) );
$should_prioritize_image = $has_priority_candidate && function_exists( 'gutenberg_lab_blocks_claim_frontend_priority_image' )
	? gutenberg_lab_blocks_claim_frontend_priority_image()
	: false;
$image_loading_attrs     = $should_prioritize_image
	? array(
		'fetchpriority' => 'high',
		'loading'       => 'eager',
	)
	: array(
		'loading' => 'lazy',
	);

$classes = array(
	'media-panel',
	'media-panel--height-' . sanitize_html_class( $container_height ),
	'media-panel--content-style-' . sanitize_html_class( $content_style ),
	'media-panel--position-' . sanitize_html_class( $content_position ),
	'media-panel--content-width-' . sanitize_html_class( $content_width ),
	$align ? 'align' . sanitize_html_class( $align ) : 'alignfull',
);

if ( 'none' !== $accent_border ) {
	$classes[] = 'media-panel--accent-border-' . sanitize_html_class( $accent_border );
}

if ( 'none' !== $atmosphere_edge ) {
	$classes[] = 'vvm-atmosphere-edge';
	$classes[] = 'vvm-atmosphere-edge--' . sanitize_html_class( $atmosphere_edge );
}

if ( $dark_overlay ) {
	$classes[] = 'media-panel--dark-overlay';
	$classes[] = 'media-panel--overlay-style-' . sanitize_html_class( $overlay_gradient_style );
}

if ( $curtain_parallax ) {
	$classes[] = 'media-panel--curtain-parallax';
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', $classes ),
	)
);
?>

<section <?php echo $wrapper_attributes; ?>>
	<div class="media-panel__stage">
		<div class="media-panel__media">
			<?php if ( 'video' === $media_type && $video_data['has_uploaded_video'] ) : ?>
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
					<source src="<?php echo esc_url( $video_data['uploaded_video_url'] ); ?>" type="video/mp4" />
				</video>
				<?php
				echo gutenberg_lab_blocks_get_native_video_control_button(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			<?php elseif ( 'video' === $media_type && $video_data['has_vimeo_video'] ) : ?>
				<?php
				echo gutenberg_lab_blocks_render_vimeo_shell(
					array(
						'autoplay_url'  => gutenberg_lab_blocks_get_vimeo_embed_url(
							$video_data['vimeo_id'],
							'autoplay',
							$video_data['vimeo_hash'] ?? ''
						),
						'manual_url'    => gutenberg_lab_blocks_get_vimeo_embed_url(
							$video_data['vimeo_id'],
							'manual',
							$video_data['vimeo_hash'] ?? ''
						),
						'iframe_class'  => 'media-panel__video',
						'lazy_load'     => true,
						'poster_alt'    => $video_data['poster_alt'],
						'poster_attrs'  => array_merge(
							array( 'decoding' => 'async' ),
							$image_loading_attrs
						),
						'poster_class'  => 'media-panel__image',
						'poster_id'     => $video_data['poster_id'],
						'poster_size'   => 'gutenberg-lab-hero',
						'poster_sizes'  => '100vw',
						'poster_url'    => $video_data['poster_url'],
						'shell_class'   => 'media-panel__vimeo-shell',
						'title'         => __( 'Media panel Vimeo video', 'gutenberg-lab-blocks' ),
						'wrapper_attrs' => array(
							'data-vimeo-autoplay-enabled' => 'true',
						),
					)
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			<?php elseif ( 'video' === $media_type && $fallback_image_url ) : ?>
				<?php
				echo gutenberg_lab_blocks_render_responsive_image(
					array(
						'alt'           => $fallback_image_alt,
						'attachment_id' => $fallback_image_id,
						'class'         => 'media-panel__image',
						'fallback_url'  => $fallback_image_url,
						'fetchpriority' => $image_loading_attrs['fetchpriority'] ?? '',
						'loading'       => $image_loading_attrs['loading'],
						'size'          => 'gutenberg-lab-hero',
						'sizes'         => '100vw',
					)
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			<?php elseif ( $image_url ) : ?>
				<?php
				echo gutenberg_lab_blocks_render_responsive_image(
					array(
						'alt'           => $image_alt,
						'attachment_id' => $image_id,
						'class'         => 'media-panel__image',
						'fallback_url'  => $image_url,
						'fetchpriority' => $image_loading_attrs['fetchpriority'] ?? '',
						'loading'       => $image_loading_attrs['loading'],
						'size'          => 'gutenberg-lab-hero',
						'sizes'         => '100vw',
					)
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			<?php endif; ?>
		</div>

		<div class="media-panel__overlay">
			<div class="media-panel__overlay-content">
				<?php echo $content; ?>
			</div>
		</div>
	</div>
</section>
