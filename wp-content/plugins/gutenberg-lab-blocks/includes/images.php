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

		$decoded_url = rawurldecode( $image_url );

		if ( false !== strpos( $decoded_url, '/wp-content/uploads/' ) ) {
			$upload_path = preg_replace( '#^.*?(/wp-content/uploads/)#i', '$1', $decoded_url );

			if ( is_string( $upload_path ) && $upload_path !== $decoded_url ) {
				$image_url = home_url( $upload_path );
			}
		}

		$attachment_id = attachment_url_to_postid( $image_url );

		if ( $attachment_id && wp_attachment_is_image( (int) $attachment_id ) ) {
			return (int) $attachment_id;
		}

		$decoded_url   = rawurldecode( $image_url );
		$attachment_id = attachment_url_to_postid( $decoded_url );

		if ( $attachment_id && wp_attachment_is_image( (int) $attachment_id ) ) {
			return (int) $attachment_id;
		}

		$parsed_url = wp_parse_url( $decoded_url );
		$path       = is_array( $parsed_url ) ? ( $parsed_url['path'] ?? '' ) : '';

		if ( ! is_string( $path ) || '' === $path ) {
			return 0;
		}

		// Legacy blocks may save a generated sub-size URL such as image-1920x1200.jpg.
		$original_path = preg_replace( '/-\d+x\d+(?=\.(?:jpe?g|png|gif|webp|avif)$)/i', '', $path );

		if ( ! is_string( $original_path ) || $original_path === $path ) {
			return 0;
		}

		$origin_url    = home_url( $original_path );
		$attachment_id = attachment_url_to_postid( $origin_url );

		if ( $attachment_id && wp_attachment_is_image( (int) $attachment_id ) ) {
			return (int) $attachment_id;
		}

		// WordPress appends "-scaled" to very large master images before creating sub-sizes.
		$scaled_path = preg_replace( '/(\.(?:jpe?g|png|gif|webp|avif)$)/i', '-scaled$1', $original_path );

		if ( ! is_string( $scaled_path ) || $scaled_path === $original_path ) {
			return 0;
		}

		$scaled_url    = home_url( $scaled_path );
		$attachment_id = attachment_url_to_postid( $scaled_url );

		if ( $attachment_id && wp_attachment_is_image( (int) $attachment_id ) ) {
			return (int) $attachment_id;
		}

		$attachment_id = gutenberg_lab_blocks_get_attachment_id_from_filename( basename( $original_path ) );

		if ( $attachment_id && wp_attachment_is_image( (int) $attachment_id ) ) {
			return (int) $attachment_id;
		}

		return 0;
	}
}

if ( ! function_exists( 'gutenberg_lab_blocks_get_attachment_id_from_filename' ) ) {
	/**
	 * Resolves a legacy saved image URL by matching the media filename.
	 *
	 * Some imported blocks can retain an old generated-size URL even after the
	 * current attachment has moved to a different month folder or gained a "-1"
	 * duplicate suffix. This fallback keeps those blocks responsive without
	 * requiring editors to reselect each image manually.
	 *
	 * @param string $filename Original filename without an upload directory.
	 * @return int
	 */
	function gutenberg_lab_blocks_get_attachment_id_from_filename( $filename ) {
		static $cache = array();

		$filename = is_string( $filename ) ? sanitize_file_name( $filename ) : '';

		if ( '' === $filename ) {
			return 0;
		}

		if ( array_key_exists( $filename, $cache ) ) {
			return (int) $cache[ $filename ];
		}

		$path_info = pathinfo( $filename );
		$extension = isset( $path_info['extension'] ) ? strtolower( (string) $path_info['extension'] ) : '';
		$stem      = isset( $path_info['filename'] ) ? (string) $path_info['filename'] : '';

		if ( '' === $extension || '' === $stem ) {
			$cache[ $filename ] = 0;

			return 0;
		}

		global $wpdb;

		$exact_file     = $stem . '.' . $extension;
		$scaled_file    = $stem . '-scaled.' . $extension;
		$duplicate_like = '%/' . $wpdb->esc_like( $stem ) . '-%.' . $wpdb->esc_like( $extension );

		$attachment_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT posts.ID
				FROM {$wpdb->posts} AS posts
				INNER JOIN {$wpdb->postmeta} AS filemeta
					ON posts.ID = filemeta.post_id
				WHERE posts.post_type = %s
					AND posts.post_mime_type LIKE %s
					AND filemeta.meta_key = %s
					AND (
						filemeta.meta_value = %s
						OR filemeta.meta_value LIKE %s
						OR filemeta.meta_value LIKE %s
						OR filemeta.meta_value LIKE %s
					)
				ORDER BY posts.ID DESC
				LIMIT 1",
				'attachment',
				'image/%',
				'_wp_attached_file',
				$exact_file,
				'%/' . $wpdb->esc_like( $exact_file ),
				'%/' . $wpdb->esc_like( $scaled_file ),
				$duplicate_like
			)
		);

		$cache[ $filename ] = $attachment_id;

		return $attachment_id;
	}
}

if ( ! function_exists( 'gutenberg_lab_blocks_get_attachment_id_from_generated_url_filename' ) ) {
	/**
	 * Resolves a generated-size URL by matching the original filename.
	 *
	 * @param string $image_url Saved or rewritten image URL.
	 * @return int
	 */
	function gutenberg_lab_blocks_get_attachment_id_from_generated_url_filename( $image_url ) {
		$image_url = is_string( $image_url ) ? rawurldecode( trim( $image_url ) ) : '';

		if ( '' === $image_url ) {
			return 0;
		}

		if ( false !== strpos( $image_url, '/wp-content/uploads/' ) ) {
			$image_url = preg_replace( '#^.*?(/wp-content/uploads/)#i', '$1', $image_url );
		}

		$parsed_url = wp_parse_url( $image_url );
		$path       = is_array( $parsed_url ) ? ( $parsed_url['path'] ?? '' ) : $image_url;

		if ( ! is_string( $path ) || '' === $path ) {
			return 0;
		}

		$original_path = preg_replace( '/-\d+x\d+(?=\.(?:jpe?g|png|gif|webp|avif)$)/i', '', $path );

		if ( ! is_string( $original_path ) || $original_path === $path ) {
			return 0;
		}

		return gutenberg_lab_blocks_get_attachment_id_from_filename( basename( $original_path ) );
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
		$fallback_url  = is_string( $args['fallback_url'] ) ? trim( $args['fallback_url'] ) : '';

		if ( $attachment_id && wp_attachment_is_image( $attachment_id ) && '' !== $fallback_url ) {
			$current_srcset = wp_get_attachment_image_srcset( $attachment_id, $args['size'] );
			$filename_attachment_id = gutenberg_lab_blocks_get_attachment_id_from_generated_url_filename( $fallback_url );
			$filename_srcset        = $filename_attachment_id ? wp_get_attachment_image_srcset( $filename_attachment_id, $args['size'] ) : '';

			if (
				$filename_attachment_id &&
				'' !== (string) $filename_srcset &&
				( $filename_attachment_id !== $attachment_id || '' === (string) $current_srcset )
			) {
				$attachment_id = $filename_attachment_id;
			}
		}

		$custom_attrs = (array) $args['attrs'];
		$image_attrs  = array_merge(
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

		if ( '' === $fallback_url ) {
			return '';
		}

		unset( $image_attrs['sizes'] );
		$image_attrs['src'] = esc_url_raw( $fallback_url );

		return '<img' . gutenberg_lab_blocks_get_html_attributes( $image_attrs ) . ' />';
	}
}
