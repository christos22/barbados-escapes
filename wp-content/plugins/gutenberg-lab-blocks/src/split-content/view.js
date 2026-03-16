function initializeSplitContentSlider( slider ) {
	const track = slider.querySelector( '.split-content__slides' );
	const slides = slider.querySelectorAll( '.split-content__slide' );
	const previousButton = slider.querySelector( '[data-split-content-prev]' );
	const nextButton = slider.querySelector( '[data-split-content-next]' );

	if ( ! track || slides.length <= 1 || ! previousButton || ! nextButton ) {
		return;
	}

	let currentIndex = 0;

	const updateSlider = () => {
		track.style.transform = `translateX(-${ currentIndex * 100 }%)`;
	};

	previousButton.addEventListener( 'click', () => {
		currentIndex = ( currentIndex - 1 + slides.length ) % slides.length;
		updateSlider();
	} );

	nextButton.addEventListener( 'click', () => {
		currentIndex = ( currentIndex + 1 ) % slides.length;
		updateSlider();
	} );

	updateSlider();
}

function initializeSplitContentSizing( block ) {
	const syncMediaHeight = () => {
		const mediaFrame = block.querySelector( '.split-content__media-frame' );
		const contentFlow = block.querySelector( '.split-content__content-flow' );
		const isDesktopSplitLayout =
			block.classList.contains( 'split-content--layout-split' ) &&
			window.innerWidth > 1023;

		if ( ! mediaFrame || ! contentFlow || ! isDesktopSplitLayout ) {
			block.style.removeProperty( '--split-content-media-target-height' );
			return;
		}

		const computedStyles = window.getComputedStyle( block );
		const baselineHeight =
			parseFloat(
				computedStyles.getPropertyValue( '--split-content-media-min-height' )
			) || 0;
		const overhangHeight =
			parseFloat(
				computedStyles.getPropertyValue( '--split-content-media-overhang' )
			) || 0;
		const contentHeight = contentFlow.getBoundingClientRect().height;
		const targetHeight = Math.max(
			baselineHeight,
			Math.ceil( contentHeight + overhangHeight )
		);

		block.style.setProperty(
			'--split-content-media-target-height',
			`${ targetHeight }px`
		);
	};

	const scheduleSync = () => {
		window.requestAnimationFrame( syncMediaHeight );
	};

	scheduleSync();
	window.addEventListener( 'resize', scheduleSync );

	if ( 'undefined' === typeof ResizeObserver ) {
		return;
	}

	const resizeObserver = new ResizeObserver( scheduleSync );
	resizeObserver.observe( block );

	const contentFlow = block.querySelector( '.split-content__content-flow' );

	if ( contentFlow ) {
		resizeObserver.observe( contentFlow );
	}
}

window.addEventListener( 'DOMContentLoaded', () => {
	document
		.querySelectorAll( '.wp-block-gutenberg-lab-blocks-split-content' )
		.forEach( initializeSplitContentSizing );

	document
		.querySelectorAll( '[data-split-content-slider]' )
		.forEach( initializeSplitContentSlider );
} );
