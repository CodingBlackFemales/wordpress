<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WpProQuiz_Controller_Admin {

	protected $_ajax;

	public function __construct() {

		$this->_ajax = new WpProQuiz_Controller_Ajax();

		$this->_ajax->init();

		//deprecated - use WpProQuiz_Controller_Ajax
		add_action( 'wp_ajax_wp_pro_quiz_update_sort', array( $this, 'updateSort' ) );
		add_action( 'wp_ajax_wp_pro_quiz_load_question', array( $this, 'loadQuestions' ) );

		add_action( 'wp_ajax_wp_pro_quiz_reset_lock', array( $this, 'resetLock' ) );

		add_action( 'wp_ajax_wp_pro_quiz_load_toplist', array( $this, 'adminToplist' ) );

		add_action( 'wp_ajax_wp_pro_quiz_completed_quiz', array( $this, 'completedQuiz' ) );
		add_action( 'wp_ajax_nopriv_wp_pro_quiz_completed_quiz', array( $this, 'completedQuiz' ) );

		add_action( 'wp_ajax_wp_pro_quiz_cookie_save_quiz', array( $this, 'cookieSaveQuiz' ) );
		add_action( 'wp_ajax_nopriv_wp_pro_quiz_cookie_save_quiz', array( $this, 'cookieSaveQuiz' ) );

		add_action( 'wp_ajax_wp_pro_quiz_check_lock', array( $this, 'quizCheckLock' ) );
		add_action( 'wp_ajax_nopriv_wp_pro_quiz_check_lock', array( $this, 'quizCheckLock' ) );

		//0.19
		add_action( 'wp_ajax_wp_pro_quiz_add_toplist', array( $this, 'addInToplist' ) );
		add_action( 'wp_ajax_nopriv_wp_pro_quiz_add_toplist', array( $this, 'addInToplist' ) );

		add_action( 'wp_ajax_wp_pro_quiz_show_front_toplist', array( $this, 'showFrontToplist' ) );
		add_action( 'wp_ajax_nopriv_wp_pro_quiz_show_front_toplist', array( $this, 'showFrontToplist' ) );

		add_action( 'wp_ajax_wp_pro_quiz_load_quiz_data', array( $this, 'loadQuizData' ) );
		add_action( 'wp_ajax_nopriv_wp_pro_quiz_load_quiz_data', array( $this, 'loadQuizData' ) );

		add_action( 'admin_menu', array( $this, 'register_page' ) );
	}

	public function loadQuizData() {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} else {
			$user_id = 0;
		}

		if ( isset( $_POST['quizId'] ) ) {
			$id = absint( $_POST['quizId'] );
		} else {
			$id = 0;
		}

		if ( isset( $_POST['quiz'] ) ) {
			$quiz_post_id = absint( $_POST['quiz'] );
		} else {
			$quiz_post_id = 0;
		}

		if ( ! wp_verify_nonce( $_POST['quiz_nonce'], 'sfwd-quiz-nonce-' . $quiz_post_id . '-' . $id . '-' . $user_id ) ) {
			error_log( 'nonce failed' );
			die();
		}

		$q = new WpProQuiz_Controller_Quiz();

		echo json_encode( $q->loadQuizData() );

		exit;
	}

	public function adminToplist() {
		if ( ( isset( $_POST['nonce'] ) ) && ( ! empty( $_POST['nonce'] ) ) && ( wp_verify_nonce( $_POST['nonce'], 'learndash-wpproquiz-toplist' ) ) ) {
			$t = new WpProQuiz_Controller_Toplist();
			$t->route();
		}

		exit;
	}

	public function showFrontToplist() {
		if ( ( isset( $_POST['nonce'] ) ) && ( ! empty( $_POST['nonce'] ) ) && ( wp_verify_nonce( $_POST['nonce'], 'learndash-wpproquiz-toplist' ) ) ) {
			$t = new WpProQuiz_Controller_Toplist();

			$t->showFrontToplist();
		}
		exit;
	}

	public function addInToplist() {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} else {
			$user_id = 0;
		}

		if ( isset( $_POST['quizId'] ) ) {
			$id = absint( $_POST['quizId'] );
		} else {
			$id = 0;
		}

		if ( isset( $_POST['quiz'] ) ) {
			$quiz_post_id = absint( $_POST['quiz'] );
		} else {
			$quiz_post_id = 0;
		}

		if ( ! wp_verify_nonce( $_POST['quiz_nonce'], 'sfwd-quiz-nonce-' . $quiz_post_id . '-' . $id . '-' . $user_id ) ) {
			die();
		}

		$t = new WpProQuiz_Controller_Toplist();

		$t->addInToplist();

		exit;
	}

	public function resetLock() {
		if ( ( isset( $_POST['nonce'] ) ) && ( ! empty( $_POST['nonce'] ) ) && ( wp_verify_nonce( $_POST['nonce'], 'learndash-wpproquiz-reset-lock' ) ) ) {
			if ( ( isset( $_GET['post'] ) ) && ( ! isset( $_GET['id'] ) ) ) {
				$_GET['id'] = get_post_meta( $_GET['post'], 'quiz_pro_id', true );
			}

			$c = new WpProQuiz_Controller_Quiz();
			$c->route();
		}
	}

	public function quizCheckLock() {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} else {
			$user_id = 0;
		}

		if ( isset( $_POST['quizId'] ) ) {
			$id = absint( $_POST['quizId'] );
		} else {
			$id = 0;
		}

		if ( isset( $_POST['quiz'] ) ) {
			$quiz_post_id = absint( $_POST['quiz'] );
		} else {
			$quiz_post_id = 0;
		}

		if ( ! wp_verify_nonce( $_POST['quiz_nonce'], 'sfwd-quiz-nonce-' . $quiz_post_id . '-' . $id . '-' . $user_id ) ) {
			die();
		}

		$quizController = new WpProQuiz_Controller_Quiz();

		echo json_encode( $quizController->isLockQuiz( $_POST['quizId'] ) );

		exit;
	}

	public function updateSort() {
		if ( ( isset( $_POST['nonce'] ) ) && ( ! empty( $_POST['nonce'] ) ) && ( wp_verify_nonce( $_POST['nonce'], 'wpProQuiz_nonce' ) ) ) {
			$c = new WpProQuiz_Controller_Question();
			$c->route();
		}
	}

	public function loadQuestions() {
		if ( ( isset( $_POST['nonce'] ) ) && ( ! empty( $_POST['nonce'] ) ) && ( wp_verify_nonce( $_POST['nonce'], 'wpProQuiz_nonce' ) ) ) {
			$c = new WpProQuiz_Controller_Question();
			$c->route();
		}
	}

	public function cookieSaveQuiz() {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} else {
			wp_send_json_error( array( 'message' => esc_html__( 'Your login has expired. Please log in and reload this page to continue.', 'learndash' ) ), 403 );
		}

		if ( isset( $_POST['quiz_nonce'] ) ) {
			$quiz_nonce = esc_attr( $_POST['quiz_nonce'] );
		} else {
			wp_send_json_error( array( 'message' => esc_html__( 'Your login has expired. Please log in and reload this page to continue.', 'learndash' ) ), 403 );
		}

		if ( isset( $_POST['quizId'] ) ) {
			$id = absint( $_POST['quizId'] );
		} else {
			$id = 0;
		}

		if ( isset( $_POST['quiz'] ) ) {
			$quiz_post_id = absint( $_POST['quiz'] );
		} else {
			$quiz_post_id = 0;
		}

		if ( isset( $_POST['course_id'] ) ) {
			$course_id = absint( $_POST['course_id'] );
		} else {
			$course_id = 0;
		}

		if ( ( empty( $quiz_nonce ) ) || ( ! wp_verify_nonce( $quiz_nonce, 'sfwd-quiz-nonce-' . $quiz_post_id . '-' . $id . '-' . $user_id ) ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Your login has expired. Please log in and reload this page to continue.', 'learndash' ) ), 403 );
		}

		/********************************************************
		 * New Quiz Activity save logic
		 ********************************************************/

		if ( ( isset( $_POST['results'] ) ) && ( ! empty( $_POST['results'] ) ) ) {
			$results = (array) json_decode( stripslashes( $_POST['results'] ), true );

			$quiz_started = 0;
			if ( isset( $_POST['quiz_started'] ) ) {
				$quiz_started = absint( $_POST['quiz_started'] / 1000 );
			}

			//error_log( __FUNCTION__ . ': quiz_post_id[' . $quiz_post_id . '] quiz_started[' . $quiz_started . '] results<pre>' . print_r( $results, true ) . '</pre>' );

			//error_log( __FUNCTION__ . ': _COOKIE<pre>' . print_r( $_COOKIE, true ) . '</pre>' );

			if ( ( ! empty( $quiz_post_id ) ) && ( ! empty( $quiz_started ) ) ) {
				$success = LDLMS_User_Quiz_Resume::update_user_quiz_resume_metadata( $user_id, $quiz_post_id, $course_id, $quiz_started, $results );
			} else {
				// we return success so the browser does not show failure.
				$success = true;
			}
		}

		if ( $success ) {
			wp_send_json_success( $results );
		} else {
			wp_send_json_error(
				array(
					'message' => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( '%s data could not be saved to the server. Please reload the page and try again. If this error persists, please contact support.', 'placeholder: Quiz', 'learndash' ),
						esc_html( LearnDash_Custom_Label::get_label( 'quiz' ) )
					),
				)
			);
		}
	}
	public function completedQuiz() {

		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} else {
			$user_id = 0;
		}

		if ( isset( $_POST['quizId'] ) ) {
			$id = absint( $_POST['quizId'] );
		} else {
			$id = 0;
		}

		if ( isset( $_POST['quiz'] ) ) {
			$quiz_post_id = absint( $_POST['quiz'] );
		} else {
			$quiz_post_id = 0;
		}

		learndash_quiz_debug_log_init( $quiz_post_id );
		learndash_quiz_debug_log_message( 'Browser version: ' . $_SERVER['HTTP_USER_AGENT'] );
		learndash_quiz_debug_log_message( '---------------------------------' );
		learndash_quiz_debug_log_message( 'in ' . __FUNCTION__ );
		learndash_quiz_debug_log_message( '_POST<pre>' . print_r( $_POST, true ) . '</pre>' );

		learndash_quiz_debug_log_message( 'user_id ' . $user_id );
		learndash_quiz_debug_log_message( 'quiz id ' . $id );
		learndash_quiz_debug_log_message( 'quiz_post_id ' . $quiz_post_id );

		if ( ! wp_verify_nonce( $_POST['quiz_nonce'], 'sfwd-quiz-nonce-' . $quiz_post_id . '-' . $id . '-' . $user_id ) ) {
			learndash_quiz_debug_log_message( 'quiznonce verify failed' );
			die();
		}

		// First we unpack the $_POST['results'] string
		if ( ( isset( $_POST['results'] ) ) && ( ! empty( $_POST['results'] ) ) && ( is_string( $_POST['results'] ) ) ) {
			$_POST['results'] = json_decode( stripslashes( $_POST['results'] ), true );
			learndash_quiz_debug_log_message( '_POST[results]<pre>' . print_r( $_POST['results'], true ) . '</pre>' );
		}

		// LD 2.4.3 - Change in logic. Instead of accepting the values for points, correct etc from JS we now pass the 'results' array on the complete quiz
		// AJAX action. This now let's us verify the points, correct answers etc. as each have a unique nonce.
		$total_awarded_points  = 0;
		$total_possible_points = 0;
		$total_correct         = 0;

		// Remove any stored activity user meta used for quiz resume as it is no longer needed.
		if ( isset( $_POST['course'] ) ) {
			$course_post_id = absint( $_POST['course'] );
		} else {
			$course_post_id = 0;
		}

		// If the results is not present then abort.
		if ( ! isset( $_POST['results'] ) ) {
			return array(
				'text'  => esc_html__( 'An error has occurred.', 'learndash' ),
				'clear' => true,
			);
		}

		if ( ! isset( $_POST['results']['comp'] ) ) {
			return array(
				'text'  => esc_html__( 'An error has occurred.', 'learndash' ),
				'clear' => true,
			);
		}

		learndash_quiz_debug_log_message( 'Verifying submitted results' );

		// Loop over the 'results' items. We verify and tally the points+correct counts as well as the student response 'data'. When we get to the 'comp' results element
		// we set the award points and correct as well as determine the total possible points.
		// @TODO Need to test how this works with variabel question quizzes.
		foreach ( $_POST['results'] as $r_idx => $result ) {
			learndash_quiz_debug_log_message( '[' . $r_idx . '] result<pre>' . print_r( $result, true ) . '</pre>' );

			if ( $r_idx !== 'comp' ) {
				$question_mapper = new WpProQuiz_Model_QuestionMapper();
				$question        = $question_mapper->fetchById( intval( $r_idx ), null );
				if ( ! is_a( $question, 'WpProQuiz_Model_Question' ) ) {
					continue;
				}

				// Validate the Points items.
				if ( ( isset( $result['p_nonce'] ) ) && ( ! empty( $result['p_nonce'] ) ) ) {
					$points_array = array(
						'points'         => intval( $result['points'] ),
						'correct'        => intval( $result['correct'] ),
						'possiblePoints' => intval( $result['possiblePoints'] ),
					);
					if ( $points_array['correct'] === false ) {
						$points_array['correct'] = 0;
					} elseif ( $points_array['correct'] === true ) {
						$points_array['correct'] = 1;
					}
					$points_str = maybe_serialize( $points_array );

					$points_nonce = 'ld_quiz_pnonce' . $user_id . '_' . $id . '_' . $quiz_post_id . '_' . $r_idx . '_' . $points_str;
					if ( ! wp_verify_nonce( $result['p_nonce'], $points_nonce ) ) {
						learndash_quiz_debug_log_message( 'invalid points nonce (p_nonce). Clearing points values.' );
						learndash_quiz_debug_log_message( 'p_nonce[' . $result['a_nonce'] . ' points_nonce[' . $points_nonce . ']' );

						$_POST['results'][ $r_idx ]['points']         = 0;
						$_POST['results'][ $r_idx ]['correct']        = 0;
						$_POST['results'][ $r_idx ]['possiblePoints'] = 0;

						// Set the possible points from the question.
						if ( is_a( $question, 'WpProQuiz_Model_Question' ) ) {
							$_POST['results'][ $r_idx ]['possiblePoints'] = $question->getPoints();
						}
					}
				} else {
					learndash_quiz_debug_log_message( 'missing/empty answer p_nonce. Clearing points values.' );
					$_POST['results'][ $r_idx ]['points']         = 0;
					$_POST['results'][ $r_idx ]['correct']        = 0;
					$_POST['results'][ $r_idx ]['possiblePoints'] = 0;

					// Set the possible points from the question.
					$question_mapper = new WpProQuiz_Model_QuestionMapper();
					$question        = $question_mapper->fetchById( intval( $r_idx ), null );
					if ( is_a( $question, 'WpProQuiz_Model_Question' ) ) {
						$_POST['results'][ $r_idx ]['possiblePoints'] = $question->getPoints();
					}
				}

				$total_awarded_points  += intval( $_POST['results'][ $r_idx ]['points'] );
				$total_possible_points += intval( $_POST['results'][ $r_idx ]['possiblePoints'] );
				$total_correct         += $_POST['results'][ $r_idx ]['correct'];

				// Validate the Answer items.
				if ( ( isset( $result['a_nonce'] ) ) && ( ! empty( $result['a_nonce'] ) ) ) {
					global $learndash_completed_question;
					$learndash_completed_question = $question;
					
					$response_str = maybe_serialize(
						array_map(
							function ( $array_item ) {
								global $learndash_completed_question;
								if ( is_string( $array_item ) ) {
									/** This filter is documented in includes/quiz/ld-quiz-pro.php */
									$question_legacy_sanitize_scheme = apply_filters( 'learndash_quiz_question_legacy_sanitize_scheme', false, $array_item, $learndash_completed_question );

									if ( ! $question_legacy_sanitize_scheme ) {
										$array_item = esc_attr( wp_unslash( trim( $array_item ) ) );
									} else {
										$array_item = trim( $array_item );
									}
								}
								return $array_item;
							},
							$result['data']
						)
					);
					unset( $learndash_completed_question );

					$answers_nonce = 'ld_quiz_anonce' . $user_id . '_' . $id . '_' . $quiz_post_id . '_' . $r_idx . '_' . $response_str;
					if ( ! wp_verify_nonce( $result['a_nonce'], $answers_nonce ) ) {
						learndash_quiz_debug_log_message( 'invalid answer a_nonce. Clearing answer/response values.' );
						learndash_quiz_debug_log_message( 'a_nonce[' . $result['a_nonce'] . ' answers_nonce[' . $answers_nonce . ']' );
						$_POST['results'][ $r_idx ]['data'] = array();
					}
				} else {
					learndash_quiz_debug_log_message( 'missing/empty answer a_nonce. Clearing answer/response values.' );
					$_POST['results'][ $r_idx ]['data'] = array();
				}
			}
		}

		$_POST['results']['comp']['points']           = intval( $total_awarded_points );
		$_POST['results']['comp']['correctQuestions'] = intval( $total_correct );

		if ( ! empty( $total_possible_points ) ) {
			$comp_result = round( ( $total_awarded_points / $total_possible_points ) * 100, 2 );
		} else {
			$comp_result = 0.00;
		}
		if ( floatval( $comp_result ) !== floatval( $_POST['results']['comp']['result'] ) ) {
			learndash_quiz_debug_log_message( 'invalid or mismatched [comp][result] percentage sent [' . floatval( $_POST['results']['comp']['result'] ) . ']' );
			learndash_quiz_debug_log_message( 'Recalculated result value [' . floatval( $comp_result ) . '] will be used.' );

			$_POST['results']['comp']['result'] = $comp_result;
		}

		$quiz = new WpProQuiz_Controller_Quiz();
		learndash_quiz_debug_log_message( 'calling completeQuiz' );
		$quiz->completedQuiz();
	}

	private function localizeScript() {
		global $wp_locale;

		$isRtl = isset( $wp_locale->is_rtl ) ? $wp_locale->is_rtl : false;

		$translation_array = array(
			// translators: placeholder: quiz.
			'delete_msg'                          => sprintf( esc_html_x( 'Do you really want to delete the %s/question?', 'placeholder: quiz.', 'learndash' ), learndash_get_custom_label_lower( 'quiz' ) ),
			'no_title_msg'                        => esc_html__( 'Title is not filled!', 'learndash' ),
			// translators: placeholder: question.
			'no_question_msg'                     => sprintf( esc_html_x( 'No %s deposited!', 'placeholder: question', 'learndash' ), learndash_get_custom_label_lower( 'question' ) ),
			'no_correct_msg'                      => esc_html__( 'Correct answer was not selected!', 'learndash' ),
			'no_answer_msg'                       => esc_html__( 'No answer deposited!', 'learndash' ),
			// translators: placeholder: quiz.
			'no_quiz_start_msg'                   => sprintf( esc_html_x( 'No %s description filled!', 'placeholder: quiz', 'learndash' ), learndash_get_custom_label_lower( 'quiz' ) ),
			'fail_grade_result'                   => esc_html__( 'The percent values in result text are incorrect.', 'learndash' ),
			'no_nummber_points'                   => esc_html__( 'No number in the field "Points" or less than 1', 'learndash' ),
			'no_nummber_points_new'               => esc_html__( 'No number in the field "Points" or less than 0', 'learndash' ),
			// translators: placeholder: quiz.
			'no_selected_quiz'                    => sprintf( esc_html_x( 'No %s selected', 'placeholder: quiz', 'learndash' ), learndash_get_custom_label_lower( 'quiz' ) ),
			'reset_statistics_msg'                => esc_html__( 'Do you really want to reset the statistic?', 'learndash' ),
			'no_data_available'                   => esc_html__( 'No data available', 'learndash' ),
			'no_sort_element_criterion'           => esc_html__( 'No sort element in the criterion', 'learndash' ),
			'dif_points'                          => esc_html__( '"Different points for every answer" is not possible at "Free" choice', 'learndash' ),
			'category_no_name'                    => esc_html__( 'You must specify a name.', 'learndash' ),
			'confirm_delete_entry'                => esc_html__( 'This entry should really be deleted?', 'learndash' ),
			'not_all_fields_completed'            => esc_html__( 'Not all fields completed.', 'learndash' ),
			'temploate_no_name'                   => esc_html__( 'You must specify a template name.', 'learndash' ),
			'no_delete_answer'                    => esc_html__( 'Cannot delete only answer', 'learndash' ),

			'metabox_title_correct_message'       => esc_html__( 'Message with the correct answer', 'learndash' ) . ' ' . esc_html__( '(optional)', 'learndash' ),
			'metabox_title_correct_message_essay' => esc_html__( 'Message after Essay is submitted - optional', 'learndash' ),

			'closeText'                           => esc_html__( 'Close', 'learndash' ),
			'currentText'                         => esc_html__( 'Today', 'learndash' ),
			'monthNames'                          => array_values( $wp_locale->month ),
			'monthNamesShort'                     => array_values( $wp_locale->month_abbrev ),
			'dayNames'                            => array_values( $wp_locale->weekday ),
			'dayNamesShort'                       => array_values( $wp_locale->weekday_abbrev ),
			'dayNamesMin'                         => array_values( $wp_locale->weekday_initial ),
			'dateFormat'                          => WpProQuiz_Helper_Until::convertPHPDateFormatToJS( get_option( 'date_format', 'm/d/Y' ) ),
			'firstDay'                            => get_option( 'start_of_week' ),
			'isRTL'                               => $isRtl,
			'select2_enabled'                     => learndash_use_select2_lib(),
			'select2_fetch_enabled'               => learndash_use_select2_lib_ajax_fetch(),
		);

		wp_localize_script( 'wpProQuiz_admin_javascript', 'wpProQuizLocalize', $translation_array );
	}

	public function enqueueScript() {
		global $learndash_assets_loaded;

		learndash_admin_settings_page_assets();

		wp_enqueue_script(
			'wpProQuiz_admin_javascript',
			plugins_url( 'js/wpProQuiz_admin' . learndash_min_asset() . '.js', WPPROQUIZ_FILE ),
			array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker' ),
			LEARNDASH_SCRIPT_VERSION_TOKEN
		);
		$learndash_assets_loaded['scripts']['wpProQuiz_admin_javascript'] = __FUNCTION__;

		$this->localizeScript();
	}

	public function register_page() {
		// translators: placeholder: Quiz.
		$quiz_title = sprintf( esc_html_x( 'Advanced %s', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) );
		$page       = add_submenu_page(
			'edit.php?post_type=sfwd-quiz',
			$quiz_title,
			$quiz_title,
			'wpProQuiz_show',
			'ldAdvQuiz',
			array( $this, 'route' )
		);

		add_action( 'admin_print_scripts-' . $page, array( $this, 'enqueueScript' ) );
	}

	public function route() {
		$module = isset( $_GET['module'] ) ? $_GET['module'] : 'overallView';

		$c = null;

		switch ( $module ) {
			case 'overallView':
				$c = new WpProQuiz_Controller_Quiz();
				break;
			case 'question':
				$c = new WpProQuiz_Controller_Question();
				break;
			case 'preview':
				$c = new WpProQuiz_Controller_Preview();
				break;
			case 'statistics':
				$c = new WpProQuiz_Controller_Statistics();
				break;
			case 'importExport':
				$c = new WpProQuiz_Controller_ImportExport();
				break;
			case 'globalSettings':
				$c = new WpProQuiz_Controller_GlobalSettings();
				break;
			case 'styleManager':
				$c = new WpProQuiz_Controller_StyleManager();
				break;
			case 'toplist':
				$c = new WpProQuiz_Controller_Toplist();
				break;
			case 'wpq_support':
				//$c = new WpProQuiz_Controller_WpqSupport();
				break;
		}

		if ( $c !== null ) {
			$c->route();
		}
	}
}
