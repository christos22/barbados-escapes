<?php
/**
 * Server rendering for the Read More block.
 *
 * The frontend shell is dynamic so we can output a real button while the
 * revealed body remains ordinary editor-authored Gutenberg content.
 *
 * @package GutenbergLabBlocks
 */

$read_more_label = trim( wp_strip_all_tags( (string) ( $attributes['readMoreLabel'] ?? '' ) ) );
$read_less_label = trim( wp_strip_all_tags( (string) ( $attributes['readLessLabel'] ?? '' ) ) );

if ( '' === $read_more_label ) {
	$read_more_label = __( 'Read More', 'gutenberg-lab-blocks' );
}

if ( '' === $read_less_label ) {
	$read_less_label = __( 'Read Less', 'gutenberg-lab-blocks' );
}

$id_base = ! empty( $attributes['anchor'] )
	? sanitize_html_class( $attributes['anchor'] )
	: sanitize_html_class( wp_unique_id( 'vvm-read-more-' ) );

$content_id = $id_base . '-content';

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'                              => 'vvm-read-more',
		'data-vvm-read-more-root'           => '',
		'data-vvm-read-more-collapsed-label' => $read_more_label,
		'data-vvm-read-more-expanded-label'  => $read_less_label,
	)
);
?>

<section <?php echo $wrapper_attributes; ?>>
	<div class="vvm-read-more__control">
		<button
			type="button"
			class="vvm-read-more__button"
			aria-expanded="false"
			aria-controls="<?php echo esc_attr( $content_id ); ?>"
			data-vvm-read-more-button
		>
			<span class="vvm-read-more__button-label" data-vvm-read-more-label><?php echo esc_html( $read_more_label ); ?></span>
		</button>
	</div>

	<div
		id="<?php echo esc_attr( $content_id ); ?>"
		class="vvm-read-more__content"
		data-vvm-read-more-content
	>
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</section>
