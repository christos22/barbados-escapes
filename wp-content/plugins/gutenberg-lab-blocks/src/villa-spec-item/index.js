import { registerBlockType } from '@wordpress/blocks';
import { RichText, useBlockProps } from '@wordpress/block-editor';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// Keep each item as plain saved markup so the parent can stay lightweight
	// and the authored values remain easy to inspect in post content.
	save( { attributes } ) {
		const { value, label } = attributes;

		if ( ! value && ! label ) {
			return null;
		}

		const blockProps = useBlockProps.save( {
			className: 'vvm-villa-specs__item',
		} );

		return (
			<div { ...blockProps }>
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
