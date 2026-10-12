import assert from 'node:assert/strict';
import { getMinimumDate } from '../src/villa-availability-calendar/date-policy.js';

const now = new Date( '2026-10-12T02:00:00Z' );
assert.equal( getMinimumDate( { timeZone: 'America/Barbados', minimumDate: '2026-10-01' }, now ), '2026-10-11' );
assert.equal( getMinimumDate( { timeZone: '-04:00' }, now ), '2026-10-11' );
assert.equal( getMinimumDate( { timeZone: '+05:30' }, now ), '2026-10-12' );
assert.equal( getMinimumDate( { timeZone: 'UTC', minimumDate: '2026-10-01' }, now ), '2026-10-12' );
assert.equal( getMinimumDate( { timeZone: 'UTC', minimumDate: '2026-10-13' }, now ), '2026-10-13' );
assert.equal( getMinimumDate( { timeZone: 'UTC' }, new Date( '2026-12-31T23:59:59Z' ) ), '2026-12-31' );
assert.equal( getMinimumDate( { timeZone: 'UTC' }, new Date( '2027-01-01T00:00:00Z' ) ), '2027-01-01' );
console.log( '7 calendar timezone/cache-boundary checks passed.' );
