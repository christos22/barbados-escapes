import { __ } from '@wordpress/i18n';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

import './editor.scss';

const TEMPLATE = [ [ 'gutenberg-lab-blocks/villa-gallery-hero-slide' ] ];

export default function Edit() {
	const blockProps = useBlockProps( {
		className: 'vvm-villa-gallery-hero__media-region',
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-villa-gallery-hero__media-region-shell',
		},
		{
			allowedBlocks: [ 'gutenberg-lab-blocks/villa-gallery-hero-slide' ],
			template: TEMPLATE,
			templateLock: false,
			orientation: 'horizontal',
		}
	);

	return (
		<div { ...blockProps }>
			<p className="vvm-villa-gallery-hero__region-label">
				{ __( 'Hero media rail', 'gutenberg-lab-blocks' ) }
			</p>
			<div { ...innerBlocksProps } />
		</div>
	);
}
