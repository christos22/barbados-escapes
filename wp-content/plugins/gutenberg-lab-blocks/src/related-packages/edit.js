import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	TextControl,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import './editor.scss';

const COLUMN_OPTIONS = [
	{ label: __( '2 Columns', 'gutenberg-lab-blocks' ), value: '2' },
	{ label: __( '3 Columns', 'gutenberg-lab-blocks' ), value: '3' },
];

export default function Edit( { attributes, setAttributes } ) {
	const { heading, introText, count, columns, excludeCurrent } = attributes;
	const blockProps = useBlockProps( {
		className: `vvm-related-packages-placeholder vvm-related-packages-placeholder--columns-${ columns }`,
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Query Settings', 'gutenberg-lab-blocks' ) }>
					<TextControl
						label={ __( 'Heading', 'gutenberg-lab-blocks' ) }
						value={ heading }
						onChange={ ( value ) => setAttributes( { heading: value } ) }
					/>
					<TextareaControl
						label={ __( 'Intro Text', 'gutenberg-lab-blocks' ) }
						value={ introText }
						onChange={ ( value ) => setAttributes( { introText: value } ) }
					/>
					<RangeControl
						label={ __( 'Packages to Show', 'gutenberg-lab-blocks' ) }
						value={ count }
						onChange={ ( value ) => setAttributes( { count: value } ) }
						min={ 1 }
						max={ 12 }
					/>
					<SelectControl
						label={ __( 'Columns', 'gutenberg-lab-blocks' ) }
						value={ columns }
						options={ COLUMN_OPTIONS }
						onChange={ ( value ) => setAttributes( { columns: value } ) }
					/>
					<ToggleControl
						label={ __( 'Exclude Current Package', 'gutenberg-lab-blocks' ) }
						checked={ excludeCurrent }
						onChange={ ( value ) => setAttributes( { excludeCurrent: value } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				{ heading && <h3 className="vvm-related-packages-placeholder__heading">{ heading }</h3> }
				{ introText && <p className="vvm-related-packages-placeholder__intro">{ introText }</p> }
				<div className="vvm-related-packages-placeholder__grid">
					<div className="vvm-related-packages-placeholder__card" />
					<div className="vvm-related-packages-placeholder__card" />
					<div className="vvm-related-packages-placeholder__card" />
				</div>
			</section>
		</>
	);
}
