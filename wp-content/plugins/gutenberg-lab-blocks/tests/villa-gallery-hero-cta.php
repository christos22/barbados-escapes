<?php
/**
 * Run with: wp eval-file wp-content/plugins/gutenberg-lab-blocks/tests/villa-gallery-hero-cta.php
 * An optional first argument selects a clean release render.php for isolated testing.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

$block_type = WP_Block_Type_Registry::get_instance()->get_registered( 'gutenberg-lab-blocks/villa-gallery-hero' );
$attributes = $block_type->prepare_attributes_for_render( array() );
$parsed     = array( 'blockName' => $block_type->name, 'attrs' => $attributes, 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() );
$block      = new WP_Block( $parsed, array( 'postId' => 0 ) );
$previous_supports_block = WP_Block_Supports::$block_to_render;
WP_Block_Supports::$block_to_render = $parsed;
ob_start();
require $args[0] ?? WP_PLUGIN_DIR . '/gutenberg-lab-blocks/build/villa-gallery-hero/render.php';
ob_end_clean();
WP_Block_Supports::$block_to_render = $previous_supports_block;

$copy  = '<p class="intro">Wellness &#45; by the sea. A &amp; B. &#8217;</p>';
$other = '<a href="/contact/?a=1&amp;b=2" class="contact">Contact</a>';
$input = $copy . $other . '<a href="#old"><span>Explore Gallery</span> &#8594;</a><a href="#second">Explore Gallery</a>';
$output = gutenberg_lab_blocks_villa_gallery_hero_link_gallery_cta( $input, 'test-gallery' );

$checks = array(
	'Authored text and entities remain unchanged' => str_starts_with( $output, $copy . $other ),
	'Both gallery links target the rail'          => 2 === substr_count( $output, 'href="#test-gallery"' ),
	'Both gallery links retain their JS hook'     => 2 === substr_count( $output, 'data-villa-gallery-hero-cta="gallery"' ),
	'Nested label and arrow remain unchanged'    => str_contains( $output, '<span>Explore Gallery</span> &#8594;</a>' ),
	'WordPress preserves the short hyphen'        => str_contains( html_entity_decode( wptexturize( $output ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ), 'Wellness - by the sea.' ),
	'Non-gallery content is untouched'           => $copy . $other === gutenberg_lab_blocks_villa_gallery_hero_link_gallery_cta( $copy . $other, 'test-gallery' ),
	'Empty target is untouched'                  => $input === gutenberg_lab_blocks_villa_gallery_hero_link_gallery_cta( $input, '' ),
	'Unmatched link labels are untouched'        => '<p>Explore Gallery</p>' . $other === gutenberg_lab_blocks_villa_gallery_hero_link_gallery_cta( '<p>Explore Gallery</p>' . $other, 'test-gallery' ),
);
foreach ( $checks as $label => $passed ) {
	if ( ! $passed ) {
		WP_CLI::error( $label );
	}
}
WP_CLI::success( count( $checks ) . ' hero CTA markup checks passed.' );
