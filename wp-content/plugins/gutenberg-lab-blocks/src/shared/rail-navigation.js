const RAIL_SCROLL_TOLERANCE = 1;
const RAIL_MIN_SCROLLABLE_DISTANCE = 12;

function getRailMaxScroll( track ) {
	return Math.max( 0, track.scrollWidth - track.clientWidth );
}

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

function getRailItemTargetScrollLeft(
	track,
	item,
	{ align = 'nearest' } = {}
) {
	if ( ! track || ! item ) {
		return 0;
	}

	const maxScrollLeft = getRailMaxScroll( track );
	const currentScrollLeft = track.scrollLeft;
	const itemStart = item.offsetLeft;
	const itemEnd = itemStart + item.offsetWidth;
	const visibleStart = currentScrollLeft;
	const visibleEnd = currentScrollLeft + track.clientWidth;
	const isFullyVisible = itemStart >= visibleStart && itemEnd <= visibleEnd;

	if ( isFullyVisible ) {
		return currentScrollLeft;
	}

	if ( 'center' === align ) {
		return Math.min(
			maxScrollLeft,
			Math.max(
				0,
				itemStart - ( track.clientWidth - item.offsetWidth ) / 2
			)
		);
	}

	if ( itemStart < visibleStart ) {
		return Math.min( maxScrollLeft, Math.max( 0, itemStart ) );
	}

	return Math.min(
		maxScrollLeft,
		Math.max( 0, itemEnd - track.clientWidth )
	);
}

export function syncRailButtons(
	track,
	previousButton,
	nextButton,
	{ hideAtEdges = false } = {}
) {
	if ( ! track ) {
		return;
	}

	const maxScrollLeft = getRailMaxScroll( track );
	const canScroll = maxScrollLeft > RAIL_MIN_SCROLLABLE_DISTANCE;
	const isAtStart = track.scrollLeft <= RAIL_SCROLL_TOLERANCE;
	const isAtEnd = track.scrollLeft >= maxScrollLeft - RAIL_SCROLL_TOLERANCE;

	[ previousButton, nextButton ].forEach( ( button ) => {
		if ( button ) {
			button.hidden = ! canScroll;
		}
	} );

	if ( previousButton ) {
		previousButton.hidden = ! canScroll || ( hideAtEdges && isAtStart );
		previousButton.disabled = ! canScroll || isAtStart;
	}

	if ( nextButton ) {
		nextButton.hidden = ! canScroll || ( hideAtEdges && isAtEnd );
		nextButton.disabled = ! canScroll || isAtEnd;
	}
}

function scrollRailTo( track, left, prefersReducedMotion ) {
	if ( ! track ) {
		return;
	}

	track.scrollTo( {
		left,
		behavior: prefersReducedMotion ? 'auto' : 'smooth',
	} );
}

export function scrollRailByStep(
	track,
	slides,
	direction,
	prefersReducedMotion
) {
	if ( ! track ) {
		return;
	}

	const targetScrollLeft = Math.min(
		getRailMaxScroll( track ),
		Math.max(
			0,
			track.scrollLeft + ( getRailStep( track, slides ) || track.clientWidth ) * direction
		)
	);

	scrollRailTo( track, targetScrollLeft, prefersReducedMotion );
}

export function revealRailItem(
	track,
	item,
	prefersReducedMotion,
	options = {}
) {
	if ( ! track || ! item ) {
		return;
	}

	const targetScrollLeft = getRailItemTargetScrollLeft( track, item, options );

	if ( Math.abs( track.scrollLeft - targetScrollLeft ) < RAIL_SCROLL_TOLERANCE ) {
		return;
	}

	scrollRailTo( track, targetScrollLeft, prefersReducedMotion );
}
