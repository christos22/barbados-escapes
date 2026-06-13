const FORM_SELECTOR = '.vvm-villa-contact-form';
const FORM_FIELD_SELECTOR = 'select[name="villa-bedrooms"]';

const hasOption = ( select, value ) =>
	Array.from( select.options ).some( ( option ) => option.value === value );

const updateSelect = ( select, value ) => {
	if ( ! select || select.value === value || ! hasOption( select, value ) ) {
		return;
	}

	select.value = value;
};

const form = document.querySelector( FORM_SELECTOR );
const formSelect = form?.querySelector( FORM_FIELD_SELECTOR );
const pricingSelects = Array.from(
	document.querySelectorAll( '[data-vvm-bedroom-selector]' )
);

if ( form && formSelect && pricingSelects.length ) {
	const updatePricingSelects = ( value ) => {
		pricingSelects.forEach( ( select ) => updateSelect( select, value ) );
	};

	pricingSelects.forEach( ( pricingSelect ) => {
		pricingSelect.addEventListener( 'change', () => {
			updateSelect( formSelect, pricingSelect.value );
			updatePricingSelects( pricingSelect.value );
		} );
	} );

	formSelect.addEventListener( 'change', ( event ) => {
		// CF7 validates every field before the changed control. The bedroom value
		// is server-validated on submit, so keep this synchronization local.
		event.stopPropagation();
		updatePricingSelects( formSelect.value );
	} );

	if (
		formSelect.value &&
		hasOption( pricingSelects[ 0 ], formSelect.value )
	) {
		updatePricingSelects( formSelect.value );
	} else {
		updateSelect( formSelect, pricingSelects[ 0 ].value );
	}

	// CF7 resets fields after a successful AJAX submission.
	document.addEventListener( 'wpcf7reset', ( event ) => {
		if ( event.target !== form && ! form.contains( event.target ) ) {
			return;
		}

		window.requestAnimationFrame( () => {
			updatePricingSelects( formSelect.value );
		} );
	} );
}
