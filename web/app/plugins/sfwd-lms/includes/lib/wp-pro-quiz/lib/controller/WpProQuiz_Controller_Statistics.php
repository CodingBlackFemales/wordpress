<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName,PSR2.Classes.PropertyDeclaration.Underscore
class WpProQuiz_Controller_Statistics extends WpProQuiz_Controller_Controller {

	public function route() {
		$action = ( isset( $_GET['action'] ) ) ? $_GET['action'] : 'show';

		switch ( $action ) {
			case 'show':
			default:
				$this->show( $_GET['id'] );
		}
	}

	public function getAverageResult( $quizId ) {
		$statisticRefMapper = new WpProQuiz_Model_StatisticRefMapper();
		//      $quizMapper = new WpProQuiz_Model_QuizMapper();

		//      $r = $statisticRefMapper->fetchByQuiz($quizId);
		//      $maxPoints = $quizMapper->sumQuestionPoints($quizId);
		//      $sumQuestion = $quizMapper->countQuestion($quizId);

		//      if($r['count'] > 0) {
		//          return round((100 * $r['points'] / ($r['count'] * $maxPoints / $sumQuestion)), 2);
		//      }

		$result = $statisticRefMapper->fetchFrontAvg( $quizId );

		if ( isset( $result['g_points'] ) && $result['g_points'] ) {
			return round( 100 * $result['points'] / $result['g_points'], 2 );
		}

		return 0;
	}

	/**
	 * @deprecated
	 */
	private function show( $quizId ) {

		if ( ! current_user_can( 'wpProQuiz_show_statistics' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'learndash' ) );
		}

		$this->showNew( $quizId );
		return;

		$view           = new WpProQuiz_View_Statistics();
		$questionMapper = new WpProQuiz_Model_QuestionMapper();
		$quizMapper     = new WpProQuiz_Model_QuizMapper();
		$categoryMapper = new WpProQuiz_Model_CategoryMapper();
		$formMapper     = new WpProQuiz_Model_FormMapper();

		$quiz = $quizMapper->fetch( $quizId );

		$questions     = $questionMapper->fetchAll( $quiz );
		$category      = $categoryMapper->fetchAll();
		$categoryEmpty = new WpProQuiz_Model_Category();

		$categoryEmpty->setCategoryName( __( 'No category', 'learndash' ) );

		$list = array();
		$cats = array();

		foreach ( $category as $c ) {
			$cats[ $c->getCategoryId() ] = $c;
		}

		$cats[0] = $categoryEmpty;

		foreach ( $questions as $q ) {
			$list[ $q->getCategoryId() ][] = $q;
		}

		$view->quiz         = $quizMapper->fetch( $quizId );
		$view->questionList = $list;
		$view->categoryList = $cats;
		$view->forms        = $formMapper->fetch( $quizId );

		if ( has_action( 'pre_user_query', 'ure_exclude_administrators' ) ) {
			remove_action( 'pre_user_query', 'ure_exclude_administrators' );

			$users = get_users( array( 'fields' => array( 'ID', 'user_login', 'display_name' ) ) );

			add_action( 'pre_user_query', 'ure_exclude_administrators' );

		} else {
			$users = get_users( array( 'fields' => array( 'ID', 'user_login', 'display_name' ) ) );
		}

		array_unshift( $users, (object) array( 'ID' => 0 ) );

		$view->users = $users;
		$view->show();
	}

	private function showNew( $quizId ) {
		$view = new WpProQuiz_View_StatisticsNew();

		$quizMapper = new WpProQuiz_Model_QuizMapper();

		$quiz = $quizMapper->fetch( $quizId );

		if ( learndash_use_select2_lib_ajax_fetch() ) {
			$users = array();
		} else {
			if ( has_action( 'pre_user_query', 'ure_exclude_administrators' ) ) {
				remove_action( 'pre_user_query', 'ure_exclude_administrators' );

				$users = get_users( array( 'fields' => array( 'ID', 'user_login', 'display_name' ) ) );

				add_action( 'pre_user_query', 'ure_exclude_administrators' );

			} else {
				$get_users_args = array(
					'fields' => array( 'ID', 'user_login', 'display_name' ),
				);

				if ( ! learndash_is_admin_user( get_current_user_id() ) ) {
					if ( learndash_is_group_leader_user( get_current_user_id() ) ) {

						$include_user_ids = learndash_get_groups_leaders_users_for_course_step( $quiz->getPostId() );
					} else {
						$include_user_ids = array( get_current_user_id() );
					}
				}
				if ( ( isset( $include_user_ids ) ) && ( ! empty( $include_user_ids ) ) ) {
					$get_users_args['include'] = $include_user_ids;
				}

				$users = get_users( $get_users_args );
			}
		}

		$view->quiz  = $quiz;
		$view->users = $users;
		$view->show();
	}

	/**
	 *
	 * @param WpProQuiz_Model_Quiz $quiz
	 * @return void|boolean
	 */
	public function save( $quiz = null ) {
		learndash_quiz_debug_log_message( 'in ' . __CLASS__ . ' - ' . __FUNCTION__ );

		if ( isset( $this->_post['quizId'] ) ) {
			$quizId = absint( $this->_post['quizId'] );
		} else {
			$quizId = 0;
		}
		learndash_quiz_debug_log_message( 'quizId ' . $quizId );

		if ( isset( $this->_post['quiz'] ) ) {
			$quiz_post_id = absint( $this->_post['quiz'] );
		} else {
			$quiz_post_id = 0;
		}
		learndash_quiz_debug_log_message( 'quiz_post_id ' . $quiz_post_id );

		if ( isset( $this->_post['course_id'] ) ) {
			$course_post_id = absint( $this->_post['course_id'] );
		} else {
			$course_post_id = 0;
		}
		learndash_quiz_debug_log_message( 'course_post_id ' . $course_post_id );

		$array  = $this->_post['results'];
		$lockIp = $this->getIp();
		learndash_quiz_debug_log_message( 'lockIp ' . $lockIp );

		$userId = get_current_user_id();

		if ( false === $lockIp ) {
			learndash_quiz_debug_log_message( 'lockIp === false - aborting' );
			return false;
		}

		if ( null === $quiz ) {
			$quizMapper = new WpProQuiz_Model_QuizMapper();
			$quiz       = $quizMapper->fetch( $quizId );
		}

		if ( ! $quiz->isStatisticsOn() ) {
			learndash_quiz_debug_log_message( 'quiz->isStatisticsOn not enabled? - aborting' );
			return false;
		}

		$values     = $this->makeDataList( $quiz, $array, $userId, $quiz->getQuizModus() );
		$formValues = $this->makeFormData( $quiz, $userId, isset( $this->_post['forms'] ) ? $this->_post['forms'] : null );

		if ( false === $values ) {
			return;
		}

		if ( $quiz->getStatisticsIpLock() > 0 ) {
			$lockMapper = new WpProQuiz_Model_LockMapper();
			$lockTime   = $quiz->getStatisticsIpLock() * 60;

			$lockMapper->deleteOldLock( $lockTime, $quiz->getId(), time(), WpProQuiz_Model_Lock::TYPE_STATISTIC );

			if ( $lockMapper->isLock( $quizId, $lockIp, $userId, WpProQuiz_Model_Lock::TYPE_STATISTIC ) ) {
				return false;
			}

			$lock = new WpProQuiz_Model_Lock();
			$lock->setQuizId( $quizId )
					->setLockIp( $lockIp )
					->setUserId( $userId )
					->setLockType( WpProQuiz_Model_Lock::TYPE_STATISTIC )
					->setLockDate( time() );

			$lockMapper->insert( $lock );
		}

		$statisticRefModel = new WpProQuiz_Model_StatisticRefModel();

		$statisticRefModel->setCreateTime( time() );
		$statisticRefModel->setUserId( $userId );
		$statisticRefModel->setQuizId( $quizId );
		$statisticRefModel->setQuizPostId( $quiz_post_id );
		$statisticRefModel->setCoursePostId( $course_post_id );
		$statisticRefModel->setFormData( $formValues );

		$statisticRefMapper = new WpProQuiz_Model_StatisticRefMapper();
		learndash_quiz_debug_log_message( 'calling statisticRefMapper->statisticSave' );
		learndash_quiz_debug_log_message( 'values<pre>' . print_r( $values, true ) . '</pre>' );

		$statisticRefMapper_id = $statisticRefMapper->statisticSave( $statisticRefModel, $values );

		learndash_quiz_debug_log_message( 'statisticRefMapper_id ' . $statisticRefMapper_id );
		return $statisticRefMapper_id;
	}

	/**
	 * @param WpProQuiz_Model_Quiz $quiz
	 * @param int $userId
	 */
	private function makeFormData( $quiz, $userId, $data ) {
		if ( ! $quiz->isFormActivated() || empty( $data ) ) {
			return null;
		}

		$formMapper = new WpProQuiz_Model_FormMapper();

		$forms = $formMapper->fetch( $quiz->getId() );

		if ( empty( $forms ) ) {
			return null;
		}

		$formArray = array();

		foreach ( $forms as $form ) {
			if ( $form->getType() != WpProQuiz_Model_Form::FORM_TYPE_DATE ) {
				$str = isset( $data[ $form->getFormId() ] ) ? $data[ $form->getFormId() ] : '';

				if ( WpProQuiz_Helper_Form::valid( $form, $str ) === false ) {
					return null;
				}

				$formArray[ $form->getFormId() ] = trim( $str );
			} else {
				$date = isset( $data[ $form->getFormId() ] ) ? $data[ $form->getFormId() ] : array();

				$dateStr = WpProQuiz_Helper_Form::validData( $form, $date );

				if ( null === $dateStr ) {
					return null;
				}

				$formArray[ $form->getFormId() ] = $dateStr;
			}
		}

		return $formArray;
	}

	private function makeDataList( $quiz, $array, $userId, $modus ) {

		$questionMapper = new WpProQuiz_Model_QuestionMapper();

		$question = $questionMapper->fetchAllList( $quiz, array( 'id', 'points' ) );

		$ids = array();

		foreach ( $question as $q ) {
			if ( ! isset( $array[ $q['id'] ] ) ) {
				continue;
			}

			$ids[] = $q['id'];
			$v     = $array[ $q['id'] ];

			//if(!isset($v) || $v['points'] > $q['points'] || $v['points'] < 0) {
			//	return false;
			//}

			if ( ! isset( $v['points'] ) ) {
				$v['points'] = 0;
			}

			if ( (int) $v['points'] > (int) $q['points'] ) {
				$v['points'] = (int) $q['points'];
			}

			if ( $v['points'] < 0 ) {
				$v['points'] = 0;
			}
		}

		$avgTime = null;

		if ( WpProQuiz_Model_Quiz::QUIZ_MODUS_SINGLE == $modus ) {
			$avgTime = ceil( $array['comp']['quizTime'] / count( $question ) );
		}

		unset( $array['comp'] );

		$ak = array_keys( $array );

		if ( array_diff( $ids, $ak ) !== array_diff( $ak, $ids ) ) {
			return false;
		}

		$values = array();

		$quiz_questions = array();
		if ( ! empty( $quiz->getPostId() ) ) {
			$ld_quiz_questions_object = LDLMS_Factory_Post::quiz_questions( $quiz->getPostId() );
			if ( $ld_quiz_questions_object ) {
				$quiz_questions = $ld_quiz_questions_object->get_questions();
			}
		}

		foreach ( $array as $k => $v ) {
			$s = new WpProQuiz_Model_Statistic();
			$s->setQuizId( $quiz->getId() );
			$s->setQuestionId( $k );
			$s->setQuestionPostId( absint( array_search( $k, $quiz_questions ) ) );
			$s->setUserId( $userId );
			$s->setHintCount( isset( $v['tip'] ) ? 1 : 0 );
			$s->setCorrectCount( $v['correct'] ? 1 : 0 );
			$s->setIncorrectCount( $v['correct'] ? 0 : 1 );
			$s->setPoints( $v['points'] );
			$s->setQuestionTime( null === $avgTime ? $v['time'] : $avgTime );
			$s->setAnswerData( isset( $v['data'] ) ? $v['data'] : null );

			$values[] = $s;
		}

		return $values;
	}

	private function getIp() {
		if ( get_current_user_id() > 0 ) {
			return '0';
		} else {
			return filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP );
		}
	}

	/**
	 * @deprecated
	 */
	public static function ajaxLoadStatistic( $data, $func ) {
		if ( ! current_user_can( 'wpProQuiz_show_statistics' ) ) {
			return wp_json_encode( array() );
		}

		$userId = $data['userId'];
		$quizId = $data['quizId'];
		$testId = $data['testId'];

		$maxPoints   = 0;
		$sumQuestion = 0;

		$inTest = false;

		$category     = array();
		$categoryList = array();
		$testJson     = array();

		$formData = null;

		$statisticMapper    = new WpProQuiz_Model_StatisticMapper();
		$questionMapper     = new WpProQuiz_Model_QuestionMapper();
		$statisticRefMapper = new WpProQuiz_Model_StatisticRefMapper();

		$tests = $statisticRefMapper->fetchAll( $quizId, $userId, $testId );

		$i = 1;
		foreach ( $tests as $test ) {
			if ( $testId == $test->getStatisticRefId() ) {
				$inTest = true;
			}

			$testJson[] = array(
				'id'   => $test->getStatisticRefId(),
				'date' => '#' . ( $i++ ) . ' ' . WpProQuiz_Helper_Until::convertTime(
					$test->getCreateTime(),
					LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Management_Display', 'statistics_time_format' )
					//get_option('wpProQuiz_statisticTimeFormat', 'Y/m/d g:i A')
				),
			);
		}

		if ( ! $inTest ) {
			$data['testId'] = $testId = 0;
		}

		if ( ! $testId ) {
			$statistics = $statisticRefMapper->fetchAvg( $quizId, $userId );
		} else {
			$statistics = $statisticMapper->fetchAllByRef( $testId );
			$refModel   = $statisticRefMapper->fetchByRefId( $testId );
			$formData   = $refModel->getFormData();
		}

		$questionData = $questionMapper->fetchAllList( $quizId, array( 'id', 'category_id', 'points' ) );

		$empty = array(
			'questionId'   => 0,
			'correct'      => 0,
			'incorrect'    => 0,
			'hint'         => 0,
			'points'       => 0,
			'result'       => 0,
			'questionTime' => 0,
		);

		$ca = $sa = array();

		$ga = $empty;

		foreach ( $questionData as $cc ) {
			$categoryList[ $cc['id'] ] = $cc['category_id'];

			$c = &$category[ $cc['category_id'] ];

			if ( empty( $c ) ) {
				$c        = $cc;
				$c['sum'] = 1;
			} else {
				$c['points'] += $cc['points'];
				$c['sum']++;
			}

			$maxPoints += $cc['points'];
			$sumQuestion++;

			$sa[ $cc['id'] ]               = self::calcTotal( $empty );
			$sa[ $cc['id'] ]['questionId'] = $cc['id'];

			$ca[ $cc['category_id'] ] = self::calcTotal( $empty );
		}

		foreach ( $statistics as $statistic ) {
			$s = $statistic->getCorrectCount() + $statistic->getIncorrectCount();

			if ( $s > 0 ) {
				$correct   = $statistic->getCorrectCount() . ' (' . round( ( 100 * $statistic->getCorrectCount() / $s ), 2 ) . '%)';
				$incorrect = $statistic->getIncorrectCount() . ' (' . round( ( 100 * $statistic->getIncorrectCount() / $s ), 2 ) . '%)';
			} else {
				$incorrect = $correct = '0 (0%)';
			}

			$ga['correct']      += $statistic->getCorrectCount();
			$ga['incorrect']    += $statistic->getIncorrectCount();
			$ga['hint']         += $statistic->getHintCount();
			$ga['points']       += $statistic->getPoints();
			$ga['questionTime'] += $statistic->getQuestionTime();

			$cats = &$ca[ $categoryList[ $statistic->getQuestionId() ] ];

			if ( ! is_array( $cats ) ) {
				$cats = $empty;
			}

			$cats['correct']      += $statistic->getCorrectCount();
			$cats['incorrect']    += $statistic->getIncorrectCount();
			$cats['hint']         += $statistic->getHintCount();
			$cats['points']       += $statistic->getPoints();
			$cats['questionTime'] += $statistic->getQuestionTime();

			$sa[ $statistic->getQuestionId() ] = array(
				'questionId'   => $statistic->getQuestionId(),
				'correct'      => $correct,
				'incorrect'    => $incorrect,
				'hint'         => $statistic->getHintCount(),
				'points'       => $statistic->getPoints(),
				'questionTime' => self::convertToTimeString( $statistic->getQuestionTime() ),
			);
		}

		foreach ( $ca as $catIndex => $cat ) {
			$ca[ $catIndex ] = self::calcTotal( $cat, $category[ $catIndex ]['points'], $category[ $catIndex ]['sum'] );
		}

		$sa[0] = self::calcTotal( $ga, $maxPoints, $sumQuestion );

		return wp_json_encode(
			array(
				'question' => $sa,
				'category' => $ca,
				'tests'    => $testJson,
				'testId'   => $data['testId'],
				'formData' => $formData,
			)
		);
	}

	/**
	 * @deprecated
	 */
	public static function ajaxReset( $data, $func ) {
		if ( ! current_user_can( 'wpProQuiz_reset_statistics' ) ) {
			return;
		}

		$statisticRefMapper = new WpProQuiz_Model_StatisticRefMapper();

		$quizId = $data['quizId'];
		$userId = $data['userId'];
		$testId = $data['testId'];

		switch ( $data['type'] ) {
			case 0:
				$statisticRefMapper->deleteUserTest( $quizId, $userId, $testId );
				break;
			case 1:
				$statisticRefMapper->deleteUser( $quizId, $userId );
				break;
			case 2:
				$statisticRefMapper->deleteAll( $quizId );
				break;
		}
	}

	/**
	 * @deprecated
	 */
	public static function ajaxLoadStatsticOverview( $data, $func ) {
		if ( ! current_user_can( 'wpProQuiz_show_statistics' ) ) {
			return wp_json_encode( array() );
		}

		$statisticRefMapper = new WpProQuiz_Model_StatisticRefMapper();

		$quizMapper = new WpProQuiz_Model_QuizMapper();

		$quizId = $data['quizId'];

		$page  = ( isset( $data['page'] ) && $data['page'] > 0 ) ? $data['page'] : 1;
		$limit = $data['pageLimit'];
		$start = $limit * ( $page - 1 );

		$statistics = $statisticRefMapper->fetchOverview( $quizId, (bool) $data['onlyCompleted'], $start, $limit );

		$d = array( 'items' => array() );

		$maxPoints   = $quizMapper->sumQuestionPoints( $quizId );
		$sumQuestion = $quizMapper->countQuestion( $quizId );

		foreach ( $statistics as $statistic ) {

			$sum = $statistic->getCorrectCount() + $statistic->getIncorrectCount();

			if ( $sum > 0 ) {
				$correct      = $statistic->getCorrectCount() . ' (' . round( ( 100 * $statistic->getCorrectCount() / $sum ), 2 ) . '%)';
				$incorrect    = $statistic->getIncorrectCount() . ' (' . round( ( 100 * $statistic->getIncorrectCount() / $sum ), 2 ) . '%)';
				$hint         = $statistic->getHintCount();
				$result       = round( ( 100 * $statistic->getPoints() / ( $sum * $maxPoints / $sumQuestion ) ), 2 ) . '%';
				$points       = $statistic->getPoints();
				$questionTime = self::convertToTimeString( $statistic->getQuestionTime() );
			} else {
				$questionTime = $points = $result = $hint = $correct = $incorrect = '---';
			}

			$d['items'][] = array(
				'userId'       => $statistic->getUserId(),
				'userName'     => $statistic->getUserName(),
				'points'       => $points,
				'correct'      => $correct,
				'incorrect'    => $incorrect,
				'hint'         => $hint,
				'result'       => $result,
				'questionTime' => $questionTime,
			);
		}

		if ( isset( $data['nav'] ) && $data['nav'] ) {
			$count     = $statisticRefMapper->countOverview( $quizId, (bool) $data['onlyCompleted'] );
			$d['page'] = ceil( ( $count > 0 ? $count : 1 ) / $limit );
		}

		return wp_json_encode( $d );

	}

	/**
	 * @deprecated
	 */
	private static function calcTotal( $a, $maxPoints = null, $sumQuestion = null ) {
		$s = $a['correct'] + $a['incorrect'];

		if ( $s > 0 ) {
			$a['correct']   = $a['correct'] . ' (' . round( ( 100 * $a['correct'] / $s ), 2 ) . '%)';
			$a['incorrect'] = $a['incorrect'] . ' (' . round( ( 100 * $a['incorrect'] / $s ), 2 ) . '%)';

			if ( null !== $maxPoints ) {
				$a['result'] = round( ( 100 * $a['points'] / ( $s * $maxPoints / $sumQuestion ) ), 2 ) . '%';
			}
		} else {
			$a['result'] = $a['correct'] = $a['incorrect'] = '0 (0%)';
		}

		$a['questionTime'] = self::convertToTimeString( $a['questionTime'] );

		return $a;
	}

	/**
	 * @deprecated
	 */
	private static function convertToTimeString( $s ) {
		$h  = floor( $s / 3600 );
		$s -= $h * 3600;
		$m  = floor( $s / 60 );
		$s -= $m * 60;

		return sprintf( '%02d:%02d:%02d', $h, $m, $s );
	}

	/**
	 * @deprecated
	 */
	public static function ajaxLoadFormOverview( $data, $func ) {
		if ( ! current_user_can( 'wpProQuiz_show_statistics' ) ) {
			return wp_json_encode( array() );
		}

		$statisticRefMapper = new WpProQuiz_Model_StatisticRefMapper();
		$quizMapper         = new WpProQuiz_Model_QuizMapper();

		$quizId = $data['quizId'];

		$page  = ( isset( $data['page'] ) && $data['page'] > 0 ) ? $data['page'] : 1;
		$limit = $data['pageLimit'];
		$start = $limit * ( $page - 1 );

		$statisticModel = $statisticRefMapper->fetchFormOverview( $quizId, $start, $limit, $data['onlyUser'] );

		$items = array();

		$maxPoints   = $quizMapper->sumQuestionPoints( $quizId );
		$sumQuestion = $quizMapper->countQuestion( $quizId );

		foreach ( $statisticModel as $model ) {
			if ( ! $model->getUserId() ) {
				$model->setUserName( __( 'Anonymous', 'learndash' ) );
			}

			$sum    = $model->getCorrectCount() + $model->getIncorrectCount();
			$result = round( ( 100 * $model->getPoints() / ( $sum * $maxPoints / $sumQuestion ) ), 2 ) . '%';

			$items[] = array(
				'userName' => $model->getUserName(),
				'userId'   => $model->getUserId(),
				'testId'   => $model->getStatisticRefId(),
				'date'     => WpProQuiz_Helper_Until::convertTime(
					$model->getCreateTime(),
					LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Management_Display', 'statistics_time_format' )
					//get_option('wpProQuiz_statisticTimeFormat', 'Y/m/d g:i A')
				),
				'result'   => $result,
			);
		}

		$d = array( 'items' => $items );

		if ( isset( $data['nav'] ) && $data['nav'] ) {
			$count     = $statisticRefMapper->countFormOverview( $quizId, $data['onlyUser'] );
			$d['page'] = ceil( ( $count > 0 ? $count : 1 ) / $limit );
		}

		return wp_json_encode( $d );
	}

	public static function ajaxLoadHistory( $data, $func ) {
		if ( ! current_user_can( 'wpProQuiz_show_statistics' ) ) {
			return wp_json_encode( array() );
		}

		$statisticRefMapper = new WpProQuiz_Model_StatisticRefMapper();
		$quizMapper         = new WpProQuiz_Model_QuizMapper();

		$quizId = absint( $data['quizId'] );
		$quiz   = absint( $data['quiz'] );
		$page   = ( isset( $data['page'] ) && $data['page'] > 0 ) ? absint( $data['page'] ) : 1;
		$limit  = absint( $data['pageLimit'] );
		$start  = $limit * ( $page - 1 );

		$startTime = (int) $data['dateFrom'];
		$endTime   = (int) $data['dateTo'] ? $data['dateTo'] + 86400 : 0;

		$statisticModel = $statisticRefMapper->fetchHistoryWithArgs(
			array(
				'quizId'    => $quizId,
				'quiz'      => $quiz,
				'page'      => $page,
				'limit'     => $limit,
				'users'     => $data['users'],
				'startTime' => $startTime,
				'endTime'   => $endTime,
			)
		);

		foreach ( $statisticModel as $model ) {
			if ( ! $model->getUserId() ) {
				$model->setUserName( __( 'Anonymous', 'learndash' ) );
			} elseif ( $model->getUserName() == '' ) {
				$model->setUserName( __( 'Deleted user', 'learndash' ) );
			}

			$sum    = $model->getCorrectCount() + $model->getIncorrectCount();
			$result = round( 100 * $model->getPoints() / $model->getGPoints(), 2 ) . '%';

			$model->setResult( $result );

			$date_format = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Management_Display', 'statistics_time_format' );
			$date_format = trim( $date_format );
			if ( empty( $date_format ) ) {
				$date_format = 'Y/m/d g:i A';
			}
			$model->setFormatTime(
				WpProQuiz_Helper_Until::convertTime(
					$model->getCreateTime(),
					$date_format
				)
			);

			$model->setFormatCorrect( $model->getCorrectCount() . ' (' . round( 100 * $model->getCorrectCount() / $sum, 2 ) . '%)' );
			$model->setFormatIncorrect( $model->getIncorrectCount() . ' (' . round( 100 * $model->getIncorrectCount() / $sum, 2 ) . '%)' );
		}

		$view               = new WpProQuiz_View_StatisticsAjax();
		$view->historyModel = $statisticModel;

		$html = $view->getHistoryTable();
		$navi = null;

		if ( isset( $data['generateNav'] ) && $data['generateNav'] ) {
			$count = $statisticRefMapper->countHistory( $quizId, $data['users'], $startTime, $endTime );
			$navi  = ceil( ( $count > 0 ? $count : 1 ) / $limit );
		}

		return wp_json_encode(
			array(
				'html' => $html,
				'navi' => $navi,
			)
		);
	}

	public static function ajaxLoadStatisticUser( $data, $func ) {

		// The userId is not passed into this data payload. so for now we set to zero. We will load it via the $statisticRefMapper shortly after
		//$userId = 0; // intval($data['userId']);

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

		$quizId = 0;
		if ( isset( $data['quizId'] ) ) {
			$quizId = absint( $data['quizId'] );
		}

		$userId = 0;
		if ( isset( $data['userId'] ) ) {
			$userId = absint( $data['userId'] );
		}

		$refId = 0;
		if ( isset( $data['refId'] ) ) {
			$refId = absint( $data['refId'] );
		}

		$avg = 0;
		if ( isset( $data['avg'] ) ) {
			$avg = (bool) $data['avg'];
		}

		$refIdUserId = $avg ? $userId : $refId;

		$statisticRefMapper = new WpProQuiz_Model_StatisticRefMapper();
		$statisticModel     = $statisticRefMapper->fetchByRefId( $refIdUserId, $quizId );
		if ( $statisticModel instanceof WpProQuiz_Model_StatisticRefModel ) {
			$userId = $statisticModel->getUserId();
		}

		$statisticUserMapper = new WpProQuiz_Model_StatisticUserMapper();
		$formMapper          = new WpProQuiz_Model_FormMapper();

		$statisticUsers = $statisticUserMapper->fetchUserStatistic( $refIdUserId, $quizId, $avg );

		/**
		 * Hack to get the statistics questions to be in the same order
		 * as the actual Quiz. Currently no order column on statistics.
		 */
		$quiz_post_id = learndash_get_quiz_id_by_pro_quiz_id( $quizId );
		$quiz_post_id = absint( $quiz_post_id );
		if ( ( ! empty( $statisticUsers ) ) && ( ! empty( $quiz_post_id ) ) ) {
			$quiz_questions = learndash_get_quiz_questions( $quiz_post_id );
			if ( ! empty( $quiz_questions ) ) {
				$questions_new = array();
				foreach ( $quiz_questions as $question_post_id => $question_pro_id ) {
					$question_pro_id = absint( $question_pro_id );
					foreach ( $statisticUsers as $stat_question_idx => $stat_question ) {
						$stat_question_id = $stat_question->getQuestionId();
						$stat_question_id = absint( $stat_question_id );
						if ( $question_pro_id === $stat_question_id ) {
							unset( $statisticUsers[ $stat_question_idx ] );
							$questions_new[] = $stat_question;
							break;
						}
					}
				}

				// If there are any questions not matched we merge to the bottom.
				if ( ! empty( $statisticUsers ) ) {
					$questions_new = array_merge( $questions_new, $statisticUsers );
				}

				if ( ! empty( $questions_new ) ) {
					$statisticUsers = $questions_new;
				}
			}
		}

		$output = array();

		foreach ( $statisticUsers as $statistic ) {
			if ( ! isset( $output[ $statistic->getCategoryId() ] ) ) {
				$output[ $statistic->getCategoryId() ] = array(
					'questions'    => array(),
					'categoryId'   => $statistic->getCategoryId(),
					'categoryName' => $statistic->getCategoryId() ? $statistic->getCategoryName() : esc_html__( 'No category', 'learndash' ),
				);
			}

			$o             = &$output[ $statistic->getCategoryId() ];
			$question_item = array(
				'correct'            => $statistic->getCorrectCount(),
				'incorrect'          => $statistic->getIncorrectCount(),
				'hintCount'          => $statistic->getHintCount(),
				'time'               => $statistic->getQuestionTime(),
				'points'             => $statistic->getPoints(),
				'gPoints'            => $statistic->getGPoints(),
				'statistcAnswerData' => $statistic->getStatisticAnswerData(),
				'questionName'       => $statistic->getQuestionName(),
				'questionAnswerData' => $statistic->getQuestionAnswerData(),
				'answerType'         => $statistic->getAnswerType(),
				'questionId'         => $statistic->getQuestionId(),
			);

			$questionId = $statistic->getQuestionId();
			if ( ! empty( $questionId ) ) {
				$questionMapper = new WpProQuiz_Model_QuestionMapper();
				$question       = $questionMapper->fetchById( intval( $questionId ) );
				if ( ( ! empty( $question ) ) && ( $question instanceof WpProQuiz_Model_Question ) ) {
					$question_item['questionCorrectMsg']   = $question->getCorrectMsg();
					$question_item['questionIncorrectMsg'] = $question->getIncorrectMsg();
				}
			}

			// For the sort_answer items. This worked correctly with LD 2.0.6.8. But in 2.1.x there was a change where
			// the stored value for 'statistcAnswerData' was not simply keys to match 'questionAnswerData' but md5 value.
			// This causes a mis-match when viewing statistics data. To complicate things we will have a mix of LD 2.0.6.8
			// quiz values and 2.1.x quiz values.
			if ( ( 'sort_answer' == $question_item['answerType'] ) || ( 'matrix_sort_answer' == $question_item['answerType'] ) ) {

				if ( ( isset( $question_item['questionAnswerData'] ) ) && ( ! empty( $question_item['questionAnswerData'] ) )
				&& ( isset( $question_item['statistcAnswerData'] ) ) && ( ! empty( $question_item['statistcAnswerData'] ) ) ) {

					// So first we check the value of the first item from 'statistcAnswerData'. If the value
					// is a simple int then we can move on. If not, then we have some work to do.
					$statistcAnswerData_item = $question_item['statistcAnswerData'][0];
					if ( ( -1 == $statistcAnswerData_item )
					|| ( 0 !== strcmp( $statistcAnswerData_item, intval( $statistcAnswerData_item ) ) ) ) {

						$questionId = $statistic->getQuestionId();

						// Next we loop over the 'questionAnswerData' items.
						foreach ( $question_item['questionAnswerData'] as $q_k => $q_v ) {

							// Take the item key and encode it.
							//$datapos = LD_QuizPro::datapos($questionId, intval($q_k));
							// We can't call LD_QuizPro::datapos because is uses current_user which will NOT match the statistic user.
							$datapos = md5( intval( $userId ) . $questionId . intval( $q_k ) );

							// If we find that encoded value in the 'statistcAnswerData' we update the value.
							$s_pos = array_search( $datapos, $question_item['statistcAnswerData'], true );

							if ( false !== $s_pos ) {
								$question_item['statistcAnswerData'][ $s_pos ] = intval( $q_k );
							}
						}
					}
				}
			}

			$question_item['result'] = '';

			// For essay type questions if the related post (graded_id) is still in the 'not_graded post_status we clear out the incorrect value.
			// The reason for this is within the statistics logic either the correct or incorrect field is set. In the case of not graded essays
			// the result is the incorrect field will be set to 1. See LEARNDASH-212
			if ( 'essay' == $question_item['answerType'] ) {
				if ( ( isset( $question_item['statistcAnswerData']['graded_id'] ) ) && ( ! empty( $question_item['statistcAnswerData']['graded_id'] ) ) ) {
					$graded_post = get_post( $question_item['statistcAnswerData']['graded_id'] );
					if ( ( $graded_post ) && ( is_a( $graded_post, 'WP_Post' ) ) ) {
						$graded_data = learndash_get_submitted_essay_data( $data['quizId'], $question_item['questionId'], $graded_post );
						if ( isset( $graded_data['status'] ) ) {
							if ( 'graded' === $graded_data['status'] ) {
								if ( isset( $graded_data['points_awarded'] ) ) {
									$question_item['correct']   = absint( $graded_data['points_awarded'] );
									$question_item['points']    = absint( $graded_data['points_awarded'] );
									$question_item['incorrect'] = absint( $question_item['gPoints'] ) - $question_item['correct'];
									$question_item['result']    = esc_html__( 'Graded', 'learndash' );
								}
							} else {
								$question_item['result'] = esc_html__( 'Ungraded', 'learndash' );
							}
						}
					}
				}
			}

			/**
			 * Filters quiz result column output.
			 *
			 * Allow filter of the new 'Result' column output. This is pretty free-form and was not used before 2.4.0.
			 *
			 * @since 2.4.0
			 *
			 * @param string $question_result Quiz result column output.
			 * @param array  $question_item   An array of quiz question data.
			 */
			$question_item['result'] = apply_filters( 'learndash-quiz-statistics-result', $question_item['result'], $question_item );

			$o['questions'][] = $question_item;
		}

		$view = new WpProQuiz_View_StatisticsAjax();

		$view->avg            = $avg;
		$view->statisticModel = $statisticRefMapper->fetchByRefId( $refIdUserId, $quizId, $avg );

		$view->userName = esc_html__( 'Anonymous', 'learndash' );

		if ( $view->statisticModel->getUserId() ) {
			$userInfo = get_userdata( $view->statisticModel->getUserId() );

			if ( false !== $userInfo ) {
				$view->userName = $userInfo->user_login . ' (' . $userInfo->display_name . ')';
			} else {
				$view->userName = esc_html__( 'Deleted user', 'learndash' );
			}
		}

		if ( ! $avg ) {
			$view->forms = $formMapper->fetch( $quizId );
		}

		$view->userStatistic = $output;

		$html = $view->getUserTable();

		return wp_json_encode(
			array(
				'html' => $html,
			)
		);
	}

	public static function ajaxRestStatistic( $data, $func ) {
		if ( ! current_user_can( 'wpProQuiz_reset_statistics' ) ) {
			return;
		}

		$statisticRefMapper = new WpProQuiz_Model_StatisticRefMapper();

		switch ( $data['type'] ) {
			case 0: //RefId or UserId
				if ( $data['refId'] ) {
					$statisticRefMapper->deleteByRefId( $data['refId'] );
				} elseif ( '' != $data['userId'] ) {
					$statisticRefMapper->deleteByUserIdQuizId( $data['userId'], $data['quizId'] );
				}
				break;
			case 1: //alles
				$statisticRefMapper->deleteAll( $data['quizId'] );
				break;
		}
	}

	public static function ajaxLoadStatsticOverviewNew( $data, $func ) {
		if ( ! current_user_can( 'wpProQuiz_show_statistics' ) ) {
			return wp_json_encode( array() );
		}

		$statisticRefMapper = new WpProQuiz_Model_StatisticRefMapper();
		$quizMapper         = new WpProQuiz_Model_QuizMapper();

		$data['page']      = ( isset( $data['page'] ) && $data['page'] > 0 ) ? $data['page'] : 1;
		$data['pageLimit'] = ( isset( $data['pageLimit'] ) && $data['pageLimit'] > 0 ) ? $data['pageLimit'] : 50;
		$data['start']     = absint( $data['pageLimit'] ) * ( $data['page'] - 1 );

		$statisticModel = $statisticRefMapper->fetchStatisticOverviewWithArgs( $data );

		$view                 = new WpProQuiz_View_StatisticsAjax();
		$view->statisticModel = $statisticModel;

		$navi = null;

		if ( isset( $data['generateNav'] ) && $data['generateNav'] ) {
			$count = $statisticRefMapper->countOverviewNew( $data['quizId'], $data['onlyCompleted'] );
			$navi  = ceil( ( $count > 0 ? $count : 1 ) / $data['pageLimit'] );
		}

		return wp_json_encode(
			array(
				'navi' => $navi,
				'html' => $view->getOverviewTable(),
			)
		);
	}
}
