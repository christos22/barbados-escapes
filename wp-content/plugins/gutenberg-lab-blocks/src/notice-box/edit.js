import { __ } from '@wordpress/i18n';
import { RichText, useBlockProps } from '@wordpress/block-editor';

import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	return (
		<RichText
			{ ...useBlockProps() }
			tagName="p"
			value={ attributes.message }
			onChange={ ( message ) => setAttributes( { message } ) }
			placeholder={ __( 'Write a notice message...', 'gutenberg-lab-blocks' ) }
		/>
	);
}
