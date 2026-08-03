import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import './editor.scss';

export default function Edit() {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<Placeholder
				icon="screenoptions"
				label={ __( 'Villa Search Results', 'gutenberg-lab-blocks' ) }
				instructions={ __(
					'The frontend reads the selected search filters, checks villa availability, and renders the matching shared villa cards.',
					'gutenberg-lab-blocks'
				) }
			/>
		</div>
	);
}
