<?php
/**
 * Server rendering for the manual Card Grid block.
 *
 * The block stores each card as a nested child block so editors keep a native
 * Gutenberg experience, while PHP owns the shared wrapper classes that the
 * future post-driven grid can reuse.
 */

if ( ! function_exists( 'gutenberg_lab_card_grid_normalize_spacing_preset_slug' ) ) {
	/**
	 * Convert Gutenberg spacing slugs like `2xl` into the CSS preset slug `2-xl`.
	 */
	function gutenberg_lab_card_grid_normalize_spacing_preset_slug( $spacing_slug ) {
		$spacing_slug = sanitize_title( $spacing_slug );

		return preg_replace( '/^([0-9]+)([a-z])/', '$1-$2', $spacing_slug );
	}
}

if ( ! function_exists( 'gutenberg_lab_card_grid_resolve_block_gap_value' ) ) {
	/**
	 * Resolve Gutenberg preset tokens into the CSS value used by the grid wrapper.
	 */
	function gutenberg_lab_card_grid_resolve_block_gap_value( $block_gap ) {
		if ( ! is_string( $block_gap ) || '' === $block_gap ) {
			return '';
		}

		if ( str_starts_with( $block_gap, 'var:preset|spacing|' ) ) {
			$spacing_slug = substr( $block_gap, strlen( 'var:preset|spacing|' ) );
			$spacing_slug = gutenberg_lab_card_grid_normalize_spacing_preset_slug( $spacing_slug );

			if ( '' !== $spacing_slug ) {
				return sprintf( 'var(--wp--preset--spacing--%s)', $spacing_slug );
			}
		}

		return $block_gap;
	}
}

$content_source     = 'villas' === ( $attributes['contentSource'] ?? 'manual' ) ? 'villas' : 'manual';
$villa_count        = max( 1, (int) ( $attributes['villaCount'] ?? 3 ) );
$villa_presentation = $attributes['villaPresentation'] ?? 'cinematic';
$exclude_current    = ! empty( $attributes['excludeCurrent'] );
$columns            = $attributes['columns'] ?? '2';
$is_villa_cinematic = 'villas' === $content_source && 'cinematic' === $villa_presentation;
$enable_carousel = ! empty( $attributes['enableCarousel'] );
$media_ratio = $attributes['mediaRatio'] ?? 'landscape';
$block_gap   = $attributes['style']['spacing']['blockGap'] ?? '';

$allowed_columns = array( '2', '3' );
$allowed_villa_presentations = array(
	'cinematic',
	'standard',
	'collection',
);
$allowed_ratios  = array(
	'landscape',
	'widescreen',
	'square',
	'portrait',
	'portrait-tall',
);

if ( ! in_array( $columns, $allowed_columns, true ) ) {
	$columns = '2';
}

if ( ! in_array( $villa_presentation, $allowed_villa_presentations, true ) ) {
	$villa_presentation = 'cinematic';
}

if ( ! in_array( $media_ratio, $allowed_ratios, true ) ) {
	$media_ratio = 'landscape';
}

$styles = array();

if ( is_string( $block_gap ) && '' !== $block_gap ) {
	$block_gap = gutenberg_lab_card_grid_resolve_block_gap_value( $block_gap );
	// Keep a trailing semicolon so WordPress can safely append native support
	// styles like padding/margin without producing an invalid style attribute.
	$styles[] = '--wp--style--block-gap:' . esc_attr( $block_gap ) . ';';
}

$card_markup = '';
$card_count  = 0;

if ( 'villas' === $content_source ) {
	$query_args = array(
		'post_type'           => 'villa',
		'post_status'         => 'publish',
		'posts_per_page'      => $villa_count,
		'ignore_sticky_posts' => true,
		'orderby'             => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
	);

	if ( $exclude_current && is_singular( 'villa' ) ) {
		$query_args['post__not_in'] = array( get_queried_object_id() );
	}

	if (
		$exclude_current &&
		empty( $query_args['post__not_in'] ) &&
		isset( $block->context['postId'], $block->context['postType'] ) &&
		'villa' === $block->context['postType']
	) {
		$query_args['post__not_in'] = array( (int) $block->context['postId'] );
	}

	$villas_query = new WP_Query(
		$query_args
	);

	if ( $villas_query->have_posts() ) {
		while ( $villas_query->have_posts() ) {
			$villas_query->the_post();
			$card_markup .= gutenberg_lab_blocks_render_villa_card(
				get_the_ID(),
				array(
					'cta_label_override' => $is_villa_cinematic
						? __( 'Enquire', 'gutenberg-lab-blocks' )
						: '',
					'presentation'       => $villa_presentation,
				)
			);
		}

		$card_count = (int) $villas_query->post_count;
	}

	wp_reset_postdata();

	if ( 0 === $card_count ) {
		$is_editor_preview =
			is_admin() ||
			( function_exists( 'wp_is_serving_rest_request' ) && wp_is_serving_rest_request() ) ||
			( defined( 'REST_REQUEST' ) && REST_REQUEST );

		if ( ! $is_editor_preview ) {
			return;
		}

		$card_markup = sprintf(
			'<p class="vvm-card-grid__empty-state">%s</p>',
			esc_html__( 'Add published villa posts to populate this card grid.', 'gutenberg-lab-blocks' )
		);
	}
} else {
	$card_markup = $content;
	$card_count  = preg_match_all(
		'/wp-block-gutenberg-lab-blocks-card-grid-card\b/',
		$content,
		$matches
	);
	$card_count  = false === $card_count ? 0 : $card_count;
}

$columns_int   = (int) $columns;
$use_carousel = ! $is_villa_cinematic && $enable_carousel && $columns_int > 0 && $card_count > $columns_int;

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode(
			' ',
				array(
					'vvm-card-grid',
					$is_villa_cinematic ? 'alignfull' : '',
					$enable_carousel ? 'vvm-card-grid--carousel-enabled' : '',
					$use_carousel ? 'vvm-card-grid--display-carousel' : 'vvm-card-grid--display-grid',
					'vvm-card-grid--source-' . sanitize_html_class( $content_source ),
				'villas' === $content_source ? 'vvm-card-grid--villa-presentation-' . sanitize_html_class( $villa_presentation ) : '',
				'vvm-card-grid--columns-' . sanitize_html_class( $columns ),
				'vvm-card-grid--ratio-' . sanitize_html_class( $media_ratio ),
				)
			),
		'style' => implode( ';', $styles ),
	)
);

if ( '' === trim( $card_markup ) ) {
	return;
}
?>

<section <?php echo $wrapper_attributes; ?>>
	<?php if ( $use_carousel ) : ?>
		<div
			class="vvm-card-grid__carousel vvm-slider-surface"
			data-card-grid-carousel
			data-columns="<?php echo esc_attr( $columns_int ); ?>"
		>
			<div
				<?php
				echo gutenberg_lab_blocks_get_slider_controls_attributes(
					$attributes,
					array(
						'class_name' => 'vvm-card-grid__carousel-controls',
					)
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			>
				<button
					type="button"
					class="vvm-card-grid__carousel-button vvm-slider-button vvm-slider-button--prev"
					data-card-grid-prev
					aria-label="<?php esc_attr_e( 'Previous cards', 'gutenberg-lab-blocks' ); ?>"
				>
					<?php echo gutenberg_lab_blocks_get_slider_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
				<button
					type="button"
					class="vvm-card-grid__carousel-button vvm-slider-button vvm-slider-button--next"
					data-card-grid-next
					aria-label="<?php esc_attr_e( 'Next cards', 'gutenberg-lab-blocks' ); ?>"
				>
					<?php echo gutenberg_lab_blocks_get_slider_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
			</div>
			<div class="vvm-card-grid__viewport" data-card-grid-viewport>
				<div class="vvm-card-grid__items" data-card-grid-track>
					<?php echo $card_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</div>
	<?php else : ?>
		<div class="vvm-card-grid__items">
			<?php echo $card_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	<?php endif; ?>
</section>
