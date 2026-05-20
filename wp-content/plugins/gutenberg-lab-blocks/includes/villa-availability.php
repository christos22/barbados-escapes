<?php
/**
 * Villa availability storage, iCal sync, and calendar rendering.
 *
 * @package GutenbergLabBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const GUTENBERG_LAB_BLOCKS_VILLA_AVAILABILITY_DB_VERSION = '20260518';
const GUTENBERG_LAB_BLOCKS_VILLA_ICAL_META              = '_gutenberg_lab_villa_ical_feeds';
const GUTENBERG_LAB_BLOCKS_VILLA_MANUAL_BLOCKS_META     = '_gutenberg_lab_villa_manual_blocks';
const GUTENBERG_LAB_BLOCKS_VILLA_SYNC_STATUS_META       = '_gutenberg_lab_villa_availability_sync_status';
const GUTENBERG_LAB_BLOCKS_VILLA_SYNC_HOOK              = 'gutenberg_lab_blocks_sync_villa_availability';

/**
 * Returns the normalized table name for day-level villa availability.
 *
 * The table is intentionally day-based. That keeps the first version simple
 * and gives the future villa archive search a fast "is this date range clear?"
 * lookup without reparsing iCal feeds on every request.
 *
 * @return string
 */
function gutenberg_lab_blocks_get_villa_availability_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'gblb_villa_availability';
}

/**
 * Creates or updates the villa availability table.
 */
function gutenberg_lab_blocks_install_villa_availability() {
	global $wpdb;

	$table_name      = gutenberg_lab_blocks_get_villa_availability_table_name();
	$charset_collate = $wpdb->get_charset_collate();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	dbDelta(
		"CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			villa_id bigint(20) unsigned NOT NULL,
			source_type varchar(20) NOT NULL,
			source_key varchar(64) NOT NULL,
			date date NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'unavailable',
			price decimal(10,2) NULL,
			currency char(3) NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY villa_source_date (villa_id, source_type, source_key, date),
			KEY villa_date (villa_id, date),
			KEY date_status (date, status)
		) {$charset_collate};"
	);

	update_option( 'gutenberg_lab_blocks_villa_availability_db_version', GUTENBERG_LAB_BLOCKS_VILLA_AVAILABILITY_DB_VERSION );
}

/**
 * Keeps local installs current even when the plugin was already active.
 */
function gutenberg_lab_blocks_maybe_install_villa_availability() {
	if ( GUTENBERG_LAB_BLOCKS_VILLA_AVAILABILITY_DB_VERSION !== get_option( 'gutenberg_lab_blocks_villa_availability_db_version' ) ) {
		gutenberg_lab_blocks_install_villa_availability();
	}
}
add_action( 'admin_init', 'gutenberg_lab_blocks_maybe_install_villa_availability' );

/**
 * Adds the six-hour interval requested for the first iCal sync pass.
 *
 * @param array<string, array<string, mixed>> $schedules Existing schedules.
 * @return array<string, array<string, mixed>>
 */
function gutenberg_lab_blocks_add_villa_availability_cron_interval( $schedules ) {
	$schedules['gblb_six_hours'] = array(
		'interval' => 6 * HOUR_IN_SECONDS,
		'display'  => __( 'Every six hours', 'gutenberg-lab-blocks' ),
	);

	return $schedules;
}
add_filter( 'cron_schedules', 'gutenberg_lab_blocks_add_villa_availability_cron_interval' );

/**
 * Schedules the recurring villa availability sync.
 */
function gutenberg_lab_blocks_schedule_villa_availability_sync() {
	if ( ! wp_next_scheduled( GUTENBERG_LAB_BLOCKS_VILLA_SYNC_HOOK ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'gblb_six_hours', GUTENBERG_LAB_BLOCKS_VILLA_SYNC_HOOK );
	}
}
add_action( 'init', 'gutenberg_lab_blocks_schedule_villa_availability_sync' );

/**
 * Removes the recurring villa availability sync.
 */
function gutenberg_lab_blocks_unschedule_villa_availability_sync() {
	$timestamp = wp_next_scheduled( GUTENBERG_LAB_BLOCKS_VILLA_SYNC_HOOK );

	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, GUTENBERG_LAB_BLOCKS_VILLA_SYNC_HOOK );
	}
}

/**
 * Parses a strict YYYY-MM-DD value.
 *
 * @param string $value Raw date value.
 * @return string
 */
function gutenberg_lab_blocks_normalize_iso_date( $value ) {
	$value = is_string( $value ) ? trim( $value ) : '';

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
		return '';
	}

	$date   = DateTimeImmutable::createFromFormat( '!Y-m-d', $value, wp_timezone() );
	$errors = DateTimeImmutable::getLastErrors();

	if (
		false === $date ||
		( is_array( $errors ) && ( $errors['warning_count'] > 0 || $errors['error_count'] > 0 ) )
	) {
		return '';
	}

	return $date->format( 'Y-m-d' );
}

/**
 * Normalizes an iCal URL before storing or fetching it.
 *
 * Many calendar exports use webcal:// links in the UI. WordPress' HTTP API
 * expects http(s), so we convert webcal to https and reject non-web protocols.
 *
 * @param string $url Raw iCal URL.
 * @return string
 */
function gutenberg_lab_blocks_normalize_ical_url( $url ) {
	$url = is_string( $url ) ? trim( $url ) : '';

	if ( '' === $url ) {
		return '';
	}

	if ( 0 === stripos( $url, 'webcal://' ) ) {
		$url = 'https://' . substr( $url, 9 );
	}

	return esc_url_raw( $url, array( 'http', 'https' ) );
}

/**
 * Returns every unavailable night between start and end.
 *
 * End is exclusive, mirroring iCal checkout semantics.
 *
 * @param string $start_date Start date.
 * @param string $end_date   End date.
 * @return array<int, string>
 */
function gutenberg_lab_blocks_get_date_span( $start_date, $end_date ) {
	$start_date = gutenberg_lab_blocks_normalize_iso_date( $start_date );
	$end_date   = gutenberg_lab_blocks_normalize_iso_date( $end_date );

	if ( '' === $start_date || '' === $end_date || $end_date <= $start_date ) {
		return array();
	}

	$dates  = array();
	$cursor = new DateTimeImmutable( $start_date, wp_timezone() );
	$end    = new DateTimeImmutable( $end_date, wp_timezone() );
	$limit  = 0;

	while ( $cursor < $end && $limit < 1095 ) {
		$dates[] = $cursor->format( 'Y-m-d' );
		$cursor  = $cursor->modify( '+1 day' );
		++$limit;
	}

	return $dates;
}

/**
 * Returns the iCal feed rows saved for one villa.
 *
 * @param int $villa_id Villa post ID.
 * @return array<int, array{label: string, url: string, key: string}>
 */
function gutenberg_lab_blocks_get_villa_ical_feeds( $villa_id ) {
	$feeds = get_post_meta( $villa_id, GUTENBERG_LAB_BLOCKS_VILLA_ICAL_META, true );

	if ( ! is_array( $feeds ) ) {
		return array();
	}

	$clean = array();

	foreach ( $feeds as $feed ) {
		if ( ! is_array( $feed ) || empty( $feed['url'] ) ) {
			continue;
		}

		$url = gutenberg_lab_blocks_normalize_ical_url( $feed['url'] );

		if ( '' === $url ) {
			continue;
		}

		$clean[] = array(
			'label' => isset( $feed['label'] ) ? sanitize_text_field( $feed['label'] ) : '',
			'url'   => $url,
			'key'   => sha1( $url ),
		);
	}

	return $clean;
}

/**
 * Returns manually blocked ranges for one villa.
 *
 * @param int $villa_id Villa post ID.
 * @return array<int, array{label: string, start: string, end: string}>
 */
function gutenberg_lab_blocks_get_villa_manual_blocks( $villa_id ) {
	$blocks = get_post_meta( $villa_id, GUTENBERG_LAB_BLOCKS_VILLA_MANUAL_BLOCKS_META, true );

	if ( ! is_array( $blocks ) ) {
		return array();
	}

	$clean = array();

	foreach ( $blocks as $block ) {
		if ( ! is_array( $block ) ) {
			continue;
		}

		$start = isset( $block['start'] ) ? gutenberg_lab_blocks_normalize_iso_date( $block['start'] ) : '';
		$end   = isset( $block['end'] ) ? gutenberg_lab_blocks_normalize_iso_date( $block['end'] ) : '';

		if ( '' === $start || '' === $end || $end <= $start ) {
			continue;
		}

		$clean[] = array(
			'label' => isset( $block['label'] ) ? sanitize_text_field( $block['label'] ) : '',
			'start' => $start,
			'end'   => $end,
		);
	}

	return $clean;
}

/**
 * Replaces all dates for one villa/source pair.
 *
 * @param int           $villa_id    Villa post ID.
 * @param string        $source_type Source type.
 * @param string        $source_key  Source key.
 * @param array<int, string> $dates  Date strings.
 */
function gutenberg_lab_blocks_replace_villa_availability_dates( $villa_id, $source_type, $source_key, $dates ) {
	global $wpdb;

	gutenberg_lab_blocks_maybe_install_villa_availability();

	$table_name  = gutenberg_lab_blocks_get_villa_availability_table_name();
	$villa_id    = absint( $villa_id );
	$source_type = sanitize_key( $source_type );
	$source_key  = substr( sanitize_key( $source_key ), 0, 64 );
	$now         = current_time( 'mysql' );

	if ( ! $villa_id || '' === $source_type || '' === $source_key ) {
		return;
	}

	$wpdb->delete(
		$table_name,
		array(
			'villa_id'     => $villa_id,
			'source_type' => $source_type,
			'source_key'  => $source_key,
		),
		array( '%d', '%s', '%s' )
	);

	$dates = array_values( array_unique( array_filter( array_map( 'gutenberg_lab_blocks_normalize_iso_date', (array) $dates ) ) ) );

	foreach ( $dates as $date ) {
		$wpdb->insert(
			$table_name,
			array(
				'villa_id'     => $villa_id,
				'source_type' => $source_type,
				'source_key'  => $source_key,
				'date'        => $date,
				'status'      => 'unavailable',
				'created_at'  => $now,
				'updated_at'  => $now,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}
}

/**
 * Removes old iCal rows when a feed URL is removed or changed.
 *
 * @param int             $villa_id     Villa post ID.
 * @param array<int,string> $source_keys Current iCal source keys.
 */
function gutenberg_lab_blocks_prune_villa_ical_sources( $villa_id, $source_keys ) {
	global $wpdb;

	$table_name  = gutenberg_lab_blocks_get_villa_availability_table_name();
	$villa_id    = absint( $villa_id );
	$source_keys = array_values( array_filter( array_map( 'sanitize_key', (array) $source_keys ) ) );

	if ( ! $villa_id ) {
		return;
	}

	if ( empty( $source_keys ) ) {
		$wpdb->delete(
			$table_name,
			array(
				'villa_id'     => $villa_id,
				'source_type' => 'ical',
			),
			array( '%d', '%s' )
		);
		return;
	}

	$placeholders = implode( ', ', array_fill( 0, count( $source_keys ), '%s' ) );
	$params       = array_merge( array( $villa_id, 'ical' ), $source_keys );

	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$table_name} WHERE villa_id = %d AND source_type = %s AND source_key NOT IN ({$placeholders})",
			$params
		)
	);
}

/**
 * Rebuilds the manual blocked-date source for one villa.
 *
 * @param int $villa_id Villa post ID.
 */
function gutenberg_lab_blocks_refresh_villa_manual_availability( $villa_id ) {
	$dates = array();

	foreach ( gutenberg_lab_blocks_get_villa_manual_blocks( $villa_id ) as $block ) {
		$dates = array_merge( $dates, gutenberg_lab_blocks_get_date_span( $block['start'], $block['end'] ) );
	}

	gutenberg_lab_blocks_replace_villa_availability_dates( $villa_id, 'manual', 'manual', $dates );
}

/**
 * Parses iCal content into unavailable nights.
 *
 * @param string $ical_body Raw iCal body.
 * @return array<int, string>|WP_Error
 */
function gutenberg_lab_blocks_parse_ical_unavailable_dates( $ical_body ) {
	if ( ! class_exists( '\Sabre\VObject\Reader' ) ) {
		return new WP_Error(
			'ical_parser_missing',
			__( 'The iCal parser is not available. Run Composer install for this plugin.', 'gutenberg-lab-blocks' )
		);
	}

	try {
		$calendar = \Sabre\VObject\Reader::read( $ical_body );
		$start    = new DateTimeImmutable( 'first day of this month 00:00:00', wp_timezone() );
		$end      = $start->modify( '+24 months' );
		$calendar = method_exists( $calendar, 'expand' ) ? $calendar->expand( $start, $end, wp_timezone() ) : $calendar;
		$dates    = array();

		if ( ! isset( $calendar->VEVENT ) ) {
			return array();
		}

		foreach ( $calendar->VEVENT as $event ) {
			if ( isset( $event->STATUS ) && 'CANCELLED' === strtoupper( (string) $event->STATUS ) ) {
				continue;
			}

			if ( ! isset( $event->DTSTART ) ) {
				continue;
			}

			$event_start = $event->DTSTART->getDateTime( wp_timezone() );
			$event_end   = isset( $event->DTEND ) ? $event->DTEND->getDateTime( wp_timezone() ) : null;

			if ( ! $event_start instanceof DateTimeInterface ) {
				continue;
			}

			$event_start = $event_start->setTimezone( wp_timezone() );

			if ( ! $event_end instanceof DateTimeInterface ) {
				$event_end = $event_start->modify( '+1 day' );
			}

			$event_end = $event_end->setTimezone( wp_timezone() );

			$start_date = $event_start->format( 'Y-m-d' );
			$end_date   = $event_end->format( 'Y-m-d' );

			if ( $end_date <= $start_date ) {
				$end_date = ( new DateTimeImmutable( $start_date, wp_timezone() ) )->modify( '+1 day' )->format( 'Y-m-d' );
			}

			$dates = array_merge( $dates, gutenberg_lab_blocks_get_date_span( $start_date, $end_date ) );
		}

		return array_values( array_unique( $dates ) );
	} catch ( Exception $exception ) {
		return new WP_Error( 'ical_parse_error', $exception->getMessage() );
	}
}

/**
 * Syncs all villa iCal feeds.
 */
function gutenberg_lab_blocks_sync_all_villa_availability() {
	$query = new WP_Query(
		array(
			'post_type'      => 'villa',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => GUTENBERG_LAB_BLOCKS_VILLA_ICAL_META,
					'compare' => 'EXISTS',
				),
			),
		)
	);

	foreach ( $query->posts as $villa_id ) {
		gutenberg_lab_blocks_sync_villa_availability( $villa_id );
	}
}
add_action( GUTENBERG_LAB_BLOCKS_VILLA_SYNC_HOOK, 'gutenberg_lab_blocks_sync_all_villa_availability' );

/**
 * Syncs iCal feeds for one villa and preserves previous feed dates on failure.
 *
 * @param int $villa_id Villa post ID.
 * @return array<string, mixed>
 */
function gutenberg_lab_blocks_sync_villa_availability( $villa_id ) {
	$villa_id = absint( $villa_id );
	$feeds    = gutenberg_lab_blocks_get_villa_ical_feeds( $villa_id );
	$statuses = array();
	$keys     = wp_list_pluck( $feeds, 'key' );

	gutenberg_lab_blocks_maybe_install_villa_availability();
	gutenberg_lab_blocks_refresh_villa_manual_availability( $villa_id );
	gutenberg_lab_blocks_prune_villa_ical_sources( $villa_id, $keys );

	foreach ( $feeds as $feed ) {
		$response = wp_remote_get(
			$feed['url'],
			array(
				'timeout'             => 15,
				'redirection'         => 3,
				'limit_response_size' => 2 * MB_IN_BYTES,
				'user-agent'          => 'Barbados Escapes Villa Availability; ' . home_url( '/' ),
			)
		);

		$status = array(
			'label'             => $feed['label'],
			'url'               => $feed['url'],
			'key'               => $feed['key'],
			'last_synced'       => current_time( 'timestamp' ),
			'unavailable_count' => 0,
			'error'             => '',
		);

		if ( is_wp_error( $response ) ) {
			$status['error']       = $response->get_error_message();
			$statuses[ $feed['key'] ] = $status;
			continue;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code < 200 || $response_code >= 300 ) {
			$status['error']       = sprintf(
				/* translators: %d: HTTP status code. */
				__( 'Feed returned HTTP %d.', 'gutenberg-lab-blocks' ),
				$response_code
			);
			$statuses[ $feed['key'] ] = $status;
			continue;
		}

		$dates = gutenberg_lab_blocks_parse_ical_unavailable_dates( wp_remote_retrieve_body( $response ) );

		if ( is_wp_error( $dates ) ) {
			$status['error']       = $dates->get_error_message();
			$statuses[ $feed['key'] ] = $status;
			continue;
		}

		gutenberg_lab_blocks_replace_villa_availability_dates( $villa_id, 'ical', $feed['key'], $dates );

		$status['unavailable_count'] = count( $dates );
		$statuses[ $feed['key'] ]    = $status;
	}

	$summary = array(
		'last_synced'       => current_time( 'timestamp' ),
		'feeds'             => $statuses,
		'unavailable_count' => count( gutenberg_lab_blocks_get_villa_unavailable_dates( $villa_id ) ),
	);

	update_post_meta( $villa_id, GUTENBERG_LAB_BLOCKS_VILLA_SYNC_STATUS_META, $summary );

	return $summary;
}

/**
 * Returns unavailable date strings for a villa.
 *
 * @param int    $villa_id   Villa post ID.
 * @param string $start_date Optional inclusive start date.
 * @param string $end_date   Optional exclusive end date.
 * @return array<int, string>
 */
function gutenberg_lab_blocks_get_villa_unavailable_dates( $villa_id, $start_date = '', $end_date = '' ) {
	global $wpdb;

	// Frontend renders may run before an admin_init upgrade path after deploy.
	gutenberg_lab_blocks_maybe_install_villa_availability();

	$table_name = gutenberg_lab_blocks_get_villa_availability_table_name();
	$villa_id   = absint( $villa_id );

	if ( ! $villa_id ) {
		return array();
	}

	$where  = 'WHERE villa_id = %d AND status = %s';
	$params = array( $villa_id, 'unavailable' );

	$start_date = gutenberg_lab_blocks_normalize_iso_date( $start_date );
	$end_date   = gutenberg_lab_blocks_normalize_iso_date( $end_date );

	if ( '' !== $start_date ) {
		$where   .= ' AND date >= %s';
		$params[] = $start_date;
	}

	if ( '' !== $end_date ) {
		$where   .= ' AND date < %s';
		$params[] = $end_date;
	}

	return array_map(
		'strval',
		$wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT date FROM {$table_name} {$where} ORDER BY date ASC",
				$params
			)
		)
	);
}

/**
 * Checks if a guest stay avoids every unavailable calendar date in a range.
 *
 * @param int    $villa_id       Villa post ID.
 * @param string $arrival_date   Arrival date.
 * @param string $departure_date Departure date.
 * @return bool
 */
function gutenberg_lab_blocks_is_villa_date_range_available( $villa_id, $arrival_date, $departure_date ) {
	global $wpdb;

	// CF7 validation can run on frontend requests, so do not rely on admin_init.
	gutenberg_lab_blocks_maybe_install_villa_availability();

	$villa_id        = absint( $villa_id );
	$arrival_date    = gutenberg_lab_blocks_normalize_iso_date( $arrival_date );
	$departure_date  = gutenberg_lab_blocks_normalize_iso_date( $departure_date );
	$table_name      = gutenberg_lab_blocks_get_villa_availability_table_name();

	if ( ! $villa_id || '' === $arrival_date || '' === $departure_date || $departure_date <= $arrival_date ) {
		return false;
	}

	$blocked_count = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_name} WHERE villa_id = %d AND status = %s AND date >= %s AND date <= %s",
			$villa_id,
			'unavailable',
			$arrival_date,
			$departure_date
		)
	);

	return 0 === $blocked_count;
}

/**
 * Adds the availability meta box to villas.
 */
function gutenberg_lab_blocks_add_villa_availability_meta_box() {
	add_meta_box(
		'gutenberg-lab-villa-availability',
		__( 'Availability', 'gutenberg-lab-blocks' ),
		'gutenberg_lab_blocks_render_villa_availability_meta_box',
		'villa',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes_villa', 'gutenberg_lab_blocks_add_villa_availability_meta_box' );

/**
 * Renders the Availability meta box.
 *
 * @param WP_Post $post Current villa post.
 */
function gutenberg_lab_blocks_render_villa_availability_meta_box( $post ) {
	$feeds         = gutenberg_lab_blocks_get_villa_ical_feeds( $post->ID );
	$manual_blocks = gutenberg_lab_blocks_get_villa_manual_blocks( $post->ID );
	$status        = get_post_meta( $post->ID, GUTENBERG_LAB_BLOCKS_VILLA_SYNC_STATUS_META, true );

	if ( empty( $feeds ) ) {
		$feeds = array( array( 'label' => '', 'url' => '', 'key' => '' ) );
	}

	if ( empty( $manual_blocks ) ) {
		$manual_blocks = array( array( 'label' => '', 'start' => '', 'end' => '' ) );
	}

	wp_nonce_field( 'gutenberg_lab_blocks_save_villa_availability', 'gutenberg_lab_blocks_villa_availability_nonce' );
	?>
	<div class="gblb-villa-availability-admin">
		<p>
			<?php esc_html_e( 'Add any Airbnb, Vrbo, or bespoke iCal links for this villa. Manual blocks are useful for owner stays or private holds.', 'gutenberg-lab-blocks' ); ?>
		</p>

		<h3><?php esc_html_e( 'iCal feeds', 'gutenberg-lab-blocks' ); ?></h3>
		<table class="widefat striped" data-gblb-availability-table="feeds">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Label', 'gutenberg-lab-blocks' ); ?></th>
					<th><?php esc_html_e( 'Feed URL', 'gutenberg-lab-blocks' ); ?></th>
					<th><?php esc_html_e( 'Action', 'gutenberg-lab-blocks' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $feeds as $index => $feed ) : ?>
					<?php gutenberg_lab_blocks_render_villa_ical_feed_row( $index, $feed ); ?>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p>
			<button type="button" class="button" data-gblb-add-availability-row="feeds">
				<?php esc_html_e( 'Add iCal feed', 'gutenberg-lab-blocks' ); ?>
			</button>
		</p>

		<h3><?php esc_html_e( 'Manual blocked dates', 'gutenberg-lab-blocks' ); ?></h3>
		<p class="description">
			<?php esc_html_e( 'The end date is exclusive, just like a checkout date. To block 1-7 June, set start to 1 June and end to 8 June.', 'gutenberg-lab-blocks' ); ?>
		</p>
		<table class="widefat striped" data-gblb-availability-table="manual">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Label', 'gutenberg-lab-blocks' ); ?></th>
					<th><?php esc_html_e( 'Start date', 'gutenberg-lab-blocks' ); ?></th>
					<th><?php esc_html_e( 'End / checkout date', 'gutenberg-lab-blocks' ); ?></th>
					<th><?php esc_html_e( 'Action', 'gutenberg-lab-blocks' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $manual_blocks as $index => $block ) : ?>
					<?php gutenberg_lab_blocks_render_villa_manual_block_row( $index, $block ); ?>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p>
			<button type="button" class="button" data-gblb-add-availability-row="manual">
				<?php esc_html_e( 'Add manual block', 'gutenberg-lab-blocks' ); ?>
			</button>
		</p>

		<div class="gblb-villa-availability-admin__status">
			<h3><?php esc_html_e( 'Sync status', 'gutenberg-lab-blocks' ); ?></h3>
			<?php gutenberg_lab_blocks_render_villa_sync_status( $status ); ?>
			<p>
				<a
					class="button button-primary"
					href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=gutenberg_lab_blocks_sync_villa_availability&villa_id=' . absint( $post->ID ) ), 'gutenberg_lab_blocks_sync_villa_availability_' . absint( $post->ID ) ) ); ?>"
				>
					<?php esc_html_e( 'Sync now', 'gutenberg-lab-blocks' ); ?>
				</a>
			</p>
		</div>

		<script type="text/html" data-gblb-template="feeds">
			<?php gutenberg_lab_blocks_render_villa_ical_feed_row( '__index__', array( 'label' => '', 'url' => '' ) ); ?>
		</script>
		<script type="text/html" data-gblb-template="manual">
			<?php gutenberg_lab_blocks_render_villa_manual_block_row( '__index__', array( 'label' => '', 'start' => '', 'end' => '' ) ); ?>
		</script>
	</div>
	<script>
		document.addEventListener( 'click', function( event ) {
			var addButton = event.target.closest( '[data-gblb-add-availability-row]' );
			var removeButton = event.target.closest( '[data-gblb-remove-availability-row]' );

			if ( addButton ) {
				var tableKey = addButton.getAttribute( 'data-gblb-add-availability-row' );
				var table = document.querySelector( '[data-gblb-availability-table="' + tableKey + '"] tbody' );
				var template = document.querySelector( '[data-gblb-template="' + tableKey + '"]' );

				if ( table && template ) {
					table.insertAdjacentHTML( 'beforeend', template.innerHTML.replaceAll( '__index__', String( Date.now() ) ) );
				}
			}

			if ( removeButton ) {
				var row = removeButton.closest( 'tr' );

				if ( row ) {
					row.remove();
				}
			}
		} );
	</script>
	<?php
}

/**
 * Renders one iCal feed admin row.
 *
 * @param int|string            $index Row index.
 * @param array<string, string> $feed  Feed data.
 */
function gutenberg_lab_blocks_render_villa_ical_feed_row( $index, $feed ) {
	?>
	<tr>
		<td>
			<input
				type="text"
				class="widefat"
				name="gutenberg_lab_villa_ical_feeds[<?php echo esc_attr( $index ); ?>][label]"
				value="<?php echo esc_attr( $feed['label'] ?? '' ); ?>"
				placeholder="<?php echo esc_attr__( 'Airbnb, Vrbo, Owner calendar', 'gutenberg-lab-blocks' ); ?>"
			/>
		</td>
		<td>
			<input
				type="url"
				class="widefat"
				name="gutenberg_lab_villa_ical_feeds[<?php echo esc_attr( $index ); ?>][url]"
				value="<?php echo esc_attr( $feed['url'] ?? '' ); ?>"
				placeholder="https://example.com/calendar.ics"
			/>
		</td>
		<td>
			<button type="button" class="button" data-gblb-remove-availability-row>
				<?php esc_html_e( 'Remove', 'gutenberg-lab-blocks' ); ?>
			</button>
		</td>
	</tr>
	<?php
}

/**
 * Renders one manual blocked-date admin row.
 *
 * @param int|string            $index Row index.
 * @param array<string, string> $block Manual block data.
 */
function gutenberg_lab_blocks_render_villa_manual_block_row( $index, $block ) {
	?>
	<tr>
		<td>
			<input
				type="text"
				class="widefat"
				name="gutenberg_lab_villa_manual_blocks[<?php echo esc_attr( $index ); ?>][label]"
				value="<?php echo esc_attr( $block['label'] ?? '' ); ?>"
				placeholder="<?php echo esc_attr__( 'Owner stay', 'gutenberg-lab-blocks' ); ?>"
			/>
		</td>
		<td>
			<input
				type="date"
				class="widefat"
				name="gutenberg_lab_villa_manual_blocks[<?php echo esc_attr( $index ); ?>][start]"
				value="<?php echo esc_attr( $block['start'] ?? '' ); ?>"
			/>
		</td>
		<td>
			<input
				type="date"
				class="widefat"
				name="gutenberg_lab_villa_manual_blocks[<?php echo esc_attr( $index ); ?>][end]"
				value="<?php echo esc_attr( $block['end'] ?? '' ); ?>"
			/>
		</td>
		<td>
			<button type="button" class="button" data-gblb-remove-availability-row>
				<?php esc_html_e( 'Remove', 'gutenberg-lab-blocks' ); ?>
			</button>
		</td>
	</tr>
	<?php
}

/**
 * Renders sync status details in the villa edit screen.
 *
 * @param mixed $status Sync status meta.
 */
function gutenberg_lab_blocks_render_villa_sync_status( $status ) {
	if ( ! is_array( $status ) || empty( $status['last_synced'] ) ) {
		echo '<p>' . esc_html__( 'This villa has not been synced yet.', 'gutenberg-lab-blocks' ) . '</p>';
		return;
	}

	printf(
		'<p>%1$s <strong>%2$s</strong>. %3$s <strong>%4$d</strong>.</p>',
		esc_html__( 'Last sync:', 'gutenberg-lab-blocks' ),
		esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), absint( $status['last_synced'] ) ) ),
		esc_html__( 'Unavailable dates stored:', 'gutenberg-lab-blocks' ),
		isset( $status['unavailable_count'] ) ? absint( $status['unavailable_count'] ) : 0
	);

	if ( empty( $status['feeds'] ) || ! is_array( $status['feeds'] ) ) {
		return;
	}

	echo '<ul>';
	foreach ( $status['feeds'] as $feed_status ) {
		if ( ! is_array( $feed_status ) ) {
			continue;
		}

		$label = ! empty( $feed_status['label'] ) ? $feed_status['label'] : $feed_status['url'];
		$error = ! empty( $feed_status['error'] ) ? $feed_status['error'] : '';

		echo '<li>';
		echo esc_html( $label );

		if ( '' !== $error ) {
			echo ' - <strong>' . esc_html__( 'Error:', 'gutenberg-lab-blocks' ) . '</strong> ' . esc_html( $error );
		} else {
			printf(
				' - %s %d',
				esc_html__( 'dates:', 'gutenberg-lab-blocks' ),
				isset( $feed_status['unavailable_count'] ) ? absint( $feed_status['unavailable_count'] ) : 0
			);
		}

		echo '</li>';
	}
	echo '</ul>';
}

/**
 * Saves villa availability admin fields.
 *
 * @param int $post_id Current villa ID.
 */
function gutenberg_lab_blocks_save_villa_availability_meta( $post_id ) {
	if ( ! isset( $_POST['gutenberg_lab_blocks_villa_availability_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['gutenberg_lab_blocks_villa_availability_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'gutenberg_lab_blocks_save_villa_availability' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$feeds      = array();
	$seen_urls  = array();
	$feed_input = isset( $_POST['gutenberg_lab_villa_ical_feeds'] ) && is_array( $_POST['gutenberg_lab_villa_ical_feeds'] )
		? wp_unslash( $_POST['gutenberg_lab_villa_ical_feeds'] )
		: array();

	foreach ( $feed_input as $feed ) {
		if ( ! is_array( $feed ) ) {
			continue;
		}

		$url = isset( $feed['url'] ) ? gutenberg_lab_blocks_normalize_ical_url( $feed['url'] ) : '';

		if ( '' === $url || isset( $seen_urls[ $url ] ) ) {
			continue;
		}

		$seen_urls[ $url ] = true;
		$feeds[]          = array(
			'label' => isset( $feed['label'] ) ? sanitize_text_field( $feed['label'] ) : '',
			'url'   => $url,
		);
	}

	if ( empty( $feeds ) ) {
		delete_post_meta( $post_id, GUTENBERG_LAB_BLOCKS_VILLA_ICAL_META );
	} else {
		update_post_meta( $post_id, GUTENBERG_LAB_BLOCKS_VILLA_ICAL_META, $feeds );
	}

	$manual_blocks = array();
	$manual_input  = isset( $_POST['gutenberg_lab_villa_manual_blocks'] ) && is_array( $_POST['gutenberg_lab_villa_manual_blocks'] )
		? wp_unslash( $_POST['gutenberg_lab_villa_manual_blocks'] )
		: array();

	foreach ( $manual_input as $block ) {
		if ( ! is_array( $block ) ) {
			continue;
		}

		$start = isset( $block['start'] ) ? gutenberg_lab_blocks_normalize_iso_date( $block['start'] ) : '';
		$end   = isset( $block['end'] ) ? gutenberg_lab_blocks_normalize_iso_date( $block['end'] ) : '';

		if ( '' === $start || '' === $end || $end <= $start ) {
			continue;
		}

		$manual_blocks[] = array(
			'label' => isset( $block['label'] ) ? sanitize_text_field( $block['label'] ) : '',
			'start' => $start,
			'end'   => $end,
		);
	}

	if ( empty( $manual_blocks ) ) {
		delete_post_meta( $post_id, GUTENBERG_LAB_BLOCKS_VILLA_MANUAL_BLOCKS_META );
	} else {
		update_post_meta( $post_id, GUTENBERG_LAB_BLOCKS_VILLA_MANUAL_BLOCKS_META, $manual_blocks );
	}

	gutenberg_lab_blocks_refresh_villa_manual_availability( $post_id );
	gutenberg_lab_blocks_prune_villa_ical_sources( $post_id, array_map( 'sha1', wp_list_pluck( $feeds, 'url' ) ) );
}
add_action( 'save_post_villa', 'gutenberg_lab_blocks_save_villa_availability_meta' );

/**
 * Handles the admin "Sync now" action.
 */
function gutenberg_lab_blocks_handle_manual_villa_availability_sync() {
	$villa_id = isset( $_GET['villa_id'] ) ? absint( $_GET['villa_id'] ) : 0;

	if ( ! $villa_id || ! current_user_can( 'edit_post', $villa_id ) ) {
		wp_die( esc_html__( 'You are not allowed to sync this villa.', 'gutenberg-lab-blocks' ) );
	}

	check_admin_referer( 'gutenberg_lab_blocks_sync_villa_availability_' . $villa_id );

	$status = gutenberg_lab_blocks_sync_villa_availability( $villa_id );
	$errors = 0;

	foreach ( $status['feeds'] as $feed_status ) {
		if ( ! empty( $feed_status['error'] ) ) {
			++$errors;
		}
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'post'                         => $villa_id,
				'action'                       => 'edit',
				'gblb_availability_synced'     => 1,
				'gblb_availability_sync_error' => $errors,
			),
			admin_url( 'post.php' )
		)
	);
	exit;
}
add_action( 'admin_post_gutenberg_lab_blocks_sync_villa_availability', 'gutenberg_lab_blocks_handle_manual_villa_availability_sync' );

/**
 * Shows a short result notice after manual sync.
 */
function gutenberg_lab_blocks_render_villa_availability_admin_notice() {
	if ( empty( $_GET['gblb_availability_synced'] ) ) {
		return;
	}

	$error_count = isset( $_GET['gblb_availability_sync_error'] ) ? absint( $_GET['gblb_availability_sync_error'] ) : 0;
	$class       = $error_count > 0 ? 'notice notice-warning is-dismissible' : 'notice notice-success is-dismissible';
	$message     = $error_count > 0
		? sprintf(
			/* translators: %d: number of feed errors. */
			__( 'Availability sync finished, but %d feed had an error. Check the villa Availability box for details.', 'gutenberg-lab-blocks' ),
			$error_count
		)
		: __( 'Availability sync finished successfully.', 'gutenberg-lab-blocks' );

	printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
}
add_action( 'admin_notices', 'gutenberg_lab_blocks_render_villa_availability_admin_notice' );

/**
 * Normalizes a requested date to the first day of its month.
 *
 * @param string $date Date in Y-m-d format.
 * @return DateTimeImmutable|null
 */
function gutenberg_lab_blocks_normalize_villa_availability_month_start( $date ) {
	$timezone = wp_timezone();

	if ( '' === trim( (string) $date ) ) {
		return new DateTimeImmutable( 'first day of this month 00:00:00', $timezone );
	}

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $date ) ) {
		return null;
	}

	$parsed = DateTimeImmutable::createFromFormat( '!Y-m-d', (string) $date, $timezone );

	if ( ! $parsed || $parsed->format( 'Y-m-d' ) !== $date ) {
		return null;
	}

	return $parsed->modify( 'first day of this month 00:00:00' );
}

/**
 * Formats the label for the visible calendar window.
 *
 * @param DateTimeImmutable $month_start    First visible month.
 * @param int               $months_to_show Number of visible months.
 * @return string
 */
function gutenberg_lab_blocks_format_villa_availability_window_label( DateTimeImmutable $month_start, $months_to_show ) {
	$window_end = $month_start->modify( '+' . max( 0, $months_to_show - 1 ) . ' months' );

	return sprintf(
		/* translators: 1: first visible month. 2: last visible month. */
		__( '%1$s - %2$s', 'gutenberg-lab-blocks' ),
		$month_start->format( 'F Y' ),
		$window_end->format( 'F Y' )
	);
}

/**
 * Builds the HTML and data for one visible availability window.
 *
 * @param int               $villa_id       Villa post ID.
 * @param DateTimeImmutable $month_start    First visible month.
 * @param int               $months_to_show Number of visible months.
 * @return array<string, mixed>
 */
function gutenberg_lab_blocks_get_villa_availability_calendar_window( $villa_id, DateTimeImmutable $month_start, $months_to_show ) {
	$months_to_show = min( 18, max( 1, absint( $months_to_show ) ) );
	$range_end      = $month_start->modify( '+' . $months_to_show . ' months' );
	$unavailable    = gutenberg_lab_blocks_get_villa_unavailable_dates( $villa_id, $month_start->format( 'Y-m-d' ), $range_end->format( 'Y-m-d' ) );
	$lookup         = array_fill_keys( $unavailable, true );

	ob_start();

	for ( $month_index = 0; $month_index < $months_to_show; ++$month_index ) {
		echo gutenberg_lab_blocks_render_villa_availability_month( $month_start->modify( '+' . $month_index . ' months' ), $lookup ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	return array(
		'start'            => $month_start->format( 'Y-m-d' ),
		'end'              => $range_end->format( 'Y-m-d' ),
		'monthsToShow'     => $months_to_show,
		'rangeLabel'       => gutenberg_lab_blocks_format_villa_availability_window_label( $month_start, $months_to_show ),
		'html'             => ob_get_clean(),
		'unavailableDates' => $unavailable,
	);
}

/**
 * Registers the public endpoint used to page through availability windows.
 */
function gutenberg_lab_blocks_register_villa_availability_rest_routes() {
	register_rest_route(
		'gutenberg-lab-blocks/v1',
		'/villa-availability/(?P<villa_id>\d+)',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'gutenberg_lab_blocks_get_villa_availability_window_rest',
			'permission_callback' => '__return_true',
			'args'                => array(
				'villa_id' => array(
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
				'start'    => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'months'   => array(
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'gutenberg_lab_blocks_register_villa_availability_rest_routes' );

/**
 * Returns one availability window for frontend year paging.
 *
 * @param WP_REST_Request $request REST request.
 * @return WP_REST_Response|WP_Error
 */
function gutenberg_lab_blocks_get_villa_availability_window_rest( WP_REST_Request $request ) {
	$villa_id = absint( $request['villa_id'] );

	if ( ! $villa_id || 'villa' !== get_post_type( $villa_id ) || 'publish' !== get_post_status( $villa_id ) ) {
		return new WP_Error( 'gutenberg_lab_blocks_invalid_villa', __( 'Villa not found.', 'gutenberg-lab-blocks' ), array( 'status' => 404 ) );
	}

	$months_to_show = $request->get_param( 'months' ) ? absint( $request->get_param( 'months' ) ) : 12;
	$month_start    = gutenberg_lab_blocks_normalize_villa_availability_month_start( (string) $request->get_param( 'start' ) );

	if ( ! $month_start ) {
		return new WP_Error( 'gutenberg_lab_blocks_invalid_calendar_start', __( 'Invalid calendar start date.', 'gutenberg-lab-blocks' ), array( 'status' => 400 ) );
	}

	$minimum_start = new DateTimeImmutable( 'first day of this month 00:00:00', wp_timezone() );

	if ( $month_start < $minimum_start ) {
		$month_start = $minimum_start;
	}

	$response                 = gutenberg_lab_blocks_get_villa_availability_calendar_window( $villa_id, $month_start, $months_to_show );
	$response['minimumStart'] = $minimum_start->format( 'Y-m-d' );

	return rest_ensure_response( $response );
}

/**
 * Renders the front-end availability calendar block.
 *
 * @param array<string, mixed> $attributes         Block attributes.
 * @param string              $wrapper_attributes Block wrapper attributes.
 * @param WP_Block|null       $block              Rendered block instance with post context.
 * @return string
 */
function gutenberg_lab_blocks_render_villa_availability_calendar( $attributes, $wrapper_attributes = '', $block = null ) {
	$villa_id = isset( $attributes['villaId'] ) ? absint( $attributes['villaId'] ) : 0;

	if ( ! $villa_id && is_a( $block, 'WP_Block' ) && isset( $block->context['postId'] ) ) {
		$context_post_id = absint( $block->context['postId'] );

		if ( $context_post_id && 'villa' === get_post_type( $context_post_id ) ) {
			$villa_id = $context_post_id;
		}
	}

	if ( ! $villa_id && is_singular( 'villa' ) ) {
		$villa_id = get_queried_object_id() ?: get_the_ID();
	}

	if ( ! $villa_id && isset( $GLOBALS['post']->ID ) && 'villa' === get_post_type( $GLOBALS['post']->ID ) ) {
		$villa_id = absint( $GLOBALS['post']->ID );
	}

	if ( ! $villa_id || 'villa' !== get_post_type( $villa_id ) ) {
		return '';
	}

	$months_to_show = isset( $attributes['monthsToShow'] ) ? absint( $attributes['monthsToShow'] ) : 12;
	$months_to_show = min( 18, max( 1, $months_to_show ) );
	$heading        = isset( $attributes['heading'] ) && '' !== trim( (string) $attributes['heading'] )
		? sanitize_text_field( $attributes['heading'] )
		: __( 'Latest Availability', 'gutenberg-lab-blocks' );
	$form_selector  = isset( $attributes['formSelector'] ) && '' !== trim( (string) $attributes['formSelector'] )
		? sanitize_text_field( $attributes['formSelector'] )
		: '.vvm-villa-contact-form';
	$month_start    = gutenberg_lab_blocks_normalize_villa_availability_month_start( '' );
	$window         = gutenberg_lab_blocks_get_villa_availability_calendar_window( $villa_id, $month_start, $months_to_show );
	$payload        = array(
		'villaId'          => $villa_id,
		'villaTitle'       => get_the_title( $villa_id ),
		'villaUrl'         => get_permalink( $villa_id ),
		'formSelector'     => $form_selector,
		'endpoint'         => rest_url( 'gutenberg-lab-blocks/v1/villa-availability/' . $villa_id ),
		'windowStart'      => $window['start'],
		'windowEnd'        => $window['end'],
		'minimumStart'     => $window['start'],
		'monthsToShow'     => $months_to_show,
		'unavailableDates' => $window['unavailableDates'],
		'fields'           => array(
			'arrival'      => 'preferred-arrival',
			'departure'    => 'preferred-departure',
			'villaId'      => 'villa-id',
			'villaTitle'   => 'villa-title',
			'villaUrl'     => 'villa-url',
			'dateSummary'  => 'selected-dates-summary',
		),
		'prices'           => array(),
		'messages'         => array(
			'selectArrival' => __( 'Choose an available check-in date.', 'gutenberg-lab-blocks' ),
			'selectDeparture' => __( 'Choose a check-out date.', 'gutenberg-lab-blocks' ),
			'arrivalUnavailable' => __( 'That check-in date is unavailable. Please choose another date.', 'gutenberg-lab-blocks' ),
			'departureAfterArrival' => __( 'Check-out date must be after check-in date.', 'gutenberg-lab-blocks' ),
			'unavailable'  => __( 'Those dates include unavailable nights.', 'gutenberg-lab-blocks' ),
			'selected'     => __( 'Selected dates have been added to the enquiry form.', 'gutenberg-lab-blocks' ),
			'completeEnquiry' => __( 'Enquire', 'gutenberg-lab-blocks' ),
			'previousYear' => __( 'Previous year', 'gutenberg-lab-blocks' ),
			'nextYear'     => __( 'Next year', 'gutenberg-lab-blocks' ),
			'loadError'    => __( 'Availability could not be loaded. Please try again.', 'gutenberg-lab-blocks' ),
		),
	);

	ob_start();
	?>
	<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<div class="vvm-villa-availability-calendar__header">
			<h2 class="vvm-villa-availability-calendar__title"><?php echo esc_html( $heading ); ?></h2>
			<p class="vvm-villa-availability-calendar__status" data-vvm-calendar-status aria-live="polite">
				<?php esc_html_e( 'Choose your check-in date.', 'gutenberg-lab-blocks' ); ?>
			</p>
		</div>
		<nav class="vvm-villa-availability-calendar__navigation" aria-label="<?php esc_attr_e( 'Availability calendar navigation', 'gutenberg-lab-blocks' ); ?>">
			<button
				type="button"
				class="vvm-villa-availability-calendar__nav-button vvm-slider-button vvm-slider-button--prev"
				data-vvm-calendar-prev
				aria-label="<?php esc_attr_e( 'Previous year', 'gutenberg-lab-blocks' ); ?>"
				disabled
			>
				<?php echo gutenberg_lab_blocks_get_slider_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
			<button
				type="button"
				class="vvm-villa-availability-calendar__nav-button vvm-slider-button vvm-slider-button--next"
				data-vvm-calendar-next
				aria-label="<?php esc_attr_e( 'Next year', 'gutenberg-lab-blocks' ); ?>"
			>
				<?php echo gutenberg_lab_blocks_get_slider_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</nav>
		<div class="vvm-villa-availability-calendar__months" data-vvm-calendar>
			<?php echo $window['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<p class="vvm-villa-availability-calendar__legend">
			<span class="vvm-villa-availability-calendar__legend-swatch" aria-hidden="true"></span>
			<?php esc_html_e( 'Unavailable', 'gutenberg-lab-blocks' ); ?>
		</p>
		<script type="application/json" data-vvm-calendar-data><?php echo wp_json_encode( $payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
	</section>
	<?php

	return ob_get_clean();
}

/**
 * Renders one month grid.
 *
 * @param DateTimeImmutable     $month_start        First day of month.
 * @param array<string, bool>   $unavailable_lookup Unavailable date lookup.
 * @return string
 */
function gutenberg_lab_blocks_render_villa_availability_month( DateTimeImmutable $month_start, $unavailable_lookup ) {
	$days_in_month = (int) $month_start->format( 't' );
	$first_weekday = (int) $month_start->format( 'w' );
	$today         = wp_date( 'Y-m-d' );
	$day           = 1;
	$weekdays      = array(
		__( 'Su', 'gutenberg-lab-blocks' ),
		__( 'Mo', 'gutenberg-lab-blocks' ),
		__( 'Tu', 'gutenberg-lab-blocks' ),
		__( 'We', 'gutenberg-lab-blocks' ),
		__( 'Th', 'gutenberg-lab-blocks' ),
		__( 'Fr', 'gutenberg-lab-blocks' ),
		__( 'Sa', 'gutenberg-lab-blocks' ),
	);

	ob_start();
	?>
	<article class="vvm-villa-availability-calendar__month">
		<h3 class="vvm-villa-availability-calendar__month-title">
			<?php echo esc_html( $month_start->format( 'F Y' ) ); ?>
		</h3>
		<table class="vvm-villa-availability-calendar__table">
			<thead>
				<tr>
					<?php foreach ( $weekdays as $weekday ) : ?>
						<th scope="col"><?php echo esc_html( $weekday ); ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php for ( $row = 0; $row < 6; ++$row ) : ?>
					<?php if ( $day > $days_in_month ) : ?>
						<?php break; ?>
					<?php endif; ?>
					<tr>
						<?php for ( $column = 0; $column < 7; ++$column ) : ?>
							<?php if ( ( 0 === $row && $column < $first_weekday ) || $day > $days_in_month ) : ?>
								<td class="vvm-villa-availability-calendar__empty"></td>
								<?php continue; ?>
							<?php endif; ?>
							<?php
							$date        = $month_start->setDate( (int) $month_start->format( 'Y' ), (int) $month_start->format( 'm' ), $day )->format( 'Y-m-d' );
							$unavailable = isset( $unavailable_lookup[ $date ] );
							$classes     = array( 'vvm-villa-availability-calendar__day' );

							if ( $unavailable ) {
								$classes[] = 'is-unavailable';
							}

							if ( $date === $today ) {
								$classes[] = 'is-today';
							}
							?>
							<td>
								<button
									type="button"
									class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
									data-vvm-calendar-day
									data-date="<?php echo esc_attr( $date ); ?>"
									data-unavailable="<?php echo $unavailable ? 'true' : 'false'; ?>"
									aria-disabled="<?php echo $unavailable ? 'true' : 'false'; ?>"
									aria-label="<?php echo esc_attr( sprintf( '%1$s %2$s', $month_start->setDate( (int) $month_start->format( 'Y' ), (int) $month_start->format( 'm' ), $day )->format( 'F j, Y' ), $unavailable ? __( 'unavailable', 'gutenberg-lab-blocks' ) : __( 'available', 'gutenberg-lab-blocks' ) ) ); ?>"
								>
									<?php echo esc_html( (string) $day ); ?>
								</button>
							</td>
							<?php ++$day; ?>
						<?php endfor; ?>
					</tr>
				<?php endfor; ?>
			</tbody>
		</table>
	</article>
	<?php

	return ob_get_clean();
}

/**
 * Validates the selected villa range on CF7 submit.
 *
 * @param WPCF7_Validation $result Current validation result.
 * @param WPCF7_FormTag    $tag    Current form tag.
 * @return WPCF7_Validation
 */
function gutenberg_lab_blocks_validate_cf7_villa_availability( $result, $tag ) {
	if ( ! $tag instanceof WPCF7_FormTag && class_exists( 'WPCF7_FormTag' ) ) {
		$tag = new WPCF7_FormTag( $tag );
	}

	if ( ! $tag instanceof WPCF7_FormTag || 'preferred-departure' !== $tag->name ) {
		return $result;
	}

	$arrival   = isset( $_POST['preferred-arrival'] ) ? gutenberg_lab_blocks_normalize_iso_date( sanitize_text_field( wp_unslash( $_POST['preferred-arrival'] ) ) ) : '';
	$departure = isset( $_POST['preferred-departure'] ) ? gutenberg_lab_blocks_normalize_iso_date( sanitize_text_field( wp_unslash( $_POST['preferred-departure'] ) ) ) : '';
	$villa_id  = isset( $_POST['villa-id'] ) ? absint( $_POST['villa-id'] ) : 0;

	if ( '' !== $arrival && '' !== $departure && $departure <= $arrival ) {
		$result->invalidate( $tag, __( 'Check-out date must be after check-in date.', 'gutenberg-lab-blocks' ) );
		return $result;
	}

	if ( $villa_id && '' !== $arrival && '' !== $departure && ! gutenberg_lab_blocks_is_villa_date_range_available( $villa_id, $arrival, $departure ) ) {
		$result->invalidate( $tag, __( 'Those dates are not available. Please choose another date range.', 'gutenberg-lab-blocks' ) );
	}

	return $result;
}
add_filter( 'wpcf7_validate_date', 'gutenberg_lab_blocks_validate_cf7_villa_availability', 30, 2 );
add_filter( 'wpcf7_validate_date*', 'gutenberg_lab_blocks_validate_cf7_villa_availability', 30, 2 );
