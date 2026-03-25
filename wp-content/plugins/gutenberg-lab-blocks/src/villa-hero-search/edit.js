import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { buttonLabel, locationPlaceholder } = attributes;
	const blockProps = useBlockProps( {
		className: 'vvm-villa-hero-search-editor-preview',
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Search Settings', 'gutenberg-lab-blocks' ) }>
					<TextControl
						label={ __( 'Button Label', 'gutenberg-lab-blocks' ) }
						value={ buttonLabel }
						onChange={ ( value ) => setAttributes( { buttonLabel: value } ) }
					/>
					<TextControl
						label={ __( 'Location Placeholder', 'gutenberg-lab-blocks' ) }
						value={ locationPlaceholder }
						onChange={ ( value ) =>
							setAttributes( { locationPlaceholder: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<ServerSideRender
					block="gutenberg-lab-blocks/villa-hero-search"
					attributes={ attributes }
				/>
				<p className="vvm-villa-hero-search-editor-preview__hint">
					{ __(
						'Preview uses the live Villa Location terms and keeps the selected archive filter visible while you edit.',
						'gutenberg-lab-blocks'
					) }
				</p>
			</div>
		</>
	);
}
