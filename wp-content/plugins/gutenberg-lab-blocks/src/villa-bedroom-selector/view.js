const FORM_SELECTOR = '.vvm-villa-contact-form';
const FORM_FIELD_SELECTOR = 'select[name="villa-bedrooms"]';
const PRICING_SCOPE_SELECTOR = '.vvm-villa-pricing__card, #request-availability';
const PRICING_TABLE_SELECTOR = '.vvm-villa-pricing__table table';
const PRICING_KEY_DATASET = 'vvmBedroomPricingKey';

const hasOption = ( select, value ) =>
	Array.from( select.options ).some( ( option ) => option.value === value );

const updateSelect = ( select, value ) => {
	if ( ! select || select.value === value || ! hasOption( select, value ) ) {
		return;
	}

	select.value = value;
};

const getPricingTable = ( select ) => {
	const scope = select.closest( PRICING_SCOPE_SELECTOR ) ?? document;

	return scope.querySelector( PRICING_TABLE_SELECTOR );
};

const getSelectedPricingKey = ( select, value ) => {
	const option = Array.from( select?.options ?? [] ).find(
		( currentOption ) => currentOption.value === value
	);

	return option?.dataset?.[ PRICING_KEY_DATASET ] ?? '';
};

const updatePricingTable = ( select, value ) => {
	const selectedPricingKey = getSelectedPricingKey( select, value );
	const rows = Array.from( getPricingTable( select )?.tBodies?.[ 0 ]?.rows ?? [] );

	rows.forEach( ( row ) => {
		// Rows without a pricing key are generic seasonal notes, so keep them visible.
		const rowPricingKey = row.dataset?.[ PRICING_KEY_DATASET ] ?? '';

		row.hidden = Boolean(
			selectedPricingKey &&
				rowPricingKey &&
				rowPricingKey !== selectedPricingKey
		);
	} );
};

const form = document.querySelector( FORM_SELECTOR );
const formSelect = form?.querySelector( FORM_FIELD_SELECTOR );
const pricingSelects = Array.from(
	document.querySelectorAll( '[data-vvm-bedroom-selector]' )
);

const updatePricingSelects = ( value ) => {
	pricingSelects.forEach( ( select ) => {
		updateSelect( select, value );
		updatePricingTable( select, value );
	} );
};

if ( pricingSelects.length ) {
	pricingSelects.forEach( ( pricingSelect ) => {
		pricingSelect.addEventListener( 'change', () => {
			updateSelect( formSelect, pricingSelect.value );
			updatePricingSelects( pricingSelect.value );
		} );
	} );

	if ( form && formSelect ) {
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
			updatePricingSelects( pricingSelects[ 0 ].value );
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
	} else {
		updatePricingSelects( pricingSelects[ 0 ].value );
	}
}
