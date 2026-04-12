import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { Button, PanelBody } from '@wordpress/components';

const ALLOWED_INNER_BLOCKS = [
	'core/heading',
	'core/paragraph',
	'core/buttons',
];

const TEMPLATE = [
	[
		'core/heading',
		{
			level: 3,
			placeholder: __( 'Slide heading…', 'gutenberg-lab-blocks' ),
		},
	],
	[
		'core/paragraph',
		{
			placeholder: __( 'Add slide text…', 'gutenberg-lab-blocks' ),
		},
	],
	[ 'core/buttons', {} ],
];

export default function Edit( { attributes, setAttributes } ) {
	const { imageAlt, imageId, imageUrl } = attributes;

	const blockProps = useBlockProps( {
		className: 'vvm-card-carousel__slide',
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-card-carousel__slide-content',
		},
		{
			allowedBlocks: ALLOWED_INNER_BLOCKS,
			template: TEMPLATE,
			templateLock: false,
		}
	);

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
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Slide media', 'gutenberg-lab-blocks' ) }
					initialOpen={ true }
				>
					{/* The image stays as a lightweight attribute while the textual
						content remains native Gutenberg blocks for better flexibility. */}
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ onSelectImage }
							allowedTypes={ [ 'image' ] }
							value={ imageId }
							render={ ( { open } ) => (
								<Button variant="secondary" onClick={ open }>
									{ imageUrl
										? __(
												'Replace image',
												'gutenberg-lab-blocks'
										  )
										: __( 'Select image', 'gutenberg-lab-blocks' ) }
								</Button>
							) }
						/>
					</MediaUploadCheck>
					{ imageUrl ? (
						<Button variant="link" isDestructive onClick={ removeImage }>
							{ __( 'Remove image', 'gutenberg-lab-blocks' ) }
						</Button>
					) : null }
				</PanelBody>
			</InspectorControls>

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
						<MediaUploadCheck>
							<MediaUpload
								onSelect={ onSelectImage }
								allowedTypes={ [ 'image' ] }
								value={ imageId }
								render={ ( { open } ) => (
									<div className="vvm-card-carousel__slide-placeholder">
										<Button variant="secondary" onClick={ open }>
											{ __( 'Select slide image', 'gutenberg-lab-blocks' ) }
										</Button>
									</div>
								) }
							/>
						</MediaUploadCheck>
					) }
				</div>
				<div { ...innerBlocksProps } />
			</article>
		</>
	);
}
