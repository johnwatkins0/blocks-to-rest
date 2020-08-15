<?php
/**
 * Plugin Name: Blocks to Rest
 * Description: Sends structured Gutenberg block data to REST.
 * Version: 1.0
 *
 * @package blocks-to-rest
 */

namespace JohnWatkins\BlocksToRest;

const EDITOR_BLOCKS_META_FIELD = 'editor_blocks';

add_action( 'init', __NAMESPACE__ . '\filter_post_rest_response', 99 );
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\enqueue_script' );

/**
 * Returns whether WP supports array schema types for post meta fields. Nonscalar types are introduced in WP 5.3.
 *
 * @see https://make.wordpress.org/core/2019/10/03/wp-5-3-supports-object-and-array-meta-types-in-the-rest-api/
 *
 * @return bool
 */
function this_wordpress_supports_nonscalar_post_meta() {
	global $wp_version;

	return 5.3 <= floatval( $wp_version );
}

/**
 * Enqueues block editor script.
 */
function enqueue_script() {
	$asset_data_file = trailingslashit( plugin_dir_path( __FILE__ ) ) . 'build/index.asset.php';

	if ( ! file_exists( $asset_data_file ) ) {
		return;
	}

	$script_data = include $asset_data_file;

	wp_enqueue_script(
		'blocks-to-rest',
		trailingslashit( plugin_dir_url( __FILE__ ) ) . 'build/index.js',
		$script_data['dependencies'],
		$script_data['version'],
		true
	);

	wp_localize_script(
		'blocks-to-rest',
		'BLOCKS_TO_REST',
		[
			'EDITOR_BLOCKS_META_FIELD' => EDITOR_BLOCKS_META_FIELD,
			'EDITOR_BLOCKS_META_TYPE'  => this_wordpress_supports_nonscalar_post_meta() ? 'array' : 'string',
		]
	);
}

/**
 * Registers the editor_blocks meta field.
 */
function filter_post_rest_response() {
	if ( ! function_exists( 'use_block_editor_for_post_type' ) ) {
		require ABSPATH . 'wp-admin/includes/post.php';
	}

	foreach ( array_filter( get_post_types_by_support( 'editor' ), 'use_block_editor_for_post_type' ) as $post_type ) {
		add_filter( sprintf( 'rest_prepare_%s', $post_type ), __NAMESPACE__ . '\\convert_content_to_array', 10, 3 );
	}
}

/**
 * Converts post contenti nto an array of blocks.
 *
 * @param WP_REST_Response $response
 * @param WP_Post $post
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function convert_content_to_array( $response, $post, $request ) {
	if ( 'edit' === $request['context'] ) {
		return $response;
	}

	$content = $post->post_content;
	if ( false !== strpos( $content, 'EDITOR_BLOCKS' ) ) {
		$content = explode( '/EDITOR_BLOCKS', $content )[0];
		$content = explode( 'EDITOR_BLOCKS', $content )[1];
		$response->data['content']['blocks'] = json_decode( trim( $content ) );
	} else {
		$editor_blocks = get_post_meta( $post->ID, 'editor_blocks', true );
		if ( is_array( $editor_blocks ) ) {
			$response->data['content']['blocks'] = $editor_blocks;
		}
	}
	
	return $response;
}
