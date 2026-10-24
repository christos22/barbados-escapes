import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { canArrive, canDepart, isStayAvailable } from '../src/villa-availability-calendar/availability-policy.js';

// The same behavioral cases exercise PHP search/CF7 and browser selection.
const cases = JSON.parse( readFileSync( new URL( './villa-availability-boundaries.json', import.meta.url ) ) );
for ( const test of cases ) {
	assert.equal( isStayAvailable( test.arrival, test.departure, new Set( test.blocked ), test.endpoints ), test.available, test.name );
}
const blocked = new Set( [ '2030-05-11', '2030-05-12', '2030-05-13' ] );
assert.equal( canArrive( '2030-05-13', blocked ), false, 'arrival picker rejects the last occupied night' );
assert.equal( canArrive( '2030-05-14', blocked ), true, 'arrival picker allows actual checkout' );
assert.equal( canDepart( '2030-05-11', blocked, true ), true, 'departure picker allows a same-day changeover' );
assert.equal( canDepart( '2030-05-12', blocked, true ), false, 'departure picker rejects dates inside a booking' );
assert.equal( canArrive( '2030-05-14', blocked, '2030-05-15' ), false, 'past arrival stays disabled' );
assert.equal( canDepart( '2030-05-11', blocked, true, '2030-05-15' ), false, 'past checkout stays disabled' );
console.log( `${ cases.length + 6 } browser availability boundary checks passed.` );
