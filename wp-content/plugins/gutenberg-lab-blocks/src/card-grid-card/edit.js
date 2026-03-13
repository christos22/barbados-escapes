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
	'core/list',
	'core/buttons',
];

const TEMPLATE = [
	[
		'core/heading',
		{
			level: 3,
			placeholder: __( 'Add card heading…', 'gutenberg-lab-blocks' ),
		},
	],
	[
		'core/paragraph',
		{
			placeholder: __( 'Add card text…', 'gutenberg-lab-blocks' ),
		},
	],
	[ 'core/buttons', {} ],
];

export default function Edit( { attributes, setAttributes } ) {
	const { imageId, imageUrl, imageAlt } = attributes;

	const blockProps = useBlockProps( {
		className: 'vvm-card-grid__card',
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-card-grid__card-content',
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
					title={ __( 'Card media', 'gutenberg-lab-blocks' ) }
					initialOpen={ true }
				>
					{/* The image is stored as a simple attribute because the card shell
						should stay lightweight, while the text/button content remains
						native Gutenberg blocks for global typography and spacing. */}
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
						<Button
							variant="link"
							isDestructive
							onClick={ removeImage }
						>
							{ __( 'Remove image', 'gutenberg-lab-blocks' ) }
						</Button>
					) : null }
				</PanelBody>
			</InspectorControls>

			<article { ...blockProps }>
				<div className="vvm-card-grid__card-media">
					{ imageUrl ? (
						<img
							className="vvm-card-grid__card-image"
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
									<div className="vvm-card-grid__card-placeholder">
										<Button variant="secondary" onClick={ open }>
											{ __(
												'Select card image',
												'gutenberg-lab-blocks'
											) }
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
