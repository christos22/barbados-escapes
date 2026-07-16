#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';
import ExcelJS from 'exceljs';

const scriptDirectory = path.dirname( fileURLToPath( import.meta.url ) );
const defaultOutput = path.resolve( scriptDirectory, '../../../../../villa-content-template.xlsx' );
const positionalArguments = process.argv.slice( 2 ).filter( ( argument ) => ! argument.startsWith( '--' ) );
const outputPath = path.resolve( positionalArguments[ 0 ] || defaultOutput );
const sampleArgument = process.argv.find( ( argument ) => argument.startsWith( '--sample=' ) );
const samplePath = sampleArgument ? path.resolve( sampleArgument.split( '=' ).slice( 1 ).join( '=' ) ) : '';
const exampleArgument = process.argv.find( ( argument ) => argument.startsWith( '--example=' ) );
const defaultExamplePath = path.resolve( scriptDirectory, 'fixtures/monkey-hill-example.json' );
const examplePath = process.argv.includes( '--no-example' )
	? ''
	: (
		exampleArgument
			? path.resolve( exampleArgument.split( '=' ).slice( 1 ).join( '=' ) )
			: ( fs.existsSync( defaultExamplePath ) ? defaultExamplePath : '' )
	);
const sample = samplePath ? JSON.parse( fs.readFileSync( samplePath, 'utf8' ) ) : null;
const example = examplePath ? JSON.parse( fs.readFileSync( examplePath, 'utf8' ) ) : null;

const colors = {
	darkGreen: 'FF1E3D2F',
	gold: 'FFC4922A',
	lightGold: 'FFF5ECD7',
	ivory: 'FFFBF8F2',
	required: 'FFFFF2CC',
	optional: 'FFDDEBF7',
	comments: 'FFEAF4E6',
	fixed: 'FFE2E3E5',
	exclude: 'FFFF0000',
	white: 'FFFFFFFF',
	text: 'FF24332B',
	muted: 'FF67736D',
	border: 'FFD9D5CB',
};

const thinBorder = {
	top: { style: 'thin', color: { argb: colors.border } },
	left: { style: 'thin', color: { argb: colors.border } },
	bottom: { style: 'thin', color: { argb: colors.border } },
	right: { style: 'thin', color: { argb: colors.border } },
};

const keyValueSheets = [
	{
		name: 'Overview',
		title: 'Villa Overview',
		intro: 'Start with the core facts guests will see in the hero, villa card, specifications, and location section.',
		fields: [
			[ 'villa_name', 'Villa name', true, 'Use the public-facing villa name.' ],
			[ 'property_area', 'Area or neighbourhood', true, 'Example: Sugar Hill, Royal Westmoreland, or Gibbs.' ],
			[ 'parish', 'Villa location', true, 'Choose an existing website location from the dropdown.', 'parish' ],
			[ 'hero_location_line', 'Hero location line', true, 'Example: Sugar Hill, St. James, Barbados.' ],
			[ 'hero_statement', 'Hero statement', true, 'A concise, distinctive statement shown over the villa hero.' ],
			[ 'bedrooms', 'Number of bedrooms', true, 'Whole number only.', 'whole' ],
			[ 'bathrooms', 'Number of bathrooms', true, 'Decimals such as 4.5 are allowed.', 'decimal' ],
			[ 'sleeps', 'Maximum guests', true, 'Whole number only.', 'whole' ],
			[ 'bedroom_selector_enabled', 'Show bedroom selector?', true, 'Choose Yes when guests can enquire for a bedroom drop-down configuration. Choose No when the villa is only offered as a fixed whole-villa stay.', 'yesNo', 'No' ],
			[ 'bedroom_selector_choices', 'Bedroom selector options', false, 'Optional. Enter each visitor-facing option separated by commas. Example from With Bedroom Selection: 2 Bedrooms, 1 Bedroom. Only include real selectable options; the website adds its own placeholder automatically.' ],
				[ 'pool_summary', 'Pool summary', true, 'Example: 1 Private Pool or 2 Pools.' ],
				[ 'starting_rate_usd', 'Starting nightly rate (USD)', true, 'Include the $ symbol, for example $2,000. USD only.', 'currency' ],
				[ 'display_address', 'Display address', true, 'The location wording shown to guests.' ],
				[ 'map_address', 'Exact map address or plus code', false, 'Optional. Use when Google Maps needs a more precise address than the guest-facing display address.' ],
				[ 'google_maps_link', 'Full Google Maps link', false, 'Use a full URL containing coordinates when possible.' ],
				[ 'coordinates', 'Coordinates', false, 'Optional but useful. Paste latitude, longitude, for example 13.16879926789435, -59.63036875681415. Use this when the Google Maps link is shortened.' ],
				[ 'ical_link', 'iCal link', false, 'Paste one or more full .ics/iCal calendar feed URLs from Airbnb, Vrbo, or the owner calendar. Separate multiple URLs with a new line or note.' ],
				[ 'postal_code', 'Postal code', false, 'Leave blank if the property does not use one.' ],
				[ 'card_short_description', 'Villa map card location line', false, 'Example: Sugar Hill Resort, St. James.' ],
		],
	},
	{
		name: 'Villa Story',
		title: 'Villa Story',
		intro: 'Please include all content pertaining to the villa story and main attributes. Each answer becomes a paragraph or heading on the villa page.',
		fields: [
			[ 'story_eyebrow', 'Story section label', true, 'A short introduction such as A Private Island Retreat.' ],
			[ 'story_headline', 'Main story headline', true, 'One strong short sentence that captures the villa experience.' ],
			[ 'intro_paragraph_1', 'Introduction paragraph 1', true, 'Lead with the villa experience and strongest qualities.' ],
			[ 'intro_paragraph_2', 'Introduction paragraph 2', false, 'Optional supporting paragraph.' ],
			[ 'intro_paragraph_3', 'Introduction paragraph 3', false, 'Optional supporting paragraph.' ],
			[ 'expanded_paragraph_1', 'Full description paragraph 1', true, 'Adds detail behind the Read More section.' ],
			[ 'expanded_paragraph_2', 'Full description paragraph 2', false, 'Optional.' ],
			[ 'expanded_paragraph_3', 'Full description paragraph 3', false, 'Optional.' ],
			[ 'expanded_paragraph_4', 'Full description paragraph 4', false, 'Optional.' ],
			[ 'expanded_paragraph_5', 'Full description paragraph 5', false, 'Optional.' ],
			[ 'expanded_paragraph_6', 'Full description paragraph 6', false, 'Optional.' ],
			[ 'why_love_headline', 'Why we love it headline', true, 'A short editorial summary above the highlights list.' ],
			[ 'highlight_1', 'Highlight 1', true, 'A concise reason guests will love this villa.' ],
			[ 'highlight_2', 'Highlight 2', true, 'A concise reason guests will love this villa.' ],
			[ 'highlight_3', 'Highlight 3', true, 'A concise reason guests will love this villa.' ],
			[ 'highlight_4', 'Highlight 4', false, 'Optional.' ],
			[ 'highlight_5', 'Highlight 5', false, 'Optional.' ],
			[ 'natalie_quote', 'Natalie pull quote', true, 'A concise recommendation or point of view.' ],
			[ 'natalie_paragraph_1', 'Natalie paragraph 1', true, 'Write in Natalie’s first-person voice.' ],
			[ 'natalie_paragraph_2', 'Natalie paragraph 2', false, 'Optional.' ],
			[ 'natalie_paragraph_3', 'Natalie paragraph 3', false, 'Optional.' ],
		],
	},
	{
		name: 'Pricing & Enquiry',
		title: 'Enquiry, Pricing & Location Copy',
		intro: 'These fields complete the pricing, enquiry, map, and related-villas sections.',
		fields: [
			[ 'contact_eyebrow', 'Enquiry section label', true, 'Example: Plan Your Stay.' ],
			[ 'tax_note', 'Tax and service charge note', true, 'State clearly what is included or excluded.' ],
			[ 'security_deposit_note', 'Security deposit note', true, 'State whether a refundable deposit may apply.' ],
			[ 'booking_terms_1', 'Booking term 1', false, 'Part of collapsible paragraph shown under pricing.' ],
			[ 'booking_terms_2', 'Booking term 2', false, 'Part of collapsible paragraph shown under pricing.' ],
			[ 'booking_terms_3', 'Booking term 3', false, 'Part of collapsible paragraph shown under pricing.' ],
		],
	},
];

const tableSheets = [
	{
		name: 'Nearby',
		title: 'Nearby',
		intro: 'Add useful beaches, towns, restaurants, shops, or attractions and a simple travel time. Maximum 6 places.',
		rows: 6,
		columns: [
			[ 'place', 'Place', true, 'Example: Holetown.' ],
			[ 'travel_time', 'Travel time', true, 'Example: 10 mins. The importer sorts these from shortest to longest.' ],
		],
		widths: [ 38, 32 ],
	},
	{
			name: 'Staff',
			title: 'Villa Staff',
			intro: 'Optional. Add one row per included or available staff role. Enter Not applicable in the first cell to omit this section. Leave details blank when unknown; the importer flags them for review and keeps the public page tidy.',
			optionalSection: true,
			rows: 12,
			columns: [
				[ 'role', 'Role', true, 'Example: Housekeeping, Chef, or Villa Manager.' ],
				[ 'arrangement', 'Arrangement', false, 'Example: Included 6 days per week or Fees apply. Leave blank if not confirmed.' ],
				[ 'description', 'Description', false, 'Explain the schedule or service briefly. Leave blank if not confirmed.' ],
			],
			widths: [ 24, 28, 58 ],
		},
	{
		name: 'Bedrooms',
		title: 'Bedrooms',
		intro: 'Add one row per bedroom. The number of completed rows must match the bedroom total on Overview.',
		rows: 15,
		columns: [
			[ 'area', 'Floor or area', true, 'Example: Ground Floor.' ],
			[ 'room_name', 'Room name', true, 'Each room name must be unique.' ],
			[ 'room_label', 'Short label', false, 'Example: Bedroom One.' ],
			[ 'bed_configuration', 'Bed configuration', true, 'Example: King bed or Twin beds.' ],
			[ 'ensuite', 'Ensuite?', false, 'Choose Yes or No.', 'yesNo' ],
				[ 'views', 'View', false, 'Example: Ocean view.' ],
				[ 'features', 'Other features', false, 'Separate multiple items with commas.' ],
				[ 'description', 'Room description', false, 'Optional. One concise sentence when available; leave blank if details are not confirmed yet.' ],
			],
			widths: [ 20, 24, 18, 24, 13, 20, 34, 48 ],
		},
	{
		name: 'Amenities',
		title: 'Amenities',
		intro: 'Add one amenity per row. Use Inside the Villa or Outdoor Living; the importer always renders these as two sections.',
		rows: 35,
		columns: [
			[ 'group', 'Amenity group', true, 'Choose Inside the Villa or Outdoor Living. Older custom groups are folded into the nearest section.', 'amenityGroup' ],
			[ 'item', 'Amenity', true, 'Example: Private swimming pool.' ],
		],
		widths: [ 28, 58 ],
	},
	{
		name: 'Rates',
		title: 'Seasonal Rates',
			intro: 'Add one row per rate period. Use DD MMM YYYY dates, for example 10 Jan 2026, and USD rates with the $ symbol.',
			rows: 15,
			columns: [
				[ 'rate_label', 'Rate label', false, 'Optional. Example: 3 Bedrooms, 4 Bedrooms, Festive, or Winter.' ],
				[ 'start_date', 'Start date', true, 'Use DD MMM YYYY, for example 10 Jan 2026.', 'date' ],
				[ 'end_date', 'End date', true, 'Use DD MMM YYYY, for example 14 Dec 2026.', 'date' ],
				[ 'nightly_rate_usd', 'Nightly rate (USD)', true, 'Include the $ symbol, for example $2,000. USD only.', 'currency' ],
				[ 'minimum_nights', 'Minimum nights', true, 'Whole number only.', 'whole' ],
			],
			widths: [ 24, 17, 17, 22, 18 ],
		},
	{
		name: 'House Rules',
		title: 'House Rules',
		intro: 'Complete all six standard rules. Add extra rules below them when needed.',
		rows: 10,
		columns: [
			[ 'rule', 'Rule', true, 'The first six rule names are fixed.' ],
			[ 'details', 'Details', true, 'Write the guest-facing rule clearly.' ],
		],
		widths: [ 24, 68 ],
		fixedRows: [ 'Check-in', 'Check-out', 'Minimum stay', 'Children', 'Pets', 'Smoking' ],
	},
	{
		name: 'Reviews',
		title: 'Guest Reviews',
		intro: 'Optional. Add approved reviews only. Enter Not applicable in the first cell to omit this section. Add up to 10 reviews.',
		optionalSection: true,
		rows: 10,
		columns: [
			[ 'title', 'Review title', true, 'A short pull quote or heading.' ],
			[ 'review', 'Review', true, 'Use the approved guest wording.' ],
			[ 'guest_name', 'Guest name', true, 'Use the approved display name.' ],
		],
		widths: [ 30, 70, 24 ],
	},
	{
		name: 'Related Villas',
		title: 'Related Villas',
		intro: 'Add three exact names of published villas.',
		rows: 3,
		columns: [
			[ 'villa_name', 'Published villa name', true, 'The spelling must match WordPress exactly.' ],
		],
		widths: [ 48 ],
	},
];

const bedroomCopyDefinition = {
	name: 'Bedroom Copy',
	title: 'Bedroom Copy',
	intro: 'Add the sentences shown above the bedroom cards. Individual room descriptions still live on the Bedrooms sheet.',
	fields: [
		[ 'layout_heading', 'Bedroom layout heading', false, 'The large sentence under BEDROOM LAYOUT, above the bedroom gallery.' ],
		[ 'layout_description', 'Bedroom layout description', false, 'Short paragraph below the bedroom layout heading. Keep it guest-facing and concise.' ],
	],
	areaRows: 8,
	areaColumns: [
		[ 'area', 'Floor or area', true, 'Must match a Floor or area used on the Bedrooms sheet, for example Ground Floor.' ],
		[ 'display_title', 'Display title', false, 'Optional. Use this when the card title should differ from the tab label, for example Main House.' ],
		[ 'description', 'Description', false, 'The sentence shown on the floor/area intro card.' ],
	],
	areaExamples: [
		{
			area: 'Ground Floor',
			display_title: 'Ground Floor',
			description: 'Three rooms placed for easy access to terraces, gardens, and the villa’s main living spaces.',
		},
		{
			area: 'First Floor',
			display_title: 'First Floor',
			description: 'Three elevated rooms arranged for privacy, views, and easy access across the main house.',
		},
		{
			area: 'Private Guest Cottage',
			display_title: 'Private Guest Cottage',
			description: 'Two additional bedrooms create a flexible cottage layout for extended family or guests seeking extra privacy.',
		},
	],
};

const workbook = new ExcelJS.Workbook();
workbook.creator = 'Barbados Escapes';
workbook.company = 'Verse and Vision';
workbook.subject = 'Client-friendly villa content import template';
workbook.title = 'Barbados Escapes Villa Content Template';
workbook.description = 'Complete in Excel or Google Sheets, then export as XLSX for the villa importer.';
workbook.created = new Date();
workbook.modified = new Date();
workbook.calcProperties.fullCalcOnLoad = true;

const instructions = workbook.addWorksheet( 'Instructions', {
	views: [ { state: 'frozen', ySplit: 2, showGridLines: false } ],
	properties: { tabColor: { argb: colors.gold } },
});

const addTitle = ( sheet, title, intro, endColumn ) => {
	sheet.mergeCells( 1, 1, 1, endColumn );
	sheet.getCell( 1, 1 ).value = title;
	sheet.getCell( 1, 1 ).font = { name: 'Aptos Display', size: 20, bold: true, color: { argb: colors.white } };
	sheet.getCell( 1, 1 ).alignment = { vertical: 'middle', horizontal: 'left' };
	sheet.getCell( 1, 1 ).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: colors.darkGreen } };
	sheet.getRow( 1 ).height = 34;

	sheet.mergeCells( 2, 1, 2, endColumn );
	sheet.getCell( 2, 1 ).value = intro;
	sheet.getCell( 2, 1 ).font = { name: 'Aptos', size: 11, color: { argb: colors.text } };
	sheet.getCell( 2, 1 ).alignment = { wrapText: true, vertical: 'middle' };
	sheet.getCell( 2, 1 ).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: colors.lightGold } };
	sheet.getRow( 2 ).height = 36;

	sheet.mergeCells( 3, 1, 3, endColumn );
	sheet.getCell( 3, 1 ).value = 'Yellow = required   |   Blue = optional   |   Grey = fixed field   |   Red = skipped by import   |   Do not rename tabs';
	sheet.getCell( 3, 1 ).font = { name: 'Aptos', size: 10, italic: true, color: { argb: colors.muted } };
	sheet.getCell( 3, 1 ).alignment = { vertical: 'middle' };
	sheet.getRow( 3 ).height = 24;
};

const applyValidation = ( cell, validation ) => {
	if ( ! validation ) {
		return;
	}

	const listValidations = {
		parish: '=Lists!$A$2:$A$5',
		view: '=Lists!$B$2:$B$6',
		yesNo: '=Lists!$C$2:$C$3',
		amenityGroup: '=Lists!$D$2:$D$7',
	};

	if ( listValidations[ validation ] ) {
		cell.dataValidation = {
			type: 'list',
			allowBlank: true,
			formulae: [ listValidations[ validation ] ],
			showErrorMessage: true,
			errorTitle: 'Choose or type a clear value',
			error: 'Select a suggested value or enter a clear alternative where permitted.',
		};
		return;
	}

	if ( validation === 'whole' ) {
		cell.dataValidation = {
			type: 'whole',
			operator: 'between',
			allowBlank: true,
			formulae: [ 1, 50 ],
			showErrorMessage: true,
			errorTitle: 'Whole number required',
			error: 'Enter a whole number between 1 and 50.',
		};
	}

	if ( validation === 'decimal' ) {
		cell.dataValidation = {
			type: 'decimal',
			operator: 'between',
			allowBlank: true,
			formulae: [ 0.5, 50 ],
			showErrorMessage: true,
			errorTitle: 'Number required',
			error: 'Enter a number between 0.5 and 50.',
		};
	}

	if ( validation === 'currency' ) {
		cell.dataValidation = {
			type: 'decimal',
			operator: 'greaterThan',
			allowBlank: true,
			formulae: [ 0 ],
			showErrorMessage: true,
			errorTitle: 'Positive USD amount required',
			error: 'Enter a positive USD amount. The $ symbol is accepted.',
		};
		cell.numFmt = '$#,##0';
	}

	if ( validation === 'date' ) {
		cell.dataValidation = {
			type: 'date',
			operator: 'between',
			allowBlank: true,
			formulae: [ new Date( '2020-01-01T00:00:00Z' ), new Date( '2100-12-31T00:00:00Z' ) ],
			showErrorMessage: true,
			errorTitle: 'Date required',
			error: 'Enter a date such as 10 Jan 2026.',
		};
		cell.numFmt = 'd mmm yyyy';
	}
};

const styleInputCell = ( cell, required, fixed = false ) => {
	cell.font = { name: 'Aptos', size: 11, color: { argb: colors.text } };
	cell.alignment = { vertical: 'top', wrapText: true };
	cell.fill = {
		type: 'pattern',
		pattern: 'solid',
		fgColor: { argb: fixed ? colors.fixed : ( required ? colors.required : colors.optional ) },
	};
	cell.border = thinBorder;
};

const styleExampleCell = ( cell ) => {
	cell.font = { name: 'Aptos', size: 10, color: { argb: colors.muted } };
	cell.alignment = { vertical: 'top', wrapText: true };
	cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: colors.ivory } };
	cell.border = thinBorder;
};

const styleCommentCell = ( cell ) => {
	cell.font = { name: 'Aptos', size: 10, color: { argb: colors.text } };
	cell.alignment = { vertical: 'top', wrapText: true };
	cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: colors.comments } };
	cell.border = thinBorder;
};

const applyDisplayFormat = ( cell, validation ) => {
	if ( validation === 'currency' ) {
		cell.numFmt = '$#,##0';
	}

	if ( validation === 'date' ) {
		cell.numFmt = 'd mmm yyyy';
	}
};

const lookupValue = ( container, key ) => {
	if ( ! container ) {
		return '';
	}

	const value = container[ key ];
	return value === null || value === undefined ? '' : value;
};

const formatDisplayDate = ( value ) => {
	const match = String( value || '' ).match( /^(\d{4})-(\d{2})-(\d{2})$/ );

	if ( ! match ) {
		return value;
	}

	const monthNames = [
		'Jan',
		'Feb',
		'Mar',
		'Apr',
		'May',
		'Jun',
		'Jul',
		'Aug',
		'Sep',
		'Oct',
		'Nov',
		'Dec',
	];
	const monthIndex = Number( match[ 2 ] ) - 1;

	if ( monthIndex < 0 || monthIndex >= monthNames.length ) {
		return value;
	}

	return `${ Number( match[ 3 ] ) } ${ monthNames[ monthIndex ] } ${ match[ 1 ] }`;
};

const formatDisplayCurrency = ( value ) => {
	const normalized = String( value || '' )
		.replace( /\bUSD\b/gi, '' )
		.replace( /US\$/gi, '' )
		.replace( /[$,\s]/g, '' )
		.trim();

	if ( normalized === '' ) {
		return value;
	}

	const number = Number( normalized );
	return Number.isFinite( number ) ? `$${ number.toLocaleString( 'en-US' ) }` : value;
};

const storyContainer = ( source ) => {
	if ( ! source ) {
		return null;
	}

	const story = { ...( source.story || {} ) };

	( source.highlights || [] ).forEach( ( highlight, index ) => {
		story[ `highlight_${ index + 1 }` ] = highlight?.highlight || '';
	} );

	return story;
};

const keyValueContainer = ( definition, source ) => {
	if ( ! source ) {
		return null;
	}

	if ( definition.name === 'Overview' ) {
		return source.overview;
	}

	if ( definition.name === 'Villa Story' ) {
		return storyContainer( source );
	}

	return source.extras;
};

const displayValue = ( container, key, validation ) => {
	let value = lookupValue( container, key );

	if ( Array.isArray( value ) ) {
		value = value
			.map( ( item ) => ( typeof item === 'object' && item !== null ? item.label : item ) )
			.filter( ( item ) => item !== null && item !== undefined && String( item ).trim() !== '' )
			.join( ', ' );
	}

	if ( validation === 'yesNo' && typeof value === 'boolean' ) {
		value = value ? 'Yes' : 'No';
	}

	if ( validation === 'date' ) {
		value = formatDisplayDate( value );
	}

	if ( validation === 'currency' ) {
		value = formatDisplayCurrency( value );
	}

	return value;
};

const bedroomCopyContainer = ( source ) => {
	if ( ! source ) {
		return null;
	}

	return source.bedroom_copy || null;
};

const addBedroomCopySheet = () => {
	const definition = bedroomCopyDefinition;
	const sheet = workbook.addWorksheet( definition.name, {
		views: [ { state: 'frozen', ySplit: 11, showGridLines: false } ],
		properties: { tabColor: { argb: colors.darkGreen } },
	});
	const sampleCopy = bedroomCopyContainer( sample );
	const exampleCopy = bedroomCopyContainer( example ) || {
		layout_heading: 'Eight beautifully appointed rooms across the main house and private cottage.',
		layout_description: 'Six en-suite rooms sit within the main residence, while two additional bedrooms in the private guest cottage create a more flexible and beautifully balanced layout for families, friends, and extended stays.',
		areas: definition.areaExamples,
	};
	const areaExampleStartColumn = 5;
	const endColumn = areaExampleStartColumn + definition.areaColumns.length - 1;

	addTitle( sheet, definition.title, definition.intro, endColumn );

	const keyValueHeader = [ 'Question', 'Client answer', 'Monkey Hill example', 'Guidance', 'Comments' ];
	sheet.getRow( 4 ).values = keyValueHeader;
	sheet.getRow( 4 ).height = 28;
	sheet.getRow( 4 ).eachCell( ( cell ) => {
		cell.font = { name: 'Aptos', size: 11, bold: true, color: { argb: colors.white } };
		cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: colors.gold } };
		cell.alignment = { vertical: 'middle', horizontal: 'left' };
		cell.border = thinBorder;
	} );

	definition.fields.forEach( ( [ key, label, required, guidance ], index ) => {
		const rowNumber = index + 5;
		const row = sheet.getRow( rowNumber );
		row.height = 46;
		row.getCell( 1 ).value = label;
		row.getCell( 2 ).value = lookupValue( sampleCopy, key );
		row.getCell( 3 ).value = lookupValue( exampleCopy, key );
		row.getCell( 4 ).value = guidance;
		row.getCell( 5 ).value = '';
		row.getCell( 9 ).value = key;

		row.getCell( 1 ).font = { name: 'Aptos', size: 11, bold: true, color: { argb: colors.text } };
		row.getCell( 4 ).font = { name: 'Aptos', size: 10, color: { argb: colors.muted } };
		row.getCell( 1 ).alignment = { vertical: 'top', wrapText: true };
		row.getCell( 4 ).alignment = { vertical: 'top', wrapText: true };
		row.getCell( 1 ).border = thinBorder;
		row.getCell( 4 ).border = thinBorder;
		styleInputCell( row.getCell( 2 ), required );
		styleExampleCell( row.getCell( 3 ) );
		row.getCell( 3 ).note = 'Example only. Type the new villa answer in the Client answer column.';
		styleCommentCell( row.getCell( 5 ) );
		row.getCell( 5 ).note = 'Optional client note. This is ignored by the importer.';
	} );

	sheet.mergeCells( 'A8:H8' );
	sheet.getCell( 'A8' ).value = 'Floor / area intro cards';
	sheet.getCell( 'A8' ).font = { name: 'Aptos Display', size: 14, bold: true, color: { argb: colors.white } };
	sheet.getCell( 'A8' ).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: colors.gold } };
	sheet.getCell( 'A8' ).alignment = { vertical: 'middle', horizontal: 'left' };
	sheet.getRow( 8 ).height = 28;

	sheet.mergeCells( 'A9:H9' );
	sheet.getCell( 'A9' ).value = 'Use this table for cards such as Ground Floor / Main House. Leave blank to use the importer’s default copy.';
	sheet.getCell( 'A9' ).font = { name: 'Aptos', size: 10, italic: true, color: { argb: colors.muted } };
	sheet.getCell( 'A9' ).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: colors.ivory } };
	sheet.getCell( 'A9' ).alignment = { vertical: 'middle', wrapText: true };
	sheet.getRow( 9 ).height = 26;

	const keyRow = sheet.getRow( 10 );
	const headerRow = sheet.getRow( 11 );
	keyRow.hidden = true;
	keyRow.height = 2;
	headerRow.height = 32;

	definition.areaColumns.forEach( ( [ key, label, required, guidance ], index ) => {
		const column = index + 1;
		keyRow.getCell( column ).value = key;
		headerRow.getCell( column ).value = label;
		headerRow.getCell( column ).note = guidance;
		headerRow.getCell( column ).font = { name: 'Aptos', size: 11, bold: true, color: { argb: colors.white } };
		headerRow.getCell( column ).fill = {
			type: 'pattern',
			pattern: 'solid',
			fgColor: { argb: required ? colors.gold : colors.darkGreen },
		};
		headerRow.getCell( column ).alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
		headerRow.getCell( column ).border = thinBorder;
	} );

	const commentsHeader = headerRow.getCell( 4 );
	commentsHeader.value = 'Comments';
	commentsHeader.font = { name: 'Aptos', size: 10, bold: true, color: { argb: colors.text } };
	commentsHeader.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: colors.comments } };
	commentsHeader.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
	commentsHeader.border = thinBorder;
	commentsHeader.note = 'Optional client notes for this row. This column is ignored by the importer.';

	definition.areaColumns.forEach( ( [ , label ], index ) => {
		const column = areaExampleStartColumn + index;
		const cell = headerRow.getCell( column );
		cell.value = `Monkey Hill example: ${ label }`;
		cell.font = { name: 'Aptos', size: 10, bold: true, color: { argb: colors.text } };
		cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: colors.lightGold } };
		cell.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
		cell.border = thinBorder;
		cell.note = 'Example only. Type the new villa details in the left-hand table.';
	} );

	const sampleAreas = sampleCopy?.areas || [];
	const exampleAreas = exampleCopy?.areas || definition.areaExamples;

	for ( let index = 0; index < definition.areaRows; index++ ) {
		const rowNumber = index + 12;
		const row = sheet.getRow( rowNumber );
		const sampleArea = sampleAreas[ index ] || {};
		const exampleArea = exampleAreas[ index ] || {};
		row.height = 42;

		definition.areaColumns.forEach( ( [ key, , required, guidance ], columnIndex ) => {
			const cell = row.getCell( columnIndex + 1 );
			cell.value = lookupValue( sampleArea, key );
			styleInputCell( cell, required );
			cell.note = guidance;

			const exampleCell = row.getCell( areaExampleStartColumn + columnIndex );
			exampleCell.value = lookupValue( exampleArea, key );
			styleExampleCell( exampleCell );
		} );

		styleCommentCell( row.getCell( 4 ) );
	}

	sheet.getColumn( 1 ).width = 30;
	sheet.getColumn( 2 ).width = 54;
	sheet.getColumn( 3 ).width = 66;
	sheet.getColumn( 4 ).width = 48;
	sheet.getColumn( 5 ).width = 32;
	sheet.getColumn( 6 ).width = 30;
	sheet.getColumn( 7 ).width = 66;
	sheet.getColumn( 9 ).hidden = true;
	sheet.autoFilter = {
		from: { row: 11, column: 1 },
		to: { row: 11, column: 4 },
	};
	sheet.pageSetup = {
		orientation: 'landscape',
		fitToPage: true,
		fitToWidth: 1,
		fitToHeight: 0,
		paperSize: 9,
		margins: { left: 0.3, right: 0.3, top: 0.5, bottom: 0.5, header: 0.2, footer: 0.2 },
		printArea: 'A1:G19',
	};
};

for ( const definition of keyValueSheets ) {
	const sheet = workbook.addWorksheet( definition.name, {
		views: [ { state: 'frozen', xSplit: 2, ySplit: 4, showGridLines: false } ],
		properties: { tabColor: { argb: colors.gold } },
	});
	addTitle( sheet, definition.title, definition.intro, 6 );

	const headers = [ 'Import key', 'Question', 'Client answer', 'Monkey Hill example', 'Guidance', 'Comments' ];
	sheet.getRow( 4 ).values = headers;
	sheet.getRow( 4 ).height = 28;
	sheet.getRow( 4 ).eachCell( ( cell ) => {
		cell.font = { name: 'Aptos', size: 11, bold: true, color: { argb: colors.white } };
		cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: colors.gold } };
		cell.alignment = { vertical: 'middle', horizontal: 'left' };
		cell.border = thinBorder;
	} );

	const sampleContainer = keyValueContainer( definition, sample );
	const exampleContainer = keyValueContainer( definition, example );

	definition.fields.forEach( ( [ key, label, required, guidance, validation, defaultValue = '' ], index ) => {
		const rowNumber = index + 5;
		const row = sheet.getRow( rowNumber );
		let value = sampleContainer
			? displayValue( sampleContainer, key, validation )
			: defaultValue;

		if ( validation === 'yesNo' && typeof value === 'boolean' ) {
			value = value ? 'Yes' : 'No';
		}
		row.values = [ key, label, value, displayValue( exampleContainer, key, validation ), guidance, '' ];
		row.height = Math.max( 30, Math.min( 66, 18 + Math.ceil( Math.max( label.length, guidance.length ) / 48 ) * 14 ) );

		row.getCell( 1 ).font = { name: 'Aptos', size: 9, color: { argb: colors.muted } };
		row.getCell( 2 ).font = { name: 'Aptos', size: 11, bold: true, color: { argb: colors.text } };
		row.getCell( 5 ).font = { name: 'Aptos', size: 10, color: { argb: colors.muted } };
		row.getCell( 2 ).alignment = { vertical: 'top', wrapText: true };
		row.getCell( 5 ).alignment = { vertical: 'top', wrapText: true };
		row.getCell( 2 ).border = thinBorder;
		row.getCell( 5 ).border = thinBorder;
		styleInputCell( row.getCell( 3 ), required );
		applyValidation( row.getCell( 3 ), validation );
		styleExampleCell( row.getCell( 4 ) );
		row.getCell( 4 ).note = 'Example only. Type the new villa answer in the Client answer column.';
		applyDisplayFormat( row.getCell( 4 ), validation );
		styleCommentCell( row.getCell( 6 ) );
		row.getCell( 6 ).note = 'Optional client note. This is ignored by the importer.';
	} );

	sheet.getColumn( 1 ).hidden = true;
	sheet.getColumn( 2 ).width = 31;
	sheet.getColumn( 3 ).width = 54;
	sheet.getColumn( 4 ).width = 54;
	sheet.getColumn( 5 ).width = 48;
	sheet.getColumn( 6 ).width = 36;
	sheet.autoFilter = { from: 'B4', to: 'F4' };
	sheet.pageSetup = {
		orientation: 'landscape',
		fitToPage: true,
		fitToWidth: 1,
		fitToHeight: 0,
		paperSize: 9,
		margins: { left: 0.3, right: 0.3, top: 0.5, bottom: 0.5, header: 0.2, footer: 0.2 },
		printArea: `B1:F${ definition.fields.length + 4 }`,
	};
};

for ( const definition of tableSheets ) {
	const columnCount = definition.columns.length;
	const commentsColumn = columnCount + 1;
	const spacerColumn = columnCount + 2;
	const exampleStartColumn = columnCount + 3;
	const endColumn = exampleStartColumn + columnCount - 1;
	const sheet = workbook.addWorksheet( definition.name, {
		views: [ { state: 'frozen', ySplit: 5, showGridLines: false } ],
		properties: { tabColor: { argb: colors.darkGreen } },
	});
	addTitle( sheet, definition.title, definition.intro, endColumn );

	const keyRow = sheet.getRow( 4 );
	const headerRow = sheet.getRow( 5 );
	keyRow.hidden = true;
	keyRow.height = 2;
	headerRow.height = 30;

	definition.columns.forEach( ( [ key, label, required, guidance ], index ) => {
		const column = index + 1;
		const visuallyRequired = required && ! definition.optionalSection;
		keyRow.getCell( column ).value = key;
		headerRow.getCell( column ).value = label;
		headerRow.getCell( column ).note = guidance;
		headerRow.getCell( column ).font = { name: 'Aptos', size: 11, bold: true, color: { argb: colors.white } };
		headerRow.getCell( column ).fill = {
			type: 'pattern',
			pattern: 'solid',
			fgColor: { argb: visuallyRequired ? colors.gold : colors.darkGreen },
		};
		headerRow.getCell( column ).alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
		headerRow.getCell( column ).border = thinBorder;
		sheet.getColumn( column ).width = definition.widths[ index ];
	} );

	const commentsHeader = headerRow.getCell( commentsColumn );
	commentsHeader.value = 'Comments';
	commentsHeader.font = { name: 'Aptos', size: 10, bold: true, color: { argb: colors.text } };
	commentsHeader.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: colors.comments } };
	commentsHeader.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
	commentsHeader.border = thinBorder;
	commentsHeader.note = 'Optional client notes for this row. This column is ignored by the importer.';
	sheet.getColumn( commentsColumn ).width = 30;
	sheet.getColumn( spacerColumn ).width = 3;

	definition.columns.forEach( ( [ , label ], index ) => {
		const column = exampleStartColumn + index;
		const cell = headerRow.getCell( column );
		cell.value = `Monkey Hill example: ${ label }`;
		cell.font = { name: 'Aptos', size: 10, bold: true, color: { argb: colors.text } };
		cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: colors.lightGold } };
		cell.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
		cell.border = thinBorder;
		cell.note = 'Example only. Type the new villa details in the left-hand table.';
		sheet.getColumn( column ).width = definition.widths[ index ];
	} );

	const sampleKey = definition.name === 'House Rules'
		? 'rules'
		: definition.name.toLowerCase().replaceAll( ' ', '_' );
	const sampleRows = sample?.[ sampleKey ] || [];
	const exampleRows = example?.[ sampleKey ] || [];
	const populatedRowCount = Math.max( definition.rows, sampleRows.length, exampleRows.length );

	for ( let index = 0; index < populatedRowCount; index++ ) {
		const rowNumber = index + 6;
		const row = sheet.getRow( rowNumber );
		const sampleRow = sampleRows[ index ] || {};
		const exampleRow = exampleRows[ index ] || {};
		row.height = definition.name === 'Reviews' ? 54 : 32;

		definition.columns.forEach( ( [ key, , required, guidance, validation ], columnIndex ) => {
			const cell = row.getCell( columnIndex + 1 );
			const fixedValue = definition.fixedRows?.[ index ] || '';
			const visuallyRequired = required && ! definition.optionalSection;
			cell.value = fixedValue && key === 'rule' ? fixedValue : lookupValue( sampleRow, key );
			styleInputCell( cell, visuallyRequired, Boolean( fixedValue && key === 'rule' ) );
			cell.note = guidance;
			applyValidation( cell, validation );

			const commentCell = row.getCell( commentsColumn );
			styleCommentCell( commentCell );

			const exampleCell = row.getCell( exampleStartColumn + columnIndex );
			exampleCell.value = fixedValue && key === 'rule'
				? fixedValue
				: displayValue( exampleRow, key, validation );
			styleExampleCell( exampleCell );
			applyDisplayFormat( exampleCell, validation );
		} );
	}

	sheet.autoFilter = {
		from: { row: 5, column: 1 },
		to: { row: 5, column: commentsColumn },
	};
	sheet.pageSetup = {
		orientation: 'landscape',
		fitToPage: true,
		fitToWidth: 1,
		fitToHeight: 0,
		paperSize: 9,
		margins: { left: 0.3, right: 0.3, top: 0.5, bottom: 0.5, header: 0.2, footer: 0.2 },
		printArea: `A1:${ sheet.getColumn( endColumn ).letter }${ definition.rows + 5 }`,
	};

	if ( definition.name === 'Bedrooms' ) {
		addBedroomCopySheet();
	}
};

const additionalRequests = workbook.addWorksheet( 'Additional Requests', {
	views: [ { state: 'frozen', ySplit: 4, showGridLines: false } ],
	properties: { tabColor: { argb: colors.comments } },
});
addTitle(
	additionalRequests,
	'Additional Requests',
	'Untracked general notes for extra client requests, questions, caveats, or follow-up items. This sheet is ignored by the importer. May be subject to additional charge TBC.',
	4
);
additionalRequests.getRow( 4 ).values = [ 'Topic or section', 'Request / note', 'Priority', 'Developer response' ];
additionalRequests.getRow( 4 ).height = 30;
additionalRequests.getRow( 4 ).eachCell( ( cell ) => {
	cell.font = { name: 'Aptos', size: 11, bold: true, color: { argb: colors.white } };
	cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: colors.gold } };
	cell.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
	cell.border = thinBorder;
} );

for ( let rowNumber = 5; rowNumber <= 24; rowNumber++ ) {
	const row = additionalRequests.getRow( rowNumber );
	row.height = 36;

	for ( let column = 1; column <= 4; column++ ) {
		const cell = row.getCell( column );
		styleCommentCell( cell );
		cell.note = 'Untracked note only. This sheet is ignored by the importer.';
	}
}

additionalRequests.getColumn( 1 ).width = 28;
additionalRequests.getColumn( 2 ).width = 68;
additionalRequests.getColumn( 3 ).width = 18;
additionalRequests.getColumn( 4 ).width = 48;
additionalRequests.autoFilter = { from: 'A4', to: 'D4' };
additionalRequests.pageSetup = {
	orientation: 'landscape',
	fitToPage: true,
	fitToWidth: 1,
	fitToHeight: 0,
	paperSize: 9,
	margins: { left: 0.3, right: 0.3, top: 0.5, bottom: 0.5, header: 0.2, footer: 0.2 },
	printArea: 'A1:D24',
};

instructions.mergeCells( 'A1:H1' );
instructions.getCell( 'A1' ).value = 'Barbados Escapes Villa Content Template';
instructions.getCell( 'A1' ).font = { name: 'Aptos Display', size: 22, bold: true, color: { argb: colors.white } };
instructions.getCell( 'A1' ).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: colors.darkGreen } };
instructions.getCell( 'A1' ).alignment = { vertical: 'middle', horizontal: 'left' };
instructions.getRow( 1 ).height = 38;

instructions.mergeCells( 'A2:H2' );
instructions.getCell( 'A2' ).value = 'A simple content document for creating a new villa page accurately, with Monkey Hill shown as an example.';
instructions.getCell( 'A2' ).font = { name: 'Aptos', size: 12, italic: true, color: { argb: colors.text } };
instructions.getCell( 'A2' ).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: colors.lightGold } };
instructions.getCell( 'A2' ).alignment = { vertical: 'middle' };
instructions.getRow( 2 ).height = 30;

const instructionSections = [
	[ 4, 'How to use this template', colors.gold ],
	[ 5, '1', 'Make a copy', 'In Google Sheets use File > Make a copy. Name it “Villa Content - Villa Name”.' ],
	[ 6, '2', 'Complete the yellow fields', 'Yellow cells are required. Blue cells are optional. Keep the wording guest-facing and final.' ],
	[ 7, '3', 'Use Monkey Hill as a guide', 'The example column and example tables show where existing villa information belongs. Type the new villa details in Client answer or the left-hand tables.' ],
	[ 8, '4', 'Add notes if useful', 'Use Comments for questions, caveats, or reminders. Comments help review but are ignored by the importer.' ],
	[ 9, '5', 'Use one item per row', 'Bedrooms, amenities, rates, reviews, and nearby places each have their own tabs. Highlights are in Villa Story.' ],
	[ 10, '6', 'Share the completed Sheet', 'The developer downloads it as Microsoft Excel (.xlsx) and runs the villa importer.' ],
	[ 12, 'Important rules', colors.gold ],
	[ 13, 'Do not rename tabs', 'The importer uses the tab names to understand the content.' ],
	[ 14, 'Do not paste formatted layouts', 'Paste plain text into answer cells. The website controls fonts, colours, and page layout.' ],
	[ 15, 'Use “Not applicable” carefully', 'For a completely optional table such as Reviews or Staff, enter Not applicable in the first cell and leave the rest of that row blank.' ],
	[ 16, 'Images are separate', 'The featured image, gallery, videos, and availability calendars are added in WordPress after import.' ],
	[ 17, 'Rates are USD currency', 'Include the $ symbol, for example $1,500. Dates should use DD MMM YYYY, for example 10 Jan 2026, or follow the Monkey Hill examples.' ],
	[ 18, 'Red means do not import', 'Anything marked red in an answer/table cell is skipped by the importer and reported in the dry-run warnings.' ],
	[ 19, 'Colour guide', colors.gold ],
];

for ( const item of instructionSections ) {
	const row = item[ 0 ];

	if ( item.length === 3 && item[ 2 ].startsWith( 'FF' ) ) {
		instructions.mergeCells( row, 1, row, 8 );
		instructions.getCell( row, 1 ).value = item[ 1 ];
		instructions.getCell( row, 1 ).font = { name: 'Aptos Display', size: 14, bold: true, color: { argb: colors.white } };
		instructions.getCell( row, 1 ).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: item[ 2 ] } };
		instructions.getRow( row ).height = 27;
		continue;
	}

	if ( item.length === 4 ) {
		instructions.getCell( row, 1 ).value = item[ 1 ];
		instructions.getCell( row, 2 ).value = item[ 2 ];
		instructions.mergeCells( row, 3, row, 8 );
		instructions.getCell( row, 3 ).value = item[ 3 ];
		instructions.getCell( row, 1 ).font = { name: 'Aptos Display', size: 16, bold: true, color: { argb: colors.gold } };
		instructions.getCell( row, 2 ).font = { name: 'Aptos', size: 11, bold: true, color: { argb: colors.text } };
		instructions.getCell( row, 3 ).font = { name: 'Aptos', size: 11, color: { argb: colors.text } };
		instructions.getCell( row, 3 ).alignment = { wrapText: true, vertical: 'middle' };
		instructions.getRow( row ).height = 34;
		continue;
	}

	instructions.getCell( row, 1 ).value = item[ 1 ];
	instructions.mergeCells( row, 2, row, 8 );
	instructions.getCell( row, 2 ).value = item[ 2 ];
	instructions.getCell( row, 1 ).font = { name: 'Aptos', size: 11, bold: true, color: { argb: colors.text } };
	instructions.getCell( row, 2 ).font = { name: 'Aptos', size: 11, color: { argb: colors.text } };
	instructions.getCell( row, 2 ).alignment = { wrapText: true, vertical: 'middle' };
	instructions.getRow( row ).height = 34;
}

const legend = [
	[ 'A20:B20', colors.required, 'Required client answer' ],
	[ 'C20:D20', colors.optional, 'Optional client answer' ],
	[ 'E20:F20', colors.comments, 'Comments - ignored by import' ],
	[ 'G20:H20', colors.ivory, 'Monkey Hill example only' ],
	[ 'A21:B21', colors.fixed, 'Fixed field - complete the answer beside it' ],
	[ 'C21:D21', colors.exclude, 'Red - skipped by import' ],
];
for ( const [ range, color, label ] of legend ) {
	instructions.mergeCells( range );
	const cell = instructions.getCell( range.split( ':' )[ 0 ] );
	cell.value = label;
	cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: color } };
	cell.font = {
		name: 'Aptos',
		size: 10,
		bold: true,
		color: { argb: color === colors.exclude ? colors.white : colors.text },
	};
	cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
	cell.border = thinBorder;
}
instructions.getRow( 20 ).height = 38;
instructions.getRow( 21 ).height = 38;

instructions.mergeCells( 'A23:H23' );
instructions.getCell( 'A23' ).value = 'Developer handoff';
instructions.getCell( 'A23' ).font = { name: 'Aptos Display', size: 14, bold: true, color: { argb: colors.white } };
instructions.getCell( 'A23' ).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: colors.gold } };
instructions.mergeCells( 'A24:H25' );
instructions.getCell( 'A24' ).value = 'Download the completed Google Sheet with File > Download > Microsoft Excel (.xlsx). First run a dry import, then create the WordPress draft. The importer never publishes automatically.';
instructions.getCell( 'A24' ).alignment = { wrapText: true, vertical: 'middle' };
instructions.getCell( 'A24' ).font = { name: 'Aptos', size: 11, color: { argb: colors.text } };
instructions.getCell( 'A24' ).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: colors.ivory } };
instructions.getCell( 'A24' ).border = thinBorder;

instructions.columns = [
	{ width: 9 },
	{ width: 24 },
	{ width: 18 },
	{ width: 18 },
	{ width: 18 },
	{ width: 18 },
	{ width: 18 },
	{ width: 18 },
];
instructions.pageSetup = {
	orientation: 'landscape',
	fitToPage: true,
	fitToWidth: 1,
	fitToHeight: 1,
	paperSize: 9,
	margins: { left: 0.4, right: 0.4, top: 0.5, bottom: 0.5, header: 0.2, footer: 0.2 },
	printArea: 'A1:H25',
};

const lists = workbook.addWorksheet( 'Lists' );
lists.state = 'veryHidden';
lists.getColumn( 1 ).values = [ 'Villa locations', 'St. James', 'St. Peter', 'Mullins', 'Paynes Bay' ];
lists.getColumn( 2 ).values = [ 'Primary views', 'Ocean View', 'Oceanfront', 'Hillside Retreat', 'Garden View', 'Golf Course View' ];
lists.getColumn( 3 ).values = [ 'Yes or No', 'Yes', 'No' ];
lists.getColumn( 4 ).values = [
	'Amenity groups',
	'Inside the Villa',
	'Outdoor Living',
];

const importSheet = workbook.addWorksheet( '_Import' );
importSheet.state = 'veryHidden';
importSheet.getCell( 'A1' ).value = 'schema_version';
importSheet.getCell( 'B1' ).value = '1.0';
importSheet.getCell( 'A2' ).value = 'template_name';
importSheet.getCell( 'B2' ).value = 'Barbados Escapes Villa Content';
importSheet.getCell( 'A3' ).value = 'generated_utc';
importSheet.getCell( 'B3' ).value = new Date().toISOString();

const desiredSheetOrder = [
	'Instructions',
	'Overview',
	'Villa Story',
	'Nearby',
	'Staff',
	'Bedrooms',
	'Bedroom Copy',
	'Amenities',
	'Rates',
	'House Rules',
	'Reviews',
	'Pricing & Enquiry',
	'Related Villas',
	'Additional Requests',
	'Lists',
	'_Import',
];
const worksheetByName = new Map( workbook.worksheets.map( ( worksheet ) => [ worksheet.name, worksheet ] ) );

desiredSheetOrder.forEach( ( sheetName, index ) => {
	const worksheet = worksheetByName.get( sheetName );

	if ( worksheet ) {
		worksheet.orderNo = index + 1;
	}
} );

fs.mkdirSync( path.dirname( outputPath ), { recursive: true } );
await workbook.xlsx.writeFile( outputPath );
console.log( outputPath );
