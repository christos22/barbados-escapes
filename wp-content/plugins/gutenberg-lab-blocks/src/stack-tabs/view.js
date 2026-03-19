function activateStackTab( root, nextIndex ) {
	const tabButtons = Array.from(
		root.querySelectorAll( '[data-stack-tabs-tab-button]' )
	);
	const panels = Array.from( root.querySelectorAll( '[data-stack-tabs-panel]' ) );

	tabButtons.forEach( ( button, index ) => {
		const isActive = index === nextIndex;

		button.classList.toggle( 'is-active', isActive );
		button.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
		button.tabIndex = isActive ? 0 : -1;
	} );

	panels.forEach( ( panel, index ) => {
		const isActive = index === nextIndex;

		panel.classList.toggle( 'is-active', isActive );
		panel.hidden = ! isActive;
	} );
}

function initializeRevealGroup( panel ) {
	const itemButtons = Array.from(
		panel.querySelectorAll( '[data-stack-tabs-item-button]' )
	);
	const itemBodies = Array.from(
		panel.querySelectorAll( '[data-stack-tabs-item-body]' )
	);
	const stagePanels = Array.from(
		panel.querySelectorAll( '[data-stack-tabs-stage-panel]' )
	);
	const itemWrappers = Array.from(
		panel.querySelectorAll( '[data-stack-tabs-item]' )
	);

	if ( ! itemButtons.length ) {
		return;
	}

	const activateItem = ( nextIndex ) => {
		itemButtons.forEach( ( button, index ) => {
			const isActive = index === nextIndex;

			button.classList.toggle( 'is-active', isActive );
			button.setAttribute( 'aria-expanded', isActive ? 'true' : 'false' );
		} );

		itemBodies.forEach( ( body, index ) => {
			const isActive = index === nextIndex;

			body.classList.toggle( 'is-active', isActive );
			body.hidden = ! isActive;
		} );

		stagePanels.forEach( ( stagePanel, index ) => {
			const isActive = index === nextIndex;

			stagePanel.classList.toggle( 'is-active', isActive );
			stagePanel.hidden = ! isActive;
		} );

		itemWrappers.forEach( ( wrapper, index ) => {
			wrapper.classList.toggle( 'is-active', index === nextIndex );
		} );
	};

	itemButtons.forEach( ( button, index ) => {
		button.addEventListener( 'click', () => {
			activateItem( index );
		} );
	} );

	activateItem( 0 );
}

function handleTabKeyboardNavigation( root ) {
	const tabButtons = Array.from(
		root.querySelectorAll( '[data-stack-tabs-tab-button]' )
	);

	if ( ! tabButtons.length ) {
		return;
	}

	tabButtons.forEach( ( button, index ) => {
		button.addEventListener( 'keydown', ( event ) => {
			let nextIndex = null;

			switch ( event.key ) {
				case 'ArrowRight':
				case 'ArrowDown':
					nextIndex = ( index + 1 ) % tabButtons.length;
					break;
				case 'ArrowLeft':
				case 'ArrowUp':
					nextIndex = ( index - 1 + tabButtons.length ) % tabButtons.length;
					break;
				case 'Home':
					nextIndex = 0;
					break;
				case 'End':
					nextIndex = tabButtons.length - 1;
					break;
				default:
					return;
			}

			event.preventDefault();
			activateStackTab( root, nextIndex );
			tabButtons[ nextIndex ]?.focus();
		} );
	} );
}

function initializeStackTabs( root ) {
	const tabButtons = Array.from(
		root.querySelectorAll( '[data-stack-tabs-tab-button]' )
	);
	const panels = Array.from( root.querySelectorAll( '[data-stack-tabs-panel]' ) );

	if ( ! tabButtons.length || ! panels.length ) {
		return;
	}

	root.classList.add( 'is-enhanced' );

	panels.forEach( initializeRevealGroup );

	tabButtons.forEach( ( button, index ) => {
		button.addEventListener( 'click', () => {
			activateStackTab( root, index );
		} );
	} );

	handleTabKeyboardNavigation( root );
	activateStackTab( root, 0 );
}

window.addEventListener( 'DOMContentLoaded', () => {
	document
		.querySelectorAll( '[data-stack-tabs-root]' )
		.forEach( initializeStackTabs );
} );
