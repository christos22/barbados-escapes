import { __ } from '@wordpress/i18n';
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

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

function normalizeTransitionStyle( transitionStyle ) {
	return 'slide' === transitionStyle ? 'slide' : 'fade';
}

export default function Edit( { attributes, clientId, setAttributes } ) {
	const { align, transitionStyle = 'fade' } = attributes;
	const normalizedTransitionStyle =
		normalizeTransitionStyle( transitionStyle );
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

	useEffect( () => {
		// Keep saved attributes constrained to the supported transition values.
		if ( transitionStyle !== normalizedTransitionStyle ) {
			setAttributes( {
				transitionStyle: normalizedTransitionStyle,
			} );
		}
	}, [ normalizedTransitionStyle, setAttributes, transitionStyle ] );

	const blockProps = useBlockProps( {
		className: [
			'vvm-feature-carousel',
			'vvm-feature-carousel--editor',
			`vvm-feature-carousel--transition-${ normalizedTransitionStyle }`,
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
							value={ normalizedTransitionStyle }
							options={ TRANSITION_OPTIONS }
							help={
								'slide' === normalizedTransitionStyle
									? __(
											'Slide moves the carousel track while the text panel stays fully visible.',
											'gutenberg-lab-blocks'
									  )
									: __(
											'Fade crossfades the imagery while the text panel stays fixed.',
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
							{ 'slide' === normalizedTransitionStyle
								? __(
										'Transition style: Slide. The track moves between slides while the text panel stays stable.',
										'gutenberg-lab-blocks'
								  )
								: __(
										'Transition style: Fade. The imagery crossfades while the text panel stays stable.',
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
