import flatpickr from 'flatpickr';

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

const formatDateKey = ( value ) => new Date( value ).toISOString().slice( 0, 10 );

const addDays = ( value, days ) => {
	const dateKey = parseDateKey( value );

	if ( dateKey === null ) {
		return '';
	}

	return formatDateKey( dateKey + days * DAY_MS );
};

const normalizeDateValue = ( value ) => {
	const dateKey = parseDateKey( value );

	if ( dateKey === null ) {
		return '';
	}

	return formatDateKey( dateKey );
};

const formatPickerDate = ( date ) => {
	if ( ! ( date instanceof Date ) || Number.isNaN( date.getTime() ) ) {
		return '';
	}

	const year = date.getFullYear();
	const month = String( date.getMonth() + 1 ).padStart( 2, '0' );
	const day = String( date.getDate() ).padStart( 2, '0' );

	return `${ year }-${ month }-${ day }`;
};

const getLaterDate = ( first, second ) => {
	const firstKey = parseDateKey( first );
	const secondKey = parseDateKey( second );

	if ( firstKey === null ) {
		return second || first || '';
	}

	if ( secondKey === null ) {
		return first || second || '';
	}

	return secondKey > firstKey ? second : first;
};

const shiftMonthStart = ( value, monthOffset ) => {
	const parts = String( value || '' )
		.split( '-' )
		.map( ( part ) => Number( part ) );

	if ( parts.length !== 3 || parts.some( Number.isNaN ) ) {
		return value;
	}

	const date = new Date( parts[ 0 ], parts[ 1 ] - 1 + monthOffset, 1 );
	const year = date.getFullYear();
	const month = String( date.getMonth() + 1 ).padStart( 2, '0' );

	return `${ year }-${ month }-01`;
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

const getFieldByName = ( form, name ) => {
	if ( ! form || ! name ) {
		return null;
	}

	const fieldName =
		typeof window.CSS !== 'undefined' && typeof window.CSS.escape === 'function'
			? window.CSS.escape( name )
			: String( name ).replace( /"/g, '\\"' );

	return form.querySelector( `[name="${ fieldName }"]` );
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

const setFieldValue = ( form, name, value, type = 'hidden', shouldDispatch = true ) => {
	if ( ! form || ! name ) {
		return null;
	}

	let field = getFieldByName( form, name );

	if ( ! field ) {
		field = document.createElement( 'input' );
		field.type = type;
		field.name = name;
		form.appendChild( field );
	}

	field.value = value;

	if ( shouldDispatch ) {
		field.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		field.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	}

	return field;
};

const initializeCalendar = ( root ) => {
	const dataNode = root.querySelector( '[data-vvm-calendar-data]' );
	const statusNode = root.querySelector( '[data-vvm-calendar-status]' );
	const calendarNode = root.querySelector( '[data-vvm-calendar]' );
	const rangeNode = root.querySelector( '[data-vvm-calendar-range]' );
	const previousButton = root.querySelector( '[data-vvm-calendar-prev]' );
	const nextButton = root.querySelector( '[data-vvm-calendar-next]' );

	if ( ! dataNode || ! calendarNode ) {
		return;
	}

	let data;

	try {
		data = JSON.parse( dataNode.textContent );
	} catch ( error ) {
		return;
	}

	const unavailableDates = new Set( data.unavailableDates || [] );
	const checkoutBufferDates = new Set( data.bufferDates || [] );
	const allowUnavailableEndpoints = data.allowUnavailableEndpoints === true;
	const monthsToShow = Number( data.monthsToShow || 12 );
	const minimumStart = data.minimumStart || data.windowStart;
	let dayButtons = Array.from( root.querySelectorAll( '[data-vvm-calendar-day]' ) );
	let selectedArrival = '';
	let selectedDeparture = '';
	let previewDeparture = '';
	let windowStart = data.windowStart || minimumStart;
	let loadedWindowEnd =
		data.windowEnd || shiftMonthStart( windowStart, monthsToShow );
	let arrivalPicker = null;
	let departurePicker = null;
	let isSyncingPickers = false;
	let isLoading = false;

	const setStatus = ( message ) => {
		if ( statusNode ) {
			statusNode.classList.remove( 'has-selection' );
			statusNode.textContent = message;
		}
	};

	const setNavigationState = () => {
		if ( previousButton ) {
			previousButton.disabled = isLoading || windowStart <= minimumStart;
		}

		if ( nextButton ) {
			nextButton.disabled = isLoading;
		}

		root.classList.toggle( 'is-loading', isLoading );
		root.setAttribute( 'aria-busy', isLoading ? 'true' : 'false' );
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

		statusNode.classList.add( 'has-selection' );
		message.textContent = `${
			data.messages?.selected || 'Selected dates have been added to the enquiry form.'
		} ${ summary }.`;

		statusNode.replaceChildren( message );
	};

	const isUnavailableDate = ( date ) => unavailableDates.has( date );

	const isCheckoutBufferDate = ( date ) => checkoutBufferDates.has( date );

	const isUnavailableRangeStart = ( date ) =>
		isUnavailableDate( date ) && ! isUnavailableDate( addDays( date, -1 ) );

	const isUnavailableRangeEnd = ( date ) =>
		isUnavailableDate( date ) && ! isUnavailableDate( addDays( date, 1 ) );

	const isSelectableUnavailableRangeStart = ( date ) =>
		isUnavailableRangeStart( date ) && ! isCheckoutBufferDate( date );

	const isSelectableUnavailableRangeEnd = ( date ) =>
		isUnavailableRangeEnd( date ) && ! isCheckoutBufferDate( date );

	const canSelectArrivalDate = ( date ) =>
		! isUnavailableDate( date ) ||
		( allowUnavailableEndpoints && isSelectableUnavailableRangeEnd( date ) );

	const canSelectDepartureDate = ( date ) =>
		! isUnavailableDate( date ) ||
		( allowUnavailableEndpoints && isSelectableUnavailableRangeStart( date ) );

	const rangeIsAvailable = ( arrival, departure ) => {
		const arrivalKey = parseDateKey( arrival );
		const departureKey = parseDateKey( departure );

		if ( arrivalKey === null || departureKey === null || departureKey <= arrivalKey ) {
			return false;
		}

		if ( ! canSelectArrivalDate( arrival ) || ! canSelectDepartureDate( departure ) ) {
			return false;
		}

		for ( let cursor = arrivalKey; cursor < departureKey; cursor += DAY_MS ) {
			const cursorDate = new Date( cursor ).toISOString().slice( 0, 10 );

			// When endpoint selection is enabled, a grey check-in date is only
			// allowed if it is the final date in a blocked run.
			if (
				isUnavailableDate( cursorDate ) &&
					! (
						allowUnavailableEndpoints &&
						cursorDate === arrival &&
						isSelectableUnavailableRangeEnd( cursorDate )
					)
				) {
					return false;
			}
		}

		return true;
	};

	const syncButtons = () => {
		const arrivalKey = parseDateKey( selectedArrival );
		const departureKey = parseDateKey( selectedDeparture );
		const previewDepartureKey = parseDateKey( previewDeparture );
		const hasPreview =
			selectedArrival &&
			! selectedDeparture &&
			arrivalKey !== null &&
			previewDepartureKey !== null &&
			previewDepartureKey > arrivalKey;
		const previewIsAvailable =
			hasPreview && rangeIsAvailable( selectedArrival, previewDeparture );

		dayButtons.forEach( ( button ) => {
			const date = button.dataset.date;
			const dateKey = parseDateKey( date );
			const isUnavailable = isUnavailableDate( date );
			const isCheckoutBuffer = isCheckoutBufferDate( date );
			const isRangeStart =
				allowUnavailableEndpoints &&
				isUnavailable &&
				isSelectableUnavailableRangeStart( date );
			const isRangeEnd =
				allowUnavailableEndpoints &&
				isUnavailable &&
				isSelectableUnavailableRangeEnd( date );
			const isArrival = date === selectedArrival;
			const isDeparture = date === selectedDeparture;
			const canSelectBoundary =
				allowUnavailableEndpoints &&
				isUnavailable &&
				( selectedArrival && ! selectedDeparture && date > selectedArrival
					? isSelectableUnavailableRangeStart( date )
					: isSelectableUnavailableRangeEnd( date ) );
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
			const isPreviewEnd = hasPreview && date === previewDeparture;
			const isPreviewRange =
				hasPreview &&
				dateKey !== null &&
				dateKey > arrivalKey &&
				dateKey < previewDepartureKey;
			const isPreviewInvalid =
				hasPreview &&
				! previewIsAvailable &&
				dateKey !== null &&
				dateKey > arrivalKey &&
				dateKey <= previewDepartureKey;

			button.classList.toggle( 'is-selected-start', isArrival );
			button.classList.toggle( 'is-selected-end', isDeparture );
			button.classList.toggle( 'is-in-range', isInRange );
			button.classList.toggle( 'is-checkout-candidate', isCheckoutCandidate );
			button.classList.toggle(
				'is-preview-in-range',
				isPreviewRange && previewIsAvailable
			);
			button.classList.toggle( 'is-preview-end', isPreviewEnd && previewIsAvailable );
			button.classList.toggle( 'is-preview-invalid', isPreviewInvalid );
			button.classList.toggle( 'is-unavailable', isUnavailable );
			button.classList.toggle( 'is-checkout-buffer', isCheckoutBuffer );
			button.classList.toggle( 'is-unavailable-range-start', isRangeStart );
			button.classList.toggle( 'is-unavailable-range-end', isRangeEnd );
			button.classList.toggle( 'is-boundary-selectable', canSelectBoundary );
			button.setAttribute( 'aria-pressed', isArrival || isDeparture ? 'true' : 'false' );
			button.setAttribute(
				'aria-disabled',
				isUnavailable && ! canSelectBoundary && ! isArrival && ! isDeparture
					? 'true'
					: 'false'
			);
		} );
	};

	const clearPreview = () => {
		if ( ! previewDeparture ) {
			return;
		}

		previewDeparture = '';
		syncButtons();
	};

	const previewRange = ( date ) => {
		if ( ! selectedArrival || selectedDeparture || date <= selectedArrival ) {
			clearPreview();
			return;
		}

		if ( previewDeparture === date ) {
			return;
		}

		previewDeparture = date;
		syncButtons();
	};

	const bindDayButtons = () => {
		dayButtons.forEach( ( button ) => {
			button.addEventListener( 'pointerenter', () => {
				previewRange( button.dataset.date );
			} );

			button.addEventListener( 'focus', () => {
				previewRange( button.dataset.date );
			} );

			button.addEventListener( 'click', () => {
				const date = button.dataset.date;

				if ( ! selectedArrival || selectedDeparture || date <= selectedArrival ) {
					if ( ! canSelectArrivalDate( date ) ) {
						setStatus( data.messages?.selectArrival || 'Choose an available check-in date.' );
						return;
					}

					selectedArrival = date;
					selectedDeparture = '';
					previewDeparture = '';
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
				previewDeparture = '';
				syncButtons();
				fillEnquiryForm();
				renderSelectedStatus();
				scrollToElement( findEnquiryTarget( data.formSelector ) );
			} );
		} );
	};

	calendarNode.addEventListener( 'pointerleave', clearPreview );
	calendarNode.addEventListener( 'focusout', ( event ) => {
		if ( ! calendarNode.contains( event.relatedTarget ) ) {
			clearPreview();
		}
	} );

	const updateWindow = ( windowData ) => {
		if ( ! windowData || ! windowData.html ) {
			return;
		}

		calendarNode.innerHTML = windowData.html;
		windowStart = windowData.start || windowStart;
		loadedWindowEnd = getLaterDate( loadedWindowEnd, windowData.end );
		dayButtons = Array.from( root.querySelectorAll( '[data-vvm-calendar-day]' ) );

		if ( rangeNode && windowData.rangeLabel ) {
			rangeNode.textContent = windowData.rangeLabel;
		}

		( windowData.unavailableDates || [] ).forEach( ( date ) => {
			unavailableDates.add( date );
		} );

		( windowData.bufferDates || [] ).forEach( ( date ) => {
			checkoutBufferDates.add( date );
		} );

		bindDayButtons();
		refreshFormDatePickers();
		syncButtons();
	};

	const loadWindow = async ( nextStart ) => {
		if ( isLoading || ! data.endpoint || ! nextStart ) {
			return;
		}

		isLoading = true;
		setNavigationState();

		try {
			const url = new URL( data.endpoint, window.location.origin );

			url.searchParams.set( 'start', nextStart );
			url.searchParams.set( 'months', String( monthsToShow ) );
			url.searchParams.set( 'allowEndpoints', allowUnavailableEndpoints ? '1' : '0' );

			const response = await fetch( url.toString(), {
				credentials: 'same-origin',
				headers: {
					Accept: 'application/json',
				},
			} );

			if ( ! response.ok ) {
				throw new Error( `Availability request failed: ${ response.status }` );
			}

			updateWindow( await response.json() );
		} catch ( error ) {
			setStatus(
				data.messages?.loadError ||
					'Availability could not be loaded. Please try again.'
			);
		} finally {
			isLoading = false;
			setNavigationState();
		}
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
		const arrivalField = getFieldByName( form, fields.arrival );
		const departureField = getFieldByName( form, fields.departure );

		isSyncingPickers = true;
		arrivalPicker?.setDate( selectedArrival, false, 'Y-m-d' );
		setFieldValue( form, fields.arrival, selectedArrival, 'date', false );
		refreshFormDatePickers();
		departurePicker?.setDate( selectedDeparture, false, 'Y-m-d' );
		setFieldValue( form, fields.departure, selectedDeparture, 'date', false );
		isSyncingPickers = false;

		setFieldValue( form, fields.villaId, String( data.villaId || '' ), 'hidden', false );
		setFieldValue( form, fields.villaTitle, data.villaTitle || '', 'hidden', false );
		setFieldValue( form, fields.villaUrl, data.villaUrl || '', 'hidden', false );
		setFieldValue(
			form,
			fields.allowUnavailableEndpoints,
			allowUnavailableEndpoints ? '1' : '0',
			'hidden',
			false
		);
		setFieldValue( form, fields.dateSummary, summary, 'hidden', false );

		if ( arrivalField && departureField ) {
			validateFormDateFields( form, arrivalField, departureField );
		}
	};

	const getVisibleDateField = ( field ) => field?._flatpickr?.altInput || field;

	const syncVillaFormMetadata = ( form ) => {
		const fields = data.fields || {};

		setFieldValue( form, fields.villaId, String( data.villaId || '' ) );
		setFieldValue( form, fields.villaTitle, data.villaTitle || '' );
		setFieldValue( form, fields.villaUrl, data.villaUrl || '' );
		setFieldValue(
			form,
			fields.allowUnavailableEndpoints,
			allowUnavailableEndpoints ? '1' : '0'
		);
	};

	const setDateFieldValidity = ( field, message = '' ) => {
		if ( ! field ) {
			return;
		}

		const visibleField = getVisibleDateField( field );
		const fieldsToUpdate =
			visibleField && visibleField !== field ? [ field, visibleField ] : [ field ];

		fieldsToUpdate.forEach( ( currentField ) => {
			if ( typeof currentField.setCustomValidity === 'function' ) {
				currentField.setCustomValidity( message );
			}

			currentField.setAttribute( 'aria-invalid', message ? 'true' : 'false' );
		} );
		field.setAttribute( 'aria-invalid', message ? 'true' : 'false' );
	};

	const updateFormDateSummary = ( form, arrival, departure, isValidRange ) => {
		const fields = data.fields || {};

		if ( ! fields.dateSummary ) {
			return;
		}

		if ( ! arrival || ! departure || ! isValidRange ) {
			setFieldValue( form, fields.dateSummary, '' );
			return;
		}

		const nights = getNightsCount( arrival, departure );
		const summary = `${ formatDate( arrival ) } - ${ formatDate(
			departure
		) } (${ formatNights( nights ) })`;

		setFieldValue( form, fields.dateSummary, summary );
	};

	const validateFormDateFields = ( form, arrivalField, departureField, shouldReport = false ) => {
		const arrival = normalizeDateValue( arrivalField.value );
		const departure = normalizeDateValue( departureField.value );
		let isValid = true;
		let reportTarget = null;

		setDateFieldValidity( arrivalField );
		setDateFieldValidity( departureField );

		if ( minimumStart ) {
			arrivalField.min = minimumStart;
			departureField.min = arrival ? addDays( arrival, 1 ) : minimumStart;
		}

		if ( arrival && ! canSelectArrivalDate( arrival ) ) {
			setDateFieldValidity(
				arrivalField,
				data.messages?.arrivalUnavailable ||
					'That check-in date is unavailable. Please choose another date.'
			);
			isValid = false;
			reportTarget = reportTarget || arrivalField;
		}

		if ( arrival && departure && departure <= arrival ) {
			setDateFieldValidity(
				departureField,
				data.messages?.departureAfterArrival ||
					'Check-out date must be after check-in date.'
			);
			isValid = false;
			reportTarget = reportTarget || departureField;
		} else if (
			arrival &&
			departure &&
			! rangeIsAvailable( arrival, departure )
		) {
			setDateFieldValidity(
				departureField,
				data.messages?.unavailable || 'Those dates include unavailable nights.'
			);
			isValid = false;
			reportTarget = reportTarget || departureField;
		}

		updateFormDateSummary( form, arrival, departure, isValid );

		const visibleReportTarget = getVisibleDateField( reportTarget );

		if (
			shouldReport &&
			visibleReportTarget &&
			typeof visibleReportTarget.reportValidity === 'function'
		) {
			visibleReportTarget.reportValidity();
		}

		return isValid;
	};

	const getPickerAppendTarget = ( form ) =>
		form.closest( '.vvm-villa-contact__form-card' ) ||
		form.closest( '.vvm-villa-contact' ) ||
		form.parentElement ||
		document.body;

	const getArrivalDisableRules = () => [
		( date ) => ! canSelectArrivalDate( formatPickerDate( date ) ),
	];

	const getDepartureDisableRules = ( arrival ) => [
		( date ) => {
			const departure = formatPickerDate( date );

			if ( ! departure ) {
				return true;
			}

			if ( ! arrival ) {
				return ! canSelectDepartureDate( departure );
			}

			return departure <= arrival || ! rangeIsAvailable( arrival, departure );
		},
	];

	const refreshFormDatePickers = () => {
		const arrival = normalizeDateValue( arrivalPicker?.input?.value || '' );
		const pickerMaxDate = loadedWindowEnd || null;

		if ( arrivalPicker ) {
			arrivalPicker.set( 'minDate', minimumStart || null );
			arrivalPicker.set( 'maxDate', pickerMaxDate );
			arrivalPicker.set( 'disable', getArrivalDisableRules() );
			arrivalPicker.redraw();
		}

		if ( departurePicker ) {
			departurePicker.set( 'minDate', arrival ? addDays( arrival, 1 ) : minimumStart || null );
			departurePicker.set( 'maxDate', pickerMaxDate );
			departurePicker.set( 'disable', getDepartureDisableRules( arrival ) );
			departurePicker.redraw();
		}
	};

	const syncCalendarSelectionFromForm = ( form, arrivalField, departureField ) => {
		const arrival = normalizeDateValue( arrivalField.value );
		const departure = normalizeDateValue( departureField.value );
		const isValid = validateFormDateFields( form, arrivalField, departureField, true );
		const arrivalIsUnavailable = arrival && ! canSelectArrivalDate( arrival );

		selectedArrival = arrival && ! arrivalIsUnavailable ? arrival : '';
		selectedDeparture = arrival && departure && isValid ? departure : '';
		previewDeparture = '';

		if ( selectedArrival && selectedDeparture ) {
			renderSelectedStatus();
		} else if ( selectedArrival ) {
			setStatus( data.messages?.selectDeparture || 'Choose a check-out date.' );
		}

		syncButtons();
	};

	const setupDatePicker = ( field, options ) =>
		flatpickr( field, {
			allowInput: false,
			appendTo: options.appendTo,
			altFormat: 'M j, Y',
			altInput: true,
			altInputClass: `${ field.className || '' } vvm-villa-date-picker-input`.trim(),
			clickOpens: true,
			dateFormat: 'Y-m-d',
			disableMobile: true,
			maxDate: loadedWindowEnd || null,
			minDate: options.minDate || minimumStart || null,
			monthSelectorType: 'static',
			onChange: options.onChange,
			onDayCreate: ( _selectedDates, _dateStr, _instance, dayElement ) => {
				const date = formatPickerDate( dayElement.dateObj );

				if ( unavailableDates.has( date ) ) {
					dayElement.classList.add( 'vvm-flatpickr-unavailable' );
					dayElement.classList.toggle(
						'vvm-flatpickr-checkout-buffer',
						isCheckoutBufferDate( date )
					);
					dayElement.classList.toggle(
						'vvm-flatpickr-unavailable-start',
						allowUnavailableEndpoints && isSelectableUnavailableRangeStart( date )
					);
					dayElement.classList.toggle(
						'vvm-flatpickr-unavailable-end',
						allowUnavailableEndpoints && isSelectableUnavailableRangeEnd( date )
					);
					dayElement.classList.toggle(
						'vvm-flatpickr-boundary-selectable',
						allowUnavailableEndpoints &&
							( isSelectableUnavailableRangeStart( date ) ||
								isSelectableUnavailableRangeEnd( date ) )
					);
					dayElement.setAttribute(
						'title',
						data.messages?.arrivalUnavailable || 'Unavailable'
					);
				}
			},
			onReady: ( _selectedDates, _dateStr, instance ) => {
				instance.calendarContainer.classList.add( 'vvm-villa-date-picker' );
				instance.altInput?.setAttribute( 'readonly', 'readonly' );
			},
		} );

	const bindFormDateFields = () => {
		const form = findForm( data.formSelector );
		const fields = data.fields || {};

		if ( ! form || ! fields.arrival || ! fields.departure ) {
			return;
		}

		syncVillaFormMetadata( form );

		const arrivalField = getFieldByName( form, fields.arrival );
		const departureField = getFieldByName( form, fields.departure );

		if ( ! arrivalField || ! departureField ) {
			return;
		}

		const appendTo = getPickerAppendTarget( form );

		const validateWithMessage = () => {
			validateFormDateFields( form, arrivalField, departureField, true );
		};

		arrivalPicker = setupDatePicker( arrivalField, {
			appendTo,
			onChange: () => {
				if ( isSyncingPickers ) {
					return;
				}

				departurePicker?.clear( false );
				refreshFormDatePickers();
				syncCalendarSelectionFromForm( form, arrivalField, departureField );
			},
		} );

		departurePicker = setupDatePicker( departureField, {
			appendTo,
			minDate: minimumStart,
			onChange: () => {
				if ( isSyncingPickers ) {
					return;
				}

				syncCalendarSelectionFromForm( form, arrivalField, departureField );
			},
		} );

		arrivalField.addEventListener( 'input', validateWithMessage );
		arrivalField.addEventListener( 'change', validateWithMessage );
		departureField.addEventListener( 'input', validateWithMessage );
		departureField.addEventListener( 'change', validateWithMessage );

		form.addEventListener(
			'submit',
			( event ) => {
				if ( validateFormDateFields( form, arrivalField, departureField, true ) ) {
					return;
				}

				event.preventDefault();
				event.stopImmediatePropagation();
			},
			true
		);

		refreshFormDatePickers();
		validateFormDateFields( form, arrivalField, departureField );
	};

	if ( previousButton ) {
		previousButton.addEventListener( 'click', () => {
			const nextStart = shiftMonthStart( windowStart, -monthsToShow );

			loadWindow( nextStart < minimumStart ? minimumStart : nextStart );
		} );
	}

	if ( nextButton ) {
		nextButton.addEventListener( 'click', () => {
			loadWindow( shiftMonthStart( windowStart, monthsToShow ) );
		} );
	}

	bindFormDateFields();
	bindDayButtons();
	syncButtons();
	setNavigationState();
};

document.addEventListener( 'DOMContentLoaded', () => {
	document
		.querySelectorAll( '.vvm-villa-availability-calendar' )
		.forEach( initializeCalendar );
} );
