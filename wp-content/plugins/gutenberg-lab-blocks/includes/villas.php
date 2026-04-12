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
				'villa_location',
			),
		)
	);
}
add_action( 'init', 'gutenberg_lab_blocks_register_villas_post_type' );

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
		'villa_card_cta_label' => 'gutenberg_lab_villa_card_cta_label',
		'villa_card_cta_url'   => 'gutenberg_lab_villa_card_cta_url',
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

	if ( '' === $excerpt ) {
		$excerpt = wp_trim_words( wp_strip_all_tags( $villa->post_content ), 24 );
	}

	if ( '' === $image_alt && $image_id ) {
		$image_alt = get_the_title( $image_id );
	}

	return array(
		'id'        => $villa_id,
		'title'     => get_the_title( $villa_id ),
		'permalink' => get_permalink( $villa_id ),
		'excerpt'   => $excerpt,
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
		)
	);

	// The cinematic variation uses a fixed CTA label in the mock while still
	// honoring the villa-level CTA destination override.
	if ( '' !== $args['cta_label_override'] ) {
		$villa_data['cta']['label'] = $args['cta_label_override'];
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
			<h3 class="wp-block-heading">
				<a href="<?php echo esc_url( $villa_data['permalink'] ); ?>">
					<?php echo esc_html( $villa_data['title'] ); ?>
				</a>
			</h3>

			<?php if ( '' !== $villa_data['excerpt'] ) : ?>
				<p><?php echo esc_html( $villa_data['excerpt'] ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $villa_data['cta']['url'] ) && ! empty( $villa_data['cta']['label'] ) ) : ?>
				<div class="wp-block-buttons">
					<div class="wp-block-button is-style-vvm-primary">
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
					<div class="wp-block-button is-style-vvm-primary">
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
