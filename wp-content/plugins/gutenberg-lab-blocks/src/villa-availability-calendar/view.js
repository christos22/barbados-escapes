const DAY_MS = 24 * 60 * 60 * 1000;

const parseDateKey = ( value ) => {
	const parts = String( value || '' )
		.split( '-' )
		.map( ( part ) => Number( part ) );

	if ( parts.length !== 3 || parts.some( Number.isNaN ) ) {
		return null;
	}

	return Date.UTC( parts[ 0 ], parts[ 1 ] - 1, parts[ 2 ] );
};

const formatDate = ( value ) => {
	const parts = String( value || '' )
		.split( '-' )
		.map( ( part ) => Number( part ) );

	if ( parts.length !== 3 || parts.some( Number.isNaN ) ) {
		return value;
	}

	return new Intl.DateTimeFormat( document.documentElement.lang || 'en-GB', {
		day: 'numeric',
		month: 'short',
		year: 'numeric',
	} ).format( new Date( parts[ 0 ], parts[ 1 ] - 1, parts[ 2 ] ) );
};

const getNightsCount = ( arrival, departure ) => {
	const arrivalKey = parseDateKey( arrival );
	const departureKey = parseDateKey( departure );

	if ( arrivalKey === null || departureKey === null || departureKey <= arrivalKey ) {
		return 0;
	}

	return Math.round( ( departureKey - arrivalKey ) / DAY_MS );
};

const formatNights = ( nights ) => {
	if ( nights === 1 ) {
		return '1 night';
	}

	return `${ nights } nights`;
};

const findForm = ( selector ) => {
	if ( ! selector ) {
		return null;
	}

	let target;

	// The selector is block-configurable, so invalid CSS should fail softly.
	try {
		target = document.querySelector( selector );
	} catch ( error ) {
		return null;
	}

	if ( ! target ) {
		return null;
	}

	if ( target.matches( 'form' ) ) {
		return target;
	}

	return target.querySelector( 'form' );
};

const findEnquiryTarget = ( selector ) => {
	const form = findForm( selector );

	if ( ! form ) {
		return null;
	}

	return (
		form.closest( '.vvm-villa-contact__form-card' ) ||
		form.closest( '.vvm-villa-contact' ) ||
		form
	);
};

const scrollToElement = ( element ) => {
	if ( ! element ) {
		return;
	}

	const headerOffset = 96;
	const top = element.getBoundingClientRect().top + window.scrollY - headerOffset;

	window.scrollTo( {
		top: Math.max( 0, top ),
		behavior: 'smooth',
	} );
};

const setFieldValue = ( form, name, value, type = 'hidden' ) => {
	if ( ! form || ! name ) {
		return;
	}

	let field = form.querySelector( `[name="${ name }"]` );

	if ( ! field ) {
		field = document.createElement( 'input' );
		field.type = type;
		field.name = name;
		form.appendChild( field );
	}

	field.value = value;
	field.dispatchEvent( new Event( 'input', { bubbles: true } ) );
	field.dispatchEvent( new Event( 'change', { bubbles: true } ) );
};

const initializeCalendar = ( root ) => {
	const dataNode = root.querySelector( '[data-vvm-calendar-data]' );
	const statusNode = root.querySelector( '[data-vvm-calendar-status]' );
	const dayButtons = Array.from( root.querySelectorAll( '[data-vvm-calendar-day]' ) );

	if ( ! dataNode || dayButtons.length === 0 ) {
		return;
	}

	let data;

	try {
		data = JSON.parse( dataNode.textContent );
	} catch ( error ) {
		return;
	}

	const unavailableDates = new Set( data.unavailableDates || [] );
	let selectedArrival = '';
	let selectedDeparture = '';

	const setStatus = ( message ) => {
		if ( statusNode ) {
			statusNode.classList.remove( 'has-selection' );
			statusNode.textContent = message;
		}
	};

	const renderSelectedStatus = () => {
		if ( ! statusNode ) {
			return;
		}

		const nights = getNightsCount( selectedArrival, selectedDeparture );
		const summary = `${ formatDate( selectedArrival ) } - ${ formatDate(
			selectedDeparture
		) } (${ formatNights( nights ) })`;
		const message = document.createElement( 'span' );
		const button = document.createElement( 'button' );

		statusNode.classList.add( 'has-selection' );
		message.textContent = `${
			data.messages?.selected || 'Selected dates have been added to the enquiry form.'
		} ${ summary }. `;

		button.type = 'button';
		button.className = 'vvm-villa-availability-calendar__status-cta';
		button.textContent = data.messages?.completeEnquiry || 'Enquire';
		button.addEventListener( 'click', () => {
			scrollToElement( findEnquiryTarget( data.formSelector ) );
		} );

		statusNode.replaceChildren( message, button );
	};

	const rangeIsAvailable = ( arrival, departure ) => {
		const arrivalKey = parseDateKey( arrival );
		const departureKey = parseDateKey( departure );

		if ( arrivalKey === null || departureKey === null || departureKey <= arrivalKey ) {
			return false;
		}

		for ( let cursor = arrivalKey; cursor < departureKey; cursor += DAY_MS ) {
			const cursorDate = new Date( cursor ).toISOString().slice( 0, 10 );

			if ( unavailableDates.has( cursorDate ) ) {
				return false;
			}
		}

		return true;
	};

	const syncButtons = () => {
		const arrivalKey = parseDateKey( selectedArrival );
		const departureKey = parseDateKey( selectedDeparture );

		dayButtons.forEach( ( button ) => {
			const date = button.dataset.date;
			const dateKey = parseDateKey( date );
			const isUnavailable = unavailableDates.has( date );
			const isArrival = date === selectedArrival;
			const isDeparture = date === selectedDeparture;
			const isInRange =
				arrivalKey !== null &&
				departureKey !== null &&
				dateKey !== null &&
				dateKey > arrivalKey &&
				dateKey < departureKey;
			const isCheckoutCandidate =
				selectedArrival &&
				! selectedDeparture &&
				dateKey !== null &&
				arrivalKey !== null &&
				dateKey > arrivalKey &&
				rangeIsAvailable( selectedArrival, date );

			button.classList.toggle( 'is-selected-start', isArrival );
			button.classList.toggle( 'is-selected-end', isDeparture );
			button.classList.toggle( 'is-in-range', isInRange );
			button.classList.toggle( 'is-checkout-candidate', isCheckoutCandidate );
			button.classList.toggle( 'is-unavailable', isUnavailable );
			button.setAttribute( 'aria-pressed', isArrival || isDeparture ? 'true' : 'false' );
		} );
	};

	const fillEnquiryForm = () => {
		const form = findForm( data.formSelector );

		if ( ! form ) {
			return;
		}

		const fields = data.fields || {};
		const nights = getNightsCount( selectedArrival, selectedDeparture );
		const summary = `${ formatDate( selectedArrival ) } - ${ formatDate(
			selectedDeparture
		) } (${ formatNights( nights ) })`;

		setFieldValue( form, fields.arrival, selectedArrival, 'date' );
		setFieldValue( form, fields.departure, selectedDeparture, 'date' );
		setFieldValue( form, fields.villaId, String( data.villaId || '' ) );
		setFieldValue( form, fields.villaTitle, data.villaTitle || '' );
		setFieldValue( form, fields.villaUrl, data.villaUrl || '' );
		setFieldValue( form, fields.dateSummary, summary );
	};

	dayButtons.forEach( ( button ) => {
		button.addEventListener( 'click', () => {
			const date = button.dataset.date;
			const isUnavailable = unavailableDates.has( date );

			if ( ! selectedArrival || selectedDeparture || date <= selectedArrival ) {
				if ( isUnavailable ) {
					setStatus( data.messages?.selectArrival || 'Choose an available check-in date.' );
					return;
				}

				selectedArrival = date;
				selectedDeparture = '';
				setStatus( data.messages?.selectDeparture || 'Choose a check-out date.' );
				syncButtons();
				return;
			}

			if ( ! rangeIsAvailable( selectedArrival, date ) ) {
				setStatus( data.messages?.unavailable || 'Those dates include unavailable nights.' );
				syncButtons();
				return;
			}

			selectedDeparture = date;
			syncButtons();
			fillEnquiryForm();
			renderSelectedStatus();
		} );
	} );

	syncButtons();
};

document.addEventListener( 'DOMContentLoaded', () => {
	document
		.querySelectorAll( '.vvm-villa-availability-calendar' )
		.forEach( initializeCalendar );
} );
