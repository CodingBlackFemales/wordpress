<?php
/**
 * Quiz Metaboxes.
 *
 * Introduces metaboxes at Add/Edit Quiz page to be used as
 * a wrapper by the React application at front-end.
 *
 * @since 3.0.0
 * @package LearnDash
 */

namespace LearnDash\Quiz\Metaboxes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds the metaboxes for quiz post type.
 *
 * Fires on `add_meta_boxes_sfwd-quiz` and `learndash_add_meta_boxes` hook.
 *
 * @since 3.0.0
 */
function add_meta_boxes() {

	$screen = get_current_screen();

	if ( 'sfwd-quiz' !== get_post_type( get_the_ID() ) &&
		'sfwd-quiz_page_quizzes-builder' !== $screen->id ) {
		return;
	}

	add_meta_box(
		'sfwd-quiz-questions',
		sprintf( '%s', \LearnDash_Custom_Label::get_label( 'questions' ) ),
		'LearnDash\Quiz\Metaboxes\meta_box_questions_callback',
		null,
		'side'
	);
}
add_action( 'add_meta_boxes_sfwd-quiz', 'LearnDash\Quiz\Metaboxes\add_meta_boxes' );
add_action( 'learndash_add_meta_boxes', 'LearnDash\Quiz\Metaboxes\add_meta_boxes' );

/**
 * Prints the output for quiz navigation meta box.
 *
 * @since 3.0.0
 */
function meta_box_questions_callback() {
	?>
	<div id="sfwd-questions-app"></div>
	<?php
}
