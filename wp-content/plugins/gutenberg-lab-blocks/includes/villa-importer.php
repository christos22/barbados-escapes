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
 * Serializes trusted HTML attributes.
 *
 * @param array<string, mixed> $attributes Attribute map.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_html_attributes( $attributes ) {
	$markup = gutenberg_lab_blocks_get_villa_bedroom_selector_attributes( $attributes );

	return '' === $markup ? '' : ' ' . $markup;
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
 * Converts Gutenberg preset tokens to the CSS custom properties saved blocks use.
 *
 * @param mixed $value Raw style value.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_css_value( $value ) {
	$value = trim( (string) $value );

	if ( preg_match( '/^var:preset\|([a-z0-9-]+)\|([a-z0-9-]+)$/i', $value, $matches ) ) {
		return sprintf(
			'var(--wp--preset--%1$s--%2$s)',
			sanitize_key( $matches[1] ),
			sanitize_key( $matches[2] )
		);
	}

	return $value;
}

/**
 * Builds the class list that core blocks save for common supports.
 *
 * @param string|array<int, string> $base_classes Required block classes.
 * @param array<string, mixed>      $attributes Block attributes.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_block_classes( $base_classes, $attributes = array() ) {
	$classes = is_array( $base_classes ) ? $base_classes : array_filter( array( $base_classes ) );

	if ( ! empty( $attributes['className'] ) ) {
		$classes[] = gutenberg_lab_blocks_villa_importer_classes( $attributes['className'] );
	}

	if ( ! empty( $attributes['align'] ) ) {
		$classes[] = 'align' . sanitize_html_class( $attributes['align'] );
	}

	if ( ! empty( $attributes['textColor'] ) ) {
		$classes[] = 'has-' . sanitize_html_class( $attributes['textColor'] ) . '-color';
		$classes[] = 'has-text-color';
	}

	if ( ! empty( $attributes['backgroundColor'] ) ) {
		$classes[] = 'has-' . sanitize_html_class( $attributes['backgroundColor'] ) . '-background-color';
		$classes[] = 'has-background';
	}

	if ( ! empty( $attributes['borderColor'] ) ) {
		$classes[] = 'has-border-color';
		$classes[] = 'has-' . sanitize_html_class( $attributes['borderColor'] ) . '-border-color';
	}

	if ( ! empty( $attributes['fontSize'] ) ) {
		$classes[] = 'has-' . sanitize_html_class( $attributes['fontSize'] ) . '-font-size';
	}

	if ( ! empty( $attributes['fontFamily'] ) ) {
		$classes[] = 'has-' . sanitize_html_class( $attributes['fontFamily'] ) . '-font-family';
	}

	if ( ! empty( $attributes['style']['color']['text'] ) ) {
		$classes[] = 'has-text-color';
	}

	if ( ! empty( $attributes['style']['color']['background'] ) ) {
		$classes[] = 'has-background';
	}

	if ( ! empty( $attributes['style']['border']['color'] ) ) {
		$classes[] = 'has-border-color';
	}

	if ( ! empty( $attributes['style']['elements']['link']['color']['text'] ) ) {
		$classes[] = 'has-link-color';
	}

	return implode( ' ', array_unique( array_filter( $classes ) ) );
}

/**
 * Builds a saved HTML style attribute for the core supports the importer uses.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_style_attr( $attributes ) {
	$styles = array();
	$style  = $attributes['style'] ?? array();

	if ( ! empty( $attributes['width'] ) ) {
		$styles['flex-basis'] = $attributes['width'];
	}

	foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
		if ( isset( $style['spacing']['padding'][ $side ] ) ) {
			$styles[ 'padding-' . $side ] = gutenberg_lab_blocks_villa_importer_css_value( $style['spacing']['padding'][ $side ] );
		}
	}

	foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
		if ( isset( $style['spacing']['margin'][ $side ] ) ) {
			$styles[ 'margin-' . $side ] = gutenberg_lab_blocks_villa_importer_css_value( $style['spacing']['margin'][ $side ] );
		}
	}

	if ( isset( $style['color']['background'] ) ) {
		$styles['background-color'] = gutenberg_lab_blocks_villa_importer_css_value( $style['color']['background'] );
	}

	if ( isset( $style['color']['text'] ) ) {
		$styles['color'] = gutenberg_lab_blocks_villa_importer_css_value( $style['color']['text'] );
	}

	if ( isset( $style['border']['color'] ) ) {
		$styles['border-color'] = gutenberg_lab_blocks_villa_importer_css_value( $style['border']['color'] );
	}

	if ( isset( $style['border']['width'] ) ) {
		$styles['border-width'] = gutenberg_lab_blocks_villa_importer_css_value( $style['border']['width'] );
	}

	if ( isset( $style['border']['top']['color'] ) ) {
		$styles['border-top-color'] = gutenberg_lab_blocks_villa_importer_css_value( $style['border']['top']['color'] );
	}

	if ( isset( $style['border']['top']['width'] ) ) {
		$styles['border-top-width'] = gutenberg_lab_blocks_villa_importer_css_value( $style['border']['top']['width'] );
	}

	if ( isset( $style['typography']['fontStyle'] ) ) {
		$styles['font-style'] = gutenberg_lab_blocks_villa_importer_css_value( $style['typography']['fontStyle'] );
	}

	if ( isset( $style['typography']['fontWeight'] ) ) {
		$styles['font-weight'] = gutenberg_lab_blocks_villa_importer_css_value( $style['typography']['fontWeight'] );
	}

	if ( isset( $style['typography']['lineHeight'] ) ) {
		$styles['line-height'] = gutenberg_lab_blocks_villa_importer_css_value( $style['typography']['lineHeight'] );
	}

	if ( empty( $styles ) ) {
		return '';
	}

	$declarations = array();

	foreach ( $styles as $property => $value ) {
		if ( '' !== (string) $value ) {
			$declarations[] = sanitize_key( $property ) . ':' . esc_attr( $value );
		}
	}

	return empty( $declarations ) ? '' : ' style="' . implode( ';', $declarations ) . '"';
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

	$class_name = gutenberg_lab_blocks_villa_importer_block_classes( array(), $attributes );
	$class      = '' !== $class_name ? ' class="' . esc_attr( $class_name ) . '"' : '';
	$style      = gutenberg_lab_blocks_villa_importer_style_attr( $attributes );

	return sprintf(
		'<!-- wp:paragraph%1$s --><p%2$s%3$s>%4$s</p><!-- /wp:paragraph -->',
		gutenberg_lab_blocks_villa_importer_attributes( $attributes ),
		$class,
		$style,
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

	$classes = gutenberg_lab_blocks_villa_importer_block_classes( 'wp-block-heading', $attributes );
	$style   = gutenberg_lab_blocks_villa_importer_style_attr( $attributes );

	return sprintf(
		'<!-- wp:heading%1$s --><h%2$d class="%3$s"%4$s>%5$s</h%2$d><!-- /wp:heading -->',
		gutenberg_lab_blocks_villa_importer_attributes( $attributes ),
		$level,
		esc_attr( $classes ),
		$style,
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
	$classes = gutenberg_lab_blocks_villa_importer_block_classes( 'wp-block-group', $attributes );
	$style   = gutenberg_lab_blocks_villa_importer_style_attr( $attributes );

	$id = ! empty( $attributes['anchor'] )
		? ' id="' . esc_attr( sanitize_title( $attributes['anchor'] ) ) . '"'
		: '';

	return gutenberg_lab_blocks_villa_importer_block(
		'group',
		$attributes,
		sprintf(
			'<div%1$s class="%2$s"%3$s>%4$s</div>',
			$id,
			esc_attr( $classes ),
			$style,
			$inner_markup
		)
	);
}

/**
 * Builds a static core image block from an attachment ID.
 *
 * The importer only assigns media opportunistically. If no matching attachment
 * exists yet, callers can omit the image card and keep the content editable.
 *
 * @param int $image_id Attachment ID.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_image( $image_id ) {
	$image_id = absint( $image_id );

	if ( ! $image_id ) {
		return '';
	}

	$image_url = wp_get_attachment_image_url( $image_id, 'large' );

	if ( ! $image_url ) {
		return '';
	}

	$image_alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );

	if ( '' === $image_alt ) {
		$image_alt = get_the_title( $image_id );
	}

	return gutenberg_lab_blocks_villa_importer_block(
		'image',
		array(
			'id'              => $image_id,
			'sizeSlug'        => 'large',
			'linkDestination' => 'none',
		),
		sprintf(
			'<figure class="wp-block-image size-large"><img src="%1$s" alt="%2$s" class="wp-image-%3$d"/></figure>',
			esc_url( $image_url ),
			esc_attr( $image_alt ),
			$image_id
		)
	);
}

/**
 * Builds a Villa Spec Item with the static fallback its editor save() expects.
 *
 * The block also renders in PHP, but its JavaScript save function intentionally
 * keeps readable fallback HTML. Saving matching fallback markup avoids editor
 * validation warnings when an imported villa is opened for review.
 *
 * @param array<string, mixed> $attributes Spec item attributes.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_spec_item( $attributes ) {
	$value     = gutenberg_lab_blocks_villa_importer_text( $attributes['value'] ?? '' );
	$label     = gutenberg_lab_blocks_villa_importer_text( $attributes['label'] ?? '' );
	$icon_slug = sanitize_key( $attributes['iconSlug'] ?? '' );
	$icon_size = isset( $attributes['iconSize'] ) ? (float) $attributes['iconSize'] : 0;

	if ( '' === $value && '' === $label && '' === $icon_slug ) {
		return '';
	}

	$block_attributes = array(
		'value'    => $value,
		'label'    => $label,
		'iconSlug' => $icon_slug,
	);

	if ( $icon_size > 0 ) {
		$block_attributes['iconSize'] = $icon_size;
	}

	$classes = array(
		'wp-block-gutenberg-lab-blocks-villa-spec-item',
		'vvm-villa-specs__item',
	);

	if ( '' !== $icon_slug ) {
		$classes[] = 'vvm-villa-specs__item--has-icon';
	}

	$content = '';

	if ( '' !== $icon_slug ) {
		$style = $icon_size > 0
			? sprintf( ' style="%s:%srem"', '--vvm-villa-spec-icon-size', esc_attr( $icon_size ) )
			: '';

		$content .= sprintf(
			'<span class="vvm-villa-specs__icon" aria-hidden="true" data-icon="%1$s"%2$s></span>',
			esc_attr( $icon_slug ),
			$style
		);
	}

	if ( '' !== $value ) {
		$content .= sprintf(
			'<p class="vvm-villa-specs__value">%s</p>',
			esc_html( $value )
		);
	}

	if ( '' !== $label ) {
		$content .= sprintf(
			'<p class="vvm-villa-specs__label">%s</p>',
			esc_html( $label )
		);
	}

	return gutenberg_lab_blocks_villa_importer_block(
		'gutenberg-lab-blocks/villa-spec-item',
		$block_attributes,
		sprintf(
			'<div class="%1$s">%2$s</div>',
			esc_attr( implode( ' ', $classes ) ),
			$content
		)
	);
}

/**
 * Finds a reasonable supporting villa image when media has already been uploaded.
 *
 * @param string              $villa_name Villa title.
 * @param array<int, string>  $preferred_terms Title fragments to prefer.
 * @return int Attachment ID or 0.
 */
function gutenberg_lab_blocks_villa_importer_find_supporting_image_id( $villa_name, $preferred_terms = array() ) {
	$villa_name = gutenberg_lab_blocks_villa_importer_text( $villa_name );

	if ( '' === $villa_name ) {
		return 0;
	}

	$attachments = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_status'    => 'inherit',
			'posts_per_page' => 50,
			's'              => $villa_name,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		)
	);

	if ( empty( $attachments ) ) {
		return 0;
	}

	foreach ( $preferred_terms as $term ) {
		$term = strtolower( gutenberg_lab_blocks_villa_importer_text( $term ) );

		if ( '' === $term ) {
			continue;
		}

		foreach ( $attachments as $attachment_id ) {
			if ( str_contains( strtolower( get_the_title( $attachment_id ) ), $term ) ) {
				return absint( $attachment_id );
			}
		}
	}

	return absint( $attachments[0] );
}

/**
 * Builds a native columns wrapper.
 *
 * @param array<int, string>                $columns Serialized column contents.
 * @param array<string, mixed>              $attributes Optional columns block attributes.
 * @param array<int, array<string, mixed>>  $column_attributes Optional per-column attributes.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_columns( $columns, $attributes = array(), $column_attributes = array() ) {
	$column_markup = '';

	foreach ( array_values( $columns ) as $index => $column ) {
		$current_attributes = $column_attributes[ $index ] ?? array();
		$classes            = gutenberg_lab_blocks_villa_importer_block_classes( 'wp-block-column', $current_attributes );
		$style              = gutenberg_lab_blocks_villa_importer_style_attr( $current_attributes );

		$column_markup .= gutenberg_lab_blocks_villa_importer_block(
			'column',
			$current_attributes,
			sprintf(
				'<div class="%1$s"%2$s>%3$s</div>',
				esc_attr( $classes ),
				$style,
				$column
			)
		);
	}

	$classes = gutenberg_lab_blocks_villa_importer_block_classes( 'wp-block-columns', $attributes );
	$style   = gutenberg_lab_blocks_villa_importer_style_attr( $attributes );

	return gutenberg_lab_blocks_villa_importer_block(
		'columns',
		$attributes,
		sprintf(
			'<div class="%1$s"%2$s>%3$s</div>',
			esc_attr( $classes ),
			$style,
			$column_markup
		)
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
	$item_class  = str_contains( ' ' . $class_name . ' ', ' list-yellow-dots ' )
		? ' class="has-refined-sans-font-family"'
		: '';

	foreach ( array_filter( array_map( 'gutenberg_lab_blocks_villa_importer_text', $items ) ) as $item ) {
		$item_markup .= '<li' . $item_class . '>' . esc_html( $item ) . '</li>';
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
		$row_attributes = array();
		$cells          = $row;

		if ( is_array( $row ) && isset( $row['cells'] ) && is_array( $row['cells'] ) ) {
			$cells          = $row['cells'];
			$row_attributes = isset( $row['attributes'] ) && is_array( $row['attributes'] )
				? $row['attributes']
				: array();
		}

		$body_markup .= '<tr' . gutenberg_lab_blocks_villa_importer_html_attributes( $row_attributes ) . '>';

		foreach ( $cells as $cell ) {
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
 * @param array<string, mixed>              $attributes Optional wrapper block attributes.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_buttons( $buttons, $attributes = array() ) {
	$button_markup = '';

	foreach ( $buttons as $button ) {
		$label = gutenberg_lab_blocks_villa_importer_text( $button['label'] ?? '' );
		$url   = esc_url( $button['url'] ?? '' );

		if ( '' === $label || '' === $url ) {
			continue;
		}

		$class_name        = gutenberg_lab_blocks_villa_importer_classes( $button['class'] ?? '' );
		$button_attributes = '' !== $class_name ? array( 'className' => $class_name ) : array();
		$classes           = 'wp-block-button' . ( '' !== $class_name ? ' ' . $class_name : '' );
		$inner             = sprintf(
			'<div class="%1$s"><a class="wp-block-button__link wp-element-button" href="%2$s">%3$s</a></div>',
			esc_attr( $classes ),
			$url,
			esc_html( $label )
		);

		$button_markup .= gutenberg_lab_blocks_villa_importer_block( 'button', $button_attributes, $inner );
	}

	if ( '' === $button_markup ) {
		return '';
	}

	$wrapper_class_name = isset( $attributes['className'] )
		? gutenberg_lab_blocks_villa_importer_classes( $attributes['className'] )
		: '';
	$wrapper_classes    = 'wp-block-buttons' . ( '' !== $wrapper_class_name ? ' ' . $wrapper_class_name : '' );

	return gutenberg_lab_blocks_villa_importer_block(
		'buttons',
		$attributes,
		'<div class="' . esc_attr( $wrapper_classes ) . '">' . $button_markup . '</div>'
	);
}

/**
 * Builds the static Google Map block markup expected by the map plugin.
 *
 * @param string $address Exact map address or plus-code location.
 * @param string $villa_name Villa name used for a stable block class.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_gmap_block( $address, $villa_name ) {
	$address = gutenberg_lab_blocks_villa_importer_text( $address );

	if ( '' === $address ) {
		return '';
	}

	$unique_id   = 'gmap-block-' . substr( md5( $villa_name ), 0, 8 );
	$height      = 320;
	$block_style = sprintf(
		"\n        \n        .%1\$s.wp-block-gmap-gmap-block iframe{height:%2\$dpx;}\n    \n        @media (max-width: 1024px) and (min-width: 768px) {\n            \n         \n    \n        }\n        @media (max-width: 767px) {\n            \n         \n    \n        }\n    ",
		$unique_id,
		$height
	);
	$iframe_url  = 'https://maps.google.com/maps?' . http_build_query(
		array(
			'q'      => $address,
			'z'      => 15,
			't'      => 'roadmap',
			'output' => 'embed',
		),
		'',
		'&',
		PHP_QUERY_RFC1738
	);

	return gutenberg_lab_blocks_villa_importer_block(
		'gmap/gmap-block',
		array(
			'address'              => $address,
			'zoom'                 => 15,
			'gmap_mapHeightRanges' => array(
				'desk' => $height,
				'tab'  => '',
				'mob'  => '',
			),
			'uniqueId'             => $unique_id,
			'blockStyle'           => $block_style,
		),
		sprintf(
			'<div class="wp-block-gmap-gmap-block %1$s"><iframe src="%2$s" class="embd-map" title="%3$s"></iframe></div>',
			esc_attr( $unique_id ),
			esc_attr( $iframe_url ),
			esc_attr( $address )
		)
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
 * Formats normalized workbook dates for visible villa content.
 *
 * @param mixed $date Date text, usually YYYY-MM-DD from the parser.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_format_date( $date ) {
	$date = gutenberg_lab_blocks_villa_importer_text( $date );
	$timestamp = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date )
		? strtotime( $date . ' 00:00:00 UTC' )
		: false;

	return $timestamp ? gmdate( 'j M Y', $timestamp ) : $date;
}

/**
 * Standard copy hidden from the client workbook to keep the template simpler.
 *
 * @param array<string, mixed> $extras Normalized extras.
 * @return array<string, mixed>
 */
function gutenberg_lab_blocks_villa_importer_default_extras( $extras ) {
	return array_merge(
		array(
			'contact_heading'      => 'Request availability and let us take care of the details.',
			'contact_text'         => 'Tell us your preferred dates and what matters most to you. We will come back with availability, guidance, and any details you need.',
			'whatsapp_label'       => 'WhatsApp Us',
			'pricing_heading'      => 'Seasonal Pricing',
			'pricing_helper'       => 'Need help choosing dates? We can advise on the best timing, rates, and villa fit for your stay.',
			'location_description' => '',
			'related_heading'      => 'Other villas in our collection',
		),
		$extras
	);
}

/**
 * Returns the best available excerpt when the simplified workbook omits one.
 *
 * @param array<string, mixed> $data Normalized workbook data.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_excerpt( $data ) {
	$overview = $data['overview'] ?? array();
	$story    = $data['story'] ?? array();

	return gutenberg_lab_blocks_villa_importer_text(
		$overview['hero_statement']
			?? $story['intro_paragraph_1']
			?? ''
	);
}

/**
 * Returns the standard gold heading attributes used by hand-built villa pages.
 *
 * @return array<string, mixed>
 */
function gutenberg_lab_blocks_villa_importer_gold_heading_attrs() {
	return array(
		'style'     => array(
			'elements' => array(
				'link' => array(
					'color' => array(
						'text' => 'var:preset|color|gold',
					),
				),
			),
		),
		'textColor' => 'gold',
	);
}

/**
 * Returns the standard light-gold heading attributes used on dark cards.
 *
 * @return array<string, mixed>
 */
function gutenberg_lab_blocks_villa_importer_light_gold_heading_attrs() {
	return array(
		'style'     => array(
			'elements' => array(
				'link' => array(
					'color' => array(
						'text' => 'var:preset|color|light-gold',
					),
				),
			),
		),
		'textColor' => 'light-gold',
	);
}

/**
 * Adds one staff row to a normalized visual staff group.
 *
 * @param array<string, array<string, mixed>> $groups Grouped staff cards.
 * @param string                             $group_key Target group.
 * @param string                             $title Display title.
 * @param string                             $label Short eyebrow/arrangement text.
 * @param string                             $detail Staff detail.
 * @return void
 */
function gutenberg_lab_blocks_villa_importer_add_staff_group_detail( &$groups, $group_key, $title, $label, $detail ) {
	if ( '' === $label && '' === $detail ) {
		return;
	}

	if ( ! isset( $groups[ $group_key ] ) ) {
		$groups[ $group_key ] = array(
			'title'   => $title,
			'label'   => $label,
			'details' => array(),
			'dark'    => false,
		);
	}

	if ( '' !== $label && empty( $groups[ $group_key ]['label'] ) ) {
		$groups[ $group_key ]['label'] = $label;
	}

	$groups[ $group_key ]['details'][] = $detail;
}

/**
 * Builds the styled staff cards used by the villa story section.
 *
 * @param array<int, array<string, mixed>> $staff Staff rows from the workbook.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_build_staff_section( $staff ) {
	$groups = array();

	foreach ( $staff as $staff_member ) {
		$role        = gutenberg_lab_blocks_villa_importer_text( $staff_member['role'] ?? '' );
		$arrangement = gutenberg_lab_blocks_villa_importer_text( $staff_member['arrangement'] ?? '' );
		$description = gutenberg_lab_blocks_villa_importer_text( $staff_member['description'] ?? '' );
		$details     = $description;
		$role_key    = strtolower( $role );

		if ( '' === $role || ( '' === $arrangement && '' === $details ) ) {
			continue;
		}

		if ( preg_match( '/housekeep|laund|maid/', $role_key ) ) {
			$label = preg_match( '/laund/', $role_key ) ? 'and laundry' : $arrangement;
			gutenberg_lab_blocks_villa_importer_add_staff_group_detail(
				$groups,
				'housekeeping',
				'Housekeeping',
				$label,
				$details
			);
			continue;
		}

		if ( preg_match( '/chef|cook/', $role_key ) ) {
			gutenberg_lab_blocks_villa_importer_add_staff_group_detail(
				$groups,
				'private-chef',
				'Private Chef',
				'' !== $arrangement ? $arrangement : 'Available on request',
				$details
			);
			$groups['private-chef']['dark'] = true;
			continue;
		}

		gutenberg_lab_blocks_villa_importer_add_staff_group_detail(
			$groups,
			sanitize_title( $role ),
			$role,
			$arrangement,
			$details
		);
	}

	if ( empty( $groups ) ) {
		return '';
	}

	$columns = array(
		gutenberg_lab_blocks_villa_importer_heading(
			'Villa Staff',
			4,
			gutenberg_lab_blocks_villa_importer_gold_heading_attrs()
		) .
		gutenberg_lab_blocks_villa_importer_paragraph(
			'Simple, discreet service designed to keep the stay effortless.',
			array(
				'style'      => array(
					'typography' => array(
						'fontStyle'  => 'normal',
						'fontWeight' => '300',
					),
				),
				'fontFamily' => 'refined-sans',
			)
		),
	);
	$column_attributes = array(
		array(
			'style' => array(
				'spacing' => array(
					'padding' => array(
						'top'    => 'var:preset|spacing|sm',
						'right'  => '0',
						'bottom' => 'var:preset|spacing|sm',
						'left'   => '0',
					),
				),
			),
		),
	);

	foreach ( $groups as $group ) {
		$dark  = ! empty( $group['dark'] );
		$label = gutenberg_lab_blocks_villa_importer_text( $group['label'] ?? '' );
		$copy  = implode( ' ', array_unique( array_map( 'gutenberg_lab_blocks_villa_importer_text', $group['details'] ) ) );

		$card = gutenberg_lab_blocks_villa_importer_heading(
			$group['title'],
			5,
			$dark ? gutenberg_lab_blocks_villa_importer_light_gold_heading_attrs() : array()
		);

		if ( '' !== $label ) {
			$card .= gutenberg_lab_blocks_villa_importer_heading(
				$label,
				4,
				gutenberg_lab_blocks_villa_importer_gold_heading_attrs()
			);
		}

		$paragraph_attrs = array(
			'style'      => array(
				'spacing' => array(
					'padding' => array(
						'top' => 'var:preset|spacing|sm',
					),
				),
				'border'  => array(
					'top' => array(
						'color' => '#efe6d6',
						'width' => '1px',
					),
				),
			),
			'fontFamily' => 'refined-sans',
		);

		if ( $dark ) {
			$paragraph_attrs['textColor']                  = 'light-gold';
			$paragraph_attrs['style']['elements']['link'] = array(
				'color' => array(
					'text' => 'var:preset|color|light-gold',
				),
			);
		}

		if ( '' !== $copy ) {
			$card .= gutenberg_lab_blocks_villa_importer_paragraph( $copy, $paragraph_attrs );
		}

		$columns[] = $card;

		$column_attributes[] = $dark
			? array(
				'backgroundColor' => 'dark-green',
				'style'           => array(
					'spacing' => array(
						'padding' => array(
							'top'    => 'var:preset|spacing|xs',
							'right'  => 'var:preset|spacing|xs',
							'bottom' => 'var:preset|spacing|xs',
							'left'   => 'var:preset|spacing|xs',
						),
					),
				),
			)
			: array(
				'style' => array(
					'color'   => array(
						'background' => '#fbf8f2',
					),
					'spacing' => array(
						'padding' => array(
							'top'    => 'var:preset|spacing|xs',
							'right'  => 'var:preset|spacing|xs',
							'bottom' => 'var:preset|spacing|xs',
							'left'   => 'var:preset|spacing|xs',
						),
					),
					'border'  => array(
						'color' => '#efe6d6',
						'width' => '1px',
					),
				),
			);
	}

	return gutenberg_lab_blocks_villa_importer_columns(
		$columns,
		array(
			'className'       => 'vvm-villa-staff',
			'backgroundColor' => 'white',
			'style'           => array(
				'border' => array(
					'top' => array(
						'color' => 'var:preset|color|gold',
						'width' => '1px',
					),
				),
			),
		),
		$column_attributes
	);
}

/**
 * Builds the editable copy inside the villa gallery hero.
 *
 * Keeping this separate lets clone mode preserve a proven hero/gallery media
 * scaffold while replacing only the client-authored text and CTA copy.
 *
 * @param array<string, mixed> $data Normalized workbook data.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_build_hero_content( $data ) {
	$overview = $data['overview'];

	return
		gutenberg_lab_blocks_villa_importer_heading(
			$overview['hero_location_line'],
			4,
			array_merge(
				gutenberg_lab_blocks_villa_importer_gold_heading_attrs(),
				array( 'className' => 'eyebrow' )
			)
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
}

/**
 * Builds the villa facts strip.
 *
 * In clone mode, the imported villa should inherit the scaffold's visual style.
 * Some older villas use one label-only line per spec, while newer block defaults
 * split values and labels. We replace the facts but follow the source item shape.
 *
 * @param array<string, mixed> $data Normalized workbook data.
 * @param array<string, mixed> $source_block Optional source Villa Specs block or attributes.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_build_villa_specs( $data, $source_block = array() ) {
	$overview     = $data['overview'];
	$attributes   = $source_block['attrs'] ?? $source_block;
	$source_items = array();
	$pool_summary = gutenberg_lab_blocks_villa_importer_text( $overview['pool_summary'] ?? '' );
	$pool_value   = $pool_summary;
	$pool_label   = 'Pool';

	foreach ( $source_block['innerBlocks'] ?? array() as $inner_block ) {
		if ( 'gutenberg-lab-blocks/villa-spec-item' === ( $inner_block['blockName'] ?? '' ) ) {
			$source_items[] = $inner_block['attrs'] ?? array();
		}
	}

	if ( preg_match( '/^([0-9]+(?:\.[0-9]+)?)\s+(.+)$/', $pool_summary, $matches ) ) {
		$pool_value = $matches[1];
		$pool_label = $matches[2];
	}

	$label_specs = array(
		array(
			'label'    => sprintf(
				'%s %s',
				(string) $overview['bedrooms'],
				1 === (int) $overview['bedrooms'] ? 'Bedroom' : 'Bedrooms'
			),
			'iconSlug' => 'bedrooms',
		),
		array(
			'label'    => sprintf(
				'%s %s',
				(string) $overview['bathrooms'],
				1.0 === (float) $overview['bathrooms'] ? 'Bathroom' : 'Bathrooms'
			),
			'iconSlug' => 'bathtub-thick',
		),
		array( 'label' => 'Sleeps ' . (string) $overview['sleeps'], 'iconSlug' => 'people' ),
		array( 'label' => $pool_summary, 'iconSlug' => 'pool-alternative' ),
		array(
			'label'    => 'From ' . gutenberg_lab_blocks_villa_importer_format_usd( $overview['starting_rate_usd'] ) . '/Night',
			'iconSlug' => 'dollar',
		),
	);

	$split_specs = array(
		array(
			'value'    => (string) $overview['bedrooms'],
			'label'    => 1 === (int) $overview['bedrooms'] ? 'Bedroom' : 'Bedrooms',
			'iconSlug' => 'bedrooms',
		),
		array(
			'value'    => (string) $overview['bathrooms'],
			'label'    => 1.0 === (float) $overview['bathrooms'] ? 'Bathroom' : 'Bathrooms',
			'iconSlug' => 'bathtub-thick',
		),
		array(
			'value'    => (string) $overview['sleeps'],
			'label'    => 'Sleeps',
			'iconSlug' => 'people',
		),
		array(
			'value'    => $pool_value,
			'label'    => $pool_label,
			'iconSlug' => 'pool-alternative',
		),
		array(
			'value'    => 'From ' . gutenberg_lab_blocks_villa_importer_format_usd( $overview['starting_rate_usd'] ),
			'label'    => 'Per Night',
			'iconSlug' => 'dollar',
		),
	);
	$spec_markup = '';

	foreach ( $label_specs as $index => $label_spec ) {
		$source_item     = $source_items[ $index ] ?? array();
		$source_value    = gutenberg_lab_blocks_villa_importer_text( $source_item['value'] ?? '' );
		$source_icon     = sanitize_key( $source_item['iconSlug'] ?? '' );
		$source_icon_size = isset( $source_item['iconSize'] ) ? (float) $source_item['iconSize'] : 0;

		$spec = empty( $source_items )
			? $label_spec
			: ( '' !== $source_value ? $split_specs[ $index ] : array( 'label' => $label_spec['label'] ) );

		if ( ! empty( $source_items ) && '' !== $source_icon ) {
			$spec['iconSlug'] = $source_icon;
		}

		if ( $source_icon_size > 0 ) {
			$spec['iconSize'] = $source_icon_size;
		}

		$spec_markup .= gutenberg_lab_blocks_villa_importer_spec_item( $spec );
	}

	return gutenberg_lab_blocks_villa_importer_block(
		'gutenberg-lab-blocks/villa-specs',
		$attributes,
		$spec_markup
	);
}

/**
 * Builds the hero and villa facts strip.
 *
 * @param array<string, mixed> $data Normalized workbook data.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_build_hero( $data ) {
	$hero =
		gutenberg_lab_blocks_villa_importer_block(
			'gutenberg-lab-blocks/villa-gallery-hero-media',
			array( 'lock' => array( 'move' => true, 'remove' => true ) ),
			''
		) .
		gutenberg_lab_blocks_villa_importer_block(
			'gutenberg-lab-blocks/villa-gallery-hero-content',
			array( 'lock' => array( 'move' => true, 'remove' => true ) ),
			gutenberg_lab_blocks_villa_importer_build_hero_content( $data )
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

	return $hero . "\n\n" . gutenberg_lab_blocks_villa_importer_build_villa_specs( $data );
}

/**
 * Builds the main story, highlights, nearby, and staff columns.
 *
 * @param array<string, mixed> $data Normalized workbook data.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_build_story_columns( $data ) {
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
		gutenberg_lab_blocks_villa_importer_heading(
			$story['story_eyebrow'],
			4,
			gutenberg_lab_blocks_villa_importer_gold_heading_attrs()
		) .
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
		$left .= gutenberg_lab_blocks_villa_importer_build_staff_section( $data['staff'] );
	}

	$right =
		gutenberg_lab_blocks_villa_importer_group(
			gutenberg_lab_blocks_villa_importer_heading(
				'Why We Love It',
				4,
				gutenberg_lab_blocks_villa_importer_gold_heading_attrs()
			) .
			gutenberg_lab_blocks_villa_importer_heading(
				$story['why_love_headline'],
				3,
				gutenberg_lab_blocks_villa_importer_light_gold_heading_attrs()
			) .
			gutenberg_lab_blocks_villa_importer_list( $highlights, 'list-yellow-dots' ),
			array(
				'backgroundColor' => 'dark-green',
				'style'           => array(
					'spacing' => array(
						'padding' => array(
							'top'    => 'var:preset|spacing|md',
							'right'  => 'var:preset|spacing|md',
							'bottom' => 'var:preset|spacing|md',
							'left'   => 'var:preset|spacing|md',
						),
					),
				),
				'layout'          => array(
					'type' => 'constrained',
				),
			)
		) .
		gutenberg_lab_blocks_villa_importer_group(
			gutenberg_lab_blocks_villa_importer_heading(
				'Nearby',
				4,
				gutenberg_lab_blocks_villa_importer_gold_heading_attrs()
			) .
			gutenberg_lab_blocks_villa_importer_table( $nearby, 'table-singe-border-bottom' ),
			array(
				'style'  => array(
					'color'   => array(
						'background' => '#efe6d6',
					),
					'spacing' => array(
						'padding' => array(
							'top'    => 'var:preset|spacing|md',
							'right'  => 'var:preset|spacing|md',
							'bottom' => 'var:preset|spacing|md',
							'left'   => 'var:preset|spacing|md',
						),
					),
				),
				'layout' => array(
					'type' => 'constrained',
				),
			)
		);

	return gutenberg_lab_blocks_villa_importer_columns(
		array( $left, $right ),
		array(
			'className' => 'vvm-villa-story-columns',
			'style'     => array(
				'spacing' => array(
					'padding'  => array(
						'top'    => 'var:preset|spacing|xl',
						'bottom' => 'var:preset|spacing|lg',
					),
					'blockGap' => array(
						'left' => 'var:preset|spacing|xl',
					),
				),
			),
		),
		array(
			array(
				'width' => '60%',
				'style' => array(
					'spacing' => array(
						'padding' => array(
							'top'    => 'var:preset|spacing|md',
							'bottom' => 'var:preset|spacing|md',
						),
					),
				),
			),
			array(
				'width' => '40%',
			),
		)
	);
}

/**
 * Builds the editable text portion of the editorial perspective section.
 *
 * @param array<string, mixed> $data Normalized workbook data.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_build_perspective_text( $data ) {
	$story       = $data['story'];
	$perspective = '';

	foreach ( range( 1, 3 ) as $index ) {
		$perspective .= gutenberg_lab_blocks_villa_importer_paragraph( $story[ 'natalie_paragraph_' . $index ] ?? '' );
	}

	return gutenberg_lab_blocks_villa_importer_heading(
		'Natalie’s Villa Perspective',
		4,
		gutenberg_lab_blocks_villa_importer_gold_heading_attrs()
	) .
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
	);
}

/**
 * Builds the editorial perspective section.
 *
 * @param array<string, mixed> $data Normalized workbook data.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_build_perspective( $data ) {
	return gutenberg_lab_blocks_villa_importer_group(
		gutenberg_lab_blocks_villa_importer_build_perspective_text( $data ),
		array( 'className' => 'vvm-villa-perspective' )
	);
}

/**
 * Builds the main story, highlights, nearby, staff, and editorial perspective.
 *
 * @param array<string, mixed> $data Normalized workbook data.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_build_story( $data ) {
	return gutenberg_lab_blocks_villa_importer_build_story_columns( $data ) .
		"\n\n" .
		gutenberg_lab_blocks_villa_importer_build_perspective( $data );
}

/**
 * Builds one compact bedroom carousel detail line.
 *
 * @param array<string, mixed> $bedroom Normalized bedroom row.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_bedroom_gallery_detail( $bedroom ) {
	$details           = array();
	$bed_configuration = gutenberg_lab_blocks_villa_importer_text( $bedroom['bed_configuration'] ?? '' );

	if ( '' !== $bed_configuration ) {
		$details[] = $bed_configuration;
	}

	$ensuite = strtolower( gutenberg_lab_blocks_villa_importer_text( $bedroom['ensuite'] ?? '' ) );

	if ( 'yes' === $ensuite ) {
		$details[] = 'En-suite';
	} elseif ( 'no' === $ensuite ) {
		$details[] = 'Shared bathroom';
	}

	$feature_candidates = array_merge(
		gutenberg_lab_blocks_villa_importer_split_list( $bedroom['views'] ?? '' ),
		gutenberg_lab_blocks_villa_importer_split_list( $bedroom['features'] ?? '' )
	);

	foreach ( $feature_candidates as $feature ) {
		$feature = gutenberg_lab_blocks_villa_importer_text( $feature );

		if ( '' !== $feature ) {
			$details[] = $feature;
			break;
		}
	}

	return implode( ' · ', array_slice( array_filter( $details ), 0, 3 ) );
}

/**
 * Builds one bedroom gallery carousel slide block.
 *
 * The workbook currently treats photos as a WordPress follow-up task, but the
 * importer accepts optional image fields so future AI-assisted passes can add
 * slide images without changing the layout contract.
 *
 * @param array<string, mixed> $bedroom Normalized bedroom row.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_bedroom_gallery_slide( $bedroom ) {
	$image_id  = absint( $bedroom['image_id'] ?? $bedroom['attachment_id'] ?? 0 );
	$image_url = gutenberg_lab_blocks_villa_importer_text( $bedroom['image_url'] ?? $bedroom['photo_url'] ?? '' );
	$image_alt = gutenberg_lab_blocks_villa_importer_text( $bedroom['image_alt'] ?? '' );

	if ( $image_id && '' === $image_url ) {
		$image_url = wp_get_attachment_image_url( $image_id, 'full' );
	}

	if ( $image_id && '' === $image_alt ) {
		$image_alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
	}

	$attributes = array(
		'eyebrow' => gutenberg_lab_blocks_villa_importer_text( $bedroom['area'] ?? 'Bedroom' ),
		'title'   => gutenberg_lab_blocks_villa_importer_text( $bedroom['room_name'] ?? 'Bedroom' ),
		'detail'  => gutenberg_lab_blocks_villa_importer_bedroom_gallery_detail( $bedroom ),
	);

	if ( $image_id ) {
		$attributes['imageId'] = $image_id;
	}

	if ( '' !== $image_url ) {
		$attributes['imageUrl'] = $image_url;
	}

	if ( '' !== $image_alt ) {
		$attributes['imageAlt'] = $image_alt;
	}

	return gutenberg_lab_blocks_villa_importer_self_closing_block(
		'gutenberg-lab-blocks/villa-gallery-carousel-slide',
		array_filter(
			$attributes,
			static function ( $value ) {
				return '' !== $value && null !== $value;
			}
		)
	);
}

/**
 * Splits a bedroom name into an eyebrow and title for the level cards.
 *
 * @param string $room_name Workbook bedroom name.
 * @return array{eyebrow:string,title:string}
 */
function gutenberg_lab_blocks_villa_importer_bedroom_card_title_parts( $room_name ) {
	$room_name = gutenberg_lab_blocks_villa_importer_text( $room_name );

	if ( preg_match( '/^(.*?)\s*\((.*?)\)$/', $room_name, $matches ) ) {
		return array(
			'eyebrow' => gutenberg_lab_blocks_villa_importer_text( $matches[1] ),
			'title'   => gutenberg_lab_blocks_villa_importer_text( $matches[2] ),
		);
	}

	return array(
		'eyebrow' => $room_name,
		'title'   => $room_name,
	);
}

/**
 * Builds one chip or badge for a bedroom level card.
 *
 * @param string $text       Chip text.
 * @param string $class_name Chip class name.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_bedroom_level_chip( $text, $class_name ) {
	return gutenberg_lab_blocks_villa_importer_paragraph(
		strtoupper( gutenberg_lab_blocks_villa_importer_text( $text ) ),
		array( 'className' => $class_name )
	);
}

/**
 * Builds feature chips for one bedroom level card.
 *
 * @param array<string, mixed> $bedroom Normalized bedroom row.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_bedroom_level_chips( $bedroom ) {
	$chips             = '';
	$bed_configuration = gutenberg_lab_blocks_villa_importer_text(
		$bedroom['bed_configuration'] ?? ''
	);
	$ensuite           = gutenberg_lab_blocks_villa_importer_boolean(
		$bedroom['ensuite'] ?? false,
		false
	);
	$features          = preg_split(
		'/[,;]+/',
		gutenberg_lab_blocks_villa_importer_text( $bedroom['features'] ?? '' )
	);

	if ( '' !== $bed_configuration ) {
		$chips .= gutenberg_lab_blocks_villa_importer_bedroom_level_chip(
			$bed_configuration,
			'vvm-bedroom-levels__chip vvm-bedroom-levels__chip--bed'
		);
	}

	if ( $ensuite ) {
		$chips .= gutenberg_lab_blocks_villa_importer_bedroom_level_chip(
			'Ensuite',
			'vvm-bedroom-levels__badge'
		);
	}

	foreach ( $features as $feature ) {
		$feature = gutenberg_lab_blocks_villa_importer_text( $feature );

		if ( '' === $feature ) {
			continue;
		}

		$chips .= gutenberg_lab_blocks_villa_importer_bedroom_level_chip(
			$feature,
			'vvm-bedroom-levels__chip'
		);
	}

	if ( '' === $chips ) {
		return '';
	}

	return gutenberg_lab_blocks_villa_importer_group(
		$chips,
		array( 'className' => 'vvm-bedroom-levels__chips' )
	);
}

/**
 * Builds the area intro copy for a bedroom level panel.
 *
 * @param string $area  Bedroom area.
 * @param int    $count Room count.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_bedroom_level_intro_copy( $area, $count ) {
	$area_lower = strtolower( gutenberg_lab_blocks_villa_importer_text( $area ) );

	if ( str_contains( $area_lower, 'ground' ) ) {
		return sprintf(
			_n(
				'One bedroom arranged on the ground floor for easy access to the villa living spaces and outdoor areas.',
				'%d bedrooms arranged on the ground floor for easy access to the villa living spaces and outdoor areas.',
				$count,
				'gutenberg-lab-blocks'
			),
			$count
		);
	}

	return sprintf(
		_n(
			'One bedroom arranged in this area for a flexible villa stay.',
			'%d bedrooms arranged in this area for a flexible villa stay.',
			$count,
			'gutenberg-lab-blocks'
		),
		$count
	);
}

/**
 * Builds the bedroom-level grid used inside the Bedrooms tab.
 *
 * @param array<int, array<string, mixed>> $bedrooms Normalized bedroom rows.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_build_bedroom_levels( $bedrooms ) {
	$groups = array();

	foreach ( $bedrooms as $bedroom ) {
		$area = gutenberg_lab_blocks_villa_importer_text(
			$bedroom['area'] ?? __( 'Bedrooms', 'gutenberg-lab-blocks' )
		);

		if ( '' === $area ) {
			$area = __( 'Bedrooms', 'gutenberg-lab-blocks' );
		}

		$groups[ $area ][] = $bedroom;
	}

	if ( empty( $groups ) ) {
		return '';
	}

	$nav    = '';
	$panels = '';
	$index  = 0;

	foreach ( $groups as $area => $rooms ) {
		$is_active     = 0 === $index;
		$anchor        = 'bedrooms-' . sanitize_title( $area );
		$button_class  = 'vvm-bedroom-levels__nav-button' . ( $is_active ? ' is-active' : '' );
		$panel_class   = 'vvm-bedroom-levels__panel vvm-bedroom-levels__grid' . ( $is_active ? ' is-active' : '' );
		$room_count    = count( $rooms );
		$room_cards    = '';
		$room_count_text = sprintf(
			_n( '%d ROOM', '%d ROOMS', $room_count, 'gutenberg-lab-blocks' ),
			$room_count
		);

		$nav .= gutenberg_lab_blocks_villa_importer_block(
			'button',
			array( 'className' => $button_class ),
			sprintf(
				'<div class="wp-block-button %1$s"><a class="wp-block-button__link wp-element-button" href="#%2$s">%3$s</a></div>',
				esc_attr( $button_class ),
				esc_attr( $anchor ),
				esc_html( strtoupper( $area ) )
			)
		);

		foreach ( $rooms as $room ) {
			$title_parts = gutenberg_lab_blocks_villa_importer_bedroom_card_title_parts(
				$room['room_name'] ?? ''
			);
			$chips       = gutenberg_lab_blocks_villa_importer_bedroom_level_chips( $room );
			$description = gutenberg_lab_blocks_villa_importer_text( $room['description'] ?? '' );

			$room_cards .= gutenberg_lab_blocks_villa_importer_group(
				gutenberg_lab_blocks_villa_importer_paragraph(
					$title_parts['eyebrow'],
					array( 'className' => 'vvm-bedroom-levels__eyebrow' )
				) .
				gutenberg_lab_blocks_villa_importer_heading(
					$title_parts['title'],
					3,
					array( 'className' => 'vvm-bedroom-levels__title' )
				) .
				$chips .
				gutenberg_lab_blocks_villa_importer_paragraph(
					$description,
					array( 'className' => 'vvm-bedroom-levels__copy' )
				),
				array( 'className' => 'vvm-bedroom-levels__card vvm-bedroom-levels__room-card' )
			);
		}

		$intro_card = gutenberg_lab_blocks_villa_importer_group(
			gutenberg_lab_blocks_villa_importer_paragraph(
				strtoupper( $area ),
				array( 'className' => 'vvm-bedroom-levels__eyebrow' )
			) .
			gutenberg_lab_blocks_villa_importer_heading(
				$area,
				3,
				array( 'className' => 'vvm-bedroom-levels__title' )
			) .
			gutenberg_lab_blocks_villa_importer_paragraph(
				gutenberg_lab_blocks_villa_importer_bedroom_level_intro_copy(
					$area,
					$room_count
				),
				array(
					'className'  => 'vvm-bedroom-levels__copy',
					'fontFamily' => 'refined-sans',
				)
			) .
			gutenberg_lab_blocks_villa_importer_paragraph(
				$room_count_text,
				array( 'className' => 'vvm-bedroom-levels__room-count' )
			) .
			gutenberg_lab_blocks_villa_importer_block(
				'separator',
				array( 'className' => 'vvm-bedroom-levels__divider' ),
				'<hr class="wp-block-separator has-alpha-channel-opacity vvm-bedroom-levels__divider"/>'
			) .
			gutenberg_lab_blocks_villa_importer_paragraph(
				count( $groups ) > 1
					? __( 'Select another level using the tabs above.', 'gutenberg-lab-blocks' )
					: __( 'Bedroom details are generated from the villa workbook.', 'gutenberg-lab-blocks' ),
				array( 'className' => 'vvm-bedroom-levels__hint' )
			),
			array( 'className' => 'vvm-bedroom-levels__card vvm-bedroom-levels__intro-card' )
		);

		$panels .= gutenberg_lab_blocks_villa_importer_group(
			$intro_card . $room_cards,
			array(
				'className' => $panel_class,
				'layout'    => array( 'type' => 'default' ),
				'anchor'    => $anchor,
			)
		);

		++$index;
	}

	$nav = gutenberg_lab_blocks_villa_importer_block(
		'buttons',
		array(
			'className' => 'vvm-bedroom-levels__nav',
			'layout'    => array(
				'type'           => 'flex',
				'justifyContent' => 'left',
				'flexWrap'       => 'wrap',
			),
		),
		'<div class="wp-block-buttons vvm-bedroom-levels__nav">' . $nav . '</div>'
	);

	return gutenberg_lab_blocks_villa_importer_group(
		$nav . $panels,
		array(
			'className' => 'vvm-bedroom-levels',
			'layout'    => array( 'type' => 'default' ),
		)
	);
}

/**
 * Builds the Bedrooms Stack Tab content.
 *
 * @param array<string, mixed> $data Normalized workbook data.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_build_bedrooms_tab( $data ) {
	$bedrooms      = array_values( array_filter( $data['bedrooms'] ?? array() ) );
	$bedroom_count = absint( $data['overview']['bedrooms'] ?? count( $bedrooms ) );
	$slide_markup  = '';

	foreach ( $bedrooms as $bedroom ) {
		$slide_markup .= gutenberg_lab_blocks_villa_importer_bedroom_gallery_slide( $bedroom ) . "\n\n";
	}

	$bedroom_heading = 1 === $bedroom_count
		? 'One thoughtfully arranged bedroom across the villa.'
		: sprintf( '%d thoughtfully arranged bedrooms across the villa.', $bedroom_count );
	$content =
		gutenberg_lab_blocks_villa_importer_heading(
			'BEDROOM LAYOUT',
			4,
			gutenberg_lab_blocks_villa_importer_gold_heading_attrs()
		) .
		gutenberg_lab_blocks_villa_importer_heading(
			$bedroom_heading,
			2
		) .
		gutenberg_lab_blocks_villa_importer_paragraph(
			'Bedrooms are arranged to give guests a clear, easy-to-scan view of the villa’s principal sleeping spaces.',
			array(
				'style'      => array(
					'color'      => array(
						'text' => 'rgba(23, 53, 40, 0.68)',
					),
					'typography' => array(
						'lineHeight' => '1.75',
					),
				),
				'fontFamily' => 'refined-sans',
			),
		) .
		'<!-- wp:separator {"style":{"color":{"background":"rgba(23, 53, 40, 0.12)"}}} --><hr class="wp-block-separator has-text-color has-alpha-channel-opacity has-background" style="background-color:rgba(23, 53, 40, 0.12);color:rgba(23, 53, 40, 0.12)"/><!-- /wp:separator -->' .
		gutenberg_lab_blocks_villa_importer_heading(
			'BEDROOM GALLERY',
			4,
			array_merge(
				gutenberg_lab_blocks_villa_importer_gold_heading_attrs(),
				array(
					'style' => array(
						'spacing' => array(
							'margin' => array(
								'top' => 'var:preset|spacing|md',
							),
						),
					),
				)
			)
		) .
		gutenberg_lab_blocks_villa_importer_paragraph(
			'A refined preview of the villa’s principal sleeping spaces.',
			array(
				'style'      => array(
					'color'      => array(
						'text' => 'rgba(23, 53, 40, 0.68)',
					),
					'typography' => array(
						'lineHeight' => '1.65',
					),
				),
				'fontFamily' => 'refined-sans',
			)
		) .
		gutenberg_lab_blocks_villa_importer_block(
			'gutenberg-lab-blocks/villa-gallery-carousel',
			array(
				'isCardInteractive' => false,
				'showCaption'       => false,
			),
			trim( $slide_markup )
		) .
		gutenberg_lab_blocks_villa_importer_build_bedroom_levels( $bedrooms );

	return gutenberg_lab_blocks_villa_importer_block(
		'gutenberg-lab-blocks/stack-tab',
		array( 'label' => 'Bedrooms' ),
		gutenberg_lab_blocks_villa_importer_group(
			$content,
			array(
				'style'  => array(
					'spacing' => array(
						'blockGap' => 'var:preset|spacing|sm',
					),
				),
				'layout' => array( 'type' => 'default' ),
			)
		)
	);
}

/**
 * Returns presentation copy for known amenity groups.
 *
 * The workbook stays simple for clients: they only provide a group and item.
 * These presets keep imported villas aligned with the approved Monkey Hill
 * card hierarchy without asking clients to manage design-specific fields.
 *
 * @param string $group Amenity group label from the workbook.
 * @return array{eyebrow: string, title: string, copy: string, chips: array<int, string>}
 */
function gutenberg_lab_blocks_villa_importer_amenity_group_meta( $group ) {
	$group = gutenberg_lab_blocks_villa_importer_text( $group );
	$key   = preg_replace( '/[^a-z0-9]+/', ' ', strtolower( $group ) );
	$key   = trim( preg_replace( '/\s+/', ' ', $key ) );

	$presets = array(
		'inside the villa' => array(
			'eyebrow' => 'Refined Interior Living',
			'title'   => 'Inside the Villa',
			'copy'    => 'Spacious, elegant, and quietly equipped for long, comfortable stays with family or guests.',
			'chips'   => array( 'Media Room', 'Gourmet Kitchen', 'High-Speed WiFi', 'Ensuite Bedrooms' ),
		),
		'outdoor living' => array(
			'eyebrow' => 'Outdoor Living & Amenities',
			'title'   => 'Outdoor Living',
			'copy'    => 'Landscaped grounds, beach access, and open-air spaces designed for long Caribbean days.',
			'chips'   => array( 'Private Pool', 'Outdoor Dining', 'Beach Access', 'Garden Living' ),
		),
		'resort community' => array(
			'eyebrow' => 'Resort & Community',
			'title'   => 'Resort & Community',
			'copy'    => 'Shared resort privileges and community amenities that extend the villa experience.',
			'chips'   => array( 'Club Access', 'Beach Access', 'Fitness', 'Security' ),
		),
		'services staff' => array(
			'eyebrow' => 'Services & Staff',
			'title'   => 'Services & Staff',
			'copy'    => 'Thoughtful service touches that keep each stay comfortable, polished, and easy.',
			'chips'   => array( 'Housekeeping', 'Concierge', 'Chef Available', 'Laundry' ),
		),
		'technology entertainment' => array(
			'eyebrow' => 'Technology & Entertainment',
			'title'   => 'Technology & Entertainment',
			'copy'    => 'Connected comforts and entertainment essentials for relaxed days and quiet evenings.',
			'chips'   => array( 'WiFi', 'TV', 'Sound System', 'Media Room' ),
		),
		'family features' => array(
			'eyebrow' => 'Family Features',
			'title'   => 'Family Features',
			'copy'    => 'Practical details that help families settle in and enjoy the villa with ease.',
			'chips'   => array( 'Flexible Rooms', 'Pool', 'Kitchen', 'Laundry' ),
		),
	);

	if ( isset( $presets[ $key ] ) ) {
		return $presets[ $key ];
	}

	return array(
		'eyebrow' => $group,
		'title'   => $group,
		'copy'    => 'A curated set of villa amenities designed for a comfortable Barbados stay.',
		'chips'   => array(),
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

	foreach ( array_keys( $groups ) as $group_index => $group ) {
		$amenities = $groups[ $group ];
		$meta      = gutenberg_lab_blocks_villa_importer_amenity_group_meta( $group );
		$rows  = '';
		$index = 1;

		foreach ( $amenities as $amenity ) {
			$item = $amenity['item'] ?? '';

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

		$chips = '';

		foreach ( $meta['chips'] as $chip ) {
			$chips .= gutenberg_lab_blocks_villa_importer_paragraph(
				$chip,
				array( 'className' => 'vvm-villa-amenities__chip' )
			);
		}

		$card_class = 'vvm-villa-amenities__card';

		if ( 1 === ( $group_index % 2 ) ) {
			$card_class .= ' vvm-villa-amenities__card--dark';
		}

		$content .= gutenberg_lab_blocks_villa_importer_group(
			gutenberg_lab_blocks_villa_importer_group(
				gutenberg_lab_blocks_villa_importer_group(
					gutenberg_lab_blocks_villa_importer_paragraph(
						$meta['eyebrow'],
						array( 'className' => 'vvm-villa-amenities__eyebrow' )
					) .
					gutenberg_lab_blocks_villa_importer_heading(
						$meta['title'],
						3,
						array( 'className' => 'vvm-villa-amenities__title' )
					) .
					gutenberg_lab_blocks_villa_importer_paragraph(
						$meta['copy'],
						array( 'className' => 'vvm-villa-amenities__copy' )
					),
					array( 'className' => 'vvm-villa-amenities__intro' )
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
			array( 'className' => $card_class )
		);
	}

	return gutenberg_lab_blocks_villa_importer_block(
		'gutenberg-lab-blocks/stack-tab',
		array( 'label' => 'Amenities' ),
		gutenberg_lab_blocks_villa_importer_group(
			$content,
			array( 'className' => 'vvm-villa-amenities vvm-villa-amenities--show-numbers' )
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
	$rows      = '';
	$overview  = $data['overview'] ?? array();
	$image_id  = gutenberg_lab_blocks_villa_importer_find_supporting_image_id(
		$overview['villa_name'] ?? '',
		array( 'gazebo', 'garden', 'terrace', 'pool', 'outside' )
	);
	$image     = gutenberg_lab_blocks_villa_importer_image( $image_id );

	foreach ( $data['rules'] as $rule ) {
		$label   = gutenberg_lab_blocks_villa_importer_text( $rule['rule'] ?? '' );
		$details = gutenberg_lab_blocks_villa_importer_text( $rule['details'] ?? '' );

		if ( '' === $label && '' === $details ) {
			continue;
		}

		$rows .= gutenberg_lab_blocks_villa_importer_group(
			gutenberg_lab_blocks_villa_importer_block(
				'paragraph',
				array( 'className' => 'vvm-villa-amenities__item' ),
				sprintf(
					'<p class="vvm-villa-amenities__item"><strong>%1$s</strong>%2$s</p>',
					esc_html( $label ),
					esc_html( $details )
				)
			),
			array( 'className' => 'vvm-villa-amenities__row vvm-villa-rules__row' )
		);
	}

	$content_card = gutenberg_lab_blocks_villa_importer_group(
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

	if ( '' !== $image ) {
		$content_card .= gutenberg_lab_blocks_villa_importer_group(
			$image,
			array( 'className' => 'vvm-villa-amenities__card' )
		);
	}

	return gutenberg_lab_blocks_villa_importer_block(
		'gutenberg-lab-blocks/stack-tab',
		array( 'label' => 'House Rules' ),
		gutenberg_lab_blocks_villa_importer_group(
			gutenberg_lab_blocks_villa_importer_group( $content_card ),
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
	$overview    = $data['overview'];
	$extras      = gutenberg_lab_blocks_villa_importer_default_extras( $data['extras'] );
	$rate_rows   = array( array( 'Season', 'Rate per night', 'Minimum stay' ) );
	$map_address = gutenberg_lab_blocks_villa_importer_text(
		$overview['map_address'] ?? ( $overview['display_address'] ?? '' )
	);
	$map_link    = $overview['google_maps_link'] ?? '';
	$coordinates = gutenberg_lab_blocks_villa_importer_extract_coordinates(
		trim( ( $overview['coordinates'] ?? '' ) . ' ' . $map_link )
	);

	$has_short_maps_link = is_string( $map_link ) && preg_match( '#https?://maps\.app\.goo\.gl/#i', $map_link );

	if ( ! empty( $coordinates ) && ( '' === $map_link || $has_short_maps_link ) ) {
		$map_link = sprintf(
			'https://www.google.com/maps/search/?api=1&query=%1$s,%2$s',
			rawurlencode( $coordinates['latitude'] ),
			rawurlencode( $coordinates['longitude'] )
		);
	}

	foreach ( $data['rates'] as $rate ) {
		$date_range = sprintf(
			'%s - %s',
			gutenberg_lab_blocks_villa_importer_format_date( $rate['start_date'] ),
			gutenberg_lab_blocks_villa_importer_format_date( $rate['end_date'] )
		);
		$rate_label = gutenberg_lab_blocks_villa_importer_text( $rate['rate_label'] ?? ( $rate['season'] ?? '' ) );
		$season = '' !== $rate_label
			? sprintf( '%s (%s)', $rate_label, $date_range )
			: $date_range;

		$rate_rows[] = array(
			'cells' => array(
				$season,
				gutenberg_lab_blocks_villa_importer_format_usd( $rate['nightly_rate_usd'] ),
				sprintf( '%d nights', (int) $rate['minimum_nights'] ),
			),
		);
	}

	$booking_terms            = '';
	$bedroom_selector_enabled = gutenberg_lab_blocks_villa_importer_boolean(
		$overview['bedroom_selector_enabled'] ?? false,
		false
	);
	$selector_attrs           = array();
	$bedroom_selector_choices = gutenberg_lab_blocks_sanitize_villa_bedroom_choice_rows(
		$overview['bedroom_selector_choices'] ?? array()
	);
	$minimum_bedroom_choice   = min(
		max( 1, absint( $overview['minimum_bedroom_choice'] ?? 1 ) ),
		max( 1, absint( $overview['bedrooms'] ?? 1 ) )
	);

	if ( ! empty( $bedroom_selector_choices ) ) {
		$selector_attrs['bedroomChoices'] = $bedroom_selector_choices;
	} elseif ( $minimum_bedroom_choice > 1 ) {
		$selector_attrs['minimumBedrooms'] = $minimum_bedroom_choice;
	}

	$selector_markup = $bedroom_selector_enabled
		? gutenberg_lab_blocks_villa_importer_self_closing_block(
			'gutenberg-lab-blocks/villa-bedroom-selector',
			$selector_attrs
		)
		: '';
	$pricing_intro_class = $bedroom_selector_enabled
		? 'vvm-villa-pricing__intro vvm-villa-pricing__intro--has-bedroom-selector'
		: 'vvm-villa-pricing__intro';

	foreach ( range( 1, 3 ) as $index ) {
		$booking_terms .= gutenberg_lab_blocks_villa_importer_paragraph(
			$extras[ 'booking_terms_' . $index ] ?? '',
			array(
				'fontSize'   => 'xs',
				'fontFamily' => 'refined-sans',
				'style'      => array(
					'typography' => array(
						'fontStyle'  => 'normal',
						'fontWeight' => '300',
					),
				),
			)
		);
	}

	$pricing_intro = gutenberg_lab_blocks_villa_importer_group(
		gutenberg_lab_blocks_villa_importer_paragraph(
			'Rates & Stay Requirements',
			array( 'className' => 'vvm-villa-amenities__eyebrow' )
		) .
		gutenberg_lab_blocks_villa_importer_heading( $extras['pricing_heading'], 3 ) .
		$selector_markup,
		array(
			'className' => $pricing_intro_class,
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
		gutenberg_lab_blocks_villa_importer_paragraph(
			$extras['tax_note'],
			array(
				'style'      => array(
					'elements' => array(
						'link' => array(
							'color' => array(
								'text' => 'var:preset|color|dark-green',
							),
						),
					),
					'spacing'  => array(
						'padding' => array(
							'left'  => '14px',
							'right' => '33px',
						),
					),
				),
				'textColor'  => 'dark-green',
				'fontSize'   => 'lg',
				'fontFamily' => 'refined-sans',
			)
		) .
		gutenberg_lab_blocks_villa_importer_paragraph(
			$extras['security_deposit_note'],
			array(
				'style'      => array(
					'elements'   => array(
						'link' => array(
							'color' => array(
								'text' => 'var:preset|color|gold',
							),
						),
					),
					'spacing'    => array(
						'padding' => array(
							'left'  => '14px',
							'right' => '33px',
						),
					),
					'typography' => array(
						'fontStyle'  => 'normal',
						'fontWeight' => '300',
					),
				),
				'textColor'  => 'gold',
				'fontSize'   => 'xs',
				'fontFamily' => 'refined-sans',
			)
		);

	if ( '' !== $booking_terms ) {
		$booking_terms_block = gutenberg_lab_blocks_villa_importer_block(
			'gutenberg-lab-blocks/read-more',
			array(
				'readMoreLabel' => 'Read Booking Terms',
				'readLessLabel' => 'Close Booking Terms',
				'style'         => array(
					'spacing' => array(
						'padding' => array(
							'top' => 'var:preset|spacing|md',
						),
					),
				),
			),
			$booking_terms
		);

		$pricing .= gutenberg_lab_blocks_villa_importer_group(
			$booking_terms_block,
			array(
				'className'       => 'vvm-villa-pricing__terms',
				'backgroundColor' => 'ivory',
				'borderColor'     => 'gold',
				'style'           => array(
					'spacing' => array(
						'margin'   => array(
							'top'    => 'var:preset|spacing|md',
							'bottom' => '0px',
						),
						'padding'  => array(
							'top'    => 'var:preset|spacing|0',
							'bottom' => 'var:preset|spacing|md',
						),
						'blockGap' => 'var:preset|spacing|sm',
					),
					'border'  => array(
						'width' => '1px',
					),
				),
				'layout'          => array(
					'type' => 'default',
				),
			)
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
		gutenberg_lab_blocks_villa_importer_group(
			gutenberg_lab_blocks_villa_importer_paragraph(
				$extras['contact_eyebrow'],
				array( 'className' => 'vvm-villa-amenities__eyebrow' )
			) .
			gutenberg_lab_blocks_villa_importer_heading( $extras['contact_heading'], 3 ) .
			gutenberg_lab_blocks_villa_importer_paragraph(
				$extras['contact_text'],
				array( 'className' => 'vvm-villa-amenities__copy' )
			),
			array( 'className' => 'vvm-villa-contact__intro' )
		) .
		gutenberg_lab_blocks_villa_importer_contact_form_block( $contact_form ) .
		gutenberg_lab_blocks_villa_importer_buttons(
			array(
				array(
					'label' => $extras['whatsapp_label'] ?? 'WhatsApp Us',
					'url'   => '#request-availability',
					'class' => 'vvm-villa-contact__whatsapp vvm-contact-widget-trigger',
				),
			),
			array(
				'className' => 'vvm-villa-contact__whatsapp-row',
				'layout'    => array(
					'type'           => 'flex',
					'justifyContent' => 'left',
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
		array(
			'className' => 'vvm-villa-pricing-contact',
			'style'     => array(
				'spacing' => array(
					'margin' => array(
						'top'    => 'var:preset|spacing|xl',
						'bottom' => 'var:preset|spacing|xl',
					),
				),
			),
			'layout'    => array( 'type' => 'default' ),
		)
	);

	$map_markup = '';

	if ( '' !== $map_address ) {
		$map_markup = gutenberg_lab_blocks_villa_importer_gmap_block(
			$map_address,
			$overview['villa_name']
		);
	}

	$location_details =
		gutenberg_lab_blocks_villa_importer_paragraph(
			'Location',
			array( 'className' => 'vvm-villa-amenities__eyebrow' )
		) .
		gutenberg_lab_blocks_villa_importer_paragraph(
			$overview['display_address'],
			array( 'className' => 'vvm-villa-amenities__item' )
		) .
		gutenberg_lab_blocks_villa_importer_paragraph(
			$extras['location_description'] ?? '',
			array( 'className' => 'vvm-villa-amenities__copy' )
		);

	if ( '' !== $map_link ) {
		$location_details .= gutenberg_lab_blocks_villa_importer_buttons(
			array(
				array(
					'label' => 'Open in Google Maps',
					'url'   => $map_link,
					'class' => 'is-style-vvm-link-primary',
				),
			)
		);
	}

	return $pricing_contact . "\n\n" .
		gutenberg_lab_blocks_villa_importer_group(
			gutenberg_lab_blocks_villa_importer_group(
				gutenberg_lab_blocks_villa_importer_group(
					$map_markup . $location_details,
					array(
						'className' => 'vvm-villa-contact__media-slot',
						'layout'    => array( 'type' => 'constrained' ),
					)
				),
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
		array(
			'align' => 'wide',
			'style' => array(
				'spacing' => array(
					'margin' => array(
						'top'    => 'var:preset|spacing|section-md',
						'bottom' => 'var:preset|spacing|md',
					),
				),
			),
		)
	) .
		gutenberg_lab_blocks_villa_importer_self_closing_block(
			'gutenberg-lab-blocks/card-grid',
			array(
				'contentSource'     => 'villas',
				'selectedVillaIds' => array_values( array_map( 'absint', $related_ids ) ),
				'villaPresentation' => 'collection',
				'columns'           => '3',
				'align'             => 'wide',
				'style'             => array(
					'spacing' => array(
						'blockGap' => 'var:preset|spacing|lg',
						'padding'  => array(
							'top'    => '0',
							'bottom' => 'var:preset|spacing|section-md',
						),
					),
				),
			)
		);
}

/**
 * Parses generated or cloned markup into meaningful blocks.
 *
 * Empty freeform whitespace blocks are normal when WordPress serializes nested
 * content; filtering them keeps section replacement logic predictable.
 *
 * @param string $markup Serialized block markup.
 * @return array<int, array<string, mixed>>
 */
function gutenberg_lab_blocks_villa_importer_parse_import_blocks( $markup ) {
	return array_values(
		array_filter(
			parse_blocks( (string) $markup ),
			static function ( $block ) {
				return null !== ( $block['blockName'] ?? null ) ||
					'' !== trim( (string) ( $block['innerHTML'] ?? '' ) );
			}
		)
	);
}

/**
 * Returns the first meaningful block from serialized markup.
 *
 * @param string $markup Serialized block markup.
 * @return array<string, mixed>|null
 */
function gutenberg_lab_blocks_villa_importer_first_import_block( $markup ) {
	$blocks = gutenberg_lab_blocks_villa_importer_parse_import_blocks( $markup );

	return $blocks[0] ?? null;
}

/**
 * Checks a parsed Gutenberg block for an editor class.
 *
 * @param array<string, mixed> $block Parsed block.
 * @param string               $class_name Class name to find.
 * @return bool
 */
function gutenberg_lab_blocks_villa_importer_block_has_class( $block, $class_name ) {
	$classes = (string) ( $block['attrs']['className'] ?? '' );

	return in_array( $class_name, preg_split( '/\s+/', trim( $classes ) ), true );
}

/**
 * Rebuilds a core wrapper block while preserving its source attributes.
 *
 * @param string               $block_name Core block name without the core/ prefix.
 * @param array<string, mixed> $attributes Source block attributes.
 * @param string               $base_class Saved HTML base class.
 * @param string               $inner_markup Serialized child markup.
 * @return array<string, mixed>|null
 */
function gutenberg_lab_blocks_villa_importer_core_wrapper_block( $block_name, $attributes, $base_class, $inner_markup ) {
	$classes = gutenberg_lab_blocks_villa_importer_block_classes( $base_class, $attributes );
	$style   = gutenberg_lab_blocks_villa_importer_style_attr( $attributes );

	return gutenberg_lab_blocks_villa_importer_first_import_block(
		gutenberg_lab_blocks_villa_importer_block(
			$block_name,
			$attributes,
			sprintf(
				'<div class="%1$s"%2$s>%3$s</div>',
				esc_attr( $classes ),
				$style,
				$inner_markup
			)
		)
	);
}

/**
 * Applies workbook hero copy to a cloned gallery hero while preserving media.
 *
 * @param array<string, mixed> $source_block Parsed source hero block.
 * @param array<string, mixed> $data Normalized workbook data.
 * @return array<string, mixed>|null
 */
function gutenberg_lab_blocks_villa_importer_source_hero_block( $source_block, $data ) {
	$inner_blocks     = array();
	$content_replaced = false;

	foreach ( $source_block['innerBlocks'] ?? array() as $inner_block ) {
		if ( 'gutenberg-lab-blocks/villa-gallery-hero-content' === ( $inner_block['blockName'] ?? '' ) ) {
			$attributes       = $inner_block['attrs'] ?? array( 'lock' => array( 'move' => true, 'remove' => true ) );
			$inner_blocks[]   = gutenberg_lab_blocks_villa_importer_first_import_block(
				gutenberg_lab_blocks_villa_importer_block(
					'gutenberg-lab-blocks/villa-gallery-hero-content',
					$attributes,
					gutenberg_lab_blocks_villa_importer_build_hero_content( $data )
				)
			);
			$content_replaced = true;
			continue;
		}

		$inner_blocks[] = $inner_block;
	}

	if ( ! $content_replaced ) {
		$inner_blocks[] = gutenberg_lab_blocks_villa_importer_first_import_block(
			gutenberg_lab_blocks_villa_importer_block(
				'gutenberg-lab-blocks/villa-gallery-hero-content',
				array( 'lock' => array( 'move' => true, 'remove' => true ) ),
				gutenberg_lab_blocks_villa_importer_build_hero_content( $data )
			)
		);
	}

	return gutenberg_lab_blocks_villa_importer_first_import_block(
		gutenberg_lab_blocks_villa_importer_block(
			'gutenberg-lab-blocks/villa-gallery-hero',
			$source_block['attrs'] ?? array(),
			serialize_blocks( array_filter( $inner_blocks ) )
		)
	);
}

/**
 * Collects image attributes from the first bedroom gallery carousel in a source block.
 *
 * @param array<string, mixed> $block Parsed source block.
 * @return array<int, array<string, mixed>>
 */
function gutenberg_lab_blocks_villa_importer_collect_carousel_media( $block ) {
	if ( 'gutenberg-lab-blocks/villa-gallery-carousel' === ( $block['blockName'] ?? '' ) ) {
		$media = array();

		foreach ( $block['innerBlocks'] ?? array() as $slide ) {
			if ( 'gutenberg-lab-blocks/villa-gallery-carousel-slide' !== ( $slide['blockName'] ?? '' ) ) {
				continue;
			}

			$attributes = $slide['attrs'] ?? array();
			$media[]    = array_filter(
				array(
					'imageId'  => $attributes['imageId'] ?? null,
					'imageUrl' => $attributes['imageUrl'] ?? null,
					'imageAlt' => $attributes['imageAlt'] ?? null,
				),
				static function ( $value ) {
					return null !== $value && '' !== $value;
				}
			);
		}

		return array_values( array_filter( $media ) );
	}

	foreach ( $block['innerBlocks'] ?? array() as $inner_block ) {
		$media = gutenberg_lab_blocks_villa_importer_collect_carousel_media( $inner_block );

		if ( ! empty( $media ) ) {
			return $media;
		}
	}

	return array();
}

/**
 * Applies placeholder carousel media to generated bedroom slides by position.
 *
 * @param array<string, mixed> $block Parsed generated block.
 * @param array<int, array<string, mixed>> $media Source image attributes.
 * @param bool                 $applied Whether media has already been applied.
 * @return array<string, mixed>
 */
function gutenberg_lab_blocks_villa_importer_apply_carousel_media( $block, $media, &$applied = false ) {
	if ( $applied ) {
		return $block;
	}

	if ( 'gutenberg-lab-blocks/villa-gallery-carousel' === ( $block['blockName'] ?? '' ) ) {
		foreach ( $block['innerBlocks'] ?? array() as $index => $slide ) {
			if ( empty( $media[ $index ] ) || 'gutenberg-lab-blocks/villa-gallery-carousel-slide' !== ( $slide['blockName'] ?? '' ) ) {
				continue;
			}

			$block['innerBlocks'][ $index ]['attrs'] = array_merge(
				$slide['attrs'] ?? array(),
				$media[ $index ]
			);
		}

		$applied = true;
		return $block;
	}

	foreach ( $block['innerBlocks'] ?? array() as $index => $inner_block ) {
		$block['innerBlocks'][ $index ] = gutenberg_lab_blocks_villa_importer_apply_carousel_media(
			$inner_block,
			$media,
			$applied
		);
	}

	return $block;
}

/**
 * Builds Stack Tabs and borrows source bedroom carousel images as placeholders.
 *
 * @param array<string, mixed> $data Normalized workbook data.
 * @param array<string, mixed> $source_block Parsed source Stack Tabs block.
 * @param array<int, string>   $warnings Import warnings.
 * @return array<string, mixed>|null
 */
function gutenberg_lab_blocks_villa_importer_source_tabs_block( $data, $source_block, &$warnings ) {
	$tabs  = gutenberg_lab_blocks_villa_importer_first_import_block(
		gutenberg_lab_blocks_villa_importer_build_tabs( $data )
	);
	$media = gutenberg_lab_blocks_villa_importer_collect_carousel_media( $source_block );

	if ( ! $tabs ) {
		return null;
	}

	if ( ! empty( $media ) ) {
		$applied = false;
		$tabs    = gutenberg_lab_blocks_villa_importer_apply_carousel_media( $tabs, $media, $applied );

		if ( $applied ) {
			$warnings[] = 'Bedroom gallery images were copied from the source villa as placeholders; replace them with villa-specific media before publishing.';
		}
	}

	return $tabs;
}

/**
 * Replaces the text column in a cloned perspective section and preserves images.
 *
 * @param array<string, mixed> $source_block Parsed source perspective block.
 * @param array<string, mixed> $data Normalized workbook data.
 * @return array<string, mixed>|null
 */
function gutenberg_lab_blocks_villa_importer_source_perspective_block( $source_block, $data ) {
	if ( 'core/columns' !== ( $source_block['blockName'] ?? '' ) || empty( $source_block['innerBlocks'] ) ) {
		return gutenberg_lab_blocks_villa_importer_first_import_block(
			gutenberg_lab_blocks_villa_importer_build_perspective( $data )
		);
	}

	$columns    = $source_block['innerBlocks'];
	$first_col  = $columns[0];
	$attributes = $source_block['attrs'] ?? array();
	$class_name = trim(
		gutenberg_lab_blocks_villa_importer_classes(
			( $attributes['className'] ?? '' ) . ' vvm-villa-perspective'
		)
	);

	if ( '' !== $class_name ) {
		$attributes['className'] = $class_name;
	}

	$replacement = gutenberg_lab_blocks_villa_importer_core_wrapper_block(
		'column',
		$first_col['attrs'] ?? array(),
		'wp-block-column',
		gutenberg_lab_blocks_villa_importer_build_perspective_text( $data )
	);

	if ( $replacement ) {
		$columns[0] = $replacement;
	}

	return gutenberg_lab_blocks_villa_importer_core_wrapper_block(
		'columns',
		$attributes,
		'wp-block-columns',
		serialize_blocks( $columns )
	);
}

/**
 * Builds content by cloning a complete source villa and replacing known sections.
 *
 * This is intentionally conservative: sections absent from the source scaffold
 * are not invented. The dry-run warning list tells the human/AI reviewer what
 * still needs judgment.
 *
 * @param array<string, mixed> $data Normalized workbook data.
 * @param array<int, int>      $related_ids Related published Villa IDs.
 * @param WP_Post             $source_post Source villa post.
 * @param array<int, string>   $warnings Import warnings.
 * @return string|WP_Error
 */
function gutenberg_lab_blocks_villa_importer_build_content_from_source( $data, $related_ids, $source_post, &$warnings ) {
	$contact_form = gutenberg_lab_blocks_villa_importer_contact_form_attributes();
	$extras       = gutenberg_lab_blocks_villa_importer_default_extras( $data['extras'] ?? array() );

	if ( is_wp_error( $contact_form ) ) {
		return $contact_form;
	}

	$source_blocks = gutenberg_lab_blocks_villa_importer_parse_import_blocks( $source_post->post_content );

	if ( empty( $source_blocks ) ) {
		return new WP_Error(
			'empty_source_villa_content',
			__( 'The selected source villa does not contain usable Gutenberg blocks.', 'gutenberg-lab-blocks' )
		);
	}

	$pricing_location_blocks = gutenberg_lab_blocks_villa_importer_parse_import_blocks(
		gutenberg_lab_blocks_villa_importer_build_pricing_contact_location( $data, $contact_form )
	);
	$related_blocks          = gutenberg_lab_blocks_villa_importer_parse_import_blocks(
		gutenberg_lab_blocks_villa_importer_build_related_villas( $related_ids, $extras['related_heading'] )
	);
	$pricing_contact_block   = null;
	$location_block          = null;
	$related_heading_block   = null;
	$related_grid_block      = null;

	foreach ( $pricing_location_blocks as $block ) {
		if ( gutenberg_lab_blocks_villa_importer_block_has_class( $block, 'vvm-villa-pricing-contact' ) ) {
			$pricing_contact_block = $block;
		}

		if ( gutenberg_lab_blocks_villa_importer_block_has_class( $block, 'vvm-villa-contact' ) ) {
			$location_block = $block;
		}
	}

	foreach ( $related_blocks as $block ) {
		if ( 'core/heading' === ( $block['blockName'] ?? '' ) ) {
			$related_heading_block = $block;
		}

		if ( 'gutenberg-lab-blocks/card-grid' === ( $block['blockName'] ?? '' ) ) {
			$related_grid_block = $block;
		}
	}

	$built_blocks = array();
	$replaced     = array(
		'hero'        => false,
		'specs'       => false,
		'story'       => false,
		'perspective' => false,
		'tabs'        => false,
		'calendar'    => false,
		'pricing'     => false,
		'location'    => false,
		'related'     => false,
	);
	$seen_specs       = false;
	$seen_tabs        = false;
	$story_done       = false;
	$perspective_done = false;

	for ( $index = 0; $index < count( $source_blocks ); $index++ ) {
		$block      = $source_blocks[ $index ];
		$block_name = $block['blockName'] ?? '';
		$next_block = $source_blocks[ $index + 1 ] ?? null;

		if (
			'core/paragraph' === $block_name &&
			empty( $block['innerBlocks'] ) &&
			'' === trim( wp_strip_all_tags( (string) ( $block['innerHTML'] ?? '' ) ) )
		) {
			continue;
		}

		if ( 'gutenberg-lab-blocks/villa-gallery-hero' === $block_name ) {
			$replacement = gutenberg_lab_blocks_villa_importer_source_hero_block( $block, $data );

			if ( $replacement ) {
				$built_blocks[]    = $replacement;
				$replaced['hero'] = true;
				continue;
			}
		}

		if ( 'gutenberg-lab-blocks/villa-specs' === $block_name ) {
			$replacement = gutenberg_lab_blocks_villa_importer_first_import_block(
				gutenberg_lab_blocks_villa_importer_build_villa_specs( $data, $block )
			);

			if ( $replacement ) {
				$built_blocks[]     = $replacement;
				$seen_specs         = true;
				$replaced['specs'] = true;
				continue;
			}
		}

		if ( $seen_specs && ! $seen_tabs && ! $story_done && 'core/columns' === $block_name ) {
			$replacement = gutenberg_lab_blocks_villa_importer_first_import_block(
				gutenberg_lab_blocks_villa_importer_build_story_columns( $data )
			);

			if ( $replacement ) {
				$built_blocks[]     = $replacement;
				$story_done         = true;
				$replaced['story'] = true;
				continue;
			}
		}

		if ( $story_done && ! $seen_tabs && ! $perspective_done && 'core/columns' === $block_name ) {
			$replacement = gutenberg_lab_blocks_villa_importer_source_perspective_block( $block, $data );

			if ( $replacement ) {
				$built_blocks[]           = $replacement;
				$perspective_done         = true;
				$replaced['perspective'] = true;
				continue;
			}
		}

		if ( ! $perspective_done && gutenberg_lab_blocks_villa_importer_block_has_class( $block, 'vvm-villa-perspective' ) ) {
			$replacement = gutenberg_lab_blocks_villa_importer_source_perspective_block( $block, $data );

			if ( $replacement ) {
				$built_blocks[]           = $replacement;
				$perspective_done         = true;
				$replaced['perspective'] = true;
				continue;
			}
		}

		if ( 'gutenberg-lab-blocks/stack-tabs' === $block_name ) {
			$replacement = gutenberg_lab_blocks_villa_importer_source_tabs_block( $data, $block, $warnings );

			if ( $replacement ) {
				$built_blocks[]    = $replacement;
				$seen_tabs         = true;
				$replaced['tabs'] = true;
				continue;
			}
		}

		if ( 'gutenberg-lab-blocks/villa-availability-calendar' === $block_name ) {
			$replacement = gutenberg_lab_blocks_villa_importer_first_import_block(
				gutenberg_lab_blocks_villa_importer_self_closing_block(
					'gutenberg-lab-blocks/villa-availability-calendar',
					array( 'allowUnavailableEndpoints' => true )
				)
			);

			if ( $replacement ) {
				$built_blocks[]        = $replacement;
				$replaced['calendar'] = true;
				continue;
			}
		}

		if ( gutenberg_lab_blocks_villa_importer_block_has_class( $block, 'vvm-villa-pricing-contact' ) ) {
			if ( $pricing_contact_block ) {
				$built_blocks[]      = $pricing_contact_block;
				$replaced['pricing'] = true;
				continue;
			}
		}

		if ( gutenberg_lab_blocks_villa_importer_block_has_class( $block, 'vvm-villa-contact' ) ) {
			if ( $location_block ) {
				$built_blocks[]       = $location_block;
				$replaced['location'] = true;
				continue;
			}
		}

		if (
			'core/heading' === $block_name &&
			$next_block &&
			'gutenberg-lab-blocks/card-grid' === ( $next_block['blockName'] ?? '' )
		) {
			if ( $related_heading_block ) {
				$built_blocks[]       = $related_heading_block;
				$replaced['related'] = true;
			}

			continue;
		}

		if ( 'gutenberg-lab-blocks/card-grid' === $block_name ) {
			if ( $related_grid_block ) {
				$built_blocks[]       = $related_grid_block;
				$replaced['related'] = true;
			}

			continue;
		}

		$built_blocks[] = $block;
	}

	foreach ( $replaced as $section => $was_replaced ) {
		if ( ! $was_replaced && ! in_array( $section, array( 'related' ), true ) ) {
			$warnings[] = sprintf(
				'Source villa scaffold did not contain a recognizable %s section, so that section was not inserted automatically.',
				$section
			);
		}
	}

	$warnings[] = sprintf(
		'Clone mode used "%s" as the layout scaffold. Review preserved media and any unusual source-only sections before publishing.',
		get_the_title( $source_post )
	);

	return serialize_blocks( $built_blocks );
}

/**
 * Builds the complete post_content payload.
 *
 * @param array<string, mixed> $data Normalized workbook data.
 * @param array<int, int>      $related_ids Related published Villa IDs.
 * @param WP_Post|null         $source_post Optional source villa scaffold.
 * @param array<int, string>   $warnings Additional import warnings.
 * @return string|WP_Error
 */
function gutenberg_lab_blocks_villa_importer_build_content( $data, $related_ids, $source_post = null, &$warnings = array() ) {
	if ( $source_post instanceof WP_Post ) {
		return gutenberg_lab_blocks_villa_importer_build_content_from_source(
			$data,
			$related_ids,
			$source_post,
			$warnings
		);
	}

	$contact_form = gutenberg_lab_blocks_villa_importer_contact_form_attributes();
	$extras       = gutenberg_lab_blocks_villa_importer_default_extras( $data['extras'] ?? array() );

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
			$extras['related_heading']
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
function gutenberg_lab_blocks_villa_importer_location_key( $location_name ) {
	$location_name = strtolower( gutenberg_lab_blocks_villa_importer_text( $location_name ) );
	$location_name = preg_replace( '/\bsaint\b/', 'st', $location_name );
	$location_name = preg_replace( '/\bst\.?\b/', 'st', $location_name );
	$location_name = preg_replace( '/[^a-z0-9]+/', ' ', $location_name );

	return trim( preg_replace( '/\s+/', ' ', $location_name ) );
}

/**
 * Finds an existing location term without silently creating typo terms.
 *
 * @param string $location_name Client location name.
 * @return WP_Term|WP_Error
 */
function gutenberg_lab_blocks_villa_importer_resolve_location( $location_name ) {
	$location_name = gutenberg_lab_blocks_villa_importer_text( $location_name );
	$location_key  = gutenberg_lab_blocks_villa_importer_location_key( $location_name );
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
			0 === strcasecmp( $term->slug, sanitize_title( $location_name ) ) ||
			gutenberg_lab_blocks_villa_importer_location_key( $term->name ) === $location_key ||
			gutenberg_lab_blocks_villa_importer_location_key( $term->slug ) === $location_key
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
 * Finds a complete villa to use as the clone-mode layout scaffold.
 *
 * @param string $source Source villa ID, slug, or exact title.
 * @return WP_Post|WP_Error
 */
function gutenberg_lab_blocks_villa_importer_resolve_source_villa( $source ) {
	$source = gutenberg_lab_blocks_villa_importer_text( $source );

	if ( '' === $source ) {
		return new WP_Error(
			'missing_source_villa',
			__( 'Choose a source villa with --source=<villa-id-or-slug>.', 'gutenberg-lab-blocks' )
		);
	}

	if ( ctype_digit( $source ) ) {
		$post = get_post( absint( $source ) );
	} else {
		$post = get_page_by_path( sanitize_title( $source ), OBJECT, 'villa' );

		if ( ! $post ) {
			$matches = get_posts(
				array(
					'post_type'      => 'villa',
					'post_status'    => 'any',
					'title'          => $source,
					'posts_per_page' => 1,
				)
			);
			$post = $matches[0] ?? null;
		}
	}

	if ( ! $post || 'villa' !== $post->post_type ) {
		return new WP_Error(
			'unknown_source_villa',
			sprintf(
				/* translators: %s: Source villa ID, slug, or title. */
				__( 'The source villa "%s" was not found.', 'gutenberg-lab-blocks' ),
				$source
			)
		);
	}

	if ( in_array( $post->post_status, array( 'trash', 'auto-draft' ), true ) ) {
		return new WP_Error(
			'invalid_source_villa_status',
			sprintf(
				/* translators: 1: Villa title. 2: Post status. */
				__( 'The source villa "%1$s" cannot be used because its status is "%2$s".', 'gutenberg-lab-blocks' ),
				get_the_title( $post ),
				$post->post_status
			)
		);
	}

	if ( '' === trim( (string) $post->post_content ) ) {
		return new WP_Error(
			'empty_source_villa',
			sprintf(
				/* translators: %s: Villa title. */
				__( 'The source villa "%s" has no saved block content.', 'gutenberg-lab-blocks' ),
				get_the_title( $post )
			)
		);
	}

	return $post;
}

/**
 * Extracts coordinates from common full Google Maps URL shapes.
 *
 * Short share URLs are deliberately not fetched during import.
 *
 * @param string $coordinate_source Google Maps URL or raw "latitude, longitude" text.
 * @return array{latitude:string,longitude:string}|array{}
 */
function gutenberg_lab_blocks_villa_importer_extract_coordinates( $coordinate_source ) {
	$coordinate_source = html_entity_decode( (string) $coordinate_source, ENT_QUOTES, 'UTF-8' );
	$patterns          = array(
		'/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/',
		'/[?&](?:query|q)=(-?\d+(?:\.\d+)?)(?:%2C|,)(-?\d+(?:\.\d+)?)/i',
		'/(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)/',
	);

	foreach ( $patterns as $pattern ) {
		if ( preg_match( $pattern, $coordinate_source, $matches ) ) {
			$latitude  = gutenberg_lab_blocks_sanitize_villa_schema_coordinate( $matches[1] );
			$longitude = gutenberg_lab_blocks_sanitize_villa_schema_coordinate( $matches[2] );

			if ( '' !== $latitude && '' !== $longitude ) {
				return array(
					'latitude'  => $latitude,
					'longitude' => $longitude,
				);
			}
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
 * Returns the stable fingerprint for importer-generated content.
 *
 * @param string $content Serialized post content.
 * @return string
 */
function gutenberg_lab_blocks_villa_importer_content_hash( $content ) {
	return hash( 'sha256', (string) $content );
}

/**
 * Checks whether the current draft content still matches the last import.
 *
 * Clone mode intentionally copies source media as placeholders. Those media
 * should not block immediate spreadsheet updates, but manual WordPress edits
 * after import should.
 *
 * @param int $post_id Villa post ID.
 * @return bool
 */
function gutenberg_lab_blocks_villa_importer_content_matches_last_import( $post_id ) {
	$post = get_post( $post_id );

	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	$stored_hash = (string) get_post_meta( $post_id, '_gutenberg_lab_villa_import_content_hash', true );

	return '' !== $stored_hash &&
		hash_equals( $stored_hash, gutenberg_lab_blocks_villa_importer_content_hash( $post->post_content ) );
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
 * Builds the pricing-row key map used by the frontend selector.
 *
 * @param array<int, array<string, mixed>> $rates Normalized workbook rates.
 * @return array<int, string>
 */
function gutenberg_lab_blocks_villa_importer_pricing_row_keys( $rates ) {
	$keys = array();

	foreach ( $rates as $rate ) {
		$rate_label = gutenberg_lab_blocks_villa_importer_text(
			$rate['rate_label'] ?? ( $rate['season'] ?? '' )
		);

		$keys[] = gutenberg_lab_blocks_get_villa_bedroom_pricing_key_from_label(
			$rate_label
		);
	}

	return array_filter( $keys ) ? $keys : array();
}

/**
 * Builds post meta from normalized workbook content.
 *
 * @param array<string, mixed> $data Normalized workbook data.
 * @return array<string, string>
 */
function gutenberg_lab_blocks_villa_importer_meta( $data ) {
	$overview       = $data['overview'];
	$schema_address = gutenberg_lab_blocks_villa_importer_text(
		$overview['map_address'] ?? ( $overview['display_address'] ?? '' )
	);
	$coordinates    = gutenberg_lab_blocks_villa_importer_extract_coordinates(
		trim( ( $overview['coordinates'] ?? '' ) . ' ' . ( $overview['google_maps_link'] ?? '' ) )
	);
	$facts = sprintf(
		'%1$s Bedrooms - %2$s Bathrooms - Sleeps %3$s',
		$overview['bedrooms'],
		$overview['bathrooms'],
		$overview['sleeps']
	);
	$price = 'From ' . gutenberg_lab_blocks_villa_importer_format_usd( $overview['starting_rate_usd'] ) . '/night';
	$card_location = $overview['card_short_description'] ?? sprintf(
		'%s, %s',
		$overview['property_area'],
		$overview['parish']
	);
	$bedroom_selector_choices = gutenberg_lab_blocks_sanitize_villa_bedroom_choice_rows(
		$overview['bedroom_selector_choices'] ?? array()
	);
	$pricing_row_keys = gutenberg_lab_blocks_villa_importer_pricing_row_keys(
		$data['rates'] ?? array()
	);

	$meta = array(
		'villa_card_eyebrow'           => $card_location,
		'villa_card_descriptor'        => gutenberg_lab_blocks_villa_importer_excerpt( $data ),
		'villa_card_facts'             => $facts,
		'villa_card_price'             => $price,
		'villa_card_cta_label'         => 'Explore villa',
		'villa_schema_latitude'        => $coordinates['latitude'] ?? '',
		'villa_schema_longitude'       => $coordinates['longitude'] ?? '',
		'villa_schema_street_address'  => $schema_address,
		'villa_schema_postal_code'     => $overview['postal_code'] ?? '',
		'villa_bedroom_selector_enabled' => gutenberg_lab_blocks_villa_importer_boolean(
			$overview['bedroom_selector_enabled'] ?? false,
			false
		) ? '1' : '0',
		'villa_bedroom_selector_choices' => $bedroom_selector_choices,
		'villa_pricing_row_bedroom_keys' => $pricing_row_keys,
		'_gutenberg_lab_villa_import_managed' => '1',
		'_gutenberg_lab_villa_import_schema_version' => $data['schema_version'],
		'_gutenberg_lab_villa_import_source_file' => $data['source_file'] ?? '',
		'_gutenberg_lab_villa_imported_at' => gmdate( 'c' ),
	);

	return array_filter(
		$meta,
		static fn( $value ) => is_array( $value ) || '' !== (string) $value
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
	 * [--source=<id|slug|title>]
	 * : Clone a complete source villa scaffold, then replace recognized content sections.
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
		$source_ref = (string) ( $assoc_args['source'] ?? '' );
		$source_post = null;
		$overview  = $data['overview'];
		$title     = gutenberg_lab_blocks_villa_importer_text( $overview['villa_name'] );
		$slug      = sanitize_title( $title );
		$location  = gutenberg_lab_blocks_villa_importer_resolve_location( $overview['parish'] );

		if ( is_wp_error( $location ) ) {
			WP_CLI::error( $location->get_error_message() );
		}

		if ( '' !== $source_ref ) {
			$source_post = gutenberg_lab_blocks_villa_importer_resolve_source_villa( $source_ref );

			if ( is_wp_error( $source_post ) ) {
				WP_CLI::error( $source_post->get_error_message() );
			}

			if ( $update_id && (int) $source_post->ID === $update_id ) {
				WP_CLI::error( 'The source villa cannot be the same post as the draft being updated.' );
			}
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

			if (
				gutenberg_lab_blocks_villa_importer_has_media( $update_id ) &&
				! gutenberg_lab_blocks_villa_importer_content_matches_last_import( $update_id )
			) {
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
		$warnings = array_merge(
			(array) ( $data['warnings'] ?? array() ),
			$related['warnings']
		);
		$content_warnings = array();
		$content = gutenberg_lab_blocks_villa_importer_build_content(
			$data,
			$related['ids'],
			$source_post,
			$content_warnings
		);

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

		$warnings = array_merge( $warnings, $content_warnings );

		if ( empty( gutenberg_lab_blocks_villa_importer_extract_coordinates( trim( ( $overview['coordinates'] ?? '' ) . ' ' . ( $overview['google_maps_link'] ?? '' ) ) ) ) ) {
			$warnings[] = 'Google Maps coordinates were not detected; add exact schema coordinates during review.';
		}

		$warnings[] = $source_post instanceof WP_Post
			? 'Featured image and source placeholder gallery media still need to be reviewed or replaced.'
			: 'Featured image and gallery media still need to be assigned.';
		$warnings[] = ! empty( $overview['ical_link'] )
			? 'iCal link supplied; importer will store it as an availability feed. Sync and review the calendar after import.'
			: 'Availability calendar feeds still need to be configured.';
		if ( $source_post instanceof WP_Post ) {
			$warnings[] = 'After villa-specific media work starts, continue in WordPress instead of re-running the importer over the same draft.';
		}

		WP_CLI::line( sprintf( 'Villa: %s', $title ) );
		WP_CLI::line( sprintf( 'Slug: %s', $slug ) );
		WP_CLI::line( sprintf( 'Location: %s', $location->name ) );
		WP_CLI::line(
			$source_post instanceof WP_Post
				? sprintf( 'Source scaffold: %s (#%d)', get_the_title( $source_post ), $source_post->ID )
				: 'Source scaffold: none; generated neutral villa layout'
		);
		WP_CLI::line( sprintf( 'Bedrooms: %d', count( $data['bedrooms'] ) ) );
		WP_CLI::line( sprintf( 'Rates: %d', count( $data['rates'] ) ) );
		WP_CLI::line( sprintf( 'Top-level blocks: %d', count( $parsed_blocks ) ) );
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
			'post_excerpt' => wp_slash( gutenberg_lab_blocks_villa_importer_excerpt( $data ) ),
			'post_content' => wp_slash( $content ),
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

		update_post_meta(
			$post_id,
			'_gutenberg_lab_villa_import_content_hash',
			gutenberg_lab_blocks_villa_importer_content_hash( $content )
		);

		if ( $source_post instanceof WP_Post ) {
			update_post_meta( $post_id, '_gutenberg_lab_villa_import_source_villa', (string) $source_post->ID );
		} else {
			delete_post_meta( $post_id, '_gutenberg_lab_villa_import_source_villa' );
		}

		if (
			! empty( $overview['ical_link'] ) &&
			function_exists( 'gutenberg_lab_blocks_update_villa_availability_meta' )
		) {
			gutenberg_lab_blocks_update_villa_availability_meta(
				$post_id,
				array(
					array(
						'label' => $title,
						'url'   => $overview['ical_link'],
					),
				),
				array(),
				0
			);
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
