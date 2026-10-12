<?php
/**
 * Local regression checks: ddev wp eval-file wp-content/plugins/gutenberg-lab-blocks/tests/villa-publication-qa.php
 * Creates disposable local fixtures and removes only those fixtures in finally.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! str_contains( home_url(), '.ddev.site' ) ) {
	throw new RuntimeException( 'Run these fixture tests locally only.' );
}

$ids = array();
$created_terms = array();
$checks = 0;
$assert = static function ( $condition, $label ) use ( &$checks ) {
	if ( ! $condition ) {
		throw new RuntimeException( $label );
	}
	++$checks;
};
try {
	foreach ( array( 'publish', 'draft', 'protected' ) as $status ) {
		$ids[ $status ] = wp_insert_post( array(
			'post_type' => 'villa', 'post_title' => 'QA House & Cottage ' . $status,
			'post_status' => 'protected' === $status ? 'publish' : $status,
			'post_password' => 'protected' === $status ? wp_generate_password() : '',
		), true );
		$assert( ! is_wp_error( $ids[ $status ] ), 'Create test fixture.' );
	}
	$id = $ids['publish'];
	update_post_meta( $id, 'villa_plain_hyphens', true );
	$GLOBALS['wp_query'] = new WP_Query( array( 'p' => $id, 'post_type' => 'villa' ) );
	$input = '<p>A &#8211; B &mdash; C - D</p><a href="/a—b">Link</a><script>{"name":"A—B"}</script><style>/* A—B */</style>';
	$output = gutenberg_lab_blocks_villa_plain_hyphens( $input );
	$assert( str_contains( html_entity_decode( $output ), '<p>A - B - C - D</p>' ), 'Normalize literal and encoded text dashes.' );
	$assert( str_contains( $output, 'href="/a—b"' ), 'Preserve URL attributes.' );
	$assert( str_contains( $output, '<script>{"name":"A—B"}</script>' ), 'Preserve scripts.' );
	$assert( str_contains( $output, '<style>/* A—B */</style>' ), 'Preserve styles.' );
	$assert( str_contains( html_entity_decode( wptexturize( gutenberg_lab_blocks_villa_plain_hyphens( wptexturize( '<p>A - B</p>' ) ) ) ), 'A - B' ), 'Survive an editor round-trip and nested template wptexturize.' );
	delete_post_meta( $id, 'villa_plain_hyphens' );
	$assert( $input === gutenberg_lab_blocks_villa_plain_hyphens( $input ), 'Other villas remain unchanged.' );

	$context = gutenberg_lab_vvm_seo_get_context();
	$assert( ! str_contains( $context['title'], '&#' ) && str_contains( $context['title'], ' & ' ), 'SEO title uses plain text ampersand.' );
	$crumbs = gutenberg_lab_vvm_seo_get_breadcrumb_items( $context );
	$assert( get_post_type_archive_link( 'villa' ) === $crumbs[1]['url'], 'Villas breadcrumb points to archive.' );

	$image_ids = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_mime_type' => 'image', 'numberposts' => 8, 'fields' => 'ids' ) );
	$assert( 8 === count( $image_ids ), 'Local schema test has eight existing images.' );
	$image_blocks = '';
	foreach ( $image_ids as $image_id ) {
		$image_blocks .= '<!-- wp:image {"id":' . $image_id . '} --><figure class="wp-block-image"><img class="wp-image-' . $image_id . '" /></figure><!-- /wp:image -->';
	}
	wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $image_blocks ) ) );
	update_post_meta( $id, 'villa_card_facts', '6 Bedrooms - 6.5 Bathrooms - Sleeps 12' );
	update_post_meta( $id, 'villa_schema_latitude', '13.2292958' );
	update_post_meta( $id, 'villa_schema_longitude', '-59.6394081' );
	$term_ids = array();
	foreach ( array( 'Gibbes Villas', 'St. Peter' ) as $name ) {
		$term = term_exists( $name, 'villa_location' );
		if ( ! $term ) {
			$term = wp_insert_term( $name, 'villa_location' );
			if ( is_wp_error( $term ) ) {
				throw new RuntimeException( $term->get_error_message() );
			}
			$created_terms[] = (int) $term['term_id'];
		}
		$term_ids[] = (int) $term['term_id'];
	}
	wp_set_object_terms( $id, $term_ids, 'villa_location' );
	$context['object'] = get_post( $id );
	$schema = gutenberg_lab_vvm_seo_get_villa_schema( $context, null );
	$assert( 'QA House & Cottage publish' === ( $schema['name'] ?? '' ), 'Rental name decodes the ampersand.' );
	$assert( 'QA House & Cottage publish' === ( $schema['containsPlace']['name'] ?? '' ), 'Accommodation name decodes the ampersand.' );
	$assert( 'Gibbes' === ( $schema['address']['addressLocality'] ?? '' ), 'Locality excludes the search category suffix.' );
	$assert( 'St. Peter' === ( $schema['address']['addressRegion'] ?? '' ), 'Parish is region, not country.' );
	$assert( 'BB' === ( $schema['address']['addressCountry'] ?? '' ), 'Country remains Barbados.' );
	$assert( 12 === ( $schema['containsPlace']['occupancy']['value'] ?? 0 ), 'Schema occupancy is unchanged.' );

	$calendar = gutenberg_lab_blocks_render_villa_availability_calendar( array( 'villaId' => $id, 'monthsToShow' => 1 ) );
	$assert( str_contains( $calendar, 'data-vvm-calendar' ), 'Keep the requested blank calendar without iCal.' );
	$assert( str_contains( $calendar, '"minimumDate":"' . wp_date( 'Y-m-d' ) . '"' ), 'Calendar minimum is today, not month start.' );
	$assert( ! str_contains( $calendar, '#038;' ), 'Enquiry villa name has no HTML entity.' );
	$past = new DateTimeImmutable( 'first day of last month', wp_timezone() );
	$html = gutenberg_lab_blocks_render_villa_availability_month( $past, array() );
	$parser = new WP_HTML_Tag_Processor( $html );
	while ( $parser->next_tag( 'BUTTON' ) ) {
		$assert( null !== $parser->get_attribute( 'disabled' ), 'Every past date is disabled in server HTML.' );
		$assert( 'true' === $parser->get_attribute( 'aria-disabled' ), 'Past date is accessibly disabled.' );
	}
	$tag = new WPCF7_FormTag( array( 'type' => 'date', 'name' => 'preferred-departure' ) );
	$_POST = array( 'villa-id' => $id, 'preferred-arrival' => wp_date( 'Y-m-d', time() - DAY_IN_SECONDS ), 'preferred-departure' => wp_date( 'Y-m-d', time() + DAY_IN_SECONDS ) );
	$result = gutenberg_lab_blocks_validate_cf7_villa_availability( new WPCF7_Validation(), $tag );
	$assert( ! $result->is_valid(), 'Server rejects a forged past arrival.' );
	$_POST['preferred-arrival'] = wp_date( 'Y-m-d' );
	$result = gutenberg_lab_blocks_validate_cf7_villa_availability( new WPCF7_Validation(), $tag );
	$assert( $result->is_valid(), 'Today and future checkout remain valid without a feed.' );
	$_POST['preferred-departure'] = $_POST['preferred-arrival'];
	$assert( ! gutenberg_lab_blocks_validate_cf7_villa_availability( new WPCF7_Validation(), $tag )->is_valid(), 'Checkout must follow arrival.' );

	$markup = '<!-- wp:query {"query":{"namespace":"gutenberg-lab/public-villa-sitemap","postType":"villa","perPage":100,"inherit":false}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- /wp:post-template --></div><!-- /wp:query -->';
	$html = do_blocks( $markup );
	$assert( str_contains( $html, get_permalink( $id ) ), 'Sitemap includes new public villa.' );
	$assert( ! str_contains( $html, get_permalink( $ids['draft'] ) ), 'Sitemap excludes drafts.' );
	$assert( ! str_contains( $html, get_permalink( $ids['protected'] ) ), 'Sitemap excludes password-protected villas.' );
	$purge_urls = gutenberg_lab_blocks_villa_sitemap_purge_urls( array( '/existing/', '/site-map/' ) );
	$assert( array( '/existing/', '/site-map/' ) === $purge_urls, 'Publication cache purge includes sitemap once and preserves existing targets.' );

	foreach ( array( array(), array( 'villa_page' => 2 ), array( 'villa_location' => 'gibbes-villas' ) ) as $request ) {
		$_GET = $request;
		$GLOBALS['wp_query'] = new WP_Query( array( 'post_type' => 'villa' ) );
		$context = gutenberg_lab_vvm_seo_get_context();
		$assert( 'villa_archive' === $context['kind'], 'Archive has SEO context.' );
		$assert( '' !== $context['description'], 'Archive has description.' );
		$robots = gutenberg_lab_vvm_seo_villa_archive_robots( array() );
		$assert( isset( $request['villa_location'] ) === ! empty( $robots['noindex'] ), 'Only filtered results are noindex.' );
		if ( isset( $request['villa_page'] ) ) {
			$assert( str_contains( $context['url'], 'villa_page=2' ), 'Page two has its own canonical.' );
		}
	}
	WP_CLI::success( $checks . ' publication regression checks passed.' );
} finally {
	$_GET = array();
	$_POST = array();
	wp_reset_query();
	foreach ( $ids as $id ) {
		if ( is_int( $id ) ) {
			wp_delete_post( $id, true );
		}
	}
	foreach ( $created_terms as $term_id ) {
		wp_delete_term( $term_id, 'villa_location' );
	}
}
