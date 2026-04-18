import { __ } from '@wordpress/i18n';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

import './editor.scss';

const ALLOWED_BLOCKS = [ 'gutenberg-lab-blocks/villa-spec-item' ];

const TEMPLATE = [
	[
		'gutenberg-lab-blocks/villa-spec-item',
		{
			value: '8',
			label: __( 'Bedrooms', 'gutenberg-lab-blocks' ),
		},
	],
	[
		'gutenberg-lab-blocks/villa-spec-item',
		{
			value: '8.5',
			label: __( 'Bathrooms', 'gutenberg-lab-blocks' ),
		},
	],
	[
		'gutenberg-lab-blocks/villa-spec-item',
		{
			value: '16',
			label: __( 'Sleeps', 'gutenberg-lab-blocks' ),
		},
	],
	[
		'gutenberg-lab-blocks/villa-spec-item',
		{
			value: __( 'Private', 'gutenberg-lab-blocks' ),
			label: __( 'Infinity Pool', 'gutenberg-lab-blocks' ),
		},
	],
	[
		'gutenberg-lab-blocks/villa-spec-item',
		{
			value: __( 'From $2,000', 'gutenberg-lab-blocks' ),
			label: __( 'Per Night', 'gutenberg-lab-blocks' ),
		},
	],
];

export default function Edit( { attributes } ) {
	const blockProps = useBlockProps( {
		className: [
			'vvm-villa-specs',
			attributes?.align ? '' : 'alignfull',
			'vvm-villa-specs--editor',
		]
			.filter( Boolean )
			.join( ' ' ),
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-villa-specs__items',
		},
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: TEMPLATE,
			templateLock: false,
			orientation: 'horizontal',
		}
	);

	return (
		<section { ...blockProps }>
			<div className="vvm-villa-specs__shell">
				<p className="vvm-villa-specs__editor-note">
					{ __(
						'Edit each value and label inline, then reorder or duplicate items as needed for the current villa.',
						'gutenberg-lab-blocks'
					) }
				</p>
				<div { ...innerBlocksProps } />
			</div>
		</section>
	);
}
