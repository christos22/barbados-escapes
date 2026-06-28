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

const getOptionalSheet = ( name ) => workbook.getWorksheet( name ) || null;

const getSheetAny = ( names ) => {
	for ( const name of names ) {
		const sheet = getOptionalSheet( name );

		if ( sheet ) {
			return sheet;
		}
	}

	fail( `Missing required worksheet "${ names[ 0 ] }". Do not rename or delete template tabs.` );
};

const formatTimeOnlyDate = ( value ) => {
	const hours = value.getUTCHours();
	const minutes = value.getUTCMinutes();
	const period = hours >= 12 ? 'pm' : 'am';
	const hour12 = hours % 12 || 12;

	return minutes === 0
		? `${ hour12 }${ period }`
		: `${ hour12 }:${ String( minutes ).padStart( 2, '0' ) }${ period }`;
};

const cellText = ( cell ) => {
	const value = cell?.value;

	if ( value === null || value === undefined ) {
		return '';
	}

	if ( value instanceof Date ) {
		const numFmt = String( cell?.numFmt || '' ).toLowerCase();
		const isTimeOnly = value.getUTCFullYear() <= 1900 && /h|am\/pm/.test( numFmt );

		if ( isTimeOnly ) {
			return formatTimeOnlyDate( value );
		}

		return value.toISOString().slice( 0, 10 );
	}

	if ( typeof value === 'object' ) {
		if ( Array.isArray( value.richText ) ) {
			return value.richText.map( ( part ) => part.text || '' ).join( '' ).trim();
		}

		if ( value.text !== undefined ) {
			if ( typeof value.text === 'object' && Array.isArray( value.text.richText ) ) {
				return value.text.richText.map( ( part ) => part.text || '' ).join( '' ).trim();
			}

			return String( value.text ).trim();
		}

		if ( value.result !== undefined ) {
			return String( value.result ).trim();
		}
	}

	return String( value ).trim();
};

const styleWarnings = [];
const styleWarningKeys = new Set();

const isRedArgb = ( value ) => {
	const hex = String( value || '' ).replace( /^#/, '' ).toUpperCase();
	const rgb = hex.length === 8 ? hex.slice( 2 ) : hex;

	if ( rgb.length !== 6 || ! /^[0-9A-F]{6}$/.test( rgb ) ) {
		return false;
	}

	if ( [ 'FF0000', 'C00000', '9C0006', 'FFC7CE' ].includes( rgb ) ) {
		return true;
	}

	const red = Number.parseInt( rgb.slice( 0, 2 ), 16 );
	const green = Number.parseInt( rgb.slice( 2, 4 ), 16 );
	const blue = Number.parseInt( rgb.slice( 4, 6 ), 16 );

	return red >= 160 && red - green >= 60 && red - blue >= 60;
};

const isRedMarkedCell = ( cell ) => {
	const fill = cell?.fill;
	const font = cell?.font;

	return [
		fill?.fgColor?.argb,
		fill?.bgColor?.argb,
		font?.color?.argb,
	].some( isRedArgb );
};

const addStyleWarning = ( message ) => {
	if ( styleWarningKeys.has( message ) ) {
		return;
	}

	styleWarningKeys.add( message );
	styleWarnings.push( message );
};

const readKeyValuesFromSheet = ( sheet, startRow = 5 ) => {
	const values = {};

	for ( let rowNumber = startRow; rowNumber <= sheet.rowCount; rowNumber++ ) {
		const row = sheet.getRow( rowNumber );
		const keyCell = row.getCell( 1 );
		const valueCell = row.getCell( 3 );
		const key = cellText( keyCell );

		if ( ! key || key.startsWith( '#' ) ) {
			continue;
		}

		if ( isRedMarkedCell( keyCell ) || isRedMarkedCell( valueCell ) ) {
			addStyleWarning(
				`${ sheet.name } row ${ rowNumber }: red-marked import cell was skipped.`
			);
			continue;
		}

		values[ key ] = cellText( valueCell );
	}

	return values;
};

const readKeyValues = ( sheetName, startRow = 5 ) =>
	readKeyValuesFromSheet( getSheet( sheetName ), startRow );

const readKeyValuesAny = ( sheetNames, startRow = 5 ) =>
	readKeyValuesFromSheet( getSheetAny( sheetNames ), startRow );

const readTableFromSheet = ( sheet, keysRow = 4, dataStartRow = 6 ) => {
	const keys = [];
	const rows = [];
	const header = sheet.getRow( keysRow );
	const commentsColumns = [];
	const labelRow = sheet.getRow( keysRow + 1 );

	for ( let column = 1; column <= sheet.columnCount; column++ ) {
		const headerCell = header.getCell( column );
		const key = cellText( headerCell );

		if ( key ) {
			if ( isRedMarkedCell( headerCell ) ) {
				addStyleWarning(
					`${ sheet.name } column ${ headerCell.address.replace( /\d+$/, '' ) }: red-marked import column was skipped.`
				);
				continue;
			}

			keys.push( { column, key } );
		}

		if ( cellText( labelRow.getCell( column ) ).toLowerCase() === 'comments' ) {
			commentsColumns.push( column );
		}
	}

	if ( keys.length === 0 ) {
		fail( `Worksheet "${ sheet.name }" has no import keys. Restore the original template tab.` );
	}

	for ( let rowNumber = dataStartRow; rowNumber <= sheet.rowCount; rowNumber++ ) {
		const row = sheet.getRow( rowNumber );
		const item = {};
		let hasValue = false;
		const redCells = [];

		for ( const { column, key } of keys ) {
			const cell = row.getCell( column );
			const value = cellText( cell );

			if ( isRedMarkedCell( cell ) ) {
				redCells.push( cell.address );
			}

			item[ key ] = value;
			hasValue ||= value !== '';
		}

		if ( hasValue && redCells.length > 0 ) {
			addStyleWarning(
				`${ sheet.name } row ${ rowNumber }: skipped because ${ redCells.join( ', ' ) } is marked red.`
			);
			continue;
		}

		const comments = commentsColumns
			.map( ( column ) => cellText( row.getCell( column ) ) )
			.filter( Boolean )
			.join( ' ' );

		if ( comments ) {
			item.__comments = comments;
		}

		if ( hasValue ) {
			item.__row = rowNumber;
			rows.push( item );
		}
	}

	return rows;
};

const readTable = ( sheetName, keysRow = 4, dataStartRow = 6 ) =>
	readTableFromSheet( getSheet( sheetName ), keysRow, dataStartRow );

const readTableOptional = ( sheetName, keysRow = 4, dataStartRow = 6 ) => {
	const sheet = getOptionalSheet( sheetName );
	return sheet ? readTableFromSheet( sheet, keysRow, dataStartRow ) : [];
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

const currencyValue = ( value ) => {
	const normalized = String( value || '' )
		.replace( /\bUSD\b/gi, '' )
		.replace( /US\$/gi, '' )
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

const commaListValue = ( value ) =>
	String( value || '' )
		.split( ',' )
		.map( ( item ) => item.trim().replace( /\s+/g, ' ' ) )
		.filter( Boolean );

const coordinatePairValue = ( value ) => {
	const match = String( value || '' ).match( /(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)/ );

	if ( ! match ) {
		return null;
	}

	const latitude = Number( match[ 1 ] );
	const longitude = Number( match[ 2 ] );

	if (
		! Number.isFinite( latitude ) ||
		! Number.isFinite( longitude ) ||
		latitude < -90 ||
		latitude > 90 ||
		longitude < -180 ||
		longitude > 180
	) {
		return null;
	}

	return `${ latitude }, ${ longitude }`;
};

const rateLabelFromComments = ( comments ) => {
	const text = String( comments || '' ).replace( /\s+/g, ' ' ).trim();

	if ( ! text || text.length > 120 ) {
		return '';
	}

	if ( /\brate\b/i.test( text ) ) {
		return text;
	}

	const bedroomLabel = text.match( /^(\d{1,2})\s*(?:bed(?:room)?s?|brs?)\.?$/i );

	if ( bedroomLabel ) {
		const bedroomCount = Number( bedroomLabel[ 1 ] );
		return `${ bedroomCount } ${ bedroomCount === 1 ? 'Bedroom' : 'Bedrooms' }`;
	}

	return '';
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

const dateValue = ( value ) => {
	if ( value instanceof Date && ! Number.isNaN( value.valueOf() ) ) {
		return value.toISOString().slice( 0, 10 );
	}

	const text = String( value || '' ).trim();

	if ( ! text ) {
		return null;
	}

	if ( /^\d{4}-\d{2}-\d{2}$/.test( text ) ) {
		const date = new Date( `${ text }T00:00:00Z` );
		return ! Number.isNaN( date.valueOf() ) && date.toISOString().slice( 0, 10 ) === text
			? text
			: null;
	}

	const match = text.match( /^(\d{1,2})\s+([A-Za-z]{3,9})\s+(\d{4})$/ );

	if ( ! match ) {
		return null;
	}

	const monthNames = {
		jan: 0,
		january: 0,
		feb: 1,
		february: 1,
		mar: 2,
		march: 2,
		apr: 3,
		april: 3,
		may: 4,
		jun: 5,
		june: 5,
		jul: 6,
		july: 6,
		aug: 7,
		august: 7,
		sep: 8,
		sept: 8,
		september: 8,
		oct: 9,
		october: 9,
		nov: 10,
		november: 10,
		dec: 11,
		december: 11,
	};
	const day = Number( match[ 1 ] );
	const month = monthNames[ match[ 2 ].toLowerCase() ];
	const year = Number( match[ 3 ] );

	if ( month === undefined ) {
		return null;
	}

	const date = new Date( Date.UTC( year, month, day ) );

	return date.getUTCFullYear() === year &&
		date.getUTCMonth() === month &&
		date.getUTCDate() === day
		? date.toISOString().slice( 0, 10 )
		: null;
};

const importSheet = getSheet( '_Import' );
const schemaVersion = cellText( importSheet.getCell( 'B1' ) );

if ( schemaVersion !== SCHEMA_VERSION ) {
	fail( `Unsupported workbook schema "${ schemaVersion || 'missing' }". Expected ${ SCHEMA_VERSION }.` );
}

const overview = readKeyValues( 'Overview' );
const story = readKeyValues( 'Villa Story' );
const extras = readKeyValuesAny( [ 'Pricing & Enquiry', 'Page Extras' ] );
const bedrooms = removeNotApplicableRows( readTable( 'Bedrooms' ) );
const amenities = removeNotApplicableRows( readTable( 'Amenities' ) );
const staff = removeNotApplicableRows( readTable( 'Staff' ) );
const rates = removeNotApplicableRows( readTable( 'Rates' ) );
const rules = removeNotApplicableRows( readTable( 'House Rules' ) );
const reviews = removeNotApplicableRows( readTable( 'Reviews' ) );
const nearby = removeNotApplicableRows( readTable( 'Nearby' ) );
const storyHighlights = [ 1, 2, 3, 4, 5 ]
	.map( ( index ) => ( {
		highlight: story[ `highlight_${ index }` ] || '',
		__row: 16 + index,
	} ) )
	.filter( ( highlight ) => highlight.highlight !== '' );
const highlights = storyHighlights.length > 0
	? storyHighlights
	: removeNotApplicableRows( readTableOptional( 'Highlights' ) );
const relatedVillas = removeNotApplicableRows( readTable( 'Related Villas' ) );
const errors = [];
const warnings = [];
warnings.push( ...styleWarnings );

const truncateForWarning = ( value ) => {
	const text = String( value || '' ).replace( /\s+/g, ' ' ).trim();
	return text.length > 140 ? `${ text.slice( 0, 137 ) }...` : text;
};

const addCommentWarnings = ( sheetName, rows ) => {
	for ( const row of rows ) {
		if ( row.__comments && ! row.__comments_imported ) {
			warnings.push(
				`${ sheetName } row ${ rowNumber( row, '?' ) }: Comments column is not imported: "${ truncateForWarning( row.__comments ) }".`
			);
		}
	}
};

for ( const rate of rates ) {
	const importedLabel = rate.rate_label || rateLabelFromComments( rate.__comments );

	if ( importedLabel && ! rate.rate_label ) {
		rate.rate_label = importedLabel;
		rate.__comments_imported = true;
		warnings.push(
			`Rates row ${ rowNumber( rate, '?' ) }: Comments looked like a rate label and was imported as "${ truncateForWarning( importedLabel ) }". Move this into Rate label next time.`
		);
	}
}

for ( const [ sheetName, rows ] of [
	[ 'Bedrooms', bedrooms ],
	[ 'Amenities', amenities ],
	[ 'Staff', staff ],
	[ 'Rates', rates ],
	[ 'House Rules', rules ],
	[ 'Reviews', reviews ],
	[ 'Nearby', nearby ],
	[ 'Related Villas', relatedVillas ],
] ) {
	addCommentWarnings( sheetName, rows );
}

for ( const removedKey of [
	'short_summary',
	'primary_view',
	'card_small_label',
	'card_cta_label',
] ) {
	delete overview[ removedKey ];
}

delete story.natalie_title;

for ( const removedKey of [
	'contact_heading',
	'contact_text',
	'whatsapp_label',
	'pricing_heading',
	'pricing_helper',
	'location_description',
	'related_heading',
] ) {
	delete extras[ removedKey ];
}

for ( const amenity of amenities ) {
	delete amenity.featured;
}

for ( const rate of rates ) {
	delete rate.season;
}

addRequired(
	errors,
	overview,
	[
		'villa_name',
		'property_area',
		'parish',
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
		'tax_note',
		'security_deposit_note',
	],
	'Pricing & Enquiry'
);

const bedroomCount = integerValue( overview.bedrooms );
const bathroomCount = numberValue( overview.bathrooms );
const sleepsCount = integerValue( overview.sleeps );
const startingRate = currencyValue( overview.starting_rate_usd );
const bedroomSelectorEnabled = yesNoValue( overview.bedroom_selector_enabled, false );
const hasLegacyMinimumBedroomChoice = hasOwn( overview, 'minimum_bedroom_choice' );
const minimumBedroomChoice = hasLegacyMinimumBedroomChoice
	? ( integerValue( overview.minimum_bedroom_choice ) || 1 )
	: 1;
const bedroomSelectorChoices = commaListValue( overview.bedroom_selector_choices );
const coordinates = coordinatePairValue( overview.coordinates );

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

if ( hasLegacyMinimumBedroomChoice && minimumBedroomChoice < 1 ) {
	errors.push( 'Overview: Lowest bedroom option must be a whole number greater than zero.' );
}

if ( hasLegacyMinimumBedroomChoice && bedroomCount && minimumBedroomChoice > bedroomCount ) {
	errors.push( 'Overview: Lowest bedroom option cannot be greater than the bedroom total.' );
}

const bedroomSelectorChoiceNames = new Set();

for ( const choice of bedroomSelectorChoices ) {
	const normalizedChoice = choice.toLowerCase();

	if ( choice.length > 120 ) {
		errors.push( `Overview: Bedroom selector option "${ choice }" must be 120 characters or fewer.` );
	}

	if ( normalizedChoice === 'select room number' ) {
		errors.push( 'Overview: Bedroom selector options should only include real selectable options; the website adds its own placeholder automatically.' );
	}

	if ( bedroomSelectorChoiceNames.has( normalizedChoice ) ) {
		errors.push( `Overview: Bedroom selector option "${ choice }" is duplicated.` );
	}

	bedroomSelectorChoiceNames.add( normalizedChoice );
}

if ( ! bedroomSelectorEnabled && bedroomSelectorChoices.length > 0 ) {
	warnings.push( 'Bedroom selector options were supplied but Show bedroom selector is No; the selector will remain hidden.' );
}

if ( overview.coordinates && ! coordinates ) {
	errors.push( 'Overview: Coordinates must use "latitude, longitude", for example 13.16879926789435, -59.63036875681415.' );
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

	for ( const key of [ 'area', 'room_name', 'bed_configuration' ] ) {
		if ( ! bedroom[ key ] ) {
			errors.push( `Bedrooms row ${ row }: "${ key }" is required.` );
		}
	}

	if ( ! bedroom.description ) {
		warnings.push( `Bedrooms row ${ row }: room description is blank.` );
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

const validAmenities = [];

if ( amenities.length === 0 ) {
	errors.push( 'Amenities: Add at least one amenity row.' );
}

for ( const [ index, amenity ] of amenities.entries() ) {
	const row = rowNumber( amenity, index + 6 );

	if ( ! amenity.group || ! amenity.item ) {
		warnings.push( `Amenities row ${ row }: group and amenity are required; row was skipped.` );
		continue;
	}

	validAmenities.push( amenity );
}

if ( amenities.length > 0 && validAmenities.length === 0 ) {
	errors.push( 'Amenities: Add at least one valid amenity row.' );
}

for ( const [ index, staffMember ] of staff.entries() ) {
	const row = rowNumber( staffMember, index + 6 );
	const missingFields = [ 'role', 'arrangement', 'description' ].filter(
		( key ) => ! staffMember[ key ]
	);

	if ( missingFields.length > 0 ) {
		warnings.push( `Staff row ${ row }: ${ missingFields.join( ', ' ) } blank.` );
	}
}

if ( rates.length === 0 ) {
	errors.push( 'Rates: Add at least one seasonal rate row.' );
}

for ( const [ index, rate ] of rates.entries() ) {
	const row = rowNumber( rate, index + 6 );
	const rateAmount = currencyValue( rate.nightly_rate_usd );
	const minimumNights = integerValue( rate.minimum_nights );
	const startDate = dateValue( rate.start_date );
	const endDate = dateValue( rate.end_date );

	if ( ! rate.start_date || ! rate.end_date ) {
		errors.push( `Rates row ${ row }: start date and end date are required.` );
	}

	if ( rate.start_date && ! startDate ) {
		errors.push( `Rates row ${ row }: start date must use a date such as 10 Jan 2026.` );
	}

	if ( rate.end_date && ! endDate ) {
		errors.push( `Rates row ${ row }: end date must use a date such as 14 Dec 2026.` );
	}

	if ( rateAmount === null || rateAmount <= 0 ) {
		errors.push( `Rates row ${ row }: nightly rate must be greater than zero.` );
	}

	if ( ! minimumNights || minimumNights < 1 ) {
		errors.push( `Rates row ${ row }: minimum nights must be a whole number.` );
	}

	if (
		startDate &&
		endDate &&
		startDate > endDate
	) {
		warnings.push( `Rates row ${ row }: start date is after end date; review the date range.` );
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

if ( ! overview.google_maps_link && ! coordinates ) {
	warnings.push( 'No Google Maps link or coordinates were supplied; schema coordinates must be added later.' );
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

const storyPayload = compactValues( story );

for ( const index of [ 1, 2, 3, 4, 5 ] ) {
	delete storyPayload[ `highlight_${ index }` ];
}

const overviewPayload = {
	...compactValues( overview ),
	bedrooms: bedroomCount,
	bathrooms: bathroomCount,
	sleeps: sleepsCount,
	starting_rate_usd: startingRate,
	bedroom_selector_enabled: bedroomSelectorEnabled,
	bedroom_selector_choices: bedroomSelectorChoices,
};

if ( coordinates ) {
	overviewPayload.coordinates = coordinates;
}

if ( hasLegacyMinimumBedroomChoice ) {
	overviewPayload.minimum_bedroom_choice = minimumBedroomChoice;
}

const payload = {
	schema_version: SCHEMA_VERSION,
	source_file: path.basename( inputPath ),
	overview: overviewPayload,
	story: storyPayload,
	extras: compactValues( extras ),
	bedrooms: bedrooms.map( compactValues ),
	amenities: validAmenities.map( compactValues ),
	staff: staff.map( compactValues ),
	rates: rates.map( ( rate ) => ( {
		...compactValues( rate ),
		start_date: dateValue( rate.start_date ),
		end_date: dateValue( rate.end_date ),
		nightly_rate_usd: currencyValue( rate.nightly_rate_usd ),
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
