import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	SelectControl,
	TextControl,
} from '@wordpress/components';

import './editor.scss';
import { getVimeoVideoId } from '../shared/vimeo-url';

const MEDIA_TYPE_OPTIONS = [
	{ label: __( 'Image', 'gutenberg-lab-blocks' ), value: 'image' },
	{ label: __( 'Video', 'gutenberg-lab-blocks' ), value: 'video' },
];

const VIDEO_SOURCE_OPTIONS = [
	{
		label: __( 'Uploaded video', 'gutenberg-lab-blocks' ),
		value: 'uploaded',
	},
	{ label: __( 'Vimeo URL', 'gutenberg-lab-blocks' ), value: 'vimeo' },
];

export default function Edit( { attributes, setAttributes } ) {
	const {
		mediaType,
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
		thumbnailLabel,
	} = attributes;
	const isVimeoVideo = 'video' === mediaType && 'vimeo' === videoSource;
	const hasValidVimeoUrl = Boolean( getVimeoVideoId( vimeoUrl ) );
	const hasCompleteVimeoVideo = hasValidVimeoUrl && Boolean( posterImageUrl );

	const previewImageUrl =
		'video' === mediaType ? posterImageUrl || '' : imageUrl || '';
	const previewAlt =
		'video' === mediaType ? posterImageAlt || '' : imageAlt || '';

	const blockProps = useBlockProps( {
		className: 'vvm-villa-gallery-hero__slide-editor',
	} );

	const updateMediaType = ( value ) => {
		setAttributes( {
			mediaType: value,
			...( 'video' === value
				? {
						imageId: undefined,
						imageUrl: '',
						imageAlt: '',
				  }
				: {
						videoId: undefined,
						videoSource: 'uploaded',
						videoUrl: '',
						vimeoUrl: '',
						posterImageId: undefined,
						posterImageUrl: '',
						posterImageAlt: '',
				  } ),
		} );
	};

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
			videoSource: 'uploaded',
			videoUrl: media.url,
			vimeoUrl: '',
		} );
	};

	const updateVideoSource = ( value ) => {
		setAttributes( {
			videoSource: value,
			...( 'vimeo' === value
				? {
						videoId: undefined,
						videoUrl: '',
				  }
				: {
						vimeoUrl: '',
				  } ),
		} );
	};

	const onSelectPoster = ( media ) => {
		setAttributes( {
			posterImageId: media.id,
			posterImageUrl: media.url,
			posterImageAlt: media.alt || '',
		} );
	};

	const removeMedia = () => {
		setAttributes(
			'video' === mediaType
				? {
						videoId: undefined,
						videoUrl: '',
						vimeoUrl: '',
				  }
				: {
						imageId: undefined,
						imageUrl: '',
						imageAlt: '',
				  }
		);
	};

	const removePoster = () => {
		setAttributes( {
			posterImageId: undefined,
			posterImageUrl: '',
			posterImageAlt: '',
		} );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Slide settings', 'gutenberg-lab-blocks' ) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __( 'Media type', 'gutenberg-lab-blocks' ) }
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
							{ 'uploaded' === videoSource ? (
								<MediaUploadCheck>
									<MediaUpload
										onSelect={ onSelectVideo }
										allowedTypes={ [ 'video' ] }
										value={ videoId }
										render={ ( { open } ) => (
											<Button variant="secondary" onClick={ open }>
												{ videoUrl
													? __(
															'Replace video',
															'gutenberg-lab-blocks'
													  )
													: __(
															'Select video',
															'gutenberg-lab-blocks'
													  ) }
											</Button>
										) }
									/>
								</MediaUploadCheck>
							) : (
								<TextControl
									label={ __( 'Vimeo URL', 'gutenberg-lab-blocks' ) }
									value={ vimeoUrl }
									onChange={ ( value ) =>
										setAttributes( { vimeoUrl: value } )
									}
									help={
										hasValidVimeoUrl
											? __(
													'Accepted formats include standard Vimeo and player.vimeo.com links.',
													'gutenberg-lab-blocks'
											  )
											: __(
													'Add a valid Vimeo URL to complete this slide.',
													'gutenberg-lab-blocks'
											  )
									}
								/>
							) }
						</>
					) : (
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
											: __(
													'Select image',
													'gutenberg-lab-blocks'
											  ) }
									</Button>
								) }
							/>
						</MediaUploadCheck>
					)}

					{ ( imageUrl || videoUrl || vimeoUrl ) ? (
						<Button variant="link" isDestructive onClick={ removeMedia }>
							{ __( 'Remove media', 'gutenberg-lab-blocks' ) }
						</Button>
					) : null }

					{ 'video' === mediaType ? (
						<>
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

							{ posterImageUrl ? (
								<Button
									variant="link"
									isDestructive
									onClick={ removePoster }
								>
									{ __( 'Remove poster image', 'gutenberg-lab-blocks' ) }
								</Button>
							) : null }

							{ ! hasCompleteVimeoVideo && isVimeoVideo ? (
								<p className="components-base-control__help">
									{ __(
										'Vimeo slides need both a valid Vimeo URL and a poster image before they can render in the gallery.',
										'gutenberg-lab-blocks'
									) }
								</p>
							) : null }
						</>
					) : null }

						<TextControl
							label={ __( 'Thumbnail label', 'gutenberg-lab-blocks' ) }
							value={ thumbnailLabel }
							onChange={ ( value ) =>
								setAttributes( { thumbnailLabel: value } )
							}
						/>
					</PanelBody>
				</InspectorControls>

			<article { ...blockProps }>
				<div className="vvm-villa-gallery-hero__slide-card">
					<div
						className={ [
							'vvm-villa-gallery-hero__slide-preview',
							previewImageUrl ? '' : 'is-placeholder',
							'video' === mediaType ? 'is-video' : '',
						]
							.filter( Boolean )
							.join( ' ' ) }
					>
						{ previewImageUrl ? (
							<img src={ previewImageUrl } alt={ previewAlt } />
						) : 'video' === mediaType && 'vimeo' === videoSource ? (
							<div className="vvm-villa-gallery-hero__slide-placeholder">
								<Button variant="secondary" disabled>
									{ __(
										'Add Vimeo URL in the sidebar',
										'gutenberg-lab-blocks'
									) }
								</Button>
							</div>
						) : (
							<MediaUploadCheck>
								<MediaUpload
									onSelect={
										'video' === mediaType ? onSelectVideo : onSelectImage
									}
									allowedTypes={
										'video' === mediaType ? [ 'video' ] : [ 'image' ]
									}
									value={ 'video' === mediaType ? videoId : imageId }
									render={ ( { open } ) => (
										<div className="vvm-villa-gallery-hero__slide-placeholder">
											<Button variant="secondary" onClick={ open }>
												{ 'video' === mediaType
													? 'vimeo' === videoSource
														? __(
																'Add Vimeo URL in the sidebar',
																'gutenberg-lab-blocks'
														  )
														: __(
																'Select video',
																'gutenberg-lab-blocks'
														  )
													: __(
															'Select image',
															'gutenberg-lab-blocks'
													  ) }
											</Button>
										</div>
									) }
								/>
							</MediaUploadCheck>
						) }

						{ 'video' === mediaType ? (
							<span
								className="vvm-villa-gallery-hero__slide-play-badge"
								aria-hidden="true"
							>
								▶
							</span>
						) : null }
					</div>

					<div className="vvm-villa-gallery-hero__slide-copy">
						<p className="vvm-villa-gallery-hero__slide-title">
							{ thumbnailLabel ||
								__( 'Add a thumbnail label', 'gutenberg-lab-blocks' ) }
						</p>
						<p className="vvm-villa-gallery-hero__slide-caption">
							{ 'video' === mediaType
								? __(
										'This item swaps the main stage to inline video when active.',
										'gutenberg-lab-blocks'
								  )
								: __(
										'This item swaps the main stage image when active.',
										'gutenberg-lab-blocks'
								  ) }
						</p>
					</div>
				</div>
			</article>
		</>
	);
}
