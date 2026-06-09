import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';

import AmenityIconControls, {
	getAmenityIconMarkup,
	getAmenityIconSizeStyle,
} from '../shared/amenity-icon-controls';

export default function Edit( { attributes, setAttributes } ) {
	const { value, label, iconSlug, iconSize } = attributes;
	const iconMarkup = getAmenityIconMarkup( iconSlug );
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
					<AmenityIconControls
						defaultSize={ 2.5 }
						iconSize={ iconSize }
						iconSlug={ iconSlug }
						max={ 6 }
						setAttributes={ setAttributes }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				{ iconMarkup ? (
					<span
						className="vvm-villa-specs__icon"
						aria-hidden="true"
						style={ getAmenityIconSizeStyle(
							iconSize,
							'--vvm-villa-spec-icon-size'
						) }
						dangerouslySetInnerHTML={ { __html: iconMarkup } }
					/>
				) : null }
				<RichText
					tagName="p"
					className="vvm-villa-specs__value"
					value={ value }
					onChange={ ( nextValue ) =>
						setAttributes( { value: nextValue } )
					}
					placeholder={ __(
						'Add spec value…',
						'gutenberg-lab-blocks'
					) }
					allowedFormats={ [] }
				/>
				<RichText
					tagName="p"
					className="vvm-villa-specs__label"
					value={ label }
					onChange={ ( nextValue ) =>
						setAttributes( { label: nextValue } )
					}
					placeholder={ __(
						'Add spec label…',
						'gutenberg-lab-blocks'
					) }
					allowedFormats={ [] }
				/>
			</div>
		</>
	);
}
