<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName,PSR2.Classes.PropertyDeclaration.Underscore
class WpProQuiz_Controller_Preview extends WpProQuiz_Controller_Controller {

	public function route() {
		global $learndash_assets_loaded;

		wp_enqueue_script(
			'wpProQuiz_front_javascript',
			plugins_url( 'js/wpProQuiz_front' . learndash_min_asset() . '.js', WPPROQUIZ_FILE ),
			array( 'jquery', 'jquery-ui-sortable' ),
			LEARNDASH_SCRIPT_VERSION_TOKEN
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

		$filepath = SFWD_LMS::get_template( 'learndash_quiz_front.css', null, null, true );
		if ( ! empty( $filepath ) ) {
			wp_enqueue_style( 'learndash_quiz_front_css', learndash_template_url_from_path( $filepath ), array(), LEARNDASH_SCRIPT_VERSION_TOKEN );
			wp_style_add_data( 'learndash_quiz_front_css', 'rtl', 'replace' );
			$learndash_assets_loaded['styles']['learndash_quiz_front_css'] = __FUNCTION__;
		}

		$this->showAction( $_GET['id'] );
	}

	public function showAction( $id ) {
		$view = new WpProQuiz_View_FrontQuiz();

		$quizMapper     = new WpProQuiz_Model_QuizMapper();
		$questionMapper = new WpProQuiz_Model_QuestionMapper();
		$categoryMapper = new WpProQuiz_Model_CategoryMapper();
		$formMapper     = new WpProQuiz_Model_FormMapper();

		$quiz = $quizMapper->fetch( $id );

		if ( $quiz->isShowMaxQuestion() && $quiz->getShowMaxQuestionValue() > 0 ) {

			$value = $quiz->getShowMaxQuestionValue();

			if ( $quiz->isShowMaxQuestionPercent() ) {
				$count = $questionMapper->count( $id );

				$value = ceil( $count * $value / 100 );
			}

			$question = $questionMapper->fetchAll( $quiz, true, $value );

		} else {
			$question = $questionMapper->fetchAll( $id );
		}

		$view->quiz     = $quiz;
		$view->question = $question;
		$view->category = $categoryMapper->fetchByQuiz( $quiz );
		$view->forms    = $formMapper->fetch( $quiz->getId() );

		$view->show( true );
	}
}
