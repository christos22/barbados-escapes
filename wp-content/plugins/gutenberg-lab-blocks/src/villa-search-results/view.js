const FRAGMENT_PARAMETER = 'villa_results_fragment';

const setupVillaInfiniteScroll = ( results ) => {
	const items = results.querySelector( '[data-vvm-villa-results-items]' );
	const pagination = results.querySelector(
		'[data-vvm-villa-results-pagination]'
	);
	const nextLink = pagination?.querySelector( 'a.next' );
	const sentinel = results.querySelector(
		'[data-vvm-villa-results-sentinel]'
	);

	// Keep normal pagination visible when enhancement is unsupported.
	if (
		! items ||
		! pagination ||
		! nextLink ||
		! sentinel ||
		typeof window.IntersectionObserver !== 'function'
	) {
		return;
	}

	let nextUrl = nextLink.href;
	let isLoading = false;

	const finish = () => {
		observer?.disconnect();
		results.classList.remove( 'is-loading' );
		results.removeAttribute( 'aria-busy' );
	};

	const restorePagination = () => {
		observer?.disconnect();
		results.classList.remove( 'is-infinite-scroll-ready', 'is-loading' );
		results.removeAttribute( 'aria-busy' );
	};

	const loadNextPage = async () => {
		if ( isLoading || ! nextUrl ) {
			return;
		}

		isLoading = true;
		results.classList.add( 'is-loading' );
		results.setAttribute( 'aria-busy', 'true' );

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
			items.append( ...template.content.children );

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
				finish();
				return;
			}

			nextLink.href = nextUrl;
		} catch {
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
