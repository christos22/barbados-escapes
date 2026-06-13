<?php
/**
 * Dynamic render for the Villa Bedroom Selector block.
 *
 * @package GutenbergLabBlocks
 */

$context_id = isset( $block->context['postId'] )
	? absint( $block->context['postId'] )
	: 0;
$villa_id   = gutenberg_lab_blocks_resolve_villa_booking_post_id( $context_id );
$minimum    = max( 1, absint( $attributes['minimumBedrooms'] ?? 1 ) );
$choices    = gutenberg_lab_blocks_get_villa_bedroom_choices(
	$villa_id,
	$minimum
);

if ( empty( $choices ) ) {
	return '';
}

$select_id         = wp_unique_id( 'vvm-villa-bedroom-selector-' );
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'                          => 'vvm-villa-bedroom-selector',
		'data-vvm-bedroom-selector-root' => '',
	)
);
?>

<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<label class="screen-reader-text" for="<?php echo esc_attr( $select_id ); ?>">
		<?php esc_html_e( 'Bedrooms for seasonal pricing', 'gutenberg-lab-blocks' ); ?>
	</label>
	<select
		id="<?php echo esc_attr( $select_id ); ?>"
		class="vvm-villa-bedroom-selector__select"
		data-vvm-bedroom-selector
	>
		<?php foreach ( $choices as $value => $label ) : ?>
			<option value="<?php echo esc_attr( (string) $value ); ?>">
				<?php echo esc_html( $label ); ?>
			</option>
		<?php endforeach; ?>
	</select>
</div>
