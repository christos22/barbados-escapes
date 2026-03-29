<?php
/**
 * Villa content model and hero-search helpers.
 *
 * @package GutenbergLabBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the Villas content type used by the homepage hero search.
 *
 * We keep the first version intentionally small: a real CPT plus a structured
 * location taxonomy. That gives us a native archive target for the GET form
 * now, while leaving room to extend the search contract later.
 */
function gutenberg_lab_blocks_register_villas_post_type() {
	register_post_type(
		'villa',
		array(
			'labels'       => array(
				'name'                  => __( 'Villas', 'gutenberg-lab-blocks' ),
				'singular_name'         => __( 'Villa', 'gutenberg-lab-blocks' ),
				'add_new'               => __( 'Add Villa', 'gutenberg-lab-blocks' ),
				'add_new_item'          => __( 'Add New Villa', 'gutenberg-lab-blocks' ),
				'edit_item'             => __( 'Edit Villa', 'gutenberg-lab-blocks' ),
				'new_item'              => __( 'New Villa', 'gutenberg-lab-blocks' ),
				'view_item'             => __( 'View Villa', 'gutenberg-lab-blocks' ),
				'view_items'            => __( 'View Villas', 'gutenberg-lab-blocks' ),
				'search_items'          => __( 'Search Villas', 'gutenberg-lab-blocks' ),
				'not_found'             => __( 'No villas found.', 'gutenberg-lab-blocks' ),
				'not_found_in_trash'    => __( 'No villas found in Trash.', 'gutenberg-lab-blocks' ),
				'all_items'             => __( 'Villas', 'gutenberg-lab-blocks' ),
				'archives'              => __( 'Villa Archives', 'gutenberg-lab-blocks' ),
				'attributes'            => __( 'Villa Attributes', 'gutenberg-lab-blocks' ),
				'insert_into_item'      => __( 'Insert into villa', 'gutenberg-lab-blocks' ),
				'uploaded_to_this_item' => __( 'Uploaded to this villa', 'gutenberg-lab-blocks' ),
				'featured_image'        => __( 'Villa Image', 'gutenberg-lab-blocks' ),
				'set_featured_image'    => __( 'Set villa image', 'gutenberg-lab-blocks' ),
				'remove_featured_image' => __( 'Remove villa image', 'gutenberg-lab-blocks' ),
				'use_featured_image'    => __( 'Use as villa image', 'gutenberg-lab-blocks' ),
				'menu_name'             => __( 'Villas', 'gutenberg-lab-blocks' ),
				'filter_items_list'     => __( 'Filter villas list', 'gutenberg-lab-blocks' ),
				'items_list_navigation' => __( 'Villas list navigation', 'gutenberg-lab-blocks' ),
				'items_list'            => __( 'Villas list', 'gutenberg-lab-blocks' ),
			),
			'public'       => true,
			'show_in_rest' => true,
			'has_archive'  => true,
			'rewrite'      => array(
				'slug' => 'villas',
			),
			'menu_icon'    => 'dashicons-admin-home',
			'supports'     => array(
				'title',
				'editor',
				'excerpt',
				'thumbnail',
			),
			'taxonomies'   => array(
				'villa_location',
			),
		)
	);
}
add_action( 'init', 'gutenberg_lab_blocks_register_villas_post_type' );

/**
 * Registers the structured location taxonomy for Villas.
 *
 * A taxonomy is the most WordPress-native way to power the first search field
 * because it gives us term management, archive filtering, and REST visibility
 * without custom tables or bespoke option lists.
 */
function gutenberg_lab_blocks_register_villa_location_taxonomy() {
	register_taxonomy(
		'villa_location',
		array( 'villa' ),
		array(
			'labels'            => array(
				'name'              => __( 'Villa Locations', 'gutenberg-lab-blocks' ),
				'singular_name'     => __( 'Villa Location', 'gutenberg-lab-blocks' ),
				'search_items'      => __( 'Search Villa Locations', 'gutenberg-lab-blocks' ),
				'all_items'         => __( 'All Villa Locations', 'gutenberg-lab-blocks' ),
				'parent_item'       => __( 'Parent Villa Location', 'gutenberg-lab-blocks' ),
				'parent_item_colon' => __( 'Parent Villa Location:', 'gutenberg-lab-blocks' ),
				'edit_item'         => __( 'Edit Villa Location', 'gutenberg-lab-blocks' ),
				'update_item'       => __( 'Update Villa Location', 'gutenberg-lab-blocks' ),
				'add_new_item'      => __( 'Add New Villa Location', 'gutenberg-lab-blocks' ),
				'new_item_name'     => __( 'New Villa Location Name', 'gutenberg-lab-blocks' ),
				'menu_name'         => __( 'Locations', 'gutenberg-lab-blocks' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'query_var'         => 'villa_location',
			'rewrite'           => array(
				'slug' => 'villa-location',
			),
		)
	);
}
add_action( 'init', 'gutenberg_lab_blocks_register_villa_location_taxonomy' );

/**
 * Returns the selected villa location slug from the current request.
 *
 * The hero search uses a plain GET form, so the frontend and the editor preview
 * both need a single source of truth for the active location value.
 *
 * @return string
 */
function gutenberg_lab_blocks_get_selected_villa_location_slug() {
	$selected_location = get_query_var( 'villa_location' );

	if ( is_string( $selected_location ) && '' !== $selected_location ) {
		return sanitize_title( $selected_location );
	}

	if ( isset( $_GET['villa_location'] ) ) {
		return sanitize_title( wp_unslash( $_GET['villa_location'] ) );
	}

	return '';
}

/**
 * Returns the published villa location terms used by the hero search.
 *
 * @return WP_Term[]
 */
function gutenberg_lab_blocks_get_villa_location_terms() {
	static $terms = null;

	if ( null !== $terms ) {
		return $terms;
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'villa_location',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $terms ) ) {
		$terms = array();
	}

	return $terms;
}

/**
 * Renders the shared hero search form markup.
 *
 * Keeping the form rendering in PHP means the archive template and the block
 * preview both stay in sync with the live term list and active query value.
 *
 * @param array  $attributes         Block attributes.
 * @param string $wrapper_attributes Wrapper attributes from `get_block_wrapper_attributes()`.
 * @return string
 */
function gutenberg_lab_blocks_render_villa_hero_search_markup( $attributes = array(), $wrapper_attributes = '' ) {
	$button_label        = isset( $attributes['buttonLabel'] ) && is_string( $attributes['buttonLabel'] )
		? sanitize_text_field( $attributes['buttonLabel'] )
		: __( 'Search', 'gutenberg-lab-blocks' );
	$location_placeholder = isset( $attributes['locationPlaceholder'] ) && is_string( $attributes['locationPlaceholder'] )
		? sanitize_text_field( $attributes['locationPlaceholder'] )
		: __( 'Search by Area', 'gutenberg-lab-blocks' );
	$locations            = gutenberg_lab_blocks_get_villa_location_terms();
	$selected_location    = gutenberg_lab_blocks_get_selected_villa_location_slug();
	$archive_url          = get_post_type_archive_link( 'villa' );
	$field_id             = wp_unique_id( 'villa-hero-search-field-' );
	$has_locations        = ! empty( $locations );
	$empty_state_label    = __( 'Add villa locations to enable search.', 'gutenberg-lab-blocks' );

	if ( ! $archive_url ) {
		$archive_url = home_url( '/villas/' );
	}

	ob_start();
	?>
	<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<form class="vvm-villa-hero-search__form" action="<?php echo esc_url( $archive_url ); ?>" method="get">
			<div class="vvm-villa-hero-search__field">
				<label class="screen-reader-text" for="<?php echo esc_attr( $field_id ); ?>">
					<?php esc_html_e( 'Villa location', 'gutenberg-lab-blocks' ); ?>
				</label>
				<select
					id="<?php echo esc_attr( $field_id ); ?>"
					name="villa_location"
					class="vvm-villa-hero-search__select"
					<?php disabled( ! $has_locations ); ?>
				>
					<option value="">
						<?php echo esc_html( $has_locations ? $location_placeholder : $empty_state_label ); ?>
					</option>
					<?php foreach ( $locations as $location ) : ?>
						<option
							value="<?php echo esc_attr( $location->slug ); ?>"
							<?php selected( $selected_location, $location->slug ); ?>
						>
							<?php echo esc_html( $location->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="vvm-villa-hero-search__actions">
				<button
					class="vvm-villa-hero-search__submit"
					type="submit"
					aria-label="<?php echo esc_attr( $button_label ); ?>"
					<?php disabled( ! $has_locations ); ?>
				>
					<span class="vvm-villa-hero-search__submit-label">
						<?php echo esc_html( $button_label ); ?>
					</span>
					<span class="vvm-villa-hero-search__submit-icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" focusable="false">
							<circle cx="11" cy="11" r="6.5"></circle>
							<path d="M16 16l4 4"></path>
						</svg>
					</span>
				</button>
			</div>
		</form>
	</section>
	<?php

	return (string) ob_get_clean();
}

/**
 * Registers a reusable homepage hero pattern that composes the media shell and search block.
 *
 * The pattern gives editors a ready-made starting point while preserving the
 * Gutenberg principle that structure is assembled from blocks instead of being
 * hidden inside a monolithic custom hero.
 */
function gutenberg_lab_blocks_register_villa_hero_pattern() {
	if ( ! function_exists( 'register_block_pattern' ) ) {
		return;
	}

	register_block_pattern(
		'gutenberg-lab-blocks/villa-home-hero',
		array(
			'title'       => __( 'Villa Home Hero', 'gutenberg-lab-blocks' ),
			'description' => __( 'A full-screen homepage hero with a centered headline and villa search bar.', 'gutenberg-lab-blocks' ),
			'categories'  => array( 'featured' ),
			'content'     =>
				'<!-- wp:gutenberg-lab-blocks/media-panel {"align":"full","className":"vvm-media-panel--villa-hero","mediaType":"image","darkOverlay":true,"containerHeight":"full","contentPosition":"bottom-center","contentWidth":"lg"} -->' .
				'<!-- wp:heading {"textAlign":"center","level":1,"fontSize":"six-xl"} --><h1 class="wp-block-heading has-text-align-center has-six-xl-font-size">' . esc_html__( 'Your Perfect Place in Barbados', 'gutenberg-lab-blocks' ) . '</h1><!-- /wp:heading -->' .
				'<!-- wp:gutenberg-lab-blocks/villa-hero-search /-->' .
				'<!-- /wp:gutenberg-lab-blocks/media-panel -->',
		)
	);
}
add_action( 'init', 'gutenberg_lab_blocks_register_villa_hero_pattern', 20 );
