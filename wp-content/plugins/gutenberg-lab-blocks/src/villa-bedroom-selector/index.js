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
const BEDROOM_SELECTOR_LOCK_KEY =
	'gutenberg-lab-villa-bedroom-selector-choices';

const toInteger = ( value, maximum ) => {
	const number = Number.parseInt( value, 10 );

	if ( ! Number.isFinite( number ) || number < 1 ) {
		return 0;
	}

	return Math.min( maximum, number );
};

const formatBedroomChoice = ( choice ) => {
	const bedrooms = toInteger( choice.bedrooms, 30 );
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

const collectVillaSpecs = ( blocks, data ) => {
	blocks.forEach( ( block ) => {
		if ( block.name === 'gutenberg-lab-blocks/villa-spec-item' ) {
			const label = String( block.attributes.label || '' )
				.trim()
				.toLowerCase();
			const value = toInteger( block.attributes.value, 100 );

			if ( /^bedrooms?$/.test( label ) ) {
				data.bedrooms = Math.min( 30, value );
			}

			if ( /^(sleeps?|guests?)$/.test( label ) ) {
				data.sleeps = value;
			}
		}

		if ( block.innerBlocks?.length ) {
			collectVillaSpecs( block.innerBlocks, data );
		}
	} );
};

const getBedroomFieldHelp = ( invalid, duplicate ) => {
	if ( invalid ) {
		return __(
			'Enter a bedroom number from 1 to 30.',
			'gutenberg-lab-blocks'
		);
	}

	if ( duplicate ) {
		return __( 'Bedroom numbers must be unique.', 'gutenberg-lab-blocks' );
	}

	return undefined;
};

const useBedroomSelectorEnabled = () =>
	useSelect( ( select ) => {
		const editor = select( 'core/editor' );

		if ( editor.getCurrentPostType() !== 'villa' ) {
			return true;
		}

		const meta = editor.getEditedPostAttribute( 'meta' ) || {};

		return meta[ BEDROOM_SELECTOR_META_KEY ] ?? true;
	}, [] );

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

const useAutomaticBedroomChoices = ( minimumBedrooms ) =>
	useSelect(
		( select ) => {
			const data = { bedrooms: 0, sleeps: 0 };
			collectVillaSpecs(
				select( 'core/block-editor' ).getBlocks(),
				data
			);

			if ( data.bedrooms < 1 ) {
				return [];
			}

			const minimum = Math.min(
				data.bedrooms,
				Math.max( 1, toInteger( minimumBedrooms, 30 ) )
			);
			const capacity =
				data.bedrooms > 0 && data.sleeps % data.bedrooms === 0
					? data.sleeps / data.bedrooms
					: 0;
			const choices = [];

			for (
				let bedrooms = data.bedrooms;
				bedrooms >= minimum;
				bedrooms--
			) {
				choices.push( {
					bedrooms,
					sleeps: bedrooms * capacity || '',
				} );
			}

			return choices;
		},
		[ minimumBedrooms ]
	);

const Edit = ( { attributes, clientId, setAttributes } ) => {
	const { bedroomChoices = [], minimumBedrooms } = attributes;
	const isEnabled = useBedroomSelectorEnabled();
	const automaticChoices = useAutomaticBedroomChoices( minimumBedrooms );
	const isCustom = bedroomChoices.length > 0;
	const displayedChoices = isCustom ? bedroomChoices : automaticChoices;
	const validPreviewChoices = displayedChoices.filter(
		( choice ) => toInteger( choice.bedrooms, 30 ) > 0
	);
	const duplicatedBedrooms = new Set(
		validPreviewChoices
			.map( ( choice ) => toInteger( choice.bedrooms, 30 ) )
			.filter(
				( bedrooms, index, choices ) =>
					choices.indexOf( bedrooms ) !== index
			)
	);
	const usedBedroomCount = new Set(
		validPreviewChoices.map( ( choice ) =>
			toInteger( choice.bedrooms, 30 )
		)
	).size;
	const canAddChoice = usedBedroomCount < 30;
	const hasChoiceErrors =
		isEnabled &&
		isCustom &&
		( validPreviewChoices.length < 1 ||
			displayedChoices.some(
				( choice ) => toInteger( choice.bedrooms, 30 ) < 1
			) ||
			duplicatedBedrooms.size > 0 );
	const blockProps = useBlockProps( {
		className: 'vvm-villa-bedroom-selector',
	} );
	const { lockPostSaving, unlockPostSaving } = useDispatch( 'core/editor' );
	const postLockKey = `${ BEDROOM_SELECTOR_LOCK_KEY }-${ clientId }`;

	useEffect( () => {
		if ( hasChoiceErrors ) {
			lockPostSaving( postLockKey );
		} else {
			unlockPostSaving( postLockKey );
		}

		return () => unlockPostSaving( postLockKey );
	}, [ hasChoiceErrors, lockPostSaving, postLockKey, unlockPostSaving ] );

	const updateChoice = ( index, property, value ) => {
		const choices = displayedChoices.map( ( choice ) => ( {
			...choice,
		} ) );
		choices[ index ][ property ] = value;
		setAttributes( { bedroomChoices: choices } );
	};

	const removeChoice = ( index ) => {
		setAttributes( {
			bedroomChoices: displayedChoices.filter(
				( _, choiceIndex ) => choiceIndex !== index
			),
		} );
	};

	const addChoice = () => {
		if ( ! canAddChoice ) {
			return;
		}

		const usedBedrooms = new Set(
			displayedChoices.map( ( choice ) =>
				toInteger( choice.bedrooms, 30 )
			)
		);
		const maximum = Math.max( 0, ...usedBedrooms );
		let bedrooms = maximum;

		while ( bedrooms > 0 && usedBedrooms.has( bedrooms ) ) {
			bedrooms--;
		}

		if ( bedrooms < 1 ) {
			bedrooms = Math.min( 30, maximum + 1 || 1 );
		}

		const reference = displayedChoices.find( ( choice ) => {
			const choiceBedrooms = toInteger( choice.bedrooms, 30 );
			const choiceSleeps = toInteger( choice.sleeps, 100 );

			return (
				choiceBedrooms > 0 &&
				choiceSleeps > 0 &&
				choiceSleeps % choiceBedrooms === 0
			);
		} );
		const capacity = reference
			? toInteger( reference.sleeps, 100 ) /
			  toInteger( reference.bedrooms, 30 )
			: 0;
		const newChoice = {
			bedrooms,
			sleeps: bedrooms * capacity || '',
		};
		const choices = [ ...displayedChoices, newChoice ].sort(
			( first, second ) =>
				toInteger( second.bedrooms, 30 ) -
				toInteger( first.bedrooms, 30 )
		);

		setAttributes( { bedroomChoices: choices } );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Bedroom choices', 'gutenberg-lab-blocks' ) }
				>
					<p>
						{ __(
							'These options populate both the pricing selector and the enquiry form. Sleeps is optional.',
							'gutenberg-lab-blocks'
						) }
					</p>

					<BedroomSelectorToggleControl />

					{ ! isCustom && automaticChoices.length > 0 && (
						<Notice status="info" isDismissible={ false }>
							{ __(
								'Currently generated from the Villa Specs block. Editing a row creates a custom list.',
								'gutenberg-lab-blocks'
							) }
						</Notice>
					) }

					{ hasChoiceErrors && (
						<Notice status="error" isDismissible={ false }>
							{ __(
								'Fix the bedroom choices before saving this villa.',
								'gutenberg-lab-blocks'
							) }
						</Notice>
					) }

					{ displayedChoices.map( ( choice, index ) => {
						const bedrooms = toInteger( choice.bedrooms, 30 );
						const invalid = bedrooms < 1;
						const duplicate = duplicatedBedrooms.has( bedrooms );

						return (
							<fieldset key={ index }>
								<legend>
									{ sprintf(
										/* translators: %d is the choice number. */
										__(
											'Choice %d',
											'gutenberg-lab-blocks'
										),
										index + 1
									) }
								</legend>
								<TextControl
									label={ __(
										'Bedrooms',
										'gutenberg-lab-blocks'
									) }
									type="number"
									min="1"
									max="30"
									value={ choice.bedrooms }
									help={ getBedroomFieldHelp(
										invalid,
										duplicate
									) }
									onChange={ ( value ) =>
										updateChoice( index, 'bedrooms', value )
									}
									__nextHasNoMarginBottom
								/>
								<TextControl
									label={ __(
										'Sleeps',
										'gutenberg-lab-blocks'
									) }
									type="number"
									min="1"
									max="100"
									value={ choice.sleeps }
									onChange={ ( value ) =>
										updateChoice( index, 'sleeps', value )
									}
									__nextHasNoMarginBottom
								/>
								<Button
									variant="link"
									isDestructive
									disabled={ displayedChoices.length < 2 }
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
									{ __(
										'Remove choice',
										'gutenberg-lab-blocks'
									) }
								</Button>
								<hr />
							</fieldset>
						);
					} ) }

					{ displayedChoices.length === 0 && (
						<Notice status="warning" isDismissible={ false }>
							{ __(
								'Add Bedroom and Sleeps values to the Villa Specs block, or add a custom choice here.',
								'gutenberg-lab-blocks'
							) }
						</Notice>
					) }

					<Button
						variant="secondary"
						disabled={ ! canAddChoice }
						onClick={ addChoice }
					>
						{ __( 'Add choice', 'gutenberg-lab-blocks' ) }
					</Button>

					{ isCustom && automaticChoices.length > 0 && (
						<Button
							variant="tertiary"
							onClick={ () =>
								setAttributes( { bedroomChoices: [] } )
							}
						>
							{ __(
								'Reset to Villa Specs',
								'gutenberg-lab-blocks'
							) }
						</Button>
					) }
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
									key={ `${ choice.bedrooms }-${ index }` }
								>
									{ formatBedroomChoice( choice ) }
								</option>
							) )
						) : (
							<option>
								{ __(
									'Add at least one bedroom choice',
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
			<BedroomSelectorToggleControl />
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
