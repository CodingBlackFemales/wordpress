<?php
/**
 * Posts API Controller.
 *
 * @since 4.10.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\API\Controllers;

use WP_Error;
use WP_Post_Type;
use WP_REST_Posts_Controller;
use WP_REST_Request;

/**
 * Posts API Controller.
 *
 * @since 4.10.2
 */
class Posts extends WP_REST_Posts_Controller {
	/**
	 * Checks if a given request has access to read a post.
	 * We override this method to implement our own permissions check.
	 *
	 * @since 4.10.3
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error True if the request has read access for the item, WP_Error object or false otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		$default_check = parent::get_item_permissions_check( $request );

		// If the default check is a WP_Error, return the default check.
		if ( $default_check instanceof WP_Error ) {
			return $default_check;
		}

		// Check if the user has permission to edit the post (LD restriction).

		$post = $this->get_post( $request['id'] );

		if ( is_wp_error( $post ) ) {
			return new WP_Error(
				'learndash_rest_forbidden_context',
				__( 'Sorry, you are not allowed to access this resource.', 'learndash' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		$post_type_object = get_post_type_object( $post->post_type );

		if (
			! $post_type_object instanceof WP_Post_Type
			|| ! current_user_can( $post_type_object->cap->edit_post, $post->ID )
		) {
			return new WP_Error(
				'learndash_rest_forbidden_context',
				__( 'Sorry, you are not allowed to access this resource.', 'learndash' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return $default_check;
	}

	/**
	 * Checks if a given request has access to read posts.
	 * We override this method to implement our own permissions check.
	 *
	 * @since 4.10.3
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		$default_check = parent::get_items_permissions_check( $request );

		// If the default check is a WP_Error, return the default check.
		if ( $default_check instanceof WP_Error ) {
			return $default_check;
		}

		// Check if the user has permission to edit posts (LD restriction).

		$post_type_object = get_post_type_object( $this->post_type );

		if (
			! $post_type_object instanceof WP_Post_Type
			|| ! current_user_can( $post_type_object->cap->edit_posts )
		) {
			return new WP_Error(
				'learndash_rest_forbidden_context',
				__( 'Sorry, you are not allowed to access this resource.', 'learndash' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return $default_check;
	}
}
