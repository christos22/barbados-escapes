<?php
/**
 * Dynamic render for the Site Alerts placement block.
 *
 * @package GutenbergLabBlocks
 */

$slot = gutenberg_lab_blocks_sanitize_site_message_slot( $attributes['slot'] ?? 'header' );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => sprintf(
			'vvm-site-alerts vvm-site-alerts--slot-%s',
			sanitize_html_class( $slot )
		),
	)
);

echo gutenberg_lab_blocks_render_site_alerts_markup( $slot, $wrapper_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
