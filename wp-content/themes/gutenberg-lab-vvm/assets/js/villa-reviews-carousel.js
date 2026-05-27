function getVillaReviewsCarouselMetrics( viewport, track, slides ) {
	const firstOffset = slides[ 0 ]?.offsetLeft ?? 0;
	const maxTranslate = Math.max( 0, track.scrollWidth - viewport.clientWidth );
	let maxIndex = 0;

	for ( let index = 0; index < slides.length; index += 1 ) {
		const slideOffset = slides[ index ].offsetLeft - firstOffset;

		maxIndex = index;

		if ( slideOffset >= maxTranslate - 1 ) {
			break;
		}
	}

	return {
		firstOffset,
		maxIndex,
		maxTranslate,
	};
}

const villaReviewsLayoutEvent = 'vvm-villa-reviews:layout-change';
const villaReviewsCopyClampedClass = 'vvm-villa-reviews__copy--clamped';
const villaReviewsExpandedClass = 'is-review-expanded';
let villaReviewsReadMoreId = 0;

function setVillaReviewExpanded( review, copy, button, isExpanded ) {
	review.classList.toggle( villaReviewsExpandedClass, isExpanded );
	copy.classList.toggle( villaReviewsCopyClampedClass, ! isExpanded );
	button.setAttribute( 'aria-expanded', isExpanded ? 'true' : 'false' );
	button.textContent = isExpanded ? 'Show less' : 'Read more';
}

function createVillaReviewsReadMoreButton( review, copy ) {
	const button = document.createElement( 'button' );

	if ( ! copy.id ) {
		villaReviewsReadMoreId += 1;
		copy.id = `vvm-villa-review-copy-${ villaReviewsReadMoreId }`;
	}

	button.type = 'button';
	button.className = 'vvm-villa-reviews__read-more';
	button.setAttribute( 'aria-controls', copy.id );

	button.addEventListener( 'click', () => {
		const isExpanded = ! review.classList.contains(
			villaReviewsExpandedClass
		);

		setVillaReviewExpanded( review, copy, button, isExpanded );
		review.dispatchEvent(
			new CustomEvent( villaReviewsLayoutEvent, {
				bubbles: true,
			} )
		);
	} );

	return button;
}

function syncVillaReviewReadMore( review ) {
	const copy = review.querySelector( ':scope > .vvm-villa-reviews__copy' );

	if ( ! copy || ! copy.getClientRects().length ) {
		return;
	}

	let button = review.querySelector(
		':scope > .vvm-villa-reviews__read-more'
	);
	const wasExpanded = review.classList.contains( villaReviewsExpandedClass );

	review.classList.remove( villaReviewsExpandedClass );
	copy.classList.add( villaReviewsCopyClampedClass );

	const hasOverflow = copy.scrollHeight > copy.clientHeight + 1;

	if ( ! hasOverflow ) {
		copy.classList.remove( villaReviewsCopyClampedClass );
		button?.remove();
		return;
	}

	if ( ! button ) {
		button = createVillaReviewsReadMoreButton( review, copy );
		copy.after( button );
	}

	setVillaReviewExpanded( review, copy, button, wasExpanded );
}

function syncVillaReviewsReadMore( reviews ) {
	if ( ! reviews.getClientRects().length ) {
		return;
	}

	reviews
		.querySelectorAll( '.vvm-villa-reviews__review' )
		.forEach( syncVillaReviewReadMore );

	reviews.dispatchEvent(
		new CustomEvent( villaReviewsLayoutEvent, {
			bubbles: true,
		} )
	);
}

function initializeVillaReviewsReadMore( reviews ) {
	if ( reviews.dataset.vvmReadMoreInitialized === 'true' ) {
		return;
	}

	reviews.dataset.vvmReadMoreInitialized = 'true';

	const scheduleReadMoreSync = () => {
		window.requestAnimationFrame( () => syncVillaReviewsReadMore( reviews ) );
	};

	window.addEventListener( 'resize', scheduleReadMoreSync, { passive: true } );

	if ( 'MutationObserver' in window ) {
		const stackPanel = reviews.closest( '[data-stack-tabs-panel]' );

		if ( stackPanel ) {
			const mutationObserver = new MutationObserver( scheduleReadMoreSync );

			mutationObserver.observe( stackPanel, {
				attributes: true,
				attributeFilter: [ 'hidden', 'class' ],
			} );
		}
	}

	reviews
		.closest( '[data-stack-tabs-root]' )
		?.addEventListener( 'click', ( event ) => {
			if ( event.target.closest( '[data-stack-tabs-tab-button]' ) ) {
				scheduleReadMoreSync();
			}
		} );

	scheduleReadMoreSync();
}

function createVillaReviewsArrowIcon() {
	const icon = document.createElementNS( 'http://www.w3.org/2000/svg', 'svg' );
	const path = document.createElementNS( 'http://www.w3.org/2000/svg', 'path' );

	icon.classList.add( 'vvm-slider-button__icon' );
	icon.setAttribute( 'viewBox', '0 0 24 24' );
	icon.setAttribute( 'aria-hidden', 'true' );
	icon.setAttribute( 'focusable', 'false' );
	path.setAttribute( 'd', 'M9 6l6 6-6 6' );
	icon.appendChild( path );

	return icon;
}

function createVillaReviewsButton( direction ) {
	const button = document.createElement( 'button' );

	button.type = 'button';
	button.className = [
		'vvm-villa-reviews__button',
		'vvm-slider-button',
		`vvm-slider-button--${ direction }`,
	].join( ' ' );
	button.setAttribute(
		'aria-label',
		direction === 'prev' ? 'Previous review' : 'Next review'
	);
	button.appendChild( createVillaReviewsArrowIcon() );

	return button;
}

function initializeVillaReviewsCarousel( reviews ) {
	const list = reviews.querySelector( ':scope .vvm-villa-reviews__list' );
	const slides = Array.from(
		list?.querySelectorAll( ':scope > .vvm-villa-reviews__review' ) ?? []
	);

	if ( ! list || slides.length < 2 ) {
		return;
	}

	const viewport = document.createElement( 'div' );
	const track = document.createElement( 'div' );
	const controls = document.createElement( 'div' );
	const previousButton = createVillaReviewsButton( 'prev' );
	const nextButton = createVillaReviewsButton( 'next' );
	const status = document.createElement( 'p' );

	let currentIndex = 0;
	let activeTranslate = 0;
	let dragStartX = 0;
	let dragDelta = 0;
	let startTranslate = 0;
	let isDragging = false;
	let suppressClick = false;

	viewport.className = 'vvm-villa-reviews__viewport';
	track.className = 'vvm-villa-reviews__track';
	controls.className = 'vvm-villa-reviews__controls';
	status.className = 'vvm-villa-reviews__status screen-reader-text';
	status.setAttribute( 'aria-live', 'polite' );

	slides.forEach( ( slide, index ) => {
		slide.classList.add( 'vvm-villa-reviews__slide' );
		slide.setAttribute( 'role', 'group' );
		slide.setAttribute( 'aria-roledescription', 'slide' );
		slide.setAttribute( 'aria-label', `${ index + 1 } of ${ slides.length }` );
		track.appendChild( slide );
	} );

	viewport.appendChild( track );
	controls.append( previousButton, nextButton );
	list.replaceChildren( viewport, controls, status );

	reviews.classList.add( 'is-carousel' );
	list.classList.add( 'is-carousel' );
	list.setAttribute( 'role', 'region' );
	list.setAttribute( 'aria-roledescription', 'carousel' );
	list.setAttribute( 'aria-label', 'Guest reviews' );

	const applyTranslate = ( value ) => {
		track.style.transform = `translateX(-${ Math.max( 0, value ) }px)`;
	};

	const getVisibleRange = () => {
		const viewportEnd = activeTranslate + viewport.clientWidth + 1;
		let firstVisible = currentIndex;
		let lastVisible = currentIndex;

		slides.forEach( ( slide, index ) => {
			const slideStart = slide.offsetLeft - ( slides[ 0 ]?.offsetLeft ?? 0 );
			const slideEnd = slideStart + slide.offsetWidth;
			const isVisible =
				slideEnd > activeTranslate + 1 && slideStart < viewportEnd;

			if ( ! isVisible ) {
				return;
			}

			firstVisible = Math.min( firstVisible, index );
			lastVisible = Math.max( lastVisible, index );
		} );

		return {
			firstVisible,
			lastVisible,
		};
	};

	const syncViewportHeight = ( firstVisible, lastVisible ) => {
		const visibleHeight = slides
			.slice( firstVisible, lastVisible + 1 )
			.reduce(
				( tallestHeight, slide ) =>
					Math.max( tallestHeight, slide.offsetHeight ),
				0
			);

		if ( ! visibleHeight ) {
			viewport.style.removeProperty( 'height' );
			return;
		}

		const nextHeight = `${ Math.ceil( visibleHeight ) }px`;

		if ( viewport.style.height !== nextHeight ) {
			viewport.style.height = nextHeight;
		}
	};

	const syncCarouselState = () => {
		if ( viewport.clientWidth <= 0 ) {
			reviews.classList.remove( 'has-carousel-overflow' );
			viewport.style.removeProperty( 'height' );
			controls.hidden = true;
			controls.setAttribute( 'aria-hidden', 'true' );
			previousButton.disabled = true;
			nextButton.disabled = true;
			return;
		}

		const { firstOffset, maxIndex, maxTranslate } =
			getVillaReviewsCarouselMetrics( viewport, track, slides );
		const hasOverflow = maxTranslate > 1;

		currentIndex = Math.min( currentIndex, maxIndex );
		activeTranslate = Math.min(
			( slides[ currentIndex ]?.offsetLeft ?? firstOffset ) - firstOffset,
			maxTranslate
		);

		track.style.removeProperty( 'transition' );
		applyTranslate( activeTranslate );

		reviews.classList.toggle( 'has-carousel-overflow', hasOverflow );
		controls.hidden = ! hasOverflow;
		controls.setAttribute( 'aria-hidden', hasOverflow ? 'false' : 'true' );
		previousButton.disabled = ! hasOverflow || currentIndex <= 0;
		nextButton.disabled = ! hasOverflow || currentIndex >= maxIndex;

		const { firstVisible, lastVisible } = getVisibleRange();
		syncViewportHeight( firstVisible, lastVisible );
		status.textContent =
			firstVisible === lastVisible
				? `Showing review ${ firstVisible + 1 } of ${ slides.length }`
				: `Showing reviews ${ firstVisible + 1 } to ${
						lastVisible + 1
					} of ${ slides.length }`;
		};

	const scheduleCarouselSync = () => {
		window.requestAnimationFrame( syncCarouselState );
	};

	const settleDrag = ( pointerId ) => {
		if ( ! isDragging ) {
			return;
		}

		const threshold = Math.min( 96, viewport.clientWidth * 0.15 );
		const { maxIndex } = getVillaReviewsCarouselMetrics(
			viewport,
			track,
			slides
		);

		isDragging = false;
		suppressClick = Math.abs( dragDelta ) > 8;
		reviews.classList.remove( 'is-dragging' );
		track.style.removeProperty( 'transition' );

		if ( dragDelta <= -threshold ) {
			currentIndex = Math.min( maxIndex, currentIndex + 1 );
		} else if ( dragDelta >= threshold ) {
			currentIndex = Math.max( 0, currentIndex - 1 );
		}

		dragDelta = 0;
		syncCarouselState();
		viewport.releasePointerCapture?.( pointerId );
	};

	previousButton.addEventListener( 'click', () => {
		currentIndex = Math.max( 0, currentIndex - 1 );
		syncCarouselState();
	} );

	nextButton.addEventListener( 'click', () => {
		const { maxIndex } = getVillaReviewsCarouselMetrics(
			viewport,
			track,
			slides
		);

		currentIndex = Math.min( maxIndex, currentIndex + 1 );
		syncCarouselState();
	} );

	viewport.addEventListener( 'pointerdown', ( event ) => {
		if ( event.pointerType === 'mouse' && event.button !== 0 ) {
			return;
		}

		const { maxTranslate } = getVillaReviewsCarouselMetrics(
			viewport,
			track,
			slides
		);

		if ( maxTranslate <= 1 ) {
			return;
		}

		isDragging = true;
		suppressClick = false;
		dragStartX = event.clientX;
		dragDelta = 0;
		startTranslate = activeTranslate;
		reviews.classList.add( 'is-dragging' );
		track.style.transition = 'none';
		viewport.setPointerCapture?.( event.pointerId );
	} );

	viewport.addEventListener( 'pointermove', ( event ) => {
		if ( ! isDragging ) {
			return;
		}

		const { maxTranslate } = getVillaReviewsCarouselMetrics(
			viewport,
			track,
			slides
		);

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

	if ( 'ResizeObserver' in window ) {
		const resizeObserver = new ResizeObserver( scheduleCarouselSync );

		resizeObserver.observe( viewport );
	}

	if ( 'MutationObserver' in window ) {
		const stackPanel = reviews.closest( '[data-stack-tabs-panel]' );

		if ( stackPanel ) {
			const mutationObserver = new MutationObserver( scheduleCarouselSync );

			mutationObserver.observe( stackPanel, {
				attributes: true,
				attributeFilter: [ 'hidden', 'class' ],
			} );
		}
	}

	reviews
		.closest( '[data-stack-tabs-root]' )
		?.addEventListener( 'click', ( event ) => {
			if ( event.target.closest( '[data-stack-tabs-tab-button]' ) ) {
				scheduleCarouselSync();
			}
		} );

	reviews.addEventListener( villaReviewsLayoutEvent, scheduleCarouselSync );

	syncCarouselState();
}

document.addEventListener( 'DOMContentLoaded', () => {
	document
		.querySelectorAll( '.vvm-villa-reviews' )
		.forEach( ( reviews ) => {
			initializeVillaReviewsReadMore( reviews );
			initializeVillaReviewsCarousel( reviews );
		} );
} );
