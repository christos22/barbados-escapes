import { __, sprintf } from '@wordpress/i18n';
import { Button, RangeControl } from '@wordpress/components';

const DEFAULT_MIN_SIZE = 1;
const DEFAULT_MAX_SIZE = 12;
const DEFAULT_SIZE_STEP = 0.25;

export function normalizeIconSize(
	value,
	defaultValue = 0,
	{ min = DEFAULT_MIN_SIZE, max = DEFAULT_MAX_SIZE } = {}
) {
	const numericValue = Number.parseFloat( value );

	if ( Number.isNaN( numericValue ) || numericValue <= 0 ) {
		return defaultValue;
	}

	return Math.min( max, Math.max( min, numericValue ) );
}

export function getIconSizeStyle( iconSize, cssVariableName ) {
	const normalizedSize = normalizeIconSize( iconSize );

	if ( ! normalizedSize || ! cssVariableName ) {
		return undefined;
	}

	return {
		[ cssVariableName ]: `${ normalizedSize }rem`,
	};
}

export default function IconSizeControl( {
	defaultSize,
	iconSize,
	max = DEFAULT_MAX_SIZE,
	min = DEFAULT_MIN_SIZE,
	onChange,
	onReset,
	sizeLabel = __( 'Icon size', 'gutenberg-lab-blocks' ),
	step = DEFAULT_SIZE_STEP,
} ) {
	const customSize = normalizeIconSize( iconSize, 0, { min, max } );
	const rangeValue = normalizeIconSize( iconSize, defaultSize, {
		min,
		max,
	} );

	return (
		<>
			<RangeControl
				label={ sizeLabel }
				value={ rangeValue }
				min={ min }
				max={ max }
				step={ step }
				help={ sprintf(
					/* translators: %s: default icon size in rem. */
					__(
						'Default for this block is %srem. Reset to use the responsive theme default.',
						'gutenberg-lab-blocks'
					),
					defaultSize
				) }
				onChange={ ( nextIconSize ) =>
					onChange(
						normalizeIconSize( nextIconSize, defaultSize, {
							min,
							max,
						} )
					)
				}
			/>
			{ customSize ? (
				<Button variant="link" onClick={ onReset }>
					{ __( 'Reset icon size', 'gutenberg-lab-blocks' ) }
				</Button>
			) : null }
		</>
	);
}
