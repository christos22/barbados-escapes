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

window.addEventListener( 'DOMContentLoaded', () => {
	document
		.querySelectorAll( '[data-split-content-slider]' )
		.forEach( initializeSplitContentSlider );
} );
