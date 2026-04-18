import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,

	save( { attributes } ) {
		const { detail, eyebrow, imageAlt, imageUrl, title } = attributes;
		const blockProps = useBlockProps.save( {
			className: 'vvm-villa-gallery-carousel__slide',
		} );

		return (
			<article { ...blockProps }>
				<div className="vvm-villa-gallery-carousel__card">
					<div
						className={
							imageUrl
								? 'vvm-villa-gallery-carousel__slide-media'
								: 'vvm-villa-gallery-carousel__slide-media vvm-villa-gallery-carousel__slide-media--placeholder'
						}
					>
						{ imageUrl ? (
							<img
								className="vvm-villa-gallery-carousel__slide-image"
								src={ imageUrl }
								alt={ imageAlt }
							/>
						) : (
							<span className="vvm-villa-gallery-carousel__slide-placeholder">
								Add image
							</span>
						) }
					</div>

					<div className="vvm-villa-gallery-carousel__copy">
						{ eyebrow ? (
							<p className="vvm-villa-gallery-carousel__eyebrow">
								{ eyebrow }
							</p>
						) : null }
						{ title ? (
							<h3 className="vvm-villa-gallery-carousel__title">
								{ title }
							</h3>
						) : null }
						{ detail ? (
							<p className="vvm-villa-gallery-carousel__detail">
								{ detail }
							</p>
						) : null }
					</div>
				</div>
			</article>
		);
	},
} );
