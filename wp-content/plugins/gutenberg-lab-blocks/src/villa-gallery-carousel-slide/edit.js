import { __ } from '@wordpress/i18n';
import {
	MediaUpload,
	MediaUploadCheck,
	PlainText,
	useBlockProps,
} from '@wordpress/block-editor';
import { Button } from '@wordpress/components';

function ImageButton( { imageId, imageUrl, onSelectImage } ) {
	return (
		<MediaUploadCheck>
			<MediaUpload
				onSelect={ onSelectImage }
				allowedTypes={ [ 'image' ] }
				value={ imageId }
				render={ ( { open } ) => (
					<Button variant="secondary" onClick={ open }>
						{ imageUrl
							? __( 'Replace image', 'gutenberg-lab-blocks' )
							: __( 'Select image', 'gutenberg-lab-blocks' ) }
					</Button>
				) }
			/>
		</MediaUploadCheck>
	);
}

export default function Edit( { attributes, setAttributes } ) {
	const { detail, eyebrow, imageAlt, imageId, imageUrl, title } = attributes;
	const blockProps = useBlockProps( {
		className: 'vvm-villa-gallery-carousel__slide',
	} );

	const onSelectImage = ( media ) => {
		setAttributes( {
			imageId: media.id,
			imageUrl: media.url,
			imageAlt: media.alt || '',
		} );
	};

	const removeImage = () => {
		setAttributes( {
			imageId: undefined,
			imageUrl: '',
			imageAlt: '',
		} );
	};

	return (
		<article { ...blockProps }>
			<div className="vvm-villa-gallery-carousel__editor-actions">
				<ImageButton
					imageId={ imageId }
					imageUrl={ imageUrl }
					onSelectImage={ onSelectImage }
				/>
				{ imageUrl ? (
					<Button variant="link" isDestructive onClick={ removeImage }>
						{ __( 'Remove image', 'gutenberg-lab-blocks' ) }
					</Button>
				) : null }
			</div>

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
						<div className="vvm-villa-gallery-carousel__slide-placeholder">
							<ImageButton
								imageId={ imageId }
								imageUrl={ imageUrl }
								onSelectImage={ onSelectImage }
							/>
						</div>
					) }
				</div>

				<div className="vvm-villa-gallery-carousel__copy">
					<PlainText
						className="vvm-villa-gallery-carousel__eyebrow"
						value={ eyebrow }
						onChange={ ( value ) => setAttributes( { eyebrow: value } ) }
						placeholder={ __(
							'Bedroom image',
							'gutenberg-lab-blocks'
						) }
					/>
					<PlainText
						className="vvm-villa-gallery-carousel__title"
						value={ title }
						onChange={ ( value ) => setAttributes( { title: value } ) }
						placeholder={ __(
							'Master Suite',
							'gutenberg-lab-blocks'
						) }
					/>
					<PlainText
						className="vvm-villa-gallery-carousel__detail"
						value={ detail }
						onChange={ ( value ) => setAttributes( { detail: value } ) }
						placeholder={ __(
							'Garden outlook - Private terrace',
							'gutenberg-lab-blocks'
						) }
					/>
				</div>
			</div>
		</article>
	);
}
