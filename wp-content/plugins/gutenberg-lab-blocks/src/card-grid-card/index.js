import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	save( { attributes } ) {
		const { imageUrl, imageAlt } = attributes;
		const blockProps = useBlockProps.save( {
			className: 'vvm-card-grid__card',
		} );

		return (
			<article { ...blockProps }>
				<div className="vvm-card-grid__card-media">
					{ imageUrl ? (
						<img
							className="vvm-card-grid__card-image"
							src={ imageUrl }
							alt={ imageAlt }
						/>
					) : null }
				</div>
				<div className="vvm-card-grid__card-content">
					<InnerBlocks.Content />
				</div>
			</article>
		);
	},
} );
