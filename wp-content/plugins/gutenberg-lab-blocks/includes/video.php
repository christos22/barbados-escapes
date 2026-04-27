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

if ( ! function_exists( 'gutenberg_lab_blocks_get_vimeo_embed_url' ) ) {
	/**
	 * Builds a player.vimeo.com embed URL for the given mode.
	 *
	 * @param string $video_id Vimeo video ID.
	 * @param string $mode     Either `autoplay` or `manual`.
	 * @return string
	 */
	function gutenberg_lab_blocks_get_vimeo_embed_url( $video_id, $mode = 'manual' ) {
		$video_id = is_string( $video_id ) ? trim( $video_id ) : '';

		if ( '' === $video_id ) {
			return '';
		}

		$mode         = 'autoplay' === $mode ? 'autoplay' : 'manual';
		$base_url     = 'https://player.vimeo.com/video/' . rawurlencode( $video_id );
		$query_args   = array(
			'dnt'         => '1',
			'playsinline' => '1',
		);

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
		$poster_url   = trim( (string) ( $attributes[ $args['poster_url_key'] ] ?? '' ) );
		$poster_alt   = trim( (string) ( $attributes[ $args['poster_alt_key'] ] ?? '' ) );
		$vimeo_id     = 'vimeo' === $video_source
			? gutenberg_lab_blocks_get_vimeo_video_id( $vimeo_url )
			: '';
		$has_poster   = '' !== $poster_url || ! $args['poster_required'];

		return array(
			'source'             => $video_source,
			'uploaded_video_url' => esc_url_raw( $video_url ),
			'vimeo_url'          => esc_url_raw( $vimeo_url ),
			'vimeo_id'           => sanitize_text_field( $vimeo_id ),
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
				'button_label'   => __( 'Play video', 'gutenberg-lab-blocks' ),
				'iframe_class'   => '',
				'lazy_load'      => false,
				'poster_alt'     => '',
				'poster_class'   => '',
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
		$wrapper_markup = '';

		foreach ( $wrapper_attributes as $attribute_name => $attribute_value ) {
			if ( ! is_string( $attribute_name ) || '' === trim( $attribute_name ) ) {
				continue;
			}

			if ( '' === $attribute_value ) {
				$wrapper_markup .= ' ' . sanitize_key( $attribute_name );
				continue;
			}

			$wrapper_markup .= sprintf(
				' %1$s="%2$s"',
				sanitize_key( $attribute_name ),
				esc_attr( (string) $attribute_value )
			);
		}

		$poster_attributes = array_merge(
			array(
				'class' => trim( 'vvm-vimeo-shell__poster ' . $args['poster_class'] ),
				'src'   => $args['poster_url'],
				'alt'   => $args['poster_alt'],
			),
			array_diff_key(
				(array) $args['poster_attrs'],
				array(
					'class' => true,
					'src'   => true,
					'alt'   => true,
				)
			)
		);
		$poster_markup     = '';

		foreach ( $poster_attributes as $attribute_name => $attribute_value ) {
			if ( ! is_string( $attribute_name ) || '' === trim( $attribute_name ) || null === $attribute_value || false === $attribute_value ) {
				continue;
			}

			if ( '' === $attribute_value || true === $attribute_value ) {
				$poster_markup .= ' ' . sanitize_key( $attribute_name );
				continue;
			}

			$poster_markup .= sprintf(
				' %1$s="%2$s"',
				sanitize_key( $attribute_name ),
				esc_attr( (string) $attribute_value )
			);
		}

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
					data-vimeo-iframe
				></iframe>
			</div>
				<div
					class="vvm-vimeo-shell__poster-shell"
					data-vimeo-poster-shell
					data-vimeo-play-trigger
					role="button"
					tabindex="0"
					aria-label="<?php echo esc_attr( $args['button_label'] ); ?>"
				>
					<img<?php echo $poster_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> />
					<span class="screen-reader-text"><?php echo esc_html( $args['button_label'] ); ?></span>
				</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}
