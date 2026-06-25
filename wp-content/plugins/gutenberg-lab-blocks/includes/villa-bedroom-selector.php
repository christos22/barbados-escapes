<?php
/**
 * Villa bedroom choice helpers and Contact Form 7 integration.
 *
 * @package GutenbergLabBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks whether bedroom selection is enabled for a villa.
 *
 * A villa must explicitly opt in. Missing meta means the bedroom selectors are
 * disabled.
 *
 * @param int $villa_id Villa post ID.
 * @return bool
 */
function gutenberg_lab_blocks_is_villa_bedroom_selector_enabled( $villa_id ) {
	$villa_id = absint( $villa_id );

	if (
		! $villa_id ||
		'villa' !== get_post_type( $villa_id )
	) {
		return false;
	}

	if (
		! metadata_exists(
			'post',
			$villa_id,
			'villa_bedroom_selector_enabled'
		)
	) {
		return false;
	}

	return rest_sanitize_boolean(
		get_post_meta(
			$villa_id,
			'villa_bedroom_selector_enabled',
			true
		)
	);
}

/**
 * Sanitizes one editor-managed bedroom choice label.
 *
 * @param mixed $label Raw choice label.
 * @return string
 */
function gutenberg_lab_blocks_sanitize_villa_bedroom_choice_label( $label ) {
	$label = sanitize_text_field( (string) $label );
	$label = trim( preg_replace( '/\s+/', ' ', $label ) );

	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( $label, 0, 120 );
	}

	return substr( $label, 0, 120 );
}

/**
 * Sanitizes editor-managed bedroom choices.
 *
 * New choices are custom text labels. Legacy numeric bedroom/sleeps rows are
 * still accepted so existing block content keeps rendering.
 *
 * @param mixed $rows Raw block attribute value.
 * @return array<int, array{label:string}>
 */
function gutenberg_lab_blocks_sanitize_villa_bedroom_choice_rows( $rows ) {
	$choices = array();
	$seen    = array();

	if ( ! is_array( $rows ) ) {
		return $choices;
	}

	foreach ( $rows as $row ) {
		if ( count( $choices ) >= 30 ) {
			break;
		}

		$label = '';

		if ( is_scalar( $row ) ) {
			$label = gutenberg_lab_blocks_sanitize_villa_bedroom_choice_label( $row );
		} elseif ( is_array( $row ) ) {
			if ( isset( $row['label'] ) ) {
				$label = gutenberg_lab_blocks_sanitize_villa_bedroom_choice_label(
					$row['label']
				);
			} else {
				$bedrooms = min( 30, absint( $row['bedrooms'] ?? 0 ) );

				if ( $bedrooms > 0 ) {
					$label = gutenberg_lab_blocks_format_villa_bedroom_choice(
						$bedrooms,
						min( 100, absint( $row['sleeps'] ?? 0 ) )
					);
				}
			}
		}

		$dedupe_key = strtolower( $label );

		if ( '' === $label || isset( $seen[ $dedupe_key ] ) ) {
			continue;
		}

		$choices[]           = array( 'label' => $label );
		$seen[ $dedupe_key ] = true;
	}

	return $choices;
}

/**
 * Formats one visitor-facing bedroom choice.
 *
 * @param int $bedrooms Bedroom count.
 * @param int $sleeps   Optional guest capacity.
 * @return string
 */
function gutenberg_lab_blocks_format_villa_bedroom_choice( $bedrooms, $sleeps = 0 ) {
	$label = sprintf(
		_n( '%d Bedroom', '%d Bedrooms', $bedrooms, 'gutenberg-lab-blocks' ),
		$bedrooms
	);

	if ( $sleeps > 0 ) {
		$label .= sprintf(
			/* translators: %d is the number of guests. */
			__( ' (sleeps %d)', 'gutenberg-lab-blocks' ),
			$sleeps
		);
	}

	return $label;
}

/**
 * Returns a stable pricing key for one bedroom count.
 *
 * @param int $bedrooms Bedroom count.
 * @return string
 */
function gutenberg_lab_blocks_get_villa_bedroom_pricing_key( $bedrooms ) {
	$bedrooms = absint( $bedrooms );

	return $bedrooms ? 'bedrooms-' . $bedrooms : '';
}

/**
 * Extracts a bedroom count from legacy labels.
 *
 * @param string $label Bedroom or rate label.
 * @return int
 */
function gutenberg_lab_blocks_get_villa_bedroom_count_from_label( $label ) {
	if (
		preg_match(
			'/(?:^|[^\d])(\d+)\s*[-–—]?\s*bedrooms?\b/i',
			(string) $label,
			$matches
		)
	) {
		return absint( $matches[1] );
	}

	return 0;
}

/**
 * Builds a pricing key from a selector or legacy rate label.
 *
 * @param string $label Bedroom or rate label.
 * @return string
 */
function gutenberg_lab_blocks_get_villa_bedroom_pricing_key_from_label( $label ) {
	$bedrooms = gutenberg_lab_blocks_get_villa_bedroom_count_from_label( $label );

	if ( $bedrooms ) {
		return gutenberg_lab_blocks_get_villa_bedroom_pricing_key( $bedrooms );
	}

	$slug = sanitize_title( $label );

	return $slug ? 'choice-' . $slug : '';
}

/**
 * Returns labels and pricing keys for a villa's bedroom selector.
 *
 * @param int      $villa_id         Villa post ID.
 * @param int|null $minimum_override Optional block-level minimum.
 * @return array<int, array{label:string,pricingKey:string}>
 */
function gutenberg_lab_blocks_get_villa_bedroom_choice_data( $villa_id, $minimum_override = null ) {
	$data     = gutenberg_lab_blocks_get_villa_booking_data( $villa_id );
	$maximum  = (int) $data['bedrooms'];
	$minimum  = null === $minimum_override
		? (int) $data['minimum_bedrooms']
		: max( 1, min( $maximum, absint( $minimum_override ) ) );
	$choices  = array();

	if ( ! empty( $data['has_custom_bedroom_choices'] ) ) {
		foreach ( $data['bedroom_choices'] as $choice ) {
			$label = (string) $choice['label'];

			$choices[] = array(
				'label'      => $label,
				'pricingKey' => gutenberg_lab_blocks_get_villa_bedroom_pricing_key_from_label( $label ),
			);
		}

		return $choices;
	}

	if ( $maximum < 1 ) {
		return $choices;
	}

	for ( $bedrooms = $maximum; $bedrooms >= $minimum; --$bedrooms ) {
		$choices[] = array(
			'label'      => gutenberg_lab_blocks_format_villa_bedroom_choice(
				$bedrooms
			),
			'pricingKey' => gutenberg_lab_blocks_get_villa_bedroom_pricing_key(
				$bedrooms
			),
		);
	}

	return $choices;
}

/**
 * Serializes trusted selector attributes.
 *
 * @param array<string, mixed> $attributes Attribute map.
 * @return string
 */
function gutenberg_lab_blocks_get_villa_bedroom_selector_attributes( $attributes ) {
	$markup = '';

	foreach ( $attributes as $name => $value ) {
		if (
			! is_string( $name ) ||
			! preg_match( '/^[a-zA-Z_:][-a-zA-Z0-9_:.]*$/', $name ) ||
			false === $value ||
			null === $value
		) {
			continue;
		}

		if ( true === $value || '' === $value ) {
			$markup .= ' ' . $name;
			continue;
		}

		$markup .= sprintf(
			' %1$s="%2$s"',
			$name,
			esc_attr( (string) $value )
		);
	}

	return trim( $markup );
}

/**
 * Returns option markup for a villa bedroom select.
 *
 * @param array<int, string> $choices Bedroom choice labels.
 * @return string
 */
function gutenberg_lab_blocks_render_villa_bedroom_choice_options( $choices ) {
	$markup = '';

	foreach ( $choices as $choice ) {
		$label       = is_array( $choice ) ? (string) ( $choice['label'] ?? '' ) : (string) $choice;
		$pricing_key = is_array( $choice )
			? (string) ( $choice['pricingKey'] ?? '' )
			: gutenberg_lab_blocks_get_villa_bedroom_pricing_key_from_label( $label );

		if ( '' === $label ) {
			continue;
		}

		$attributes = array( 'value' => $label );

		if ( '' !== $pricing_key ) {
			$attributes['data-vvm-bedroom-pricing-key'] = $pricing_key;
		}

		$markup .= sprintf(
			'<option %1$s>%2$s</option>',
			gutenberg_lab_blocks_get_villa_bedroom_selector_attributes( $attributes ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html( $label )
		);
	}

	return $markup;
}

/**
 * Enqueues selector assets when markup is injected outside the block parser.
 */
function gutenberg_lab_blocks_enqueue_villa_bedroom_selector_assets() {
	$block_type = WP_Block_Type_Registry::get_instance()->get_registered(
		'gutenberg-lab-blocks/villa-bedroom-selector'
	);

	if ( ! $block_type instanceof WP_Block_Type ) {
		return;
	}

	foreach ( (array) $block_type->style_handles as $style_handle ) {
		wp_enqueue_style( $style_handle );
	}

	foreach ( (array) $block_type->view_script_handles as $script_handle ) {
		wp_enqueue_script( $script_handle );
	}
}

/**
 * Enqueues selector assets for enabled villa pages even when legacy content is
 * missing the selector block.
 */
function gutenberg_lab_blocks_enqueue_villa_bedroom_selector_assets_for_current_villa() {
	if ( ! is_singular( 'villa' ) ) {
		return;
	}

	$villa_id = absint( get_queried_object_id() );

	if ( gutenberg_lab_blocks_should_render_villa_bedroom_selector( $villa_id ) ) {
		gutenberg_lab_blocks_enqueue_villa_bedroom_selector_assets();
	}
}
add_action(
	'wp_enqueue_scripts',
	'gutenberg_lab_blocks_enqueue_villa_bedroom_selector_assets_for_current_villa',
	20
);

/**
 * Renders the visitor-facing pricing selector.
 *
 * @param int             $villa_id           Villa post ID.
 * @param int|null        $minimum_override   Optional minimum bedroom override.
 * @param array|string    $wrapper_attributes Wrapper attributes or a prepared attribute string.
 * @return string
 */
function gutenberg_lab_blocks_render_villa_bedroom_selector( $villa_id, $minimum_override = null, $wrapper_attributes = array() ) {
	$villa_id = absint( $villa_id );

	if (
		! $villa_id ||
		! gutenberg_lab_blocks_is_villa_bedroom_selector_enabled( $villa_id )
	) {
		return '';
	}

	$choices = gutenberg_lab_blocks_get_villa_bedroom_choice_data(
		$villa_id,
		$minimum_override
	);

	if ( empty( $choices ) ) {
		return '';
	}

	gutenberg_lab_blocks_enqueue_villa_bedroom_selector_assets();

	if ( is_array( $wrapper_attributes ) ) {
		$classes = preg_split(
			'/\s+/',
			(string) ( $wrapper_attributes['class'] ?? '' ),
			-1,
			PREG_SPLIT_NO_EMPTY
		);
		$classes[] = 'vvm-villa-bedroom-selector';

		$wrapper_attributes['class']                          = implode(
			' ',
			array_unique( $classes )
		);
		$wrapper_attributes['data-vvm-bedroom-selector-root'] = '';
		$wrapper_attributes = gutenberg_lab_blocks_get_villa_bedroom_selector_attributes(
			$wrapper_attributes
		);
	}

	if ( ! is_string( $wrapper_attributes ) || '' === trim( $wrapper_attributes ) ) {
		$wrapper_attributes = gutenberg_lab_blocks_get_villa_bedroom_selector_attributes(
			array(
				'class'                          => 'vvm-villa-bedroom-selector',
				'data-vvm-bedroom-selector-root' => '',
			)
		);
	}

	$select_id = wp_unique_id( 'vvm-villa-bedroom-selector-' );

	return sprintf(
		'<div %1$s><label class="screen-reader-text" for="%2$s">%3$s</label><select id="%2$s" class="vvm-villa-bedroom-selector__select" data-vvm-bedroom-selector>%4$s</select></div>',
		$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		esc_attr( $select_id ),
		esc_html__( 'Bedrooms for seasonal pricing', 'gutenberg-lab-blocks' ),
		gutenberg_lab_blocks_render_villa_bedroom_choice_options( $choices ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
}

/**
 * Collects booking data from structured villa blocks.
 *
 * The visible Villa Specs block supplies the fallback bedroom range. Explicit
 * bedroom choices can override that range when a villa needs bespoke labels.
 *
 * @param array<int, array<string, mixed>> $blocks Parsed Gutenberg blocks.
 * @param array<string, mixed>             $data   Collected booking data.
 */
function gutenberg_lab_blocks_collect_villa_booking_data( $blocks, &$data ) {
	foreach ( $blocks as $parsed_block ) {
		$block_name = (string) ( $parsed_block['blockName'] ?? '' );
		$attributes = (array) ( $parsed_block['attrs'] ?? array() );

		if ( 'gutenberg-lab-blocks/villa-spec-item' === $block_name ) {
			$label = strtolower(
				trim( wp_strip_all_tags( (string) ( $attributes['label'] ?? '' ) ) )
			);
			$value = absint(
				wp_strip_all_tags( (string) ( $attributes['value'] ?? '' ) )
			);

			if ( $value && preg_match( '/^bedrooms?$/', $label ) ) {
				$data['bedrooms'] = min( 30, $value );
			}
		}

		if ( 'gutenberg-lab-blocks/villa-bedroom-selector' === $block_name ) {
			$data['minimum_bedrooms'] = max(
				1,
				min( 30, absint( $attributes['minimumBedrooms'] ?? 1 ) )
			);

			$raw_custom_choices = $attributes['bedroomChoices'] ?? array();

			if ( is_array( $raw_custom_choices ) && ! empty( $raw_custom_choices ) ) {
				$data['has_custom_bedroom_choices'] = true;
				$data['bedroom_choices']            = gutenberg_lab_blocks_sanitize_villa_bedroom_choice_rows(
					$raw_custom_choices
				);
			}
		}

		if ( ! empty( $parsed_block['innerBlocks'] ) ) {
			gutenberg_lab_blocks_collect_villa_booking_data(
				$parsed_block['innerBlocks'],
				$data
			);
		}
	}
}

/**
 * Returns bedroom selector settings for a villa.
 *
 * @param int $villa_id Villa post ID.
 * @return array{
 *     bedrooms:int,
 *     minimum_bedrooms:int,
 *     has_custom_bedroom_choices:bool,
 *     bedroom_choices:array<int, array{label:string}>
 * }
 */
function gutenberg_lab_blocks_get_villa_booking_data( $villa_id ) {
	static $cache = array();

	$villa_id = absint( $villa_id );

	if (
		! $villa_id ||
		'villa' !== get_post_type( $villa_id )
	) {
		return array(
			'bedrooms'                   => 0,
			'minimum_bedrooms'           => 1,
			'has_custom_bedroom_choices' => false,
			'bedroom_choices'            => array(),
		);
	}

	if ( isset( $cache[ $villa_id ] ) ) {
		return $cache[ $villa_id ];
	}

	$data = array(
		'bedrooms'                   => 0,
		'minimum_bedrooms'           => 1,
		'has_custom_bedroom_choices' => false,
		'bedroom_choices'            => array(),
	);
	$post = get_post( $villa_id );

	if ( $post instanceof WP_Post ) {
		gutenberg_lab_blocks_collect_villa_booking_data(
			parse_blocks( $post->post_content ),
			$data
		);
	}

	$meta_choices = gutenberg_lab_blocks_sanitize_villa_bedroom_choice_rows(
		get_post_meta( $villa_id, 'villa_bedroom_selector_choices', true )
	);

	if ( ! empty( $meta_choices ) ) {
		$data['has_custom_bedroom_choices'] = true;
		$data['bedroom_choices']            = $meta_choices;
	}

	$data['minimum_bedrooms'] = min(
		max( 1, $data['minimum_bedrooms'] ),
		max( 1, $data['bedrooms'] )
	);
	$cache[ $villa_id ]       = $data;

	return $data;
}

/**
 * Returns allowed bedroom labels.
 *
 * @param int      $villa_id         Villa post ID.
 * @param int|null $minimum_override Optional block-level minimum.
 * @return array<int, string>
 */
function gutenberg_lab_blocks_get_villa_bedroom_choices( $villa_id, $minimum_override = null ) {
	$choices = array();

	foreach ( gutenberg_lab_blocks_get_villa_bedroom_choice_data( $villa_id, $minimum_override ) as $choice ) {
		$choices[] = (string) $choice['label'];
	}

	return $choices;
}

/**
 * Checks whether a villa has an active bedroom choice list.
 *
 * This gates both selectors and CF7 posted data. If the feature is disabled,
 * or the editor has no valid choices, the bedroom field should disappear and
 * submitted bedroom data should not be trusted.
 *
 * @param int $villa_id Villa post ID.
 * @return bool
 */
function gutenberg_lab_blocks_should_render_villa_bedroom_selector( $villa_id ) {
	return (
		gutenberg_lab_blocks_is_villa_bedroom_selector_enabled( $villa_id ) &&
		! empty( gutenberg_lab_blocks_get_villa_bedroom_choices( $villa_id ) )
	);
}

/**
 * Injects a pricing selector into legacy villa pricing groups when enabled.
 *
 * @param string               $block_content Rendered block markup.
 * @param array<string, mixed> $block         Parsed block.
 * @return string
 */
function gutenberg_lab_blocks_inject_villa_bedroom_selector_block( $block_content, $block ) {
	$class_name = (string) ( $block['attrs']['className'] ?? '' );
	$has_selector = str_contains( $block_content, 'data-vvm-bedroom-selector' );

	if (
		'core/group' !== ( $block['blockName'] ?? '' ) ||
		! preg_match( '/(?:^|\s)vvm-villa-pricing__intro(?:\s|$)/', $class_name )
	) {
		return $block_content;
	}

	$selector = '';

	if ( ! $has_selector ) {
		$villa_id = gutenberg_lab_blocks_resolve_villa_booking_post_id(
			absint( $block['context']['postId'] ?? 0 )
		);

		if ( ! gutenberg_lab_blocks_should_render_villa_bedroom_selector( $villa_id ) ) {
			return $block_content;
		}

		$selector = gutenberg_lab_blocks_render_villa_bedroom_selector( $villa_id );

		if ( '' === $selector ) {
			return $block_content;
		}
	}

	$processor = new WP_HTML_Tag_Processor( $block_content );

	if ( $processor->next_tag() ) {
		$processor->add_class( 'vvm-villa-pricing__intro--has-bedroom-selector' );
		$block_content = $processor->get_updated_html();
	}

	if ( $has_selector ) {
		return $block_content;
	}

	$closing_position = strripos( $block_content, '</div>' );

	if ( false === $closing_position ) {
		return $block_content;
	}

	return substr_replace(
		$block_content,
		$selector,
		$closing_position,
		0
	);
}
add_filter(
	'render_block',
	'gutenberg_lab_blocks_inject_villa_bedroom_selector_block',
	20,
	2
);

/**
 * Adds pricing keys to legacy saved villa pricing table rows.
 *
 * Newly imported rows include these attributes in saved content. This render
 * backfill keeps older tables on the same frontend data contract.
 *
 * @param string               $block_content Rendered block markup.
 * @param array<string, mixed> $block         Parsed block.
 * @return string
 */
function gutenberg_lab_blocks_add_villa_pricing_row_keys( $block_content, $block ) {
	$class_name = (string) ( $block['attrs']['className'] ?? '' );

	if (
		'core/table' !== ( $block['blockName'] ?? '' ) ||
		(
			! preg_match( '/(?:^|\s)vvm-villa-pricing__table(?:\s|$)/', $class_name ) &&
			! str_contains( $block_content, 'vvm-villa-pricing__table' )
		)
	) {
		return $block_content;
	}

	$updated = preg_replace_callback(
		'~<tr\b([^>]*)>(.*?)</tr>~is',
		static function ( $matches ) {
			if ( preg_match( '/\bdata-vvm-bedroom-pricing-key\s*=/i', $matches[1] ) ) {
				return $matches[0];
			}

			if ( ! preg_match( '~<td\b[^>]*>(.*?)</td>~is', $matches[2], $cell_matches ) ) {
				return $matches[0];
			}

			$bedrooms = gutenberg_lab_blocks_get_villa_bedroom_count_from_label(
				html_entity_decode(
					wp_strip_all_tags( $cell_matches[1] ),
					ENT_QUOTES | ENT_HTML5,
					get_bloginfo( 'charset' ) ?: 'UTF-8'
				)
			);

			if ( ! $bedrooms ) {
				return $matches[0];
			}

			return sprintf(
				'<tr%1$s data-vvm-bedroom-pricing-key="%2$s">%3$s</tr>',
				$matches[1],
				esc_attr( gutenberg_lab_blocks_get_villa_bedroom_pricing_key( $bedrooms ) ),
				$matches[2]
			);
		},
		$block_content
	);

	return null === $updated ? $block_content : $updated;
}
add_filter(
	'render_block',
	'gutenberg_lab_blocks_add_villa_pricing_row_keys',
	20,
	2
);

/**
 * Resolves a villa ID during page rendering or a CF7 REST submission.
 *
 * @param int $preferred_id Preferred villa post ID.
 * @return int
 */
function gutenberg_lab_blocks_resolve_villa_booking_post_id( $preferred_id = 0 ) {
	$candidates = array( absint( $preferred_id ) );

	if ( isset( $_POST['_wpcf7_container_post'] ) ) {
		$candidates[] = absint( wp_unslash( $_POST['_wpcf7_container_post'] ) );
	}

	if ( isset( $_POST['villa-id'] ) ) {
		$candidates[] = absint( wp_unslash( $_POST['villa-id'] ) );
	}

	$candidates[] = absint( get_queried_object_id() );
	$candidates[] = absint( get_the_ID() );

	foreach ( array_unique( $candidates ) as $candidate ) {
		if ( $candidate && 'villa' === get_post_type( $candidate ) ) {
			return $candidate;
		}
	}

	return 0;
}

/**
 * Replaces the CF7 bedroom field's placeholder option with villa choices.
 *
 * @param array<string, mixed> $tag      Scanned CF7 form tag.
 * @param bool                 $_replace Whether CF7 is currently rendering.
 * @return array<string, mixed>
 */
function gutenberg_lab_blocks_filter_cf7_villa_bedroom_tag( $tag, $_replace ) {
	if (
		! is_array( $tag ) ||
		'villa-bedrooms' !== ( $tag['name'] ?? '' )
	) {
		return $tag;
	}

	$villa_id = gutenberg_lab_blocks_resolve_villa_booking_post_id();
	$choices  = gutenberg_lab_blocks_get_villa_bedroom_choices( $villa_id );

	/*
	 * CF7 builds its browser-validation schema without page context. Making
	 * this one scanned tag optional prevents a disabled villa from inheriting
	 * a generic required rule; the villa-aware validator below remains the
	 * server-side authority when the feature is enabled.
	 */
	if (
		! $_replace ||
		(
			$villa_id &&
			! gutenberg_lab_blocks_should_render_villa_bedroom_selector( $villa_id )
		)
	) {
		$tag['type'] = 'select';
	}

	/*
	 * CF7 fetches its browser-validation schema without page/post context.
	 * Give that schema a bounded generic allowlist; page rendering and submit
	 * validation still use the current villa's smaller, exact choice set.
	 */
	if ( empty( $choices ) && ! $villa_id ) {
		$choices = array_map(
			'gutenberg_lab_blocks_format_villa_bedroom_choice',
			range( 1, 30 )
		);
	}

	// The first real bedroom choice is the default; there is no neutral placeholder.
	$tag['raw_values'] = $choices;
	$tag['values']     = $choices;
	$tag['labels']     = $choices;
	$tag['pipes']      = null;

	return $tag;
}
add_filter(
	'wpcf7_form_tag',
	'gutenberg_lab_blocks_filter_cf7_villa_bedroom_tag',
	20,
	2
);

/**
 * Renders the villa bedroom select row for forms without a stored CF7 tag.
 *
 * @param int $villa_id Villa post ID.
 * @return string
 */
function gutenberg_lab_blocks_render_cf7_villa_bedroom_field( $villa_id ) {
	if ( ! gutenberg_lab_blocks_should_render_villa_bedroom_selector( $villa_id ) ) {
		return '';
	}

	$choices = gutenberg_lab_blocks_get_villa_bedroom_choice_data( $villa_id );

	if ( empty( $choices ) ) {
		return '';
	}

	gutenberg_lab_blocks_enqueue_villa_bedroom_selector_assets();

	return sprintf(
		'<p data-name="villa-bedrooms"><label class="screen-reader-text" for="villa-bedrooms">%1$s</label><span class="wpcf7-form-control-wrap" data-name="villa-bedrooms"><select id="villa-bedrooms" name="villa-bedrooms" class="wpcf7-form-control wpcf7-select vvm-villa-contact-form__field" aria-required="true" aria-invalid="false">%2$s</select></span></p>',
		esc_html__( 'Bedrooms', 'gutenberg-lab-blocks' ),
		gutenberg_lab_blocks_render_villa_bedroom_choice_options( $choices ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
}

/**
 * Removes the form's bedroom row when the current villa disables the feature.
 *
 * CF7 does not expose a form-tag removal API. The pattern is deliberately
 * limited to one paragraph containing its generated villa-bedrooms wrapper.
 *
 * @param string $html Rendered CF7 form HTML.
 * @return string
 */
function gutenberg_lab_blocks_filter_cf7_villa_bedroom_elements( $html ) {
	$villa_id = gutenberg_lab_blocks_resolve_villa_booking_post_id();
	$has_bedroom_field = preg_match(
		'/\bname=(["\'])villa-bedrooms\1/i',
		$html
	);

	if (
		! $villa_id ||
		gutenberg_lab_blocks_should_render_villa_bedroom_selector( $villa_id )
	) {
		if ( ! $villa_id || $has_bedroom_field ) {
			return $html;
		}

		$field = gutenberg_lab_blocks_render_cf7_villa_bedroom_field( $villa_id );

		if ( '' === $field ) {
			return $html;
		}

		if (
			preg_match(
				'~<p\b[^>]*>(?:(?!</p>).)*?\bfor=(["\'])your-name\1(?:(?!</p>).)*?</p>~is',
				$html,
				$matches,
				PREG_OFFSET_CAPTURE
			)
		) {
			return substr_replace(
				$html,
				$field,
				(int) $matches[0][1],
				0
			);
		}

		if (
			preg_match(
				'~<div\b[^>]*class=(["\'])(?:(?!\1).)*\bvvm-villa-contact-form__grid\b(?:(?!\1).)*\1[^>]*>~i',
				$html,
				$matches,
				PREG_OFFSET_CAPTURE
			)
		) {
			$grid_start = (int) $matches[0][1];
			$grid_close = stripos( $html, '</div>', $grid_start );

			if ( false !== $grid_close ) {
				return substr_replace( $html, $field, $grid_close, 0 );
			}
		}

		return $html;
	}

	$filtered_html = preg_replace(
		'~<p\b[^>]*>(?:(?!</p>).)*?\bdata-name=(["\'])villa-bedrooms\1(?:(?!</p>).)*?</p>~is',
		'',
		$html,
		1
	);

	return null === $filtered_html ? $html : $filtered_html;
}
add_filter(
	'wpcf7_form_elements',
	'gutenberg_lab_blocks_filter_cf7_villa_bedroom_elements',
	20
);

/**
 * Removes inactive bedroom values from CF7 mail data.
 *
 * A hidden or disabled bedroom feature should not accept attacker-supplied
 * fields, even when the raw request includes a villa-bedrooms value.
 *
 * @param array<string, mixed> $posted_data Sanitized CF7 posted data.
 * @return array<string, mixed>
 */
function gutenberg_lab_blocks_filter_cf7_villa_bedroom_posted_data( $posted_data ) {
	$villa_id = gutenberg_lab_blocks_resolve_villa_booking_post_id();

	if (
		$villa_id &&
		! gutenberg_lab_blocks_should_render_villa_bedroom_selector( $villa_id )
	) {
		unset( $posted_data['villa-bedrooms'] );
	}

	return $posted_data;
}
add_filter(
	'wpcf7_posted_data',
	'gutenberg_lab_blocks_filter_cf7_villa_bedroom_posted_data',
	20
);

/**
 * Validates injected bedroom fields when the CF7 form has no saved tag.
 *
 * @param WPCF7_Validation       $result Current validation result.
 * @param array<int, WPCF7_FormTag> $tags Scanned form tags.
 * @return WPCF7_Validation
 */
function gutenberg_lab_blocks_validate_cf7_injected_villa_bedrooms( $result, $tags ) {
	foreach ( $tags as $tag ) {
		if (
			$tag instanceof WPCF7_FormTag &&
			'villa-bedrooms' === $tag->name
		) {
			return $result;
		}
	}

	$submitted = isset( $_POST['villa-bedrooms'] )
		? sanitize_text_field( wp_unslash( $_POST['villa-bedrooms'] ) )
		: '';
	$villa_id = gutenberg_lab_blocks_resolve_villa_booking_post_id();

	if (
		! $villa_id ||
		! gutenberg_lab_blocks_should_render_villa_bedroom_selector( $villa_id )
	) {
		return $result;
	}

	$choices = gutenberg_lab_blocks_get_villa_bedroom_choices( $villa_id );

	if ( ! in_array( $submitted, $choices, true ) ) {
		$result->invalidate(
			array(
				'type'    => 'select',
				'name'    => 'villa-bedrooms',
				'options' => array( 'id:villa-bedrooms' ),
			),
			__( 'Please choose a valid bedroom option.', 'gutenberg-lab-blocks' )
		);
	}

	return $result;
}
add_filter(
	'wpcf7_validate',
	'gutenberg_lab_blocks_validate_cf7_injected_villa_bedrooms',
	30,
	2
);

/**
 * Adds the selected bedroom label to villa enquiry emails when the form body
 * does not already include it.
 *
 * @param array<string, mixed> $components   Mail components.
 * @param WPCF7_ContactForm    $contact_form Current form.
 * @return array<string, mixed>
 */
function gutenberg_lab_blocks_add_villa_bedrooms_to_mail( $components, $contact_form ) {
	$submission = class_exists( 'WPCF7_Submission' )
		? WPCF7_Submission::get_instance()
		: null;

	if ( ! $submission instanceof WPCF7_Submission ) {
		return $components;
	}

	$villa_id = absint( $submission->get_meta( 'container_post_id' ) );

	if ( ! gutenberg_lab_blocks_should_render_villa_bedroom_selector( $villa_id ) ) {
		return $components;
	}

	$submitted = (string) $submission->get_posted_data( 'villa-bedrooms' );
	$choices   = gutenberg_lab_blocks_get_villa_bedroom_choices( $villa_id );

	if (
		! in_array( $submitted, $choices, true ) ||
		empty( $components['body'] ) ||
		! is_string( $components['body'] ) ||
		false !== stripos( $components['body'], 'Bedrooms:' )
	) {
		return $components;
	}

	$line = sprintf(
		/* translators: %s is the selected bedroom label. */
		__( 'Bedrooms: %s', 'gutenberg-lab-blocks' ),
		$submitted
	) . "\n";

	$updated_body = preg_replace(
		'/^(Preferred departure date:[^\r\n]*(?:\r?\n))/m',
		'$1' . $line,
		$components['body'],
		1
	);

	$components['body'] = null === $updated_body
		? $components['body'] . "\n" . $line
		: $updated_body;

	return $components;
}
add_filter(
	'wpcf7_mail_components',
	'gutenberg_lab_blocks_add_villa_bedrooms_to_mail',
	20,
	2
);

/**
 * Rejects missing, malformed, or out-of-range bedroom submissions.
 *
 * CF7 also creates an enum rule from the filtered options. This explicit
 * server-side check keeps the trust boundary clear if client validation is
 * bypassed or disabled.
 *
 * @param WPCF7_Validation $result Current validation result.
 * @param WPCF7_FormTag    $tag    Current form tag.
 * @return WPCF7_Validation
 */
function gutenberg_lab_blocks_validate_cf7_villa_bedrooms( $result, $tag ) {
	if ( ! $tag instanceof WPCF7_FormTag && class_exists( 'WPCF7_FormTag' ) ) {
		$tag = new WPCF7_FormTag( $tag );
	}

	if (
		! $tag instanceof WPCF7_FormTag ||
		'villa-bedrooms' !== $tag->name
	) {
		return $result;
	}

	$submitted = isset( $_POST['villa-bedrooms'] )
		? sanitize_text_field( wp_unslash( $_POST['villa-bedrooms'] ) )
		: '';
	$container_id = isset( $_POST['_wpcf7_container_post'] )
		? absint( wp_unslash( $_POST['_wpcf7_container_post'] ) )
		: 0;
	$submitted_villa_id = isset( $_POST['villa-id'] )
		? absint( wp_unslash( $_POST['villa-id'] ) )
		: 0;
	$has_mismatched_villa = (
		$container_id &&
		$submitted_villa_id &&
		$container_id !== $submitted_villa_id
	);
	$villa_id = gutenberg_lab_blocks_resolve_villa_booking_post_id();

	if ( $has_mismatched_villa ) {
		$result->invalidate(
			$tag,
			__( 'Please choose a valid bedroom option.', 'gutenberg-lab-blocks' )
		);

		return $result;
	}

	if (
		$villa_id &&
		! gutenberg_lab_blocks_should_render_villa_bedroom_selector( $villa_id )
	) {
		return $result;
	}

	$choices  = gutenberg_lab_blocks_get_villa_bedroom_choices( $villa_id );

	if ( ! in_array( $submitted, $choices, true ) ) {
		$result->invalidate(
			$tag,
			__( 'Please choose a valid bedroom option.', 'gutenberg-lab-blocks' )
		);
	}

	return $result;
}
add_filter(
	'wpcf7_validate_select',
	'gutenberg_lab_blocks_validate_cf7_villa_bedrooms',
	30,
	2
);
add_filter(
	'wpcf7_validate_select*',
	'gutenberg_lab_blocks_validate_cf7_villa_bedrooms',
	30,
	2
);
