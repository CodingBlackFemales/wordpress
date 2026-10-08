<?php
/**
 * AJAX handlers.
 *
 * @since 5.2.0
 *
 * @package LearnDash\Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * AJAX handler for 'ld_notifications_get_posts_list' action
 *
 * @return void
 */
function learndash_notifications_ajax_get_posts_list() {
	if ( ! wp_verify_nonce( $_POST['nonce'], 'ld_notifications_nonce' ) ) {
		wp_die();
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die();
	}

	// By default WP_Query search all post title, content, and excerpt.
	// This filter modify it to only search in post title.
	add_filter(
		'posts_search',
		function ( $search, $wp_query ) {
			if ( isset( $wp_query->query['ld_notifications_action'] ) && $wp_query->query['ld_notifications_action'] === 'ld_notifications_get_posts_list' ) {
				$search = preg_replace( '/(OR)\s.*?post_(excerpt|content)\sLIKE\s.*?\)/', '', $search );
			}

			return $search;
		},
		10,
		2
	);

	$posts      = [];
	$posts_args = [];

	foreach ( $_POST as $key => $value ) {
		switch ( $key ) {
			case 'group_id':
			case 'course_id':
			case 'lesson_id':
			case 'topic_id':
			case 'quiz_id':
			case 'parent_id':
				if ( is_array( $value ) ) {
					array_walk_recursive(
						$value,
						function ( &$v ) {
							$v = sanitize_text_field( $v );
						}
					);
				} else {
					$value = sanitize_text_field( $value );
					$value = [ $value ];
				}

				// Use key name as variable name.
				$$key = $value;
				break;

			default:
				// Use key name as variable name.
				$$key = sanitize_text_field( $value );
				break;
		}
	}

	$posts_per_page = 10;
	$paged          = ! empty( $page ) ? absint( $page ) : 1;

	switch ( $post_type ) {
		case 'groups':
			$label = LearnDash_Custom_Label::get_label( 'group' );
			break;

		case 'sfwd-courses':
			$label = LearnDash_Custom_Label::get_label( 'course' );
			break;

		case 'sfwd-lessons':
			$label = LearnDash_Custom_Label::get_label( 'lesson' );
			break;

		case 'sfwd-topic':
			$label = LearnDash_Custom_Label::get_label( 'topic' );
			break;

		case 'sfwd-quiz':
			$label = LearnDash_Custom_Label::get_label( 'quiz' );

			if ( in_array( 'all', $parent_id, true ) && ( ! in_array( 'all', $lesson_id, true ) && ! empty( $lesson_id ) ) ) {
				$parent_id = $lesson_id;
			} elseif ( in_array( 'all', $parent_id, true ) && ( ! in_array( 'all', $course_id, true ) && ! empty( $course_id ) ) ) {
				$parent_id = $course_id;
			}
			break;
	}

	if ( ! empty( $post_type ) ) {
		$posts_args = [
			'post_type'               => $post_type,
			's'                       => $keyword ?? null,
			'posts_per_page'          => $posts_per_page,
			'paged'                   => $paged,
			'post_status'             => 'any',
			'orderby'                 => 'relevance',
			'order'                   => 'ASC',
			'suppress_filters'        => false,
			'ld_notifications_action' => 'ld_notifications_get_posts_list',
		];

		$post_ids = [];

		if (
			isset( $parent_id )
			&& is_array( $parent_id )
			&& isset( $course_id )
			&& is_array( $course_id )
		) {
			foreach ( $parent_id as $p_id ) {
				if ( $p_id === 'all' ) {
					continue;
				}

				foreach ( $course_id as $c_id ) {
					if (
						$c_id === 'all'
						&& (
							! empty( $parent_id )
							&& in_array( 'all', $parent_id, true )
						)
					) {
						$post_ids = [];
						break 2;
					}

					if ( intval( $p_id ) === intval( $c_id ) ) {
						$post_ids = array_merge( learndash_course_get_steps_by_type( $c_id, $post_type ), $post_ids );
					} else {
						$post_ids = array_merge( learndash_course_get_children_of_step( $c_id, $p_id, $post_type ), $post_ids );
					}
				}
			}
		}

		if ( ! empty( $post_ids ) ) {
			$posts_args['post__in'] = $post_ids;
		}
	}

	if ( ! empty( $posts_args ) ) {
		$query = new WP_Query( $posts_args );
		$posts = $query->get_posts();
	}

	$results = [];

	if ( $paged === 1 ) {
		$results[] = [
			'id'   => 'all',
			// translators: %s: Post type label.
			'text' => sprintf( _x( 'Any %s', 'Post type label', 'learndash' ), $label ),
		];
	}

	foreach ( $posts as $post ) {
		if ( is_numeric( $post ) ) {
			$post = get_post( $post );
		}

		$results[] = [
			'id'   => $post->ID,
			'text' => $post->post_title . '  (ID: ' . $post->ID . ')',
		];
	}

	$response = [
		'results'    => $results,
		'pagination' => ! empty( $query->max_num_pages ) && $paged < $query->max_num_pages,
	];

	echo wp_json_encode( $response );
	wp_die();
}

add_action( 'wp_ajax_ld_notifications_get_posts_list', 'learndash_notifications_ajax_get_posts_list' );
