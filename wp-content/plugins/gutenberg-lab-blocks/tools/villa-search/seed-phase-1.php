<?php
/**
 * Import the client-approved Phase 1 villa search data.
 *
 * Usage:
 *   wp eval-file tools/villa-search/seed-phase-1.php
 *   wp eval-file tools/villa-search/seed-phase-1.php apply
 *
 * The default run is read-only. Pass `apply` only after reviewing its report.
 * This script intentionally leaves Landfall's guest capacity unchanged until
 * the client confirms whether the approved value is 12 or 14.
 *
 * @package GutenbergLabBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$apply = isset( $args[0] ) && 'apply' === $args[0];

// Values are transcribed from the approved client workbook. Prices are not
// duplicated here: each price is read from the villa specs ribbon below.
$approved_villas = array(
	'monkey-hill'       => array(
		'bedrooms'   => 8,
		'sleeps'     => 16,
		'location'   => 'Sugar Hill Villas',
		'collections' => array( 'Wedding Villas', 'Family Villas', 'Villas with Pools', 'Ridgefront Villas', 'Villas with Community Gym' ),
	),
	'ocean-heights'     => array(
		'bedrooms'   => 7,
		'sleeps'     => 12,
		'location'   => 'Prospect',
		'collections' => array( 'Family Villas', 'Beachfront Villas' ),
	),
	'landfall-house'    => array(
		'bedrooms'    => 6,
		'sleeps'      => null,
		'location'    => 'Sandy Lane',
		'collections' => array( 'Beachfront Villas', 'Wedding Villas', 'Family Villas', 'Villas with Pools', 'Fully Staffed Villas' ),
	),
	'tara-house'        => array(
		'bedrooms'    => 4,
		'sleeps'      => 8,
		'location'    => 'Speightstown',
		'collections' => array( 'Family Villas', 'Villas with Pools', 'Villas with Private Gym / Gym Equipment', 'Ridgefront Villas', 'Fully Staffed Villas' ),
	),
	'point-of-view'     => array(
		'bedrooms'    => 5,
		'sleeps'      => 10,
		'location'    => 'Sandy Lane',
		'collections' => array( 'Family Villas', 'Villas with Pools', 'Ridgefront Villas', 'Fully Staffed Villas', 'Villas with Beach Club Access' ),
	),
	'benjoli-breeze'    => array(
		'bedrooms'    => 5,
		'sleeps'      => 10,
		'location'    => 'Royal Westmoreland',
		'collections' => array( 'Family Villas', 'Villas with Pools', 'Golf Resort Villas', 'Fully Staffed Villas', 'Villas with Community Gym', 'Villas with Beach Club Access' ),
	),
	'bananaquit'        => array(
		'bedrooms'    => 5,
		'sleeps'      => 10,
		'location'    => 'Sugar Hill Villas',
		'collections' => array( 'Family Villas', 'Villas with Pools', 'Villas with Community Gym' ),
	),
	'happy-trees'       => array(
		'bedrooms'    => 4,
		'sleeps'      => 8,
		'location'    => 'Sandy Lane',
		'collections' => array( 'Family Villas', 'Villas with Pools', 'Fully Staffed Villas', 'Villas with Beach Club Access' ),
	),
	'ixora'             => array(
		'bedrooms'    => 4,
		'sleeps'      => 8,
		'location'    => 'Royal Westmoreland',
		'collections' => array( 'Family Villas', 'Villas with Pools', 'Golf Resort Villas', 'Fully Staffed Villas', 'Villas with Community Gym', 'Villas with Beach Club Access' ),
	),
	'wild-cane-ridge-2' => array(
		'bedrooms'    => 6,
		'sleeps'      => 12,
		'location'    => 'Royal Westmoreland',
		'collections' => array( 'Family Villas', 'Villas with Pools', 'Golf Resort Villas', 'Fully Staffed Villas', 'Villas with Community Gym', 'Villas with Beach Club Access' ),
	),
	'cool-wind'         => array(
		'bedrooms'    => 6,
		'sleeps'      => 13,
		'location'    => 'Westland Heights',
		'collections' => array( 'Family Villas', 'Villas with Pools', 'Fully Staffed Villas', 'Villas with Private Gym / Gym Equipment', 'Villas with Beach Club Access' ),
	),
	'dene-court'        => array(
		'bedrooms'    => 5,
		'sleeps'      => 10,
		'location'    => 'Sandy Lane',
		'collections' => array( 'Family Villas', 'Villas with Pools', 'Fully Staffed Villas', 'Villas with Beach Club Access' ),
	),
	'sandalwood-house'  => array(
		'bedrooms'    => 6,
		'sleeps'      => 12,
		'location'    => 'Sandy Lane',
		'collections' => array( 'Family Villas', 'Villas with Pools', 'Fully Staffed Villas', 'Villas with Beach Club Access' ),
	),
);

/**
 * Read the starting price from the saved villa specs ribbon.
 *
 * Only an exact `vvm-villa-specs__label` beginning with "From $" is accepted,
 * so unrelated prices elsewhere in the post cannot enter the search index.
 *
 * @param int $villa_id Villa post ID.
 * @return int
 */
function gutenberg_lab_blocks_seed_search_specs_price( $villa_id ) {
	$content = (string) get_post_field( 'post_content', $villa_id );
	$pattern = '/<(?:p|span)\b[^>]*class=(["\'])[^"\']*\bvvm-villa-specs__label\b[^"\']*\1[^>]*>(.*?)<\/(?:p|span)>/is';

	if ( ! preg_match_all( $pattern, $content, $matches ) ) {
		return 0;
	}

	foreach ( $matches[2] as $raw_label ) {
		$label = trim( html_entity_decode( wp_strip_all_tags( $raw_label ), ENT_QUOTES, 'UTF-8' ) );

		if ( preg_match( '/^From\s+\$([0-9][0-9,]*)\s*(?:\/\s*Night)?$/i', $label, $price_match ) ) {
			return absint( str_replace( ',', '', $price_match[1] ) );
		}
	}

	return 0;
}

/**
 * Resolve or create one approved search term, then make it publicly searchable.
 *
 * @param string $taxonomy Target taxonomy.
 * @param string $name     Approved term label.
 * @param bool   $apply    Whether writes are enabled.
 * @return int
 */
function gutenberg_lab_blocks_seed_search_term( $taxonomy, $name, $apply ) {
	$existing = term_exists( $name, $taxonomy );

	if ( is_array( $existing ) ) {
		$term_id = absint( $existing['term_id'] );
	} elseif ( $existing ) {
		$term_id = absint( $existing );
	} elseif ( ! $apply ) {
		return 0;
	} else {
		$created = wp_insert_term( $name, $taxonomy );

		if ( is_wp_error( $created ) ) {
			WP_CLI::error( $created->get_error_message() );
		}

		$term_id = absint( $created['term_id'] );
	}

	if ( $apply && $term_id ) {
		update_term_meta( $term_id, 'villa_search_enabled', 1 );
	}

	return $term_id;
}

$rows       = array();
$validated  = array();
$validation_errors = array();

// Validate every villa and price before performing the first write.
foreach ( $approved_villas as $slug => $approved ) {
	$villa = get_page_by_path( $slug, OBJECT, 'villa' );

	if ( ! $villa || 'publish' !== $villa->post_status ) {
		$validation_errors[] = sprintf( 'Published villa not found for slug: %s', $slug );
		continue;
	}

	$starting_price = gutenberg_lab_blocks_seed_search_specs_price( $villa->ID );

	if ( ! $starting_price ) {
		$validation_errors[] = sprintf( 'No exact specs-ribbon price found for %s (%s).', $villa->post_title, $slug );
		continue;
	}

	$approved['collections'] = array_values( array_unique( $approved['collections'] ) );
	$approved['price']       = $starting_price;
	$validated[ $villa->ID ] = $approved;

	$rows[] = array(
		'ID'          => $villa->ID,
		'Villa'       => $villa->post_title,
		'Bedrooms'    => $approved['bedrooms'],
		'Guests'      => null === $approved['sleeps'] ? 'UNCHANGED' : $approved['sleeps'],
		'Price'       => '$' . number_format_i18n( $starting_price ),
		'Area'        => $approved['location'],
		'Collections' => count( $approved['collections'] ),
		'Age policy'  => 'All ages',
	);
}

WP_CLI\Utils\format_items(
	'table',
	$rows,
	array( 'ID', 'Villa', 'Bedrooms', 'Guests', 'Price', 'Area', 'Collections', 'Age policy' )
);

if ( $validation_errors ) {
	foreach ( $validation_errors as $validation_error ) {
		WP_CLI::warning( $validation_error );
	}

	WP_CLI::error( 'Validation failed. No production content was changed.' );
}

if ( count( $validated ) !== count( $approved_villas ) ) {
	WP_CLI::error( 'The validated villa count does not match the approved workbook count.' );
}

if ( ! $apply ) {
	WP_CLI::log( 'Preview only. Landfall guest capacity will remain unchanged.' );
	WP_CLI::log( 'Re-run with the positional argument `apply` to write the validated values.' );
	return;
}

foreach ( $validated as $villa_id => $approved ) {
	update_post_meta( $villa_id, 'villa_search_bedrooms', $approved['bedrooms'] );
	update_post_meta( $villa_id, 'villa_search_starting_price_usd', $approved['price'] );
	update_post_meta( $villa_id, 'villa_search_guest_age_policy', 'all_ages' );

	// The user explicitly asked us not to populate Landfall's guest value yet.
	if ( null !== $approved['sleeps'] ) {
		update_post_meta( $villa_id, 'villa_search_sleeps', $approved['sleeps'] );
	}

	$location_id = gutenberg_lab_blocks_seed_search_term( 'villa_location', $approved['location'], true );

	// Keep parish/location context already attached to each villa and append the
	// search-specific area instead of replacing useful editorial taxonomy data.
	wp_set_object_terms( $villa_id, array( $location_id ), 'villa_location', true );

	$collection_ids = array();
	foreach ( $approved['collections'] as $collection_name ) {
		$collection_ids[] = gutenberg_lab_blocks_seed_search_term( 'villa_collection', $collection_name, true );
	}

	// Collections come entirely from the approved workbook, so replace any old
	// dummy assignments rather than silently retaining stale search matches.
	wp_set_object_terms( $villa_id, array_filter( $collection_ids ), 'villa_collection', false );
}

WP_CLI::success( 'Applied approved search data to 13 villas. Landfall guest capacity was left unchanged.' );
