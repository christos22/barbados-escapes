<?php
/**
 * Theme setup for the VVM block theme port.
 *
 * Page/post body content stays editor-managed. Shared site chrome now follows
 * the native Gutenberg model: the theme files seed the initial template-part
 * and navigation content, then the database-backed entities become editable in
 * the Site Editor.
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
 * Returns the navigation post ID for a stable slug, or zero if missing.
 *
 * @param string $slug Navigation post slug.
 * @return int
 */
function gutenberg_lab_vvm_get_navigation_post_id( $slug ) {
	$navigation_post = get_page_by_path( $slug, OBJECT, 'wp_navigation' );

	return $navigation_post instanceof WP_Post ? (int) $navigation_post->ID : 0;
}

/**
 * Returns the seeded primary navigation post ID.
 *
 * @return int
 */
function gutenberg_lab_vvm_get_primary_navigation_post_id() {
	return gutenberg_lab_vvm_get_navigation_post_id( 'navigation' );
}

/**
 * Returns the seeded flat header navigation post ID.
 *
 * @return int
 */
function gutenberg_lab_vvm_get_header_navigation_post_id() {
	return gutenberg_lab_vvm_get_navigation_post_id( 'header-navigation' );
}

/**
 * Hides the classic Menus screen so the theme stays on the block-navigation workflow.
 */
function gutenberg_lab_vvm_remove_classic_menus_screen() {
	remove_submenu_page( 'themes.php', 'nav-menus.php' );
}
add_action( 'admin_menu', 'gutenberg_lab_vvm_remove_classic_menus_screen', 100 );

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
 * @param string $label           Human-readable menu label.
 * @param string $slug            Page slug to resolve.
 * @param array  $extra_attrs     Optional block attributes such as `className`.
 * @return string
 */
function gutenberg_lab_vvm_navigation_link_markup( $label, $slug, $extra_attrs = array() ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );

	$attributes = array(
		'label' => $label,
	);

	if ( $page instanceof WP_Post ) {
		$attributes['type'] = 'page';
		$attributes['id']   = (int) $page->ID;
		$attributes['url']  = get_permalink( $page );
		$attributes['kind'] = 'post-type';
	} else {
		$attributes['type'] = 'custom';
		$attributes['url']  = home_url( '/' . $slug . '/' );
		$attributes['kind'] = 'custom';
	}

	$attributes = array_merge( $attributes, $extra_attrs );

	return '<!-- wp:navigation-link ' . wp_json_encode( $attributes ) . ' /-->';
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
 * Returns the canonical block markup for the flat header drawer navigation.
 *
 * The visual drawer design wants a single stacked list instead of nested
 * disclosure groups. We store that as its own `wp_navigation` entity so the
 * frontend semantics stay honest and the CSS can style plain links instead of
 * fighting Gutenberg's submenu behavior.
 *
 * @return string
 */
function gutenberg_lab_vvm_get_header_navigation_content() {
	$links = array(
		array(
			'label' => 'About Us',
			'slug'  => 'about-us',
		),
		array(
			'label' => 'Test Page 4',
			'slug'  => 'test-page-4',
			'class' => 'vvm-header-nav__child',
		),
		array(
			'label' => 'Test Page 5',
			'slug'  => 'test-page-5',
			'class' => 'vvm-header-nav__child',
		),
		array(
			'label' => 'Test Page 6',
			'slug'  => 'test-page-6',
			'class' => 'vvm-header-nav__child',
		),
		array(
			'label' => 'Blog',
			'slug'  => 'blog',
		),
		array(
			'label' => 'Test Page 1',
			'slug'  => 'test-page-1',
			'class' => 'vvm-header-nav__child',
		),
		array(
			'label' => 'Test Page 2',
			'slug'  => 'test-page-2',
			'class' => 'vvm-header-nav__child',
		),
		array(
			'label' => 'Test Page 3',
			'slug'  => 'test-page-3',
			'class' => 'vvm-header-nav__child',
		),
		array(
			'label' => 'Contact Us',
			'slug'  => 'contact-us',
		),
	);

	return implode(
		"\n\n",
		array_map(
			static function ( $link ) {
				$attributes = array();

				if ( ! empty( $link['class'] ) ) {
					$attributes['className'] = $link['class'];
				}

				return gutenberg_lab_vvm_navigation_link_markup( $link['label'], $link['slug'], $attributes );
			},
			$links
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
 * Creates a navigation post only when it does not exist yet.
 *
 * @param string $slug    Stable post_name for the navigation entity.
 * @param string $title   Editor-facing navigation title.
 * @param string $content Serialized navigation block content.
 * @return int
 */
function gutenberg_lab_vvm_seed_navigation_post( $slug, $title, $content ) {
	$existing = get_page_by_path( $slug, OBJECT, 'wp_navigation' );

	if ( $existing instanceof WP_Post ) {
		return (int) $existing->ID;
	}

	return (int) wp_insert_post(
		array(
			'post_type'    => 'wp_navigation',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
		)
	);
}

/**
 * Creates or updates a navigation post by slug.
 *
 * This is only used for the one-time migration from the old code-owned chrome
 * setup to the new native Gutenberg workflow.
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
		$header_navigation_id = ! empty( $navigation_refs['header'] )
			? (int) $navigation_refs['header']
			: (int) $navigation_refs['primary'];

		$content = preg_replace( '/"ref":\d+/', '"ref":' . $header_navigation_id, $content, 1 );
	}

	if ( 'footer' === $slug && ! empty( $navigation_refs['footer'] ) ) {
		$content = preg_replace( '/"ref":\d+/', '"ref":' . (int) $navigation_refs['footer'], $content, 1 );
	}

	return $content;
}

/**
 * Creates a template part post only when it does not exist yet.
 *
 * This keeps the initial header/footer markup version controlled, but once the
 * entity exists Gutenberg owns subsequent edits through the Site Editor.
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
		$post_id = (int) $existing->wp_id;

		wp_set_post_terms( $post_id, array( get_stylesheet() ), 'wp_theme' );
		wp_set_post_terms( $post_id, array( $area ), 'wp_template_part_area' );

		return $post_id;
	}

	$content = gutenberg_lab_vvm_get_template_part_content( $slug, $navigation_refs );
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
 * Creates or updates a template-part post from the current file markup.
 *
 * We use this once when migrating away from the old code-owned chrome setup so
 * the live database entity picks up the locked native block structure.
 *
 * @param string $slug            Template part slug.
 * @param string $title           Editor-facing template part title.
 * @param string $area            Template part area taxonomy value.
 * @param array  $navigation_refs Map of logical navigation keys to post IDs.
 * @return int
 */
function gutenberg_lab_vvm_upsert_template_part_post( $slug, $title, $area, $navigation_refs ) {
	$existing = get_block_template( get_stylesheet() . '//' . $slug, 'wp_template_part' );
	$content  = gutenberg_lab_vvm_get_template_part_content( $slug, $navigation_refs );

	if ( $existing && ! empty( $existing->wp_id ) ) {
		$post_id = (int) $existing->wp_id;

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $content,
			)
		);

		wp_set_post_terms( $post_id, array( get_stylesheet() ), 'wp_theme' );
		wp_set_post_terms( $post_id, array( $area ), 'wp_template_part_area' );

		return $post_id;
	}

	return gutenberg_lab_vvm_ensure_template_part_post( $slug, $title, $area, $navigation_refs );
}

/**
 * Seeds the native Gutenberg entities that power the shared site chrome.
 *
 * The file markup acts as the initial template. After the first seed, the
 * `wp_template_part` and `wp_navigation` entities become the editor-owned
 * source of truth, which matches the WordPress.com style workflow.
 */
function gutenberg_lab_vvm_bootstrap_native_entities() {
	$primary_navigation_id = gutenberg_lab_vvm_seed_navigation_post(
		'navigation',
		'Primary Navigation',
		gutenberg_lab_vvm_get_primary_navigation_content()
	);

	$footer_navigation_id = gutenberg_lab_vvm_seed_navigation_post(
		'footer-navigation',
		'Footer Navigation',
		gutenberg_lab_vvm_get_footer_navigation_content()
	);

	$header_navigation_id = gutenberg_lab_vvm_seed_navigation_post(
		'header-navigation',
		'Header Navigation',
		gutenberg_lab_vvm_get_header_navigation_content()
	);

	$navigation_refs = array(
		'primary' => $primary_navigation_id,
		'header'  => $header_navigation_id,
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
 * Seeds the native entities on normal loads if they are still missing.
 */
function gutenberg_lab_vvm_ensure_native_entities_exist() {
	gutenberg_lab_vvm_bootstrap_native_entities();
}
add_action( 'init', 'gutenberg_lab_vvm_ensure_native_entities_exist', 20 );

/**
 * Performs a one-time migration from code-owned chrome to native Gutenberg.
 *
 * Earlier versions rewrote the site chrome on every request. We intentionally
 * replace that once so the current site gets the new locked header structure,
 * then we stop touching the entities and let editors own them from there.
 */
function gutenberg_lab_vvm_migrate_site_chrome_to_native_gutenberg() {
	if ( get_option( 'gutenberg_lab_vvm_native_site_editor_migrated' ) ) {
		return;
	}

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

	$header_navigation_id = gutenberg_lab_vvm_upsert_navigation_post(
		'header-navigation',
		'Header Navigation',
		gutenberg_lab_vvm_get_header_navigation_content()
	);

	$navigation_refs = array(
		'primary' => $primary_navigation_id,
		'header'  => $header_navigation_id,
		'footer'  => $footer_navigation_id,
	);

	gutenberg_lab_vvm_upsert_template_part_post(
		'header',
		'Header',
		WP_TEMPLATE_PART_AREA_HEADER,
		$navigation_refs
	);

	gutenberg_lab_vvm_upsert_template_part_post(
		'footer',
		'Footer',
		WP_TEMPLATE_PART_AREA_FOOTER,
		$navigation_refs
	);

	update_option( 'gutenberg_lab_vvm_native_site_editor_migrated', 1, false );
}
add_action( 'init', 'gutenberg_lab_vvm_migrate_site_chrome_to_native_gutenberg', 21 );

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
	$environment   = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
	$is_dev_env    = in_array( $environment, array( 'local', 'development' ), true ) || ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG );

	if ( $is_dev_env && file_exists( $absolute_path ) ) {
		return (string) filemtime( $absolute_path );
	}

	return (string) wp_get_theme()->get( 'Version' );
}

/**
 * Enqueue the remaining remote display font for both the editor and the front end.
 *
 * TT Norms now ships locally via `theme.json` font-face declarations so the
 * global sans stack is theme-native. We still enqueue the shared serif here
 * because the theme continues to use Cormorant Garamond as a separate preset.
 */
function gutenberg_lab_vvm_enqueue_fonts() {
	wp_enqueue_style(
		'gutenberg-lab-vvm-fonts',
		'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&display=swap',
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
