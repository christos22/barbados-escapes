import { createBlock } from '@wordpress/blocks';
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import './editor.scss';

const COLUMN_OPTIONS = [
	{ label: __( '2 Columns', 'gutenberg-lab-blocks' ), value: '2' },
	{ label: __( '3 Columns', 'gutenberg-lab-blocks' ), value: '3' },
];

const ALLOWED_BLOCKS = [ 'core/heading', 'core/paragraph', 'core/buttons' ];

const TEMPLATE = [
	[
		'core/heading',
		{
			level: 2,
			content: __( 'More Packages', 'gutenberg-lab-blocks' ),
			placeholder: __( 'Related packages heading', 'gutenberg-lab-blocks' ),
		},
	],
	[
		'core/paragraph',
		{
			placeholder: __( 'Add an optional introduction.', 'gutenberg-lab-blocks' ),
		},
	],
];

function hasMeaningfulInnerBlocks( innerBlocks = [] ) {
	return innerBlocks.some( ( innerBlock ) => {
		if ( 'core/heading' === innerBlock.name || 'core/paragraph' === innerBlock.name ) {
			return Boolean( innerBlock.attributes.content );
		}

		return true;
	} );
}

export default function Edit( { attributes, setAttributes, clientId } ) {
	const { heading, introText, count, columns, excludeCurrent } = attributes;
	const { replaceInnerBlocks } = useDispatch( blockEditorStore );
	const innerBlocks = useSelect(
		( select ) => select( blockEditorStore ).getBlocks( clientId ),
		[ clientId ]
	);

	useEffect( () => {
		if (
			hasMeaningfulInnerBlocks( innerBlocks ) ||
			( ! heading && ! introText )
		) {
			return;
		}

		const migratedBlocks = [];

		// Convert legacy attribute-based copy into native Gutenberg child blocks.
		if ( heading ) {
			migratedBlocks.push(
				createBlock( 'core/heading', {
					level: 2,
					content: heading,
				} )
			);
		}

		if ( introText ) {
			migratedBlocks.push(
				createBlock( 'core/paragraph', {
					content: introText,
				} )
			);
		}

		replaceInnerBlocks( clientId, migratedBlocks, false );
		setAttributes( {
			heading: '',
			introText: '',
		} );
	}, [
		clientId,
		heading,
		innerBlocks,
		introText,
		replaceInnerBlocks,
		setAttributes,
	] );

	const blockProps = useBlockProps( {
		className: `vvm-related-packages-placeholder vvm-related-packages-placeholder--columns-${ columns }`,
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-related-packages__header',
		},
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: TEMPLATE,
			templateLock: false,
			renderAppender: InnerBlocks.ButtonBlockAppender,
		}
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Query Settings', 'gutenberg-lab-blocks' ) }>
					<RangeControl
						label={ __( 'Packages to Show', 'gutenberg-lab-blocks' ) }
						value={ count }
						onChange={ ( value ) => setAttributes( { count: value } ) }
						min={ 1 }
						max={ 12 }
					/>
					<SelectControl
						label={ __( 'Columns', 'gutenberg-lab-blocks' ) }
						value={ columns }
						options={ COLUMN_OPTIONS }
						onChange={ ( value ) => setAttributes( { columns: value } ) }
					/>
					<ToggleControl
						label={ __( 'Exclude Current Package', 'gutenberg-lab-blocks' ) }
						checked={ excludeCurrent }
						onChange={ ( value ) => setAttributes( { excludeCurrent: value } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<div { ...innerBlocksProps } />
				<div className="vvm-related-packages-placeholder__grid">
					<div className="vvm-related-packages-placeholder__card" />
					<div className="vvm-related-packages-placeholder__card" />
					<div className="vvm-related-packages-placeholder__card" />
				</div>
			</section>
		</>
	);
}
