import { __ } from '@wordpress/i18n';
import {
	MediaUpload,
	MediaUploadCheck,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { Button, TextControl } from '@wordpress/components';

const ALLOWED_INNER_BLOCKS = [
	'core/heading',
	'core/paragraph',
	'core/list',
	'core/buttons',
	'core/group',
	'core/quote',
];

const TEMPLATE = [
	[
		'core/heading',
		{
			level: 3,
			placeholder: __( 'Add reveal heading…', 'gutenberg-lab-blocks' ),
		},
	],
	[
		'core/paragraph',
		{
			placeholder: __( 'Add reveal description…', 'gutenberg-lab-blocks' ),
		},
	],
	[ 'core/buttons', {} ],
];

export default function Edit( { attributes, setAttributes } ) {
	const { label, mediaId, mediaUrl, mediaAlt } = attributes;
	const blockProps = useBlockProps( {
		className: 'vvm-stack-tabs__item-editor',
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-stack-tabs__item-editor-content',
		},
		{
			allowedBlocks: ALLOWED_INNER_BLOCKS,
			template: TEMPLATE,
			templateLock: false,
		}
	);

	const onSelectImage = ( media ) => {
		setAttributes( {
			mediaId: media.id,
			mediaUrl: media.url,
			mediaAlt: media.alt || '',
		} );
	};

	const removeImage = () => {
		setAttributes( {
			mediaId: undefined,
			mediaUrl: '',
			mediaAlt: '',
		} );
	};

	return (
		<div { ...blockProps }>
			<div className="vvm-stack-tabs__item-editor-meta">
				<TextControl
					label={ __( 'Item label', 'gutenberg-lab-blocks' ) }
					value={ label }
					onChange={ ( value ) => setAttributes( { label: value } ) }
					help={ __(
						'This becomes the clickable reveal row label on the front end.',
						'gutenberg-lab-blocks'
					) }
				/>

				<div className="vvm-stack-tabs__item-editor-media">
					{ mediaUrl ? (
						<img
							className="vvm-stack-tabs__item-editor-image"
							src={ mediaUrl }
							alt={ mediaAlt }
						/>
					) : (
						<div className="vvm-stack-tabs__item-editor-placeholder">
							<p>
								{ __(
									'Select the image that appears in the right-side media stage.',
									'gutenberg-lab-blocks'
								) }
							</p>
						</div>
					) }

					<div className="vvm-stack-tabs__item-editor-actions">
						<MediaUploadCheck>
							<MediaUpload
								onSelect={ onSelectImage }
								allowedTypes={ [ 'image' ] }
								value={ mediaId }
								render={ ( { open } ) => (
									<Button variant="secondary" onClick={ open }>
										{ mediaUrl
											? __(
													'Replace image',
													'gutenberg-lab-blocks'
											  )
											: __(
													'Select image',
													'gutenberg-lab-blocks'
											  ) }
									</Button>
								) }
							/>
						</MediaUploadCheck>

						{ mediaUrl ? (
							<Button variant="link" isDestructive onClick={ removeImage }>
								{ __( 'Remove image', 'gutenberg-lab-blocks' ) }
							</Button>
						) : null }
					</div>
				</div>
			</div>

			<div { ...innerBlocksProps } />
		</div>
	);
}
