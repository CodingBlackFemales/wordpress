<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WpProQuiz_Model_StatisticRefMapper extends WpProQuiz_Model_Mapper {

	public function fetchAll( $quizId, $userId, $testId = 0 ) {
		$r = array();

		if ( ! $testId || $userId > 0 ) {
			$where = ' AND is_old = 0 ';
		} else {
			$where = ' AND statistic_ref_id = ' . (int) $testId;
		}

		$results = $this->_wpdb->get_results(
			$this->_wpdb->prepare(
				"SELECT * FROM {$this->_tableStatisticRef} WHERE quiz_id = %d AND user_id = %d {$where} ORDER BY create_time ASC",
				$quizId,
				$userId
			),
			ARRAY_A
		);

		foreach ( $results as $row ) {
			$row['form_data'] = null;

			$r[] = new WpProQuiz_Model_StatisticRefModel( $row );
		}

		return $r;
	}
	public function fetchAllByRef( $statisticRefId ) {
		if ( ! empty( $statisticRefId ) ) {
			$where   = 'sf.statistic_ref_id = %d';
			$results = $this->_wpdb->get_results(
				$this->_wpdb->prepare(
					"SELECT
						sf.*
					FROM
						{$this->_tableStatisticRef} AS sf
					WHERE
						{$where}",
					$statisticRefId
				),
				ARRAY_A
			);

			foreach ( $results as $row ) {
				$row['form_data'] = $row['form_data'] === null ? null : @json_decode( $row['form_data'], true );

				return new WpProQuiz_Model_StatisticRefModel( $row );
			}
		}
	}

	public function fetchByRefId( $refIdUserId, $quizId, $avg = false ) {
		$where   = $avg ? 'sf.user_id = %d' : 'sf.statistic_ref_id = %d';
		$results = $this->_wpdb->get_results(
			$this->_wpdb->prepare(
				"SELECT
					sf.*,
					MIN(sf.create_time) AS min_create_time,
					MAX(sf.create_time) AS max_create_time
				FROM
					{$this->_tableStatisticRef} AS sf
				WHERE
					{$where} AND sf.quiz_id = %d",
				$refIdUserId,
				$quizId
			),
			ARRAY_A
		);

		foreach ( $results as $row ) {
			$row['form_data'] = $row['form_data'] === null ? null : @json_decode( $row['form_data'], true );

			return new WpProQuiz_Model_StatisticRefModel( $row );
		}
	}

	/**
	 * Fetches all statistics by a quiz post ID.
	 *
	 * @since 4.3.0
	 *
	 * @param int $quiz_post_id Quiz Post ID.
	 *
	 * @return WpProQuiz_Model_StatisticRefModel[]
	 */
	public function fetch_all_by_quiz_post_id( int $quiz_post_id ): array {
		$sql = $this->_wpdb->prepare(
			"SELECT * FROM {$this->_tableStatisticRef} WHERE quiz_post_id = %d ORDER BY create_time ASC",
			$quiz_post_id
		);

		$rows = $this->_wpdb->get_results( $sql, ARRAY_A );

		foreach ( $rows as &$row ) {
			$row['form_data'] = $row['form_data'] === null ? null : @json_decode( $row['form_data'], true );

			$row = new WpProQuiz_Model_StatisticRefModel( $row );
		}

		return $rows;
	}

	public function fetchAvg( $quizId, $userId ) {
		$r = array();

		$results = $this->_wpdb->get_results(
			$this->_wpdb->prepare(
				'
			SELECT
				question_id,
				SUM(correct_count) AS correct_count,
				SUM(incorrect_count) AS incorrect_count,
				SUM(hint_count) AS hint_count,
				SUM(points) AS points,
				(SUM(question_time) / COUNT(DISTINCT sf.statistic_ref_id)) AS question_time
			FROM
				' . $this->_tableStatistic . ' AS s,
				' . $this->_tableStatisticRef . ' AS sf
			WHERE
				s.statistic_ref_id = sf.statistic_ref_id
				AND
				sf.quiz_id = %d AND sf.user_id = %d
			GROUP BY s.question_id
			',
				$quizId,
				$userId
			),
			ARRAY_A
		);

		foreach ( $results as $row ) {
			$r[] = new WpProQuiz_Model_Statistic( $row );
		}

		return $r;
	}

	public function fetchOverview( $quizId, $onlyCompleded, $start, $limit ) {
		$sql = 'SELECT
						u.`user_login`, u.`display_name`, u.ID AS user_id,
						SUM(s.`correct_count`) as correct_count,
						SUM(s.`incorrect_count`) as incorrect_count,
						SUM(s.`hint_count`) as hint_count,
						SUM(s.`points`) as points,
						(SUM(s.question_time)) as question_time
					FROM
						`' . $this->_wpdb->users . '` AS u
						' . ( $onlyCompleded ? 'INNER' : 'LEFT' ) . ' JOIN `' . $this->_tableStatisticRef . '` AS sf ON
								(sf.user_id = u.ID AND sf.quiz_id = %d)
						LEFT JOIN `' . $this->_tableStatistic . '` AS s ON ( s.statistic_ref_id = sf.statistic_ref_id )
					GROUP BY u.ID
					ORDER BY u.`user_login`
					LIMIT %d , %d';

		$a = array();

		$results = $this->_wpdb->get_results(
			$this->_wpdb->prepare( $sql, $quizId, $start, $limit ),
			ARRAY_A
		);

		foreach ( $results as $row ) {

			$row['user_name'] = $row['user_login'] . ' (' . $row['display_name'] . ')';

			$a[] = new WpProQuiz_Model_StatisticOverview( $row );
		}

		return $a;

	}

	/**
	 * Fetch records from statistics table with limit and offset.
	 *
	 * @param array $args {
	 *      Optional: Array of arguments to fetch data.
	 *
	 *      @type int $limit   Limit for the records to fetch.
	 *      @type int $offset  Offset for record fetch.
	 * }
	 *
	 * @return array
	 */
	public function fetchSelected( array $args = array() ) {
		$a                   = array();
		$placeholder_counter = 0;
		$placeholder_args    = array();
		$default_args        = array(
			'limit'   => get_option( 'posts_per_page' ),
			'offset'  => 0,
			'order'   => 'DESC',
			'orderby' => 'create_time',
		);

		$join  = apply_filters( 'learndash_statrefs_joins', '' );
		$where = apply_filters( 'learndash_statrefs_where', ' 1=1 ' );

		if ( ( isset( $args['orderby'] ) ) && ( 'date' === $args['orderby'] ) ) {
			$args['orderby'] = 'create_time';
		}

		$args  = wp_parse_args( $args, $default_args );
		$query = "SELECT statref.* FROM $this->_tableStatisticRef as statref {$join} WHERE {$where} ";

		$query .= ' ORDER BY %' . ( ++ $placeholder_counter ) . 's %' . ( ++ $placeholder_counter ) . 's LIMIT %' . ( ++ $placeholder_counter ) . 'd, %' . ( ++ $placeholder_counter ) . 'd';

		$placeholder_args[] = $args['orderby'];
		$placeholder_args[] = $args['order'];
		$placeholder_args[] = $args['offset'];
		$placeholder_args[] = $args['limit'];

		$sql_str = $this->_wpdb->prepare( $query, $placeholder_args );
		//error_log( 'sql_str[' . $sql_str . ']' );
		$results = $this->_wpdb->get_results( $sql_str, ARRAY_A );
		foreach ( $results as $row ) {
			$a[] = new WpProQuiz_Model_StatisticRefModel( $row );
		}

		return $a;
	}


	/**
	 * Fetch records count from statistics table with limit and offset.
	 *
	 * @param array $args {
	 *      Optional: Array of arguments to fetch data.
	 *
	 *      @type int $limit   Limit for the records to fetch.
	 *      @type int $offset  Offset for record fetch.
	 * }
	 *
	 * @return array
	 */
	public function fetchSelectedCount( array $args = array() ) {
		$a                   = array();
		$placeholder_counter = 0;
		$placeholder_args    = array();
		$default_args        = array();

		$join  = apply_filters( 'learndash_statrefs_joins', '' );
		$where = apply_filters( 'learndash_statrefs_where', ' 1=1 ' );

		$args    = wp_parse_args( $args, $default_args );
		$query   = "SELECT count(*) as count FROM $this->_tableStatisticRef as statref {$join} WHERE {$where} ";
		$sql_str = $this->_wpdb->prepare( $query, $placeholder_args );
		//error_log( 'sql_str[' . $sql_str . ']' );
		$results = $this->_wpdb->get_var( $sql_str );
		return absint( $results );
	}

	public function countOverview( $quizId, $onlyCompleded ) {

		if ( $onlyCompleded ) {
			return $this->_wpdb->get_var(
				$this->_wpdb->prepare(
					"SELECT
							COUNT(user_id)
						FROM {$this->_tableStatisticRef}
						WHERE
							quiz_id = %d",
					$quizId
				)
			);
		} else {
			return $this->_wpdb->get_var(
				"SELECT COUNT(ID) FROM {$this->_wpdb->users}"
			);
		}
	}

	public function fetchByQuiz( $quizId ) {
		$sql = 'SELECT
					(SUM(`correct_count`) + SUM(`incorrect_count`)) as count,
					SUM(`points`) as points
				FROM
					' . $this->_tableStatisticRef . ' AS sf,
					' . $this->_tableStatistic . ' AS s
				WHERE
					sf.quiz_id = %d AND s.statistic_ref_id = sf.statistic_ref_id';

		return $this->_wpdb->get_row(
			$this->_wpdb->prepare( $sql, $quizId ),
			ARRAY_A
		);
	}

	/**
	 *
	 * @param WpProQuiz_Model_StatisticRefModel $statisticRefModel
	 * @param WpProQuiz_Model_Statistic[] $statisticModel
	 */
	public function statisticSave( $statisticRefModel, $statisticModel ) {
		$values = array();

		$refId = null;
		$isOld = false;

		//      if(!$statisticRefModel->getUserId()) {
		//          $isOld = true;

		//          $refId = $this->_wpdb->get_var(
		//                  $this->_wpdb->prepare('
		//                      SELECT statistic_ref_id
		//                      FROM '.$this->_tableStatisticRef.'
		//                      WHERE quiz_id = %d AND user_id = %d
		//              ', $statisticRefModel->getQuizId(), $statisticRefModel->getUserId())
		//          );
		//      }

		if ( $refId === null ) {

			$refData = array(
				'quiz_id'        => $statisticRefModel->getQuizId(),
				'quiz_post_id'   => $statisticRefModel->getQuizPostId(),
				'course_post_id' => $statisticRefModel->getCoursePostId(),
				'user_id'        => $statisticRefModel->getUserId(),
				'create_time'    => $statisticRefModel->getCreateTime(),
				'is_old'         => (int) $isOld,
			);

			$refFormat = array( '%d', '%d', '%d', '%d', '%d', '%d' );

			if ( $statisticRefModel->getFormData() !== null && is_array( $statisticRefModel->getFormData() ) ) {
				$refData['form_data'] = @json_encode( $statisticRefModel->getFormData() );
				$refFormat[]          = '%s';
			}

			$this->_wpdb->insert( $this->_tableStatisticRef, $refData, $refFormat );

			$refId = $this->_wpdb->insert_id;
		}

		if ( ! empty( $refId ) ) {

			$questions = array();
			if ( ! empty( $statisticRefModel->getQuizPostId() ) ) {
				$ld_quiz_questions_object = LDLMS_Factory_Post::quiz_questions( $statisticRefModel->getQuizPostId() );
				if ( $ld_quiz_questions_object ) {
					$questions = $ld_quiz_questions_object->get_questions( 'post_ids' );
				}
			}

			foreach ( $statisticModel as $d ) {
				$answerData = $d->getAnswerData() === null ? 'NULL' : $this->_wpdb->prepare( '%s', json_encode( $d->getAnswerData() ) );

				$values[] = '( ' . implode(
					', ',
					array(
						'statistic_ref_id' => $refId,
						'question_id'      => $d->getQuestionId(),
						'question_post_id' => $d->getQuestionPostId(),
						'correct_count'    => $d->getCorrectCount(),
						'incorrect_count'  => $d->getIncorrectCount(),
						'hint_count'       => $d->getHintCount(),
						'points'           => $d->getPoints(),
						'question_time'    => $d->getQuestionTime(),
						'answer_data'      => $answerData,
					)
				) . ' )';
			}

			$this->_wpdb->query(
				'INSERT INTO
					' . $this->_tableStatistic . ' (
						statistic_ref_id, question_id, question_post_id, correct_count, incorrect_count, hint_count, points, question_time, answer_data
					)
				VALUES
					' . implode( ', ', $values )
			);
		}

		return $refId;
	}

	public function deleteUser( $quizId, $userId ) {
		return $this->_wpdb->query(
			$this->_wpdb->prepare(
				'
				DELETE s, sf
				FROM ' . $this->_tableStatistic . ' AS s
					INNER JOIN ' . $this->_tableStatisticRef . ' AS sf
					ON s.statistic_ref_id = sf.statistic_ref_id
				WHERE
					sf.quiz_id = %d AND sf.user_id = %d
			',
				$quizId,
				$userId
			)
		);
	}

	public function deleteAll( $quizId ) {
		return $this->_wpdb->query(
			$this->_wpdb->prepare(
				'
				DELETE s, sf
				FROM ' . $this->_tableStatistic . ' AS s
					INNER JOIN ' . $this->_tableStatisticRef . ' AS sf
					ON s.statistic_ref_id = sf.statistic_ref_id
				WHERE
					sf.quiz_id = %d
			',
				$quizId
			)
		);
	}

	public function deleteUserTest( $quizId, $userId, $testId ) {
		if ( ! $testId ) {
			return $this->deleteUser( $quizId, $userId );
		}

		return $this->_wpdb->query(
			$this->_wpdb->prepare(
				'
				DELETE s, sf
				FROM ' . $this->_tableStatistic . ' AS s
					INNER JOIN ' . $this->_tableStatisticRef . ' AS sf
					ON s.statistic_ref_id = sf.statistic_ref_id
				WHERE
					sf.quiz_id = %d AND sf.user_id = %d AND sf.statistic_ref_id = %d
			',
				$quizId,
				$userId,
				$testId
			)
		);
	}

	public function deleteQuestion( $questionId ) {
		return $this->_wpdb->delete( $this->_tableStatistic, array( 'question_id' => $questionId ), array( '%d' ) );
	}

	public function fetchFormOverview( $quizId, $page, $limit, $onlyUser = 0 ) {

		switch ( $onlyUser ) {
			case 1:
				$where = ' AND sf.user_id > 0 ';
				break;
			case 2:
				$where = ' AND sf.user_id = 0 ';
				break;
			default:
				$where = '';
		}

		$result = $this->_wpdb->get_results(
			$this->_wpdb->prepare(
				'
				SELECT
					u.`user_login`, u.`display_name`, u.ID AS user_id,
					sf.*,
					SUM(s.correct_count) AS correct_count,
					SUM(s.incorrect_count) AS incorrect_count,
					SUM(s.points) AS points
				FROM
					' . $this->_tableStatisticRef . ' AS sf
					INNER JOIN ' . $this->_tableStatistic . ' AS s ON(s.statistic_ref_id = sf.statistic_ref_id)
					LEFT JOIN ' . $this->_wpdb->users . ' AS u ON(u.ID = sf.user_id)
				WHERE
					quiz_id = %d AND sf.form_data IS NOT NULL ' . $where . '
				GROUP BY
					sf.statistic_ref_id
				ORDER BY
					sf.create_time DESC
				LIMIT
					%d, %d
			',
				$quizId,
				$page,
				$limit
			),
			ARRAY_A
		);

		$r = array();

		foreach ( $result as $row ) {
			$row['user_name'] = $row['user_login'] . ' (' . $row['display_name'] . ')';

			$r[] = new WpProQuiz_Model_StatisticFormOverview( $row );
		}

		return $r;
	}

	public function fetchHistory( $quizId = 0, $page = 1, $limit = '', $users = -1, $startTime = 0, $endTime = 0 ) {
		return $this->fetchHistoryWithArgs(
			array(
				'quizId'    => $quizId,
				'page'      => $page,
				'limit'     => $limit,
				'users'     => $users,
				'startTime' => $startTime,
				'endTime'   => $endTime,
			)
		);
	}

	public function fetchHistoryWithArgs( $args = array() ) {
		$where     = '';
		$timeWhere = '';

		$default_args = array(
			'quizId'    => 0,
			'quiz'      => 0,
			'page'      => 1,
			'limit'     => '',
			'users'     => -1,
			'startTime' => 0,
			'endTime'   => 0,
		);
		$args         = wp_parse_args( $args, $default_args );

		switch ( $args['users'] ) {
			case -3: //only anonym
				$where = 'AND user_id = 0';
				break;
			case -2: //only reg user
				$where = 'AND user_id > 0';
				break;
			case -1: //all
				$where = '';
				break;
			default:
				$where = 'AND user_id = ' . (int) $args['users'];
				break;
		}

		if ( $args['startTime'] ) {
			$timeWhere = 'AND create_time >= ' . (int) $args['startTime'];
		}

		if ( $args['endTime'] ) {
			$timeWhere .= ' AND create_time <= ' . (int) $args['endTime'];
		}

		$where = $where . ' ' . $timeWhere;

		/**
		 * Filter Quiz Statistics History Where clause.
		 *
		 * @since 3.2.0
		 *
		 * @param striing $where Where clause.
		 * @param array   $args  Query args.
		 */
		$where = apply_filters( 'learndash_fetch_quiz_statistic_history_where', $where, $args );

		if ( $args['page'] < 1 ) {
			$args['page'] = 1;
		}
		$limit_offset = $args['limit'] * ( $args['page'] - 1 );

		$result = $this->_wpdb->get_results(
			$this->_wpdb->prepare(
				'SELECT
				u.`user_login`, u.`display_name`, u.ID AS user_id,
				sf.*,
				SUM(s.correct_count) AS correct_count,
				SUM(s.incorrect_count) AS incorrect_count,
				SUM(s.points) AS points,
				SUM(q.points) AS g_points
			FROM
				' . $this->_tableStatisticRef . ' AS sf
				INNER JOIN ' . $this->_tableStatistic . ' AS s ON(s.statistic_ref_id = sf.statistic_ref_id)
				LEFT JOIN ' . $this->_wpdb->users . ' AS u ON(u.ID = sf.user_id)
				INNER JOIN ' . $this->_tableQuestion . ' AS q ON(q.id = s.question_id)
			WHERE
				sf.quiz_id = %d AND sf.is_old = 0 ' . $where . '
			GROUP BY
				sf.statistic_ref_id
			ORDER BY
				sf.create_time DESC
			LIMIT
				%d, %d
			',
				$args['quizId'],
				$limit_offset,
				$args['limit']
			),
			ARRAY_A
		);

		$r = array();

		foreach ( $result as $row ) {
			if ( ! empty( $row['user_login'] ) ) {
				$row['user_name'] = $row['user_login'] . ' (' . $row['display_name'] . ')';
			}

			$r[] = new WpProQuiz_Model_StatisticHistory( $row );
		}

		return $r;
	}

	public function countFormOverview( $quizId, $onlyUser ) {

		switch ( $onlyUser ) {
			case 1:
				$where = ' AND user_id > 0 ';
				break;
			case 2:
				$where = ' AND user_id = 0 ';
				break;
			default:
				$where = '';
		}

		return $this->_wpdb->get_var(
			$this->_wpdb->prepare(
				"SELECT
					COUNT(user_id)
					FROM {$this->_tableStatisticRef}
					WHERE
					quiz_id = %d AND form_data IS NOT NULL " . $where,
				$quizId
			)
		);
	}

	public function countHistory( $quizId, $users = -1, $startTime = 0, $endTime = 0 ) {
		$timeWhere = '';
		$where     = '';

		switch ( $users ) {
			case -3: //only anonym
				$where = 'AND user_id = 0';
				break;
			case -2: //only reg user
				$where = 'AND user_id > 0';
				break;
			case -1: //all
				$where = '';
				break;
			default:
				$where = 'AND user_id = ' . (int) $users;
				break;
		}

		if ( $startTime ) {
			$timeWhere = 'AND create_time >= ' . (int) $startTime;
		}

		if ( $endTime ) {
			$timeWhere .= ' AND create_time <= ' . (int) $endTime;
		}

		$where = $where . ' ' . $timeWhere;

		$where = apply_filters( 'learndash_fetch_statistic_form_history_count_where', $where, $users, $users, $startTime, $endTime );

		return $this->_wpdb->get_var(
			$this->_wpdb->prepare(
				"SELECT COUNT(user_id) FROM {$this->_tableStatisticRef} WHERE quiz_id = %d AND is_old = 0 {$where}",
				$quizId
			)
		);
	}

	public function deleteByRefId( $refId ) {
		return $this->_wpdb->query(
			$this->_wpdb->prepare(
				'
				DELETE s, sf
				FROM ' . $this->_tableStatistic . ' AS s
					INNER JOIN ' . $this->_tableStatisticRef . ' AS sf
					ON (s.statistic_ref_id = sf.statistic_ref_id)
				WHERE
					sf.statistic_ref_id = %d
			',
				$refId
			)
		);
	}

	public function deleteByUserIdQuizId( $userId, $quizId ) {
		return $this->_wpdb->query(
			$this->_wpdb->prepare(
				'
				DELETE s, sf
				FROM ' . $this->_tableStatistic . ' AS s
					INNER JOIN ' . $this->_tableStatisticRef . ' AS sf
					ON (s.statistic_ref_id = sf.statistic_ref_id)
				WHERE
					sf.user_id = %d AND sf.quiz_id = %d
			',
				$userId,
				$quizId
			)
		);
	}

	public function fetchStatisticOverview( $quizId, $onlyCompleded, $start, $limit ) {
		return $this->fetchStatisticOverviewWithArgs(
			array(
				'quizId'        => $quizId,
				'onlyCompleded' => $onlyCompleded,
				'start'         => $start,
				'limit'         => $limit,
			)
		);
	}

	public function fetchStatisticOverviewWithArgs( $args = array() ) {
		$a     = array();
		$where = '';

		$default_args = array(
			'quizId'        => 0,
			'quiz'          => 0,
			'onlyCompleded' => '',
			'start'         => 0,
			'limit'         => 50,
		);

		$args = wp_parse_args( $args, $default_args );

		/**
		 * Filter Quiz Statistics Overview Where clause.
		 *
		 * @since 3.2.0
		 *
		 * @param striing $where Where clause.
		 * @param array   $args  Query args.
		 */

		$where = apply_filters( 'learndash_fetch_quiz_statistic_overview_where', $where, $args );

		$sql = $this->_wpdb->prepare(
			'(
				SELECT
					u.`user_login`, u.`display_name`, u.ID AS user_id,
					SUM(s.`correct_count`) as correct_count,
					SUM(s.`incorrect_count`) as incorrect_count,
					SUM(s.`hint_count`) as hint_count,
					SUM(s.`points`) as points,
					AVG(s.question_time) as question_time,
					SUM(q.points * (s.correct_count + s.incorrect_count)) AS g_points
				FROM
					' . $this->_wpdb->users . ' AS u
					' . ( $args['onlyCompleded'] ? 'INNER' : 'LEFT' ) . ' JOIN ' . $this->_tableStatisticRef . ' AS sf ON (sf.user_id = u.ID AND sf.quiz_id = %d)
					LEFT JOIN ' . $this->_tableStatistic . ' AS s ON ( s.statistic_ref_id = sf.statistic_ref_id )
					LEFT JOIN ' . $this->_tableQuestion . ' AS q ON(q.id = s.question_id)
					WHERE 1=1 ' . $where . '
				GROUP BY u.ID
			)
			UNION
			(
				SELECT
					NULL, NULL, 0,
					SUM(s.`correct_count`) as correct_count,
					SUM(s.`incorrect_count`) as incorrect_count,
					SUM(s.`hint_count`) as hint_count,
					SUM(s.`points`) as points,
					AVG(s.question_time) as question_time,
					SUM(q.points * (s.correct_count + s.incorrect_count)) AS g_points
				FROM
					' . $this->_tableMaster . ' AS m
					' . ( $args['onlyCompleded'] ? 'INNER' : 'LEFT' ) . ' JOIN ' . $this->_tableStatisticRef . ' AS sf ON(sf.quiz_id = m.id AND sf.user_id = 0)
					LEFT JOIN ' . $this->_tableStatistic . ' AS s ON (s.statistic_ref_id = sf.statistic_ref_id)
					LEFT JOIN ' . $this->_tableQuestion . ' AS q ON (q.id = s.question_id)
				WHERE
					m.id = %d
				GROUP BY sf.user_id
			)

			ORDER BY
				user_login
			LIMIT
				%d, %d',
			$args['quizId'],
			$args['quizId'],
			$args['start'],
			$args['limit']
		);

		//);
		//error_log( 'sql[' . $sql . ']' );
		$results = $this->_wpdb->get_results( $sql, ARRAY_A );
		foreach ( $results as $row ) {
			if ( ! empty( $row['user_login'] ) ) {
				$row['user_name'] = $row['user_login'] . ' (' . $row['display_name'] . ')';
			}

			$a[] = new WpProQuiz_Model_StatisticOverview( $row );
		}

		return $a;
	}

	public function countOverviewNew( $quizId, $onlyCompleded ) {
		return $this->_wpdb->get_var(
			$this->_wpdb->prepare(
				'SELECT
					COUNT(*) as g_count
				FROM
					(
						(SELECT
							u.ID
						FROM
							' . $this->_wpdb->users . ' AS u
							' . ( $onlyCompleded ? 'INNER' : 'LEFT' ) . ' JOIN ' . $this->_tableStatisticRef . ' AS sf ON (sf.user_id = u.ID AND sf.quiz_id = %d)
							LEFT JOIN ' . $this->_tableStatistic . ' AS s ON ( s.statistic_ref_id = sf.statistic_ref_id )
							LEFT JOIN ' . $this->_tableQuestion . ' AS q ON(q.id = s.question_id)
						GROUP BY u.ID )
					UNION
						(SELECT
							sf.user_id
						FROM
							' . $this->_tableMaster . ' AS m
							' . ( $onlyCompleded ? 'INNER' : 'LEFT' ) . ' JOIN ' . $this->_tableStatisticRef . ' AS sf ON(sf.quiz_id = m.id AND sf.user_id = 0)
							LEFT JOIN ' . $this->_tableStatistic . ' AS s ON (s.statistic_ref_id = sf.statistic_ref_id)
							LEFT JOIN ' . $this->_tableQuestion . ' AS q ON (q.id = s.question_id)
						WHERE
							m.id = %d
						GROUP BY sf.user_id)
					) AS c_all',
				$quizId,
				$quizId
			)
		);
	}

	public function fetchFrontAvg( $quizId ) {
		return $this->_wpdb->get_row(
			$this->_wpdb->prepare(
				"SELECT
					SUM(s.points) AS points,
					SUM(q.points * (s.correct_count + s.incorrect_count)) AS g_points
				FROM
					{$this->_tableStatisticRef} AS sf
					INNER JOIN {$this->_tableStatistic} AS s ON ( s.statistic_ref_id = sf.statistic_ref_id )
					INNER JOIN {$this->_tableQuestion} AS q ON ( q.id = s.question_id )
				WHERE
					sf.quiz_id = %d",
				$quizId
			),
			ARRAY_A
		);
	}

	/**
	 * Fetch total points for a quiz by stat_ref_id.
	 *
	 * @param int $ref_id Statistic reference ID.
	 *
	 * @return array
	 */
	public function fetchTotalPoints( $ref_id ) {
		$query = "SELECT SUM(ldqn.points) as total_points FROM {$this->_tableQuestion} as ldqn
					INNER JOIN {$this->_tableStatistic} as ldstat ON ldqn.id = ldstat.question_id
					WHERE ldstat.statistic_ref_id = %d";

		return $this->_wpdb->get_var(
			$this->_wpdb->prepare( $query, $ref_id )
		);
	}

	/**
	 * Query Statistics Forms data.
	 *
	 * @since 3.5.0
	 *
	 * @param int   $quiz_id    ProQuiz Quiz ID.
	 * @param array $query_args Array of Query parameters.
	 *
	 * @return array Array of `WpProQuiz_Model_StatisticRefModel` instances.
	 */
	public function fetchWithForms( $quiz_pro_id = 0, $query_args = array() ) {
		$form_entries = array();

		$default_args = array(
			'user_id'  => '',
			'orderby'  => 'date',
			'order'    => 'DESC',
			'per_page' => 10,
			'page'     => 1,
		);

		$query_args = wp_parse_args( $query_args, $default_args );

		$quiz_pro_id = absint( $quiz_pro_id );
		if ( ! empty( $quiz_pro_id ) ) {
			$sql_str                = 'SELECT * FROM ' . $this->_tableStatisticRef . " WHERE form_data != '' AND quiz_id = %d ";
			$sql_placeholder_values = array( $quiz_pro_id );

			if ( ( isset( $query_args['user_id'] ) ) && ( ! empty( $query_args['user_id'] ) ) ) {
				$sql_str                 .= ' AND user_id = %d ';
				$sql_placeholder_values[] = $query_args['user_id'];
			}

			if ( 'date' === $query_args['orderby'] ) {
				$query_args['orderby'] = 'create_time';
			}
			$sql_str .= ' ORDER BY ' . $query_args['orderby'] . ' ' . $query_args['order'];
			$sql_str .= ' LIMIT ' . $query_args['per_page'] * ( $query_args['page'] - 1 ) . ', ' . $query_args['per_page'];

			$sql_str_full = $this->_wpdb->prepare( $sql_str, $sql_placeholder_values );

			$results = $this->_wpdb->get_results(
				$this->_wpdb->prepare( $sql_str, $sql_placeholder_values ),
				ARRAY_A
			);

			foreach ( $results as $row ) {
				$form_entries[] = new WpProQuiz_Model_StatisticRefModel( $row );
			}
		}

		return $form_entries;
	}

	/**
	 * Query Statistics Forms data.
	 *
	 * @since 3.5.0
	 *
	 * @param int   $quiz_id    ProQuiz Quiz ID.
	 * @param array $query_args Array of Query parameters.
	 *
	 * @return array Array of `WpProQuiz_Model_StatisticRefModel` instances.
	 */
	public function fetchWithFormsTotal( $quiz_pro_id = 0, $query_args = array() ) {
		$result_total = 0;
		$default_args = array(
			'user_id' => '',
		);

		$query_args = wp_parse_args( $query_args, $default_args );

		$quiz_pro_id = absint( $quiz_pro_id );
		if ( ! empty( $quiz_pro_id ) ) {
			$sql_str                = 'SELECT count(*) as count FROM ' . $this->_tableStatisticRef . " WHERE form_data != '' AND quiz_id = %d ";
			$sql_placeholder_values = array( $quiz_pro_id );

			if ( ( isset( $query_args['user_id'] ) ) && ( ! empty( $query_args['user_id'] ) ) ) {
				$sql_str                 .= ' AND user_id = %d ';
				$sql_placeholder_values[] = $query_args['user_id'];
			}

			$sql_str_full = $this->_wpdb->prepare( $sql_str, $sql_placeholder_values );
			$result_total = $this->_wpdb->get_var(
				$this->_wpdb->prepare( $sql_str, $sql_placeholder_values )
			);
		}

		return $result_total;
	}
}
