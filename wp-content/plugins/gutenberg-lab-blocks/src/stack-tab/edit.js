import { __ } from '@wordpress/i18n';
import {
	InnerBlocks,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { TextControl } from '@wordpress/components';

const ALLOWED_BLOCKS = [ 'gutenberg-lab-blocks/stack-tab-item' ];

const TEMPLATE = [
	[
		'gutenberg-lab-blocks/stack-tab-item',
		{ label: __( 'Reveal Item One', 'gutenberg-lab-blocks' ) },
	],
	[
		'gutenberg-lab-blocks/stack-tab-item',
		{ label: __( 'Reveal Item Two', 'gutenberg-lab-blocks' ) },
	],
	[
		'gutenberg-lab-blocks/stack-tab-item',
		{ label: __( 'Reveal Item Three', 'gutenberg-lab-blocks' ) },
	],
	[
		'gutenberg-lab-blocks/stack-tab-item',
		{ label: __( 'Reveal Item Four', 'gutenberg-lab-blocks' ) },
	],
];

export default function Edit( { attributes, setAttributes } ) {
	const { label } = attributes;
	const blockProps = useBlockProps( {
		className: 'vvm-stack-tabs__tab-editor',
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'vvm-stack-tabs__tab-editor-items',
		},
		{
			allowedBlocks: ALLOWED_BLOCKS,
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
