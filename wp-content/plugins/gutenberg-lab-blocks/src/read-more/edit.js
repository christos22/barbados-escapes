import { __ } from '@wordpress/i18n';
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

import './editor.scss';

const TEMPLATE = [
	[
		'core/paragraph',
		{
			placeholder: __(
				'Add the content revealed by the Read More button...',
				'gutenberg-lab-blocks'
			),
			fontFamily: 'refined-sans',
		},
	],
];

export default function Edit( { attributes, setAttributes } ) {
	const { readMoreLabel, readLessLabel } = attributes;
	const blockProps = useBlockProps( {
		className: 'vvm-read-more vvm-read-more--editor',
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-read-more__content vvm-read-more__content--editor',
		},
		{
			template: TEMPLATE,
			templateLock: false,
			renderAppender: InnerBlocks.ButtonBlockAppender,
		}
	);

	return (
		<section { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Button labels', 'gutenberg-lab-blocks' ) }>
					<TextControl
						label={ __( 'Collapsed label', 'gutenberg-lab-blocks' ) }
						value={ readMoreLabel }
						onChange={ ( value ) =>
							setAttributes( { readMoreLabel: value } )
						}
						placeholder={ __( 'Read More', 'gutenberg-lab-blocks' ) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<TextControl
						label={ __( 'Expanded label', 'gutenberg-lab-blocks' ) }
						value={ readLessLabel }
						onChange={ ( value ) =>
							setAttributes( { readLessLabel: value } )
						}
						placeholder={ __( 'Read Less', 'gutenberg-lab-blocks' ) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</PanelBody>
			</InspectorControls>

			<div className="vvm-read-more__control">
				<span className="vvm-read-more__button vvm-read-more__button--editor">
					{ readMoreLabel || __( 'Read More', 'gutenberg-lab-blocks' ) }
				</span>
			</div>

			<div { ...innerBlocksProps } />
		</section>
	);
}
