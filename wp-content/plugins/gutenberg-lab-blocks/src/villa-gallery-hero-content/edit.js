import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

import './editor.scss';

const ALLOWED_BLOCKS = [
	'core/post-title',
	'core/post-excerpt',
	'core/heading',
	'core/paragraph',
	'core/buttons',
	'core/group',
	'core/spacer',
];

const TEMPLATE = [
	[
		'core/post-title',
		{
			level: 1,
		},
	],
	[
		'core/paragraph',
		{
			placeholder: __( 'Add short supporting copy…', 'gutenberg-lab-blocks' ),
		},
	],
	[ 'core/buttons', {} ],
];

function blockTreeHasTitleBlock( innerBlocks = [] ) {
	return innerBlocks.some( ( innerBlock ) => {
		if ( ! innerBlock || ! innerBlock.name ) {
			return false;
		}

		if (
			'core/post-title' === innerBlock.name ||
			'core/heading' === innerBlock.name
		) {
			return true;
		}

		return blockTreeHasTitleBlock( innerBlock.innerBlocks ?? [] );
	} );
}

export default function Edit( { clientId } ) {
	const { fallbackTitle, hasExplicitTitleBlock } = useSelect(
		( select ) => {
			const block = select( 'core/block-editor' ).getBlock( clientId );

			return {
				fallbackTitle: select( 'core/editor' ).getEditedPostAttribute( 'title' ),
				hasExplicitTitleBlock: blockTreeHasTitleBlock( block?.innerBlocks ?? [] ),
			};
		},
		[ clientId ]
	);

	const blockProps = useBlockProps( {
		className: 'vvm-villa-gallery-hero__content-region',
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-villa-gallery-hero__content-region-shell',
		},
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: TEMPLATE,
			templateLock: false,
		}
	);

	return (
		<div { ...blockProps }>
			<p className="vvm-villa-gallery-hero__region-label">
				{ __( 'Overlay content', 'gutenberg-lab-blocks' ) }
			</p>
			{ ! hasExplicitTitleBlock && fallbackTitle && (
				<div className="vvm-villa-gallery-hero__fallback-title-preview">
					<h1 className="vvm-villa-gallery-hero__fallback-title">
						{ fallbackTitle }
					</h1>
					<p className="vvm-villa-gallery-hero__fallback-note">
						{ __(
							'Shown automatically from the villa title until you add a Heading or Post Title block here.',
							'gutenberg-lab-blocks'
						) }
					</p>
				</div>
			) }
			<div { ...innerBlocksProps } />
		</div>
	);
}
