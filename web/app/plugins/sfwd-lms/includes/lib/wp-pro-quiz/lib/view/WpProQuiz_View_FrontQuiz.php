<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName,PSR2.Classes.PropertyDeclaration.Underscore
class WpProQuiz_View_FrontQuiz extends WpProQuiz_View_View {

	/**
	 * @var WpProQuiz_Model_Quiz
	 */
	public $quiz;

	/**
	 * @deprecated 3.5.0
	 */
	private $_clozeTemp = array();

	/**
	 * @deprecated 3.5.0
	 */
	private $_assessmetTemp = array();

	private $_shortcode_atts = array();

	public function set_shortcode_atts( $atts = array() ) {
		$this->_shortcode_atts = $atts;
	}

	private function getFreeCorrect( $data, $question = null ) {

		$t = str_replace( "\r\n", "\n", $data->getAnswer() );
		$t = str_replace( "\r", "\n", $t );
		$t = explode( "\n", $t );

		foreach ( $t as $idx => $item ) {
			$item = trim( $item );
			if ( '' == $item ) {
				unset( $t[ $idx ] );
			} else {
				/** This filter is documented in includes/quiz/ld-quiz-pro.php */
				if ( apply_filters( 'learndash_quiz_question_free_answers_to_lowercase', true, $question ) ) {
					if ( function_exists( 'mb_strtolower' ) ) {
						$item = mb_strtolower( $item );
					} else {
						$item = strtolower( $item );
					}
				}
				$t[ $idx ] = $item;
			}
		}

		return array_values( $t );
	}

	public function show( $preview = false ) {

		$question_count = count( $this->question );

		// Keep the saved order if needed.

		if ( is_user_logged_in() ) {
			$quiz_resume_enabled = (bool) learndash_get_setting(
				$this->quiz->getPostId(),
				'quiz_resume'
			);

			if ( true === $quiz_resume_enabled ) {
				$course_id = 0;
				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) !== 'yes' ) {
					$course_id = learndash_get_setting( $this->quiz->getPostId(), 'course' );
					$course_id = absint( $course_id );
				}

				$quiz_resume_activity = LDLMS_User_Quiz_Resume::get_user_quiz_resume_activity(
					get_current_user_id(),
					$this->quiz->getPostId(),
					$course_id
				);

				if (
					is_a( $quiz_resume_activity, 'LDLMS_Model_Activity' ) &&
					property_exists( $quiz_resume_activity, 'activity_id' ) &&
					! empty( $quiz_resume_activity->activity_id )
				) {
					if ( ( property_exists( $quiz_resume_activity, 'activity_meta' ) ) && ( ! empty( $quiz_resume_activity->activity_meta ) ) ) {
						$quiz_resume_data = $quiz_resume_activity->activity_meta;

						if (
							isset( $quiz_resume_data['randomQuestions'] ) &&
							$quiz_resume_data['randomQuestions'] &&
							! empty( $quiz_resume_data['randomOrder'] ) &&
							count( $this->question ) > 0
						) {
							$questionPostIdProIdHash = array();
							foreach ( $this->question as $question ) {
								/** @var WpProQuiz_Model_Question $question Question. */
								$questionPostIdProIdHash[ $question->getId() ] = $question->getQuestionPostId();
							}

							$questions = array();
							foreach ( $quiz_resume_data['randomOrder'] as $question_id ) {
								$question = $this->question[ $questionPostIdProIdHash[ $question_id ] ];

								$questions[ $question->getQuestionPostId() ] = $question;
							}
							$this->question = $questions;
						}
					}
				}
			}
		}

		$result = $this->quiz->getResultText();

		if ( ! $this->quiz->isResultGradeEnabled() ) {
			$result = array(
				'text'    => array( $result ),
				'prozent' => array( 0 ),
			);
		}

		$resultsProzent = wp_json_encode( $result['prozent'] );

		$quiz_meta = array(
			'quiz_pro_id'  => $this->quiz->getId(),
			'quiz_post_id' => $this->quiz->getPostId(),
		);

		?>
		<div class="wpProQuiz_content" id="wpProQuiz_<?php echo esc_attr( $this->quiz->getId() ); ?>" data-quiz-meta="<?php echo htmlspecialchars( wp_json_encode( $quiz_meta ) ); ?>">
			<div class="wpProQuiz_spinner" style="display:none">
				<div></div>
			</div>
			<?php

			if ( ! $this->quiz->isTitleHidden() ) {
				echo '<h2>', wp_kses_post( $this->quiz->getName() ), '</h2>';
			}

			LD_QuizPro::showQuizContent( $this->quiz->getID() );
			$this->showTimeLimitBox();
			$this->showCheckPageBox( $question_count );
			$this->showInfoPageBox();
			$this->showStartQuizBox();
			$this->showUserQuizStatisticsBox();
			$this->showLockBox();
			$this->showLoadQuizBox();
			$this->showStartOnlyRegisteredUserBox();
			$this->showPrerequisiteBox();
			$this->showResultBox( $result, $question_count );

			if ( $this->quiz->getToplistDataShowIn() == WpProQuiz_Model_Quiz::QUIZ_TOPLIST_SHOW_IN_BUTTON ) {
				$this->showToplistInButtonBox();
			}

			$this->showReviewBox( $question_count );
			$this->showQuizAnker();

			$quizData = $this->showQuizBox( $question_count );

			?>
		</div>
		<?php
		if ( $preview ) {
			add_action( 'admin_footer', array( $this, 'script_preview' ) );
		} else {
			add_action( 'wp_print_footer_scripts', array( $this, 'script' ), 999 );
		}

	}

	public function script_preview() {
		$this->script( true );
	}

	public function script( $preview = false ) {

		if ( ( isset( $this->_shortcode_atts['quiz_id'] ) ) && ( ! empty( $this->_shortcode_atts['quiz_id'] ) ) ) {
			$post = get_post( absint( $this->_shortcode_atts['quiz_id'] ) );
		} else {
			$post = get_queried_object();
		}

		if ( ( empty( $post ) ) || ( ! is_a( $post, 'WP_Post' ) ) ) {
			return;
		}

		$question_count = count( $this->question );

		$result = $this->quiz->getResultText();

		if ( ! $this->quiz->isResultGradeEnabled() ) {
			$result = array(
				'text'    => array( $result ),
				'prozent' => array( 0 ),
			);
		}

		$resultsProzent = wp_json_encode( $result['prozent'] );

		ob_start();
		$quizData = $this->showQuizBox( $question_count );
		ob_get_clean();

		foreach ( $quizData['json'] as $key => $value ) {
			foreach ( array( 'points', 'correct' ) as $key2 ) {
				unset( $quizData['json'][ $key ][ $key2 ] );
			}
		}
		$user_id = get_current_user_id();
		$bo      = $this->createOption( $preview );

		if ( ( isset( $this->_shortcode_atts['quiz_pro_id'] ) ) && ( ! empty( $this->_shortcode_atts['quiz_pro_id'] ) ) ) {
			$quiz_pro_id = absint( $this->_shortcode_atts['quiz_pro_id'] );
		} else {
			if ( 'sfwd-quiz' != @$post->post_type ) {
				$quiz_pro_id = $this->quiz->getId();
			}
		}

		if ( ( isset( $this->_shortcode_atts['quiz_id'] ) ) && ( ! empty( $this->_shortcode_atts['quiz_id'] ) ) ) {
			$quiz_post_id = absint( $this->_shortcode_atts['quiz_id'] );
		} else {
			if ( 'sfwd-quiz' != @$post->post_type ) {
				$quiz_post_id = learndash_get_quiz_id_by_pro_quiz_id( $quiz_pro_id );
			}
		}

		if ( ( isset( $quiz_post_id ) ) && ( ! empty( $quiz_post_id ) ) ) {
			$quiz_meta = get_post_meta( $quiz_post_id, '_sfwd-quiz', true );
		} else {
			$quiz_meta = array();
		}

		if ( ( isset( $quiz_meta['sfwd-quiz_passingpercentage'] ) ) && ( ! empty( $quiz_meta['sfwd-quiz_passingpercentage'] ) ) ) {
			$quiz_meta_sfwd_quiz_passingpercentage = floatval( $quiz_meta['sfwd-quiz_passingpercentage'] );
		} else {
			$quiz_meta_sfwd_quiz_passingpercentage = 0;
		}

		$ld_script_debug = 0;
		if ( isset( $_GET['LD_DEBUG'] ) ) {
			$ld_script_debug = true;
		}

		if ( ( isset( $this->_shortcode_atts['course_id'] ) ) && ( ! empty( $this->_shortcode_atts['course_id'] ) ) ) {
			$course_id = absint( $this->_shortcode_atts['course_id'] );
		} else {
			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) !== 'yes' ) {
				$course_id = learndash_get_setting( $quiz_post_id, 'course' );
				$course_id = absint( $course_id );
			}
		}
		if ( empty( $course_id ) || is_null( $course_id ) ) {
			$course_id = 0;
		}

		// Lesson ID
		if ( ( isset( $this->_shortcode_atts['lesson_id'] ) ) && ( ! empty( $this->_shortcode_atts['lesson_id'] ) ) ) {
			$lesson_id = absint( $this->_shortcode_atts['lesson_id'] );
		} else {
			if ( ! empty( $course_id ) ) {
				$lesson_id = learndash_course_get_single_parent_step( $course_id, $quiz_post_id, 'sfwd-lessons' );
			}
		}
		if ( ( empty( $lesson_id ) ) || ( is_null( $lesson_id ) ) ) {
			$lesson_id = 0;
		}

		// Topic ID
		if ( ( isset( $this->_shortcode_atts['topic_id'] ) ) && ( ! empty( $this->_shortcode_atts['topic_id'] ) ) ) {
			$topic_id = absint( $this->_shortcode_atts['topic_id'] );
		} else {
			if ( ! empty( $course_id ) ) {
				$topic_id = learndash_course_get_single_parent_step( $course_id, $quiz_post_id, 'sfwd-topic' );
			}
		}
		if ( ( empty( $topic_id ) ) || ( is_null( $topic_id ) ) ) {
			$topic_id = 0;
		}

		$quiz_nonce = '';
		if ( ! empty( $user_id ) ) {
			$quiz_nonce = wp_create_nonce( 'sfwd-quiz-nonce-' . $quiz_post_id . '-' . $quiz_pro_id . '-' . $user_id );
		} else {
			$quiz_nonce = wp_create_nonce( 'sfwd-quiz-nonce-' . $quiz_post_id . '-' . $quiz_pro_id . '-0' );
		}

		$timelimitcookie = intval( $this->quiz->getTimeLimitCookie() );

		$quiz_resume_id                = 0;
		$quiz_resume_data              = array();
		$quiz_resume_enabled           = false;
		$quiz_resume_cookie_send_timer = LEARNDASH_QUIZ_RESUME_COOKIE_SEND_TIMER_MIN;
		$quiz_resume_cookie_expiration = 604800; // 7 days.
		$quiz_resume_quiz_started      = 0;

		if ( is_user_logged_in() ) {
			$quiz_resume_enabled = (bool) learndash_get_setting( $quiz_post_id, 'quiz_resume' );
			if ( true === $quiz_resume_enabled ) {
				$quiz_resume_cookie_send_timer = (int) learndash_get_setting( $quiz_post_id, 'quiz_resume_cookie_send_timer' );
				if ( LEARNDASH_QUIZ_RESUME_COOKIE_SEND_TIMER_MIN < $quiz_resume_cookie_send_timer ) {
					$quiz_resume_cookie_send_timer = LEARNDASH_QUIZ_RESUME_COOKIE_SEND_TIMER_MIN;
				}
				$quiz_resume_activity = LDLMS_User_Quiz_Resume::get_user_quiz_resume_activity( $user_id, $quiz_post_id, $course_id );
				if ( ( is_a( $quiz_resume_activity, 'LDLMS_Model_Activity' ) ) && ( property_exists( $quiz_resume_activity, 'activity_id' ) ) && ( ! empty( $quiz_resume_activity->activity_id ) ) ) {
					$quiz_resume_id = $quiz_resume_activity->activity_id;
					if ( ( property_exists( $quiz_resume_activity, 'activity_meta' ) ) && ( ! empty( $quiz_resume_activity->activity_meta ) ) ) {
						$quiz_resume_data = $quiz_resume_activity->activity_meta;
					}
					if ( ( property_exists( $quiz_resume_activity, 'activity_started' ) ) && ( ! empty( $quiz_resume_activity->activity_started ) ) ) {
						$quiz_resume_quiz_started = $quiz_resume_activity->activity_started;
					}
				}

				// Disable the legacy cookie save logic if quiz resume is enabled.
				$timelimitcookie = 0;
			}
		}

		$quiz_resume_data = learndash_prepare_quiz_resume_data_to_js( $quiz_resume_data );

		echo " <script type='text/javascript'>
		function load_wpProQuizFront" . esc_attr( $this->quiz->getId() ) . "() {
			jQuery('#wpProQuiz_" . esc_attr( $this->quiz->getId() ) . "').wpProQuizFront({
				course_id: " . (int) $course_id . ',
				lesson_id: ' . (int) $lesson_id . ',
				topic_id: ' . (int) $topic_id . ',
				quiz: ' . (int) $quiz_post_id . ',
				quizId: ' . (int) $this->quiz->getId() . ',
				mode: ' . (int) $this->quiz->getQuizModus() . ',
				globalPoints: ' . (int) $quizData['globalPoints'] . ',
				timelimit: ' . (int) $this->quiz->getTimeLimit() . ',
				timelimitcookie: ' . (int) $timelimitcookie . ',
				resultsGrade: ' . esc_attr( $resultsProzent ) . ',
				bo: ' . (int) $bo . ',
				passingpercentage: ' . (int) $quiz_meta_sfwd_quiz_passingpercentage . ',
				user_id: ' . (int) $user_id . ',
				qpp: ' . (int) $this->quiz->getQuestionsPerPage() . ',
				catPoints: ' . wp_json_encode( $quizData['catPoints'] ) . ',
				formPos: ' . (int) $this->quiz->getFormShowPosition() . ",
				essayUploading: '" . esc_html(
					SFWD_LMS::get_template(
						'learndash_quiz_messages',
						array(
							'quiz_post_id' => $this->quiz->getID(),
							'context'      => 'quiz_essay_uploading',
							'message'      => esc_html__( 'Uploading', 'learndash' ),
						)
					)
				) . "',
				essaySuccess: '" . esc_html(
					SFWD_LMS::get_template(
						'learndash_quiz_messages',
						array(
							'quiz_post_id' => $this->quiz->getID(),
							'context'      => 'quiz_essay_success',
							'message'      => esc_html__( 'Success', 'learndash' ),
						)
					)
				) . "',
				lbn: " . wp_json_encode(
					( $this->quiz->isShowReviewQuestion() && ! $this->quiz->isQuizSummaryHide() ) ? SFWD_LMS::get_template(
						'learndash_quiz_messages',
						array(
							'quiz_post_id' => $this->quiz->getID(),
							'context'      => 'quiz_quiz_summary_button_label',
							// translators: placeholder: Quiz.
							'message'      => sprintf( esc_html_x( '%s Summary', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
						)
					) : SFWD_LMS::get_template(
						'learndash_quiz_messages',
						array(
							'quiz_post_id' => $this->quiz->getID(),
							'context'      => 'quiz_finish_button_label',
							// translators: placeholder: Quiz.
							'message'      => sprintf( esc_html_x( 'Finish %s', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
						)
					)
				) . ',
				json: ' . wp_json_encode( $quizData['json'] ) . ',
				ld_script_debug: ' . (int) $ld_script_debug . ",
				quiz_nonce: '" . esc_attr( $quiz_nonce ) . "',
				scrollSensitivity: '" .
				/**
				 * Filters quiz scroll sensitivity.
				 *
				 * Used for Sort and Matrix question types.
				 *
				 * @since 3.5.1.1
				 *
				 * @param int $sensitivity  Default 10 of 20 max.
				 * @param int $quiz_post_id Quiz ID
				 * @param int $user_id      User ID
				 */
				(int) apply_filters( 'learndash_quiz_scroll_sensitivity', 10, $quiz_post_id, $user_id ) . "',
				scrollSpeed: '" .
				/**
				 * Filters quiz scroll speed.
				 *
				 * Used for Sort and Matrix question types.
				 *
				 * @since 3.5.1.1
				 *
				 * @param int $speed        Default 10 of 20 max.
				 * @param int $quiz_post_id Quiz ID
				 * @param int $user_id      User ID
				 */
				(int) apply_filters( 'learndash_quiz_scroll_speed', 10, $quiz_post_id, $user_id ) . "',
				quiz_resume_enabled:  '" .
				/**
				 * Filters quiz resume enabled
				 *
				 * @since 3.5.0
				 *
				 * @param int $quiz_resume_enabled Whether the quiz resume is enabled.
				 * @param int $quiz_post_id        Quiz ID
				 * @param int $user_id             User ID
				 *
				 */
				(int) apply_filters( 'learndash_quiz_resume_enabled', $quiz_resume_enabled, $quiz_post_id, $user_id ) . "',
				quiz_resume_id: '" . (int) $quiz_resume_id . "',
				quiz_resume_quiz_started: '" . (int) $quiz_resume_quiz_started . "',
				quiz_resume_data: '" .
				/**
				 * Filters quiz resume data sent to the front-end
				 *
				 * @since 3.5.0
				 *
				 * @param int $quiz_resume_data Saved data sent to the front-end.
				 * @param int $quiz_post_id     Quiz ID
				 * @param int $user_id          User ID
				 *
				 */
				wp_json_encode( apply_filters( 'learndash_quiz_resume_data', $quiz_resume_data, $quiz_post_id, $user_id ), JSON_HEX_APOS ) . "',
				quiz_resume_cookie_expiration: '" .
				/**
				 * Filters the quiz resume cookie expiration.
				 *
				 * @since 3.5.0
				 *
				 * @param int $quiz_resume_cookie_expiration Cookie expiration time in seconds.
				 * @param int $quiz_post_id     Quiz ID
				 * @param int $user_id          User ID
				 *
				 */
				(int) apply_filters( 'learndash_quiz_resume_cookie_expiration', $quiz_resume_cookie_expiration, $quiz_post_id, $user_id ) . "',
				quiz_resume_cookie_send_timer: '" .
				/**
				 * Filters interval quiz resume saves data to the server.
				 *
				 * @since 3.5.0
				 *
				 * @param int $quiz_resume_cookie_send_timer Interval data is sent to the server in miliseconds.
				 * @param int $quiz_post_id     Quiz ID
				 * @param int $user_id          User ID
				 *
				 */
				(int) apply_filters( 'learndash_quiz_resume_cookie_send_timer', $quiz_resume_cookie_send_timer, $quiz_post_id, $user_id ) . "',
			});
		}
		var loaded_wpProQuizFront" . (int) $this->quiz->getId() . ' = 0;
		jQuery( function($) {
			load_wpProQuizFront' . (int) $this->quiz->getId() . '();
			loaded_wpProQuizFront' . (int) $this->quiz->getId() . " = 1;
		});
		jQuery(window).on('load',function($) {
			if(loaded_wpProQuizFront" . (int) $this->quiz->getId() . ' == 0)
			load_wpProQuizFront' . (int) $this->quiz->getId() . '();
		});
		</script> ';
	}

	public function max_question_script() {
		$question_count = count( $this->question );

		$result = $this->quiz->getResultText();

		if ( ! $this->quiz->isResultGradeEnabled() ) {
			$result = array(
				'text'    => array( $result ),
				'prozent' => array( 0 ),
			);
		}

		$resultsProzent = wp_json_encode( $result['prozent'] );
		$user_id        = get_current_user_id();
		$bo             = $this->createOption( false );

		//global $post;
		$post = get_queried_object();

		if ( 'sfwd-quiz' != @$post->post_type ) {
			$quiz_id      = $this->quiz->getId();
			$quiz_post_id = learndash_get_quiz_id_by_pro_quiz_id( $quiz_id );
		} else {
			$quiz_post_id = ( empty( $post->ID ) ) ? '0' : $post->ID;

			$quiz_meta = get_post_meta( $quiz_post_id, '_sfwd-quiz', true );
		}

		if ( ( isset( $quiz_meta['sfwd-quiz_passingpercentage'] ) ) && ( ! empty( $quiz_meta['sfwd-quiz_passingpercentage'] ) ) ) {
			$quiz_meta_sfwd_quiz_passingpercentage = intval( $quiz_meta['sfwd-quiz_passingpercentage'] );
		} else {
			$quiz_meta_sfwd_quiz_passingpercentage = 0;
		}

		// If the Quiz URL contains the query string parameter 'LD_DEBUG' to turn on debug output (console.log()) in the JS
		$ld_script_debug = 0;
		if ( isset( $_GET['LD_DEBUG'] ) ) {
			$ld_script_debug = true;
		}

		$course_id = learndash_get_course_id();
		if ( ( empty( $course_id ) ) || ( is_null( $course_id ) ) ) {
			$course_id = 0;
		}

		// Lesson ID
		$lesson_id = learndash_course_get_single_parent_step( $course_id, $quiz_post_id, 'sfwd-lessons' );
		if ( ( empty( $lesson_id ) ) || ( is_null( $lesson_id ) ) ) {
			$lesson_id = 0;
		}

		// Topic ID
		$topic_id = learndash_course_get_single_parent_step( $course_id, $quiz_post_id, 'sfwd-topic' );
		if ( ( empty( $topic_id ) ) || ( is_null( $topic_id ) ) ) {
			$topic_id = 0;
		}

		$quiz_nonce = '';
		if ( ! empty( $user_id ) ) {
			$quiz_nonce = wp_create_nonce( 'sfwd-quiz-nonce-' . $quiz_post_id . '-' . $this->quiz->getId() . '-' . $user_id );
		} else {
			$quiz_nonce = wp_create_nonce( 'sfwd-quiz-nonce-' . $quiz_post_id . '-' . $this->quiz->getId() . '-0' );
		}

		$timelimitcookie = intval( $this->quiz->getTimeLimitCookie() );

		$quiz_resume_id                = 0;
		$quiz_resume_data              = array();
		$quiz_resume_enabled           = false;
		$quiz_resume_cookie_send_timer = LEARNDASH_QUIZ_RESUME_COOKIE_SEND_TIMER_MIN;
		$quiz_resume_cookie_expiration = 604800; // 7 days.
		$quiz_resume_quiz_started      = 0;

		if ( is_user_logged_in() ) {
			$quiz_resume_enabled = (bool) learndash_get_setting( $quiz_post_id, 'quiz_resume' );
			if ( true === $quiz_resume_enabled ) {
				$quiz_resume_cookie_send_timer = (int) learndash_get_setting( $quiz_post_id, 'quiz_resume_cookie_send_timer' );
				if ( LEARNDASH_QUIZ_RESUME_COOKIE_SEND_TIMER_MIN < $quiz_resume_cookie_send_timer ) {
					$quiz_resume_cookie_send_timer = LEARNDASH_QUIZ_RESUME_COOKIE_SEND_TIMER_MIN;
				}
				$quiz_resume_activity = LDLMS_User_Quiz_Resume::get_user_quiz_resume_activity( $user_id, $quiz_post_id, $course_id );
				if ( ( is_a( $quiz_resume_activity, 'LDLMS_Model_Activity' ) ) && ( property_exists( $quiz_resume_activity, 'activity_id' ) ) && ( ! empty( $quiz_resume_activity->activity_id ) ) ) {
					$quiz_resume_id = $quiz_resume_activity->activity_id;
					if ( ( property_exists( $quiz_resume_activity, 'activity_meta' ) ) && ( ! empty( $quiz_resume_activity->activity_meta ) ) ) {
						$quiz_resume_data = $quiz_resume_activity->activity_meta;
					}
					if ( ( property_exists( $quiz_resume_activity, 'activity_started' ) ) && ( ! empty( $quiz_resume_activity->activity_started ) ) ) {
						$quiz_resume_quiz_started = $quiz_resume_activity->activity_started;
					}
				}

				// Disable the legacy cookie save logic if quiz resume is enabled.
				$timelimitcookie = 0;
			}
		}

		$quiz_resume_data = learndash_prepare_quiz_resume_data_to_js( $quiz_resume_data );

		echo "<script type='text/javascript'>
		jQuery( function($) {
			$('#wpProQuiz_" . (int) $this->quiz->getId() . "').wpProQuizFront({
				course_id: " . (int) $course_id . ',
				lesson_id: ' . (int) $lesson_id . ',
				topic_id: ' . (int) $topic_id . ',
				quiz: ' . (int) $quiz_post_id . ',
				quizId: ' . (int) $this->quiz->getId() . ',
				mode: ' . (int) $this->quiz->getQuizModus() . ',
				timelimit: ' . (int) $this->quiz->getTimeLimit() . ',
				timelimitcookie: ' . (int) $timelimitcookie . ',
				resultsGrade: ' . esc_attr( $resultsProzent ) . ',
				bo: ' . (int) $bo . ',
				passingpercentage: ' . (int) $quiz_meta_sfwd_quiz_passingpercentage . ',
				user_id: ' . (int) $user_id . ',
				qpp: ' . (int) $this->quiz->getQuestionsPerPage() . ',
				formPos: ' . (int) $this->quiz->getFormShowPosition() . ',
				ld_script_debug: ' . (int) $ld_script_debug . ",
				quiz_nonce: '" . esc_attr( $quiz_nonce ) . "',
				scrollSensitivity: '" .
				/** This filter is documented in includes/lib/wp-pro-quiz/lib/view/WpProQuiz_ViewFrontQuiz.php */
				(int) apply_filters( 'learndash_quiz_scroll_sensitivity', 10, $quiz_post_id, $user_id ) . "',
				scrollSpeed: '" .
				/** This filter is documented in includes/lib/wp-pro-quiz/lib/view/WpProQuiz_ViewFrontQuiz.php */
				(int) apply_filters( 'learndash_quiz_scroll_speed', 10, $quiz_post_id, $user_id ) . "',
				quiz_resume_enabled:  '" .
				/** This filter is documented in includes/lib/wp-pro-quiz/lib/view/WpProQuiz_ViewFrontQuiz.php */
				(int) apply_filters( 'learndash_quiz_resume_enabled', $quiz_resume_enabled, $quiz_post_id, $user_id ) . "',
				quiz_resume_id: '" . (int) $quiz_resume_id . "',
				quiz_resume_quiz_started: '" . (int) $quiz_resume_quiz_started . "',
				quiz_resume_data: '" .
				/** This filter is documented in includes/lib/wp-pro-quiz/lib/view/WpProQuiz_ViewFrontQuiz.php */
				wp_json_encode( apply_filters( 'learndash_quiz_resume_data', $quiz_resume_data, $quiz_post_id, $user_id ) ) . "',
				quiz_resume_cookie_expiration: '" .
				/** This filter is documented in includes/lib/wp-pro-quiz/lib/view/WpProQuiz_ViewFrontQuiz.php */
				(int) apply_filters( 'learndash_quiz_resume_cookie_expiration', $quiz_resume_cookie_expiration, $quiz_post_id, $user_id ) . "',
				quiz_resume_cookie_send_timer: '" .
				/** This filter is documented in includes/lib/wp-pro-quiz/lib/view/WpProQuiz_ViewFrontQuiz.php */
				(int) apply_filters( 'learndash_quiz_resume_cookie_send_timer', $quiz_resume_cookie_send_timer, $quiz_post_id, $user_id ) . "',
				essayUploading: '" . esc_html(
					SFWD_LMS::get_template(
						'learndash_quiz_messages',
						array(
							'quiz_post_id' => $this->quiz->getID(),
							'context'      => 'quiz_essay_uploading',
							'message'      => esc_html__( 'Uploading', 'learndash' ),
						)
					)
				) . "',
				essaySuccess: '" . esc_html(
					SFWD_LMS::get_template(
						'learndash_quiz_messages',
						array(
							'quiz_post_id' => $this->quiz->getID(),
							'context'      => 'quiz_essay_success',
							'message'      => esc_html__( 'Success', 'learndash' ),
						)
					)
				) . "',
				lbn: " . wp_json_encode(
					( $this->quiz->isShowReviewQuestion() && ! $this->quiz->isQuizSummaryHide() ) ? SFWD_LMS::get_template(
						'learndash_quiz_messages',
						array(
							'quiz_post_id' => $this->quiz->getID(),
							'context'      => 'quiz_quiz_summary_button_label',
							// translators: placeholder: Quiz.
							'message'      => sprintf( esc_html_x( '%s Summary', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
						)
					) : SFWD_LMS::get_template(
						'learndash_quiz_messages',
						array(
							'quiz_post_id' => $this->quiz->getID(),
							'context'      => 'quiz_finish_button_label',
							// translators: placeholder: Quiz.
							'message'      => sprintf( esc_html_x( 'Finish %s', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
						)
					)
				) . '
			});
		});
		</script>';
	}

	private function createOption( $preview ) {
		$bo = 0;

		$bo |= ( (int) $this->quiz->isAnswerRandom() ) << 0;
		$bo |= ( (int) $this->quiz->isQuestionRandom() ) << 1;
		$bo |= ( (int) $this->quiz->isDisabledAnswerMark() ) << 2;
		$bo |= ( (int) ( $this->quiz->isQuizRunOnce() || $this->quiz->isPrerequisite() || $this->quiz->isStartOnlyRegisteredUser() ) ) << 3;
		$bo |= ( (int) $preview ) << 4;
		$bo |= ( (int) get_option( 'wpProQuiz_corsActivated' ) ) << 5;
		$bo |= ( (int) $this->quiz->isToplistDataAddAutomatic() ) << 6;
		$bo |= ( (int) $this->quiz->isShowReviewQuestion() ) << 7;
		$bo |= ( (int) $this->quiz->isQuizSummaryHide() ) << 8;
		$bo |= ( (int) ( $this->quiz->isSkipQuestion() && $this->quiz->isShowReviewQuestion() ) ) << 9;
		$bo |= ( (int) $this->quiz->isAutostart() ) << 10;
		$bo |= ( (int) $this->quiz->isForcingQuestionSolve() ) << 11;
		$bo |= ( (int) $this->quiz->isHideQuestionPositionOverview() ) << 12;
		$bo |= ( (int) $this->quiz->isFormActivated() ) << 13;
		$bo |= ( (int) $this->quiz->isShowMaxQuestion() ) << 14;
		$bo |= ( (int) $this->quiz->isSortCategories() ) << 15;

		return $bo;
	}

	public function showMaxQuestion() {
		$question_count = count( $this->question );

		$result = $this->quiz->getResultText();

		if ( ! $this->quiz->isResultGradeEnabled() ) {
			$result = array(
				'text'    => array( $result ),
				'prozent' => array( 0 ),
			);
		}

		$resultsProzent = wp_json_encode( $result['prozent'] );

		?>
		<div class="wpProQuiz_content" id="wpProQuiz_<?php echo (int) $this->quiz->getId(); ?>">
			<?php

			if ( ! $this->quiz->isTitleHidden() ) {
				echo '<h2>', wp_kses_post( $this->quiz->getName() ), '</h2>';
			}

			LD_QuizPro::showQuizContent( $this->quiz->getID() );
			$this->showTimeLimitBox();
			$this->showCheckPageBox( $question_count );
			$this->showInfoPageBox();
			$this->showStartQuizBox();
			$this->showUserQuizStatisticsBox();
			$this->showLockBox();
			$this->showLoadQuizBox();
			$this->showStartOnlyRegisteredUserBox();
			$this->showPrerequisiteBox();
			$this->showResultBox( $result, $question_count );

			if ( $this->quiz->getToplistDataShowIn() == WpProQuiz_Model_Quiz::QUIZ_TOPLIST_SHOW_IN_BUTTON ) {
				$this->showToplistInButtonBox();
			}

			$this->showReviewBox( $question_count );
			$this->showQuizAnker();
			?>
		</div>
		<?php
		add_action( 'wp_footer', array( $this, 'max_question_script' ), 20 );
	}

	public function getQuizData() {
		ob_start();

		$quizData = $this->showQuizBox( count( $this->question ) );

		$quizData['content']  = ob_get_contents();
		$quizData['site_url'] = get_site_url();

		ob_end_clean();

		return $quizData;
	}

	private function showQuizAnker() {
		echo SFWD_LMS::get_template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
			'quiz/partials/show_quiz_show_anker_box.php',
			array(
				'quiz_view'      => $this,
				'quiz'           => $this->quiz,
				'shortcode_atts' => $this->_shortcode_atts,
			)
		);
	}

	public function showAddToplist() {
		echo SFWD_LMS::get_template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
			'quiz/partials/show_quiz_toplist_add_box.php',
			array(
				'quiz_view'      => $this,
				'quiz'           => $this->quiz,
				'shortcode_atts' => $this->_shortcode_atts,
			)
		);
	}

	/**
	 * Fetch Fill in blank (cloze) question data.
	 *
	 * @deprecated 3.5.0 Use {@see 'learndash_question_cloze_fetch_data'} instead.
	 *
	 * @param string $answer_text Question answer text.
	 */
	private function fetchCloze( $answer_text ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.5.0', 'learndash_question_cloze_fetch_data' );
		}

		return learndash_question_cloze_fetch_data( $answer_text );
	}

	/**
	 * Callback for Fill in blank (cloze) question data.
	 *
	 * @deprecated 3.5.0
	 *
	 * @param string $t placeholder string.
	 */
	private function clozeCallback( $t ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.5.0' );
		}

		$a = array_shift( $this->_clozeTemp );

		return null === $a ? '' : $a;
	}

	/**
	 * Fetch Assessment question data.
	 *
	 * @deprecated 3.5.0 Use {@see 'learndash_question_assessment_fetch_data'} instead.
	 *
	 * @param string $answerText Question answer text
	 * @param int    $quizId     Quiz ID
	 * @param int    $questionId Question ID
	 */
	private function fetchAssessment( $answerText, $quizId, $questionId ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.5.0', 'learndash_question_assessment_fetch_data' );
		}
		return learndash_question_assessment_fetch_data( $answerText, $quizId, $questionId );
	}

	/**
	 * Callback for Assessment question data.
	 *
	 * @deprecated 3.5.0
	 *
	 * @param string $t placeholder string.
	 */
	private function assessmentCallback( $t ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.5.0' );
		}

		$a = array_shift( $this->_assessmetTemp );

		return null === $a ? '' : $a;
	}

	public function showFormBox() {
		echo SFWD_LMS::get_template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
			'quiz/partials/show_quiz_form_box.php',
			array(
				'quiz_view'      => $this,
				'quiz'           => $this->quiz,
				'shortcode_atts' => $this->_shortcode_atts,
			)
		);
	}

	private function showLockBox() {
		echo SFWD_LMS::get_template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
			'quiz/partials/show_quiz_lock_box.php',
			array(
				'quiz_view'      => $this,
				'quiz'           => $this->quiz,
				'shortcode_atts' => $this->_shortcode_atts,
			)
		);
	}

	private function showStartOnlyRegisteredUserBox() {
		echo SFWD_LMS::get_template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
			'quiz/partials/show_quiz_only_registered_users_box.php',
			array(
				'quiz_view'      => $this,
				'quiz'           => $this->quiz,
				'shortcode_atts' => $this->_shortcode_atts,
			)
		);
	}

	private function showPrerequisiteBox() {
		echo SFWD_LMS::get_template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
			'quiz/partials/show_quiz_prerequisite_box.php',
			array(
				'quiz_view'      => $this,
				'quiz'           => $this->quiz,
				'shortcode_atts' => $this->_shortcode_atts,
			)
		);
	}

	private function showCheckPageBox( $questionCount ) {
		echo SFWD_LMS::get_template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
			'quiz/partials/show_quiz_check_page_box.php',
			array(
				'quiz_view'      => $this,
				'quiz'           => $this->quiz,
				'shortcode_atts' => $this->_shortcode_atts,
				'question_count' => $questionCount,
			)
		);
	}

	private function showInfoPageBox() {
		echo SFWD_LMS::get_template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
			'quiz/partials/show_quiz_info_page_box.php',
			array(
				'quiz_view'      => $this,
				'quiz'           => $this->quiz,
				'shortcode_atts' => $this->_shortcode_atts,
			)
		);
	}

	private function showStartQuizBox() {
		echo SFWD_LMS::get_template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
			'quiz/partials/show_quiz_start_box.php',
			array(
				'quiz_view'      => $this,
				'quiz'           => $this->quiz,
				'shortcode_atts' => $this->_shortcode_atts,
			)
		);
	}

	private function showUserQuizStatisticsBox() {

		// For now don't use.
		return;

		global $post;

		if ( current_user_can( 'wpProQuiz_show_statistics' ) ) {
			$user_quizzes = get_user_meta( get_current_user_id(), '_sfwd-quizzes', true );
			if ( ! empty( $user_quizzes ) ) {
				$user_quizzes = array_reverse( $user_quizzes );

				foreach ( $user_quizzes as $user_quiz_idx => $user_quiz ) {
					if ( ( isset( $user_quiz['quiz'] ) ) && ( $user_quiz['quiz'] == $post->ID ) ) {
						if ( ( isset( $user_quiz['pro_quizid'] ) ) && ( $user_quiz['pro_quizid'] == $this->quiz->getID() ) ) {
							if ( ( isset( $user_quiz['statistic_ref_id'] ) ) && ( ! empty( $user_quiz['statistic_ref_id'] ) ) ) {
								?>
								<div class="wpProQuiz_text">
									<div>
										<input class="wpProQuiz_button" type="button" value="
										<?php
											echo esc_html(
												SFWD_LMS::get_template(
													'learndash_quiz_messages',
													array(
														'quiz_post_id'  => $this->quiz->getID(),
														'context'       => 'quiz_view_statistics_button_label',
														// translators: placeholder: Quiz.
														'message'       => sprintf( esc_html_x( 'View %s Statistics', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
													)
												)
											);

										?>
											" name="viewUserQuizStatistics" data-quiz_id="<?php echo esc_attr( $user_quiz['pro_quizid'] ); ?>" data-ref_id="<?php echo intval( $user_quiz['statistic_ref_id'] ); ?>" />

									</div>
								</div>
								<?php
								LD_QuizPro::showModalWindow();
								return;
							}
						}
					}
				}
			}
		}
	}

	private function showTimeLimitBox() {
		echo SFWD_LMS::get_template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
			'quiz/partials/show_quiz_time_limit_box.php',
			array(
				'quiz_view' => $this,
				'quiz'      => $this->quiz,
				'atts'      => $this->_shortcode_atts,
			)
		);
	}

	public function showReviewBox( $questionCount ) {
		echo SFWD_LMS::get_template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
			'quiz/partials/show_quiz_review_box.php',
			array(
				'quiz_view'      => $this,
				'quiz'           => $this->quiz,
				'shortcode_atts' => $this->_shortcode_atts,
				'question_count' => $questionCount,
			)
		);
	}

	public function showReviewQuestions( $questionCount ) {
		echo SFWD_LMS::get_template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
			'quiz/partials/show_quiz_review_questions.php',
			array(
				'quiz_view'      => $this,
				'quiz'           => $this->quiz,
				'shortcode_atts' => $this->_shortcode_atts,
				'question_count' => $questionCount,
			)
		);
	}

	public function showReviewLegend() {
		echo SFWD_LMS::get_template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
			'quiz/partials/show_quiz_review_legend.php',
			array(
				'quiz_view'      => $this,
				'quiz'           => $this->quiz,
				'shortcode_atts' => $this->_shortcode_atts,
			)
		);
	}

	public function showReviewButtons() {
		echo SFWD_LMS::get_template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
			'quiz/partials/show_quiz_review_buttons.php',
			array(
				'quiz_view'      => $this,
				'quiz'           => $this->quiz,
				'shortcode_atts' => $this->_shortcode_atts,
			)
		);
	}

	public function showResultBox( $result, $questionCount ) {
		echo SFWD_LMS::get_template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
			'quiz/partials/show_quiz_result_box.php',
			array(
				'quiz_view'      => $this,
				'quiz'           => $this->quiz,
				'shortcode_atts' => $this->_shortcode_atts,
				'question_count' => $questionCount,
				'result'         => $result,
			)
		);
	}

	private function showToplistInButtonBox() {
		echo SFWD_LMS::get_template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
			'quiz/partials/show_quiz_toplist_box.php',
			array(
				'quiz_view'      => $this,
				'quiz'           => $this->quiz,
				'shortcode_atts' => $this->_shortcode_atts,
			)
		);
	}

	public function showQuizBox( $questionCount ) {
		$args     = array(
			'quiz_view'      => $this,
			'quiz'           => $this->quiz,
			'shortcode_atts' => $this->_shortcode_atts,
			'question_count' => $questionCount,
		);
		$filepath = SFWD_LMS::get_template(
			'quiz/partials/show_quiz_questions_box.php',
			array(),
			false,
			true
		);

		$quizData = array();
		if ( $filepath ) {
			$level = ob_get_level();
			ob_start();
			if ( ( ! empty( $args ) ) && ( is_array( $args ) ) ) {
				extract( $args );
			}

			$quizData = include $filepath;
			$contents = learndash_ob_get_clean( $level );
			echo $contents; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		return $quizData;
	}

	private function showLoadQuizBox() {
		echo SFWD_LMS::get_template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
			'quiz/partials/show_quiz_load_box.php',
			array(
				'quiz_view'      => $this,
				'quiz'           => $this->quiz,
				'shortcode_atts' => $this->_shortcode_atts,
			)
		);
	}
}
