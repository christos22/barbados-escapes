import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	// Keep a lightweight saved shape so the image attributes remain visible in
	// block serialization while the parent still owns the final front-end shell.
	save( { attributes } ) {
		const { imageAlt, imageUrl } = attributes;
		const blockProps = useBlockProps.save( {
			className: 'vvm-feature-carousel__slide-editor',
		} );

		return (
			<article { ...blockProps }>
				<div
					className={
						imageUrl
							? 'vvm-feature-carousel__slide-editor-media'
							: 'vvm-feature-carousel__slide-editor-media vvm-feature-carousel__slide-editor-media--placeholder'
					}
				>
					{ imageUrl ? (
						<img
							className="vvm-feature-carousel__slide-editor-image"
							src={ imageUrl }
							alt={ imageAlt }
						/>
					) : (
						<span className="vvm-feature-carousel__slide-editor-placeholder-label">
							Select a slide image
						</span>
					) }
				</div>

				<div className="vvm-feature-carousel__slide-editor-panel">
					<div className="vvm-feature-carousel__slide-editor-panel-flow">
						<InnerBlocks.Content />
					</div>
				</div>
			</article>
		);
	},
} );
