<?php
/**
 * Packages custom post type, taxonomy, and editor meta registration.
 *
 * @package GutenbergLabBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the supported package CTA kinds.
 *
 * @return array<string, string>
 */
function gutenberg_lab_blocks_get_package_cta_kind_choices() {
	return array(
		'purchase' => __( 'Purchase', 'gutenberg-lab-blocks' ),
		'book'     => __( 'Book', 'gutenberg-lab-blocks' ),
		'call'     => __( 'Call', 'gutenberg-lab-blocks' ),
		'custom'   => __( 'Custom', 'gutenberg-lab-blocks' ),
	);
}

/**
 * Sanitizes a CTA kind to the supported option set.
 *
 * @param string $cta_kind Requested CTA kind.
 * @return string
 */
function gutenberg_lab_blocks_sanitize_package_cta_kind( $cta_kind ) {
	$cta_kind = is_string( $cta_kind ) ? sanitize_key( $cta_kind ) : '';
	$choices  = gutenberg_lab_blocks_get_package_cta_kind_choices();

	if ( isset( $choices[ $cta_kind ] ) ) {
		return $cta_kind;
	}

	return 'custom';
}

/**
 * Returns the registered package meta schema.
 *
 * @return array<string, array<string, mixed>>
 */
function gutenberg_lab_blocks_get_package_meta_schema() {
	return array(
		'package_price'               => array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'package_primary_cta_label'   => array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'package_primary_cta_url'     => array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
		),
		'package_primary_cta_kind'    => array(
			'type'              => 'string',
			'default'           => 'purchase',
			'sanitize_callback' => 'gutenberg_lab_blocks_sanitize_package_cta_kind',
		),
		'package_secondary_cta_label' => array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'package_secondary_cta_url'   => array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
		),
		'package_secondary_cta_kind'  => array(
			'type'              => 'string',
			'default'           => 'custom',
			'sanitize_callback' => 'gutenberg_lab_blocks_sanitize_package_cta_kind',
		),
	);
}

/**
 * Registers the Packages content type used by block-driven listings.
 *
 * We keep this as a real post type so editors get native revisioning, URLs,
 * REST support, and the built-in excerpt UI without inventing custom tables.
 */
function gutenberg_lab_blocks_get_packages_post_template() {
	return array(
		array(
			'gutenberg-lab-blocks/media-panel',
			array(
				'align'            => 'full',
				'containerHeight'  => 'large',
				'contentPosition'  => 'center-center',
				'contentWidth'     => 'md',
			),
			array(
				array(
					'core/heading',
					array(
						'level'       => 1,
						'placeholder' => __( 'Add the package hero heading...', 'gutenberg-lab-blocks' ),
					)
				),
				array(
					'core/paragraph',
					array(
						'placeholder' => __( 'Add the hero summary text...', 'gutenberg-lab-blocks' ),
					)
				),
				array(
					'gutenberg-lab-blocks/package-meta',
					array(
						'variant' => 'hero',
					)
				),
			),
		),
		array(
			'gutenberg-lab-blocks/basic-content',
			array(
				'withSidebar'      => false,
				'contentWidth'     => '100_percent',
				'contentAlignment' => 'left',
				'spacingTop'       => 'medium',
				'spacingBottom'    => 'medium',
			),
		),
		array(
			'gutenberg-lab-blocks/packages-display',
			array(
				'heading'         => __( 'More Day Spa Packages', 'gutenberg-lab-blocks' ),
				'introText'       => __( 'Explore other restorative day-away experiences built from the same shared package system.', 'gutenberg-lab-blocks' ),
				'displayMode'     => 'carousel',
				'count'           => 6,
				'columns'         => '3',
				'excludeCurrent'  => true,
				'showPackageType' => true,
				'showExcerpt'     => true,
				'showPrice'       => true,
				'showCta'         => false,
				'align'           => 'wide',
			),
		),
		array(
			'core/buttons',
			array(
				'layout' => array(
					'type'            => 'flex',
					'justifyContent'  => 'center',
				),
				'style'  => array(
					'spacing' => array(
						'margin' => array(
							'top'    => 'var:preset|spacing|lg',
							'bottom' => 'var:preset|spacing|xl',
						),
					),
				),
			),
			array(
				array(
					'core/button',
					array(
						'text'      => __( 'View All Packages', 'gutenberg-lab-blocks' ),
						'url'       => '/packages/',
						'className' => 'is-style-vvm-primary',
					)
				),
			),
		),
	);
}

/**
 * Registers the Packages content type used by block-driven listings.
 *
 * We keep this as a real post type so editors get native revisioning, URLs,
 * REST support, and the built-in excerpt UI without inventing custom tables.
 */
function gutenberg_lab_blocks_register_packages_post_type() {
	register_post_type(
		'packages',
		array(
			'labels' => array(
				'name'                  => __( 'Packages', 'gutenberg-lab-blocks' ),
				'singular_name'         => __( 'Package', 'gutenberg-lab-blocks' ),
				'add_new'               => __( 'Add Package', 'gutenberg-lab-blocks' ),
				'add_new_item'          => __( 'Add New Package', 'gutenberg-lab-blocks' ),
				'edit_item'             => __( 'Edit Package', 'gutenberg-lab-blocks' ),
				'new_item'              => __( 'New Package', 'gutenberg-lab-blocks' ),
				'view_item'             => __( 'View Package', 'gutenberg-lab-blocks' ),
				'view_items'            => __( 'View Packages', 'gutenberg-lab-blocks' ),
				'search_items'          => __( 'Search Packages', 'gutenberg-lab-blocks' ),
				'not_found'             => __( 'No packages found.', 'gutenberg-lab-blocks' ),
				'not_found_in_trash'    => __( 'No packages found in Trash.', 'gutenberg-lab-blocks' ),
				'all_items'             => __( 'Packages', 'gutenberg-lab-blocks' ),
				'archives'              => __( 'Package Archives', 'gutenberg-lab-blocks' ),
				'attributes'            => __( 'Package Attributes', 'gutenberg-lab-blocks' ),
				'insert_into_item'      => __( 'Insert into package', 'gutenberg-lab-blocks' ),
				'uploaded_to_this_item' => __( 'Uploaded to this package', 'gutenberg-lab-blocks' ),
				'featured_image'        => __( 'Package Image', 'gutenberg-lab-blocks' ),
				'set_featured_image'    => __( 'Set package image', 'gutenberg-lab-blocks' ),
				'remove_featured_image' => __( 'Remove package image', 'gutenberg-lab-blocks' ),
				'use_featured_image'    => __( 'Use as package image', 'gutenberg-lab-blocks' ),
				'menu_name'             => __( 'Packages', 'gutenberg-lab-blocks' ),
				'filter_items_list'     => __( 'Filter packages list', 'gutenberg-lab-blocks' ),
				'items_list_navigation' => __( 'Packages list navigation', 'gutenberg-lab-blocks' ),
				'items_list'            => __( 'Packages list', 'gutenberg-lab-blocks' ),
			),
			'public'       => true,
			'show_in_rest' => true,
				// Designers expect the Packages landing experience to live under
				// Pages, so a real Page owns `/packages/` instead of the CPT archive.
				'has_archive'  => false,
			'menu_icon'    => 'dashicons-products',
			'rewrite'      => array(
				'slug' => 'packages',
			),
			'supports'     => array(
				'title',
				'editor',
				'excerpt',
				'thumbnail',
				'page-attributes',
				'revisions',
			),
			'template'     => gutenberg_lab_blocks_get_packages_post_template(),
		)
	);
}
add_action( 'init', 'gutenberg_lab_blocks_register_packages_post_type' );

/**
 * Registers the package taxonomy used for labels and future filtering.
 */
function gutenberg_lab_blocks_register_package_type_taxonomy() {
	register_taxonomy(
		'package_type',
		array( 'packages' ),
		array(
			'labels'            => array(
				'name'          => __( 'Package Types', 'gutenberg-lab-blocks' ),
				'singular_name' => __( 'Package Type', 'gutenberg-lab-blocks' ),
				'search_items'  => __( 'Search Package Types', 'gutenberg-lab-blocks' ),
				'all_items'     => __( 'All Package Types', 'gutenberg-lab-blocks' ),
				'edit_item'     => __( 'Edit Package Type', 'gutenberg-lab-blocks' ),
				'update_item'   => __( 'Update Package Type', 'gutenberg-lab-blocks' ),
				'add_new_item'  => __( 'Add New Package Type', 'gutenberg-lab-blocks' ),
				'new_item_name' => __( 'New Package Type Name', 'gutenberg-lab-blocks' ),
				'menu_name'     => __( 'Package Types', 'gutenberg-lab-blocks' ),
			),
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'hierarchical'      => true,
			'rewrite'           => array(
				'slug' => 'package-type',
			),
		)
	);
}
add_action( 'init', 'gutenberg_lab_blocks_register_package_type_taxonomy' );

/**
 * Registers package meta fields for REST and template access.
 */
function gutenberg_lab_blocks_register_packages_meta() {
	foreach ( gutenberg_lab_blocks_get_package_meta_schema() as $meta_key => $meta_args ) {
		register_post_meta(
			'packages',
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
add_action( 'init', 'gutenberg_lab_blocks_register_packages_meta' );

/**
 * Adds focused package fields instead of relying on generic custom fields.
 */
function gutenberg_lab_blocks_add_packages_meta_box() {
	add_meta_box(
		'gutenberg-lab-package-price',
		__( 'Package Price', 'gutenberg-lab-blocks' ),
		'gutenberg_lab_blocks_render_packages_meta_box',
		'packages',
		'side',
		'default'
	);

	add_meta_box(
		'gutenberg-lab-package-ctas',
		__( 'Package CTAs', 'gutenberg-lab-blocks' ),
		'gutenberg_lab_blocks_render_package_cta_meta_box',
		'packages',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes_packages', 'gutenberg_lab_blocks_add_packages_meta_box' );

/**
 * Renders the package price input.
 *
 * @param WP_Post $post Current package post object.
 */
function gutenberg_lab_blocks_render_packages_meta_box( $post ) {
	$package_price = get_post_meta( $post->ID, 'package_price', true );

	wp_nonce_field( 'gutenberg_lab_blocks_save_package_fields', 'gutenberg_lab_blocks_package_fields_nonce' );
	?>
	<p>
		<label for="gutenberg-lab-package-price-field">
			<?php esc_html_e( 'Price', 'gutenberg-lab-blocks' ); ?>
		</label>
	</p>
	<input
		type="text"
		id="gutenberg-lab-package-price-field"
		name="gutenberg_lab_package_price"
		class="widefat"
		value="<?php echo esc_attr( $package_price ); ?>"
		placeholder="<?php echo esc_attr__( '$199', 'gutenberg-lab-blocks' ); ?>"
	/>
	<p class="description">
		<?php esc_html_e( 'Store the display-ready package price here so blocks and templates can read it from post meta.', 'gutenberg-lab-blocks' ); ?>
	</p>
	<?php
}

/**
 * Renders the CTA meta box fields.
 *
 * @param WP_Post $post Current package post object.
 */
function gutenberg_lab_blocks_render_package_cta_meta_box( $post ) {
	$primary_label   = get_post_meta( $post->ID, 'package_primary_cta_label', true );
	$primary_url     = get_post_meta( $post->ID, 'package_primary_cta_url', true );
	$primary_kind    = get_post_meta( $post->ID, 'package_primary_cta_kind', true );
	$secondary_label = get_post_meta( $post->ID, 'package_secondary_cta_label', true );
	$secondary_url   = get_post_meta( $post->ID, 'package_secondary_cta_url', true );
	$secondary_kind  = get_post_meta( $post->ID, 'package_secondary_cta_kind', true );
	$choices         = gutenberg_lab_blocks_get_package_cta_kind_choices();

	if ( '' === $primary_kind ) {
		$primary_kind = 'purchase';
	}

	if ( '' === $secondary_kind ) {
		$secondary_kind = 'custom';
	}
	?>
	<p>
		<label for="gutenberg-lab-package-primary-cta-label">
			<?php esc_html_e( 'Primary CTA Label', 'gutenberg-lab-blocks' ); ?>
		</label>
	</p>
	<input
		type="text"
		id="gutenberg-lab-package-primary-cta-label"
		name="gutenberg_lab_package_primary_cta_label"
		class="widefat"
		value="<?php echo esc_attr( $primary_label ); ?>"
		placeholder="<?php echo esc_attr__( 'Purchase Now', 'gutenberg-lab-blocks' ); ?>"
	/>
	<p>
		<label for="gutenberg-lab-package-primary-cta-url">
			<?php esc_html_e( 'Primary CTA URL', 'gutenberg-lab-blocks' ); ?>
		</label>
	</p>
	<input
		type="url"
		id="gutenberg-lab-package-primary-cta-url"
		name="gutenberg_lab_package_primary_cta_url"
		class="widefat"
		value="<?php echo esc_attr( $primary_url ); ?>"
		placeholder="https://"
	/>
	<p>
		<label for="gutenberg-lab-package-primary-cta-kind">
			<?php esc_html_e( 'Primary CTA Type', 'gutenberg-lab-blocks' ); ?>
		</label>
	</p>
	<select
		id="gutenberg-lab-package-primary-cta-kind"
		name="gutenberg_lab_package_primary_cta_kind"
		class="widefat"
	>
		<?php foreach ( $choices as $value => $label ) : ?>
			<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $primary_kind, $value ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>
		<?php endforeach; ?>
	</select>

	<hr />

	<p>
		<label for="gutenberg-lab-package-secondary-cta-label">
			<?php esc_html_e( 'Secondary CTA Label', 'gutenberg-lab-blocks' ); ?>
		</label>
	</p>
	<input
		type="text"
		id="gutenberg-lab-package-secondary-cta-label"
		name="gutenberg_lab_package_secondary_cta_label"
		class="widefat"
		value="<?php echo esc_attr( $secondary_label ); ?>"
		placeholder="<?php echo esc_attr__( 'Learn More', 'gutenberg-lab-blocks' ); ?>"
	/>
	<p>
		<label for="gutenberg-lab-package-secondary-cta-url">
			<?php esc_html_e( 'Secondary CTA URL', 'gutenberg-lab-blocks' ); ?>
		</label>
	</p>
	<input
		type="url"
		id="gutenberg-lab-package-secondary-cta-url"
		name="gutenberg_lab_package_secondary_cta_url"
		class="widefat"
		value="<?php echo esc_attr( $secondary_url ); ?>"
		placeholder="https://"
	/>
	<p>
		<label for="gutenberg-lab-package-secondary-cta-kind">
			<?php esc_html_e( 'Secondary CTA Type', 'gutenberg-lab-blocks' ); ?>
		</label>
	</p>
	<select
		id="gutenberg-lab-package-secondary-cta-kind"
		name="gutenberg_lab_package_secondary_cta_kind"
		class="widefat"
	>
		<?php foreach ( $choices as $value => $label ) : ?>
			<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $secondary_kind, $value ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<p class="description">
		<?php esc_html_e( 'Use these structured CTA fields so the single template and package cards can stay in sync.', 'gutenberg-lab-blocks' ); ?>
	</p>
	<?php
}

/**
 * Persists the package meta fields from the meta boxes.
 *
 * @param int $post_id Current post ID.
 */
function gutenberg_lab_blocks_save_packages_meta( $post_id ) {
	if ( ! isset( $_POST['gutenberg_lab_blocks_package_fields_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gutenberg_lab_blocks_package_fields_nonce'] ) ), 'gutenberg_lab_blocks_save_package_fields' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$request_to_meta = array(
		'gutenberg_lab_package_price'               => 'package_price',
		'gutenberg_lab_package_primary_cta_label'   => 'package_primary_cta_label',
		'gutenberg_lab_package_primary_cta_url'     => 'package_primary_cta_url',
		'gutenberg_lab_package_primary_cta_kind'    => 'package_primary_cta_kind',
		'gutenberg_lab_package_secondary_cta_label' => 'package_secondary_cta_label',
		'gutenberg_lab_package_secondary_cta_url'   => 'package_secondary_cta_url',
		'gutenberg_lab_package_secondary_cta_kind'  => 'package_secondary_cta_kind',
	);
	$meta_schema      = gutenberg_lab_blocks_get_package_meta_schema();

	foreach ( $request_to_meta as $request_key => $meta_key ) {
		if ( ! array_key_exists( $meta_key, $meta_schema ) ) {
			continue;
		}

		$raw_value = '';

		if ( isset( $_POST[ $request_key ] ) ) {
			$raw_value = wp_unslash( $_POST[ $request_key ] );
		}

		$sanitizer = $meta_schema[ $meta_key ]['sanitize_callback'] ?? 'sanitize_text_field';
		$value     = is_callable( $sanitizer ) ? call_user_func( $sanitizer, $raw_value ) : sanitize_text_field( $raw_value );

		if ( '' === $value ) {
			delete_post_meta( $post_id, $meta_key );
			continue;
		}

		update_post_meta( $post_id, $meta_key, $value );
	}
}
add_action( 'save_post_packages', 'gutenberg_lab_blocks_save_packages_meta' );
