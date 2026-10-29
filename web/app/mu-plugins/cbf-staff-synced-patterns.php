<?php
/**
 * Let content staff view synced patterns in the editor.
 *
 * WordPress maps reading a synced pattern (wp_block) to edit_posts, and the
 * block editor fetches each pattern with context=edit, which needs edit_post on
 * the pattern itself. Content Editors and Reviewers have neither, so every
 * synced pattern in a lesson shows "Block has been deleted or is unavailable"
 * and the inserter lists none.
 *
 * This lets anyone who can edit LearnDash courses (edit_courses) read published
 * patterns, and serves their pattern requests in the view context, which still
 * includes the raw markup the editor needs. They can't edit a pattern, so the
 * editor hides "Edit original": patterns change in their source files and are
 * synced with `wp cbf patterns sync`.
 *
 * @package CBF
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether a user is content staff without the core capability to read patterns.
 *
 * @param int $user_id User ID.
 * @return bool
 */
function cbf_staff_synced_patterns_applies( int $user_id ): bool {
	return $user_id && user_can( $user_id, 'edit_courses' ) && ! user_can( $user_id, 'edit_posts' );
}

add_filter(
	'map_meta_cap',
	static function ( array $caps, string $cap, int $user_id, array $args ): array {
		if ( $cap !== 'read_post' || empty( $args[0] ) || ! cbf_staff_synced_patterns_applies( $user_id ) ) {
			return $caps;
		}

		$post = get_post( $args[0] );

		if ( ! $post || $post->post_type !== 'wp_block' || $post->post_status !== 'publish' ) {
			return $caps;
		}

		return array( 'edit_courses' );
	},
	10,
	4
);

add_filter(
	'rest_request_before_callbacks',
	static function ( $response, $handler, WP_REST_Request $request ) {
		if (
			$request->get_method() !== 'GET'
			|| $request['context'] !== 'edit'
			|| ! preg_match( '#^/wp/v2/blocks(?:/\d+)?$#', $request->get_route() )
			|| ! cbf_staff_synced_patterns_applies( get_current_user_id() )
		) {
			return $response;
		}

		$request->set_param( 'context', 'view' );

		return $response;
	},
	10,
	3
);
