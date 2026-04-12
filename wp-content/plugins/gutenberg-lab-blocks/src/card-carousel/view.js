function getCardCarouselMetrics( viewport, track, slides ) {
	const firstOffset = slides[ 0 ]?.offsetLeft ?? 0;
	const maxTranslate = Math.max( 0, track.scrollWidth - viewport.clientWidth );
	const maxIndex = slides.reduce( ( lastVisibleIndex, slide, index ) => {
		return slide.offsetLeft - firstOffset <= maxTranslate + 1
			? index
			: lastVisibleIndex;
	}, 0 );

	return {
		firstOffset,
		maxIndex,
		maxTranslate,
	};
}

function initializeCardCarousel( carousel ) {
	const viewport = carousel.querySelector( '[data-card-carousel-viewport]' );
	const track = carousel.querySelector( '[data-card-carousel-track]' );
	const slides = Array.from( track?.children ?? [] );
	const previousButton = carousel.querySelector( '[data-card-carousel-prev]' );
	const nextButton = carousel.querySelector( '[data-card-carousel-next]' );

	if ( ! viewport || ! track || slides.length === 0 ) {
		return;
	}

	let currentIndex = 0;
	let activeTranslate = 0;
	let dragStartX = 0;
	let dragDelta = 0;
	let startTranslate = 0;
	let isDragging = false;
	let suppressClick = false;

	const applyTranslate = ( value ) => {
		track.style.transform = `translateX(-${ Math.max( 0, value ) }px)`;
	};

	const syncCarouselState = () => {
		const { firstOffset, maxIndex, maxTranslate } = getCardCarouselMetrics(
			viewport,
			track,
			slides
		);
		const hasOverflow = maxTranslate > 1;

		currentIndex = Math.min( currentIndex, maxIndex );
		activeTranslate = Math.min(
			( slides[ currentIndex ]?.offsetLeft ?? firstOffset ) - firstOffset,
			maxTranslate
		);

		track.style.removeProperty( 'transition' );
		applyTranslate( activeTranslate );

		carousel.classList.toggle( 'has-overflow', hasOverflow );

		if ( previousButton ) {
			previousButton.disabled = ! hasOverflow || currentIndex <= 0;
		}

		if ( nextButton ) {
			nextButton.disabled = ! hasOverflow || currentIndex >= maxIndex;
		}
	};

	const settleDrag = ( pointerId ) => {
		if ( ! isDragging ) {
			return;
		}

		const threshold = Math.min( 96, viewport.clientWidth * 0.15 );
		const { maxIndex } = getCardCarouselMetrics( viewport, track, slides );

		isDragging = false;
		suppressClick = Math.abs( dragDelta ) > 8;
		carousel.classList.remove( 'is-dragging' );
		track.style.removeProperty( 'transition' );

		if ( dragDelta <= -threshold ) {
			currentIndex = Math.min( maxIndex, currentIndex + 1 );
		} else if ( dragDelta >= threshold ) {
			currentIndex = Math.max( 0, currentIndex - 1 );
		}

		dragDelta = 0;
		syncCarouselState();

		if ( typeof pointerId === 'number' ) {
			viewport.releasePointerCapture?.( pointerId );
		}
	};

	previousButton?.addEventListener( 'click', () => {
		currentIndex = Math.max( 0, currentIndex - 1 );
		syncCarouselState();
	} );

	nextButton?.addEventListener( 'click', () => {
		const { maxIndex } = getCardCarouselMetrics( viewport, track, slides );

		currentIndex = Math.min( maxIndex, currentIndex + 1 );
		syncCarouselState();
	} );

	viewport.addEventListener( 'pointerdown', ( event ) => {
		if ( event.pointerType === 'mouse' && event.button !== 0 ) {
			return;
		}

		if ( event.target.closest( 'a, button, input, textarea, select' ) ) {
			return;
		}

		const { maxTranslate } = getCardCarouselMetrics( viewport, track, slides );

		if ( maxTranslate <= 1 ) {
			return;
		}

		isDragging = true;
		suppressClick = false;
		dragStartX = event.clientX;
		dragDelta = 0;
		startTranslate = activeTranslate;
		carousel.classList.add( 'is-dragging' );
		track.style.transition = 'none';
		viewport.setPointerCapture?.( event.pointerId );
	} );

	viewport.addEventListener( 'pointermove', ( event ) => {
		if ( ! isDragging ) {
			return;
		}

		const { maxTranslate } = getCardCarouselMetrics( viewport, track, slides );

		dragDelta = event.clientX - dragStartX;
		activeTranslate = Math.min(
			maxTranslate,
			Math.max( 0, startTranslate - dragDelta )
		);

		applyTranslate( activeTranslate );
	} );

	viewport.addEventListener( 'pointerup', ( event ) => {
		settleDrag( event.pointerId );
	} );

	viewport.addEventListener( 'pointercancel', ( event ) => {
		settleDrag( event.pointerId );
	} );

	viewport.addEventListener( 'pointerleave', ( event ) => {
		if ( event.pointerType === 'mouse' ) {
			settleDrag( event.pointerId );
		}
	} );

	track.addEventListener(
		'click',
		( event ) => {
			if ( ! suppressClick ) {
				return;
			}

			event.preventDefault();
			event.stopPropagation();
			suppressClick = false;
		},
		true
	);

	window.addEventListener( 'resize', syncCarouselState, { passive: true } );
	syncCarouselState();
}

window.addEventListener( 'DOMContentLoaded', () => {
	document
		.querySelectorAll( '[data-card-carousel]' )
		.forEach( initializeCardCarousel );
} );
