<?php
/**
 * Plugin Name: Blocks to Rest
 * Description: Sends structured Gutenberg block data to REST.
 * Version: 1.0
 *
 * @package blocks-to-rest
 */

namespace JohnWatkins0\BlocksToRest;

const EDITOR_BLOCKS_META_FIELD = 'editor_blocks';

add_action( 'init', __NAMESPACE__ . '\add_editor_blocks_post_meta', 99 );
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
	wp_enqueue_script(
		'blocks-to-rest',
		trailingslashit( plugin_dir_url( __FILE__ ) ) . 'dist/main.js',
		[ 'lodash', 'wp-data' ],
		filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'dist/main.js' ),
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
function add_editor_blocks_post_meta() {
	if ( ! function_exists( 'use_block_editor_for_post_type' ) ) {
		require ABSPATH . 'wp-admin/includes/post.php';
	}

	foreach ( array_filter( get_post_types_by_support( 'editor' ), 'use_block_editor_for_post_type' ) as $post_type ) {
		if ( ! this_wordpress_supports_nonscalar_post_meta() ) {
			register_post_meta(
				$post_type,
				EDITOR_BLOCKS_META_FIELD,
				[
					'show_in_rest' => true,
					'single'       => true,
					'type'         => 'string',
				]
			);

			// Convert the field to an array in the REST response.
			add_filter(
				sprintf( 'rest_prepare_%s', $post_type ),
				function( $response ) {
					$meta = $response->data['meta'] ?? [];

					if ( isset( $meta[ EDITOR_BLOCKS_META_FIELD ] ) && is_string( $meta[ EDITOR_BLOCKS_META_FIELD ] ) ) {
						$response->data['meta'][ EDITOR_BLOCKS_META_FIELD ] = json_decode(
							$response->data['meta'][ EDITOR_BLOCKS_META_FIELD ]
						);
					}

					return $response;
				}
			);
		} else {
			register_post_meta(
				$post_type,
				EDITOR_BLOCKS_META_FIELD,
				[
					'single'       => true,
					'show_in_rest' => [ // Array type support introduced in WP 5.3.
						'schema' => [
							'type'  => 'array',
							'items' => [
								'type'                 => 'object',
								'properties'           => [
									'clientId' => [
										'type' => 'string',
									],
								],
								'additionalProperties' => true, // Equates to "any" for unspecified properties.
							],
						],
					],
					'type'         => 'array',
				]
			);
		}
	}
}
