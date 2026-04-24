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
	TextControl,
	ToggleControl,
} from '@wordpress/components';

import './editor.scss';
import { getVimeoVideoId } from '../shared/vimeo-url';

const MEDIA_TYPE_OPTIONS = [
	{ label: 'Image', value: 'image' },
	{ label: 'Video', value: 'video' },
];

const VIDEO_SOURCE_OPTIONS = [
	{ label: __( 'Uploaded video', 'gutenberg-lab-blocks' ), value: 'uploaded' },
	{ label: __( 'Vimeo URL', 'gutenberg-lab-blocks' ), value: 'vimeo' },
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

const OVERLAY_GRADIENT_STYLE_OPTIONS = [
	{ label: 'Barbados Green', value: 'brand-green' },
	{ label: 'Dark Vignette', value: 'dark-vignette' },
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

const ACCENT_BORDER_OPTIONS = [
	{ label: 'None', value: 'none' },
	{ label: 'Top', value: 'top' },
	{ label: 'Bottom', value: 'bottom' },
	{ label: 'Top and Bottom', value: 'both' },
];

const ATMOSPHERE_EDGE_OPTIONS = [
	{ label: 'None', value: 'none' },
	{ label: 'Fog Top', value: 'fog-top' },
	{ label: 'Dark Green Bottom Edge', value: 'fog-bottom' },
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
		videoSource,
		videoUrl,
		vimeoUrl,
		fallbackImageId,
		fallbackImageUrl,
		fallbackImageAlt,
		darkOverlay,
		overlayGradientStyle,
		contentStyle,
		containerHeight,
		contentPosition,
		contentWidth,
		accentBorder,
		atmosphereEdge,
		curtainParallax,
		align,
		style,
	} = attributes;
	const blockGap = resolveBlockGapPreviewValue( style?.spacing?.blockGap );
	const isVimeoVideo = 'video' === mediaType && 'vimeo' === videoSource;
	const hasValidVimeoUrl = Boolean( getVimeoVideoId( vimeoUrl ) );
	const hasCompleteVimeoVideo = hasValidVimeoUrl && Boolean( fallbackImageUrl );

	const blockProps = useBlockProps({
		className: [
			'media-panel',
			`media-panel--height-${containerHeight}`,
			`media-panel--content-style-${contentStyle}`,
			`media-panel--position-${contentPosition}`,
			`media-panel--content-width-${contentWidth}`,
			'none' !== accentBorder
				? `media-panel--accent-border-${accentBorder}`
				: '',
			'none' !== atmosphereEdge
				? `vvm-atmosphere-edge vvm-atmosphere-edge--${atmosphereEdge}`
				: '',
			align ? '' : 'alignfull',
			darkOverlay ? 'media-panel--dark-overlay' : '',
			darkOverlay
				? `media-panel--overlay-style-${overlayGradientStyle}`
				: '',
			curtainParallax ? 'media-panel--curtain-parallax' : '',
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
			videoSource: 'uploaded',
			vimeoUrl: '',
		});
	};

	const onSelectFallbackImage = (media) => {
		setAttributes({
			fallbackImageId: media.id,
			fallbackImageUrl: media.url,
			fallbackImageAlt: media.alt || '',
		});
	};

	const updateVideoSource = ( nextVideoSource ) => {
		setAttributes( {
			videoSource: nextVideoSource,
			...( 'vimeo' === nextVideoSource
				? {
						videoId: undefined,
						videoUrl: '',
				  }
				: {
						vimeoUrl: '',
				  } ),
		} );
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
							setAttributes({
								mediaType: mediaTypeValue,
								...( 'video' === mediaTypeValue
									? {}
									: {
											videoSource: 'uploaded',
											videoId: undefined,
											videoUrl: '',
											vimeoUrl: '',
											fallbackImageId: undefined,
											fallbackImageUrl: '',
											fallbackImageAlt: '',
									  } ),
							})
						}
					/>

					{mediaType === 'video' && (
						<>
							<SelectControl
								label={__( 'Video source', 'gutenberg-lab-blocks' )}
								value={videoSource}
								options={VIDEO_SOURCE_OPTIONS}
								onChange={updateVideoSource}
							/>

							{ 'uploaded' === videoSource ? (
								<p className="components-base-control__help">
									{__(
										'Uploaded videos keep the current native HTML video behavior.',
										'gutenberg-lab-blocks'
									)}
								</p>
							) : (
								<TextControl
									label={__( 'Vimeo URL', 'gutenberg-lab-blocks' )}
									value={vimeoUrl}
									onChange={(nextVimeoUrl) =>
										setAttributes({ vimeoUrl: nextVimeoUrl })
									}
									help={
										hasValidVimeoUrl
											? __(
													'Accepted formats include standard Vimeo and player.vimeo.com links.',
													'gutenberg-lab-blocks'
											  )
											: __(
													'Add a valid Vimeo URL to complete this video source.',
													'gutenberg-lab-blocks'
											  )
									}
								/>
							)}

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
								<p className="components-base-control__help">
									{__(
										'Vimeo requires a poster image so the block can stay polished while the player loads or if autoplay is blocked.',
										'gutenberg-lab-blocks'
									)}
								</p>
							</div>

							{isVimeoVideo && !hasCompleteVimeoVideo && (
								<p className="components-base-control__help">
									{__(
										'Complete the Vimeo URL and poster image before this video can render on the frontend.',
										'gutenberg-lab-blocks'
									)}
								</p>
							)}
						</>
					)}

					<ToggleControl
						label={__('Gradient Overlay', 'gutenberg-lab-blocks')}
						checked={darkOverlay}
						onChange={(darkOverlayValue) =>
							setAttributes({ darkOverlay: darkOverlayValue })
						}
						help={__(
							'Adds a full-height readability gradient over the media.',
							'gutenberg-lab-blocks'
						)}
					/>

					{darkOverlay && (
						<SelectControl
							label={__('Overlay Gradient Style', 'gutenberg-lab-blocks')}
							value={overlayGradientStyle}
							options={OVERLAY_GRADIENT_STYLE_OPTIONS}
							onChange={(overlayGradientStyleValue) =>
								setAttributes({
									overlayGradientStyle: overlayGradientStyleValue,
								})
							}
							help={__(
								'Choose between the Barbados green overlay and the darker Figma-style vignette for header-safe hero images.',
								'gutenberg-lab-blocks'
							)}
						/>
					)}

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
						label={__('Accent Border', 'gutenberg-lab-blocks')}
						value={accentBorder}
						options={ACCENT_BORDER_OPTIONS}
						onChange={(accentBorderValue) =>
							setAttributes({ accentBorder: accentBorderValue })
						}
						help={__(
							'Adds an 8px gold rule at the top, bottom, or both edges of the panel.',
							'gutenberg-lab-blocks'
						)}
					/>

					<SelectControl
						label={__('Atmosphere Edge', 'gutenberg-lab-blocks')}
						value={atmosphereEdge}
						options={ATMOSPHERE_EDGE_OPTIONS}
						onChange={(atmosphereEdgeValue) =>
							setAttributes({ atmosphereEdge: atmosphereEdgeValue })
						}
						help={__(
							'Adds a soft white fog at the top or a dark green cover at the bottom edge of the media.',
							'gutenberg-lab-blocks'
						)}
					/>

					<ToggleControl
						label={__('Curtain Parallax', 'gutenberg-lab-blocks')}
						checked={curtainParallax}
						onChange={(curtainParallaxValue) =>
							setAttributes({
								curtainParallax: curtainParallaxValue,
							})
						}
						help={__(
							'Keeps the panel fixed while the sections below scroll over it on the frontend.',
							'gutenberg-lab-blocks'
						)}
					/>
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>
				<div className="media-panel__stage">
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
						) : 'uploaded' === videoSource ? (
							<MediaUploadCheck>
								<MediaUpload
									onSelect={onSelectVideo}
									allowedTypes={['video']}
									value={videoId}
									render={({ open }) => (
										<Button variant="secondary" onClick={open}>
											{videoUrl && 'uploaded' === videoSource
												? __('Replace video', 'gutenberg-lab-blocks')
												: __('Select video', 'gutenberg-lab-blocks')}
										</Button>
									)}
								/>
							</MediaUploadCheck>
						) : (
							<Button
								variant="secondary"
								disabled
							>
								{__(
									'Vimeo source configured in the sidebar',
									'gutenberg-lab-blocks'
								)}
							</Button>
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
								{ 'uploaded' === videoSource && videoUrl && (
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

								{ isVimeoVideo && fallbackImageUrl && (
									<img
										className="media-panel__image"
										src={fallbackImageUrl}
										alt={fallbackImageAlt}
									/>
								)}

								{ 'uploaded' === videoSource && !videoUrl && fallbackImageUrl && (
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
			</div>
		</>
	);
}
