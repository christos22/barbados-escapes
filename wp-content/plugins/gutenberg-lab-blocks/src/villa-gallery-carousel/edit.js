import { __ } from '@wordpress/i18n';
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

import {
	SliderArrowControlsPanel,
	SliderArrowPreview,
} from '../shared/slider-arrow-controls';
import './editor.scss';

const ALLOWED_BLOCKS = [ 'gutenberg-lab-blocks/villa-gallery-carousel-slide' ];

function renderCaption( title, detail ) {
	if ( ! title && ! detail ) {
		return (
			<span className="vvm-villa-gallery-carousel__caption-empty">
				{ __(
					'Active slide caption appears here.',
					'gutenberg-lab-blocks'
				) }
			</span>
		);
	}

	return (
		<>
			<span className="vvm-villa-gallery-carousel__caption-title">
				{ title || __( 'Gallery image', 'gutenberg-lab-blocks' ) }
			</span>
			{ detail ? (
				<>
					<span
						className="vvm-villa-gallery-carousel__caption-separator"
						aria-hidden="true"
					>
						{' '}
						&mdash;{' '}
					</span>
					<span className="vvm-villa-gallery-carousel__caption-detail">
						{ detail }
					</span>
				</>
			) : null }
		</>
	);
}

export default function Edit( { attributes, clientId, setAttributes } ) {
	const slideBlocks = useSelect(
		( select ) =>
			(
				select( 'core/block-editor' ).getBlock( clientId )?.innerBlocks ?? []
			).filter(
				( innerBlock ) =>
					'gutenberg-lab-blocks/villa-gallery-carousel-slide' ===
					innerBlock.name
			),
		[ clientId ]
	);

	const activeSlide = slideBlocks[ 0 ]?.attributes ?? {};
	const captionTitle = activeSlide.title?.trim() ?? '';
	const captionDetail = activeSlide.detail?.trim() ?? '';

	const blockProps = useBlockProps( {
		className: 'vvm-villa-gallery-carousel vvm-villa-gallery-carousel--editor',
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-villa-gallery-carousel__editor-track',
		},
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: [],
			templateLock: false,
			orientation: 'horizontal',
			renderAppender: InnerBlocks.ButtonBlockAppender,
		}
	);

	return (
		<>
			{ slideBlocks.length > 1 ? (
				<InspectorControls>
					<PanelBody
						title={ __( 'Carousel Settings', 'gutenberg-lab-blocks' ) }
						initialOpen={ true }
					>
						<p className="components-base-control__help">
							{ __(
								'Adjust the shared arrow placement for this villa gallery rail.',
								'gutenberg-lab-blocks'
							) }
						</p>
					</PanelBody>
					<SliderArrowControlsPanel
						attributes={ attributes }
						setAttributes={ setAttributes }
						initialOpen={ false }
					/>
				</InspectorControls>
			) : null }
			<section { ...blockProps }>
				<div className="vvm-villa-gallery-carousel__shell">
					<div className="vvm-villa-gallery-carousel__editor-viewport vvm-slider-surface">
						<SliderArrowPreview
							attributes={ attributes }
							className="vvm-villa-gallery-carousel__controls vvm-villa-gallery-carousel__editor-controls"
							buttonClassName="vvm-villa-gallery-carousel__rail-button"
							isVisible={ slideBlocks.length > 1 }
						/>
						<div { ...innerBlocksProps } />
					</div>

					<p className="vvm-villa-gallery-carousel__caption">
						{ renderCaption( captionTitle, captionDetail ) }
					</p>

					{ 0 === slideBlocks.length ? (
						<p className="vvm-villa-gallery-carousel__editor-note">
							{ __(
								'Add gallery slides only when you need them. Each slide holds its own image, label, title, and detail line.',
								'gutenberg-lab-blocks'
							) }
						</p>
					) : null }
				</div>
			</section>
		</>
	);
}
