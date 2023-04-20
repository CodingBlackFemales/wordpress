<?php
/**
 * @since 0.23
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName,PSR2.Classes.PropertyDeclaration.Underscore
class WpProQuiz_Controller_Ajax {

	private $_adminCallbacks = array();
	private $_frontCallbacks = array();

	public function init() {
		$this->initCallbacks();

		add_action( 'wp_ajax_wp_pro_quiz_admin_ajax', array( $this, 'adminAjaxCallback' ) );
		add_action( 'wp_ajax_nopriv_wp_pro_quiz_admin_ajax', array( $this, 'frontAjaxCallback' ) );

		add_action( 'wp_ajax_wp_pro_quiz_admin_ajax_load_data', array( $this, 'ajax_load_quiz_data' ) );
		add_action( 'wp_ajax_nopriv_wp_pro_quiz_admin_ajax_load_data', array( $this, 'ajax_load_quiz_data' ) );

		add_action( 'wp_ajax_wp_pro_quiz_admin_ajax_statistic_load_user', array( $this, 'ajax_statistic_load_user' ) );
		// We don't have a need to support nopriv access to user statistics.
		//add_action('wp_ajax_nopriv_wp_pro_quiz_admin_ajax_statistic_load_user', array($this, 'ajax_statistic_load_user' ) );
	}

	public function adminAjaxCallback() {
		if ( ( ! isset( $_POST['nonce'] ) ) || ( ! wp_verify_nonce( $_POST['nonce'], 'wpProQuiz_nonce' ) ) ) {
			die();
		}
		$this->ajaxCallbackHandler( true );
	}

	public function frontAjaxCallback() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'wpProQuiz_nonce' ) ) {
			die();
		}
		$this->ajaxCallbackHandler( false );
	}

	public function ajax_statistic_load_user() {
		$userId = 0;

		if ( ! isset( $data ) ) {
			if ( isset( $_POST['data'] ) ) {
				$data = $_POST['data'];
			}
		}

		if ( ( isset( $data['statistic_nonce'] ) ) && ( ! empty( $data['statistic_nonce'] ) ) ) {
			if ( ( isset( $data['userId'] ) ) && ( ! empty( $data['userId'] ) ) ) {
				$userId = intval( $data['userId'] );
			}

			if ( ! wp_verify_nonce( $data['statistic_nonce'], 'statistic_nonce_' . $data['refId'] . '_' . get_current_user_id() . '_' . $userId ) ) {
				return wp_json_encode( array() );
			}
		} elseif ( ! current_user_can( 'wpProQuiz_show_statistics' ) ) {
			return wp_json_encode( array() );
		}
		$this->ajaxCallbackHandler( true );
	}

	public function ajax_load_quiz_data() {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} else {
			$user_id = 0;
		}

		if ( isset( $_POST['data']['quizId'] ) ) {
			$quiz_id = absint( $_POST['data']['quizId'] );
		} else {
			$quiz_id = 0;
		}

		if ( isset( $_POST['data']['quiz'] ) ) {
			$quiz_post_id = absint( $_POST['data']['quiz'] );
		} else {
			$quiz_post_id = 0;
		}

		if ( ! wp_verify_nonce( $_POST['quiz_nonce'], 'sfwd-quiz-nonce-' . $quiz_post_id . '-' . $quiz_id . '-' . $user_id ) ) {
			die();
		}
		$this->ajaxCallbackHandler( false );
	}

	private function ajaxCallbackHandler( $admin ) {
		$func  = isset( $_POST['func'] ) ? $_POST['func'] : '';
		$data  = isset( $_POST['data'] ) ? $_POST['data'] : null;
		$calls = $admin ? $this->_adminCallbacks : $this->_frontCallbacks;

		if ( isset( $calls[ $func ] ) ) {
			$r = call_user_func( $calls[ $func ], $data, $func );

			if ( null !== $r ) {
				echo $r;
			}
		}

		exit;
	}

	private function initCallbacks() {
		$this->_adminCallbacks = array(
			'categoryAdd'                               => array( 'WpProQuiz_Controller_Category', 'ajaxAddCategory' ),
			'categoryDelete'                            => array( 'WpProQuiz_Controller_Category', 'ajaxDeleteCategory' ),
			'categoryEdit'                              => array( 'WpProQuiz_Controller_Category', 'ajaxEditCategory' ),

			'statisticLoad'                             => array( 'WpProQuiz_Controller_Statistics', 'ajaxLoadStatistic' ),
			/** @deprecated **/
							'statisticLoadOverview'     => array( 'WpProQuiz_Controller_Statistics', 'ajaxLoadStatsticOverview' ),
			/** @deprecated **/
							'statisticReset'            => array( 'WpProQuiz_Controller_Statistics', 'ajaxReset' ),
			/** @deprecated **/
							'statisticLoadFormOverview' => array( 'WpProQuiz_Controller_Statistics', 'ajaxLoadFormOverview' ),
			/** @deprecated **/

							'statisticLoadHistory'      => array( 'WpProQuiz_Controller_Statistics', 'ajaxLoadHistory' ),
			'statisticLoadUser'                         => array( 'WpProQuiz_Controller_Statistics', 'ajaxLoadStatisticUser' ),
			'statisticResetNew'                         => array( 'WpProQuiz_Controller_Statistics', 'ajaxRestStatistic' ),
			'statisticLoadOverviewNew'                  => array( 'WpProQuiz_Controller_Statistics', 'ajaxLoadStatsticOverviewNew' ),

			'templateEdit'                              => array( 'WpProQuiz_Controller_Template', 'ajaxEditTemplate' ),
			'templateDelete'                            => array( 'WpProQuiz_Controller_Template', 'ajaxDeleteTemplate' ),

			'quizLoadData'                              => array( 'WpProQuiz_Controller_Front', 'ajaxQuizLoadData' ),
		);

		//nopriv
		$this->_frontCallbacks = array(
			'quizLoadData' => array( 'WpProQuiz_Controller_Front', 'ajaxQuizLoadData' ),
		);
	}
}
