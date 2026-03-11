<?php
/**
 * Server-rendered footer meta block.
 *
 * The current year is calculated in PHP so editors never need to update it.
 */

$copyright_text = wp_kses_post( $attributes['copyrightText'] ?? '' );

if ( '' === trim( wp_strip_all_tags( $copyright_text ) ) ) {
	$copyright_text = 'Verse & Vision Media. All Rights Reserved';
}
?>
<p <?php echo get_block_wrapper_attributes( array( 'class' => 'vvm-site-footer-meta' ) ); ?>>
	<?php echo esc_html__( '© ', 'gutenberg-lab-blocks' ); ?>
	<?php echo esc_html( gmdate( 'Y' ) ); ?>
	<?php echo esc_html__( ' ', 'gutenberg-lab-blocks' ); ?>
	<?php echo $copyright_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</p>
