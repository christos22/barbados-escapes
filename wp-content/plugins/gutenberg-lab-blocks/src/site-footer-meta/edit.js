import { RichText, useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * The editor keeps the trailing legal copy editable while PHP owns the year.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { copyrightText } = attributes;
	const blockProps = useBlockProps( {
		className: 'vvm-site-footer-meta',
	} );

	return (
		<div { ...blockProps }>
			<RichText
				tagName="p"
				value={ copyrightText }
				placeholder={ __( 'Footer legal text...', 'gutenberg-lab-blocks' ) }
				onChange={ ( value ) => setAttributes( { copyrightText: value } ) }
			/>
		</div>
	);
}
