<?php
/**
 * Extends WP Pro Quiz functionality to meet needs of LearnDash
 *
 * @since 2.1.0
 *
 * @package LearnDash\Quiz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// cspell:ignore edithtml, qizzes .
// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase -- We'll never refactor it.

/**
 * Include WP Pro Quiz Plugin
 */
require_once LEARNDASH_LMS_LIBRARY_DIR . '/wp-pro-quiz/wp-pro-quiz.php';

/**
 * LearnDash QuizPro class
 *
 * @since 2.1.0
 */
class LD_QuizPro {
	/**
	 * Debug or not.
	 *
	 * @var bool
	 */
	public $debug = false;

	/**
	 * LD_QuizPro constructor
	 *
	 * @since 2.1.0
	 */
	public function __construct() {

		add_action( 'wp_pro_quiz_completed_quiz', array( $this, 'wp_pro_quiz_completed' ) );
		add_action( 'plugins_loaded', array( $this, 'quiz_edit_redirect' ), 1 );

		add_filter( 'ldadvquiz_the_content', 'wptexturize' );
		add_filter( 'ldadvquiz_the_content', 'convert_smilies' );
		add_filter( 'ldadvquiz_the_content', 'convert_chars' );
		add_filter( 'ldadvquiz_the_content', 'wpautop' );
		add_filter( 'ldadvquiz_the_content', 'shortcode_unautop' );
		add_filter( 'ldadvquiz_the_content', 'prepend_attachment' );

		add_filter( 'learndash_quiz_content', array( $this, 'learndash_quiz_content' ), 1 );

		if ( ! empty( $_GET['ld_fix_permissions'] ) ) {
			$role = get_role( 'administrator' );
			if ( ( $role ) && ( $role instanceof WP_Role ) ) {

				$role->add_cap( 'wpProQuiz_show' );
				$role->add_cap( 'wpProQuiz_add_quiz' );
				$role->add_cap( 'wpProQuiz_edit_quiz' );
				$role->add_cap( 'wpProQuiz_delete_quiz' );
				$role->add_cap( 'wpProQuiz_show_statistics' );
				$role->add_cap( 'wpProQuiz_reset_statistics' );
				$role->add_cap( 'wpProQuiz_import' );
				$role->add_cap( 'wpProQuiz_export' );
				$role->add_cap( 'wpProQuiz_change_settings' );
				$role->add_cap( 'wpProQuiz_toplist_edit' );
				$role->add_cap( 'wpProQuiz_toplist_edit' );
			}
		}

		add_action( 'wp_ajax_ld_adv_quiz_pro_ajax', array( $this, 'ld_adv_quiz_pro_ajax' ) );
		add_action( 'wp_ajax_nopriv_ld_adv_quiz_pro_ajax', array( $this, 'ld_adv_quiz_pro_ajax' ) );

		add_action( 'learndash_quiz_submitted', array( $this, 'set_quiz_status_meta' ), 1, 2 );
	}

	/**
	 * Submit quiz and echo JSON representation of the checked quiz answers
	 *
	 * @since 2.1.0
	 */
	public function ld_adv_quiz_pro_ajax() {
		// First we unpack the $_POST['results'] string.
		if ( ( isset( $_POST['data']['responses'] ) ) && ( ! empty( $_POST['data']['responses'] ) ) && ( is_string( $_POST['data']['responses'] ) ) ) {
			$_POST['data']['responses'] = json_decode( stripslashes( $_POST['data']['responses'] ), true );
		}

		$func = isset( $_POST['func'] ) ? $_POST['func'] : '';
		$data = isset( $_POST['data'] ) ? (array) $_POST['data'] : null;

		switch ( $func ) {
			case 'checkAnswers':
				echo $this->checkAnswers( $data );
				break;
		}

		exit; // We need to exit as this is the AJAX handler and should not return control back to WP.
	}

	/**
	 * Check answers for submitted quiz
	 *
	 * @since 2.1.0
	 *
	 * @param array $data Quiz information and answers to be checked.
	 *
	 * @return string  JSON representation of checked answers
	 */
	public function checkAnswers( $data ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Better to keep it this way.
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} else {
			$user_id = 0;
		}

		if ( isset( $data['quizId'] ) ) {
			$id = absint( $data['quizId'] );
		} else {
			$id = 0;
		}

		if ( isset( $data['quiz'] ) ) {
			$quiz_post_id = absint( $data['quiz'] );
		} else {
			$quiz_post_id = 0;
		}

		if ( ( ! isset( $data['quiz_nonce'] ) ) || ( ! wp_verify_nonce( $data['quiz_nonce'], 'sfwd-quiz-nonce-' . $quiz_post_id . '-' . $id . '-' . $user_id ) ) ) {
			die();
		}

		learndash_quiz_debug_log_init( $quiz_post_id );
		learndash_quiz_debug_log_message( 'Browser version: ' . $_SERVER['HTTP_USER_AGENT'] );
		learndash_quiz_debug_log_message( '---------------------------------' );
		learndash_quiz_debug_log_message( 'in ' . __FUNCTION__ );
		learndash_quiz_debug_log_message( '_POST<pre>' . print_r( $_POST, true ) . '</pre>' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- It's okay, the second argument is true.

		learndash_quiz_debug_log_message( 'user_id ' . $user_id );
		learndash_quiz_debug_log_message( 'quiz id ' . $id );
		learndash_quiz_debug_log_message( 'quiz_post_id ' . $quiz_post_id );

		if ( defined( 'LEARNDASH_QUIZ_DEBUG' ) && LEARNDASH_QUIZ_DEBUG ) {
			/**
			 * Filters quiz user responses.
			 *
			 * @since 4.3.0
			 *
			 * @param array $data         User Quiz response array.
			 * @param int   $user_id      User ID.
			 * @param int   $quiz_post_id Quiz Post ID.
			 */
			$data = apply_filters( 'learndash_quiz_check_answers_data', $data, $user_id, $quiz_post_id );

			learndash_quiz_debug_log_message( 'after filter: learndash_quiz_check_answers_data: data: ' . print_r( $data, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- It's okay, the second argument is true.
		}

		$quiz_post = get_post( $quiz_post_id );

		$view       = new WpProQuiz_View_FrontQuiz();
		$quizMapper = new WpProQuiz_Model_QuizMapper();
		$quiz       = $quizMapper->fetch( $id );
		if ( $quiz_post_id !== absint( $quiz->getPostId() ) ) {
			$quiz->setPostId( $quiz_post_id );
		}

		$questionMapper = new WpProQuiz_Model_QuestionMapper();
		$categoryMapper = new WpProQuiz_Model_CategoryMapper();
		$formMapper     = new WpProQuiz_Model_FormMapper();

		$questionModels = $questionMapper->fetchAll( $quiz );

		$view->quiz     = $quiz;
		$view->question = $questionModels;
		$view->category = $categoryMapper->fetchByQuiz( $quiz );

		$question_count = count( $questionModels );
		ob_start();
		$quizData = $view->showQuizBox( $question_count );
		ob_get_clean();

		$json           = $quizData['json'];
		$results        = array();
		$question_index = 0;

		foreach ( $data['responses'] as $question_id => $info ) {
			if ( isset( $questionModel ) ) {
				unset( $questionModel );
			}

			foreach ( $questionModels as $questionModel ) {
				if ( $questionModel->getId() == intval( $question_id ) ) {

					$userResponse = $info['response'];

					$questionData           = $json[ $question_id ];
					$correct                = false;
					$points                 = 0;
					$statisticsData         = new stdClass();
					$extra                  = array();
					$extra['type']          = $questionData['type'];
					$questionData['points'] = isset( $questionData['points'] ) ? $questionData['points'] : $questionData['globalPoints'];

					$question_index++;
					$answer_pointed_activated = $questionModel->isAnswerPointsActivated();

					/**
					 * Filters whether use the legacy sanitize user response question.
					 *
					 * @since 4.3.0
					 *
					 * @param boolean $use_legacy   Whether to use legacy sanitize scheme. Default false.
					 * @param mixed   $userResponse User question response data.
					 * @param object  $questionData WpProQuiz_Model_Question Question Model instance.
					*/
					$question_legacy_sanitize_scheme = apply_filters( 'learndash_quiz_question_legacy_sanitize_scheme', false, $userResponse, $questionData );

					switch ( $questionData['type'] ) {
						case 'free_answer':
							if ( ! $question_legacy_sanitize_scheme ) {
								$userResponse          = esc_attr( wp_unslash( trim( $userResponse ) ) );
								$userResponse_filtered = $userResponse;
							} else {
								$userResponse = stripslashes( trim( $userResponse ) );
								$userResponse = $userResponse_filtered;
							}
							$correct = false;
							$points  = 0;

							if ( ( ! empty( $questionData['correct'] ) ) && ( '' !== $userResponse_filtered ) ) {

								/**
								 * The default value is based on the opposite of the legacy sanitize var value.
								 *
								 * If the legacy var is 'false' then we probably want to set this var as true since we do
								 * want to format the correct answers.
								 */
								$format_correct = ! $question_legacy_sanitize_scheme;

								/**
								 * Filters whether to format the question correct answers.
								 *
								 * This might mean converting HTML to entities, removing some HTML tags, etc.
								 *
								 * @since 4.4.0
								 *
								 * @param boolean                  $format_correct  Whether to format the question correct answers.
								 * @param array                    $questionData    Array of question data.
								 * @param WpProQuiz_Model_Question $question_model  Question model object.
								 */
								$format_correct = apply_filters( 'learndash_quiz_format_correct_answer', $format_correct, $questionData, $questionModel );

								foreach ( $questionData['correct'] as $questionData_idx => $questionData_correct ) {
									if ( $format_correct ) {
										$questionData_correct_filtered = esc_attr( trim( $questionData_correct ) );
									} else {
										$questionData_correct_filtered = stripslashes( trim( $questionData_correct ) );
									}

									/**
									 * Filters whether to convert quiz question free to lowercase or not.
									 *
									 * @since 3.5.0
									 *
									 * @param boolean $convert_to_lower Whether to convert quiz question free to lower case.
									 * @param object  $question         WpProQuiz_Model_Question Question Model instance.
									*/
									if ( apply_filters( 'learndash_quiz_question_free_answers_to_lowercase', true, $questionModel ) ) {
										if ( function_exists( 'mb_strtolower' ) ) {
											$userResponse_filtered         = mb_strtolower( $userResponse_filtered );
											$questionData_correct_filtered = mb_strtolower( $questionData_correct_filtered );
										} else {
											$userResponse_filtered         = strtolower( $userResponse_filtered );
											$questionData_correct_filtered = strtolower( $questionData_correct_filtered );
										}
									}

									if ( $userResponse_filtered == $questionData_correct_filtered ) {
										$correct = true;
										if ( $questionModel->isAnswerPointsActivated() ) {
											if ( isset( $questionData['points'][ $questionData_idx ] ) ) {
												$points = (int) $questionData['points'][ $questionData_idx ];
											} else {
												$points = 1;
											}
										} else {
											$points = $questionModel->getPoints();
										}
										break;
									}
								}
							}

							/**
							 * Filters answer points for free question type.
							 *
							 * @param int    $points        Points for the question.
							 * @param array  $question_data An array of question data.
							 * @param string $user_response User response data.
							 */
							$points = apply_filters( 'learndash_ques_free_answer_pts', $points, $questionData, $userResponse );

							/**
							 * Filters whether the answer is correct or not for a free question type.
							 *
							 * @param boolean $correct       Whether the answer is correct or not.
							 * @param array   $question_data An array of question data.
							 * @param string  $user_response User response data.
							 */
							$correct = apply_filters( 'learndash_ques_free_answer_correct', $correct, $questionData, $userResponse );

							$extra['r'] = $userResponse;
							if ( ! $quiz->isDisabledAnswerMark() && empty( $questionData['disCorrect'] ) ) {
								if ( isset( $questionData['correct'] ) ) {
									$extra['c'] = $questionData['correct'];
								} else {
									$extra['c'] = array();
								}
							}

							break;

						case 'multiple':
							// Normalize the user response/answers.
							if ( ( is_array( $userResponse ) ) && ( ! empty( $userResponse ) ) ) {
								foreach ( $userResponse as $key => $value ) {
									if ( ( $value != 0 ) && ( $value != 1 ) ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison -- Strict compare causes failure. Need to rework logic.
										if ( $value === 'true' ) {
											$userResponse[ $key ] = true;
										} else {
											$userResponse[ $key ] = false;
										}
									}
								}
							}

							$correct = true;
							$r       = array();
							if ( ! empty( $questionData['correct'] ) ) {
								foreach ( $questionData['correct'] as $answerIndex => $correctAnswer ) {
									if ( $answer_pointed_activated ) {
										if ( ( isset( $userResponse[ $answerIndex ] ) ) && ( $userResponse[ $answerIndex ] == $correctAnswer ) ) {
											$r[ $answerIndex ] = $userResponse[ $answerIndex ];
											$correct_this_item = true;

											if ( $userResponse[ $answerIndex ] == true ) {
												$points += $questionData['points'][ $answerIndex ];
											}
										} else {
											$r[ $answerIndex ] = false;
											$correct_this_item = false;
										}

										if ( has_filter( 'learndash_ques_multiple_answer_pts_each' ) ) {
											/**
											 * Filters the points of each answer for multiple answer type question.
											 *
											 * @param int        $point          Points for the question.
											 * @param int|string $answer_index   Index of the answer.
											 * @param array      $question_data  An array of question data.
											 * @param mixed      $correct_answer Correct answer for the question.
											 * @param array      $user_response  An array of user response data.
											 */
											$points = apply_filters( 'learndash_ques_multiple_answer_pts_each', $points, $questionData, $answerIndex, $correctAnswer, $userResponse );
										} else {
											/**
											 * Added logic to subtract points on selected incorrect answers.
											 *
											 * @since 2.5.7
											 */
											if ( $questionData['correct'][ $answerIndex ] == 0 ) {
												if ( $correct_this_item == false ) {
													if ( intval( $questionData['points'][ $answerIndex ] ) > 0 ) {
														$points -= intval( $questionData['points'][ $answerIndex ] );
													}
												}

												end( $questionData['correct'] );
											}
										}

										/**
										 * Filters whether to correct the answer for a multiple answer type question or not.
										 *
										 * @param boolean    $correct_item   Whether to correct the answer or not.
										 * @param array      $question_data  An array of question data.
										 * @param int|string $answer_index   Index of the answer.
										 * @param mixed      $correct_answer Correct answer for the question.
										 * @param array      $user_response  An array of user response data.
										 */
										$correct_this_item = apply_filters( 'learndash_ques_multiple_answer_correct_each', $correct_this_item, $questionData, $answerIndex, $correctAnswer, $userResponse );
										if ( ( $correct_this_item != true ) && ( $correct == true ) ) {
											$correct = false;
										}
									} else {

										/**
										 * Points are allocated for the entire question if the user selects all the correct answers and none of
										 * the incorrect answers
										 *
										 * If the user selects an answer that is marked as correct, mark the question true and let the
										 * foreach loop check the next answer
										 *
										 * if they select an incorrect answer, or fail to select a correct answer, mark it false and break
										 * the foreach
										 *
										 * we don't want to break the foreach if the user did not select an incorrect answer
										 */
										if ( ! empty( $correctAnswer ) && ! empty( $userResponse[ $answerIndex ] ) ) {
											$correct           = true;
											$r[ $answerIndex ] = true;
											$points            = $questionData['points'];
										} elseif ( empty( $correctAnswer ) && ! empty( $userResponse[ $answerIndex ] ) ) {
											$correct           = false;
											$r[ $answerIndex ] = false;
											$points            = 0;
											break;
										} elseif ( ! empty( $correctAnswer ) && empty( $userResponse[ $answerIndex ] ) ) {
											$correct           = false;
											$r[ $answerIndex ] = false;
											$points            = 0;
											break;
										}

										// See https://developers.learndash.com/hook/learndash_ques_multiple_answer_pts_whole/ for examples of this filter.
										/**
										 * Filters points awarded for a multiple answer type question.
										 *
										 * LearnDash multiple answer question, allow points to be allocated for not marking an incorrect answer.
										 * LearnDash Core loops over all the answers in a multiple answer question. If the user does not mark an incorrect answer,
										 * allow the possibility of giving them points.
										 *
										 * @param int        $points         Points awarded to quiz
										 * @param array      $question_data  An array of question data.
										 * @param int|string $answer_index   Index of the answer.
										 * @param mixed      $correct_answer Correct answer for the question.
										 * @param array      $user_response  An array of user response data.
										 */
										$points = apply_filters( 'learndash_ques_multiple_answer_pts_whole', $points, $questionData, $answerIndex, $correctAnswer, $userResponse );

										/**
										 * Filters whether the answer to the multiple type question is correct or not.
										 *
										 * @param boolean    $correct        Whether the answer is correct or not.
										 * @param array      $question_data  An array of question data.
										 * @param int|string $answer_index   Index of the answer.
										 * @param mixed      $correct_answer Correct answer for the question.
										 * @param array      $user_response  An array of user response data.
										 */
										$correct = apply_filters( 'learndash_ques_multiple_answer_correct_whole', $correct, $questionData, $answerIndex, $correctAnswer, $userResponse );

									}
								}
							}

							// If total question points are less than zero.
							if ( $points < 0 ) {
								$points = 0;
							}

							$extra['r'] = $userResponse;

							if ( ! $quiz->isDisabledAnswerMark() ) {
								$extra['c'] = $questionData['correct'];
							}

							break;

						case 'single':
							// Normalize the user response/answers.
							if ( ( is_array( $userResponse ) ) && ( ! empty( $userResponse ) ) ) {
								foreach ( $userResponse as $key => $value ) {
									if ( ( $value != 0 ) && ( $value != 1 ) ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison -- Strict compare causes failure. Need to rework logic.
										if ( $value === 'true' ) {
											$userResponse[ $key ] = true;
										} else {
											$userResponse[ $key ] = false;
										}
									}
								}
							}

							if ( ! empty( $questionData['correct'] ) ) {
								foreach ( $questionData['correct'] as $answerIndex => $correctAnswer ) {
									if ( $userResponse[ $answerIndex ] === true ) {

										if ( ( ( isset( $questionData['diffMode'] ) ) && ( ! empty( $questionData['diffMode'] ) ) ) || ( ! empty( $correctAnswer ) ) ) {
											// DiffMode or Correct.
											if ( is_array( $questionData['points'] ) ) {
												$points = $questionData['points'][ $answerIndex ];
											} else {
												$points = $questionData['points'];
											}
										}

										if ( ! empty( $correctAnswer ) || ! empty( $questionData['disCorrect'] ) ) {
											$correct = true;
										}

										// See https://developers.learndash.com/hook/learndash_ques_single_answer_pts/ for examples of this filter.
										/**
										 * Filters points awarded for a single answer type question.
										 *
										 * LearnDash single answer question, allow points to be allocated for not marking an incorrect answer.
										 * Allow all possibility of given answer to be correct answer.
										 *
										 * @param integer    $points         Points awarded to quiz
										 * @param array      $question_data  An array of question data.
										 * @param int|string $answer_index   Index of the answer.
										 * @param mixed      $correct_answer Correct answer for the question.
										 * @param array      $user_response  An array of user response data.
										 */
										$points = apply_filters( 'learndash_ques_single_answer_pts', $points, $questionData, $answerIndex, $correctAnswer, $userResponse );
										/**
										 * Filters whether the answer to the single type question is correct or not.
										 *
										 * @param boolean    $correct        Whether the answer is correct or not.
										 * @param array      $question_data  An array of question data.
										 * @param int|string $answer_index   Index of the answer.
										 * @param mixed      $correct_answer Correct answer for the question.
										 * @param array      $user_response  An array of user response data.
										 */
										$correct = apply_filters( 'learndash_ques_single_answer_correct', $correct, $questionData, $answerIndex, $correctAnswer, $userResponse );
									}
								}
							}

							$extra['r'] = $userResponse;

							if ( ! $quiz->isDisabledAnswerMark() && empty( $questionData['disCorrect'] ) ) {
								if ( ! empty( $questionData['correct'] ) ) {
									$extra['c'] = $questionData['correct'];
								}
							}
							break;

						case 'sort_answer':
						case 'matrix_sort_answer':
							$correct                 = true;
							$questionData['correct'] = self::datapos_array( $question_id, count( $questionData['correct'] ) );

							// Normalize the user response/answers.
							if ( ( is_array( $userResponse ) ) && ( ! empty( $userResponse ) ) ) {
								foreach ( $userResponse as $key => &$value ) {
									$value = sanitize_text_field( wp_unslash( trim( $value ) ) );
								}
								unset( $value );
							}

							if ( ! empty( $questionData['correct'] ) ) {
								foreach ( $questionData['correct'] as $answerIndex => $answer ) {
									if ( ! isset( $userResponse[ $answerIndex ] ) || $userResponse[ $answerIndex ] != $answer ) {
										$correct = false;
									} else {
										if ( is_array( $questionData['points'] ) ) {
											$points += $questionData['points'][ $answerIndex ];
										}
									}
									if ( isset( $userResponse[ $answerIndex ] ) ) {
										$statisticsData->{$answerIndex} = $userResponse[ $answerIndex ];
									} else {
										$statisticsData->{$answerIndex} = '';
									}
								}
							}

							if ( $correct ) {
								if ( ! is_array( $questionData['points'] ) ) {
									$points = $questionData['points'];
								}
							} else {
								$statisticsData = new stdClass();
							}

							$extra['r'] = $userResponse;

							if ( ! $quiz->isDisabledAnswerMark() && empty( $questionData['disCorrect'] ) ) {
								$extra['c'] = $questionData['correct'];
							} else {
								$statisticsData = new stdClass();
							}

							break;

						case 'cloze_answer':
							$answerData = array();

							// Normalize the user response/answers.
							if ( ( is_array( $userResponse ) ) && ( ! empty( $userResponse ) ) ) {
								foreach ( $userResponse as $key => &$value ) {
									if ( ! $question_legacy_sanitize_scheme ) {
										$value = esc_attr( wp_unslash( trim( $value ) ) );
									} else {
										$value = stripslashes( trim( $value ) );
									}
								}
								unset( $value );
							}

							if ( ! empty( $questionData['correct'] ) ) {
								foreach ( $questionData['correct'] as $answerIndex => $correctArray ) {
									$answerData[ $answerIndex ] = false;

									if ( ! isset( $userResponse[ $answerIndex ] ) ) {
										$answerData[ $answerIndex ] = false;
									}

									/**
									 * The default value is based on the opposite of the legacy sanitize var value.
									 *
									 * If the legacy var is 'false' then we probably want to set this var as true since we do
									 * want to format the correct answers.
									 */
									$format_correct = ! $question_legacy_sanitize_scheme;

									/** This filter is documented in includes/quiz/ld-quiz-pro.php */
									$format_correct = apply_filters( 'learndash_quiz_format_correct_answer', $format_correct, $questionData, $questionModel );
									if ( $format_correct ) {
										foreach ( $correctArray as $key => &$value ) {
											$value = esc_attr( wp_unslash( trim( $value ) ) );
										}
										unset( $value );
									}

									/** This filter is documented in includes/lib/wp-pro-quiz/wp-pro-quiz.php */
									if ( apply_filters( 'learndash_quiz_question_cloze_answers_to_lowercase', true ) ) {
										if ( function_exists( 'mb_strtolower' ) ) {
											$user_answer_formatted = mb_strtolower( $userResponse[ $answerIndex ] );
										} else {
											$user_answer_formatted = strtolower( $userResponse[ $answerIndex ] );
										}
									} else {
										$user_answer_formatted = $userResponse[ $answerIndex ];
									}

									$correct_idx = array_search( $user_answer_formatted, $correctArray );
									if ( false !== $correct_idx ) {
										$answerData[ $answerIndex ] = true;
									}

									/**
									 * Filters whether to check the answer of close type question.
									 *
									 * @param boolean                  $check_answer    Whether to check the answer.
									 * @param string                   $question_type   Type of the question.
									 * @param string                   $answer          The answer given by user for the question.
									 * @param array                    $correct_answers An array of correct answers for the question.
									 * @param int                      $answer_index    Answer index.
									 * @param WpProQuiz_Model_Question $question_model  Question model object.
									 */
									$answerData[ $answerIndex ]     = apply_filters(
										'learndash_quiz_check_answer',
										$answerData[ $answerIndex ],
										$questionData['type'],
										$userResponse[ $answerIndex ],
										$correctArray,
										$answerIndex,
										$questionModel
									);
									$statisticsData->{$answerIndex} = $answerData[ $answerIndex ];

									if ( $answerData[ $answerIndex ] === true ) {
										if ( ! isset( $questionData['points'] ) ) {
											$questionData['points'] = 1;
										}
										if ( $questionModel->isAnswerPointsActivated() ) {
											if ( ( is_array( $questionData['points'] ) ) && ( isset( $questionData['points'][ $answerIndex ][ $correct_idx ] ) ) ) {
												$points += (int) $questionData['points'][ $answerIndex ][ $correct_idx ];
											} else {
												$points = $questionData['points'];
											}
										} else {
											$points = $questionData['points'];
										}
									}
								}
							}

							// If we have one wrong answer.
							if ( in_array( false, $answerData ) === true ) {
								$correct = false;

								// If we are NOT using individual points and there is at least one wrong answer
								// then we clear the points.
								if ( ! $questionModel->isAnswerPointsActivated() ) {
									$points = 0;
								}
							} else {
								// If all the fields are correct then the points stand and we set the correct to true.
								$correct = true;
							}

							$extra['r'] = $userResponse;

							if ( ! $quiz->isDisabledAnswerMark() && empty( $questionData['disCorrect'] ) ) {
								$extra['c'] = $questionData['correct'];
							}
							break;

						case 'assessment_answer':
							// Normalize the user response/answers.
							$userResponse = absint( $userResponse );

							$correct = false;
							$points  = 0;

							if ( ( ! empty( $userResponse ) ) && ( isset( $questionData['correct'][ $userResponse - 1 ] ) ) ) {
								$correct = true;
								if ( isset( $questionData['points'][ $userResponse - 1 ] ) ) {
									$points = $questionData['points'][ $userResponse - 1 ];
								} else {
									$points = 1;
								}
							}

							$extra['r'] = $userResponse;

							break;

						case 'essay':
							if ( ! empty( $userResponse ) ) {
								$essay_data = $questionModel->getAnswerData();

								$essay_data = array_shift( $essay_data );

								switch ( $essay_data->getGradingProgression() ) {
									case '':
									case 'not-graded-none':
										$points                 = 0;
										$correct                = false;
										$extra['graded_status'] = 'not_graded';
										break;

									case 'not-graded-full':
										$points                 = $essay_data->getPoints();
										$correct                = false;
										$extra['graded_status'] = 'not_graded';
										break;

									case 'graded-full':
										$points                 = $essay_data->getPoints();
										$correct                = true;
										$extra['graded_status'] = 'graded';
										break;

									default:
										$points                 = 0;
										$correct                = false;
										$extra['graded_status'] = 'not_graded';
								}

								$essay_id           = learndash_add_new_essay_response( $userResponse, $questionModel, $quiz, $data );
								$extra['graded_id'] = $essay_id;
							} else {
								$points                 = 0;
								$correct                = false;
								$extra['graded_status'] = 'not_graded';
								$extra['graded_id']     = 0;

							}
							break;

						default:
							break;
					}

					if ( ! $quiz->isHideAnswerMessageBox() ) {
						foreach ( $questionModels as $key => $value ) {
							if ( $value->getId() == $question_id ) {
								if ( $correct || $value->isCorrectSameText() ) {
									$extra['AnswerMessage'] = do_shortcode( learndash_the_content( $value->getCorrectMsg(), __FUNCTION__ ) );
								} else {
									$extra['AnswerMessage'] = do_shortcode( learndash_the_content( $value->getIncorrectMsg(), __FUNCTION__ ) );
								}

								break;
							}
						}
					}

					$extra['possiblePoints'] = $questionModel->getPoints();

					/**
					 * Filters a quiz question result.
					 *
					 * @since 4.4.1.2
					 *
					 * @param array $results     Result values.
					 * @param int   $question_id Question ID.
					 */
					$results[ $question_id ] = apply_filters(
						'learndash_quiz_question_result',
						array(
							'c' => $correct,
							'p' => $points,
							's' => $statisticsData,
							'e' => $extra,
						),
						$question_id
					);

					break;
				}
			}
		}

		/**
		 * Fires after a quiz question is answered.
		 *
		 * @param array                $results         An array of quiz results data.
		 * @param WpProQuiz_Model_Quiz $quiz            WpProQuiz_Model_Quiz object.
		 * @param array                $question_models An array of question model objects.
		 */
		do_action( 'ldadvquiz_answered', $results, $quiz, $questionModels );

		$total_points = 0;

		foreach ( $results as $r_idx => $result ) {

			if ( ( isset( $result['e'] ) ) && ( ! empty( $result['e'] ) ) ) {
				if ( ( isset( $result['e']['type'] ) ) && ( ! empty( $result['e']['type'] ) ) ) {
					$response_str = '';

					switch ( $result['e']['type'] ) {
						case 'essay':
							if ( ( isset( $result['e']['graded_id'] ) ) && ( ! empty( $result['e']['graded_id'] ) ) ) {
								$response_str = maybe_serialize( array( 'graded_id' => $result['e']['graded_id'] ) );
							}
							break;

						case 'free_answer':
							if ( ( isset( $result['e']['r'] ) ) && ( '' !== $result['e']['r'] ) ) {

								$response_str = maybe_serialize( array( trim( $result['e']['r'] ) ) );
							}
							break;

						case 'assessment_answer':
							if ( ( isset( $result['e']['r'] ) ) && ( ! empty( $result['e']['r'] ) ) ) {
								$response_str = maybe_serialize( array( (string) $result['e']['r'] ) );
							}
							break;

						case 'multiple':
						case 'single':
						default:
							if ( ( isset( $result['e']['r'] ) ) && ( ! empty( $result['e']['r'] ) ) ) {
								$result_array = array();
								foreach ( $result['e']['r'] as $ri_idx => $ri ) {
									if ( $ri === true ) {
										$ri = 1;
									} elseif ( $ri === false ) {
										$ri = 0;
									}

									$result_array[ $ri_idx ] = $ri;
								}
								$response_str = maybe_serialize( $result_array );
							}
							break;
					}

					if ( ! empty( $response_str ) ) {
						$answers_nonce                = wp_create_nonce( 'ld_quiz_anonce' . $user_id . '_' . $id . '_' . $quiz_post_id . '_' . $r_idx . '_' . $response_str ); // cspell:disable-line.
						$results[ $r_idx ]['a_nonce'] = $answers_nonce;
					}
				}
			}

			$points_array = array(
				'points'         => intval( $result['p'] ),
				'correct'        => intval( $result['c'] ),
				'possiblePoints' => intval( $result['e']['possiblePoints'] ),
			);
			if ( $points_array['correct'] === false ) {
				$points_array['correct'] = 0;
			} elseif ( $points_array['correct'] === true ) {
				$points_array['correct'] = 1;
			}
			$points_str                   = maybe_serialize( $points_array );
			$points_nonce                 = wp_create_nonce( 'ld_quiz_pnonce' . $user_id . '_' . $id . '_' . $quiz_post_id . '_' . $r_idx . '_' . $points_str ); // cspell:disable-line.
			$results[ $r_idx ]['p_nonce'] = $points_nonce;
		}

		learndash_quiz_debug_log_message( __FUNCTION__ . ': results: ' . print_r( $results, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- It's okay, the second argument is true.

		return wp_json_encode( $results );
	}

	/**
	 * Redirect from the Advanced Quiz edit or add link to the Quiz edit or add link
	 *
	 * @since 2.1.0
	 */
	public function quiz_edit_redirect() {

		if ( ! empty( $_GET['page'] ) && $_GET['page'] == 'ldAdvQuiz' && empty( $_GET['module'] ) && ! empty( $_GET['action'] ) && $_GET['action'] == 'addEdit' ) {

			if ( ! empty( $_GET['post_id'] ) ) {
				header( 'Location: ' . admin_url( 'post.php?action=edit&post=' . absint( $_GET['post_id'] ) ) );
				exit;
			} elseif ( ! empty( $_GET['quizId'] ) ) {
				$post_id = learndash_get_quiz_id_by_pro_quiz_id( absint( $_GET['quizId'] ) );

				if ( ! empty( $post_id ) ) {
					header( 'Location: ' . admin_url( 'post.php?action=edit&post=' . absint( $post_id ) ) );
				} else {
					header( 'Location: ' . admin_url( 'edit.php?post_type=sfwd-quiz' ) );
				}

				exit;
			}

			header( 'Location: ' . admin_url( 'post-new.php?post_type=sfwd-quiz' ) );
			exit;
		}
	}



	/**
	 * Echoes quiz content
	 *
	 * @since 2.1.0
	 *
	 * @param int $pro_quiz_id Pro Quiz ID.
	 */
	public static function showQuizContent( $pro_quiz_id ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Better to keep it this way.
		global $post;

		if ( empty( $post ) || $post->post_type == 'sfwd-quiz' ) {
			return '';
		}

		echo self::get_description( $pro_quiz_id );
	}



	/**
	 * Returns the HTML representation of the quiz description
	 *
	 * @since 2.1.0
	 *
	 * @param int $pro_quiz_id Pro Quiz ID.
	 *
	 * @return string HTML representation of quiz description
	 */
	public static function get_description( $pro_quiz_id ) {
		$post_id = learndash_get_quiz_id_by_pro_quiz_id( $pro_quiz_id );

		if ( empty( $post_id ) ) {
			return '';
		}

		$quiz = get_post( $post_id );

		if ( empty( $quiz->post_content ) ) {
			return '';
		}

		/**
		 * Filters the description of the quiz.
		 *
		 * @param string $quiz_description The quiz description.
		 */
		$content = apply_filters( 'ldadvquiz_the_content', $quiz->post_content );

		/**
		 * Added call to do_shortcode to process any shortcodes within the quiz content.
		 *
		 * @since 2.6.0
		 */
		$content = do_shortcode( $content );

		$content = str_replace( ']]>', ']]&gt;', $content );
		return "<div class='wpProQuiz_description'>" . $content . '</div>';
	}



	/**
	 * Outputs the debugging message to the error log file
	 *
	 * @since 2.1.0
	 *
	 * @param string $msg Debugging message.
	 */
	public function debug( $msg ) {
	}


	/**
	 * Does the list of questions for this quiz have a graded question in it
	 * Dataset used is not the quizdata saved to user meta, but follows the
	 * Question Model of WpProQuiz
	 *
	 * @since 2.1.0
	 *
	 * @param array $questions Questions.
	 *
	 * @return bool
	 */
	public static function quiz_has_graded_question( $questions ) {
		$graded_question_types = array( 'essay' );

		foreach ( $questions as $question ) {
			if ( ! is_a( $question, 'WpProQuiz_Model_Question' ) ) {
				continue;
			}

			if ( in_array( $question->getAnswerType(), $graded_question_types, true ) ) {
				// found one! halt foreach and return true.
				return true;
			}
		}

		// foreach completed without finding any, return false.
		return false;
	}



	/**
	 * Checks a users submitted quiz attempt to see if that quiz
	 * has graded questions and if all of them have been graded
	 *
	 * @since 2.2.0
	 *
	 * @param array $quiz_attempt Quiz Attempt data.
	 */
	public static function quiz_attempt_has_ungraded_question( $quiz_attempt ) {
		if ( isset( $quiz_attempt['graded'] ) ) {
			foreach ( $quiz_attempt['graded'] as $graded ) {
				if ( 'not_graded' == $graded['status'] ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * This function runs when a quiz is started and is used to set the quiz start timestamp
	 *
	 * @since 2.3.0
	 *
	 * @param array  $quizdata Quiz data array.
	 * @param object $user     WP_User object.
	 */
	public function set_quiz_status_meta( $quizdata, $user ) {

		if ( empty( $quizdata ) ) {
			return;
		}
		if ( ! ( $user instanceof WP_User ) ) {
			return;
		}

		if ( isset( $quizdata['questions'] ) ) {
			unset( $quizdata['questions'] );
		}

		if ( ( isset( $quizdata['quiz'] ) ) && ( $quizdata['quiz'] instanceof WP_Post ) ) {
			$quiz_post = $quizdata['quiz'];
			unset( $quizdata['quiz'] );
			$quizdata['quiz'] = intval( $quiz_post->ID );
		}

		$course_id = 0;
		$lesson_id = 0;
		$topic_id  = 0;

		if ( ( isset( $quizdata['course'] ) ) && ( $quizdata['course'] instanceof WP_Post ) ) {
			$course_post = $quizdata['course'];
			unset( $quizdata['course'] );
			$quizdata['course'] = intval( $course_post->ID );
			$course_id          = $quizdata['course'];
		}
		if ( ( isset( $quizdata['lesson'] ) ) && ( $quizdata['lesson'] instanceof WP_Post ) ) {
			$lesson_post = $quizdata['lesson'];
			unset( $quizdata['lesson'] );
			$quizdata['lesson'] = intval( $lesson_post->ID );
			$lesson_id          = $quizdata['lesson'];
		}
		if ( ( isset( $quizdata['topic'] ) ) && ( $quizdata['topic'] instanceof WP_Post ) ) {
			$topic_post = $quizdata['topic'];
			unset( $quizdata['topic'] );
			$quizdata['topic'] = intval( $topic_post->ID );
			$topic_id          = $quizdata['topic'];
		}

		if ( ( isset( $quizdata['course'] ) ) && ( ! empty( $quizdata['course'] ) ) ) {
			// Update the Course if this quiz has.
			$course_start_time = learndash_activity_course_get_earliest_started( $user->ID, $quizdata['course'], $quizdata['started'] );
			$course_activity   = learndash_activity_start_course( $user->ID, $quizdata['course'], $course_start_time );
			if ( $course_activity ) {
				learndash_activity_update_meta_set(
					$course_activity->activity_id,
					array(
						'steps_completed' => learndash_course_get_completed_steps( $user->ID, $quizdata['course'] ),
						'steps_last_id'   => $quizdata['quiz'],
					)
				);
			}

			if ( ( isset( $quizdata['lesson'] ) ) && ( ! empty( $quizdata['lesson'] ) ) ) {
				learndash_activity_start_lesson( $user->ID, $quizdata['course'], $quizdata['lesson'], $quizdata['started'] );
			}
			if ( ( isset( $quizdata['topic'] ) ) && ( ! empty( $quizdata['topic'] ) ) ) {
				learndash_activity_start_topic( $user->ID, $quizdata['course'], $quizdata['topic'], $quizdata['started'] );
			}
		}

		LDLMS_User_Quiz_Resume::delete_user_quiz_resume_metadata( $user->ID, $quizdata['quiz'], $quizdata['course'], $quizdata['started'] );

		if ( ( isset( $quizdata['started'] ) ) && ( ! empty( $quizdata['started'] ) ) && ( isset( $quizdata['completed'] ) ) && ( ! empty( $quizdata['completed'] ) ) ) {

			if ( $quizdata['pass'] == true ) {
				$quizdata_pass = true;
			} else {
				$quizdata_pass = false;
			}

			$quiz_args     = array(
				'course_id'          => $quizdata['course'],
				'user_id'            => $user->ID,
				'post_id'            => $quizdata['quiz'],
				'activity_type'      => 'quiz',
				'activity_completed' => 0,
			);
			$quiz_activity = learndash_get_user_activity( $quiz_args );
			if ( ( is_object( $quiz_activity ) ) && ( property_exists( $quiz_activity, 'activity_id' ) ) && ( ! empty( $quiz_activity->activity_id ) ) ) {
				$activity_id = (int) $quiz_activity->activity_id;
			} else {
				$activity_id = 0;
			}
			learndash_update_user_activity(
				array(
					'activity_id'        => $activity_id,
					'course_id'          => absint( $quizdata['course'] ),
					'user_id'            => absint( $user->ID ),
					'post_id'            => absint( $quizdata['quiz'] ),
					'activity_type'      => 'quiz',
					'activity_status'    => $quizdata_pass,
					'activity_started'   => (int) $quizdata['started'],
					'activity_completed' => (int) $quizdata['completed'],
					'activity_meta'      => $quizdata,
				)
			);
		}
	}

	/**
	 * This function runs when a quiz is completed, and does the action 'wp_pro_quiz_completed_quiz'
	 *
	 * @since 2.1.0
	 *
	 * @param integer $statistic_ref_id Quiz Statistics Ref ID.
	 */
	public function wp_pro_quiz_completed( $statistic_ref_id = 0 ) {
		learndash_quiz_debug_log_message( 'in ' . __FUNCTION__ );
		learndash_quiz_debug_log_message( 'statistic_ref_id ' . $statistic_ref_id );

		$results      = array();
		$quiz_pro_id  = isset( $_POST['quizId'] ) ? absint( $_POST['quizId'] ) : null;
		$quiz_post_id = isset( $_POST['quiz'] ) ? absint( $_POST['quiz'] ) : null;
		$score        = isset( $_POST['results']['comp']['correctQuestions'] ) ? $_POST['results']['comp']['correctQuestions'] : null;
		$points       = isset( $_POST['results']['comp']['points'] ) ? absint( $_POST['results']['comp']['points'] ) : null;
		$result       = isset( $_POST['results']['comp']['result'] ) ? $_POST['results']['comp']['result'] : null;
		$timespent    = isset( $_POST['timespent'] ) ? floatval( $_POST['timespent'] ) : null;

		if ( ( is_null( $quiz_post_id ) ) || ( is_null( $quiz_pro_id ) ) || ( is_null( $points ) ) ) {
			return wp_json_encode( $results );
		}

		$course_id = ( ( isset( $_POST['course_id'] ) ) && ( intval( $_POST['course_id'] ) > 0 ) ) ? intval( $_POST['course_id'] ) : learndash_get_course_id( $quiz_post_id );
		$lesson_id = ( ( isset( $_POST['lesson_id'] ) ) && ( intval( $_POST['lesson_id'] ) > 0 ) ) ? intval( $_POST['lesson_id'] ) : 0;
		$topic_id  = ( ( isset( $_POST['topic_id'] ) ) && ( intval( $_POST['topic_id'] ) > 0 ) ) ? intval( $_POST['topic_id'] ) : 0;
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} else {
			$user_id = 0;
		}

		$quizMapper = new WpProQuiz_Model_QuizMapper();
		$quiz_pro   = $quizMapper->fetch( $quiz_pro_id );
		if ( ( ! $quiz_pro ) || ( ! is_a( $quiz_pro, 'WpProQuiz_Model_Quiz' ) ) ) {
			return wp_json_encode( $results );
		}
		$quiz_pro->setPostId( $quiz_post_id );

		$questionMapper = new WpProQuiz_Model_QuestionMapper();
		$questions      = $questionMapper->fetchAll( $quiz_pro );
		if ( is_array( $questions ) ) {
			$questions_count = count( $questions );
		}

		// check if these set of questions has questions that need to be graded.
		$has_graded = self::quiz_has_graded_question( $questions );

		// store the id's of the graded question to be saved in usermeta.
		$graded = array();
		foreach ( $_POST['results'] as $question_id => $individual_result ) {
			if ( 'comp' == $question_id ) {
				continue;
			}

			if ( isset( $individual_result['graded_id'] ) && ! empty( $individual_result['graded_id'] ) ) {
				$graded[ $question_id ] = array(
					'post_id'        => intval( $individual_result['graded_id'] ),
					'status'         => esc_html( $individual_result['graded_status'] ),
					'points_awarded' => intval( $individual_result['points'] ),
				);
			}
		}

		if ( empty( $graded ) ) {
			$has_graded = false;
		}

		if ( empty( $result ) ) {
			$total_points = 0;

			// Rewrote logic here to only count points for the questions shown to the user.
			// For example I might have a Quiz showing only 5 of 10 questions. In the above code
			// the points counted include ALL 10 questions. Not correct.
			// Instead we do the logic below and only process the 5 shown questions.
			foreach ( $_POST['results'] as $question_id => $q_result ) {
				if ( 'comp' == $question_id ) {
					continue;
				}

				if ( ( isset( $q_result['possiblePoints'] ) ) && ( ! empty( $q_result['possiblePoints'] ) ) ) {
					$total_points += intval( $q_result['possiblePoints'] );
				}
			}
		} else {
			$total_points = round( $points * 100 / $result );
		}

		$questions_shown_count = count( $_POST['results'] ) - 1;

		if ( isset( $_POST['quiz_nonce'] ) && isset( $_POST['quizId'] ) ) {
			if ( ! wp_verify_nonce( $_POST['quiz_nonce'], 'sfwd-quiz-nonce-' . absint( $_POST['quiz'] ) . '-' . absint( $_POST['quizId'] ) . '-' . $user_id ) ) {
				return;
			}
		} elseif ( empty( $_SERVER['HTTP_REFERER'] ) ) {
			return;
		}

		$user_quiz_meta = array();
		if ( ! empty( $user_id ) ) {
			$user_quiz_meta = get_user_meta( $user_id, '_sfwd-quizzes', true );
			$user_quiz_meta = maybe_unserialize( $user_quiz_meta );

			if ( ! is_array( $user_quiz_meta ) ) {
				$user_quiz_meta = array();
			}
		}

		$quiz_post_settings = learndash_get_setting( $quiz_post_id );
		if ( ! is_array( $quiz_post_settings ) ) {
			$quiz_post_settings = array();
		}
		if ( ! isset( $quiz_post_settings['passingpercentage'] ) ) {
			$quiz_post_settings['passingpercentage'] = 0;
		}
		$passingpercentage = absint( $quiz_post_settings['passingpercentage'] );

		$pass      = ( $result >= $passingpercentage ) ? 1 : 0;
		$quiz_post = get_post( $quiz_post_id );

		$quizdata = array(
			'quiz'                => $quiz_post_id,
			'score'               => $score,
			'count'               => $questions_count,
			'question_show_count' => $questions_shown_count,
			'pass'                => $pass,
			'rank'                => '-',
			'time'                => time(),
			'pro_quizid'          => $quiz_pro_id,
			'course'              => $course_id,
			'lesson'              => $lesson_id,
			'topic'               => $topic_id,
			'points'              => absint( $points ),
			'total_points'        => absint( $total_points ),
			'percentage'          => $result,
			'timespent'           => $timespent,
			'has_graded'          => ( $has_graded ) ? true : false,
			'statistic_ref_id'    => absint( $statistic_ref_id ),
			'started'             => 0,
			'completed'           => 0,
		);

		// On the timestamps below we divide against 1000 because they were generated via JavaScript which uses milliseconds.
		if ( isset( $_POST['results']['comp']['quizStartTimestamp'] ) ) {
			$quizdata['started'] = intval( $_POST['results']['comp']['quizStartTimestamp'] / 1000 );
		}

		if ( isset( $_POST['results']['comp']['quizEndTimestamp'] ) ) {
			$quizdata['completed'] = intval( $_POST['results']['comp']['quizEndTimestamp'] / 1000 );
		}

		if ( ( isset( $quizdata['started'] ) ) && ( ! empty( $quizdata['started'] ) ) && ( isset( $quizdata['completed'] ) ) && ( ! empty( $quizdata['completed'] ) ) ) {
			$quiz_time_diff  = absint( $quizdata['completed'] ) - absint( $quizdata['started'] );
			$quiz_time_end   = time();
			$quiz_time_start = $quiz_time_end - $quiz_time_diff;

			$quizdata['started']   = $quiz_time_start;
			$quizdata['completed'] = $quiz_time_end;
		} else {
			$quizdata['started']   = 0;
			$quizdata['completed'] = 0;
		}

		if ( ! empty( $graded ) ) {
			$quizdata['graded'] = $graded;
		}

		$quizdata['ld_version'] = LEARNDASH_VERSION;

		$quizdata['quiz_key'] = $quizdata['completed'] . '_' . absint( $quiz_pro_id ) . '_' . absint( $quiz_post_id ) . '_' . absint( $course_id );

		if ( ! empty( $user_id ) ) {
			$user_quiz_meta[] = $quizdata;

			learndash_quiz_debug_log_message( 'calling update_user_meta()' );
			learndash_quiz_debug_log_message( 'quizdata<pre>' . print_r( $quizdata, true ) . '</pre>' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- It's okay, the second argument is true.

			update_user_meta( $user_id, '_sfwd-quizzes', $user_quiz_meta );
		}

		if ( ! empty( $course_id ) ) {
			$quizdata['course'] = get_post( $course_id );
		} else {
			$quizdata['course'] = 0;
		}

		if ( ! empty( $lesson_id ) ) {
			$quizdata['lesson'] = get_post( $lesson_id );
		} else {
			$quizdata['lesson'] = 0;
		}

		if ( ! empty( $topic_id ) ) {
			$quizdata['topic'] = get_post( $topic_id );
		} else {
			$quizdata['topic'] = 0;
		}

		$quizdata['questions'] = $questions;

		if ( ! empty( $user_id ) ) {
			/**
			 * Fires after the quiz is submitted
			 *
			 * @since 3.0.0
			 *
			 * @param array   $quiz_data    An array of quiz data.
			 * @param WP_User $current_user Current user object.
			 */
			do_action( 'learndash_quiz_submitted', $quizdata, get_user_by( 'id', $user_id ) );

			/**
			 * Changed in 2.6.0. If the quiz has essay type questions that are not
			 * auto-graded we don't send out the 'learndash_quiz_completed' action.
			 */
			$send_quiz_completed = true;
			if ( ( isset( $quizdata['has_graded'] ) ) && ( true === $quizdata['has_graded'] ) ) {
				if ( ( isset( $quizdata['graded'] ) ) && ( ! empty( $quizdata['graded'] ) ) ) {
					foreach ( $quizdata['graded'] as $grade_item ) {
						if ( ( isset( $grade_item['status'] ) ) && ( $grade_item['status'] !== 'graded' ) ) {
							$send_quiz_completed = false;
						}
					}
				}
			}

			if ( true === $send_quiz_completed ) {
				if ( ! empty( $course_id ) ) {
					$quiz_parent_post_id = 0;
					if ( ! empty( $topic_id ) ) {
						$quiz_parent_post_id = $topic_id;
					} elseif ( ! empty( $lesson_id ) ) {
						$quiz_parent_post_id = $lesson_id;
					}

					if ( ! empty( $quiz_parent_post_id ) ) {

						/**
						 * Filter to set all parent steps completed.
						 *
						 * @since 4.2.0
						 *
						 * @param boolean $set_all_steps_completed Whether to set all steps completed.
						 * @param int     $quiz_post_id            Quiz post ID.
						 * @param int     $user_id                 User ID.
						 * @param int     $course_id               Course ID.
						 */
						if ( apply_filters( 'learndash_complete_all_parent_steps', true, $quiz_post_id, $user_id, $course_id ) ) {
							if ( ! empty( $topic_id ) ) {
								if ( learndash_can_complete_step( $user_id, $topic_id, $course_id ) ) {
									learndash_process_mark_complete( $user_id, $topic_id, false, $course_id );
								}
							}
							if ( ! empty( $lesson_id ) ) {
								if ( learndash_can_complete_step( $user_id, $lesson_id, $course_id ) ) {
									learndash_process_mark_complete( $user_id, $lesson_id, false, $course_id );
								}
							}
						} else {
							if ( learndash_can_complete_step( $user_id, $quiz_parent_post_id, $course_id ) ) {
								learndash_process_mark_complete( $user_id, $quiz_parent_post_id, false, $course_id );
							}
						}
					} else {
						$all_quizzes_complete = true;
						$quizzes              = learndash_get_global_quiz_list( $course_id );
						if ( ! empty( $quizzes ) ) {
							foreach ( $quizzes as $quiz ) {
								if ( learndash_is_quiz_notcomplete( $user_id, array( $quiz->ID => 1 ), false, $course_id ) ) {
									$all_quizzes_complete = false;
									break;
								}
							}
						}
						if ( true === $all_quizzes_complete ) {
							learndash_process_mark_complete( $user_id, $course_id, false, $course_id );
						}
					}
				}

				/** This action is documented in includes/ld-users.php */
				do_action( 'learndash_quiz_completed', $quizdata, get_user_by( 'id', $user_id ) );
			} elseif ( defined( 'LEARNDASH_QUIZ_ESSAY_SUBMIT_COMPLETED' ) && LEARNDASH_QUIZ_ESSAY_SUBMIT_COMPLETED === true ) {
				/** This action is documented in includes/ld-users.php */
				do_action( 'learndash_quiz_completed', $quizdata, get_user_by( 'id', $user_id ) );
			}
		}

		$results[ $quiz_pro_id ]['quiz_result_settings'] = array(
			'showAverageResult'      => $quiz_pro->isShowAverageResult() ? 1 : 0,
			'showCategoryScore'      => $quiz_pro->isShowCategoryScore() ? 1 : 0,
			'showRestartQuizButton'  => $quiz_pro->isBtnRestartQuizHidden() ? 0 : 1,
			'showResultPoints'       => $quiz_pro->isHideResultPoints() ? 0 : 1,
			'showResultQuizTime'     => $quiz_pro->isHideResultQuizTime() ? 0 : 1,
			'showViewQuestionButton' => $quiz_pro->isBtnViewQuestionHidden() ? 0 : 1,
		);
		/** This filter is documented in includes/lib/wp-pro-quiz/lib/view/WpProQuiz_View_FrontQuiz.php */
		$results[ $quiz_pro_id ]['showContinueButton'] = apply_filters(
			// cspell:disable-next-line.
			'show_quiz_continue_buttom_on_fail', // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- It is what it is.
			false,
			$quizdata['quiz']
		) ? 1 : 0;

		/**
		 * Filters settings of the completed quiz results.
		 *
		 * @param array $quiz_result_settings An array of quiz result settings data.
		 * @param mixed $quiz_data            An array of quiz data.
		 */
		$results[ $quiz_pro_id ]['quiz_result_settings'] = apply_filters( 'learndash_quiz_completed_result_settings', $results[ $quiz_pro_id ]['quiz_result_settings'], $quizdata );

		echo wp_json_encode( $results );
	}

	/**
	 * Returns an array of quizzes in the string format of "$quiz_id - $quiz_name"
	 *
	 * @since 2.1.0
	 *
	 * @return array  $list  String of $q->getId() . ' - ' . $q->getName()
	 */
	public static function get_quiz_list() {
		$quizzes_list = array();

		global $wpdb;

		$quiz_items = $wpdb->get_results( $wpdb->prepare( 'SELECT id, name FROM ' . LDLMS_DB::get_table_name( 'quiz_master' ) . ' ORDER BY %s ', 'id' ) );
		if ( ! empty( $quiz_items ) ) {
			foreach ( $quiz_items as $q ) {
				$quizzes_list[ $q->id ] = $q->id . ' - ' . $q->name;
			}
		}
		return $quizzes_list;
	}



	/**
	 * Echoes the HTML with inline javascript that contains the JSON representation of the certificate details and continue link details
	 *
	 * @since 2.1.0
	 *
	 * @param int $pro_quiz_id WPProQuiz ID.
	 */
	public static function certificate_details( $pro_quiz_id = null ) {

		$quiz_post_id = 0;

		if ( is_null( $pro_quiz_id ) ) {
			global $post;
			if ( ( $post instanceof WP_Post ) && ( $post->post_type == 'sfwd-quiz' ) ) {
				$pro_quiz_id = $post->ID;
			}
		} else {
			if ( is_a( $pro_quiz_id, 'WpProQuiz_Model_Quiz' ) ) {
				$pro_quiz     = $pro_quiz_id;
				$pro_quiz_id  = $pro_quiz->getId();
				$quiz_post_id = $pro_quiz->getPostId();
			} else {
				$quiz_post_id = learndash_get_quiz_id_by_pro_quiz_id( $pro_quiz_id );
			}

			if ( ! empty( $quiz_post_id ) ) {
				$quiz_post = get_post( $quiz_post_id );
				if ( ( $quiz_post instanceof WP_Post ) && ( $quiz_post->post_type == 'sfwd-quiz' ) ) {
					$quiz_post_id = $quiz_post->ID;
				}
			}
		}

		if ( ! empty( $quiz_post_id ) ) {
			echo '<script>';
			echo 'var certificate_details = ' . wp_json_encode( learndash_certificate_details( $quiz_post_id ) ) . ';';
			echo '</script>';

			echo '<script>';
			echo 'var certificate_pending = "' .
				SFWD_LMS::get_template(
					'learndash_quiz_messages',
					array(
						'quiz_post_id' => $quiz_post_id,
						'context'      => 'quiz_certificate_pending_message',
						'message'      => sprintf(
							// translators: questions.
							esc_html_x( 'Certificate Pending - %s still need to be graded, please check your profile for the status', 'placeholder: questions', 'learndash' ),
							learndash_get_custom_label( 'questions' )
						),
					)
				) . '";';
			echo '</script>';

			// Continue link will appear through javascript.
			echo '<script>';
				echo "var continue_details ='" . learndash_quiz_continue_link( $quiz_post_id ) . "';"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
			echo '</script>';
		}
	}

	/**
	 * Returns the certificate link appended to input HTML content if the Post ID is set, else it only returns the input HTML content
	 *
	 * @since 2.1.0
	 *
	 * @param  string $content HTML.
	 * @param  mixed  $pro_quiz (integer) WPProQuiz ID, (object) WpProQuiz_Model_Quiz.
	 *
	 * @return string HTML $content or $content concatenated with the certificate link
	 */
	public static function certificate_link( $content, $pro_quiz = null ) {
		$quiz_post_id = null;
		$pro_quiz_id  = null;

		if ( ! is_null( $pro_quiz ) ) {
			if ( is_a( $pro_quiz, 'WpProQuiz_Model_Quiz' ) ) {
				$pro_quiz_id  = $pro_quiz->getId();
				$quiz_post_id = $pro_quiz->getPostId();
			} else {
				$pro_quiz_id = absint( $pro_quiz );
			}
		}

		if ( empty( $quiz_post_id ) ) {
			if ( empty( $pro_quiz_id ) ) {
				$post_id = get_the_ID();
				if ( ! empty( $post_id ) ) {
					$quiz_post = get_post( $post_id );
					if ( ( $quiz_post instanceof WP_Post ) && ( $quiz_post->post_type == 'sfwd-quiz' ) ) {
						$quiz_post_id = $quiz_post->ID;
					}
				}
			}

			if ( empty( $quiz_post_id ) ) {
				$quiz_post_id = learndash_get_quiz_id_by_pro_quiz_id( $pro_quiz_id );
				if ( ! empty( $quiz_post_id ) ) {
					$quiz_post = get_post( $quiz_post_id );
					if ( ( $quiz_post instanceof WP_Post ) && ( $quiz_post->post_type == 'sfwd-quiz' ) ) {
						$quiz_post_id = $quiz_post->ID;
					}
				}
			}
		} else {
			$quiz_post = get_post( $quiz_post_id );
			if ( ( $quiz_post instanceof WP_Post ) && ( $quiz_post->post_type == 'sfwd-quiz' ) ) {
				$quiz_post_id = $quiz_post->ID;
			} else {
				$quiz_post_id = 0;
			}
		}

		if ( ! empty( $quiz_post_id ) ) {
			$cd = learndash_certificate_details( $quiz_post_id );
			if ( ( ! empty( $cd ) ) && ( isset( $cd['certificateLink'] ) ) && ( ! empty( $cd['certificateLink'] ) ) ) {
				$user_id = get_current_user_id();
				/** This filter is documented in includes/ld-certificates.php */
				$ret      = "<a class='btn-blue' href='" . $cd['certificateLink'] . "' target='_blank'>" . apply_filters(
					'ld_certificate_link_label',
					SFWD_LMS::get_template(
						'learndash_quiz_messages',
						array(
							'quiz_post_id' => $quiz_post_id,
							'context'      => 'quiz_certificate_button_label',
							'message'      => esc_html__( 'PRINT YOUR CERTIFICATE', 'learndash' ),
						)
					),
					$user_id,
					$quiz_post_id
				) . '</a>';
				$content .= $ret;
			}
		}

		return $content;
	}



	/**
	 * Returns the HTML of the add or edit page for the current quiz. If advanced quizzes are disabled, it returns an empty string.
	 *
	 * @since 2.1.0
	 *
	 * @return string
	 */
	public static function edithtml() {
		global $pagenow, $post;
		$_post = array( '1' );

		if ( ! empty( $_GET['templateLoadId'] ) ) {
			$_post = $_GET;
		}

		if ( $pagenow == 'post-new.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] == 'sfwd-quiz' || $pagenow == 'post.php' && ! empty( $_GET['post'] ) && get_post( $_GET['post'] )->post_type == 'sfwd-quiz' ) {
			// To fix issues with plugins using get_current_screen.
			$screen_file = ABSPATH . '/wp-admin/includes/screen.php';
			require_once $screen_file;

			$quizId  = 0;
			$post_id = 0;
			if ( ! empty( $_GET['post'] ) ) {
				$post_id = intval( $_GET['post'] );
				$quizId  = intval( learndash_get_setting( $post_id, 'quiz_pro', true ) );

				/** This filter is documented in includes/admin/classes-posts-edits/class-learndash-admin-quiz-edit.php */
				if ( apply_filters( 'learndash_disable_advance_quiz', false, $post_id ) ) {
					return '';
				}
			} else {
				global $post;
				if ( ( is_a( $post, 'WP_Post' ) ) && ( $post->post_type == 'sfwd-quiz' ) ) {
					$post_id = $post->ID;
				}
			}

			$pro_quiz = new WpProQuiz_Controller_Quiz();

			ob_start();
			$pro_quiz->route(
				array(
					'action'  => 'addEdit',
					'quizId'  => $quizId,
					'post_id' => $post_id,
				),
				$_post
			);
			$return = ob_get_clean();

			return $return;
		}

		return '';
	}



	/**
	 * Routes to the WpProQuiz_Controller_Quiz controller to output the add or edit page for quizzes if not auto saving, post id is set,
	 * and the current user has permissions to add or edit quizzes. If there is an available template to load, WordPress redirects to
	 * the proper URL.
	 *
	 * @since 2.1.0
	 *
	 * @param int $post_id Post ID.
	 */
	public static function edit_process( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( empty( $post_id ) || empty( $_POST['post_type'] ) ) {
			return '';
		}

		// Check permissions.
		if ( 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}

		$post = get_post( $post_id );

		/** This filter is documented in includes/admin/classes-posts-edits/class-learndash-admin-quiz-edit.php */
		if ( 'sfwd-quiz' != $post->post_type || empty( $_POST['form'] ) || ! empty( $_POST['disable_advance_quiz_save'] ) || apply_filters( 'learndash_disable_advance_quiz', false, $post ) ) {
			return;
		}

		$quizId   = intval( learndash_get_setting( $post_id, 'quiz_pro', true ) );
		$pro_quiz = new WpProQuiz_Controller_Quiz();
		$pro_quiz->route(
			array(
				'action'  => 'addUpdateQuiz',
				'quizId'  => $quizId,
				'post_id' => $post_id,
			)
		);

		if ( ! empty( $_POST['templateLoad'] ) && ! empty( $_POST['templateLoadId'] ) ) {
			$url = admin_url( 'post.php?post=' . $post_id . '&action=edit' ) . '&templateLoad=' . rawurlencode( $_POST['templateLoad'] ) . '&templateLoadId=' . $_POST['templateLoadId'];
			learndash_safe_redirect( $url );
		}
	}



	/**
	 * Returns a MD5 checksum on a concatenated string comprised of user id, question id, and pos
	 *
	 * @since 2.1.0
	 *
	 * @param int $question_id Question ID.
	 * @param int $pos         Position.
	 *
	 * @return string MD5 Checksum
	 */
	public static function datapos( $question_id, $pos ) {
		$pos = intval( $pos );

		return md5( get_current_user_id() . $question_id . $pos );
	}



	/**
	 * Returns an array of MD5 Checksums on a concatenated string comprised of user id, question id, and i, where the array size is count and i is incremented from 0 for each array element
	 *
	 * @since 2.1.0
	 *
	 * @param  int $question_id Question ID.
	 * @param  int $count       Count.
	 *
	 * @return array    Array of MD5 checksum strings
	 */
	public static function datapos_array( $question_id, $count ) {
		$datapos_array = array();
		$user_id       = get_current_user_id();

		for ( $i = 0; $i < $count; $i++ ) {
			$datapos_array[ $i ] = md5( $user_id . $question_id . $i );
		}

		return $datapos_array;
	}

	/**
	 * Show Modal Window
	 *
	 * @since 2.3.0
	 */
	public static function showModalWindow() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Better to keep it this way.
		static $show_only_once = false;

		/**
		 * Added for LEARNDASH-2754 to prevent loading the inline CSS when inside
		 * the Gutenberg editor publish/update. Need a better way to handle this.
		 */
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}

		if ( ! $show_only_once ) {
			$show_only_once = true;
			?>
			<style>
			.wpProQuiz_blueBox {
				padding: 20px;
				background-color: rgb(223, 238, 255);
				border: 1px dotted;
				margin-top: 10px;
			}
			.categoryTr th {
				background-color: #F1F1F1;
			}
			.wpProQuiz_modal_backdrop {
				background: #000;
				opacity: 0.7;
				top: 0;
				bottom: 0;
				right: 0;
				left: 0;
				position: fixed;
				z-index: 159900;
			}
			.wpProQuiz_modal_window {
				position: fixed;
				background: #FFF;
				top: 40px;
				bottom: 40px;
				left: 40px;
				right: 40px;
				z-index: 160000;
			}
			.wpProQuiz_actions {
				display: none;
				padding: 2px 0 0;
			}

			.mobile .wpProQuiz_actions {
				display: block;
			}

			tr:hover .wpProQuiz_actions {
				display: block;
			}
			</style>
			<div id="wpProQuiz_user_overlay" style="display: none;">
				<div class="wpProQuiz_modal_window" style="padding: 20px; overflow: scroll;">
					<input type="button" value="<?php esc_html_e( 'Close', 'learndash' ); ?>" class="button-primary" style=" position: fixed; top: 48px; right: 59px; z-index: 160001;" id="wpProQuiz_overlay_close">

					<div id="wpProQuiz_user_content" style="margin-top: 20px;"></div>

					<div id="wpProQuiz_loadUserData" class="wpProQuiz_blueBox" style="background-color: #F8F5A8; display: none; margin: 50px;">
						<img alt="load" src="<?php echo admin_url( '/images/wpspin_light.gif' ); ?>" />
						<?php esc_html_e( 'Loading', 'learndash' ); ?>
					</div>
				</div>
				<div class="wpProQuiz_modal_backdrop"></div>
			</div>
			<?php
		}
	}

	/**
	 * Quiz Content
	 *
	 * @since 2.3.0
	 *
	 * @param string $quiz_content Quiz Content.
	 */
	public function learndash_quiz_content( $quiz_content ) {
		return $quiz_content;
	}


}

new LD_QuizPro();

/**
 * Gets the list of quizzes not associated with any course.
 *
 * This function will query and return all global.
 * A GLOBAL Quizzes is:
 * 1. Quizzes not associated with a Course.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.6.0
 *
 * @param boolean $bypass_transient Optional. Whether to bypass the transient cache. Default false.
 *
 * @return array An array of quiz IDs.
 */
function learndash_get_non_course_qizzes( $bypass_transient = false ) {
	global $wpdb;

	$global_quiz_ids = array();

	$transient_key = 'learndash_global_quiz_ids';
	if ( ! $bypass_transient ) {
		$global_quiz_ids_transient = LDLMS_Transients::get( $transient_key );
	} else {
		$global_quiz_ids_transient = false;
	}

	if ( false === $global_quiz_ids_transient ) {

		$global_quiz_ids_query_str = "SELECT posts.ID FROM {$wpdb->posts} as posts
			LEFT JOIN {$wpdb->postmeta} as postmeta1 ON posts.ID = postmeta1.post_id AND postmeta1.meta_key LIKE 'ld_course%'
			LEFT JOIN {$wpdb->postmeta} as postmeta2 ON posts.ID = postmeta2.post_id AND postmeta2.meta_key = 'course_id'
			WHERE posts.post_type = 'sfwd-quiz'
				AND ( postmeta1.post_id IS NULL AND postmeta2.post_id IS NULL )";

		$global_quiz_ids = $wpdb->get_col( $global_quiz_ids_query_str );
		LDLMS_Transients::set( $transient_key, $global_quiz_ids, MINUTE_IN_SECONDS );
	} else {
		$global_quiz_ids = $global_quiz_ids_transient;
	}

	return $global_quiz_ids;
}

/**
 * Gets all the open quizzes.
 *
 * This function will query and return all open Quizzes.
 * An OPEN Quiz is:
 * 1. Not associated with a Course.
 * 2. The Quiz setting "Only registered users are allowed to start the quiz" is NOT set.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.6.0
 *
 * @param boolean $bypass_transient Optional. Whether to bypass the transient cache. Default false.
 *
 * @return array An array of Quiz IDs.
 */
function learndash_get_open_quizzes( $bypass_transient = false ) {
	global $wpdb;

	$open_quiz_ids = array();

	$transient_key = 'learndash_global_quiz_ids';
	if ( ! $bypass_transient ) {
		$open_quiz_ids_transient = LDLMS_Transients::get( $transient_key );
	} else {
		$open_quiz_ids_transient = false;
	}

	if ( false === $open_quiz_ids_transient ) {

		$global_quiz_ids = learndash_get_non_course_qizzes(); // cspell:disable-line.
		if ( ! empty( $global_quiz_ids ) ) {
			$open_quiz_ids_query_str = "SELECT posts.ID FROM {$wpdb->posts} as posts
				LEFT JOIN {$wpdb->postmeta} as postmeta1 ON posts.ID = postmeta1.post_id AND postmeta1.meta_key = 'quiz_pro_id'
				LEFT JOIN " . LDLMS_DB::get_table_name( 'quiz_master' ) . " as quiz_master ON postmeta1.meta_value = quiz_master.id
				WHERE posts.post_type = 'sfwd-quiz'
					AND posts.ID IN (" . implode( ',', $global_quiz_ids ) . ')
					AND quiz_master.start_only_registered_user = 0';

			$open_quiz_ids = $wpdb->get_col( $open_quiz_ids_query_str );
			LDLMS_Transients::set( $transient_key, $open_quiz_ids, MINUTE_IN_SECONDS );
		}
	} else {
		$open_quiz_ids = $open_quiz_ids_transient;
	}

	return $open_quiz_ids;
}


$quiz_debug_error_log_file = '';
global $quiz_debug_error_log_file;

/**
 * Quiz Debug Log Init
 *
 * @since 3.2.3
 *
 * @param integer $quiz_id Quiz ID.
 */
function learndash_quiz_debug_log_init( $quiz_id = 0 ) {
	global $quiz_debug_error_log_file;

	if ( defined( 'LEARNDASH_QUIZ_DEBUG' ) && LEARNDASH_QUIZ_DEBUG ) {
		$user_id      = get_current_user_id();
		$ld_debug_dir = dirname( LEARNDASH_TEMPLATES_DIR ) . '/debug';

		if ( ! file_exists( $ld_debug_dir ) ) {
			if ( ! is_writable( dirname( $ld_debug_dir ) ) ) {
				return false;
			}

			if ( wp_mkdir_p( $ld_debug_dir ) === false ) {
				return false;
			}
		}

		learndash_put_directory_index_file( trailingslashit( $ld_debug_dir ) . 'index.php' );

		Learndash_Admin_File_Download_Handler::register_file_path(
			'learndash-debug',
			$ld_debug_dir
		);

		Learndash_Admin_File_Download_Handler::try_to_protect_file_path(
			$ld_debug_dir
		);

		$date_time                 = learndash_adjust_date_time_display( time(), 'Ymd' );
		$quiz_debug_error_log_file = trailingslashit( $ld_debug_dir ) . 'ld_debug_quiz_' . $date_time . '_' . absint( $user_id ) . '_' . absint( $quiz_id ) . '.log';
		return $quiz_debug_error_log_file;
	}
}

/**
 * Quiz Debug Log Message
 *
 * @since 3.2.3
 *
 * @param string $message Message.
 */
function learndash_quiz_debug_log_message( $message = '' ) {
	global $quiz_debug_error_log_file;

	if ( defined( 'LEARNDASH_QUIZ_DEBUG' ) && LEARNDASH_QUIZ_DEBUG ) {
		if ( ( ! empty( $message ) ) && ( ! empty( $quiz_debug_error_log_file ) ) ) {
			if ( ! file_exists( $quiz_debug_error_log_file ) ) {
				if ( ! is_writable( dirname( $quiz_debug_error_log_file ) ) ) {
					return false;
				}
			} else {
				if ( ! is_writable( $quiz_debug_error_log_file ) ) {
					return false;
				}
			}

			$user_id   = get_current_user_id();
			$date_time = learndash_adjust_date_time_display( time(), 'Y-m-d H:i:s' );
			file_put_contents( $quiz_debug_error_log_file, $date_time . ' - ' . $user_id . ' - ' . $message . "\r\n", FILE_APPEND ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents -- It's okay here.
		}
	}
}


/**
 * Utility function to fetch the WPProQuiz Question from ID.
 *
 * @since 3.5.0
 *
 * @param int $question_pro_id The WPProQuiz Question ID.
 *
 * @return WpProQuiz_Model_Question|null
 */
function fetchQuestionModel( $question_pro_id = 0 ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Better to keep it this way.
	if ( ! empty( $question_pro_id ) ) {
		$question_mapper = new WpProQuiz_Model_QuestionMapper();
		return $question_mapper->fetch( $question_pro_id );
	}

	return null;
}

// phpcs:enable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase -- Back to good code.
