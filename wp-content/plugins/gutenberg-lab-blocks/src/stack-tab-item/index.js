import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// Keep a small semantic wrapper in saved markup so the item remains portable
	// if it is ever rendered outside the custom parent shell.
	save( { attributes } ) {
		const { label, mediaUrl, mediaAlt } = attributes;
		const blockProps = useBlockProps.save( {
			className: 'vvm-stack-tabs__item-template',
			'data-stack-tabs-item-label': label || '',
			'data-stack-tabs-item-media-url': mediaUrl || '',
			'data-stack-tabs-item-media-alt': mediaAlt || '',
		} );

		return (
			<div { ...blockProps }>
				<div className="vvm-stack-tabs__item-template-content">
					<InnerBlocks.Content />
				</div>
			</div>
		);
	},
} );
