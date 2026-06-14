#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';
import ExcelJS from 'exceljs';

const scriptDirectory = path.dirname( fileURLToPath( import.meta.url ) );
const defaultOutput = path.resolve( scriptDirectory, '../../../../../docs/villa-content-template.xlsx' );
const outputPath = path.resolve( process.argv[ 2 ] || defaultOutput );
const sampleArgument = process.argv.find( ( argument ) => argument.startsWith( '--sample=' ) );
const samplePath = sampleArgument ? path.resolve( sampleArgument.split( '=' ).slice( 1 ).join( '=' ) ) : '';
const sample = samplePath ? JSON.parse( fs.readFileSync( samplePath, 'utf8' ) ) : null;

const colors = {
	darkGreen: 'FF1E3D2F',
	gold: 'FFC4922A',
	lightGold: 'FFF5ECD7',
	ivory: 'FFFBF8F2',
	required: 'FFFFF2CC',
	optional: 'FFDDEBF7',
	fixed: 'FFE2E3E5',
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
			[ 'short_summary', 'Short summary', true, 'One or two clear sentences for search results and internal summaries.' ],
			[ 'hero_location_line', 'Hero location line', true, 'Example: Sugar Hill, St. James, Barbados.' ],
			[ 'hero_statement', 'Hero statement', true, 'A concise, distinctive statement shown over the villa hero.' ],
			[ 'bedrooms', 'Number of bedrooms', true, 'Whole number only.', 'whole' ],
			[ 'bathrooms', 'Number of bathrooms', true, 'Decimals such as 4.5 are allowed.', 'decimal' ],
			[ 'sleeps', 'Maximum guests', true, 'Whole number only.', 'whole' ],
			[ 'bedroom_selector_enabled', 'Show bedroom selector?', true, 'Choose Yes when guests can enquire for a bedroom configuration. Choose No when the villa is only offered as a fixed whole-villa stay.', 'yesNo', 'Yes' ],
			[ 'minimum_bedroom_choice', 'Lowest bedroom option to show', false, 'Leave blank for 1. If a 7-bedroom villa only rents from 5 bedrooms upward, enter 5.', 'whole' ],
			[ 'pool_summary', 'Pool summary', true, 'Example: 1 Private Pool or 2 Pools.' ],
			[ 'starting_rate_usd', 'Starting nightly rate (USD)', true, 'Enter numbers only. Do not type a currency symbol.', 'currency' ],
			[ 'display_address', 'Display address', true, 'The location wording shown to guests.' ],
			[ 'google_maps_link', 'Full Google Maps link', false, 'Use a full URL containing coordinates when possible.' ],
			[ 'postal_code', 'Postal code', false, 'Leave blank if the property does not use one.' ],
			[ 'primary_view', 'Primary view or setting', false, 'Choose the best match or type a short alternative.', 'view' ],
			[ 'card_small_label', 'Villa card label', false, 'Example: Private West Coast Villa.' ],
			[ 'card_short_description', 'Villa card location line', false, 'Example: Sugar Hill, St. James.' ],
			[ 'card_cta_label', 'Villa card button label', false, 'Default: Explore villa.' ],
		],
	},
	{
		name: 'Villa Story',
		title: 'Villa Story',
		intro: 'Write naturally in the client voice. Each answer becomes a paragraph or heading on the villa page.',
		fields: [
			[ 'story_eyebrow', 'Story section label', true, 'A short introduction such as A Private Island Retreat.' ],
			[ 'story_headline', 'Main story headline', true, 'One strong sentence that captures the villa experience.' ],
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
			[ 'natalie_title', 'Natalie section label', true, 'Default: Natalie’s Villa Perspective.' ],
			[ 'natalie_quote', 'Natalie pull quote', true, 'A concise recommendation or point of view.' ],
			[ 'natalie_paragraph_1', 'Natalie paragraph 1', true, 'Write in Natalie’s first-person voice.' ],
			[ 'natalie_paragraph_2', 'Natalie paragraph 2', false, 'Optional.' ],
			[ 'natalie_paragraph_3', 'Natalie paragraph 3', false, 'Optional.' ],
		],
	},
	{
		name: 'Page Extras',
		title: 'Enquiry, Pricing & Location Copy',
		intro: 'These fields complete the pricing, enquiry, map, and related-villas sections.',
		fields: [
			[ 'contact_eyebrow', 'Enquiry section label', true, 'Example: Plan Your Stay.' ],
			[ 'contact_heading', 'Enquiry headline', true, 'Invite guests to request availability.' ],
			[ 'contact_text', 'Enquiry supporting text', true, 'Tell guests what information to share.' ],
			[ 'whatsapp_label', 'WhatsApp button label', false, 'Default: WhatsApp Us.' ],
			[ 'pricing_heading', 'Pricing headline', true, 'Example: Seasonal Pricing.' ],
			[ 'pricing_helper', 'Pricing helper text', true, 'Explain that final rates depend on dates and stay details.' ],
			[ 'tax_note', 'Tax and service charge note', true, 'State clearly what is included or excluded.' ],
			[ 'security_deposit_note', 'Security deposit note', true, 'State whether a refundable deposit may apply.' ],
			[ 'booking_terms_1', 'Booking term 1', false, 'Optional paragraph shown under pricing.' ],
			[ 'booking_terms_2', 'Booking term 2', false, 'Optional paragraph shown under pricing.' ],
			[ 'booking_terms_3', 'Booking term 3', false, 'Optional paragraph shown under pricing.' ],
			[ 'location_description', 'Location description', true, 'Describe access to beaches, restaurants, shops, and attractions.' ],
			[ 'related_heading', 'Related villas heading', false, 'Default: Other villas in our collection.' ],
		],
	},
];

const tableSheets = [
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
			[ 'description', 'Room description', true, 'One concise sentence.' ],
		],
		widths: [ 20, 24, 18, 24, 13, 20, 34, 48 ],
	},
	{
		name: 'Amenities',
		title: 'Amenities',
		intro: 'Add one amenity per row and group similar items together. Featured items appear as emphasis chips.',
		rows: 35,
		columns: [
			[ 'group', 'Amenity group', true, 'Choose a common group or type a clear alternative.', 'amenityGroup' ],
			[ 'item', 'Amenity', true, 'Example: Private swimming pool.' ],
			[ 'featured', 'Featured?', false, 'Choose Yes for the strongest selling points.', 'yesNo' ],
		],
		widths: [ 28, 48, 16 ],
	},
	{
		name: 'Staff',
		title: 'Villa Staff',
		intro: 'Optional. Add one row per included or available staff role. Enter Not applicable in the first cell to omit this section.',
		optionalSection: true,
		rows: 12,
		columns: [
			[ 'role', 'Role', true, 'Example: Housekeeping, Chef, or Villa Manager.' ],
			[ 'arrangement', 'Arrangement', true, 'Example: Included, Weekdays, or Available at extra cost.' ],
			[ 'description', 'Description', true, 'Explain the schedule or service briefly.' ],
		],
		widths: [ 24, 28, 58 ],
	},
	{
		name: 'Rates',
		title: 'Seasonal Rates',
		intro: 'Add one row per rate period. Use YYYY-MM-DD dates and numeric USD rates without a currency symbol.',
		rows: 15,
		columns: [
			[ 'season', 'Season or rate name', true, 'Example: Summer, Winter, or Festive.' ],
			[ 'start_date', 'Start date', true, 'Use YYYY-MM-DD.', 'date' ],
			[ 'end_date', 'End date', true, 'Use YYYY-MM-DD.', 'date' ],
			[ 'nightly_rate_usd', 'Nightly rate (USD)', true, 'Numbers only.', 'currency' ],
			[ 'minimum_nights', 'Minimum nights', true, 'Whole number only.', 'whole' ],
		],
		widths: [ 26, 17, 17, 22, 18 ],
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
		intro: 'Optional. Add approved reviews only. Enter Not applicable in the first cell to omit this section.',
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
		name: 'Nearby',
		title: 'Nearby',
		intro: 'Add useful beaches, towns, restaurants, shops, or attractions and a simple travel time.',
		rows: 15,
		columns: [
			[ 'place', 'Place', true, 'Example: Holetown.' ],
			[ 'travel_time', 'Travel time', true, 'Example: 10 minutes by car.' ],
		],
		widths: [ 38, 32 ],
	},
	{
		name: 'Highlights',
		title: 'Why We Love It',
		intro: 'Add at least three concise reasons guests will love this villa.',
		rows: 12,
		columns: [
			[ 'highlight', 'Highlight', true, 'One clear selling point per row.' ],
		],
		widths: [ 76 ],
	},
	{
		name: 'Related Villas',
		title: 'Related Villas',
		intro: 'Optional. Add up to three exact names of published villas. Leave blank for automatic suggestions.',
		optionalSection: true,
		rows: 6,
		columns: [
			[ 'villa_name', 'Published villa name', true, 'The spelling must match WordPress exactly.' ],
		],
		widths: [ 48 ],
	},
];

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
	sheet.getCell( 3, 1 ).value = 'Yellow = required   |   Blue = optional   |   Grey = fixed field   |   Do not rename tabs';
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
			errorTitle: 'Positive number required',
			error: 'Enter the USD amount as a number without a currency symbol.',
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
			error: 'Enter a date using YYYY-MM-DD.',
		};
		cell.numFmt = 'yyyy-mm-dd';
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

const lookupValue = ( container, key ) => {
	if ( ! container ) {
		return '';
	}

	const value = container[ key ];
	return value === null || value === undefined ? '' : value;
};

for ( const definition of keyValueSheets ) {
	const sheet = workbook.addWorksheet( definition.name, {
		views: [ { state: 'frozen', xSplit: 2, ySplit: 4, showGridLines: false } ],
		properties: { tabColor: { argb: colors.gold } },
	});
	addTitle( sheet, definition.title, definition.intro, 4 );

	const headers = [ 'Import key', 'Question', 'Client answer', 'Guidance' ];
	sheet.getRow( 4 ).values = headers;
	sheet.getRow( 4 ).height = 28;
	sheet.getRow( 4 ).eachCell( ( cell ) => {
		cell.font = { name: 'Aptos', size: 11, bold: true, color: { argb: colors.white } };
		cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: colors.gold } };
		cell.alignment = { vertical: 'middle', horizontal: 'left' };
		cell.border = thinBorder;
	} );

	const sampleContainer = definition.name === 'Overview'
		? sample?.overview
		: ( definition.name === 'Villa Story' ? sample?.story : sample?.extras );

	definition.fields.forEach( ( [ key, label, required, guidance, validation, defaultValue = '' ], index ) => {
		const rowNumber = index + 5;
		const row = sheet.getRow( rowNumber );
		let value = sampleContainer
			? lookupValue( sampleContainer, key )
			: defaultValue;

		if ( validation === 'yesNo' && typeof value === 'boolean' ) {
			value = value ? 'Yes' : 'No';
		}
		row.values = [ key, label, value, guidance ];
		row.height = Math.max( 30, Math.min( 66, 18 + Math.ceil( Math.max( label.length, guidance.length ) / 48 ) * 14 ) );

		row.getCell( 1 ).font = { name: 'Aptos', size: 9, color: { argb: colors.muted } };
		row.getCell( 2 ).font = { name: 'Aptos', size: 11, bold: true, color: { argb: colors.text } };
		row.getCell( 4 ).font = { name: 'Aptos', size: 10, color: { argb: colors.muted } };
		row.getCell( 2 ).alignment = { vertical: 'top', wrapText: true };
		row.getCell( 4 ).alignment = { vertical: 'top', wrapText: true };
		row.getCell( 2 ).border = thinBorder;
		row.getCell( 4 ).border = thinBorder;
		styleInputCell( row.getCell( 3 ), required );
		applyValidation( row.getCell( 3 ), validation );
	} );

	sheet.getColumn( 1 ).hidden = true;
	sheet.getColumn( 2 ).width = 31;
	sheet.getColumn( 3 ).width = 62;
	sheet.getColumn( 4 ).width = 48;
	sheet.autoFilter = { from: 'B4', to: 'D4' };
	sheet.pageSetup = {
		orientation: 'landscape',
		fitToPage: true,
		fitToWidth: 1,
		fitToHeight: 0,
		paperSize: 9,
		margins: { left: 0.3, right: 0.3, top: 0.5, bottom: 0.5, header: 0.2, footer: 0.2 },
		printArea: `B1:D${ definition.fields.length + 4 }`,
	};
};

for ( const definition of tableSheets ) {
	const columnCount = definition.columns.length;
	const sheet = workbook.addWorksheet( definition.name, {
		views: [ { state: 'frozen', ySplit: 5, showGridLines: false } ],
		properties: { tabColor: { argb: colors.darkGreen } },
	});
	addTitle( sheet, definition.title, definition.intro, columnCount );

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

	const sampleKey = definition.name === 'House Rules'
		? 'rules'
		: definition.name.toLowerCase().replaceAll( ' ', '_' );
	const sampleRows = sample?.[ sampleKey ] || [];

	for ( let index = 0; index < definition.rows; index++ ) {
		const rowNumber = index + 6;
		const row = sheet.getRow( rowNumber );
		const sampleRow = sampleRows[ index ] || {};
		row.height = definition.name === 'Reviews' ? 54 : 32;

		definition.columns.forEach( ( [ key, , required, guidance, validation ], columnIndex ) => {
			const cell = row.getCell( columnIndex + 1 );
			const fixedValue = definition.fixedRows?.[ index ] || '';
			const visuallyRequired = required && ! definition.optionalSection;
			cell.value = fixedValue && key === 'rule' ? fixedValue : lookupValue( sampleRow, key );
			styleInputCell( cell, visuallyRequired, Boolean( fixedValue && key === 'rule' ) );
			cell.note = guidance;
			applyValidation( cell, validation );
		} );
	}

	sheet.autoFilter = {
		from: { row: 5, column: 1 },
		to: { row: 5, column: columnCount },
	};
	sheet.pageSetup = {
		orientation: 'landscape',
		fitToPage: true,
		fitToWidth: 1,
		fitToHeight: 0,
		paperSize: 9,
		margins: { left: 0.3, right: 0.3, top: 0.5, bottom: 0.5, header: 0.2, footer: 0.2 },
		printArea: `A1:${ sheet.getColumn( columnCount ).letter }${ definition.rows + 5 }`,
	};
};

instructions.mergeCells( 'A1:H1' );
instructions.getCell( 'A1' ).value = 'Barbados Escapes Villa Content Template';
instructions.getCell( 'A1' ).font = { name: 'Aptos Display', size: 22, bold: true, color: { argb: colors.white } };
instructions.getCell( 'A1' ).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: colors.darkGreen } };
instructions.getCell( 'A1' ).alignment = { vertical: 'middle', horizontal: 'left' };
instructions.getRow( 1 ).height = 38;

instructions.mergeCells( 'A2:H2' );
instructions.getCell( 'A2' ).value = 'A simple, client-friendly source document for creating a new villa page accurately.';
instructions.getCell( 'A2' ).font = { name: 'Aptos', size: 12, italic: true, color: { argb: colors.text } };
instructions.getCell( 'A2' ).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: colors.lightGold } };
instructions.getCell( 'A2' ).alignment = { vertical: 'middle' };
instructions.getRow( 2 ).height = 30;

const instructionSections = [
	[ 4, 'How to use this template', colors.gold ],
	[ 5, '1', 'Make a copy', 'In Google Sheets use File > Make a copy. Name it “Villa Content - Villa Name”.' ],
	[ 6, '2', 'Complete the yellow fields', 'Yellow cells are required. Blue cells are optional. Keep the wording guest-facing and final.' ],
	[ 7, '3', 'Use one item per row', 'Bedrooms, amenities, rates, reviews, highlights, and nearby places each have their own tabs.' ],
	[ 8, '4', 'Share the completed Sheet', 'The developer downloads it as Microsoft Excel (.xlsx) and runs the villa importer.' ],
	[ 10, 'Important rules', colors.gold ],
	[ 11, 'Do not rename tabs', 'The importer uses the tab names to understand the content.' ],
	[ 12, 'Do not paste formatted layouts', 'Paste plain text into answer cells. The website controls fonts, colours, and page layout.' ],
	[ 13, 'Use “Not applicable” carefully', 'For a completely optional table such as Reviews or Staff, enter Not applicable in the first cell and leave the rest of that row blank.' ],
	[ 14, 'Images are separate', 'The featured image, gallery, videos, and availability calendars are added in WordPress after import.' ],
	[ 15, 'Rates are numeric USD', 'Enter 1500, not $1,500. Dates should use YYYY-MM-DD.' ],
	[ 17, 'Colour guide', colors.gold ],
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
	[ 'A18:B18', colors.required, 'Required client answer' ],
	[ 'D18:E18', colors.optional, 'Optional client answer' ],
	[ 'G18:H18', colors.fixed, 'Fixed field - complete the answer beside it' ],
];
for ( const [ range, color, label ] of legend ) {
	instructions.mergeCells( range );
	const cell = instructions.getCell( range.split( ':' )[ 0 ] );
	cell.value = label;
	cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: color } };
	cell.font = { name: 'Aptos', size: 10, bold: true, color: { argb: colors.text } };
	cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
	cell.border = thinBorder;
}
instructions.getRow( 18 ).height = 38;

instructions.mergeCells( 'A20:H20' );
instructions.getCell( 'A20' ).value = 'Developer handoff';
instructions.getCell( 'A20' ).font = { name: 'Aptos Display', size: 14, bold: true, color: { argb: colors.white } };
instructions.getCell( 'A20' ).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: colors.gold } };
instructions.mergeCells( 'A21:H22' );
instructions.getCell( 'A21' ).value = 'Download the completed Google Sheet with File > Download > Microsoft Excel (.xlsx). First run a dry import, then create the WordPress draft. The importer never publishes automatically.';
instructions.getCell( 'A21' ).alignment = { wrapText: true, vertical: 'middle' };
instructions.getCell( 'A21' ).font = { name: 'Aptos', size: 11, color: { argb: colors.text } };
instructions.getCell( 'A21' ).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: colors.ivory } };
instructions.getCell( 'A21' ).border = thinBorder;

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
	printArea: 'A1:H22',
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
	'Resort & Community',
	'Services & Staff',
	'Technology & Entertainment',
	'Family Features',
];

const importSheet = workbook.addWorksheet( '_Import' );
importSheet.state = 'veryHidden';
importSheet.getCell( 'A1' ).value = 'schema_version';
importSheet.getCell( 'B1' ).value = '1.0';
importSheet.getCell( 'A2' ).value = 'template_name';
importSheet.getCell( 'B2' ).value = 'Barbados Escapes Villa Content';
importSheet.getCell( 'A3' ).value = 'generated_utc';
importSheet.getCell( 'B3' ).value = new Date().toISOString();

fs.mkdirSync( path.dirname( outputPath ), { recursive: true } );
await workbook.xlsx.writeFile( outputPath );
console.log( outputPath );
