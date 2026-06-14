import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	Notice,
	PanelBody,
	RangeControl,
	ToggleControl,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';

import './style.scss';

import metadata from './block.json';

const BEDROOM_SELECTOR_META_KEY = 'villa_bedroom_selector_enabled';

const useBedroomSelectorEnabled = () =>
	useSelect( ( select ) => {
		const editor = select( 'core/editor' );

		if ( editor.getCurrentPostType() !== 'villa' ) {
			return true;
		}

		const meta = editor.getEditedPostAttribute( 'meta' ) || {};

		return meta[ BEDROOM_SELECTOR_META_KEY ] ?? true;
	}, [] );

const Edit = ( { attributes, setAttributes } ) => {
	const { minimumBedrooms } = attributes;
	const isEnabled = useBedroomSelectorEnabled();
	const blockProps = useBlockProps( {
		className: 'vvm-villa-bedroom-selector',
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Bedroom choices', 'gutenberg-lab-blocks' ) }
				>
					<RangeControl
						label={ __(
							'Minimum bedrooms',
							'gutenberg-lab-blocks'
						) }
						help={ __(
							'The maximum comes from the villa’s Bedroom spec.',
							'gutenberg-lab-blocks'
						) }
						value={ minimumBedrooms }
						onChange={ ( value ) =>
							setAttributes( {
								minimumBedrooms: Math.max(
									1,
									Number( value ) || 1
								),
							} )
						}
						min={ 1 }
						max={ 30 }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ isEnabled ? (
					<select
						className="vvm-villa-bedroom-selector__select"
						aria-label={ __(
							'Bedrooms for seasonal pricing',
							'gutenberg-lab-blocks'
						) }
						disabled
					>
						<option>
							{ __(
								'Choices use the current Villa Specs block',
								'gutenberg-lab-blocks'
							) }
						</option>
					</select>
				) : (
					<Notice status="info" isDismissible={ false }>
						{ __(
							'Bedroom selectors are disabled for this villa.',
							'gutenberg-lab-blocks'
						) }
					</Notice>
				) }
			</div>
		</>
	);
};

const VillaBedroomSelectorSettings = () => {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);
	const isEnabled = useBedroomSelectorEnabled();
	const { editPost } = useDispatch( 'core/editor' );

	if ( postType !== 'villa' ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="villa-bedroom-selector"
			title={ __( 'Bedroom selector', 'gutenberg-lab-blocks' ) }
		>
			<ToggleControl
				label={ __(
					'Enable bedroom selectors',
					'gutenberg-lab-blocks'
				) }
				help={
					isEnabled
						? __(
								'The pricing and enquiry form selectors are visible.',
								'gutenberg-lab-blocks'
						  )
						: __(
								'Both bedroom selectors are hidden and inactive.',
								'gutenberg-lab-blocks'
						  )
				}
				checked={ isEnabled }
				onChange={ ( value ) =>
					editPost( {
						meta: {
							[ BEDROOM_SELECTOR_META_KEY ]: value,
						},
					} )
				}
				__nextHasNoMarginBottom
			/>
		</PluginDocumentSettingPanel>
	);
};

registerBlockType( metadata.name, {
	edit: Edit,

	// Dynamic block: PHP reads the current villa and renders trusted options.
	save() {
		return null;
	},
} );

registerPlugin( 'gutenberg-lab-villa-bedroom-selector-settings', {
	render: VillaBedroomSelectorSettings,
} );
