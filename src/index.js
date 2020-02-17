import { subscribe, select, dispatch } from '@wordpress/data';
import { debounce } from 'lodash';

const {
	EDITOR_BLOCKS_META_FIELD,
	EDITOR_BLOCKS_META_TYPE,
} = window.BLOCKS_TO_REST;

/**
 * Adds block data to post meta.
 */
const undebouncedAddBlocksToMetaOnPostSave = () => {
	const {
		isPostLocked,
		getEditedPostAttribute,
		getEditorBlocks,
		hasChangedContent,
	} = select( 'core/editor' );

	if ( ! isPostLocked() && hasChangedContent() ) {
		dispatch( 'core/editor' ).editPost( {
			meta: {
				...getEditedPostAttribute( 'meta' ),
				[ EDITOR_BLOCKS_META_FIELD ]:
					'array' === EDITOR_BLOCKS_META_TYPE
						? getEditorBlocks()
						: JSON.stringify( getEditorBlocks() ),
			},
		} );
	}
};

// Maximum call stack will be exceeded if the callback is not debounced.
const addBlocksToMetaOnPostSave = debounce(
	undebouncedAddBlocksToMetaOnPostSave,
	1000
);

subscribe( addBlocksToMetaOnPostSave );
