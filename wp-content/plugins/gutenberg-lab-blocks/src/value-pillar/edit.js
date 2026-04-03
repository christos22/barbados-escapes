import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';

import {
	getValuePillarDashiconClass,
	VALUE_PILLAR_DEFINITIONS,
} from '../value-pillars/shared';

const ALLOWED_INNER_BLOCKS = [ 'core/heading', 'core/paragraph' ];

const TEMPLATE = [
	[
		'core/heading',
		{
			level: 4,
			placeholder: __( 'Add pillar title…', 'gutenberg-lab-blocks' ),
		},
	],
	[
		'core/paragraph',
		{
			placeholder: __( 'Add pillar description…', 'gutenberg-lab-blocks' ),
		},
	],
];

const ICON_OPTIONS = VALUE_PILLAR_DEFINITIONS.map( ( definition ) => ( {
	label: definition.title,
	value: definition.slug,
} ) );

export default function Edit( { attributes, setAttributes } ) {
	const { iconSlug } = attributes;
	const dashiconClass = getValuePillarDashiconClass( iconSlug );
	const blockProps = useBlockProps( {
		className: 'vvm-value-pillars__item',
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-value-pillars__body',
		},
		{
			allowedBlocks: ALLOWED_INNER_BLOCKS,
			template: TEMPLATE,
			templateLock: 'all',
			renderAppender: false,
		}
	);

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Icon', 'gutenberg-lab-blocks' ) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __( 'Pillar icon', 'gutenberg-lab-blocks' ) }
						value={ iconSlug }
						options={ ICON_OPTIONS }
						onChange={ ( value ) => setAttributes( { iconSlug: value } ) }
						help={ __(
							'This stores a semantic icon choice so the temporary Dashicons can be replaced later without changing saved content.',
							'gutenberg-lab-blocks'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			<article { ...blockProps }>
				<div className="vvm-value-pillars__icon-wrap" aria-hidden="true">
					<span
						className={ [
							'vvm-value-pillars__icon',
							'dashicons',
							dashiconClass,
						].join( ' ' ) }
					/>
				</div>
				<div { ...innerBlocksProps } />
			</article>
		</>
	);
}
