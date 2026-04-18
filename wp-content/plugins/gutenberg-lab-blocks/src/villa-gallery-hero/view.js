import Splide from '@splidejs/splide';

const REDUCED_MOTION_MEDIA_QUERY = '(prefers-reduced-motion: reduce)';
const THUMB_ACTIVE_CLASS = 'vvm-villa-gallery-hero__thumb-slide--active';
const FULL_WIDTH_THUMB_MAX = 5;

function getManagedVideoToggle( video ) {
	return (
		video?.parentElement?.querySelector( '[data-villa-gallery-video-toggle]' ) ??
		null
	);
}

function setManagedVideoToggleVisibility( video, isVisible ) {
	const toggle = getManagedVideoToggle( video );

	if ( ! toggle ) {
		return;
	}

	toggle.hidden = ! isVisible;
	toggle.setAttribute( 'aria-hidden', isVisible ? 'false' : 'true' );
}

function showManagedVideoFallback( video ) {
	if ( ! video ) {
		return;
	}

	video.controls = false;
	setManagedVideoToggleVisibility( video, true );
}

function hideManagedVideoFallback( video ) {
	if ( ! video ) {
		return;
	}

	setManagedVideoToggleVisibility( video, false );
}

function resetManagedVideo( video, { showFallback = false } = {} ) {
	if ( ! video ) {
		return;
	}

	video.pause?.();

	try {
		video.currentTime = 0;
	} catch ( error ) {
		// Some browsers guard the currentTime setter while metadata loads.
	}

	video.controls = false;

	if ( showFallback ) {
		showManagedVideoFallback( video );
		return;
	}

	hideManagedVideoFallback( video );
}

function attemptManagedVideoAutoplay( video ) {
	if ( ! video ) {
		return;
	}

	video.controls = false;
	hideManagedVideoFallback( video );

	const playPromise = video.play?.();

	if ( playPromise && 'function' === typeof playPromise.catch ) {
		playPromise.catch( () => {
			showManagedVideoFallback( video );
		} );
	}
}

function playManagedVideoFromUserAction( video ) {
	if ( ! video ) {
		return;
	}

	hideManagedVideoFallback( video );
	video.controls = true;

	const playPromise = video.play?.();

	if ( playPromise && 'function' === typeof playPromise.catch ) {
		playPromise.catch( () => {
			showManagedVideoFallback( video );
		} );
	}

	window.requestAnimationFrame( () => {
		video.focus?.();
	} );
}

function bindManagedVideoControls( rootElement ) {
	rootElement
		.querySelectorAll( '[data-villa-gallery-video-toggle]' )
		.forEach( ( button ) => {
			if ( 'true' === button.dataset.villaGalleryVideoBound ) {
				return;
			}

			button.dataset.villaGalleryVideoBound = 'true';

			button.addEventListener( 'click', () => {
				const video =
					button.parentElement?.querySelector(
						'[data-villa-gallery-video], [data-villa-gallery-static-video]'
					) ?? null;

				playManagedVideoFromUserAction( video );
			} );
		} );
}

function bindReducedMotionPreference( mediaQuery, syncCallback ) {
	if ( 'function' === typeof mediaQuery.addEventListener ) {
		mediaQuery.addEventListener( 'change', syncCallback );
		return;
	}

	if ( 'function' === typeof mediaQuery.addListener ) {
		mediaQuery.addListener( syncCallback );
	}
}

function syncStageVideos( stageElement, shouldAutoplay ) {
	if ( ! stageElement ) {
		return;
	}

	const activeSlide = stageElement.querySelector( '.splide__slide.is-active' );
	const videos = stageElement.querySelectorAll( '[data-villa-gallery-video]' );

	videos.forEach( ( video ) => {
		const isActive = activeSlide?.contains( video );

		if ( ! isActive ) {
			resetManagedVideo( video );
			return;
		}

		if ( shouldAutoplay ) {
			attemptManagedVideoAutoplay( video );
			return;
		}

		resetManagedVideo( video, { showFallback: true } );
	} );
}

function syncStaticVideo( rootElement, shouldAutoplay ) {
	const video = rootElement.querySelector( '[data-villa-gallery-static-video]' );

	if ( ! video ) {
		return;
	}

	if ( shouldAutoplay ) {
		attemptManagedVideoAutoplay( video );
		return;
	}

	resetManagedVideo( video, { showFallback: true } );
}

function setActiveThumbState( thumbsElement, activeIndex ) {
	if ( ! thumbsElement ) {
		return;
	}

	thumbsElement
		.querySelectorAll( '.splide__slide' )
		.forEach( ( slide, slideIndex ) => {
			const isActive = slideIndex === activeIndex;

			slide.classList.toggle( THUMB_ACTIVE_CLASS, isActive );
			slide.setAttribute( 'aria-current', isActive ? 'true' : 'false' );
		} );
}

function revealActiveThumbIfNeeded( thumbs, activeIndex ) {
	const move = thumbs?.Components?.Move;
	const track = thumbs?.root?.querySelector( '.splide__track' );
	const activeSlide = thumbs?.root?.querySelectorAll( '.splide__slide' )?.[
		activeIndex
	];

	if ( ! move || ! track || ! activeSlide ) {
		return;
	}

	const trackRect = track.getBoundingClientRect();
	const slideRect = activeSlide.getBoundingClientRect();
	const currentPosition = move.getPosition();
	let nextPosition = currentPosition;

	if ( slideRect.left < trackRect.left ) {
		nextPosition += trackRect.left - slideRect.left;
	} else if ( slideRect.right > trackRect.right ) {
		nextPosition -= slideRect.right - trackRect.right;
	}

	const minPosition = move.getLimit( true );
	const maxPosition = move.getLimit( false );

	nextPosition = Math.min(
		maxPosition,
		Math.max( minPosition, nextPosition )
	);

	if ( nextPosition === currentPosition ) {
		return;
	}

	move.translate( nextPosition, true );
}

function syncThumbRail( thumbs, activeIndex ) {
	if ( ! thumbs ) {
		return;
	}

	setActiveThumbState( thumbs.root, activeIndex );
	revealActiveThumbIfNeeded( thumbs, activeIndex );
}

function syncActiveGalleryState(
	stageElement,
	thumbs,
	activeIndex,
	shouldAutoplay
) {
	syncStageVideos( stageElement, shouldAutoplay );
	syncThumbRail( thumbs, activeIndex );
}

function getThumbOptions( thumbCount, prefersReducedMotion ) {
	const useFullWidthRail = thumbCount <= FULL_WIDTH_THUMB_MAX;

	return {
		arrows: false,
		autoWidth: ! useFullWidthRail,
		drag: ! useFullWidthRail,
		gap: '1px',
		keyboard: false,
		pagination: false,
		perPage: useFullWidthRail ? thumbCount : undefined,
		rewind: false,
		speed: prefersReducedMotion ? 0 : 600,
		trimSpace: ! useFullWidthRail,
		updateOnMove: false,
		breakpoints: useFullWidthRail
			? {
					1023: {
						autoWidth: true,
						drag: true,
						perPage: 1,
						trimSpace: true,
					},
				}
			: undefined,
	};
}

function bindThumbInteractions( thumbs, stage ) {
	const thumbSlides = thumbs?.root?.querySelectorAll( '.splide__slide' ) ?? [];

	thumbSlides.forEach( ( slide, slideIndex ) => {
		slide.setAttribute( 'role', 'button' );
		slide.setAttribute( 'tabindex', '0' );

		slide.addEventListener( 'click', () => {
			stage.go( slideIndex );
		} );

		slide.addEventListener( 'keydown', ( event ) => {
			if ( 'Enter' !== event.key && ' ' !== event.key ) {
				return;
			}

			event.preventDefault();
			stage.go( slideIndex );
		} );
	} );
}

function initializeVillaGalleryHero( rootElement ) {
	const stageElement = rootElement.querySelector( '[data-villa-gallery-stage]' );
	const thumbsElement = rootElement.querySelector( '[data-villa-gallery-thumbs]' );
	const previousButton = rootElement.querySelector( '[data-villa-gallery-prev]' );
	const nextButton = rootElement.querySelector( '[data-villa-gallery-next]' );
	const reducedMotionMediaQuery = window.matchMedia(
		REDUCED_MOTION_MEDIA_QUERY
	);
	const shouldAutoplayVideos = () => ! reducedMotionMediaQuery.matches;

	bindManagedVideoControls( rootElement );

	if ( ! stageElement || ! thumbsElement ) {
		const syncStaticState = () =>
			syncStaticVideo( rootElement, shouldAutoplayVideos() );

		syncStaticState();
		bindReducedMotionPreference( reducedMotionMediaQuery, syncStaticState );
		return;
	}

	const thumbCount = thumbsElement.querySelectorAll( '.splide__slide' ).length;
	const prefersReducedMotion = reducedMotionMediaQuery.matches;

	if ( thumbCount <= 1 ) {
		const syncStaticState = () =>
			syncStaticVideo( rootElement, shouldAutoplayVideos() );

		syncStaticState();
		bindReducedMotionPreference( reducedMotionMediaQuery, syncStaticState );
		return;
	}

	thumbsElement.classList.toggle(
		'vvm-villa-gallery-hero__thumbs--full-width',
		thumbCount <= FULL_WIDTH_THUMB_MAX
	);

	const thumbs = new Splide(
		thumbsElement,
		getThumbOptions( thumbCount, prefersReducedMotion )
	);

	const stage = new Splide( stageElement, {
		arrows: false,
		drag: false,
		keyboard: 'focused',
		pagination: false,
		rewind: true,
		type: 'fade',
		speed: prefersReducedMotion ? 0 : 700,
		updateOnMove: true,
		waitForTransition: true,
	} );

	const syncActiveState = () =>
		syncActiveGalleryState(
			stageElement,
			thumbs,
			stage.index,
			shouldAutoplayVideos()
		);

	// `ready` fires after Splide finishes its initial setup, which means the
	// first active slide is in place before we try to autoplay its inline video.
	stage.on( 'ready moved', syncActiveState );

	stage.mount();
	thumbs.mount();

	previousButton?.addEventListener( 'click', () => {
		stage.go( '<' );
	} );

	nextButton?.addEventListener( 'click', () => {
		stage.go( '>' );
	} );

	bindThumbInteractions( thumbs, stage );
	syncActiveState();
	bindReducedMotionPreference( reducedMotionMediaQuery, syncActiveState );
}

window.addEventListener( 'DOMContentLoaded', () => {
	document
		.querySelectorAll( '[data-villa-gallery-hero]' )
		.forEach( initializeVillaGalleryHero );
} );
