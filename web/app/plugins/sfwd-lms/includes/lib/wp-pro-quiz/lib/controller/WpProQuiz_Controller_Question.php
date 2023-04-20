<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName,PSR2.Classes.PropertyDeclaration.Underscore
class WpProQuiz_Controller_Question extends WpProQuiz_Controller_Controller {

	private $_quizId;

	/**
	 * View instance
	 * @var object $view.
	 */
	protected $view;

	public function route() {

		if ( ! isset( $_GET['quiz_id'] ) || empty( $_GET['quiz_id'] ) ) {
			// translators: placeholder: Quiz.
			WpProQuiz_View_View::admin_notices( sprintf( esc_html_x( '%s not found', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ), 'error' );

			return;
		}

		$this->_quizId = (int) $_GET['quiz_id'];
		$action        = isset( $_GET['action'] ) ? $_GET['action'] : 'show';

		$m = new WpProQuiz_Model_QuizMapper();

		if ( $m->exists( $this->_quizId ) == 0 ) {
			// translators: placeholder: Quiz.
			WpProQuiz_View_View::admin_notices( sprintf( esc_html_x( '%s not found', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ), 'error' );

			return;
		}

		switch ( $action ) {
			case 'show':
				$this->showAction();
				break;
			case 'addEdit':
				$this->addEditQuestion( (int) $_GET['quiz_id'] );
				break;
			case 'delete':
				$this->deleteAction( $_GET['id'] );
				break;
			case 'save_sort':
				$this->saveSort();
				break;
			case 'load_question':
				$this->loadQuestion( $_GET['quiz_id'] );
				break;
			case 'copy_question':
				$this->copyQuestion( $_GET['quiz_id'] );
				break;
		}
	}

	private function addEditQuestion( $quizId ) {
		$questionId = isset( $_GET['questionId'] ) ? (int) $_GET['questionId'] : 0;

		if ( $questionId ) {
			if ( ! current_user_can( 'wpProQuiz_edit_quiz' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'learndash' ) );
			}
		} else {
			if ( ! current_user_can( 'wpProQuiz_add_quiz' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'learndash' ) );
			}
		}

		$quizMapper     = new WpProQuiz_Model_QuizMapper();
		$questionMapper = new WpProQuiz_Model_QuestionMapper();
		$cateoryMapper  = new WpProQuiz_Model_CategoryMapper();
		$templateMapper = new WpProQuiz_Model_TemplateMapper();

		if ( $questionId && $questionMapper->existsAndWritable( $questionId ) == 0 ) {
			WpProQuiz_View_View::admin_notices(
				sprintf(
				// translators: placeholder: Question.
					esc_html_x( '%s not found', 'placeholder: Question', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'question' )
				),
				'error'
			);

			return;
		}

		$question = new WpProQuiz_Model_Question();

		if ( isset( $this->_post['template'] ) || ( isset( $this->_post['templateLoad'] ) && isset( $this->_post['templateLoadId'] ) ) ) {
			if ( isset( $this->_post['template'] ) ) {
				$template = $this->saveTemplate();
			} else {
				$template = $templateMapper->fetchById( $this->_post['templateLoadId'] );
			}

			$data = $template->getData();

			if ( null !== $data ) {
				$question = $data['question'];
				$question->setId( $questionId );
				$question->setQuizId( $quizId );
			}
		} elseif ( isset( $this->_post['submit'] ) ) {
			$add_new_question_url = admin_url( 'admin.php?page=ldAdvQuiz&module=question&action=addEdit&quiz_id=' . $quizId . '&post_id=' . @$_REQUEST['post_id'] );
			// translators: placeholder: question.
			$add_new_question = "<a href='" . $add_new_question_url . "'>" . sprintf( esc_html_x( 'Click here to add another %s.', 'placeholder: question', 'learndash' ), LearnDash_Custom_Label::get_label( 'question' ) ) . '</a>';

			$question = $questionMapper->save( $this->getPostQuestionModel( $quizId, $questionId ), true );

			$questionId = $question->getId();
		} else {
			if ( $questionId ) {
				$question = $questionMapper->fetch( $questionId );
			}
		}

		$this->view             = new WpProQuiz_View_QuestionEdit();
		$this->view->categories = $cateoryMapper->fetchAll();
		$this->view->quiz       = $quizMapper->fetch( $quizId );
		$this->view->templates  = $templateMapper->fetchAll( WpProQuiz_Model_Template::TEMPLATE_TYPE_QUESTION, false );
		$this->view->question   = $question;
		$this->view->data       = $this->setAnswerObject( $question );

		// translators: placeholder: question.
		$this->view->header = $questionId ? sprintf( esc_html_x( 'Edit %s', 'placeholder: question', 'learndash' ), learndash_get_custom_label( 'question' ) ) : sprintf( esc_html_x( 'New %s', 'placeholder: question', 'learndash' ), learndash_get_custom_label( 'question' ) );

		if ( $this->view->question->isAnswerPointsActivated() ) {
			$this->view->question->setPoints( 1 );
		}

		$this->view->show();
	}

	private function saveTemplate() {
		$questionModel = $this->getPostQuestionModel( 0, 0 );

		$templateMapper = new WpProQuiz_Model_TemplateMapper();
		$template       = new WpProQuiz_Model_Template();

		if ( '0' == $this->_post['templateSaveList'] ) {
			$template->setName( trim( $this->_post['templateName'] ) );
		} else {
			$template = $templateMapper->fetchById( $this->_post['templateSaveList'], false );
		}

		$template->setType( WpProQuiz_Model_Template::TEMPLATE_TYPE_QUESTION );

		$template->setData(
			array(
				'question' => $questionModel,
			)
		);

		return $templateMapper->save( $template );
	}

	public function getPostQuestionModel( $quizId, $questionId ) {
		$questionMapper = new WpProQuiz_Model_QuestionMapper();

		$post = WpProQuiz_Controller_Request::getPost();

		$post['id']     = $questionId;
		$post['quizId'] = $quizId;
		$post['title']  = isset( $post['title'] ) ? trim( $post['title'] ) : '';
		$post['sort']   = $questionMapper->getSort( $questionId );

		$clearPost = $this->clearPost( $post );

		$post['answerData'] = $clearPost['answerData'];

		if ( ( isset( $post['title'] ) ) && ( empty( $post['title'] ) ) ) {
			$count = $questionMapper->count( $quizId );
			// translators: placeholder: Question, question count.
			$post['title'] = sprintf( esc_html_x( '%1$s: %2$d', 'placeholder: Question, question count', 'learndash' ), learndash_get_custom_label( 'question' ), $count + 1 );
		}

		if ( ( isset( $post['answerType'] ) ) && ( 'assessment_answer' === $post['answerType'] ) ) {
			$post['answerPointsActivated'] = 1;
		}

		if ( ( isset( $post['answerType'] ) ) && ( 'essay' === $post['answerType'] ) ) {
			$post['answerPointsActivated'] = 0;
		}

		if ( isset( $post['answerPointsActivated'] ) ) {
			if ( isset( $post['answerPointsDiffModusActivated'] ) ) {
				$post['points'] = $clearPost['maxPoints'];
			} else {
				$post['points'] = $clearPost['points'];
			}
		}

		if ( isset( $post['category'] ) ) {
			$post['categoryId'] = $post['category'] > 0 ? $post['category'] : 0;
		} else {
			$post['categoryId'] = 0;
		}

		return new WpProQuiz_Model_Question( $post );
	}

	public function copyQuestion( $quizId ) {

		if ( ! current_user_can( 'wpProQuiz_edit_quiz' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'learndash' ) );
		}

		$m = new WpProQuiz_Model_QuestionMapper();

		$questions = $m->fetchById( $this->_post['copyIds'] );

		foreach ( $questions as $question ) {
			$question->setId( 0 );
			$question->setQuizId( $quizId );

			$m->save( $question );
		}

		$this->showAction();
	}

	public function loadQuestion( $quizId ) {

		if ( ! current_user_can( 'wpProQuiz_edit_quiz' ) ) {
			echo wp_json_encode( array() );
			exit;
		}

		$quizMapper     = new WpProQuiz_Model_QuizMapper();
		$questionMapper = new WpProQuiz_Model_QuestionMapper();
		$data           = array();

		$quiz = $quizMapper->fetchAll();

		foreach ( $quiz as $qz ) {

			if ( $qz->getId() == $quizId ) {
				continue;
			}

			$question      = $questionMapper->fetchAll( $qz->getId() );
			$questionArray = array();

			foreach ( $question as $qu ) {
				$questionArray[] = array(
					'name' => $qu->getTitle(),
					'id'   => $qu->getId(),
				);
			}

			$data[] = array(
				'name'     => $qz->getName(),
				'id'       => $qz->getId(),
				'question' => $questionArray,
			);
		}

		echo wp_json_encode( $data );

		exit;
	}

	public function saveSort() {

		if ( ! current_user_can( 'wpProQuiz_edit_quiz' ) ) {
			exit;
		}

		$mapper = new WpProQuiz_Model_QuestionMapper();
		$map    = $this->_post['sort'];

		foreach ( $map as $k => $v ) {
			$mapper->updateSort( $v, $k );
		}

		exit;
	}

	public function deleteAction( $id ) {

		if ( ! current_user_can( 'wpProQuiz_delete_quiz' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'learndash' ) );
		}

		if ( ( isset( $_GET['question-delete-nonce'] ) ) && ( ! empty( $_GET['question-delete-nonce'] ) ) && ( wp_verify_nonce( $_GET['question-delete-nonce'], 'question-delete-nonce-' . absint( $id ) ) ) ) {
			$mapper = new WpProQuiz_Model_QuestionMapper();
			$mapper->setOnlineOff( $id );

			$this->showAction();
		}
	}

	/**
	 * @deprecated
	 */
	public function editAction( $id ) {

		if ( ! current_user_can( 'wpProQuiz_edit_quiz' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'learndash' ) );
		}

		$questionMapper = new WpProQuiz_Model_QuestionMapper();
		$quizMapper     = new WpProQuiz_Model_QuizMapper();
		$cateoryMapper  = new WpProQuiz_Model_CategoryMapper();

		$this->view             = new WpProQuiz_View_QuestionEdit();
		$this->view->quiz       = $quizMapper->fetch( $id );
		$this->view->question   = $questionMapper->fetch( $id );
		$this->view->data       = $this->setAnswerObject( $this->view->question );
		$this->view->categories = $cateoryMapper->fetchAll();

		// translators: placeholder: question.
		$this->view->header = sprintf( esc_html_x( 'Edit %s', 'placeholder: question', 'learndash' ), learndash_get_custom_label( 'question' ) );

		if ( $this->view->question->isAnswerPointsActivated() ) {
			$this->view->question->setPoints( 1 );
		}

		$this->view->show();
	}

	/**
	 * @deprecated
	 */
	public function editPostAction( $id ) {
		$mapper = new WpProQuiz_Model_QuestionMapper();

		if ( isset( $this->_post['submit'] ) && $mapper->existsAndWritable( $id ) ) {
			$post = $this->_post;

			$post['id']    = $id;
			$post['title'] = isset( $post['title'] ) ? trim( $post['title'] ) : '';

			$clearPost = $this->clearPost( $post );

			$post['answerData'] = $clearPost['answerData'];

			if ( empty( $post['title'] ) ) {
				$question = $mapper->fetch( $id );

				// translators: placeholder: Quiz sequence.
				$post['title'] = sprintf( esc_html_x( 'Question: %d', 'placeholder: Quiz sequence', 'learndash' ), $question->getSort() + 1 );
			}

			if ( 'assessment_answer' === $post['answerType'] ) {
				$post['answerPointsActivated'] = 1;
			}

			if ( 'essay' === $post['answerType'] ) {
				$post['answerPointsActivated'] = 0;
			}

			if ( isset( $post['answerPointsActivated'] ) ) {
				if ( isset( $post['answerPointsDiffModusActivated'] ) ) {
					$post['points'] = $clearPost['maxPoints'];
				} else {
					$post['points'] = $clearPost['points'];
				}
			}

			$post['categoryId'] = $post['category'] > 0 ? $post['category'] : 0;

			$mapper->save( new WpProQuiz_Model_Question( $post ), true );
		}
	}

	/**
	 * @deprecated
	 */
	public function createAction() {

		if ( ! current_user_can( 'wpProQuiz_add_quiz' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'learndash' ) );
		}

		$quizMapper     = new WpProQuiz_Model_QuizMapper();
		$cateoryMapper  = new WpProQuiz_Model_CategoryMapper();
		$templateMapper = new WpProQuiz_Model_TemplateMapper();

		$this->view             = new WpProQuiz_View_QuestionEdit();
		$this->view->question   = new WpProQuiz_Model_Question();
		$this->view->categories = $cateoryMapper->fetchAll();
		$this->view->quiz       = $quizMapper->fetch( $this->_quizId );
		$this->view->data       = $this->setAnswerObject();
		$this->view->templates  = $templateMapper->fetchAll( WpProQuiz_Model_Template::TEMPLATE_TYPE_QUESTION, false );

		// translators: placeholder: question.
		$this->view->header = sprintf( esc_html_x( 'New %s', 'placeholder: question', 'learndash' ), learndash_get_custom_label( 'question' ) );

		if ( $this->view->question->isAnswerPointsActivated() ) {
			$this->view->question->setPoints( 1 );
		}

		$this->view->show();
	}

	public function setAnswerObject( WpProQuiz_Model_Question $question = null ) {
		//Defaults
		$data = array(
			'sort_answer'        => array( new WpProQuiz_Model_AnswerTypes() ),
			'classic_answer'     => array( new WpProQuiz_Model_AnswerTypes() ),
			'matrix_sort_answer' => array( new WpProQuiz_Model_AnswerTypes() ),
			'cloze_answer'       => array( new WpProQuiz_Model_AnswerTypes() ),
			'free_answer'        => array( new WpProQuiz_Model_AnswerTypes() ),
			'assessment_answer'  => array( new WpProQuiz_Model_AnswerTypes() ),
			'essay'              => array( new WpProQuiz_Model_AnswerTypes() ),
		);

		if ( null !== $question ) {
			$type       = $question->getAnswerType();
			$type       = ( 'single' == $type || 'multiple' == $type ) ? 'classic_answer' : $type;
			$answerData = $question->getAnswerData();

			if ( ( isset( $data[ $type ] ) ) && ( null !== $answerData ) && ( ! empty( $answerData ) ) ) {
				$data[ $type ] = $question->getAnswerData();
			}
		}

		return $data;
	}

	public function clearPost( $post ) {

		if ( ( isset( $post['answerType'] ) ) && ( 'cloze_answer' == $post['answerType'] ) && ( isset( $post['answerData']['cloze'] ) ) ) {
			$question_cloze_data = learndash_question_cloze_fetch_data( $post['answerData']['cloze']['answer'] );

			/**
			 * Calculate points & maxPoints
			 */

			$points    = 0;
			$maxPoints = 0;

			foreach ( $question_cloze_data['points'] as $points_set ) {
				if ( ( is_array( $points_set ) ) && ( ! empty( $points_set ) ) ) {
					$item_points = max( $points_set );
				} else {
					$item_points = 1;
				}

				$points   += $item_points;
				$maxPoints = max( $maxPoints, $item_points );
			}

			return array(
				'points'     => $points,
				'maxPoints'  => $maxPoints,
				'answerData' => array( new WpProQuiz_Model_AnswerTypes( $post['answerData']['cloze'] ) ),
			);
		}

		if ( ( isset( $post['answerType'] ) ) && ( 'assessment_answer' == $post['answerType'] ) && ( isset( $post['answerData']['assessment'] ) ) ) {
			if ( isset( $post['sfwd-question_quiz'] ) ) {
				$quiz_id = absint( $post['sfwd-question_quiz'] );
			} else {
				$quiz_id = 0;
			}

			if ( isset( $post['post_ID'] ) ) {
				$question_id = absint( $post['post_ID'] );
			} else {
				$question_id = 0;
			}

			$cloze_answer_data = learndash_question_assessment_fetch_data( $post['answerData']['assessment']['answer'], $quiz_id, $question_id );

			if ( isset( $cloze_answer_data['points'] ) ) {
				$points     = max( $cloze_answer_data['points'] );
				$max_points = $points;
			} elseif ( isset( $cloze_answer_data['correct'] ) ) {
				$points     = count( $cloze_answer_data['correct'] );
				$max_points = $points;
			} else {
				$points     = 0;
				$max_points = 0;
			}

			return array(
				'points'     => $points,
				'maxPoints'  => $max_points,
				'answerData' => array( new WpProQuiz_Model_AnswerTypes( $post['answerData']['assessment'] ) ),
			);
		}

		if ( ( isset( $post['answerType'] ) ) && ( 'essay' == $post['answerType'] ) && ( isset( $post['answerData']['essay'] ) ) ) {
			$answerType = new WpProQuiz_Model_AnswerTypes( $post['answerData']['essay'] );
			$answerType->setPoints( $post['points'] );
			$answerType->setGraded( true );
			$answerType->setGradedType( $post['answerData']['essay']['type'] );
			$answerType->setGradingProgression( $post['answerData']['essay']['progression'] );
			$points = $post['points'];

			return array(
				'points'     => $points,
				'maxPoints'  => $points,
				'answerData' => array( $answerType ),
			);
		}

		if ( ( isset( $post['answerType'] ) ) && ( 'free_answer' == $post['answerType'] ) && ( isset( $post['answerData'] ) ) ) {
			foreach ( $post['answerData'] as $k => $v ) {
				$answerType  = new WpProQuiz_Model_AnswerTypes( $v );
				$answer_data = learndash_question_free_get_answer_data( $answerType );

				$points    = 0;
				$maxPoints = 0;
				if ( isset( $post['points'] ) ) {
					$points    = absint( $post['points'] );
					$maxPoints = $points;
				}

				if ( ( isset( $post['answerPointsActivated'] ) ) && ( isset( $answer_data['points'] ) ) ) {
					if ( is_array( $answer_data['points'] ) ) {
						$maxPoints = max( $answer_data['points'] );
						$points    = $maxPoints;
					} else {
						$maxPoints = absint( $answer_data['points'] );
						$points    = $maxPoints;
					}
				}

				return array(
					'points'     => $points,
					'maxPoints'  => $maxPoints,
					'answerData' => array( $answerType ),
				);
				break;
			}
		}

		if ( isset( $post['answerData']['cloze'] ) ) {
			unset( $post['answerData']['cloze'] );
		}
		if ( isset( $post['answerData']['assessment'] ) ) {
			unset( $post['answerData']['assessment'] );
		}

		if ( isset( $post['answerData']['none'] ) ) {
			unset( $post['answerData']['none'] );
		}

		$answerData = array();
		$points     = 0;
		$maxPoints  = 0;

		if ( isset( $post['answerData'] ) ) {
			foreach ( $post['answerData'] as $k => $v ) {
				if ( ( isset( $v['answer'] ) ) && ( trim( $v['answer'] ) == '' ) ) {
					if ( 'matrix_sort_answer' != $post['answerType'] ) {
						continue;
					} else {
						if ( ( ! isset( $v['sort_string'] ) ) || ( trim( $v['sort_string'] ) == '' ) ) {
							continue;
						}
					}
				}

				$answerType = new WpProQuiz_Model_AnswerTypes( $v );

				if ( ( 'matrix_sort_answer' == $post['answerType'] ) || ( 'sort_answer' == $post['answerType'] ) ) {
					$points   += $answerType->getPoints();
					$maxPoints = max( $maxPoints, $answerType->getPoints() );
				} elseif ( $answerType->isCorrect() ) {
					$points   += $answerType->getPoints();
					$maxPoints = max( $maxPoints, $answerType->getPoints() );
				}

				$answerData[] = $answerType;
			}
		}

		return array(
			'points'     => $points,
			'maxPoints'  => $maxPoints,
			'answerData' => $answerData,
		);
	}

	public function clear( $a ) {
		foreach ( $a as $k => $v ) {
			if ( is_array( $v ) ) {
				$a[ $k ] = $this->clear( $a[ $k ] );
			}

			if ( is_string( $a[ $k ] ) ) {
				$a[ $k ] = trim( $a[ $k ] );

				if ( '' != $a[ $k ] ) {
					continue;
				}
			}

			if ( empty( $a[ $k ] ) ) {
				unset( $a[ $k ] );
			}
		}

		return $a;
	}

	public function showAction() {
		if ( ! current_user_can( 'wpProQuiz_show' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'learndash' ) );
		}

		$m  = new WpProQuiz_Model_QuizMapper();
		$mm = new WpProQuiz_Model_QuestionMapper();

		$this->view       = new WpProQuiz_View_QuestionOverall();
		$this->view->quiz = $m->fetch( $this->_quizId );

		$this->view->question = $mm->fetchAll( $this->_quizId );
		$this->view->show();
	}
}
