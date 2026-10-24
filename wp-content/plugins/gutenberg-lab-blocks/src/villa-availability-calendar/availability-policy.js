// Keep browser selection aligned with the PHP occupied-night validator.
const DAY_MS = 24 * 60 * 60 * 1000;
const dateKey = ( value ) => {
	if ( ! /^\d{4}-\d{2}-\d{2}$/.test( value || '' ) ) {
		return null;
	}
	const key = Date.parse( `${ value }T00:00:00Z` );
	return Number.isFinite( key ) && new Date( key ).toISOString().slice( 0, 10 ) === value
		? key : null;
};

export const isBlockedRangeStart = ( date, blocked ) => {
	const key = dateKey( date );
	return key !== null && blocked.has( date ) &&
		! blocked.has( new Date( key - DAY_MS ).toISOString().slice( 0, 10 ) );
};

// A checkout marker belongs to the first free day after an occupied night.
// Buffer days and back-to-back bookings must never look partly available.
export const isCheckoutDate = ( date, blocked, buffers = new Set() ) => {
	const key = dateKey( date );
	if ( key === null || blocked.has( date ) || buffers.has( date ) ) {
		return false;
	}
	const previous = new Date( key - DAY_MS ).toISOString().slice( 0, 10 );
	return blocked.has( previous ) && ! buffers.has( previous );
};

export const canArrive = ( date, blocked, minimumDate = '' ) =>
	dateKey( date ) !== null && date >= minimumDate && ! blocked.has( date );

export const canDepart = ( date, blocked, allowBlockedCheckout = false, minimumDate = '' ) =>
	dateKey( date ) !== null && date >= minimumDate && (
		! blocked.has( date ) || ( allowBlockedCheckout && isBlockedRangeStart( date, blocked ) )
	);

export const isStayAvailable = ( arrival, departure, blocked, allowBlockedCheckout = false, minimumDate = '' ) => {
	const start = dateKey( arrival );
	const end = dateKey( departure );
	if ( start === null || end === null || end <= start || ( end - start ) / DAY_MS > 1095 ||
		! canArrive( arrival, blocked, minimumDate ) ||
		! canDepart( departure, blocked, allowBlockedCheckout, minimumDate ) ) {
		return false;
	}
	// iCal DTEND is exclusive. The last blocked night is never a checkout day.
	for ( let cursor = start; cursor < end; cursor += DAY_MS ) {
		if ( blocked.has( new Date( cursor ).toISOString().slice( 0, 10 ) ) ) {
			return false;
		}
	}
	return true;
};
