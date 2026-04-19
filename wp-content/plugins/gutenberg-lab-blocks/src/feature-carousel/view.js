import { bootPeekingCarousel } from '../shared/peeking-carousel-view';

bootPeekingCarousel( {
	rootSelector: '[data-feature-carousel-root]',
	carouselSelector: '[data-feature-carousel]',
	previousButtonSelector: '[data-feature-carousel-prev]',
	nextButtonSelector: '[data-feature-carousel-next]',
	slideSelector: '[data-feature-carousel-slide]',
	realSlideSelector:
		'.splide__slide:not(.splide__slide--clone)[data-feature-carousel-slide]',
	durationVariable: '--vvm-feature-carousel-fade-duration',
} );
