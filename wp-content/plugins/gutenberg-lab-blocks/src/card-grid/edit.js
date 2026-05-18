import { __ } from '@wordpress/i18n';
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import {
	Button,
	ComboboxControl,
	PanelBody,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { decodeEntities } from '@wordpress/html-entities';
import ServerSideRender from '@wordpress/server-side-render';

import {
	SliderArrowControlsPanel,
	SliderArrowPreview,
} from '../shared/slider-arrow-controls';
import './editor.scss';

const ALLOWED_BLOCKS = [ 'gutenberg-lab-blocks/card-grid-card' ];

const TEMPLATE = [
	[ 'gutenberg-lab-blocks/card-grid-card' ],
	[ 'gutenberg-lab-blocks/card-grid-card' ],
	[ 'gutenberg-lab-blocks/card-grid-card' ],
];

const COLUMN_OPTIONS = [
	{ label: __( '2 columns', 'gutenberg-lab-blocks' ), value: '2' },
	{ label: __( '3 columns', 'gutenberg-lab-blocks' ), value: '3' },
];

const CONTENT_SOURCE_OPTIONS = [
	{ label: __( 'Manual cards', 'gutenberg-lab-blocks' ), value: 'manual' },
	{ label: __( 'Villa posts', 'gutenberg-lab-blocks' ), value: 'villas' },
];

const VILLA_PRESENTATION_OPTIONS = [
	{ label: __( 'Cinematic', 'gutenberg-lab-blocks' ), value: 'cinematic' },
	{ label: __( 'Standard', 'gutenberg-lab-blocks' ), value: 'standard' },
	{ label: __( 'Collection', 'gutenberg-lab-blocks' ), value: 'collection' },
];

const MEDIA_RATIO_OPTIONS = [
	{ label: __( 'Landscape', 'gutenberg-lab-blocks' ), value: 'landscape' },
	{ label: __( 'Widescreen', 'gutenberg-lab-blocks' ), value: 'widescreen' },
	{ label: __( 'Square', 'gutenberg-lab-blocks' ), value: 'square' },
	{ label: __( 'Portrait', 'gutenberg-lab-blocks' ), value: 'portrait' },
	{
		label: __( 'Tall Portrait', 'gutenberg-lab-blocks' ),
		value: 'portrait-tall',
	},
];

function normalizeSpacingPresetSlug( spacingSlug ) {
	if ( typeof spacingSlug !== 'string' || spacingSlug === '' ) {
		return undefined;
	}

	return spacingSlug
		.trim()
		.toLowerCase()
		.replace( /^([0-9]+)([a-z])/, '$1-$2' );
}

function resolveBlockGapPreviewValue( blockGap ) {
	if ( typeof blockGap !== 'string' || blockGap === '' ) {
		return undefined;
	}

	// Gutenberg stores preset picks as tokens like `var:preset|spacing|2xl`.
	// The actual grid reads a CSS custom property, so we resolve that token
	// into the matching preset variable for editor parity.
	if ( blockGap.startsWith( 'var:preset|spacing|' ) ) {
		const spacingSlug = normalizeSpacingPresetSlug(
			blockGap.replace( 'var:preset|spacing|', '' )
		);

		return spacingSlug
			? `var(--wp--preset--spacing--${ spacingSlug })`
			: undefined;
	}

	return blockGap;
}

function normalizeVillaIds( villaIds ) {
	if ( ! Array.isArray( villaIds ) ) {
		return [];
	}

	return villaIds.reduce( ( normalizedIds, villaId ) => {
		const parsedVillaId = Number( villaId );

		if (
			Number.isInteger( parsedVillaId ) &&
			parsedVillaId > 0 &&
			! normalizedIds.includes( parsedVillaId )
		) {
			normalizedIds.push( parsedVillaId );
		}

		return normalizedIds;
	}, [] );
}

export default function Edit( { attributes, clientId, setAttributes } ) {
	const {
		contentSource,
		selectedVillaIds = [],
		villaPresentation,
		columns,
		enableCarousel,
		mediaRatio,
		style,
	} = attributes;
	const isVillaCinematicStyle =
		'villas' === contentSource && 'cinematic' === villaPresentation;
	const presentationHelp = {
		cinematic: __(
			'Uses the premium square editorial card layout from the mock.',
			'gutenberg-lab-blocks'
		),
		standard: __(
			'Uses the standard card-grid card treatment.',
			'gutenberg-lab-blocks'
		),
		collection: __(
			'Uses the related-villa collection card with descriptor and facts meta.',
			'gutenberg-lab-blocks'
		),
	};
	const blockGap = resolveBlockGapPreviewValue( style?.spacing?.blockGap );
	const sanitizedSelectedVillaIds = normalizeVillaIds( selectedVillaIds );
	const villas = useSelect(
		( select ) =>
			select( 'core' ).getEntityRecords( 'postType', 'villa', {
				per_page: -1,
				orderby: 'title',
				order: 'asc',
				_fields: 'id,title',
			} ),
		[]
	);
	const villaOptions = ( villas ?? [] ).map( ( villa ) => ( {
		value: String( villa.id ),
		label: decodeEntities(
			villa.title?.rendered ||
				__( '(Untitled villa)', 'gutenberg-lab-blocks' )
		),
	} ) );
	const selectedVillaOptions = sanitizedSelectedVillaIds.map( ( villaId ) => {
		const selectedOption = villaOptions.find(
			( option ) => Number( option.value ) === villaId
		);

		return (
			selectedOption || {
				value: String( villaId ),
				label: `${ __(
					'Villa',
					'gutenberg-lab-blocks'
				) } #${ villaId }`,
			}
		);
	} );
	const availableVillaOptions = villaOptions.filter(
		( option ) =>
			! sanitizedSelectedVillaIds.includes( Number( option.value ) )
	);
	const manualCardCount = useSelect(
		( select ) =>
			select( 'core/block-editor' ).getBlock( clientId )?.innerBlocks
				?.length ?? 0,
		[ clientId ]
	);
	const cardCount =
		'villas' === contentSource
			? sanitizedSelectedVillaIds.length
			: manualCardCount;
	const willUseCarousel =
		! isVillaCinematicStyle &&
		enableCarousel &&
		cardCount > Number.parseInt( columns, 10 );

	const blockProps = useBlockProps( {
		className: [
			'vvm-card-grid',
			isVillaCinematicStyle ? 'alignfull' : '',
			enableCarousel ? 'vvm-card-grid--carousel-enabled' : '',
			`vvm-card-grid--source-${ contentSource }`,
			'villas' === contentSource
				? `vvm-card-grid--villa-presentation-${ villaPresentation }`
				: '',
			`vvm-card-grid--columns-${ columns }`,
			`vvm-card-grid--ratio-${ mediaRatio }`,
		].join( ' ' ),
		style: blockGap
			? {
					'--wp--style--block-gap': blockGap,
			  }
			: undefined,
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-card-grid__items',
		},
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: TEMPLATE,
			templateLock: false,
			orientation: 'horizontal',
			renderAppender: InnerBlocks.ButtonBlockAppender,
		}
	);

	const setSelectedVillaIds = ( nextVillaIds ) => {
		setAttributes( {
			selectedVillaIds: normalizeVillaIds( nextVillaIds ),
		} );
	};

	const addSelectedVilla = ( selectedVillaId ) => {
		const villaId = Number( selectedVillaId );

		if (
			! Number.isInteger( villaId ) ||
			villaId <= 0 ||
			sanitizedSelectedVillaIds.includes( villaId )
		) {
			return;
		}

		setSelectedVillaIds( [ ...sanitizedSelectedVillaIds, villaId ] );
	};

	const moveSelectedVilla = ( villaId, direction ) => {
		const currentIndex = sanitizedSelectedVillaIds.indexOf( villaId );
		const nextIndex = currentIndex + direction;

		if (
			currentIndex < 0 ||
			nextIndex < 0 ||
			nextIndex >= sanitizedSelectedVillaIds.length
		) {
			return;
		}

		const nextVillaIds = [ ...sanitizedSelectedVillaIds ];
		nextVillaIds[ currentIndex ] = sanitizedSelectedVillaIds[ nextIndex ];
		nextVillaIds[ nextIndex ] = villaId;
		setSelectedVillaIds( nextVillaIds );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Content source', 'gutenberg-lab-blocks' ) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __(
							'Populate cards from',
							'gutenberg-lab-blocks'
						) }
						value={ contentSource }
						options={ CONTENT_SOURCE_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { contentSource: value } )
						}
						help={
							'villas' === contentSource
								? __(
										'Villa mode renders only the villa posts selected below.',
										'gutenberg-lab-blocks'
								  )
								: __(
										'Manual mode keeps each card fully editable with nested Gutenberg blocks.',
										'gutenberg-lab-blocks'
								  )
						}
					/>

					{ 'villas' === contentSource ? (
						<>
							<ComboboxControl
								label={ __(
									'Selected villas',
									'gutenberg-lab-blocks'
								) }
								help={
									sanitizedSelectedVillaIds.length
										? __(
												'Selected villas render in the order shown below.',
												'gutenberg-lab-blocks'
										  )
										: __(
												'Choose the villa posts to show in this grid.',
												'gutenberg-lab-blocks'
										  )
								}
								options={ availableVillaOptions }
								value=""
								onChange={ addSelectedVilla }
							/>
							{ selectedVillaOptions.length ? (
								<div className="vvm-card-grid__selected-villas">
									<p className="vvm-card-grid__selected-villas-label">
										{ __(
											'Manual order',
											'gutenberg-lab-blocks'
										) }
									</p>
									<ol className="vvm-card-grid__selected-villas-list">
										{ selectedVillaOptions.map(
											( option, index ) => {
												const villaId = Number(
													option.value
												);

												return (
													<li
														className="vvm-card-grid__selected-villa"
														key={ option.value }
													>
														<span className="vvm-card-grid__selected-villa-title">
															{ option.label }
														</span>
														<span className="vvm-card-grid__selected-villa-actions">
															<Button
																variant="tertiary"
																disabled={
																	0 === index
																}
																onClick={ () =>
																	moveSelectedVilla(
																		villaId,
																		-1
																	)
																}
															>
																{ __(
																	'Up',
																	'gutenberg-lab-blocks'
																) }
															</Button>
															<Button
																variant="tertiary"
																disabled={
																	index ===
																	selectedVillaOptions.length -
																		1
																}
																onClick={ () =>
																	moveSelectedVilla(
																		villaId,
																		1
																	)
																}
															>
																{ __(
																	'Down',
																	'gutenberg-lab-blocks'
																) }
															</Button>
															<Button
																variant="tertiary"
																isDestructive
																onClick={ () =>
																	setSelectedVillaIds(
																		sanitizedSelectedVillaIds.filter(
																			(
																				selectedVillaId
																			) =>
																				selectedVillaId !==
																				villaId
																		)
																	)
																}
															>
																{ __(
																	'Remove',
																	'gutenberg-lab-blocks'
																) }
															</Button>
														</span>
													</li>
												);
											}
										) }
									</ol>
									<Button
										variant="link"
										isDestructive
										onClick={ () =>
											setSelectedVillaIds( [] )
										}
									>
										{ __(
											'Clear selected villas',
											'gutenberg-lab-blocks'
										) }
									</Button>
								</div>
							) : null }
							<SelectControl
								label={ __(
									'Villa presentation',
									'gutenberg-lab-blocks'
								) }
								value={ villaPresentation }
								options={ VILLA_PRESENTATION_OPTIONS }
								onChange={ ( value ) =>
									setAttributes( {
										villaPresentation: value,
									} )
								}
								help={ presentationHelp[ villaPresentation ] }
							/>
						</>
					) : null }
				</PanelBody>

				<PanelBody
					title={ __( 'Layout', 'gutenberg-lab-blocks' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Columns', 'gutenberg-lab-blocks' ) }
						value={ columns }
						options={ COLUMN_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { columns: value } )
						}
					/>
					<ToggleControl
						label={ __(
							'Enable carousel overflow',
							'gutenberg-lab-blocks'
						) }
						checked={ enableCarousel }
						onChange={ ( value ) =>
							setAttributes( { enableCarousel: value } )
						}
						disabled={ isVillaCinematicStyle }
						help={
							isVillaCinematicStyle
								? __(
										'The Villa Cinematic variation always renders as a responsive editorial grid.',
										'gutenberg-lab-blocks'
								  )
								: willUseCarousel
								? __(
										'The front end will switch to a carousel because the card count is greater than the selected columns.',
										'gutenberg-lab-blocks'
								  )
								: __(
										'When enabled, the front end stays a grid until the number of cards exceeds the selected columns.',
										'gutenberg-lab-blocks'
								  )
						}
					/>
					<SelectControl
						label={ __( 'Media ratio', 'gutenberg-lab-blocks' ) }
						value={ mediaRatio }
						options={ MEDIA_RATIO_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { mediaRatio: value } )
						}
						disabled={ isVillaCinematicStyle }
						help={
							isVillaCinematicStyle
								? __(
										'The Villa Cinematic variation locks the cards to a square format.',
										'gutenberg-lab-blocks'
								  )
								: __(
										'This controls the shared image shape for every card in the grid.',
										'gutenberg-lab-blocks'
								  )
						}
					/>
				</PanelBody>
				{ willUseCarousel ? (
					<SliderArrowControlsPanel
						attributes={ attributes }
						setAttributes={ setAttributes }
						initialOpen={ false }
					/>
				) : null }
			</InspectorControls>

			<section { ...blockProps }>
				{ 'villas' === contentSource ? (
					<div className="vvm-card-grid__dynamic-preview">
						<ServerSideRender
							block="gutenberg-lab-blocks/card-grid"
							attributes={ attributes }
						/>
					</div>
				) : (
					<div
						className={
							willUseCarousel
								? 'vvm-card-grid__carousel vvm-slider-surface'
								: undefined
						}
					>
						{ willUseCarousel ? (
							<SliderArrowPreview
								attributes={ attributes }
								className="vvm-card-grid__carousel-controls"
								buttonClassName="vvm-card-grid__carousel-button"
							/>
						) : null }
						<div
							className={
								willUseCarousel
									? 'vvm-card-grid__viewport'
									: undefined
							}
						>
							{ /* Keep the parent focused on layout. Each child owns its own
								media and native content blocks so typography and button
								styles stay global. */ }
							<div { ...innerBlocksProps } />
						</div>
					</div>
				) }
			</section>
		</>
	);
}
