document.addEventListener( 'DOMContentLoaded', () => {
	document.documentElement.classList.add( 'js' );

	const header = document.querySelector( '.vvm-header' );
	const hasHeroHeader = document.body.classList.contains( 'vvm-has-hero-header' );
	const footer = document.querySelector( '.vvm-footer' );
	const bedroomLevelSections = document.querySelectorAll( '.vvm-bedroom-levels' );

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

	const initializeBedroomLevels = () => {
		bedroomLevelSections.forEach( ( section ) => {
			const buttons = Array.from(
				section.querySelectorAll( '.vvm-bedroom-levels__nav-button' )
			);
			const panels = Array.from(
				section.querySelectorAll( '.vvm-bedroom-levels__panel' )
			);

			if ( ! buttons.length || ! panels.length ) {
				return;
			}

			section
				.querySelector( '.vvm-bedroom-levels__nav' )
				?.setAttribute( 'role', 'tablist' );

			const activatePanel = ( targetPanel ) => {
				if ( ! targetPanel ) {
					return;
				}

				panels.forEach( ( panel ) => {
					const isActive = panel === targetPanel;

					panel.classList.toggle( 'is-active', isActive );
					panel.hidden = ! isActive;
				} );

				buttons.forEach( ( button ) => {
					const link = button.querySelector( '.wp-block-button__link' );
					const targetId = link?.hash ? link.hash.slice( 1 ) : '';
					const isActive = targetId === targetPanel.id;

					button.classList.toggle( 'is-active', isActive );
					link?.setAttribute( 'aria-selected', String( isActive ) );
				} );
			};

			buttons.forEach( ( button, index ) => {
				const link = button.querySelector( '.wp-block-button__link' );
				const targetId = link?.hash ? link.hash.slice( 1 ) : '';
				const panelById = targetId ? document.getElementById( targetId ) : null;
				const targetPanel =
					( panelById && section.contains( panelById ) && panelById ) ||
					panels[ index ];

				if ( ! link || ! targetPanel ) {
					return;
				}

				link.setAttribute( 'role', 'tab' );
				link.setAttribute( 'aria-controls', targetPanel.id );

				link.addEventListener( 'click', ( event ) => {
					event.preventDefault();
					activatePanel( targetPanel );
				} );
			} );

			panels.forEach( ( panel ) => {
				panel.setAttribute( 'role', 'tabpanel' );
			} );

			activatePanel(
				panels.find( ( panel ) => panel.classList.contains( 'is-active' ) ) ||
					panels[ 0 ]
			);
		} );
	};

	syncScrollbarWidth();
	initializeFooterReveal();
	initializeBedroomLevels();
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
