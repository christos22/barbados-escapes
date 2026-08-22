<?php
/**
 * Server-side query and rendering for the Phase 1 villa results page.
 *
 * @package GutenbergLabBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the current results page number without affecting WordPress archives.
 *
 * A dedicated query parameter avoids conflicts with the main archive query and
 * keeps pagination stable when every other search filter is preserved.
 *
 * @return int
 */
function gutenberg_lab_blocks_get_villa_search_page() {
	if ( ! isset( $_GET['villa_page'] ) || ! is_scalar( $_GET['villa_page'] ) ) {
		return 1;
	}

	$page = gutenberg_lab_blocks_sanitize_villa_search_integer(
		wp_unslash( $_GET['villa_page'] )
	);

	return max( 1, $page );
}

/**
 * Returns only non-empty search parameters suitable for a public URL.
 *
 * @param array<string, int|string> $request Normalized search request.
 * @return array<string, int|string>
 */
function gutenberg_lab_blocks_get_villa_search_url_args( $request ) {
	return array_filter(
		$request,
		static function ( $value ) {
			return '' !== $value && 0 !== $value;
		}
	);
}

/**
 * Returns a paginated villa-search URL with every active filter preserved.
 *
 * @param int                           $page    Requested results page.
 * @param array<string, int|string> $request Normalized search request.
 * @return string
 */
function gutenberg_lab_blocks_get_villa_search_page_url( $page, $request ) {
	$archive_url = gutenberg_lab_blocks_get_villa_search_results_url();

	if ( ! $archive_url ) {
		return '';
	}

	return add_query_arg(
		array_merge(
			gutenberg_lab_blocks_get_villa_search_url_args( $request ),
			array( 'villa_page' => max( 1, absint( $page ) ) )
		),
		$archive_url
	);
}

/**
 * Resolves a selected filter term by slug.
 *
 * @param string $taxonomy Villa filter taxonomy.
 * @param string $slug     Normalized term slug.
 * @return WP_Term|null
 */
function gutenberg_lab_blocks_get_villa_search_term( $taxonomy, $slug ) {
	if (
		'' === $slug ||
		! in_array( $taxonomy, array( 'villa_location', 'villa_collection' ), true )
	) {
		return null;
	}

	$term = get_term_by( 'slug', $slug, $taxonomy );

	if (
		! $term instanceof WP_Term ||
		! (bool) get_term_meta( $term->term_id, 'villa_search_enabled', true ) ||
		0 === absint( $term->count )
	) {
		return null;
	}

	return $term;
}

/**
 * Builds the small, deterministic candidate query for villa search.
 *
 * Dates are applied after this query by the existing availability service.
 * Querying IDs keeps memory use low and lets every result reuse the normal
 * villa card renderer.
 *
 * @param array<string, int|string> $request Normalized search request.
 * @return int[]
 */
function gutenberg_lab_blocks_query_villa_search_candidate_ids( $request ) {
	$query_args = array(
		'post_type'              => 'villa',
		'post_status'            => 'publish',
		'posts_per_page'         => -1,
		'fields'                 => 'ids',
		'has_password'           => false,
		'ignore_sticky_posts'    => true,
		'no_found_rows'          => true,
		'orderby'                => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	);

	$tax_query = array();

	foreach ( array( 'villa_location', 'villa_collection' ) as $taxonomy ) {
		if ( empty( $request[ $taxonomy ] ) ) {
			continue;
		}

		// A selected term must be both real and explicitly enabled for public
		// search. Invalid, disabled or empty terms are an exact zero-match query,
		// never permission to broaden the result set to every villa.
		$term = gutenberg_lab_blocks_get_villa_search_term(
			$taxonomy,
			(string) $request[ $taxonomy ]
		);

		if ( ! $term ) {
			return array();
		}

		$tax_query[] = array(
			'taxonomy' => $taxonomy,
			'field'    => 'term_id',
			'terms'    => array( $term->term_id ),
		);
	}

	if ( $tax_query ) {
		$tax_query['relation']   = 'AND';
		$query_args['tax_query'] = $tax_query;
	}

	$meta_filters = array(
		'min_bedrooms' => array(
			'key'     => 'villa_search_bedrooms',
			'compare' => '>=',
		),
		'guests'       => array(
			'key'     => 'villa_search_sleeps',
			'compare' => '>=',
		),
		'min_price'    => array(
			'key'     => 'villa_search_starting_price_usd',
			'compare' => '>=',
		),
		'max_price'    => array(
			'key'     => 'villa_search_starting_price_usd',
			'compare' => '<=',
		),
	);
	$meta_query   = array();

	foreach ( $meta_filters as $request_key => $filter ) {
		if ( empty( $request[ $request_key ] ) ) {
			continue;
		}

		$meta_query[] = array(
			'key'     => $filter['key'],
			'value'   => gutenberg_lab_blocks_sanitize_villa_search_integer( $request[ $request_key ] ),
			'compare' => $filter['compare'],
			'type'    => 'NUMERIC',
		);
	}

	if ( $meta_query ) {
		$meta_query['relation']   = 'AND';
		$query_args['meta_query'] = $meta_query;
	}

	$query = new WP_Query( $query_args );
	$candidate_ids = array_values( array_map( 'absint', $query->posts ) );
	$children_under_12 = absint( $request['children_under_12'] ?? 0 );
	$children_12_17    = absint( $request['children_12_17'] ?? 0 );

	// A legacy `guests=N` search contains no age breakdown, so it remains a
	// capacity-only query. Age restrictions apply only to explicit child counts.
	if ( $children_under_12 > 0 || $children_12_17 > 0 ) {
		$candidate_ids = array_values(
			array_filter(
				$candidate_ids,
				static function ( $villa_id ) use ( $children_under_12 ) {
					$policy = gutenberg_lab_blocks_sanitize_villa_guest_age_policy(
						get_post_meta( $villa_id, 'villa_search_guest_age_policy', true )
					);

					if ( $children_under_12 > 0 ) {
						return 'all_ages' === $policy;
					}

					return 'adults_18_plus' !== $policy;
				}
			)
		);
	}

	return $candidate_ids;
}

/**
 * Applies the existing villa availability rules to candidate IDs.
 *
 * @param int[]                         $villa_ids Candidate villa IDs.
 * @param array<string, int|string> $request   Normalized search request.
 * @return int[]
 */
function gutenberg_lab_blocks_filter_villa_ids_by_availability( $villa_ids, $request ) {
	$arrival  = (string) $request['arrival'];
	$departure = (string) $request['departure'];

	if ( '' === $arrival || '' === $departure || $departure <= $arrival ) {
		return $villa_ids;
	}

	$lookups = gutenberg_lab_blocks_get_villa_unavailable_date_lookups(
		$villa_ids,
		$arrival,
		$departure
	);

	return array_values(
		array_filter(
			$villa_ids,
			static function ( $villa_id ) use ( $arrival, $departure, $lookups ) {
				// Match the public calendar: turnaround-day edges may be used,
				// but unavailable nights inside the stay still exclude a villa.
				return isset( $lookups[ $villa_id ] ) &&
					gutenberg_lab_blocks_is_villa_date_lookup_available(
						$lookups[ $villa_id ],
						$arrival,
						$departure,
						true
					);
			}
		)
	);
}

/**
 * Returns the complete result set and current page slice.
 *
 * @param array<string, int|string> $request Normalized search request.
 * @return array<string, int|int[]>
 */
function gutenberg_lab_blocks_get_villa_search_results( $request ) {
	$per_page  = 8;
	$villa_ids = gutenberg_lab_blocks_query_villa_search_candidate_ids( $request );
	$villa_ids = gutenberg_lab_blocks_filter_villa_ids_by_availability( $villa_ids, $request );
	$total      = count( $villa_ids );
	$pages      = max( 1, (int) ceil( $total / $per_page ) );
	$page       = min( gutenberg_lab_blocks_get_villa_search_page(), $pages );
	$offset     = ( $page - 1 ) * $per_page;
	$page_ids   = array_slice( $villa_ids, $offset, $per_page );

	if ( $page_ids ) {
		// Prime post, meta and term caches in one batch. The shared card helper
		// can then render every card without repeated per-villa SQL lookups.
		get_posts(
			array(
				'post_type'           => 'villa',
				'post_status'         => 'publish',
				'posts_per_page'      => count( $page_ids ),
				'post__in'            => $page_ids,
				'orderby'             => 'post__in',
				'ignore_sticky_posts' => true,
			)
		);
	}

	return array(
		'ids'      => $page_ids,
		'total'    => $total,
		'page'     => $page,
		'pages'    => $pages,
		'per_page' => $per_page,
	);
}

/**
 * Returns a villa enquiry URL carrying the relevant stay and party details.
 *
 * Location, collection, bedroom and price filters describe the results list.
 * Dates and the guest breakdown belong to the selected villa enquiry, so only
 * those values continue into the villa form.
 *
 * @param int                       $villa_id Villa post ID.
 * @param array<string, int|string> $request  Normalized search request.
 * @return string
 */
function gutenberg_lab_blocks_get_villa_search_enquiry_url( $villa_id, $request ) {
	$permalink = get_permalink( $villa_id );

	if ( ! $permalink ) {
		return '';
	}

	$arrival      = isset( $request['arrival'] ) ? (string) $request['arrival'] : '';
	$departure    = isset( $request['departure'] ) ? (string) $request['departure'] : '';
	$enquiry_args = array();

	if ( '' !== $arrival && '' !== $departure && $departure > $arrival ) {
		$enquiry_args['arrival']   = $arrival;
		$enquiry_args['departure'] = $departure;
	}

	foreach ( array( 'guests', 'adults', 'children_12_17', 'children_under_12' ) as $party_key ) {
		$value = isset( $request[ $party_key ] ) ? absint( $request[ $party_key ] ) : 0;

		if ( $value > 0 ) {
			$enquiry_args[ $party_key ] = $value;
		}
	}

	if ( $enquiry_args ) {
		$permalink = add_query_arg( $enquiry_args, $permalink );
	}

	return $permalink . '#enquire';
}

/**
 * Returns the trusted guest fields shared by the form, validation and email.
 *
 * @return array<string, array<string, int|string>>
 */
function gutenberg_lab_blocks_get_villa_enquiry_guest_fields() {
	return array(
		'villa-adults'            => array(
			'request_key' => 'adults',
			'label'       => __( 'Adults', 'gutenberg-lab-blocks' ),
			'description' => __( '18+', 'gutenberg-lab-blocks' ),
			'minimum'     => 1,
		),
		'villa-children-12-17'    => array(
			'request_key' => 'children_12_17',
			'label'       => __( 'Children', 'gutenberg-lab-blocks' ),
			'description' => __( '12–17', 'gutenberg-lab-blocks' ),
			'minimum'     => 0,
		),
		'villa-children-under-12' => array(
			'request_key' => 'children_under_12',
			'label'       => __( 'Children', 'gutenberg-lab-blocks' ),
			'description' => __( 'Under 12', 'gutenberg-lab-blocks' ),
			'minimum'     => 0,
		),
	);
}

/**
 * Renders the guest breakdown forwarded by a results-page enquiry CTA.
 *
 * The fields remain editable on the villa page. Raw HTML inputs are used so
 * every existing villa CF7 form gains the feature without a database edit;
 * the explicit validator below keeps the same server-side trust boundary as
 * a stored Contact Form 7 number tag.
 *
 * @param array<string, int|string> $request Normalized villa-search request.
 * @return string
 */
function gutenberg_lab_blocks_render_villa_enquiry_guest_fields( $request ) {
	if ( empty( $request['guests'] ) ) {
		return '';
	}

	$markup = '<fieldset class="vvm-villa-contact-form__party vvm-villa-contact-form__wide" data-vvm-villa-guest-breakdown>';
	$markup .= '<legend>' . esc_html__( 'Guests', 'gutenberg-lab-blocks' ) . '</legend>';
	$markup .= '<div class="vvm-villa-contact-form__party-grid">';

	foreach ( gutenberg_lab_blocks_get_villa_enquiry_guest_fields() as $field_name => $field ) {
		$request_key = (string) $field['request_key'];
		$minimum     = absint( $field['minimum'] );
		$value       = isset( $request[ $request_key ] ) ? absint( $request[ $request_key ] ) : 0;
		$value       = min( 30, max( $minimum, $value ) );
		$field_id    = wp_unique_id( $field_name . '-' );

		$markup .= sprintf(
			'<label class="vvm-villa-contact-form__party-field" for="%1$s"><span class="vvm-villa-contact-form__party-label">%2$s <small>%3$s</small></span><span class="wpcf7-form-control-wrap" data-name="%4$s"><input id="%1$s" name="%4$s" class="wpcf7-form-control wpcf7-number vvm-villa-contact-form__field" type="number" min="%5$d" max="30" step="1" inputmode="numeric" value="%6$d" required aria-required="true" /></span></label>',
			esc_attr( $field_id ),
			esc_html( $field['label'] ),
			esc_html( $field['description'] ),
			esc_attr( $field_name ),
			$minimum,
			$value
		);
	}

	$markup .= '</div>';
	$markup .= '<input type="hidden" name="villa-guest-breakdown" value="1" />';
	$markup .= '</fieldset>';

	return $markup;
}

/**
 * Adds a prefilled guest breakdown to the villa Contact Form 7 form.
 *
 * @param string $html Rendered CF7 form HTML.
 * @return string
 */
function gutenberg_lab_blocks_add_villa_enquiry_guest_fields( $html ) {
	if ( ! is_singular( 'villa' ) || false !== strpos( $html, 'data-vvm-villa-guest-breakdown' ) ) {
		return $html;
	}

	$request = gutenberg_lab_blocks_get_villa_search_request();
	$fields  = gutenberg_lab_blocks_render_villa_enquiry_guest_fields( $request );

	if ( '' === $fields ) {
		return $html;
	}

	if (
		preg_match(
			'~<p\b[^>]*>(?:(?!</p>).)*?\b(?:for|id|name)=(["\'])villa-escape-details\1(?:(?!</p>).)*?</p>~is',
			$html,
			$matches,
			PREG_OFFSET_CAPTURE
		)
	) {
		return substr_replace( $html, $fields, (int) $matches[0][1], 0 );
	}

	$grid_start = strpos( $html, 'vvm-villa-contact-form__grid' );
	$grid_close = false !== $grid_start ? stripos( $html, '</div>', $grid_start ) : false;

	return false === $grid_close ? $html : substr_replace( $html, $fields, $grid_close, 0 );
}
add_filter(
	'wpcf7_form_elements',
	'gutenberg_lab_blocks_add_villa_enquiry_guest_fields',
	25
);

/**
 * Normalizes the submitted guest fields and rejects malformed or partial data.
 *
 * @return array{present: bool, valid: bool, values: array<string, int>, total: int, error_field: string}
 */
function gutenberg_lab_blocks_get_submitted_villa_guest_breakdown() {
	$result = array(
		'present'     => isset( $_POST['villa-guest-breakdown'] ),
		'valid'       => false,
		'values'      => array(),
		'total'       => 0,
		'error_field' => 'villa-adults',
	);

	if ( ! $result['present'] ) {
		return $result;
	}

	foreach ( gutenberg_lab_blocks_get_villa_enquiry_guest_fields() as $field_name => $field ) {
		$result['error_field'] = $field_name;

		if ( ! isset( $_POST[ $field_name ] ) || ! is_scalar( $_POST[ $field_name ] ) ) {
			return $result;
		}

		$raw_value = trim( (string) wp_unslash( $_POST[ $field_name ] ) );
		$minimum   = absint( $field['minimum'] );

		if ( ! preg_match( '/^\d{1,2}$/D', $raw_value ) ) {
			return $result;
		}

		$value = (int) $raw_value;

		if ( $value < $minimum || $value > 30 ) {
			return $result;
		}

		$result['values'][ $field_name ] = $value;
		$result['total']                += $value;
	}

	$result['error_field'] = 'villa-adults';
	$result['valid']       = $result['total'] > 0;

	return $result;
}

/**
 * Validates the injected guest fields during a villa Contact Form 7 request.
 *
 * @param WPCF7_Validation          $result Current validation result.
 * @param array<int, WPCF7_FormTag> $_tags  Scanned CF7 form tags.
 * @return WPCF7_Validation
 */
function gutenberg_lab_blocks_validate_villa_enquiry_guest_fields( $result, $_tags ) {
	$breakdown = gutenberg_lab_blocks_get_submitted_villa_guest_breakdown();

	if ( ! $breakdown['present'] || $breakdown['valid'] ) {
		return $result;
	}

	$result->invalidate(
		array(
			'type'    => 'number',
			'name'    => $breakdown['error_field'],
			'options' => array( 'id:' . $breakdown['error_field'] ),
		),
		__( 'Please enter a valid guest breakdown.', 'gutenberg-lab-blocks' )
	);

	return $result;
}
add_filter(
	'wpcf7_validate',
	'gutenberg_lab_blocks_validate_villa_enquiry_guest_fields',
	30,
	2
);

/**
 * Adds normalized injected guest values to Contact Form 7 posted data.
 *
 * @param array<string, mixed> $posted_data Sanitized CF7 posted data.
 * @return array<string, mixed>
 */
function gutenberg_lab_blocks_add_villa_guest_breakdown_to_posted_data( $posted_data ) {
	$breakdown = gutenberg_lab_blocks_get_submitted_villa_guest_breakdown();

	if ( ! $breakdown['present'] || ! $breakdown['valid'] ) {
		return $posted_data;
	}

	foreach ( $breakdown['values'] as $field_name => $value ) {
		$posted_data[ $field_name ] = (string) $value;
	}

	$posted_data['villa-guests'] = (string) $breakdown['total'];

	return $posted_data;
}
add_filter(
	'wpcf7_posted_data',
	'gutenberg_lab_blocks_add_villa_guest_breakdown_to_posted_data',
	20
);

/**
 * Formats the guest breakdown added to the villa enquiry email.
 *
 * Keeping this separate from Contact Form 7's submission singleton makes the
 * output deterministic and easy to test without sending a real enquiry.
 *
 * @param array<string, int|string> $values Normalized guest values.
 * @return string
 */
function gutenberg_lab_blocks_get_villa_guest_breakdown_mail_details( $values ) {
	$adults            = isset( $values['villa-adults'] ) ? absint( $values['villa-adults'] ) : 0;
	$children_12_17    = isset( $values['villa-children-12-17'] ) ? absint( $values['villa-children-12-17'] ) : 0;
	$children_under_12 = isset( $values['villa-children-under-12'] ) ? absint( $values['villa-children-under-12'] ) : 0;

	if ( $adults < 1 || $adults > 30 || $children_12_17 > 30 || $children_under_12 > 30 ) {
		return '';
	}

	$total = $adults + $children_12_17 + $children_under_12;

	return sprintf(
		/* translators: 1: total guests, 2: adults, 3: children ages 12-17, 4: children under 12. */
		__( "Guests: %1\$d total\nAdults (18+): %2\$d\nChildren (12–17): %3\$d\nChildren (under 12): %4\$d\n", 'gutenberg-lab-blocks' ),
		$total,
		$adults,
		$children_12_17,
		$children_under_12
	);
}

/**
 * Adds the complete guest breakdown to villa enquiry emails.
 *
 * @param array<string, mixed> $components   Mail components.
 * @param WPCF7_ContactForm    $_contact_form Current form.
 * @return array<string, mixed>
 */
function gutenberg_lab_blocks_add_villa_guest_breakdown_to_mail( $components, $_contact_form ) {
	$submission = class_exists( 'WPCF7_Submission' )
		? WPCF7_Submission::get_instance()
		: null;

	if (
		! $submission instanceof WPCF7_Submission ||
		empty( $components['body'] ) ||
		! is_string( $components['body'] ) ||
		false !== stripos( $components['body'], 'Adults (18+):' )
	) {
		return $components;
	}

	$details = gutenberg_lab_blocks_get_villa_guest_breakdown_mail_details(
		array(
			'villa-adults'            => $submission->get_posted_data( 'villa-adults' ),
			'villa-children-12-17'    => $submission->get_posted_data( 'villa-children-12-17' ),
			'villa-children-under-12' => $submission->get_posted_data( 'villa-children-under-12' ),
		)
	);

	if ( '' === $details ) {
		return $components;
	}

	$updated_body = preg_replace_callback(
		'/^(Preferred departure date:[^\r\n]*(?:\r?\n))/m',
		static function ( $matches ) use ( $details ) {
			return $matches[0] . $details;
		},
		$components['body'],
		1,
		$replacement_count
	);

	if ( null === $updated_body || 0 === $replacement_count ) {
		$components['body'] = rtrim( $components['body'] ) . "\n\n" . trim( $details );
	} else {
		$components['body'] = $updated_body;
	}

	return $components;
}
add_filter(
	'wpcf7_mail_components',
	'gutenberg_lab_blocks_add_villa_guest_breakdown_to_mail',
	20,
	2
);

/**
 * Renders result cards through the shared villa-card contract.
 *
 * @param int[]                     $villa_ids Villa post IDs for the current results page.
 * @param array<string, int|string> $request   Normalized search request.
 * @return string
 */
function gutenberg_lab_blocks_render_villa_search_cards( $villa_ids, $request ) {
	$card_markup = '';

	foreach ( $villa_ids as $villa_id ) {
		$card_markup .= gutenberg_lab_blocks_render_villa_card(
			$villa_id,
			array(
				'enquiry_url'      => gutenberg_lab_blocks_get_villa_search_enquiry_url( $villa_id, $request ),
				'heading_level'    => 2,
				'presentation'     => 'collection',
				'show_description' => true,
				'show_details'     => true,
				'show_fact_icons'  => true,
				'show_price'       => true,
			)
		);
	}

	return $card_markup;
}

/**
 * Formats the primary result-count summary.
 *
 * @param int $total Number of matching villas.
 * @return string
 */
function gutenberg_lab_blocks_get_villa_search_summary( $total ) {
	return sprintf(
		/* translators: %s: number of matching villas. */
		_n( '%s private residence available', '%s private residences available', $total, 'gutenberg-lab-blocks' ),
		number_format_i18n( $total )
	);
}

/**
 * Returns human-readable labels for the remaining active filters.
 *
 * @param array<string, int|string> $request Normalized search request.
 * @return string[]
 */
function gutenberg_lab_blocks_get_villa_search_filter_labels( $request ) {
	$labels = array();

	foreach ( array( 'villa_location', 'villa_collection' ) as $taxonomy ) {
		$term = gutenberg_lab_blocks_get_villa_search_term(
			$taxonomy,
			(string) $request[ $taxonomy ]
		);

		if ( $term ) {
			$labels[] = $term->name;
		}
	}

	if ( ! empty( $request['min_bedrooms'] ) ) {
		$labels[] = sprintf(
			/* translators: %s: minimum number of bedrooms. */
			__( '%s+ bedrooms', 'gutenberg-lab-blocks' ),
			number_format_i18n( absint( $request['min_bedrooms'] ) )
		);
	}

	if ( ! empty( $request['guests'] ) ) {
		$guests   = absint( $request['guests'] );
		$labels[] = sprintf(
			/* translators: %s: number of guests. */
			_n( '%s guest', '%s guests', $guests, 'gutenberg-lab-blocks' ),
			number_format_i18n( $guests )
		);
	}

	if ( ! empty( $request['min_price'] ) && ! empty( $request['max_price'] ) ) {
		$labels[] = sprintf(
			/* translators: 1: minimum nightly price, 2: maximum nightly price. */
			__( '$%1$s–$%2$s per night', 'gutenberg-lab-blocks' ),
			number_format_i18n( absint( $request['min_price'] ) ),
			number_format_i18n( absint( $request['max_price'] ) )
		);
	} elseif ( ! empty( $request['min_price'] ) ) {
		$labels[] = sprintf(
			/* translators: %s: minimum nightly price. */
			__( 'From $%s per night', 'gutenberg-lab-blocks' ),
			number_format_i18n( absint( $request['min_price'] ) )
		);
	} elseif ( ! empty( $request['max_price'] ) ) {
		$labels[] = sprintf(
			/* translators: %s: maximum nightly price. */
			__( 'Up to $%s per night', 'gutenberg-lab-blocks' ),
			number_format_i18n( absint( $request['max_price'] ) )
		);
	}

	return $labels;
}

/**
 * Renders pagination while retaining every active filter.
 *
 * @param int                           $current Current result page.
 * @param int                           $pages   Total result pages.
 * @param array<string, int|string> $request Normalized search request.
 * @return string
 */
function gutenberg_lab_blocks_render_villa_search_pagination( $current, $pages, $request ) {
	if ( $pages < 2 ) {
		return '';
	}

	$archive_url = gutenberg_lab_blocks_get_villa_search_results_url();

	if ( ! $archive_url ) {
		return '';
	}

	$placeholder = 999999999;
	$url         = add_query_arg(
		array_merge(
			gutenberg_lab_blocks_get_villa_search_url_args( $request ),
			array( 'villa_page' => $placeholder )
		),
		$archive_url
	);
	$base        = str_replace( (string) $placeholder, '%#%', $url );
	$normalize_pagination_url = static function ( $link ) use ( $request ) {
		$query_args = array();
		$query      = wp_parse_url( $link, PHP_URL_QUERY );

		if ( is_string( $query ) ) {
			wp_parse_str( $query, $query_args );
		}

		$page = isset( $query_args['villa_page'] )
			? gutenberg_lab_blocks_sanitize_villa_search_integer( $query_args['villa_page'] )
			: 1;

		return gutenberg_lab_blocks_get_villa_search_page_url( max( 1, $page ), $request );
	};

	// paginate_links() automatically copies every query parameter from the
	// current URL. Rebuild each link through the approved search contract so
	// empty, unknown or malformed parameters cannot leak into later pages.
	add_filter( 'paginate_links', $normalize_pagination_url );
	$pagination = (string) paginate_links(
		array(
			'base'      => $base,
			'format'    => '',
			'current'   => $current,
			'total'     => $pages,
			'mid_size'  => 1,
			'prev_text' => __( 'Previous', 'gutenberg-lab-blocks' ),
			'next_text' => __( 'Next', 'gutenberg-lab-blocks' ),
			'type'      => 'list',
		)
	);
	remove_filter( 'paginate_links', $normalize_pagination_url );

	return $pagination;
}

/**
 * Serves the next card batch used by progressive infinite scrolling.
 *
 * Normal pagination remains valid without JavaScript. Enhanced browsers ask
 * for this compact JSON fragment as the scroll sentinel approaches.
 */
function gutenberg_lab_blocks_serve_villa_search_fragment() {
	if (
		! gutenberg_lab_blocks_is_villa_search_public_enabled() ||
		! is_post_type_archive( 'villa' ) ||
		! isset( $_GET['villa_results_fragment'] ) ||
		! is_scalar( $_GET['villa_results_fragment'] ) ||
		'1' !== sanitize_text_field( wp_unslash( $_GET['villa_results_fragment'] ) )
	) {
		return;
	}

	$request  = gutenberg_lab_blocks_get_villa_search_request();
	$results  = gutenberg_lab_blocks_get_villa_search_results( $request );
	$next_url = $results['page'] < $results['pages']
		? gutenberg_lab_blocks_get_villa_search_page_url( $results['page'] + 1, $request )
		: '';

	wp_send_json_success(
		array(
			'html'    => gutenberg_lab_blocks_render_villa_search_cards( $results['ids'], $request ),
			'nextUrl' => $next_url,
			'page'    => $results['page'],
			'pages'   => $results['pages'],
		)
	);
}
add_action( 'template_redirect', 'gutenberg_lab_blocks_serve_villa_search_fragment', 20 );

/**
 * Renders the full results block from normalized public query parameters.
 *
 * @param string $wrapper_attributes Escaped block wrapper attributes.
 * @return string
 */
function gutenberg_lab_blocks_render_villa_search_results_markup( $wrapper_attributes = '' ) {
	if ( ! gutenberg_lab_blocks_should_render_villa_search() ) {
		return '';
	}

	$request       = gutenberg_lab_blocks_get_villa_search_request();
	$results       = gutenberg_lab_blocks_get_villa_search_results( $request );
	$filter_labels = gutenberg_lab_blocks_get_villa_search_filter_labels( $request );
	$archive_url   = gutenberg_lab_blocks_get_villa_search_results_url();
	$card_markup   = gutenberg_lab_blocks_render_villa_search_cards( $results['ids'], $request );

	ob_start();
	?>
	<section
		<?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		data-vvm-villa-search-results
		data-vvm-villa-results-loading="<?php esc_attr_e( 'Loading more villas…', 'gutenberg-lab-blocks' ); ?>"
		data-vvm-villa-results-loaded-singular="<?php esc_attr_e( '1 more villa loaded.', 'gutenberg-lab-blocks' ); ?>"
		data-vvm-villa-results-loaded-plural="<?php esc_attr_e( '%d more villas loaded.', 'gutenberg-lab-blocks' ); ?>"
		data-vvm-villa-results-complete="<?php esc_attr_e( 'All villas loaded.', 'gutenberg-lab-blocks' ); ?>"
		data-vvm-villa-results-error="<?php esc_attr_e( 'More villas could not load automatically. Use the next page link.', 'gutenberg-lab-blocks' ); ?>"
	>
		<header class="vvm-villa-search-results__header">
			<p class="vvm-villa-search-results__summary" aria-live="polite">
				<?php echo esc_html( gutenberg_lab_blocks_get_villa_search_summary( $results['total'] ) ); ?>
			</p>

			<?php if ( $filter_labels ) : ?>
				<ul class="vvm-villa-search-results__filters" aria-label="<?php esc_attr_e( 'Selected filters', 'gutenberg-lab-blocks' ); ?>">
					<?php foreach ( $filter_labels as $filter_label ) : ?>
						<li><?php echo esc_html( $filter_label ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</header>

		<?php if ( '' !== $card_markup ) : ?>
			<div class="vvm-card-grid__items" data-vvm-villa-results-items>
				<?php echo $card_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		<?php else : ?>
			<div class="vvm-villa-search-results__empty">
				<h2><?php esc_html_e( 'No exact matches yet', 'gutenberg-lab-blocks' ); ?></h2>
				<p><?php esc_html_e( 'Try widening one or two filters, or browse the complete villa collection.', 'gutenberg-lab-blocks' ); ?></p>
				<?php if ( $archive_url ) : ?>
					<a class="vvm-villa-search-results__clear" href="<?php echo esc_url( $archive_url ); ?>">
						<?php esc_html_e( 'Clear all filters', 'gutenberg-lab-blocks' ); ?>
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php
		$pagination = gutenberg_lab_blocks_render_villa_search_pagination(
			$results['page'],
			$results['pages'],
			$request
		);
		?>
		<?php if ( '' !== $pagination ) : ?>
			<nav
				class="vvm-villa-search-results__pagination"
				aria-label="<?php esc_attr_e( 'Villa results pages', 'gutenberg-lab-blocks' ); ?>"
				data-vvm-villa-results-pagination
			>
				<?php echo $pagination; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</nav>
			<div class="vvm-villa-search-results__loader" aria-hidden="true">
				<span class="vvm-villa-search-results__spinner"></span>
				<span><?php esc_html_e( 'Loading more villas…', 'gutenberg-lab-blocks' ); ?></span>
			</div>
			<p
				class="vvm-villa-search-results__announcement"
				aria-live="polite"
				role="status"
				data-vvm-villa-results-status
			></p>
			<div
				class="vvm-villa-search-results__sentinel"
				aria-hidden="true"
				data-vvm-villa-results-sentinel
			></div>
		<?php endif; ?>
	</section>
	<?php

	return trim( (string) ob_get_clean() );
}
