import { __ } from '@wordpress/i18n';
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

import {
	SliderArrowControlsPanel,
	SliderArrowPreview,
} from '../shared/slider-arrow-controls';
import './editor.scss';

const ALLOWED_BLOCKS = [ 'gutenberg-lab-blocks/editorial-feature-slide' ];

const TEMPLATE = [ [ 'gutenberg-lab-blocks/editorial-feature-slide' ] ];

export default function Edit( { attributes, clientId, setAttributes } ) {
	const { align, enableSlider = false } = attributes;
	const slideCount = useSelect(
		( select ) =>
			(
				select( 'core/block-editor' ).getBlock( clientId )
					?.innerBlocks ?? []
			).filter(
				( innerBlock ) =>
					'gutenberg-lab-blocks/editorial-feature-slide' ===
					innerBlock.name
			).length,
		[ clientId ]
	);
	const willUseSlider = enableSlider && slideCount > 1;
	const blockProps = useBlockProps( {
		className: [
			'vvm-editorial-feature',
			'vvm-editorial-feature--editor',
			enableSlider
				? 'vvm-editorial-feature--slider-enabled'
				: 'vvm-editorial-feature--slider-disabled',
			willUseSlider
				? 'vvm-editorial-feature--display-slider'
				: 'vvm-editorial-feature--display-static',
			align ? '' : 'alignfull',
		]
			.filter( Boolean )
			.join( ' ' ),
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-editorial-feature__track',
		},
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: TEMPLATE,
			templateLock: false,
			orientation: willUseSlider ? 'horizontal' : 'vertical',
			renderAppender: InnerBlocks.ButtonBlockAppender,
		}
	);

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Editorial Feature', 'gutenberg-lab-blocks' ) }
					initialOpen={ true }
				>
					<ToggleControl
						label={ __( 'Enable slider', 'gutenberg-lab-blocks' ) }
						checked={ enableSlider }
						onChange={ ( value ) =>
							setAttributes( { enableSlider: value } )
						}
						help={
							willUseSlider
								? __(
										'The front end will render one editorial item at a time with arrow controls.',
										'gutenberg-lab-blocks'
								  )
								: __(
										'Add at least two editorial items before the slider controls appear on the front end.',
										'gutenberg-lab-blocks'
								  )
						}
					/>
				</PanelBody>
				{ enableSlider ? (
					<SliderArrowControlsPanel
						attributes={ attributes }
						setAttributes={ setAttributes }
						disabled={ ! willUseSlider }
						help={ __(
							'These controls are active when the block has at least two editorial items.',
							'gutenberg-lab-blocks'
						) }
					/>
				) : null }
			</InspectorControls>

			<section { ...blockProps }>
				<div className="vvm-editorial-feature__inner">
					<div
						className={
							willUseSlider
								? 'vvm-editorial-feature__carousel vvm-slider-surface'
								: 'vvm-editorial-feature__carousel'
						}
					>
						<div className="vvm-editorial-feature__viewport">
							{/* Each child owns one two-column editorial item. The parent
								only decides whether those items stack or slide. */}
							<div { ...innerBlocksProps } />
						</div>
						{ willUseSlider ? (
							<SliderArrowPreview
								attributes={ attributes }
								className="vvm-editorial-feature__controls"
								buttonClassName="vvm-editorial-feature__button"
							/>
						) : null }
					</div>
				</div>
			</section>
		</>
	);
}
