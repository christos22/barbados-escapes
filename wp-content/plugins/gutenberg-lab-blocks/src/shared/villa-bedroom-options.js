/**
 * Shared villa bedroom option helpers.
 *
 * Used by the bedroom selector settings and the Villa Rates rows, so both
 * read the same option list and the same option keys.
 */
import { useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';

export const BEDROOM_SELECTOR_META_KEY = 'villa_bedroom_selector_enabled';
export const BEDROOM_CHOICES_META_KEY = 'villa_bedroom_selector_choices';
export const MAX_CHOICES = 30;

export const toInteger = ( value, maximum ) => {
	const number = Number.parseInt( value, 10 );

	if ( ! Number.isFinite( number ) || number < 1 ) {
		return 0;
	}

	return Math.min( maximum, number );
};

export const normalizeChoiceLabel = ( value ) =>
	String( value ?? '' )
		.replace( /\s+/g, ' ' )
		.trim();

export const getChoiceDedupeKey = ( value ) =>
	normalizeChoiceLabel( value ).toLowerCase();

export const formatBedroomChoice = ( choice ) => {
	const bedrooms = toInteger( choice.bedrooms, MAX_CHOICES );
	const sleeps = toInteger( choice.sleeps, 100 );
	const bedroomLabel =
		bedrooms === 1
			? __( '1 Bedroom', 'gutenberg-lab-blocks' )
			: sprintf(
					/* translators: %d is the number of bedrooms. */
					__( '%d Bedrooms', 'gutenberg-lab-blocks' ),
					bedrooms
			  );

	return sleeps
		? sprintf(
				/* translators: 1: Bedroom label. 2: Number of guests. */
				__( '%1$s (sleeps %2$d)', 'gutenberg-lab-blocks' ),
				bedroomLabel,
				sleeps
		  )
		: bedroomLabel;
};

export const getChoiceLabel = ( choice ) => {
	if ( typeof choice === 'string' || typeof choice === 'number' ) {
		return normalizeChoiceLabel( choice );
	}

	if ( ! choice || typeof choice !== 'object' ) {
		return '';
	}

	const label = normalizeChoiceLabel( choice.label );

	if ( label ) {
		return label;
	}

	if ( toInteger( choice.bedrooms, MAX_CHOICES ) < 1 ) {
		return '';
	}

	return formatBedroomChoice( choice );
};

// Mirrors gutenberg_lab_blocks_sanitize_villa_bedroom_pricing_key() in PHP.
const PRICING_KEY_PATTERN = /^(?:bedrooms-\d+|choice-[a-z0-9_-]+)$/;

export const sanitizePricingKey = ( key ) => {
	const value = String( key ?? '' )
		.trim()
		.toLowerCase();

	return PRICING_KEY_PATTERN.test( value ) ? value : '';
};

// Mirrors gutenberg_lab_blocks_get_villa_bedroom_pricing_key_from_label():
// "4 Bedrooms (sleeps 8)" becomes "bedrooms-4"; other text becomes a slug.
export const getPricingKeyFromLabel = ( label ) => {
	const text = String( label ?? '' );
	const match = text.match( /(?:^|[^\d])(\d+)\s*[-–—]?\s*bed(?:room)?s?\b/i );

	if ( match && Number.parseInt( match[ 1 ], 10 ) > 0 ) {
		return `bedrooms-${ Number.parseInt( match[ 1 ], 10 ) }`;
	}

	const slug = text
		.normalize( 'NFKD' )
		.replace( /[̀-ͯ]/g, '' )
		.toLowerCase()
		.replace( /[^a-z0-9]+/g, '-' )
		.replace( /^-+|-+$/g, '' );

	return slug ? `choice-${ slug }` : '';
};

// New options get a random key, so it never depends on the text typed.
export const createChoiceKey = () =>
	`choice-${ Math.random().toString( 36 ).slice( 2, 10 ) }`;

// The key a choice links rate rows with: its stored key, or the old
// label-derived key for options saved before keys existed.
export const getChoiceKey = ( choice ) =>
	sanitizePricingKey( choice?.key ) ||
	getPricingKeyFromLabel( choice?.label );

/**
 * Stores a key on every choice that lacks one.
 *
 * Call this BEFORE applying an edit: the key is taken from the label as it is
 * now, so renaming "4 Bedrooms" keeps the rows that were linked to it.
 *
 * @param {Array<{label:string,key?:string}>} choices Current choices.
 * @return {Array<{label:string,key:string}>} Choices that all have a key.
 */
export const withChoiceKeys = ( choices ) => {
	const used = new Set(
		choices
			.map( ( choice ) => sanitizePricingKey( choice.key ) )
			.filter( Boolean )
	);

	return choices.map( ( choice ) => {
		const stored = sanitizePricingKey( choice.key );

		if ( stored ) {
			return { ...choice, key: stored };
		}

		let key = getPricingKeyFromLabel( choice.label );

		if ( ! key || used.has( key ) ) {
			key = createChoiceKey();
		}

		used.add( key );

		return { ...choice, key };
	} );
};

export const normalizeChoiceRows = ( choices ) =>
	( Array.isArray( choices ) ? choices : [] ).map( ( choice ) => {
		const row = { label: getChoiceLabel( choice ) };
		const key = sanitizePricingKey( choice?.key );

		// Keep the stored key so editing the list never drops it.
		if ( key ) {
			row.key = key;
		}

		return row;
	} );

export const getFilledChoiceRows = ( choices ) =>
	normalizeChoiceRows( choices ).filter(
		( choice ) => normalizeChoiceLabel( choice.label ) !== ''
	);

export const collectVillaSpecs = ( blocks, data ) => {
	blocks.forEach( ( block ) => {
		if ( block.name === 'gutenberg-lab-blocks/villa-spec-item' ) {
			const label = String( block.attributes.label || '' )
				.trim()
				.toLowerCase();
			const value = toInteger( block.attributes.value, 100 );

			if ( /^bedrooms?$/.test( label ) ) {
				data.bedrooms = Math.min( MAX_CHOICES, value );
			}
		}

		if ( block.innerBlocks?.length ) {
			collectVillaSpecs( block.innerBlocks, data );
		}
	} );
};

export const useBedroomSelectorEnabled = () =>
	useSelect( ( select ) => {
		const editor = select( 'core/editor' );

		if ( editor.getCurrentPostType() !== 'villa' ) {
			return true;
		}

		const meta = editor.getEditedPostAttribute( 'meta' ) || {};

		return meta[ BEDROOM_SELECTOR_META_KEY ] ?? false;
	}, [] );

export const useVillaBedroomChoices = () =>
	useSelect( ( select ) => {
		const editor = select( 'core/editor' );

		if ( editor.getCurrentPostType() !== 'villa' ) {
			return [];
		}

		const meta = editor.getEditedPostAttribute( 'meta' ) || {};

		return normalizeChoiceRows( meta[ BEDROOM_CHOICES_META_KEY ] );
	}, [] );

export const useAutomaticBedroomChoices = ( minimumBedrooms ) =>
	useSelect(
		( select ) => {
			const data = { bedrooms: 0 };
			collectVillaSpecs(
				select( 'core/block-editor' ).getBlocks(),
				data
			);

			if ( data.bedrooms < 1 ) {
				return [];
			}

			const minimum = Math.min(
				data.bedrooms,
				Math.max( 1, toInteger( minimumBedrooms, MAX_CHOICES ) )
			);
			const choices = [];

			for (
				let bedrooms = data.bedrooms;
				bedrooms >= minimum;
				bedrooms--
			) {
				choices.push( {
					label: formatBedroomChoice( { bedrooms } ),
				} );
			}

			return choices;
		},
		[ minimumBedrooms ]
	);

// Finds the first block with this name anywhere in the editor tree.
const findBlock = ( blocks, name ) => {
	for ( const block of blocks ) {
		if ( block.name === name ) {
			return block;
		}

		const found = findBlock( block.innerBlocks || [], name );

		if ( found ) {
			return found;
		}
	}

	return null;
};

/**
 * Returns the villa's bedroom options as { key, label } pairs.
 *
 * Same order of precedence as gutenberg_lab_blocks_get_villa_booking_data():
 * saved custom options, then the legacy selector block's options, then the
 * automatic range from the Villa Specs bedroom count.
 *
 * @return {{isEnabled:boolean, options:Array<{key:string,label:string}>}} Options.
 */
export const useVillaBedroomOptions = () => {
	const isEnabled = useBedroomSelectorEnabled();
	const metaChoices = useVillaBedroomChoices();
	const selectorAttributes = useSelect(
		( select ) =>
			findBlock(
				select( 'core/block-editor' ).getBlocks(),
				'gutenberg-lab-blocks/villa-bedroom-selector'
			)?.attributes ?? null,
		[]
	);
	const automaticChoices = useAutomaticBedroomChoices(
		selectorAttributes?.minimumBedrooms ?? 1
	);
	const legacyChoices = getFilledChoiceRows(
		selectorAttributes?.bedroomChoices ?? []
	);
	const filledMeta = getFilledChoiceRows( metaChoices );
	let choices = automaticChoices;

	if ( filledMeta.length ) {
		choices = filledMeta;
	} else if ( legacyChoices.length ) {
		choices = legacyChoices;
	}

	const seen = new Set();
	const options = [];

	choices.forEach( ( choice ) => {
		const key = getChoiceKey( choice );

		// A select can't hold two options with the same value; PHP would also
		// link both to the same rows, so keep the first.
		if ( ! key || seen.has( key ) ) {
			return;
		}

		seen.add( key );
		options.push( { key, label: normalizeChoiceLabel( choice.label ) } );
	} );

	return { isEnabled, options, metaChoices };
};
