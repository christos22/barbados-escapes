#!/usr/bin/env node

import path from 'node:path';
import process from 'node:process';
import ExcelJS from 'exceljs';

const SCHEMA_VERSION = '1.0';
const inputPath = process.argv[ 2 ] ? path.resolve( process.argv[ 2 ] ) : '';

const fail = ( message ) => {
	console.error( `ERROR: ${ message }` );
	process.exit( 1 );
};

if ( ! inputPath ) {
	console.error( 'Usage: parse-workbook.mjs <villa-workbook.xlsx>' );
	process.exit( 1 );
}

const workbook = new ExcelJS.Workbook();

try {
	await workbook.xlsx.readFile( inputPath );
} catch ( error ) {
	fail( `Could not read workbook: ${ error.message }` );
}

const getSheet = ( name ) => {
	const sheet = workbook.getWorksheet( name );

	if ( ! sheet ) {
		fail( `Missing required worksheet "${ name }". Do not rename or delete template tabs.` );
	}

	return sheet;
};

const cellText = ( cell ) => {
	const value = cell?.value;

	if ( value === null || value === undefined ) {
		return '';
	}

	if ( value instanceof Date ) {
		return value.toISOString().slice( 0, 10 );
	}

	if ( typeof value === 'object' ) {
		if ( Array.isArray( value.richText ) ) {
			return value.richText.map( ( part ) => part.text || '' ).join( '' ).trim();
		}

		if ( value.text !== undefined ) {
			return String( value.text ).trim();
		}

		if ( value.result !== undefined ) {
			return String( value.result ).trim();
		}
	}

	return String( value ).trim();
};

const readKeyValues = ( sheetName, startRow = 5 ) => {
	const sheet = getSheet( sheetName );
	const values = {};

	for ( let rowNumber = startRow; rowNumber <= sheet.rowCount; rowNumber++ ) {
		const row = sheet.getRow( rowNumber );
		const key = cellText( row.getCell( 1 ) );

		if ( ! key || key.startsWith( '#' ) ) {
			continue;
		}

		values[ key ] = cellText( row.getCell( 3 ) );
	}

	return values;
};

const readTable = ( sheetName, keysRow = 4, dataStartRow = 6 ) => {
	const sheet = getSheet( sheetName );
	const keys = [];
	const rows = [];
	const header = sheet.getRow( keysRow );

	for ( let column = 1; column <= sheet.columnCount; column++ ) {
		const key = cellText( header.getCell( column ) );

		if ( key ) {
			keys.push( { column, key } );
		}
	}

	if ( keys.length === 0 ) {
		fail( `Worksheet "${ sheetName }" has no import keys. Restore the original template tab.` );
	}

	for ( let rowNumber = dataStartRow; rowNumber <= sheet.rowCount; rowNumber++ ) {
		const row = sheet.getRow( rowNumber );
		const item = {};
		let hasValue = false;

		for ( const { column, key } of keys ) {
			const value = cellText( row.getCell( column ) );
			item[ key ] = value;
			hasValue ||= value !== '';
		}

		if ( hasValue ) {
			item.__row = rowNumber;
			rows.push( item );
		}
	}

	return rows;
};

const isNotApplicable = ( value ) =>
	String( value || '' ).trim().toLowerCase() === 'not applicable';

const removeNotApplicableRows = ( rows ) =>
	rows.filter( ( row ) => {
		const suppliedValues = Object.entries( row )
			.filter( ( [ key, value ] ) => ! key.startsWith( '__' ) && value !== '' )
			.map( ( [ , value ] ) => value );

		// A single "Not applicable" cell is the client-friendly way to omit an
		// entire table section. In populated rows it only clears that one field.
		return ! ( suppliedValues.length === 1 && isNotApplicable( suppliedValues[ 0 ] ) );
	} );

const numberValue = ( value ) => {
	const normalized = String( value || '' )
		.replace( /[$,\s]/g, '' )
		.trim();

	if ( normalized === '' ) {
		return null;
	}

	const number = Number( normalized );
	return Number.isFinite( number ) ? number : null;
};

const integerValue = ( value ) => {
	const number = numberValue( value );
	return Number.isInteger( number ) ? number : null;
};

const yesNoValue = ( value, fallback = true ) => {
	if ( value === true || value === false ) {
		return value;
	}

	const normalized = String( value || '' ).trim().toLowerCase();

	if ( ! normalized ) {
		return fallback;
	}

	if ( normalized === 'yes' ) {
		return true;
	}

	if ( normalized === 'no' ) {
		return false;
	}

	if ( normalized === 'true' ) {
		return true;
	}

	if ( normalized === 'false' ) {
		return false;
	}

	return null;
};

const addRequired = ( errors, object, keys, sheetName ) => {
	for ( const key of keys ) {
		if ( ! object[ key ] || isNotApplicable( object[ key ] ) ) {
			errors.push( `${ sheetName }: "${ key }" is required.` );
		}
	}
};

const hasOwn = ( object, key ) => Object.prototype.hasOwnProperty.call( object, key );
const rowNumber = ( item, fallback ) => Number( item.__row || fallback );

const isIsoDate = ( value ) => {
	if ( ! /^\d{4}-\d{2}-\d{2}$/.test( value ) ) {
		return false;
	}

	const date = new Date( `${ value }T00:00:00Z` );
	return ! Number.isNaN( date.valueOf() ) && date.toISOString().slice( 0, 10 ) === value;
};

const importSheet = getSheet( '_Import' );
const schemaVersion = cellText( importSheet.getCell( 'B1' ) );

if ( schemaVersion !== SCHEMA_VERSION ) {
	fail( `Unsupported workbook schema "${ schemaVersion || 'missing' }". Expected ${ SCHEMA_VERSION }.` );
}

const overview = readKeyValues( 'Overview' );
const story = readKeyValues( 'Villa Story' );
const extras = readKeyValues( 'Page Extras' );
const bedrooms = removeNotApplicableRows( readTable( 'Bedrooms' ) );
const amenities = removeNotApplicableRows( readTable( 'Amenities' ) );
const staff = removeNotApplicableRows( readTable( 'Staff' ) );
const rates = removeNotApplicableRows( readTable( 'Rates' ) );
const rules = removeNotApplicableRows( readTable( 'House Rules' ) );
const reviews = removeNotApplicableRows( readTable( 'Reviews' ) );
const nearby = removeNotApplicableRows( readTable( 'Nearby' ) );
const highlights = removeNotApplicableRows( readTable( 'Highlights' ) );
const relatedVillas = removeNotApplicableRows( readTable( 'Related Villas' ) );
const errors = [];
const warnings = [];

addRequired(
	errors,
	overview,
	[
		'villa_name',
		'property_area',
		'parish',
		'short_summary',
		'hero_location_line',
		'hero_statement',
		'bedrooms',
		'bathrooms',
		'sleeps',
		...( hasOwn( overview, 'bedroom_selector_enabled' ) ? [ 'bedroom_selector_enabled' ] : [] ),
		'pool_summary',
		'starting_rate_usd',
		'display_address',
	],
	'Overview'
);

addRequired(
	errors,
	story,
	[
		'story_eyebrow',
		'story_headline',
		'intro_paragraph_1',
		'expanded_paragraph_1',
		'why_love_headline',
		'natalie_title',
		'natalie_quote',
		'natalie_paragraph_1',
	],
	'Villa Story'
);

addRequired(
	errors,
	extras,
	[
		'contact_eyebrow',
		'contact_heading',
		'contact_text',
		'pricing_heading',
		'pricing_helper',
		'tax_note',
		'security_deposit_note',
		'location_description',
	],
	'Page Extras'
);

const bedroomCount = integerValue( overview.bedrooms );
const bathroomCount = numberValue( overview.bathrooms );
const sleepsCount = integerValue( overview.sleeps );
const startingRate = numberValue( overview.starting_rate_usd );
const bedroomSelectorEnabled = yesNoValue( overview.bedroom_selector_enabled, true );
const minimumBedroomChoice = integerValue( overview.minimum_bedroom_choice ) || 1;

if ( ! bedroomCount || bedroomCount < 1 ) {
	errors.push( 'Overview: Bedrooms must be a whole number greater than zero.' );
}

if ( bathroomCount === null || bathroomCount <= 0 ) {
	errors.push( 'Overview: Bathrooms must be a number greater than zero.' );
}

if ( ! sleepsCount || sleepsCount < 1 ) {
	errors.push( 'Overview: Sleeps must be a whole number greater than zero.' );
}

if ( startingRate === null || startingRate <= 0 ) {
	errors.push( 'Overview: Starting nightly rate must be a number greater than zero.' );
}

if ( bedroomSelectorEnabled === null ) {
	errors.push( 'Overview: Show bedroom selector must be Yes or No.' );
}

if ( minimumBedroomChoice < 1 ) {
	errors.push( 'Overview: Lowest bedroom option must be a whole number greater than zero.' );
}

if ( bedroomCount && minimumBedroomChoice > bedroomCount ) {
	errors.push( 'Overview: Lowest bedroom option cannot be greater than the bedroom total.' );
}

if ( bedrooms.length === 0 ) {
	errors.push( 'Bedrooms: Add at least one bedroom row.' );
} else if ( bedroomCount && bedrooms.length !== bedroomCount ) {
	errors.push(
		`Bedrooms: Overview says ${ bedroomCount }, but ${ bedrooms.length } bedroom rows were provided.`
	);
}

const bedroomNames = new Set();

for ( const [ index, bedroom ] of bedrooms.entries() ) {
	const row = rowNumber( bedroom, index + 6 );

	for ( const key of [ 'area', 'room_name', 'bed_configuration', 'description' ] ) {
		if ( ! bedroom[ key ] ) {
			errors.push( `Bedrooms row ${ row }: "${ key }" is required.` );
		}
	}

	const normalizedName = String( bedroom.room_name || '' ).toLowerCase();
	if ( normalizedName && bedroomNames.has( normalizedName ) ) {
		errors.push( `Bedrooms row ${ row }: room name "${ bedroom.room_name }" is duplicated.` );
	}
	bedroomNames.add( normalizedName );

	if (
		bedroom.ensuite &&
		! [ 'yes', 'no' ].includes( bedroom.ensuite.toLowerCase() )
	) {
		errors.push( `Bedrooms row ${ row }: Ensuite must be Yes or No.` );
	}
}

if ( amenities.length === 0 ) {
	errors.push( 'Amenities: Add at least one amenity row.' );
}

for ( const [ index, amenity ] of amenities.entries() ) {
	const row = rowNumber( amenity, index + 6 );

	if ( ! amenity.group || ! amenity.item ) {
		errors.push( `Amenities row ${ row }: group and amenity are required.` );
	}

	if (
		amenity.featured &&
		! [ 'yes', 'no' ].includes( amenity.featured.toLowerCase() )
	) {
		errors.push( `Amenities row ${ row }: Featured must be Yes or No.` );
	}
}

for ( const [ index, staffMember ] of staff.entries() ) {
	const row = rowNumber( staffMember, index + 6 );

	if ( ! staffMember.role || ! staffMember.arrangement || ! staffMember.description ) {
		errors.push( `Staff row ${ row }: role, arrangement, and description are required.` );
	}
}

if ( rates.length === 0 ) {
	errors.push( 'Rates: Add at least one seasonal rate row.' );
}

for ( const [ index, rate ] of rates.entries() ) {
	const row = rowNumber( rate, index + 6 );
	const rateAmount = numberValue( rate.nightly_rate_usd );
	const minimumNights = integerValue( rate.minimum_nights );

	if ( ! rate.season || ! rate.start_date || ! rate.end_date ) {
		errors.push( `Rates row ${ row }: season, start date, and end date are required.` );
	}

	if ( rate.start_date && ! isIsoDate( rate.start_date ) ) {
		errors.push( `Rates row ${ row }: start date must use YYYY-MM-DD.` );
	}

	if ( rate.end_date && ! isIsoDate( rate.end_date ) ) {
		errors.push( `Rates row ${ row }: end date must use YYYY-MM-DD.` );
	}

	if ( rateAmount === null || rateAmount <= 0 ) {
		errors.push( `Rates row ${ row }: nightly rate must be greater than zero.` );
	}

	if ( ! minimumNights || minimumNights < 1 ) {
		errors.push( `Rates row ${ row }: minimum nights must be a whole number.` );
	}

	if (
		isIsoDate( rate.start_date ) &&
		isIsoDate( rate.end_date ) &&
		rate.start_date > rate.end_date
	) {
		errors.push( `Rates row ${ row }: start date must be before end date.` );
	}
}

for ( const requiredRule of [ 'check-in', 'check-out', 'minimum stay', 'children', 'pets', 'smoking' ] ) {
	if ( ! rules.some( ( rule ) => String( rule.rule || '' ).toLowerCase() === requiredRule ) ) {
		errors.push( `House Rules: Add the "${ requiredRule }" rule.` );
	}
}

for ( const [ index, rule ] of rules.entries() ) {
	const row = rowNumber( rule, index + 6 );

	if ( ! rule.rule || ! rule.details ) {
		errors.push( `House Rules row ${ row }: rule and details are required.` );
	}
}

for ( const [ index, review ] of reviews.entries() ) {
	const row = rowNumber( review, index + 6 );

	if ( ! review.title || ! review.review || ! review.guest_name ) {
		errors.push( `Reviews row ${ row }: title, review, and guest name are required.` );
	}
}

if ( highlights.length < 3 ) {
	errors.push( 'Highlights: Add at least three reasons guests will love the villa.' );
}

for ( const [ index, highlight ] of highlights.entries() ) {
	if ( ! highlight.highlight ) {
		errors.push( `Highlights row ${ rowNumber( highlight, index + 6 ) }: highlight is required.` );
	}
}

if ( nearby.length === 0 ) {
	errors.push( 'Nearby: Add at least one nearby place.' );
}

for ( const [ index, place ] of nearby.entries() ) {
	const row = rowNumber( place, index + 6 );

	if ( ! place.place || ! place.travel_time ) {
		errors.push( `Nearby row ${ row }: place and travel time are required.` );
	}
}

for ( const [ index, relatedVilla ] of relatedVillas.entries() ) {
	if ( ! relatedVilla.villa_name ) {
		errors.push( `Related Villas row ${ rowNumber( relatedVilla, index + 6 ) }: villa name is required.` );
	}
}

if ( relatedVillas.length > 3 ) {
	warnings.push( 'More than three related villas were supplied; only the first three will be used.' );
}

if ( staff.length === 0 ) {
	warnings.push( 'No villa staff rows were supplied; the staff section will be omitted.' );
}

if ( reviews.length === 0 ) {
	warnings.push( 'No reviews were supplied; the Reviews tab will be omitted.' );
}

if ( relatedVillas.length === 0 ) {
	warnings.push( 'No related villas were supplied; the importer will select current published villas.' );
}

if ( ! overview.google_maps_link ) {
	warnings.push( 'No Google Maps link was supplied; schema coordinates must be added later.' );
}

if ( errors.length > 0 ) {
	for ( const error of errors ) {
		console.error( `ERROR: ${ error }` );
	}
	process.exit( 1 );
}

const compactValues = ( object ) =>
	Object.fromEntries(
		Object.entries( object ).filter(
			( [ key, value ] ) => ! key.startsWith( '__' ) && value !== '' && ! isNotApplicable( value )
		)
	);

const payload = {
	schema_version: SCHEMA_VERSION,
	source_file: path.basename( inputPath ),
	overview: {
		...compactValues( overview ),
		bedrooms: bedroomCount,
			bathrooms: bathroomCount,
			sleeps: sleepsCount,
			starting_rate_usd: startingRate,
			bedroom_selector_enabled: bedroomSelectorEnabled,
			minimum_bedroom_choice: minimumBedroomChoice,
		},
	story: compactValues( story ),
	extras: compactValues( extras ),
	bedrooms: bedrooms.map( compactValues ),
	amenities: amenities.map( compactValues ),
	staff: staff.map( compactValues ),
	rates: rates.map( ( rate ) => ( {
		...compactValues( rate ),
		nightly_rate_usd: numberValue( rate.nightly_rate_usd ),
		minimum_nights: integerValue( rate.minimum_nights ),
	} ) ),
	rules: rules.map( compactValues ),
	reviews: reviews.map( compactValues ),
	nearby: nearby.map( compactValues ),
	highlights: highlights.map( compactValues ),
	related_villas: relatedVillas.map( compactValues ),
	warnings,
};

process.stdout.write( `${ JSON.stringify( payload, null, 2 ) }\n` );
