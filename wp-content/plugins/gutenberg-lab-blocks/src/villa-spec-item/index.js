import { registerBlockType } from '@wordpress/blocks';
import { RichText, useBlockProps } from '@wordpress/block-editor';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// PHP renders the live block. This saved markup remains as a readable
	// fallback and keeps existing saved Spec Items valid in the editor.
	save( { attributes } ) {
		const { value, label, iconSlug } = attributes;

		if ( ! value && ! label && ! iconSlug ) {
			return null;
		}

		const blockProps = useBlockProps.save( {
			className: [
				'vvm-villa-specs__item',
				iconSlug ? 'vvm-villa-specs__item--has-icon' : '',
			]
				.filter( Boolean )
				.join( ' ' ),
		} );

		return (
			<div { ...blockProps }>
				{ iconSlug ? (
					<span
						className="vvm-villa-specs__icon"
						aria-hidden="true"
						data-icon={ iconSlug }
					/>
				) : null }
				{ value ? (
					<RichText.Content
						tagName="p"
						className="vvm-villa-specs__value"
						value={ value }
					/>
				) : null }
				{ label ? (
					<RichText.Content
						tagName="p"
						className="vvm-villa-specs__label"
						value={ label }
					/>
				) : null }
			</div>
		);
	},
} );
