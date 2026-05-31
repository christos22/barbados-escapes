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
				'page-attributes',
			),
			'taxonomies'   => array(
				'villa_amenity',
				'villa_location',
			),
		)
	);
}
add_action( 'init', 'gutenberg_lab_blocks_register_villas_post_type' );

/**
 * Returns the fixed icon choices for villa amenity terms.
 *
 * The selected icon belongs to the term, not the block instance. That keeps the
 * same amenity visually consistent everywhere it is reused.
 *
 * @return array<string, string>
 */
function gutenberg_lab_blocks_get_villa_amenity_icon_choices() {
	return array(
		'default'             => __( 'Default', 'gutenberg-lab-blocks' ),
		'view'                => __( 'View', 'gutenberg-lab-blocks' ),
		'ocean'               => __( 'Ocean', 'gutenberg-lab-blocks' ),
		'ocean-front'         => __( 'Ocean Front', 'gutenberg-lab-blocks' ),
		'hillside-retreat'    => __( 'Hillside Retreat', 'gutenberg-lab-blocks' ),
		'bed'                 => __( 'Bed', 'gutenberg-lab-blocks' ),
		'people'              => __( 'People', 'gutenberg-lab-blocks' ),
		'shower'              => __( 'Shower', 'gutenberg-lab-blocks' ),
		'golf'                => __( 'Golf', 'gutenberg-lab-blocks' ),
		'wellness'            => __( 'Wellness', 'gutenberg-lab-blocks' ),
		'bbq'                 => __( 'BBQ', 'gutenberg-lab-blocks' ),
		'beach-club-access'   => __( 'Beach Club Access', 'gutenberg-lab-blocks' ),
		'covered-terrace'     => __( 'Covered Terrace', 'gutenberg-lab-blocks' ),
		'desk'                => __( 'Desk', 'gutenberg-lab-blocks' ),
		'dvd'                 => __( 'DVD', 'gutenberg-lab-blocks' ),
		'ev-charging'         => __( 'EV Charging', 'gutenberg-lab-blocks' ),
		'fitness-studio'      => __( 'Fitness Studio', 'gutenberg-lab-blocks' ),
		'ice-maker'           => __( 'Ice Maker', 'gutenberg-lab-blocks' ),
		'in-room-safe'        => __( 'In-room Safe', 'gutenberg-lab-blocks' ),
		'ipod'                => __( 'iPod', 'gutenberg-lab-blocks' ),
		'ocean-view'          => __( 'Ocean View', 'gutenberg-lab-blocks' ),
		'outdoor-shower'      => __( 'Outdoor Shower', 'gutenberg-lab-blocks' ),
		'pergola'             => __( 'Pergola', 'gutenberg-lab-blocks' ),
		'pool'                => __( 'Pool', 'gutenberg-lab-blocks' ),
		'pool-bar'            => __( 'Pool Bar', 'gutenberg-lab-blocks' ),
		'poolside-washroom'   => __( 'Poolside Washroom', 'gutenberg-lab-blocks' ),
		'private-entry-gates' => __( 'Private Entry Gates', 'gutenberg-lab-blocks' ),
		'saltwater-pool'     => __( 'Saltwater Pool', 'gutenberg-lab-blocks' ),
		'sauna'              => __( 'Sauna', 'gutenberg-lab-blocks' ),
		'security-cameras'   => __( 'Security Cameras', 'gutenberg-lab-blocks' ),
		'security-system'    => __( 'Security System', 'gutenberg-lab-blocks' ),
		'speakers'           => __( 'Speakers', 'gutenberg-lab-blocks' ),
		'sun-deck'           => __( 'Sun Deck', 'gutenberg-lab-blocks' ),
		'yoga'               => __( 'Yoga', 'gutenberg-lab-blocks' ),
		'yoga-pavilion'      => __( 'Yoga Pavilion', 'gutenberg-lab-blocks' ),
	);
}

/**
 * Sanitizes amenity icon keys against the fixed icon map.
 *
 * @param string $icon_key Raw icon key.
 * @return string
 */
function gutenberg_lab_blocks_sanitize_villa_amenity_icon_key( $icon_key ) {
	$icon_key = sanitize_key( $icon_key );
	$choices  = gutenberg_lab_blocks_get_villa_amenity_icon_choices();

	return isset( $choices[ $icon_key ] ) ? $icon_key : 'default';
}

/**
 * Registers the reusable Amenity taxonomy for villa posts.
 */
function gutenberg_lab_blocks_register_villa_amenity_taxonomy() {
	register_taxonomy(
		'villa_amenity',
		array( 'villa' ),
		array(
			'labels'            => array(
				'name'                       => __( 'Villa Amenities', 'gutenberg-lab-blocks' ),
				'singular_name'              => __( 'Villa Amenity', 'gutenberg-lab-blocks' ),
				'search_items'               => __( 'Search Villa Amenities', 'gutenberg-lab-blocks' ),
				'popular_items'              => __( 'Popular Villa Amenities', 'gutenberg-lab-blocks' ),
				'all_items'                  => __( 'All Villa Amenities', 'gutenberg-lab-blocks' ),
				'edit_item'                  => __( 'Edit Villa Amenity', 'gutenberg-lab-blocks' ),
				'update_item'                => __( 'Update Villa Amenity', 'gutenberg-lab-blocks' ),
				'add_new_item'               => __( 'Add New Villa Amenity', 'gutenberg-lab-blocks' ),
				'new_item_name'              => __( 'New Villa Amenity Name', 'gutenberg-lab-blocks' ),
				'separate_items_with_commas' => __( 'Separate amenities with commas', 'gutenberg-lab-blocks' ),
				'add_or_remove_items'        => __( 'Add or remove amenities', 'gutenberg-lab-blocks' ),
				'choose_from_most_used'      => __( 'Choose from the most used amenities', 'gutenberg-lab-blocks' ),
				'not_found'                  => __( 'No amenities found.', 'gutenberg-lab-blocks' ),
				'menu_name'                  => __( 'Amenities', 'gutenberg-lab-blocks' ),
			),
			'public'            => true,
			'hierarchical'      => false,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'query_var'         => 'villa_amenity',
			'rewrite'           => array(
				'slug' => 'villa-amenity',
			),
		)
	);
}
add_action( 'init', 'gutenberg_lab_blocks_register_villa_amenity_taxonomy' );

/**
 * Registers the icon key stored on each amenity term.
 */
function gutenberg_lab_blocks_register_villa_amenity_meta() {
	register_term_meta(
		'villa_amenity',
		'villa_amenity_icon',
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => 'default',
			'sanitize_callback' => 'gutenberg_lab_blocks_sanitize_villa_amenity_icon_key',
			'show_in_rest'      => true,
		)
	);
}
add_action( 'init', 'gutenberg_lab_blocks_register_villa_amenity_meta' );

/**
 * Renders the amenity icon picker field on the add-term screen.
 */
function gutenberg_lab_blocks_render_villa_amenity_add_icon_field() {
	?>
	<div class="form-field term-villa-amenity-icon-wrap">
		<label for="gutenberg-lab-villa-amenity-icon">
			<?php esc_html_e( 'Amenity icon', 'gutenberg-lab-blocks' ); ?>
		</label>
		<select id="gutenberg-lab-villa-amenity-icon" name="gutenberg_lab_villa_amenity_icon">
			<?php foreach ( gutenberg_lab_blocks_get_villa_amenity_icon_choices() as $icon_key => $icon_label ) : ?>
				<option value="<?php echo esc_attr( $icon_key ); ?>">
					<?php echo esc_html( $icon_label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p>
			<?php esc_html_e( 'The term name is the label visitors see; this icon is reused anywhere the amenity appears.', 'gutenberg-lab-blocks' ); ?>
		</p>
	</div>
	<?php
}
add_action( 'villa_amenity_add_form_fields', 'gutenberg_lab_blocks_render_villa_amenity_add_icon_field' );

/**
 * Renders the amenity icon picker field on the edit-term screen.
 *
 * @param WP_Term $term Current amenity term.
 */
function gutenberg_lab_blocks_render_villa_amenity_edit_icon_field( $term ) {
	$selected_icon = gutenberg_lab_blocks_sanitize_villa_amenity_icon_key(
		(string) get_term_meta( $term->term_id, 'villa_amenity_icon', true )
	);
	?>
	<tr class="form-field term-villa-amenity-icon-wrap">
		<th scope="row">
			<label for="gutenberg-lab-villa-amenity-icon">
				<?php esc_html_e( 'Amenity icon', 'gutenberg-lab-blocks' ); ?>
			</label>
		</th>
		<td>
			<select id="gutenberg-lab-villa-amenity-icon" name="gutenberg_lab_villa_amenity_icon">
				<?php foreach ( gutenberg_lab_blocks_get_villa_amenity_icon_choices() as $icon_key => $icon_label ) : ?>
					<option
						value="<?php echo esc_attr( $icon_key ); ?>"
						<?php selected( $selected_icon, $icon_key ); ?>
					>
						<?php echo esc_html( $icon_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p class="description">
				<?php esc_html_e( 'The term name is the label visitors see; this icon is reused anywhere the amenity appears.', 'gutenberg-lab-blocks' ); ?>
			</p>
		</td>
	</tr>
	<?php
}
add_action( 'villa_amenity_edit_form_fields', 'gutenberg_lab_blocks_render_villa_amenity_edit_icon_field' );

/**
 * Saves the selected icon on amenity term create/update.
 *
 * @param int $term_id Current amenity term ID.
 */
function gutenberg_lab_blocks_save_villa_amenity_icon_field( $term_id ) {
	if ( ! isset( $_POST['gutenberg_lab_villa_amenity_icon'] ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}

	$icon_key = gutenberg_lab_blocks_sanitize_villa_amenity_icon_key(
		wp_unslash( $_POST['gutenberg_lab_villa_amenity_icon'] )
	);

	update_term_meta( $term_id, 'villa_amenity_icon', $icon_key );
}
add_action( 'created_villa_amenity', 'gutenberg_lab_blocks_save_villa_amenity_icon_field' );
add_action( 'edited_villa_amenity', 'gutenberg_lab_blocks_save_villa_amenity_icon_field' );

/**
 * Returns the registered villa meta schema.
 *
 * Keeping the CTA override in explicit post meta makes the card-grid query
 * deterministic while defaulting cleanly back to the villa permalink.
 *
 * @return array<string, array<string, mixed>>
 */
function gutenberg_lab_blocks_get_villa_meta_schema() {
	return array(
		'villa_card_eyebrow'    => array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'villa_card_descriptor' => array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'villa_card_facts'      => array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'villa_card_cta_label' => array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'villa_card_cta_url'   => array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
		),
	);
}

/**
 * Registers the Villa meta fields used by the dynamic card grid.
 */
function gutenberg_lab_blocks_register_villa_meta() {
	foreach ( gutenberg_lab_blocks_get_villa_meta_schema() as $meta_key => $meta_args ) {
		register_post_meta(
			'villa',
			$meta_key,
			array_merge(
				$meta_args,
				array(
					'show_in_rest' => true,
					'single'       => true,
				)
			)
		);
	}
}
add_action( 'init', 'gutenberg_lab_blocks_register_villa_meta' );

/**
 * Adds the focused villa CTA meta box instead of generic custom fields.
 */
function gutenberg_lab_blocks_add_villa_meta_boxes() {
	add_meta_box(
		'gutenberg-lab-villa-card-content',
		__( 'Card Content', 'gutenberg-lab-blocks' ),
		'gutenberg_lab_blocks_render_villa_card_content_meta_box',
		'villa',
		'normal',
		'default'
	);

	add_meta_box(
		'gutenberg-lab-villa-card-cta',
		__( 'Card CTA', 'gutenberg-lab-blocks' ),
		'gutenberg_lab_blocks_render_villa_card_cta_meta_box',
		'villa',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes_villa', 'gutenberg_lab_blocks_add_villa_meta_boxes' );

/**
 * Renders the reusable card text fields.
 *
 * These fields keep post-driven card grids editable without hardcoding copy in
 * the block instance that happens to display the villa.
 *
 * @param WP_Post $post Current villa post object.
 */
function gutenberg_lab_blocks_render_villa_card_content_meta_box( $post ) {
	$eyebrow    = get_post_meta( $post->ID, 'villa_card_eyebrow', true );
	$descriptor = get_post_meta( $post->ID, 'villa_card_descriptor', true );
	$facts      = get_post_meta( $post->ID, 'villa_card_facts', true );

	wp_nonce_field( 'gutenberg_lab_blocks_save_villa_fields', 'gutenberg_lab_blocks_villa_fields_nonce' );
	?>
	<p>
		<label for="gutenberg-lab-villa-card-eyebrow">
			<?php esc_html_e( 'Card eyebrow', 'gutenberg-lab-blocks' ); ?>
		</label>
	</p>
	<input
		type="text"
		id="gutenberg-lab-villa-card-eyebrow"
		name="gutenberg_lab_villa_card_eyebrow"
		class="widefat"
		value="<?php echo esc_attr( $eyebrow ); ?>"
		placeholder="<?php echo esc_attr( gutenberg_lab_blocks_get_villa_card_default_eyebrow() ); ?>"
	/>
	<p class="description">
		<?php esc_html_e( 'Small label above the villa title on collection-style cards. Leave blank to use the default.', 'gutenberg-lab-blocks' ); ?>
	</p>

	<p>
		<label for="gutenberg-lab-villa-card-descriptor">
			<?php esc_html_e( 'Card descriptor', 'gutenberg-lab-blocks' ); ?>
		</label>
	</p>
	<input
		type="text"
		id="gutenberg-lab-villa-card-descriptor"
		name="gutenberg_lab_villa_card_descriptor"
		class="widefat"
		value="<?php echo esc_attr( $descriptor ); ?>"
		placeholder="<?php echo esc_attr__( 'Luxury Oceanfront Estate - West Coast, St James', 'gutenberg-lab-blocks' ); ?>"
	/>
	<p class="description">
		<?php esc_html_e( 'Short location or positioning line. Leave blank to use the villa excerpt.', 'gutenberg-lab-blocks' ); ?>
	</p>

	<p>
		<label for="gutenberg-lab-villa-card-facts">
			<?php esc_html_e( 'Card facts', 'gutenberg-lab-blocks' ); ?>
		</label>
	</p>
	<input
		type="text"
		id="gutenberg-lab-villa-card-facts"
		name="gutenberg_lab_villa_card_facts"
		class="widefat"
		value="<?php echo esc_attr( $facts ); ?>"
		placeholder="<?php echo esc_attr__( '7 Bedrooms - Sleeps 12 - From $1,300/night', 'gutenberg-lab-blocks' ); ?>"
	/>
	<p class="description">
		<?php esc_html_e( 'Compact facts line used by collection-style villa cards.', 'gutenberg-lab-blocks' ); ?>
	</p>
	<?php
}

/**
 * Renders the villa CTA meta fields.
 *
 * @param WP_Post $post Current villa post object.
 */
function gutenberg_lab_blocks_render_villa_card_cta_meta_box( $post ) {
	$cta_label = get_post_meta( $post->ID, 'villa_card_cta_label', true );
	$cta_url   = get_post_meta( $post->ID, 'villa_card_cta_url', true );

	wp_nonce_field( 'gutenberg_lab_blocks_save_villa_fields', 'gutenberg_lab_blocks_villa_fields_nonce' );
	?>
	<p>
		<label for="gutenberg-lab-villa-card-cta-label">
			<?php esc_html_e( 'CTA Label', 'gutenberg-lab-blocks' ); ?>
		</label>
	</p>
	<input
		type="text"
		id="gutenberg-lab-villa-card-cta-label"
		name="gutenberg_lab_villa_card_cta_label"
		class="widefat"
		value="<?php echo esc_attr( $cta_label ); ?>"
		placeholder="<?php echo esc_attr__( 'View Villa', 'gutenberg-lab-blocks' ); ?>"
	/>
	<p class="description">
		<?php esc_html_e( 'Leave blank to use the default card label.', 'gutenberg-lab-blocks' ); ?>
	</p>

	<p>
		<label for="gutenberg-lab-villa-card-cta-url">
			<?php esc_html_e( 'CTA URL', 'gutenberg-lab-blocks' ); ?>
		</label>
	</p>
	<input
		type="url"
		id="gutenberg-lab-villa-card-cta-url"
		name="gutenberg_lab_villa_card_cta_url"
		class="widefat"
		value="<?php echo esc_attr( $cta_url ); ?>"
		placeholder="<?php echo esc_attr__( 'Leave blank to use the villa permalink.', 'gutenberg-lab-blocks' ); ?>"
	/>
	<p class="description">
		<?php esc_html_e( 'Use this to override the default permalink on villa cards.', 'gutenberg-lab-blocks' ); ?>
	</p>
	<?php
}

/**
 * Saves the focused villa CTA fields.
 *
 * @param int $post_id Current villa ID.
 */
function gutenberg_lab_blocks_save_villa_meta( $post_id ) {
	if ( ! isset( $_POST['gutenberg_lab_blocks_villa_fields_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['gutenberg_lab_blocks_villa_fields_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'gutenberg_lab_blocks_save_villa_fields' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$field_map = array(
		'villa_card_eyebrow'    => 'gutenberg_lab_villa_card_eyebrow',
		'villa_card_descriptor' => 'gutenberg_lab_villa_card_descriptor',
		'villa_card_facts'      => 'gutenberg_lab_villa_card_facts',
		'villa_card_cta_label'  => 'gutenberg_lab_villa_card_cta_label',
		'villa_card_cta_url'    => 'gutenberg_lab_villa_card_cta_url',
	);
	$meta_schema = gutenberg_lab_blocks_get_villa_meta_schema();

	foreach ( $field_map as $meta_key => $input_name ) {
		if ( ! array_key_exists( $input_name, $_POST ) ) {
			continue;
		}

		$value = wp_unslash( $_POST[ $input_name ] );

		if (
			isset( $meta_schema[ $meta_key ]['sanitize_callback'] ) &&
			is_callable( $meta_schema[ $meta_key ]['sanitize_callback'] )
		) {
			$value = call_user_func( $meta_schema[ $meta_key ]['sanitize_callback'], $value );
		}

		if ( '' === $value ) {
			delete_post_meta( $post_id, $meta_key );
			continue;
		}

		update_post_meta( $post_id, $meta_key, $value );
	}
}
add_action( 'save_post_villa', 'gutenberg_lab_blocks_save_villa_meta' );

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
 * Returns the default eyebrow label used by collection-style villa cards.
 *
 * @return string
 */
function gutenberg_lab_blocks_get_villa_card_default_eyebrow() {
	return __( 'Curated Villa', 'gutenberg-lab-blocks' );
}

/**
 * Returns the default CTA label used by villa cards.
 *
 * @return string
 */
function gutenberg_lab_blocks_get_villa_card_default_cta_label() {
	return __( 'View Villa', 'gutenberg-lab-blocks' );
}

/**
 * Returns the normalized CTA payload for one villa card.
 *
 * @param int $villa_id Villa post ID.
 * @return array<string, string>
 */
function gutenberg_lab_blocks_get_villa_card_cta( $villa_id ) {
	$cta_label = get_post_meta( $villa_id, 'villa_card_cta_label', true );
	$cta_url   = get_post_meta( $villa_id, 'villa_card_cta_url', true );
	$permalink = get_permalink( $villa_id );

	if ( '' === $cta_label ) {
		$cta_label = gutenberg_lab_blocks_get_villa_card_default_cta_label();
	}

	if ( '' === $cta_url && is_string( $permalink ) ) {
		$cta_url = $permalink;
	}

	return array(
		'label' => $cta_label,
		'url'   => is_string( $cta_url ) ? $cta_url : '',
	);
}

/**
 * Returns the normalized amenities assigned to one villa.
 *
 * @param int $villa_id Villa post ID.
 * @return array<int, array<string, string>>
 */
function gutenberg_lab_blocks_get_villa_amenities( $villa_id ) {
	$terms = wp_get_object_terms(
		$villa_id,
		'villa_amenity',
		array(
			// Keep name as the WordPress fallback; Intuitive Custom Post Order
			// filters configured villa_amenity terms into the saved admin order.
			'orderby' => 'name',
			'order'   => 'ASC',
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}

	$amenities = array();

	foreach ( $terms as $term ) {
		if ( ! $term instanceof WP_Term ) {
			continue;
		}

		$amenities[] = array(
			'id'       => (string) $term->term_id,
			'name'     => $term->name,
			'slug'     => $term->slug,
			'icon_key' => gutenberg_lab_blocks_sanitize_villa_amenity_icon_key(
				(string) get_term_meta( $term->term_id, 'villa_amenity_icon', true )
			),
		);
	}

	return $amenities;
}

/**
 * Returns custom SVG asset paths for villa amenity icons.
 *
 * These files live in the block plugin because the amenity taxonomy and icon
 * selector are plugin-owned, not theme-owned.
 *
 * @return array<string, string>
 */
function gutenberg_lab_blocks_get_villa_amenity_icon_asset_paths() {
	$asset_dir = dirname( __DIR__ ) . '/assets/icons/villa-amenities/';

	return array(
		'hillside-retreat'    => $asset_dir . 'hillside-retreat.svg',
		'ocean'               => $asset_dir . 'ocean.svg',
		'ocean-front'         => $asset_dir . 'ocean-front.svg',
		'bbq'                 => $asset_dir . 'bbq.svg',
		'beach-club-access'   => $asset_dir . 'beach-club-access.svg',
		'covered-terrace'     => $asset_dir . 'covered-terrace.svg',
		'desk'                => $asset_dir . 'desk.svg',
		'dvd'                 => $asset_dir . 'dvd.svg',
		'ev-charging'         => $asset_dir . 'ev-charging.svg',
		'fitness-studio'      => $asset_dir . 'fitness-studio.svg',
		'ice-maker'           => $asset_dir . 'ice-maker.svg',
		'in-room-safe'        => $asset_dir . 'in-room-safe.svg',
		'ipod'                => $asset_dir . 'ipod.svg',
		'ocean-view'          => $asset_dir . 'ocean-view.svg',
		'outdoor-shower'      => $asset_dir . 'outdoor-shower.svg',
		'pergola'             => $asset_dir . 'pergola.svg',
		'pool'                => $asset_dir . 'pool.svg',
		'pool-bar'            => $asset_dir . 'pool-bar.svg',
		'poolside-washroom'   => $asset_dir . 'poolside-washroom.svg',
		'private-entry-gates' => $asset_dir . 'private-entry-gates.svg',
		'saltwater-pool'     => $asset_dir . 'saltwater-pool.svg',
		'sauna'              => $asset_dir . 'sauna.svg',
		'security-cameras'   => $asset_dir . 'security-cameras.svg',
		'security-system'    => $asset_dir . 'security-system.svg',
		'speakers'           => $asset_dir . 'speakers.svg',
		'sun-deck'           => $asset_dir . 'sun-deck.svg',
		'yoga'               => $asset_dir . 'yoga.svg',
		'yoga-pavilion'      => $asset_dir . 'yoga-pavilion.svg',
	);
}

/**
 * Returns a trusted SVG asset for a villa amenity icon key.
 *
 * The source files are committed assets. We normalize them here so they behave
 * like the path-based icons: no white artboard, inherited text color, and no
 * accidental strokes from the shared icon CSS.
 *
 * @param string $icon_key Amenity icon key.
 * @return string
 */
function gutenberg_lab_blocks_get_villa_amenity_icon_asset_svg( $icon_key ) {
	$asset_paths = gutenberg_lab_blocks_get_villa_amenity_icon_asset_paths();

	if ( ! isset( $asset_paths[ $icon_key ] ) || ! is_readable( $asset_paths[ $icon_key ] ) ) {
		return '';
	}

	$svg = file_get_contents( $asset_paths[ $icon_key ] );

	if ( ! is_string( $svg ) || '' === trim( $svg ) ) {
		return '';
	}

	$classes = sprintf(
		'vvm-villa-amenity-icon vvm-villa-amenity-icon--%s',
		esc_attr( $icon_key )
	);

	$replacements = array(
		'/<\?xml.*?\?>/is'              => '',
		'/<rect\b[^>]*\/?>/i'           => '',
		'/\s(?:width|height)="[^"]*"/i' => '',
		'/\sxmlns:xlink="[^"]*"/i'      => '',
		'/<path\b(?![^>]*\bstroke=)/i'  => '<path stroke="none"',
	);

	foreach ( $replacements as $pattern => $replacement ) {
		$updated_svg = preg_replace( $pattern, $replacement, $svg );

		if ( ! is_string( $updated_svg ) ) {
			return '';
		}

		$svg = $updated_svg;
	}

	$svg = preg_replace(
		'/<svg\b/i',
		sprintf(
			'<svg class="%s" aria-hidden="true" focusable="false"',
			$classes
		),
		$svg,
		1
	);

	if ( ! is_string( $svg ) ) {
		return '';
	}

	$svg = str_replace(
		array(
			'fill="#000000"',
			'fill="#000"',
			'fill="black"',
		),
		'fill="currentColor"',
		$svg
	);

	return trim( $svg );
}

/**
 * Returns an SVG for a villa amenity icon key.
 *
 * @param string $icon_key Amenity icon key.
 * @return string
 */
function gutenberg_lab_blocks_get_villa_amenity_icon_svg( $icon_key ) {
	$icon_key = gutenberg_lab_blocks_sanitize_villa_amenity_icon_key( $icon_key );

	$asset_svg = gutenberg_lab_blocks_get_villa_amenity_icon_asset_svg( $icon_key );

	if ( '' !== $asset_svg ) {
		return $asset_svg;
	}

	$paths    = array(
		'default'  => '<circle cx="12" cy="12" r="6.5"></circle><path d="M12 5.5v13M5.5 12h13"></path>',
		'view'     => '<path d="M3.5 13c2.2-4.1 5-6.2 8.5-6.2s6.3 2.1 8.5 6.2"></path><path d="M5.8 14.5c1.7 1.8 3.8 2.7 6.2 2.7s4.5-.9 6.2-2.7"></path><path d="M8.5 12.8c.9.7 2.1 1.1 3.5 1.1s2.6-.4 3.5-1.1"></path>',
		'bed'      => '<path d="M4 11.5V7.8c0-.9.7-1.6 1.6-1.6h4.1c1 0 1.8.8 1.8 1.8v3.5"></path><path d="M11.5 11.5V9.2h5.3c1.8 0 3.2 1.4 3.2 3.2v4.1"></path><path d="M4 16.5h16"></path><path d="M4 18.5v-7"></path><path d="M20 18.5v-2"></path>',
		'people'   => '<circle cx="12" cy="7.5" r="3"></circle><path d="M7.2 18.8c.7-3 2.3-4.5 4.8-4.5s4.1 1.5 4.8 4.5"></path><circle cx="5.8" cy="10" r="2.1"></circle><path d="M2.8 18.2c.3-2 1.3-3.2 3-3.7"></path><circle cx="18.2" cy="10" r="2.1"></circle><path d="M21.2 18.2c-.3-2-1.3-3.2-3-3.7"></path>',
		'shower'   => '<path d="M8 5.5c1.1-1.5 2.5-2.2 4.1-2.2 2.7 0 4.9 2.2 4.9 4.9v1.1"></path><path d="M12 9.5h8"></path><path d="M13.2 12.8l-1 1.7"></path><path d="M16 12.8l-1 1.7"></path><path d="M18.8 12.8l-1 1.7"></path><path d="M12 18.2l-1 1.7"></path><path d="M14.8 18.2l-1 1.7"></path><path d="M17.6 18.2l-1 1.7"></path>',
		'golf'     => '<path d="M10 21V4"></path><path d="M10 4l8 2.3-8 2.4"></path><ellipse cx="10" cy="21" rx="6.5" ry="1.7"></ellipse><circle cx="17.5" cy="18.8" r="1"></circle>',
		'wellness' => '<path d="M12 19.5c-3.8-2-5.7-4.6-5.7-7.8 0-2.3 1.5-4.1 3.5-4.1 1.1 0 1.9.4 2.2 1.1.3-.7 1.1-1.1 2.2-1.1 2 0 3.5 1.8 3.5 4.1 0 3.2-1.9 5.8-5.7 7.8Z"></path><path d="M4.5 11.2c-1.3.8-2 2-2 3.6 0 2.1 1.8 3.8 4.1 3.8"></path><path d="M19.5 11.2c1.3.8 2 2 2 3.6 0 2.1-1.8 3.8-4.1 3.8"></path>',
	);

	return sprintf(
		'<svg class="vvm-villa-amenity-icon vvm-villa-amenity-icon--%1$s" viewBox="0 0 24 24" aria-hidden="true" focusable="false">%2$s</svg>',
		esc_attr( $icon_key ),
		$paths[ $icon_key ] ?? $paths['default']
	);
}

/**
 * Renders a reusable amenity list for villa cards and future villa blocks.
 *
 * @param array $amenities Normalized amenities from `gutenberg_lab_blocks_get_villa_amenities()`.
 * @param array $args      Rendering options.
 * @return string
 */
function gutenberg_lab_blocks_render_villa_amenities( $amenities, $args = array() ) {
	if ( empty( $amenities ) ) {
		return '';
	}

	$args = wp_parse_args(
		$args,
		array(
			'class_name' => 'vvm-villa-amenities',
			'limit'      => 4,
		)
	);

	$limit     = max( 1, (int) $args['limit'] );
	$amenities = array_slice( $amenities, 0, $limit );

	ob_start();
	?>
	<ul class="<?php echo esc_attr( $args['class_name'] ); ?>">
		<?php foreach ( $amenities as $amenity ) : ?>
			<li class="<?php echo esc_attr( $args['class_name'] . '__item' ); ?>">
				<span class="<?php echo esc_attr( $args['class_name'] . '__icon' ); ?>">
					<?php echo gutenberg_lab_blocks_get_villa_amenity_icon_svg( $amenity['icon_key'] ?? 'default' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</span>
				<span class="<?php echo esc_attr( $args['class_name'] . '__label' ); ?>">
					<?php echo esc_html( $amenity['name'] ?? '' ); ?>
				</span>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php

	return trim( (string) ob_get_clean() );
}

/**
 * Returns the structured data used by the villa-driven card grid.
 *
 * @param int $villa_id Villa post ID.
 * @return array<string, mixed>|null
 */
function gutenberg_lab_blocks_get_villa_data( $villa_id ) {
	$villa = get_post( $villa_id );

	if ( ! $villa instanceof WP_Post || 'villa' !== $villa->post_type ) {
		return null;
	}

	$image_id  = (int) get_post_thumbnail_id( $villa_id );
	$image_url = $image_id ? get_the_post_thumbnail_url( $villa_id, 'large' ) : '';
	$image_alt = $image_id ? get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : '';
	$excerpt   = get_the_excerpt( $villa_id );
	$eyebrow   = get_post_meta( $villa_id, 'villa_card_eyebrow', true );
	$descriptor = get_post_meta( $villa_id, 'villa_card_descriptor', true );
	$facts      = get_post_meta( $villa_id, 'villa_card_facts', true );

	if ( '' === $excerpt ) {
		$excerpt = wp_trim_words( wp_strip_all_tags( $villa->post_content ), 24 );
	}

	if ( '' === $descriptor ) {
		$descriptor = $excerpt;
	}

	if ( '' === $eyebrow ) {
		$eyebrow = gutenberg_lab_blocks_get_villa_card_default_eyebrow();
	}

	if ( '' === $image_alt && $image_id ) {
		$image_alt = get_the_title( $image_id );
	}

	return array(
		'id'        => $villa_id,
		'title'     => get_the_title( $villa_id ),
		'permalink' => get_permalink( $villa_id ),
		'excerpt'   => $excerpt,
		'eyebrow'   => $eyebrow,
		'descriptor' => $descriptor,
		'facts'     => $facts,
		'amenities' => gutenberg_lab_blocks_get_villa_amenities( $villa_id ),
		'image_url' => $image_url,
		'image_alt' => $image_alt,
		'cta'       => gutenberg_lab_blocks_get_villa_card_cta( $villa_id ),
	);
}

/**
 * Renders one villa card using the shared card-grid markup contract.
 *
 * The card-grid already owns the responsive layout. This helper just maps
 * villa fields onto the same shell so manual and queried cards stay aligned.
 *
 * @param int   $villa_id Villa post ID.
 * @param array $args     Render overrides for the active block variation.
 * @return string
 */
function gutenberg_lab_blocks_render_villa_card( $villa_id, $args = array() ) {
	$villa_data = gutenberg_lab_blocks_get_villa_data( $villa_id );

	if ( ! $villa_data ) {
		return '';
	}

	$args = wp_parse_args(
		$args,
		array(
			'cta_label_override' => '',
			'presentation'       => 'standard',
		)
	);

	// The cinematic variation uses a fixed CTA label in the mock while still
	// honoring the villa-level CTA destination override.
	if ( '' !== $args['cta_label_override'] ) {
		$villa_data['cta']['label'] = $args['cta_label_override'];
	}

	if ( 'collection' === $args['presentation'] ) {
		$villa_data['cta']['label'] = __( 'Explore Villa', 'gutenberg-lab-blocks' );
	}

	$media_classes = array(
		'vvm-card-grid__card-media',
		'' !== $villa_data['image_url']
			? 'vvm-card-grid__card-media--background'
			: 'vvm-card-grid__card-media--placeholder',
	);
	$media_styles  = '';

	if ( '' !== $villa_data['image_url'] ) {
		$media_styles = sprintf(
			"background-image:url('%s');",
			esc_url_raw( $villa_data['image_url'] )
		);
	}

	ob_start();
	?>
	<article class="vvm-card-grid__card vvm-card-grid__card--villa">
		<a
			class="<?php echo esc_attr( implode( ' ', $media_classes ) ); ?>"
			href="<?php echo esc_url( $villa_data['permalink'] ); ?>"
			aria-label="<?php echo esc_attr( sprintf( __( 'View %s', 'gutenberg-lab-blocks' ), $villa_data['title'] ) ); ?>"
			<?php if ( '' !== $media_styles ) : ?>
				style="<?php echo esc_attr( $media_styles ); ?>"
			<?php endif; ?>
		>
			<?php if ( '' === $villa_data['image_url'] ) : ?>
				<span class="vvm-card-grid__card-placeholder-label">
					<?php esc_html_e( 'Villa image coming soon', 'gutenberg-lab-blocks' ); ?>
				</span>
			<?php endif; ?>
		</a>

		<div class="vvm-card-grid__card-content">
			<?php if ( 'collection' === $args['presentation'] && '' !== $villa_data['eyebrow'] ) : ?>
				<p class="vvm-card-grid__card-eyebrow">
					<?php echo esc_html( $villa_data['eyebrow'] ); ?>
				</p>
			<?php endif; ?>

			<h3 class="wp-block-heading">
				<a href="<?php echo esc_url( $villa_data['permalink'] ); ?>">
					<?php echo esc_html( $villa_data['title'] ); ?>
				</a>
			</h3>

			<?php if ( 'collection' === $args['presentation'] && '' !== $villa_data['descriptor'] ) : ?>
				<p class="vvm-card-grid__card-descriptor"><?php echo esc_html( $villa_data['descriptor'] ); ?></p>
			<?php elseif ( '' !== $villa_data['excerpt'] ) : ?>
				<p><?php echo esc_html( $villa_data['excerpt'] ); ?></p>
			<?php endif; ?>

			<?php if ( 'collection' === $args['presentation'] && '' !== $villa_data['facts'] ) : ?>
				<p class="vvm-card-grid__card-facts"><?php echo esc_html( $villa_data['facts'] ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $villa_data['cta']['url'] ) && ! empty( $villa_data['cta']['label'] ) ) : ?>
				<div class="wp-block-buttons">
					<div class="wp-block-button is-style-vvm-ghost">
						<a
							class="wp-block-button__link wp-element-button"
							href="<?php echo esc_url( $villa_data['cta']['url'] ); ?>"
						>
							<?php echo esc_html( $villa_data['cta']['label'] ); ?>
						</a>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</article>
	<?php

	return trim( (string) ob_get_clean() );
}

/**
 * Renders one villa slide using the Card Carousel shell.
 *
 * The carousel keeps the same villa content mapping as the card grid while
 * swapping in the taller portrait card composition required by the new block.
 *
 * @param int $villa_id Villa post ID.
 * @return string
 */
function gutenberg_lab_blocks_render_villa_carousel_slide( $villa_id ) {
	$villa_data = gutenberg_lab_blocks_get_villa_data( $villa_id );

	if ( ! $villa_data ) {
		return '';
	}

	$has_image = '' !== $villa_data['image_url'];

	ob_start();
	?>
	<article class="vvm-card-carousel__slide vvm-card-carousel__slide--villa">
		<div class="vvm-card-carousel__slide-media<?php echo $has_image ? '' : ' vvm-card-carousel__slide-media--placeholder'; ?>">
			<?php if ( $has_image ) : ?>
				<img
					class="vvm-card-carousel__slide-image"
					src="<?php echo esc_url( $villa_data['image_url'] ); ?>"
					alt="<?php echo esc_attr( $villa_data['image_alt'] ); ?>"
				/>
			<?php else : ?>
				<span class="vvm-card-carousel__slide-placeholder-label">
					<?php esc_html_e( 'Villa image coming soon', 'gutenberg-lab-blocks' ); ?>
				</span>
			<?php endif; ?>
		</div>

		<div class="vvm-card-carousel__slide-content">
			<h3 class="wp-block-heading"><?php echo esc_html( $villa_data['title'] ); ?></h3>

			<?php if ( '' !== $villa_data['excerpt'] ) : ?>
				<p><?php echo esc_html( $villa_data['excerpt'] ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $villa_data['cta']['url'] ) && ! empty( $villa_data['cta']['label'] ) ) : ?>
				<div class="wp-block-buttons">
					<div class="wp-block-button is-style-vvm-ghost">
						<a
							class="wp-block-button__link wp-element-button"
							href="<?php echo esc_url( $villa_data['cta']['url'] ); ?>"
						>
							<?php echo esc_html( $villa_data['cta']['label'] ); ?>
						</a>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</article>
	<?php

	return trim( (string) ob_get_clean() );
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
