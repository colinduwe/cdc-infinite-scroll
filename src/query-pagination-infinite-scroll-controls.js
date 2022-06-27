/**
 * WordPress dependencies
 */
import { __, _x } from '@wordpress/i18n';
import {
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

export function InfiniteScrollControls( { value, onChange } ) {
	return (
		<ToggleGroupControl
			label={ __( 'Loading Type' ) }
			value={ value }
			onChange={ onChange }
			help={ __(
				'How should more items be added to the page'
			) }
			isBlock
		>
			<ToggleGroupControlOption
				value="scroll"
				label={ _x(
					'Scroll',
					'Load type option for infinite scroll blocks'
				) }
			/>
			<ToggleGroupControlOption
				value="loadmore"
				label={ _x(
					'Load More',
					'Load type option for infinite scroll blocks'
				) }
			/>
		</ToggleGroupControl>
	);
}