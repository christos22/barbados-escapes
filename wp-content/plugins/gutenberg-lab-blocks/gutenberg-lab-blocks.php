<?php
/**
 * Plugin Name: Gutenberg Lab Blocks
 * Description: Custom Gutenberg blocks for learning block development.
 * Version: 0.1.0
 * Author: Your Name
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gutenberg_lab_blocks_register_blocks() {
	register_block_type( __DIR__ . '/build/notice-box' );
	register_block_type( __DIR__ . '/build/media-panel' );
}
add_action( 'init', 'gutenberg_lab_blocks_register_blocks' );
