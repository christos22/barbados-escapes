import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import './style.scss';

import metadata from './block.json';

const Edit = ( { attributes, setAttributes } ) => {
	const { minimumBedrooms } = attributes;
	const blockProps = useBlockProps( {
		className: 'vvm-villa-bedroom-selector',
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Bedroom choices', 'gutenberg-lab-blocks' ) }
				>
					<RangeControl
						label={ __(
							'Minimum bedrooms',
							'gutenberg-lab-blocks'
						) }
						help={ __(
							'The maximum comes from the villa’s Bedroom spec.',
							'gutenberg-lab-blocks'
						) }
						value={ minimumBedrooms }
						onChange={ ( value ) =>
							setAttributes( {
								minimumBedrooms: Math.max(
									1,
									Number( value ) || 1
								),
							} )
						}
						min={ 1 }
						max={ 30 }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<select
					className="vvm-villa-bedroom-selector__select"
					aria-label={ __(
						'Bedrooms requested',
						'gutenberg-lab-blocks'
					) }
					disabled
				>
					<option>
						{ __(
							'Choices use the current Villa Specs block',
							'gutenberg-lab-blocks'
						) }
					</option>
				</select>
			</div>
		</>
	);
};

registerBlockType( metadata.name, {
	edit: Edit,

	// Dynamic block: PHP reads the current villa and renders trusted options.
	save() {
		return null;
	},
} );
