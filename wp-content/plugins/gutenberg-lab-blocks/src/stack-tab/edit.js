import { cloneBlock, createBlock } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import {
	InnerBlocks,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { TextControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

const TEMPLATE = [
	[
		'core/paragraph',
		{ placeholder: __( 'Add tab content…', 'gutenberg-lab-blocks' ) },
	],
];

function blockHasMeaningfulContent( block ) {
	if ( ! block ) {
		return false;
	}

	const { attributes = {}, innerBlocks = [] } = block;
	const scalarValues = [
		attributes.content,
		attributes.caption,
		attributes.url,
		attributes.title,
		attributes.text,
		attributes.alt,
	];

	if (
		scalarValues.some(
			( value ) => 'string' === typeof value && value.trim() !== ''
		)
	) {
		return true;
	}

	if ( innerBlocks.some( blockHasMeaningfulContent ) ) {
		return true;
	}

	return false;
}

function migrateLegacyItemBlock( legacyItemBlock ) {
	const migratedBlocks = [];
	const {
		mediaId,
		mediaUrl,
		mediaAlt,
	} = legacyItemBlock.attributes || {};

	if ( mediaUrl ) {
		// Preserve authored legacy media by turning it into a normal image block.
		migratedBlocks.push(
			createBlock( 'core/image', {
				id: mediaId,
				url: mediaUrl,
				alt: mediaAlt || '',
			} )
		);
	}

	legacyItemBlock.innerBlocks
		.filter( blockHasMeaningfulContent )
		.forEach( ( innerBlock ) => {
			migratedBlocks.push( cloneBlock( innerBlock ) );
		} );

	return migratedBlocks;
}

export default function Edit( { attributes, clientId, setAttributes } ) {
	const { label } = attributes;
	const innerBlocks = useSelect(
		( select ) =>
			select( 'core/block-editor' ).getBlock( clientId )?.innerBlocks ?? [],
		[ clientId ]
	);
	const { replaceInnerBlocks } = useDispatch( 'core/block-editor' );

	useEffect( () => {
		// Strip old Stack Tab Item wrappers out of existing content once per block.
		const hasLegacyItems = innerBlocks.some(
			( innerBlock ) =>
				'gutenberg-lab-blocks/stack-tab-item' === innerBlock.name
		);

		if ( ! hasLegacyItems ) {
			return;
		}

		const migratedBlocks = innerBlocks.flatMap( ( innerBlock ) => {
			if ( 'gutenberg-lab-blocks/stack-tab-item' !== innerBlock.name ) {
				return [ cloneBlock( innerBlock ) ];
			}

			return migrateLegacyItemBlock( innerBlock );
		} );

		replaceInnerBlocks(
			clientId,
			migratedBlocks.length ? migratedBlocks : [ createBlock( 'core/paragraph' ) ],
			false
		);
	}, [ clientId, innerBlocks, replaceInnerBlocks ] );

	const blockProps = useBlockProps( {
		className: 'vvm-stack-tabs__tab-editor',
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-stack-tabs__tab-editor-items',
		},
		{
			template: TEMPLATE,
			templateLock: false,
			renderAppender: InnerBlocks.ButtonBlockAppender,
		}
	);

	return (
		<div { ...blockProps }>
			<div className="vvm-stack-tabs__tab-editor-header">
				<TextControl
					label={ __( 'Tab label', 'gutenberg-lab-blocks' ) }
					value={ label }
					onChange={ ( value ) => setAttributes( { label: value } ) }
					help={ __(
						'This becomes the top-level tab button label on the front end.',
						'gutenberg-lab-blocks'
					) }
				/>
			</div>
			<div { ...innerBlocksProps } />
		</div>
	);
}
