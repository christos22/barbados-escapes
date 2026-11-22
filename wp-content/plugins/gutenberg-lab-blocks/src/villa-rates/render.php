<?php
/**
 * Server rendering for the Villa Rates Table.
 *
 * Outputs the same markup the old core/table produced
 * (`figure.wp-block-table.vvm-villa-pricing__table > table`), so the theme's
 * table styles and the bedroom selector's view.js work unchanged. The only
 * difference: each row carries its own `data-vvm-bedroom-pricing-key`, read
 * from the row block instead of a position-based meta list.
 *
 * @package GutenbergLabBlocks
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

$headers = gutenberg_lab_blocks_villa_rates_get_headers( $attributes['headers'] ?? null );
$rows    = gutenberg_lab_blocks_villa_rates_get_rows(
	$block->parsed_block['innerBlocks'] ?? array(),
	count( $headers )
);

if ( empty( $rows ) ) {
	return;
}

// Adds colour classes and any custom class from the editor.
$wrapper_attributes = get_block_wrapper_attributes(
	array( 'class' => 'wp-block-table vvm-villa-pricing__table' )
);
?>
<figure <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><table><thead><tr>
<?php foreach ( $headers as $header ) : ?>
<th><?php echo $header; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post in helper. ?></th>
<?php endforeach; ?>
</tr></thead><tbody>
<?php foreach ( $rows as $row ) : ?>
<tr<?php echo '' !== $row['key'] ? ' data-vvm-bedroom-pricing-key="' . esc_attr( $row['key'] ) . '"' : ''; ?>>
	<?php foreach ( $row['cells'] as $cell ) : ?>
<td><?php echo $cell; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post in helper. ?></td>
	<?php endforeach; ?>
</tr>
<?php endforeach; ?>
</tbody></table></figure>
