import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';

import {
	SliderArrowControlsPanel,
	SliderArrowPreview,
} from '../shared/slider-arrow-controls';
import './editor.scss';

const HEIGHT_OPTIONS = [
	{ label: __( 'Large', 'gutenberg-lab-blocks' ), value: 'large' },
	{ label: __( 'Full screen', 'gutenberg-lab-blocks' ), value: 'full' },
];

const OVERLAY_STYLE_OPTIONS = [
	{ label: __( 'Brand green', 'gutenberg-lab-blocks' ), value: 'brand-green' },
	{
		label: __( 'Dark vignette', 'gutenberg-lab-blocks' ),
		value: 'dark-vignette',
	},
];

const TEMPLATE = [
	[
		'gutenberg-lab-blocks/villa-gallery-hero-media',
		{
			lock: {
				move: true,
				remove: true,
			},
		},
	],
	[
		'gutenberg-lab-blocks/villa-gallery-hero-content',
		{
			lock: {
				move: true,
				remove: true,
			},
		},
	],
];

function getPreviewState( heroBlock ) {
	const mediaRegion = heroBlock?.innerBlocks?.find(
		( innerBlock ) =>
			'gutenberg-lab-blocks/villa-gallery-hero-media' === innerBlock.name
	);
	const slideBlocks = ( mediaRegion?.innerBlocks ?? [] ).filter(
		( innerBlock ) =>
			'gutenberg-lab-blocks/villa-gallery-hero-slide' === innerBlock.name
	);
	const slideBlock = slideBlocks.find( ( innerBlock ) => {
		if ( 'gutenberg-lab-blocks/villa-gallery-hero-slide' !== innerBlock.name ) {
			return false;
		}

		const {
			mediaType = 'image',
			imageUrl = '',
			videoUrl = '',
			posterImageUrl = '',
		} = innerBlock.attributes ?? {};

		return 'video' === mediaType
			? Boolean( videoUrl || posterImageUrl )
			: Boolean( imageUrl );
	} );

	if ( ! slideBlock ) {
		return {
			previewMediaUrl: '',
			slideCount: slideBlocks.length,
		};
	}

	const {
		mediaType = 'image',
		imageUrl = '',
		posterImageUrl = '',
	} = slideBlock.attributes ?? {};

	return {
		previewMediaUrl:
			'video' === mediaType ? posterImageUrl || '' : imageUrl || '',
		slideCount: slideBlocks.length,
	};
}

export default function Edit( { attributes, clientId, setAttributes } ) {
	const { align, heroHeight, overlayStyle, showArrows } = attributes;
	const { previewMediaUrl, slideCount } = useSelect(
		( select ) => {
			const heroBlock = select( 'core/block-editor' ).getBlock( clientId );

			return getPreviewState( heroBlock );
		},
		[ clientId ]
	);
	const showStagePreviewArrows = showArrows && slideCount > 1;
	const showThumbPreviewArrows = slideCount > 1;

	const blockProps = useBlockProps( {
		className: [
			'vvm-villa-gallery-hero',
			`vvm-villa-gallery-hero--height-${ heroHeight }`,
			`vvm-villa-gallery-hero--overlay-${ overlayStyle }`,
			align ? '' : 'alignfull',
		]
			.filter( Boolean )
			.join( ' ' ),
		style: previewMediaUrl
			? {
					'--vvm-villa-gallery-hero-preview-image': `url(${ previewMediaUrl })`,
				}
			: undefined,
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-villa-gallery-hero__layout-editor',
		},
		{
			allowedBlocks: [
				'gutenberg-lab-blocks/villa-gallery-hero-media',
				'gutenberg-lab-blocks/villa-gallery-hero-content',
			],
			template: TEMPLATE,
			templateLock: 'all',
			renderAppender: false,
		}
	);

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Hero layout', 'gutenberg-lab-blocks' ) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __( 'Hero height', 'gutenberg-lab-blocks' ) }
						value={ heroHeight }
						options={ HEIGHT_OPTIONS }
						onChange={ ( value ) => setAttributes( { heroHeight: value } ) }
					/>
					<SelectControl
						label={ __( 'Overlay treatment', 'gutenberg-lab-blocks' ) }
						value={ overlayStyle }
						options={ OVERLAY_STYLE_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { overlayStyle: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show slider arrows', 'gutenberg-lab-blocks' ) }
						help={ __(
							'Displays the previous and next arrow controls on the front end.',
							'gutenberg-lab-blocks'
						) }
						checked={ showArrows }
						onChange={ ( value ) =>
							setAttributes( { showArrows: value } )
						}
					/>
				</PanelBody>
				{ showStagePreviewArrows ? (
					<SliderArrowControlsPanel
						attributes={ attributes }
						setAttributes={ setAttributes }
						title={ __( 'Stage arrows', 'gutenberg-lab-blocks' ) }
						initialOpen={ false }
					/>
				) : null }
				{ showThumbPreviewArrows ? (
					<SliderArrowControlsPanel
						attributes={ attributes }
						setAttributes={ setAttributes }
						title={ __( 'Thumbnail arrows', 'gutenberg-lab-blocks' ) }
						positionKey="thumbArrowPositionPreset"
						offsetXKey="thumbArrowOffsetX"
						offsetYKey="thumbArrowOffsetY"
						initialOpen={ false }
					/>
				) : null }
			</InspectorControls>

			<section { ...blockProps }>
				<div className="vvm-villa-gallery-hero__editor-stage-shell vvm-slider-surface">
					<div { ...innerBlocksProps } />
					<SliderArrowPreview
						attributes={ attributes }
						className="vvm-villa-gallery-hero__controls vvm-villa-gallery-hero__editor-controls"
						buttonClassName="vvm-villa-gallery-hero__button"
						isVisible={ showStagePreviewArrows }
					/>
				</div>
				{ showThumbPreviewArrows ? (
					<div className="vvm-villa-gallery-hero__editor-thumbs-shell vvm-slider-surface">
						<div className="vvm-villa-gallery-hero__editor-thumbs-track">
							{ Array.from( {
								length: Math.min( slideCount, 4 ),
							} ).map( ( _item, index ) => (
								<span
									key={ index }
									className="vvm-villa-gallery-hero__editor-thumb"
								/>
							) ) }
						</div>
						<SliderArrowPreview
							attributes={ attributes }
							className="vvm-villa-gallery-hero__thumb-controls"
							buttonClassName="vvm-villa-gallery-hero__thumb-rail-button"
							positionKey="thumbArrowPositionPreset"
							offsetXKey="thumbArrowOffsetX"
							offsetYKey="thumbArrowOffsetY"
						/>
					</div>
				) : null }
			</section>
		</>
	);
}
