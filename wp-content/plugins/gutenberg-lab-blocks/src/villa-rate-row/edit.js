/**
 * Editor UI for one rate row: a bedroom option dropdown and the row's cells.
 *
 * The chosen option is stored on the row itself (`bedroomOption`), so the row
 * keeps its link wherever it is moved, copied or duplicated.
 */
import { RichText, useBlockProps } from '@wordpress/block-editor';
import { SelectControl } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import {
	BEDROOM_CHOICES_META_KEY,
	useVillaBedroomOptions,
	withChoiceKeys,
} from '../shared/villa-bedroom-options';

const DEFAULT_HEADERS = [ 'Season', 'Rate per night', 'Minimum stay' ];
const CELL_FORMATS = [
	'core/bold',
	'core/italic',
	'core/link',
	'core/superscript',
];

// Placeholder text from a heading, without any markup.
const toPlainText = ( html ) =>
	String( html ?? '' )
		.replace( /<[^>]*>/g, '' )
		.trim();

export default function Edit( { attributes, setAttributes, context } ) {
	const contextHeaders = context[ 'gutenberg-lab-blocks/villaRatesHeaders' ];
	const headers =
		Array.isArray( contextHeaders ) && contextHeaders.length
			? contextHeaders
			: DEFAULT_HEADERS;

	// Pad to the column count so a newly added column shows an empty cell.
	const cells = headers.map(
		( _, index ) => attributes.cells?.[ index ] ?? ''
	);
	const key = attributes.bedroomOption || '';

	const { isEnabled, options, metaChoices } = useVillaBedroomOptions();
	const { editPost } = useDispatch( 'core/editor' );
	const isUnlinked =
		isEnabled && '' !== key && ! options.some( ( o ) => o.key === key );

	const selectOptions = [
		{ value: '', label: __( 'All options', 'gutenberg-lab-blocks' ) },
		...options.map( ( option ) => ( {
			value: option.key,
			label: option.label,
		} ) ),
	];

	if ( isUnlinked ) {
		selectOptions.push( {
			value: key,
			label: __(
				'Removed option (choose another)',
				'gutenberg-lab-blocks'
			),
		} );
	}

	const updateCell = ( index, value ) => {
		// Keep cells beyond the visible columns, in case a column comes back.
		const nextCells = [ ...( attributes.cells ?? [] ) ];

		while ( nextCells.length < cells.length ) {
			nextCells.push( '' );
		}

		nextCells[ index ] = value;
		setAttributes( { cells: nextCells } );
	};

	const updateOption = ( value ) => {
		setAttributes( { bedroomOption: value } );

		// Options saved before keys existed only have a derived key. Store the
		// keys now, so renaming the option later keeps this row linked to it.
		if (
			metaChoices.length &&
			metaChoices.some( ( choice ) => ! choice.key )
		) {
			editPost( {
				meta: {
					[ BEDROOM_CHOICES_META_KEY ]: withChoiceKeys( metaChoices ),
				},
			} );
		}
	};

	const blockProps = useBlockProps( {
		className: `vvm-villa-rate-row${ isUnlinked ? ' is-unlinked' : '' }`,
	} );

	return (
		<div { ...blockProps }>
			{ isEnabled && (
				<div className="vvm-villa-rate-row__option">
					<SelectControl
						label={ __( 'Bedroom option', 'gutenberg-lab-blocks' ) }
						hideLabelFromVision
						value={ key }
						options={ selectOptions }
						onChange={ updateOption }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					{ isUnlinked && (
						<p className="vvm-villa-rate-row__warning">
							{ __(
								'Hidden: this option was removed.',
								'gutenberg-lab-blocks'
							) }
						</p>
					) }
				</div>
			) }
			{ cells.map( ( cell, index ) => (
				<RichText
					key={ index }
					tagName="div"
					className="vvm-villa-rate-row__cell"
					value={ cell }
					allowedFormats={ CELL_FORMATS }
					placeholder={ toPlainText( headers[ index ] ) }
					aria-label={ toPlainText( headers[ index ] ) }
					onChange={ ( value ) => updateCell( index, value ) }
				/>
			) ) }
		</div>
	);
}
