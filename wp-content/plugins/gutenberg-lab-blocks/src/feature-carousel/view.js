import Splide from '@splidejs/splide';

const REDUCED_MOTION_MEDIA_QUERY = '(prefers-reduced-motion: reduce)';
const DEFAULT_FADE_DURATION = '700ms';
const NO_MOTION_DURATION = '0ms';
const REAL_SLIDE_SELECTOR =
	'.splide__slide:not(.splide__slide--clone)[data-feature-carousel-slide]';
const FOCUSABLE_SELECTOR =
	'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]';

function bindReducedMotionPreference( mediaQuery, syncCallback ) {
	if ( 'function' === typeof mediaQuery.addEventListener ) {
		mediaQuery.addEventListener( 'change', syncCallback );
		return;
	}

	if ( 'function' === typeof mediaQuery.addListener ) {
		mediaQuery.addListener( syncCallback );
	}
}

function syncButtons( previousButton, nextButton ) {
	if ( previousButton ) {
		previousButton.disabled = false;
	}

	if ( nextButton ) {
		nextButton.disabled = false;
	}
}

function syncSlideInteractivity( slide, isActive ) {
	slide.classList.toggle( 'is-active', isActive );
	slide.setAttribute( 'aria-current', isActive ? 'true' : 'false' );

	slide.querySelectorAll( FOCUSABLE_SELECTOR ).forEach( ( element ) => {
		if ( isActive ) {
			const savedTabIndex = element.dataset.featureCarouselTabindex;

			if ( undefined === savedTabIndex || '' === savedTabIndex ) {
				element.removeAttribute( 'tabindex' );
			} else {
				element.setAttribute( 'tabindex', savedTabIndex );
			}

			delete element.dataset.featureCarouselTabindex;
			return;
		}

		if ( undefined === element.dataset.featureCarouselTabindex ) {
			element.dataset.featureCarouselTabindex =
				element.getAttribute( 'tabindex' ) ?? '';
		}

		element.setAttribute( 'tabindex', '-1' );
	} );
}

function syncActiveState( rootElement, splide ) {
	// Ignore Splide loop clones so only the original editorial slides own focus.
	rootElement
		.querySelectorAll( REAL_SLIDE_SELECTOR )
		.forEach( ( slide, index ) => {
			syncSlideInteractivity( slide, index === splide.index );
		} );
}

function syncMotionPreference( rootElement, mediaQuery ) {
	rootElement.style.setProperty(
		'--vvm-feature-carousel-fade-duration',
		mediaQuery.matches ? NO_MOTION_DURATION : DEFAULT_FADE_DURATION
	);
}

function initializeFeatureCarousel( rootElement ) {
	const carouselElement = rootElement.querySelector( '[data-feature-carousel]' );
	const previousButton = rootElement.querySelector(
		'[data-feature-carousel-prev]'
	);
	const nextButton = rootElement.querySelector( '[data-feature-carousel-next]' );
	const reducedMotionMediaQuery = window.matchMedia(
		REDUCED_MOTION_MEDIA_QUERY
	);
	const slideCount =
		carouselElement?.querySelectorAll( '[data-feature-carousel-slide]' ).length ??
		0;

	if ( ! carouselElement || 0 === slideCount ) {
		return;
	}

	if ( 1 === slideCount ) {
		syncMotionPreference( rootElement, reducedMotionMediaQuery );
		rootElement
			.querySelectorAll( REAL_SLIDE_SELECTOR )
			.forEach( ( slide ) => syncSlideInteractivity( slide, true ) );
		return;
	}

	// We keep the peeking layout, so we suppress track motion and let CSS fade
	// the active slide state instead of using Splide's built-in fade mode.
	const getSpeed = () => 0;
	syncMotionPreference( rootElement, reducedMotionMediaQuery );
	const splide = new Splide( carouselElement, {
		arrows: false,
		drag: true,
		fixedWidth: 'var(--vvm-feature-carousel-slide-width)',
		focus: 0,
		gap: '0rem',
		keyboard: 'focused',
		pagination: false,
		perMove: 1,
		speed: getSpeed(),
		trimSpace: false,
		type: 'loop',
		updateOnMove: true,
		waitForTransition: true,
		padding: {
			left: '0rem',
			right: '0rem',
		},
		breakpoints: {
			1023: {
				fixedWidth: '100%',
				focus: 0,
				gap: '0.875rem',
				perPage: 1,
				padding: {
					left: '0rem',
					right: '0rem',
				},
				trimSpace: true,
			},
		},
	} );

	const syncUi = () => {
		syncButtons( previousButton, nextButton );
		syncActiveState( rootElement, splide );
	};

	previousButton?.addEventListener( 'click', () => {
		splide.go( '<' );
	} );

	nextButton?.addEventListener( 'click', () => {
		splide.go( '>' );
	} );

	splide.on( 'mounted move resized updated', syncUi );
	splide.mount();
	syncUi();

	bindReducedMotionPreference( reducedMotionMediaQuery, () => {
		syncMotionPreference( rootElement, reducedMotionMediaQuery );
	} );
}

window.addEventListener( 'DOMContentLoaded', () => {
	document
		.querySelectorAll( '[data-feature-carousel-root]' )
		.forEach( initializeFeatureCarousel );
} );
