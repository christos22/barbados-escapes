<?php
/**
 * Seed the approved Phase 1 villa search data.
 *
 * Usage:
 *   wp eval-file tools/villa-search/seed-phase-1.php
 *   wp eval-file tools/villa-search/seed-phase-1.php apply
 *
 * The default run is read-only. Pass `apply` only after reviewing its report.
 *
 * @package GutenbergLabBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$apply = isset( $args[0] ) && 'apply' === $args[0];

// Values below are transcribed from the supplied Phase 1 workbook. For villas
// with selectable capacities, search uses the maximum supported value.
$approved_villas = array(
	'monkey-hill'    => array(
		'bedrooms'   => 8,
		'sleeps'     => 16,
		'location'   => 'Sugar Hill Villas',
		'collections' => array(
			'Wedding Villas',
			'Family Villas',
			'Villas with Pools',
			'Ridgefront Villas',
			'Villas with Community Gym',
		),
	),
	'ocean-heights'  => array(
		'bedrooms'   => 7,
		'sleeps'     => 12,
		'location'   => 'Prospect',
		'collections' => array(
			'Family Villas',
			'Beachfront Villas',
		),
	),
	'landfall-house' => array(
		'bedrooms'      => 6,
		'sleeps'        => 14,
		'starting_price' => 5150,
		'location'      => 'Sandy Lane Villas',
		'collections'   => array(
			'Beachfront Villas',
			'Wedding Villas',
			'Family Villas',
			'Villas with Pools',
			'Fully Staffed Villas',
		),
	),
	'tara-house'      => array(
		'bedrooms'   => 4,
		'sleeps'     => 8,
		'location'   => 'Speightstown',
		'collections' => array(
			'Family Villas',
			'Villas with Pools',
			'Villas with Private Gym / Gym Equipment',
			'Ridgefront Villas',
			'Fully Staffed Villas',
		),
	),
	'point-of-view'   => array(
		'bedrooms'   => 5,
		'sleeps'     => 10,
		'location'   => 'Sandy Lane Villas',
		'collections' => array(
			'Family Villas',
			'Villas with Pools',
			'Ridgefront Villas',
			'Fully Staffed Villas',
		),
	),
	'benjoli-breeze'  => array(
		'bedrooms'   => 5,
		'sleeps'     => 10,
		'location'   => 'Royal Westmoreland Villas',
		'collections' => array(
			'Family Villas',
			'Villas with Pools',
			'Golf Resort Villas',
			'Fully Staffed Villas',
			'Villas with Community Gym',
		),
	),
	'bananaquit'      => array(
		'bedrooms'   => 5,
		'sleeps'     => 10,
		'location'   => 'Sugar Hill Villas',
		'collections' => array(
			'Family Villas',
			'Villas with Pools',
			'Villas with Community Gym',
		),
	),
	'happy-trees'     => array(
		'bedrooms'   => 4,
		'sleeps'     => 8,
		'location'   => 'Sandy Lane Villas',
		'collections' => array(
			'Family Villas',
			'Villas with Pools',
			'Fully Staffed Villas',
		),
	),
	'ixora'           => array(
		'bedrooms'   => 4,
		'sleeps'     => 8,
		'location'   => 'Royal Westmoreland Villas',
		'collections' => array(
			'Family Villas',
			'Villas with Pools',
			'Golf Resort Villas',
			'Fully Staffed Villas',
			'Villas with Community Gym',
		),
	),
);

/**
 * Reads one exact whole number from saved card copy.
 *
 * This is a guarded one-time fallback for villas that predate the workbook. It
 * never derives values from prose unless the expected label is an exact match.
 *
 * @param string $value   Saved card meta.
 * @param string $pattern Strict numeric pattern.
 * @return int
 */
function gutenberg_lab_blocks_seed_search_read_number( $value, $pattern ) {
	if ( ! preg_match( $pattern, $value, $matches ) ) {
		return 0;
	}

	return absint( str_replace( ',', '', $matches[1] ) );
}

/**
 * Ensures an approved filter term exists and is visible in public search.
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
			WP_CLI::warning( $created->get_error_message() );
			return 0;
		}

		$term_id = absint( $created['term_id'] );
	}

	if ( $apply && $term_id ) {
		update_term_meta( $term_id, 'villa_search_enabled', 1 );
	}

	return $term_id;
}

$villa_ids = get_posts(
	array(
		'post_type'      => 'villa',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);
$rows      = array();

foreach ( $villa_ids as $villa_id ) {
	$slug          = get_post_field( 'post_name', $villa_id );
	$approved      = $approved_villas[ $slug ] ?? array();
	$card_facts    = (string) get_post_meta( $villa_id, 'villa_card_facts', true );
	$card_price    = (string) get_post_meta( $villa_id, 'villa_card_price', true );
	$bedrooms      = absint( $approved['bedrooms'] ?? 0 );
	$sleeps        = absint( $approved['sleeps'] ?? 0 );
	$starting_price = absint( $approved['starting_price'] ?? 0 );

	// Dene Court, Cool Wind and Sandalwood House are not in the workbook.
	// Their existing structured card labels provide exact guarded fallbacks.
	if ( ! $bedrooms ) {
		$bedrooms = gutenberg_lab_blocks_seed_search_read_number(
			$card_facts,
			'/\b([0-9]+)\s+Bedrooms?\b/i'
		);
	}

	if ( ! $sleeps ) {
		$sleeps = gutenberg_lab_blocks_seed_search_read_number(
			$card_facts,
			'/\bSleeps\s+([0-9]+)\b/i'
		);
	}

	if ( ! $starting_price ) {
		$starting_price = gutenberg_lab_blocks_seed_search_read_number(
			$card_price,
			'/\bFrom\s+\$([0-9][0-9,]*)/i'
		);
	}

	$rows[] = array(
		'ID'       => $villa_id,
		'Villa'    => get_the_title( $villa_id ),
		'Bedrooms' => $bedrooms ?: 'MISSING',
		'Sleeps'   => $sleeps ?: 'MISSING',
		'Price'    => $starting_price ?: 'MISSING',
		'Area'     => $approved['location'] ?? 'Not supplied',
		'Source'   => $approved ? 'Workbook + saved card price' : 'Saved card fields',
	);

	if ( ! $apply ) {
		continue;
	}

	foreach (
		array(
			'villa_search_bedrooms'          => $bedrooms,
			'villa_search_sleeps'            => $sleeps,
			'villa_search_starting_price_usd' => $starting_price,
		) as $meta_key => $meta_value
	) {
		if ( $meta_value ) {
			update_post_meta( $villa_id, $meta_key, $meta_value );
		}
	}

	if ( empty( $approved ) ) {
		continue;
	}

	$location_id = gutenberg_lab_blocks_seed_search_term(
		'villa_location',
		$approved['location'],
		true
	);

	if ( $location_id ) {
		wp_set_object_terms( $villa_id, array( $location_id ), 'villa_location', true );
	}

	foreach ( $approved['collections'] as $collection_name ) {
		$collection_id = gutenberg_lab_blocks_seed_search_term(
			'villa_collection',
			$collection_name,
			true
		);

		if ( $collection_id ) {
			wp_set_object_terms( $villa_id, array( $collection_id ), 'villa_collection', true );
		}
	}
}

WP_CLI\Utils\format_items(
	'table',
	$rows,
	array( 'ID', 'Villa', 'Bedrooms', 'Sleeps', 'Price', 'Area', 'Source' )
);

if ( $apply ) {
	WP_CLI::success( 'Applied Phase 1 villa search data.' );
} else {
	WP_CLI::log( 'Preview only. Re-run with the positional argument `apply` to write these values.' );
}
