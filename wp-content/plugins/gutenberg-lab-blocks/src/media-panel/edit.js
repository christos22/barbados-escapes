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

export default function Edit({ attributes, setAttributes }) {
	const {
		mediaType,
		imageId,
		imageUrl,
		imageAlt,
		videoId,
		videoUrl,
		darkOverlay,
		containerHeight,
		contentPosition,
	} = attributes;

	const blockProps = useBlockProps({
		className: `is-height-${containerHeight} is-position-${contentPosition}`,
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
			allowedBlocks: [
				'core/heading',
				'core/paragraph',
				'core/list',
				'core/buttons',
				'core/spacer',
			],
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

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Media Settings', 'gutenberg-lab-blocks')}>
					<SelectControl
						label={__('Media Type', 'gutenberg-lab-blocks')}
						value={mediaType}
						options={[
							{ label: 'Image', value: 'image' },
							{ label: 'Video', value: 'video' },
						]}
						onChange={(mediaTypeValue) =>
							setAttributes({ mediaType: mediaTypeValue })
						}
					/>

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
						options={[
							{ label: 'Small', value: 'small' },
							{ label: 'Medium', value: 'medium' },
							{ label: 'Large', value: 'large' },
							{ label: 'Full', value: 'full' },
						]}
						onChange={(containerHeightValue) =>
							setAttributes({ containerHeight: containerHeightValue })
						}
					/>

					<SelectControl
						label={__('Content Position', 'gutenberg-lab-blocks')}
						value={contentPosition}
						options={[
							{ label: 'Top Left', value: 'top-left' },
							{ label: 'Top Center', value: 'top-center' },
							{ label: 'Top Right', value: 'top-right' },
							{ label: 'Center Left', value: 'center-left' },
							{ label: 'Center Center', value: 'center-center' },
							{ label: 'Center Right', value: 'center-right' },
							{ label: 'Bottom Left', value: 'bottom-left' },
							{ label: 'Bottom Center', value: 'bottom-center' },
							{ label: 'Bottom Right', value: 'bottom-right' },
						]}
						onChange={(contentPositionValue) =>
							setAttributes({ contentPosition: contentPositionValue })
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>
				<div className="media-panel__media">
					{mediaType === 'image' ? (
						<>
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

							{videoUrl && (
								<video
									className="media-panel__video"
									src={videoUrl}
									autoPlay
									muted
									loop
									playsInline
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
