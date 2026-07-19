<?php
/**
 * Plain WordPress pages that live beneath a villa URL.
 *
 * @package GutenbergLabBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const GUTENBERG_LAB_BLOCKS_VILLA_SUBPAGE_PARENT_META = '_gutenberg_lab_villa_parent_slug';

/**
 * Registers the internal relationship used by villa subpages.
 */
function gutenberg_lab_blocks_register_villa_subpage_meta() {
	register_post_meta(
		'page',
		GUTENBERG_LAB_BLOCKS_VILLA_SUBPAGE_PARENT_META,
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => false,
			'sanitize_callback' => 'sanitize_title',
			'default'           => '',
		)
	);
}
add_action( 'init', 'gutenberg_lab_blocks_register_villa_subpage_meta' );

/**
 * Registers `/villas/{villa}/{page}/` for related plain pages.
 */
function gutenberg_lab_blocks_register_villa_subpage_rewrite() {
	add_rewrite_tag( '%villa_parent%', '([^&]+)' );
	add_rewrite_rule(
		'^villas/([^/]+)/([^/]+)/?$',
		'index.php?pagename=$matches[2]&villa_parent=$matches[1]',
		'top'
	);
}
add_action( 'init', 'gutenberg_lab_blocks_register_villa_subpage_rewrite' );

/**
 * Returns the configured published parent villa for a page.
 *
 * @param int|WP_Post $page Page ID or object.
 * @return WP_Post|null
 */
function gutenberg_lab_blocks_get_villa_subpage_parent( $page ) {
	$page = get_post( $page );

	if ( ! $page || 'page' !== $page->post_type ) {
		return null;
	}

	$parent_slug = sanitize_title(
		(string) get_post_meta( $page->ID, GUTENBERG_LAB_BLOCKS_VILLA_SUBPAGE_PARENT_META, true )
	);

	if ( '' === $parent_slug ) {
		return null;
	}

	$parent = get_page_by_path( $parent_slug, OBJECT, 'villa' );

	if ( ! $parent || 'publish' !== $parent->post_status ) {
		return null;
	}

	return $parent;
}

/**
 * Builds the canonical nested URL for a related villa page.
 *
 * @param string $url     Native page URL.
 * @param int    $post_id Page ID.
 * @param bool   $sample  Whether WordPress is generating a sample permalink.
 * @return string
 */
function gutenberg_lab_blocks_filter_villa_subpage_link( $url, $post_id, $sample ) {
	$page   = get_post( $post_id );
	$parent = gutenberg_lab_blocks_get_villa_subpage_parent( $page );

	if ( ! $page || ! $parent || '' === $page->post_name ) {
		return $url;
	}

	$path = sprintf(
		'villas/%1$s/%2$s',
		$parent->post_name,
		$page->post_name
	);

	return home_url( user_trailingslashit( $path, 'page' ) );
}
add_filter( 'page_link', 'gutenberg_lab_blocks_filter_villa_subpage_link', 10, 3 );

/**
 * Rejects nested URLs whose villa segment does not match the page relationship.
 *
 * @param array<string, mixed> $query_vars Parsed WordPress request variables.
 * @return array<string, mixed>
 */
function gutenberg_lab_blocks_validate_villa_subpage_request( $query_vars ) {
	$requested_parent = sanitize_title( (string) ( $query_vars['villa_parent'] ?? '' ) );
	$page_slug         = sanitize_title( (string) ( $query_vars['pagename'] ?? '' ) );

	if ( '' === $requested_parent ) {
		return $query_vars;
	}

	$page   = $page_slug ? get_page_by_path( $page_slug, OBJECT, 'page' ) : null;
	$parent = $page ? gutenberg_lab_blocks_get_villa_subpage_parent( $page ) : null;

	if ( ! $page || ! $parent || $requested_parent !== $parent->post_name ) {
		return array( 'error' => '404' );
	}

	return $query_vars;
}
add_filter( 'request', 'gutenberg_lab_blocks_validate_villa_subpage_request' );

/**
 * Redirects the native flat page URL to the canonical villa subpage URL.
 */
function gutenberg_lab_blocks_redirect_flat_villa_subpage() {
	if ( ! is_page() || is_preview() || get_query_var( 'villa_parent' ) ) {
		return;
	}

	$page   = get_queried_object();
	$parent = $page instanceof WP_Post ? gutenberg_lab_blocks_get_villa_subpage_parent( $page ) : null;

	if ( ! $parent ) {
		return;
	}

	wp_safe_redirect( get_permalink( $page ), 301, 'Gutenberg Lab Blocks' );
	exit;
}
add_action( 'template_redirect', 'gutenberg_lab_blocks_redirect_flat_villa_subpage', 9 );
