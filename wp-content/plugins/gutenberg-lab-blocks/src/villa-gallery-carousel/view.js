const REDUCED_MOTION_MEDIA_QUERY = '(prefers-reduced-motion: reduce)';
const ACTIVE_CLASS = 'is-active';

function getRailStep( track, slides ) {
	const firstSlide = slides?.[ 0 ];
	const listElement = track?.querySelector( '.splide__list' );
	const listStyles = listElement ? getComputedStyle( listElement ) : null;
	const gap =
		parseFloat( listStyles?.columnGap || listStyles?.gap || '0' ) || 0;

	if ( ! firstSlide ) {
		return track?.clientWidth || 0;
	}

	return firstSlide.offsetWidth + gap;
}

function syncRailButtons( track, previousButton, nextButton ) {
	if ( ! track ) {
		return;
	}

	const maxScrollLeft = Math.max( 0, track.scrollWidth - track.clientWidth );
	const canScroll = maxScrollLeft > 12;

	[ previousButton, nextButton ].forEach( ( button ) => {
		if ( button ) {
			button.hidden = ! canScroll;
		}
	} );

	if ( previousButton ) {
		previousButton.disabled = ! canScroll || track.scrollLeft <= 1;
	}

	if ( nextButton ) {
		nextButton.disabled = ! canScroll || track.scrollLeft >= maxScrollLeft - 1;
	}
}

function scrollRailByStep(
	track,
	slides,
	direction,
	prefersReducedMotion
) {
	if ( ! track ) {
		return;
	}

	const maxScrollLeft = Math.max( 0, track.scrollWidth - track.clientWidth );
	const step = getRailStep( track, slides ) || track.clientWidth;
	const targetScrollLeft = Math.min(
		maxScrollLeft,
		Math.max( 0, track.scrollLeft + step * direction )
	);

	track.scrollTo( {
		left: targetScrollLeft,
		behavior: prefersReducedMotion ? 'auto' : 'smooth',
	} );
}

function revealCardIfNeeded( track, slide, prefersReducedMotion ) {
	if ( ! track || ! slide ) {
		return;
	}

	const maxScrollLeft = Math.max( 0, track.scrollWidth - track.clientWidth );
	const targetScrollLeft = Math.min(
		maxScrollLeft,
		Math.max( 0, slide.offsetLeft + slide.offsetWidth - track.clientWidth )
	);

	if ( Math.abs( track.scrollLeft - targetScrollLeft ) < 1 ) {
		return;
	}

	track.scrollTo( {
		left: targetScrollLeft,
		behavior: prefersReducedMotion ? 'auto' : 'smooth',
	} );
}

function syncCaption( rootElement, button ) {
	const titleElement = rootElement.querySelector(
		'[data-villa-gallery-carousel-caption-title]'
	);
	const detailElement = rootElement.querySelector(
		'[data-villa-gallery-carousel-caption-detail]'
	);
	const separatorElement = rootElement.querySelector(
		'[data-villa-gallery-carousel-caption-separator]'
	);

	if ( ! titleElement || ! detailElement || ! separatorElement || ! button ) {
		return;
	}

	const title =
		button.dataset.captionTitle ||
		titleElement.textContent ||
		'Gallery image';
	const detail = button.dataset.captionDetail || '';

	titleElement.textContent = title;
	detailElement.textContent = detail;
	detailElement.hidden = ! detail;
	separatorElement.hidden = ! detail;
}

function setActiveSlide( rootElement, activeIndex ) {
	const slides = Array.from(
		rootElement.querySelectorAll( '.vvm-villa-gallery-carousel__slide' )
	);

	slides.forEach( ( slide, slideIndex ) => {
		const button = slide.querySelector( '[data-villa-gallery-card]' );
		const isActive = slideIndex === activeIndex;

		slide.classList.toggle( ACTIVE_CLASS, isActive );

		if ( ! button ) {
			return;
		}

		button.setAttribute( 'aria-pressed', isActive ? 'true' : 'false' );
		button.setAttribute( 'aria-current', isActive ? 'true' : 'false' );

		if ( isActive ) {
			syncCaption( rootElement, button );
		}
	} );
}

function initializeVillaGalleryCarousel( rootElement ) {
	const carouselElement = rootElement.querySelector(
		'[data-villa-gallery-carousel]'
	);
	const slides = Array.from(
		carouselElement?.querySelectorAll( '.vvm-villa-gallery-carousel__slide' ) ??
			[]
	);

	if ( ! carouselElement || 0 === slides.length ) {
		return;
	}

	const prefersReducedMotion = window.matchMedia?.(
		REDUCED_MOTION_MEDIA_QUERY
	).matches;
	let activeIndex = 0;
	const track = carouselElement.querySelector( '.splide__track' );
	const previousRailButton = rootElement.querySelector(
		'[data-villa-gallery-carousel-rail-prev]'
	);
	const nextRailButton = rootElement.querySelector(
		'[data-villa-gallery-carousel-rail-next]'
	);
	const syncRailState = () =>
		syncRailButtons( track, previousRailButton, nextRailButton );

	const syncActiveState = () => {
		setActiveSlide( rootElement, activeIndex );
		revealCardIfNeeded( track, slides[ activeIndex ], prefersReducedMotion );
		window.requestAnimationFrame( syncRailState );
	};

	previousRailButton?.addEventListener( 'click', () => {
		scrollRailByStep( track, slides, -1, prefersReducedMotion );
	} );

	nextRailButton?.addEventListener( 'click', () => {
		scrollRailByStep( track, slides, 1, prefersReducedMotion );
	} );

	track?.addEventListener(
		'scroll',
		() => {
			window.requestAnimationFrame( syncRailState );
		},
		{ passive: true }
	);

	window.addEventListener( 'resize', syncRailState );

	slides.forEach( ( slide, index ) => {
		const button = slide.querySelector( '[data-villa-gallery-card]' );

		if ( ! button ) {
			return;
		}

		const activateSlide = () => {
			activeIndex = index;
			window.requestAnimationFrame( () => {
				button.focus?.( { preventScroll: true } );
				syncActiveState();
			} );
		};

		button.addEventListener( 'click', activateSlide );
		button.addEventListener( 'focus', activateSlide );
	} );

	syncActiveState();
	syncRailState();
}

window.addEventListener( 'DOMContentLoaded', () => {
	document
		.querySelectorAll( '[data-villa-gallery-carousel-root]' )
		.forEach( initializeVillaGalleryCarousel );
} );
