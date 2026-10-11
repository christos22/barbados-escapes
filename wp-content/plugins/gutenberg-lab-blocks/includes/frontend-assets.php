<?php
/**
 * Keep frontend assets available independently of the page cache.
 *
 * A cached HTML page can outlive an Autoptimize cache purge. WordPress already
 * versions our deployed assets; serving those files directly avoids references
 * to disposable CSS/JS copies. Retain the old copies for previously cached HTML.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keep native script order, loading strategies and versioned stylesheet URLs.
 *
 * Run before Autoptimize builds its configuration on plugins_loaded. Filtering
 * its effective configuration also keeps its settings screen representative;
 * the stored options and unrelated HTML/image settings are left intact.
 *
 * @param array $config Autoptimize's effective settings.
 * @return array
 */
function gutenberg_lab_blocks_use_deployed_frontend_assets( $config ) {
	$config['autoptimize_css'] = false;
	$config['autoptimize_js']  = false;

	return $config;
}
add_filter( 'autoptimize_filter_get_config', 'gutenberg_lab_blocks_use_deployed_frontend_assets' );

// New HTML creates no optimized CSS/JS copies, so this legacy set stays bounded.
// Keep its URLs valid even when an editor or Nginx Helper clears the cache.
add_filter( 'autoptimize_filter_cache_clearall_disabled', '__return_true' );

/**
 * Explain the managed policy only on Autoptimize's own settings screen.
 */
function gutenberg_lab_blocks_frontend_assets_notice() {
	$screen = get_current_screen();

	if ( ! current_user_can( 'manage_options' ) || ! $screen || 'settings_page_autoptimize' !== $screen->id ) {
		return;
	}

	echo '<div class="notice notice-info"><p>';
	echo esc_html__(
		'Barbados Escapes serves versioned CSS and JavaScript directly from the deployed site files. CSS/JS optimization is managed by the site code. Existing Autoptimize files are retained so previously cached pages keep working; refresh the page cache when publishing changes.',
		'gutenberg-lab-blocks'
	);
	echo '</p></div>';
}
add_action( 'admin_notices', 'gutenberg_lab_blocks_frontend_assets_notice' );
