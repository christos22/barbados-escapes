<?php
/**
 * SEO metadata and structured data for Barbados Escapes.
 *
 * This is intentionally theme-level because the site does not currently use an
 * SEO plugin and the metadata is tied to the custom villa marketing experience.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns hand-authored SEO copy for the launch pages and villas.
 *
 * Slugs are used instead of IDs so the same rules survive local, staging, and
 * production database differences.
 *
 * @return array<string, mixed>
 */
function gutenberg_lab_vvm_seo_known_metadata() {
	return array(
		'front'  => array(
			'title'       => 'Luxury Barbados Villas & Private Escapes | Barbados Escapes',
			'description' => 'Discover luxury Barbados villa rentals, from beachfront Sandy Lane estates to private retreats with curated island experiences.',
		),
		'pages'  => array(
			'barbados-experiences' => array(
				'title'       => 'Private Barbados Experiences & Concierge Services | Barbados Escapes',
				'description' => 'Plan private Barbados experiences with Barbados Escapes, from island dining and boat days to tailored concierge moments around your villa stay.',
				'schema'      => 'service',
			),
			'privacy-policy'       => array(
				'title'       => 'Privacy Policy | Barbados Escapes',
				'description' => 'Read the Barbados Escapes privacy policy for details on how enquiry and website information is handled.',
			),
			'site-map'             => array(
				'title'       => 'Sitemap | Barbados Escapes',
				'description' => 'Find the main Barbados Escapes pages, villa listings, experiences, privacy policy, and contact pathways.',
			),
		),
		'villas' => array(
			'monkey-hill'    => array(
				'title'       => 'Monkey Hill Villa Rental in St James, Barbados | Barbados Escapes',
				'description' => 'Explore Monkey Hill, a private St James villa with sea views, layered terraces, poolside living, and curated Barbados concierge support.',
				'location'    => 'St James',
			),
			'landfall-house' => array(
				'title'       => 'Landfall House, Sandy Lane Beachfront Villa | Barbados Escapes',
				'description' => 'Explore Landfall House, a luxury beachfront villa on Sandy Lane Beach with staff, pool, gardens, and direct access to Barbados\' west coast.',
				'location'    => 'Sandy Lane, St James',
			),
			'tara-house'     => array(
				'title'       => 'Tara House Villa in St Peter, Barbados | Barbados Escapes',
				'description' => 'Explore Tara House, a serene Barbados villa with sea views, mature gardens, saltwater pool, yoga pavilion, gym, sauna, and breezy terraces.',
				'location'    => 'St Peter',
			),
		),
	);
}

/**
 * Normalizes long text into a concise search description.
 *
 * @param string $text Raw description.
 * @return string
 */
function gutenberg_lab_vvm_seo_clean_description( $text ) {
	$text = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, get_bloginfo( 'charset' ) );
	$text = preg_replace( '/\s+/', ' ', $text );
	$text = is_string( $text ) ? trim( $text ) : '';

	if ( strlen( $text ) <= 158 ) {
		return $text;
	}

	return rtrim( wp_html_excerpt( $text, 155, '' ), " \t\n\r\0\x0B.,;:" ) . '...';
}

/**
 * Returns a front-end URL for the current queried object.
 *
 * @param WP_Post|WP_Term|null $object Current object.
 * @return string
 */
function gutenberg_lab_vvm_seo_get_object_url( $object = null ) {
	if ( is_front_page() ) {
		return home_url( '/' );
	}

	if ( $object instanceof WP_Post ) {
		$permalink = get_permalink( $object );
		return is_string( $permalink ) ? $permalink : home_url( '/' );
	}

	if ( $object instanceof WP_Term ) {
		$link = get_term_link( $object );
		return is_wp_error( $link ) ? home_url( '/' ) : $link;
	}

	return home_url( add_query_arg( array(), $GLOBALS['wp']->request ?? '' ) );
}

/**
 * Returns normalized SEO context for the current request.
 *
 * @return array<string, mixed>|null
 */
function gutenberg_lab_vvm_seo_get_context() {
	if ( is_admin() || is_feed() || is_404() || is_search() ) {
		return null;
	}

	$known = gutenberg_lab_vvm_seo_known_metadata();
	$post  = is_singular() ? get_queried_object() : null;
	$term  = is_tax() ? get_queried_object() : null;

	$context = array(
		'title'       => '',
		'description' => '',
		'url'         => gutenberg_lab_vvm_seo_get_object_url( $post instanceof WP_Post ? $post : ( $term instanceof WP_Term ? $term : null ) ),
		'object'      => $post instanceof WP_Post ? $post : null,
		'term'        => $term instanceof WP_Term ? $term : null,
		'kind'        => 'webpage',
		'schema'      => '',
	);

	if ( is_front_page() ) {
		$context['title']       = $known['front']['title'];
		$context['description'] = $known['front']['description'];
		$context['kind']        = 'front';
		return $context;
	}

	if ( $post instanceof WP_Post ) {
		$slug = $post->post_name;

		if ( 'page' === $post->post_type && isset( $known['pages'][ $slug ] ) ) {
			$context = array_merge( $context, $known['pages'][ $slug ] );
			$context['kind'] = 'page';
			return $context;
		}

		if ( 'villa' === $post->post_type && isset( $known['villas'][ $slug ] ) ) {
			$context = array_merge( $context, $known['villas'][ $slug ] );
			$context['kind'] = 'villa';
			return $context;
		}

		$description = '' !== trim( (string) $post->post_excerpt )
			? $post->post_excerpt
			: wp_trim_words( wp_strip_all_tags( $post->post_content ), 28 );

		$context['title']       = sprintf( '%s | %s', get_the_title( $post ), get_bloginfo( 'name' ) );
		$context['description'] = gutenberg_lab_vvm_seo_clean_description( $description );
		$context['kind']        = 'villa' === $post->post_type ? 'villa' : 'page';
		return $context;
	}

	if ( $term instanceof WP_Term && 'villa_location' === $term->taxonomy ) {
		$context['title']       = sprintf( '%s Barbados Villas | Barbados Escapes', $term->name );
		$context['description'] = gutenberg_lab_vvm_seo_clean_description(
			sprintf( 'Explore Barbados Escapes villas in %s, with private stays and curated island support.', $term->name )
		);
		$context['kind']        = 'taxonomy';
		return $context;
	}

	if ( $term instanceof WP_Term && 'villa_amenity' === $term->taxonomy ) {
		$context['title']       = sprintf( 'Barbados Villas with %s | Barbados Escapes', $term->name );
		$context['description'] = gutenberg_lab_vvm_seo_clean_description(
			sprintf( 'Browse Barbados Escapes villas featuring %s and curated private-villa amenities.', $term->name )
		);
		$context['kind']        = 'taxonomy';
		return $context;
	}

	return null;
}

/**
 * Replaces WordPress's generic document title with launch-specific SEO titles.
 *
 * @param string $title Existing document title.
 * @return string
 */
function gutenberg_lab_vvm_seo_document_title( $title ) {
	$context = gutenberg_lab_vvm_seo_get_context();

	return $context && ! empty( $context['title'] ) ? $context['title'] : $title;
}
add_filter( 'pre_get_document_title', 'gutenberg_lab_vvm_seo_document_title', 20 );

/**
 * Returns attachment image data for SEO/social metadata.
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $fallback_alt  Context-aware fallback alt text.
 * @return array<string, mixed>|null
 */
function gutenberg_lab_vvm_seo_get_attachment_image( $attachment_id, $fallback_alt = '' ) {
	$attachment_id = (int) $attachment_id;

	if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
		return null;
	}

	$image = wp_get_attachment_image_src( $attachment_id, 'full' );

	if ( ! is_array( $image ) || empty( $image[0] ) ) {
		return null;
	}

	$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

	if ( '' === trim( (string) $alt ) ) {
		$alt = get_the_title( $attachment_id );
	}

	return array(
		'url'    => $image[0],
		'width'  => isset( $image[1] ) ? (int) $image[1] : 0,
		'height' => isset( $image[2] ) ? (int) $image[2] : 0,
		'alt'    => gutenberg_lab_vvm_seo_clean_image_alt( $alt, $fallback_alt ),
	);
}

/**
 * Returns useful alt text for metadata without leaking placeholder filenames.
 *
 * @param string $alt          Attachment alt/title candidate.
 * @param string $fallback_alt Context-aware fallback, usually the page title.
 * @return string
 */
function gutenberg_lab_vvm_seo_clean_image_alt( $alt, $fallback_alt = '' ) {
	$alt     = trim( (string) $alt );
	$generic = array( 'default', 'image', 'img', 'photo', 'untitled' );

	if ( '' === $alt || in_array( strtolower( $alt ), $generic, true ) ) {
		return trim( (string) $fallback_alt );
	}

	return $alt;
}

/**
 * Finds the first image attachment referenced inside saved block content.
 *
 * @param array[] $blocks Parsed block tree.
 * @return int
 */
function gutenberg_lab_vvm_seo_find_first_block_image_id( $blocks ) {
	foreach ( (array) $blocks as $block ) {
		$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();

		foreach ( array( 'id', 'mediaId', 'imageId', 'backgroundImageId' ) as $key ) {
			if ( ! empty( $attrs[ $key ] ) && wp_attachment_is_image( (int) $attrs[ $key ] ) ) {
				return (int) $attrs[ $key ];
			}
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			$image_id = gutenberg_lab_vvm_seo_find_first_block_image_id( $block['innerBlocks'] );

			if ( $image_id ) {
				return $image_id;
			}
		}
	}

	return 0;
}

/**
 * Returns the best available image for the current page.
 *
 * @param WP_Post|null $post Current post, if any.
 * @return array<string, mixed>|null
 */
function gutenberg_lab_vvm_seo_get_primary_image( $post = null ) {
	if ( $post instanceof WP_Post ) {
		$fallback_alt = get_the_title( $post );
		$thumbnail_id = get_post_thumbnail_id( $post );

		if ( $thumbnail_id ) {
			$image = gutenberg_lab_vvm_seo_get_attachment_image( $thumbnail_id, $fallback_alt );

			if ( $image ) {
				return $image;
			}
		}

		$content_image_id = gutenberg_lab_vvm_seo_find_first_block_image_id( parse_blocks( $post->post_content ) );

		if ( $content_image_id ) {
			$image = gutenberg_lab_vvm_seo_get_attachment_image( $content_image_id, $fallback_alt );

			if ( $image ) {
				return $image;
			}
		}
	}

	$custom_logo_id = (int) get_theme_mod( 'custom_logo' );

	if ( $custom_logo_id ) {
		$image = gutenberg_lab_vvm_seo_get_attachment_image( $custom_logo_id, get_bloginfo( 'name' ) );

		if ( $image ) {
			return $image;
		}
	}

	$site_icon_url = get_site_icon_url( 512 );

	if ( $site_icon_url ) {
		return array(
			'url'    => $site_icon_url,
			'width'  => 512,
			'height' => 512,
			'alt'    => get_bloginfo( 'name' ),
		);
	}

	return null;
}

/**
 * Prints one meta tag.
 *
 * @param string $name    Attribute name.
 * @param string $value   Attribute value.
 * @param string $content Meta content.
 */
function gutenberg_lab_vvm_seo_print_meta( $name, $value, $content ) {
	if ( '' === trim( (string) $content ) ) {
		return;
	}

	printf(
		"<meta %s=\"%s\" content=\"%s\" />\n",
		esc_attr( $name ),
		esc_attr( $value ),
		esc_attr( $content )
	);
}

/**
 * Returns a compact image object for JSON-LD.
 *
 * @param array<string, mixed>|null $image Image data.
 * @return array<string, mixed>|null
 */
function gutenberg_lab_vvm_seo_get_image_schema( $image ) {
	if ( empty( $image['url'] ) ) {
		return null;
	}

	return gutenberg_lab_vvm_seo_remove_empty(
		array(
			'@type'  => 'ImageObject',
			'url'    => $image['url'],
			'width'  => ! empty( $image['width'] ) ? (int) $image['width'] : null,
			'height' => ! empty( $image['height'] ) ? (int) $image['height'] : null,
			'caption' => ! empty( $image['alt'] ) ? $image['alt'] : null,
		)
	);
}

/**
 * Recursively removes empty values from schema arrays.
 *
 * @param mixed $value Raw value.
 * @return mixed
 */
function gutenberg_lab_vvm_seo_remove_empty( $value ) {
	if ( ! is_array( $value ) ) {
		return $value;
	}

	$clean = array();

	foreach ( $value as $key => $item ) {
		$item = gutenberg_lab_vvm_seo_remove_empty( $item );

		if ( null === $item || '' === $item || ( is_array( $item ) && empty( $item ) ) ) {
			continue;
		}

		$clean[ $key ] = $item;
	}

	return $clean;
}

/**
 * Returns the shared Barbados Escapes organization schema.
 *
 * @param array<string, mixed>|null $logo Logo image data.
 * @return array<string, mixed>
 */
function gutenberg_lab_vvm_seo_get_organization_schema( $logo ) {
	$site_url = home_url( '/' );
	$org_id   = trailingslashit( $site_url ) . '#organization';

	return gutenberg_lab_vvm_seo_remove_empty(
		array(
			'@type'        => array( 'Organization', 'TravelAgency' ),
			'@id'          => $org_id,
			'name'         => get_bloginfo( 'name' ),
			'url'          => $site_url,
			'logo'         => gutenberg_lab_vvm_seo_get_image_schema( $logo ),
			'image'        => ! empty( $logo['url'] ) ? $logo['url'] : null,
			'email'        => 'info@barbadosescapes.com',
			'telephone'    => '+1-246-850-5656',
			'address'      => array(
				'@type'           => 'PostalAddress',
				'addressLocality' => 'St James',
				'addressRegion'   => 'Barbados',
				'addressCountry'  => 'BB',
			),
			'areaServed'   => array(
				'@type' => 'Country',
				'name'  => 'Barbados',
			),
			'contactPoint' => array(
				'@type'       => 'ContactPoint',
				'telephone'   => '+1-246-850-5656',
				'email'       => 'info@barbadosescapes.com',
				'contactType' => 'customer service',
				'areaServed'  => 'BB',
				'availableLanguage' => 'English',
			),
			'sameAs'       => array(
				'https://www.instagram.com/barbadosescapes/',
			),
		)
	);
}

/**
 * Returns breadcrumb items for the current request.
 *
 * @param array<string, mixed> $context SEO context.
 * @return array<int, array<string, string>>
 */
function gutenberg_lab_vvm_seo_get_breadcrumb_items( $context ) {
	$items = array(
		array(
			'name' => 'Home',
			'url'  => home_url( '/' ),
		),
	);

	if ( 'front' === $context['kind'] ) {
		return $items;
	}

	if ( 'villa' === $context['kind'] ) {
		$items[] = array(
			'name' => 'Villas',
			'url'  => home_url( '/#explore-villas' ),
		);
	}

	if ( ! empty( $context['title'] ) ) {
		$items[] = array(
			'name' => preg_replace( '/\s+\|\s+Barbados Escapes$/', '', $context['title'] ),
			'url'  => $context['url'],
		);
	}

	return $items;
}

/**
 * Returns BreadcrumbList schema for the current page.
 *
 * @param array<string, mixed> $context SEO context.
 * @return array<string, mixed>
 */
function gutenberg_lab_vvm_seo_get_breadcrumb_schema( $context ) {
	$items = array();

	foreach ( gutenberg_lab_vvm_seo_get_breadcrumb_items( $context ) as $position => $item ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $position + 1,
			'name'     => $item['name'],
			'item'     => $item['url'],
		);
	}

	return array(
		'@type'           => 'BreadcrumbList',
		'@id'             => $context['url'] . '#breadcrumbs',
		'itemListElement' => $items,
	);
}

/**
 * Returns location names assigned to one villa.
 *
 * @param int $villa_id Villa post ID.
 * @return string[]
 */
function gutenberg_lab_vvm_seo_get_villa_location_names( $villa_id ) {
	$terms = wp_get_object_terms( $villa_id, 'villa_location', array( 'fields' => 'names' ) );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}

	return array_values( array_filter( array_map( 'sanitize_text_field', $terms ) ) );
}

/**
 * Returns amenityFeature schema from assigned amenity terms.
 *
 * @param int $villa_id Villa post ID.
 * @return array<int, array<string, mixed>>
 */
function gutenberg_lab_vvm_seo_get_villa_amenity_schema( $villa_id ) {
	if ( ! function_exists( 'gutenberg_lab_blocks_get_villa_amenities' ) ) {
		return array();
	}

	$features = array();

	foreach ( gutenberg_lab_blocks_get_villa_amenities( $villa_id ) as $amenity ) {
		if ( empty( $amenity['name'] ) ) {
			continue;
		}

		$features[] = array(
			'@type' => 'LocationFeatureSpecification',
			'name'  => $amenity['name'],
			'value' => true,
		);
	}

	return $features;
}

/**
 * Returns schema for a villa page.
 *
 * @param array<string, mixed>      $context SEO context.
 * @param array<string, mixed>|null $image   Primary image.
 * @return array<string, mixed>|null
 */
function gutenberg_lab_vvm_seo_get_villa_schema( $context, $image ) {
	$post = $context['object'] ?? null;

	if ( ! $post instanceof WP_Post ) {
		return null;
	}

	$locations = gutenberg_lab_vvm_seo_get_villa_location_names( $post->ID );
	$known_location = isset( $context['location'] ) ? (string) $context['location'] : '';
	$locality = '' !== $known_location ? $known_location : ( $locations[0] ?? 'Barbados' );
	$facts = get_post_meta( $post->ID, 'villa_card_facts', true );
	$price_range = '';

	if ( is_string( $facts ) && preg_match( '/From\s+\$[0-9,]+/i', $facts, $matches ) ) {
		$price_range = $matches[0];
	}

	return gutenberg_lab_vvm_seo_remove_empty(
		array(
			'@type'          => 'VacationRental',
			'@id'            => $context['url'] . '#vacation-rental',
			'name'           => get_the_title( $post ),
			'url'            => $context['url'],
			'description'    => $context['description'],
			'image'          => ! empty( $image['url'] ) ? array( $image['url'] ) : null,
			'address'        => array(
				'@type'           => 'PostalAddress',
				'addressLocality' => $locality,
				'addressRegion'   => 'Barbados',
				'addressCountry'  => 'BB',
			),
			'containedInPlace' => array(
				'@type' => 'Place',
				'name'  => 'Barbados',
			),
			'amenityFeature' => gutenberg_lab_vvm_seo_get_villa_amenity_schema( $post->ID ),
			'additionalProperty' => '' !== trim( (string) $facts )
				? array(
					array(
						'@type' => 'PropertyValue',
						'name'  => 'Villa facts',
						'value' => sanitize_text_field( $facts ),
					),
				)
				: null,
			'priceRange'     => $price_range,
			'telephone'      => '+1-246-850-5656',
			'email'          => 'info@barbadosescapes.com',
			'branchOf'       => array(
				'@id' => home_url( '/#organization' ),
			),
		)
	);
}

/**
 * Returns Service schema for the Barbados Experiences page.
 *
 * @param array<string, mixed> $context SEO context.
 * @return array<string, mixed>
 */
function gutenberg_lab_vvm_seo_get_experiences_service_schema( $context ) {
	return array(
		'@type'       => 'Service',
		'@id'         => $context['url'] . '#service',
		'name'        => 'Private Barbados Experiences and Concierge Services',
		'url'         => $context['url'],
		'description' => $context['description'],
		'provider'    => array(
			'@id' => home_url( '/#organization' ),
		),
		'areaServed'  => array(
			'@type' => 'Country',
			'name'  => 'Barbados',
		),
		'serviceType' => array(
			'Private villa concierge',
			'Barbados experience planning',
			'Luxury travel support',
		),
	);
}

/**
 * Builds all JSON-LD for the current page.
 *
 * @param array<string, mixed>      $context SEO context.
 * @param array<string, mixed>|null $image   Primary image.
 * @return array<string, mixed>
 */
function gutenberg_lab_vvm_seo_get_schema_graph( $context, $image ) {
	$site_url   = home_url( '/' );
	$website_id = trailingslashit( $site_url ) . '#website';
	$org_id     = trailingslashit( $site_url ) . '#organization';

	$graph = array(
		gutenberg_lab_vvm_seo_get_organization_schema( gutenberg_lab_vvm_seo_get_primary_image() ),
		array(
			'@type'     => 'WebSite',
			'@id'       => $website_id,
			'url'       => $site_url,
			'name'      => get_bloginfo( 'name' ),
			'publisher' => array(
				'@id' => $org_id,
			),
			'inLanguage' => get_bloginfo( 'language' ),
		),
		gutenberg_lab_vvm_seo_remove_empty(
			array(
				'@type'       => 'WebPage',
				'@id'         => $context['url'] . '#webpage',
				'url'         => $context['url'],
				'name'        => $context['title'],
				'description' => $context['description'],
				'isPartOf'    => array(
					'@id' => $website_id,
				),
				'publisher'   => array(
					'@id' => $org_id,
				),
				'primaryImageOfPage' => gutenberg_lab_vvm_seo_get_image_schema( $image ),
				'breadcrumb'  => array(
					'@id' => $context['url'] . '#breadcrumbs',
				),
				'inLanguage'  => get_bloginfo( 'language' ),
			)
		),
		gutenberg_lab_vvm_seo_get_breadcrumb_schema( $context ),
	);

	if ( 'villa' === $context['kind'] ) {
		$villa_schema = gutenberg_lab_vvm_seo_get_villa_schema( $context, $image );

		if ( $villa_schema ) {
			$graph[] = $villa_schema;
		}
	}

	if ( 'service' === ( $context['schema'] ?? '' ) ) {
		$graph[] = gutenberg_lab_vvm_seo_get_experiences_service_schema( $context );
	}

	return array(
		'@context' => 'https://schema.org',
		'@graph'   => array_values( array_map( 'gutenberg_lab_vvm_seo_remove_empty', $graph ) ),
	);
}

/**
 * Prints SEO meta tags and JSON-LD in the document head.
 */
function gutenberg_lab_vvm_seo_print_head_metadata() {
	$context = gutenberg_lab_vvm_seo_get_context();

	if ( ! $context || empty( $context['title'] ) || empty( $context['description'] ) ) {
		return;
	}

	$image = gutenberg_lab_vvm_seo_get_primary_image( $context['object'] ?? null );
	$type  = 'front' === $context['kind'] ? 'website' : 'article';

	gutenberg_lab_vvm_seo_print_meta( 'name', 'description', $context['description'] );
	gutenberg_lab_vvm_seo_print_meta( 'property', 'og:locale', str_replace( '-', '_', get_bloginfo( 'language' ) ) );
	gutenberg_lab_vvm_seo_print_meta( 'property', 'og:type', $type );
	gutenberg_lab_vvm_seo_print_meta( 'property', 'og:title', $context['title'] );
	gutenberg_lab_vvm_seo_print_meta( 'property', 'og:description', $context['description'] );
	gutenberg_lab_vvm_seo_print_meta( 'property', 'og:url', $context['url'] );
	gutenberg_lab_vvm_seo_print_meta( 'property', 'og:site_name', get_bloginfo( 'name' ) );

	if ( ! empty( $image['url'] ) ) {
		gutenberg_lab_vvm_seo_print_meta( 'property', 'og:image', $image['url'] );
		gutenberg_lab_vvm_seo_print_meta( 'property', 'og:image:alt', $image['alt'] ?? '' );
		gutenberg_lab_vvm_seo_print_meta( 'name', 'twitter:image', $image['url'] );

		if ( ! empty( $image['width'] ) ) {
			gutenberg_lab_vvm_seo_print_meta( 'property', 'og:image:width', (string) $image['width'] );
		}

		if ( ! empty( $image['height'] ) ) {
			gutenberg_lab_vvm_seo_print_meta( 'property', 'og:image:height', (string) $image['height'] );
		}
	}

	gutenberg_lab_vvm_seo_print_meta( 'name', 'twitter:card', ! empty( $image['url'] ) ? 'summary_large_image' : 'summary' );
	gutenberg_lab_vvm_seo_print_meta( 'name', 'twitter:title', $context['title'] );
	gutenberg_lab_vvm_seo_print_meta( 'name', 'twitter:description', $context['description'] );

	$schema = wp_json_encode(
		gutenberg_lab_vvm_seo_get_schema_graph( $context, $image ),
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	);

	if ( is_string( $schema ) && '' !== $schema ) {
		printf( "<script type=\"application/ld+json\">%s</script>\n", $schema ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'gutenberg_lab_vvm_seo_print_head_metadata', 5 );
