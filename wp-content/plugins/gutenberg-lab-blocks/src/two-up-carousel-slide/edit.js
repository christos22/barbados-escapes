import { __ } from '@wordpress/i18n';
import {
	InnerBlocks,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { Button, PanelBody } from '@wordpress/components';

import './editor.scss';

const ALLOWED_INNER_BLOCKS = [
	'core/heading',
	'core/paragraph',
	'core/buttons',
];

const TEMPLATE = [];

export default function Edit( { attributes, setAttributes } ) {
	const { imageAlt, imageId, imageUrl } = attributes;

	const blockProps = useBlockProps( {
		className: 'vvm-two-up-carousel__slide-editor',
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-two-up-carousel__slide-editor-content',
		},
		{
			allowedBlocks: ALLOWED_INNER_BLOCKS,
			template: TEMPLATE,
			templateLock: false,
			renderAppender: InnerBlocks.ButtonBlockAppender,
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
					{/* Keep the media as lean attributes while native inner blocks
						handle the actual copy and CTA authoring. */}
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
							? 'vvm-two-up-carousel__slide-editor-media'
							: 'vvm-two-up-carousel__slide-editor-media vvm-two-up-carousel__slide-editor-media--placeholder'
					}
				>
					{ imageUrl ? (
						<img
							className="vvm-two-up-carousel__slide-editor-image"
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
									<div className="vvm-two-up-carousel__slide-editor-placeholder">
										<Button variant="secondary" onClick={ open }>
											{ __( 'Select slide image', 'gutenberg-lab-blocks' ) }
										</Button>
									</div>
								) }
							/>
						</MediaUploadCheck>
					) }
				</div>

				<div className="vvm-two-up-carousel__slide-editor-overlay">
					<p className="vvm-two-up-carousel__slide-editor-note">
						{ __(
							'Add optional heading, paragraph, or button blocks. Slides start empty by default.',
							'gutenberg-lab-blocks'
						) }
					</p>
					<div { ...innerBlocksProps } />
				</div>
			</article>
		</>
	);
}
