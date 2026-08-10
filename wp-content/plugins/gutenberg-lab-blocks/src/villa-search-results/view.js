const FRAGMENT_PARAMETER = 'villa_results_fragment';

const setupVillaInfiniteScroll = ( results ) => {
	const items = results.querySelector( '[data-vvm-villa-results-items]' );
	const pagination = results.querySelector(
		'[data-vvm-villa-results-pagination]'
	);
	const nextLink = pagination?.querySelector( 'a.next' );
	const status = results.querySelector( '[data-vvm-villa-results-status]' );
	const sentinel = results.querySelector(
		'[data-vvm-villa-results-sentinel]'
	);

	// Keep normal pagination visible when enhancement is unsupported.
	if (
		! items ||
		! pagination ||
		! nextLink ||
		! status ||
		! sentinel ||
		typeof window.IntersectionObserver !== 'function'
	) {
		return;
	}

	let nextUrl = nextLink.href;
	let isLoading = false;
	const loadingMessage =
		results.dataset.vvmVillaResultsLoading || 'Loading more villas…';
	const loadedSingularMessage =
		results.dataset.vvmVillaResultsLoadedSingular || '1 more villa loaded.';
	const loadedPluralMessage =
		results.dataset.vvmVillaResultsLoadedPlural || '%d more villas loaded.';
	const completeMessage =
		results.dataset.vvmVillaResultsComplete || 'All villas loaded.';
	const errorMessage =
		results.dataset.vvmVillaResultsError ||
		'More villas could not load automatically. Use the next page link.';

	const finish = ( loadedMessage = '' ) => {
		observer?.disconnect();
		status.textContent = loadedMessage
			? `${ loadedMessage } ${ completeMessage }`
			: completeMessage;
		results.classList.remove( 'is-loading' );
		results.removeAttribute( 'aria-busy' );
	};

	const restorePagination = () => {
		observer?.disconnect();
		results.classList.remove( 'is-infinite-scroll-ready', 'is-loading' );
		results.removeAttribute( 'aria-busy' );
		status.textContent = errorMessage;
	};

	const loadNextPage = async () => {
		if ( isLoading || ! nextUrl ) {
			return;
		}

		isLoading = true;
		results.classList.add( 'is-loading' );
		results.setAttribute( 'aria-busy', 'true' );
		status.textContent = loadingMessage;

		try {
			const fragmentUrl = new URL( nextUrl, window.location.href );

			// Pagination is generated locally. Refuse any unexpected cross-origin
			// URL before sending credentials or trusting returned card markup.
			if ( fragmentUrl.origin !== window.location.origin ) {
				throw new Error( 'Villa results URL must be same-origin.' );
			}

			fragmentUrl.searchParams.set( FRAGMENT_PARAMETER, '1' );

			const response = await window.fetch( fragmentUrl, {
				credentials: 'same-origin',
				headers: {
					Accept: 'application/json',
				},
			} );

			if ( ! response.ok ) {
				throw new Error( 'Villa results request failed.' );
			}

			const payload = await response.json();
			const html = payload?.success ? payload.data?.html : '';

			if ( typeof html !== 'string' || html.trim() === '' ) {
				finish();
				return;
			}

			const template = document.createElement( 'template' );
			template.innerHTML = html.trim();
			const loadedCards = Array.from( template.content.children );
			const loadedMessage =
				loadedCards.length === 1
					? loadedSingularMessage
					: loadedPluralMessage.replace(
							'%d',
							String( loadedCards.length )
					  );
			items.append( ...loadedCards );

			const responseNextUrl = payload.data.nextUrl || '';

			if (
				responseNextUrl &&
				new URL( responseNextUrl, window.location.href ).origin !==
					window.location.origin
			) {
				throw new Error(
					'Villa results response must be same-origin.'
				);
			}

			nextUrl = responseNextUrl;
			isLoading = false;
			results.classList.remove( 'is-loading' );
			results.removeAttribute( 'aria-busy' );

			if ( ! nextUrl ) {
				finish( loadedMessage );
				return;
			}

			nextLink.href = nextUrl;
			status.textContent = loadedMessage;
		} catch ( error ) {
			isLoading = false;
			restorePagination();
		}
	};

	const observer = new window.IntersectionObserver(
		( entries ) => {
			if ( entries.some( ( entry ) => entry.isIntersecting ) ) {
				loadNextPage();
			}
		},
		{
			rootMargin: '600px 0px',
			threshold: 0,
		}
	);

	results.classList.add( 'is-infinite-scroll-ready' );
	observer.observe( sentinel );
};

const initVillaInfiniteScroll = () => {
	document
		.querySelectorAll( '[data-vvm-villa-search-results]' )
		.forEach( setupVillaInfiniteScroll );
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initVillaInfiniteScroll, {
		once: true,
	} );
} else {
	initVillaInfiniteScroll();
}
