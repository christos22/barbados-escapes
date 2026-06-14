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

require_once __DIR__ . '/inc/seo.php';

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
 * Marks the document as JavaScript-capable before the page body renders.
 *
 * CSS can then hide progressive-enhancement content immediately, avoiding a
 * flash of content before the footer script wires up the interaction.
 */
function gutenberg_lab_vvm_add_js_class() {
	wp_print_inline_script_tag( "document.documentElement.classList.add('js');" );
}
add_action( 'wp_head', 'gutenberg_lab_vvm_add_js_class', 0 );

/**
 * Removes the public users sitemap from WordPress core sitemaps.
 *
 * The users sitemap exposes author archive URLs, which are not useful for this
 * brochure-style villa site and add noise to the public sitemap index.
 *
 * @param WP_Sitemaps_Provider|false $provider Sitemap provider instance.
 * @param string                     $name     Registered sitemap provider name.
 * @return WP_Sitemaps_Provider|false
 */
function gutenberg_lab_vvm_disable_users_sitemap( $provider, $name ) {
	if ( 'users' === $name ) {
		return false;
	}

	return $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'gutenberg_lab_vvm_disable_users_sitemap', 10, 2 );

/**
 * Adds the public sitemap index URL to WordPress's virtual robots.txt file.
 *
 * WordPress owns the base robots output, including the wp-admin rules. This
 * filter keeps that native behavior and adds the sitemap hint crawlers expect.
 *
 * @param string $output Existing robots.txt output.
 * @param bool   $public Whether the site is public to search engines.
 * @return string
 */
function gutenberg_lab_vvm_add_robots_sitemap( $output, $public ) {
	if ( ! $public ) {
		return $output;
	}

	$sitemap_url = esc_url_raw( home_url( '/wp-sitemap.xml' ) );

	if ( str_contains( $output, $sitemap_url ) ) {
		return $output;
	}

	return rtrim( $output ) . "\nSitemap: {$sitemap_url}\n";
}
add_filter( 'robots_txt', 'gutenberg_lab_vvm_add_robots_sitemap', 10, 2 );

/**
 * Adds the villa slug to body classes for page-specific styling hooks.
 *
 * WordPress gives us `postid-*` by default, but slugs are safer across local,
 * staging, and production databases where numeric IDs may not match.
 *
 * @param array $classes Existing body classes.
 * @return array
 */
function gutenberg_lab_vvm_add_villa_slug_body_class( $classes ) {
	if ( ! is_singular( 'villa' ) ) {
		return $classes;
	}

	$villa = get_queried_object();

	if ( $villa instanceof WP_Post && ! empty( $villa->post_name ) ) {
		$classes[] = 'villa-' . sanitize_html_class( $villa->post_name );
	}

	return $classes;
}
add_filter( 'body_class', 'gutenberg_lab_vvm_add_villa_slug_body_class' );

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
 * Returns the queried post when it is protected by a WordPress post password.
 *
 * @return WP_Post|null
 */
function gutenberg_lab_vvm_get_password_protected_queried_post() {
	if ( ! is_singular() ) {
		return null;
	}

	$post = get_queried_object();

	if ( ! $post instanceof WP_Post || '' === (string) $post->post_password ) {
		return null;
	}

	return $post;
}

/**
 * Marks password-protected views as uncacheable for WordPress and proxy caches.
 */
function gutenberg_lab_vvm_disable_password_page_cache() {
	if ( ! gutenberg_lab_vvm_get_password_protected_queried_post() ) {
		return;
	}

	foreach ( array( 'DONOTCACHEPAGE', 'DONOTCACHEOBJECT', 'DONOTCACHEDB' ) as $constant ) {
		if ( ! defined( $constant ) ) {
			define( $constant, true );
		}
	}

	nocache_headers();
	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private', true );
	header( 'Pragma: no-cache', true );
	header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT', true );
	header( 'X-Accel-Expires: 0', true );
	header( 'Surrogate-Control: no-store', true );
}
add_action( 'send_headers', 'gutenberg_lab_vvm_disable_password_page_cache', 0 );

/**
 * Cache-busts the post-password redirect after WordPress sets the unlock cookie.
 *
 * The production proxy cache does not vary on the wp-postpass cookie, so a fresh
 * query string prevents the locked-page cache entry from replaying after submit.
 *
 * @param string $location Redirect destination.
 * @param int    $status   HTTP redirect status.
 * @return string
 */
function gutenberg_lab_vvm_cache_bust_postpass_redirect( $location, $status ) {
	$action = isset( $_GET['action'] ) && is_string( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';

	if ( 'postpass' !== $action || empty( $_POST['post_password'] ) || ! is_string( $_POST['post_password'] ) ) {
		return $location;
	}

	$post_id = url_to_postid( $location );

	if ( ! $post_id || '' === (string) get_post_field( 'post_password', $post_id ) ) {
		return $location;
	}

	return add_query_arg(
		'vvm-postpass',
		wp_generate_password( 12, false, false ),
		remove_query_arg( 'vvm-postpass', $location )
	);
}
add_filter( 'wp_redirect', 'gutenberg_lab_vvm_cache_bust_postpass_redirect', 10, 2 );

/**
 * Replaces WordPress' default password form with the branded page treatment.
 *
 * Password-protected pages render this form instead of their saved block content,
 * so the hero and form shell need to live in PHP rather than inside the editor.
 *
 * @param string       $output Default password form HTML.
 * @param WP_Post|null $post   Protected post, when provided by WordPress.
 * @return string
 */
function gutenberg_lab_vvm_render_password_form( $output, $post = null ) {
	$post                 = $post instanceof WP_Post ? $post : get_post();
	$post_title           = $post instanceof WP_Post ? get_post_field( 'post_title', $post ) : __( 'Protected content', 'gutenberg-lab-vvm' );
	$post_title           = $post_title ? $post_title : __( 'Protected content', 'gutenberg-lab-vvm' );
	$label_id             = 'pwbox-' . ( $post instanceof WP_Post ? (int) $post->ID : wp_rand() );
	$error_id             = 'error-' . $label_id;
	$description_id       = 'description-' . $label_id;
	$redirect_to          = $post instanceof WP_Post ? add_query_arg( 'vvm-postpass', wp_generate_password( 12, false, false ), get_permalink( $post ) ) : '';
	$has_invalid_password = $post instanceof WP_Post && isset( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] ) && post_password_required( $post );
	$describedby          = $has_invalid_password ? $error_id . ' ' . $description_id : $description_id;

	ob_start();
	?>
	<section class="vvm-password-hero alignfull">
		<div class="vvm-password-hero__inner">
			<p class="vvm-password-hero__eyebrow"><?php esc_html_e( 'Private Preview', 'gutenberg-lab-vvm' ); ?></p>
			<h1 class="vvm-password-hero__title"><?php echo esc_html( $post_title ); ?></h1>
			<p class="vvm-password-hero__copy"><?php esc_html_e( 'This page is password protected.', 'gutenberg-lab-vvm' ); ?></p>
		</div>
	</section>

	<section class="vvm-password-panel alignfull">
		<div class="vvm-password-panel__inner">
			<form class="post-password-form vvm-password-form" action="<?php echo esc_url( site_url( 'wp-login.php?action=postpass', 'login_post' ) ); ?>" method="post">
				<?php if ( $redirect_to ) : ?>
					<div class="vvm-password-form__redirect">
						<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>" />
					</div>
				<?php endif; ?>

				<p class="vvm-password-form__eyebrow"><?php esc_html_e( 'Password Required', 'gutenberg-lab-vvm' ); ?></p>
				<h2 class="vvm-password-form__title"><?php esc_html_e( 'Enter the preview password', 'gutenberg-lab-vvm' ); ?></h2>
				<p class="vvm-password-form__copy" id="<?php echo esc_attr( $description_id ); ?>"><?php esc_html_e( 'Use the password shared with you to view this page.', 'gutenberg-lab-vvm' ); ?></p>

				<?php if ( $has_invalid_password ) : ?>
					<div class="vvm-password-form__error" role="alert">
						<p id="<?php echo esc_attr( $error_id ); ?>"><?php esc_html_e( 'The password you entered was incorrect. Please try again.', 'gutenberg-lab-vvm' ); ?></p>
					</div>
				<?php endif; ?>

				<div class="vvm-password-form__label-row">
					<label class="vvm-password-form__label" for="<?php echo esc_attr( $label_id ); ?>"><?php esc_html_e( 'Password', 'gutenberg-lab-vvm' ); ?></label>
				</div>
				<div class="vvm-password-form__fields">
					<div class="vvm-password-form__input-wrap">
						<input class="vvm-password-form__input" name="post_password" id="<?php echo esc_attr( $label_id ); ?>" type="password" autocomplete="current-password" spellcheck="false" required aria-describedby="<?php echo esc_attr( $describedby ); ?>" />
						<button class="vvm-password-form__toggle" type="button" aria-controls="<?php echo esc_attr( $label_id ); ?>" aria-label="<?php esc_attr_e( 'Show password', 'gutenberg-lab-vvm' ); ?>" aria-pressed="false" data-vvm-password-toggle>
							<span class="screen-reader-text" data-vvm-password-toggle-label><?php esc_html_e( 'Show password', 'gutenberg-lab-vvm' ); ?></span>
							<span class="vvm-password-form__icon vvm-password-form__icon--show" aria-hidden="true">
								<svg viewBox="0 0 24 24" focusable="false">
									<path d="M2.06 12a10.75 10.75 0 0 1 19.88 0 10.75 10.75 0 0 1-19.88 0Z" />
									<circle cx="12" cy="12" r="3" />
								</svg>
							</span>
							<span class="vvm-password-form__icon vvm-password-form__icon--hide" aria-hidden="true">
								<svg viewBox="0 0 24 24" focusable="false">
									<path d="M2.06 12a10.75 10.75 0 0 1 19.88 0 10.75 10.75 0 0 1-19.88 0Z" />
									<circle cx="12" cy="12" r="3" />
									<path d="M4 4l16 16" />
								</svg>
							</span>
						</button>
					</div>
					<div class="vvm-password-form__submit-wrap">
						<button class="vvm-password-form__submit" type="submit"><?php esc_html_e( 'Enter', 'gutenberg-lab-vvm' ); ?></button>
					</div>
				</div>
			</form>
		</div>
	</section>
	<?php

	// Collapse template whitespace so wpautop does not inject paragraphs or
	// line breaks into the generated form controls.
	return trim( preg_replace( '/>\s+</', '><', ob_get_clean() ) );
}
add_filter( 'the_password_form', 'gutenberg_lab_vvm_render_password_form', 10, 2 );

/**
 * Adds progressive enhancement for the password visibility toggle and redirect.
 */
function gutenberg_lab_vvm_render_password_form_script() {
	$post = gutenberg_lab_vvm_get_password_protected_queried_post();

	if ( ! $post || ! post_password_required( $post ) ) {
		return;
	}
	?>
	<script>
		(() => {
			document.addEventListener('submit', (event) => {
				if (!event.target.matches('.vvm-password-form')) {
					return;
				}

				const redirect = event.target.querySelector('input[name="redirect_to"]');

				if (!redirect || !redirect.value) {
					return;
				}

				const url = new URL(redirect.value, window.location.origin);
				url.searchParams.set('vvm-postpass', Date.now().toString(36));
				redirect.value = url.toString();
			}, true);

			document.addEventListener('click', (event) => {
				const button = event.target.closest('[data-vvm-password-toggle]');

				if (!button) {
					return;
				}

				const input = document.getElementById(button.getAttribute('aria-controls'));

				if (!input) {
					return;
				}

				const shouldShow = input.type === 'password';
				input.type = shouldShow ? 'text' : 'password';
				const label = shouldShow ? 'Hide password' : 'Show password';
				const labelNode = button.querySelector('[data-vvm-password-toggle-label]');
				button.setAttribute('aria-label', label);
				button.setAttribute('aria-pressed', shouldShow ? 'true' : 'false');

				if (labelNode) {
					labelNode.textContent = label;
				}
			});
		})();
	</script>
	<?php
}
add_action( 'wp_footer', 'gutenberg_lab_vvm_render_password_form_script', 20 );

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

	// Register branded button variants in one place so block markup stays simple.
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

	register_block_style(
		'gutenberg-lab-blocks/card-carousel',
		array(
			'name'         => 'editorial-triptych',
			'label'        => __( 'Editorial Triptych', 'gutenberg-lab-vvm' ),
			'inline_style' => '',
		)
	);

	register_block_style(
		'core/group',
		array(
			// Keep the original slug so existing saved review groups retain this style.
			'name'         => 'vvm-reviews-three-up',
			'label'        => __( 'Reviews: 2 at a time', 'gutenberg-lab-vvm' ),
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
 * Builds a navigation link from a site path and uses the matched post title.
 *
 * This keeps header links Gutenberg-native when the path resolves to a page or
 * CPT entry, while still allowing planned/custom URLs to exist before content
 * is published in the local database.
 *
 * @param string $fallback_label Label to use when the path cannot resolve.
 * @param string $path           Relative site path, for example `/villas/example/`.
 * @param array  $extra_attrs    Optional block attributes such as `className`.
 * @param string $post_type      Optional post type for direct slug lookup.
 * @return string
 */
function gutenberg_lab_vvm_navigation_link_from_path_markup( $fallback_label, $path, $extra_attrs = array(), $post_type = '' ) {
	$url     = gutenberg_lab_vvm_normalize_navigation_url( $path );
	$post    = null;

	if ( '' !== $post_type ) {
		$path_bits = explode( '/', trim( $path, '/' ) );
		$path_slug = end( $path_bits );
		$post      = get_page_by_path( $path_slug, OBJECT, $post_type );

		if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
			$post = null;
		}
	}

	if ( ! $post instanceof WP_Post ) {
		$post_id = url_to_postid( $url );
		$post    = $post_id ? get_post( $post_id ) : null;
	}

	$title   = $post instanceof WP_Post ? get_the_title( $post ) : '';
	$label   = '' !== $title ? $title : $fallback_label;

	if ( $post instanceof WP_Post ) {
		$attributes = array(
			'label' => $label,
			'type'  => $post->post_type,
			'id'    => (int) $post->ID,
			'url'   => $url,
			'kind'  => 'post-type',
		);
	} else {
		$attributes = array(
			'label' => $label,
			'type'  => 'custom',
			'url'   => $url,
			'kind'  => 'custom',
		);
	}

	$attributes = array_merge( $attributes, $extra_attrs );

	return '<!-- wp:navigation-link ' . wp_json_encode( $attributes ) . ' /-->';
}

/**
 * Returns the current header navigation targets.
 *
 * The duplicate Westland URL from the supplied list is intentionally stored
 * once so the visible menu does not repeat the same destination.
 *
 * @return array[]
 */
function gutenberg_lab_vvm_get_header_navigation_links() {
	return array(
		array(
			'label' => 'Monkey Hill',
			'path'  => '/villas/monkey-hill/',
			'type'  => 'villa',
		),
		array(
			'label' => 'Landfall House',
			'path'  => '/villas/landfall-house/',
			'type'  => 'villa',
		),
		array(
			'label' => 'Tara House',
			'path'  => '/villas/tara-house/',
			'type'  => 'villa',
		),
		array(
			'label' => 'Westland Heights Cool Winds',
			'path'  => '/villas/westland-heights-cool-winds/',
			'type'  => 'villa',
		),
		array(
			'label' => 'Ocean Heights',
			'path'  => '/villas/ocean-heights/',
			'type'  => 'villa',
		),
		array(
			'label' => 'Experiences',
			'path'  => '/experiences/',
			'type'  => 'page',
		),
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
		array_map(
			static function ( $link ) {
				return gutenberg_lab_vvm_navigation_link_from_path_markup( $link['label'], $link['path'], array(), $link['type'] );
			},
			gutenberg_lab_vvm_get_header_navigation_links()
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
	return implode(
		"\n\n",
		array_map(
			static function ( $link ) {
				return gutenberg_lab_vvm_navigation_link_from_path_markup( $link['label'], $link['path'], array(), $link['type'] );
			},
			gutenberg_lab_vvm_get_header_navigation_links()
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
	$links             = array();

	if ( $villa_archive_url ) {
		$links[] = array(
			'label' => 'Our Villas',
			'url'   => $villa_archive_url,
		);
	}

	$links = array_merge(
		$links,
		array(
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

	return gutenberg_lab_vvm_custom_navigation_links_markup( $links );
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
 * Seeds the footer navigation entities used by the premium footer template.
 *
 * These posts are only created when missing. Once they exist, Appearance/Site
 * Editor owns their labels, URLs, and ordering.
 *
 * @return array<string, int>
 */
function gutenberg_lab_vvm_seed_footer_navigation_entities() {
	return array(
		'footer_villas'  => (int) gutenberg_lab_vvm_seed_navigation_post(
			'footer-villas-navigation',
			'Footer Villas Navigation',
			gutenberg_lab_vvm_get_footer_villas_navigation_content()
		),
		'footer_explore' => (int) gutenberg_lab_vvm_seed_navigation_post(
			'footer-explore-navigation',
			'Footer Explore Navigation',
			gutenberg_lab_vvm_get_footer_explore_navigation_content()
		),
		'footer_legal'   => (int) gutenberg_lab_vvm_seed_navigation_post(
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
		// Keep the public header aligned with the Site Editor's Primary Navigation.
		$content = preg_replace( '/"ref":\d+/', '"ref":' . (int) $navigation_refs['primary'], $content, 1 );
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
		gutenberg_lab_vvm_seed_footer_navigation_entities()
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

	if ( in_array( $block_name, array( 'core/cover', 'gutenberg-lab-blocks/media-panel', 'gutenberg-lab-blocks/villa-gallery-hero' ), true ) ) {
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
	// The single-villa template now starts with the dedicated gallery hero, so
	// the global header should always switch to overlay mode on that content type.
	if ( is_singular( 'villa' ) ) {
		return true;
	}

	if ( ! is_singular() ) {
		return false;
	}

	$post = get_queried_object();

	if ( ! $post instanceof WP_Post || empty( $post->post_content ) ) {
		return false;
	}

	// Protected content is replaced by the password form, so do not let a hidden
	// saved hero switch the header into overlay mode above the password screen.
	if ( post_password_required( $post ) ) {
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
 * Remove stale Google Font resource hints now that theme fonts are self-hosted.
 *
 * Autoptimize can keep printing a fonts.gstatic.com preconnect after the theme
 * stops enqueueing Google Fonts, so we strip those hints at the theme boundary.
 */
function gutenberg_lab_vvm_remove_google_font_resource_hints( $urls, $relation_type ) {
	if ( ! in_array( $relation_type, array( 'dns-prefetch', 'preconnect' ), true ) ) {
		return $urls;
	}

	return array_values(
		array_filter(
			$urls,
			static function ( $url ) {
				$href = is_array( $url ) ? ( $url['href'] ?? '' ) : $url;
				$host = wp_parse_url( $href, PHP_URL_HOST );

				return ! in_array( $host, array( 'fonts.googleapis.com', 'fonts.gstatic.com' ), true );
			}
		)
	);
}
add_filter( 'wp_resource_hints', 'gutenberg_lab_vvm_remove_google_font_resource_hints', 20, 2 );

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
		array(
			'wp-block-editor',
			'wp-blocks',
			'wp-components',
			'wp-compose',
			'wp-dom-ready',
			'wp-element',
			'wp-hooks',
			'wp-i18n',
		),
		gutenberg_lab_vvm_asset_version( 'assets/js/block-editor.js' ),
		array(
			'in_footer' => true,
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'gutenberg_lab_vvm_enqueue_block_editor_script' );

/**
 * Checks whether the current frontend request needs Contact Form 7 assets.
 *
 * Contact Form 7 loads its stylesheet globally by default. That stylesheet is
 * render-blocking, so we only keep it on singular content that actually embeds
 * a CF7 block or shortcode.
 *
 * @return bool
 */
function gutenberg_lab_vvm_should_load_contact_form_7_assets() {
	if ( is_admin() || wp_doing_ajax() || is_preview() ) {
		return true;
	}

	if ( ! is_singular() ) {
		return false;
	}

	$post = get_post();

	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	return has_block( 'contact-form-7/contact-form-selector', $post )
		|| has_shortcode( $post->post_content, 'contact-form-7' );
}

/**
 * Prevents unused Contact Form 7 CSS/JS from loading on pages without forms.
 *
 * @param bool $load Whether Contact Form 7 planned to load the asset.
 * @return bool
 */
function gutenberg_lab_vvm_load_contact_form_7_assets_when_needed( $load ) {
	if ( ! $load ) {
		return false;
	}

	return gutenberg_lab_vvm_should_load_contact_form_7_assets();
}
add_filter( 'wpcf7_load_css', 'gutenberg_lab_vvm_load_contact_form_7_assets_when_needed', 20 );
add_filter( 'wpcf7_load_js', 'gutenberg_lab_vvm_load_contact_form_7_assets_when_needed', 20 );

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

	wp_enqueue_script(
		'gutenberg-lab-vvm-villa-reviews-carousel',
		get_theme_file_uri( 'assets/js/villa-reviews-carousel.js' ),
		array(),
		gutenberg_lab_vvm_asset_version( 'assets/js/villa-reviews-carousel.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	// Load the Elfsight platform once; the footer widget containers below tell
	// Elfsight which All-in-One Chat widgets to render.
	wp_enqueue_script(
		'gutenberg-lab-vvm-elfsight-platform',
		'https://elfsightcdn.com/platform.js',
		array(),
		null,
		array(
			'in_footer' => true,
			'strategy'  => 'async',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'gutenberg_lab_vvm_enqueue_assets' );

/**
 * Prints the Elfsight All-in-One Chat mount nodes on every public page.
 *
 * Elfsight's floating widgets are installed site-wide by placing these elements
 * near the closing body tag; WordPress exposes that position through wp_footer.
 */
function gutenberg_lab_vvm_render_elfsight_chat() {
	echo '<div class="elfsight-app-00ed413b-b198-48a2-a248-4560584516e6" data-elfsight-app-lazy></div>' . "\n";
	echo '<div class="elfsight-app-5f976442-bb60-49fe-837c-7c24128cac9d" data-elfsight-app-lazy></div>' . "\n";
}
add_action( 'wp_footer', 'gutenberg_lab_vvm_render_elfsight_chat' );

/**
 * Defers Google Map iframe loading for the saved gmap block.
 *
 * The important performance detail is that we remove the iframe `src` during
 * PHP render. If we waited until JavaScript runs, the browser could already
 * start downloading Google Maps before our lazy-loader gets a chance to act.
 *
 * @param string $block_content Rendered block HTML.
 * @param array  $block         Parsed block data.
 * @return string
 */
function gutenberg_lab_vvm_lazy_load_gmap_block( $block_content, $block ) {
	if (
		is_admin() ||
		empty( $block['blockName'] ) ||
		'gmap/gmap-block' !== $block['blockName'] ||
		! class_exists( 'WP_HTML_Tag_Processor' )
	) {
		return $block_content;
	}

	$processor = new WP_HTML_Tag_Processor( $block_content );

	if ( ! $processor->next_tag( array( 'class_name' => 'wp-block-gmap-gmap-block' ) ) ) {
		return $block_content;
	}

	$wrapper_classes = trim( (string) $processor->get_attribute( 'class' ) );
	$processor->set_attribute( 'class', trim( $wrapper_classes . ' vvm-lazy-map' ) );
	$processor->set_attribute( 'data-vvm-lazy-map', '' );

	if ( ! $processor->next_tag( 'iframe' ) ) {
		return $block_content;
	}

	$map_src = $processor->get_attribute( 'src' );
	$map_host = is_string( $map_src ) ? wp_parse_url( $map_src, PHP_URL_HOST ) : '';

	if (
		! is_string( $map_src ) ||
		'' === trim( $map_src ) ||
		! is_string( $map_host ) ||
		( ! str_starts_with( $map_host, 'maps.google.' ) && 'www.google.com' !== $map_host )
	) {
		return $block_content;
	}

	$map_title = $processor->get_attribute( 'title' );
	$map_title = is_string( $map_title ) && '' !== trim( $map_title )
		? $map_title
		: __( 'Google map', 'gutenberg-lab-vvm' );

	$processor->set_attribute( 'data-src', $map_src );
	$processor->set_attribute( 'data-vvm-lazy-map-frame', '' );
	$processor->set_attribute( 'loading', 'lazy' );
	$processor->set_attribute( 'aria-hidden', 'true' );
	$processor->set_attribute( 'tabindex', '-1' );
	$processor->set_attribute( 'hidden', '' );
	$processor->remove_attribute( 'src' );

	$updated_content = $processor->get_updated_html();
	$noscript_map    = sprintf(
		'<noscript><iframe src="%1$s" class="embd-map" title="%2$s" loading="lazy"></iframe></noscript>',
		esc_url( $map_src ),
		esc_attr( $map_title )
	);
	$insert_at       = strrpos( $updated_content, '</div>' );

	if ( false === $insert_at ) {
		return $updated_content . $noscript_map;
	}

	return substr_replace( $updated_content, $noscript_map, $insert_at, 0 );
}
add_filter( 'render_block', 'gutenberg_lab_vvm_lazy_load_gmap_block', 10, 2 );

/**
 * Parses the ISO date string emitted by native date inputs.
 *
 * @param string $value Raw submitted date value.
 * @return DateTimeImmutable|null
 */
function gutenberg_lab_vvm_parse_cf7_date_value( $value ) {
	$value = trim( (string) $value );

	if ( '' === $value ) {
		return null;
	}

	$date   = DateTimeImmutable::createFromFormat( '!Y-m-d', $value );
	$errors = DateTimeImmutable::getLastErrors();

	if (
		false === $date ||
		( is_array( $errors ) && ( $errors['warning_count'] > 0 || $errors['error_count'] > 0 ) )
	) {
		return null;
	}

	return $date;
}

/**
 * Ensures the villa availability departure date is not before arrival.
 *
 * Browser date controls improve the authoring experience, but CF7 validation
 * remains the hard rule so direct POST requests cannot submit an invalid range.
 *
 * @param WPCF7_Validation $result Current validation result.
 * @param WPCF7_FormTag    $tag    Current form tag.
 * @return WPCF7_Validation
 */
function gutenberg_lab_vvm_validate_villa_availability_date_range( $result, $tag ) {
	if ( ! $tag instanceof WPCF7_FormTag && class_exists( 'WPCF7_FormTag' ) ) {
		$tag = new WPCF7_FormTag( $tag );
	}

	if ( ! $tag instanceof WPCF7_FormTag || 'preferred-departure' !== $tag->name ) {
		return $result;
	}

	$arrival_value   = isset( $_POST['preferred-arrival'] ) ? sanitize_text_field( wp_unslash( $_POST['preferred-arrival'] ) ) : '';
	$departure_value = isset( $_POST['preferred-departure'] ) ? sanitize_text_field( wp_unslash( $_POST['preferred-departure'] ) ) : '';
	$arrival_date    = gutenberg_lab_vvm_parse_cf7_date_value( $arrival_value );
	$departure_date  = gutenberg_lab_vvm_parse_cf7_date_value( $departure_value );

	if ( null !== $arrival_date && null !== $departure_date && $departure_date < $arrival_date ) {
		$result->invalidate(
			$tag,
			__( 'Departure date cannot be before arrival date.', 'gutenberg-lab-vvm' )
		);
	}

	return $result;
}
add_filter( 'wpcf7_validate_date', 'gutenberg_lab_vvm_validate_villa_availability_date_range', 20, 2 );
add_filter( 'wpcf7_validate_date*', 'gutenberg_lab_vvm_validate_villa_availability_date_range', 20, 2 );
