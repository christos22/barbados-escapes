import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, Placeholder, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import './style.scss';
import './editor.scss';

import metadata from './block.json';

const Edit = ( { attributes, setAttributes } ) => {
	const blockProps = useBlockProps();
	const { villaId, monthsToShow, heading, formSelector } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Calendar settings', 'gutenberg-lab-blocks' ) }>
					<TextControl
						label={ __( 'Heading', 'gutenberg-lab-blocks' ) }
						value={ heading }
						onChange={ ( value ) => setAttributes( { heading: value } ) }
					/>
					<TextControl
						label={ __( 'Villa ID override', 'gutenberg-lab-blocks' ) }
						help={ __( 'Leave blank on villa pages. Use this only when rendering the calendar outside a villa template.', 'gutenberg-lab-blocks' ) }
						value={ villaId || '' }
						onChange={ ( value ) =>
							setAttributes( { villaId: value ? Number( value ) : 0 } )
						}
						type="number"
					/>
					<TextControl
						label={ __( 'Months to show', 'gutenberg-lab-blocks' ) }
						value={ monthsToShow }
						onChange={ ( value ) =>
							setAttributes( {
								monthsToShow: Math.min(
									18,
									Math.max( 1, Number( value ) || 12 )
								),
							} )
						}
						type="number"
						min={ 1 }
						max={ 18 }
					/>
					<TextControl
						label={ __( 'Enquiry form selector', 'gutenberg-lab-blocks' ) }
						help={ __( 'The calendar fills this CF7 form after a date range is selected.', 'gutenberg-lab-blocks' ) }
						value={ formSelector }
						onChange={ ( value ) => setAttributes( { formSelector: value } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<Placeholder
					icon="calendar-alt"
					label={ __( 'Villa Availability Calendar', 'gutenberg-lab-blocks' ) }
					instructions={ __( 'The frontend renders the live 12-month availability calendar from villa iCal feeds and manual blocked dates.', 'gutenberg-lab-blocks' ) }
				>
					<p>
						{ villaId
							? `${ __( 'Villa ID override:', 'gutenberg-lab-blocks' ) } ${ villaId }`
							: __( 'Uses the current villa page by default.', 'gutenberg-lab-blocks' ) }
					</p>
				</Placeholder>
			</div>
		</>
	);
};

registerBlockType( metadata.name, {
	edit: Edit,

	// Dynamic block: PHP renders the month grid and stored availability.
	save() {
		return null;
	},
} );
