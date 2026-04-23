import { __ } from '@wordpress/i18n';
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

import {
	SliderArrowControlsPanel,
	SliderArrowPreview,
} from '../shared/slider-arrow-controls';
import './editor.scss';

const ALLOWED_BLOCKS = [ 'gutenberg-lab-blocks/feature-carousel-slide' ];
const TRANSITION_OPTIONS = [
	{
		label: __( 'Fade', 'gutenberg-lab-blocks' ),
		value: 'fade',
	},
	{
		label: __( 'Slide', 'gutenberg-lab-blocks' ),
		value: 'slide',
	},
];

const TEMPLATE = [
	[ 'gutenberg-lab-blocks/feature-carousel-slide' ],
	[ 'gutenberg-lab-blocks/feature-carousel-slide' ],
	[ 'gutenberg-lab-blocks/feature-carousel-slide' ],
];

export default function Edit( { attributes, clientId, setAttributes } ) {
	const { align, transitionStyle = 'fade' } = attributes;
	const slideCount = useSelect(
		( select ) =>
			(
				select( 'core/block-editor' ).getBlock( clientId )?.innerBlocks ?? []
			).filter(
				( innerBlock ) =>
					'gutenberg-lab-blocks/feature-carousel-slide' ===
					innerBlock.name
			).length,
		[ clientId ]
	);

	const blockProps = useBlockProps( {
		className: [
			'vvm-feature-carousel',
			'vvm-feature-carousel--editor',
			`vvm-feature-carousel--transition-${ transitionStyle }`,
			align ? '' : 'alignfull',
		]
			.filter( Boolean )
			.join( ' ' ),
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-feature-carousel__editor-track',
		},
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: TEMPLATE,
			templateLock: false,
			orientation: 'vertical',
			renderAppender: InnerBlocks.ButtonBlockAppender,
		}
	);

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Carousel Settings', 'gutenberg-lab-blocks' ) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __( 'Transition', 'gutenberg-lab-blocks' ) }
						value={ transitionStyle }
						options={ TRANSITION_OPTIONS }
						help={
							'slide' === transitionStyle
								? __(
										'Slide animates the carousel track between items.',
										'gutenberg-lab-blocks'
								  )
								: __(
										'Fade keeps the current editorial crossfade treatment between active slides.',
										'gutenberg-lab-blocks'
								  )
						}
						onChange={ ( value ) =>
							setAttributes( {
								transitionStyle: value,
							} )
						}
					/>
				</PanelBody>
				{ slideCount > 1 ? (
					<SliderArrowControlsPanel
						attributes={ attributes }
						setAttributes={ setAttributes }
						defaultPreset="bottom-center"
						initialOpen={ false }
					/>
				) : null }
			</InspectorControls>

			<section { ...blockProps }>
				<div className="vvm-feature-carousel__editor-shell">
					<p className="vvm-feature-carousel__editor-note">
						{ 0 === slideCount
							? __(
									'Add slides here. The front end turns them into a centered editorial carousel with peeking neighboring images.',
									'gutenberg-lab-blocks'
							  )
							: __(
									'Each slide owns its own image, heading, body copy, and CTA. The PHP render callback rebuilds the final carousel shell on the front end.',
									'gutenberg-lab-blocks'
							  ) }
					</p>

					<p className="vvm-feature-carousel__editor-note">
						{ 'slide' === transitionStyle
							? __(
									'Transition style: Slide. The shared view script will animate the track when visitors move between slides.',
									'gutenberg-lab-blocks'
							  )
							: __(
									'Transition style: Fade. The shared view script keeps the current crossfade behavior between active slides.',
									'gutenberg-lab-blocks'
							  ) }
					</p>

					<div className="vvm-feature-carousel__editor-viewport vvm-slider-surface">
						<div { ...innerBlocksProps } />
						<SliderArrowPreview
							attributes={ attributes }
							className="vvm-feature-carousel__controls vvm-feature-carousel__editor-controls"
							buttonClassName="vvm-feature-carousel__button"
							defaultPreset="bottom-center"
							isVisible={ slideCount > 1 }
						/>
					</div>
				</div>
			</section>
		</>
	);
}
