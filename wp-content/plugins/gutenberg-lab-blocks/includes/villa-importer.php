<?php
/**
 * Draft-only villa workbook importer.
 *
 * The workbook parser normalizes client-friendly spreadsheet content into JSON.
 * This file owns WordPress validation, taxonomy/meta updates, and Gutenberg
 * markup so content rules stay close to the Villa CPT.
 *
 * @package GutenbergLabBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the supported villa workbook schema version.
 */
function gutenberg_lab_blocks_villa_importer_schema_version() {
	return '1.0';
}

/**
 * Escapes plain client copy for stored block HTML.
 *
 * Client wording is kept verbatim. We only normalize line endings and remove
 * markup because the workbook is a content source, not an HTML authoring tool.
 *
 * @param mixed $value Raw workbook value.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_text( $value ) {
	return trim( wp_strip_all_tags( str_replace( array( "\r\n", "\r" ), "\n", (string) $value ) ) );
}

/**
 * Normalizes a client Yes/No value to a boolean.
 *
 * @param mixed $value Raw workbook value.
 * @param bool  $default Fallback when no value was provided.
 * @return bool
 */
function gutenberg_lab_blocks_villa_importer_boolean( $value, $default = true ) {
	if ( is_bool( $value ) ) {
		return $value;
	}

	$value = strtolower( gutenberg_lab_blocks_villa_importer_text( $value ) );

	if ( 'yes' === $value || '1' === $value || 'true' === $value ) {
		return true;
	}

	if ( 'no' === $value || '0' === $value || 'false' === $value ) {
		return false;
	}

	return (bool) $default;
}

/**
 * Serializes block attributes for a Gutenberg comment.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_attributes( $attributes ) {
	if ( empty( $attributes ) ) {
		return '';
	}

	return ' ' . wp_json_encode( $attributes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
}

/**
 * Sanitizes one or more whitespace-separated CSS classes.
 *
 * @param mixed $class_names Raw class list.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_classes( $class_names ) {
	$classes = array();

	foreach ( preg_split( '/\s+/', trim( (string) $class_names ) ) as $class_name ) {
		$class_name = sanitize_html_class( $class_name );

		if ( '' !== $class_name ) {
			$classes[] = $class_name;
		}
	}

	return implode( ' ', array_unique( $classes ) );
}

/**
 * Wraps nested markup in a Gutenberg block comment.
 *
 * @param string               $block_name Block name without the wp: prefix.
 * @param array<string, mixed> $attributes Block attributes.
 * @param string               $inner_markup Serialized child markup.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_block( $block_name, $attributes, $inner_markup ) {
	return sprintf(
		"<!-- wp:%1\$s%2\$s -->\n%3\$s\n<!-- /wp:%1\$s -->",
		$block_name,
		gutenberg_lab_blocks_villa_importer_attributes( $attributes ),
		$inner_markup
	);
}

/**
 * Builds a self-closing dynamic Gutenberg block.
 *
 * @param string               $block_name Block name without the wp: prefix.
 * @param array<string, mixed> $attributes Block attributes.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_self_closing_block( $block_name, $attributes = array() ) {
	return sprintf(
		'<!-- wp:%1$s%2$s /-->',
		$block_name,
		gutenberg_lab_blocks_villa_importer_attributes( $attributes )
	);
}

/**
 * Builds a native paragraph block.
 *
 * @param mixed                $text Paragraph copy.
 * @param array<string, mixed> $attributes Optional block attributes.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_paragraph( $text, $attributes = array() ) {
	$text = gutenberg_lab_blocks_villa_importer_text( $text );

	if ( '' === $text ) {
		return '';
	}

	$class_name = isset( $attributes['className'] )
		? gutenberg_lab_blocks_villa_importer_classes( $attributes['className'] )
		: '';
	$class      = '' !== $class_name ? ' class="' . esc_attr( $class_name ) . '"' : '';

	return sprintf(
		'<!-- wp:paragraph%1$s --><p%2$s>%3$s</p><!-- /wp:paragraph -->',
		gutenberg_lab_blocks_villa_importer_attributes( $attributes ),
		$class,
		esc_html( $text )
	);
}

/**
 * Builds a native heading block.
 *
 * @param mixed                $text Heading copy.
 * @param int                  $level Heading level.
 * @param array<string, mixed> $attributes Optional block attributes.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_heading( $text, $level = 2, $attributes = array() ) {
	$text = gutenberg_lab_blocks_villa_importer_text( $text );

	if ( '' === $text ) {
		return '';
	}

	$level = min( 6, max( 1, absint( $level ) ) );

	if ( 2 !== $level ) {
		$attributes['level'] = $level;
	}

	$classes = array( 'wp-block-heading' );

	if ( ! empty( $attributes['className'] ) ) {
		$classes[] = gutenberg_lab_blocks_villa_importer_classes( $attributes['className'] );
	}

	if ( ! empty( $attributes['align'] ) ) {
		$classes[] = 'align' . sanitize_html_class( $attributes['align'] );
	}

	return sprintf(
		'<!-- wp:heading%1$s --><h%2$d class="%3$s">%4$s</h%2$d><!-- /wp:heading -->',
		gutenberg_lab_blocks_villa_importer_attributes( $attributes ),
		$level,
		esc_attr( implode( ' ', $classes ) ),
		esc_html( $text )
	);
}

/**
 * Builds a native group block with the project class names required by CSS.
 *
 * @param string               $inner_markup Serialized child markup.
 * @param array<string, mixed> $attributes Optional block attributes.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_group( $inner_markup, $attributes = array() ) {
	$classes = array( 'wp-block-group' );

	if ( ! empty( $attributes['className'] ) ) {
		$classes[] = gutenberg_lab_blocks_villa_importer_classes( $attributes['className'] );
	}

	$id = ! empty( $attributes['anchor'] )
		? ' id="' . esc_attr( sanitize_title( $attributes['anchor'] ) ) . '"'
		: '';

	return gutenberg_lab_blocks_villa_importer_block(
		'group',
		$attributes,
		sprintf(
			'<div%1$s class="%2$s">%3$s</div>',
			$id,
			esc_attr( implode( ' ', array_filter( $classes ) ) ),
			$inner_markup
		)
	);
}

/**
 * Builds a native columns wrapper.
 *
 * @param array<int, string> $columns Serialized column contents.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_columns( $columns ) {
	$column_markup = '';

	foreach ( $columns as $column ) {
		$column_markup .= gutenberg_lab_blocks_villa_importer_block(
			'column',
			array(),
			'<div class="wp-block-column">' . $column . '</div>'
		);
	}

	return gutenberg_lab_blocks_villa_importer_block(
		'columns',
		array(),
		'<div class="wp-block-columns">' . $column_markup . '</div>'
	);
}

/**
 * Builds a native unordered list.
 *
 * @param array<int, string> $items List items.
 * @param string             $class_name Optional class.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_list( $items, $class_name = '' ) {
	$item_markup = '';

	foreach ( array_filter( array_map( 'gutenberg_lab_blocks_villa_importer_text', $items ) ) as $item ) {
		$item_markup .= '<li>' . esc_html( $item ) . '</li>';
	}

	if ( '' === $item_markup ) {
		return '';
	}

	$attributes = array();
	$class      = 'wp-block-list';

	if ( '' !== $class_name ) {
		$attributes['className'] = $class_name;
		$class                  .= ' ' . sanitize_html_class( $class_name );
	}

	return gutenberg_lab_blocks_villa_importer_block(
		'list',
		$attributes,
		sprintf( '<ul class="%1$s">%2$s</ul>', esc_attr( $class ), $item_markup )
	);
}

/**
 * Builds a native table block.
 *
 * @param array<int, array<int, mixed>> $rows Table rows.
 * @param string                        $class_name Optional class.
 * @param bool                          $has_header Whether the first row is a header.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_table( $rows, $class_name = '', $has_header = false ) {
	if ( empty( $rows ) ) {
		return '';
	}

	$attributes = array( 'hasFixedLayout' => false );

	if ( '' !== $class_name ) {
		$attributes['className'] = $class_name;
	}

	$head_markup = '';
	$body_rows   = $rows;

	if ( $has_header ) {
		$header      = array_shift( $body_rows );
		$head_markup = '<thead><tr>';

		foreach ( $header as $cell ) {
			$head_markup .= '<th>' . esc_html( gutenberg_lab_blocks_villa_importer_text( $cell ) ) . '</th>';
		}

		$head_markup .= '</tr></thead>';
	}

	$body_markup = '<tbody>';

	foreach ( $body_rows as $row ) {
		$body_markup .= '<tr>';

		foreach ( $row as $cell ) {
			$body_markup .= '<td>' . esc_html( gutenberg_lab_blocks_villa_importer_text( $cell ) ) . '</td>';
		}

		$body_markup .= '</tr>';
	}

	$body_markup .= '</tbody>';

	$figure_class = 'wp-block-table';

	if ( '' !== $class_name ) {
		$figure_class .= ' ' . sanitize_html_class( $class_name );
	}

	return gutenberg_lab_blocks_villa_importer_block(
		'table',
		$attributes,
		sprintf(
			'<figure class="%1$s"><table>%2$s%3$s</table></figure>',
			esc_attr( $figure_class ),
			$head_markup,
			$body_markup
		)
	);
}

/**
 * Builds native buttons.
 *
 * @param array<int, array<string, string>> $buttons Button label, URL, and class.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_buttons( $buttons ) {
	$button_markup = '';

	foreach ( $buttons as $button ) {
		$label = gutenberg_lab_blocks_villa_importer_text( $button['label'] ?? '' );
		$url   = esc_url( $button['url'] ?? '' );

		if ( '' === $label || '' === $url ) {
			continue;
		}

		$class_name = gutenberg_lab_blocks_villa_importer_classes( $button['class'] ?? '' );
		$attributes = '' !== $class_name ? array( 'className' => $class_name ) : array();
		$classes    = 'wp-block-button' . ( '' !== $class_name ? ' ' . $class_name : '' );
		$inner       = sprintf(
			'<div class="%1$s"><a class="wp-block-button__link wp-element-button" href="%2$s">%3$s</a></div>',
			esc_attr( $classes ),
			$url,
			esc_html( $label )
		);

		$button_markup .= gutenberg_lab_blocks_villa_importer_block( 'button', $attributes, $inner );
	}

	if ( '' === $button_markup ) {
		return '';
	}

	return gutenberg_lab_blocks_villa_importer_block(
		'buttons',
		array(),
		'<div class="wp-block-buttons">' . $button_markup . '</div>'
	);
}

/**
 * Splits comma/semicolon-separated client copy into short labels.
 *
 * @param mixed $value Raw list.
 * @return array<int, string>
 */
function gutenberg_lab_blocks_villa_importer_split_list( $value ) {
	return array_values(
		array_filter(
			array_map(
				'gutenberg_lab_blocks_villa_importer_text',
				preg_split( '/[,;]+/', (string) $value )
			)
		)
	);
}

/**
 * Formats USD values for visible villa content.
 *
 * @param mixed $amount Numeric amount.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_format_usd( $amount ) {
	$amount = (float) $amount;
	$decimals = floor( $amount ) === $amount ? 0 : 2;

	return '$' . number_format_i18n( $amount, $decimals );
}

/**
 * Builds the hero and villa facts strip.
 *
 * @param array<string, mixed> $data Normalized workbook data.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_build_hero( $data ) {
	$overview = $data['overview'];
	$content  =
		gutenberg_lab_blocks_villa_importer_heading(
			$overview['hero_location_line'],
			4,
			array( 'className' => 'eyebrow' )
		) .
		gutenberg_lab_blocks_villa_importer_self_closing_block(
			'post-title',
			array( 'level' => 1 )
		) .
		gutenberg_lab_blocks_villa_importer_paragraph( $overview['hero_statement'] ) .
		gutenberg_lab_blocks_villa_importer_buttons(
			array(
				array(
					'label' => 'Request Availability',
					'url'   => '#request-availability',
				),
				array(
					'label' => 'Explore Gallery',
					'url'   => '#villa-gallery',
					'class' => 'is-style-vvm-secondary',
				),
			)
		);

	$hero =
		gutenberg_lab_blocks_villa_importer_block(
			'gutenberg-lab-blocks/villa-gallery-hero-media',
			array( 'lock' => array( 'move' => true, 'remove' => true ) ),
			''
		) .
		gutenberg_lab_blocks_villa_importer_block(
			'gutenberg-lab-blocks/villa-gallery-hero-content',
			array( 'lock' => array( 'move' => true, 'remove' => true ) ),
			$content
		);

	$hero = gutenberg_lab_blocks_villa_importer_block(
		'gutenberg-lab-blocks/villa-gallery-hero',
		array(
			'align'      => 'full',
			'showArrows' => true,
			'anchor'     => 'villa-gallery',
		),
		$hero
	);

	$specs = array(
		array( 'value' => (string) $overview['bedrooms'], 'label' => 'Bedrooms', 'iconSlug' => 'bed' ),
		array( 'value' => (string) $overview['bathrooms'], 'label' => 'Bathrooms', 'iconSlug' => 'bathtub' ),
		array( 'value' => (string) $overview['sleeps'], 'label' => 'Sleeps', 'iconSlug' => 'people' ),
		array( 'value' => (string) $overview['pool_summary'], 'label' => 'Pool', 'iconSlug' => 'pool' ),
		array(
			'value'    => 'From ' . gutenberg_lab_blocks_villa_importer_format_usd( $overview['starting_rate_usd'] ),
			'label'    => 'Per Night',
			'iconSlug' => 'finance-and',
		),
	);
	$spec_markup = '';

	foreach ( $specs as $spec ) {
		$spec_markup .= gutenberg_lab_blocks_villa_importer_self_closing_block(
			'gutenberg-lab-blocks/villa-spec-item',
			$spec
		);
	}

	return $hero . "\n\n" . gutenberg_lab_blocks_villa_importer_block(
		'gutenberg-lab-blocks/villa-specs',
		array(),
		$spec_markup
	);
}

/**
 * Builds the main story, highlights, nearby, staff, and editorial perspective.
 *
 * @param array<string, mixed> $data Normalized workbook data.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_build_story( $data ) {
	$story      = $data['story'];
	$intro      = '';
	$expanded   = '';
	$highlights = array();
	$nearby     = array();

	foreach ( range( 1, 3 ) as $index ) {
		$intro .= gutenberg_lab_blocks_villa_importer_paragraph( $story[ 'intro_paragraph_' . $index ] ?? '' );
	}

	foreach ( range( 1, 6 ) as $index ) {
		$expanded .= gutenberg_lab_blocks_villa_importer_paragraph( $story[ 'expanded_paragraph_' . $index ] ?? '' );
	}

	foreach ( $data['highlights'] as $highlight ) {
		$highlights[] = $highlight['highlight'] ?? '';
	}

	foreach ( $data['nearby'] as $place ) {
		$nearby[] = array( $place['place'] ?? '', $place['travel_time'] ?? '' );
	}

	$left =
		gutenberg_lab_blocks_villa_importer_heading( $story['story_eyebrow'], 4 ) .
		gutenberg_lab_blocks_villa_importer_heading( $story['story_headline'], 2 ) .
		$intro;

	if ( '' !== $expanded ) {
		$left .= gutenberg_lab_blocks_villa_importer_block(
			'gutenberg-lab-blocks/read-more',
			array(),
			$expanded
		);
	}

	if ( ! empty( $data['staff'] ) ) {
		$staff_rows = array();

		foreach ( $data['staff'] as $staff_member ) {
			$staff_rows[] = array(
				$staff_member['role'] ?? '',
				$staff_member['arrangement'] ?? '',
				$staff_member['description'] ?? '',
			);
		}

		$left .= gutenberg_lab_blocks_villa_importer_heading( 'Villa Staff', 3 ) .
			gutenberg_lab_blocks_villa_importer_table( $staff_rows, 'table-singe-border-bottom' );
	}

	$right =
		gutenberg_lab_blocks_villa_importer_group(
			gutenberg_lab_blocks_villa_importer_heading( 'Why We Love It', 4, array( 'className' => 'vvm-villa-amenities__eyebrow' ) ) .
			gutenberg_lab_blocks_villa_importer_heading( $story['why_love_headline'], 3 ) .
			gutenberg_lab_blocks_villa_importer_list( $highlights, 'list-yellow-dots' ),
			array( 'className' => 'vvm-villa-amenities__card vvm-villa-amenities__card--dark' )
		) .
		gutenberg_lab_blocks_villa_importer_group(
			gutenberg_lab_blocks_villa_importer_heading( 'Nearby', 4 ) .
			gutenberg_lab_blocks_villa_importer_table( $nearby, 'table-singe-border-bottom' ),
			array( 'className' => 'vvm-villa-amenities__card' )
		);

	$perspective = '';

	foreach ( range( 1, 3 ) as $index ) {
		$perspective .= gutenberg_lab_blocks_villa_importer_paragraph( $story[ 'natalie_paragraph_' . $index ] ?? '' );
	}

	return gutenberg_lab_blocks_villa_importer_columns( array( $left, $right ) ) .
		"\n\n" .
		gutenberg_lab_blocks_villa_importer_group(
			gutenberg_lab_blocks_villa_importer_heading( $story['natalie_title'], 4 ) .
			gutenberg_lab_blocks_villa_importer_heading( $story['natalie_quote'], 2 ) .
			$perspective .
			gutenberg_lab_blocks_villa_importer_buttons(
				array(
					array(
						'label' => 'Speak With Natalie on WhatsApp',
						'url'   => '#request-availability',
						'class' => 'is-style-vvm-link-primary vvm-contact-widget-trigger',
					),
				)
			),
			array( 'className' => 'vvm-villa-perspective' )
		);
}

/**
 * Builds the Bedrooms Stack Tab content.
 *
 * @param array<string, mixed> $data Normalized workbook data.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_build_bedrooms_tab( $data ) {
	$areas = array();

	foreach ( $data['bedrooms'] as $bedroom ) {
		$area = gutenberg_lab_blocks_villa_importer_text( $bedroom['area'] ?? 'Bedrooms' );
		$areas[ $area ][] = $bedroom;
	}

	$content =
		gutenberg_lab_blocks_villa_importer_heading( 'Bedroom Layout', 4 ) .
		gutenberg_lab_blocks_villa_importer_heading(
			sprintf(
				'%d thoughtfully arranged bedrooms across the villa.',
				(int) $data['overview']['bedrooms']
			),
			2
		);

	foreach ( $areas as $area => $bedrooms ) {
		$room_markup = gutenberg_lab_blocks_villa_importer_heading( $area, 3 );

		foreach ( $bedrooms as $bedroom ) {
			$chips = array( $bedroom['bed_configuration'] ?? '' );

			if ( isset( $bedroom['ensuite'] ) ) {
				$chips[] = 'yes' === strtolower( $bedroom['ensuite'] ) ? 'Ensuite' : 'Shared bathroom';
			}

			$chips = array_merge(
				$chips,
				gutenberg_lab_blocks_villa_importer_split_list( $bedroom['views'] ?? '' ),
				gutenberg_lab_blocks_villa_importer_split_list( $bedroom['features'] ?? '' )
			);
			$chip_markup = '';

			foreach ( array_filter( $chips ) as $chip ) {
				$chip_markup .= gutenberg_lab_blocks_villa_importer_paragraph(
					$chip,
					array( 'className' => 'vvm-bedroom-levels__chip' )
				);
			}

			$room_markup .= gutenberg_lab_blocks_villa_importer_group(
				gutenberg_lab_blocks_villa_importer_paragraph(
					$bedroom['room_label'] ?? 'Bedroom',
					array( 'className' => 'vvm-bedroom-levels__eyebrow' )
				) .
				gutenberg_lab_blocks_villa_importer_heading(
					$bedroom['room_name'],
					3,
					array( 'className' => 'vvm-bedroom-levels__title' )
				) .
				gutenberg_lab_blocks_villa_importer_group(
					$chip_markup,
					array( 'className' => 'vvm-bedroom-levels__chips' )
				) .
				gutenberg_lab_blocks_villa_importer_paragraph(
					$bedroom['description'],
					array( 'className' => 'vvm-bedroom-levels__copy' )
				),
				array( 'className' => 'vvm-bedroom-levels__card vvm-bedroom-levels__room-card' )
			);
		}

		$content .= gutenberg_lab_blocks_villa_importer_group(
			$room_markup,
			array( 'className' => 'vvm-bedroom-levels__panel vvm-bedroom-levels__grid is-active' )
		);
	}

	return gutenberg_lab_blocks_villa_importer_block(
		'gutenberg-lab-blocks/stack-tab',
		array( 'label' => 'Bedrooms' ),
		$content
	);
}

/**
 * Builds the Amenities Stack Tab content.
 *
 * @param array<string, mixed> $data Normalized workbook data.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_build_amenities_tab( $data ) {
	$groups = array();

	foreach ( $data['amenities'] as $amenity ) {
		$group = gutenberg_lab_blocks_villa_importer_text( $amenity['group'] ?? 'Villa Amenities' );
		$groups[ $group ][] = $amenity;
	}

	$content = '';

	foreach ( $groups as $group => $amenities ) {
		$chips = '';
		$rows  = '';
		$index = 1;

		foreach ( $amenities as $amenity ) {
			$item = $amenity['item'] ?? '';

			if ( 'yes' === strtolower( $amenity['featured'] ?? '' ) ) {
				$chips .= gutenberg_lab_blocks_villa_importer_paragraph(
					$item,
					array( 'className' => 'vvm-villa-amenities__chip' )
				);
			}

			$rows .= gutenberg_lab_blocks_villa_importer_group(
				gutenberg_lab_blocks_villa_importer_paragraph(
					str_pad( (string) $index, 2, '0', STR_PAD_LEFT ),
					array( 'className' => 'vvm-villa-amenities__index' )
				) .
				gutenberg_lab_blocks_villa_importer_paragraph(
					$item,
					array( 'className' => 'vvm-villa-amenities__item' )
				),
				array( 'className' => 'vvm-villa-amenities__row' )
			);
			$index++;
		}

		$content .= gutenberg_lab_blocks_villa_importer_group(
			gutenberg_lab_blocks_villa_importer_group(
				gutenberg_lab_blocks_villa_importer_heading(
					$group,
					3,
					array( 'className' => 'vvm-villa-amenities__title' )
				) .
				gutenberg_lab_blocks_villa_importer_group(
					$chips,
					array( 'className' => 'vvm-villa-amenities__chips' )
				),
				array( 'className' => 'vvm-villa-amenities__header' )
			) .
			gutenberg_lab_blocks_villa_importer_group(
				$rows,
				array( 'className' => 'vvm-villa-amenities__list' )
			),
			array( 'className' => 'vvm-villa-amenities__card' )
		);
	}

	return gutenberg_lab_blocks_villa_importer_block(
		'gutenberg-lab-blocks/stack-tab',
		array( 'label' => 'Amenities' ),
		gutenberg_lab_blocks_villa_importer_group(
			$content,
			array( 'className' => 'vvm-villa-amenities' )
		)
	);
}

/**
 * Builds the House Rules Stack Tab content.
 *
 * @param array<string, mixed> $data Normalized workbook data.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_build_rules_tab( $data ) {
	$rows = '';

	foreach ( $data['rules'] as $rule ) {
		$rows .= gutenberg_lab_blocks_villa_importer_group(
			gutenberg_lab_blocks_villa_importer_paragraph(
				sprintf( '%s: %s', $rule['rule'] ?? '', $rule['details'] ?? '' ),
				array( 'className' => 'vvm-villa-amenities__item' )
			),
			array( 'className' => 'vvm-villa-amenities__row vvm-villa-rules__row' )
		);
	}

	$content = gutenberg_lab_blocks_villa_importer_group(
		gutenberg_lab_blocks_villa_importer_paragraph(
			'House Rules',
			array( 'className' => 'vvm-villa-amenities__eyebrow' )
		) .
		gutenberg_lab_blocks_villa_importer_heading(
			'Before You Arrive',
			3,
			array( 'className' => 'vvm-villa-amenities__title' )
		) .
		gutenberg_lab_blocks_villa_importer_group(
			$rows,
			array( 'className' => 'vvm-villa-amenities__list vvm-villa-rules__list' )
		),
		array( 'className' => 'vvm-villa-amenities__card' )
	);

	return gutenberg_lab_blocks_villa_importer_block(
		'gutenberg-lab-blocks/stack-tab',
		array( 'label' => 'House Rules' ),
		gutenberg_lab_blocks_villa_importer_group(
			$content,
			array( 'className' => 'vvm-villa-amenities vvm-villa-rules' )
		)
	);
}

/**
 * Builds the optional Reviews Stack Tab content.
 *
 * @param array<string, mixed> $data Normalized workbook data.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_build_reviews_tab( $data ) {
	if ( empty( $data['reviews'] ) ) {
		return '';
	}

	$reviews = '';

	foreach ( $data['reviews'] as $review ) {
		$reviews .= gutenberg_lab_blocks_villa_importer_group(
			gutenberg_lab_blocks_villa_importer_paragraph(
				$review['title'] ?? '',
				array( 'className' => 'vvm-villa-reviews__quote' )
			) .
			gutenberg_lab_blocks_villa_importer_paragraph(
				$review['review'] ?? '',
				array( 'className' => 'vvm-villa-amenities__copy vvm-villa-reviews__copy' )
			) .
			gutenberg_lab_blocks_villa_importer_paragraph(
				$review['guest_name'] ?? '',
				array( 'className' => 'vvm-villa-amenities__eyebrow vvm-villa-reviews__author' )
			),
			array( 'className' => 'vvm-villa-reviews__review' )
		);
	}

	$content = gutenberg_lab_blocks_villa_importer_group(
		gutenberg_lab_blocks_villa_importer_paragraph(
			'What Guests Remember',
			array( 'className' => 'vvm-villa-amenities__eyebrow' )
		) .
		gutenberg_lab_blocks_villa_importer_group(
			$reviews,
			array( 'className' => 'vvm-villa-reviews__list' )
		),
		array( 'className' => 'vvm-villa-amenities__card vvm-villa-amenities__card--dark' )
	);

	return gutenberg_lab_blocks_villa_importer_block(
		'gutenberg-lab-blocks/stack-tab',
		array( 'label' => 'Reviews' ),
		gutenberg_lab_blocks_villa_importer_group(
			$content,
			array( 'className' => 'vvm-villa-amenities vvm-villa-reviews is-style-vvm-reviews-three-up' )
		)
	);
}

/**
 * Builds the complete Stack Tabs section.
 *
 * @param array<string, mixed> $data Normalized workbook data.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_build_tabs( $data ) {
	$tabs =
		gutenberg_lab_blocks_villa_importer_build_bedrooms_tab( $data ) .
		gutenberg_lab_blocks_villa_importer_build_amenities_tab( $data ) .
		gutenberg_lab_blocks_villa_importer_build_rules_tab( $data ) .
		gutenberg_lab_blocks_villa_importer_build_reviews_tab( $data );

	return gutenberg_lab_blocks_villa_importer_block(
		'gutenberg-lab-blocks/stack-tabs',
		array(),
		$tabs
	);
}

/**
 * Returns Contact Form 7 block attributes without hardcoded environment IDs.
 *
 * @return array<string, mixed>|WP_Error
 */
function gutenberg_lab_blocks_villa_importer_contact_form_attributes() {
	$forms = get_posts(
		array(
			'post_type'      => 'wpcf7_contact_form',
			'post_status'    => 'publish',
			'title'          => 'Contact Form - Villa',
			'posts_per_page' => 1,
		)
	);

	if ( empty( $forms ) ) {
		return new WP_Error(
			'missing_villa_contact_form',
			__( 'The published "Contact Form - Villa" form was not found.', 'gutenberg-lab-blocks' )
		);
	}

	$form_id = (int) $forms[0]->ID;

	return array(
		'id'        => $form_id,
		'hash'      => (string) get_post_meta( $form_id, '_hash', true ),
		'title'     => 'Contact Form - Villa',
		'htmlClass' => 'vvm-villa-contact-form',
	);
}

/**
 * Builds the saved Contact Form 7 block markup.
 *
 * @param array<string, mixed> $attributes Contact Form 7 attributes.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_contact_form_block( $attributes ) {
	$shortcode_id = ! empty( $attributes['hash'] ) ? $attributes['hash'] : $attributes['id'];
	$shortcode    = sprintf(
		'[contact-form-7 id="%1$s" title="%2$s" html_class="%3$s"]',
		esc_attr( $shortcode_id ),
		esc_attr( $attributes['title'] ),
		esc_attr( $attributes['htmlClass'] )
	);

	return gutenberg_lab_blocks_villa_importer_block(
		'contact-form-7/contact-form-selector',
		$attributes,
		'<div class="wp-block-contact-form-7-contact-form-selector">' . $shortcode . '</div>'
	);
}

/**
 * Builds rates, enquiry, and location content.
 *
 * @param array<string, mixed> $data Normalized workbook data.
 * @param array<string, mixed> $contact_form Contact form attributes.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_build_pricing_contact_location( $data, $contact_form ) {
	$overview = $data['overview'];
	$extras   = $data['extras'];
	$rate_rows = array( array( 'Season', 'Rate per night', 'Minimum stay' ) );

	foreach ( $data['rates'] as $rate ) {
		$season = sprintf(
			'%s (%s - %s)',
			$rate['season'],
			$rate['start_date'],
			$rate['end_date']
		);
		$rate_rows[] = array(
			$season,
			gutenberg_lab_blocks_villa_importer_format_usd( $rate['nightly_rate_usd'] ),
			sprintf( '%d nights', (int) $rate['minimum_nights'] ),
		);
	}

	$booking_terms = '';
	$selector_attrs = array();
	$minimum_bedroom_choice = min(
		max( 1, absint( $overview['minimum_bedroom_choice'] ?? 1 ) ),
		max( 1, absint( $overview['bedrooms'] ?? 1 ) )
	);

	if ( $minimum_bedroom_choice > 1 ) {
		$selector_attrs['minimumBedrooms'] = $minimum_bedroom_choice;
	}

	foreach ( range( 1, 3 ) as $index ) {
		$booking_terms .= gutenberg_lab_blocks_villa_importer_paragraph(
			$extras[ 'booking_terms_' . $index ] ?? ''
		);
	}

	$pricing_intro = gutenberg_lab_blocks_villa_importer_group(
		gutenberg_lab_blocks_villa_importer_paragraph(
			'Rates & Stay Requirements',
			array( 'className' => 'vvm-villa-amenities__eyebrow' )
		) .
		gutenberg_lab_blocks_villa_importer_heading( $extras['pricing_heading'], 3 ) .
		gutenberg_lab_blocks_villa_importer_self_closing_block(
			'gutenberg-lab-blocks/villa-bedroom-selector',
			$selector_attrs
		),
		array(
			'className' => 'vvm-villa-pricing__intro vvm-villa-pricing__intro--has-bedroom-selector',
		)
	);

	$pricing_header = gutenberg_lab_blocks_villa_importer_group(
		$pricing_intro .
		gutenberg_lab_blocks_villa_importer_paragraph(
			$extras['pricing_helper'],
			array( 'className' => 'vvm-villa-pricing__helper' )
		),
		array( 'className' => 'vvm-villa-pricing__header' )
	);

	$pricing =
		$pricing_header .
		gutenberg_lab_blocks_villa_importer_table(
			$rate_rows,
			'vvm-villa-pricing__table',
			true
		) .
		gutenberg_lab_blocks_villa_importer_paragraph( $extras['tax_note'] ) .
		gutenberg_lab_blocks_villa_importer_paragraph( $extras['security_deposit_note'] );

	if ( '' !== $booking_terms ) {
		$pricing .= gutenberg_lab_blocks_villa_importer_block(
			'gutenberg-lab-blocks/read-more',
			array(
				'readMoreLabel' => 'Read Booking Terms',
				'readLessLabel' => 'Close Booking Terms',
			),
			$booking_terms
		);
	}

	$pricing = gutenberg_lab_blocks_villa_importer_group(
		$pricing,
		array(
			'className' => 'vvm-villa-amenities__card vvm-villa-pricing__card',
			'anchor'    => 'request-availability',
		)
	);

	$contact =
		gutenberg_lab_blocks_villa_importer_paragraph(
			$extras['contact_eyebrow'],
			array( 'className' => 'vvm-villa-amenities__eyebrow' )
		) .
		gutenberg_lab_blocks_villa_importer_heading( $extras['contact_heading'], 3 ) .
		gutenberg_lab_blocks_villa_importer_paragraph(
			$extras['contact_text'],
			array( 'className' => 'vvm-villa-amenities__copy' )
		) .
		gutenberg_lab_blocks_villa_importer_contact_form_block( $contact_form ) .
		gutenberg_lab_blocks_villa_importer_buttons(
			array(
				array(
					'label' => $extras['whatsapp_label'] ?? 'WhatsApp Us',
					'url'   => '#request-availability',
					'class' => 'vvm-villa-contact__whatsapp vvm-contact-widget-trigger',
				),
			)
		);

	$contact = gutenberg_lab_blocks_villa_importer_group(
		$contact,
		array( 'className' => 'vvm-villa-amenities__card vvm-villa-amenities__card--dark vvm-villa-contact__form-card' )
	);

	$pricing_contact = gutenberg_lab_blocks_villa_importer_group(
		gutenberg_lab_blocks_villa_importer_group(
			$pricing,
			array( 'className' => 'vvm-villa-amenities vvm-villa-pricing' )
		) .
		$contact,
		array( 'className' => 'vvm-villa-pricing-contact' )
	);

	$map_markup = '';

	if ( ! empty( $overview['display_address'] ) ) {
		$map_markup = gutenberg_lab_blocks_villa_importer_self_closing_block(
			'gmap/gmap-block',
			array(
				'address'    => $overview['display_address'],
				'zoom'       => 15,
				'uniqueId'   => 'gmap-block-' . substr( md5( $overview['villa_name'] ), 0, 8 ),
				'blockStyle' => '',
			)
		);
	}

	$location =
		$map_markup .
		gutenberg_lab_blocks_villa_importer_paragraph(
			'Location',
			array( 'className' => 'vvm-villa-amenities__eyebrow' )
		) .
		gutenberg_lab_blocks_villa_importer_paragraph(
			$overview['display_address'],
			array( 'className' => 'vvm-villa-amenities__item' )
		) .
		gutenberg_lab_blocks_villa_importer_paragraph(
			$extras['location_description'],
			array( 'className' => 'vvm-villa-amenities__copy' )
		);

	if ( ! empty( $overview['google_maps_link'] ) ) {
		$location .= gutenberg_lab_blocks_villa_importer_buttons(
			array(
				array(
					'label' => 'Open in Google Maps',
					'url'   => $overview['google_maps_link'],
					'class' => 'is-style-vvm-link-primary',
				),
			)
		);
	}

	return $pricing_contact . "\n\n" .
		gutenberg_lab_blocks_villa_importer_group(
			gutenberg_lab_blocks_villa_importer_group(
				$location,
				array( 'className' => 'vvm-villa-contact__location-card' )
			),
			array( 'className' => 'vvm-villa-amenities vvm-villa-contact' )
		);
}

/**
 * Builds the dynamic related-villa grid.
 *
 * @param array<int, int> $related_ids Related published Villa IDs.
 * @param string          $heading Related section heading.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_build_related_villas( $related_ids, $heading ) {
	if ( empty( $related_ids ) ) {
		return '';
	}

	return gutenberg_lab_blocks_villa_importer_heading(
		$heading,
		2,
		array( 'align' => 'wide' )
	) .
		gutenberg_lab_blocks_villa_importer_self_closing_block(
			'gutenberg-lab-blocks/card-grid',
			array(
				'contentSource'     => 'villas',
				'selectedVillaIds' => array_values( array_map( 'absint', $related_ids ) ),
				'villaPresentation' => 'collection',
				'columns'           => '3',
				'align'             => 'wide',
			)
		);
}

/**
 * Builds the complete post_content payload.
 *
 * @param array<string, mixed> $data Normalized workbook data.
 * @param array<int, int>      $related_ids Related published Villa IDs.
 * @return string|WP_Error
 */
function gutenberg_lab_blocks_villa_importer_build_content( $data, $related_ids ) {
	$contact_form = gutenberg_lab_blocks_villa_importer_contact_form_attributes();

	if ( is_wp_error( $contact_form ) ) {
		return $contact_form;
	}

	$content = array(
		gutenberg_lab_blocks_villa_importer_build_hero( $data ),
		gutenberg_lab_blocks_villa_importer_build_story( $data ),
		gutenberg_lab_blocks_villa_importer_build_tabs( $data ),
		gutenberg_lab_blocks_villa_importer_self_closing_block(
			'gutenberg-lab-blocks/villa-availability-calendar',
			array( 'allowUnavailableEndpoints' => true )
		),
		gutenberg_lab_blocks_villa_importer_build_pricing_contact_location( $data, $contact_form ),
		gutenberg_lab_blocks_villa_importer_build_related_villas(
			$related_ids,
			$data['extras']['related_heading'] ?? 'Other villas in our collection'
		),
	);

	return implode( "\n\n", array_filter( $content ) );
}

/**
 * Validates the normalized workbook payload at the WordPress boundary.
 *
 * @param mixed $data Decoded JSON data.
 * @return true|WP_Error
 */
function gutenberg_lab_blocks_villa_importer_validate_payload( $data ) {
	if ( ! is_array( $data ) ) {
		return new WP_Error( 'invalid_import_payload', __( 'The normalized workbook payload must be an object.', 'gutenberg-lab-blocks' ) );
	}

	if ( gutenberg_lab_blocks_villa_importer_schema_version() !== ( $data['schema_version'] ?? '' ) ) {
		return new WP_Error( 'invalid_import_schema', __( 'The workbook schema version is not supported.', 'gutenberg-lab-blocks' ) );
	}

	$required_sections = array(
		'overview',
		'story',
		'extras',
		'bedrooms',
		'amenities',
		'rates',
		'rules',
		'nearby',
		'highlights',
	);

	foreach ( $required_sections as $section ) {
		if ( empty( $data[ $section ] ) || ! is_array( $data[ $section ] ) ) {
			return new WP_Error(
				'missing_import_section',
				sprintf(
					/* translators: %s: Workbook section key. */
					__( 'The workbook section "%s" is missing or empty.', 'gutenberg-lab-blocks' ),
					$section
				)
			);
		}
	}

	return true;
}

/**
 * Finds an existing location term without silently creating typo terms.
 *
 * @param string $location_name Client location name.
 * @return WP_Term|WP_Error
 */
function gutenberg_lab_blocks_villa_importer_resolve_location( $location_name ) {
	$location_name = gutenberg_lab_blocks_villa_importer_text( $location_name );
	$terms = get_terms(
		array(
			'taxonomy'   => 'villa_location',
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $terms ) ) {
		return $terms;
	}

	foreach ( $terms as $term ) {
		if (
			0 === strcasecmp( $term->name, $location_name ) ||
			0 === strcasecmp( $term->slug, sanitize_title( $location_name ) )
		) {
			return $term;
		}
	}

	return new WP_Error(
		'unknown_villa_location',
		sprintf(
			/* translators: %s: Client location. */
			__( 'The villa location "%s" does not match an existing location term.', 'gutenberg-lab-blocks' ),
			$location_name
		)
	);
}

/**
 * Extracts coordinates from common full Google Maps URL shapes.
 *
 * Short share URLs are deliberately not fetched during import.
 *
 * @param string $maps_url Google Maps URL.
 * @return array{latitude:string,longitude:string}|array{}
 */
function gutenberg_lab_blocks_villa_importer_extract_coordinates( $maps_url ) {
	$maps_url = html_entity_decode( (string) $maps_url, ENT_QUOTES, 'UTF-8' );
	$patterns = array(
		'/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/',
		'/[?&](?:query|q)=(-?\d+(?:\.\d+)?)(?:%2C|,)(-?\d+(?:\.\d+)?)/i',
	);

	foreach ( $patterns as $pattern ) {
		if ( preg_match( $pattern, $maps_url, $matches ) ) {
			return array(
				'latitude'  => gutenberg_lab_blocks_sanitize_villa_schema_coordinate( $matches[1] ),
				'longitude' => gutenberg_lab_blocks_sanitize_villa_schema_coordinate( $matches[2] ),
			);
		}
	}

	return array();
}

/**
 * Resolves requested related villas or selects three current published villas.
 *
 * @param array<int, array<string, mixed>> $requested_rows Workbook rows.
 * @param int                              $exclude_id Villa ID to exclude.
 * @return array{ids:array<int,int>,warnings:array<int,string>}
 */
function gutenberg_lab_blocks_villa_importer_resolve_related_villas( $requested_rows, $exclude_id = 0 ) {
	$ids      = array();
	$warnings = array();

	foreach ( $requested_rows as $row ) {
		$name = gutenberg_lab_blocks_villa_importer_text( $row['villa_name'] ?? '' );

		if ( '' === $name ) {
			continue;
		}

		$matches = get_posts(
			array(
				'post_type'      => 'villa',
				'post_status'    => 'publish',
				'title'          => $name,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		if ( empty( $matches ) ) {
			$warnings[] = sprintf( 'Related villa "%s" was not found and was skipped.', $name );
			continue;
		}

		if ( (int) $matches[0] !== (int) $exclude_id ) {
			$ids[] = (int) $matches[0];
		}
	}

	if ( empty( $ids ) ) {
		$ids = get_posts(
			array(
				'post_type'      => 'villa',
				'post_status'    => 'publish',
				'posts_per_page' => 3,
				'post__not_in'   => $exclude_id ? array( $exclude_id ) : array(),
				'orderby'        => 'menu_order title',
				'order'          => 'ASC',
				'fields'         => 'ids',
			)
		);
	}

	return array(
		'ids'      => array_slice( array_values( array_unique( array_map( 'absint', $ids ) ) ), 0, 3 ),
		'warnings' => $warnings,
	);
}

/**
 * Returns the amenity taxonomy terms derived from structured overview facts.
 *
 * @param array<string, mixed> $overview Normalized overview.
 * @return array<int, array{name:string,icon:string}>
 */
function gutenberg_lab_blocks_villa_importer_summary_terms( $overview ) {
	$terms = array(
		array(
			'name' => sprintf( '%s Bedrooms', $overview['bedrooms'] ),
			'icon' => 'bed',
		),
		array(
			'name' => sprintf( '%s Bathrooms', $overview['bathrooms'] ),
			'icon' => 'bathtub',
		),
		array(
			'name' => sprintf( 'Sleeps %s', $overview['sleeps'] ),
			'icon' => 'people',
		),
	);

	if ( ! empty( $overview['primary_view'] ) ) {
		$view_icons = array(
			'Hillside Retreat' => 'hillside-retreat',
			'Ocean View'       => 'ocean-view',
			'Oceanfront'       => 'ocean-front',
		);

		$terms[] = array(
			'name' => $overview['primary_view'],
			'icon' => $view_icons[ $overview['primary_view'] ] ?? sanitize_title( $overview['primary_view'] ),
		);
	}

	return $terms;
}

/**
 * Checks whether importer-managed content now contains manually assigned media.
 *
 * Spreadsheet updates replace the generated block tree. Refusing updates after
 * media is assigned protects gallery work that happens later in WordPress.
 *
 * @param array<int, array<string, mixed>> $blocks Parsed Gutenberg blocks.
 * @return bool
 */
function gutenberg_lab_blocks_villa_importer_blocks_have_media( $blocks ) {
	$media_blocks = array(
		'core/cover',
		'core/gallery',
		'core/image',
		'core/media-text',
		'core/video',
		'gutenberg-lab-blocks/villa-gallery-hero-slide',
	);
	$media_keys   = array(
		'backgroundImageId',
		'backgroundImageUrl',
		'galleryIds',
		'imageId',
		'imageIds',
		'imageUrl',
		'imageUrls',
		'mediaId',
		'mediaUrl',
		'posterImageId',
		'posterImageUrl',
		'videoId',
		'videoUrl',
	);

	foreach ( $blocks as $block ) {
		$block_name = $block['blockName'] ?? null;
		$attributes = (array) ( $block['attrs'] ?? array() );

		if ( in_array( $block_name, $media_blocks, true ) ) {
			return true;
		}

		foreach ( $media_keys as $media_key ) {
			if ( ! empty( $attributes[ $media_key ] ) ) {
				return true;
			}
		}

		if (
			! empty( $block['innerBlocks'] ) &&
			gutenberg_lab_blocks_villa_importer_blocks_have_media( $block['innerBlocks'] )
		) {
			return true;
		}
	}

	return false;
}

/**
 * Checks whether a villa draft has media that an update could overwrite.
 *
 * @param int $post_id Villa post ID.
 * @return bool
 */
function gutenberg_lab_blocks_villa_importer_has_media( $post_id ) {
	if ( get_post_thumbnail_id( $post_id ) ) {
		return true;
	}

	$post = get_post( $post_id );

	return $post instanceof WP_Post &&
		gutenberg_lab_blocks_villa_importer_blocks_have_media( parse_blocks( $post->post_content ) );
}

/**
 * Creates or reuses structured amenity summary terms.
 *
 * @param array<string, mixed> $overview Normalized overview.
 * @return array<int, int>|WP_Error
 */
function gutenberg_lab_blocks_villa_importer_ensure_summary_terms( $overview ) {
	$term_ids = array();

	foreach ( gutenberg_lab_blocks_villa_importer_summary_terms( $overview ) as $term_data ) {
		$existing = get_term_by( 'name', $term_data['name'], 'villa_amenity' );

		if ( $existing instanceof WP_Term ) {
			$term_id = (int) $existing->term_id;
		} else {
			$created = wp_insert_term( $term_data['name'], 'villa_amenity' );

			if ( is_wp_error( $created ) ) {
				return $created;
			}

			$term_id = (int) $created['term_id'];
		}

		update_term_meta(
			$term_id,
			'villa_amenity_icon',
			gutenberg_lab_blocks_sanitize_villa_amenity_icon_key( $term_data['icon'] )
		);
		$term_ids[] = $term_id;
	}

	return $term_ids;
}

/**
 * Creates a recoverable JSON backup before an importer-managed draft update.
 *
 * @param int    $post_id Villa draft ID.
 * @param string $backup_dir Writable container path.
 * @return string|WP_Error
 */
function gutenberg_lab_blocks_villa_importer_backup_draft( $post_id, $backup_dir ) {
	$post = get_post( $post_id );

	if ( ! $post ) {
		return new WP_Error( 'missing_backup_post', __( 'The draft could not be loaded for backup.', 'gutenberg-lab-blocks' ) );
	}

	if ( ! wp_mkdir_p( $backup_dir ) ) {
		return new WP_Error( 'backup_directory_failed', __( 'The villa import backup directory could not be created.', 'gutenberg-lab-blocks' ) );
	}

	$payload = array(
		'created_at' => gmdate( 'c' ),
		'post'       => get_object_vars( $post ),
		'meta'       => get_post_meta( $post_id ),
		'taxonomies' => array(
			'villa_location' => wp_get_object_terms( $post_id, 'villa_location', array( 'fields' => 'ids' ) ),
			'villa_amenity'  => wp_get_object_terms( $post_id, 'villa_amenity', array( 'fields' => 'ids' ) ),
		),
	);
	$filename = trailingslashit( $backup_dir ) . sprintf(
		'villa-%d-%s.json',
		$post_id,
		gmdate( 'Ymd-His' )
	);
	$result = file_put_contents(
		$filename,
		wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES )
	);

	if ( false === $result ) {
		return new WP_Error( 'backup_write_failed', __( 'The villa draft backup could not be written.', 'gutenberg-lab-blocks' ) );
	}

	return $filename;
}

/**
 * Builds post meta from normalized workbook content.
 *
 * @param array<string, mixed> $data Normalized workbook data.
 * @return array<string, string>
 */
function gutenberg_lab_blocks_villa_importer_meta( $data ) {
	$overview = $data['overview'];
	$coordinates = gutenberg_lab_blocks_villa_importer_extract_coordinates(
		$overview['google_maps_link'] ?? ''
	);
	$facts = sprintf(
		'%1$s Bedrooms - %2$s Bathrooms - Sleeps %3$s - From %4$s/night',
		$overview['bedrooms'],
		$overview['bathrooms'],
		$overview['sleeps'],
		gutenberg_lab_blocks_villa_importer_format_usd( $overview['starting_rate_usd'] )
	);

	return array_filter(
		array(
			'villa_card_eyebrow'           => $overview['card_small_label'] ?? '',
			'villa_card_descriptor'        => $overview['card_short_description'] ?? sprintf(
				'%s, %s',
				$overview['property_area'],
				$overview['parish']
			),
			'villa_card_facts'             => $facts,
			'villa_card_cta_label'         => $overview['card_cta_label'] ?? 'Explore villa',
			'villa_schema_latitude'        => $coordinates['latitude'] ?? '',
			'villa_schema_longitude'       => $coordinates['longitude'] ?? '',
			'villa_schema_street_address'  => $overview['display_address'] ?? '',
			'villa_schema_postal_code'     => $overview['postal_code'] ?? '',
			'villa_bedroom_selector_enabled' => gutenberg_lab_blocks_villa_importer_boolean(
				$overview['bedroom_selector_enabled'] ?? true,
				true
			) ? '1' : '0',
			'_gutenberg_lab_villa_import_managed' => '1',
			'_gutenberg_lab_villa_import_schema_version' => $data['schema_version'],
			'_gutenberg_lab_villa_import_source_file' => $data['source_file'] ?? '',
			'_gutenberg_lab_villa_imported_at' => gmdate( 'c' ),
		),
		static fn( $value ) => '' !== (string) $value
	);
}

/**
 * WP-CLI command implementation for normalized villa JSON.
 */
class Gutenberg_Lab_Blocks_Villa_Import_Command {
	/**
	 * Creates or updates a guarded local villa draft.
	 *
	 * ## OPTIONS
	 *
	 * <json-file>
	 * : Container path to normalized workbook JSON.
	 *
	 * [--dry-run]
	 * : Validate and preview without database writes.
	 *
	 * [--update=<id>]
	 * : Update an importer-managed draft.
	 *
	 * [--yes]
	 * : Required for a non-dry-run update.
	 *
	 * [--backup-dir=<path>]
	 * : Directory for update backups.
	 *
	 * @param array<int, string>          $args Positional command arguments.
	 * @param array<string, string|bool> $assoc_args Named command arguments.
	 */
	public function __invoke( $args, $assoc_args ) {
		$json_file = $args[0] ?? '';

		if ( '' === $json_file || ! is_readable( $json_file ) ) {
			WP_CLI::error( 'The normalized workbook JSON file is missing or unreadable.' );
		}

		$data = json_decode( file_get_contents( $json_file ), true );
		$validation = gutenberg_lab_blocks_villa_importer_validate_payload( $data );

		if ( is_wp_error( $validation ) ) {
			WP_CLI::error( $validation->get_error_message() );
		}

		$dry_run   = \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$update_id = absint( $assoc_args['update'] ?? 0 );
		$confirmed = \WP_CLI\Utils\get_flag_value( $assoc_args, 'yes', false );
		$backup_dir = (string) ( $assoc_args['backup-dir'] ?? WP_CONTENT_DIR . '/uploads/villa-import-backups' );
		$overview  = $data['overview'];
		$title     = gutenberg_lab_blocks_villa_importer_text( $overview['villa_name'] );
		$slug      = sanitize_title( $title );
		$location  = gutenberg_lab_blocks_villa_importer_resolve_location( $overview['parish'] );

		if ( is_wp_error( $location ) ) {
			WP_CLI::error( $location->get_error_message() );
		}

		if ( $update_id ) {
			$existing = get_post( $update_id );

			if ( ! $existing || 'villa' !== $existing->post_type ) {
				WP_CLI::error( 'The --update ID is not a villa.' );
			}

			if ( 'draft' !== $existing->post_status ) {
				WP_CLI::error( 'Only draft villas can be updated by the importer.' );
			}

			if ( '1' !== get_post_meta( $update_id, '_gutenberg_lab_villa_import_managed', true ) ) {
				WP_CLI::error( 'The selected draft was not created by the villa importer.' );
			}

			if ( gutenberg_lab_blocks_villa_importer_has_media( $update_id ) ) {
				WP_CLI::error( 'This draft already contains assigned media. Update it in WordPress so gallery work is not overwritten.' );
			}

			if ( ! $dry_run && ! $confirmed ) {
				WP_CLI::error( 'Updating an existing draft requires --yes.' );
			}

			$slug_duplicate = get_page_by_path( $slug, OBJECT, 'villa' );

			if ( $slug_duplicate instanceof WP_Post && (int) $slug_duplicate->ID !== $update_id ) {
				WP_CLI::error(
					sprintf( 'A villa with slug "%s" already exists as post %d.', $slug, $slug_duplicate->ID )
				);
			}
		} else {
			$slug_duplicate = get_page_by_path( $slug, OBJECT, 'villa' );

			if ( $slug_duplicate instanceof WP_Post ) {
				WP_CLI::error(
					sprintf( 'A villa with slug "%s" already exists as post %d.', $slug, $slug_duplicate->ID )
				);
			}
		}

		$title_duplicates = get_posts(
			array(
				'post_type'      => 'villa',
				'post_status'    => 'any',
				'post__not_in'   => $update_id ? array( $update_id ) : array(),
				'title'          => $title,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $title_duplicates ) ) {
			WP_CLI::error(
				sprintf( 'A villa named "%s" already exists as post %d.', $title, $title_duplicates[0] )
			);
		}

		$related = gutenberg_lab_blocks_villa_importer_resolve_related_villas(
			$data['related_villas'] ?? array(),
			$update_id
		);
		$content = gutenberg_lab_blocks_villa_importer_build_content( $data, $related['ids'] );

		if ( is_wp_error( $content ) ) {
			WP_CLI::error( $content->get_error_message() );
		}

		$parsed_blocks = array_values(
			array_filter(
				parse_blocks( $content ),
				static function ( $block ) {
					return null !== ( $block['blockName'] ?? null ) ||
						'' !== trim( (string) ( $block['innerHTML'] ?? '' ) );
				}
			)
		);

		if ( empty( $parsed_blocks ) ) {
			WP_CLI::error( 'The generated villa content did not contain Gutenberg blocks.' );
		}

		// Store WordPress's canonical serialization so later editor saves do not
		// create noisy comment-only diffs.
		$content = serialize_blocks( $parsed_blocks );

		$warnings = array_merge(
			(array) ( $data['warnings'] ?? array() ),
			$related['warnings']
		);

		if ( empty( gutenberg_lab_blocks_villa_importer_extract_coordinates( $overview['google_maps_link'] ?? '' ) ) ) {
			$warnings[] = 'Google Maps coordinates were not detected; add exact schema coordinates during review.';
		}

		$warnings[] = 'Featured image and gallery media still need to be assigned.';
		$warnings[] = 'Availability calendar feeds still need to be configured.';

		WP_CLI::line( sprintf( 'Villa: %s', $title ) );
		WP_CLI::line( sprintf( 'Slug: %s', $slug ) );
		WP_CLI::line( sprintf( 'Location: %s', $location->name ) );
		WP_CLI::line( sprintf( 'Bedrooms: %d', count( $data['bedrooms'] ) ) );
		WP_CLI::line( sprintf( 'Rates: %d', count( $data['rates'] ) ) );
		WP_CLI::line( sprintf( 'Generated top-level blocks: %d', count( $parsed_blocks ) ) );
		WP_CLI::line(
			sprintf(
				'Related villas: %s',
				empty( $related['ids'] ) ? 'none' : implode( ', ', $related['ids'] )
			)
		);

		foreach ( array_values( array_unique( $warnings ) ) as $warning ) {
			WP_CLI::warning( $warning );
		}

		if ( $dry_run ) {
			WP_CLI::success( 'Dry run passed. No WordPress content was changed.' );
			return;
		}

		if ( $update_id ) {
			$backup = gutenberg_lab_blocks_villa_importer_backup_draft( $update_id, $backup_dir );

			if ( is_wp_error( $backup ) ) {
				WP_CLI::error( $backup->get_error_message() );
			}

			WP_CLI::line( sprintf( 'Backup: %s', $backup ) );
		}

		$post_args = array(
			'post_type'    => 'villa',
			'post_status'  => 'draft',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_excerpt' => gutenberg_lab_blocks_villa_importer_text( $overview['short_summary'] ),
			'post_content' => $content,
		);

		if ( $update_id ) {
			$post_args['ID'] = $update_id;
			$post_id = wp_update_post( $post_args, true );
		} else {
			$post_id = wp_insert_post( $post_args, true );
		}

		if ( is_wp_error( $post_id ) ) {
			WP_CLI::error( $post_id->get_error_message() );
		}

		$summary_terms = gutenberg_lab_blocks_villa_importer_ensure_summary_terms( $overview );

		if ( is_wp_error( $summary_terms ) ) {
			WP_CLI::error( $summary_terms->get_error_message() );
		}

		wp_set_object_terms( $post_id, array( (int) $location->term_id ), 'villa_location', false );
		wp_set_object_terms( $post_id, $summary_terms, 'villa_amenity', false );

		foreach ( gutenberg_lab_blocks_villa_importer_meta( $data ) as $meta_key => $meta_value ) {
			update_post_meta( $post_id, $meta_key, $meta_value );
		}

		clean_post_cache( $post_id );

		WP_CLI::success(
			sprintf(
				'%s villa draft %d. Review it at %s',
				$update_id ? 'Updated' : 'Created',
				$post_id,
				admin_url( 'post.php?post=' . $post_id . '&action=edit' )
			)
		);
	}
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'barbados villa import-json',
		'Gutenberg_Lab_Blocks_Villa_Import_Command'
	);
}
