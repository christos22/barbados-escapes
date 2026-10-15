<?php
/**
 * Standalone parser regressions: php tests/villa-ical-dates.php [parser-file]
 * Uses the real Sabre parser; WordPress shims keep this free of database writes.
 */
if ( 'cli' !== PHP_SAPI ) {
	exit;
}
define( 'ABSPATH', __DIR__ . '/' );
function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function wp_timezone() { return new DateTimeZone( 'America/Barbados' ); }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function absint( $value ) { return abs( (int) $value ); }
function __( $text, $domain = '' ) { return $text; }
class WP_Error {
	public function __construct( public $code, public $message ) {}
}

require dirname( __DIR__ ) . '/vendor/autoload.php';
require $argv[1] ?? dirname( __DIR__ ) . '/includes/villa-availability.php';

// Keep fixtures inside the parser's rolling 24-month window.
$year = (int) gmdate( 'Y' ) + 1;
$url  = 'https://www.worldwidedreamvillas.com/calendar-test';
$ics  = static function ( $properties ) {
	return "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//BE//Regression//EN\r\nBEGIN:VEVENT\r\nUID:date-test\r\nDTSTAMP:20260101T000000Z\r\n"
		. implode( "\r\n", $properties ) . "\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
};
$midnight = $ics( array( "DTSTART:{$year}0520T000000Z", "DTEND:{$year}0603T000000Z" ) );
$parse = static function ( $body, $source = '', $buffer = 0 ) {
	$result = gutenberg_lab_blocks_parse_ical_availability_dates( $body, $buffer, $source );
	if ( $result instanceof WP_Error ) {
		throw new RuntimeException( $result->message );
	}
	return $result;
};
$check = static function ( $label, $actual, $expected ) {
	if ( $actual !== $expected ) {
		fwrite( STDERR, "FAIL: {$label}\n" . json_encode( array( 'actual' => $actual, 'expected' => $expected ) ) . "\n" );
		exit( 1 );
	}
	echo "PASS: {$label}\n";
};
$span = static fn ( $start, $end ) => gutenberg_lab_blocks_get_date_span( "{$year}-{$start}", "{$year}-{$end}" );

$check( 'Dream Villas preserves the 14 booked nights and excludes checkout', $parse( $midnight, $url )['dates'], $span( '05-20', '06-03' ) );
$check( 'Bare Dream Villas hostname uses the same convention', $parse( $midnight, 'https://worldwidedreamvillas.com/test' )['dates'], $span( '05-20', '06-03' ) );
$check( 'Other providers retain timed UTC conversion', $parse( $midnight, 'https://example.com/test' )['dates'], $span( '05-19', '06-02' ) );
$check( 'Legacy callers retain timed UTC conversion', $parse( $midnight )['dates'], $span( '05-19', '06-02' ) );
$check( 'Hostname matching is exact', $parse( $midnight, 'https://worldwidedreamvillas.com.example.org/test' )['dates'], $span( '05-19', '06-02' ) );
$all_day = $ics( array( "DTSTART;VALUE=DATE:{$year}0520", "DTEND;VALUE=DATE:{$year}0603" ) );
$check( 'Airbnb all-day dates retain their calendar dates', $parse( $all_day, 'https://www.airbnb.com/test' )['dates'], $span( '05-20', '06-03' ) );
$check( 'Dream Villas all-day dates also remain unchanged', $parse( $all_day, $url )['dates'], $span( '05-20', '06-03' ) );
$timed = $ics( array( "DTSTART:{$year}0520T020000Z", "DTEND:{$year}0523T020000Z" ) );
$check( 'Actual timed Dream Villas events still convert to Barbados', $parse( $timed, $url )['dates'], $span( '05-19', '05-22' ) );
$mixed = $ics( array( "DTSTART:{$year}0520T000000Z", "DTEND:{$year}0523T020000Z" ) );
$check( 'Both endpoints must follow the midnight convention', $parse( $mixed, $url )['dates'], $span( '05-19', '05-22' ) );
$floating = $ics( array( "DTSTART:{$year}0520T150000", "DTEND:{$year}0523T110000" ) );
$check( 'Floating local times remain local', $parse( $floating, $url )['dates'], $span( '05-20', '05-23' ) );
$cancelled = $ics( array( "DTSTART:{$year}0520T000000Z", "DTEND:{$year}0603T000000Z", 'STATUS:CANCELLED' ) );
$check( 'Cancelled events remain ignored', $parse( $cancelled, $url )['dates'], array() );
$repeat = $ics( array( "DTSTART:{$year}0520T000000Z", "DTEND:{$year}0521T000000Z", 'RRULE:FREQ=WEEKLY;COUNT=2' ) );
$check( 'Recurring midnight dates preserve both occurrences', $parse( $repeat, $url )['dates'], array( "{$year}-05-20", "{$year}-05-27" ) );
$check( 'Turnaround buffers use the corrected boundaries', $parse( $midnight, $url, 1 )['buffer_dates'], array( "{$year}-05-19", "{$year}-06-03" ) );
$missing_end = $ics( array( "DTSTART:{$year}0520T000000Z" ) );
$check( 'Missing checkout retains the one-night fallback', $parse( $missing_end, $url )['dates'], array( "{$year}-05-20" ) );
echo "All 14 iCal date checks passed.\n";
