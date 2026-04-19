function getTwoUpMetrics( carousel, viewport, track, slides ) {
	const firstOffset = slides[ 0 ]?.offsetLeft ?? 0;
	const styles = window.getComputedStyle( carousel );
	const firstRealIndex = slides.findIndex(
		( slide ) => ! slide.classList.contains( 'is-clone' )
	);
	const realSlides = slides.filter(
		( slide ) => ! slide.classList.contains( 'is-clone' )
	);
	const visibleSlides = Math.max(
		1,
		parseInt(
			styles.getPropertyValue( '--vvm-two-up-carousel-visible-slides' ),
			10
		) || 1
	);
	const slideWidth = slides[ 0 ]?.clientWidth ?? viewport.clientWidth;
	const slideGap =
		slides.length > 1
			? Math.max(
					0,
					(slides[ 1 ]?.offsetLeft ?? 0) -
						(slides[ 0 ]?.offsetLeft ?? 0) -
						slideWidth
			  )
			: 0;
	const usedWidth =
		slideWidth * visibleSlides + slideGap * Math.max( 0, visibleSlides - 1 );
	const peek = Math.max( 0, ( viewport.clientWidth - usedWidth ) / 2 );
	const minIndex = -1 === firstRealIndex ? 0 : firstRealIndex;
	const maxIndex =
		minIndex +
		Math.max( 0, realSlides.length - visibleSlides );
	const maxTranslate = Math.max( 0, track.scrollWidth - viewport.clientWidth );

	return {
		firstOffset,
		minIndex,
		maxIndex,
		maxTranslate,
		peek: visibleSlides > 1 ? peek : 0,
		visibleSlides,
	};
}

function getTargetTranslate( slides, index, metrics ) {
	// Middle positions intentionally back up by the peek width so the previous
	// and next cards stay partially visible around the active pair.
	const rawOffset =
		(slides[ index ]?.offsetLeft ?? metrics.firstOffset) -
		metrics.firstOffset -
		( index > 0 ? metrics.peek : 0 );

	return Math.min( metrics.maxTranslate, Math.max( 0, rawOffset ) );
}

function syncActiveSlides( slides, currentIndex, visibleSlides ) {
	slides.forEach( ( slide, index ) => {
		slide.classList.toggle(
			'is-active',
			! slide.classList.contains( 'is-clone' ) &&
				index >= currentIndex &&
				index < currentIndex + visibleSlides
		);
	} );
}

function getLoopedIndex( nextIndex, minIndex, maxIndex ) {
	if ( maxIndex <= minIndex ) {
		return minIndex;
	}

	if ( nextIndex < minIndex ) {
		return maxIndex;
	}

	if ( nextIndex > maxIndex ) {
		return minIndex;
	}

	return nextIndex;
}

function initializeTwoUpCarousel( carousel ) {
	const viewport = carousel.querySelector( '[data-two-up-carousel-viewport]' );
	const track = carousel.querySelector( '[data-two-up-carousel-track]' );
	const slides = Array.from(
		carousel.querySelectorAll( '[data-two-up-carousel-slide]' )
	);
	const previousButton = carousel
		.closest( '[data-two-up-carousel-root]' )
		?.querySelector( '[data-two-up-carousel-prev]' );
	const nextButton = carousel
		.closest( '[data-two-up-carousel-root]' )
		?.querySelector( '[data-two-up-carousel-next]' );

	if ( ! viewport || ! track || slides.length === 0 ) {
		return;
	}

	let currentIndex = getTwoUpMetrics( carousel, viewport, track, slides ).minIndex;
	let activeTranslate = 0;
	let dragStartX = 0;
	let dragDelta = 0;
	let startTranslate = 0;
	let isDragging = false;
	let suppressClick = false;

	const applyTranslate = ( value ) => {
		track.style.transform = `translate3d(-${ Math.max( 0, value ) }px, 0, 0)`;
	};

	const syncCarouselState = ( { animated = false } = {} ) => {
		const metrics = getTwoUpMetrics( carousel, viewport, track, slides );
		const hasOverflow = metrics.maxTranslate > 1;

		currentIndex = Math.max(
			metrics.minIndex,
			Math.min( currentIndex, metrics.maxIndex )
		);
		activeTranslate = getTargetTranslate( slides, currentIndex, metrics );

		if ( animated ) {
			track.style.removeProperty( 'transition' );
		} else {
			track.style.transition = 'none';
		}

		applyTranslate( activeTranslate );
		syncActiveSlides( slides, currentIndex, metrics.visibleSlides );
		carousel.classList.toggle( 'has-overflow', hasOverflow );

		if ( previousButton ) {
			previousButton.disabled = ! hasOverflow;
		}

		if ( nextButton ) {
			nextButton.disabled = ! hasOverflow;
		}

		if ( ! animated ) {
			window.requestAnimationFrame( () => {
				track.style.removeProperty( 'transition' );
			} );
		}
	};

	const settleDrag = ( pointerId ) => {
		if ( ! isDragging ) {
			return;
		}

		const threshold = Math.min( 96, viewport.clientWidth * 0.14 );
		const { maxIndex, minIndex } = getTwoUpMetrics(
			carousel,
			viewport,
			track,
			slides
		);

		isDragging = false;
		suppressClick = Math.abs( dragDelta ) > 8;
		carousel.classList.remove( 'is-dragging' );

		if ( dragDelta <= -threshold ) {
			currentIndex = getLoopedIndex( currentIndex + 1, minIndex, maxIndex );
		} else if ( dragDelta >= threshold ) {
			currentIndex = getLoopedIndex( currentIndex - 1, minIndex, maxIndex );
		}

		dragDelta = 0;
		syncCarouselState( { animated: true } );

		if ( typeof pointerId === 'number' ) {
			viewport.releasePointerCapture?.( pointerId );
		}
	};

	previousButton?.addEventListener( 'click', () => {
		const { maxIndex, minIndex } = getTwoUpMetrics(
			carousel,
			viewport,
			track,
			slides
		);

		currentIndex = getLoopedIndex( currentIndex - 1, minIndex, maxIndex );
		syncCarouselState( { animated: true } );
	} );

	nextButton?.addEventListener( 'click', () => {
		const { maxIndex, minIndex } = getTwoUpMetrics(
			carousel,
			viewport,
			track,
			slides
		);

		currentIndex = getLoopedIndex( currentIndex + 1, minIndex, maxIndex );
		syncCarouselState( { animated: true } );
	} );

	viewport.addEventListener( 'pointerdown', ( event ) => {
		if ( event.pointerType === 'mouse' && event.button !== 0 ) {
			return;
		}

		if ( event.target.closest( 'a, button, input, textarea, select' ) ) {
			return;
		}

		const { maxTranslate } = getTwoUpMetrics( carousel, viewport, track, slides );

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

		const { maxTranslate } = getTwoUpMetrics( carousel, viewport, track, slides );

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

	window.addEventListener(
		'resize',
		() => {
			syncCarouselState();
		},
		{ passive: true }
	);

	syncCarouselState();
}

window.addEventListener( 'DOMContentLoaded', () => {
	document
		.querySelectorAll( '[data-two-up-carousel]' )
		.forEach( initializeTwoUpCarousel );
} );
