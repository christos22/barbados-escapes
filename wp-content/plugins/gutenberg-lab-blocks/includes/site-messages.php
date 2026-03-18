<?php
/**
 * Shared Site Message content model for alerts now and modals later.
 *
 * This file intentionally keeps the data model more robust than the current
 * rendering surface. Alerts are the first consumer, but the CPT/meta/taxonomy
 * contract is designed so future modal/pop-up rendering can plug into the same
 * editorial workflow without replacing the storage layer.
 *
 * @package GutenbergLabBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cache key for the active site-message registry.
 */
const GUTENBERG_LAB_BLOCKS_SITE_MESSAGE_REGISTRY_CACHE_KEY = 'site_message_registry_v1';

/**
 * Cache group for site-message runtime data.
 */
const GUTENBERG_LAB_BLOCKS_SITE_MESSAGE_CACHE_GROUP = 'gutenberg_lab_blocks';

/**
 * Fallback option name for the active site-message registry.
 */
const GUTENBERG_LAB_BLOCKS_SITE_MESSAGE_REGISTRY_OPTION = 'gutenberg_lab_blocks_site_message_registry_v1';

/**
 * Cron hook used to refresh the active site-message registry.
 */
const GUTENBERG_LAB_BLOCKS_SITE_MESSAGE_REGISTRY_CRON = 'gutenberg_lab_blocks_refresh_site_message_registry';

/**
 * Returns the supported site-message display types.
 *
 * @return array<string, string>
 */
function gutenberg_lab_blocks_get_site_message_display_type_choices() {
	return array(
		'alert_bar' => __( 'Alert Bar', 'gutenberg-lab-blocks' ),
		'modal'     => __( 'Modal', 'gutenberg-lab-blocks' ),
	);
}

/**
 * Returns the supported placement slots.
 *
 * We only render the header slot in v1, but the shared message model keeps the
 * slot explicit so future placements do not require schema changes.
 *
 * @return array<string, string>
 */
function gutenberg_lab_blocks_get_site_message_slot_choices() {
	return array(
		'header' => __( 'Header', 'gutenberg-lab-blocks' ),
	);
}

/**
 * Returns the supported dismissal lifetimes.
 *
 * @return array<string, string>
 */
function gutenberg_lab_blocks_get_site_message_dismissal_choices() {
	return array(
		'permanent' => __( 'Permanent', 'gutenberg-lab-blocks' ),
		'session'   => __( 'Session', 'gutenberg-lab-blocks' ),
		'1day'      => __( '1 Day', 'gutenberg-lab-blocks' ),
		'7days'     => __( '7 Days', 'gutenberg-lab-blocks' ),
		'30days'    => __( '30 Days', 'gutenberg-lab-blocks' ),
	);
}

/**
 * Returns the supported visibility scopes.
 *
 * @return array<string, string>
 */
function gutenberg_lab_blocks_get_site_message_visibility_choices() {
	return array(
		'sitewide' => __( 'Sitewide', 'gutenberg-lab-blocks' ),
		'targeted' => __( 'Targeted Content', 'gutenberg-lab-blocks' ),
	);
}

/**
 * Sanitizes a select-like site-message meta value.
 *
 * @param mixed                   $value   Raw incoming value.
 * @param array<string, string>   $choices Supported choices.
 * @param string                  $default Default value.
 * @return string
 */
function gutenberg_lab_blocks_sanitize_site_message_choice( $value, $choices, $default ) {
	$value = is_string( $value ) ? sanitize_key( $value ) : '';

	if ( isset( $choices[ $value ] ) ) {
		return $value;
	}

	return $default;
}

/**
 * Sanitizes the site-message display type.
 *
 * @param mixed $value Raw display type.
 * @return string
 */
function gutenberg_lab_blocks_sanitize_site_message_display_type( $value ) {
	return gutenberg_lab_blocks_sanitize_site_message_choice(
		$value,
		gutenberg_lab_blocks_get_site_message_display_type_choices(),
		'alert_bar'
	);
}

/**
 * Sanitizes the site-message placement slot.
 *
 * @param mixed $value Raw slot value.
 * @return string
 */
function gutenberg_lab_blocks_sanitize_site_message_slot( $value ) {
	return gutenberg_lab_blocks_sanitize_site_message_choice(
		$value,
		gutenberg_lab_blocks_get_site_message_slot_choices(),
		'header'
	);
}

/**
 * Sanitizes the dismissal expiry value.
 *
 * @param mixed $value Raw dismissal setting.
 * @return string
 */
function gutenberg_lab_blocks_sanitize_site_message_dismissal_expiry( $value ) {
	return gutenberg_lab_blocks_sanitize_site_message_choice(
		$value,
		gutenberg_lab_blocks_get_site_message_dismissal_choices(),
		'permanent'
	);
}

/**
 * Sanitizes the visibility scope.
 *
 * @param mixed $value Raw visibility scope.
 * @return string
 */
function gutenberg_lab_blocks_sanitize_site_message_visibility_scope( $value ) {
	return gutenberg_lab_blocks_sanitize_site_message_choice(
		$value,
		gutenberg_lab_blocks_get_site_message_visibility_choices(),
		'sitewide'
	);
}

/**
 * Sanitizes the message style variant.
 *
 * @param mixed $value Raw style variant.
 * @return string
 */
function gutenberg_lab_blocks_sanitize_site_message_style_variant( $value ) {
	$value = is_string( $value ) ? sanitize_key( $value ) : '';

	return '' !== $value ? $value : 'default';
}

/**
 * Sanitizes a site-message UTC datetime string.
 *
 * We store schedule boundaries in UTC so the registry can compare datetimes
 * consistently regardless of the viewer locale or the site timezone settings.
 *
 * @param mixed $value Raw datetime string.
 * @return string
 */
function gutenberg_lab_blocks_sanitize_site_message_utc_datetime( $value ) {
	if ( ! is_string( $value ) || '' === trim( $value ) ) {
		return '';
	}

	try {
		$datetime = new DateTimeImmutable( $value, new DateTimeZone( 'UTC' ) );
	} catch ( Exception $exception ) {
		return '';
	}

	return $datetime->setTimezone( new DateTimeZone( 'UTC' ) )->format( DATE_ATOM );
}

/**
 * Sanitizes the targeted object IDs array.
 *
 * @param mixed $value Raw target IDs.
 * @return array<int>
 */
function gutenberg_lab_blocks_sanitize_site_message_target_object_ids( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$target_ids = array_values(
		array_unique(
			array_filter(
				array_map( 'absint', $value )
			)
		)
	);

	return $target_ids;
}

/**
 * Sanitizes the reserved audience rules structure.
 *
 * @param mixed $value Raw audience-rules payload.
 * @return array<string, mixed>
 */
function gutenberg_lab_blocks_sanitize_site_message_audience_rules( $value ) {
	return is_array( $value ) ? $value : array();
}

/**
 * Returns the REST-registered meta schema for site messages.
 *
 * @return array<string, array<string, mixed>>
 */
function gutenberg_lab_blocks_get_site_message_meta_schema() {
	return array(
		'display_type'     => array(
			'type'              => 'string',
			'default'           => 'alert_bar',
			'sanitize_callback' => 'gutenberg_lab_blocks_sanitize_site_message_display_type',
		),
		'placement_slot'   => array(
			'type'              => 'string',
			'default'           => 'header',
			'sanitize_callback' => 'gutenberg_lab_blocks_sanitize_site_message_slot',
		),
		'dismissal_expiry' => array(
			'type'              => 'string',
			'default'           => 'permanent',
			'sanitize_callback' => 'gutenberg_lab_blocks_sanitize_site_message_dismissal_expiry',
		),
		'visibility_scope' => array(
			'type'              => 'string',
			'default'           => 'sitewide',
			'sanitize_callback' => 'gutenberg_lab_blocks_sanitize_site_message_visibility_scope',
		),
		'target_object_ids' => array(
			'type'              => 'array',
			'default'           => array(),
			'sanitize_callback' => 'gutenberg_lab_blocks_sanitize_site_message_target_object_ids',
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'integer',
					),
				),
			),
		),
		'start_at_utc'     => array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'gutenberg_lab_blocks_sanitize_site_message_utc_datetime',
		),
		'end_at_utc'       => array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'gutenberg_lab_blocks_sanitize_site_message_utc_datetime',
		),
		'style_variant'    => array(
			'type'              => 'string',
			'default'           => 'default',
			'sanitize_callback' => 'gutenberg_lab_blocks_sanitize_site_message_style_variant',
		),
		'audience_rules'   => array(
			'type'              => 'object',
			'default'           => array(),
			'sanitize_callback' => 'gutenberg_lab_blocks_sanitize_site_message_audience_rules',
			'show_in_rest'      => array(
				'schema' => array(
					'type'                 => 'object',
					'additionalProperties' => true,
				),
			),
		),
	);
}

/**
 * Returns the custom post-type capabilities.
 *
 * @return array<string, string>
 */
function gutenberg_lab_blocks_get_site_message_capabilities() {
	return array(
		'edit_post'              => 'edit_site_message',
		'read_post'              => 'read_site_message',
		'delete_post'            => 'delete_site_message',
		'edit_posts'             => 'edit_site_messages',
		'edit_others_posts'      => 'edit_others_site_messages',
		'publish_posts'          => 'publish_site_messages',
		'read_private_posts'     => 'read_private_site_messages',
		'delete_posts'           => 'delete_site_messages',
		'delete_private_posts'   => 'delete_private_site_messages',
		'delete_published_posts' => 'delete_published_site_messages',
		'delete_others_posts'    => 'delete_others_site_messages',
		'edit_private_posts'     => 'edit_private_site_messages',
		'edit_published_posts'   => 'edit_published_site_messages',
		'create_posts'           => 'create_site_messages',
	);
}

/**
 * Grants site-message capabilities to the higher-trust editorial roles.
 *
 * This keeps sitewide messages separate from normal page/post capabilities.
 */
function gutenberg_lab_blocks_sync_site_message_role_capabilities() {
	$capabilities = gutenberg_lab_blocks_get_site_message_capabilities();
	$roles        = array( 'administrator', 'editor' );

	foreach ( $roles as $role_name ) {
		$role = get_role( $role_name );

		if ( ! $role instanceof WP_Role ) {
			continue;
		}

		foreach ( $capabilities as $capability ) {
			$role->add_cap( $capability );
		}
	}
}
add_action( 'init', 'gutenberg_lab_blocks_sync_site_message_role_capabilities', 15 );

/**
 * Registers the Site Message content type.
 */
function gutenberg_lab_blocks_register_site_message_post_type() {
	register_post_type(
		'site_message',
		array(
			'labels'            => array(
				'name'               => __( 'Site Messages', 'gutenberg-lab-blocks' ),
				'singular_name'      => __( 'Site Message', 'gutenberg-lab-blocks' ),
				'add_new_item'       => __( 'Add New Site Message', 'gutenberg-lab-blocks' ),
				'edit_item'          => __( 'Edit Site Message', 'gutenberg-lab-blocks' ),
				'new_item'           => __( 'New Site Message', 'gutenberg-lab-blocks' ),
				'view_item'          => __( 'View Site Message', 'gutenberg-lab-blocks' ),
				'search_items'       => __( 'Search Site Messages', 'gutenberg-lab-blocks' ),
				'not_found'          => __( 'No site messages found.', 'gutenberg-lab-blocks' ),
				'not_found_in_trash' => __( 'No site messages found in Trash.', 'gutenberg-lab-blocks' ),
				'all_items'          => __( 'Site Messages', 'gutenberg-lab-blocks' ),
				'menu_name'          => __( 'Site Messages', 'gutenberg-lab-blocks' ),
			),
			'public'            => false,
			'publicly_queryable'=> false,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_in_admin_bar' => true,
			'show_in_rest'      => true,
			'map_meta_cap'      => true,
			'capabilities'      => gutenberg_lab_blocks_get_site_message_capabilities(),
			'menu_icon'         => 'dashicons-warning',
			'supports'          => array(
				'title',
				'editor',
				'page-attributes',
				'revisions',
			),
			'has_archive'       => false,
			'rewrite'           => false,
			'delete_with_user'  => false,
			'template'          => array(
				array(
					'core/paragraph',
					array(
						'placeholder' => __( 'Add the alert or modal message…', 'gutenberg-lab-blocks' ),
					),
				),
				array( 'core/buttons', array() ),
			),
		)
	);
}
add_action( 'init', 'gutenberg_lab_blocks_register_site_message_post_type' );

/**
 * Registers the hidden taxonomies used for low-cardinality message axes.
 */
function gutenberg_lab_blocks_register_site_message_taxonomies() {
	register_taxonomy(
		'site_message_kind',
		array( 'site_message' ),
		array(
			'public'            => false,
			'hierarchical'      => false,
			'show_ui'           => false,
			'show_admin_column' => false,
			'show_in_rest'      => false,
			'rewrite'           => false,
			'query_var'         => false,
		)
	);

	register_taxonomy(
		'site_message_slot',
		array( 'site_message' ),
		array(
			'public'            => false,
			'hierarchical'      => false,
			'show_ui'           => false,
			'show_admin_column' => false,
			'show_in_rest'      => false,
			'rewrite'           => false,
			'query_var'         => false,
		)
	);
}
add_action( 'init', 'gutenberg_lab_blocks_register_site_message_taxonomies' );

/**
 * Ensures the hidden site-message taxonomy terms always exist.
 */
function gutenberg_lab_blocks_seed_site_message_terms() {
	foreach ( array_keys( gutenberg_lab_blocks_get_site_message_display_type_choices() ) as $slug ) {
		if ( ! term_exists( $slug, 'site_message_kind' ) ) {
			wp_insert_term( ucwords( str_replace( '_', ' ', $slug ) ), 'site_message_kind', array( 'slug' => $slug ) );
		}
	}

	foreach ( array_keys( gutenberg_lab_blocks_get_site_message_slot_choices() ) as $slug ) {
		if ( ! term_exists( $slug, 'site_message_slot' ) ) {
			wp_insert_term( ucwords( str_replace( '_', ' ', $slug ) ), 'site_message_slot', array( 'slug' => $slug ) );
		}
	}
}
add_action( 'init', 'gutenberg_lab_blocks_seed_site_message_terms', 20 );

/**
 * Registers the site-message meta fields.
 */
function gutenberg_lab_blocks_register_site_message_meta() {
	foreach ( gutenberg_lab_blocks_get_site_message_meta_schema() as $meta_key => $meta_args ) {
		$show_in_rest = true;

		if ( isset( $meta_args['show_in_rest'] ) ) {
			$show_in_rest = $meta_args['show_in_rest'];
			unset( $meta_args['show_in_rest'] );
		}

		register_post_meta(
			'site_message',
			$meta_key,
			array_merge(
				$meta_args,
				array(
					'show_in_rest' => $show_in_rest,
					'single'       => true,
					'auth_callback'=> static function() {
						return current_user_can( 'edit_site_messages' );
					},
				)
			)
		);
	}
}
add_action( 'init', 'gutenberg_lab_blocks_register_site_message_meta' );

/**
 * Limits the site-message editor to safe, concise content blocks.
 *
 * @param bool|array<string> $allowed_block_types Existing block allow list.
 * @param WP_Block_Editor_Context $editor_context Current editor context.
 * @return bool|array<string>
 */
function gutenberg_lab_blocks_filter_site_message_allowed_blocks( $allowed_block_types, $editor_context ) {
	if (
		empty( $editor_context->post ) ||
		! $editor_context->post instanceof WP_Post ||
		'site_message' !== $editor_context->post->post_type
	) {
		return $allowed_block_types;
	}

	return array(
		'core/group',
		'core/heading',
		'core/paragraph',
		'core/list',
		'core/buttons',
		'core/button',
	);
}
add_filter( 'allowed_block_types_all', 'gutenberg_lab_blocks_filter_site_message_allowed_blocks', 10, 2 );

/**
 * Returns the current UTC timestamp.
 *
 * @return int
 */
function gutenberg_lab_blocks_get_current_utc_timestamp() {
	return current_time( 'timestamp', true );
}

/**
 * Normalizes a UTC datetime string to a Unix timestamp.
 *
 * @param string $datetime UTC datetime string.
 * @return int
 */
function gutenberg_lab_blocks_get_site_message_utc_timestamp( $datetime ) {
	if ( ! is_string( $datetime ) || '' === $datetime ) {
		return 0;
	}

	$timestamp = strtotime( $datetime );

	return false === $timestamp ? 0 : (int) $timestamp;
}

/**
 * Returns the registry stored in cache or its non-autoload option fallback.
 *
 * @return array<string, mixed>
 */
function gutenberg_lab_blocks_get_site_message_registry_from_storage() {
	$registry = wp_cache_get(
		GUTENBERG_LAB_BLOCKS_SITE_MESSAGE_REGISTRY_CACHE_KEY,
		GUTENBERG_LAB_BLOCKS_SITE_MESSAGE_CACHE_GROUP
	);

	if ( is_array( $registry ) ) {
		return $registry;
	}

	$registry = get_option( GUTENBERG_LAB_BLOCKS_SITE_MESSAGE_REGISTRY_OPTION, array() );

	if ( is_array( $registry ) ) {
		wp_cache_set(
			GUTENBERG_LAB_BLOCKS_SITE_MESSAGE_REGISTRY_CACHE_KEY,
			$registry,
			GUTENBERG_LAB_BLOCKS_SITE_MESSAGE_CACHE_GROUP
		);

		return $registry;
	}

	return array();
}

/**
 * Persists the rebuilt registry to cache and option storage.
 *
 * @param array<string, mixed> $registry Registry payload.
 */
function gutenberg_lab_blocks_store_site_message_registry( $registry ) {
	wp_cache_set(
		GUTENBERG_LAB_BLOCKS_SITE_MESSAGE_REGISTRY_CACHE_KEY,
		$registry,
		GUTENBERG_LAB_BLOCKS_SITE_MESSAGE_CACHE_GROUP
	);

	update_option( GUTENBERG_LAB_BLOCKS_SITE_MESSAGE_REGISTRY_OPTION, $registry, false );
}

/**
 * Clears and re-schedules the next site-message schedule boundary refresh.
 *
 * @param int $next_transition_utc Next future transition timestamp.
 */
function gutenberg_lab_blocks_schedule_next_site_message_registry_refresh( $next_transition_utc ) {
	wp_clear_scheduled_hook( GUTENBERG_LAB_BLOCKS_SITE_MESSAGE_REGISTRY_CRON );

	if ( $next_transition_utc > gutenberg_lab_blocks_get_current_utc_timestamp() ) {
		wp_schedule_single_event( $next_transition_utc, GUTENBERG_LAB_BLOCKS_SITE_MESSAGE_REGISTRY_CRON );
	}
}

/**
 * Synchronizes the hidden taxonomy terms from the editor-facing meta values.
 *
 * @param int $post_id Site message post ID.
 */
function gutenberg_lab_blocks_sync_site_message_terms( $post_id ) {
	$display_type = get_post_meta( $post_id, 'display_type', true );
	$slot         = get_post_meta( $post_id, 'placement_slot', true );

	$display_type = gutenberg_lab_blocks_sanitize_site_message_display_type( $display_type );
	$slot         = gutenberg_lab_blocks_sanitize_site_message_slot( $slot );

	wp_set_object_terms( $post_id, array( $display_type ), 'site_message_kind', false );
	wp_set_object_terms( $post_id, array( $slot ), 'site_message_slot', false );
}

/**
 * Builds the render-ready registry payload for active site messages.
 *
 * @return array<string, mixed>
 */
function gutenberg_lab_blocks_build_site_message_registry() {
	$now_utc             = gutenberg_lab_blocks_get_current_utc_timestamp();
	$next_transition_utc = 0;
	$registry            = array(
		'generated_at_utc' => gmdate( DATE_ATOM, $now_utc ),
		'next_transition_utc' => '',
		'slots'            => array(),
	);

	$messages = get_posts(
		array(
			'post_type'              => 'site_message',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'orderby'                => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
		)
	);

	foreach ( $messages as $message ) {
		$display_type = gutenberg_lab_blocks_sanitize_site_message_display_type(
			get_post_meta( $message->ID, 'display_type', true )
		);
		$slot = gutenberg_lab_blocks_sanitize_site_message_slot(
			get_post_meta( $message->ID, 'placement_slot', true )
		);
		$visibility_scope = gutenberg_lab_blocks_sanitize_site_message_visibility_scope(
			get_post_meta( $message->ID, 'visibility_scope', true )
		);
		$dismissal_expiry = gutenberg_lab_blocks_sanitize_site_message_dismissal_expiry(
			get_post_meta( $message->ID, 'dismissal_expiry', true )
		);
		$style_variant = gutenberg_lab_blocks_sanitize_site_message_style_variant(
			get_post_meta( $message->ID, 'style_variant', true )
		);
		$target_ids = gutenberg_lab_blocks_sanitize_site_message_target_object_ids(
			get_post_meta( $message->ID, 'target_object_ids', true )
		);
		$start_at_utc = gutenberg_lab_blocks_get_site_message_utc_timestamp(
			get_post_meta( $message->ID, 'start_at_utc', true )
		);
		$end_at_utc = gutenberg_lab_blocks_get_site_message_utc_timestamp(
			get_post_meta( $message->ID, 'end_at_utc', true )
		);

		if ( $start_at_utc > 0 && $start_at_utc > $now_utc ) {
			$next_transition_utc = 0 === $next_transition_utc
				? $start_at_utc
				: min( $next_transition_utc, $start_at_utc );
		}

		if ( $end_at_utc > 0 && $end_at_utc > $now_utc ) {
			$next_transition_utc = 0 === $next_transition_utc
				? $end_at_utc
				: min( $next_transition_utc, $end_at_utc );
		}

		// Invalid windows fail closed. We warn in the editor, but the runtime
		// should never show a message with an impossible schedule.
		if ( $start_at_utc > 0 && $end_at_utc > 0 && $end_at_utc <= $start_at_utc ) {
			continue;
		}

		if ( $start_at_utc > 0 && $start_at_utc > $now_utc ) {
			continue;
		}

		if ( $end_at_utc > 0 && $end_at_utc <= $now_utc ) {
			continue;
		}

		$html = trim( do_blocks( $message->post_content ) );

		if ( '' === wp_strip_all_tags( $html ) ) {
			continue;
		}

		$payload = array(
			'id'               => (int) $message->ID,
			'title'            => get_the_title( $message ),
			'display_type'     => $display_type,
			'slot'             => $slot,
			'dismissal_expiry' => $dismissal_expiry,
			'style_variant'    => $style_variant,
			'html'             => $html,
			'menu_order'       => (int) $message->menu_order,
			'date_gmt'         => get_post_time( DATE_ATOM, true, $message ),
			'token'            => sprintf(
				'site-message-%1$d-%2$s',
				(int) $message->ID,
				get_post_field( 'post_modified_gmt', $message->ID )
			),
		);

		if ( ! isset( $registry['slots'][ $slot ] ) ) {
			$registry['slots'][ $slot ] = array(
				'sitewide' => array(),
				'targeted' => array(),
			);
		}

		if ( 'targeted' === $visibility_scope ) {
			if ( empty( $target_ids ) ) {
				continue;
			}

			foreach ( $target_ids as $target_id ) {
				if ( ! isset( $registry['slots'][ $slot ]['targeted'][ $target_id ] ) ) {
					$registry['slots'][ $slot ]['targeted'][ $target_id ] = array();
				}

				$registry['slots'][ $slot ]['targeted'][ $target_id ][] = $payload;
			}

			continue;
		}

		$registry['slots'][ $slot ]['sitewide'][] = $payload;
	}

	$registry['next_transition_utc'] = $next_transition_utc > 0
		? gmdate( DATE_ATOM, $next_transition_utc )
		: '';

	return $registry;
}

/**
 * Rebuilds and stores the active site-message registry.
 *
 * @return array<string, mixed>
 */
function gutenberg_lab_blocks_rebuild_site_message_registry() {
	$registry = gutenberg_lab_blocks_build_site_message_registry();

	gutenberg_lab_blocks_store_site_message_registry( $registry );
	gutenberg_lab_blocks_schedule_next_site_message_registry_refresh(
		gutenberg_lab_blocks_get_site_message_utc_timestamp( $registry['next_transition_utc'] ?? '' )
	);

	return $registry;
}

/**
 * Returns the cached site-message registry, rebuilding it if stale/missing.
 *
 * @return array<string, mixed>
 */
function gutenberg_lab_blocks_get_site_message_registry() {
	$registry = gutenberg_lab_blocks_get_site_message_registry_from_storage();

	if ( empty( $registry ) || ! isset( $registry['slots'] ) || ! is_array( $registry['slots'] ) ) {
		return gutenberg_lab_blocks_rebuild_site_message_registry();
	}

	$next_transition_utc = gutenberg_lab_blocks_get_site_message_utc_timestamp(
		$registry['next_transition_utc'] ?? ''
	);

	if ( $next_transition_utc > 0 && $next_transition_utc <= gutenberg_lab_blocks_get_current_utc_timestamp() ) {
		return gutenberg_lab_blocks_rebuild_site_message_registry();
	}

	return $registry;
}

/**
 * Refreshes the registry when the cron event fires.
 */
function gutenberg_lab_blocks_refresh_site_message_registry_cron() {
	gutenberg_lab_blocks_rebuild_site_message_registry();
}
add_action( GUTENBERG_LAB_BLOCKS_SITE_MESSAGE_REGISTRY_CRON, 'gutenberg_lab_blocks_refresh_site_message_registry_cron' );

/**
 * Rebuilds the registry after site-message changes.
 *
 * @param int $post_id Site message ID.
 */
function gutenberg_lab_blocks_handle_site_message_post_update( $post_id ) {
	if (
		wp_is_post_revision( $post_id ) ||
		wp_is_post_autosave( $post_id ) ||
		'site_message' !== get_post_type( $post_id )
	) {
		return;
	}

	gutenberg_lab_blocks_sync_site_message_terms( $post_id );
	gutenberg_lab_blocks_rebuild_site_message_registry();
}
add_action( 'save_post_site_message', 'gutenberg_lab_blocks_handle_site_message_post_update', 20 );

/**
 * Rebuilds the registry after trash/untrash/delete actions.
 *
 * @param int $post_id Post ID.
 */
function gutenberg_lab_blocks_handle_site_message_deletion( $post_id ) {
	if ( 'site_message' !== get_post_type( $post_id ) ) {
		return;
	}

	gutenberg_lab_blocks_rebuild_site_message_registry();
}
add_action( 'deleted_post', 'gutenberg_lab_blocks_handle_site_message_deletion' );
add_action( 'trashed_post', 'gutenberg_lab_blocks_handle_site_message_deletion' );
add_action( 'untrashed_post', 'gutenberg_lab_blocks_handle_site_message_deletion' );

/**
 * Returns the current singular object ID eligible for targeted site messages.
 *
 * @return int
 */
function gutenberg_lab_blocks_get_current_site_message_target_id() {
	if ( ! is_singular() ) {
		return 0;
	}

	$target_id = (int) get_queried_object_id();

	if ( $target_id <= 0 || 'site_message' === get_post_type( $target_id ) ) {
		return 0;
	}

	return $target_id;
}

/**
 * Sort callback for merged site-message payload arrays.
 *
 * @param array<string, mixed> $left  Left payload.
 * @param array<string, mixed> $right Right payload.
 * @return int
 */
function gutenberg_lab_blocks_compare_site_message_payloads( $left, $right ) {
	$left_order  = (int) ( $left['menu_order'] ?? 0 );
	$right_order = (int) ( $right['menu_order'] ?? 0 );

	if ( $left_order !== $right_order ) {
		return $left_order <=> $right_order;
	}

	return strcmp( (string) ( $right['date_gmt'] ?? '' ), (string) ( $left['date_gmt'] ?? '' ) );
}

/**
 * Returns the active payloads for a given placement slot.
 *
 * @param string $slot Requested slot.
 * @return array<int, array<string, mixed>>
 */
function gutenberg_lab_blocks_get_active_site_message_payloads_for_slot( $slot ) {
	$slot     = gutenberg_lab_blocks_sanitize_site_message_slot( $slot );
	$registry = gutenberg_lab_blocks_get_site_message_registry();
	$payloads = array();

	if ( empty( $registry['slots'][ $slot ] ) || ! is_array( $registry['slots'][ $slot ] ) ) {
		return array();
	}

	$slot_registry = $registry['slots'][ $slot ];

	if ( ! empty( $slot_registry['sitewide'] ) && is_array( $slot_registry['sitewide'] ) ) {
		$payloads = array_merge( $payloads, $slot_registry['sitewide'] );
	}

	$target_id = gutenberg_lab_blocks_get_current_site_message_target_id();

	if (
		$target_id > 0 &&
		! empty( $slot_registry['targeted'][ $target_id ] ) &&
		is_array( $slot_registry['targeted'][ $target_id ] )
	) {
		$payloads = array_merge( $payloads, $slot_registry['targeted'][ $target_id ] );
	}

	usort( $payloads, 'gutenberg_lab_blocks_compare_site_message_payloads' );

	return $payloads;
}

/**
 * Returns a UTC datetime string in the site timezone for admin display.
 *
 * @param string $utc_datetime UTC datetime string.
 * @return string
 */
function gutenberg_lab_blocks_format_site_message_schedule_for_admin( $utc_datetime ) {
	$utc_datetime = is_string( $utc_datetime ) ? $utc_datetime : '';

	if ( '' === $utc_datetime ) {
		return '—';
	}

	try {
		$datetime = new DateTimeImmutable( $utc_datetime, new DateTimeZone( 'UTC' ) );
		$datetime = $datetime->setTimezone( wp_timezone() );
	} catch ( Exception $exception ) {
		return '—';
	}

	return $datetime->format( 'Y-m-d H:i' );
}

/**
 * Returns the current schedule status for a site-message post.
 *
 * @param int $post_id Site message ID.
 * @return string
 */
function gutenberg_lab_blocks_get_site_message_schedule_status_label( $post_id ) {
	$now_utc      = gutenberg_lab_blocks_get_current_utc_timestamp();
	$start_at_utc = gutenberg_lab_blocks_get_site_message_utc_timestamp( get_post_meta( $post_id, 'start_at_utc', true ) );
	$end_at_utc   = gutenberg_lab_blocks_get_site_message_utc_timestamp( get_post_meta( $post_id, 'end_at_utc', true ) );

	if ( $start_at_utc > 0 && $end_at_utc > 0 && $end_at_utc <= $start_at_utc ) {
		return __( 'Invalid Window', 'gutenberg-lab-blocks' );
	}

	if ( $start_at_utc > 0 && $start_at_utc > $now_utc ) {
		return __( 'Upcoming', 'gutenberg-lab-blocks' );
	}

	if ( $end_at_utc > 0 && $end_at_utc <= $now_utc ) {
		return __( 'Expired', 'gutenberg-lab-blocks' );
	}

	return __( 'Active', 'gutenberg-lab-blocks' );
}

/**
 * Adds site-message admin columns for editorial visibility.
 *
 * @param array<string, string> $columns Existing columns.
 * @return array<string, string>
 */
function gutenberg_lab_blocks_site_message_admin_columns( $columns ) {
	$columns['site_message_type']      = __( 'Type', 'gutenberg-lab-blocks' );
	$columns['site_message_slot']      = __( 'Slot', 'gutenberg-lab-blocks' );
	$columns['site_message_status']    = __( 'Status', 'gutenberg-lab-blocks' );
	$columns['site_message_schedule']  = __( 'Schedule', 'gutenberg-lab-blocks' );
	$columns['site_message_targeting'] = __( 'Targeting', 'gutenberg-lab-blocks' );

	return $columns;
}
add_filter( 'manage_site_message_posts_columns', 'gutenberg_lab_blocks_site_message_admin_columns' );

/**
 * Renders the custom site-message admin columns.
 *
 * @param string $column  Column slug.
 * @param int    $post_id Site message ID.
 */
function gutenberg_lab_blocks_render_site_message_admin_column( $column, $post_id ) {
	switch ( $column ) {
		case 'site_message_type':
			echo esc_html(
				gutenberg_lab_blocks_get_site_message_display_type_choices()[
					gutenberg_lab_blocks_sanitize_site_message_display_type( get_post_meta( $post_id, 'display_type', true ) )
				] ?? __( 'Alert Bar', 'gutenberg-lab-blocks' )
			);
			break;

		case 'site_message_slot':
			echo esc_html(
				gutenberg_lab_blocks_get_site_message_slot_choices()[
					gutenberg_lab_blocks_sanitize_site_message_slot( get_post_meta( $post_id, 'placement_slot', true ) )
				] ?? __( 'Header', 'gutenberg-lab-blocks' )
			);
			break;

		case 'site_message_status':
			echo esc_html( gutenberg_lab_blocks_get_site_message_schedule_status_label( $post_id ) );
			break;

		case 'site_message_schedule':
			printf(
				'%1$s → %2$s',
				esc_html( gutenberg_lab_blocks_format_site_message_schedule_for_admin( get_post_meta( $post_id, 'start_at_utc', true ) ) ),
				esc_html( gutenberg_lab_blocks_format_site_message_schedule_for_admin( get_post_meta( $post_id, 'end_at_utc', true ) ) )
			);
			break;

		case 'site_message_targeting':
			$visibility_scope = gutenberg_lab_blocks_sanitize_site_message_visibility_scope( get_post_meta( $post_id, 'visibility_scope', true ) );
			$target_ids       = gutenberg_lab_blocks_sanitize_site_message_target_object_ids( get_post_meta( $post_id, 'target_object_ids', true ) );

			if ( 'sitewide' === $visibility_scope ) {
				echo esc_html__( 'Sitewide', 'gutenberg-lab-blocks' );
				break;
			}

			if ( empty( $target_ids ) ) {
				echo esc_html__( 'Targeted (0)', 'gutenberg-lab-blocks' );
				break;
			}

			echo esc_html(
				sprintf(
					/* translators: %d selected content items. */
					__( 'Targeted (%d)', 'gutenberg-lab-blocks' ),
					count( $target_ids )
				)
			);
			break;
	}
}
add_action( 'manage_site_message_posts_custom_column', 'gutenberg_lab_blocks_render_site_message_admin_column', 10, 2 );

/**
 * Enqueues the site-message document settings UI in the block editor.
 */
function gutenberg_lab_blocks_enqueue_site_message_editor_assets() {
	$screen = get_current_screen();

	if ( ! $screen || 'site_message' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_script(
		'gutenberg-lab-site-message-editor',
		plugins_url( 'assets/js/site-message-editor.js', dirname( __FILE__ ) ),
		array(
			'wp-api-fetch',
			'wp-components',
			'wp-core-data',
			'wp-data',
			'wp-edit-post',
			'wp-element',
			'wp-i18n',
			'wp-plugins',
			'wp-url',
		),
		gutenberg_lab_blocks_asset_version( 'assets/js/site-message-editor.js' ),
		true
	);

	wp_localize_script(
		'gutenberg-lab-site-message-editor',
		'gutenbergLabSiteMessageSettings',
		array(
			'postType'          => 'site_message',
			'displayTypeChoices'=> gutenberg_lab_blocks_get_site_message_display_type_choices(),
			'slotChoices'       => gutenberg_lab_blocks_get_site_message_slot_choices(),
			'dismissalChoices'  => gutenberg_lab_blocks_get_site_message_dismissal_choices(),
			'visibilityChoices' => gutenberg_lab_blocks_get_site_message_visibility_choices(),
			'targetPostTypes'   => array( 'page', 'post', 'packages' ),
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'gutenberg_lab_blocks_enqueue_site_message_editor_assets' );

/**
 * Renders the active Site Alerts markup for a slot.
 *
 * @param string $slot Placement slot.
 * @param string $wrapper_attributes Block wrapper attributes.
 * @return string
 */
function gutenberg_lab_blocks_render_site_alerts_markup( $slot, $wrapper_attributes = '' ) {
	$payloads = gutenberg_lab_blocks_get_active_site_message_payloads_for_slot( $slot );

	if ( empty( $payloads ) ) {
		return '';
	}

	ob_start();
	?>
	<div <?php echo $wrapper_attributes; ?> data-site-alerts-root>
		<?php foreach ( $payloads as $payload ) : ?>
			<section
				class="vvm-site-alert vvm-site-alert--style-<?php echo esc_attr( sanitize_html_class( $payload['style_variant'] ?? 'default' ) ); ?>"
				data-site-message-token="<?php echo esc_attr( $payload['token'] ); ?>"
				data-site-message-dismissal="<?php echo esc_attr( $payload['dismissal_expiry'] ); ?>"
			>
				<div class="vvm-site-alert__inner">
					<div class="vvm-site-alert__content">
						<?php echo $payload['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<button
						type="button"
						class="vvm-site-alert__dismiss"
						data-site-message-dismiss
						aria-label="<?php esc_attr_e( 'Dismiss site message', 'gutenberg-lab-blocks' ); ?>"
					>
						×
					</button>
				</div>
			</section>
		<?php endforeach; ?>
	</div>
	<?php

	return trim( (string) ob_get_clean() );
}
