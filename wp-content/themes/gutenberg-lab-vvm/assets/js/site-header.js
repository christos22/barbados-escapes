document.addEventListener( 'DOMContentLoaded', () => {
	document.documentElement.classList.add( 'js' );

	const header = document.querySelector( '.vvm-header' );
	const hasHeroHeader = document.body.classList.contains( 'vvm-has-hero-header' );
	const footer = document.querySelector( '.vvm-footer' );
	const bedroomLevelSections = document.querySelectorAll( '.vvm-bedroom-levels' );
	const villaContactForms = document.querySelectorAll( '.vvm-villa-contact-form' );
	const lazyMaps = document.querySelectorAll( '[data-vvm-lazy-map]' );

	// Keep a real pixel scrollbar value available to CSS for edge-to-edge math.
	const syncScrollbarWidth = () => {
		const measuredScrollbarWidth = Math.max(
			window.innerWidth - document.documentElement.clientWidth,
			0
		);
		const storedScrollbarWidth =
			parseFloat(
				document.documentElement.style.getPropertyValue(
					'--vvm-scrollbar-width'
				)
			) || 0;
		const scrollbarWidth =
			document.documentElement.classList.contains( 'has-modal-open' ) &&
			storedScrollbarWidth > 0
				? storedScrollbarWidth
				: measuredScrollbarWidth;

		document.documentElement.style.setProperty(
			'--vvm-scrollbar-width',
			`${ scrollbarWidth }px`
		);
		document.documentElement.classList.toggle(
			'vvm-has-scrollbar',
			scrollbarWidth > 0
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

	const initializeVillaContactDateRanges = () => {
		villaContactForms.forEach( ( form ) => {
			const arrival = form.querySelector( '[name="preferred-arrival"]' );
			const departure = form.querySelector( '[name="preferred-departure"]' );

			if (
				! arrival ||
				! departure ||
				arrival.type !== 'date' ||
				departure.type !== 'date'
			) {
				return;
			}

			const syncDepartureMinimum = () => {
				if ( arrival.value ) {
					departure.min = arrival.value;

					if ( departure.value && departure.value < arrival.value ) {
						departure.value = '';
					}

					return;
				}

				departure.removeAttribute( 'min' );
			};

			arrival.addEventListener( 'change', syncDepartureMinimum );
			arrival.addEventListener( 'input', syncDepartureMinimum );
			syncDepartureMinimum();
		} );
	};

	const initializeVillaContactTextareas = () => {
		villaContactForms.forEach( ( form ) => {
			const textarea = form.querySelector(
				'textarea[name="villa-escape-details"]'
			);

			if ( ! textarea ) {
				return;
			}

			const syncTextareaHeight = () => {
				textarea.style.height = 'auto';
				textarea.style.height = `${ textarea.scrollHeight }px`;
			};

			// Start compact, then grow with the visitor's message.
			textarea.rows = 1;
			textarea.style.overflowY = 'hidden';
			textarea.style.resize = 'none';
			textarea.addEventListener( 'input', syncTextareaHeight );
			window.addEventListener( 'resize', syncTextareaHeight, {
				passive: true,
			} );
			syncTextareaHeight();
		} );
	};

	const initializeVillaContactWidgetButtons = () => {
		const buttons = document.querySelectorAll(
			'.vvm-villa-contact__whatsapp .wp-block-button__link'
		);

		if ( ! buttons.length ) {
			return;
		}

		const fallbackHref =
			document.querySelector( '.vvm-footer a[href^="mailto:"]' )?.href ||
			'mailto:info@barbadosescapes.com';
		const widgetControlSelector =
			'button, a[href], [role="button"], [tabindex]:not([tabindex="-1"])';
		const widgetRootSelector = [
			'#__EAAPS_PORTAL',
			'[class*="elfsight-app-"]',
			'[class*="eapps"]',
			'[id*="EAAPS"]',
			'[id*="eapps"]',
		].join( ',' );

		const isVisibleElement = ( element ) => {
			if ( ! ( element instanceof HTMLElement ) ) {
				return false;
			}

			const style = window.getComputedStyle( element );
			const rect = element.getBoundingClientRect();

			return (
				style.display !== 'none' &&
				style.visibility !== 'hidden' &&
				style.opacity !== '0' &&
				style.pointerEvents !== 'none' &&
				rect.width > 0 &&
				rect.height > 0 &&
				rect.bottom > 0 &&
				rect.right > 0 &&
				rect.top < window.innerHeight &&
				rect.left < window.innerWidth
			);
		};

		const getElementDescriptor = ( element ) =>
			[
				element.id,
				element.className,
				element.getAttribute( 'aria-label' ),
				element.getAttribute( 'title' ),
				element.textContent,
			]
				.join( ' ' )
				.toLowerCase();

		const collectWidgetRoots = () => {
			const roots = [];
			const seen = new Set();

			const addRoot = ( root ) => {
				if ( ! root || seen.has( root ) ) {
					return;
				}

				seen.add( root );
				roots.push( root );
			};

			const addShadowRoots = ( root ) => {
				root.querySelectorAll?.( '*' ).forEach( ( element ) => {
					if ( ! element.shadowRoot ) {
						return;
					}

					addRoot( element.shadowRoot );
					addShadowRoots( element.shadowRoot );
				} );
			};

			document.querySelectorAll( widgetRootSelector ).forEach( ( root ) => {
				addRoot( root );
				addShadowRoots( root );
			} );

			return roots;
		};

		const getControlScore = ( element, rootIndex ) => {
			if (
				! isVisibleElement( element ) ||
				element.closest?.( '.vvm-villa-contact__whatsapp' )
			) {
				return -1;
			}

			const descriptor = getElementDescriptor( element );

			if ( /(close|dismiss|minimize|powered|privacy|terms)/.test( descriptor ) ) {
				return -1;
			}

			const rect = element.getBoundingClientRect();
			let score = Math.max( 0, 30 - rootIndex );

			if ( /chat|contact|email|message|whatsapp|eapps|elfsight|eaaps/.test( descriptor ) ) {
				score += 30;
			}

			if ( element.tagName === 'BUTTON' ) {
				score += 15;
			}

			if (
				rect.right > window.innerWidth * 0.55 &&
				rect.bottom > window.innerHeight * 0.45
			) {
				score += 20;
			}

			// Prefer the closed floating launcher over larger in-dialog CTAs.
			if ( rect.width <= 120 && rect.height <= 120 ) {
				score += 15;
			}

			return score;
		};

		const openActiveElfsightWidget = () => {
			const controls = [];

			collectWidgetRoots().forEach( ( root, rootIndex ) => {
				root.querySelectorAll?.( widgetControlSelector ).forEach( ( element ) => {
					const score = getControlScore( element, rootIndex );

					if ( score < 0 ) {
						return;
					}

					controls.push( { element, score } );
				} );
			} );

			controls.sort( ( a, b ) => b.score - a.score );

			if ( ! controls.length ) {
				return false;
			}

			controls[ 0 ].element.click();
			return true;
		};

		window.vvmOpenActiveElfsightWidget = openActiveElfsightWidget;

		buttons.forEach( ( button ) => {
			const href = button.getAttribute( 'href' );

			if ( ! href || href === '#' ) {
				button.href = fallbackHref;
			}

			button.addEventListener( 'click', ( event ) => {
				if ( ! openActiveElfsightWidget() ) {
					return;
				}

				event.preventDefault();
			} );
		} );
	};

	const initializeLazyMaps = () => {
		if ( ! lazyMaps.length ) {
			return;
		}

		let observer;

		const loadMap = ( map ) => {
			if ( map.classList.contains( 'is-loaded' ) ) {
				return;
			}

			const iframe = map.querySelector( '[data-vvm-lazy-map-frame]' );
			const src = iframe?.dataset.src;

			if ( ! iframe || ! src ) {
				return;
			}

			// Restoring `src` is the point where Google Maps is allowed to load.
			iframe.src = src;
			iframe.removeAttribute( 'data-src' );
			iframe.removeAttribute( 'aria-hidden' );
			iframe.removeAttribute( 'tabindex' );
			iframe.removeAttribute( 'hidden' );

			map.classList.add( 'is-loaded' );
			map.removeAttribute( 'role' );
			map.removeAttribute( 'tabindex' );
			map.removeAttribute( 'aria-label' );
			observer?.unobserve( map );
		};

		const handleMapKeydown = ( event ) => {
			if ( event.key !== 'Enter' && event.key !== ' ' ) {
				return;
			}

			event.preventDefault();
			loadMap( event.currentTarget );
		};

		if ( 'IntersectionObserver' in window ) {
			observer = new IntersectionObserver(
				( entries ) => {
					entries.forEach( ( entry ) => {
						if ( entry.isIntersecting ) {
							loadMap( entry.target );
						}
					} );
				},
				{
					// Start loading shortly before the map scrolls into view.
					rootMargin: '240px 0px',
					threshold: 0.01,
				}
			);
		}

		lazyMaps.forEach( ( map ) => {
			map.setAttribute( 'role', 'button' );
			map.setAttribute( 'tabindex', '0' );
			map.setAttribute( 'aria-label', 'View interactive map' );

			map.addEventListener( 'click', () => loadMap( map ) );
			map.addEventListener( 'focusin', () => loadMap( map ) );
			map.addEventListener( 'keydown', handleMapKeydown );

			if ( observer ) {
				observer.observe( map );
				return;
			}

			loadMap( map );
		} );
	};

	const initializeHeaderDrawer = () => {
		const containers = header.querySelectorAll(
			'.wp-block-navigation__responsive-container'
		);

		containers.forEach( ( container ) => {
			const dialog = container.querySelector(
				'.wp-block-navigation__responsive-dialog'
			);
			const closeButton = container.querySelector(
				'.wp-block-navigation__responsive-container-close'
			);

			if ( ! dialog || ! closeButton ) {
				return;
			}

			const isOpen = () =>
				container.classList.contains( 'is-menu-open' ) ||
				container.classList.contains( 'has-modal-open' );

			container.addEventListener(
				'focusout',
				( event ) => {
					if ( ! isOpen() || event.relatedTarget !== null ) {
						return;
					}

					// DevTools can move focus outside the document without a real user close intent.
					event.stopImmediatePropagation();
				},
				true
			);

			container.addEventListener(
				'pointerdown',
				( event ) => {
					if ( ! isOpen() || dialog.contains( event.target ) ) {
						return;
					}

					event.preventDefault();
					closeButton.click();
				},
				true
			);
		} );
	};

	syncScrollbarWidth();
	initializeFooterReveal();
	initializeBedroomLevels();
	initializeVillaContactDateRanges();
	initializeVillaContactTextareas();
	initializeVillaContactWidgetButtons();
	initializeLazyMaps();
	window.addEventListener( 'resize', syncScrollbarWidth, { passive: true } );

	if ( ! header ) {
		return;
	}

	initializeHeaderDrawer();

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
