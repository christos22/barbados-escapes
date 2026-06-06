import { __ } from '@wordpress/i18n';
import {
	InnerBlocks,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';

const ALLOWED_INNER_BLOCKS = [ 'core/paragraph' ];

const TEMPLATE = [
	[
		'core/paragraph',
		{
			className: 'vvm-testimonial-carousel__quote',
			content: __(
				'Lying on top of a building the clouds looked no nearer than when I was lying on the street.',
				'gutenberg-lab-blocks'
			),
			placeholder: __( 'Add testimonial quote...', 'gutenberg-lab-blocks' ),
		},
	],
	[
		'core/paragraph',
		{
			className: 'vvm-testimonial-carousel__author',
			content: __( 'Liam Gillick', 'gutenberg-lab-blocks' ),
			placeholder: __( 'Add author...', 'gutenberg-lab-blocks' ),
		},
	],
];

export default function Edit() {
	const blockProps = useBlockProps( {
		className: 'vvm-testimonial-carousel__slide',
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-testimonial-carousel__slide-inner',
		},
		{
			allowedBlocks: ALLOWED_INNER_BLOCKS,
			template: TEMPLATE,
			templateLock: false,
			renderAppender: InnerBlocks.ButtonBlockAppender,
		}
	);

	return (
		<article { ...blockProps }>
			<span className="vvm-testimonial-carousel__mark" aria-hidden="true">
				“
			</span>
			<div { ...innerBlocksProps } />
		</article>
	);
}
