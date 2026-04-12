import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	save( { attributes } ) {
		const { imageAlt, imageUrl } = attributes;
		const blockProps = useBlockProps.save( {
			className: 'vvm-card-carousel__slide',
		} );

		return (
			<article { ...blockProps }>
				<div
					className={
						imageUrl
							? 'vvm-card-carousel__slide-media'
							: 'vvm-card-carousel__slide-media vvm-card-carousel__slide-media--placeholder'
					}
				>
					{ imageUrl ? (
						<img
							className="vvm-card-carousel__slide-image"
							src={ imageUrl }
							alt={ imageAlt }
						/>
					) : (
						<span className="vvm-card-carousel__slide-placeholder-label">
							Select a slide image
						</span>
					) }
				</div>

				<div className="vvm-card-carousel__slide-content">
					<InnerBlocks.Content />
				</div>
			</article>
		);
	},
} );
