<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName,PSR2.Classes.PropertyDeclaration.Underscore
class WpProQuiz_Controller_Front {

	/**
	 * @var WpProQuiz_Model_GlobalSettings
	 */
	private $_settings = null;

	public function __construct() {
		$this->loadSettings();

		add_action( 'wp_enqueue_scripts', array( $this, 'loadDefaultScripts' ) );
		add_shortcode( 'LDAdvQuiz', array( $this, 'shortcode' ) );
		add_shortcode( 'LDAdvQuiz_toplist', array( $this, 'shortcodeToplist' ) );
	}

	public function loadDefaultScripts() {
		global $learndash_assets_loaded;

		wp_enqueue_script( 'jquery' );

		$filepath = SFWD_LMS::get_template( 'learndash_quiz_front.css', null, null, true );
		if ( ! empty( $filepath ) ) {
			wp_enqueue_style( 'learndash_quiz_front_css', learndash_template_url_from_path( $filepath ), array(), LEARNDASH_SCRIPT_VERSION_TOKEN );
			wp_style_add_data( 'learndash_quiz_front_css', 'rtl', 'replace' );
			$learndash_assets_loaded['styles']['learndash_quiz_front_css'] = __FUNCTION__;
		}

		if ( $this->_settings->isJsLoadInHead() ) {
			$this->loadJsScripts( false, true, true );
		}
	}

	private function loadJsScripts( $footer = true, $quiz = true, $toplist = false ) {
		global $learndash_assets_loaded;

		if ( $quiz ) {
			wp_enqueue_script(
				'wpProQuiz_front_javascript',
				plugins_url( 'js/wpProQuiz_front' . learndash_min_asset() . '.js', WPPROQUIZ_FILE ),
				array( 'jquery', 'jquery-ui-sortable' ),
				LEARNDASH_SCRIPT_VERSION_TOKEN,
				$footer
			);
			$learndash_assets_loaded['scripts']['wpProQuiz_front_javascript'] = __FUNCTION__;

			wp_localize_script(
				'wpProQuiz_front_javascript',
				'WpProQuizGlobal',
				array(
					'ajaxurl'            => str_replace( array( 'http:', 'https:' ), array( '', '' ), admin_url( 'admin-ajax.php' ) ),
					'loadData'           => esc_html__( 'Loading', 'learndash' ),
					// translators: placeholder: question
					'questionNotSolved'  => sprintf( esc_html_x( 'You must answer this %s.', 'placeholder: question', 'learndash' ), learndash_get_custom_label_lower( 'question' ) ),
					// translators: placeholder: questions, quiz.
					'questionsNotSolved' => sprintf( esc_html_x( 'You must answer all %1$s before you can complete the %2$s.', 'placeholder: questions, quiz', 'learndash' ), learndash_get_custom_label_lower( 'questions' ), learndash_get_custom_label_lower( 'quiz' ) ),
					'fieldsNotFilled'    => esc_html__( 'All fields have to be filled.', 'learndash' ),
				)
			);

			wp_enqueue_script(
				'jquery-cookie',
				plugins_url( 'js/jquery.cookie' . learndash_min_asset() . '.js', WPPROQUIZ_FILE ),
				array( 'jquery', 'jquery-ui-sortable' ),
				'1.4.0',
				$footer
			);
			$learndash_assets_loaded['scripts']['jquery-cookie'] = __FUNCTION__;
		}

		if ( $toplist ) {
			wp_enqueue_script(
				'wpProQuiz_front_javascript_toplist',
				plugins_url( 'js/wpProQuiz_toplist' . learndash_min_asset() . '.js', WPPROQUIZ_FILE ),
				array( 'jquery', 'jquery-ui-sortable' ),
				LEARNDASH_SCRIPT_VERSION_TOKEN,
				$footer
			);
			$learndash_assets_loaded['scripts']['wpProQuiz_front_javascript_toplist'] = __FUNCTION__;

			if ( ! wp_script_is( 'wpProQuiz_front_javascript' ) ) {
				wp_localize_script(
					'wpProQuiz_front_javascript_toplist',
					'WpProQuizGlobal',
					array(
						'ajaxurl'            => str_replace( array( 'http:', 'https:' ), array( '', '' ), admin_url( 'admin-ajax.php' ) ),
						'loadData'           => esc_html__( 'Loading', 'learndash' ),
						// translators: placeholder: question
						'questionNotSolved'  => sprintf( esc_html_x( 'You must answer this %s.', 'placeholder: question', 'learndash' ), learndash_get_custom_label_lower( 'question' ) ),
						// translators: placeholder: questions, quiz.
						'questionsNotSolved' => sprintf( esc_html_x( 'You must answer all %1$s before you can complete the %2$s.', 'placeholder: questions, quiz', 'learndash' ), learndash_get_custom_label_lower( 'questions' ), learndash_get_custom_label_lower( 'quiz' ) ),
						'fieldsNotFilled'    => esc_html__( 'All fields have to be filled.', 'learndash' ),
					)
				);
			}
		}

		if ( ! $this->_settings->isTouchLibraryDeactivate() ) {
			wp_enqueue_script(
				'jquery-ui-touch-punch',
				plugins_url( 'js/jquery.ui.touch-punch.min.js', WPPROQUIZ_FILE ),
				array( 'jquery', 'jquery-ui-sortable' ),
				'0.2.2',
				$footer
			);
			$learndash_assets_loaded['scripts']['jquery-ui-touch-punch'] = __FUNCTION__;
		}
	}

	public function shortcode( $attr = array(), $content = '' ) {

		global $learndash_shortcode_used, $learndash_shortcode_atts;
		$learndash_shortcode_used = true;

		if ( ( isset( $attr[0] ) ) && ( ! empty( $attr[0] ) ) ) {
			if ( ! isset( $attr['quiz_pro_id'] ) ) {
				$attr['quiz_pro_id'] = intval( $attr[0] );
				unset( $attr[0] );
			}
		}

		$attr = shortcode_atts(
			array(
				'quiz_id'     => 0,
				'course_id'   => 0,
				'lesson_id'   => 0,
				'topic_id'    => 0,
				'quiz_pro_id' => 0,
			),
			$attr
		);
		// Just to ensure compliance.
		$attr['quiz_id']     = absint( $attr['quiz_id'] );
		$attr['course_id']   = absint( $attr['course_id'] );
		$attr['lesson_id']   = absint( $attr['lesson_id'] );
		$attr['topic_id']    = absint( $attr['topic_id'] );
		$attr['quiz_pro_id'] = absint( $attr['quiz_pro_id'] );

		if ( ! $this->_settings->isJsLoadInHead() ) {
			$this->loadJsScripts();
		}

		if ( ! empty( $attr['quiz_pro_id'] ) ) {
			ob_start();

			$learndash_shortcode_atts['LDAdvQuiz'] = $attr;
			$this->handleShortCode( $attr );

			$content = ob_get_contents();

			ob_end_clean();
		}

		if ( $this->_settings->isAddRawShortcode() ) {
			return '[raw]' . $content . '[/raw]';
		}

		return $content;
	}

	public function handleShortCode( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'quiz_id'     => 0,
				'course_id'   => 0,
				'lesson_id'   => 0,
				'topic_id'    => 0,
				'quiz_pro_id' => 0,
			),
			$atts
		);
		// Just to ensure compliance.
		$atts['quiz_id']     = absint( $atts['quiz_id'] );
		$atts['course_id']   = absint( $atts['course_id'] );
		$atts['lesson_id']   = absint( $atts['lesson_id'] );
		$atts['topic_id']    = absint( $atts['topic_id'] );
		$atts['quiz_pro_id'] = absint( $atts['quiz_pro_id'] );

		$view = new WpProQuiz_View_FrontQuiz();
		$view->set_shortcode_atts( $atts );

		$quizMapper     = new WpProQuiz_Model_QuizMapper();
		$questionMapper = new WpProQuiz_Model_QuestionMapper();
		$categoryMapper = new WpProQuiz_Model_CategoryMapper();
		$formMapper     = new WpProQuiz_Model_FormMapper();

		$quiz         = $quizMapper->fetch( $atts['quiz_pro_id'] );
		$quiz_post_id = $quiz->getPostId();
		if ( ( ! empty( $atts['quiz_id'] ) ) && ( intval( $quiz_post_id ) !== intval( $atts['quiz_id'] ) ) ) {
			$quiz->setPostId( intval( $atts['quiz_id'] ) );
		}

		$maxQuestion = false;

		if ( ( $quiz->isShowMaxQuestion() ) && ( $quiz->getShowMaxQuestionValue() > 0 ) ) {

			$value = $quiz->getShowMaxQuestionValue();

			if ( $quiz->isShowMaxQuestionPercent() ) {
				$count = $questionMapper->count( $atts['quiz_pro_id'] );

				$value = ceil( $count * $value / 100 );
			}

			//$question = $questionMapper->fetchAll( $atts['quiz_pro_id'], true, $value );
			$question    = $questionMapper->fetchAll( $quiz, true, $value );
			$maxQuestion = true;

		} else {
			//$question = $questionMapper->fetchAll( $atts['quiz_pro_id'] );
			$question = $questionMapper->fetchAll( $quiz );
		}

		if ( ( empty( $quiz ) ) || ( empty( $question ) ) ) {
			echo '';

			return;
		}

		$view->quiz     = $quiz;
		$view->question = $question;

		$view->category = $categoryMapper->fetchByQuiz( $quiz );

		$view->forms = $formMapper->fetch( $atts['quiz_pro_id'] );

		if ( $maxQuestion ) {
			$view->showMaxQuestion();
		} else {
			$view->show();
		}
	}

	public function shortcodeToplist( $attr ) {

		global $learndash_shortcode_used;
		$learndash_shortcode_used = true;

		$id      = $attr[0];
		$content = '';

		if ( ! $this->_settings->isJsLoadInHead() ) {
			$this->loadJsScripts( true, false, true );
		}

		if ( is_numeric( $id ) ) {
			ob_start();

			$this->handleShortCodeToplist( $id, isset( $attr['q'] ) );

			$content = ob_get_contents();

			ob_end_clean();
		}

		if ( $this->_settings->isAddRawShortcode() && ! isset( $attr['q'] ) ) {
			return '[raw]' . $content . '[/raw]';
		}

		return $content;
	}

	private function handleShortCodeToplist( $quizId, $inQuiz = false ) {
		$quizMapper = new WpProQuiz_Model_QuizMapper();
		$view       = new WpProQuiz_View_FrontToplist();

		$quiz = $quizMapper->fetch( $quizId );

		if ( $quiz->getId() <= 0 || ! $quiz->isToplistActivated() ) {
			echo '';
			return;
		}

		$view->quiz   = $quiz;
		$view->points = $quizMapper->sumQuestionPoints( $quizId );
		$view->inQuiz = $inQuiz;
		$view->show();
	}

	private function loadSettings() {
		$mapper = new WpProQuiz_Model_GlobalSettingsMapper();

		$this->_settings = $mapper->fetchAll();
	}

	public static function ajaxQuizLoadData( $data, $func ) {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} else {
			$user_id = 0;
		}

		if ( isset( $data['quizId'] ) ) {
			$id = $data['quizId'];
		} else {
			$id = 0;
		}

		if ( isset( $data['quiz'] ) ) {
			$quiz_post_id = $data['quiz'];
		} else {
			$quiz_post_id = 0;
		}

		if ( ( ! isset( $data['quiz_nonce'] ) ) || ( ! wp_verify_nonce( $data['quiz_nonce'], 'sfwd-quiz-nonce-' . $quiz_post_id . '-' . $id . '-' . $user_id ) ) ) {
			die();
		}

		if ( isset( $_POST['course_id'] ) ) {
			$learndash_course_id = absint( $_POST['course_id'] );
		} else {
			$learndash_course_id = (int) learndash_get_course_id();
		}


		$view = new WpProQuiz_View_FrontQuiz();

		$quizMapper     = new WpProQuiz_Model_QuizMapper();
		$questionMapper = new WpProQuiz_Model_QuestionMapper();
		$categoryMapper = new WpProQuiz_Model_CategoryMapper();
		$formMapper     = new WpProQuiz_Model_FormMapper();

		$quiz = $quizMapper->fetch( $id );
		$quiz->setPostId( absint( $quiz_post_id ) );

		if ( $quiz->isShowMaxQuestion() && $quiz->getShowMaxQuestionValue() > 0 ) {
			$learndash_quiz_resume_enabled = false;
			$learndash_quiz_resume_data    = array();

			if ( ! empty( $quiz_post_id ) && $user_id ) {
				$learndash_quiz_resume_enabled = learndash_get_setting( $quiz_post_id, 'quiz_resume' );
				if ( true === $learndash_quiz_resume_enabled ) {
					//$learndash_course_id            = learndash_get_course_id();
					$learndash_quiz_resume_activity = LDLMS_User_Quiz_Resume::get_user_quiz_resume_activity( $user_id, $quiz_post_id, $learndash_course_id );
					if ( ( is_a( $learndash_quiz_resume_activity, 'LDLMS_Model_Activity' ) ) && ( property_exists( $learndash_quiz_resume_activity, 'activity_id' ) ) && ( ! empty( $learndash_quiz_resume_activity->activity_id ) ) ) {
						$learndash_quiz_resume_id = $learndash_quiz_resume_activity->activity_id;
						if ( ( property_exists( $learndash_quiz_resume_activity, 'activity_meta' ) ) && ( ! empty( $learndash_quiz_resume_activity->activity_meta ) ) ) {
							$learndash_quiz_resume_data = $learndash_quiz_resume_activity->activity_meta;
						}
					}
				}
			}

			$value = $quiz->getShowMaxQuestionValue();

			if ( $quiz->isShowMaxQuestionPercent() ) {
				$count = $questionMapper->count( $id );

				$value = ceil( $count * $value / 100 );
			}

			if ( $learndash_quiz_resume_enabled ) {
				if ( ! empty( $learndash_quiz_resume_data ) && isset( $learndash_quiz_resume_data['randomQuestions'] ) ) {
					if ( isset( $learndash_quiz_resume_data['randomOrder'] ) ) {
						foreach ( $learndash_quiz_resume_data['randomOrder'] as $id => $value ) {
								$question[] = $questionMapper->fetchById( $value );
						}
					}
				} else {
						$question = $questionMapper->fetchAll( $quiz, true, $value );
				}
			} else {
				$question = $questionMapper->fetchAll( $quiz, true, $value );
			}
		} else {
			$question = $questionMapper->fetchAll( $quiz );
		}

		if ( empty( $quiz ) || empty( $question ) ) {
			return null;
		}

		$view->quiz     = $quiz;
		$view->question = $question;
		$view->category = $categoryMapper->fetchByQuiz( $quiz );
		$view->forms    = $formMapper->fetch( $quiz->getId() );

		return wp_json_encode( $view->getQuizData() );
	}
}
