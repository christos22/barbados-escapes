document.addEventListener( 'DOMContentLoaded', () => {
	document.documentElement.classList.add( 'js' );

	const header = document.querySelector( '.vvm-header' );
	const hasHeroHeader = document.body.classList.contains( 'vvm-has-hero-header' );
	const footer = document.querySelector( '.vvm-footer' );

	// Keep a real pixel scrollbar value available to CSS for edge-to-edge math.
	const syncScrollbarWidth = () => {
		const scrollbarWidth = Math.max(
			window.innerWidth - document.documentElement.clientWidth,
			0
		);

		document.documentElement.style.setProperty(
			'--vvm-scrollbar-width',
			`${ scrollbarWidth }px`
		);
	};

	const initializeFooterReveal = () => {
		if ( ! footer ) {
			return;
		}

		const prefersReducedMotion = window.matchMedia(
			'(prefers-reduced-motion: reduce)'
		).matches;

		if ( prefersReducedMotion || ! ( 'IntersectionObserver' in window ) ) {
			footer.classList.add( 'is-visible' );
			return;
		}

		// Reveal the footer once it enters view so the stagger only runs once.
		const observer = new IntersectionObserver(
			( entries ) => {
				entries.forEach( ( entry ) => {
					if ( ! entry.isIntersecting ) {
						return;
					}

					footer.classList.add( 'is-visible' );
					observer.disconnect();
				} );
			},
			{
				threshold: 0.2,
				rootMargin: '0px 0px -8% 0px',
			}
		);

		observer.observe( footer );
	};

	syncScrollbarWidth();
	initializeFooterReveal();
	window.addEventListener( 'resize', syncScrollbarWidth, { passive: true } );

	if ( ! header ) {
		return;
	}

	// Pages without a hero keep the header in normal document flow.
	if ( ! hasHeroHeader ) {
		header.classList.remove( 'is-scrolled' );
		return;
	}

	// Hero pages keep the transparent overlay until the visitor scrolls a bit.
	const syncHeaderState = () => {
		const isScrolled = window.scrollY > 32;

		header.classList.toggle( 'is-scrolled', isScrolled );
	};

	syncHeaderState();
	window.addEventListener( 'scroll', syncHeaderState, { passive: true } );
} );
