<?php
/**
 * Shared video helpers for custom Gutenberg Lab blocks.
 *
 * @package GutenbergLabBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'gutenberg_lab_blocks_normalize_video_source' ) ) {
	/**
	 * Returns the supported video source slug.
	 *
	 * Existing content defaults to uploaded media so MP4 blocks do not need to
	 * migrate when we add Vimeo support.
	 *
	 * @param mixed $video_source Raw block attribute value.
	 * @return string
	 */
	function gutenberg_lab_blocks_normalize_video_source( $video_source ) {
		return 'vimeo' === $video_source ? 'vimeo' : 'uploaded';
	}
}

if ( ! function_exists( 'gutenberg_lab_blocks_get_vimeo_video_id' ) ) {
	/**
	 * Extracts the Vimeo video ID from the supported public/player URL shapes.
	 *
	 * Supported inputs include:
	 * - https://vimeo.com/123456789
	 * - https://vimeo.com/channels/staffpicks/123456789
	 * - https://player.vimeo.com/video/123456789
	 *
	 * @param mixed $vimeo_url Raw author-entered Vimeo URL.
	 * @return string
	 */
	function gutenberg_lab_blocks_get_vimeo_video_id( $vimeo_url ) {
		$vimeo_url = is_string( $vimeo_url ) ? trim( $vimeo_url ) : '';

		if ( '' === $vimeo_url ) {
			return '';
		}

		$parsed_url = wp_parse_url( $vimeo_url );
		$host       = strtolower( (string) ( $parsed_url['host'] ?? '' ) );
		$path       = (string) ( $parsed_url['path'] ?? '' );

		if ( '' === $host || '' === $path ) {
			return '';
		}

		$allowed_hosts = array(
			'vimeo.com',
			'www.vimeo.com',
			'player.vimeo.com',
			'www.player.vimeo.com',
		);

		if ( ! in_array( $host, $allowed_hosts, true ) ) {
			return '';
		}

		if ( preg_match( '/(?:^|\/)(\d+)(?:$|\/)/', $path, $matches ) ) {
			return sanitize_text_field( $matches[1] );
		}

		return '';
	}
}

if ( ! function_exists( 'gutenberg_lab_blocks_get_vimeo_video_hash' ) ) {
	/**
	 * Extracts Vimeo's private/unlisted hash from supported URL formats.
	 *
	 * Vimeo unlisted links need this value forwarded as `h` on the player URL.
	 * Without it, Vimeo can render a sign-in prompt even when the numeric ID is
	 * valid.
	 *
	 * @param mixed $vimeo_url Raw author-entered Vimeo URL.
	 * @return string
	 */
	function gutenberg_lab_blocks_get_vimeo_video_hash( $vimeo_url ) {
		$vimeo_url = is_string( $vimeo_url ) ? trim( $vimeo_url ) : '';

		if ( '' === $vimeo_url ) {
			return '';
		}

		$parsed_url = wp_parse_url( $vimeo_url );

		if ( empty( $parsed_url['host'] ) ) {
			return '';
		}

		$host          = strtolower( $parsed_url['host'] );
		$allowed_hosts = array(
			'vimeo.com',
			'www.vimeo.com',
			'player.vimeo.com',
			'www.player.vimeo.com',
		);

		if ( ! in_array( $host, $allowed_hosts, true ) ) {
			return '';
		}

		if ( ! empty( $parsed_url['query'] ) ) {
			$query_args = array();
			wp_parse_str( $parsed_url['query'], $query_args );

			if ( ! empty( $query_args['h'] ) && is_scalar( $query_args['h'] ) ) {
				return preg_replace( '/[^a-zA-Z0-9]/', '', (string) $query_args['h'] );
			}
		}

		$path_parts = array_values(
			array_filter(
				explode( '/', trim( $parsed_url['path'] ?? '', '/' ) ),
				static fn( $part ) => '' !== $part
			)
		);

		foreach ( $path_parts as $index => $part ) {
			if ( ! preg_match( '/^\d+$/', $part ) || empty( $path_parts[ $index + 1 ] ) ) {
				continue;
			}

			$hash = preg_replace( '/[^a-zA-Z0-9]/', '', (string) $path_parts[ $index + 1 ] );

			return $hash ?: '';
		}

		return '';
	}
}

if ( ! function_exists( 'gutenberg_lab_blocks_get_vimeo_embed_url' ) ) {
	/**
	 * Builds a player.vimeo.com embed URL for the given mode.
	 *
	 * @param string $video_id Vimeo video ID.
	 * @param string $mode     Either `autoplay` or `manual`.
	 * @param string $hash     Optional Vimeo private/unlisted hash.
	 * @return string
	 */
	function gutenberg_lab_blocks_get_vimeo_embed_url( $video_id, $mode = 'manual', $hash = '' ) {
		$video_id = is_string( $video_id ) ? trim( $video_id ) : '';
		$hash     = is_string( $hash ) ? preg_replace( '/[^a-zA-Z0-9]/', '', $hash ) : '';

		if ( '' === $video_id ) {
			return '';
		}

		$mode         = 'autoplay' === $mode ? 'autoplay' : 'manual';
		$base_url     = 'https://player.vimeo.com/video/' . rawurlencode( $video_id );
		$query_args   = array(
			'dnt'         => '1',
			'playsinline' => '1',
		);

		if ( '' !== $hash ) {
			$query_args['h'] = $hash;
		}

		if ( 'autoplay' === $mode ) {
			$query_args = array_merge(
				$query_args,
				array(
					'autoplay'   => '1',
					'background' => '1',
					'muted'      => '1',
					'loop'       => '1',
					'autopause'  => '0',
					'controls'   => '0',
				)
			);
		} else {
			$query_args = array_merge(
				$query_args,
				array(
					'autoplay' => '1',
					'controls' => '1',
				)
			);
		}

		return esc_url_raw( add_query_arg( $query_args, $base_url ) );
	}
}

if ( ! function_exists( 'gutenberg_lab_blocks_get_video_data' ) ) {
	/**
	 * Normalizes the shared video fields used by the custom blocks.
	 *
	 * @param array $attributes Block attributes.
	 * @param array $args       Attribute key overrides.
	 * @return array<string, mixed>
	 */
	function gutenberg_lab_blocks_get_video_data( $attributes, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'video_source_key' => 'videoSource',
				'video_url_key'    => 'videoUrl',
				'vimeo_url_key'    => 'vimeoUrl',
				'poster_id_key'    => 'posterImageId',
				'poster_url_key'   => 'posterImageUrl',
				'poster_alt_key'   => 'posterImageAlt',
				'poster_required'  => true,
			)
		);

		$video_source = gutenberg_lab_blocks_normalize_video_source(
			$attributes[ $args['video_source_key'] ] ?? 'uploaded'
		);
		$video_url    = trim( (string) ( $attributes[ $args['video_url_key'] ] ?? '' ) );
		$vimeo_url    = trim( (string) ( $attributes[ $args['vimeo_url_key'] ] ?? '' ) );
		$poster_id    = gutenberg_lab_blocks_get_image_id_from_attributes(
			$attributes,
			$args['poster_id_key'],
			$args['poster_url_key']
		);
		$poster_url   = trim( (string) ( $attributes[ $args['poster_url_key'] ] ?? '' ) );
		$poster_alt   = trim( (string) ( $attributes[ $args['poster_alt_key'] ] ?? '' ) );
		$vimeo_id     = 'vimeo' === $video_source
			? gutenberg_lab_blocks_get_vimeo_video_id( $vimeo_url )
			: '';
		$vimeo_hash   = 'vimeo' === $video_source
			? gutenberg_lab_blocks_get_vimeo_video_hash( $vimeo_url )
			: '';
		$has_poster   = '' !== $poster_url || ! $args['poster_required'];

		return array(
			'source'             => $video_source,
			'uploaded_video_url' => esc_url_raw( $video_url ),
			'vimeo_url'          => esc_url_raw( $vimeo_url ),
			'vimeo_id'           => sanitize_text_field( $vimeo_id ),
			'vimeo_hash'         => sanitize_text_field( $vimeo_hash ),
			'poster_id'          => $poster_id,
			'poster_url'         => esc_url_raw( $poster_url ),
			'poster_alt'         => sanitize_text_field( $poster_alt ),
			'has_uploaded_video' => 'uploaded' === $video_source && '' !== $video_url,
			'has_vimeo_video'    => 'vimeo' === $video_source && '' !== $vimeo_id && $has_poster,
			'is_complete'        => 'vimeo' === $video_source
				? '' !== $vimeo_id && $has_poster
				: '' !== $video_url,
		);
	}
}

if ( ! function_exists( 'gutenberg_lab_blocks_render_vimeo_shell' ) ) {
	/**
	 * Renders the shared poster-first Vimeo shell used by the custom blocks.
	 *
	 * The iframe starts hidden behind the poster. Frontend JS decides whether to
	 * reveal autoplay or keep the poster/manual-start state when the player is
	 * slow.
	 *
	 * @param array $args Render configuration.
	 * @return string
	 */
	function gutenberg_lab_blocks_render_vimeo_shell( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'autoplay_url'   => '',
				'manual_url'     => '',
				'iframe_class'   => '',
				'lazy_load'      => false,
				'poster_alt'     => '',
				'poster_class'   => '',
				'poster_id'      => 0,
				'poster_size'    => 'gutenberg-lab-hero',
				'poster_sizes'   => '100vw',
				'poster_attrs'   => array(),
				'poster_url'     => '',
				'shell_class'    => '',
				'title'          => __( 'Vimeo video', 'gutenberg-lab-blocks' ),
				'timeout_ms'     => 2000,
				'wrapper_attrs'  => array(),
			)
		);

		if ( '' === $args['autoplay_url'] || '' === $args['manual_url'] || '' === $args['poster_url'] ) {
			return '';
		}

		$wrapper_attributes = array_merge(
			array(
				'class'                   => trim( 'vvm-vimeo-shell ' . $args['shell_class'] ),
				'data-vimeo-shell'        => '',
				'data-vimeo-autoplay-url' => $args['autoplay_url'],
				'data-vimeo-manual-url'   => $args['manual_url'],
				'data-vimeo-timeout'      => (string) max( 0, (int) $args['timeout_ms'] ),
			),
			(array) $args['wrapper_attrs']
		);
		$wrapper_markup     = gutenberg_lab_blocks_get_html_attributes( $wrapper_attributes );

		$poster_markup = gutenberg_lab_blocks_render_responsive_image(
			array(
				'alt'           => $args['poster_alt'],
				'attachment_id' => (int) $args['poster_id'],
				'attrs'         => (array) $args['poster_attrs'],
				'class'         => trim( 'vvm-vimeo-shell__poster ' . $args['poster_class'] ),
				'fallback_url'  => $args['poster_url'],
				'size'          => $args['poster_size'],
				'sizes'         => $args['poster_sizes'],
			)
		);

		ob_start();
		?>
		<div<?php echo $wrapper_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="vvm-vimeo-shell__player" data-vimeo-frame-shell hidden>
				<iframe
					class="<?php echo esc_attr( trim( 'vvm-vimeo-shell__iframe ' . $args['iframe_class'] ) ); ?>"
					<?php if ( ! $args['lazy_load'] ) : ?>
						src="<?php echo esc_url( $args['autoplay_url'] ); ?>"
					<?php endif; ?>
					title="<?php echo esc_attr( $args['title'] ); ?>"
					allow="autoplay; fullscreen; picture-in-picture; encrypted-media"
					allowfullscreen
					loading="eager"
					referrerpolicy="strict-origin-when-cross-origin"
					aria-hidden="true"
					inert
					tabindex="-1"
					data-vimeo-iframe
				></iframe>
			</div>
			<div
				class="vvm-vimeo-shell__poster-shell"
				data-vimeo-poster-shell
			>
				<?php echo $poster_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<?php
			echo gutenberg_lab_blocks_get_vimeo_video_control_button(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}
