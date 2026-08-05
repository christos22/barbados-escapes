import flatpickr from 'flatpickr';

const DESKTOP_QUERY = '(min-width: 782px)';
const MOBILE_QUERY = '(max-width: 781px)';

const formatDateKey = ( date ) => {
	if ( ! ( date instanceof Date ) || Number.isNaN( date.getTime() ) ) {
		return '';
	}

	const year = date.getFullYear();
	const month = String( date.getMonth() + 1 ).padStart( 2, '0' );
	const day = String( date.getDate() ).padStart( 2, '0' );

	return `${ year }-${ month }-${ day }`;
};

const parseDateKey = ( value ) => {
	const parts = String( value || '' )
		.split( '-' )
		.map( Number );

	if ( parts.length !== 3 || parts.some( Number.isNaN ) ) {
		return null;
	}

	const date = new Date( parts[ 0 ], parts[ 1 ] - 1, parts[ 2 ] );

	return formatDateKey( date ) === value ? date : null;
};

const formatDisplayDate = ( date ) =>
	new Intl.DateTimeFormat( 'en-GB', {
		day: 'numeric',
		month: 'short',
		year: 'numeric',
	} ).format( date );

const formatDisplayRange = ( dates ) =>
	dates.map( formatDisplayDate ).join( ' – ' );

const setupDateRange = ( form ) => {
	const trigger = form.querySelector(
		'[data-vvm-villa-search-date-trigger]'
	);
	const fallback = form.querySelector(
		'[data-vvm-villa-search-date-fallback]'
	);
	const fallbackArrival = form.querySelector(
		'[data-vvm-villa-search-arrival-fallback]'
	);
	const fallbackDeparture = form.querySelector(
		'[data-vvm-villa-search-departure-fallback]'
	);
	const arrival = form.querySelector( '[data-vvm-villa-search-arrival]' );
	const departure = form.querySelector( '[data-vvm-villa-search-departure]' );

	if (
		! trigger ||
		! fallback ||
		! fallbackArrival ||
		! fallbackDeparture ||
		! arrival ||
		! departure
	) {
		return;
	}

	const selectedDates = [
		parseDateKey( arrival.value ),
		parseDateKey( departure.value ),
	].filter( Boolean );
	const desktopMedia = window.matchMedia( DESKTOP_QUERY );
	const dateError =
		form.dataset.vvmVillaSearchDateError ||
		'Choose both an arrival and departure date.';

	// Flatpickr parses the input value using its machine format, so clear the
	// server-formatted display copy and restore it in onReady.
	trigger.value = '';

	const picker = flatpickr( trigger, {
		allowInput: false,
		closeOnSelect: true,
		dateFormat: 'Y-m-d',
		defaultDate: selectedDates,
		disableMobile: true,
		minDate: 'today',
		mode: 'range',
		monthSelectorType: 'static',
		positionElement: trigger,
		showMonths: desktopMedia.matches ? 2 : 1,
		onChange: ( dates, _dateString, instance ) => {
			trigger.setCustomValidity( '' );

			if ( dates.length === 2 ) {
				arrival.value = formatDateKey( dates[ 0 ] );
				departure.value = formatDateKey( dates[ 1 ] );
				trigger.value = formatDisplayRange( dates );

				// Flatpickr normally closes a completed range. Calling close
				// explicitly keeps the interaction consistent across devices.
				window.setTimeout( () => instance.close(), 0 );
				return;
			}

			arrival.value = '';
			departure.value = '';
			trigger.value = dates.length ? formatDisplayDate( dates[ 0 ] ) : '';
		},
		onReady: ( dates, _dateString, instance ) => {
			instance.calendarContainer.classList.add(
				'vvm-villa-search-calendar'
			);
			trigger.value = dates.length ? formatDisplayRange( dates ) : '';
		},
	} );

	// Only switch names after the enhancement succeeds. Without JavaScript the
	// native date inputs remain a complete, submit-ready fallback.
	fallbackArrival.disabled = true;
	fallbackDeparture.disabled = true;
	arrival.name = 'arrival';
	departure.name = 'departure';
	trigger.hidden = false;
	form.classList.add( 'is-date-enhanced' );

	const syncMonthCount = ( event ) => {
		picker.set( 'showMonths', event.matches ? 2 : 1 );
		trigger.value = picker.selectedDates.length
			? formatDisplayRange( picker.selectedDates )
			: '';
	};

	desktopMedia.addEventListener?.( 'change', syncMonthCount );

	form.addEventListener( 'submit', ( event ) => {
		if ( picker.selectedDates.length === 1 ) {
			event.preventDefault();
			trigger.setCustomValidity( dateError );
			trigger.reportValidity();
			picker.open();
		}
	} );
};

const formatUsd = ( value ) => `$${ Number( value ).toLocaleString() }`;

const setupSelectMenus = ( form ) => {
	const selects = Array.from(
		form.querySelectorAll( '.vvm-villa-hero-search__select' )
	);
	const menus = [];

	const closeMenu = ( menu, restoreFocus = false ) => {
		menu.panel.hidden = true;
		menu.trigger.setAttribute( 'aria-expanded', 'false' );
		menu.wrapper.classList.remove(
			'vvm-villa-hero-search__select-menu--open'
		);

		if ( restoreFocus ) {
			menu.trigger.focus();
		}
	};

	const closeOtherMenus = ( activeMenu ) => {
		menus.forEach( ( menu ) => {
			if ( menu !== activeMenu ) {
				closeMenu( menu );
			}
		} );
	};

	selects.forEach( ( select ) => {
		const field = select.closest( '.vvm-villa-hero-search__field' );
		const label = field?.querySelector( 'label' );

		if ( ! field || ! label || ! select.id ) {
			return;
		}

		const wrapper = document.createElement( 'div' );
		const trigger = document.createElement( 'button' );
		const value = document.createElement( 'span' );
		const panel = document.createElement( 'div' );
		const labelId = `${ select.id }-label`;
		const triggerId = `${ select.id }-trigger`;
		const valueId = `${ select.id }-value`;
		const panelId = `${ select.id }-options`;

		wrapper.className = 'vvm-villa-hero-search__select-menu';
		trigger.className = 'vvm-villa-hero-search__select-trigger';
		trigger.type = 'button';
		trigger.id = triggerId;
		trigger.setAttribute( 'aria-controls', panelId );
		trigger.setAttribute( 'aria-expanded', 'false' );
		trigger.setAttribute( 'aria-haspopup', 'listbox' );
		value.id = valueId;
		value.className = 'vvm-villa-hero-search__select-value';
		panel.id = panelId;
		panel.className = 'vvm-villa-hero-search__select-options';
		panel.hidden = true;
		panel.setAttribute( 'role', 'listbox' );

		label.id = label.id || labelId;
		label.htmlFor = triggerId;
		trigger.setAttribute( 'aria-labelledby', `${ label.id } ${ valueId }` );
		panel.setAttribute( 'aria-labelledby', label.id );
		trigger.append( value );

		const optionButtons = Array.from( select.options ).map(
			( option, index ) => {
				const button = document.createElement( 'button' );

				button.className = 'vvm-villa-hero-search__select-option';
				button.type = 'button';
				button.id = `${ panelId }-${ index }`;
				button.dataset.value = option.value;
				button.disabled = option.disabled;
				button.tabIndex = -1;
				button.textContent = option.textContent.trim();
				button.setAttribute( 'role', 'option' );
				panel.append( button );

				return button;
			}
		);

		const menu = { optionButtons, panel, select, trigger, value, wrapper };
		menus.push( menu );

		const sync = () => {
			const selectedOption = select.options[ select.selectedIndex ];

			if ( ! selectedOption ) {
				return;
			}

			value.textContent = selectedOption.textContent.trim();
			trigger.dataset.placeholder = selectedOption.value
				? 'false'
				: 'true';

			optionButtons.forEach( ( button ) => {
				button.setAttribute(
					'aria-selected',
					button.dataset.value === selectedOption.value
						? 'true'
						: 'false'
				);
			} );
		};

		const openMenu = () => {
			closeOtherMenus( menu );
			panel.hidden = false;
			trigger.setAttribute( 'aria-expanded', 'true' );
			wrapper.classList.add( 'vvm-villa-hero-search__select-menu--open' );
		};

		const focusOption = ( index ) => {
			const boundedIndex = Math.max(
				0,
				Math.min( index, optionButtons.length - 1 )
			);

			optionButtons[ boundedIndex ]?.focus();
		};

		trigger.addEventListener( 'click', () => {
			if ( panel.hidden ) {
				openMenu();
			} else {
				closeMenu( menu );
			}
		} );

		trigger.addEventListener( 'keydown', ( event ) => {
			if ( ! [ 'ArrowDown', 'ArrowUp' ].includes( event.key ) ) {
				return;
			}

			event.preventDefault();
			openMenu();
			focusOption( select.selectedIndex );
		} );

		optionButtons.forEach( ( button, index ) => {
			button.addEventListener( 'click', () => {
				select.value = button.dataset.value;
				select.dispatchEvent(
					new Event( 'change', { bubbles: true } )
				);
				closeMenu( menu, true );
			} );

			button.addEventListener( 'keydown', ( event ) => {
				if ( event.key === 'Escape' ) {
					event.preventDefault();
					closeMenu( menu, true );
				} else if ( event.key === 'ArrowDown' ) {
					event.preventDefault();
					focusOption( index + 1 );
				} else if ( event.key === 'ArrowUp' ) {
					event.preventDefault();
					focusOption( index - 1 );
				} else if ( event.key === 'Home' ) {
					event.preventDefault();
					focusOption( 0 );
				} else if ( event.key === 'End' ) {
					event.preventDefault();
					focusOption( optionButtons.length - 1 );
				}
			} );
		} );

		select.addEventListener( 'change', sync );
		wrapper.append( trigger, panel );
		field.insertBefore( wrapper, select );
		select.hidden = true;
		sync();
	} );

	document.addEventListener( 'click', ( event ) => {
		menus.forEach( ( menu ) => {
			if ( ! menu.wrapper.contains( event.target ) ) {
				closeMenu( menu );
			}
		} );
	} );
};

const setupNumberFieldLabels = ( form ) => {
	const fields = Array.from(
		form.querySelectorAll( '[data-vvm-villa-search-number-field]' )
	);

	fields.forEach( ( field ) => {
		const input = field.querySelector(
			'[data-vvm-villa-search-number-input]'
		);
		const display = field.querySelector(
			'[data-vvm-villa-search-number-display]'
		);

		if ( ! input || ! display ) {
			return;
		}

		const sync = () => {
			const value = Number.parseInt( input.value, 10 );
			const hasValue = Number.isInteger( value ) && value > 0;

			field.classList.toggle( 'has-value', hasValue );
			display.textContent = hasValue
				? `${ value } ${
						value === 1
							? field.dataset.vvmVillaSearchSingular
							: field.dataset.vvmVillaSearchPlural
				  }`
				: '';
		};

		input.addEventListener( 'input', sync );
		sync();
	} );
};

const setupPriceRange = ( form ) => {
	const minimum = form.querySelector( '[data-vvm-villa-search-min-price]' );
	const maximum = form.querySelector( '[data-vvm-villa-search-max-price]' );
	const summary = form.querySelector(
		'[data-vvm-villa-search-price-summary]'
	);
	const details = summary?.closest( 'details' );

	if ( ! minimum || ! maximum || ! summary || ! details ) {
		return;
	}

	const priceError =
		form.dataset.vvmVillaSearchPriceError ||
		'The maximum price must not be lower than the minimum price.';
	const maximumPlaceholder =
		maximum.getAttribute( 'placeholder' ) || 'No maximum';

	const refresh = () => {
		const minValue = Number( minimum.value ) || 0;
		const maxValue = Number( maximum.value ) || 0;
		const isInvalid = minValue > 0 && maxValue > 0 && maxValue < minValue;

		// Keep the native number controls aware of each other. This blocks an
		// inverted range even before our custom message is considered.
		maximum.min = String( minValue || 500 );
		maximum.placeholder = minValue
			? `At least ${ formatUsd( minValue ) }`
			: maximumPlaceholder;

		if ( maxValue ) {
			minimum.max = String( maxValue );
		} else {
			minimum.removeAttribute( 'max' );
		}

		maximum.setCustomValidity( isInvalid ? priceError : '' );
		maximum.setAttribute( 'aria-invalid', isInvalid ? 'true' : 'false' );

		if ( isInvalid ) {
			summary.textContent = 'Check price range';
		} else if ( minValue && maxValue ) {
			summary.textContent = `${ formatUsd( minValue ) }–${ formatUsd(
				maxValue
			) }`;
		} else if ( minValue ) {
			summary.textContent = `From ${ formatUsd( minValue ) }`;
		} else if ( maxValue ) {
			summary.textContent = `Up to ${ formatUsd( maxValue ) }`;
		} else {
			summary.textContent = 'Search by Price';
		}
	};

	minimum.addEventListener( 'input', refresh );
	maximum.addEventListener( 'input', refresh );
	refresh();

	document.addEventListener( 'click', ( event ) => {
		if ( details.open && ! details.contains( event.target ) ) {
			details.open = false;
		}
	} );
};

const setupAdvancedFilters = ( form ) => {
	const toggle = form.querySelector( '[data-vvm-villa-search-more-toggle]' );
	const panel = form.querySelector( '[data-vvm-villa-search-advanced]' );
	const moreLabel = form.querySelector(
		'[data-vvm-villa-search-more-label]'
	);
	const fewerLabel = form.querySelector(
		'[data-vvm-villa-search-fewer-label]'
	);

	if ( ! toggle || ! panel || ! moreLabel || ! fewerLabel ) {
		return;
	}

	const mobileMedia = window.matchMedia( MOBILE_QUERY );
	let isExpanded = panel.dataset.vvmVillaSearchHasActive === 'true';

	const render = () => {
		if ( ! mobileMedia.matches ) {
			toggle.hidden = true;
			panel.hidden = false;
			return;
		}

		toggle.hidden = false;
		toggle.setAttribute( 'aria-expanded', isExpanded ? 'true' : 'false' );
		panel.hidden = ! isExpanded;
		moreLabel.hidden = isExpanded;
		fewerLabel.hidden = ! isExpanded;
	};

	toggle.addEventListener( 'click', () => {
		isExpanded = ! isExpanded;
		render();
	} );

	mobileMedia.addEventListener?.( 'change', render );
	render();
};

const setupCleanSubmission = ( form ) => {
	// Keep public search URLs concise without disabling progressive fallback.
	// Modern browsers expose the final successful form payload here, after
	// native constraint validation and the date-range checks have passed.
	form.addEventListener( 'formdata', ( event ) => {
		[
			'arrival',
			'departure',
			'villa_location',
			'villa_collection',
			'min_bedrooms',
			'guests',
			'min_price',
			'max_price',
		].forEach( ( name ) => {
			if ( event.formData.get( name ) === '' ) {
				event.formData.delete( name );
			}
		} );
	} );
};

const setupVillaSearch = ( form ) => {
	if ( form.dataset.vvmVillaSearchReady === 'true' ) {
		return;
	}

	form.dataset.vvmVillaSearchReady = 'true';
	setupDateRange( form );
	setupSelectMenus( form );
	setupNumberFieldLabels( form );
	setupPriceRange( form );
	setupAdvancedFilters( form );
	setupCleanSubmission( form );
};

const initVillaSearch = () => {
	document
		.querySelectorAll( '[data-vvm-villa-search]' )
		.forEach( setupVillaSearch );
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initVillaSearch, {
		once: true,
	} );
} else {
	initVillaSearch();
}
