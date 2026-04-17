import Splide from '@splidejs/splide';

const THUMB_ACTIVE_CLASS = 'vvm-villa-gallery-hero__thumb-slide--active';

function syncStageVideos( stageElement ) {
	if ( ! stageElement ) {
		return;
	}

	const activeSlide = stageElement.querySelector( '.splide__slide.is-active' );
	const videos = stageElement.querySelectorAll( '[data-villa-gallery-video]' );

	videos.forEach( ( video ) => {
		const isActive = activeSlide?.contains( video );

		if ( isActive ) {
			const playPromise = video.play?.();

			if ( playPromise && 'function' === typeof playPromise.catch ) {
				playPromise.catch( () => {} );
			}

			return;
		}

		video.pause?.();

		try {
			video.currentTime = 0;
		} catch ( error ) {
			// Some browsers guard the currentTime setter while metadata loads.
		}
	} );
}

function playStaticVideo( rootElement ) {
	const video = rootElement.querySelector( '[data-villa-gallery-static-video]' );

	if ( ! video ) {
		return;
	}

	const playPromise = video.play?.();

	if ( playPromise && 'function' === typeof playPromise.catch ) {
		playPromise.catch( () => {} );
	}
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

	if ( ! stageElement || ! thumbsElement ) {
		playStaticVideo( rootElement );
		return;
	}

	const thumbCount = thumbsElement.querySelectorAll( '.splide__slide' ).length;
	const prefersReducedMotion = window.matchMedia(
		'(prefers-reduced-motion: reduce)'
	).matches;

	if ( thumbCount <= 1 ) {
		playStaticVideo( rootElement );
		return;
	}

	const thumbs = new Splide( thumbsElement, {
		arrows: false,
		autoWidth: true,
		drag: true,
		gap: '1rem',
		keyboard: false,
		pagination: false,
		rewind: false,
		speed: prefersReducedMotion ? 0 : 600,
		trimSpace: true,
		updateOnMove: false,
		breakpoints: {
			781: {
				gap: '0.75rem',
			},
		},
	} );

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

	stage.on( 'mounted moved', () => {
		syncStageVideos( stageElement );
		syncThumbRail( thumbs, stage.index );
	} );

	stage.mount();
	thumbs.mount();

	previousButton?.addEventListener( 'click', () => {
		stage.go( '<' );
	} );

	nextButton?.addEventListener( 'click', () => {
		stage.go( '>' );
	} );

	bindThumbInteractions( thumbs, stage );
	syncThumbRail( thumbs, stage.index );
}

window.addEventListener( 'DOMContentLoaded', () => {
	document
		.querySelectorAll( '[data-villa-gallery-hero]' )
		.forEach( initializeVillaGalleryHero );
} );
