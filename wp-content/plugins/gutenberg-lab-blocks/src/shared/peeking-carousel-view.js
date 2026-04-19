import Splide from '@splidejs/splide';

const REDUCED_MOTION_MEDIA_QUERY = '(prefers-reduced-motion: reduce)';
const DEFAULT_FADE_DURATION = '700ms';
const NO_MOTION_DURATION = '0ms';
const FOCUSABLE_SELECTOR =
	'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]';
const TABINDEX_DATA_KEY = 'peekingCarouselTabindex';

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

function syncActiveState( rootElement, splide, realSlideSelector ) {
	// Ignore Splide loop clones so only the authored slides own focus state.
	rootElement.querySelectorAll( realSlideSelector ).forEach( ( slide, index ) => {
		syncSlideInteractivity( slide, index === splide.index );
	} );
}

function syncMotionPreference( rootElement, mediaQuery, durationVariable ) {
	rootElement.style.setProperty(
		durationVariable,
		mediaQuery.matches ? NO_MOTION_DURATION : DEFAULT_FADE_DURATION
	);
}

function initializePeekingCarousel( rootElement, config ) {
	const {
		carouselSelector,
		durationVariable,
		nextButtonSelector,
		previousButtonSelector,
		realSlideSelector,
		slideSelector,
	} = config;
	const carouselElement = rootElement.querySelector( carouselSelector );
	const previousButton = rootElement.querySelector( previousButtonSelector );
	const nextButton = rootElement.querySelector( nextButtonSelector );
	const reducedMotionMediaQuery = window.matchMedia(
		REDUCED_MOTION_MEDIA_QUERY
	);
	const slideCount =
		carouselElement?.querySelectorAll( slideSelector ).length ?? 0;

	if ( ! carouselElement || 0 === slideCount ) {
		return;
	}

	if ( 1 === slideCount ) {
		syncMotionPreference(
			rootElement,
			reducedMotionMediaQuery,
			durationVariable
		);
		rootElement
			.querySelectorAll( realSlideSelector )
			.forEach( ( slide ) => syncSlideInteractivity( slide, true ) );
		return;
	}

	// The paired layout keeps neighboring slides visible, so CSS owns the fade
	// state while Splide only handles indexing, drag, and loop navigation.
	syncMotionPreference( rootElement, reducedMotionMediaQuery, durationVariable );
	const splide = new Splide( carouselElement, {
		arrows: false,
		drag: true,
		fixedWidth: 'var(--vvm-feature-carousel-slide-width)',
		focus: 0,
		gap: '0rem',
		keyboard: 'focused',
		pagination: false,
		perMove: 1,
		speed: 0,
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
		syncActiveState( rootElement, splide, realSlideSelector );
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
		syncMotionPreference(
			rootElement,
			reducedMotionMediaQuery,
			durationVariable
		);
	} );
}

export function bootPeekingCarousel( config ) {
	window.addEventListener( 'DOMContentLoaded', () => {
		document
			.querySelectorAll( config.rootSelector )
			.forEach( ( rootElement ) =>
				initializePeekingCarousel( rootElement, config )
			);
	} );
}
