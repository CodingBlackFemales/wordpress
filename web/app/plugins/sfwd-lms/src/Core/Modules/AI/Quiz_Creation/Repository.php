<?php
/**
 * Quiz creation AI repository.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Quiz_Creation;

use LDLMS_Post_Types;
use LearnDash\Core\Modules\AI\Quiz_Creation;
use LearnDash\Core\Modules\AI\Quiz_Creation\DTO;

/**
 * Quiz creation AI Repository class.
 *
 * @since 4.8.0
 */
class Repository {
	/**
	 * Create a quiz.
	 *
	 * @since 4.8.0
	 *
	 * @throws \Exception Throw exception if quiz can't be created.
	 *
	 * @param DTO\Quiz $quiz Quiz arguments.
	 *
	 * @return int New Quiz ID.
	 */
	public function create_quiz( DTO\Quiz $quiz ): int {
		/**
		 * Define type.
		 *
		 * @var int|\WP_Error
		 */
		$quiz_id = wp_insert_post(
			[
				'post_title'  => $quiz->title,
				'post_type'   => learndash_get_post_type_slug( LDLMS_Post_Types::QUIZ ),
				'post_status' => 'publish',
			],
			true
		);

		if ( is_wp_error( $quiz_id ) ) {
			throw new \Exception( $quiz_id->get_error_message() );
		}

		if ( $quiz_id === 0 ) {
			throw new \Exception( 'Unknown error creating quiz: ' . $quiz->title );
		}

		$pro_quiz = new \WpProQuiz_Controller_Quiz();
		$pro_quiz->route(
			[
				'action'  => 'addUpdateQuiz',
				'quizId'  => 0, // New pro quiz.
				'post_id' => $quiz_id,
			],
			[
				'form'    => [],
				'post_ID' => $quiz_id,
			]
		);

		learndash_course_add_child_to_parent( $quiz->course_id, $quiz_id, $quiz->parent_id );

		return $quiz_id;
	}

	/**
	 * Create a question.
	 *
	 * @since 4.8.0
	 *
	 * @throws \Exception Throw exception if question can't be created.
	 *
	 * @param int          $quiz_id  Quiz WP_Post ID.
	 * @param DTO\Question $question Question arguments.
	 *
	 * @return int New question ID.
	 */
	public function create_question( int $quiz_id, DTO\Question $question ) {
		global $wpdb;

		$post_args = array(
			'action'       => 'new_step',
			'post_type'    => learndash_get_post_type_slug( LDLMS_Post_Types::QUESTION ),
			'post_status'  => 'publish',
			'post_title'   => $question->title,
			'post_content' => '',
		);

		/**
		 * Define type.
		 *
		 * @var int|\WP_Error
		 */
		$question_id = wp_insert_post( $post_args, true );

		if ( is_wp_error( $question_id ) ) {
			throw new \Exception( $question_id->get_error_message() );
		}

		if ( $question_id === 0 ) {
			throw new \Exception( 'Unknown error creating question: ' . $question->title );
		}

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->posts,
			array(
				'guid' => add_query_arg(
					array(
						'post_type' => learndash_get_post_type_slug( LDLMS_Post_Types::QUESTION ),
						'p'         => $question_id,
					),
					home_url()
				),
			),
			array( 'ID' => $question_id )
		);

		$question_pro_id = learndash_update_pro_question( 0, $post_args );

		if ( empty( $question_pro_id ) ) {
			throw new \Exception( 'Failed to fetch question pro ID after creating question: ' . $question->title );
		}

		$questions = get_post_meta( $quiz_id, 'ld_quiz_questions', true );
		$questions = is_array( $questions ) ? $questions : [];

		$questions[ $question_id ] = $question_pro_id;

		update_post_meta( $quiz_id, 'ld_quiz_questions', $questions );
		update_post_meta( $question_id, 'question_pro_id', absint( $question_pro_id ) );
		learndash_proquiz_sync_question_fields( $question_id, $question_pro_id );

		learndash_update_setting( $question_id, 'quiz', $quiz_id );
		update_post_meta( $question_id, 'quiz_id', $quiz_id );

		$question_mapper     = new \WpProQuiz_Model_QuestionMapper();
		$question_model      = $question_mapper->fetch( $question_pro_id );
		$question_pro_params = [
			'_answerData' => [],
			'_answerType' => $question->type,
		];

		$answer_text         = '';
		$sort_string         = '';
		$grading_progression = 'not-graded-none';

		$default_answer_data = [
			'_answer'             => $answer_text,
			'_correct'            => false,
			'_graded'             => '1',
			'_gradedType'         => 'text',
			'_gradingProgression' => $grading_progression,
			'_html'               => false,
			'_points'             => 1,
			'_sortString'         => $sort_string,
			'_sortStringHtml'     => false,
			'_type'               => 'answer',
		];

		foreach ( $question->answers as $answer_key => $answer ) {
			if ( $question->type === Quiz_Creation::$question_type_key_matrix_sorting_choice ) {
				$answer_text = $answer->params['criterion'];
				$sort_string = $answer->params['criterion_value'];
			} elseif ( $question->type === Quiz_Creation::$question_type_key_fill_in_the_blank ) {
				// Add blank line if there's no placeholder by default.
				$question_title = ! preg_match( '/_{2,}/', $question->title )
					? $question->title . ': ____'
					: $question->title;

				$answer_text = preg_replace( '/_{2,}/', '{' . $answer->title . '}', $question_title );
			} elseif ( $question->type === Quiz_Creation::$question_type_key_essay ) {
				$answer_text         = $answer->title;
				$grading_progression = '';
			} else {
				$answer_text = $answer->title;
			}

			$answer_data = wp_parse_args(
				[
					'_answer'             => $answer_text,
					'_correct'            => $answer->is_correct,
					'_gradingProgression' => $grading_progression,
					'_sortString'         => $sort_string,
				],
				$default_answer_data
			);

			$question_pro_params['_answerData'][] = $answer_data;
		}

		if ( ! empty( $question->title ) ) {
			$question_text = $question->title;

			if ( $question->type === Quiz_Creation::$question_type_key_fill_in_the_blank ) {
				$question_text = __( 'Fill in the blank the following statement', 'learndash' );
			}

			$question_pro_params['_question'] = $question_text;

			wp_update_post(
				[
					'ID'           => $question_id,
					'post_content' => wp_slash( $question_pro_params['_question'] ),
				]
			);
		}

		$question_model->set_array_to_object( $question_pro_params );
		$question_mapper->save( $question_model );

		return $question_id;
	}
}
