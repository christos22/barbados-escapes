( function( wp, settings ) {
	if ( ! wp || ! settings ) {
		return;
	}

	const { __, sprintf } = wp.i18n;
	const { createElement, Fragment, useEffect, useMemo, useState } = wp.element;
	const { registerPlugin } = wp.plugins;
	const { PluginDocumentSettingPanel } = wp.editPost;
	const {
		Button,
		ComboboxControl,
		Notice,
		PanelRow,
		SelectControl,
		TextControl,
	} = wp.components;
	const { useDispatch, useSelect } = wp.data;
	const { addQueryArgs } = wp.url;
	const apiFetch = wp.apiFetch;

	const DISPLAY_TYPE_CHOICES = Object.entries( settings.displayTypeChoices || {} ).map(
		( [ value, label ] ) => ( { value, label } )
	);
	const SLOT_CHOICES = Object.entries( settings.slotChoices || {} ).map(
		( [ value, label ] ) => ( { value, label } )
	);
	const DISMISSAL_CHOICES = Object.entries( settings.dismissalChoices || {} ).map(
		( [ value, label ] ) => ( { value, label } )
	);
	const VISIBILITY_CHOICES = Object.entries( settings.visibilityChoices || {} ).map(
		( [ value, label ] ) => ( { value, label } )
	);
	const TARGET_POST_TYPES = settings.targetPostTypes || [ 'page', 'post', 'packages' ];
	const INVALID_SCHEDULE_LOCK = 'gutenberg-lab-site-message-invalid-schedule';

	function stripSerializedBlockContent( serializedContent ) {
		if ( typeof serializedContent !== 'string' ) {
			return '';
		}

		return serializedContent
			.replace( /<!--[\s\S]*?-->/g, '' )
			.replace( /<[^>]+>/g, ' ' )
			.replace( /\s+/g, ' ' )
			.trim();
	}

	function toLocalDateTimeInputValue( utcString ) {
		if ( typeof utcString !== 'string' || ! utcString ) {
			return '';
		}

		const date = new Date( utcString );

		if ( Number.isNaN( date.getTime() ) ) {
			return '';
		}

		const year = date.getFullYear();
		const month = `${ date.getMonth() + 1 }`.padStart( 2, '0' );
		const day = `${ date.getDate() }`.padStart( 2, '0' );
		const hours = `${ date.getHours() }`.padStart( 2, '0' );
		const minutes = `${ date.getMinutes() }`.padStart( 2, '0' );

		return `${ year }-${ month }-${ day }T${ hours }:${ minutes }`;
	}

	function toUtcISOString( localValue ) {
		if ( typeof localValue !== 'string' || ! localValue ) {
			return '';
		}

		const date = new Date( localValue );

		return Number.isNaN( date.getTime() ) ? '' : date.toISOString();
	}

	function getStatusLabel( startUtc, endUtc ) {
		const now = Date.now();
		const start = startUtc ? Date.parse( startUtc ) : 0;
		const end = endUtc ? Date.parse( endUtc ) : 0;

		if ( start && end && end <= start ) {
			return __( 'Invalid window', 'gutenberg-lab-blocks' );
		}

		if ( start && start > now ) {
			return __( 'Upcoming', 'gutenberg-lab-blocks' );
		}

		if ( end && end <= now ) {
			return __( 'Expired', 'gutenberg-lab-blocks' );
		}

		return __( 'Active', 'gutenberg-lab-blocks' );
	}

	function SiteMessagePanels() {
		const { editPost, lockPostSaving, unlockPostSaving } = useDispatch( 'core/editor' );
		const currentPostType = useSelect(
			( select ) => select( 'core/editor' ).getCurrentPostType(),
			[]
		);
		const meta = useSelect(
			( select ) => select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {},
			[]
		);
		const serializedContent = useSelect(
			( select ) => select( 'core/editor' ).getEditedPostContent(),
			[]
		);
		const currentPostId = useSelect(
			( select ) => select( 'core/editor' ).getCurrentPostId(),
			[]
		);
		const currentPostStatus = useSelect(
			( select ) => select( 'core/editor' ).getEditedPostAttribute( 'status' ),
			[]
		);

		const targetIds = Array.isArray( meta.target_object_ids )
			? meta.target_object_ids.map( ( id ) => Number.parseInt( id, 10 ) ).filter( Boolean )
			: [];
		const [ searchTerm, setSearchTerm ] = useState( '' );
		const [ searchResults, setSearchResults ] = useState( [] );
		const [ selectedItems, setSelectedItems ] = useState( [] );
		const [ isSearching, setIsSearching ] = useState( false );

		useEffect( () => {
			if ( currentPostType !== settings.postType ) {
				return;
			}

			if ( targetIds.length === 0 ) {
				setSelectedItems( [] );
				return;
			}

			let cancelled = false;

			async function fetchSelectedItems() {
				const requests = TARGET_POST_TYPES.map( ( subtype ) =>
					apiFetch( {
						path: addQueryArgs( `/wp/v2/${ subtype }`, {
							include: targetIds,
							per_page: targetIds.length,
							_fields: 'id,title',
						} ),
					} ).catch( () => [] )
				);

				const results = await Promise.all( requests );

				if ( cancelled ) {
					return;
				}

				const flattened = results
					.flat()
					.map( ( item ) => ( {
						id: item.id,
						label:
							item?.title?.rendered && item.title.rendered !== ''
								? item.title.rendered.replace( /<[^>]+>/g, '' )
								: sprintf(
										/* translators: %d site content ID. */
										__( 'Content #%d', 'gutenberg-lab-blocks' ),
										item.id
								  ),
					} ) );

				setSelectedItems( flattened );
			}

			fetchSelectedItems();

			return () => {
				cancelled = true;
			};
		}, [ currentPostType, targetIds.join( ',' ) ] );

		useEffect( () => {
			if ( currentPostType !== settings.postType ) {
				return undefined;
			}

			if ( searchTerm.trim().length < 2 ) {
				setSearchResults( [] );
				return undefined;
			}

			let cancelled = false;
			setIsSearching( true );

			apiFetch( {
				path: addQueryArgs( '/wp/v2/search', {
					search: searchTerm,
					type: 'post',
					subtype: TARGET_POST_TYPES,
					per_page: 20,
				} ),
			} )
				.then( ( results ) => {
					if ( cancelled ) {
						return;
					}

					setSearchResults(
						( results || [] )
							.filter( ( item ) => ! targetIds.includes( Number( item.id ) ) )
							.map( ( item ) => ( {
								value: String( item.id ),
								label: item.title || sprintf(
									/* translators: %d site content ID. */
									__( 'Content #%d', 'gutenberg-lab-blocks' ),
									item.id
								),
								id: Number( item.id ),
							} ) )
					);
				} )
				.catch( () => {
					if ( ! cancelled ) {
						setSearchResults( [] );
					}
				} )
				.finally( () => {
					if ( ! cancelled ) {
						setIsSearching( false );
					}
				} );

			return () => {
				cancelled = true;
			};
		}, [ currentPostType, searchTerm, targetIds.join( ',' ) ] );

		if ( currentPostType !== settings.postType ) {
			return null;
		}

		const nextMeta = ( key, value ) => {
			editPost( {
				meta: {
					...meta,
					[ key ]: value,
				},
			} );
		};

		const displayType = meta.display_type || 'alert_bar';
		const placementSlot = meta.placement_slot || 'header';
		const visibilityScope = meta.visibility_scope || 'sitewide';
		const dismissalExpiry = meta.dismissal_expiry || 'permanent';
		const startAtUtc = meta.start_at_utc || '';
		const endAtUtc = meta.end_at_utc || '';
		const strippedContent = stripSerializedBlockContent( serializedContent );
		const invalidSchedule =
			startAtUtc &&
			endAtUtc &&
			Date.parse( endAtUtc ) > 0 &&
			Date.parse( startAtUtc ) > 0 &&
			Date.parse( endAtUtc ) <= Date.parse( startAtUtc );
		const missingTargetIds = targetIds.filter(
			( targetId ) => ! selectedItems.find( ( item ) => item.id === targetId )
		);
		const shouldWarnEmptyContent =
			currentPostStatus === 'publish' &&
			visibilityScope === 'sitewide' &&
			strippedContent === '';

		const selectedItemMap = useMemo( () => {
			return new Map( selectedItems.map( ( item ) => [ item.id, item.label ] ) );
		}, [ selectedItems ] );

		const searchOptions = searchResults.map( ( item ) => ( {
			label: item.label,
			value: item.value,
		} ) );

		useEffect( () => {
			if ( currentPostType !== settings.postType ) {
				return undefined;
			}

			if ( invalidSchedule ) {
				lockPostSaving( INVALID_SCHEDULE_LOCK );
			} else {
				unlockPostSaving( INVALID_SCHEDULE_LOCK );
			}

			return () => {
				unlockPostSaving( INVALID_SCHEDULE_LOCK );
			};
		}, [ currentPostType, invalidSchedule, lockPostSaving, unlockPostSaving ] );

		return createElement(
			Fragment,
			null,
			createElement(
				PluginDocumentSettingPanel,
				{
					name: 'gutenberg-lab-site-message-display',
					title: __( 'Display', 'gutenberg-lab-blocks' ),
				},
				createElement( SelectControl, {
					label: __( 'Message Type', 'gutenberg-lab-blocks' ),
					value: displayType,
					options: DISPLAY_TYPE_CHOICES,
					onChange: ( value ) => nextMeta( 'display_type', value ),
				} ),
				createElement( SelectControl, {
					label: __( 'Placement Slot', 'gutenberg-lab-blocks' ),
					value: placementSlot,
					options: SLOT_CHOICES,
					onChange: ( value ) => nextMeta( 'placement_slot', value ),
				} ),
				displayType === 'modal'
					? createElement(
							Notice,
							{ status: 'info', isDismissible: false },
							__(
								'Modal messages are part of the shared content model, but modal rendering is not enabled yet in this lab site.',
								'gutenberg-lab-blocks'
							)
					  )
					: null
			),
			createElement(
				PluginDocumentSettingPanel,
				{
					name: 'gutenberg-lab-site-message-scheduling',
					title: __( 'Scheduling', 'gutenberg-lab-blocks' ),
				},
				createElement( PanelRow, null, getStatusLabel( startAtUtc, endAtUtc ) ),
				createElement( TextControl, {
					label: __( 'Start At', 'gutenberg-lab-blocks' ),
					type: 'datetime-local',
					value: toLocalDateTimeInputValue( startAtUtc ),
					onChange: ( value ) => nextMeta( 'start_at_utc', toUtcISOString( value ) ),
					help: __( 'Stored in UTC and compared server-side.', 'gutenberg-lab-blocks' ),
				} ),
				createElement( TextControl, {
					label: __( 'End At', 'gutenberg-lab-blocks' ),
					type: 'datetime-local',
					value: toLocalDateTimeInputValue( endAtUtc ),
					onChange: ( value ) => nextMeta( 'end_at_utc', toUtcISOString( value ) ),
				} ),
				invalidSchedule
					? createElement(
							Notice,
							{ status: 'error', isDismissible: false },
							__(
								'End time must be later than start time. Invalid windows fail closed and will not render.',
								'gutenberg-lab-blocks'
							)
					  )
					: null
			),
			createElement(
				PluginDocumentSettingPanel,
				{
					name: 'gutenberg-lab-site-message-targeting',
					title: __( 'Targeting', 'gutenberg-lab-blocks' ),
				},
				createElement( SelectControl, {
					label: __( 'Visibility', 'gutenberg-lab-blocks' ),
					value: visibilityScope,
					options: VISIBILITY_CHOICES,
					onChange: ( value ) => nextMeta( 'visibility_scope', value ),
				} ),
				visibilityScope === 'targeted'
					? createElement(
							Fragment,
							null,
							createElement( ComboboxControl, {
								label: __( 'Add Target Content', 'gutenberg-lab-blocks' ),
								value: '',
								onFilterValueChange: setSearchTerm,
								onChange: ( value ) => {
									if ( ! value ) {
										return;
									}

									const matchedResult = searchResults.find(
										( item ) => item.value === value
									);

									if ( ! matchedResult ) {
										return;
									}

									const newIds = Array.from(
										new Set( [ ...targetIds, matchedResult.id ] )
									);

									nextMeta( 'target_object_ids', newIds );
									setSearchTerm( '' );
									setSearchResults( [] );
								},
								options: searchOptions,
								help: isSearching
									? __( 'Searching…', 'gutenberg-lab-blocks' )
									: __(
											'Search Pages, Posts, or Packages. Targeted messages only render on singular views in v1.',
											'gutenberg-lab-blocks'
									  ),
							} ),
							targetIds.length > 0
								? createElement(
										'div',
										{ className: 'gutenberg-lab-site-message-targets' },
										targetIds.map( ( targetId ) =>
											createElement(
												PanelRow,
												{ key: targetId },
												createElement(
													'span',
													null,
													selectedItemMap.get( targetId ) ||
														sprintf(
															/* translators: %d missing site content ID. */
															__( 'Missing content #%d', 'gutenberg-lab-blocks' ),
															targetId
														)
												),
												createElement(
													Button,
													{
														isDestructive: true,
														variant: 'tertiary',
														onClick: () =>
															nextMeta(
																'target_object_ids',
																targetIds.filter( ( id ) => id !== targetId )
															),
													},
													__( 'Remove', 'gutenberg-lab-blocks' )
												)
											)
										)
								  )
								: null,
							targetIds.length === 0
								? createElement(
										Notice,
										{ status: 'warning', isDismissible: false },
										__(
											'Targeted visibility is selected, but no content targets are set yet.',
											'gutenberg-lab-blocks'
										)
								  )
								: null,
							missingTargetIds.length > 0
								? createElement(
										Notice,
										{ status: 'warning', isDismissible: false },
										__(
											'One or more targeted content items no longer exist. Those targets will fail closed.',
											'gutenberg-lab-blocks'
										)
								  )
								: null
					  )
					: null
			),
			createElement(
				PluginDocumentSettingPanel,
				{
					name: 'gutenberg-lab-site-message-dismissal',
					title: __( 'Dismissal', 'gutenberg-lab-blocks' ),
				},
				createElement( SelectControl, {
					label: __( 'Dismissal Expiry', 'gutenberg-lab-blocks' ),
					value: dismissalExpiry,
					options: DISMISSAL_CHOICES,
					onChange: ( value ) => nextMeta( 'dismissal_expiry', value ),
				} )
			),
			shouldWarnEmptyContent
				? createElement(
						PluginDocumentSettingPanel,
						{
							name: 'gutenberg-lab-site-message-warnings',
							title: __( 'Warnings', 'gutenberg-lab-blocks' ),
							initialOpen: true,
						},
						createElement(
							Notice,
							{ status: 'warning', isDismissible: false },
							__(
								'This sitewide published message has no visible content. Empty messages will fail closed and not render.',
								'gutenberg-lab-blocks'
							)
						)
				  )
				: null
		);
	}

	registerPlugin( 'gutenberg-lab-site-message-settings', {
		render: SiteMessagePanels,
		icon: null,
	} );
} )( window.wp, window.gutenbergLabSiteMessageSettings );
