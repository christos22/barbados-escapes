( ( wp ) => {
	const { blockEditor, blocks, components, compose, element, hooks, i18n } = wp;

	if (
		! blockEditor ||
		! blocks ||
		! components ||
		! compose ||
		! element ||
		! hooks ||
		! i18n
	) {
		return;
	}

	const ATTRIBUTE_NAME = 'vvmHidden';

	const { InspectorControls } = blockEditor;
	const { PanelBody, ToggleControl } = components;
	const { createHigherOrderComponent } = compose;
	const { Fragment, createElement } = element;
	const { addFilter } = hooks;
	const { __ } = i18n;

	// Register the attribute globally so every block can save the same flag in
	// its serialized block comment, including core blocks and synced patterns.
	const addVisibilityAttribute = ( settings ) => ( {
		...settings,
		attributes: {
			...( settings.attributes || {} ),
			[ ATTRIBUTE_NAME ]: {
				type: 'boolean',
				default: false,
			},
		},
	} );

	addFilter(
		'blocks.registerBlockType',
		'gutenberg-lab-blocks/block-visibility-attribute',
		addVisibilityAttribute
	);

	const withBlockVisibilityControl = createHigherOrderComponent(
		( BlockEdit ) => ( props ) => {
			const { attributes = {}, setAttributes } = props;

			if ( typeof setAttributes !== 'function' ) {
				return createElement( BlockEdit, props );
			}

			const isHidden = Boolean( attributes[ ATTRIBUTE_NAME ] );

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
							title: __( 'Block abilities', 'gutenberg-lab-blocks' ),
							initialOpen: false,
						},
						createElement( ToggleControl, {
							label: __( 'Hide on frontend', 'gutenberg-lab-blocks' ),
							checked: isHidden,
							help: isHidden
								? __(
										'PHP will skip this block before frontend markup is rendered.',
										'gutenberg-lab-blocks'
								  )
								: __(
										'This block renders normally on the frontend.',
										'gutenberg-lab-blocks'
								  ),
							onChange: ( shouldHide ) =>
								setAttributes( { [ ATTRIBUTE_NAME ]: shouldHide } ),
						} )
					)
				)
			);
		},
		'withBlockVisibilityControl'
	);

	addFilter(
		'editor.BlockEdit',
		'gutenberg-lab-blocks/block-visibility-control',
		withBlockVisibilityControl
	);

	// Keep hidden blocks visible in the editor so authors can select and unhide
	// them, while making their frontend state obvious at a glance.
	const withHiddenBlockEditorClass = createHigherOrderComponent(
		( BlockListBlock ) => ( props ) => {
			const isHidden = Boolean(
				props.attributes && props.attributes[ ATTRIBUTE_NAME ]
			);
			const className = [
				props.className,
				isHidden ? 'is-vvm-hidden-on-frontend' : '',
			]
				.filter( Boolean )
				.join( ' ' );

			return createElement( BlockListBlock, { ...props, className } );
		},
		'withHiddenBlockEditorClass'
	);

	addFilter(
		'editor.BlockListBlock',
		'gutenberg-lab-blocks/block-visibility-editor-class',
		withHiddenBlockEditorClass
	);
} )( window.wp );
