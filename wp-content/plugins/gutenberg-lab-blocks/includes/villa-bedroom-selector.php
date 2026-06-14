<?php
/**
 * Villa bedroom choice helpers and Contact Form 7 integration.
 *
 * @package GutenbergLabBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks whether bedroom selection is enabled for a villa.
 *
 * Existing villas predate the setting, so a missing meta row intentionally
 * preserves the feature's current enabled behavior.
 *
 * @param int $villa_id Villa post ID.
 * @return bool
 */
function gutenberg_lab_blocks_is_villa_bedroom_selector_enabled( $villa_id ) {
	$villa_id = absint( $villa_id );

	if (
		! $villa_id ||
		'villa' !== get_post_type( $villa_id )
	) {
		return false;
	}

	if (
		! metadata_exists(
			'post',
			$villa_id,
			'villa_bedroom_selector_enabled'
		)
	) {
		return true;
	}

	return rest_sanitize_boolean(
		get_post_meta(
			$villa_id,
			'villa_bedroom_selector_enabled',
			true
		)
	);
}

/**
 * Collects booking data from structured villa blocks.
 *
 * The visible Villa Specs block is the source of truth. Reading its attributes
 * avoids duplicating bedroom and guest capacity in another settings screen.
 *
 * @param array<int, array<string, mixed>> $blocks Parsed Gutenberg blocks.
 * @param array<string, int>               $data   Collected booking data.
 */
function gutenberg_lab_blocks_collect_villa_booking_data( $blocks, &$data ) {
	foreach ( $blocks as $parsed_block ) {
		$block_name = (string) ( $parsed_block['blockName'] ?? '' );
		$attributes = (array) ( $parsed_block['attrs'] ?? array() );

		if ( 'gutenberg-lab-blocks/villa-spec-item' === $block_name ) {
			$label = strtolower(
				trim( wp_strip_all_tags( (string) ( $attributes['label'] ?? '' ) ) )
			);
			$value = absint(
				wp_strip_all_tags( (string) ( $attributes['value'] ?? '' ) )
			);

			if ( $value && preg_match( '/^bedrooms?$/', $label ) ) {
				$data['bedrooms'] = min( 30, $value );
			}

			if ( $value && preg_match( '/^(?:sleeps?|guests?)$/', $label ) ) {
				$data['sleeps'] = min( 100, $value );
			}
		}

		if ( 'gutenberg-lab-blocks/villa-bedroom-selector' === $block_name ) {
			$data['minimum_bedrooms'] = max(
				1,
				min( 30, absint( $attributes['minimumBedrooms'] ?? 1 ) )
			);
		}

		if ( ! empty( $parsed_block['innerBlocks'] ) ) {
			gutenberg_lab_blocks_collect_villa_booking_data(
				$parsed_block['innerBlocks'],
				$data
			);
		}
	}
}

/**
 * Returns bedroom capacity and selector settings for a villa.
 *
 * @param int $villa_id Villa post ID.
 * @return array{bedrooms:int,sleeps:int,minimum_bedrooms:int}
 */
function gutenberg_lab_blocks_get_villa_booking_data( $villa_id ) {
	static $cache = array();

	$villa_id = absint( $villa_id );

	if (
		! $villa_id ||
		'villa' !== get_post_type( $villa_id )
	) {
		return array(
			'bedrooms'         => 0,
			'sleeps'           => 0,
			'minimum_bedrooms' => 1,
		);
	}

	if ( isset( $cache[ $villa_id ] ) ) {
		return $cache[ $villa_id ];
	}

	$data = array(
		'bedrooms'         => 0,
		'sleeps'           => 0,
		'minimum_bedrooms' => 1,
	);
	$post = get_post( $villa_id );

	if ( $post instanceof WP_Post ) {
		gutenberg_lab_blocks_collect_villa_booking_data(
			parse_blocks( $post->post_content ),
			$data
		);
	}

	$data['minimum_bedrooms'] = min(
		max( 1, $data['minimum_bedrooms'] ),
		max( 1, $data['bedrooms'] )
	);
	$cache[ $villa_id ]       = $data;

	return $data;
}

/**
 * Returns allowed bedroom values and visitor-facing labels.
 *
 * @param int      $villa_id         Villa post ID.
 * @param int|null $minimum_override Optional block-level minimum.
 * @return array<int, string>
 */
function gutenberg_lab_blocks_get_villa_bedroom_choices( $villa_id, $minimum_override = null ) {
	$data     = gutenberg_lab_blocks_get_villa_booking_data( $villa_id );
	$maximum  = (int) $data['bedrooms'];
	$minimum  = null === $minimum_override
		? (int) $data['minimum_bedrooms']
		: max( 1, min( $maximum, absint( $minimum_override ) ) );
	$capacity = 0;
	$choices  = array();

	if ( $maximum < 1 ) {
		return $choices;
	}

	// Only show sleeping capacity when it scales cleanly per bedroom.
	if (
		$data['sleeps'] > 0 &&
		0 === $data['sleeps'] % $maximum
	) {
		$capacity = (int) ( $data['sleeps'] / $maximum );
	}

	for ( $bedrooms = $maximum; $bedrooms >= $minimum; --$bedrooms ) {
		$label = sprintf(
			_n( '%d Bedroom', '%d Bedrooms', $bedrooms, 'gutenberg-lab-blocks' ),
			$bedrooms
		);

		if ( $capacity > 0 ) {
			$label .= sprintf(
				/* translators: %d is the number of guests. */
				__( ' (sleeps %d)', 'gutenberg-lab-blocks' ),
				$bedrooms * $capacity
			);
		}

		$choices[ $bedrooms ] = $label;
	}

	return $choices;
}

/**
 * Resolves a villa ID during page rendering or a CF7 REST submission.
 *
 * @param int $preferred_id Preferred villa post ID.
 * @return int
 */
function gutenberg_lab_blocks_resolve_villa_booking_post_id( $preferred_id = 0 ) {
	$candidates = array( absint( $preferred_id ) );

	if ( isset( $_POST['_wpcf7_container_post'] ) ) {
		$candidates[] = absint( wp_unslash( $_POST['_wpcf7_container_post'] ) );
	}

	if ( isset( $_POST['villa-id'] ) ) {
		$candidates[] = absint( wp_unslash( $_POST['villa-id'] ) );
	}

	$candidates[] = absint( get_queried_object_id() );
	$candidates[] = absint( get_the_ID() );

	foreach ( array_unique( $candidates ) as $candidate ) {
		if ( $candidate && 'villa' === get_post_type( $candidate ) ) {
			return $candidate;
		}
	}

	return 0;
}

/**
 * Replaces the CF7 bedroom field's placeholder option with villa choices.
 *
 * @param array<string, mixed> $tag      Scanned CF7 form tag.
 * @param bool                 $_replace Whether CF7 is currently rendering.
 * @return array<string, mixed>
 */
function gutenberg_lab_blocks_filter_cf7_villa_bedroom_tag( $tag, $_replace ) {
	if (
		! is_array( $tag ) ||
		'villa-bedrooms' !== ( $tag['name'] ?? '' )
	) {
		return $tag;
	}

	$villa_id = gutenberg_lab_blocks_resolve_villa_booking_post_id();
	$choices  = gutenberg_lab_blocks_get_villa_bedroom_choices( $villa_id );

	/*
	 * CF7 builds its browser-validation schema without page context. Making
	 * this one scanned tag optional prevents a disabled villa from inheriting
	 * a generic required rule; the villa-aware validator below remains the
	 * server-side authority when the feature is enabled.
	 */
	if (
		! $_replace ||
		(
			$villa_id &&
			! gutenberg_lab_blocks_is_villa_bedroom_selector_enabled( $villa_id )
		)
	) {
		$tag['type'] = 'select';
	}

	/*
	 * CF7 fetches its browser-validation schema without page/post context.
	 * Give that schema a bounded generic allowlist; page rendering and submit
	 * validation still use the current villa's smaller, exact choice set.
	 */
	if ( empty( $choices ) ) {
		$generic_values = range( 1, 30 );
		$choices = array_combine(
			$generic_values,
			array_map( 'strval', $generic_values )
		);
	}

	$values            = array_map( 'strval', array_keys( $choices ) );
	$tag['raw_values'] = $values;
	$tag['values']     = $values;
	$tag['labels']     = array_values( $choices );
	$tag['pipes']      = null;

	return $tag;
}
add_filter(
	'wpcf7_form_tag',
	'gutenberg_lab_blocks_filter_cf7_villa_bedroom_tag',
	20,
	2
);

/**
 * Removes the form's bedroom row when the current villa disables the feature.
 *
 * CF7 does not expose a form-tag removal API. The pattern is deliberately
 * limited to one paragraph containing its generated villa-bedrooms wrapper.
 *
 * @param string $html Rendered CF7 form HTML.
 * @return string
 */
function gutenberg_lab_blocks_filter_cf7_villa_bedroom_elements( $html ) {
	$villa_id = gutenberg_lab_blocks_resolve_villa_booking_post_id();

	if (
		! $villa_id ||
		gutenberg_lab_blocks_is_villa_bedroom_selector_enabled( $villa_id )
	) {
		return $html;
	}

	$filtered_html = preg_replace(
		'~<p\b[^>]*>(?:(?!</p>).)*?\bdata-name=(["\'])villa-bedrooms\1(?:(?!</p>).)*?</p>~is',
		'',
		$html,
		1
	);

	return null === $filtered_html ? $html : $filtered_html;
}
add_filter(
	'wpcf7_form_elements',
	'gutenberg_lab_blocks_filter_cf7_villa_bedroom_elements',
	20
);

/**
 * Rejects missing, malformed, or out-of-range bedroom submissions.
 *
 * CF7 also creates an enum rule from the filtered options. This explicit
 * server-side check keeps the trust boundary clear if client validation is
 * bypassed or disabled.
 *
 * @param WPCF7_Validation $result Current validation result.
 * @param WPCF7_FormTag    $tag    Current form tag.
 * @return WPCF7_Validation
 */
function gutenberg_lab_blocks_validate_cf7_villa_bedrooms( $result, $tag ) {
	if ( ! $tag instanceof WPCF7_FormTag && class_exists( 'WPCF7_FormTag' ) ) {
		$tag = new WPCF7_FormTag( $tag );
	}

	if (
		! $tag instanceof WPCF7_FormTag ||
		'villa-bedrooms' !== $tag->name
	) {
		return $result;
	}

	$submitted = isset( $_POST['villa-bedrooms'] )
		? sanitize_text_field( wp_unslash( $_POST['villa-bedrooms'] ) )
		: '';
	$container_id = isset( $_POST['_wpcf7_container_post'] )
		? absint( wp_unslash( $_POST['_wpcf7_container_post'] ) )
		: 0;
	$submitted_villa_id = isset( $_POST['villa-id'] )
		? absint( wp_unslash( $_POST['villa-id'] ) )
		: 0;
	$has_mismatched_villa = (
		$container_id &&
		$submitted_villa_id &&
		$container_id !== $submitted_villa_id
	);
	$villa_id = gutenberg_lab_blocks_resolve_villa_booking_post_id();

	if ( $has_mismatched_villa ) {
		$result->invalidate(
			$tag,
			__( 'Please choose a valid number of bedrooms.', 'gutenberg-lab-blocks' )
		);

		return $result;
	}

	if (
		$villa_id &&
		! gutenberg_lab_blocks_is_villa_bedroom_selector_enabled( $villa_id )
	) {
		return $result;
	}

	$choices  = gutenberg_lab_blocks_get_villa_bedroom_choices( $villa_id );

	if (
		! ctype_digit( $submitted ) ||
		! isset( $choices[ (int) $submitted ] )
	) {
		$result->invalidate(
			$tag,
			__( 'Please choose a valid number of bedrooms.', 'gutenberg-lab-blocks' )
		);
	}

	return $result;
}
add_filter(
	'wpcf7_validate_select',
	'gutenberg_lab_blocks_validate_cf7_villa_bedrooms',
	30,
	2
);
add_filter(
	'wpcf7_validate_select*',
	'gutenberg_lab_blocks_validate_cf7_villa_bedrooms',
	30,
	2
);
