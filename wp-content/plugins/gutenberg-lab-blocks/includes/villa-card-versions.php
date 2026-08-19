<?php
/**
 * Fixed frontend routes for comparing villa search-result card treatments.
 *
 * These routes reuse the real villa archive and search contract. Each URL
 * selects one presentation deterministically, avoiding a global dashboard
 * setting that changes every visitor's results at once.
 *
 * @package GutenbergLabBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the supported card versions and their render styles.
 *
 * @return array<string, string>
 */
function gutenberg_lab_blocks_get_villa_card_versions() {
	return array(
		'one'    => 'separate',
		'two'    => 'inline',
		'three'  => 'inline_faint',
		'four'   => 'inline_faint_facts',
		'five'   => 'inline_gold_icons_lg',
		'six'    => 'inline_gold_icons_xl',
		'seven'  => 'inline_gold_all_lg',
		'eight'  => 'inline_gold_all_xl',
		'nine'   => 'inline_gold_icons_lg_text',
		'ten'    => 'inline_gold_icons_xl_text',
		'eleven' => 'inline_green_icons_lg',
		'twelve' => 'inline_green_icons_xl',
	);
}

/**
 * Keeps a card render style within the supported comparison allowlist.
 *
 * @param mixed $value Candidate style value.
 * @return string
 */
function gutenberg_lab_blocks_sanitize_villa_card_style( $value ) {
	$value  = sanitize_key( (string) $value );
	$styles = array_values( gutenberg_lab_blocks_get_villa_card_versions() );

	return in_array( $value, $styles, true ) ? $value : 'separate';
}

/**
 * Returns the card style fixed to the current comparison URL.
 *
 * The selected version comes from the dedicated `/villas/version-*` route.
 *
 * @return string
 */
function gutenberg_lab_blocks_get_villa_card_style() {
	$version  = sanitize_key( (string) get_query_var( 'vvm_villa_card_version' ) );
	$versions = gutenberg_lab_blocks_get_villa_card_versions();

	return isset( $versions[ $version ] ) ? $versions[ $version ] : 'separate';
}

/**
 * Registers the fixed comparison routes before WordPress resolves requests.
 */
function gutenberg_lab_blocks_register_villa_card_version_rewrites() {
	add_rewrite_rule(
		'^villas/version-(one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve)/?$',
		'index.php?post_type=villa&vvm_villa_card_version=$matches[1]',
		'top'
	);
}
add_action( 'init', 'gutenberg_lab_blocks_register_villa_card_version_rewrites', 20 );

/**
 * Allows WordPress to retain the selected comparison version during routing.
 *
 * @param string[] $query_vars Public query variables.
 * @return string[]
 */
function gutenberg_lab_blocks_register_villa_card_version_query_var( $query_vars ) {
	$query_vars[] = 'vvm_villa_card_version';

	return $query_vars;
}
add_filter( 'query_vars', 'gutenberg_lab_blocks_register_villa_card_version_query_var' );

/**
 * Returns a canonical frontend URL for a comparison version.
 *
 * @param string $version Version word: one through twelve.
 * @return string
 */
function gutenberg_lab_blocks_get_villa_card_version_url( $version = 'one' ) {
	$versions = gutenberg_lab_blocks_get_villa_card_versions();
	$version  = isset( $versions[ $version ] ) ? $version : 'one';

	return home_url( user_trailingslashit( 'villas/version-' . $version ) );
}

/**
 * Returns the correct base URL for the active results experience.
 *
 * @return string
 */
function gutenberg_lab_blocks_get_villa_search_results_url() {
	$version  = sanitize_key( (string) get_query_var( 'vvm_villa_card_version' ) );
	$versions = gutenberg_lab_blocks_get_villa_card_versions();

	if ( isset( $versions[ $version ] ) ) {
		return gutenberg_lab_blocks_get_villa_card_version_url( $version );
	}

	$archive_url = get_post_type_archive_link( 'villa' );

	return is_string( $archive_url ) ? $archive_url : '';
}

/**
 * Redirects the short comparison URL to version one while preserving filters.
 */
function gutenberg_lab_blocks_redirect_villa_card_version_index() {
	$version      = sanitize_key( (string) get_query_var( 'vvm_villa_card_version' ) );
	$request_path = isset( $_SERVER['REQUEST_URI'] )
		? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH )
		: '';
	$index_path   = wp_parse_url( home_url( user_trailingslashit( 'villas' ) ), PHP_URL_PATH );

	if (
		'' !== $version
		|| ! is_post_type_archive( 'villa' )
		|| untrailingslashit( (string) $request_path ) !== untrailingslashit( (string) $index_path )
	) {
		return;
	}

	$destination = gutenberg_lab_blocks_get_villa_card_version_url( 'one' );

	if ( function_exists( 'gutenberg_lab_blocks_get_villa_search_request' ) ) {
		$destination = add_query_arg(
			gutenberg_lab_blocks_get_villa_search_url_args(
				gutenberg_lab_blocks_get_villa_search_request()
			),
			$destination
		);
	}

	wp_safe_redirect( $destination, 302, 'Gutenberg Lab Blocks' );
	exit;
}
add_action( 'template_redirect', 'gutenberg_lab_blocks_redirect_villa_card_version_index', 5 );

/**
 * Prevents WordPress from canonicalizing comparison URLs back to `/villas/`.
 *
 * @param string|false $redirect_url Proposed canonical redirect.
 * @return string|false
 */
function gutenberg_lab_blocks_preserve_villa_card_version_url( $redirect_url ) {
	$version = sanitize_key( (string) get_query_var( 'vvm_villa_card_version' ) );

	return isset( gutenberg_lab_blocks_get_villa_card_versions()[ $version ] )
		? false
		: $redirect_url;
}
add_filter( 'redirect_canonical', 'gutenberg_lab_blocks_preserve_villa_card_version_url' );

/**
 * Comparison URLs intentionally duplicate `/villas/`; keep them out of search.
 *
 * @param array<string, bool> $robots Robots directives.
 * @return array<string, bool>
 */
function gutenberg_lab_blocks_noindex_villa_card_versions( $robots ) {
	$version = sanitize_key( (string) get_query_var( 'vvm_villa_card_version' ) );

	if ( isset( gutenberg_lab_blocks_get_villa_card_versions()[ $version ] ) ) {
		$robots['noindex'] = true;
	}

	return $robots;
}
add_filter( 'wp_robots', 'gutenberg_lab_blocks_noindex_villa_card_versions' );

/**
 * Flushes the new routes once after this route contract changes.
 */
function gutenberg_lab_blocks_maybe_flush_villa_card_version_rewrites() {
	$rewrite_version = '20260818-2';

	if ( $rewrite_version === get_option( 'gutenberg_lab_blocks_villa_card_version_rewrites' ) ) {
		return;
	}

	flush_rewrite_rules( false );
	update_option( 'gutenberg_lab_blocks_villa_card_version_rewrites', $rewrite_version, false );
}
add_action( 'init', 'gutenberg_lab_blocks_maybe_flush_villa_card_version_rewrites', 99 );
