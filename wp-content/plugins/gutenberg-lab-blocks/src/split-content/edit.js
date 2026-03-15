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
	PanelBody,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';

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

const MEDIA_TYPE_OPTIONS = [
	{ label: __( 'Image', 'gutenberg-lab-blocks' ), value: 'image' },
	{ label: __( 'Video', 'gutenberg-lab-blocks' ), value: 'video' },
	{ label: __( 'Slider', 'gutenberg-lab-blocks' ), value: 'slider' },
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

export default function Edit( { attributes, setAttributes } ) {
	const {
		layoutStyle,
		mediaPositionDesktop,
		mediaPositionMobile,
		mediaWidth,
		mediaType,
		mediaOnEdge,
		imageId,
		imageUrl,
		imageAlt,
		videoId,
		videoUrl,
		galleryImages,
	} = attributes;

	const blockProps = useBlockProps( {
		className: [
			'split-content',
			`split-content--layout-${ layoutStyle }`,
			`split-content--desktop-${ mediaPositionDesktop }`,
			`split-content--mobile-${ mediaPositionMobile }`,
			`split-content--width-${ mediaWidth }`,
			mediaOnEdge ? 'split-content--edge' : 'split-content--contained',
		]
			.filter( Boolean )
			.join( ' ' ),
	} );

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
		} );
	};

	const onSelectGallery = ( mediaItems ) => {
		setAttributes( {
			galleryImages: normalizeGallerySelection( mediaItems ),
		} );
	};

	const renderMediaUploader = () => {
		if ( 'video' === mediaType ) {
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
				<div className="split-content__slider-preview">
					<img
						className="split-content__media-asset"
						src={ galleryImages[ 0 ].url }
						alt={ galleryImages[ 0 ].alt }
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
						onChange={ ( value ) => setAttributes( { mediaType: value } ) }
					/>
					{ renderMediaUploader() }
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<div className="split-content__grid">
					<div className="split-content__media">
						<div className="split-content__editor-actions">
							{ renderMediaUploader() }
						</div>
						{ renderMediaPreview() }
					</div>
					<div className="split-content__content">
						<div { ...innerBlocksProps } />
					</div>
				</div>
			</section>
		</>
	);
}
