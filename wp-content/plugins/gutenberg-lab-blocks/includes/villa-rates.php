<?php
/**
 * Villa Rates Table helpers.
 *
 * The Villa Rates Table block stores each rate row as a child block with its
 * own cells and bedroom option key. Because the key lives on the row itself,
 * editors can add, remove and reorder rows without the old position-based
 * `villa_pricing_row_bedroom_keys` meta drifting out of line.
 *
 * These helpers are shared by the block's render.php, the importer and the
 * one-off conversion from the old core/table markup.
 *
 * @package GutenbergLabBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Most columns a rates table may have. Real tables use three or four.
 */
const GUTENBERG_LAB_BLOCKS_VILLA_RATES_MAX_COLUMNS = 6;

/**
 * Column headings used when a table has none.
 *
 * @return array<int, string>
 */
function gutenberg_lab_blocks_villa_rates_default_headers() {
	return array( 'Season', 'Rate per night', 'Minimum stay' );
}

/**
 * Sanitizes one cell or heading. Cells are rich text (bold, links, asterisks).
 *
 * @param mixed $html Raw cell HTML.
 * @return string
 */
function gutenberg_lab_blocks_villa_rates_sanitize_cell( $html ) {
	return trim( wp_kses_post( is_scalar( $html ) ? (string) $html : '' ) );
}

/**
 * Returns the table headings, falling back to the defaults.
 *
 * @param mixed $raw_headers Block `headers` attribute.
 * @return array<int, string>
 */
function gutenberg_lab_blocks_villa_rates_get_headers( $raw_headers ) {
	$headers = array();

	foreach ( is_array( $raw_headers ) ? $raw_headers : array() as $header ) {
		if ( count( $headers ) >= GUTENBERG_LAB_BLOCKS_VILLA_RATES_MAX_COLUMNS ) {
			break;
		}

		$headers[] = gutenberg_lab_blocks_villa_rates_sanitize_cell( $header );
	}

	return $headers ? $headers : gutenberg_lab_blocks_villa_rates_default_headers();
}

/**
 * Reads the rate rows from the block's parsed child blocks.
 *
 * Cells are padded or trimmed to the heading count, so adding or removing a
 * column never breaks a row. Rows with no text at all are skipped, which keeps
 * a freshly added empty row off the live page.
 *
 * @param array<int, array<string, mixed>> $inner_blocks Parsed child blocks.
 * @param int                              $column_count Number of columns.
 * @return array<int, array{cells: array<int, string>, key: string}>
 */
function gutenberg_lab_blocks_villa_rates_get_rows( $inner_blocks, $column_count ) {
	$rows = array();

	foreach ( is_array( $inner_blocks ) ? $inner_blocks : array() as $inner_block ) {
		if ( 'gutenberg-lab-blocks/villa-rate-row' !== ( $inner_block['blockName'] ?? '' ) ) {
			continue;
		}

		// Rows never render themselves (the parent builds the table), so the
		// site-wide "Hide on frontend" toggle must be honoured here.
		if (
			function_exists( 'gutenberg_lab_blocks_is_block_hidden_on_frontend' ) &&
			gutenberg_lab_blocks_is_block_hidden_on_frontend( $inner_block )
		) {
			continue;
		}

		$attrs     = (array) ( $inner_block['attrs'] ?? array() );
		$raw_cells = array_values( is_array( $attrs['cells'] ?? null ) ? $attrs['cells'] : array() );
		$cells     = array();

		for ( $index = 0; $index < $column_count; $index++ ) {
			$cells[] = gutenberg_lab_blocks_villa_rates_sanitize_cell( $raw_cells[ $index ] ?? '' );
		}

		if ( '' === trim( wp_strip_all_tags( implode( '', $cells ) ) ) ) {
			continue;
		}

		$rows[] = array(
			'cells' => $cells,
			// Empty key = shown under every bedroom option.
			'key'   => gutenberg_lab_blocks_sanitize_villa_bedroom_pricing_key(
				$attrs['bedroomOption'] ?? ''
			),
		);
	}

	return $rows;
}

/**
 * Gives every bedroom option a stable key (PHP twin of withChoiceKeys() in
 * src/shared/villa-bedroom-options.js).
 *
 * Options without a key get the key they already use today (derived from the
 * label, for example `bedrooms-4`), so existing rate rows stay linked. A key
 * that is empty or already taken gets a random `choice-` key instead.
 *
 * @param mixed $raw_choices Stored or imported bedroom options.
 * @return array<int, array{label:string, key:string}>
 */
function gutenberg_lab_blocks_villa_rates_key_choices( $raw_choices ) {
	$choices = gutenberg_lab_blocks_sanitize_villa_bedroom_choice_rows( $raw_choices );
	$used    = array_fill_keys( array_filter( array_column( $choices, 'key' ) ), true );

	foreach ( $choices as &$choice ) {
		if ( ! empty( $choice['key'] ) ) {
			continue;
		}

		$key = gutenberg_lab_blocks_get_villa_bedroom_pricing_key_from_label( $choice['label'] );

		if ( '' === $key || isset( $used[ $key ] ) ) {
			$key = 'choice-' . strtolower( wp_generate_password( 8, false ) );
		}

		$choice['key'] = $key;
		$used[ $key ]  = true;
	}
	unset( $choice );

	return $choices;
}

/**
 * Serializes a Villa Rates Table block for saving into post content.
 *
 * Used by the importer and by the conversion from core/table. The output is the
 * same block markup the editor saves, so the editor opens it without a
 * "block contains unexpected content" warning.
 *
 * @param array<int, string>                                     $headers     Column headings.
 * @param array<int, array{cells: array<int, string>, key?: string}> $rows        Rate rows.
 * @param array<string, mixed>                                   $extra_attrs Extra parent attributes (color, className).
 * @return string
 */
function gutenberg_lab_blocks_villa_rates_serialize( $headers, $rows, $extra_attrs = array() ) {
	$headers     = gutenberg_lab_blocks_villa_rates_get_headers( $headers );
	$attrs       = $extra_attrs;
	$inner       = array();
	$inner_parts = array( "\n" );

	// Only store headings when they differ from the block.json default.
	if ( gutenberg_lab_blocks_villa_rates_default_headers() !== $headers ) {
		$attrs = array( 'headers' => $headers ) + $attrs;
	}

	foreach ( $rows as $index => $row ) {
		$row_attrs = array(
			'cells' => array_map(
				'gutenberg_lab_blocks_villa_rates_sanitize_cell',
				array_values( (array) ( $row['cells'] ?? array() ) )
			),
		);
		$key       = gutenberg_lab_blocks_sanitize_villa_bedroom_pricing_key( $row['key'] ?? '' );

		if ( '' !== $key ) {
			$row_attrs['bedroomOption'] = $key;
		}

		$inner[] = array(
			'blockName'    => 'gutenberg-lab-blocks/villa-rate-row',
			'attrs'        => $row_attrs,
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		);

		// The editor separates child blocks with a blank line.
		$inner_parts[] = null;
		$inner_parts[] = $index < count( $rows ) - 1 ? "\n\n" : "\n";
	}

	return serialize_block(
		array(
			'blockName'    => 'gutenberg-lab-blocks/villa-rates',
			'attrs'        => $attrs,
			'innerBlocks'  => $inner,
			'innerHTML'    => implode( '', array_filter( $inner_parts, 'is_string' ) ),
			'innerContent' => $rows ? $inner_parts : array(),
		)
	);
}
