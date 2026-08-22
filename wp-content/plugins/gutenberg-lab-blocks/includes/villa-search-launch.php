<?php
/**
 * Controls whether the public villa search is available.
 *
 * The option deliberately defaults to disabled. This lets the search code be
 * deployed before production villa data is ready without exposing incomplete
 * results or a half-configured homepage form.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns whether the public villa search has been launched.
 *
 * A constant can provide an environment-level override when required, while
 * the normal client-facing control remains the checkbox in Settings > Reading.
 *
 * @return bool
 */
function gutenberg_lab_blocks_is_villa_search_public_enabled() {
	if ( defined( 'VVM_VILLA_SEARCH_PUBLIC_ENABLED' ) ) {
		return (bool) VVM_VILLA_SEARCH_PUBLIC_ENABLED;
	}

	return (bool) get_option( 'gutenberg_lab_blocks_villa_search_public_enabled', false );
}

/**
 * Allows authenticated editor previews while keeping disabled public renders empty.
 *
 * @return bool
 */
function gutenberg_lab_blocks_should_render_villa_search() {
	if ( is_admin() ) {
		return true;
	}

	if ( wp_is_json_request() && current_user_can( 'edit_posts' ) ) {
		return true;
	}

	return gutenberg_lab_blocks_is_villa_search_public_enabled();
}

/**
 * Normalizes the launch checkbox to the only two stored values we accept.
 *
 * @param mixed $value Submitted setting value.
 * @return int
 */
function gutenberg_lab_blocks_sanitize_villa_search_public_enabled( $value ) {
	return empty( $value ) ? 0 : 1;
}

/**
 * Registers the launch switch with WordPress' existing Reading settings page.
 */
function gutenberg_lab_blocks_register_villa_search_launch_setting() {
	register_setting(
		'reading',
		'gutenberg_lab_blocks_villa_search_public_enabled',
		array(
			'type'              => 'boolean',
			'default'           => false,
			'sanitize_callback' => 'gutenberg_lab_blocks_sanitize_villa_search_public_enabled',
		)
	);

	add_settings_field(
		'gutenberg_lab_blocks_villa_search_public_enabled',
		__( 'Villa search', 'gutenberg-lab-blocks' ),
		'gutenberg_lab_blocks_render_villa_search_launch_field',
		'reading',
		'default'
	);
}
add_action( 'admin_init', 'gutenberg_lab_blocks_register_villa_search_launch_setting' );

/**
 * Renders the launch checkbox on Settings > Reading.
 */
function gutenberg_lab_blocks_render_villa_search_launch_field() {
	$enabled = gutenberg_lab_blocks_is_villa_search_public_enabled();
	?>
	<label for="gutenberg-lab-blocks-villa-search-public-enabled">
		<input
			type="checkbox"
			id="gutenberg-lab-blocks-villa-search-public-enabled"
			name="gutenberg_lab_blocks_villa_search_public_enabled"
			value="1"
			<?php checked( $enabled ); ?>
		/>
		<?php esc_html_e( 'Enable the public villa search', 'gutenberg-lab-blocks' ); ?>
	</label>
	<p class="description">
		<?php esc_html_e( 'Leave this off until villa search data is complete. While off, search blocks stay hidden and /villas/ returns visitors to the homepage.', 'gutenberg-lab-blocks' ); ?>
	</p>
	<?php
}

/**
 * Keeps disabled search routes out of public circulation without caching a
 * permanent redirect. Individual villa URLs remain available.
 */
function gutenberg_lab_blocks_redirect_disabled_villa_search() {
	if (
		gutenberg_lab_blocks_is_villa_search_public_enabled() ||
		! is_post_type_archive( 'villa' )
	) {
		return;
	}

	wp_safe_redirect( home_url( '/#explore-villas' ), 302 );
	exit;
}
add_action( 'template_redirect', 'gutenberg_lab_blocks_redirect_disabled_villa_search', 1 );
