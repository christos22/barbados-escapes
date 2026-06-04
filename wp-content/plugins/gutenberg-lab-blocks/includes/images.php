<?php
/**
 * Shared responsive image helpers for Gutenberg Lab Blocks.
 *
 * @package GutenbergLabBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'gutenberg_lab_blocks_register_image_sizes' ) ) {
	/**
	 * Registers the intermediate sizes used by custom block render callbacks.
	 *
	 * These sizes are generated for new uploads. Existing media needs thumbnail
	 * regeneration before every size appears in `srcset`.
	 */
	function gutenberg_lab_blocks_register_image_sizes() {
		add_image_size( 'gutenberg-lab-hero', 1920, 1200, true );
		add_image_size( 'gutenberg-lab-card-landscape', 960, 640, true );
		add_image_size( 'gutenberg-lab-card-portrait', 960, 1280, true );
		add_image_size( 'gutenberg-lab-gallery-card', 720, 960, true );
		add_image_size( 'gutenberg-lab-thumb', 480, 320, true );
	}
}
add_action( 'after_setup_theme', 'gutenberg_lab_blocks_register_image_sizes' );

if ( ! function_exists( 'gutenberg_lab_blocks_get_attachment_id_from_url' ) ) {
	/**
	 * Resolves a saved image URL to a local attachment ID when possible.
	 *
	 * @param string $image_url Saved image URL.
	 * @return int
	 */
	function gutenberg_lab_blocks_get_attachment_id_from_url( $image_url ) {
		$image_url = is_string( $image_url ) ? trim( $image_url ) : '';

		if ( '' === $image_url ) {
			return 0;
		}

		$attachment_id = attachment_url_to_postid( $image_url );

		if ( $attachment_id && wp_attachment_is_image( (int) $attachment_id ) ) {
			return (int) $attachment_id;
		}

		return 0;
	}
}

if ( ! function_exists( 'gutenberg_lab_blocks_get_image_id_from_attributes' ) ) {
	/**
	 * Returns an image attachment ID from block attributes.
	 *
	 * The ID path is preferred because WordPress can produce `srcset`, `sizes`,
	 * dimensions, and loading hints. URL resolution is only a fallback for older
	 * saved blocks.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $id_key     Attribute key storing the attachment ID.
	 * @param string $url_key    Attribute key storing the image URL.
	 * @return int
	 */
	function gutenberg_lab_blocks_get_image_id_from_attributes( $attributes, $id_key = 'imageId', $url_key = 'imageUrl' ) {
		$attributes    = is_array( $attributes ) ? $attributes : array();
		$attachment_id = isset( $attributes[ $id_key ] ) ? (int) $attributes[ $id_key ] : 0;

		if ( $attachment_id && wp_attachment_is_image( $attachment_id ) ) {
			return $attachment_id;
		}

		return gutenberg_lab_blocks_get_attachment_id_from_url( $attributes[ $url_key ] ?? '' );
	}
}

if ( ! function_exists( 'gutenberg_lab_blocks_get_attachment_alt' ) ) {
	/**
	 * Returns the best available alt text for an attachment.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $fallback_alt  Saved block fallback alt text.
	 * @return string
	 */
	function gutenberg_lab_blocks_get_attachment_alt( $attachment_id, $fallback_alt = '' ) {
		$attachment_id = (int) $attachment_id;
		$fallback_alt  = is_string( $fallback_alt ) ? trim( $fallback_alt ) : '';

		if ( '' !== $fallback_alt ) {
			return sanitize_text_field( $fallback_alt );
		}

		if ( ! $attachment_id ) {
			return '';
		}

		$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

		if ( '' === $alt ) {
			$alt = get_the_title( $attachment_id );
		}

		return sanitize_text_field( (string) $alt );
	}
}

if ( ! function_exists( 'gutenberg_lab_blocks_render_responsive_image' ) ) {
	/**
	 * Renders an image with WordPress-generated responsive attributes.
	 *
	 * @param array $args Image rendering configuration.
	 * @return string
	 */
	function gutenberg_lab_blocks_render_responsive_image( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'alt'           => '',
				'attachment_id' => 0,
				'attrs'         => array(),
				'class'         => '',
				'decoding'      => 'async',
				'fallback_url'  => '',
				'fetchpriority' => '',
				'loading'       => 'lazy',
				'size'          => 'large',
				'sizes'         => '',
			)
		);

		$attachment_id = (int) $args['attachment_id'];
		$custom_attrs  = (array) $args['attrs'];
		$image_attrs   = array_merge(
			array(
				'class'    => trim( (string) $args['class'] ),
				'alt'      => gutenberg_lab_blocks_get_attachment_alt( $attachment_id, $args['alt'] ),
				'decoding' => $args['decoding'],
			),
			$custom_attrs
		);

		if ( '' !== (string) $args['loading'] && ! array_key_exists( 'loading', $custom_attrs ) ) {
			$image_attrs['loading'] = $args['loading'];
		}

		if ( '' !== (string) $args['fetchpriority'] ) {
			$image_attrs['fetchpriority'] = $args['fetchpriority'];
		}

		if ( '' !== (string) $args['sizes'] ) {
			$image_attrs['sizes'] = $args['sizes'];
		}

		$image_attrs = array_filter(
			$image_attrs,
			static function ( $value ) {
				return null !== $value && false !== $value && '' !== $value;
			}
		);

		if ( $attachment_id && wp_attachment_is_image( $attachment_id ) ) {
			return wp_get_attachment_image(
				$attachment_id,
				$args['size'],
				false,
				$image_attrs
			);
		}

		$fallback_url = is_string( $args['fallback_url'] ) ? trim( $args['fallback_url'] ) : '';

		if ( '' === $fallback_url ) {
			return '';
		}

		unset( $image_attrs['sizes'] );
		$image_attrs['src'] = esc_url_raw( $fallback_url );

		return '<img' . gutenberg_lab_blocks_get_html_attributes( $image_attrs ) . ' />';
	}
}
