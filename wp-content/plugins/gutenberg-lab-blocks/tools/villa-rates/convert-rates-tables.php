<?php
/**
 * One-off conversion: old core/table rates tables to the Villa Rates Table block.
 *
 * Run with WP-CLI (`wp eval-file` cannot read stdin, so copy this file to the
 * server first). Arguments are key=value pairs:
 *
 *   mode=dry-run plan=/path/plan.json [ids=12,34]
 *       Reads every villa, builds the new block, renders old and new tables and
 *       compares them. Writes nothing to the database. Saves a plan file with
 *       each villa's content hash and the rows guests currently see.
 *
 *   mode=apply plan=/path/plan.json backup=/private/dir [ids=12,34]
 *       Converts only villas whose content hash still matches the plan (anyone
 *       editing since the dry run is left alone). Backs up content and meta to
 *       the backup dir first. Also stores stable keys on the bedroom options.
 *
 *   mode=verify plan=/path/plan.json
 *       Run in a fresh process after apply. Re-renders each converted villa
 *       and checks the rows match the plan exactly.
 *
 * @package GutenbergLabBlocks
 */

global $wpdb;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

$opts = array();
foreach ( (array) ( $args ?? array() ) as $arg ) {
	if ( false !== strpos( $arg, '=' ) ) {
		list( $k, $v ) = explode( '=', $arg, 2 );
		$opts[ $k ]    = $v;
	}
}

$mode      = $opts['mode'] ?? 'dry-run';
$plan_path = $opts['plan'] ?? '';
$only_ids  = array_filter( array_map( 'absint', explode( ',', $opts['ids'] ?? '' ) ) );

if ( '' === $plan_path ) {
	WP_CLI::error( 'Pass plan=/path/to/plan.json' );
}

$table_class = 'vvm-villa-pricing__table';

/**
 * Finds the one old rates table block in a post's raw content.
 *
 * @param string $content Post content.
 * @return array{markup:string, attrs:array}|WP_Error
 */
$find_old_table = static function ( $content ) use ( $table_class ) {
	preg_match_all( '~<!-- wp:table (\{.*?\}) -->.*?<!-- /wp:table -->~s', $content, $matches, PREG_SET_ORDER );
	$found = array();

	foreach ( $matches as $match ) {
		$attrs = json_decode( $match[1], true );
		$class = is_array( $attrs ) ? (string) ( $attrs['className'] ?? '' ) : '';

		if ( preg_match( '/(?:^|\s)' . $table_class . '(?:\s|$)/', $class ) ) {
			$found[] = array(
				'markup' => $match[0],
				'attrs'  => $attrs,
			);
		}
	}

	if ( 1 !== count( $found ) ) {
		return new WP_Error( 'table_count', sprintf( 'expected 1 old rates table, found %d', count( $found ) ) );
	}

	return $found[0];
};

/**
 * Splits table HTML into headings and body rows (cell inner HTML kept as is).
 *
 * @param string $html Table block HTML.
 * @return array{headers: array<int,string>, rows: array<int, array<int,string>>}
 */
$parse_table_html = static function ( $html ) {
	$headers = array();
	$rows    = array();

	if ( preg_match( '~<thead\b[^>]*>(.*?)</thead>~is', $html, $head ) ) {
		preg_match_all( '~<th\b[^>]*>(.*?)</th>~is', $head[1], $ths );
		$headers = array_map( 'trim', $ths[1] );
	}

	if ( preg_match( '~<tbody\b[^>]*>(.*?)</tbody>~is', $html, $body ) ) {
		preg_match_all( '~<tr\b([^>]*)>(.*?)</tr>~is', $body[1], $trs, PREG_SET_ORDER );

		foreach ( $trs as $tr ) {
			preg_match_all( '~<td\b[^>]*>(.*?)</td>~is', $tr[2], $tds );

			if ( $tds[1] ) {
				$rows[] = array(
					'attrs' => $tr[1],
					'cells' => array_map( 'trim', $tds[1] ),
				);
			}
		}
	}

	return array(
		'headers' => $headers,
		'rows'    => $rows,
	);
};

/**
 * What guests get from a rendered table: headings plus each row's key and
 * cells. Two renders with the same signature behave identically on the page.
 *
 * @param string $rendered Rendered HTML.
 * @return array
 */
$signature = static function ( $rendered ) use ( $parse_table_html ) {
	$parsed = $parse_table_html( $rendered );
	$rows   = array();

	foreach ( $parsed['rows'] as $row ) {
		$key = preg_match( '/data-vvm-bedroom-pricing-key="([^"]*)"/', $row['attrs'], $m ) ? $m[1] : '';
		// Compare cells after the same kses pass the new block applies.
		$rows[] = array( $key, array_map( 'gutenberg_lab_blocks_villa_rates_sanitize_cell', $row['cells'] ) );
	}

	return array(
		'headers' => array_map( 'gutenberg_lab_blocks_villa_rates_sanitize_cell', $parsed['headers'] ),
		'rows'    => $rows,
	);
};

// Renders one block in the context of a villa, as on its live page.
$render_in_villa = static function ( $parsed_block, $villa_id ) {
	global $post;
	$post = get_post( $villa_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	setup_postdata( $post );
	$html = render_block( $parsed_block );
	wp_reset_postdata();

	return $html;
};

// Gives every saved bedroom option a key (its current derived key), so later
// renames keep their rate rows. Mirrors withChoiceKeys() in the editor.
$keyed_choices = static function ( $villa_id ) {
	$raw = get_post_meta( $villa_id, 'villa_bedroom_selector_choices', true );

	if ( ! is_array( $raw ) || ! $raw ) {
		return null;
	}

	$current = gutenberg_lab_blocks_sanitize_villa_bedroom_choice_rows( $raw );
	$keyed   = gutenberg_lab_blocks_villa_rates_key_choices( $raw );

	// null = every option already has a key; nothing to save.
	return $keyed !== $current ? $keyed : null;
};

/**
 * Builds the conversion for one villa without saving anything.
 *
 * @param WP_Post $villa Villa post.
 * @return array|WP_Error
 */
$build = static function ( $villa ) use ( $find_old_table, $parse_table_html, $render_in_villa, $signature, $table_class ) {
	$old = $find_old_table( $villa->post_content );

	if ( is_wp_error( $old ) ) {
		return $old;
	}

	// 1. Render the old table exactly as the live site does today, with the
	// position-based row keys applied by the legacy render filter.
	$old_blocks = parse_blocks( $old['markup'] );
	$old_render = $render_in_villa( $old_blocks[0], $villa->ID );
	$old_sig    = $signature( $old_render );

	// 2. Take the cells from the saved HTML and the row keys from that render,
	// so the new rows carry exactly the links guests see now.
	$saved = $parse_table_html( $old_blocks[0]['innerHTML'] );

	if ( count( $saved['rows'] ) !== count( $old_sig['rows'] ) ) {
		return new WP_Error( 'row_count', 'saved and rendered row counts differ' );
	}

	// Without a bedroom dropdown nothing filters the rows, so any copied row
	// links are dead weight. Drop them rather than carry hidden links into the
	// new table, where they would surprise someone who later turns it on.
	$has_dropdown = gutenberg_lab_blocks_should_render_villa_bedroom_selector( $villa->ID );

	$rows = array();
	foreach ( $saved['rows'] as $index => $row ) {
		$rows[] = array(
			'cells' => $row['cells'],
			'key'   => $has_dropdown ? $old_sig['rows'][ $index ][0] : '',
		);
	}

	// What guests get: with no dropdown, row links have no effect on the page,
	// so compare headings and cells only.
	$expected_sig = $old_sig;
	if ( ! $has_dropdown ) {
		foreach ( $expected_sig['rows'] as &$expected_row ) {
			$expected_row[0] = '';
		}
		unset( $expected_row );
	}

	// 3. Carry over native settings (colour, extra classes).
	$extra = array();
	foreach ( array( 'textColor', 'style' ) as $attr ) {
		if ( isset( $old['attrs'][ $attr ] ) ) {
			$extra[ $attr ] = $old['attrs'][ $attr ];
		}
	}
	$classes = trim( preg_replace( '/(?:^|\s)' . $table_class . '(?=\s|$)/', ' ', (string) $old['attrs']['className'] ) );
	if ( '' !== $classes ) {
		$extra['className'] = $classes;
	}

	$notes = array();
	if ( ! empty( $old['attrs']['hasFixedLayout'] ) ) {
		$notes[] = 'old table had fixed layout';
	}

	$new_markup = gutenberg_lab_blocks_villa_rates_serialize( $saved['headers'], $rows, $extra );
	$new_blocks = parse_blocks( $new_markup );
	$new_render = $render_in_villa( $new_blocks[0], $villa->ID );
	$new_sig    = $signature( $new_render );

	return array(
		'old_markup' => $old['markup'],
		'new_markup' => $new_markup,
		'old_sig'    => $old_sig,
		'new_sig'    => $new_sig,
		'matches'    => $expected_sig === $new_sig,
		'notes'      => $has_dropdown || ! array_filter( array_column( $old_sig['rows'], 0 ) )
			? $notes
			: array_merge( $notes, array( 'no dropdown: unused copied row links dropped' ) ),
	);
};

$query = array(
	'post_type'   => 'villa',
	'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ),
	'numberposts' => -1,
	'orderby'     => 'ID',
	'order'       => 'ASC',
);
if ( $only_ids ) {
	$query['post__in'] = $only_ids;
}
$villas = get_posts( $query );

// ---------------------------------------------------------------- dry run --
if ( 'dry-run' === $mode ) {
	$plan  = array();
	$stats = array( 'convert' => 0, 'skip' => 0, 'mismatch' => 0 );

	foreach ( $villas as $villa ) {
		$result = $build( $villa );
		$label  = sprintf( '%d %s [%s]', $villa->ID, html_entity_decode( $villa->post_title ), $villa->post_status );

		if ( is_wp_error( $result ) ) {
			WP_CLI::log( "SKIP  $label: " . $result->get_error_message() );
			++$stats['skip'];
			continue;
		}

		if ( ! $result['matches'] ) {
			WP_CLI::warning( "MISMATCH $label: new table would differ from the live one" );
			WP_CLI::log( wp_json_encode( array( 'old' => $result['old_sig'], 'new' => $result['new_sig'] ) ) );
			++$stats['mismatch'];
			continue;
		}

		$keys = array_count_values( array_map( static fn( $r ) => '' === $r[0] ? 'all' : $r[0], $result['new_sig']['rows'] ) );
		WP_CLI::log(
			sprintf(
				'OK    %s: %d rows, %d cols, links %s%s',
				$label,
				count( $result['new_sig']['rows'] ),
				count( $result['new_sig']['headers'] ),
				wp_json_encode( $keys ),
				$result['notes'] ? ' NOTE: ' . implode( '; ', $result['notes'] ) : ''
			)
		);

		$plan[ $villa->ID ] = array(
			'title'     => $villa->post_title,
			'hash'      => md5( $villa->post_content ),
			// Guest-equivalent to today's table (checked above); verify uses it.
			'signature' => $result['new_sig'],
		);
		++$stats['convert'];
	}

	if ( ! wp_mkdir_p( dirname( $plan_path ) ) || false === file_put_contents( $plan_path, wp_json_encode( $plan, JSON_PRETTY_PRINT ) ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
		WP_CLI::error( "Could not write the plan file $plan_path" );
	}
	WP_CLI::success( sprintf( 'Dry run: %d to convert, %d skipped, %d mismatched. Plan: %s', $stats['convert'], $stats['skip'], $stats['mismatch'], $plan_path ) );
	return;
}

$plan = json_decode( (string) file_get_contents( $plan_path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
if ( ! is_array( $plan ) ) {
	WP_CLI::error( "Cannot read plan $plan_path" );
}

// ------------------------------------------------------------------ apply --
if ( 'apply' === $mode ) {
	$backup_dir = rtrim( $opts['backup'] ?? '', '/' );

	if ( '' === $backup_dir || ! wp_mkdir_p( $backup_dir ) ) {
		WP_CLI::error( 'Pass backup=/private/dir (outside the docroot)' );
	}

	// WP-CLI runs without a user, which would run post content through kses
	// and could strip markup elsewhere on the page. Save it untouched.
	kses_remove_filters();

	$done = 0;
	foreach ( $villas as $villa ) {
		if ( ! isset( $plan[ $villa->ID ] ) ) {
			continue;
		}

		$label = sprintf( '%d %s', $villa->ID, html_entity_decode( $villa->post_title ) );
		$fresh = get_post( $villa->ID );

		if ( md5( $fresh->post_content ) !== $plan[ $villa->ID ]['hash'] ) {
			WP_CLI::warning( "STALE $label: edited since the dry run, left untouched. Re-run the dry run." );
			continue;
		}

		$result = $build( $fresh );
		if ( is_wp_error( $result ) || ! $result['matches'] ) {
			WP_CLI::warning( "FAILED CHECK $label: not converted" );
			continue;
		}

		$choices = $keyed_choices( $fresh->ID );
		$backup  = array(
			'id'           => $fresh->ID,
			'post_content' => $fresh->post_content,
			'meta'         => array(
				'villa_bedroom_selector_choices' => get_post_meta( $fresh->ID, 'villa_bedroom_selector_choices', true ),
				'villa_pricing_row_bedroom_keys' => get_post_meta( $fresh->ID, 'villa_pricing_row_bedroom_keys', true ),
			),
		);
		$backup_file = sprintf( '%s/villa-%d-%s.json', $backup_dir, $fresh->ID, gmdate( 'Ymd-His' ) );
		if ( false === file_put_contents( $backup_file, wp_json_encode( $backup ) ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
			WP_CLI::error( "Could not write backup $backup_file; stopping." );
		}

		// Replace only the rates table; the rest of the content is untouched.
		$position    = strpos( $fresh->post_content, $result['old_markup'] );
		$new_content = substr_replace( $fresh->post_content, $result['new_markup'], $position, strlen( $result['old_markup'] ) );
		$updated     = wp_update_post(
			wp_slash(
				array(
					'ID'           => $fresh->ID,
					'post_content' => $new_content,
				)
			),
			true
		);

		if ( is_wp_error( $updated ) ) {
			WP_CLI::warning( "SAVE FAILED $label: " . $updated->get_error_message() );
			continue;
		}

		if ( null !== $choices ) {
			update_post_meta( $fresh->ID, 'villa_bedroom_selector_choices', $choices );
		}

		WP_CLI::log( "DONE  $label (backup $backup_file)" . ( null !== $choices ? ', option keys stored' : '' ) );
		++$done;
	}

	WP_CLI::success( "Converted $done villa(s). Now run mode=verify in a new process." );
	return;
}

// ----------------------------------------------------------------- verify --
if ( 'verify' === $mode ) {
	$ok  = 0;
	$bad = 0;

	foreach ( $plan as $villa_id => $entry ) {
		$villa  = get_post( (int) $villa_id );
		$blocks = array();
		$walk   = static function ( $list ) use ( &$walk, &$blocks ) {
			foreach ( $list as $b ) {
				if ( 'gutenberg-lab-blocks/villa-rates' === $b['blockName'] ) {
					$blocks[] = $b;
				}
				$walk( $b['innerBlocks'] ?? array() );
			}
		};
		$walk( parse_blocks( $villa->post_content ) );

		$old_left = is_wp_error( $find_old_table( $villa->post_content ) ) ? 0 : 1;
		$sig      = 1 === count( $blocks ) ? $signature( $render_in_villa( $blocks[0], $villa->ID ) ) : null;
		$label    = sprintf( '%d %s', $villa_id, html_entity_decode( $entry['title'] ) );

		if ( 1 === count( $blocks ) && ! $old_left && $sig === $entry['signature'] ) {
			WP_CLI::log( "OK    $label" );
			++$ok;
		} else {
			WP_CLI::warning( "BAD   $label: new blocks=" . count( $blocks ) . ", old tables left=$old_left, rows match=" . ( $sig === $entry['signature'] ? 'yes' : 'no' ) );
			++$bad;
		}
	}

	if ( $bad ) {
		WP_CLI::error( "Verify: $ok ok, $bad failed." );
	}

	WP_CLI::success( "Verify: all $ok converted villas render the same rows as before." );
	return;
}

WP_CLI::error( "Unknown mode $mode" );
