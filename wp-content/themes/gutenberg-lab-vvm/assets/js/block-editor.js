( ( wp ) => {
	const {
		blockEditor,
		blocks,
		components,
		compose,
		domReady,
		element,
		hooks,
		i18n,
	} = wp;

	const VILLA_AMENITIES_CLASS = 'vvm-villa-amenities';
	const SHOW_NUMBERS_CLASS = 'vvm-villa-amenities--show-numbers';
	const EXCLUDED_AMENITIES_CLASSES = [
		'vvm-villa-contact',
		'vvm-villa-pricing',
		'vvm-villa-reviews',
		'vvm-villa-rules',
	];

	const hasClass = ( className = '', classToFind ) =>
		className.split( /\s+/ ).includes( classToFind );

	const toggleClass = ( className = '', classToToggle, shouldAdd ) => {
		const classes = className.split( /\s+/ ).filter( Boolean );
		const filteredClasses = classes.filter(
			( currentClass ) => currentClass !== classToToggle
		);

		if ( shouldAdd ) {
			filteredClasses.push( classToToggle );
		}

		return filteredClasses.join( ' ' );
	};

	domReady( () => {
		// The theme ships its own button design system, so hide Gutenberg's stock
		// Outline style to keep editors on the supported branded variants.
		if ( blocks && blocks.unregisterBlockStyle ) {
			blocks.unregisterBlockStyle( 'core/button', 'outline' );
		}
	} );

	// Villa amenities are authored with core Group blocks. This control keeps
	// the editor UX simple while storing the choice as a normal block class.
	if (
		blockEditor &&
		components &&
		compose &&
		element &&
		hooks &&
		i18n
	) {
		const { InspectorControls } = blockEditor;
		const { PanelBody, ToggleControl } = components;
		const { createHigherOrderComponent } = compose;
		const { Fragment, createElement } = element;
		const { addFilter } = hooks;
		const { __ } = i18n;

		const withVillaAmenitiesNumberToggle = createHigherOrderComponent(
			( BlockEdit ) => ( props ) => {
				const { attributes, name, setAttributes } = props;
				const className = attributes.className || '';

				if (
					name !== 'core/group' ||
					! hasClass( className, VILLA_AMENITIES_CLASS ) ||
					EXCLUDED_AMENITIES_CLASSES.some( ( excludedClass ) =>
						hasClass( className, excludedClass )
					)
				) {
					return createElement( BlockEdit, props );
				}

				const showNumbers = hasClass( className, SHOW_NUMBERS_CLASS );

				return createElement(
					Fragment,
					null,
					createElement( BlockEdit, props ),
					createElement(
						InspectorControls,
						null,
						createElement(
							PanelBody,
							{
								title: __( 'Villa amenities', 'gutenberg-lab-vvm' ),
								initialOpen: true,
							},
							createElement( ToggleControl, {
								label: __(
									'Show numbers instead of icons',
									'gutenberg-lab-vvm'
								),
								checked: showNumbers,
								help: showNumbers
									? __(
											'Amenity rows show their saved numbers.',
											'gutenberg-lab-vvm'
									  )
									: __(
											'Amenity rows show the current theme icons.',
											'gutenberg-lab-vvm'
									  ),
								onChange: ( shouldShowNumbers ) => {
									setAttributes( {
										className: toggleClass(
											className,
											SHOW_NUMBERS_CLASS,
											shouldShowNumbers
										),
									} );
								},
							} )
						)
					)
				);
			},
			'withVillaAmenitiesNumberToggle'
		);

		addFilter(
			'editor.BlockEdit',
			'gutenberg-lab-vvm/villa-amenities-number-toggle',
			withVillaAmenitiesNumberToggle
		);
	}
} )( window.wp );
