<?php
/**
 * Standalone boundary regressions, with no database or network writes.
 * Run: php tests/villa-availability-boundaries.php [availability-file]
 */
if ( 'cli' !== PHP_SAPI ) {
	exit;
}
define( 'ABSPATH', __DIR__ . '/' );
function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function wp_timezone() { return new DateTimeZone( 'America/Barbados' ); }
function wp_date( $format ) { return ( new DateTimeImmutable( '2026-10-24', wp_timezone() ) )->format( $format ); }
function __( $text, $domain = '' ) { return $text; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return esc_html( $text ); }
function disabled( $value ) { if ( $value ) { echo 'disabled="disabled"'; } }

require $argv[1] ?? dirname( __DIR__ ) . '/includes/villa-availability.php';
$cases = json_decode( file_get_contents( __DIR__ . '/villa-availability-boundaries.json' ), true, 512, JSON_THROW_ON_ERROR );
$failed = array();
foreach ( $cases as $case ) {
	$actual = gutenberg_lab_blocks_is_villa_date_lookup_available(
		array_fill_keys( $case['blocked'], true ), $case['arrival'], $case['departure'], $case['endpoints']
	);
	if ( $actual !== $case['available'] ) {
		$failed[] = $case['name'];
	}
}
if ( $failed ) {
	fwrite( STDERR, 'FAIL: ' . implode( ', ', $failed ) . PHP_EOL );
	exit( 1 );
}
echo count( $cases ) . " PHP availability boundary checks passed.\n";

// Exercise real server-rendered markup, including first-of-month boundaries.
$markers = json_decode( file_get_contents( __DIR__ . '/villa-checkout-markers.json' ), true, 512, JSON_THROW_ON_ERROR );
foreach ( $markers as $case ) {
	$html = gutenberg_lab_blocks_render_villa_availability_month(
		new DateTimeImmutable( substr( $case['date'], 0, 7 ) . '-01', wp_timezone() ),
		array_fill_keys( $case['blocked'], true ), $case['endpoints'] ?? true,
		array_fill_keys( $case['buffers'] ?? array(), true )
	);
	if ( ! preg_match( '/<button\b[^>]*data-date="' . preg_quote( $case['date'], '/' ) . '"[^>]*>/s', $html, $matches ) ) {
		throw new RuntimeException( 'Missing rendered date: ' . $case['name'] );
	}
	$button = $matches[0];
	$blocked = in_array( $case['date'], $case['blocked'], true );
	if ( str_contains( $button, 'is-checkout-day' ) !== $case['checkout'] ||
		str_contains( $button, 'is-unavailable-range-start' ) !== $case['start'] ||
		str_contains( $button, 'data-unavailable="true"' ) !== $blocked ||
		str_contains( $button, 'aria-disabled="true"' ) !== $blocked ) {
		throw new RuntimeException( 'Incorrect checkout markup: ' . $case['name'] );
	}
}
echo count( $markers ) . " PHP checkout rendering cases passed.\n";
