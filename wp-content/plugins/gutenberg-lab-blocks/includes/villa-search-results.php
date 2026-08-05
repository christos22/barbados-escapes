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
	$archive_url = get_post_type_archive_link( 'villa' );

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

	return array_values( array_map( 'absint', $query->posts ) );
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
 * Returns a villa enquiry URL carrying the active search dates.
 *
 * Only the normalized date pair is forwarded. Other search filters describe
 * the results list, while the villa form only needs the requested stay.
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

	$arrival   = isset( $request['arrival'] ) ? (string) $request['arrival'] : '';
	$departure = isset( $request['departure'] ) ? (string) $request['departure'] : '';

	if ( '' !== $arrival && '' !== $departure && $departure > $arrival ) {
		$permalink = add_query_arg(
			array(
				'arrival'   => $arrival,
				'departure' => $departure,
			),
			$permalink
		);
	}

	return $permalink . '#enquire';
}

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

	$archive_url = get_post_type_archive_link( 'villa' );

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
	$request       = gutenberg_lab_blocks_get_villa_search_request();
	$results       = gutenberg_lab_blocks_get_villa_search_results( $request );
	$filter_labels = gutenberg_lab_blocks_get_villa_search_filter_labels( $request );
	$archive_url   = get_post_type_archive_link( 'villa' );
	$card_markup   = gutenberg_lab_blocks_render_villa_search_cards( $results['ids'], $request );

	ob_start();
	?>
	<section
		<?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		data-vvm-villa-search-results
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
