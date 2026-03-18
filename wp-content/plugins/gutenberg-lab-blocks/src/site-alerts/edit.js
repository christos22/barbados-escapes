import { __, sprintf } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';

export default function Edit( { attributes } ) {
	const { slot = 'header' } = attributes;
	const blockProps = useBlockProps( {
		className: 'vvm-site-alerts-block-placeholder',
	} );

	return (
		<div { ...blockProps }>
			<Placeholder
				icon="warning"
				label={ __( 'Site Alerts', 'gutenberg-lab-blocks' ) }
				instructions={ __(
					'This block renders active Site Message posts for the current placement slot. Editors manage the actual alert content in the Site Messages post type.',
					'gutenberg-lab-blocks'
				) }
			>
				<p>
					{ sprintf(
						__( 'Placement slot: %s', 'gutenberg-lab-blocks' ),
						slot
					) }
				</p>
			</Placeholder>
		</div>
	);
}
