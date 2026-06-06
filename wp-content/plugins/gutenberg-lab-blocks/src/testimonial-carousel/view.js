import {
	scrollRailByStep,
	syncRailButtons,
} from '../shared/rail-navigation';

const FOCUSABLE_SELECTOR =
	'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]';
const TABINDEX_DATA_KEY = 'testimonialCarouselTabindex';
const REDUCED_MOTION_MEDIA_QUERY = '(prefers-reduced-motion: reduce)';

function getActiveSlideIndex( track, slides ) {
	const scrollLeft = track.scrollLeft;
	let activeIndex = 0;
	let activeDistance = Number.POSITIVE_INFINITY;

	slides.forEach( ( slide, index ) => {
		const distance = Math.abs( slide.offsetLeft - scrollLeft );

		if ( distance < activeDistance ) {
			activeDistance = distance;
			activeIndex = index;
		}
	} );

	return activeIndex;
}

function syncSlideInteractivity( slide, isActive ) {
	slide.classList.toggle( 'is-active', isActive );
	slide.setAttribute( 'aria-hidden', isActive ? 'false' : 'true' );

	slide.querySelectorAll( FOCUSABLE_SELECTOR ).forEach( ( element ) => {
		if ( isActive ) {
			const savedTabIndex = element.dataset[ TABINDEX_DATA_KEY ];

			if ( undefined === savedTabIndex || '' === savedTabIndex ) {
				element.removeAttribute( 'tabindex' );
			} else {
				element.setAttribute( 'tabindex', savedTabIndex );
			}

			delete element.dataset[ TABINDEX_DATA_KEY ];
			return;
		}

		if ( undefined === element.dataset[ TABINDEX_DATA_KEY ] ) {
			element.dataset[ TABINDEX_DATA_KEY ] =
				element.getAttribute( 'tabindex' ) ?? '';
		}

		element.setAttribute( 'tabindex', '-1' );
	} );
}

function initializeTestimonialCarousel( slider ) {
	const track = slider.querySelector( '[data-testimonial-carousel-track]' );
	const slides = Array.from( track?.children ?? [] );
	const previousButton = slider.querySelector(
		'[data-testimonial-carousel-prev]'
	);
	const nextButton = slider.querySelector( '[data-testimonial-carousel-next]' );
	const reducedMotionMediaQuery = window.matchMedia(
		REDUCED_MOTION_MEDIA_QUERY
	);
	let syncFrame = 0;

	if ( ! track || slides.length <= 1 || ! previousButton || ! nextButton ) {
		return;
	}

	const syncState = () => {
		syncFrame = 0;
		syncRailButtons( track, previousButton, nextButton );

		const activeIndex = getActiveSlideIndex( track, slides );
		slides.forEach( ( slide, index ) => {
			syncSlideInteractivity( slide, index === activeIndex );
		} );
	};

	const scheduleSync = () => {
		if ( syncFrame ) {
			return;
		}

		syncFrame = window.requestAnimationFrame( syncState );
	};

	previousButton.addEventListener( 'click', () => {
		scrollRailByStep(
			track,
			slides,
			-1,
			reducedMotionMediaQuery.matches
		);
	} );

	nextButton.addEventListener( 'click', () => {
		scrollRailByStep(
			track,
			slides,
			1,
			reducedMotionMediaQuery.matches
		);
	} );

	track.addEventListener( 'scroll', scheduleSync, { passive: true } );
	window.addEventListener( 'resize', scheduleSync, { passive: true } );

	if ( 'undefined' !== typeof ResizeObserver ) {
		const resizeObserver = new ResizeObserver( scheduleSync );
		resizeObserver.observe( track );
	}

	syncState();
}

window.addEventListener( 'DOMContentLoaded', () => {
	document
		.querySelectorAll( '[data-testimonial-carousel-slider]' )
		.forEach( initializeTestimonialCarousel );
} );
