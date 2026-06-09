import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	InnerBlocks,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';

import AmenityIconControls, {
	getAmenityIconMarkup,
	getAmenityIconSizeStyle,
} from '../shared/amenity-icon-controls';

const ALLOWED_INNER_BLOCKS = [
	'core/group',
	'core/heading',
	'core/paragraph',
	'core/list',
	'core/buttons',
];

const TEMPLATE = [
	[
		'core/group',
		{
			className: 'vvm-editorial-feature__heading-stack',
		},
		[
			[
				'core/heading',
				{
					className: 'vvm-editorial-feature__eyebrow',
					content: __(
						'Destination & Activities',
						'gutenberg-lab-blocks'
					),
					level: 4,
					placeholder: __( 'Add eyebrow…', 'gutenberg-lab-blocks' ),
				},
			],
			[
				'core/heading',
				{
					className: 'vvm-editorial-feature__title',
					content: __( 'Only in Barbados', 'gutenberg-lab-blocks' ),
					level: 2,
					placeholder: __( 'Add headline…', 'gutenberg-lab-blocks' ),
				},
			],
		],
	],
	[
		'core/group',
		{
			className: 'vvm-editorial-feature__body-stack',
		},
		[
			[
				'core/paragraph',
				{
					content: __(
						'Introduce the experience with concise editorial copy and a clear next step.',
						'gutenberg-lab-blocks'
					),
					placeholder: __(
						'Add supporting copy…',
						'gutenberg-lab-blocks'
					),
				},
			],
			[
				'core/buttons',
				{},
				[
					[
						'core/button',
						{
							className: 'is-style-vvm-link-primary',
							text: __( 'Explore More', 'gutenberg-lab-blocks' ),
						},
					],
				],
			],
		],
	],
];

export default function Edit( { attributes, setAttributes } ) {
	const { iconSlug, iconSize } = attributes;
	const iconMarkup = getAmenityIconMarkup( iconSlug );
	const blockProps = useBlockProps( {
		className: [
			'vvm-editorial-feature__slide',
			iconMarkup ? 'vvm-editorial-feature__slide--has-icon' : '',
		]
			.filter( Boolean )
			.join( ' ' ),
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-editorial-feature__slide-grid',
		},
		{
			allowedBlocks: ALLOWED_INNER_BLOCKS,
			template: TEMPLATE,
			templateLock: false,
			orientation: 'horizontal',
			renderAppender: InnerBlocks.ButtonBlockAppender,
		}
	);

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Editorial icon', 'gutenberg-lab-blocks' ) }
					initialOpen={ true }
				>
					<AmenityIconControls
						defaultSize={ 7 }
						iconSize={ iconSize }
						iconSlug={ iconSlug }
						setAttributes={ setAttributes }
					/>
				</PanelBody>
			</InspectorControls>
			<article { ...blockProps }>
				<div { ...innerBlocksProps }>
					{ iconMarkup ? (
						<span
							className="vvm-editorial-feature__icon"
							aria-hidden="true"
							style={ getAmenityIconSizeStyle(
								iconSize,
								'--vvm-editorial-feature-icon-size'
							) }
							dangerouslySetInnerHTML={ { __html: iconMarkup } }
						/>
					) : null }
					{ innerBlocksProps.children }
				</div>
			</article>
		</>
	);
}
