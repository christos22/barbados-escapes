import { __ } from '@wordpress/i18n';
import { RichText, useBlockProps } from '@wordpress/block-editor';

export default function Edit( { attributes, setAttributes } ) {
	const { value, label } = attributes;
	const blockProps = useBlockProps( {
		className: 'vvm-villa-specs__item',
	} );

	return (
		<div { ...blockProps }>
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
	);
}
