document.addEventListener( 'DOMContentLoaded', () => {
	const header = document.querySelector( '.vvm-header' );
	const hasHeroHeader = document.body.classList.contains( 'vvm-has-hero-header' );

	if ( ! header ) {
		return;
	}

	// Pages without a hero keep the header in normal document flow.
	if ( ! hasHeroHeader ) {
		header.classList.remove( 'is-scrolled' );
		document.body.classList.remove( 'vvm-header-is-scrolled' );
		return;
	}

	// Hero pages keep the transparent overlay until the visitor scrolls a bit.
	const syncHeaderState = () => {
		const isScrolled = window.scrollY > 32;

		header.classList.toggle( 'is-scrolled', isScrolled );
		document.body.classList.toggle( 'vvm-header-is-scrolled', isScrolled );
	};

	syncHeaderState();
	window.addEventListener( 'scroll', syncHeaderState, { passive: true } );
} );
