<?php
/**
 * Plugin Name: Gutenberg Lab Blocks
 * Description: Custom Gutenberg blocks for learning block development.
 * Version: 0.1.0
 * Author: Your Name
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/video.php';
require_once __DIR__ . '/includes/packages.php';
require_once __DIR__ . '/includes/package-rendering.php';
require_once __DIR__ . '/includes/peeking-carousel.php';
require_once __DIR__ . '/includes/site-messages.php';
require_once __DIR__ . '/includes/villas.php';

/**
 * Registers rewrite-dependent content types before flushing plugin rules.
 *
 * WordPress only flushes what is registered in the current request, so we call
 * the CPT/taxonomy registration functions directly during activation and
 * deactivation to keep `/packages/`, `/villas/`, and their taxonomies stable.
 */
function gutenberg_lab_blocks_register_rewrite_content_types() {
	gutenberg_lab_blocks_register_packages_post_type();
	gutenberg_lab_blocks_register_package_type_taxonomy();
	gutenberg_lab_blocks_register_villas_post_type();
	gutenberg_lab_blocks_register_villa_location_taxonomy();
}

/**
 * Flushes rewrite rules when the plugin is activated.
 */
function gutenberg_lab_blocks_activate() {
	gutenberg_lab_blocks_register_rewrite_content_types();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'gutenberg_lab_blocks_activate' );

/**
 * Flushes rewrite rules when the plugin is deactivated.
 */
function gutenberg_lab_blocks_deactivate() {
	gutenberg_lab_blocks_register_rewrite_content_types();
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'gutenberg_lab_blocks_deactivate' );

/**
 * Return a stable version string for plugin assets.
 *
 * Using filemtime keeps browser caches fresh while we iterate on block styles.
 */
function gutenberg_lab_blocks_asset_version( $relative_path ) {
	$absolute_path = __DIR__ . '/' . ltrim( $relative_path, '/' );

	if ( file_exists( $absolute_path ) ) {
		return (string) filemtime( $absolute_path );
	}

	return '0.1.0';
}

/**
 * Shared chevron icon used by every slider arrow button in the plugin.
 *
 * Keeping the SVG in one PHP helper makes it easier to reuse across blocks
 * without duplicating long inline markup.
 */
function gutenberg_lab_blocks_get_slider_arrow_icon() {
	return '<svg class="vvm-slider-button__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 6l6 6-6 6" /></svg>';
}

/**
 * Normalizes one slider arrow preset to the supported shared CSS modifiers.
 *
 * @param string $preset Shared preset slug from block attributes.
 * @param string $default Default preset for the current block.
 * @return string
 */
function gutenberg_lab_blocks_normalize_slider_arrow_preset( $preset, $default = 'center' ) {
	$allowed_presets = array( 'center', 'bottom-right', 'bottom-center' );
	$default         = in_array( $default, $allowed_presets, true ) ? $default : 'center';

	return in_array( $preset, $allowed_presets, true ) ? $preset : $default;
}

/**
 * Clamps one slider arrow offset to the supported range and step size.
 *
 * Matching the editor control constraints here prevents unexpected values from
 * bypassing the preset-based layout contract on the front end.
 *
 * @param mixed $offset Raw block attribute value.
 * @return int
 */
function gutenberg_lab_blocks_normalize_slider_arrow_offset( $offset ) {
	$offset = is_numeric( $offset ) ? (int) $offset : 0;
	$offset = (int) round( $offset / 4 ) * 4;

	return max( -64, min( 64, $offset ) );
}

/**
 * Returns the class and inline style attributes for a shared arrow wrapper.
 *
 * @param array $attributes Block attributes.
 * @param array $args Helper configuration.
 * @return string
 */
function gutenberg_lab_blocks_get_slider_controls_attributes( $attributes, $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'class_name'   => '',
			'default_preset' => 'center',
			'offset_x_key' => 'arrowOffsetX',
			'offset_y_key' => 'arrowOffsetY',
			'position_key' => 'arrowPositionPreset',
			'style_vars'   => array(),
		)
	);

	$preset = gutenberg_lab_blocks_normalize_slider_arrow_preset(
		(string) ( $attributes[ $args['position_key'] ] ?? '' ),
		$args['default_preset']
	);
	$style_vars = array(
		'--vvm-slider-controls-offset-x' => gutenberg_lab_blocks_normalize_slider_arrow_offset(
			$attributes[ $args['offset_x_key'] ] ?? 0
		) . 'px',
		'--vvm-slider-controls-offset-y' => gutenberg_lab_blocks_normalize_slider_arrow_offset(
			$attributes[ $args['offset_y_key'] ] ?? 0
		) . 'px',
	);

	foreach ( (array) $args['style_vars'] as $property => $value ) {
		if ( ! is_string( $property ) || '' === trim( $property ) ) {
			continue;
		}

		if ( ! is_scalar( $value ) || '' === trim( (string) $value ) ) {
			continue;
		}

		$style_vars[ trim( $property ) ] = trim( (string) $value );
	}

	$style_tokens = array();

	foreach ( $style_vars as $property => $value ) {
		$style_tokens[] = $property . ':' . $value;
	}

	$style_attribute = safecss_filter_attr( implode( ';', $style_tokens ) . ';' );
	$class_names     = array_filter(
		preg_split(
			'/\s+/',
			trim(
				'vvm-slider-controls vvm-slider-controls--preset-' .
				$preset .
				' ' .
				(string) $args['class_name']
			)
		)
	);

	return sprintf(
		'class="%1$s"%2$s',
		esc_attr( implode( ' ', $class_names ) ),
		'' !== $style_attribute ? ' style="' . esc_attr( $style_attribute ) . '"' : ''
	);
}

/**
 * Returns the temporary Dashicon map for the Value Pillars child block.
 *
 * We store semantic slugs in block attributes so the icon artwork can be
 * swapped later without changing saved block content.
 *
 * @return array<string, string>
 */
function gutenberg_lab_blocks_get_value_pillar_icon_map() {
	return array(
		'curated'     => 'dashicons-star-filled',
		'transparency' => 'dashicons-visibility',
		'knowledge'   => 'dashicons-location-alt',
		'availability' => 'dashicons-clock',
		'service'     => 'dashicons-admin-users',
		'privacy'     => 'dashicons-lock',
	);
}

/**
 * Returns the Dashicon class for one semantic Value Pillar icon slug.
 *
 * @param string $icon_slug Stored semantic icon slug.
 * @return string
 */
function gutenberg_lab_blocks_get_value_pillar_dashicon_class( $icon_slug ) {
	$icon_map = gutenberg_lab_blocks_get_value_pillar_icon_map();
	$icon_slug = is_string( $icon_slug ) ? sanitize_key( $icon_slug ) : '';

	return $icon_map[ $icon_slug ] ?? $icon_map['curated'];
}

/**
 * Enqueue shared front-end/editor styles for reusable slider controls.
 *
 * Block-specific styles still handle layout, but the arrow buttons themselves
 * should stay visually consistent wherever we use sliders.
 */
function gutenberg_lab_blocks_enqueue_shared_assets() {
	wp_enqueue_style(
		'gutenberg-lab-blocks-shared',
		plugins_url( 'assets/css/frontend.css', __FILE__ ),
		array(),
		gutenberg_lab_blocks_asset_version( 'assets/css/frontend.css' )
	);

	// Dashicons ship with WordPress core, but guest-facing pages do not load the
	// font by default. The temporary Value Pillars icons need it on the front end.
	if ( ! is_admin() ) {
		wp_enqueue_style( 'dashicons' );
	}
}
add_action( 'enqueue_block_assets', 'gutenberg_lab_blocks_enqueue_shared_assets' );

function gutenberg_lab_blocks_register_blocks() {
	register_block_type( __DIR__ . '/build/media-panel' );
	register_block_type( __DIR__ . '/build/package-meta' );
	register_block_type( __DIR__ . '/build/packages-display' );
	register_block_type( __DIR__ . '/build/related-packages' );
	register_block_type( __DIR__ . '/build/site-footer-meta' );
	register_block_type( __DIR__ . '/build/basic-content' );
	register_block_type( __DIR__ . '/build/value-pillars' );
	register_block_type( __DIR__ . '/build/value-pillar' );
	register_block_type( __DIR__ . '/build/split-content' );
	register_block_type( __DIR__ . '/build/feature-carousel' );
	register_block_type( __DIR__ . '/build/feature-carousel-slide' );
	register_block_type( __DIR__ . '/build/two-up-carousel' );
	register_block_type( __DIR__ . '/build/two-up-carousel-slide' );
	register_block_type( __DIR__ . '/build/card-carousel' );
	register_block_type( __DIR__ . '/build/card-carousel-slide' );
	register_block_type( __DIR__ . '/build/card-grid' );
	register_block_type( __DIR__ . '/build/card-grid-card' );
	register_block_type( __DIR__ . '/build/stack-tabs' );
	register_block_type( __DIR__ . '/build/stack-tab' );
	register_block_type( __DIR__ . '/build/stack-tab-item' );
	register_block_type( __DIR__ . '/build/site-alerts' );
	register_block_type( __DIR__ . '/build/villa-hero-search' );
	register_block_type( __DIR__ . '/build/villa-gallery-hero' );
	register_block_type( __DIR__ . '/build/villa-gallery-hero-media' );
	register_block_type( __DIR__ . '/build/villa-gallery-hero-content' );
	register_block_type( __DIR__ . '/build/villa-gallery-hero-slide' );
	register_block_type( __DIR__ . '/build/villa-gallery-carousel' );
	register_block_type( __DIR__ . '/build/villa-gallery-carousel-slide' );
	register_block_type( __DIR__ . '/build/villa-specs' );
	register_block_type( __DIR__ . '/build/villa-spec-item' );
}
add_action( 'init', 'gutenberg_lab_blocks_register_blocks' );
