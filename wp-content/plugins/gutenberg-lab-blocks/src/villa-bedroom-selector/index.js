import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	Button,
	Notice,
	PanelBody,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
// WordPress provides this package as an editor script dependency.
// eslint-disable-next-line import/no-extraneous-dependencies
import { useEffect } from '@wordpress/element';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { __, sprintf } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';

import './style.scss';

import metadata from './block.json';

const BEDROOM_SELECTOR_META_KEY = 'villa_bedroom_selector_enabled';
const BEDROOM_CHOICES_META_KEY = 'villa_bedroom_selector_choices';
const BEDROOM_SELECTOR_LOCK_KEY =
	'gutenberg-lab-villa-bedroom-selector-choices';
const MAX_CHOICES = 30;
const MAX_LABEL_LENGTH = 120;

const toInteger = ( value, maximum ) => {
	const number = Number.parseInt( value, 10 );

	if ( ! Number.isFinite( number ) || number < 1 ) {
		return 0;
	}

	return Math.min( maximum, number );
};

const normalizeChoiceLabel = ( value ) =>
	String( value ?? '' )
		.replace( /\s+/g, ' ' )
		.trim();

const getChoiceDedupeKey = ( value ) =>
	normalizeChoiceLabel( value ).toLowerCase();

const formatBedroomChoice = ( choice ) => {
	const bedrooms = toInteger( choice.bedrooms, MAX_CHOICES );
	const sleeps = toInteger( choice.sleeps, 100 );
	const bedroomLabel =
		bedrooms === 1
			? __( '1 Bedroom', 'gutenberg-lab-blocks' )
			: sprintf(
					/* translators: %d is the number of bedrooms. */
					__( '%d Bedrooms', 'gutenberg-lab-blocks' ),
					bedrooms
			  );

	return sleeps
		? sprintf(
				/* translators: 1: Bedroom label. 2: Number of guests. */
				__( '%1$s (sleeps %2$d)', 'gutenberg-lab-blocks' ),
				bedroomLabel,
				sleeps
		  )
		: bedroomLabel;
};

const getChoiceLabel = ( choice ) => {
	if ( typeof choice === 'string' || typeof choice === 'number' ) {
		return normalizeChoiceLabel( choice );
	}

	if ( ! choice || typeof choice !== 'object' ) {
		return '';
	}

	const label = normalizeChoiceLabel( choice.label );

	if ( label ) {
		return label;
	}

	if ( toInteger( choice.bedrooms, MAX_CHOICES ) < 1 ) {
		return '';
	}

	return formatBedroomChoice( choice );
};

const normalizeChoiceRows = ( choices ) =>
	( Array.isArray( choices ) ? choices : [] ).map( ( choice ) => ( {
		label: getChoiceLabel( choice ),
	} ) );

const getFilledChoiceRows = ( choices ) =>
	normalizeChoiceRows( choices ).filter(
		( choice ) => normalizeChoiceLabel( choice.label ) !== ''
	);

const collectVillaSpecs = ( blocks, data ) => {
	blocks.forEach( ( block ) => {
		if ( block.name === 'gutenberg-lab-blocks/villa-spec-item' ) {
			const label = String( block.attributes.label || '' )
				.trim()
				.toLowerCase();
			const value = toInteger( block.attributes.value, 100 );

			if ( /^bedrooms?$/.test( label ) ) {
				data.bedrooms = Math.min( MAX_CHOICES, value );
			}
		}

		if ( block.innerBlocks?.length ) {
			collectVillaSpecs( block.innerBlocks, data );
		}
	} );
};

const getOptionLabelHelp = ( invalid, duplicate, tooLong ) => {
	if ( invalid ) {
		return __( 'Enter option text.', 'gutenberg-lab-blocks' );
	}

	if ( duplicate ) {
		return __( 'Option text must be unique.', 'gutenberg-lab-blocks' );
	}

	if ( tooLong ) {
		return sprintf(
			/* translators: %d is the maximum number of characters. */
			__(
				'Keep option text to %d characters or fewer.',
				'gutenberg-lab-blocks'
			),
			MAX_LABEL_LENGTH
		);
	}

	return undefined;
};

const getChoiceState = ( customChoices, fallbackChoices, isEnabled ) => {
	const normalizedCustomChoices = normalizeChoiceRows( customChoices );
	const normalizedFallbackChoices = getFilledChoiceRows( fallbackChoices );
	const isCustom = normalizedCustomChoices.length > 0;
	const displayedChoices = isCustom
		? normalizedCustomChoices
		: normalizedFallbackChoices;
	const validPreviewChoices = displayedChoices.filter(
		( choice ) => normalizeChoiceLabel( choice.label ) !== ''
	);
	const seenLabels = new Set();
	const duplicateLabels = new Set();

	displayedChoices.forEach( ( choice ) => {
		const label = normalizeChoiceLabel( choice.label );

		if ( ! label ) {
			return;
		}

		const dedupeKey = getChoiceDedupeKey( label );

		if ( seenLabels.has( dedupeKey ) ) {
			duplicateLabels.add( dedupeKey );
		}

		seenLabels.add( dedupeKey );
	} );

	const hasInvalidCustomLabels =
		isCustom &&
		displayedChoices.some( ( choice ) => {
			const label = normalizeChoiceLabel( choice.label );

			return ! label || label.length > MAX_LABEL_LENGTH;
		} );
	const hasChoiceErrors =
		isEnabled &&
		( displayedChoices.length < 1 ||
			( isCustom &&
				( hasInvalidCustomLabels || duplicateLabels.size > 0 ) ) );

	return {
		canAddChoice: displayedChoices.length < MAX_CHOICES,
		displayedChoices,
		duplicateLabels,
		hasChoiceErrors,
		isCustom,
		validPreviewChoices,
	};
};

const useBedroomSelectorEnabled = () =>
	useSelect( ( select ) => {
		const editor = select( 'core/editor' );

		if ( editor.getCurrentPostType() !== 'villa' ) {
			return true;
		}

		const meta = editor.getEditedPostAttribute( 'meta' ) || {};

		return meta[ BEDROOM_SELECTOR_META_KEY ] ?? false;
	}, [] );

const useVillaBedroomChoices = () =>
	useSelect( ( select ) => {
		const editor = select( 'core/editor' );

		if ( editor.getCurrentPostType() !== 'villa' ) {
			return [];
		}

		const meta = editor.getEditedPostAttribute( 'meta' ) || {};

		return normalizeChoiceRows( meta[ BEDROOM_CHOICES_META_KEY ] );
	}, [] );

const useAutomaticBedroomChoices = ( minimumBedrooms ) =>
	useSelect(
		( select ) => {
			const data = { bedrooms: 0 };
			collectVillaSpecs(
				select( 'core/block-editor' ).getBlocks(),
				data
			);

			if ( data.bedrooms < 1 ) {
				return [];
			}

			const minimum = Math.min(
				data.bedrooms,
				Math.max( 1, toInteger( minimumBedrooms, MAX_CHOICES ) )
			);
			const choices = [];

			for (
				let bedrooms = data.bedrooms;
				bedrooms >= minimum;
				bedrooms--
			) {
				choices.push( {
					label: formatBedroomChoice( { bedrooms } ),
				} );
			}

			return choices;
		},
		[ minimumBedrooms ]
	);

const BedroomSelectorToggleControl = () => {
	const isEnabled = useBedroomSelectorEnabled();
	const { editPost } = useDispatch( 'core/editor' );

	return (
		<ToggleControl
			label={ __( 'Enable bedroom selectors', 'gutenberg-lab-blocks' ) }
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
	);
};

const BedroomChoicesEditor = ( {
	choices,
	fallbackChoices,
	isEnabled,
	onChange,
} ) => {
	const {
		canAddChoice,
		displayedChoices,
		duplicateLabels,
		hasChoiceErrors,
		isCustom,
	} = getChoiceState( choices, fallbackChoices, isEnabled );

	const updateChoice = ( index, value ) => {
		const nextChoices = displayedChoices.map( ( choice ) => ( {
			...choice,
		} ) );
		nextChoices[ index ] = { label: value };
		onChange( nextChoices );
	};

	const removeChoice = ( index ) => {
		onChange(
			displayedChoices.filter(
				( _, choiceIndex ) => choiceIndex !== index
			)
		);
	};

	const addChoice = () => {
		if ( ! canAddChoice ) {
			return;
		}

		onChange( [ ...displayedChoices, { label: '' } ] );
	};

	return (
		<>
			{ ! isCustom && fallbackChoices.length > 0 && (
				<Notice status="info" isDismissible={ false }>
					{ __(
						'Currently generated from the Villa Specs block as bedroom-only labels. Edit a row to create a custom list.',
						'gutenberg-lab-blocks'
					) }
				</Notice>
			) }

			{ hasChoiceErrors && (
				<Notice status="error" isDismissible={ false }>
					{ __(
						'Add unique option text before saving this villa.',
						'gutenberg-lab-blocks'
					) }
				</Notice>
			) }

			{ displayedChoices.map( ( choice, index ) => {
				const label = normalizeChoiceLabel( choice.label );
				const invalid = ! label;
				const tooLong = label.length > MAX_LABEL_LENGTH;
				const duplicate = duplicateLabels.has(
					getChoiceDedupeKey( label )
				);

				return (
					<fieldset key={ index }>
						<legend>
							{ sprintf(
								/* translators: %d is the choice number. */
								__( 'Choice %d', 'gutenberg-lab-blocks' ),
								index + 1
							) }
						</legend>
						<TextControl
							label={ __(
								'Option text',
								'gutenberg-lab-blocks'
							) }
							value={ choice.label }
							help={ getOptionLabelHelp(
								invalid,
								duplicate,
								tooLong
							) }
							onChange={ ( value ) =>
								updateChoice( index, value )
							}
							__nextHasNoMarginBottom
						/>
						<Button
							variant="link"
							isDestructive
							aria-label={ sprintf(
								/* translators: %d is the choice number. */
								__(
									'Remove choice %d',
									'gutenberg-lab-blocks'
								),
								index + 1
							) }
							onClick={ () => removeChoice( index ) }
						>
							{ __( 'Remove choice', 'gutenberg-lab-blocks' ) }
						</Button>
						<hr />
					</fieldset>
				);
			} ) }

			{ displayedChoices.length === 0 && (
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'Add custom option text, or add a Bedrooms value to the Villa Specs block.',
						'gutenberg-lab-blocks'
					) }
				</Notice>
			) }

			<Button
				variant="secondary"
				disabled={ ! canAddChoice }
				onClick={ addChoice }
			>
				{ __( 'Add option', 'gutenberg-lab-blocks' ) }
			</Button>

			{ isCustom && fallbackChoices.length > 0 && (
				<Button variant="tertiary" onClick={ () => onChange( [] ) }>
					{ __( 'Reset to Villa Specs', 'gutenberg-lab-blocks' ) }
				</Button>
			) }
		</>
	);
};

const VillaBedroomSelectorControls = ( {
	legacyChoices = [],
	manageLock = false,
	minimumBedrooms = 1,
} ) => {
	const isEnabled = useBedroomSelectorEnabled();
	const metaChoices = useVillaBedroomChoices();
	const automaticChoices = useAutomaticBedroomChoices( minimumBedrooms );
	const legacyFallbackChoices = getFilledChoiceRows( legacyChoices );
	const fallbackChoices = legacyFallbackChoices.length
		? legacyFallbackChoices
		: automaticChoices;
	const { editPost, lockPostSaving, unlockPostSaving } =
		useDispatch( 'core/editor' );
	const { hasChoiceErrors } = getChoiceState(
		metaChoices,
		fallbackChoices,
		isEnabled
	);

	useEffect( () => {
		if ( ! manageLock ) {
			return undefined;
		}

		if ( hasChoiceErrors ) {
			lockPostSaving( BEDROOM_SELECTOR_LOCK_KEY );
		} else {
			unlockPostSaving( BEDROOM_SELECTOR_LOCK_KEY );
		}

		return () => unlockPostSaving( BEDROOM_SELECTOR_LOCK_KEY );
	}, [ hasChoiceErrors, lockPostSaving, manageLock, unlockPostSaving ] );

	return (
		<>
			<p>
				{ __(
					'These options populate both the Seasonal Pricing selector and the enquiry form. Each row is the exact text visitors see.',
					'gutenberg-lab-blocks'
				) }
			</p>

			<BedroomSelectorToggleControl />

			<BedroomChoicesEditor
				choices={ metaChoices }
				fallbackChoices={ fallbackChoices }
				isEnabled={ isEnabled }
				onChange={ ( nextChoices ) =>
					editPost( {
						meta: {
							[ BEDROOM_CHOICES_META_KEY ]: nextChoices,
						},
					} )
				}
			/>
		</>
	);
};

const Edit = ( { attributes } ) => {
	const { bedroomChoices = [], minimumBedrooms } = attributes;
	const isEnabled = useBedroomSelectorEnabled();
	const metaChoices = useVillaBedroomChoices();
	const automaticChoices = useAutomaticBedroomChoices( minimumBedrooms );
	const legacyChoices = getFilledChoiceRows( bedroomChoices );
	const fallbackChoices = legacyChoices.length
		? legacyChoices
		: automaticChoices;
	const { validPreviewChoices } = getChoiceState(
		metaChoices,
		fallbackChoices,
		isEnabled
	);
	const blockProps = useBlockProps( {
		className: 'vvm-villa-bedroom-selector',
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Bedroom choices', 'gutenberg-lab-blocks' ) }
				>
					<VillaBedroomSelectorControls
						legacyChoices={ bedroomChoices }
						minimumBedrooms={ minimumBedrooms }
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
						{ validPreviewChoices.length > 0 ? (
							validPreviewChoices.map( ( choice, index ) => (
								<option
									key={ `${ choice.label }-${ index }` }
									value={ choice.label }
								>
									{ choice.label }
								</option>
							) )
						) : (
							<option>
								{ __(
									'Add at least one bedroom option',
									'gutenberg-lab-blocks'
								) }
							</option>
						) }
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

	if ( postType !== 'villa' ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="villa-bedroom-selector"
			title={ __( 'Bedroom selector', 'gutenberg-lab-blocks' ) }
		>
			<VillaBedroomSelectorControls manageLock />
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
