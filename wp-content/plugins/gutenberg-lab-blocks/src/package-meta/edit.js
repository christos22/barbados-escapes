import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

import './editor.scss';

const VARIANT_OPTIONS = [
	{ label: __( 'Hero', 'gutenberg-lab-blocks' ), value: 'hero' },
	{ label: __( 'Card', 'gutenberg-lab-blocks' ), value: 'card' },
];

export default function Edit( { attributes, setAttributes } ) {
	const { variant } = attributes;
	const blockProps = useBlockProps( {
		className: `vvm-package-meta-editor-preview vvm-package-meta-editor-preview--${ variant }`,
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Display Settings', 'gutenberg-lab-blocks' ) }>
					<SelectControl
						label={ __( 'Variant', 'gutenberg-lab-blocks' ) }
						value={ variant }
						options={ VARIANT_OPTIONS }
						onChange={ ( value ) => setAttributes( { variant: value } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<ServerSideRender
					block="gutenberg-lab-blocks/package-meta"
					attributes={ attributes }
				/>
				<p className="vvm-package-meta-editor-preview__hint">
					{ __(
						'Preview uses the current package when available, or the first published package while editing a template.',
						'gutenberg-lab-blocks'
					) }
				</p>
			</div>
		</>
	);
}
