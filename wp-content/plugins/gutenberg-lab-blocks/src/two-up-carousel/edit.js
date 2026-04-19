import { __ } from '@wordpress/i18n';
import {
	InnerBlocks,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';

import './editor.scss';

const ALLOWED_BLOCKS = [ 'gutenberg-lab-blocks/two-up-carousel-slide' ];

const TEMPLATE = [
	[ 'gutenberg-lab-blocks/two-up-carousel-slide' ],
	[ 'gutenberg-lab-blocks/two-up-carousel-slide' ],
	[ 'gutenberg-lab-blocks/two-up-carousel-slide' ],
];

export default function Edit( { attributes, clientId } ) {
	const { align } = attributes;
	const slideCount = useSelect(
		( select ) =>
			(
				select( 'core/block-editor' ).getBlock( clientId )?.innerBlocks ?? []
			).filter(
				( innerBlock ) =>
					'gutenberg-lab-blocks/two-up-carousel-slide' ===
					innerBlock.name
			).length,
		[ clientId ]
	);

	const blockProps = useBlockProps( {
		className: [
			'vvm-two-up-carousel',
			'vvm-two-up-carousel--editor',
			align ? '' : 'alignfull',
		]
			.filter( Boolean )
			.join( ' ' ),
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-two-up-carousel__editor-track',
		},
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: TEMPLATE,
			templateLock: false,
			orientation: 'vertical',
			renderAppender: InnerBlocks.ButtonBlockAppender,
		}
	);

	return (
		<section { ...blockProps }>
			<div className="vvm-two-up-carousel__editor-shell">
				<p className="vvm-two-up-carousel__editor-note">
					{ 0 === slideCount
						? __(
								'Add custom slide cards here. The front end shows two cards at a time and lets the neighboring cards peek in from the sides.',
								'gutenberg-lab-blocks'
						  )
						: __(
								'Each child slide owns its own image and optional copy while PHP rebuilds the shared two-card rail on the front end.',
								'gutenberg-lab-blocks'
						  ) }
				</p>

				<div className="vvm-two-up-carousel__editor-viewport">
					<div { ...innerBlocksProps } />
				</div>
			</div>
		</section>
	);
}
