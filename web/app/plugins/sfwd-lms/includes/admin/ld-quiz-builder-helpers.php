<?php
/**
 * Quiz Builder Helpers.
 *
 * Used to provide proper data to Quiz Builder app.
 *
 * @since 3.0.0
 * @package LearnDash\Builder
 */

namespace LearnDash\Admin\QuizBuilderHelpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gets the quiz data for the quiz builder.
 *
 * @since 3.0.0
 *
 * @param array $data The data passed down to the front-end.
 *
 * @return array The data passed down to the front-end.
 */
function get_quiz_data( $data ) {
	global $pagenow, $typenow;

	$output_questions = array();

	if ( ( 'post.php' === $pagenow ) && ( learndash_get_post_type_slug( 'quiz' ) === $typenow ) ) {
		$quiz_id = get_the_ID();
		if ( ! empty( $quiz_id ) ) {
			// Get quiz's questions.
			$questions_ids = learndash_get_quiz_questions( $quiz_id );

			if ( ! empty( $questions_ids ) ) {
				foreach ( $questions_ids as $question_id => $question_pro_id ) {
					$question_id     = absint( $question_id );
					$question_pro_id = absint( $question_pro_id );

					$question_post = get_post( $question_id );
					if ( ( ! $question_post ) || ( ! is_a( $question_post, 'WP_Post' ) ) || ( learndash_get_post_type_slug( 'question' ) !== $question_post->post_type ) ) {
						continue;
					}

					// Get answers from question.
					$question_mapper = new \WpProQuiz_Model_QuestionMapper();

					if ( ! empty( $question_pro_id ) ) {
						$question_model = $question_mapper->fetch( $question_pro_id );
					} else {
						$question_model = $question_mapper->fetch( null );
					}

					if ( ( empty( $question_model->getId() ) ) || ( $question_model->getId() !== $question_pro_id ) ) {
						continue;
					}

					$question_data       = $question_model->get_object_as_array();
					$controller_question = new \WpProQuiz_Controller_Question();

					if ( $question_model && is_a( $question_model, 'WpProQuiz_Model_Question' ) ) {
						$answers_data = $controller_question->setAnswerObject( $question_model );
					} else {
						$answers_data = $controller_question->setAnswerObject();
					}

					// Store answers in our format used at FE.
					$processed_answers = array();

					foreach ( $answers_data as $answer_type => $answers ) {
						foreach ( $answers as $answer ) {
							$processed_answers[ $answer_type ][] = array(
								'answer'             => $answer->getAnswer(),
								'html'               => $answer->isHtml(),
								'points'             => $answer->getPoints(),
								'correct'            => $answer->isCorrect(),
								'sortString'         => $answer->getSortString(),
								'sortStringHtml'     => $answer->isSortStringHtml(),
								'graded'             => $answer->isGraded(),
								'gradingProgression' => $answer->getGradingProgression(),
								'gradedType'         => $answer->getGradedType(),
								'type'               => 'answer',
							);
						}
					}

					// Output question's data and answers.
					$output_questions[] = array(
						'ID'              => $question_id,
						'expanded'        => false,
						'post_title'      => $question_data['_title'],
						'post_status'     => learndash_get_step_post_status_slug( $question_post ),
						'post_content'    => $question_data['_question'],
						'edit_link'       => get_edit_post_link( $question_id, '' ),
						'type'            => $question_post->post_type,
						'question_type'   => $question_data['_answerType'],
						'points'          => $question_data['_points'],
						'answers'         => $processed_answers,
						'correctMsg'      => $question_data['_correctMsg'],
						'incorrectMsg'    => $question_data['_incorrectMsg'],
						'correctSameText' => $question_data['_correctSameText'],
						'tipEnabled'      => $question_data['_tipEnabled'],
						'tipMsg'          => $question_data['_tipMsg'],
					);
				}
			}
		}
	}

	// Output all the quiz's questions.
	$data['outline'] = array(
		'questions' => $output_questions,
	);

	$data['post_statuses'] = learndash_get_step_post_statuses();

	// Add labels and data to Quiz Builder at FE.
	$data['labels']['questions_types']             = $GLOBALS['learndash_question_types'];
	$data['questions_types_map']                   = array(
		'single'             => 'classic_answer',
		'multiple'           => 'classic_answer',
		'sort_answer'        => 'sort_answer',
		'matrix_sort_answer' => 'matrix_sort_answer',
		'cloze_answer'       => 'cloze_answer',
		'free_answer'        => 'free_answer',
		'assessment_answer'  => 'assessment_answer',
		'essay'              => 'essay',
	);
	$data['labels']['points']                      = array(
		'singular' => esc_html__( 'point', 'learndash' ),
		'plural'   => esc_html__( 'points', 'learndash' ),
	);
	$data['labels']['questions_types_description'] = array(
		'free_answer'       => wp_kses_post( _x( 'Correct answers (one per line) (answers will be converted to lower case). If mode "Different points for each answer" is activated, you can assign points to each answer using "|". Example: One|15. The default point value is 1.', 'Question type description for Free Answers', 'learndash' ) ),
		'sort_answer'       => esc_html_x( 'Please sort the answers in the right order with the "move" button. The answers will be displayed randomly.', 'Question type description for Sort Answers', 'learndash' ),
		'cloze_answer'      => array(
			wp_kses_post( __( 'Use <strong class="description-red">{ }</strong> to mark a gap and correct answer:<br /> <strong>I <span class="description-red">{</span>play<span class="description-red">}</span> soccer.</strong>', 'learndash' ) ),
			wp_kses_post( __( 'Use <strong class="description-red">[ ]</strong> to mark multiple correct answers:<br /> <strong>I {<span class="description-red">[</span>love<span class="description-red">][</span>hate<span class="description-red">]</span>} soccer.</strong>', 'learndash' ) ),
			wp_kses_post( __( ' If mode "Different points for each answer" is activated:<br /> <strong><span class="description-red">{</span>play|2<span class="description-red">}</span></strong> - 2 points earned.<br /> <strong><span class="description-red">{</span><span class="description-red">[</span>love|2<span class="description-red">]</span><span class="description-red">[</span>hate|5<span class="description-red">]</span><span class="description-red">}</span></strong> - 2 or 5 points earned.', 'learndash' ) ),
		),
		'essay'             => array(
			esc_html__( 'How should the user submit their answer?', 'learndash' ),
			sprintf(
				// translators: placeholders: question, course.
				esc_html_x( 'This is a %1$s that can be graded and potentially prevent a user from progressing to the next step of the %2$s.', 'placeholders: question, course', 'learndash' ),
				\learndash_get_custom_label_lower( 'question' ),
				\learndash_get_custom_label_lower( 'course' )
			),
			esc_html__( 'The user can only progress if the essay is marked as "Graded" and if the user has enough points to move on.', 'learndash' ),
			sprintf(
				// translators: placeholders: question, quiz.
				esc_html_x( 'How should the answer to this %1$s be marked and graded upon %2$s submission?', 'placeholders: question, quiz', 'learndash' ),
				\learndash_get_custom_label_lower( 'question' ),
				\learndash_get_custom_label_lower( 'quiz' )
			),
		),
		'assessment_answer' => array(
			wp_kses_post( __( 'Use <strong class="description-red">{ }</strong> to mark an assessment:<br /> <strong>Less true <span class="description-red">{</span> [1] [2] [3] [4] [5] <span class="description-red">}</span> More true</strong>', 'learndash' ) ),
			wp_kses_post( __( 'Use <strong class="description-red">[ ]</strong> to mark selectable items:<br /> <strong>Less true { <span class="description-red">[</span>A<span class="description-red">]</span> <span class="description-red">[</span>B<span class="description-red">]</span> <span class="description-red">[</span>C<span class="description-red">]</span> } More true</strong>', 'learndash' ) ),
		),
	);

	return $data;
}
