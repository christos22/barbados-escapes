<?php
/**
 * Shared package rendering helpers used by templates and dynamic blocks.
 *
 * @package GutenbergLabBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns a published package ID for editor previews when no real post context exists.
 *
 * Template editing often renders dynamic blocks outside a singular package request,
 * so we provide one stable preview package instead of showing empty placeholders.
 *
 * @return int
 */
function gutenberg_lab_blocks_get_preview_package_id() {
	$preview_posts = get_posts(
		array(
			'post_type'      => 'packages',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		)
	);

	if ( empty( $preview_posts ) ) {
		return 0;
	}

	return (int) $preview_posts[0];
}

/**
 * Resolve a package ID from block/post context.
 *
 * @param WP_Block|null $block                Current block instance.
 * @param bool          $allow_preview_fallback Whether editor previews may fall back to a sample package.
 * @return int
 */
function gutenberg_lab_blocks_resolve_package_id( $block = null, $allow_preview_fallback = false ) {
	if ( $block instanceof WP_Block && isset( $block->context['postId'] ) ) {
		return (int) $block->context['postId'];
	}

	$post_id = get_the_ID();

	if ( $post_id ) {
		return (int) $post_id;
	}

	if ( is_singular( 'packages' ) ) {
		return (int) get_queried_object_id();
	}

	if (
		$allow_preview_fallback &&
		(
			is_admin() ||
			( function_exists( 'wp_is_serving_rest_request' ) && wp_is_serving_rest_request() ) ||
			( defined( 'REST_REQUEST' ) && REST_REQUEST )
		)
	) {
		return gutenberg_lab_blocks_get_preview_package_id();
	}

	return 0;
}

/**
 * Returns the default CTA label for a supported CTA kind.
 *
 * @param string $cta_kind CTA kind slug.
 * @return string
 */
function gutenberg_lab_blocks_get_package_cta_default_label( $cta_kind ) {
	switch ( $cta_kind ) {
		case 'purchase':
			return __( 'Purchase Now', 'gutenberg-lab-blocks' );
		case 'book':
			return __( 'Book Now', 'gutenberg-lab-blocks' );
		case 'call':
			return __( 'Call to Book', 'gutenberg-lab-blocks' );
		default:
			return __( 'Learn More', 'gutenberg-lab-blocks' );
	}
}

/**
 * Formats a stored CTA value into a normalized array.
 *
 * @param int    $package_id Package ID.
 * @param string $position   CTA slot, primary or secondary.
 * @return array<string, string>|null
 */
function gutenberg_lab_blocks_get_package_cta( $package_id, $position ) {
	$position = 'secondary' === $position ? 'secondary' : 'primary';
	$label    = get_post_meta( $package_id, "package_{$position}_cta_label", true );
	$url      = get_post_meta( $package_id, "package_{$position}_cta_url", true );
	$kind     = get_post_meta( $package_id, "package_{$position}_cta_kind", true );
	$kind     = gutenberg_lab_blocks_sanitize_package_cta_kind( $kind );

	if ( '' === $url ) {
		return null;
	}

	if ( '' === $label ) {
		$label = gutenberg_lab_blocks_get_package_cta_default_label( $kind );
	}

	return array(
		'label' => $label,
		'url'   => $url,
		'kind'  => $kind,
	);
}

/**
 * Returns the structured data used across the package UI.
 *
 * @param int $package_id Package post ID.
 * @return array<string, mixed>|null
 */
function gutenberg_lab_blocks_get_package_data( $package_id ) {
	$package = get_post( $package_id );

	if ( ! $package instanceof WP_Post || 'packages' !== $package->post_type ) {
		return null;
	}

	$image_id  = (int) get_post_thumbnail_id( $package_id );
	$image_url = $image_id ? get_the_post_thumbnail_url( $package_id, 'large' ) : '';
	$image_alt = $image_id ? get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : '';
	$terms     = get_the_terms( $package_id, 'package_type' );
	$term_name = '';

	if ( $terms && ! is_wp_error( $terms ) ) {
		$term_name = $terms[0]->name;
	}

	if ( '' === $image_alt && $image_id ) {
		$image_alt = get_the_title( $image_id );
	}

	$excerpt = get_the_excerpt( $package_id );

	if ( '' === $excerpt ) {
		$excerpt = wp_trim_words( wp_strip_all_tags( $package->post_content ), 24 );
	}

	return array(
		'id'            => $package_id,
		'title'         => get_the_title( $package_id ),
		'permalink'     => get_permalink( $package_id ),
		'excerpt'       => $excerpt,
		'price'         => get_post_meta( $package_id, 'package_price', true ),
		'image_url'     => $image_url,
		'image_alt'     => $image_alt,
		'package_type'  => $term_name,
		'primary_cta'   => gutenberg_lab_blocks_get_package_cta( $package_id, 'primary' ),
		'secondary_cta' => gutenberg_lab_blocks_get_package_cta( $package_id, 'secondary' ),
	);
}

/**
 * Renders one CTA button using the existing theme button styles.
 *
 * @param array<string, string> $cta CTA data.
 * @param string                $style_slug Button style slug.
 * @return string
 */
function gutenberg_lab_blocks_render_package_cta_button( $cta, $style_slug ) {
	if ( empty( $cta['url'] ) || empty( $cta['label'] ) ) {
		return '';
	}

	return sprintf(
		'<div class="wp-block-button is-style-%1$s"><a class="wp-block-button__link wp-element-button" href="%2$s">%3$s</a></div>',
		esc_attr( $style_slug ),
		esc_url( $cta['url'] ),
		esc_html( $cta['label'] )
	);
}

/**
 * Renders the package price and CTA summary UI.
 *
 * @param int    $package_id Package post ID.
 * @param string $variant    Display variant.
 * @param array  $args       Visibility overrides.
 * @return string
 */
function gutenberg_lab_blocks_render_package_meta_markup( $package_id, $variant = 'hero', $args = array() ) {
	$package_data = gutenberg_lab_blocks_get_package_data( $package_id );

	if ( ! $package_data ) {
		return '';
	}

	$args            = wp_parse_args(
		$args,
		array(
			'show_package_type' => true,
			'show_price'        => true,
			'show_price_label'  => true,
			'show_ctas'         => true,
		)
	);
	$variant = 'card' === $variant ? 'card' : 'hero';

	// Card CTAs should read like the live package cards: a filled primary action
	// followed by a quieter text link stacked underneath.
	if ( 'card' === $variant ) {
		$primary_style   = 'vvm-primary';
		$secondary_style = 'vvm-link-primary';
	} else {
		$primary_style   = 'vvm-primary';
		$secondary_style = 'vvm-secondary';
	}
	$button_markup   = '';

	if ( $args['show_ctas'] && ! empty( $package_data['primary_cta'] ) ) {
		$button_markup .= gutenberg_lab_blocks_render_package_cta_button( $package_data['primary_cta'], $primary_style );
	}

	if ( $args['show_ctas'] && ! empty( $package_data['secondary_cta'] ) ) {
		$button_markup .= gutenberg_lab_blocks_render_package_cta_button( $package_data['secondary_cta'], $secondary_style );
	}

	if ( ! $args['show_price'] ) {
		$package_data['price'] = '';
	}

	if ( ! $args['show_package_type'] ) {
		$package_data['package_type'] = '';
	}

	if ( '' === $package_data['price'] && '' === $package_data['package_type'] && '' === $button_markup ) {
		return '';
	}

	ob_start();
	?>
	<div class="vvm-package-meta vvm-package-meta--<?php echo esc_attr( $variant ); ?>">
		<?php if ( '' !== $package_data['package_type'] ) : ?>
			<p class="vvm-package-meta__eyebrow"><?php echo esc_html( $package_data['package_type'] ); ?></p>
		<?php endif; ?>

		<?php if ( '' !== $package_data['price'] ) : ?>
			<div class="vvm-package-meta__price-wrap">
				<?php if ( $args['show_price_label'] ) : ?>
					<p class="vvm-package-meta__price-label"><?php esc_html_e( 'Package Price', 'gutenberg-lab-blocks' ); ?></p>
				<?php endif; ?>
				<p class="vvm-package-meta__price"><?php echo esc_html( $package_data['price'] ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $button_markup ) : ?>
			<div class="wp-block-buttons vvm-package-meta__actions">
				<?php echo $button_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		<?php endif; ?>
	</div>
	<?php

	return trim( (string) ob_get_clean() );
}

/**
 * Renders a single package card.
 *
 * @param int   $package_id Package post ID.
 * @param array $args       Visibility overrides.
 * @return string
 */
function gutenberg_lab_blocks_render_package_card( $package_id, $args = array() ) {
	$package_data = gutenberg_lab_blocks_get_package_data( $package_id );

	if ( ! $package_data ) {
		return '';
	}

	$args = wp_parse_args(
		$args,
		array(
			'show_package_type' => true,
			'show_excerpt'      => true,
			'show_price'        => true,
			'show_cta'          => true,
		)
	);

	ob_start();
	?>
	<article class="vvm-package-card">
		<a class="vvm-package-card__media-link" href="<?php echo esc_url( $package_data['permalink'] ); ?>">
			<?php if ( '' !== $package_data['image_url'] ) : ?>
				<img
					class="vvm-package-card__image"
					src="<?php echo esc_url( $package_data['image_url'] ); ?>"
					alt="<?php echo esc_attr( $package_data['image_alt'] ); ?>"
				/>
			<?php else : ?>
				<span class="vvm-package-card__image-placeholder"></span>
			<?php endif; ?>
		</a>

		<div class="vvm-package-card__body">
				<?php if ( $args['show_package_type'] && '' !== $package_data['package_type'] ) : ?>
					<p class="vvm-package-card__eyebrow"><?php echo esc_html( $package_data['package_type'] ); ?></p>
				<?php endif; ?>

			<h3 class="vvm-package-card__title">
				<a href="<?php echo esc_url( $package_data['permalink'] ); ?>">
					<?php echo esc_html( $package_data['title'] ); ?>
				</a>
			</h3>

				<?php
				echo gutenberg_lab_blocks_render_package_meta_markup(
					$package_id,
					'card',
					array(
						'show_package_type' => false,
						'show_price'        => $args['show_price'],
						'show_price_label'  => false,
						'show_ctas'         => false,
					)
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>

				<?php if ( $args['show_excerpt'] && '' !== $package_data['excerpt'] ) : ?>
					<p class="vvm-package-card__excerpt"><?php echo esc_html( $package_data['excerpt'] ); ?></p>
				<?php endif; ?>

				<?php
				echo gutenberg_lab_blocks_render_package_meta_markup(
					$package_id,
					'card',
					array(
						'show_package_type' => false,
						'show_price'        => false,
						'show_ctas'         => $args['show_cta'],
					)
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</div>
		</article>
	<?php

	return trim( (string) ob_get_clean() );
}

/**
 * Renders a package listing section used by grid and carousel blocks.
 *
 * Keeping the query and wrapper markup here means archive, related sections,
 * and future homepage rails all share the same package-card output path.
 *
 * @param array         $attributes         Block attributes.
 * @param WP_Block|null $block              Current block instance.
 * @param string        $wrapper_attributes Serialized wrapper attributes.
 * @param string        $header_markup      Saved nested block markup.
 * @return string
 */
function gutenberg_lab_blocks_render_packages_display_markup( $attributes, $block = null, $wrapper_attributes = '', $header_markup = '' ) {
	$attributes = wp_parse_args(
		$attributes,
		array(
			'count'           => 3,
			'columns'         => '3',
			'displayMode'     => 'grid',
			'excludeCurrent'  => false,
			'showPackageType' => true,
			'showExcerpt'     => true,
			'showPrice'       => true,
			'showCta'         => false,
			'suppressHeader'  => false,
		)
	);

	$header_markup     = trim( (string) $header_markup );
	$count             = max( 1, (int) $attributes['count'] );
	$columns           = in_array( (string) $attributes['columns'], array( '2', '3' ), true ) ? (string) $attributes['columns'] : '3';
	$display_mode      = 'carousel' === $attributes['displayMode'] ? 'carousel' : 'grid';
	$exclude_current   = ! empty( $attributes['excludeCurrent'] );
	$show_package_type = ! empty( $attributes['showPackageType'] );
	$show_excerpt      = ! empty( $attributes['showExcerpt'] );
	$show_price        = ! empty( $attributes['showPrice'] );
	$show_cta          = ! empty( $attributes['showCta'] );
	$suppress_header   = ! empty( $attributes['suppressHeader'] );
	$current_id        = $exclude_current ? gutenberg_lab_blocks_resolve_package_id( $block ) : 0;
	$visible_columns   = (int) $columns;

	$query_args = array(
		'post_type'           => 'packages',
		'post_status'         => 'publish',
		'posts_per_page'      => $count,
		'ignore_sticky_posts' => true,
		'orderby'             => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
	);

	if ( $current_id ) {
		$query_args['post__not_in'] = array( $current_id );
	}

	$packages_query = new WP_Query( $query_args );

	if ( ! $packages_query->have_posts() ) {
		wp_reset_postdata();
		return '';
	}

	$use_carousel = 'carousel' === $display_mode && $packages_query->post_count > 1;

	if ( '' === $wrapper_attributes ) {
		$wrapper_attributes = sprintf(
			'class="vvm-packages-display vvm-packages-display--display-%1$s vvm-packages-display--columns-%2$s"',
			esc_attr( $display_mode ),
			esc_attr( $columns )
		);
	}

	ob_start();
	?>
	<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php if ( ! $suppress_header && '' !== $header_markup ) : ?>
			<header class="vvm-packages-display__header">
				<?php echo $header_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</header>
		<?php endif; ?>

		<?php if ( $use_carousel ) : ?>
			<div
				class="vvm-packages-display__carousel"
				data-packages-display-carousel
				data-visible-columns="<?php echo esc_attr( $visible_columns ); ?>"
			>
				<div class="vvm-packages-display__carousel-controls vvm-slider-controls vvm-slider-controls--bottom-right">
					<button
						type="button"
						class="vvm-packages-display__carousel-button vvm-slider-button vvm-slider-button--prev"
						data-packages-display-prev
						aria-label="<?php esc_attr_e( 'Previous packages', 'gutenberg-lab-blocks' ); ?>"
					>
						<?php echo gutenberg_lab_blocks_get_slider_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
					<button
						type="button"
						class="vvm-packages-display__carousel-button vvm-slider-button vvm-slider-button--next"
						data-packages-display-next
						aria-label="<?php esc_attr_e( 'Next packages', 'gutenberg-lab-blocks' ); ?>"
					>
						<?php echo gutenberg_lab_blocks_get_slider_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
				</div>

				<div class="vvm-packages-display__viewport" data-packages-display-viewport>
					<div class="vvm-packages-display__items" data-packages-display-track>
						<?php
						while ( $packages_query->have_posts() ) :
							$packages_query->the_post();
							echo gutenberg_lab_blocks_render_package_card(
								get_the_ID(),
								array(
									'show_package_type' => $show_package_type,
									'show_excerpt'      => $show_excerpt,
									'show_price'        => $show_price,
									'show_cta'          => $show_cta,
								)
							); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						endwhile;
						?>
					</div>
				</div>
			</div>
		<?php else : ?>
			<div class="vvm-packages-display__items">
				<?php
				while ( $packages_query->have_posts() ) :
					$packages_query->the_post();
					echo gutenberg_lab_blocks_render_package_card(
						get_the_ID(),
						array(
							'show_package_type' => $show_package_type,
							'show_excerpt'      => $show_excerpt,
							'show_price'        => $show_price,
							'show_cta'          => $show_cta,
						)
					); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				endwhile;
				?>
			</div>
		<?php endif; ?>
	</section>
	<?php

	wp_reset_postdata();

	return trim( (string) ob_get_clean() );
}
