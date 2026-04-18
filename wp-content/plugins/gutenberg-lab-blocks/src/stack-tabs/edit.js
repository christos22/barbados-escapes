import { __, sprintf } from '@wordpress/i18n';
import {
	InnerBlocks,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';

import './editor.scss';

const ALLOWED_BLOCKS = [ 'gutenberg-lab-blocks/stack-tab' ];

const TEMPLATE = [
	[
		'gutenberg-lab-blocks/stack-tab',
		{ label: __( 'Bedrooms', 'gutenberg-lab-blocks' ) },
	],
	[
		'gutenberg-lab-blocks/stack-tab',
		{ label: __( 'Amenities', 'gutenberg-lab-blocks' ) },
	],
	[
		'gutenberg-lab-blocks/stack-tab',
		{ label: __( 'House Rules', 'gutenberg-lab-blocks' ) },
	],
	[
		'gutenberg-lab-blocks/stack-tab',
		{ label: __( 'Reviews', 'gutenberg-lab-blocks' ) },
	],
];

function getPreviewLabel( block, index ) {
	const label = block?.attributes?.label?.trim();

	if ( label ) {
		return label;
	}

	return sprintf( __( 'Tab %d', 'gutenberg-lab-blocks' ), index + 1 );
}

export default function Edit( { clientId } ) {
	const tabBlocks = useSelect(
		( select ) =>
			select( 'core/block-editor' ).getBlock( clientId )?.innerBlocks ?? [],
		[ clientId ]
	);
	const previewBlocks = tabBlocks.length
		? tabBlocks
		: TEMPLATE.map( ( [ , attributes ] ) => ( { attributes } ) );
	const previewLabels = previewBlocks.map( ( block, index ) =>
		getPreviewLabel( block, index )
	);

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
			templateLock: false,
			renderAppender: InnerBlocks.ButtonBlockAppender,
		}
	);

	return (
		<section { ...blockProps }>
			<div className="vvm-stack-tabs__shell">
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
							'Edit each child tab label, then add any blocks inside the tab panel. The front end turns those panels into the interactive tab content.',
							'gutenberg-lab-blocks'
						) }
					</p>
				</div>

				<div { ...innerBlocksProps } />
			</div>
		</section>
	);
}
