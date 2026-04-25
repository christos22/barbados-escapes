<?php
/**
 * Project-specific WordPress Abilities exposed through the MCP Adapter.
 *
 * @package GutenbergLabBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Makes selected core read-only abilities visible to the MCP default server.
 *
 * The ability's own permission callback still decides whether the current user
 * can execute it. This only lets authenticated MCP clients discover it.
 *
 * @param array<string, mixed> $args Ability registration arguments.
 * @param string               $ability_name Ability name.
 * @return array<string, mixed>
 */
function gutenberg_lab_blocks_mcp_expose_core_abilities( $args, $ability_name ) {
	$core_abilities = array(
		'core/get-site-info',
		'core/get-user-info',
		'core/get-environment-info',
	);

	if ( in_array( $ability_name, $core_abilities, true ) ) {
		$args['meta']['mcp']['public'] = true;
	}

	return $args;
}
add_filter( 'wp_register_ability_args', 'gutenberg_lab_blocks_mcp_expose_core_abilities', 10, 2 );

/**
 * Registers the category used by Barbados Escapes content abilities.
 */
function gutenberg_lab_blocks_register_mcp_ability_categories() {
	if ( ! function_exists( 'wp_register_ability_category' ) ) {
		return;
	}

	wp_register_ability_category(
		'barbados-escapes-content',
		array(
			'label'       => __( 'Barbados Escapes Content', 'gutenberg-lab-blocks' ),
			'description' => __( 'Content editing abilities for the Barbados Escapes villa build.', 'gutenberg-lab-blocks' ),
		)
	);
}
add_action( 'wp_abilities_api_categories_init', 'gutenberg_lab_blocks_register_mcp_ability_categories' );

/**
 * Registers narrow content abilities for villa Stack Tabs blocks.
 */
function gutenberg_lab_blocks_register_mcp_abilities() {
	if ( ! function_exists( 'wp_register_ability' ) ) {
		return;
	}

	wp_register_ability(
		'barbados-escapes/get-villa-stack-tabs',
		array(
			'label'               => __( 'Get Villa Stack Tabs', 'gutenberg-lab-blocks' ),
			'description'         => __( 'Returns labels and content summaries for Stack Tabs blocks on one villa.', 'gutenberg-lab-blocks' ),
			'category'            => 'barbados-escapes-content',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'post_id' => array(
						'type'        => 'integer',
						'description' => __( 'Villa post ID.', 'gutenberg-lab-blocks' ),
						'minimum'     => 1,
					),
				),
				'required'             => array( 'post_id' ),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'post_id' => array( 'type' => 'integer' ),
					'tabs'    => array(
						'type'  => 'array',
						'items' => array( 'type' => 'object' ),
					),
				),
			),
			'permission_callback' => 'gutenberg_lab_blocks_mcp_can_read_villa',
			'execute_callback'    => 'gutenberg_lab_blocks_mcp_get_villa_stack_tabs',
			'meta'                => array(
				'annotations' => array(
					'readonly'    => true,
					'destructive' => false,
					'idempotent'  => true,
				),
				'mcp'         => array(
					'public' => true,
					'type'   => 'tool',
				),
			),
		)
	);

	wp_register_ability(
		'barbados-escapes/replace-villa-stack-tab-content',
		array(
			'label'               => __( 'Replace Villa Stack Tab Content', 'gutenberg-lab-blocks' ),
			'description'         => __( 'Replaces the inner Gutenberg block markup for one named Stack Tab on a villa.', 'gutenberg-lab-blocks' ),
			'category'            => 'barbados-escapes-content',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'post_id'      => array(
						'type'        => 'integer',
						'description' => __( 'Villa post ID.', 'gutenberg-lab-blocks' ),
						'minimum'     => 1,
					),
					'tab_label'    => array(
						'type'        => 'string',
						'description' => __( 'Existing Stack Tab label to replace, for example Bedrooms.', 'gutenberg-lab-blocks' ),
						'minLength'   => 1,
					),
					'block_markup' => array(
						'type'        => 'string',
						'description' => __( 'Serialized Gutenberg block markup to store inside the tab.', 'gutenberg-lab-blocks' ),
						'minLength'   => 1,
					),
				),
				'required'             => array( 'post_id', 'tab_label', 'block_markup' ),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'post_id'     => array( 'type' => 'integer' ),
					'tab_label'   => array( 'type' => 'string' ),
					'block_count' => array( 'type' => 'integer' ),
					'updated'     => array( 'type' => 'boolean' ),
				),
			),
			'permission_callback' => 'gutenberg_lab_blocks_mcp_can_edit_villa',
			'execute_callback'    => 'gutenberg_lab_blocks_mcp_replace_villa_stack_tab_content',
			'meta'                => array(
				'annotations' => array(
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => true,
				),
				'mcp'         => array(
					'public' => true,
					'type'   => 'tool',
				),
			),
		)
	);
}
add_action( 'wp_abilities_api_init', 'gutenberg_lab_blocks_register_mcp_abilities' );

/**
 * Gets a villa post from ability input.
 *
 * @param array<string, mixed> $input Ability input.
 * @return WP_Post|WP_Error
 */
function gutenberg_lab_blocks_mcp_get_villa_from_input( $input ) {
	$post_id = absint( $input['post_id'] ?? 0 );

	if ( ! $post_id ) {
		return new WP_Error( 'missing_post_id', __( 'A valid villa post ID is required.', 'gutenberg-lab-blocks' ) );
	}

	$post = get_post( $post_id );

	if ( ! $post || 'villa' !== $post->post_type ) {
		return new WP_Error( 'invalid_villa', __( 'The requested post is not a villa.', 'gutenberg-lab-blocks' ) );
	}

	return $post;
}

/**
 * Permission callback for reading villa tab content.
 *
 * @param array<string, mixed> $input Ability input.
 * @return bool|WP_Error
 */
function gutenberg_lab_blocks_mcp_can_read_villa( $input ) {
	$post = gutenberg_lab_blocks_mcp_get_villa_from_input( is_array( $input ) ? $input : array() );

	if ( is_wp_error( $post ) ) {
		return $post;
	}

	return current_user_can( 'read_post', $post->ID );
}

/**
 * Permission callback for editing villa tab content.
 *
 * @param array<string, mixed> $input Ability input.
 * @return bool|WP_Error
 */
function gutenberg_lab_blocks_mcp_can_edit_villa( $input ) {
	$post = gutenberg_lab_blocks_mcp_get_villa_from_input( is_array( $input ) ? $input : array() );

	if ( is_wp_error( $post ) ) {
		return $post;
	}

	return current_user_can( 'edit_post', $post->ID );
}

/**
 * Returns the text content for a Stack Tab child block.
 *
 * @param array<string, mixed> $tab_block Parsed Stack Tab block.
 * @return string
 */
function gutenberg_lab_blocks_mcp_get_tab_text( $tab_block ) {
	$markup = '';

	foreach ( (array) ( $tab_block['innerBlocks'] ?? array() ) as $inner_block ) {
		if ( is_array( $inner_block ) ) {
			$markup .= render_block( $inner_block );
		}
	}

	return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $markup ) ) );
}

/**
 * Collects all Stack Tab children from parsed blocks.
 *
 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
 * @return array<int, array<string, mixed>>
 */
function gutenberg_lab_blocks_mcp_collect_stack_tabs( $blocks ) {
	$tabs = array();

	foreach ( $blocks as $block ) {
		if ( ! is_array( $block ) ) {
			continue;
		}

		if ( 'gutenberg-lab-blocks/stack-tabs' === ( $block['blockName'] ?? '' ) ) {
			foreach ( (array) ( $block['innerBlocks'] ?? array() ) as $tab_block ) {
				if ( 'gutenberg-lab-blocks/stack-tab' !== ( $tab_block['blockName'] ?? '' ) ) {
					continue;
				}

				$text = gutenberg_lab_blocks_mcp_get_tab_text( $tab_block );
				$tabs[] = array(
					'label'       => (string) ( $tab_block['attrs']['label'] ?? '' ),
					'block_count' => count( (array) ( $tab_block['innerBlocks'] ?? array() ) ),
					'has_content' => '' !== $text,
					'text'        => $text,
				);
			}
		}

		$inner_tabs = gutenberg_lab_blocks_mcp_collect_stack_tabs( (array) ( $block['innerBlocks'] ?? array() ) );
		$tabs       = array_merge( $tabs, $inner_tabs );
	}

	return $tabs;
}

/**
 * Executes the read-only Stack Tabs ability.
 *
 * @param array<string, mixed> $input Ability input.
 * @return array<string, mixed>|WP_Error
 */
function gutenberg_lab_blocks_mcp_get_villa_stack_tabs( $input ) {
	$post = gutenberg_lab_blocks_mcp_get_villa_from_input( is_array( $input ) ? $input : array() );

	if ( is_wp_error( $post ) ) {
		return $post;
	}

	return array(
		'post_id' => (int) $post->ID,
		'tabs'    => gutenberg_lab_blocks_mcp_collect_stack_tabs( parse_blocks( $post->post_content ) ),
	);
}

/**
 * Removes empty freeform parser results from ability-provided block markup.
 *
 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
 * @return array<int, array<string, mixed>>
 */
function gutenberg_lab_blocks_mcp_remove_empty_freeform_blocks( $blocks ) {
	return array_values(
		array_filter(
			$blocks,
			static function ( $block ) {
				if ( ! is_array( $block ) ) {
					return false;
				}

				if ( null !== ( $block['blockName'] ?? null ) ) {
					return true;
				}

				return '' !== trim( wp_strip_all_tags( (string) ( $block['innerHTML'] ?? '' ) ) );
			}
		)
	);
}

/**
 * Validates block markup before it is inserted into a villa tab.
 *
 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
 * @return true|WP_Error
 */
function gutenberg_lab_blocks_mcp_validate_tab_blocks( $blocks ) {
	$allowed_blocks = array(
		'core/buttons',
		'core/button',
		'core/column',
		'core/columns',
		'core/group',
		'core/heading',
		'core/image',
		'core/list',
		'core/list-item',
		'core/paragraph',
		'core/separator',
		'core/table',
	);

	foreach ( $blocks as $block ) {
		$block_name = $block['blockName'] ?? null;

		if ( ! is_string( $block_name ) || '' === $block_name ) {
			return new WP_Error( 'unsupported_freeform_markup', __( 'Only serialized Gutenberg blocks are supported for tab content.', 'gutenberg-lab-blocks' ) );
		}

		if ( ! in_array( $block_name, $allowed_blocks, true ) ) {
			return new WP_Error(
				'unsupported_block',
				sprintf(
					/* translators: %s: Block name. */
					__( 'The block "%s" is not allowed in villa tab MCP updates.', 'gutenberg-lab-blocks' ),
					$block_name
				)
			);
		}

		$inner_blocks = gutenberg_lab_blocks_mcp_remove_empty_freeform_blocks( (array) ( $block['innerBlocks'] ?? array() ) );

		if ( ! empty( $inner_blocks ) ) {
			$inner_validation = gutenberg_lab_blocks_mcp_validate_tab_blocks( $inner_blocks );

			if ( is_wp_error( $inner_validation ) ) {
				return $inner_validation;
			}
		}
	}

	return true;
}

/**
 * Builds innerContent placeholders for a parsed block with replaced children.
 *
 * @param int $block_count Number of child blocks.
 * @return array<int, string|null>
 */
function gutenberg_lab_blocks_mcp_build_inner_content_placeholders( $block_count ) {
	$inner_content = array( "\n" );

	for ( $index = 0; $index < $block_count; $index++ ) {
		$inner_content[] = null;
		$inner_content[] = "\n";
	}

	return $inner_content;
}

/**
 * Replaces one named Stack Tab in a parsed block tree.
 *
 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
 * @param string                           $tab_label Target tab label.
 * @param array<int, array<string, mixed>> $new_blocks New tab content blocks.
 * @param bool                             $replaced Whether a block has already been replaced.
 * @return array<int, array<string, mixed>>
 */
function gutenberg_lab_blocks_mcp_replace_stack_tab_in_blocks( $blocks, $tab_label, $new_blocks, &$replaced ) {
	$target_label = strtolower( trim( $tab_label ) );

	foreach ( $blocks as $block_index => $block ) {
		if ( $replaced || ! is_array( $block ) ) {
			continue;
		}

		if ( 'gutenberg-lab-blocks/stack-tabs' === ( $block['blockName'] ?? '' ) ) {
			foreach ( (array) ( $block['innerBlocks'] ?? array() ) as $tab_index => $tab_block ) {
				$current_label = strtolower( trim( (string) ( $tab_block['attrs']['label'] ?? '' ) ) );

				if ( 'gutenberg-lab-blocks/stack-tab' !== ( $tab_block['blockName'] ?? '' ) || $current_label !== $target_label ) {
					continue;
				}

				$blocks[ $block_index ]['innerBlocks'][ $tab_index ]['innerBlocks']  = $new_blocks;
				$blocks[ $block_index ]['innerBlocks'][ $tab_index ]['innerContent'] = gutenberg_lab_blocks_mcp_build_inner_content_placeholders( count( $new_blocks ) );
				$replaced = true;
				break;
			}
		}

		if ( ! $replaced && ! empty( $block['innerBlocks'] ) ) {
			$blocks[ $block_index ]['innerBlocks'] = gutenberg_lab_blocks_mcp_replace_stack_tab_in_blocks(
				(array) $block['innerBlocks'],
				$tab_label,
				$new_blocks,
				$replaced
			);
		}
	}

	return $blocks;
}

/**
 * Executes the Stack Tab replacement ability.
 *
 * @param array<string, mixed> $input Ability input.
 * @return array<string, mixed>|WP_Error
 */
function gutenberg_lab_blocks_mcp_replace_villa_stack_tab_content( $input ) {
	$input = is_array( $input ) ? $input : array();
	$post  = gutenberg_lab_blocks_mcp_get_villa_from_input( $input );

	if ( is_wp_error( $post ) ) {
		return $post;
	}

	$tab_label    = sanitize_text_field( (string) ( $input['tab_label'] ?? '' ) );
	$block_markup = (string) ( $input['block_markup'] ?? '' );

	if ( '' === $tab_label || '' === trim( $block_markup ) ) {
		return new WP_Error( 'missing_tab_content', __( 'A tab label and block markup are required.', 'gutenberg-lab-blocks' ) );
	}

	$new_blocks = gutenberg_lab_blocks_mcp_remove_empty_freeform_blocks( parse_blocks( $block_markup ) );

	if ( empty( $new_blocks ) ) {
		return new WP_Error( 'empty_block_markup', __( 'The block markup did not contain any Gutenberg blocks.', 'gutenberg-lab-blocks' ) );
	}

	$validation = gutenberg_lab_blocks_mcp_validate_tab_blocks( $new_blocks );

	if ( is_wp_error( $validation ) ) {
		return $validation;
	}

	$replaced       = false;
	$updated_blocks = gutenberg_lab_blocks_mcp_replace_stack_tab_in_blocks(
		parse_blocks( $post->post_content ),
		$tab_label,
		$new_blocks,
		$replaced
	);

	if ( ! $replaced ) {
		return new WP_Error( 'tab_not_found', __( 'The requested Stack Tab label was not found on this villa.', 'gutenberg-lab-blocks' ) );
	}

	$update_result = wp_update_post(
		array(
			'ID'           => $post->ID,
			'post_content' => serialize_blocks( $updated_blocks ),
		),
		true
	);

	if ( is_wp_error( $update_result ) ) {
		return $update_result;
	}

	return array(
		'post_id'     => (int) $post->ID,
		'tab_label'   => $tab_label,
		'block_count' => count( $new_blocks ),
		'updated'     => true,
	);
}
