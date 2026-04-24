import { __, sprintf } from '@wordpress/i18n';
import {
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import {
	Button,
	BaseControl,
	ColorPicker,
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useEffect, useRef } from '@wordpress/element';

import {
	SliderArrowControlsPanel,
	SliderArrowPreview,
} from '../shared/slider-arrow-controls';
import { getVimeoVideoId } from '../shared/vimeo-url';
import './editor.scss';

const DESKTOP_POSITION_OPTIONS = [
	{ label: __( 'Left', 'gutenberg-lab-blocks' ), value: 'left' },
	{ label: __( 'Right', 'gutenberg-lab-blocks' ), value: 'right' },
];

const LAYOUT_STYLE_OPTIONS = [
	{ label: __( 'Split', 'gutenberg-lab-blocks' ), value: 'split' },
	{ label: __( 'Overlay', 'gutenberg-lab-blocks' ), value: 'overlay' },
	{ label: __( 'Overlap Card', 'gutenberg-lab-blocks' ), value: 'overlap' },
];

const MOBILE_POSITION_OPTIONS = [
	{ label: __( 'Top', 'gutenberg-lab-blocks' ), value: 'top' },
	{ label: __( 'Bottom', 'gutenberg-lab-blocks' ), value: 'bottom' },
];

const MEDIA_WIDTH_OPTIONS = [
	{ label: __( '25%', 'gutenberg-lab-blocks' ), value: '25' },
	{ label: __( '50%', 'gutenberg-lab-blocks' ), value: '50' },
	{ label: __( '75%', 'gutenberg-lab-blocks' ), value: '75' },
];

const SECTION_HEIGHT_OPTIONS = [
	{ label: __( 'Small', 'gutenberg-lab-blocks' ), value: 'small' },
	{ label: __( 'Medium', 'gutenberg-lab-blocks' ), value: 'medium' },
];

const MEDIA_TYPE_OPTIONS = [
	{ label: __( 'Image', 'gutenberg-lab-blocks' ), value: 'image' },
	{ label: __( 'Video', 'gutenberg-lab-blocks' ), value: 'video' },
	{ label: __( 'Slider', 'gutenberg-lab-blocks' ), value: 'slider' },
];

const VIDEO_SOURCE_OPTIONS = [
	{
		label: __( 'Uploaded video', 'gutenberg-lab-blocks' ),
		value: 'uploaded',
	},
	{ label: __( 'Vimeo URL', 'gutenberg-lab-blocks' ), value: 'vimeo' },
];

const ALLOWED_INNER_BLOCKS = [
	'core/heading',
	// Keep dynamic singular template content available in the split layout.
	'core/post-title',
	'core/post-excerpt',
	'core/paragraph',
	'core/list',
	'core/buttons',
	'core/image',
	'core/quote',
	'core/group',
	'core/separator',
	'core/details',
	'core/spacer',
];

const INNER_BLOCKS_TEMPLATE = [
	[
		'core/heading',
		{
			level: 2,
			placeholder: __( 'Add heading...', 'gutenberg-lab-blocks' ),
		},
	],
	[
		'core/paragraph',
		{
			placeholder: __( 'Add body text...', 'gutenberg-lab-blocks' ),
		},
	],
	[ 'core/buttons', {} ],
];

function normalizeGallerySelection( mediaItems ) {
	const items = Array.isArray( mediaItems ) ? mediaItems : [ mediaItems ];

	return items
		.filter( Boolean )
		.map( ( media ) => ( {
			id: media.id,
			url: media.url,
			alt: media.alt || '',
		} ) );
}

function getMediaPreviewLabel( mediaType ) {
	if ( 'video' === mediaType ) {
		return __( 'Select a video', 'gutenberg-lab-blocks' );
	}

	if ( 'slider' === mediaType ) {
		return __( 'Select gallery images', 'gutenberg-lab-blocks' );
	}

	return __( 'Select an image', 'gutenberg-lab-blocks' );
}

function getContentPanelStyleVars( contentBackgroundColor ) {
	if ( typeof contentBackgroundColor !== 'string' || ! contentBackgroundColor ) {
		return undefined;
	}

	return {
		'--split-content-overlay-panel-bg': contentBackgroundColor,
		'--split-content-overlay-panel-bg-reverse': contentBackgroundColor,
		'--split-content-overlap-card-background': contentBackgroundColor,
	};
}

export default function Edit( { attributes, setAttributes } ) {
	const {
		layoutStyle,
		mediaPositionDesktop,
		mediaPositionMobile,
		mediaWidth,
		sectionHeight,
		contentBackgroundColor,
		mediaType,
		mediaOnEdge,
		imageId,
		imageUrl,
		imageAlt,
		videoId,
		videoSource,
		videoUrl,
		vimeoUrl,
		posterImageId,
		posterImageUrl,
		posterImageAlt,
		galleryImages,
	} = attributes;
	const blockRef = useRef();
	const contentPanelStyleVars = getContentPanelStyleVars( contentBackgroundColor );
	const hasSliderArrows =
		'slider' === mediaType && galleryImages.length > 1;
	const isVimeoVideo = 'video' === mediaType && 'vimeo' === videoSource;
	const hasValidVimeoUrl = Boolean( getVimeoVideoId( vimeoUrl ) );
	const hasCompleteVimeoVideo = hasValidVimeoUrl && Boolean( posterImageUrl );

	const blockProps = useBlockProps( {
		ref: blockRef,
		className: [
			'split-content',
			`split-content--layout-${ layoutStyle }`,
			`split-content--desktop-${ mediaPositionDesktop }`,
			`split-content--mobile-${ mediaPositionMobile }`,
			`split-content--width-${ mediaWidth }`,
			`split-content--height-${ sectionHeight }`,
			mediaOnEdge ? 'split-content--edge' : 'split-content--contained',
		]
			.filter( Boolean )
			.join( ' ' ),
		style: contentPanelStyleVars,
	} );

	useEffect( () => {
		const blockElement = blockRef.current;

		if ( ! blockElement ) {
			return undefined;
		}

		const syncMediaHeight = () => {
			const mediaFrame = blockElement.querySelector( '.split-content__media-frame' );
			const contentFlow = blockElement.querySelector( '.split-content__content-flow' );
			const isDesktopSplitLayout =
				blockElement.classList.contains( 'split-content--layout-split' ) &&
				window.innerWidth > 1023;

			if ( ! mediaFrame || ! contentFlow || ! isDesktopSplitLayout ) {
				blockElement.style.removeProperty( '--split-content-media-target-height' );
				return;
			}

			const computedStyles = window.getComputedStyle( blockElement );
			const baselineHeight =
				parseFloat(
					computedStyles.getPropertyValue( '--split-content-media-min-height' )
				) || 0;
			const overhangHeight =
				parseFloat(
					computedStyles.getPropertyValue( '--split-content-media-overhang' )
				) || 0;
			const contentHeight = contentFlow.getBoundingClientRect().height;
			const targetHeight = Math.max(
				baselineHeight,
				Math.ceil( contentHeight + overhangHeight )
			);

			blockElement.style.setProperty(
				'--split-content-media-target-height',
				`${ targetHeight }px`
			);
		};

		const scheduleSync = () => {
			window.requestAnimationFrame( syncMediaHeight );
		};

		scheduleSync();

		if ( 'undefined' === typeof ResizeObserver ) {
			window.addEventListener( 'resize', scheduleSync );

			return () => {
				window.removeEventListener( 'resize', scheduleSync );
			};
		}

		const resizeObserver = new ResizeObserver( scheduleSync );
		resizeObserver.observe( blockElement );

		const contentFlow = blockElement.querySelector( '.split-content__content-flow' );

		if ( contentFlow ) {
			resizeObserver.observe( contentFlow );
		}

		window.addEventListener( 'resize', scheduleSync );

		return () => {
			resizeObserver.disconnect();
			window.removeEventListener( 'resize', scheduleSync );
		};
	}, [
		layoutStyle,
		mediaPositionDesktop,
		mediaPositionMobile,
		mediaWidth,
		sectionHeight,
		mediaType,
		mediaOnEdge,
		imageUrl,
		videoUrl,
		vimeoUrl,
		posterImageUrl,
		galleryImages,
	] );

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'split-content__content-flow',
		},
		{
			allowedBlocks: ALLOWED_INNER_BLOCKS,
			template: INNER_BLOCKS_TEMPLATE,
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

	const onSelectVideo = ( media ) => {
		setAttributes( {
			videoId: media.id,
			videoUrl: media.url,
			videoSource: 'uploaded',
			vimeoUrl: '',
		} );
	};

	const onSelectPoster = ( media ) => {
		setAttributes( {
			posterImageId: media.id,
			posterImageUrl: media.url,
			posterImageAlt: media.alt || '',
		} );
	};

	const onSelectGallery = ( mediaItems ) => {
		setAttributes( {
			galleryImages: normalizeGallerySelection( mediaItems ),
		} );
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

	const updateMediaType = ( nextMediaType ) => {
		setAttributes( {
			mediaType: nextMediaType,
			...( 'video' === nextMediaType
				? {}
				: {
						videoSource: 'uploaded',
						videoId: undefined,
						videoUrl: '',
						vimeoUrl: '',
						posterImageId: undefined,
						posterImageUrl: '',
						posterImageAlt: '',
				  } ),
		} );
	};

	const renderMediaUploader = () => {
		if ( 'video' === mediaType ) {
			if ( 'vimeo' === videoSource ) {
				return (
					<>
						<TextControl
							label={ __( 'Vimeo URL', 'gutenberg-lab-blocks' ) }
							value={ vimeoUrl }
							onChange={ ( nextVimeoUrl ) =>
								setAttributes( { vimeoUrl: nextVimeoUrl } )
							}
							help={
								hasValidVimeoUrl
									? __(
											'Accepted formats include standard Vimeo and player.vimeo.com links.',
											'gutenberg-lab-blocks'
									  )
									: __(
											'Add a valid Vimeo URL to complete this media panel.',
											'gutenberg-lab-blocks'
									  )
							}
						/>
						<MediaUploadCheck>
							<MediaUpload
								onSelect={ onSelectPoster }
								allowedTypes={ [ 'image' ] }
								value={ posterImageId }
								render={ ( { open } ) => (
									<Button variant="secondary" onClick={ open }>
										{ posterImageUrl
											? __(
													'Replace poster image',
													'gutenberg-lab-blocks'
											  )
											: __(
													'Select poster image',
													'gutenberg-lab-blocks'
											  ) }
									</Button>
								) }
							/>
						</MediaUploadCheck>
					</>
				);
			}

			return (
				<MediaUploadCheck>
					<MediaUpload
						onSelect={ onSelectVideo }
						allowedTypes={ [ 'video' ] }
						value={ videoId }
						render={ ( { open } ) => (
							<Button variant="secondary" onClick={ open }>
								{ videoUrl
									? __( 'Replace video', 'gutenberg-lab-blocks' )
									: __( 'Select video', 'gutenberg-lab-blocks' ) }
							</Button>
						) }
					/>
				</MediaUploadCheck>
			);
		}

		if ( 'slider' === mediaType ) {
			return (
				<MediaUploadCheck>
					<MediaUpload
						onSelect={ onSelectGallery }
						allowedTypes={ [ 'image' ] }
						multiple
						gallery
						value={ galleryImages.map( ( image ) => image.id ) }
						render={ ( { open } ) => (
							<Button variant="secondary" onClick={ open }>
								{ galleryImages.length
									? __(
											'Replace gallery images',
											'gutenberg-lab-blocks'
									  )
									: __(
											'Select gallery images',
											'gutenberg-lab-blocks'
									  ) }
							</Button>
						) }
					/>
				</MediaUploadCheck>
			);
		}

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
	};

	const renderMediaPreview = () => {
		if ( isVimeoVideo ) {
			if ( posterImageUrl ) {
				return (
					<img
						className="split-content__media-asset"
						src={ posterImageUrl }
						alt={ posterImageAlt }
					/>
				);
			}

			return (
				<div className="split-content__media-placeholder">
					<p>{ __( 'Select a poster image for Vimeo', 'gutenberg-lab-blocks' ) }</p>
				</div>
			);
		}

		if ( 'video' === mediaType && videoUrl ) {
			return (
				<video
					className="split-content__media-asset"
					src={ videoUrl }
					autoPlay
					muted
					loop
					playsInline
				/>
			);
		}

		if ( 'slider' === mediaType && galleryImages.length ) {
			return (
				<div className="split-content__slider-preview vvm-slider-surface">
					<img
						className="split-content__media-asset"
						src={ galleryImages[ 0 ].url }
						alt={ galleryImages[ 0 ].alt }
					/>
					<SliderArrowPreview
						attributes={ attributes }
						className="split-content__slider-controls"
						buttonClassName="split-content__slider-button"
						defaultPreset="bottom-right"
						isVisible={ hasSliderArrows }
					/>
					<span className="split-content__slider-badge">
						{ sprintf(
							/* translators: %d is the number of selected gallery images. */
							__( '%d slides', 'gutenberg-lab-blocks' ),
							galleryImages.length
						) }
					</span>
				</div>
			);
		}

		if ( 'image' === mediaType && imageUrl ) {
			return (
				<img
					className="split-content__media-asset"
					src={ imageUrl }
					alt={ imageAlt }
				/>
			);
		}

		return (
			<div className="split-content__media-placeholder">
				<p>{ getMediaPreviewLabel( mediaType ) }</p>
			</div>
		);
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Layout Settings', 'gutenberg-lab-blocks' ) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __( 'Layout Style', 'gutenberg-lab-blocks' ) }
						value={ layoutStyle }
						options={ LAYOUT_STYLE_OPTIONS }
						onChange={ ( value ) => setAttributes( { layoutStyle: value } ) }
					/>
					<SelectControl
						label={ __( 'Media Position - Desktop', 'gutenberg-lab-blocks' ) }
						value={ mediaPositionDesktop }
						options={ DESKTOP_POSITION_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { mediaPositionDesktop: value } )
						}
					/>
					<SelectControl
						label={ __( 'Media Position - Mobile', 'gutenberg-lab-blocks' ) }
						value={ mediaPositionMobile }
						options={ MOBILE_POSITION_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { mediaPositionMobile: value } )
						}
					/>
					<SelectControl
						label={ __( 'Section Height', 'gutenberg-lab-blocks' ) }
						value={ sectionHeight }
						options={ SECTION_HEIGHT_OPTIONS }
						onChange={ ( value ) => setAttributes( { sectionHeight: value } ) }
						help={ __(
							'Sets the baseline media height. If the content grows taller, the media will still overhang it slightly on desktop split layouts.',
							'gutenberg-lab-blocks'
						) }
					/>
					{ 'split' !== layoutStyle && (
						<BaseControl
							label={ __( 'Content Background', 'gutenberg-lab-blocks' ) }
							help={ __(
								'Choose the panel color. Use the alpha slider to keep the panel translucent, or reset to return to the default white treatment.',
								'gutenberg-lab-blocks'
							) }
						>
							<ColorPicker
								color={ contentBackgroundColor || '#ffffffff' }
								enableAlpha
								onChange={ ( value ) =>
									setAttributes( { contentBackgroundColor: value } )
								}
							/>
							<Button
								variant="tertiary"
								onClick={ () =>
									setAttributes( { contentBackgroundColor: '' } )
								}
								disabled={ ! contentBackgroundColor }
							>
								{ __( 'Reset content background', 'gutenberg-lab-blocks' ) }
							</Button>
						</BaseControl>
					) }
					{ 'split' === layoutStyle && (
						<>
							<SelectControl
								label={ __( 'Media Width', 'gutenberg-lab-blocks' ) }
								value={ mediaWidth }
								options={ MEDIA_WIDTH_OPTIONS }
								onChange={ ( value ) =>
									setAttributes( { mediaWidth: value } )
								}
							/>
							<ToggleControl
								label={ __( 'Media On Edge', 'gutenberg-lab-blocks' ) }
								checked={ mediaOnEdge }
								onChange={ ( value ) =>
									setAttributes( { mediaOnEdge: value } )
								}
								help={ __(
									'Let the media break out to the viewport edge while the content stays aligned to the theme gutters.',
									'gutenberg-lab-blocks'
								) }
							/>
						</>
					) }
					{ 'overlay' === layoutStyle && (
						<p className="split-content__control-help">
							{ __(
								'Overlay mode uses full-width media and places a translucent content panel on top. Desktop position controls which side the panel sits on.',
								'gutenberg-lab-blocks'
							) }
						</p>
					) }
					{ 'overlap' === layoutStyle && (
						<p className="split-content__control-help">
							{ __(
								'Overlap Card uses a floating white card that overlaps a more portrait media panel. Desktop position controls whether the image sits on the left or right.',
								'gutenberg-lab-blocks'
							) }
						</p>
					) }
				</PanelBody>
				<PanelBody
					title={ __( 'Media Settings', 'gutenberg-lab-blocks' ) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __( 'Media Type', 'gutenberg-lab-blocks' ) }
						value={ mediaType }
						options={ MEDIA_TYPE_OPTIONS }
						onChange={ updateMediaType }
					/>
					{ 'video' === mediaType ? (
						<>
							<SelectControl
								label={ __( 'Video source', 'gutenberg-lab-blocks' ) }
								value={ videoSource }
								options={ VIDEO_SOURCE_OPTIONS }
								onChange={ updateVideoSource }
							/>
							{ 'vimeo' === videoSource && ! hasCompleteVimeoVideo ? (
								<p className="components-base-control__help">
									{ __(
										'Complete the Vimeo URL and poster image before this block can render the player on the frontend.',
										'gutenberg-lab-blocks'
									) }
								</p>
							) : null }
						</>
					) : null }
					{ renderMediaUploader() }
				</PanelBody>
				{ hasSliderArrows ? (
					<SliderArrowControlsPanel
						attributes={ attributes }
						setAttributes={ setAttributes }
						defaultPreset="bottom-right"
						initialOpen={ false }
					/>
				) : null }
			</InspectorControls>

			<section { ...blockProps }>
				<div className="split-content__grid">
					<div className="split-content__media">
						<div className="split-content__editor-actions">
							{ renderMediaUploader() }
						</div>
						<div className="split-content__media-frame">
							{ renderMediaPreview() }
						</div>
					</div>
					<div className="split-content__content">
						<div { ...innerBlocksProps } />
					</div>
				</div>
			</section>
		</>
	);
}
