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
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

import './editor.scss';

const COLUMN_OPTIONS = [
	{ label: __( '2 Columns', 'gutenberg-lab-blocks' ), value: '2' },
	{ label: __( '3 Columns', 'gutenberg-lab-blocks' ), value: '3' },
];

const DISPLAY_MODE_OPTIONS = [
	{ label: __( 'Grid', 'gutenberg-lab-blocks' ), value: 'grid' },
	{ label: __( 'Carousel', 'gutenberg-lab-blocks' ), value: 'carousel' },
];

const ALLOWED_BLOCKS = [ 'core/heading', 'core/paragraph', 'core/buttons' ];

const TEMPLATE = [
	[
		'core/heading',
		{
			level: 2,
			placeholder: __( 'Packages heading', 'gutenberg-lab-blocks' ),
		},
	],
	[
		'core/paragraph',
		{
			placeholder: __( 'Add an optional introduction.', 'gutenberg-lab-blocks' ),
		},
	],
];

export default function Edit( { attributes, setAttributes } ) {
	const {
		displayMode,
		count,
		columns,
		excludeCurrent,
		showPackageType,
		showExcerpt,
		showPrice,
		showCta,
	} = attributes;

	const blockProps = useBlockProps( {
		className: `vvm-packages-display-editor-preview vvm-packages-display-editor-preview--${ displayMode } vvm-packages-display-editor-preview--columns-${ columns }`,
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-packages-display__header',
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
					<SelectControl
						label={ __( 'Display Mode', 'gutenberg-lab-blocks' ) }
						value={ displayMode }
						options={ DISPLAY_MODE_OPTIONS }
						onChange={ ( value ) => setAttributes( { displayMode: value } ) }
					/>
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
				<PanelBody title={ __( 'Card Content', 'gutenberg-lab-blocks' ) }>
					<ToggleControl
						label={ __( 'Show Package Type', 'gutenberg-lab-blocks' ) }
						checked={ showPackageType }
						onChange={ ( value ) => setAttributes( { showPackageType: value } ) }
					/>
					<ToggleControl
						label={ __( 'Show Excerpt', 'gutenberg-lab-blocks' ) }
						checked={ showExcerpt }
						onChange={ ( value ) => setAttributes( { showExcerpt: value } ) }
					/>
					<ToggleControl
						label={ __( 'Show Price', 'gutenberg-lab-blocks' ) }
						checked={ showPrice }
						onChange={ ( value ) => setAttributes( { showPrice: value } ) }
					/>
					<ToggleControl
						label={ __( 'Show CTA Buttons', 'gutenberg-lab-blocks' ) }
						checked={ showCta }
						onChange={ ( value ) => setAttributes( { showCta: value } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<div { ...innerBlocksProps } />
				<div className="vvm-packages-display-editor-preview__results">
					<ServerSideRender
						block="gutenberg-lab-blocks/packages-display"
						attributes={ {
							...attributes,
							suppressHeader: true,
						} }
					/>
				</div>
			</section>
		</>
	);
}
