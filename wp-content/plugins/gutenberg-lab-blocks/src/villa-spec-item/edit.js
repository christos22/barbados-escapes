import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';

const iconSettings = window.gutenbergLabBlocksVillaAmenityIcons || {};

// PHP exposes the amenity taxonomy icon registry here so this block picker
// stays aligned with the taxonomy selector instead of carrying a second list.
const ICON_OPTIONS = [
	{ value: '', label: __( 'No icon', 'gutenberg-lab-blocks' ) },
	...( iconSettings.choices || [] ),
];

export default function Edit( { attributes, setAttributes } ) {
	const { value, label, iconSlug } = attributes;
	const iconMarkup = iconSettings.icons?.[ iconSlug ] || '';
	const blockProps = useBlockProps( {
		className: [
			'vvm-villa-specs__item',
			iconMarkup ? 'vvm-villa-specs__item--has-icon' : '',
		]
			.filter( Boolean )
			.join( ' ' ),
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Spec icon', 'gutenberg-lab-blocks' ) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __( 'Icon', 'gutenberg-lab-blocks' ) }
						value={ iconSlug }
						options={ ICON_OPTIONS }
						help={ __(
							'Uses the same icon choices as villa amenity terms.',
							'gutenberg-lab-blocks'
						) }
						onChange={ ( nextIconSlug ) =>
							setAttributes( { iconSlug: nextIconSlug } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				{ iconMarkup ? (
					<span
						className="vvm-villa-specs__icon"
						aria-hidden="true"
						dangerouslySetInnerHTML={ { __html: iconMarkup } }
					/>
				) : null }
				<RichText
					tagName="p"
					className="vvm-villa-specs__value"
					value={ value }
					onChange={ ( nextValue ) => setAttributes( { value: nextValue } ) }
					placeholder={ __( 'Add spec value…', 'gutenberg-lab-blocks' ) }
					allowedFormats={ [] }
				/>
				<RichText
					tagName="p"
					className="vvm-villa-specs__label"
					value={ label }
					onChange={ ( nextValue ) => setAttributes( { label: nextValue } ) }
					placeholder={ __( 'Add spec label…', 'gutenberg-lab-blocks' ) }
					allowedFormats={ [] }
				/>
			</div>
		</>
	);
}
