<?php
/**
 * Let content staff edit media details.
 *
 * WordPress maps editing an attachment to edit_posts when the user uploaded it
 * and to edit_others_posts when someone else did. Content Editors and Reviewers
 * can upload files but have neither capability, so they can't set the title,
 * alt text, caption or description of any image, including their own uploads.
 *
 * This lets anyone who can edit LearnDash courses (edit_courses) edit any
 * attachment's details. Deleting attachments keeps core's rules.
 *
 * @package CBF
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'map_meta_cap',
	static function ( array $caps, string $cap, int $user_id, array $args ): array {
		if ( $cap !== 'edit_post' || empty( $args[0] ) || ! $user_id ) {
			return $caps;
		}

		$post = get_post( $args[0] );

		if ( ! $post || $post->post_type !== 'attachment' || $post->post_status === 'trash' ) {
			return $caps;
		}

		if ( ! user_can( $user_id, 'edit_courses' ) || user_can( $user_id, 'edit_posts' ) ) {
			return $caps;
		}

		return array( 'edit_courses' );
	},
	10,
	4
);
