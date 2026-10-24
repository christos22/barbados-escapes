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
