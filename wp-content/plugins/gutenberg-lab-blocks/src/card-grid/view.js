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

function initializeCardGridCarousel( carousel ) {
	const track = carousel.querySelector( '[data-card-grid-track]' );
	const cards = Array.from( track?.children ?? [] );
	const previousButton = carousel.querySelector( '[data-card-grid-prev]' );
	const nextButton = carousel.querySelector( '[data-card-grid-next]' );

	if ( ! track || cards.length < 2 || ! previousButton || ! nextButton ) {
		return;
	}

	let currentIndex = 0;

	const syncCarouselState = () => {
		const visibleColumns = getVisibleColumns( carousel );
		const maxIndex = Math.max( 0, cards.length - visibleColumns );

		currentIndex = Math.min( currentIndex, maxIndex );
		const firstCardOffset = cards[ 0 ]?.offsetLeft ?? 0;
		const currentCardOffset =
			cards[ currentIndex ]?.offsetLeft ?? firstCardOffset;
		track.style.transform = `translateX(-${
			currentCardOffset - firstCardOffset
		}px)`;

		previousButton.disabled = currentIndex <= 0;
		nextButton.disabled = currentIndex >= maxIndex;
	};

	previousButton.addEventListener( 'click', () => {
		currentIndex = Math.max( 0, currentIndex - 1 );
		syncCarouselState();
	} );

	nextButton.addEventListener( 'click', () => {
		const visibleColumns = getVisibleColumns( carousel );
		const maxIndex = Math.max( 0, cards.length - visibleColumns );

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
