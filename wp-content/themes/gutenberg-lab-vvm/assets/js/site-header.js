document.addEventListener( 'DOMContentLoaded', () => {
	// The VVM header stays transparent until the visitor scrolls a bit.
	const header = document.querySelector( '.vvm-header' );

	if ( ! header ) {
		return;
	}

	const syncHeaderState = () => {
		const isScrolled = window.scrollY > 32;

		header.classList.toggle( 'is-scrolled', isScrolled );
		document.body.classList.toggle( 'vvm-header-is-scrolled', isScrolled );
	};

	syncHeaderState();
	window.addEventListener( 'scroll', syncHeaderState, { passive: true } );
} );
