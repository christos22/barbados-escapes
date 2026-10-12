/**
 * Use the site's date, not the visitor's timezone or a cached month's first day.
 */
export const getMinimumDate = ( data, now = new Date() ) => {
	const zone = data.timeZone || 'UTC';
	const offset = /^([+-])(\d{2}):(\d{2})$/.exec( zone );
	let today;

	if ( offset ) {
		const minutes = ( Number( offset[ 2 ] ) * 60 + Number( offset[ 3 ] ) ) *
			( offset[ 1 ] === '-' ? -1 : 1 );
		today = new Date( now.getTime() + minutes * 60000 ).toISOString().slice( 0, 10 );
	} else {
		const parts = new Intl.DateTimeFormat( 'en-US', {
			timeZone: zone, year: 'numeric', month: '2-digit', day: '2-digit',
		} ).formatToParts( now );
		const part = ( type ) => parts.find( ( value ) => value.type === type ).value;
		today = `${ part( 'year' ) }-${ part( 'month' ) }-${ part( 'day' ) }`;
	}

	return data.minimumDate > today ? data.minimumDate : today;
};
