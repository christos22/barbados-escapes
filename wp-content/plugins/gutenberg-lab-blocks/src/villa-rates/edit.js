/**
 * Editor UI for the Villa Rates Table.
 *
 * The parent only owns the column headings. Each rate row is a child block,
 * so Gutenberg's own tools handle adding, duplicating, deleting and moving
 * rows (block toolbar arrows, List View drag and drop).
 */
import { createBlock } from '@wordpress/blocks';
import {
	InspectorControls,
	RichText,
	store as blockEditorStore,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { Button, Notice, PanelBody } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __, _n, sprintf } from '@wordpress/i18n';

import { useVillaBedroomOptions } from '../shared/villa-bedroom-options';

const ROW_BLOCK = 'gutenberg-lab-blocks/villa-rate-row';
const MAX_COLUMNS = 6;
const DEFAULT_HEADERS = [ 'Season', 'Rate per night', 'Minimum stay' ];

export default function Edit( { attributes, setAttributes, clientId } ) {
	const headers =
		Array.isArray( attributes.headers ) && attributes.headers.length
			? attributes.headers
			: DEFAULT_HEADERS;
	const { isEnabled, options } = useVillaBedroomOptions();

	// The rows are this block's children in the editor store.
	const rows = useSelect(
		( select ) => select( blockEditorStore ).getBlocks( clientId ),
		[ clientId ]
	);
	const { insertBlock } = useDispatch( blockEditorStore );

	// Rows linked to an option that no longer exists are hidden from guests
	// under every option, so point them out.
	const optionKeys = new Set( options.map( ( option ) => option.key ) );
	const unlinkedCount = isEnabled
		? rows.filter(
				( row ) =>
					row.attributes.bedroomOption &&
					! optionKeys.has( row.attributes.bedroomOption )
		  ).length
		: 0;

	// One grid template shared by the heading row and every rate row (via a
	// CSS custom property), so the columns line up like a table.
	const gridTemplate = `${ isEnabled ? 'minmax(9rem, 0.8fr) ' : '' }repeat(${
		headers.length
	}, minmax(0, 1fr))`;

	const blockProps = useBlockProps( {
		className: 'vvm-villa-rates-editor',
		style: { '--vvm-villa-rates-columns': gridTemplate },
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'vvm-villa-rates-editor__rows' },
		{
			allowedBlocks: [ ROW_BLOCK ],
			orientation: 'vertical',
			template: [ [ ROW_BLOCK ] ],
			renderAppender: false,
		}
	);

	const updateHeader = ( index, value ) => {
		const nextHeaders = [ ...headers ];
		nextHeaders[ index ] = value;
		setAttributes( { headers: nextHeaders } );
	};

	const addRow = () =>
		insertBlock(
			createBlock( ROW_BLOCK, { cells: headers.map( () => '' ) } ),
			rows.length,
			clientId
		);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Columns', 'gutenberg-lab-blocks' ) }>
					<p>
						{ __(
							'Edit the headings in the table itself. Removing a column hides its cells; adding it back shows them again.',
							'gutenberg-lab-blocks'
						) }
					</p>
					<Button
						variant="secondary"
						disabled={ headers.length >= MAX_COLUMNS }
						accessibleWhenDisabled
						onClick={ () =>
							setAttributes( { headers: [ ...headers, '' ] } )
						}
					>
						{ __( 'Add column', 'gutenberg-lab-blocks' ) }
					</Button>{ ' ' }
					<Button
						variant="tertiary"
						isDestructive
						disabled={ headers.length <= 1 }
						accessibleWhenDisabled
						onClick={ () =>
							setAttributes( { headers: headers.slice( 0, -1 ) } )
						}
					>
						{ __( 'Remove last column', 'gutenberg-lab-blocks' ) }
					</Button>
				</PanelBody>
				<PanelBody
					title={ __( 'Bedroom options', 'gutenberg-lab-blocks' ) }
					initialOpen={ false }
				>
					<p>
						{ isEnabled
							? __(
									'Each row shows under the bedroom option chosen in its first column, or under every option when set to All options. Add, rename or remove options under Settings, Villa, Bedroom selector.',
									'gutenberg-lab-blocks'
							  )
							: __(
									'The bedroom selector is off for this villa, so guests see every row. Turn it on under Settings, Villa, Bedroom selector to link rows to options.',
									'gutenberg-lab-blocks'
							  ) }
					</p>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ unlinkedCount > 0 && (
					<Notice status="warning" isDismissible={ false }>
						{ sprintf(
							/* translators: %d is the number of rows. */
							_n(
								'%d row is linked to a bedroom option that no longer exists, so guests never see it. Choose an option for it, or delete it.',
								'%d rows are linked to a bedroom option that no longer exists, so guests never see them. Choose an option for each, or delete them.',
								unlinkedCount,
								'gutenberg-lab-blocks'
							),
							unlinkedCount
						) }
					</Notice>
				) }

				<div
					className="vvm-villa-rates-editor__head"
					aria-hidden="false"
				>
					{ isEnabled && (
						<div className="vvm-villa-rates-editor__heading">
							{ __( 'Bedroom option', 'gutenberg-lab-blocks' ) }
						</div>
					) }
					{ headers.map( ( header, index ) => (
						<RichText
							key={ index }
							tagName="div"
							className="vvm-villa-rates-editor__heading"
							value={ header }
							allowedFormats={ [] }
							placeholder={ __(
								'Heading',
								'gutenberg-lab-blocks'
							) }
							onChange={ ( value ) =>
								updateHeader( index, value )
							}
						/>
					) ) }
				</div>

				<div { ...innerBlocksProps } />

				<Button
					className="vvm-villa-rates-editor__add"
					variant="secondary"
					onClick={ addRow }
				>
					{ __( 'Add rate row', 'gutenberg-lab-blocks' ) }
				</Button>
			</div>
		</>
	);
}
