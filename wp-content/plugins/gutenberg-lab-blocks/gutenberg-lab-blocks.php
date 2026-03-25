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

require_once __DIR__ . '/includes/packages.php';
require_once __DIR__ . '/includes/package-rendering.php';
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
}
add_action( 'enqueue_block_assets', 'gutenberg_lab_blocks_enqueue_shared_assets' );

function gutenberg_lab_blocks_register_blocks() {
	register_block_type( __DIR__ . '/build/media-panel' );
	register_block_type( __DIR__ . '/build/package-meta' );
	register_block_type( __DIR__ . '/build/packages-display' );
	register_block_type( __DIR__ . '/build/related-packages' );
	register_block_type( __DIR__ . '/build/site-footer-meta' );
	register_block_type( __DIR__ . '/build/basic-content' );
	register_block_type( __DIR__ . '/build/split-content' );
	register_block_type( __DIR__ . '/build/card-grid' );
	register_block_type( __DIR__ . '/build/card-grid-card' );
	register_block_type( __DIR__ . '/build/stack-tabs' );
	register_block_type( __DIR__ . '/build/stack-tab' );
	register_block_type( __DIR__ . '/build/stack-tab-item' );
	register_block_type( __DIR__ . '/build/site-alerts' );
	register_block_type( __DIR__ . '/build/villa-hero-search' );
}
add_action( 'init', 'gutenberg_lab_blocks_register_blocks' );
