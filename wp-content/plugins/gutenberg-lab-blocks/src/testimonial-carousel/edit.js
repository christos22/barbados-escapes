import { __ } from '@wordpress/i18n';
import {
	InnerBlocks,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	RangeControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';

import {
	SliderArrowControlsPanel,
	SliderArrowPreview,
} from '../shared/slider-arrow-controls';
import './editor.scss';

const ALLOWED_BLOCKS = [ 'gutenberg-lab-blocks/testimonial-carousel-slide' ];

const TEMPLATE = [
	[ 'gutenberg-lab-blocks/testimonial-carousel-slide' ],
	[ 'gutenberg-lab-blocks/testimonial-carousel-slide' ],
];

function normalizeOpacity( value, fallback = 0 ) {
	const numericValue = Number.parseInt( value, 10 );

	if ( Number.isNaN( numericValue ) ) {
		return fallback;
	}

	return Math.max( 0, Math.min( 100, numericValue ) );
}

export default function Edit( { attributes, clientId, setAttributes } ) {
	const {
		align,
		backgroundImageId,
		backgroundImageUrl = '',
		backgroundImageAlt = '',
		backgroundImageOpacity = 35,
		overlayOpacity = 70,
	} = attributes;
	const slideCount = useSelect(
		( select ) =>
			(
				select( 'core/block-editor' ).getBlock( clientId )
					?.innerBlocks ?? []
			).filter(
				( innerBlock ) =>
					'gutenberg-lab-blocks/testimonial-carousel-slide' ===
					innerBlock.name
			).length,
		[ clientId ]
	);
	const willUseSlider = slideCount > 1;
	const blockProps = useBlockProps( {
		className: [
			'vvm-testimonial-carousel',
			'vvm-testimonial-carousel--editor',
			willUseSlider
				? 'vvm-testimonial-carousel--display-slider'
				: 'vvm-testimonial-carousel--display-static',
			backgroundImageUrl
				? 'vvm-testimonial-carousel--has-background-image'
				: '',
			align ? '' : 'alignfull',
		]
			.filter( Boolean )
			.join( ' ' ),
		style: {
			'--vvm-testimonial-image-opacity': normalizeOpacity(
				backgroundImageOpacity,
				35
			) / 100,
			'--vvm-testimonial-overlay-opacity': normalizeOpacity(
				overlayOpacity,
				70
			) / 100,
		},
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-testimonial-carousel__track',
		},
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: TEMPLATE,
			templateLock: false,
			orientation: willUseSlider ? 'horizontal' : 'vertical',
			renderAppender: InnerBlocks.ButtonBlockAppender,
		}
	);

	const setBackgroundImage = ( image ) => {
		setAttributes( {
			backgroundImageId: image?.id,
			backgroundImageUrl: image?.url ?? '',
			backgroundImageAlt: image?.alt ?? '',
		} );
	};

	const clearBackgroundImage = () => {
		setAttributes( {
			backgroundImageId: undefined,
			backgroundImageUrl: '',
			backgroundImageAlt: '',
		} );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Background Image', 'gutenberg-lab-blocks' ) }
					initialOpen={ true }
				>
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ setBackgroundImage }
							allowedTypes={ [ 'image' ] }
							value={ backgroundImageId }
							render={ ( { open } ) => (
								<div className="vvm-testimonial-carousel__media-control">
									{ backgroundImageUrl ? (
										<img
											src={ backgroundImageUrl }
											alt={ backgroundImageAlt }
										/>
									) : null }
									<Button
										variant="secondary"
										onClick={ open }
									>
										{ backgroundImageUrl
											? __(
													'Replace image',
													'gutenberg-lab-blocks'
											  )
											: __(
													'Choose image',
													'gutenberg-lab-blocks'
											  ) }
									</Button>
									{ backgroundImageUrl ? (
										<Button
											variant="link"
											isDestructive
											onClick={ clearBackgroundImage }
										>
											{ __(
												'Remove image',
												'gutenberg-lab-blocks'
											) }
										</Button>
									) : null }
								</div>
							) }
						/>
					</MediaUploadCheck>
					<RangeControl
						label={ __( 'Image opacity', 'gutenberg-lab-blocks' ) }
						value={ normalizeOpacity( backgroundImageOpacity, 35 ) }
						onChange={ ( value ) =>
							setAttributes( {
								backgroundImageOpacity: normalizeOpacity(
									value,
									35
								),
							} )
						}
						min={ 0 }
						max={ 100 }
						step={ 1 }
						help={ __(
							'0% hides the image. 100% makes the image fully opaque.',
							'gutenberg-lab-blocks'
						) }
					/>
					<RangeControl
						label={ __( 'Pale overlay opacity', 'gutenberg-lab-blocks' ) }
						value={ normalizeOpacity( overlayOpacity, 70 ) }
						onChange={ ( value ) =>
							setAttributes( {
								overlayOpacity: normalizeOpacity( value, 70 ),
							} )
						}
						min={ 0 }
						max={ 100 }
						step={ 1 }
						help={ __(
							'100% gives the faint Fairmont-style wash. 0% removes the wash.',
							'gutenberg-lab-blocks'
						) }
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Carousel', 'gutenberg-lab-blocks' ) }
					initialOpen={ false }
				>
					<p className="components-base-control__help">
						{ willUseSlider
							? __(
									'The front end will show one quote at a time.',
									'gutenberg-lab-blocks'
							  )
							: __(
									'Add a second quote before arrows appear on the front end.',
									'gutenberg-lab-blocks'
							  ) }
					</p>
				</PanelBody>
				<SliderArrowControlsPanel
					attributes={ attributes }
					setAttributes={ setAttributes }
					disabled={ ! willUseSlider }
					help={ __(
						'Arrow controls are active when the carousel has at least two quotes.',
						'gutenberg-lab-blocks'
					) }
				/>
			</InspectorControls>

			<section { ...blockProps }>
				{ backgroundImageUrl ? (
					<img
						className="vvm-testimonial-carousel__background-image"
						src={ backgroundImageUrl }
						alt=""
						aria-hidden="true"
					/>
				) : null }
				<span
					className="vvm-testimonial-carousel__overlay"
					aria-hidden="true"
				/>
				<div className="vvm-testimonial-carousel__inner">
					<div className="vvm-testimonial-carousel__carousel vvm-slider-surface">
						<div className="vvm-testimonial-carousel__viewport">
							<div { ...innerBlocksProps } />
						</div>
						{ willUseSlider ? (
							<SliderArrowPreview
								attributes={ attributes }
								className="vvm-testimonial-carousel__controls"
								buttonClassName="vvm-testimonial-carousel__button"
							/>
						) : null }
					</div>
				</div>
			</section>
		</>
	);
}
