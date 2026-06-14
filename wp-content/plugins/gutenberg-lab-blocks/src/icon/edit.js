import { __ } from '@wordpress/i18n';
import {
	AlignmentToolbar,
	BlockControls,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	TextControl,
} from '@wordpress/components';

import IconSizeControl, {
	getIconSizeStyle,
} from '../shared/icon-size-control';
import { getAmenityIconMarkup } from '../shared/amenity-icon-controls';

import './editor.scss';

const iconSettings = window.gutenbergLabBlocksVillaAmenityIcons || {};

const ICON_OPTIONS = [
	{ value: '', label: __( 'No icon', 'gutenberg-lab-blocks' ) },
	...( iconSettings.choices || [] ),
];

const SIZE_PRESET_OPTIONS = [
	{ label: __( 'XS', 'gutenberg-lab-blocks' ), value: 'xs' },
	{ label: __( 'SM', 'gutenberg-lab-blocks' ), value: 'sm' },
	{ label: __( 'MD', 'gutenberg-lab-blocks' ), value: 'md' },
	{ label: __( 'LG', 'gutenberg-lab-blocks' ), value: 'lg' },
	{ label: __( 'XL', 'gutenberg-lab-blocks' ), value: 'xl' },
	{ label: __( 'Custom', 'gutenberg-lab-blocks' ), value: 'custom' },
];

const SIZE_PRESETS = SIZE_PRESET_OPTIONS.map( ( option ) => option.value );
const ALIGNMENTS = [ 'left', 'center', 'right' ];

function hasCustomIconSize( customSize ) {
	const numericSize = Number.parseFloat( customSize );

	return ! Number.isNaN( numericSize ) && numericSize > 0;
}

function normalizeSizePreset(
	sizePreset,
	customSize,
	useCustomFallback = false
) {
	const normalizedSizePreset = SIZE_PRESETS.includes( sizePreset )
		? sizePreset
		: 'md';

	if (
		useCustomFallback &&
		'md' === normalizedSizePreset &&
		hasCustomIconSize( customSize )
	) {
		return 'custom';
	}

	return normalizedSizePreset;
}

function normalizeAlignment( alignment ) {
	return ALIGNMENTS.includes( alignment ) ? alignment : 'left';
}

export default function Edit( { attributes, setAttributes } ) {
	const {
		alignment,
		ariaLabel,
		customSize,
		iconSlug,
		sizePreset,
	} = attributes;
	const normalizedSizePreset = normalizeSizePreset(
		sizePreset,
		customSize,
		true
	);
	const normalizedAlignment = normalizeAlignment( alignment );
	const iconMarkup = getAmenityIconMarkup( iconSlug );
	const isCustomSize = 'custom' === normalizedSizePreset;
	const blockProps = useBlockProps( {
		className: [
			'vvm-icon',
			iconMarkup ? 'vvm-icon--has-icon' : 'vvm-icon--is-empty',
			`vvm-icon--size-${ normalizedSizePreset }`,
			`vvm-icon--align-${ normalizedAlignment }`,
		]
			.filter( Boolean )
			.join( ' ' ),
		style: isCustomSize
			? getIconSizeStyle( customSize, '--vvm-icon-block-size' )
			: undefined,
	} );

	return (
		<>
			<BlockControls group="block">
				<AlignmentToolbar
					value={ normalizedAlignment }
					onChange={ ( nextAlignment ) =>
						setAttributes( {
							alignment: normalizeAlignment( nextAlignment ),
						} )
					}
				/>
			</BlockControls>
			<InspectorControls>
				<PanelBody
					title={ __( 'Icon', 'gutenberg-lab-blocks' ) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __( 'Icon', 'gutenberg-lab-blocks' ) }
						value={ iconSlug }
						options={ ICON_OPTIONS }
						help={ __(
							'Uses the shared icon registry used by the other icon blocks.',
							'gutenberg-lab-blocks'
						) }
						onChange={ ( nextIconSlug ) =>
							setAttributes( { iconSlug: nextIconSlug } )
						}
					/>
					<SelectControl
						label={ __( 'Size', 'gutenberg-lab-blocks' ) }
						value={ normalizedSizePreset }
						options={ SIZE_PRESET_OPTIONS }
						onChange={ ( nextSizePreset ) => {
							const nextNormalizedSizePreset =
								normalizeSizePreset( nextSizePreset );

							setAttributes( {
								sizePreset: nextNormalizedSizePreset,
								...( 'custom' === nextNormalizedSizePreset
									? {}
									: { customSize: 0 } ),
							} );
						} }
					/>
					{ isCustomSize ? (
						<IconSizeControl
							defaultSize={ 3.5 }
							iconSize={ customSize }
							max={ 12 }
							min={ 0.75 }
							onChange={ ( nextCustomSize ) =>
								setAttributes( { customSize: nextCustomSize } )
							}
							onReset={ () => setAttributes( { customSize: 0 } ) }
						/>
					) : null }
				</PanelBody>
				<PanelBody
					title={ __( 'Accessibility', 'gutenberg-lab-blocks' ) }
					initialOpen={ false }
				>
					<TextControl
						label={ __( 'Accessible label', 'gutenberg-lab-blocks' ) }
						value={ ariaLabel }
						help={ __(
							'Leave empty when the icon is decorative.',
							'gutenberg-lab-blocks'
						) }
						onChange={ ( nextAriaLabel ) =>
							setAttributes( { ariaLabel: nextAriaLabel } )
						}
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				{ iconMarkup ? (
					<span
						className="vvm-icon__glyph"
						aria-hidden={ ariaLabel ? undefined : true }
						aria-label={ ariaLabel || undefined }
						role={ ariaLabel ? 'img' : undefined }
						dangerouslySetInnerHTML={ { __html: iconMarkup } }
					/>
				) : (
					<span className="vvm-icon__placeholder">
						{ __( 'Select an icon', 'gutenberg-lab-blocks' ) }
					</span>
				) }
			</div>
		</>
	);
}
