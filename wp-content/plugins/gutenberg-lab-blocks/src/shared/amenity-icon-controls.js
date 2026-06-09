import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';

import IconSizeControl, {
	getIconSizeStyle,
	normalizeIconSize,
} from './icon-size-control';

const iconSettings = window.gutenbergLabBlocksVillaAmenityIcons || {};

const ICON_OPTIONS = [
	{ value: '', label: __( 'No icon', 'gutenberg-lab-blocks' ) },
	...( iconSettings.choices || [] ),
];

export function getAmenityIconMarkup( iconSlug ) {
	return iconSettings.icons?.[ iconSlug ] || '';
}

export const getAmenityIconSizeStyle = getIconSizeStyle;
export const normalizeAmenityIconSize = normalizeIconSize;

export default function AmenityIconControls( {
	defaultSize,
	help = __(
		'Uses the same icon choices as villa amenity terms.',
		'gutenberg-lab-blocks'
	),
	iconAttribute = 'iconSlug',
	iconSize,
	iconSlug,
	label = __( 'Icon', 'gutenberg-lab-blocks' ),
	max = 12,
	min = 1,
	setAttributes,
	sizeAttribute = 'iconSize',
	sizeLabel = __( 'Icon size', 'gutenberg-lab-blocks' ),
	step = 0.25,
} ) {
	const hasIcon = Boolean( iconSlug );

	return (
		<>
			<SelectControl
				label={ label }
				value={ iconSlug }
				options={ ICON_OPTIONS }
				help={ help }
				onChange={ ( nextIconSlug ) =>
					setAttributes( {
						[ iconAttribute ]: nextIconSlug,
						...( nextIconSlug ? {} : { [ sizeAttribute ]: 0 } ),
					} )
				}
			/>
			{ hasIcon ? (
				<IconSizeControl
					defaultSize={ defaultSize }
					iconSize={ iconSize }
					max={ max }
					min={ min }
					onChange={ ( nextIconSize ) =>
						setAttributes( { [ sizeAttribute ]: nextIconSize } )
					}
					onReset={ () => setAttributes( { [ sizeAttribute ]: 0 } ) }
					sizeLabel={ sizeLabel }
					step={ step }
				/>
			) : null }
		</>
	);
}
