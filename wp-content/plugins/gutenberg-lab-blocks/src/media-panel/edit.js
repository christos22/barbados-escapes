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

const CONTENT_STYLE_OPTIONS = [
	{ label: 'Overlay', value: 'overlay' },
	{ label: 'Boxed', value: 'boxed' },
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

const ATMOSPHERE_EDGE_OPTIONS = [
	{ label: 'None', value: 'none' },
	{ label: 'Fog Top', value: 'fog-top' },
	{ label: 'Fog Bottom', value: 'fog-bottom' },
];

const ALLOWED_INNER_BLOCKS = [
	'core/heading',
	// Allow template authors to pull the current singular item's title.
	'core/post-title',
	// The package excerpt is native post data, so this stays dynamic in templates.
	'core/post-excerpt',
	// Package layouts often need the structured price + CTA block in the hero.
	'gutenberg-lab-blocks/package-meta',
	'core/paragraph',
	'core/list',
	'core/buttons',
	'core/image',
	'core/quote',
	'core/group',
	'core/separator',
	'core/details',
	'core/spacer',
	'gutenberg-lab-blocks/villa-hero-search',
];

function normalizeSpacingPresetSlug( spacingSlug ) {
	if ( typeof spacingSlug !== 'string' || spacingSlug === '' ) {
		return undefined;
	}

	return spacingSlug.trim().toLowerCase().replace( /^([0-9]+)([a-z])/, '$1-$2' );
}

function resolveBlockGapPreviewValue( blockGap ) {
	if ( typeof blockGap !== 'string' || blockGap === '' ) {
		return undefined;
	}

	// Gutenberg stores preset picks as tokens like `var:preset|spacing|2xl`.
	// The overlay stack reads a CSS custom property, so we resolve the token to
	// the matching preset variable for editor parity.
	if ( blockGap.startsWith( 'var:preset|spacing|' ) ) {
		const spacingSlug = normalizeSpacingPresetSlug(
			blockGap.replace( 'var:preset|spacing|', '' )
		);

		return spacingSlug
			? `var(--wp--preset--spacing--${ spacingSlug })`
			: undefined;
	}

	return blockGap;
}

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
		contentStyle,
		containerHeight,
		contentPosition,
		contentWidth,
		atmosphereEdge,
		align,
		style,
	} = attributes;
	const blockGap = resolveBlockGapPreviewValue( style?.spacing?.blockGap );

	const blockProps = useBlockProps({
		className: [
			'media-panel',
			`media-panel--height-${containerHeight}`,
			`media-panel--content-style-${contentStyle}`,
			`media-panel--position-${contentPosition}`,
			`media-panel--content-width-${contentWidth}`,
			'none' !== atmosphereEdge
				? `vvm-atmosphere-edge vvm-atmosphere-edge--${atmosphereEdge}`
				: '',
			align ? '' : 'alignfull',
			darkOverlay ? 'media-panel--dark-overlay' : '',
		]
			.filter(Boolean)
			.join(' '),
		style: blockGap
			? {
					'--wp--style--block-gap': blockGap,
				}
			: undefined,
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
						<div className="media-panel__inspector-upload">
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
						</div>
					)}

					<ToggleControl
						label={__('Gradient Overlay', 'gutenberg-lab-blocks')}
						checked={darkOverlay}
						onChange={(darkOverlayValue) =>
							setAttributes({ darkOverlay: darkOverlayValue })
						}
					/>

					<SelectControl
						label={__('Content Style', 'gutenberg-lab-blocks')}
						value={contentStyle}
						options={CONTENT_STYLE_OPTIONS}
						onChange={(contentStyleValue) =>
							setAttributes({ contentStyle: contentStyleValue })
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

					<SelectControl
						label={__('Atmosphere Edge', 'gutenberg-lab-blocks')}
						value={atmosphereEdge}
						options={ATMOSPHERE_EDGE_OPTIONS}
						onChange={(atmosphereEdgeValue) =>
							setAttributes({ atmosphereEdge: atmosphereEdgeValue })
						}
						help={__(
							'Adds a soft fog-style fade at the top or bottom edge of the media. This is decorative and can be reused on future layouts.',
							'gutenberg-lab-blocks'
						)}
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
