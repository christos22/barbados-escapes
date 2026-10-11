<?php
/**
 * WP-CLI integration regression for Autoptimize and the deployed-asset policy.
 *
 * Run against staging or DDEV with both plugins active. This uses the installed
 * Autoptimize implementation, including its real clearall() deletion path.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 'Run this check through WP-CLI.' );
}

$site = untrailingslashit( home_url() );
$host = wp_parse_url( $site, PHP_URL_HOST );
if ( 'https://barbadosescapes.com/staging' !== $site && ! str_ends_with( (string) $host, '.ddev.site' ) ) {
	WP_CLI::error( 'The cache-clear regression is limited to staging or DDEV.' );
}

if ( ! function_exists( 'gutenberg_lab_blocks_use_deployed_frontend_assets' ) || ! class_exists( 'autoptimizeCache' ) ) {
	WP_CLI::error( 'Activate the updated blocks plugin and Autoptimize before running this check.' );
}

// Fail before creating files or attempting a purge if the safety policy is absent.
if ( true !== apply_filters( 'autoptimize_filter_cache_clearall_disabled', false ) ) {
	WP_CLI::error( 'Autoptimize asset retention is not active.' );
}

$config = autoptimizeConfig::instance();
if ( $config->get( 'autoptimize_css' ) || $config->get( 'autoptimize_js' ) ) {
	WP_CLI::error( 'The effective Autoptimize configuration still rewrites CSS/JS.' );
}

// Check that the policy does not change unrelated HTML/image settings.
$sample = array( 'autoptimize_css' => 'on', 'autoptimize_js' => 'on', 'autoptimize_html' => 'on', 'unrelated' => 'keep' );
$actual = gutenberg_lab_blocks_use_deployed_frontend_assets( $sample );
if ( false !== $actual['autoptimize_css'] || false !== $actual['autoptimize_js'] || 'on' !== $actual['autoptimize_html'] || 'keep' !== $actual['unrelated'] ) {
	WP_CLI::error( 'The policy changed unrelated optimizer settings.' );
}

$plugin_url = plugins_url( 'gutenberg-lab-blocks/build/villa-hero-search/' );
$css_url    = $plugin_url . 'style-index.css?ver=cache-regression';
$js_url     = $plugin_url . 'view.js?ver=cache-regression';
$html       = '<!doctype html><html><head><link rel="stylesheet" href="' . esc_url( $css_url ) . '"></head><body><form></form><script defer src="' . esc_url( $js_url ) . '"></script></body></html>';
$optimized  = autoptimize()->end_buffering( $html );

if ( ! str_contains( $optimized, $css_url ) || ! str_contains( $optimized, $js_url ) || str_contains( $optimized, '/cache/autoptimize/' ) ) {
	WP_CLI::error( 'Autoptimize changed the deployed asset URLs.' );
}

// Simulate an old cached page referencing an optimized file, then clear twice:
// once directly (admin/CLI) and once through Nginx Helper's after-purge event.
$sentinel = AUTOPTIMIZE_CACHE_DIR . 'css/autoptimize_single_' . md5( wp_generate_uuid4() ) . '.css';
$body     = '/* Temporary asset-retention regression fixture. */';
$failure  = '';
try {
	if ( ! autoptimizeCache::cacheavail() || false === file_put_contents( $sentinel, $body ) ) {
		throw new RuntimeException( 'Could not create the cache regression fixture.' );
	}

	autoptimizeCache::clearall();
	do_action( 'rt_nginx_helper_after_fastcgi_purge_all' );
	clearstatcache( true, $sentinel );
	if ( ! is_file( $sentinel ) || $body !== file_get_contents( $sentinel ) ) {
		throw new RuntimeException( 'A cache purge removed an asset referenced by old HTML.' );
	}
} catch ( Throwable $error ) {
	$failure = $error->getMessage();
} finally {
	// Delete only our unique test fixture; leave every pre-existing file intact.
	if ( is_file( $sentinel ) ) {
		unlink( $sentinel );
	}
}

if ( $failure ) {
	WP_CLI::error( $failure );
}

WP_CLI::success( 'Deployed CSS/JS URLs are preserved; direct and Nginx-triggered cache clears retain old assets.' );
