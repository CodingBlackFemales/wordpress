<?php
/**
 * Legacy Course Functions
 *
 * Functions included here are considered legacy and are no longer used and
 * will soon be deprecated.
 *
 * @since 3.4.0
 * @package LearnDash\Course
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gets the lesson list for a course.
 *
 * Important: This function is not recommended. Use `Course::get_lessons` instead.
 *
 * @global wpdb    $wpdb WordPress database abstraction object.
 * @global WP_Post $post Global post object.
 *
 * @since 2.1.0
 *
 * @param int|null $id   The ID of the resource.
 * @param array    $atts An array of lesson arguments.
 *
 * @return array|string Returns Lesson list output or empty array.
 */
function learndash_get_lesson_list( $id = null, $atts = array() ) {
	global $post;

	if ( empty( $id ) ) {
		if ( $post instanceof WP_Post ) {
			$id = $post->ID;
		}
	}

	$course_id = learndash_get_course_id( $id );

	if ( empty( $course_id ) ) {
		return array();
	}

	global $wpdb;

	$lessons             = sfwd_lms_get_post_options( 'sfwd-lessons' );
	$course_lessons_args = learndash_get_course_lessons_order( $course_id );
	$orderby             = ( isset( $course_lessons_args['orderby'] ) ) ? $course_lessons_args['orderby'] : 'title';
	$order               = ( isset( $course_lessons_args['order'] ) ) ? $course_lessons_args['order'] : 'ASC';

	switch ( $orderby ) {
		case 'title':
			$orderby = 'title';
			break;
		case 'date':
			$orderby = 'date';
			break;
	}

	$lessons_args = array(
		'array'      => true,
		'course_id'  => $course_id,
		'post_type'  => 'sfwd-lessons',
		'meta_key'   => 'course_id',
		'meta_value' => $course_id,
		'orderby'    => $orderby,
		'order'      => $order,
	);

	$lessons_args = array_merge( $lessons_args, $atts );

	if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
		$ld_course_steps_object = LDLMS_Factory_Post::course_steps( $course_id );
		$ld_course_steps_object->load_steps();
		$course_steps = $ld_course_steps_object->get_steps( 't' );

		if ( ( isset( $course_steps[ $lessons_args['post_type'] ] ) ) && ( ! empty( $course_steps[ $lessons_args['post_type'] ] ) ) ) {
			$lessons_args['post__in'] = $course_steps[ $lessons_args['post_type'] ];
			$lessons_args['orderby']  = 'post__in';

			unset( $lessons_args['order'] );
			unset( $lessons_args['meta_key'] );
			unset( $lessons_args['meta_value'] );
		} else {
			return array();
		}
	}

	/**
	 * Filters query arguments for getting the lesson list.
	 *
	 * @since 2.5.7
	 *
	 * @param array $lesson_args An array of arguments for getting lesson list.
	 * @param int   $id          ID of resource.
	 * @param int   $course_id   Course ID.
	 */
	$lessons_args = apply_filters( 'learndash_get_lesson_list_args', $lessons_args, $id, $course_id );
	if ( ! empty( $lessons_args ) ) {
		return ld_lesson_list( $lessons_args );
	}

	return array();
}
