<?php
/**
 * Theme setup for the VVM block theme port.
 *
 * Page/post body content stays editor-managed, but the shared site chrome
 * lives in version-controlled theme files and is synchronized into the
 * database-backed block entities WordPress renders.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
}
add_action( 'after_setup_theme', 'gutenberg_lab_vvm_setup' );

/**
 * Hides chrome-editing admin screens in this code-owned workflow.
 *
 * Header, footer, and navigation stay git-tracked, so the dashboard should
 * steer editors toward Pages instead of exposing competing site-chrome UIs.
 */
function gutenberg_lab_vvm_remove_classic_menus_screen() {
	remove_submenu_page( 'themes.php', 'nav-menus.php' );
	remove_submenu_page( 'themes.php', 'site-editor.php' );
}
add_action( 'admin_menu', 'gutenberg_lab_vvm_remove_classic_menus_screen', 100 );

/**
 * Redirects requests away from admin screens that edit code-owned site chrome.
 *
 * We leave Pages available for normal content editing, but template, template
 * part, and navigation entities should not be maintained through wp-admin.
 */
function gutenberg_lab_vvm_redirect_site_chrome_editing_screens() {
	if ( ! is_admin() ) {
		return;
	}

	global $pagenow;

	$restricted_post_types = array(
		'wp_template',
		'wp_template_part',
		'wp_navigation',
	);

	if ( 'site-editor.php' === $pagenow ) {
		wp_safe_redirect( admin_url( 'edit.php?post_type=page' ) );
		exit;
	}

	if ( ! in_array( $pagenow, array( 'edit.php', 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$post_type = '';

	if ( ! empty( $_GET['post_type'] ) ) {
		$post_type = sanitize_key( wp_unslash( $_GET['post_type'] ) );
	} elseif ( ! empty( $_GET['post'] ) ) {
		$post_type = get_post_type( (int) $_GET['post'] );
	}

	if ( in_array( $post_type, $restricted_post_types, true ) ) {
		wp_safe_redirect( admin_url( 'edit.php?post_type=page' ) );
		exit;
	}
}
add_action( 'admin_init', 'gutenberg_lab_vvm_redirect_site_chrome_editing_screens' );

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
 * Creates or updates a theme-owned template part post from the file version.
 *
 * WordPress renders the database-backed entity once it exists, so we upsert the
 * post from the canonical file content to keep the rendered template part
 * aligned with git-tracked source while still injecting local navigation refs.
 *
 * @param string $slug            Template part slug.
 * @param string $title           Editor-facing template part title.
 * @param string $area            Template part area taxonomy value.
 * @param array  $navigation_refs Map of logical navigation keys to post IDs.
 * @return int
 */
function gutenberg_lab_vvm_ensure_template_part_post( $slug, $title, $area, $navigation_refs ) {
	$existing = get_block_template( get_stylesheet() . '//' . $slug, 'wp_template_part' );
	$content  = gutenberg_lab_vvm_get_template_part_content( $slug, $navigation_refs );

	if ( $existing && ! empty( $existing->wp_id ) ) {
		$post_id = (int) $existing->wp_id;
		$post    = get_post( $post_id );

		if ( $post instanceof WP_Post ) {
			$updated_post = array(
				'ID'           => $post_id,
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $content,
			);

			if (
				$title !== $post->post_title ||
				$slug !== $post->post_name ||
				$content !== $post->post_content
			) {
				wp_update_post( $updated_post );
			}
		}

		wp_set_post_terms( $post_id, array( get_stylesheet() ), 'wp_theme' );
		wp_set_post_terms( $post_id, array( $area ), 'wp_template_part_area' );

		return $post_id;
	}

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'wp_template_part',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
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
 * Synchronizes the database-backed entities that power code-owned site chrome.
 *
 * Navigation, header, and footer are all generated from theme-controlled
 * source so front-end rendering stays aligned with git-tracked files.
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

}

/**
 * Runs the entity bootstrap on theme switch.
 */
function gutenberg_lab_vvm_bootstrap_native_entities_on_switch() {
	gutenberg_lab_vvm_bootstrap_native_entities();
}
add_action( 'after_switch_theme', 'gutenberg_lab_vvm_bootstrap_native_entities_on_switch' );

/**
 * Keeps code-owned navigation and template parts synchronized on normal loads.
 */
function gutenberg_lab_vvm_sync_native_entities() {
	gutenberg_lab_vvm_bootstrap_native_entities();
}
add_action( 'init', 'gutenberg_lab_vvm_sync_native_entities', 20 );

/**
 * Returns the first meaningful content block from a parsed block list.
 *
 * We skip empty wrappers so the header logic can reason about the first
 * real visual section, not just structural container blocks.
 *
 * @param array $blocks Parsed block array from `parse_blocks()`.
 * @return array|null
 */
function gutenberg_lab_vvm_get_first_meaningful_block( $blocks ) {
	$wrapper_blocks = array(
		'core/group',
		'core/columns',
		'core/column',
	);

	foreach ( $blocks as $block ) {
		$block_name = $block['blockName'] ?? '';
		$inner_html = trim( wp_strip_all_tags( $block['innerHTML'] ?? '' ) );

		if ( in_array( $block_name, $wrapper_blocks, true ) && ! $inner_html && ! empty( $block['innerBlocks'] ) ) {
			$nested_block = gutenberg_lab_vvm_get_first_meaningful_block( $block['innerBlocks'] );

			if ( $nested_block ) {
				return $nested_block;
			}
		}

		if ( ! empty( $block_name ) ) {
			return $block;
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			$nested_block = gutenberg_lab_vvm_get_first_meaningful_block( $block['innerBlocks'] );

			if ( $nested_block ) {
				return $nested_block;
			}
		}
	}

	return null;
}

/**
 * Determines whether a parsed block should opt the page into the overlay header.
 *
 * The home page uses the media panel hero. We extend that same treatment to
 * any page whose first real block is a hero-like section.
 *
 * @param array $block Parsed block definition.
 * @return bool
 */
function gutenberg_lab_vvm_is_hero_block( $block ) {
	$block_name = $block['blockName'] ?? '';
	$attrs      = $block['attrs'] ?? array();
	$class_name = (string) ( $attrs['className'] ?? '' );

	if ( in_array( $block_name, array( 'core/cover', 'gutenberg-lab-blocks/media-panel' ), true ) ) {
		return true;
	}

	return 1 === preg_match( '/(?:^|\s)(hero|page-hero|banner)(?:\s|$)/i', $class_name );
}

/**
 * Returns whether the current singular view starts with a hero block.
 *
 * @return bool
 */
function gutenberg_lab_vvm_current_view_has_hero() {
	if ( ! is_singular() ) {
		return false;
	}

	$post = get_queried_object();

	if ( ! $post instanceof WP_Post || empty( $post->post_content ) ) {
		return false;
	}

	$first_block = gutenberg_lab_vvm_get_first_meaningful_block( parse_blocks( $post->post_content ) );

	if ( ! $first_block ) {
		return false;
	}

	return gutenberg_lab_vvm_is_hero_block( $first_block );
}

/**
 * Adds a body class so the global header can switch between overlay and in-flow modes.
 *
 * @param string[] $classes Existing body classes.
 * @return string[]
 */
function gutenberg_lab_vvm_filter_body_class( $classes ) {
	$classes[] = gutenberg_lab_vvm_current_view_has_hero() ? 'vvm-has-hero-header' : 'vvm-has-static-header';

	return $classes;
}
add_filter( 'body_class', 'gutenberg_lab_vvm_filter_body_class' );

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
 * Enqueue the shared font stack for both the editor and the front end.
 *
 * VVM uses `next/font` to inject Nunito plus its CSS variable. In the lab
 * theme we need to load the family explicitly so the block editor and the
 * public site render with the same typography.
 */
function gutenberg_lab_vvm_enqueue_fonts() {
	wp_enqueue_style(
		'gutenberg-lab-vvm-fonts',
		'https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap',
		array(),
		null
	);
}
add_action( 'wp_enqueue_scripts', 'gutenberg_lab_vvm_enqueue_fonts' );
add_action( 'enqueue_block_editor_assets', 'gutenberg_lab_vvm_enqueue_fonts' );

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
