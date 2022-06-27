/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { getBlockSupport } from '@wordpress/blocks';
import { PanelBody } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { InfiniteScrollControls } from './query-pagination-infinite-scroll-controls';

const TEMPLATE = [
	[ 'core/button' ]
];

const getDefaultBlockLayout = ( blockTypeOrName ) => {
	const layoutBlockSupportConfig = getBlockSupport(
		blockTypeOrName,
		'__experimentalLayout'
	);
	return layoutBlockSupportConfig?.default;
};

export default function QueryPaginationEdit( {
	attributes: { scrollType, layout },
	setAttributes,
	clientId,
	name,
} ) {
	const usedLayout = layout || getDefaultBlockLayout( name );
	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: TEMPLATE,
		allowedBlocks: [
			'core/button',
		],
		__experimentalLayout: usedLayout,
	} );
	return (
		<>
				<InspectorControls>
					<PanelBody title={ __( 'Settings' ) }>
						<InfiniteScrollControls
							value={ scrollType }
							onChange={ ( value ) => {
								setAttributes( { scrollType: value } );
							} }
						/>
					</PanelBody>
				</InspectorControls>
			<div { ...innerBlocksProps } />
		</>
	);
}