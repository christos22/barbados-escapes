import { __ } from '@wordpress/i18n';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

import { VALUE_PILLAR_DEFINITIONS } from './shared';

import './editor.scss';

const SMALL_ALLOWED_BLOCKS = [
	'core/heading',
	'core/paragraph',
	'core/list',
	'core/buttons',
	'core/image',
	'core/group',
	'core/quote',
	'core/separator',
	'core/spacer',
];

const ALLOWED_BLOCKS = [ 'core/group' ];

const TEMPLATE = [
	[
		'core/group',
		{
			className: 'vvm-value-pillars__intro',
			templateLock: false,
			allowedBlocks: SMALL_ALLOWED_BLOCKS,
			layout: {
				type: 'constrained',
			},
		},
		[
			[
				'core/heading',
				{
					level: 2,
					placeholder: __(
						'Add the section heading…',
						'gutenberg-lab-blocks'
					),
				},
			],
			[
				'core/paragraph',
				{
					placeholder: __(
						'Add an optional introduction before the pillars…',
						'gutenberg-lab-blocks'
					),
				},
			],
		],
	],
	[
		'core/group',
		{
			className: 'vvm-value-pillars__items',
			templateLock: false,
			allowedBlocks: [ 'gutenberg-lab-blocks/value-pillar' ],
			layout: {
				type: 'default',
			},
		},
		VALUE_PILLAR_DEFINITIONS.map( ( definition ) => [
			'gutenberg-lab-blocks/value-pillar',
			{
				iconSlug: definition.slug,
			},
			[
				[
					'core/heading',
					{
						level: 4,
						content: definition.title,
					},
				],
				[
					'core/paragraph',
					{
						content: definition.description,
					},
				],
			],
		] ),
	],
];

export default function Edit( { attributes } ) {
	const blockProps = useBlockProps( {
		// Default the section shell to full-width so native background colors span
		// the page, while the inner shell still keeps the authored content centered.
		className: [
			'vvm-value-pillars',
			attributes?.align ? '' : 'alignfull',
		]
			.filter( Boolean )
			.join( ' ' ),
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-value-pillars__shell',
		},
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: TEMPLATE,
			templateLock: 'all',
			renderAppender: false,
		}
	);

	return (
		<section { ...blockProps }>
			<div { ...innerBlocksProps } />
		</section>
	);
}
