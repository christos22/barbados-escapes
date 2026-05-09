function initializeReadMore( root ) {
	const button = root.querySelector( '[data-vvm-read-more-button]' );
	const label = root.querySelector( '[data-vvm-read-more-label]' );
	const content = root.querySelector( '[data-vvm-read-more-content]' );

	if ( ! button || ! content ) {
		return;
	}

	const collapsedLabel =
		root.dataset.vvmReadMoreCollapsedLabel ||
		button.textContent.trim() ||
		'Read More';
	const expandedLabel =
		root.dataset.vvmReadMoreExpandedLabel || 'Read Less';

	const setExpanded = ( isExpanded ) => {
		root.classList.toggle( 'is-expanded', isExpanded );
		button.setAttribute( 'aria-expanded', String( isExpanded ) );
		content.setAttribute( 'aria-hidden', String( ! isExpanded ) );

		if ( label ) {
			label.textContent = isExpanded ? expandedLabel : collapsedLabel;
		}

		content.style.maxBlockSize = isExpanded
			? `${ content.scrollHeight }px`
			: '0px';

		if ( ! isExpanded && content.contains( document.activeElement ) ) {
			button.focus();
		}
	};

	root.classList.add( 'is-enhanced' );
	setExpanded( false );

	button.addEventListener( 'click', () => {
		setExpanded( ! root.classList.contains( 'is-expanded' ) );
	} );

	window.addEventListener(
		'resize',
		() => {
			if ( root.classList.contains( 'is-expanded' ) ) {
				content.style.maxBlockSize = `${ content.scrollHeight }px`;
			}
		},
		{ passive: true }
	);
}

document.documentElement.classList.add( 'js' );

window.addEventListener( 'DOMContentLoaded', () => {
	document
		.querySelectorAll( '[data-vvm-read-more-root]' )
		.forEach( initializeReadMore );
} );
