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
		'thirteen' => 'inline_green_icons_xl_gold_rule_sans_price',
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
 * Returns the approved default card style or the current comparison style.
 *
 * `/villas/` uses version six. A dedicated `/villas/version-*` route can
 * override that presentation so the remaining references stay available.
 *
 * @return string
 */
function gutenberg_lab_blocks_get_villa_card_style() {
	$version  = sanitize_key( (string) get_query_var( 'vvm_villa_card_version' ) );
	$versions = gutenberg_lab_blocks_get_villa_card_versions();

	return isset( $versions[ $version ] ) ? $versions[ $version ] : $versions['six'];
}

/**
 * Returns the Explore CTA treatment for the active search-result version.
 *
 * CTA presentation is intentionally separate from the fact/icon style. This
 * prevents a search-result experiment from changing shared villa cards used
 * elsewhere on the site.
 *
 * @return string Link, outline, or outline-glow.
 */
function gutenberg_lab_blocks_get_villa_card_explore_cta_style() {
	$version  = sanitize_key( (string) get_query_var( 'vvm_villa_card_version' ) );
	$versions = gutenberg_lab_blocks_get_villa_card_versions();
	$version  = isset( $versions[ $version ] ) ? $version : 'six';

	if ( in_array( $version, array( 'eleven', 'twelve', 'thirteen' ), true ) ) {
		return 'outline-glow';
	}

	if ( in_array( $version, array( 'five', 'six', 'seven', 'eight', 'nine', 'ten' ), true ) ) {
		return 'outline';
	}

	return 'link';
}

/**
 * Registers the fixed comparison routes before WordPress resolves requests.
 */
function gutenberg_lab_blocks_register_villa_card_version_rewrites() {
	add_rewrite_rule(
		'^villas/version-(one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve|thirteen)/?$',
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
 * @param string $version Version word: one through thirteen.
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
	$rewrite_version = '20260819-1';

	if ( $rewrite_version === get_option( 'gutenberg_lab_blocks_villa_card_version_rewrites' ) ) {
		return;
	}

	flush_rewrite_rules( false );
	update_option( 'gutenberg_lab_blocks_villa_card_version_rewrites', $rewrite_version, false );
}
add_action( 'init', 'gutenberg_lab_blocks_maybe_flush_villa_card_version_rewrites', 99 );
