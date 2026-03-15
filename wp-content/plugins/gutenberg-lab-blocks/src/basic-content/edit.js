import { __ } from '@wordpress/i18n';
import {
	InnerBlocks,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	store as blockEditorStore,
	useBlockProps,
} from '@wordpress/block-editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';
import { createBlocksFromInnerBlocksTemplate } from '@wordpress/blocks';
import {
	Button,
	ColorPicker,
	Notice,
	PanelBody,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';

import './editor.scss';

const WIDTH_OPTIONS = [
	{ label: __( '100%', 'gutenberg-lab-blocks' ), value: '100_percent' },
	{ label: __( '75%', 'gutenberg-lab-blocks' ), value: '75_percent' },
	{ label: __( '50%', 'gutenberg-lab-blocks' ), value: '50_percent' },
];

const ALIGNMENT_OPTIONS = [
	{ label: __( 'Left', 'gutenberg-lab-blocks' ), value: 'left' },
	{ label: __( 'Center', 'gutenberg-lab-blocks' ), value: 'center' },
	{ label: __( 'Right', 'gutenberg-lab-blocks' ), value: 'right' },
];

const SIDEBAR_POSITION_OPTIONS = [
	{ label: __( 'Right', 'gutenberg-lab-blocks' ), value: 'right' },
	{ label: __( 'Left', 'gutenberg-lab-blocks' ), value: 'left' },
];

const SPACING_TOP_OPTIONS = [
	{ label: __( 'No Spacing', 'gutenberg-lab-blocks' ), value: 'no_spacing' },
	{ label: __( 'Extra Small', 'gutenberg-lab-blocks' ), value: 'extra_small' },
	{ label: __( 'Small', 'gutenberg-lab-blocks' ), value: 'small' },
	{ label: __( 'Medium', 'gutenberg-lab-blocks' ), value: 'medium' },
	{ label: __( 'Large', 'gutenberg-lab-blocks' ), value: 'large' },
	{ label: __( 'Extra Large', 'gutenberg-lab-blocks' ), value: 'extra_large' },
];

const SPACING_BOTTOM_OPTIONS = [
	{ label: __( 'No Spacing', 'gutenberg-lab-blocks' ), value: 'no_spacing' },
	{ label: __( 'Small', 'gutenberg-lab-blocks' ), value: 'small' },
	{ label: __( 'Medium', 'gutenberg-lab-blocks' ), value: 'medium' },
	{ label: __( 'Large', 'gutenberg-lab-blocks' ), value: 'large' },
];

const BACKGROUND_OPTIONS = [
	{ label: __( 'No Background', 'gutenberg-lab-blocks' ), value: 'no_background' },
	{ label: __( 'Color', 'gutenberg-lab-blocks' ), value: 'color' },
	{ label: __( 'Image', 'gutenberg-lab-blocks' ), value: 'image' },
];

// Keep the inner columns focused on content-oriented blocks instead of
// exposing large layout primitives inside an already structured section block.
const SMALL_ALLOWED_BLOCKS = [
	'core/heading',
	'core/post-title',
	'core/post-excerpt',
	// Singular templates still need a way to render the main post body.
	'core/post-content',
	'core/paragraph',
	'core/list',
	'core/buttons',
	'core/image',
	'core/quote',
	'core/group',
	'core/separator',
	'core/details',
	'core/spacer',
];

// We keep both regions in the block at all times so toggling the sidebar never
// destroys authored sidebar content.
const TEMPLATE = [
	[
		'core/columns',
		{
			className: 'vvm-basic-content__columns',
			isStackedOnMobile: true,
			verticalAlignment: 'top',
			// Keep the two-column scaffold fixed once inserted.
			templateLock: 'all',
		},
		[
			[
				'core/column',
				{
					className: 'vvm-basic-content__main-column',
					// Keep the scaffold fixed, but constrain this inserter to small
					// content blocks so the section stays easy to author.
					allowedBlocks: SMALL_ALLOWED_BLOCKS,
					templateLock: false,
				},
				[
					[
						'core/heading',
						{
							level: 2,
							placeholder: __( 'Add the main heading...', 'gutenberg-lab-blocks' ),
						},
					],
					[
						'core/paragraph',
						{
							placeholder: __(
								'Add the main content...',
								'gutenberg-lab-blocks'
							),
						},
					],
				],
			],
			[
				'core/column',
				{
					className: 'vvm-basic-content__sidebar-column',
					// Match the old ACF sidebar WYSIWYG with a constrained block canvas.
					allowedBlocks: SMALL_ALLOWED_BLOCKS,
					templateLock: false,
				},
				[
					[
						'core/heading',
						{
							level: 3,
							placeholder: __( 'Add the sidebar heading...', 'gutenberg-lab-blocks' ),
						},
					],
					[
						'core/paragraph',
						{
							placeholder: __(
								'Add the sidebar content...',
								'gutenberg-lab-blocks'
							),
						},
					],
				],
			],
		],
	],
];

function getBackgroundStyle( backgroundType, backgroundColor, backgroundImageUrl ) {
	if ( 'color' === backgroundType && backgroundColor ) {
		return {
			backgroundColor,
		};
	}

	if ( 'image' === backgroundType && backgroundImageUrl ) {
		return {
			backgroundImage: `url(${ backgroundImageUrl })`,
			backgroundPosition: 'center',
			backgroundRepeat: 'no-repeat',
			backgroundSize: 'cover',
		};
	}

	return {};
}

function hasMatchingAllowedBlocks( allowedBlocks = [] ) {
	return (
		allowedBlocks.length === SMALL_ALLOWED_BLOCKS.length &&
		allowedBlocks.every(
			( blockName, index ) => blockName === SMALL_ALLOWED_BLOCKS[ index ]
		)
	);
}

export default function Edit( { attributes, setAttributes, clientId } ) {
	const {
		withSidebar,
		contentWidth,
		contentAlignment,
		sidebarPosition,
		spacingTop,
		spacingBottom,
		backgroundType,
		backgroundColor,
		backgroundImageId,
		backgroundImageUrl,
		hasInitializedTemplate,
		hideSection,
	} = attributes;
	const { replaceInnerBlocks, updateBlockAttributes } = useDispatch( blockEditorStore );
	const innerBlocks = useSelect(
		( select ) => select( blockEditorStore ).getBlocks( clientId ),
		[ clientId ]
	);
	const hydrationGuardRef = useRef( false );

	useEffect( () => {
		if ( hasInitializedTemplate ) {
			return;
		}

		// Existing saved blocks can report zero inner blocks briefly while the
		// editor hydrates. Give that one pass before assuming the block is new.
		if ( 0 === innerBlocks.length && ! hydrationGuardRef.current ) {
			hydrationGuardRef.current = true;
			return;
		}

		if ( 0 === innerBlocks.length ) {
			replaceInnerBlocks(
				clientId,
				createBlocksFromInnerBlocksTemplate( TEMPLATE ),
				false
			);
			setAttributes( { hasInitializedTemplate: true } );
			return;
		}

		setAttributes( { hasInitializedTemplate: true } );
	}, [
		clientId,
		hasInitializedTemplate,
		innerBlocks.length,
		replaceInnerBlocks,
		setAttributes,
	] );

	useEffect( () => {
		const columnsBlock = innerBlocks[ 0 ];

		if ( ! columnsBlock || 'core/columns' !== columnsBlock.name ) {
			return;
		}

		// Existing block instances were created before the nested lock attributes
		// were added to the template, so we normalize them on load.
		if ( 'all' !== columnsBlock.attributes.templateLock ) {
			updateBlockAttributes( columnsBlock.clientId, {
				templateLock: 'all',
			} );
		}

		columnsBlock.innerBlocks.forEach( ( columnBlock ) => {
			if (
				'core/column' === columnBlock.name &&
				(
					false !== columnBlock.attributes.templateLock ||
					! hasMatchingAllowedBlocks( columnBlock.attributes.allowedBlocks )
				)
			) {
				updateBlockAttributes( columnBlock.clientId, {
					templateLock: false,
					allowedBlocks: SMALL_ALLOWED_BLOCKS,
				} );
			}
		} );
	}, [ innerBlocks, updateBlockAttributes ] );

	const blockProps = useBlockProps( {
		className: [
			'vvm-basic-content',
			withSidebar ? 'vvm-basic-content--with-sidebar' : 'vvm-basic-content--no-sidebar',
			`vvm-basic-content--content-width-${ contentWidth.replace( '_percent', '' ) }`,
			`vvm-basic-content--content-align-${ contentAlignment }`,
			`vvm-basic-content--sidebar-${ sidebarPosition }`,
			`vvm-basic-content--spacing-top-${ spacingTop.replace( '_', '-' ) }`,
			`vvm-basic-content--spacing-bottom-${ spacingBottom.replace( '_', '-' ) }`,
			hideSection ? 'is-hidden-section' : '',
		]
			.filter( Boolean )
			.join( ' ' ),
		style: getBackgroundStyle(
			backgroundType,
			backgroundColor,
			backgroundImageUrl
		),
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Content', 'gutenberg-lab-blocks' ) } initialOpen={ true }>
					<ToggleControl
						label={ __( 'With Sidebar', 'gutenberg-lab-blocks' ) }
						checked={ withSidebar }
						onChange={ ( value ) => setAttributes( { withSidebar: value } ) }
					/>

					{ withSidebar ? (
						<SelectControl
							label={ __( 'Sidebar Position', 'gutenberg-lab-blocks' ) }
							value={ sidebarPosition }
							options={ SIDEBAR_POSITION_OPTIONS }
							onChange={ ( value ) =>
								setAttributes( { sidebarPosition: value } )
							}
						/>
					) : (
						<>
							<SelectControl
								label={ __( 'Content Width', 'gutenberg-lab-blocks' ) }
								value={ contentWidth }
								options={ WIDTH_OPTIONS }
								onChange={ ( value ) =>
									setAttributes( { contentWidth: value } )
								}
							/>
							<SelectControl
								label={ __( 'Content Alignment', 'gutenberg-lab-blocks' ) }
								value={ contentAlignment }
								options={ ALIGNMENT_OPTIONS }
								onChange={ ( value ) =>
									setAttributes( { contentAlignment: value } )
								}
							/>
						</>
					) }
				</PanelBody>

				<PanelBody title={ __( 'Settings', 'gutenberg-lab-blocks' ) } initialOpen={ false }>
					<ToggleControl
						label={ __( 'Hide Section', 'gutenberg-lab-blocks' ) }
						checked={ hideSection }
						onChange={ ( value ) => setAttributes( { hideSection: value } ) }
					/>

					<SelectControl
						label={ __( 'Spacing Top', 'gutenberg-lab-blocks' ) }
						value={ spacingTop }
						options={ SPACING_TOP_OPTIONS }
						onChange={ ( value ) => setAttributes( { spacingTop: value } ) }
					/>

					<SelectControl
						label={ __( 'Spacing Bottom', 'gutenberg-lab-blocks' ) }
						value={ spacingBottom }
						options={ SPACING_BOTTOM_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { spacingBottom: value } )
						}
					/>

					<SelectControl
						label={ __( 'Background', 'gutenberg-lab-blocks' ) }
						value={ backgroundType }
						options={ BACKGROUND_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( {
								backgroundType: value,
								backgroundImageId:
									'image' === value ? backgroundImageId : undefined,
								backgroundImageUrl:
									'image' === value ? backgroundImageUrl : '',
							} )
						}
					/>

					{ 'color' === backgroundType && (
						<ColorPicker
							color={ backgroundColor || '#ffffff' }
							enableAlpha={ true }
							onChange={ ( value ) =>
								setAttributes( { backgroundColor: value } )
							}
						/>
					) }

					{ 'image' === backgroundType && (
						<>
							<MediaUploadCheck>
								<MediaUpload
									allowedTypes={ [ 'image' ] }
									onSelect={ ( media ) =>
										setAttributes( {
											backgroundImageId: media.id,
											backgroundImageUrl: media.url,
										} )
									}
									value={ backgroundImageId }
									render={ ( { open } ) => (
										<Button variant="secondary" onClick={ open }>
											{ backgroundImageUrl
												? __(
														'Replace background image',
														'gutenberg-lab-blocks'
												  )
												: __(
														'Select background image',
														'gutenberg-lab-blocks'
												  ) }
										</Button>
									) }
								/>
							</MediaUploadCheck>

							{ backgroundImageUrl && (
								<Button
									variant="tertiary"
									onClick={ () =>
										setAttributes( {
											backgroundImageId: undefined,
											backgroundImageUrl: '',
										} )
									}
								>
									{ __( 'Remove background image', 'gutenberg-lab-blocks' ) }
								</Button>
							) }
						</>
					) }
				</PanelBody>
			</InspectorControls>

				<section { ...blockProps }>
					{ hideSection && (
						<Notice status="warning" isDismissible={ false }>
						{ __(
							'This section is hidden on the front end.',
							'gutenberg-lab-blocks'
						) }
					</Notice>
				) }

					{/*
					 * The parent wrapper stays fully locked so authors cannot remove the
					 * outer scaffold. The nested `templateLock` values on Columns/Column
					 * decide where editing is still allowed.
					 */}
					<InnerBlocks templateLock="all" />
				</section>
		</>
	);
}
