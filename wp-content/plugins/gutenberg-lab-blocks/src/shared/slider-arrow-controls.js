import { __ } from '@wordpress/i18n';
import { PanelBody, RangeControl, SelectControl } from '@wordpress/components';

const PRESET_OPTIONS = [
	{ label: __( 'Centered', 'gutenberg-lab-blocks' ), value: 'center' },
	{
		label: __( 'Bottom right', 'gutenberg-lab-blocks' ),
		value: 'bottom-right',
	},
	{
		label: __( 'Bottom center', 'gutenberg-lab-blocks' ),
		value: 'bottom-center',
	},
];

const MIN_OFFSET = -64;
const MAX_OFFSET = 64;
const OFFSET_STEP = 4;

function normalizePreset( preset, defaultPreset = 'center' ) {
	return PRESET_OPTIONS.some( ( option ) => option.value === preset )
		? preset
		: defaultPreset;
}

function normalizeOffset( value ) {
	const numericValue = Number.parseInt( value, 10 );

	if ( Number.isNaN( numericValue ) ) {
		return 0;
	}

	const snappedValue = Math.round( numericValue / OFFSET_STEP ) * OFFSET_STEP;

	return Math.max( MIN_OFFSET, Math.min( MAX_OFFSET, snappedValue ) );
}

export function getSliderArrowPreviewProps(
	attributes,
	{
		className = '',
		defaultPreset = 'center',
		offsetXKey = 'arrowOffsetX',
		offsetYKey = 'arrowOffsetY',
		positionKey = 'arrowPositionPreset',
		style = {},
	} = {}
) {
	const preset = normalizePreset( attributes?.[ positionKey ], defaultPreset );

	return {
		className: [
			'vvm-slider-controls',
			`vvm-slider-controls--preset-${ preset }`,
			className,
		]
			.filter( Boolean )
			.join( ' ' ),
		style: {
			'--vvm-slider-controls-offset-x': `${ normalizeOffset(
				attributes?.[ offsetXKey ]
			) }px`,
			'--vvm-slider-controls-offset-y': `${ normalizeOffset(
				attributes?.[ offsetYKey ]
			) }px`,
			...style,
		},
	};
}

function SliderArrowIcon() {
	return (
		<svg
			className="vvm-slider-button__icon"
			viewBox="0 0 24 24"
			aria-hidden="true"
			focusable="false"
		>
			<path d="M9 6l6 6-6 6" />
		</svg>
	);
}

export function SliderArrowPreview( {
	attributes,
	buttonClassName = '',
	className = '',
	defaultPreset = 'center',
	isVisible = true,
	nextButtonClassName = '',
	offsetXKey = 'arrowOffsetX',
	offsetYKey = 'arrowOffsetY',
	positionKey = 'arrowPositionPreset',
	previousButtonClassName = '',
	style = {},
} ) {
	if ( ! isVisible ) {
		return null;
	}

	const previewProps = getSliderArrowPreviewProps( attributes, {
		className,
		defaultPreset,
		offsetXKey,
		offsetYKey,
		positionKey,
		style,
	} );

	return (
		<div { ...previewProps } aria-hidden="true">
			<button
				type="button"
				className={ [
					'vvm-slider-button',
					'vvm-slider-button--prev',
					buttonClassName,
					previousButtonClassName,
				]
					.filter( Boolean )
					.join( ' ' ) }
				tabIndex={ -1 }
				disabled
			>
				<SliderArrowIcon />
			</button>
			<button
				type="button"
				className={ [
					'vvm-slider-button',
					'vvm-slider-button--next',
					buttonClassName,
					nextButtonClassName,
				]
					.filter( Boolean )
					.join( ' ' ) }
				tabIndex={ -1 }
				disabled
			>
				<SliderArrowIcon />
			</button>
		</div>
	);
}

export function SliderArrowControlsPanel( {
	attributes,
	defaultPreset = 'center',
	disabled = false,
	help = '',
	initialOpen = false,
	offsetXKey = 'arrowOffsetX',
	offsetYKey = 'arrowOffsetY',
	positionKey = 'arrowPositionPreset',
	setAttributes,
	title = __( 'Arrow position', 'gutenberg-lab-blocks' ),
} ) {
	return (
		<PanelBody title={ title } initialOpen={ initialOpen }>
			<SelectControl
				label={ __( 'Position preset', 'gutenberg-lab-blocks' ) }
				value={ normalizePreset( attributes?.[ positionKey ], defaultPreset ) }
				options={ PRESET_OPTIONS }
				onChange={ ( value ) =>
					setAttributes( {
						[ positionKey ]: normalizePreset( value, defaultPreset ),
					} )
				}
				help={ help || undefined }
				disabled={ disabled }
			/>
			<RangeControl
				label={ __( 'Horizontal offset', 'gutenberg-lab-blocks' ) }
				value={ normalizeOffset( attributes?.[ offsetXKey ] ) }
				onChange={ ( value ) =>
					setAttributes( {
						[ offsetXKey ]: normalizeOffset( value ),
					} )
				}
				min={ MIN_OFFSET }
				max={ MAX_OFFSET }
				step={ OFFSET_STEP }
				disabled={ disabled }
			/>
			<RangeControl
				label={ __( 'Vertical offset', 'gutenberg-lab-blocks' ) }
				value={ normalizeOffset( attributes?.[ offsetYKey ] ) }
				onChange={ ( value ) =>
					setAttributes( {
						[ offsetYKey ]: normalizeOffset( value ),
					} )
				}
				min={ MIN_OFFSET }
				max={ MAX_OFFSET }
				step={ OFFSET_STEP }
				disabled={ disabled }
			/>
		</PanelBody>
	);
}
