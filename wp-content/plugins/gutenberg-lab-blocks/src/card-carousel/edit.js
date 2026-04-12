import { __ } from '@wordpress/i18n';
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

import './editor.scss';

const CONTENT_SOURCE_OPTIONS = [
	{ label: __( 'Manual slides', 'gutenberg-lab-blocks' ), value: 'manual' },
	{ label: __( 'Villa posts', 'gutenberg-lab-blocks' ), value: 'villas' },
];

const INTRO_TEMPLATE = [
	[
		'core/heading',
		{
			level: 2,
			placeholder: __( 'Carousel heading', 'gutenberg-lab-blocks' ),
		},
	],
	[
		'core/paragraph',
		{
			placeholder: __( 'Add the optional intro copy.', 'gutenberg-lab-blocks' ),
		},
	],
	[ 'core/buttons', {} ],
];

const SLIDES_TEMPLATE = [
	[ 'gutenberg-lab-blocks/card-carousel-slide' ],
	[ 'gutenberg-lab-blocks/card-carousel-slide' ],
	[ 'gutenberg-lab-blocks/card-carousel-slide' ],
];

const TEMPLATE = [
	[
		'core/group',
		{
			className: 'vvm-card-carousel__intro',
			layout: { type: 'constrained' },
			lock: {
				move: true,
				remove: true,
			},
		},
		INTRO_TEMPLATE,
	],
	[
		'core/group',
		{
			className: 'vvm-card-carousel__slides',
			layout: { type: 'default' },
			lock: {
				move: true,
				remove: true,
			},
		},
		SLIDES_TEMPLATE,
	],
];

export default function Edit( { attributes, setAttributes } ) {
	const { align, contentSource, villaCount } = attributes;

	const blockProps = useBlockProps( {
		className: [
			'vvm-card-carousel',
			align ? '' : 'alignfull',
			`vvm-card-carousel--source-${ contentSource }`,
		].join( ' ' ),
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-card-carousel__layout-editor',
		},
		{
			allowedBlocks: [ 'core/group' ],
			template: TEMPLATE,
			templateLock: false,
			orientation: 'horizontal',
			renderAppender: false,
		}
	);

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Content source', 'gutenberg-lab-blocks' ) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __( 'Populate slides from', 'gutenberg-lab-blocks' ) }
						value={ contentSource }
						options={ CONTENT_SOURCE_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { contentSource: value } )
						}
						help={
							'villas' === contentSource
								? __(
										'Villa mode reuses the featured image, title, excerpt, and CTA mapping from the existing villa cards.',
										'gutenberg-lab-blocks'
								  )
								: __(
										'Manual mode keeps the intro copy and slide cards editable inside this block.',
										'gutenberg-lab-blocks'
								  )
						}
					/>

					{ 'villas' === contentSource ? (
						<RangeControl
							label={ __( 'Villas to show', 'gutenberg-lab-blocks' ) }
							value={ villaCount }
							onChange={ ( value ) =>
								setAttributes( { villaCount: value ?? 3 } )
							}
							min={ 1 }
							max={ 12 }
						/>
					) : null }
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<div className="vvm-card-carousel__editor-shell">
					<div { ...innerBlocksProps } />

					{ 'villas' === contentSource ? (
						<div className="vvm-card-carousel__dynamic-preview">
							<ServerSideRender
								block="gutenberg-lab-blocks/card-carousel"
								attributes={ attributes }
							/>
						</div>
					) : null }
				</div>
			</section>
		</>
	);
}
