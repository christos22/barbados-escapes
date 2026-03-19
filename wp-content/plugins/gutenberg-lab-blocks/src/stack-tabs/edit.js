import { __, sprintf } from '@wordpress/i18n';
import {
	RichText,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';

import './editor.scss';

const ALLOWED_BLOCKS = [ 'gutenberg-lab-blocks/stack-tab' ];

const TEMPLATE = [
	[ 'gutenberg-lab-blocks/stack-tab', { label: __( 'Tab One', 'gutenberg-lab-blocks' ) } ],
	[ 'gutenberg-lab-blocks/stack-tab', { label: __( 'Tab Two', 'gutenberg-lab-blocks' ) } ],
	[ 'gutenberg-lab-blocks/stack-tab', { label: __( 'Tab Three', 'gutenberg-lab-blocks' ) } ],
];

function getPreviewLabel( block, index ) {
	const label = block?.attributes?.label?.trim();

	if ( label ) {
		return label;
	}

	return sprintf( __( 'Tab %d', 'gutenberg-lab-blocks' ), index + 1 );
}

export default function Edit( { attributes, clientId, setAttributes } ) {
	const { heading, intro } = attributes;
	const tabBlocks = useSelect(
		( select ) =>
			select( 'core/block-editor' ).getBlock( clientId )?.innerBlocks ?? [],
		[ clientId ]
	);
	const previewLabels = TEMPLATE.map( ( templateItem, index ) => {
		const templateAttributes = templateItem[ 1 ] || {};

		return (
			tabBlocks[ index ]?.attributes?.label?.trim() ||
			templateAttributes.label ||
			getPreviewLabel( null, index )
		);
	} );

	const blockProps = useBlockProps( {
		className: 'vvm-stack-tabs vvm-stack-tabs--editor',
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-stack-tabs__editor-tabs',
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
			<div className="vvm-stack-tabs__shell">
				<div className="vvm-stack-tabs__header vvm-stack-tabs__header--editor">
					<RichText
						tagName="h2"
						className="vvm-stack-tabs__heading"
						value={ heading }
						onChange={ ( value ) => setAttributes( { heading: value } ) }
						placeholder={ __(
							'Add the section heading…',
							'gutenberg-lab-blocks'
						) }
						allowedFormats={ [] }
					/>
					<RichText
						tagName="p"
						className="vvm-stack-tabs__intro"
						value={ intro }
						onChange={ ( value ) => setAttributes( { intro: value } ) }
						placeholder={ __(
							'Add the section introduction…',
							'gutenberg-lab-blocks'
						) }
					/>
				</div>

				<div className="vvm-stack-tabs__editor-preview" aria-hidden="true">
					<div className="vvm-stack-tabs__nav">
						{ previewLabels.map( ( label, index ) => (
							<button
								key={ label + index }
								type="button"
								className={ [
									'vvm-stack-tabs__tab-button',
									0 === index ? 'is-active' : '',
								]
									.filter( Boolean )
									.join( ' ' ) }
								tabIndex={ -1 }
							>
								<span className="vvm-stack-tabs__tab-button-label">
									{ label }
								</span>
							</button>
						) ) }
					</div>
					<p className="vvm-stack-tabs__editor-note">
						{ __(
							'The front end will turn these three authored panels into interactive tabs. In the editor we keep each tab expanded so editing stays practical.',
							'gutenberg-lab-blocks'
						) }
					</p>
				</div>

				<div { ...innerBlocksProps } />
			</div>
		</section>
	);
}
