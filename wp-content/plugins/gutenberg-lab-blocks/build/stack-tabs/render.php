<?php
/**
 * Server rendering for the Stack Tabs parent block.
 *
 * The parent owns the accessible tablist markup and delegates each panel's
 * left/right reveal layout to the child `stack-tab` renderer.
 *
 * @package GutenbergLabBlocks
 */

$heading = wp_kses_post( $attributes['heading'] ?? '' );
$intro   = wp_kses_post( $attributes['intro'] ?? '' );

$parsed_inner_blocks = $block->parsed_block['innerBlocks'] ?? array();
$parsed_inner_blocks = is_array( $parsed_inner_blocks ) ? $parsed_inner_blocks : array();
$tab_blocks          = array_values(
	array_filter(
		$parsed_inner_blocks,
		static function ( $inner_block ) {
			return isset( $inner_block['blockName'] ) && 'gutenberg-lab-blocks/stack-tab' === $inner_block['blockName'];
		}
	)
);

if ( empty( $tab_blocks ) ) {
	return;
}

$section_label = wp_strip_all_tags( $heading );

if ( '' === $section_label ) {
	$section_label = __( 'Stack tabs section', 'gutenberg-lab-blocks' );
}

$section_id_base = ! empty( $attributes['anchor'] )
	? sanitize_html_class( $attributes['anchor'] )
	: sanitize_html_class( wp_unique_id( 'vvm-stack-tabs-' ) );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'               => 'vvm-stack-tabs',
		'data-stack-tabs-root' => '',
		'data-stack-tabs-id'  => $section_id_base,
	)
);
?>

<section <?php echo $wrapper_attributes; ?>>
	<div class="vvm-stack-tabs__shell">
		<?php if ( '' !== trim( wp_strip_all_tags( $heading ) . wp_strip_all_tags( $intro ) ) ) : ?>
			<div class="vvm-stack-tabs__header">
				<?php if ( '' !== trim( wp_strip_all_tags( $heading ) ) ) : ?>
					<h2 class="vvm-stack-tabs__heading">
						<?php echo $heading; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</h2>
				<?php endif; ?>

				<?php if ( '' !== trim( wp_strip_all_tags( $intro ) ) ) : ?>
					<div class="vvm-stack-tabs__intro">
						<?php echo wpautop( $intro ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="vvm-stack-tabs__nav" role="tablist" aria-label="<?php echo esc_attr( $section_label ); ?>">
			<?php foreach ( $tab_blocks as $index => $tab_block ) : ?>
				<?php
				$label     = trim( (string) ( $tab_block['attrs']['label'] ?? '' ) );
				$label     = '' !== $label ? $label : sprintf( __( 'Tab %d', 'gutenberg-lab-blocks' ), $index + 1 );
				$button_id = sprintf( '%1$s-tab-%2$d', $section_id_base, $index + 1 );
				$panel_id  = sprintf( '%1$s-panel-%2$d', $section_id_base, $index + 1 );
				?>
				<button
					type="button"
					id="<?php echo esc_attr( $button_id ); ?>"
					class="vvm-stack-tabs__tab-button<?php echo 0 === $index ? ' is-active' : ''; ?>"
					role="tab"
					aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>"
					aria-controls="<?php echo esc_attr( $panel_id ); ?>"
					tabindex="<?php echo 0 === $index ? '0' : '-1'; ?>"
					data-stack-tabs-tab-button
				>
					<span class="vvm-stack-tabs__tab-button-label"><?php echo esc_html( $label ); ?></span>
				</button>
			<?php endforeach; ?>
		</div>

		<div class="vvm-stack-tabs__panels">
			<?php foreach ( $tab_blocks as $index => $tab_block ) : ?>
				<?php
				$button_id = sprintf( '%1$s-tab-%2$d', $section_id_base, $index + 1 );
				$panel_id  = sprintf( '%1$s-panel-%2$d', $section_id_base, $index + 1 );
				?>
				<div
					id="<?php echo esc_attr( $panel_id ); ?>"
					class="vvm-stack-tabs__panel<?php echo 0 === $index ? ' is-active' : ''; ?>"
					role="tabpanel"
					aria-labelledby="<?php echo esc_attr( $button_id ); ?>"
					tabindex="0"
					data-stack-tabs-panel
				>
					<?php echo render_block( $tab_block ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
