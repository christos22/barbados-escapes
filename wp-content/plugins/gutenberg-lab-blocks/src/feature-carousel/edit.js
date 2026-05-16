import { __ } from '@wordpress/i18n';
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { Button, PanelBody, RangeControl, SelectControl } from '@wordpress/components';
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

const TEXT_MODE_OPTIONS = [
	{
		label: __( 'Text changes per slide', 'gutenberg-lab-blocks' ),
		value: 'per-slide',
	},
	{
		label: __( 'One static text panel', 'gutenberg-lab-blocks' ),
		value: 'static',
	},
];

const ACCENT_BORDER_OPTIONS = [
	{ label: __( 'None', 'gutenberg-lab-blocks' ), value: 'none' },
	{ label: __( 'Top', 'gutenberg-lab-blocks' ), value: 'top' },
	{ label: __( 'Bottom', 'gutenberg-lab-blocks' ), value: 'bottom' },
	{ label: __( 'Top and Bottom', 'gutenberg-lab-blocks' ), value: 'both' },
];

const PANEL_BACKGROUND_OPTIONS = [
	{ label: __( 'Default', 'gutenberg-lab-blocks' ), value: '' },
	{ label: __( 'White', 'gutenberg-lab-blocks' ), value: 'white' },
	{ label: __( 'Light Gold', 'gutenberg-lab-blocks' ), value: 'light-gold' },
	{ label: __( 'Ivory', 'gutenberg-lab-blocks' ), value: 'ivory' },
	{ label: __( 'Dark Green', 'gutenberg-lab-blocks' ), value: 'dark-green' },
];

const PANEL_BACKGROUND_VALUES = {
	white: 'var(--wp--preset--color--white, #fff)',
	'light-gold': 'var(--wp--preset--color--light-gold, #f5ecd7)',
	ivory: 'var(--wp--preset--color--ivory, #fbf8ef)',
	'dark-green': 'var(--wp--preset--color--dark-green, #1e3d2f)',
};

const TEMPLATE = [
	[ 'gutenberg-lab-blocks/feature-carousel-slide' ],
	[ 'gutenberg-lab-blocks/feature-carousel-slide' ],
	[ 'gutenberg-lab-blocks/feature-carousel-slide' ],
];

function normalizeTransitionStyle( transitionStyle ) {
	return 'fade' === transitionStyle ? 'fade' : 'slide';
}

function normalizeTextMode( textMode ) {
	return 'static' === textMode ? 'static' : 'per-slide';
}

function getPanelStyle( {
	panelBackground,
	panelPaddingBlock,
	panelPaddingInline,
} ) {
	return Object.fromEntries(
		Object.entries( {
			'--vvm-feature-carousel-panel-surface':
				PANEL_BACKGROUND_VALUES[ panelBackground ],
			'--vvm-feature-carousel-panel-padding-block':
				Number.isFinite( panelPaddingBlock )
					? `${ panelPaddingBlock }rem`
					: undefined,
			'--vvm-feature-carousel-panel-padding-inline':
				Number.isFinite( panelPaddingInline )
					? `${ panelPaddingInline }rem`
					: undefined,
		} ).filter( ( [ , value ] ) => value )
	);
}

export default function Edit( { attributes, clientId, setAttributes } ) {
	const {
		accentBorder = 'none',
		align,
		panelBackground = '',
		panelPaddingBlock,
		panelPaddingInline,
		textMode = 'per-slide',
		transitionStyle = 'slide',
	} = attributes;
	const normalizedTransitionStyle =
		normalizeTransitionStyle( transitionStyle );
	const normalizedTextMode = normalizeTextMode( textMode );
	const slideCount = useSelect(
		( select ) =>
			(
				select( 'core/block-editor' ).getBlock( clientId )
					?.innerBlocks ?? []
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
		if ( textMode !== normalizedTextMode ) {
			setAttributes( {
				textMode: normalizedTextMode,
			} );
		}
	}, [
		normalizedTextMode,
		normalizedTransitionStyle,
		setAttributes,
		textMode,
		transitionStyle,
	] );

	const blockProps = useBlockProps( {
		className: [
			'vvm-feature-carousel',
			'vvm-feature-carousel--editor',
			`vvm-feature-carousel--transition-${ normalizedTransitionStyle }`,
			`vvm-feature-carousel--text-${ normalizedTextMode }`,
			// Match the frontend render class so the editor shows the accent rule.
			'none' !== accentBorder
				? `vvm-feature-carousel--accent-border-${ accentBorder }`
				: '',
			align ? '' : 'alignfull',
		]
			.filter( Boolean )
			.join( ' ' ),
		style: getPanelStyle( {
			panelBackground,
			panelPaddingBlock,
			panelPaddingInline,
		} ),
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
										'Slide moves the image rail while the text panel stays fixed and swaps content.',
										'gutenberg-lab-blocks'
								  )
								: __(
										'Fade crossfades the image rail while the text panel stays fixed and swaps content.',
										'gutenberg-lab-blocks'
								  )
						}
						onChange={ ( value ) =>
							setAttributes( {
								transitionStyle: value,
							} )
						}
					/>
					<SelectControl
						label={ __( 'Text mode', 'gutenberg-lab-blocks' ) }
						value={ normalizedTextMode }
						options={ TEXT_MODE_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( {
								textMode: value,
							} )
						}
						help={
							'static' === normalizedTextMode
								? __(
										'Only the first slide text is rendered; all slides can keep changing images.',
										'gutenberg-lab-blocks'
								  )
								: __(
										'Each slide renders its own image and matching text panel.',
										'gutenberg-lab-blocks'
								  )
						}
					/>
					<SelectControl
						label={ __( 'Accent Border', 'gutenberg-lab-blocks' ) }
						value={ accentBorder }
						options={ ACCENT_BORDER_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( {
								accentBorder: value,
							} )
						}
						help={ __(
							'Adds an 8px gold rule at the top, bottom, or both edges of the carousel.',
							'gutenberg-lab-blocks'
						) }
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Text Panel', 'gutenberg-lab-blocks' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Panel background', 'gutenberg-lab-blocks' ) }
						value={ panelBackground }
						options={ PANEL_BACKGROUND_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( {
								panelBackground: value,
							} )
						}
					/>
					<RangeControl
						label={ __( 'Vertical padding', 'gutenberg-lab-blocks' ) }
						value={ panelPaddingBlock }
						onChange={ ( value ) =>
							setAttributes( {
								panelPaddingBlock: value ?? undefined,
							} )
						}
						min={ 1 }
						max={ 8 }
						step={ 0.25 }
						allowReset
					/>
					<RangeControl
						label={ __( 'Horizontal padding', 'gutenberg-lab-blocks' ) }
						value={ panelPaddingInline }
						onChange={ ( value ) =>
							setAttributes( {
								panelPaddingInline: value ?? undefined,
							} )
						}
						min={ 1 }
						max={ 8 }
						step={ 0.25 }
						allowReset
					/>
					<Button
						variant="secondary"
						onClick={ () =>
							setAttributes( {
								panelBackground: '',
								panelPaddingBlock: undefined,
								panelPaddingInline: undefined,
							} )
						}
					>
						{ __( 'Reset panel appearance', 'gutenberg-lab-blocks' ) }
					</Button>
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
									'Add slides here. The front end turns them into a media slider with one fixed copy panel.',
									'gutenberg-lab-blocks'
							  )
							: __(
									'Each slide owns its image, heading, body copy, and CTA. PHP renders images into the rail and copy into the fixed panel.',
									'gutenberg-lab-blocks'
							  ) }
					</p>

					<p className="vvm-feature-carousel__editor-note">
						{ 'static' === normalizedTextMode
							? __(
									'Static text mode: edit the shared text on the first slide. Additional slides contribute images only.',
									'gutenberg-lab-blocks'
							  )
							: __(
									'Per-slide text mode: each slide image has its own matching text panel.',
									'gutenberg-lab-blocks'
							  ) }
					</p>

					<p className="vvm-feature-carousel__editor-note">
						{ 'slide' === normalizedTransitionStyle
							? __(
									'Transition style: Slide. Images move while the text panel stays in place.',
									'gutenberg-lab-blocks'
							  )
							: __(
									'Transition style: Fade. Images crossfade while the text panel stays in place.',
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
