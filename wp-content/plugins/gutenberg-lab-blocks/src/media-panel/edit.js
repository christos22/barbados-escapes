import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';

import './editor.scss';

const MEDIA_TYPE_OPTIONS = [
	{ label: 'Image', value: 'image' },
	{ label: 'Video', value: 'video' },
];

const HEIGHT_OPTIONS = [
	{ label: 'Small', value: 'small' },
	{ label: 'Medium', value: 'medium' },
	{ label: 'Large', value: 'large' },
	{ label: 'Full', value: 'full' },
];

const POSITION_OPTIONS = [
	{ label: 'Top Left', value: 'top-left' },
	{ label: 'Top Center', value: 'top-center' },
	{ label: 'Top Right', value: 'top-right' },
	{ label: 'Center Left', value: 'center-left' },
	{ label: 'Center Center', value: 'center-center' },
	{ label: 'Center Right', value: 'center-right' },
	{ label: 'Bottom Left', value: 'bottom-left' },
	{ label: 'Bottom Center', value: 'bottom-center' },
	{ label: 'Bottom Right', value: 'bottom-right' },
];

const CONTENT_WIDTH_OPTIONS = [
	{ label: 'Small', value: 'sm' },
	{ label: 'Medium', value: 'md' },
	{ label: 'Large', value: 'lg' },
];

const ALLOWED_INNER_BLOCKS = [
	'core/heading',
	'core/paragraph',
	'core/list',
	'core/buttons',
	'core/spacer',
];

export default function Edit({ attributes, setAttributes }) {
	const {
		mediaType,
		imageId,
		imageUrl,
		imageAlt,
		videoId,
		videoUrl,
		fallbackImageId,
		fallbackImageUrl,
		fallbackImageAlt,
		darkOverlay,
		containerHeight,
		contentPosition,
		contentWidth,
		align,
	} = attributes;

	const blockProps = useBlockProps({
		className: [
			'media-panel',
			`media-panel--height-${containerHeight}`,
			`media-panel--position-${contentPosition}`,
			`media-panel--content-width-${contentWidth}`,
			align ? '' : 'alignfull',
			darkOverlay ? 'media-panel--dark-overlay' : '',
		]
			.filter(Boolean)
			.join(' '),
	});

	const innerBlocksTemplate = [
		[
			'core/heading',
			{
				level: 2,
				placeholder: __('Add heading...', 'gutenberg-lab-blocks'),
			},
		],
		[
			'core/paragraph',
			{
				placeholder: __('Add body text...', 'gutenberg-lab-blocks'),
			},
		],
		['core/buttons', {}],
	];

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'media-panel__overlay-content',
		},
		{
			template: innerBlocksTemplate,
			templateLock: false,
			allowedBlocks: ALLOWED_INNER_BLOCKS,
		}
	);

	const onSelectImage = (media) => {
		setAttributes({
			imageId: media.id,
			imageUrl: media.url,
			imageAlt: media.alt || '',
		});
	};

	const onSelectVideo = (media) => {
		setAttributes({
			videoId: media.id,
			videoUrl: media.url,
		});
	};

	const onSelectFallbackImage = (media) => {
		setAttributes({
			fallbackImageId: media.id,
			fallbackImageUrl: media.url,
			fallbackImageAlt: media.alt || '',
		});
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Media Settings', 'gutenberg-lab-blocks')}>
					<SelectControl
						label={__('Media Type', 'gutenberg-lab-blocks')}
						value={mediaType}
						options={MEDIA_TYPE_OPTIONS}
						onChange={(mediaTypeValue) =>
							setAttributes({ mediaType: mediaTypeValue })
						}
					/>

					{mediaType === 'video' && (
						<MediaUploadCheck>
							<MediaUpload
								onSelect={onSelectFallbackImage}
								allowedTypes={['image']}
								value={fallbackImageId}
								render={({ open }) => (
									<Button variant="secondary" onClick={open}>
										{fallbackImageUrl
											? __(
													'Replace fallback image',
													'gutenberg-lab-blocks'
											  )
											: __(
													'Select fallback image',
													'gutenberg-lab-blocks'
											  )}
									</Button>
								)}
							/>
						</MediaUploadCheck>
					)}

					<ToggleControl
						label={__('Dark Overlay', 'gutenberg-lab-blocks')}
						checked={darkOverlay}
						onChange={(darkOverlayValue) =>
							setAttributes({ darkOverlay: darkOverlayValue })
						}
					/>

					<SelectControl
						label={__('Container Height', 'gutenberg-lab-blocks')}
						value={containerHeight}
						options={HEIGHT_OPTIONS}
						onChange={(containerHeightValue) =>
							setAttributes({ containerHeight: containerHeightValue })
						}
					/>

					<SelectControl
						label={__('Content Position', 'gutenberg-lab-blocks')}
						value={contentPosition}
						options={POSITION_OPTIONS}
						onChange={(contentPositionValue) =>
							setAttributes({ contentPosition: contentPositionValue })
						}
					/>

					<SelectControl
						label={__('Content Width', 'gutenberg-lab-blocks')}
						value={contentWidth}
						options={CONTENT_WIDTH_OPTIONS}
						onChange={(contentWidthValue) =>
							setAttributes({ contentWidth: contentWidthValue })
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>
				<div className="media-panel__editor-actions">
					{mediaType === 'image' ? (
						<MediaUploadCheck>
							<MediaUpload
								onSelect={onSelectImage}
								allowedTypes={['image']}
								value={imageId}
								render={({ open }) => (
									<Button variant="secondary" onClick={open}>
										{imageUrl
											? __('Replace image', 'gutenberg-lab-blocks')
											: __('Select image', 'gutenberg-lab-blocks')}
									</Button>
								)}
							/>
						</MediaUploadCheck>
					) : (
						<MediaUploadCheck>
							<MediaUpload
								onSelect={onSelectVideo}
								allowedTypes={['video']}
								value={videoId}
								render={({ open }) => (
									<Button variant="secondary" onClick={open}>
										{videoUrl
											? __('Replace video', 'gutenberg-lab-blocks')
											: __('Select video', 'gutenberg-lab-blocks')}
									</Button>
								)}
							/>
						</MediaUploadCheck>
					)}
				</div>

				<div className="media-panel__media">
					{mediaType === 'image' ? (
						<>
							{imageUrl && (
								<img
									className="media-panel__image"
									src={imageUrl}
									alt={imageAlt}
								/>
							)}
						</>
					) : (
						<>
							{videoUrl && (
								<video
									className="media-panel__video"
									src={videoUrl}
									autoPlay
									muted
									loop
									playsInline
									poster={fallbackImageUrl || undefined}
								/>
							)}

							{!videoUrl && fallbackImageUrl && (
								<img
									className="media-panel__image"
									src={fallbackImageUrl}
									alt={fallbackImageAlt}
								/>
							)}
						</>
					)}
				</div>

				<div className="media-panel__overlay">
					<div {...innerBlocksProps} />
				</div>
			</div>
		</>
	);
}
