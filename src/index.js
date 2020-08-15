import domReady from '@wordpress/dom-ready';
import { useEffect, render, useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { registerBlockType, createBlock } from '@wordpress/blocks';

/**
 * Saves block data.
 */
function Edit( { setAttributes } ) {
	const { isPostLocked, hasChangedContent } = useSelect( ( select ) => ( {
		isPostLocked: select( 'core/editor' ).isPostLocked(),
		hasChangedContent: select( 'core/editor' ).hasChangedContent(),
	} ) );

	const { editorBlocks, stringifiedEditorBlocks } = useSelect( ( select ) => {
		if ( isPostLocked || ! hasChangedContent ) {
			return { stringifiedEditorBlocks: '' };
		}

		const currentBlocks = select( 'core/editor' )
			.getEditorBlocks()
			.filter( ( { name } ) => name !== 'tmobile/blocks-to-rest' )
			.map( ( { clientId, name, attributes, innerBlocks } ) => ( {
				clientId,
				name,
				attributes,
				innerBlocks,
			} ) );
		return {
			editorBlocks: currentBlocks,
			stringifiedEditorBlocks: JSON.stringify( currentBlocks ),
		};
	} );

	useEffect( () => {
		if ( stringifiedEditorBlocks ) {
			console.log( 'updating' );
			setAttributes( {
				blocks: editorBlocks,
			} );
		}
	}, [ stringifiedEditorBlocks ] );

	return (
		<style>
			{ '[data-type="tmobile/blocks-to-rest"] { display: none; }' }
		</style>
	);
}

registerBlockType( 'tmobile/blocks-to-rest', {
	attributes: {
		blocks: {
			type: 'array',
			default: [],
		},
	},
	category: 'layout',
	edit: Edit,
	inserter: false,
	save: ( { attributes } ) =>
		`EDITOR_BLOCKS${ JSON.stringify( attributes.blocks ) }/EDITOR_BLOCKS`,
	supports: {
		multiple: false,
	},
	title: 'Block Content',
} );

/**
 * Inserts the block into the editor if it is not there already.
 *
 * @param {Object} props
 */
function Inserter() {
	const [ inserted, setInserted ] = useState( false );
	const dispatch = useDispatch();

	const blocks = useSelect( ( select ) => {
		if ( inserted ) {
			return null;
		}

		return select( 'core/block-editor' ).getBlocks();
	} );

	useEffect( () => {
		if ( ! inserted && blocks && blocks.length ) {
			for ( const block of blocks ) {
				if ( block.name === 'tmobile/blocks-to-rest' ) {
					setInserted( true );
					return;
				}
			}

			dispatch( 'core/block-editor' ).insertBlock(
				createBlock( 'tmobile/blocks-to-rest' )
			);
			setInserted( true );
		}
	}, [ blocks ] );

	return null;
}

domReady( () => {
	const root = document.createElement( 'div' );
	document.body.appendChild( root );

	render( <Inserter />, root );
} );
