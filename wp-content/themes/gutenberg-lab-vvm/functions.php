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
			'assets/css/buttons.css',
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

	add_theme_support( 'post-thumbnails' );
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

	foreach (
		array(
			array(
				'name'       => 'vvm-primary',
				'label'      => __( 'Primary', 'gutenberg-lab-vvm' ),
				'is_default' => true,
			),
			array(
				'name'  => 'vvm-secondary',
				'label' => __( 'Secondary', 'gutenberg-lab-vvm' ),
			),
			array(
				'name'  => 'vvm-ghost',
				'label' => __( 'Ghost', 'gutenberg-lab-vvm' ),
			),
			array(
				'name'  => 'vvm-link-primary',
				'label' => __( 'Link Primary', 'gutenberg-lab-vvm' ),
			),
			array(
				'name'  => 'vvm-link-secondary',
				'label' => __( 'Link Secondary', 'gutenberg-lab-vvm' ),
			),
		) as $button_style
	) {
		register_block_style(
			'core/button',
			array_merge(
				$button_style,
				array(
					'inline_style' => '',
				)
			)
		);
	}

	register_block_style(
		'gutenberg-lab-blocks/card-grid',
		array(
			'name'         => 'villa-cinematic',
			'label'        => __( 'Villa Cinematic', 'gutenberg-lab-vvm' ),
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
 * Normalizes a navigation target into the URL stored in a nav-link block.
 *
 * Footer links mix internal clean slugs, post permalinks, and placeholder
 * social URLs. Centralizing normalization keeps the seeded nav entities
 * predictable without hardcoding different helper functions for each case.
 *
 * @param string $url Raw URL or relative path.
 * @return string
 */
function gutenberg_lab_vvm_normalize_navigation_url( $url ) {
	$url = trim( (string) $url );

	if ( '' === $url ) {
		return home_url( '/' );
	}

	if (
		'#' === $url ||
		str_starts_with( $url, 'http://' ) ||
		str_starts_with( $url, 'https://' ) ||
		str_starts_with( $url, 'mailto:' ) ||
		str_starts_with( $url, 'tel:' )
	) {
		return $url;
	}

	if ( str_starts_with( $url, '/' ) ) {
		return home_url( user_trailingslashit( ltrim( $url, '/' ) ) );
	}

	return home_url( user_trailingslashit( $url ) );
}

/**
 * Builds serialized custom navigation-link markup from a URL or clean path.
 *
 * Footer legal links are chrome-only and may exist before the actual pages do,
 * so we seed them as custom URLs to keep the paths stable and predictable.
 *
 * @param string $label       Human-readable menu label.
 * @param string $url         Absolute URL, anchor, or relative site path.
 * @param array  $extra_attrs Optional block attributes such as `className`.
 * @return string
 */
function gutenberg_lab_vvm_custom_navigation_link_markup( $label, $url, $extra_attrs = array() ) {
	$attributes = array_merge(
		array(
			'label' => $label,
			'type'  => 'custom',
			'url'   => gutenberg_lab_vvm_normalize_navigation_url( $url ),
			'kind'  => 'custom',
		),
		$extra_attrs
	);

	return '<!-- wp:navigation-link ' . wp_json_encode( $attributes ) . ' /-->';
}

/**
 * Builds a serialized list of custom navigation-link blocks.
 *
 * @param array[] $links Each link includes `label` and `url`.
 * @return string
 */
function gutenberg_lab_vvm_custom_navigation_links_markup( $links ) {
	return implode(
		"\n\n",
		array_map(
			static function ( $link ) {
				return gutenberg_lab_vvm_custom_navigation_link_markup( $link['label'], $link['url'] );
			},
			$links
		)
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
	return gutenberg_lab_vvm_custom_navigation_links_markup(
		array(
			array(
				'label' => 'Privacy Policy',
				'url'   => '/privacy-policy/',
			),
			array(
				'label' => 'Terms of Service',
				'url'   => '/terms-of-service/',
			),
			array(
				'label' => 'Sitemap',
				'url'   => '/site-map/',
			),
		)
	);
}

/**
 * Returns the canonical block markup for the footer villas navigation entity.
 *
 * @return string
 */
function gutenberg_lab_vvm_get_footer_villas_navigation_content() {
	$villas = array(
		array(
			'label' => 'Monkey Hill',
			'slug'  => 'monkey-hill',
		),
		array(
			'label' => 'Ocean Heights',
			'slug'  => 'ocean-heights',
		),
		array(
			'label' => 'Crick Hill House',
			'slug'  => 'crick-hill-house',
		),
	);

	$links = array_map(
		static function ( $villa ) {
			$post = get_page_by_path( $villa['slug'], OBJECT, 'villa' );
			$url  = $post instanceof WP_Post
				? get_permalink( $post )
				: home_url( user_trailingslashit( 'villas/' . $villa['slug'] ) );

			return array(
				'label' => $villa['label'],
				'url'   => $url,
			);
		},
		$villas
	);

	return gutenberg_lab_vvm_custom_navigation_links_markup( $links );
}

/**
 * Returns the canonical block markup for the footer explore navigation entity.
 *
 * @return string
 */
function gutenberg_lab_vvm_get_footer_explore_navigation_content() {
	$villa_archive_url = get_post_type_archive_link( 'villa' );

	if ( ! $villa_archive_url ) {
		$villa_archive_url = '/villas/';
	}

	return gutenberg_lab_vvm_custom_navigation_links_markup(
		array(
			array(
				'label' => 'Our Villas',
				'url'   => $villa_archive_url,
			),
			array(
				'label' => 'Private Experiences',
				'url'   => '/private-experiences/',
			),
			array(
				'label' => 'Destinations',
				'url'   => '/destinations/',
			),
			array(
				'label' => 'Membership',
				'url'   => '/membership/',
			),
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
 * Syncs the footer navigation entities used by the premium footer template.
 *
 * @param string $mode Either `seed` or `upsert`.
 * @return array<string, int>
 */
function gutenberg_lab_vvm_sync_footer_navigation_entities( $mode = 'seed' ) {
	$sync_navigation = 'upsert' === $mode
		? 'gutenberg_lab_vvm_upsert_navigation_post'
		: 'gutenberg_lab_vvm_seed_navigation_post';

	return array(
		'footer_villas'  => (int) call_user_func(
			$sync_navigation,
			'footer-villas-navigation',
			'Footer Villas Navigation',
			gutenberg_lab_vvm_get_footer_villas_navigation_content()
		),
		'footer_explore' => (int) call_user_func(
			$sync_navigation,
			'footer-explore-navigation',
			'Footer Explore Navigation',
			gutenberg_lab_vvm_get_footer_explore_navigation_content()
		),
		'footer_legal'   => (int) call_user_func(
			$sync_navigation,
			'footer-navigation',
			'Footer Navigation',
			gutenberg_lab_vvm_get_footer_navigation_content()
		),
	);
}

/**
 * Replaces serialized navigation ref placeholders in template-part markup.
 *
 * @param string $content       Template-part block markup.
 * @param array  $replacements  Placeholder ref integers keyed to live IDs.
 * @return string
 */
function gutenberg_lab_vvm_replace_navigation_refs( $content, $replacements ) {
	foreach ( $replacements as $placeholder => $navigation_id ) {
		$content = str_replace(
			'"ref":' . (int) $placeholder,
			'"ref":' . (int) $navigation_id,
			$content
		);
	}

	return $content;
}

/**
 * Loads a file-based template part and swaps placeholder navigation refs.
 *
 * We intentionally keep the canonical block markup in the theme files, then
 * inject environment-specific navigation IDs when provisioning template-part
 * posts for the current site. The source files intentionally use placeholder
 * refs so they stay valid block markup without implying real database IDs.
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

	if ( 'footer' === $slug ) {
		$content = gutenberg_lab_vvm_replace_navigation_refs(
			$content,
			array(
				9101 => (int) ( $navigation_refs['footer_villas'] ?? 0 ),
				9102 => (int) ( $navigation_refs['footer_explore'] ?? 0 ),
				9105 => (int) ( $navigation_refs['footer_legal'] ?? 0 ),
			)
		);
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

	$header_navigation_id = gutenberg_lab_vvm_seed_navigation_post(
		'header-navigation',
		'Header Navigation',
		gutenberg_lab_vvm_get_header_navigation_content()
	);

	$navigation_refs = array_merge(
		array(
			'primary' => $primary_navigation_id,
			'header'  => $header_navigation_id,
		),
		gutenberg_lab_vvm_sync_footer_navigation_entities( 'seed' )
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

	$header_navigation_id = gutenberg_lab_vvm_upsert_navigation_post(
		'header-navigation',
		'Header Navigation',
		gutenberg_lab_vvm_get_header_navigation_content()
	);

	$navigation_refs = array_merge(
		array(
			'primary' => $primary_navigation_id,
			'header'  => $header_navigation_id,
		),
		gutenberg_lab_vvm_sync_footer_navigation_entities( 'upsert' )
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
 * Seeds the initial Barbados Escapes villa posts when they are missing.
 *
 * The CPT lives in the block plugin. The theme only owns the initial content
 * bootstrap so the footer and archive have real content to point at.
 */
function gutenberg_lab_vvm_seed_initial_villas() {
	$seed_version = '2026-04-12-barbados-escapes-villas-v5';

	if ( get_option( 'gutenberg_lab_vvm_initial_villa_seed_version' ) === $seed_version ) {
		return;
	}

	if ( ! post_type_exists( 'villa' ) ) {
		return;
	}

	$villas = array(
		array(
			'title'      => 'Monkey Hill',
			'slug'       => 'monkey-hill',
			'location'   => 'St. James',
			'menu_order' => 0,
			'excerpt'    => 'A breezy hilltop retreat with sea views, layered terraces, and generous indoor-outdoor living made for slow Barbados mornings.',
			'content'    =>
				'<!-- wp:paragraph --><p>Monkey Hill is placeholder villa content for the Barbados Escapes build. Use this post to test archive cards, hero search results, and the single-villa flow while the final client copy is still in progress.</p><!-- /wp:paragraph -->' .
				'<!-- wp:paragraph --><p>The story here is a private west-coast stay with a calm arrival sequence, open-air lounging, and sunset-facing entertaining spaces.</p><!-- /wp:paragraph -->',
			'cta_label'  => 'Explore Monkey Hill',
		),
		array(
			'title'      => 'Ocean Heights',
			'slug'       => 'ocean-heights',
			'location'   => 'Paynes Bay',
			'menu_order' => 1,
			'excerpt'    => 'A modern ocean-view villa concept with bright social spaces, a broad pool terrace, and easy access to Barbados beach clubs.',
			'content'    =>
				'<!-- wp:paragraph --><p>Ocean Heights gives the card grid and villa archive a second content shape: more contemporary, more open, and slightly more family-travel oriented than Monkey Hill.</p><!-- /wp:paragraph -->' .
				'<!-- wp:paragraph --><p>Keep this as placeholder copy until the client delivers the final positioning, amenity list, and editorial description.</p><!-- /wp:paragraph -->',
			'cta_label'  => 'View Ocean Heights',
		),
		array(
			'title'      => 'Crick Hill House',
			'slug'       => 'crick-hill-house',
			'location'   => 'Mullins',
			'menu_order' => 2,
			'excerpt'    => 'A classic Barbados villa direction with garden privacy, warm timber details, and flexible gathering space for extended stays.',
			'content'    =>
				'<!-- wp:paragraph --><p>Crick Hill House rounds out the seeded villa set with a slightly more traditional voice and a longer-stay feel. It is here to support design development, search testing, and card-grid population.</p><!-- /wp:paragraph -->' .
				'<!-- wp:paragraph --><p>Swap this text for final marketing copy once the client signs off on the villa narrative and photography package.</p><!-- /wp:paragraph -->',
			'cta_label'  => 'Discover Crick Hill House',
		),
	);
	$placeholder_image_ids = get_posts(
		array(
			'name'           => 'placeholder-dsc01270_1-scaled',
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	$placeholder_image_id = ! empty( $placeholder_image_ids ) ? (int) $placeholder_image_ids[0] : 0;

	foreach ( $villas as $villa ) {
		$existing = get_page_by_path( $villa['slug'], OBJECT, 'villa' );
		$villa_id = 0;

		if ( $existing instanceof WP_Post ) {
			$update_args = array(
				'ID' => $existing->ID,
			);

			if ( (int) $existing->menu_order !== (int) $villa['menu_order'] ) {
				$update_args['menu_order'] = (int) $villa['menu_order'];
			}

			if ( '' === trim( (string) $existing->post_excerpt ) ) {
				$update_args['post_excerpt'] = $villa['excerpt'];
			}

			if ( '' === trim( (string) $existing->post_content ) ) {
				$update_args['post_content'] = $villa['content'];
			}

			if ( count( $update_args ) > 1 ) {
				wp_update_post( $update_args );
			}

			$villa_id = (int) $existing->ID;
		} else {
			$villa_id = wp_insert_post(
				array(
					'post_type'    => 'villa',
					'post_status'  => 'publish',
					'post_title'   => $villa['title'],
					'post_name'    => $villa['slug'],
					'post_excerpt' => $villa['excerpt'],
					'post_content' => $villa['content'],
					'menu_order'   => (int) $villa['menu_order'],
				)
			);
		}

		if ( ! $villa_id || is_wp_error( $villa_id ) ) {
			continue;
		}

		if ( empty( wp_get_object_terms( $villa_id, 'villa_location', array( 'fields' => 'ids' ) ) ) ) {
			wp_set_object_terms( $villa_id, array( $villa['location'] ), 'villa_location', false );
		}

		if ( '' === get_post_meta( $villa_id, 'villa_card_cta_label', true ) ) {
			update_post_meta( $villa_id, 'villa_card_cta_label', $villa['cta_label'] );
		}

		if ( $placeholder_image_id && ! has_post_thumbnail( $villa_id ) ) {
			set_post_thumbnail( $villa_id, $placeholder_image_id );
		}
	}

	update_option( 'gutenberg_lab_vvm_initial_villa_seed_version', $seed_version, false );
}
add_action( 'init', 'gutenberg_lab_vvm_seed_initial_villas', 12 );

/**
 * Refreshes the seeded footer chrome when the canonical file markup changes.
 *
 * The site editor owns the footer entity after the original migration, but
 * this gives us a safe one-time code-driven refresh for deliberate redesigns.
 */
function gutenberg_lab_vvm_refresh_footer_template_part() {
	$footer_refresh_version = '2026-04-11-barbados-escapes-footer-v3';

	if ( get_option( 'gutenberg_lab_vvm_footer_refresh_version' ) === $footer_refresh_version ) {
		return;
	}

	$primary_navigation_id = gutenberg_lab_vvm_get_primary_navigation_post_id();
	$header_navigation_id  = gutenberg_lab_vvm_get_header_navigation_post_id();
	$navigation_refs       = array_merge(
		array(
			'primary' => $primary_navigation_id,
			'header'  => $header_navigation_id ? $header_navigation_id : $primary_navigation_id,
		),
		gutenberg_lab_vvm_sync_footer_navigation_entities( 'upsert' )
	);

	gutenberg_lab_vvm_upsert_template_part_post(
		'footer',
		'Footer',
		WP_TEMPLATE_PART_AREA_FOOTER,
		$navigation_refs
	);

	update_option( 'gutenberg_lab_vvm_footer_refresh_version', $footer_refresh_version, false );
}
add_action( 'init', 'gutenberg_lab_vvm_refresh_footer_template_part', 22 );

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
 * Keeps the editor button style picker aligned with the theme's design system.
 *
 * We expose custom branded button variations, so the stock Outline option only
 * creates ambiguity and makes it easy to pick a style the theme does not want
 * editors using.
 */
function gutenberg_lab_vvm_enqueue_block_editor_script() {
	wp_enqueue_script(
		'gutenberg-lab-vvm-block-editor',
		get_theme_file_uri( 'assets/js/block-editor.js' ),
		array( 'wp-blocks', 'wp-dom-ready' ),
		gutenberg_lab_vvm_asset_version( 'assets/js/block-editor.js' ),
		array(
			'in_footer' => true,
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'gutenberg_lab_vvm_enqueue_block_editor_script' );

/**
 * Enqueues the shared theme stylesheet and the minimal front-end chrome script.
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

	// Keep the branded button system in one shared stylesheet so the front end
	// and editor can consume the same source of truth.
	wp_enqueue_style(
		'gutenberg-lab-vvm-buttons',
		get_theme_file_uri( 'assets/css/buttons.css' ),
		array( 'gutenberg-lab-vvm-style' ),
		gutenberg_lab_vvm_asset_version( 'assets/css/buttons.css' )
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
