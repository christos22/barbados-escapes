<?php
/**
 * Replace the homepage hero's legacy Explore Villas button with villa search.
 *
 * Usage:
 *   wp eval-file tools/villa-search/restore-home-search.php
 *   wp eval-file tools/villa-search/restore-home-search.php apply
 *
 * The default run is read-only. Pass `apply` only after reviewing its report.
 *
 * @package GutenbergLabBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$apply         = isset( $args[0] ) && 'apply' === $args[0];
$front_page_id = absint( get_option( 'page_on_front' ) );
$front_page    = get_post( $front_page_id );

if ( ! $front_page instanceof WP_Post || 'page' !== $front_page->post_type ) {
	WP_CLI::error( 'The configured static homepage could not be found.' );
}

$content = (string) $front_page->post_content;

if ( str_contains( $content, '<!-- wp:gutenberg-lab-blocks/villa-hero-search' ) ) {
	WP_CLI::success( 'The Villa Hero Search block is already present. No change needed.' );
	return;
}

// Isolate the styled hero so another button elsewhere on the page can never
// be replaced by this maintenance command.
$hero_pattern = '/<!-- wp:gutenberg-lab-blocks\/media-panel\s+\{[^>]*"className":"is-style-villa-hero"[^>]*\}\s*-->.*?<!-- \/wp:gutenberg-lab-blocks\/media-panel -->/s';

if ( 1 !== preg_match( $hero_pattern, $content, $hero_match, PREG_OFFSET_CAPTURE ) ) {
	WP_CLI::error( 'Expected exactly one styled homepage hero, but it was not found.' );
}

$hero_markup = $hero_match[0][0];
$hero_offset = $hero_match[0][1];

// The match is intentionally constrained to a complete Buttons block whose
// visible CTA is the legacy Explore Villas label.
$button_pattern = '/<!-- wp:buttons\b.*?-->\s*<div\b.*?>.*?Explore Villas.*?<!-- \/wp:buttons -->/s';
$search_block   = '<!-- wp:gutenberg-lab-blocks/villa-hero-search /-->';
$updated_hero   = preg_replace( $button_pattern, $search_block, $hero_markup, 1, $replacement_count );

if ( 1 !== $replacement_count || ! is_string( $updated_hero ) ) {
	WP_CLI::error( 'Expected exactly one Explore Villas button group inside the hero.' );
}

$updated_content = substr( $content, 0, $hero_offset )
	. $updated_hero
	. substr( $content, $hero_offset + strlen( $hero_markup ) );

if (
	1 !== substr_count( $updated_content, '<!-- wp:gutenberg-lab-blocks/villa-hero-search' ) ||
	str_contains( $updated_hero, 'Explore Villas' )
) {
	WP_CLI::error( 'The transformed content failed its search-block assertions.' );
}

WP_CLI::log( 'Homepage ID: ' . $front_page_id );
WP_CLI::log( 'Current modified date: ' . $front_page->post_modified );
WP_CLI::log( 'Replacement count: ' . $replacement_count );
WP_CLI::log( 'Current content MD5: ' . md5( $content ) );
WP_CLI::log( 'Updated content MD5: ' . md5( $updated_content ) );

if ( ! $apply ) {
	WP_CLI::log( 'Preview only. Re-run with the positional argument `apply` to save the homepage.' );
	return;
}

$updated_post_id = wp_update_post(
	array(
		'ID'           => $front_page_id,
		'post_content' => $updated_content,
	),
	true
);

if ( is_wp_error( $updated_post_id ) ) {
	WP_CLI::error( $updated_post_id->get_error_message() );
}

WP_CLI::success( 'Restored the Villa Hero Search block on the homepage.' );
