<?php
/**
 * Read-only coverage for legacy, newly imported and empty villa hero intros.
 * Run: ddev wp eval-file wp-content/plugins/gutenberg-lab-blocks/tests/villa-hero-intro.php
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	throw new RuntimeException( 'Run this check through WP-CLI.' );
}
$checks = 0;
$assert = static function ( $condition, $message ) use ( &$checks ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
	++$checks;
};
$find = static function ( $blocks, $name ) use ( &$find ) {
	foreach ( $blocks as $item ) {
		if ( $name === ( $item['blockName'] ?? '' ) ) {
			return $item;
		}
		$nested = $find( $item['innerBlocks'] ?? array(), $name );
		if ( $nested ) {
			return $nested;
		}
	}
	return null;
};
$villas = get_posts( array( 'post_type' => 'villa', 'post_status' => 'publish', 'posts_per_page' => -1 ) );
$assert( count( $villas ) > 0, 'Real villa fixtures must be available.' );
foreach ( $villas as $villa ) {
	$parsed = $find( parse_blocks( $villa->post_content ), 'gutenberg-lab-blocks/villa-gallery-hero' );
	$assert( (bool) $parsed, 'Missing hero: ' . $villa->ID );
	$block = new WP_Block( $parsed, array( 'postId' => $villa->ID, 'postType' => 'villa' ) );
	$markup = $block->render();
	$regions = gutenberg_lab_blocks_villa_gallery_hero_get_regions( $block );
	$intro = $regions['content_markup'];
	$assert( 1 === substr_count( $intro, '<h1 ' ), 'One villa title: ' . $villa->ID );
	if ( $find( $parsed['innerBlocks'], 'core/post-title' ) ) {
		$assert( str_contains( $intro, '>' . esc_html( $villa->post_title ) . '</h1>' ), 'Hero must use the authored name without protected/private prefixes: ' . $villa->ID );
	}
	$assert( 1 === substr_count( $intro, '<p ' ), 'One location: ' . $villa->ID );
	$assert( ! preg_match( '/<(?:a|button|h4)\b/i', $intro ), 'Retired hero CTA or heading: ' . $villa->ID );
	$assert( str_contains( $markup, $intro ), 'Intro must appear in real rendered hero: ' . $villa->ID );
	$assert( $villa->post_content === get_post_field( 'post_content', $villa->ID ), 'Rendering must not rewrite the villa.' );
}

// Both import modes call this builder; retired workbook text must stay out of the hero.
$imported = gutenberg_lab_blocks_villa_importer_build_hero_content( array( 'overview' => array(
	'hero_location_line' => 'St. Peter & St. James',
	'hero_statement'     => 'RETIRED DESCRIPTION',
) ) );
$blocks = parse_blocks( $imported );
$assert( 2 === count( $blocks ) && 'core/post-title' === $blocks[0]['blockName'], 'Imports must start with the native title.' );
$assert( 'core/paragraph' === $blocks[1]['blockName'], 'Location must remain a native paragraph.' );
$assert( ! str_contains( $imported, 'RETIRED' ) && ! str_contains( $imported, 'wp:button' ), 'Imports must omit retired hero copy/buttons.' );
$intro = gutenberg_lab_blocks_villa_gallery_hero_get_intro_markup( $blocks, $block );
$assert( str_contains( $intro, 'St. Peter &amp; St. James' ) && ! str_contains( $intro, '&amp;amp;' ), 'Location entities must be escaped exactly once.' );

$nested = parse_blocks( '<!-- wp:group --><div class="wp-block-group"><!-- wp:heading {"level":4} --><h4 class="wp-block-heading">Sandy Lane</h4><!-- /wp:heading --><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Villa &amp; Cottage</h1><!-- /wp:heading --><!-- wp:paragraph --><p>Retired copy</p><!-- /wp:paragraph --></div><!-- /wp:group -->' );
$intro = gutenberg_lab_blocks_villa_gallery_hero_get_intro_markup( $nested, $block );
$assert( str_contains( $intro, '>Villa &amp; Cottage</h1><p ' ) && str_contains( $intro, '>Sandy Lane</p>' ), 'Legacy groups must retain authored title/location in the new order.' );
$assert( ! str_contains( $intro, 'Retired copy' ), 'Nested descriptions must be omitted.' );
$intro = gutenberg_lab_blocks_villa_gallery_hero_get_intro_markup( array(), $block );
$assert( str_contains( $intro, '<h1 ' ) && ! str_contains( $intro, 'post-excerpt' ), 'Empty heroes must use post title/location, never the excerpt.' );

WP_CLI::success( $checks . ' intro checks passed across ' . count( $villas ) . ' published villas. No content changed.' );
