import { __ } from '@wordpress/i18n';
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';

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

const MEDIA_RATIO_OPTIONS = [
	{ label: __( 'Landscape', 'gutenberg-lab-blocks' ), value: 'landscape' },
	{ label: __( 'Widescreen', 'gutenberg-lab-blocks' ), value: 'widescreen' },
	{ label: __( 'Square', 'gutenberg-lab-blocks' ), value: 'square' },
	{ label: __( 'Portrait', 'gutenberg-lab-blocks' ), value: 'portrait' },
	{ label: __( 'Tall Portrait', 'gutenberg-lab-blocks' ), value: 'portrait-tall' },
];

function normalizeSpacingPresetSlug( spacingSlug ) {
	if ( typeof spacingSlug !== 'string' || spacingSlug === '' ) {
		return undefined;
	}

	return spacingSlug.trim().toLowerCase().replace( /^([0-9]+)([a-z])/, '$1-$2' );
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

export default function Edit( { attributes, setAttributes } ) {
	const { columns, mediaRatio, style } = attributes;
	const blockGap = resolveBlockGapPreviewValue( style?.spacing?.blockGap );

	const blockProps = useBlockProps( {
		className: [
			'vvm-card-grid',
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

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Layout', 'gutenberg-lab-blocks' ) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __( 'Columns', 'gutenberg-lab-blocks' ) }
						value={ columns }
						options={ COLUMN_OPTIONS }
						onChange={ ( value ) => setAttributes( { columns: value } ) }
					/>
					<SelectControl
						label={ __( 'Media ratio', 'gutenberg-lab-blocks' ) }
						value={ mediaRatio }
						options={ MEDIA_RATIO_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { mediaRatio: value } )
						}
						help={ __(
							'This controls the shared image shape for every card in the grid.',
							'gutenberg-lab-blocks'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				{/* Keep the parent focused on layout. Each child owns its own media
					and native content blocks so typography and button styles stay global. */}
				<div { ...innerBlocksProps } />
			</section>
		</>
	);
}
