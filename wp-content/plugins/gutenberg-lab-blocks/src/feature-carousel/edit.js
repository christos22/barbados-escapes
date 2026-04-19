import { __ } from '@wordpress/i18n';
import {
	InnerBlocks,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';

import './editor.scss';

const ALLOWED_BLOCKS = [ 'gutenberg-lab-blocks/feature-carousel-slide' ];

const TEMPLATE = [
	[ 'gutenberg-lab-blocks/feature-carousel-slide' ],
	[ 'gutenberg-lab-blocks/feature-carousel-slide' ],
	[ 'gutenberg-lab-blocks/feature-carousel-slide' ],
];

export default function Edit( { attributes, clientId } ) {
	const { align } = attributes;
	const slideCount = useSelect(
		( select ) =>
			(
				select( 'core/block-editor' ).getBlock( clientId )?.innerBlocks ?? []
			).filter(
				( innerBlock ) =>
					'gutenberg-lab-blocks/feature-carousel-slide' ===
					innerBlock.name
			).length,
		[ clientId ]
	);

	const blockProps = useBlockProps( {
		className: [
			'vvm-feature-carousel',
			'vvm-feature-carousel--editor',
			align ? '' : 'alignfull',
		]
			.filter( Boolean )
			.join( ' ' ),
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-feature-carousel__editor-track',
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
			<div className="vvm-feature-carousel__editor-shell">
				<p className="vvm-feature-carousel__editor-note">
					{ 0 === slideCount
						? __(
								'Add slides here. The front end turns them into a centered editorial carousel with peeking neighboring images.',
								'gutenberg-lab-blocks'
						  )
						: __(
								'Each slide owns its own image, heading, body copy, and CTA. The PHP render callback rebuilds the final carousel shell on the front end.',
								'gutenberg-lab-blocks'
						  ) }
				</p>

				<div className="vvm-feature-carousel__editor-viewport">
					<div { ...innerBlocksProps } />
				</div>
			</div>
		</section>
	);
}
