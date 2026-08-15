<?php
/**
 * Villa search-result card style setting and comparison screen.
 *
 * @package GutenbergLabBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the supported search-result card styles.
 *
 * @return array<string, string>
 */
function gutenberg_lab_blocks_get_villa_card_style_choices() {
	return array(
		'separate'     => __( 'Option A — Price on its own line', 'gutenberg-lab-blocks' ),
		'inline'       => __( 'Option B — Price inline with icons', 'gutenberg-lab-blocks' ),
		'inline_faint' => __( 'Option C — Price inline with faint dividers', 'gutenberg-lab-blocks' ),
		'inline_faint_facts' => __( 'Option D — Faint dividers and facts', 'gutenberg-lab-blocks' ),
	);
}

/**
 * Keeps the saved style within the supported allowlist.
 *
 * @param mixed $value Candidate option value.
 * @return string
 */
function gutenberg_lab_blocks_sanitize_villa_card_style( $value ) {
	$value   = sanitize_key( (string) $value );
	$choices = gutenberg_lab_blocks_get_villa_card_style_choices();

	return isset( $choices[ $value ] ) ? $value : 'separate';
}

/**
 * Returns the active global search-result card style.
 *
 * @return string
 */
function gutenberg_lab_blocks_get_villa_card_style() {
	return gutenberg_lab_blocks_sanitize_villa_card_style(
		get_option( 'gutenberg_lab_blocks_villa_card_style', 'separate' )
	);
}

/**
 * Registers the option through WordPress' Settings API.
 */
function gutenberg_lab_blocks_register_villa_card_style_setting() {
	register_setting(
		'gutenberg_lab_blocks_villa_card_style',
		'gutenberg_lab_blocks_villa_card_style',
		array(
			'default'           => 'separate',
			'sanitize_callback' => 'gutenberg_lab_blocks_sanitize_villa_card_style',
			'show_in_rest'      => false,
			'type'              => 'string',
		)
	);
}
add_action( 'admin_init', 'gutenberg_lab_blocks_register_villa_card_style_setting' );

/**
 * Adds the comparison screen beneath Villas in the dashboard.
 */
function gutenberg_lab_blocks_register_villa_card_style_page() {
	add_submenu_page(
		'edit.php?post_type=villa',
		__( 'Villa Card Style', 'gutenberg-lab-blocks' ),
		__( 'Card Style', 'gutenberg-lab-blocks' ),
		'manage_options',
		'vvm-villa-card-style',
		'gutenberg_lab_blocks_render_villa_card_style_page'
	);
}
add_action( 'admin_menu', 'gutenberg_lab_blocks_register_villa_card_style_page' );

/**
 * Loads card presentation CSS only on the comparison screen.
 *
 * @param string $hook_suffix Current admin page hook.
 */
function gutenberg_lab_blocks_enqueue_villa_card_style_admin_assets( $hook_suffix ) {
	if ( 'villa_page_vvm-villa-card-style' !== $hook_suffix ) {
		return;
	}

	$plugin_file = dirname( __DIR__ ) . '/gutenberg-lab-blocks.php';
	$plugin_url  = plugin_dir_url( $plugin_file );

	wp_enqueue_style(
		'gutenberg-lab-blocks-card-grid-admin-preview',
		$plugin_url . 'build/card-grid/style-index.css',
		array(),
		gutenberg_lab_blocks_asset_version( 'build/card-grid/style-index.css' )
	);

	wp_enqueue_style(
		'gutenberg-lab-blocks-villa-results-admin-preview',
		$plugin_url . 'build/villa-search-results/style-index.css',
		array( 'gutenberg-lab-blocks-card-grid-admin-preview' ),
		gutenberg_lab_blocks_asset_version( 'build/villa-search-results/style-index.css' )
	);

	wp_enqueue_style(
		'gutenberg-lab-blocks-villa-card-style-admin',
		$plugin_url . 'assets/admin/villa-card-style.css',
		array( 'gutenberg-lab-blocks-villa-results-admin-preview' ),
		gutenberg_lab_blocks_asset_version( 'assets/admin/villa-card-style.css' )
	);
}
add_action( 'admin_enqueue_scripts', 'gutenberg_lab_blocks_enqueue_villa_card_style_admin_assets' );

/**
 * Finds a real villa for both previews so comparisons use current card data.
 *
 * @return int
 */
function gutenberg_lab_blocks_get_villa_card_style_preview_id() {
	$preferred_villa = get_page_by_path( 'sandalwood-house', OBJECT, 'villa' );

	if ( $preferred_villa instanceof WP_Post ) {
		return (int) $preferred_villa->ID;
	}

	$villas = get_posts(
		array(
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'order'          => 'ASC',
			'orderby'        => 'title',
			'post_status'    => 'publish',
			'post_type'      => 'villa',
			'posts_per_page' => 1,
		)
	);

	return isset( $villas[0] ) ? (int) $villas[0] : 0;
}

/**
 * Renders the dashboard comparison and global selector.
 */
function gutenberg_lab_blocks_render_villa_card_style_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$current_style = gutenberg_lab_blocks_get_villa_card_style();
	$choices       = gutenberg_lab_blocks_get_villa_card_style_choices();
	$preview_id    = gutenberg_lab_blocks_get_villa_card_style_preview_id();
	?>
	<div class="wrap vvm-villa-card-style-admin">
		<h1><?php esc_html_e( 'Villa Card Style', 'gutenberg-lab-blocks' ); ?></h1>
		<p class="description">
			<?php esc_html_e( 'Compare the search-result card treatments using live villa content, then choose the site-wide style.', 'gutenberg-lab-blocks' ); ?>
		</p>

		<?php settings_errors(); ?>

		<form action="options.php" method="post">
			<?php settings_fields( 'gutenberg_lab_blocks_villa_card_style' ); ?>

			<div class="vvm-villa-card-style-admin__choices">
				<?php foreach ( $choices as $style_key => $style_label ) : ?>
					<div class="vvm-villa-card-style-admin__choice">
						<label class="vvm-villa-card-style-admin__choice-heading">
							<input
								type="radio"
								name="gutenberg_lab_blocks_villa_card_style"
								value="<?php echo esc_attr( $style_key ); ?>"
								<?php checked( $current_style, $style_key ); ?>
							>
							<strong><?php echo esc_html( $style_label ); ?></strong>
						</label>

						<?php if ( $preview_id > 0 ) : ?>
							<div class="vvm-villa-card-style-admin__preview vvm-villa-search-results">
								<?php
								echo gutenberg_lab_blocks_render_villa_card( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									$preview_id,
									array(
										'enquiry_url'      => get_permalink( $preview_id ) . '#enquire',
										'fact_style'       => $style_key,
										'presentation'     => 'collection',
										'show_fact_icons'  => true,
										'show_price'       => true,
									)
								);
								?>
							</div>
						<?php else : ?>
							<div class="notice notice-warning inline">
								<?php esc_html_e( 'Publish a villa to display the live card preview.', 'gutenberg-lab-blocks' ); ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<?php submit_button( __( 'Save Card Style', 'gutenberg-lab-blocks' ) ); ?>
		</form>
	</div>
	<?php
}
