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

const MEDIA_TYPE_OPTIONS = [
	{ label: __( 'Image', 'gutenberg-lab-blocks' ), value: 'image' },
	{ label: __( 'Video', 'gutenberg-lab-blocks' ), value: 'video' },
];

function getResolvedActionLabel( mediaType, actionLabel ) {
	if ( actionLabel ) {
		return actionLabel;
	}

	return 'video' === mediaType
		? __( 'Play', 'gutenberg-lab-blocks' )
		: __( 'View', 'gutenberg-lab-blocks' );
}

export default function Edit( { attributes, setAttributes } ) {
	const {
		mediaType,
		imageId,
		imageUrl,
		imageAlt,
		videoId,
		videoUrl,
		posterImageId,
		posterImageUrl,
		posterImageAlt,
		thumbnailLabel,
		thumbnailAction,
	} = attributes;

	const previewImageUrl =
		'video' === mediaType ? posterImageUrl || '' : imageUrl || '';
	const previewAlt =
		'video' === mediaType ? posterImageAlt || '' : imageAlt || '';
	const resolvedActionLabel = getResolvedActionLabel(
		mediaType,
		thumbnailAction
	);

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
						videoUrl: '',
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
			videoUrl: media.url,
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

					<MediaUploadCheck>
						<MediaUpload
							onSelect={
								'video' === mediaType ? onSelectVideo : onSelectImage
							}
							allowedTypes={ 'video' === mediaType ? [ 'video' ] : [ 'image' ] }
							value={ 'video' === mediaType ? videoId : imageId }
							render={ ( { open } ) => (
								<Button variant="secondary" onClick={ open }>
									{ 'video' === mediaType
										? videoUrl
											? __(
													'Replace video',
													'gutenberg-lab-blocks'
											  )
											: __(
													'Select video',
													'gutenberg-lab-blocks'
											  )
										: imageUrl
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

					{ ( imageUrl || videoUrl ) ? (
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
						</>
					) : null }

					<TextControl
						label={ __( 'Thumbnail label', 'gutenberg-lab-blocks' ) }
						value={ thumbnailLabel }
						onChange={ ( value ) =>
							setAttributes( { thumbnailLabel: value } )
						}
					/>
					<TextControl
						label={ __( 'Thumbnail action', 'gutenberg-lab-blocks' ) }
						value={ thumbnailAction }
						onChange={ ( value ) =>
							setAttributes( { thumbnailAction: value } )
						}
						help={ __(
							'Leave blank to use “View” for images or “Play” for video.',
							'gutenberg-lab-blocks'
						) }
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
													? __(
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
						<p className="vvm-villa-gallery-hero__slide-action">
							{ resolvedActionLabel }
						</p>
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
