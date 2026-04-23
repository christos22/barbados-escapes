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

const ALLOWED_BLOCKS = [ 'gutenberg-lab-blocks/two-up-carousel-slide' ];

const TEMPLATE = [
	[ 'gutenberg-lab-blocks/two-up-carousel-slide' ],
	[ 'gutenberg-lab-blocks/two-up-carousel-slide' ],
	[ 'gutenberg-lab-blocks/two-up-carousel-slide' ],
];

export default function Edit( { attributes, clientId, setAttributes } ) {
	const { align } = attributes;
	const slideCount = useSelect(
		( select ) =>
			(
				select( 'core/block-editor' ).getBlock( clientId )?.innerBlocks ?? []
			).filter(
				( innerBlock ) =>
					'gutenberg-lab-blocks/two-up-carousel-slide' ===
					innerBlock.name
			).length,
		[ clientId ]
	);

	const blockProps = useBlockProps( {
		className: [
			'vvm-two-up-carousel',
			'vvm-two-up-carousel--editor',
			align ? '' : 'alignfull',
		]
			.filter( Boolean )
			.join( ' ' ),
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-two-up-carousel__editor-track',
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
			{ slideCount > 1 ? (
				<InspectorControls>
					<PanelBody
						title={ __( 'Carousel Settings', 'gutenberg-lab-blocks' ) }
						initialOpen={ true }
					>
						<p className="components-base-control__help">
							{ __(
								'Adjust the shared arrow placement for this two-up carousel instance.',
								'gutenberg-lab-blocks'
							) }
						</p>
					</PanelBody>
					<SliderArrowControlsPanel
						attributes={ attributes }
						setAttributes={ setAttributes }
						defaultPreset="bottom-center"
						initialOpen={ false }
					/>
				</InspectorControls>
			) : null }
			<section { ...blockProps }>
				<div className="vvm-two-up-carousel__editor-shell">
					<p className="vvm-two-up-carousel__editor-note">
						{ 0 === slideCount
							? __(
									'Add custom slide cards here. The front end shows two cards at a time and lets the neighboring cards peek in from the sides.',
									'gutenberg-lab-blocks'
							  )
							: __(
									'Each child slide owns its own image and optional copy while PHP rebuilds the shared two-card rail on the front end.',
									'gutenberg-lab-blocks'
							  ) }
					</p>

					<div className="vvm-two-up-carousel__editor-stage-shell vvm-slider-surface">
						<div className="vvm-two-up-carousel__editor-viewport">
							<div { ...innerBlocksProps } />
						</div>
						<div className="vvm-two-up-carousel__editor-controls-shell">
							<SliderArrowPreview
								attributes={ attributes }
								className="vvm-two-up-carousel__controls"
								buttonClassName="vvm-two-up-carousel__button"
								defaultPreset="bottom-center"
								isVisible={ slideCount > 1 }
							/>
						</div>
					</div>
				</div>
			</section>
		</>
	);
}
