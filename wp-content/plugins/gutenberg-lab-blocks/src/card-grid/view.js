function getVisibleColumns( carousel ) {
	const rootStyles = window.getComputedStyle( carousel );
	const visibleColumns = Number.parseInt(
		rootStyles.getPropertyValue( '--vvm-card-grid-visible-columns' ),
		10
	);

	return Number.isNaN( visibleColumns ) || visibleColumns < 1
		? 1
		: visibleColumns;
}

function getTrackGap( track ) {
	const trackStyles = window.getComputedStyle( track );
	const gap = Number.parseFloat( trackStyles.columnGap || trackStyles.gap );

	return Number.isNaN( gap ) ? 0 : gap;
}

function getCarouselSizeValue( carousel, customProperty, fallback ) {
	const rootStyles = window.getComputedStyle( carousel );
	const value = Number.parseFloat(
		rootStyles.getPropertyValue( customProperty )
	);

	return Number.isNaN( value ) || value <= 0 ? fallback : value;
}

function getActiveVisibleColumns( carousel, availableWidth, gap ) {
	let visibleColumns = getVisibleColumns( carousel );
	const cardMinWidth = getCarouselSizeValue(
		carousel,
		'--vvm-card-grid-card-min-width',
		0
	);

	// Avoid squeezing extra carousel columns into tablet widths. If the cards
	// would fall below the designed useful width, show fewer centered cards.
	while ( visibleColumns > 1 ) {
		const totalGap = gap * ( visibleColumns - 1 );
		const rawCardWidth = ( availableWidth - totalGap ) / visibleColumns;

		if ( rawCardWidth >= cardMinWidth ) {
			break;
		}

		visibleColumns -= 1;
	}

	return visibleColumns;
}

function syncCarouselCardWidth( carousel, viewport, track ) {
	const gap = getTrackGap( track );
	const availableWidth = carousel.clientWidth || viewport.clientWidth;
	const visibleColumns = getActiveVisibleColumns(
		carousel,
		availableWidth,
		gap
	);
	const cardMaxWidth = getCarouselSizeValue(
		carousel,
		'--vvm-card-grid-card-max-width',
		Number.POSITIVE_INFINITY
	);
	const totalGap = gap * ( visibleColumns - 1 );
	const rawCardWidth = ( availableWidth - totalGap ) / visibleColumns;
	const cardWidth = Math.floor(
		Math.min( cardMaxWidth, Math.max( 0, rawCardWidth ) )
	);
	const viewportWidth = ( cardWidth * visibleColumns ) + totalGap;

	// CSS alone cannot divide a dynamic gap value reliably, so JS writes the
	// exact capped pixel width that the carousel controls should slide by.
	carousel.style.setProperty(
		'--vvm-card-grid-card-width',
		`${ cardWidth }px`
	);
	carousel.style.setProperty(
		'--vvm-card-grid-carousel-viewport-width',
		`${ viewportWidth }px`
	);

	return { cardWidth, gap, visibleColumns };
}

function syncCarouselControlPosition( carousel, card ) {
	const media = card?.querySelector( '.vvm-card-grid__card-media' );

	if ( ! media ) {
		return;
	}

	const carouselRect = carousel.getBoundingClientRect();
	const mediaRect = media.getBoundingClientRect();

	// Mobile controls sit on the image/content boundary, which depends on the
	// measured card width after the carousel has applied its responsive sizing.
	carousel.style.setProperty(
		'--vvm-card-grid-carousel-controls-top',
		`${ Math.round( mediaRect.bottom - carouselRect.top ) }px`
	);
}

function initializeCardGridCarousel( carousel ) {
	const viewport = carousel.querySelector( '[data-card-grid-viewport]' );
	const track = carousel.querySelector( '[data-card-grid-track]' );
	const cards = Array.from( track?.children ?? [] );
	const previousButton = carousel.querySelector( '[data-card-grid-prev]' );
	const nextButton = carousel.querySelector( '[data-card-grid-next]' );

	if (
		! viewport ||
		! track ||
		cards.length < 2 ||
		! previousButton ||
		! nextButton
	) {
		return;
	}

	let currentIndex = 0;
	let currentVisibleColumns = 1;

	const syncCarouselState = () => {
		const carouselLayout = syncCarouselCardWidth(
			carousel,
			viewport,
			track
		);
		currentVisibleColumns = carouselLayout.visibleColumns;
		const maxIndex = Math.max( 0, cards.length - currentVisibleColumns );

		currentIndex = Math.min( currentIndex, maxIndex );
		const firstCardOffset = cards[ 0 ]?.offsetLeft ?? 0;
		const currentCardOffset =
			cards[ currentIndex ]?.offsetLeft ?? firstCardOffset;
		track.style.transform = `translateX(-${
			currentCardOffset - firstCardOffset
		}px)`;
		syncCarouselControlPosition( carousel, cards[ currentIndex ] );

		previousButton.disabled = currentIndex <= 0;
		nextButton.disabled = currentIndex >= maxIndex;
	};

	previousButton.addEventListener( 'click', () => {
		currentIndex = Math.max( 0, currentIndex - 1 );
		syncCarouselState();
	} );

	nextButton.addEventListener( 'click', () => {
		const maxIndex = Math.max( 0, cards.length - currentVisibleColumns );

		currentIndex = Math.min( maxIndex, currentIndex + 1 );
		syncCarouselState();
	} );

	window.addEventListener( 'resize', syncCarouselState, { passive: true } );
	syncCarouselState();
}

function initializeVillaCinematicGrid( grid ) {
	const cards = Array.from( grid.querySelectorAll( '.vvm-card-grid__card--villa' ) );

	if ( cards.length === 0 ) {
		return;
	}

	cards.forEach( ( card, index ) => {
		card.style.setProperty( '--vvm-card-grid-enter-delay', `${ index * 0.2 }s` );
	} );

	if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
		grid.classList.add( 'is-in-view' );
		return;
	}

	grid.classList.add( 'has-cinematic-reveal' );

	if ( typeof window.IntersectionObserver !== 'function' ) {
		grid.classList.add( 'is-in-view' );
		return;
	}

	const observer = new window.IntersectionObserver(
		( entries ) => {
			entries.forEach( ( entry ) => {
				if ( ! entry.isIntersecting ) {
					return;
				}

				grid.classList.add( 'is-in-view' );
				observer.disconnect();
			} );
		},
		{
			threshold: 0.18,
		}
	);

	observer.observe( grid );
}

window.addEventListener( 'DOMContentLoaded', () => {
	document
		.querySelectorAll( '[data-card-grid-carousel]' )
		.forEach( initializeCardGridCarousel );

	document
		.querySelectorAll(
			'.wp-block-gutenberg-lab-blocks-card-grid.vvm-card-grid--villa-presentation-cinematic.vvm-card-grid--source-villas'
		)
		.forEach( initializeVillaCinematicGrid );
} );
