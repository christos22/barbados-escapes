import { __ } from '@wordpress/i18n';
import { createBlock } from '@wordpress/blocks';
import {
	InnerBlocks,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	store as blockEditorStore,
	useBlockProps,
} from '@wordpress/block-editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import {
	Button,
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

const TEXT_ALIGNMENT_OPTIONS = [
	{ label: __( 'Left', 'gutenberg-lab-blocks' ), value: 'left' },
	{ label: __( 'Center', 'gutenberg-lab-blocks' ), value: 'center' },
	{ label: __( 'Right', 'gutenberg-lab-blocks' ), value: 'right' },
];

const SIDEBAR_POSITION_OPTIONS = [
	{ label: __( 'Right', 'gutenberg-lab-blocks' ), value: 'right' },
	{ label: __( 'Left', 'gutenberg-lab-blocks' ), value: 'left' },
];

const ACCENT_BORDER_OPTIONS = [
	{ label: __( 'None', 'gutenberg-lab-blocks' ), value: 'none' },
	{ label: __( 'Top', 'gutenberg-lab-blocks' ), value: 'top' },
	{ label: __( 'Bottom', 'gutenberg-lab-blocks' ), value: 'bottom' },
	{ label: __( 'Top and Bottom', 'gutenberg-lab-blocks' ), value: 'both' },
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
			],
			[
				'core/column',
				{
					className: 'vvm-basic-content__sidebar-column',
					// Match the old ACF sidebar WYSIWYG with a constrained block canvas.
					allowedBlocks: SMALL_ALLOWED_BLOCKS,
					templateLock: false,
				},
			],
		],
	],
];

function createMainColumnStarterBlocks() {
	return [
		createBlock( 'core/heading', {
			level: 2,
			placeholder: __( 'Add the main heading...', 'gutenberg-lab-blocks' ),
		} ),
		createBlock( 'core/paragraph', {
			placeholder: __( 'Add the main content...', 'gutenberg-lab-blocks' ),
		} ),
	];
}

function createSidebarStarterBlocks() {
	return [
		createBlock( 'core/heading', {
			level: 3,
			placeholder: __( 'Add the sidebar heading...', 'gutenberg-lab-blocks' ),
		} ),
		createBlock( 'core/paragraph', {
			placeholder: __( 'Add the sidebar content...', 'gutenberg-lab-blocks' ),
		} ),
	];
}

function getPreviewStyle( { backgroundImageUrl } ) {
	const previewStyle = {};

	// Gutenberg does not give this custom block a native background-image
	// control, so we keep that one justified custom style.
	if ( backgroundImageUrl ) {
		previewStyle.backgroundImage = `url(${ backgroundImageUrl })`;
		previewStyle.backgroundPosition = 'center';
		previewStyle.backgroundRepeat = 'no-repeat';
		previewStyle.backgroundSize = 'cover';
	}

	return previewStyle;
}

function resolveSpacingValue( value ) {
	if ( ! value || 'string' !== typeof value ) {
		return '';
	}

	// Gutenberg stores preset tokens in `var:preset|spacing|slug` form.
	if ( value.startsWith( 'var:preset|spacing|' ) ) {
		const slug = value.replace( 'var:preset|spacing|', '' );
		return `var(--wp--preset--spacing--${ slug })`;
	}

	return value;
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
		contentTextAlignment,
		sidebarPosition,
		backgroundImageId,
		backgroundImageUrl,
		style,
		hasInitializedTemplate,
		hideSection,
		fullWidth,
		accentBorder,
	} = attributes;
	const { replaceInnerBlocks, updateBlockAttributes } = useDispatch(
		blockEditorStore
	);
	const innerBlocks = useSelect(
		( select ) => select( blockEditorStore ).getBlocks( clientId ),
		[ clientId ]
	);

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

		const [ mainColumnBlock, sidebarColumnBlock ] = columnsBlock.innerBlocks;

		if ( ! mainColumnBlock || ! sidebarColumnBlock || hasInitializedTemplate ) {
			return;
		}

		// Seed starter content only for brand-new blocks. Existing authored
		// content must not be synchronized against the template subtree.
		if (
			mainColumnBlock.innerBlocks.length > 0 ||
			sidebarColumnBlock.innerBlocks.length > 0
		) {
			setAttributes( { hasInitializedTemplate: true } );
			return;
		}

		replaceInnerBlocks(
			mainColumnBlock.clientId,
			createMainColumnStarterBlocks(),
			false
		);
		replaceInnerBlocks(
			sidebarColumnBlock.clientId,
			createSidebarStarterBlocks(),
			false
		);
		setAttributes( { hasInitializedTemplate: true } );
	}, [
		hasInitializedTemplate,
		innerBlocks,
		replaceInnerBlocks,
		setAttributes,
		updateBlockAttributes,
	] );

	const blockProps = useBlockProps( {
		className: [
			'vvm-basic-content',
			fullWidth ? 'alignfull' : '',
			withSidebar ? 'vvm-basic-content--with-sidebar' : 'vvm-basic-content--no-sidebar',
			`vvm-basic-content--content-width-${ contentWidth.replace( '_percent', '' ) }`,
			`vvm-basic-content--content-align-${ contentAlignment }`,
			`vvm-basic-content--text-align-${ contentTextAlignment }`,
			`vvm-basic-content--sidebar-${ sidebarPosition }`,
			fullWidth ? 'vvm-basic-content--full-width' : '',
			'none' !== accentBorder
				? `vvm-basic-content--accent-border-${ accentBorder }`
				: '',
			hideSection ? 'is-hidden-section' : '',
		]
			.filter( Boolean )
			.join( ' ' ),
		style: {
			...getPreviewStyle( { backgroundImageUrl } ),
			...( style?.spacing?.blockGap
				? {
						'--vvm-basic-content-flow-gap': resolveSpacingValue(
							style.spacing.blockGap
						),
				  }
				: {} ),
		},
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Layout', 'gutenberg-lab-blocks' ) } initialOpen={ true }>
					<ToggleControl
						label={ __( 'With Sidebar', 'gutenberg-lab-blocks' ) }
						checked={ withSidebar }
						onChange={ ( value ) => setAttributes( { withSidebar: value } ) }
					/>

					<ToggleControl
						label={ __( 'Full Width Section', 'gutenberg-lab-blocks' ) }
						checked={ fullWidth }
						onChange={ ( value ) => setAttributes( { fullWidth: value } ) }
						help={ __(
							'Let the section background span the full page width while the content still follows your width and spacing choices.',
							'gutenberg-lab-blocks'
						) }
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
								label={ __( 'Content Area Width', 'gutenberg-lab-blocks' ) }
								value={ contentWidth }
								options={ WIDTH_OPTIONS }
								onChange={ ( value ) =>
									setAttributes( { contentWidth: value } )
								}
							/>
							<SelectControl
								label={ __( 'Content Position', 'gutenberg-lab-blocks' ) }
								value={ contentAlignment }
								options={ ALIGNMENT_OPTIONS }
								onChange={ ( value ) =>
									setAttributes( { contentAlignment: value } )
								}
							/>
						</>
					) }

					<SelectControl
						label={ __( 'Text Alignment', 'gutenberg-lab-blocks' ) }
						value={ contentTextAlignment }
						options={ TEXT_ALIGNMENT_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { contentTextAlignment: value } )
						}
					/>

					<SelectControl
						label={ __( 'Accent Border', 'gutenberg-lab-blocks' ) }
						value={ accentBorder }
						options={ ACCENT_BORDER_OPTIONS }
						onChange={ ( value ) => setAttributes( { accentBorder: value } ) }
						help={ __(
							'Adds an 8px gold rule at the top, bottom, or both edges of the section.',
							'gutenberg-lab-blocks'
						) }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Visibility', 'gutenberg-lab-blocks' ) } initialOpen={ false }>
					<ToggleControl
						label={ __( 'Hide Section', 'gutenberg-lab-blocks' ) }
						checked={ hideSection }
						onChange={ ( value ) => setAttributes( { hideSection: value } ) }
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Background', 'gutenberg-lab-blocks' ) }
					initialOpen={ false }
				>
					<p>
						{ __(
							'Use the Styles tab for spacing and background color. This panel only manages an optional section background image.',
							'gutenberg-lab-blocks'
						) }
					</p>

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
				<InnerBlocks template={ TEMPLATE } templateLock="all" />
			</section>
		</>
	);
}
