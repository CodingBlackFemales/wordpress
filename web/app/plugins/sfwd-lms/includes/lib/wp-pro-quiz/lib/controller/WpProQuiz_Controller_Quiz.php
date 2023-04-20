<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName,PSR2.Classes.PropertyDeclaration.Underscore

class WpProQuiz_Controller_Quiz extends WpProQuiz_Controller_Controller {
	private $view;

	public function route( $get = null, $post = null ) {
		if ( empty( $get ) ) {
			$get = $_GET;
		}
		$action = isset( $get['action'] ) ? $get['action'] : 'show';

		switch ( $action ) {
			case 'show':
				$this->showAction();
				break;

			case 'addEdit':
				$this->addEditQuiz( $get, $post );
				break;

			case 'getEdit':
				return $this->getEditQuiz( $get, $post );

			case 'addUpdateQuiz':
				return $this->addUpdateQuiz( $get, $post );

			case 'reset_lock':
				if ( isset( $get['id'] ) ) {
					$this->resetLock( intval( $get['id'] ) );
				}
				break;
		}
	}

	private function addEditQuiz( $get = null, $post = null ) {

		if ( empty( $get ) ) {
			$get = $_GET;
		}
		if ( ! empty( $post ) ) {
			$this->_post = $post;
		}

		$quizId = isset( $get['quizId'] ) ? (int) $get['quizId'] : 0;

		if ( $quizId ) {
			if ( ! current_user_can( 'wpProQuiz_edit_quiz' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'learndash' ) );
			}
		} else {
			if ( ! current_user_can( 'wpProQuiz_add_quiz' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'learndash' ) );
			}
		}

		$prerequisiteMapper = new WpProQuiz_Model_PrerequisiteMapper();
		$quizMapper         = new WpProQuiz_Model_QuizMapper();
		$formMapper         = new WpProQuiz_Model_FormMapper();
		$templateMapper     = new WpProQuiz_Model_TemplateMapper();

		$quiz                 = new WpProQuiz_Model_Quiz();
		$forms                = null;
		$prerequisiteQuizList = array();

		if ( ! empty( $get['post_id'] ) ) {
			$quiz_post = get_post( $get['post_id'] );
			if ( ( ! empty( $quiz_post ) ) && ( is_a( $quiz_post, 'WP_Post' ) ) ) {
				$this->_post['name'] = $quiz_post->post_title;
			}
		}

		if ( $quizId && $quizMapper->exists( $quizId ) == 0 ) {
			WpProQuiz_View_View::admin_notices(
				sprintf(
				// translators: placeholder: Quiz.
					esc_html_x( '%s not found', 'placeholder: Quiz', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'quiz' )
				),
				'error'
			);

			return;
		}
		if ( isset( $this->_post['template'] ) || ( isset( $this->_post['templateLoad'] ) && isset( $this->_post['templateLoadId'] ) ) ) {
			if ( isset( $this->_post['template'] ) ) {
				$template = $this->saveTemplate();
			} else {
				$template = $templateMapper->fetchById( $this->_post['templateLoadId'] );
			}

			$data = $template->getData();

			if ( null !== $data ) {
				$quiz = $data['quiz'];
				$quiz->setId( $quizId );
				$quiz->setName( $this->_post['name'] );
				$quiz->setText( 'AAZZAAZZ' ); // cspell:disable-line.
				$quizMapper->save( $quiz );
				if ( empty( $quizId ) && ! empty( $get['post_id'] ) ) {
					learndash_update_setting( $get['post_id'], 'quiz_pro', $quiz->getId() );
				}
				$quizId = $quiz->getId();

				$forms                = $data['forms'];
				$prerequisiteQuizList = $data['prerequisiteQuizList'];
			}
		} elseif ( isset( $this->_post['form'] ) ) {
			if ( isset( $this->_post['resultGradeEnabled'] ) ) {
				$this->_post['result_text'] = $this->filterResultTextGrade( $this->_post );
			}

			// Patch to only set Statistics on if post from form save.
			// LEARNDASH-1434 & LEARNDASH-1481
			if ( ! isset( $this->_post['statisticsOn'] ) ) {
				$this->_post['statisticsOn']          = '0';
				$this->_post['viewProfileStatistics'] = '0';
			}

			$quiz = new WpProQuiz_Model_Quiz( $this->_post );
			$quiz->setId( $quizId );

			if ( $this->checkValidit( $this->_post ) ) {

				$quiz->setText( 'AAZZAAZZ' ); // cspell:disable-line.

				$quizMapper->save( $quiz );
				if ( empty( $quizId ) && ! empty( $get['post_id'] ) ) {
					learndash_update_setting( $get['post_id'], 'quiz_pro', $quiz->getId() );
				}

				$quizId = $quiz->getId();

				$prerequisiteMapper->delete( $quizId );

				if ( $quiz->isPrerequisite() && ! empty( $this->_post['prerequisiteList'] ) ) {
					$prerequisiteMapper->save( $quizId, $this->_post['prerequisiteList'] );
					$quizMapper->activateStatitic( $this->_post['prerequisiteList'], 1440 );
				}

				if ( ! $this->formHandler( $quiz->getId(), $this->_post ) ) {
					$quiz->setFormActivated( false );
					$quiz->setText( 'AAZZAAZZ' ); // cspell:disable-line.
					$quizMapper->save( $quiz );
				}

				$forms                = $formMapper->fetch( $quizId );
				$prerequisiteQuizList = $prerequisiteMapper->fetchQuizIds( $quizId );

			} else {
				//WpProQuiz_View_View::admin_notices( sprintf( esc_html_x('%s title or %s description are not filled', 'Quiz title or quiz description are not filled', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' ), learndash_get_custom_label_lower( 'quiz' )));
			}
		} elseif ( $quizId ) {
			$quiz                 = $quizMapper->fetch( $quizId );
			$forms                = $formMapper->fetch( $quizId );
			$prerequisiteQuizList = $prerequisiteMapper->fetchQuizIds( $quizId );
		}

		$this->view = new WpProQuiz_View_QuizEdit();

		$this->view->quiz                 = $quiz;
		$this->view->forms                = $forms;
		$this->view->prerequisiteQuizList = $prerequisiteQuizList;
		$this->view->templates            = $templateMapper->fetchAll( WpProQuiz_Model_Template::TEMPLATE_TYPE_QUIZ, false );
		$this->view->quizList             = $quizMapper->fetchAllAsArray( array( 'id', 'name' ), $quizId ? array( $quizId ) : array() );
		$this->view->captchaIsInstalled   = class_exists( 'ReallySimpleCaptcha' );

		// translators: placeholder: quiz.
		$this->view->header = $quizId ? sprintf( esc_html_x( 'Edit %s', 'placeholder: quiz', 'learndash' ), learndash_get_custom_label_lower( 'quiz' ) ) : sprintf( esc_html_x( 'Create %s', 'Create quiz', 'learndash' ), learndash_get_custom_label_lower( 'quiz' ) );

		$this->view->show( $get );
	}

	private function addUpdateQuiz( $get = null, $post = null ) {
		if ( empty( $get ) ) {
			$get = $_GET;
		}
		if ( ! empty( $post ) ) {
			$this->_post = $post;
		}

		$quizId = isset( $get['quizId'] ) ? (int) $get['quizId'] : 0;

		if ( $quizId ) {
			if ( ! current_user_can( 'wpProQuiz_edit_quiz' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'learndash' ) );
			}
		} else {
			if ( ! current_user_can( 'wpProQuiz_add_quiz' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'learndash' ) );
			}
		}

		$prerequisiteMapper = new WpProQuiz_Model_PrerequisiteMapper();
		$quizMapper         = new WpProQuiz_Model_QuizMapper();
		$formMapper         = new WpProQuiz_Model_FormMapper();
		$templateMapper     = new WpProQuiz_Model_TemplateMapper();

		$quiz                 = new WpProQuiz_Model_Quiz();
		$forms                = null;
		$prerequisiteQuizList = array();

		if ( ! empty( $get['post_id'] ) ) {
			$quiz_post = get_post( $get['post_id'] );
			if ( ( ! empty( $quiz_post ) ) && ( is_a( $quiz_post, 'WP_Post' ) ) ) {
				$this->_post['name'] = $quiz_post->post_title;
			}
		}

		if ( $quizId && $quizMapper->exists( $quizId ) == 0 ) {
			// translators: placeholder: Quiz.
			WpProQuiz_View_View::admin_notices( sprintf( esc_html_x( '%s not found', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ), 'error' );

			return;
		}

		if ( ( ( isset( $this->_post['templateSaveList'] ) ) && ( intval( $this->_post['templateSaveList'] ) > 0 ) ) || ( ( isset( $this->_post['templateName'] ) ) && ( ! empty( $this->_post['templateName'] ) ) ) ) {
			$template = $this->saveTemplate();
		}

		if ( isset( $this->_post['form'] ) ) {
			if ( isset( $this->_post['resultGradeEnabled'] ) ) {
				$this->_post['result_text'] = $this->filterResultTextGrade( $this->_post );
			}

			// Patch to only set Statistics on if post from form save.
			// LEARNDASH-1434 & LEARNDASH-1481.
			if ( ! isset( $this->_post['statisticsOn'] ) ) {
				$this->_post['statisticsOn']          = '0';
				$this->_post['viewProfileStatistics'] = '0';
			}

			$quiz = new WpProQuiz_Model_Quiz( $this->_post );
			$quiz->setId( $quizId );
			if ( ! empty( $get['post_id'] ) ) {
				$quiz_post = get_post( $get['post_id'] );
				if ( ( ! empty( $quiz_post ) ) && ( is_a( $quiz_post, 'WP_Post' ) ) ) {
					$quiz->setPostId = $quiz_post->ID;
				}
			}

			if ( $this->checkValidit( $this->_post ) ) {
				$quiz->setText( 'AAZZAAZZ' ); // cspell:disable-line.

				$quiz2 = $quizMapper->save( $quiz );

				if ( ( empty( $quizId ) ) && ( isset( $get['post_id'] ) ) && ( ! empty( $get['post_id'] ) ) ) {
					learndash_update_setting( $get['post_id'], 'quiz_pro', $quiz->getId() );

					$quiz_id_primary_new = absint( learndash_get_quiz_primary_shared( $quiz->getId(), false ) );
					if ( empty( $quiz_id_primary_new ) ) {
						update_post_meta( $get['post_id'], 'quiz_pro_primary_' . $quiz->getId(), $quiz->getId() );
					}
				}

				$quizId = $quiz->getId();

				$prerequisiteMapper->delete( $quizId );

				if ( $quiz->isPrerequisite() && ! empty( $this->_post['prerequisiteList'] ) ) {
					$prerequisiteMapper->save( $quizId, $this->_post['prerequisiteList'] );
					$quizMapper->activateStatitic( $this->_post['prerequisiteList'], 1440 );
				}

				if ( ! $this->formHandler( $quiz->getId(), $this->_post ) ) {
					$quiz->setFormActivated( false );
					$quiz->setText( ' AAZZAAZZ' ); // cspell:disable-line.
					$quizMapper->save( $quiz );
				}
			} else {
				return false;
			}
		}
	}

	private function getEditQuiz( $get = null, $post = null ) {

		if ( empty( $get ) ) {
			$get = $_GET;
		}
		if ( ! empty( $post ) ) {
			$this->_post = $post;
		}

		$quizId = isset( $get['quizId'] ) ? (int) $get['quizId'] : 0;

		$prerequisiteMapper = new WpProQuiz_Model_PrerequisiteMapper();
		$quizMapper         = new WpProQuiz_Model_QuizMapper();
		$formMapper         = new WpProQuiz_Model_FormMapper();
		$templateMapper     = new WpProQuiz_Model_TemplateMapper();

		$quiz                 = new WpProQuiz_Model_Quiz();
		$forms                = null;
		$prerequisiteQuizList = array();

		if ( ! empty( $get['post_id'] ) ) {
			$quiz_post = get_post( $get['post_id'] );
			if ( ( ! empty( $quiz_post ) ) && ( is_a( $quiz_post, 'WP_Post' ) ) ) {
				$this->_post['name'] = $quiz_post->post_title;
			}
		}

		if ( $quizId && $quizMapper->exists( $quizId ) == 0 ) {
			WpProQuiz_View_View::admin_notices( esc_html__( 'Missing ProQuiz Associated Settings.', 'learndash' ), 'error' );

			$this->view                       = new WpProQuiz_View_QuizEdit();
			$this->view->quiz                 = $quiz;
			$this->view->forms                = $forms;
			$this->view->prerequisiteQuizList = $prerequisiteQuizList;
			$this->view->templates            = $templateMapper->fetchAll( WpProQuiz_Model_Template::TEMPLATE_TYPE_QUIZ, false );
			$this->view->quizList             = $quizMapper->fetchAllAsArray( array( 'id', 'name' ), $quizId ? array( $quizId ) : array() );
			$this->view->captchaIsInstalled   = class_exists( 'ReallySimpleCaptcha' );
			return $this->view;
		}

		if ( ( isset( $get['templateLoad'] ) ) && ( ! empty( $get['templateLoad'] ) ) && ( isset( $get['templateLoadId'] ) ) && ( ! empty( $get['templateLoadId'] ) ) ) {
			$template = $templateMapper->fetchById( (int) $get['templateLoadId'] );
			if ( ( $template ) && is_a( $template, 'WpProQuiz_Model_Template' ) ) {
				$data = $template->getData();

				if ( null !== $data ) {
					$quiz = $data['quiz'];
					$quiz->setId( $quizId );
					$quiz->setName( $this->_post['name'] );
					$quiz->setText( 'AAZZAAZZ' ); // cspell:disable-line.
					$forms                = $data['forms'];
					$prerequisiteQuizList = $data['prerequisiteQuizList'];
				}
			}
		} else {
			$quiz                 = $quizMapper->fetch( $quizId );
			$forms                = $formMapper->fetch( $quizId );
			$prerequisiteQuizList = $prerequisiteMapper->fetchQuizIds( $quizId );
		}
		$this->view = new WpProQuiz_View_QuizEdit();

		$this->view->quiz                 = $quiz;
		$this->view->forms                = $forms;
		$this->view->prerequisiteQuizList = $prerequisiteQuizList;
		$this->view->templates            = $templateMapper->fetchAll( WpProQuiz_Model_Template::TEMPLATE_TYPE_QUIZ, false );
		$this->view->quizList             = $quizMapper->fetchAllAsArray( array( 'id', 'name' ), $quizId ? array( $quizId ) : array() );
		$this->view->captchaIsInstalled   = class_exists( 'ReallySimpleCaptcha' );

		$this->view->header = $quizId
		// translators: placeholder: quiz.
		? sprintf( esc_html_x( 'Edit %s', 'placeholder: quiz', 'learndash' ), learndash_get_custom_label_lower( 'quiz' ) )
		// translators: placeholder: quiz.
		: sprintf( esc_html_x( 'Create %s', 'Create quiz', 'learndash' ), learndash_get_custom_label_lower( 'quiz' ) );

		return $this->view;
	}

	/**
	 * Check Lock
	 *
	 * @deprecated 3.6.0
	 */
	public function checkLock() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.6.0' );
		}
	}

	public function isLockQuiz( $quizId ) {
		$quizId = (int) $this->_post['quizId'];
		$userId = get_current_user_id();
		$data   = array();

		$bypass_course_limits_admin_users = learndash_can_user_bypass( $userId, 'learndash_quiz_lock', $quizId, $this->_post );
		if ( true === $bypass_course_limits_admin_users ) {
			return $data;
		}

		$lockMapper         = new WpProQuiz_Model_LockMapper();
		$quizMapper         = new WpProQuiz_Model_QuizMapper();
		$prerequisiteMapper = new WpProQuiz_Model_PrerequisiteMapper();

		$quiz = $quizMapper->fetch( $this->_post['quizId'] );

		if ( null === $quiz || $quiz->getId() <= 0 ) {
			return null;
		}

		if ( $this->isPreLockQuiz( $quiz ) ) {
			$lockIp     = false;
			$lockCookie = false;
			$cookieJson = null;

			/*
			if ( empty( $userId ) ) {
				$lockIp = $lockMapper->isLock($this->_post['quizId'], $this->getIp(), $userId, WpProQuiz_Model_Lock::TYPE_QUIZ);
				$cookieTime = $quiz->getQuizRunOnceTime();

				if(isset($this->_cookie['wpProQuiz_lock']) && $userId == 0 && $quiz->isQuizRunOnceCookie()) {
					$cookieJson = json_decode( $this->_cookie['wpProQuiz_lock'], true );
					$repeats = '';
					if ( ( isset( $this->_post['quiz'] ) ) && ( ! empty( $this->_post['quiz'] ) ) ) {
						$repeats = learndash_quiz_get_repeats( $this->_post['quiz'] );
					}
					if ( '' !== $repeats ) {
						$repeats = absint( $repeats );

						if ( ( $cookieJson !== false ) && ( isset( $cookieJson[ $this->_post['quizId'] ] ) ) ) {
							$cookie_quiz = $cookieJson[ $this->_post['quizId'] ];
							$cookie_quiz = learndash_quiz_convert_lock_cookie( $cookie_quiz );
							if ( ( $cookie_quiz['time'] === $cookieTime ) && ( $cookie_quiz['count'] > $repeats ) ) {
								$lockCookie = true;
							} else {
								$lockIp = false;
							}
						}
					} else {
						$lockIp = false;
					}
				}
			}
			*/
			$data['lock'] = array(
				'is'  => ( $lockIp || $lockCookie ),
				'pre' => true,
			);
		}

		if ( $quiz->isPrerequisite() ) {
			$quizIds = array();

			if ( $userId > 0 ) {
				$quizIds = $prerequisiteMapper->getNoPrerequisite( $quizId, $userId );
			} else {
				$checkIds = $prerequisiteMapper->fetchQuizIds( $quizId );

				if ( isset( $this->_cookie['wpProQuiz_result'] ) ) {
					$r = json_decode( $this->_cookie['wpProQuiz_result'], true );

					if ( null !== $r && is_array( $r ) ) {
						foreach ( $checkIds as $id ) {
							if ( ! isset( $r[ $id ] ) || ! $r[ $id ] ) {
								$quizIds[] = $id;
							}
						}
					}
				} else {
					$quizIds = $checkIds;
				}
			}

			if ( ! empty( $quizIds ) ) {
				$post_quiz_ids = array();
				foreach ( $quizIds as $pro_quiz_id ) {
					$post_quiz_id = learndash_get_quiz_id_by_pro_quiz_id( $pro_quiz_id );
					if ( ! empty( $post_quiz_id ) ) {
						$post_quiz_ids[ $post_quiz_id ] = $pro_quiz_id;
					}
				}
				if ( ! empty( $post_quiz_ids ) ) {
					// Commenting code as `$post_course_id` is not used.
					// if ( ( isset( $this->_post['course_id'] ) ) && ( ! empty( $this->_post['course_id'] ) ) ) {
					// 	$post_course_id = intval( $this->_post['course_id'] );
					// } else {
					// 	$post_course_id = 0;
					// }
					// $post_course_id = apply_filters(
					//  'learndash_quiz_prerequisite_course_check',
					//  $post_course_id,
					//  $userId,
					// 	$post_quiz_ids
					// );

					$post_quiz_ids = learndash_is_quiz_notcomplete( $userId, $post_quiz_ids, true, -1 );
					if ( ! empty( $post_quiz_ids ) ) {
						$quizIds = array_values( $post_quiz_ids );
					} else {
						$quizIds = array();
					}
				}

				if ( ! empty( $quizIds ) ) {
					$names = $quizMapper->fetchCol( $quizIds, 'name' );

					if ( ! empty( $names ) ) {
						$data['prerequisite'] = implode( ', ', $names );
					}
				}
			}
		}

		if ( $quiz->isStartOnlyRegisteredUser() ) {
			$data['startUserLock'] = (int) ! is_user_logged_in();
		}

		return $data;
	}

	public function loadQuizData() {
		$quizId = (int) $_POST['quizId'];
		$userId = get_current_user_id();

		$quizMapper          = new WpProQuiz_Model_QuizMapper();
		$toplistController   = new WpProQuiz_Controller_Toplist();
		$statisticController = new WpProQuiz_Controller_Statistics();

		$quiz = $quizMapper->fetch( $quizId );

		$data = array();

		if ( null === $quiz || $quiz->getId() <= 0 ) {
			return array();
		}

		$data['toplist'] = $toplistController->getAddToplist( $quiz );

		if ( $quiz->isShowAverageResult() ) {
			$data['averageResult'] = $statisticController->getAverageResult( $quizId );
		} else {
			$data['averageResult'] = 0;
		}

		return $data;
	}

	private function resetLock( $quizId ) {
		if ( ( isset( $_POST['nonce'] ) ) && ( ! empty( $_POST['nonce'] ) ) && ( wp_verify_nonce( $_POST['nonce'], 'learndash-wpproquiz-reset-lock' ) ) ) {
			if ( ! current_user_can( 'wpProQuiz_edit_quiz' ) ) {
				exit;
			}

			$lm = new WpProQuiz_Model_LockMapper();
			$qm = new WpProQuiz_Model_QuizMapper();

			$q = $qm->fetch( $quizId );

			if ( $q->getId() > 0 ) {

				$q->setQuizRunOnceTime( time() );

				$qm->save( $q );

				$lm->deleteByQuizId( $quizId, WpProQuiz_Model_Lock::TYPE_QUIZ );
			}
		}
		exit;
	}

	private function showAction() {
		if ( ! current_user_can( 'wpProQuiz_show' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'learndash' ) );
		}

		$this->view = new WpProQuiz_View_QuizOverall();

		// Removed per LEARNDASH-4602
		//$m = new WpProQuiz_Model_QuizMapper();
		//$this->view->quiz = $m->fetchAll();

		$this->view->show();
	}

	private function editAction( $id ) {

		if ( ! current_user_can( 'wpProQuiz_edit_quiz' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'learndash' ) );
		}

		$prerequisiteMapper = new WpProQuiz_Model_PrerequisiteMapper();
		$quizMapper         = new WpProQuiz_Model_QuizMapper();
		$formMapper         = new WpProQuiz_Model_FormMapper();
		$templateMapper     = new WpProQuiz_Model_TemplateMapper();
		$m                  = new WpProQuiz_Model_QuizMapper();

		$this->view = new WpProQuiz_View_QuizEdit();
		// translators: placeholder: quiz.
		$this->view->header = sprintf( esc_html_x( 'Edit %s', 'placeholder: quiz', 'learndash' ), learndash_get_custom_label_lower( 'quiz' ) );

		$forms                = $formMapper->fetch( $id );
		$prerequisiteQuizList = $prerequisiteMapper->fetchQuizIds( $id );

		if ( $m->exists( $id ) == 0 ) {
			// translators: placeholder: Quiz.
			WpProQuiz_View_View::admin_notices( sprintf( esc_html_x( '%s not found', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ), 'error' );
			return;
		}

		if ( isset( $this->_post['submit'] ) ) {

			if ( isset( $this->_post['resultGradeEnabled'] ) ) {
				$this->_post['result_text'] = $this->filterResultTextGrade( $this->_post );
			}

			$quiz = new WpProQuiz_Model_Quiz( $this->_post );
			$quiz->setId( $id );

			if ( $this->checkValidit( $this->_post ) ) {

				$prerequisiteMapper = new WpProQuiz_Model_PrerequisiteMapper();

				$prerequisiteMapper->delete( $id );

				if ( $quiz->isPrerequisite() && ! empty( $this->_post['prerequisiteList'] ) ) {
					$prerequisiteMapper->save( $id, $this->_post['prerequisiteList'] );
					$quizMapper->activateStatitic( $this->_post['prerequisiteList'], 1440 );
				}

				if ( ! $this->formHandler( $quiz->getId(), $this->_post ) ) {
					$quiz->setFormActivated( false );
				}

				$quizMapper->save( $quiz );

				$this->showAction();

				return;
			}
		} elseif ( isset( $this->_post['template'] ) || isset( $this->_post['templateLoad'] ) ) {
			if ( isset( $this->_post['template'] ) ) {
				$template = $this->saveTemplate();
			} else {
				$template = $templateMapper->fetchById( $this->_post['templateLoadId'] );
			}

			$data = $template->getData();

			if ( null !== $data ) {
				$quiz                 = $data['quiz'];
				$forms                = $data['forms'];
				$prerequisiteQuizList = $data['prerequisiteQuizList'];
			}
		} else {
			$quiz = $m->fetch( $id );
		}

		$this->view->quiz                 = $quiz;
		$this->view->prerequisiteQuizList = $prerequisiteQuizList;
		$this->view->quizList             = $m->fetchAllAsArray( array( 'id', 'name' ), array( $id ) );
		$this->view->captchaIsInstalled   = class_exists( 'ReallySimpleCaptcha' );
		$this->view->forms                = $forms;
		$this->view->templates            = $templateMapper->fetchAll( WpProQuiz_Model_Template::TEMPLATE_TYPE_QUIZ, false );
		$this->view->show();
	}

	private function createAction() {

		if ( ! current_user_can( 'wpProQuiz_add_quiz' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'learndash' ) );
		}

		$this->view = new WpProQuiz_View_QuizEdit();
		// translators: placeholder: quiz.
		$this->view->header = sprintf( esc_html_x( 'Create %s', 'placeholder: quiz', 'learndash' ), learndash_get_custom_label_lower( 'quiz' ) );

		$forms                = null;
		$prerequisiteQuizList = array();

		$m              = new WpProQuiz_Model_QuizMapper();
		$templateMapper = new WpProQuiz_Model_TemplateMapper();

		if ( isset( $this->_post['submit'] ) ) {

			if ( isset( $this->_post['resultGradeEnabled'] ) ) {
				$this->_post['result_text'] = $this->filterResultTextGrade( $this->_post );
			}

			$quiz       = new WpProQuiz_Model_Quiz( $this->_post );
			$quizMapper = new WpProQuiz_Model_QuizMapper();

			if ( $this->checkValidit( $this->_post ) ) {
				$quizMapper->save( $quiz );

				$id                 = $quizMapper->getInsertId();
				$prerequisiteMapper = new WpProQuiz_Model_PrerequisiteMapper();

				if ( $quiz->isPrerequisite() && ! empty( $this->_post['prerequisiteList'] ) ) {
					$prerequisiteMapper->save( $id, $this->_post['prerequisiteList'] );
					$quizMapper->activateStatitic( $this->_post['prerequisiteList'], 1440 );
				}

				if ( ! $this->formHandler( $id, $this->_post ) ) {
					$quiz->setFormActivated( false );
					$quizMapper->save( $quiz );
				}

				$this->showAction();
				return;
			}
		} elseif ( isset( $this->_post['template'] ) || isset( $this->_post['templateLoad'] ) ) {
			if ( isset( $this->_post['template'] ) ) {
				$template = $this->saveTemplate();
			} else {
				$template = $templateMapper->fetchById( $this->_post['templateLoadId'] );
			}

			$data = $template->getData();

			if ( null !== $data ) {
				$quiz                 = $data['quiz'];
				$forms                = $data['forms'];
				$prerequisiteQuizList = $data['prerequisiteQuizList'];
			}
		} else {
			$quiz = new WpProQuiz_Model_Quiz();
		}

		$this->view->quiz                 = $quiz;
		$this->view->prerequisiteQuizList = $prerequisiteQuizList;
		$this->view->quizList             = $m->fetchAllAsArray( array( 'id', 'name' ) );
		$this->view->captchaIsInstalled   = class_exists( 'ReallySimpleCaptcha' );
		$this->view->forms                = $forms;
		$this->view->templates            = $templateMapper->fetchAll( WpProQuiz_Model_Template::TEMPLATE_TYPE_QUIZ, false );
		$this->view->show();
	}

	private function saveTemplate() {
		$templateMapper = new WpProQuiz_Model_TemplateMapper();

		if ( isset( $this->_post['resultGradeEnabled'] ) ) {
			$this->_post['result_text'] = $this->filterResultTextGrade( $this->_post );
		}

		$quiz = new WpProQuiz_Model_Quiz( $this->_post );

		if ( $quiz->isPrerequisite() && ! empty( $this->_post['prerequisiteList'] ) && ! $quiz->isStatisticsOn() ) {
			$quiz->setStatisticsOn( true );
			$quiz->setStatisticsIpLock( 1440 );
		}

		$form = $this->_post['form'];

		unset( $form[0] );

		$forms = array();

		foreach ( $form as $f ) {
			if ( isset( $f['fieldname'] ) ) {
				$f['fieldname'] = trim( $f['fieldname'] );

				if ( empty( $f['fieldname'] ) ) {
					continue;
				}

				if ( absint( $f['form_id'] ) && absint( $f['form_delete'] ) ) {
					continue;
				}

				if ( WpProQuiz_Model_Form::FORM_TYPE_SELECT == $f['type'] || WpProQuiz_Model_Form::FORM_TYPE_RADIO == $f['type'] ) {
					if ( ! empty( $f['data'] ) ) {
						$items     = explode( "\n", $f['data'] );
						$f['data'] = array();

						foreach ( $items as $item ) {
							$item = trim( $item );

							if ( ! empty( $item ) ) {
								$f['data'][] = $item;
							}
						}
					}
				}

				if ( empty( $f['data'] ) || ! is_array( $f['data'] ) ) {
					$f['data'] = null;
				}

				$forms[] = new WpProQuiz_Model_Form( $f );
			}
		}

		$data = array(
			'quiz'                 => $quiz,
			'forms'                => $forms,
			'prerequisiteQuizList' => isset( $this->_post['prerequisiteList'] ) ? $this->_post['prerequisiteList'] : array(),
		);

		$quiz_post_id = $quiz->getPostId();
		if ( ! empty( $quiz_post_id ) ) {
			$data[ '_' . learndash_get_post_type_slug( 'quiz' ) ] = learndash_get_setting( $quiz_post_id );
		}

		// Zero out the ProQuiz Post ID and the reference for the associated settings.
		$data['quiz']->setPostId( 0 );
		$data[ '_' . learndash_get_post_type_slug( 'quiz' ) ]['quiz_pro'] = 0;

		$template = new WpProQuiz_Model_Template();

		if ( '0' == $this->_post['templateSaveList'] ) {
			$template->setName( trim( $this->_post['templateName'] ) );
		} else {
			$template = $templateMapper->fetchById( $this->_post['templateSaveList'], false );
		}

		$template->setType( WpProQuiz_Model_Template::TEMPLATE_TYPE_QUIZ );
		$template->setData( $data );

		$templateMapper->save( $template );

		return $template;
	}

	private function formHandler( $quizId, $post ) {
		if ( ! isset( $post['form'] ) ) {
			return false;
		}

		$form = $post['form'];

		unset( $form[0] );

		if ( empty( $form ) ) {
			return false;
		}

		$formMapper = new WpProQuiz_Model_FormMapper();

		$deleteIds = array();
		$forms     = array();
		$sort      = 0;

		foreach ( $form as $f ) {

			if ( ( ! isset( $f['fieldname'] ) ) || ( empty( $f['fieldname'] ) ) ) {
				continue;
			}

			$f['fieldname'] = trim( $f['fieldname'] );

			if ( (int) $f['form_id'] && (int) $f['form_delete'] ) {
				$deleteIds[] = (int) $f['form_id'];
				continue;
			}

			$f['sort']   = $sort++;
			$f['quizId'] = $quizId;

			if ( WpProQuiz_Model_Form::FORM_TYPE_SELECT == $f['type'] || WpProQuiz_Model_Form::FORM_TYPE_RADIO == $f['type'] ) {
				if ( ! empty( $f['data'] ) ) {
					$items     = explode( "\n", $f['data'] );
					$f['data'] = array();

					foreach ( $items as $item ) {
						$item = trim( $item );

						if ( ! empty( $item ) ) {
							$f['data'][] = $item;
						}
					}
				}
			}

			if ( empty( $f['data'] ) || ! is_array( $f['data'] ) ) {
				$f['data'] = null;
			}

			$forms[] = new WpProQuiz_Model_Form( $f );
		}

		if ( ! empty( $deleteIds ) ) {
			$formMapper->deleteForm( $deleteIds, $quizId );
		}

		$formMapper->update( $forms );

		return ! empty( $forms );
	}

	private function checkValidit( $post ) {

		if ( ( isset( $post['name'] ) ) && ( ! empty( $post['name'] ) ) && ( isset( $post['text'] ) ) && ( ! empty( $post['text'] ) ) ) {
			return true;
		}

		if ( ( isset( $post['post_ID'] ) ) && ( ! empty( $post['post_ID'] ) ) ) {
			if ( learndash_get_post_type_slug( 'quiz' ) === get_post_type( absint( $post['post_ID'] ) ) ) {
				return true;
			}
		}
	}

	private function filterResultTextGrade( $post ) {
		$result = array();

		$sorted = array();
		if ( isset( $post['resultTextGrade'] ) ) {
			$sorted = learndash_quiz_result_message_sort( $post['resultTextGrade'] );
			return $sorted;
		}

		return $result;
	}

	private function setResultCookie( WpProQuiz_Model_Quiz $quiz ) {
		$prerequisite = new WpProQuiz_Model_PrerequisiteMapper();

		if ( get_current_user_id() == 0 && $prerequisite->isQuizId( $quiz->getId() ) ) {
			$cookieData = array();

			if ( isset( $this->_cookie['wpProQuiz_result'] ) ) {
				$d = json_decode( $this->_cookie['wpProQuiz_result'], true );

				if ( null !== $d && is_array( $d ) ) {
					$cookieData = $d;
				}
			}

			$cookieData[ $quiz->getId() ] = 1;

			$url = wp_parse_url( get_bloginfo( 'url' ) );

			setcookie( 'wpProQuiz_result', wp_json_encode( $cookieData ), time() + 60 * 60 * 24 * 300, empty( $url['path'] ) ? '/' : $url['path'] );
		}
	}

	public function completedQuiz() {
		$lockMapper     = new WpProQuiz_Model_LockMapper();
		$quizMapper     = new WpProQuiz_Model_QuizMapper();
		$categoryMapper = new WpProQuiz_Model_CategoryMapper();

		$is100P = 100 == $this->_post['results']['comp']['result'];

		$userId = get_current_user_id();

		$quiz = $quizMapper->fetch( $this->_post['quizId'] );
		if ( ( isset( $this->_post['quiz'] ) ) && ( ! empty( $this->_post['quiz'] ) ) ) {
			if ( absint( $this->_post['quiz'] ) !== absint( $quiz->getPostId() ) ) {
				$quiz->setPostId( absint( $this->_post['quiz'] ) );
			}
		}

		if ( null === $quiz || $quiz->getId() <= 0 ) {
			exit;
		}

		$categories = $categoryMapper->fetchByQuiz( $quiz );

		$this->setResultCookie( $quiz );

		$this->emailNote( $quiz, $this->_post['results']['comp'], $categories );

		if ( ! $this->isPreLockQuiz( $quiz ) ) {
			$statistics            = new WpProQuiz_Controller_Statistics();
			$statisticRefMapper_id = $statistics->save( $quiz );
			/**
			 * Fires after the pro quiz is marked completed.
			 *
			 * @param boolean|int The value is a statistic reference ID if the statistics are saved otherwise false.
			 */
			do_action( 'wp_pro_quiz_completed_quiz', $statisticRefMapper_id );

			if ( $is100P ) {
				/**
				 * Fires after the pro quiz is completed hundred percent.
				 */
				do_action( 'wp_pro_quiz_completed_quiz_100_percent' );
			}

			exit;
		}

		$lockMapper->deleteOldLock( 60 * 60 * 24 * 7, $this->_post['quizId'], time(), WpProQuiz_Model_Lock::TYPE_QUIZ, 0 );

		$lockIp     = false;
		$lockCookie = false;
		$cookieTime = $quiz->getQuizRunOnceTime();
		$cookieJson = null;

		if ( empty( $userId ) ) {
			$lockIp = $lockMapper->isLock( $this->_post['quizId'], $this->getIp(), $userId, WpProQuiz_Model_Lock::TYPE_QUIZ );

			if ( isset( $this->_cookie['wpProQuiz_lock'] ) && $userId == 0 && $quiz->isQuizRunOnceCookie() ) {
				$cookieJson = json_decode( $this->_cookie['wpProQuiz_lock'], true );
				$repeats    = '';
				if ( ( isset( $this->_post['quiz'] ) ) && ( ! empty( $this->_post['quiz'] ) ) ) {
					$repeats = learndash_quiz_get_repeats( $this->_post['quiz'] );
				}
				if ( '' !== $repeats ) {
					$repeats = absint( $repeats );

					if ( ( $cookieJson !== false ) && ( isset( $cookieJson[ $this->_post['quizId'] ] ) ) ) {
						$cookie_quiz = $cookieJson[ $this->_post['quizId'] ];
						$cookie_quiz = learndash_quiz_convert_lock_cookie( $cookie_quiz );
						if ( ( $cookie_quiz['time'] === $cookieTime ) && ( $cookie_quiz['count'] > $repeats ) ) {
							$lockCookie = true;
						} else {
							$lockIp = false;
						}
					}
				} else {
					$lockIp = false;
				}
			} else {
				$lockIp = false;
			}
		} else {
			$repeats = '';
			if ( ( isset( $this->_post['quiz'] ) ) && ( ! empty( $this->_post['quiz'] ) ) ) {
				$repeats = learndash_quiz_get_repeats( $this->_post['quiz'] );
			}
			if ( '' !== $repeats ) {
				$repeats = absint( $repeats );

				$usermeta = get_user_meta( $userId, '_sfwd-quizzes', true );
				$usermeta = maybe_unserialize( $usermeta );

				if ( ! is_array( $usermeta ) ) {
					$usermeta = array();
				}

				if ( ! empty( $usermeta ) ) {
					$attempts_count = 0;
					foreach ( $usermeta as $k => $v ) {
						if ( ( absint( $v['quiz'] ) === absint( $this->_post['quiz'] ) ) ) {
							if ( ! empty( $this->_post['course_id'] ) ) {
								if ( ( isset( $v['course'] ) ) && ( ! empty( $v['course'] ) ) && ( absint( $v['course'] ) === absint( $this->_post['course_id'] ) ) ) {
									// Count the number of time the student has taken the quiz where the course_id matches.
									$attempts_count++;
								}
							} elseif ( empty( $this->_post['course_id'] ) ) {
								if ( ( isset( $v['course'] ) ) && ( empty( $v['course'] ) ) && ( absint( $v['course'] ) === absint( $this->_post['course_id'] ) ) ) {
									// Count the number of time the student has taken the quiz where the course_id is zero.
									$attempts_count++;
								}
							}
						}
					}
					if ( $attempts_count > $repeats ) {
						$bypass_course_limits_admin_users = learndash_can_user_bypass( $userId, 'learndash_course_lesson_access_from', $this->_post['course_id'], get_post( $this->_post['quiz'] ) );

						// For logged in users to allow an override filter.
						/** This filter is documented in includes/course/ld-course-progress.php */
						$bypass_course_limits_admin_users = apply_filters( 'learndash_prerequities_bypass', $bypass_course_limits_admin_users, $userId, $this->_post['course_id'], $this->_post['quiz'] );
						if ( true !== $bypass_course_limits_admin_users )  {
							$lockIp = true;
						}
					}
				}
			}
		}

		if ( ! $lockIp && ! $lockCookie ) {
			$statistics = new WpProQuiz_Controller_Statistics();
			learndash_quiz_debug_log_message( 'calling completeQuiz->save' );
			learndash_quiz_debug_log_message( 'quiz<pre>' . print_r( $quiz, true ) . '</pre>' );

			$statisticRefMapper_id = $statistics->save( $quiz );

			learndash_quiz_debug_log_message( 'calling do_action(wp_pro_quiz_completed_quiz) statisticRefMapper_id ' . $statisticRefMapper_id );

			if ( $is100P ) {
				/** This filter is documented in includes/lib/wp-pro-quiz/lib/controller/WpProQuiz_Controller_Quiz.php */
				do_action( 'wp_pro_quiz_completed_quiz_100_percent' );
			}

			if ( get_current_user_id() == 0 && $quiz->isQuizRunOnceCookie() ) {
				$cookieJson = false;
				if ( isset( $this->_cookie['wpProQuiz_lock'] ) ) {
					$cookieJson = json_decode( $this->_cookie['wpProQuiz_lock'], true );
				}
				if ( ( $cookieJson !== false ) && ( isset( $cookieJson[ $this->_post['quizId'] ] ) ) ) {
					$cookie_quiz = $cookieJson[ $this->_post['quizId'] ];
				} else {
					$cookie_quiz = array();
				}
				$cookie_quiz = learndash_quiz_convert_lock_cookie( $cookie_quiz );
				$cookieTime  = $quiz->getQuizRunOnceTime();
				if ( $cookie_quiz['time'] === $cookieTime ) {
					$cookie_quiz['count'] += 1;
				} else {
					$cookie_quiz['time']  = $cookieTime;
					$cookie_quiz['count'] = 1;
				}
				$cookieJson[ $this->_post['quizId'] ] = $cookie_quiz;

				$url = parse_url( get_bloginfo( 'url' ) );

				setcookie( 'wpProQuiz_lock', json_encode( $cookieJson ), time() + 60 * 60 * 24 * 60, empty( $url['path'] ) ? '/' : $url['path'] );
			}

			if ( empty( $userId ) ) {

				if ( ! $lockMapper->isLock( $this->_post['quizId'], $this->getIp(), $userId, WpProQuiz_Model_Lock::TYPE_QUIZ ) ) {

					$lock = new WpProQuiz_Model_Lock();

					$lock->setUserId( get_current_user_id() );
					$lock->setQuizId( $this->_post['quizId'] );
					$lock->setLockDate( time() );
					$lock->setLockIp( $this->getIp() );
					$lock->setLockType( WpProQuiz_Model_Lock::TYPE_QUIZ );

					$lockMapper->insert( $lock );
				}
			}

			/** This action is documented in includes/lib/wp-pro-quiz/lib/controller/WpProQuiz_Controller_Quiz.php */
			do_action( 'wp_pro_quiz_completed_quiz', $statisticRefMapper_id );
		} else {
			echo wp_json_encode( null );
		}

		exit;
	}

	public function isPreLockQuiz( WpProQuiz_Model_Quiz $quiz ) {
		$userId = get_current_user_id();

		if ( $quiz->isQuizRunOnce() ) {
			switch ( $quiz->getQuizRunOnceType() ) {
				case WpProQuiz_Model_Quiz::QUIZ_RUN_ONCE_TYPE_ALL:
					return true;
				case WpProQuiz_Model_Quiz::QUIZ_RUN_ONCE_TYPE_ONLY_USER:
					return $userId > 0;
				case WpProQuiz_Model_Quiz::QUIZ_RUN_ONCE_TYPE_ONLY_ANONYM:
					return 0 == $userId;
			}
		}

		return false;
	}

	private function getIp() {
		if ( get_current_user_id() > 0 ) {
			return '0';
		} else {
			return filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP );
		}
	}

	private function emailNote( WpProQuiz_Model_Quiz $quiz, $result, $categories ) {
		$globalMapper = new WpProQuiz_Model_GlobalSettingsMapper();

		$email_settings_admin = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Quizzes_Admin_Email' );

		$email_settings_user = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Quizzes_User_Email' );

		$user = wp_get_current_user();

		$r = array(
			'$userId'     => $user->ID,
			'$username'   => $user->display_name,
			'$userlogin'  => $user->user_login,
			'$quizname'   => $quiz->getName(),
			'$result'     => $result['result'] . '%',
			'$points'     => $result['points'],
			'$ip'         => '', //filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP),
			'$categories' => empty( $result['cats'] ) ? '' : $this->setCategoryOverview( $result['cats'], $categories ),
		);

		if ( 0 == $user->ID ) {
			$r['$username'] = $r['$ip'];
		}

		if ( $quiz->isUserEmailNotification() ) {
			$user_mail_message = str_replace( array_keys( $r ), $r, $email_settings_user['user_mail_message'] );
			$user_mail_subject = str_replace( array_keys( $r ), $r, $email_settings_user['user_mail_subject'] );

			/**
			 * Filters quiz email note user message.
			 *
			 * @param string               $message    Quiz email user message.
			 * @param array                $quiz_data  An array of quiz data.
			 * @param WpProQuiz_Model_Quiz $quiz       Quiz object.
			 * @param array                $result     Quiz result.
			 * @param array                $categories Quiz categories
			 */
			$user_mail_message = apply_filters( 'learndash_quiz_email_note_user_message', $user_mail_message, $r, $quiz, $result, $categories );

			$headers = '';

			if ( ( isset( $email_settings_user['user_mail_from'] ) ) && ( ! empty( $email_settings_user['user_mail_from'] ) ) && ( is_email( $email_settings_user['user_mail_from'] ) ) ) {
				if ( ( ! isset( $email_settings_user['user_mail_from_name'] ) ) || ( empty( $email_settings_user['user_mail_from_name'] ) ) ) {
					$email_settings_user['user_mail_from_name'] = '';

					$email_user = get_user_by( 'email', $email_settings_user['user_mail_from'] );
					if ( ( $email_user ) && ( is_a( $email_user, 'WP_User' ) ) ) {
						$email_settings_user['user_mail_from_name'] = $email_user->display_name;
					}
				}

				$headers .= 'From: ';
				if ( ( isset( $email_settings_user['user_mail_from_name'] ) ) && ( ! empty( $email_settings_user['user_mail_from_name'] ) ) ) {
					$headers .= $email_settings_user['user_mail_from_name'] . ' <' . $email_settings_user['user_mail_from'] . '>';
				} else {
					$headers .= $email_settings_user['user_mail_from'];
				}
			}

			if ( ( isset( $email_settings_user['user_mail_html'] ) ) && ( 'yes' === $email_settings_user['user_mail_html'] ) ) {
				add_filter( 'wp_mail_content_type', array( $this, 'htmlEmailContent' ) );
				$user_mail_message = wpautop( $user_mail_message );
			} else {
				$user_mail_message = esc_html( wp_strip_all_tags( wptexturize( $user_mail_message ) ) );
			}

			$email_params = array(
				'email'   => $user->user_email,
				'subject' => $user_mail_subject,
				'msg'     => $user_mail_message,
				'headers' => $headers,
			);

			/**
			 * Filters quiz email parameters.
			 *
			 * @param array                 $email_params An array of quiz email parameters.
			 * @param WpProQuiz_Model_Quiz  $quiz         Quiz object.
			 */
			$email_params = apply_filters( 'learndash_quiz_email', $email_params, $quiz );
			wp_mail( $email_params['email'], $email_params['subject'], $email_params['msg'], $email_params['headers'] );

			if ( ( isset( $email_settings_user['user_mail_html'] ) ) && ( 'yes' === $email_settings_user['user_mail_html'] ) ) {
				remove_filter( 'wp_mail_content_type', array( $this, 'htmlEmailContent' ) );
			}
		}

		if ( $quiz->getEmailNotification() == WpProQuiz_Model_Quiz::QUIZ_EMAIL_NOTE_ALL
			|| ( get_current_user_id() > 0 && $quiz->getEmailNotification() == WpProQuiz_Model_Quiz::QUIZ_EMAIL_NOTE_REG_USER ) ) {

			$admin_mail_message = str_replace( array_keys( $r ), $r, $email_settings_admin['admin_mail_message'] );
			$admin_mail_subject = str_replace( array_keys( $r ), $r, $email_settings_admin['admin_mail_subject'] );

			/**
			 * Filters quiz email note admin message.
			 *
			 * @param string               $message    Quiz email admin message.
			 * @param array                $quiz_data  An array of quiz data.
			 * @param WpProQuiz_Model_Quiz $quiz       Quiz object.
			 * @param array                $result     Quiz result.
			 * @param array                $categories Quiz categories
			 */
			$admin_mail_message = apply_filters( 'learndash_quiz_email_note_admin_message', $admin_mail_message, $r, $quiz, $result, $categories );
			$headers            = '';

			if ( ( ! isset( $email_settings_admin['admin_mail_from'] ) ) || ( empty( $email_settings_admin['admin_mail_from'] ) ) || ( ! is_email( $email_settings_admin['admin_mail_from'] ) ) ) {
				$email_settings_admin['admin_mail_from'] = get_option( 'admin_email' );
			}

			if ( ( ! isset( $email_settings_admin['admin_mail_from_name'] ) ) || ( empty( $email_settings_admin['admin_mail_from_name'] ) ) ) {
				$email_settings_admin['admin_mail_from_name'] = '';

				if ( ! empty( $email_settings_admin['admin_mail_from'] ) ) {
					$email_user = get_user_by( 'email', $email_settings_admin['admin_mail_from'] );
					if ( ( $email_user ) && ( is_a( $email_user, 'WP_User' ) ) ) {
						$email_settings_admin['admin_mail_from_name'] = $email_user->display_name;
					}
				}
			}

			if ( ! empty( $email_settings_admin['admin_mail_from'] ) ) {
				$headers .= 'From: ';
				if ( ( isset( $email_settings_admin['admin_mail_from_name'] ) ) && ( ! empty( $email_settings_admin['admin_mail_from_name'] ) ) ) {
					$headers .= $email_settings_admin['admin_mail_from_name'] . ' <' . $email_settings_admin['admin_mail_from'] . '>';
				} else {
					$headers .= $email_settings_admin['admin_mail_from'];
				}
			}

			if ( ( isset( $email_settings_admin['admin_mail_html'] ) ) && ( $email_settings_admin['admin_mail_html'] ) ) {
				add_filter( 'wp_mail_content_type', array( $this, 'htmlEmailContent' ) );
				$admin_mail_message = wpautop( $admin_mail_message );
			} else {
				$admin_mail_message = esc_html( wp_strip_all_tags( wptexturize( $admin_mail_message ) ) );
			}

			$email_params = array(
				'email'   => $email_settings_admin['admin_mail_to'],
				'subject' => $admin_mail_subject,
				'msg'     => $admin_mail_message,
				'headers' => $headers,
			);

			/**
			 * Filters quiz admin email parameters.
			 *
			 * @param array                 $email_params An array of quiz email parameters.
			 * @param WpProQuiz_Model_Quiz  $quiz         Quiz object.
			 */
			$email_params = apply_filters( 'learndash_quiz_email_admin', $email_params, $quiz );
			wp_mail( $email_params['email'], $email_params['subject'], $email_params['msg'], $email_params['headers'] );

			if ( ( isset( $email_settings_admin['admin_mail_html'] ) ) && ( 'yes' === $email_settings_admin['admin_mail_html'] ) ) {
				remove_filter( 'wp_mail_content_type', array( $this, 'htmlEmailContent' ) );
			}
		}
	}

	public function htmlEmailContent( $contentType ) {
		return 'text/html';
	}

	private function setCategoryOverview( $category_scores = array(), $question_cats = array() ) {
		if ( ( ! empty( $category_scores ) ) && ( ! empty( $question_cats ) ) ) {
			$question_categories = array();

			foreach ( $question_cats as $cat ) {
				if ( ! $cat->getCategoryId() ) {
					$cat->setCategoryName( esc_html__( 'Not categorized', 'learndash' ) );
				}

				$question_categories[ $cat->getCategoryId() ] = $cat->getCategoryName();
			}
		}

		$output = SFWD_LMS::get_template(
			'quiz_result_categories_email.php',
			array(
				'question_categories' => $question_categories,
				'category_scores'     => $category_scores,
			)
		);
		return $output;
	}
}
