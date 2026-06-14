import Splide from '@splidejs/splide';
import {
	revealRailItem,
	scrollRailByStep,
	syncRailButtons,
} from '../shared/rail-navigation';
import {
	attemptVimeoShellAutoplay,
	bindNativeVideoControls,
	bindVimeoShellTransportControls,
	resetVimeoShell,
	setNativeVideoControlState,
} from '../shared/vimeo-player-shell';

const REDUCED_MOTION_MEDIA_QUERY = '(prefers-reduced-motion: reduce)';
const THUMB_ACTIVE_CLASS = 'vvm-villa-gallery-hero__thumb-slide--active';
const MAX_VISIBLE_THUMBNAILS = 5;
const PAGE_INTENT_EVENTS = [
	'focusin',
	'keydown',
	'pointerdown',
	'pointermove',
	'scroll',
	'touchstart',
	'wheel',
];

function resetManagedVideo( video ) {
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
	setNativeVideoControlState( video, 'paused' );
}

function attemptManagedVideoAutoplay( video ) {
	if ( ! video ) {
		return;
	}

	// Browser autoplay policies allow silent inline media. Set the properties
	// before play() so JS-created retries match the PHP-rendered attributes.
	video.muted = true;
	video.defaultMuted = true;
	video.autoplay = true;
	video.playsInline = true;
	video.preload = 'auto';
	video.controls = false;
	setNativeVideoControlState( video, 'playing' );

	const playPromise = video.play?.();

	if ( playPromise && 'function' === typeof playPromise.catch ) {
		playPromise
			.then( () => {
				setNativeVideoControlState( video, 'playing' );
			} )
			.catch( () => {
				setNativeVideoControlState( video, 'paused' );
			} );
	}
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

function isSaveDataEnabled() {
	return Boolean( window.navigator?.connection?.saveData );
}

function hasVillaGalleryVimeoShells( rootElement ) {
	return Boolean( rootElement.querySelector( '[data-villa-gallery-vimeo]' ) );
}

function bindPageIntentAutoplay( callback ) {
	let didRun = false;

	const removeListeners = () => {
		PAGE_INTENT_EVENTS.forEach( ( eventName ) => {
			window.removeEventListener( eventName, handleIntent, true );
		} );
	};

	const handleIntent = ( event ) => {
		if ( didRun || false === event?.isTrusted ) {
			return;
		}

		didRun = true;
		removeListeners();
		callback();
	};

	PAGE_INTENT_EVENTS.forEach( ( eventName ) => {
		window.addEventListener( eventName, handleIntent, {
			capture: true,
			passive: true,
		} );
	} );

	return removeListeners;
}

function loadDeferredStageImage( image ) {
	if ( ! image || ! image.dataset.villaGalleryDeferredSrc ) {
		return;
	}

	const { villaGalleryDeferredSrc, villaGalleryDeferredSrcset, villaGalleryDeferredSizes } =
		image.dataset;

	if ( villaGalleryDeferredSizes ) {
		image.setAttribute( 'sizes', villaGalleryDeferredSizes );
		delete image.dataset.villaGalleryDeferredSizes;
	}

	if ( villaGalleryDeferredSrcset ) {
		image.setAttribute( 'srcset', villaGalleryDeferredSrcset );
		delete image.dataset.villaGalleryDeferredSrcset;
	}

	image.src = villaGalleryDeferredSrc;
	delete image.dataset.villaGalleryDeferredSrc;
	delete image.dataset.villaGalleryDeferredImage;
}

function loadStageSlideMedia( stageElement, activeIndex ) {
	const activeSlide = stageElement?.querySelectorAll( '.splide__slide' )?.[
		activeIndex
	];

	activeSlide
		?.querySelectorAll( '[data-villa-gallery-deferred-image]' )
		.forEach( loadDeferredStageImage );
}

function syncStageNativeVideos( stageElement, shouldAutoplay ) {
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

		resetManagedVideo( video );
	} );
}

function syncStaticNativeVideo( rootElement, shouldAutoplay ) {
	const video = rootElement.querySelector( '[data-villa-gallery-static-video]' );

	if ( ! video ) {
		return;
	}

	if ( shouldAutoplay ) {
		attemptManagedVideoAutoplay( video );
		return;
	}

	resetManagedVideo( video );
}

function syncStageVimeoShells( stageElement, shouldAutoplay ) {
	if ( ! stageElement ) {
		return;
	}

	const activeSlide = stageElement.querySelector( '.splide__slide.is-active' );
	const shells = stageElement.querySelectorAll( '[data-villa-gallery-vimeo]' );

	shells.forEach( ( shell ) => {
		const isActive = activeSlide?.contains( shell );

		if ( ! isActive ) {
			void resetVimeoShell( shell, { showPoster: true } );
			return;
		}

		if ( shouldAutoplay ) {
			void attemptVimeoShellAutoplay( shell );
			return;
		}

		void resetVimeoShell( shell, { showPoster: true } );
	} );
}

function syncStaticVimeoShell( rootElement, shouldAutoplay ) {
	const shell = rootElement.querySelector( '[data-villa-gallery-vimeo]' );

	if ( ! shell ) {
		return;
	}

	if ( shouldAutoplay ) {
		void attemptVimeoShellAutoplay( shell );
		return;
	}

	void resetVimeoShell( shell, { showPoster: true } );
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

function revealActiveThumbIfNeeded(
	thumbsElement,
	activeIndex,
	prefersReducedMotion
) {
	const track = thumbsElement?.querySelector( '.splide__track' );
	const activeSlide = thumbsElement?.querySelectorAll( '.splide__slide' )?.[
		activeIndex
	];

	// Center only hidden/partially hidden thumbs so a click does not shove the
	// newly active thumbnail against the far edge of the viewport.
	revealRailItem( track, activeSlide, prefersReducedMotion, {
		align: 'center',
	} );
}

function syncThumbRail( thumbsElement, activeIndex, prefersReducedMotion ) {
	if ( ! thumbsElement ) {
		return;
	}

	setActiveThumbState( thumbsElement, activeIndex );
	revealActiveThumbIfNeeded( thumbsElement, activeIndex, prefersReducedMotion );
}

function getThumbRailGapPixels( thumbsElement ) {
	const listElement = thumbsElement?.querySelector( '.splide__list' );
	const listStyles = listElement ? getComputedStyle( listElement ) : null;
	const gap = parseFloat(
		listStyles?.columnGap || listStyles?.gap || '0'
	);

	return Number.isFinite( gap ) ? gap : 0;
}

function syncThumbRailLayout( thumbsElement, thumbCount ) {
	if ( ! thumbsElement ) {
		return;
	}

	const visibleThumbCount = Math.min(
		Math.max( thumbCount, 1 ),
		MAX_VISIBLE_THUMBNAILS
	);
	const visibleGapCount = Math.max( 0, visibleThumbCount - 1 );
	const visibleGapWidth =
		visibleGapCount * getThumbRailGapPixels( thumbsElement );

	// The CSS uses these two values to keep the visible rail capped at five
	// thumbnails while allowing smaller galleries to fill the available width.
	thumbsElement.style.setProperty(
		'--vvm-villa-gallery-hero-thumb-visible-count',
		visibleThumbCount
	);
	thumbsElement.style.setProperty(
		'--vvm-villa-gallery-hero-thumb-visible-gap-total',
		`${ visibleGapWidth }px`
	);
	thumbsElement.classList.toggle(
		'vvm-villa-gallery-hero__thumbs--full-width',
		thumbCount <= MAX_VISIBLE_THUMBNAILS
	);
}

function syncActiveGalleryState(
	stageElement,
	thumbsElement,
	activeIndex,
	shouldAutoplayNativeVideo,
	shouldAutoplayVimeo,
	prefersReducedMotion
) {
	loadStageSlideMedia( stageElement, activeIndex );
	syncStageNativeVideos( stageElement, shouldAutoplayNativeVideo );
	syncStageVimeoShells( stageElement, shouldAutoplayVimeo );
	syncThumbRail( thumbsElement, activeIndex, prefersReducedMotion );
}

function bindThumbInteractions( thumbsElement, stage, prefersReducedMotion ) {
	const thumbSlides = thumbsElement?.querySelectorAll( '.splide__slide' ) ?? [];

	thumbSlides.forEach( ( slide, slideIndex ) => {
		slide.setAttribute( 'role', 'button' );
		slide.setAttribute( 'tabindex', '0' );

		const activateSlide = () => {
			stage.go( slideIndex );
		};

		slide.addEventListener( 'pointerdown', ( event ) => {
			if ( 'mouse' !== event.pointerType || 0 !== event.button ) {
				return;
			}

			// Smooth rail scrolling can move a thumb before the browser fires
			// click; mouse pointerdown keeps thumbnail selection responsive.
			activateSlide();
		} );

		slide.addEventListener( 'click', activateSlide );

		slide.addEventListener( 'keydown', ( event ) => {
			if ( 'Enter' !== event.key && ' ' !== event.key ) {
				return;
			}

			event.preventDefault();
			activateSlide();
		} );

		slide.addEventListener( 'focus', () => {
			window.requestAnimationFrame( () => {
				slide.focus?.( { preventScroll: true } );
				revealActiveThumbIfNeeded(
					thumbsElement,
					slideIndex,
					prefersReducedMotion
				);
			} );
		} );
	} );
}

function bindGalleryCtaScroll( rootElement, prefersReducedMotion ) {
	const galleryCta = rootElement.querySelector(
		'[data-villa-gallery-hero-cta="gallery"]'
	);
	const galleryTarget = rootElement.querySelector(
		'[data-villa-gallery-hero-target="gallery"]'
	);

	if ( ! galleryCta || ! galleryTarget ) {
		return;
	}

	galleryCta.addEventListener( 'click', ( event ) => {
		if (
			event.defaultPrevented ||
			event.altKey ||
			event.ctrlKey ||
			event.metaKey ||
			event.shiftKey
		) {
			return;
		}

		event.preventDefault();

		const targetRect = galleryTarget.getBoundingClientRect();
		const maxScrollY = Math.max(
			0,
			document.documentElement.scrollHeight - window.innerHeight
		);
		const scrollY = Math.min(
			maxScrollY,
			Math.max( 0, window.scrollY + targetRect.bottom - window.innerHeight )
		);

		// Align the bottom of the thumbnail rail with the bottom of the viewport.
		window.scrollTo( {
			top: scrollY,
			behavior: prefersReducedMotion ? 'auto' : 'smooth',
		} );

		if ( galleryTarget.id ) {
			window.history.pushState( null, '', `#${ galleryTarget.id }` );
		}
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
	let hasPageVideoIntent = false;
	const shouldAutoplayNativeVideos = () => ! reducedMotionMediaQuery.matches;
	const canAutoplayVimeoAfterIntent = () =>
		hasPageVideoIntent &&
		! reducedMotionMediaQuery.matches &&
		! isSaveDataEnabled();

	bindGalleryCtaScroll( rootElement, reducedMotionMediaQuery.matches );
	bindNativeVideoControls( rootElement );
	bindVimeoShellTransportControls( rootElement );

	if ( ! stageElement || ! thumbsElement ) {
		const syncStaticState = () => {
			syncStaticNativeVideo( rootElement, shouldAutoplayNativeVideos() );
			syncStaticVimeoShell( rootElement, canAutoplayVimeoAfterIntent() );
		};

		syncStaticState();
		if (
			hasVillaGalleryVimeoShells( rootElement ) &&
			! reducedMotionMediaQuery.matches &&
			! isSaveDataEnabled()
		) {
			bindPageIntentAutoplay( () => {
				hasPageVideoIntent = true;
				syncStaticState();
			} );
		}
		bindReducedMotionPreference( reducedMotionMediaQuery, syncStaticState );
		return;
	}

	const thumbCount = thumbsElement.querySelectorAll( '.splide__slide' ).length;
	const prefersReducedMotion = reducedMotionMediaQuery.matches;
	const thumbSlides = Array.from(
		thumbsElement.querySelectorAll( '.splide__slide' )
	);
	const thumbTrack = thumbsElement.querySelector( '.splide__track' );
	const previousThumbRailButton = rootElement.querySelector(
		'[data-villa-gallery-thumbs-prev]'
	);
	const nextThumbRailButton = rootElement.querySelector(
		'[data-villa-gallery-thumbs-next]'
	);
	const syncThumbRailButtons = () =>
		syncRailButtons(
			thumbTrack,
			previousThumbRailButton,
			nextThumbRailButton,
			{ hideAtEdges: true }
		);

	syncThumbRailLayout( thumbsElement, thumbCount );

	if ( thumbCount <= 1 ) {
		const syncStaticState = () => {
			syncStaticNativeVideo( rootElement, shouldAutoplayNativeVideos() );
			syncStaticVimeoShell( rootElement, canAutoplayVimeoAfterIntent() );
		};

		syncStaticState();
		if (
			hasVillaGalleryVimeoShells( rootElement ) &&
			! reducedMotionMediaQuery.matches &&
			! isSaveDataEnabled()
		) {
			bindPageIntentAutoplay( () => {
				hasPageVideoIntent = true;
				syncStaticState();
			} );
		}
		bindReducedMotionPreference( reducedMotionMediaQuery, syncStaticState );
		return;
	}

	const stage = new Splide( stageElement, {
		arrows: false,
		drag: false,
		keyboard: 'focused',
		pagination: false,
		rewind: true,
		type: 'fade',
		speed: prefersReducedMotion ? 0 : 700,
		updateOnMove: true,
		// Thumbnail clicks should be able to interrupt a fade started by arrows.
		waitForTransition: false,
	} );

	const syncActiveState = ( activeIndex = stage.index ) => {
		syncActiveGalleryState(
			stageElement,
			thumbsElement,
			activeIndex,
			shouldAutoplayNativeVideos(),
			canAutoplayVimeoAfterIntent(),
			prefersReducedMotion
		);
	};

	if (
		hasVillaGalleryVimeoShells( rootElement ) &&
		! reducedMotionMediaQuery.matches &&
		! isSaveDataEnabled()
	) {
		bindPageIntentAutoplay( () => {
			hasPageVideoIntent = true;
			syncActiveState();
		} );
	}

	previousThumbRailButton?.addEventListener( 'click', () => {
		scrollRailByStep( thumbTrack, thumbSlides, -1, prefersReducedMotion );
	} );

	nextThumbRailButton?.addEventListener( 'click', () => {
		scrollRailByStep( thumbTrack, thumbSlides, 1, prefersReducedMotion );
	} );

	thumbTrack?.addEventListener(
		'scroll',
		() => {
			window.requestAnimationFrame( syncThumbRailButtons );
		},
		{ passive: true }
	);

	window.addEventListener( 'resize', syncThumbRailButtons );

	// `ready` fires after Splide finishes its initial setup, which means the
	// first active slide is in place before we try to autoplay its managed media.
	stage.on( 'ready moved', () => {
		syncActiveState();
		window.requestAnimationFrame( syncThumbRailButtons );
	} );

	stage.on( 'move', ( activeIndex ) => {
		syncActiveState( activeIndex );
		window.requestAnimationFrame( syncThumbRailButtons );
	} );

	stage.mount();

	previousButton?.addEventListener( 'click', () => {
		stage.go( '<' );
	} );

	nextButton?.addEventListener( 'click', () => {
		stage.go( '>' );
	} );

	bindThumbInteractions( thumbsElement, stage, prefersReducedMotion );
	syncActiveState();
	syncThumbRailButtons();
	bindReducedMotionPreference( reducedMotionMediaQuery, syncActiveState );
}

window.addEventListener( 'DOMContentLoaded', () => {
	document
		.querySelectorAll( '[data-villa-gallery-hero]' )
		.forEach( initializeVillaGalleryHero );
} );
