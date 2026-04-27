import Splide from '@splidejs/splide';
import {
	revealRailItem,
	scrollRailByStep,
	syncRailButtons,
} from '../shared/rail-navigation';
import {
	attemptVimeoShellAutoplay,
	bindVimeoShellPlayButtons,
	resetVimeoShell,
} from '../shared/vimeo-player-shell';

const REDUCED_MOTION_MEDIA_QUERY = '(prefers-reduced-motion: reduce)';
const THUMB_ACTIVE_CLASS = 'vvm-villa-gallery-hero__thumb-slide--active';
const FULL_WIDTH_THUMB_MAX = 5;
const PAGE_INTENT_EVENTS = [
	'focusin',
	'keydown',
	'pointerdown',
	'pointermove',
	'scroll',
	'touchstart',
	'wheel',
];

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

	// Browser autoplay policies allow silent inline media. Set the properties
	// before play() so JS-created retries match the PHP-rendered attributes.
	video.muted = true;
	video.defaultMuted = true;
	video.autoplay = true;
	video.playsInline = true;
	video.preload = 'auto';
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

		resetManagedVideo( video, { showFallback: true } );
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

	resetManagedVideo( video, { showFallback: true } );
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

	revealRailItem( track, activeSlide, prefersReducedMotion );
}

function syncThumbRail( thumbsElement, activeIndex, prefersReducedMotion ) {
	if ( ! thumbsElement ) {
		return;
	}

	setActiveThumbState( thumbsElement, activeIndex );
	revealActiveThumbIfNeeded( thumbsElement, activeIndex, prefersReducedMotion );
}

function syncActiveGalleryState(
	stageElement,
	thumbsElement,
	activeIndex,
	shouldAutoplayNativeVideo,
	shouldAutoplayVimeo,
	prefersReducedMotion
) {
	syncStageNativeVideos( stageElement, shouldAutoplayNativeVideo );
	syncStageVimeoShells( stageElement, shouldAutoplayVimeo );
	syncThumbRail( thumbsElement, activeIndex, prefersReducedMotion );
}

function bindThumbInteractions( thumbsElement, stage, prefersReducedMotion ) {
	const thumbSlides = thumbsElement?.querySelectorAll( '.splide__slide' ) ?? [];

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

	bindManagedVideoControls( rootElement );
	bindVimeoShellPlayButtons( rootElement );

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
			nextThumbRailButton
		);

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

	thumbsElement.classList.toggle(
		'vvm-villa-gallery-hero__thumbs--full-width',
		thumbCount <= FULL_WIDTH_THUMB_MAX
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

	const syncActiveState = () => {
		syncActiveGalleryState(
			stageElement,
			thumbsElement,
			stage.index,
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
