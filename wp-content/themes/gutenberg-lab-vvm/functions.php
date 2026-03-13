<?php
/**
 * Theme setup for the VVM block theme port.
 *
 * The theme still uses block template parts as the source of truth, but we
 * register theme supports and menu locations so WordPress exposes the expected
 * editor UI for global site chrome.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Internal bootstrap version for one-time native entity provisioning.
 */
const GUTENBERG_LAB_VVM_BOOTSTRAP_VERSION = '2';

/**
 * Registers the global theme supports used by the header/footer implementation.
 */
function gutenberg_lab_vvm_setup() {
	// Let the block editor iframe inherit the theme's front-end stylesheet.
	// A dedicated editor stylesheet is loaded after the front-end styles so we
	// can keep the editor representative without forcing it to mimic every
	// front-end-only interaction or layout trick.
	add_theme_support( 'editor-styles' );
	add_editor_style(
		array(
			'style.css',
			'assets/css/editor.css',
		)
	);

	add_theme_support(
		'custom-logo',
		array(
			'height'      => 90,
			'width'       => 320,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'gutenberg-lab-vvm' ),
			'footer'  => __( 'Footer Menu', 'gutenberg-lab-vvm' ),
		)
	);
}
add_action( 'after_setup_theme', 'gutenberg_lab_vvm_setup' );

/**
 * Registers VVM-specific button styles for the core Button block.
 *
 * The original headless site exposes multiple link/button variants in ACF.
 * In Gutenberg we express those as block styles so editors can choose them
 * from the normal block sidebar instead of juggling custom class names.
 */
function gutenberg_lab_vvm_register_block_styles() {
	if ( ! function_exists( 'register_block_style' ) ) {
		return;
	}

	register_block_style(
		'core/button',
		array(
			'name'         => 'vvm-primary',
			'label'        => __( 'Primary', 'gutenberg-lab-vvm' ),
			'is_default'   => true,
			'inline_style' => '',
		)
	);

	register_block_style(
		'core/button',
		array(
			'name'         => 'vvm-secondary',
			'label'        => __( 'Secondary', 'gutenberg-lab-vvm' ),
			'inline_style' => '',
		)
	);

	register_block_style(
		'core/button',
		array(
			'name'         => 'vvm-link-primary',
			'label'        => __( 'Link Primary', 'gutenberg-lab-vvm' ),
			'inline_style' => '',
		)
	);

	register_block_style(
		'core/button',
		array(
			'name'         => 'vvm-link-secondary',
			'label'        => __( 'Link Secondary', 'gutenberg-lab-vvm' ),
			'inline_style' => '',
		)
	);
}
add_action( 'init', 'gutenberg_lab_vvm_register_block_styles' );

/**
 * Builds serialized navigation-link markup from a page slug.
 *
 * @param string $label Human-readable menu label.
 * @param string $slug  Page slug to resolve.
 * @return string
 */
function gutenberg_lab_vvm_navigation_link_markup( $label, $slug ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );

	if ( ! $page instanceof WP_Post ) {
		return '<!-- wp:navigation-link {"label":"' . esc_attr( $label ) . '","type":"custom","url":"' . esc_url( home_url( '/' . $slug . '/' ) ) . '","kind":"custom"} /-->';
	}

	return sprintf(
		'<!-- wp:navigation-link {"label":"%1$s","type":"page","id":%2$d,"url":"%3$s","kind":"post-type"} /-->',
		esc_attr( $label ),
		(int) $page->ID,
		esc_url( get_permalink( $page ) )
	);
}

/**
 * Builds serialized navigation-submenu markup from a page slug and children.
 *
 * @param string $label           Human-readable menu label.
 * @param string $slug            Parent page slug.
 * @param array  $child_link_data Child links with `label` and `slug` keys.
 * @return string
 */
function gutenberg_lab_vvm_navigation_submenu_markup( $label, $slug, $child_link_data ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );

	if ( $page instanceof WP_Post ) {
		$opening = sprintf(
			'<!-- wp:navigation-submenu {"label":"%1$s","type":"page","id":%2$d,"url":"%3$s","kind":"post-type"} -->',
			esc_attr( $label ),
			(int) $page->ID,
			esc_url( get_permalink( $page ) )
		);
	} else {
		$opening = sprintf(
			'<!-- wp:navigation-submenu {"label":"%1$s","type":"custom","url":"%2$s","kind":"custom"} -->',
			esc_attr( $label ),
			esc_url( home_url( '/' . $slug . '/' ) )
		);
	}

	$links = array( $opening );

	foreach ( $child_link_data as $child_link ) {
		$links[] = gutenberg_lab_vvm_navigation_link_markup( $child_link['label'], $child_link['slug'] );
	}

	$links[] = '<!-- /wp:navigation-submenu -->';

	return implode( "\n\n", $links );
}

/**
 * Returns the canonical block markup for the primary navigation entity.
 *
 * We store the menu as a real `wp_navigation` post so the Site Editor
 * navigation screen edits the same data the frontend consumes.
 *
 * @return string
 */
function gutenberg_lab_vvm_get_primary_navigation_content() {
	return implode(
		"\n\n",
		array(
			gutenberg_lab_vvm_navigation_submenu_markup(
				'About Us',
				'about-us',
				array(
					array(
						'label' => 'About Us',
						'slug'  => 'about-us',
					),
					array(
						'label' => 'Test Page 4',
						'slug'  => 'test-page-4',
					),
					array(
						'label' => 'Test Page 5',
						'slug'  => 'test-page-5',
					),
					array(
						'label' => 'Test Page 6',
						'slug'  => 'test-page-6',
					),
				)
			),
			gutenberg_lab_vvm_navigation_submenu_markup(
				'Blog',
				'blog',
				array(
					array(
						'label' => 'Blog',
						'slug'  => 'blog',
					),
					array(
						'label' => 'Test Page 1',
						'slug'  => 'test-page-1',
					),
					array(
						'label' => 'Test Page 2',
						'slug'  => 'test-page-2',
					),
					array(
						'label' => 'Test Page 3',
						'slug'  => 'test-page-3',
					),
				)
			),
			gutenberg_lab_vvm_navigation_link_markup( 'Contact Us', 'contact-us' ),
		)
	);
}

/**
 * Returns the canonical block markup for the footer utility navigation entity.
 *
 * @return string
 */
function gutenberg_lab_vvm_get_footer_navigation_content() {
	return implode(
		"\n\n",
		array(
			gutenberg_lab_vvm_navigation_link_markup( 'Contact Us', 'contact-us' ),
			gutenberg_lab_vvm_navigation_link_markup( 'Site Map', 'site-map' ),
			gutenberg_lab_vvm_navigation_link_markup( 'Privacy Policy', 'privacy-policy' ),
			gutenberg_lab_vvm_navigation_link_markup( 'Accessibility', 'accessibility' ),
		)
	);
}

/**
 * Creates or updates a navigation post by slug.
 *
 * @param string $slug    Stable post_name for the navigation entity.
 * @param string $title   Editor-facing navigation title.
 * @param string $content Serialized navigation block content.
 * @return int
 */
function gutenberg_lab_vvm_upsert_navigation_post( $slug, $title, $content ) {
	$existing = get_page_by_path( $slug, OBJECT, 'wp_navigation' );

	$args = array(
		'post_type'    => 'wp_navigation',
		'post_status'  => 'publish',
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => $content,
	);

	if ( $existing instanceof WP_Post ) {
		$args['ID'] = $existing->ID;
		wp_update_post( $args );
		return (int) $existing->ID;
	}

	return (int) wp_insert_post( $args );
}

/**
 * Loads a file-based template part and swaps placeholder navigation refs.
 *
 * We intentionally keep the canonical block markup in the theme files, then
 * inject environment-specific navigation IDs when provisioning template-part
 * posts for the current site. The source files intentionally use `ref: 0`
 * so they stay valid block markup without implying a real database ID.
 *
 * @param string $slug            Template part slug, for example `header`.
 * @param array  $navigation_refs Map of logical navigation keys to post IDs.
 * @return string
 */
function gutenberg_lab_vvm_get_template_part_content( $slug, $navigation_refs ) {
	$content = file_get_contents( get_theme_file_path( 'parts/' . $slug . '.html' ) );

	if ( false === $content ) {
		return '';
	}

	if ( 'header' === $slug && ! empty( $navigation_refs['primary'] ) ) {
		$content = preg_replace( '/"ref":\d+/', '"ref":' . (int) $navigation_refs['primary'], $content, 1 );
	}

	if ( 'footer' === $slug && ! empty( $navigation_refs['footer'] ) ) {
		$content = preg_replace( '/"ref":\d+/', '"ref":' . (int) $navigation_refs['footer'], $content, 1 );
	}

	return $content;
}

/**
 * Creates a theme-owned template part post when one does not already exist.
 *
 * Once the post exists, WordPress will render it instead of the file version,
 * which lets us keep navigation refs portable across environments.
 *
 * @param string $slug            Template part slug.
 * @param string $title           Editor-facing template part title.
 * @param string $area            Template part area taxonomy value.
 * @param array  $navigation_refs Map of logical navigation keys to post IDs.
 * @return int
 */
function gutenberg_lab_vvm_ensure_template_part_post( $slug, $title, $area, $navigation_refs ) {
	$existing = get_block_template( get_stylesheet() . '//' . $slug, 'wp_template_part' );

	if ( $existing && ! empty( $existing->wp_id ) ) {
		return (int) $existing->wp_id;
	}

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'wp_template_part',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => gutenberg_lab_vvm_get_template_part_content( $slug, $navigation_refs ),
		)
	);

	if ( ! $post_id || is_wp_error( $post_id ) ) {
		return 0;
	}

	wp_set_post_terms( $post_id, array( get_stylesheet() ), 'wp_theme' );
	wp_set_post_terms( $post_id, array( $area ), 'wp_template_part_area' );

	return (int) $post_id;
}

/**
 * Bootstraps native navigation and template-part entities for fresh installs.
 *
 * We only create missing entities. Existing Navigation or template-part posts
 * are treated as editor-managed content and left untouched.
 */
function gutenberg_lab_vvm_bootstrap_native_entities() {
	$primary_navigation_id = gutenberg_lab_vvm_upsert_navigation_post(
		'navigation',
		'Primary Navigation',
		gutenberg_lab_vvm_get_primary_navigation_content()
	);

	$footer_navigation_id = gutenberg_lab_vvm_upsert_navigation_post(
		'footer-navigation',
		'Footer Navigation',
		gutenberg_lab_vvm_get_footer_navigation_content()
	);

	$navigation_refs = array(
		'primary' => $primary_navigation_id,
		'footer'  => $footer_navigation_id,
	);

	gutenberg_lab_vvm_ensure_template_part_post(
		'header',
		'Header',
		WP_TEMPLATE_PART_AREA_HEADER,
		$navigation_refs
	);

	gutenberg_lab_vvm_ensure_template_part_post(
		'footer',
		'Footer',
		WP_TEMPLATE_PART_AREA_FOOTER,
		$navigation_refs
	);

	update_option( 'gutenberg_lab_vvm_bootstrap_version', GUTENBERG_LAB_VVM_BOOTSTRAP_VERSION );
}

/**
 * Runs the entity bootstrap on theme switch.
 */
function gutenberg_lab_vvm_bootstrap_native_entities_on_switch() {
	gutenberg_lab_vvm_bootstrap_native_entities();
}
add_action( 'after_switch_theme', 'gutenberg_lab_vvm_bootstrap_native_entities_on_switch' );

/**
 * Ensures the bootstrap also runs once for already-active local installs.
 */
function gutenberg_lab_vvm_maybe_bootstrap_native_entities() {
	if ( get_option( 'gutenberg_lab_vvm_bootstrap_version' ) === GUTENBERG_LAB_VVM_BOOTSTRAP_VERSION ) {
		return;
	}

	gutenberg_lab_vvm_bootstrap_native_entities();
}
add_action( 'init', 'gutenberg_lab_vvm_maybe_bootstrap_native_entities', 20 );

/**
 * Returns a cache-busting asset version from a theme file path.
 *
 * Using `filemtime()` during local development means CSS/JS changes show up
 * immediately without needing a separate build step for plain theme assets.
 *
 * @param string $relative_path Theme-relative asset path.
 * @return string
 */
function gutenberg_lab_vvm_asset_version( $relative_path ) {
	$absolute_path = get_theme_file_path( $relative_path );

	if ( file_exists( $absolute_path ) ) {
		return (string) filemtime( $absolute_path );
	}

	return (string) wp_get_theme()->get( 'Version' );
}

/**
 * Enqueues the shared theme stylesheet and the minimal front-end header script.
 *
 * The editor does not need to reproduce every front-end behavior, but the
 * public site can still use small progressive enhancements when they serve
 * the intended design.
 */
function gutenberg_lab_vvm_enqueue_assets() {
	wp_enqueue_style(
		'gutenberg-lab-vvm-style',
		get_stylesheet_uri(),
		array(),
		gutenberg_lab_vvm_asset_version( 'style.css' )
	);

	wp_enqueue_script(
		'gutenberg-lab-vvm-site-header',
		get_theme_file_uri( 'assets/js/site-header.js' ),
		array(),
		gutenberg_lab_vvm_asset_version( 'assets/js/site-header.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'gutenberg_lab_vvm_enqueue_assets' );
