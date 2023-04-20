<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName,PSR2.Classes.PropertyDeclaration.Underscore
class WpProQuiz_Model_Question extends WpProQuiz_Model_Model {
	protected $_id              = 0;
	protected $_questionPostId  = 0;
	protected $_quizId          = 0;
	protected $_previousId      = 0;
	protected $_online          = true;
	protected $_sort            = 0;
	protected $_title           = '';
	protected $_question        = '';
	protected $_correctMsg      = '';
	protected $_incorrectMsg    = '';
	protected $_answerType      = 'single';
	protected $_correctSameText = false;
	protected $_tipEnabled      = false;
	protected $_tipMsg          = '';
	protected $_points          = LEARNDASH_LMS_DEFAULT_QUESTION_POINTS;
	protected $_showPointsInBox = false;

	//0.19
	protected $_answerPointsActivated = false;
	protected $_answerData            = null;

	//0.23
	protected $_categoryId = 0;

	//0.24
	protected $_categoryName = '';

	//0.25
	protected $_answerPointsDiffModusActivated = false;
	protected $_disableCorrect                 = false;

	//0.27
	protected $_matrixSortAnswerCriteriaWidth = 20;

	/**
	 * Instance of specific question qnswer type model.
	 *
	 * @since 3.5.0
	 */
	private $specific_question_model = null;

	public function setId( $_id ) {
		$this->_id = (int) $_id;

		return $this;
	}

	public function getId() {
		return $this->_id;
	}

	public function setPreviousId( $_previous_id ) {
		$this->_previousId = (int) $_previous_id;

		return $this;
	}

	public function getPreviousId() {
		return $this->_previousId;
	}

	public function setOnline( $_online ) {
		$this->_online = (bool) $_online;

		return $this;
	}

	public function getOnline() {
		return $this->_online;
	}

	public function setQuestionPostId( $question_post_id = 0 ) {
		$this->_questionPostId = absint( $question_post_id );
	}

	public function getQuestionPostId() {
		return $this->_questionPostId;
	}

	public function setQuizId( $_quizId ) {
		$this->_quizId = (int) $_quizId;

		return $this;
	}

	public function getQuizId() {
		return $this->_quizId;
	}

	public function setSort( $_sort ) {
		$this->_sort = (int) $_sort;

		return $this;
	}

	public function getSort() {
		return $this->_sort;
	}

	public function setTitle( $_title ) {
		$this->_title = (string) $_title;

		return $this;
	}

	public function getTitle() {
		return $this->_title;
	}

	public function setQuestion( $_question ) {
		$this->_question = (string) $_question;

		return $this;
	}

	public function getQuestion() {
		return $this->_question;
	}

	public function setCorrectMsg( $_correctMsg ) {
		$this->_correctMsg = (string) $_correctMsg;

		return $this;
	}

	public function getCorrectMsg() {
		return $this->_correctMsg;
	}

	public function setIncorrectMsg( $_incorrectMsg ) {
		$this->_incorrectMsg = (string) $_incorrectMsg;

		return $this;
	}

	public function getIncorrectMsg() {
		if ( true == $this->_correctSameText ) {
			return $this->_correctMsg;
		} else {
			return $this->_incorrectMsg;
		}
	}

	public function setAnswerType( $_answerType ) {
		$this->_answerType = (string) $_answerType;

		return $this;
	}

	public function getAnswerType() {
		return $this->_answerType;
	}

	public function setCorrectSameText( $_correctSameText ) {
		$this->_correctSameText = (bool) $_correctSameText;

		return $this;
	}

	public function isCorrectSameText() {
		return $this->_correctSameText;
	}

	public function setTipEnabled( $_tipEnabled ) {
		$this->_tipEnabled = (bool) $_tipEnabled;

		return $this;
	}

	public function isTipEnabled() {
		return $this->_tipEnabled;
	}

	public function setTipMsg( $_tipMsg ) {
		$this->_tipMsg = (string) $_tipMsg;

		return $this;
	}

	public function getTipMsg() {
		return $this->_tipMsg;
	}

	public function setPoints( $_points ) {
		$this->_points = (int) $_points;

		return $this;
	}

	public function getPoints() {
		/**
		 * LEARNDASH-5717
		 * Added to correct the issue when the disable correct/incorrect. The
		 * points is calculated to be the max points from all answers not the
		 * one marked as correct.
		 */
		if ( ( 'single' === $this->getAnswerType() ) && ( true === $this->isDisableCorrect() ) ) {
			$_answerData = $this->getAnswerData();
			if ( ( ! empty( $_answerData ) ) && ( is_array( $_answerData ) ) ) {
				$question_answer_points = array();
				foreach ( $_answerData as $a_idx => $answer ) {
					if ( is_a( $answer, 'WpProQuiz_Model_AnswerTypes' ) ) {
						$question_answer_points[ $a_idx ] = $answer->getPoints();
					}
				}
				if ( count( $question_answer_points ) ) {
					return max( $question_answer_points );
				}
			}
		}
		return $this->_points;
	}

	public function setShowPointsInBox( $_showPointsInBox ) {
		$this->_showPointsInBox = (bool) $_showPointsInBox;

		return $this;
	}

	public function isShowPointsInBox() {
		return $this->_showPointsInBox;
	}

	public function setAnswerPointsActivated( $_answerPointsActivated ) {
		$this->_answerPointsActivated = (bool) $_answerPointsActivated;

		return $this;
	}

	public function isAnswerPointsActivated() {
		return $this->_answerPointsActivated;
	}

	public function setAnswerData( $_answerData ) {
		$this->_answerData = $_answerData;

		return $this;
	}

	public function getAnswerData( $serialize = false ) {
		global $wpdb;

		if ( ! is_null( $this->_answerData ) ) {
			if ( ! is_array( $this->_answerData ) ) {
				$answerData = @maybe_unserialize( $this->_answerData );
				if ( false === $answerData ) {
					$answerData = learndash_recount_serialized_bytes( $this->_answerData );
					if ( false !== $answerData ) {
						$answerData = @maybe_unserialize( $answerData );
						if ( false === $answerData ) {
							return null;
						}
					}
				}

				if ( ( ! empty( $answerData ) ) && ( is_array( $answerData ) ) ) {
					$changes = false;
					foreach ( $answerData as $a_idx => $answer ) {
						if ( ! is_a( $answer, 'WpProQuiz_Model_AnswerTypes' ) ) {

							$answer_model = learndash_cast_WpProQuiz_Model_AnswerTypes( $answer, 'WpProQuiz_Model_AnswerTypes' );

							if ( ! is_a( $answer_model, 'WpProQuiz_Model_AnswerTypes' ) ) {
								continue;
							}

							$changes              = true;
							$answerData[ $a_idx ] = $answer_model;
						}
					}
					if ( true === $changes ) {
						$wpdb->update(
							LDLMS_DB::get_table_name( 'quiz_question' ),
							array(
								'answer_data' => serialize( $answerData ),
							),
							array(
								'id' => $this->_id,
							),
							array( '%s' ),
							array( '%d' )
						);
					}
				}
				$this->_answerData = $answerData;
			}
		}

		if ( $serialize ) {
			return @serialize( $this->_answerData );
		} else {
			return $this->_answerData;
		}
	}

	public function setCategoryId( $_categoryId ) {
		$this->_categoryId = (int) $_categoryId;

		return $this;
	}

	public function getCategoryId() {
		return $this->_categoryId;
	}

	public function setCategoryName( $_categoryName ) {
		$this->_categoryName = (string) $_categoryName;

		return $this;
	}

	public function getCategoryName() {
		return $this->_categoryName;
	}

	public function setAnswerPointsDiffModusActivated( $_answerPointsDiffModusActivated ) {
		$this->_answerPointsDiffModusActivated = (bool) $_answerPointsDiffModusActivated;

		return $this;
	}

	public function isAnswerPointsDiffModusActivated() {
		return $this->_answerPointsDiffModusActivated;
	}

	public function setDisableCorrect( $_disableCorrect ) {
		$this->_disableCorrect = (bool) $_disableCorrect;

		return $this;
	}

	public function isDisableCorrect() {
		return $this->_disableCorrect;
	}

	public function setMatrixSortAnswerCriteriaWidth( $_matrixSortAnswerCriteriaWidth ) {
		$this->_matrixSortAnswerCriteriaWidth = (int) $_matrixSortAnswerCriteriaWidth;

		return $this;
	}

	public function getMatrixSortAnswerCriteriaWidth() {
		return $this->_matrixSortAnswerCriteriaWidth;
	}

	/**
	 * Initialize the specific question answer type model.
	 *
	 * @since 3.5.0
	 */
	final public function get_specific_question_model() {

		if ( is_null( $this->specific_question_model ) ) {
			$model_class = $this->get_specific_question_model_class( $this->getAnswerType() );
			if ( ! empty( $model_class ) ) {
				$this->specific_question_model = new $model_class( $this->get_object_as_array() );
			}
		}

		if ( ! is_null( $this->specific_question_model ) ) {
			return $this->specific_question_model;
		}
		return $this;
	}

	final public function get_specific_question_model_class( $answer_type = '' ) {
		$model_class = '';

		switch ( $answer_type ) {
			case 'cloze_answer':
				$model_class = 'WpProQuiz_Model_Question_Cloze';
				break;

			case 'free_answer':
				$model_class = 'WpProQuiz_Model_Question_Free';
				break;

			default:
				break;
		}

		return $model_class;
	}

	public function get_object_as_array() {

		$object_vars = array(
			'_id'                             => $this->getId(),
			'_questionPostId'                 => $this->getQuestionPostId(),
			'_quizId'                         => $this->getQuizId(),
			'_previousId'                     => $this->getPreviousId(),
			'_online'                         => $this->getOnline(),
			'_sort'                           => $this->getSort(),
			'_title'                          => $this->getTitle(),
			'_question'                       => $this->getQuestion(),
			'_correctMsg'                     => $this->getCorrectMsg(),
			'_incorrectMsg'                   => $this->getIncorrectMsg(),
			'_correctSameText'                => $this->isCorrectSameText(),
			'_tipEnabled'                     => $this->isTipEnabled(),
			'_tipMsg'                         => $this->getTipMsg(),
			'_points'                         => $this->getPoints(),
			'_showPointsInBox'                => $this->isShowPointsInBox(),
			'_answerPointsActivated'          => $this->isAnswerPointsActivated(),
			'_answerType'                     => $this->getAnswerType(),
			'_answerData'                     => $this->getAnswerData(),
			'_answerPointsDiffModusActivated' => $this->isAnswerPointsDiffModusActivated(),
			'_disableCorrect'                 => $this->isDisableCorrect(),
			'_matrixSortAnswerCriteriaWidth'  => $this->getMatrixSortAnswerCriteriaWidth(),
		);

		return $object_vars;
	}

	public function set_array_to_object( $array_vars = array(), $init_fields = true ) {

		foreach ( $array_vars as $key => $value ) {
			switch ( $key ) {
				case '_id':
					$this->setId( $value );
					break;

				case '_questionPostId':
					$this->setQuestionPostId( $value );
					break;

				case '_quizId':
					$this->setQuizId( $value );
					break;

				case '_sort':
					$this->setSort( $value );
					break;

				case '_online':
					$this->setOnline( $value );
					break;

				case '_previousId':
					$this->setPreviousId( $value );
					break;

				case '_title':
					$this->setTitle( $value );
					break;

				case '_question':
					$this->setQuestion( $value );
					break;

				case '_correctMsg':
					$this->setCorrectMsg( $value );
					break;

				case '_incorrectMsg':
					$this->setIncorrectMsg( $value );
					break;

				case '_answerType':
					$this->setAnswerType( $value );
					break;

				case '_answerData':
					if ( is_array( $value ) ) {
						$answer_import_array = array();
						foreach ( $value as $answer_item ) {
							if ( is_array( $answer_item ) ) {
								$answer_import = new WpProQuiz_Model_AnswerTypes();
								$answer_import->set_array_to_object( $answer_item );
								$answer_import_array[] = $answer_import;
							} elseif ( is_a( $answer_item, 'WpProQuiz_Model_AnswerTypes' ) ) {
								$answer_import_array[] = $answer_item;
							}
						}

						//if ( !empty( $answer_import_array ) ) {
							$this->setAnswerData( $answer_import_array );
						//}
					}
					break;

				case '_correctSameText':
					$this->setCorrectSameText( $value );
					break;

				case '_tipEnabled':
					$this->setTipEnabled( $value );
					break;

				case '_tipMsg':
					$this->setTipMsg( $value );
					break;

				case '_points':
					$this->setPoints( $value );
					break;

				case '_showPointsInBox':
					$this->setShowPointsInBox( $value );
					break;

				case '_answerPointsActivated':
					$this->setAnswerPointsActivated( $value );
					break;

				case '_categoryId':
					$this->setCategoryId( $value );
					break;

				case '_categoryName':
					$this->setCategoryName( $value );
					break;

				case '_answerPointsDiffModusActivated':
					$this->setAnswerPointsDiffModusActivated( $value );
					break;

				case '_disableCorrect':
					$this->setDisableCorrect( $value );
					break;

				case '_matrixSortAnswerCriteriaWidth':
					$this->setMatrixSortAnswerCriteriaWidth( $value );
					break;

				default:
					break;
			}
		}
	}
}
