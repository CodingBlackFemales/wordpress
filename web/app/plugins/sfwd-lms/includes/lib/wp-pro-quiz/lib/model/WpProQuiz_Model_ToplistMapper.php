<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName,PSR2.Classes.PropertyDeclaration.Underscore
class WpProQuiz_Model_ToplistMapper extends WpProQuiz_Model_Mapper {

	public function countFree( $quizId, $name, $email, $ip, $clearTime = null ) {
		$c = '';

		if ( null !== $clearTime ) {
			$c = 'AND date >= ' . ( time() - $clearTime );
		}

		$flooding = time() - 15;

		return $this->_wpdb->get_var(
			$this->_wpdb->prepare(
				"SELECT COUNT(*)
					FROM {$this->_tableToplist}
					WHERE quiz_id = %d AND (name = %s OR email = %s OR (ip = %s AND date >= {$flooding})) " . $c,
				$quizId,
				$name,
				$email,
				$ip
			)
		);
	}

	public function countUser( $quizId, $userId, $clearTime = null ) {
		$c = '';

		if ( null !== $clearTime ) {
			$c = 'AND date >= ' . ( time() - $clearTime );
		}

		return $this->_wpdb->get_var(
			$this->_wpdb->prepare(
				"SELECT	COUNT(*)
							FROM {$this->_tableToplist}
							WHERE quiz_id = %d AND user_id = %d " . $c,
				$quizId,
				$userId
			)
		);
	}

	public function count( $quizId ) {
		return $this->_wpdb->get_var(
			$this->_wpdb->prepare(
				"SELECT	COUNT(*) FROM {$this->_tableToplist} WHERE quiz_id = %d",
				$quizId
			)
		);
	}

	public function save( WpProQuiz_Model_Toplist $toplist ) {
		$result = $this->_wpdb->insert(
			$this->_tableToplist,
			array(
				'quiz_id' => $toplist->getQuizId(),
				'user_id' => $toplist->getUserId(),
				'date'    => $toplist->getDate(),
				'name'    => $toplist->getName(),
				'email'   => $toplist->getEmail(),
				'points'  => $toplist->getPoints(),
				'result'  => $toplist->getResult(),
				'ip'      => $toplist->getIp(),
			),
			array( '%d', '%d', '%d', '%s', '%s', '%d', '%f', '%s' )
		);

		$toplist->setToplistId( $this->_wpdb->insert_id );
	}

	public function fetch( $quizId, $limit, $sort, $start = 0 ) {
		return $this->fetchWithArgs(
			array(
				'quizId' => $quizId,
				'limit'  => $limit,
				'sort'   => $sort,
				'start'  => $start,
			)
		);
	}
	public function fetchWithArgs( $args = array() ) {
		$s     = '';
		$r     = array();
		$where = '';

		$default_args = array(
			'quizId' => 0,
			'quiz'   => 0,
			'limit'  => '',
			'sort'   => '',
			'start'  => 0,
		);

		$args = wp_parse_args( $args, $default_args );

		$start = absint( $args['start'] );

		if ( empty( $args['quiz'] ) ) {
			if ( isset( $_GET['post_id'] ) ) {
				$post_id = absint( $_GET['post_id'] );
				if ( ! empty( $post_id ) ) {
					$args['quiz'] = $post_id;
				}
			}
		}

		switch ( $args['sort'] ) {
			case WpProQuiz_Model_Quiz::QUIZ_TOPLIST_SORT_BEST:
				$s = 'ORDER BY result DESC';
				break;
			case WpProQuiz_Model_Quiz::QUIZ_TOPLIST_SORT_NEW:
				$s = 'ORDER BY date DESC';
				break;
			case WpProQuiz_Model_Quiz::QUIZ_TOPLIST_SORT_OLD:
			default:
				$s = 'ORDER BY date ASC';
				break;
		}

		/**
		 * LEARNDASH-5105
		 * We only filter the leaderboard if we ar enot showing the front_toplist.
		 */
		if ( 'wp_ajax_wp_pro_quiz_show_front_toplist' !== current_action() ) {
			$where = apply_filters( 'learndash_fetch_quiz_toplist_history_where', $where, $args );
		}

		$results = $this->_wpdb->get_results(
			$this->_wpdb->prepare(
				'SELECT * FROM ' . $this->_tableToplist . ' WHERE quiz_id = %d ' . $where . ' ' . $s . ' LIMIT %d, %d',
				$args['quizId'],
				$args['start'],
				$args['limit']
			),
			ARRAY_A
		);

		foreach ( $results as $row ) {
			$r[] = new WpProQuiz_Model_Toplist( $row );
		}

		return $r;
	}

	public function delete( $quizId, $toplistIds = null ) {
		$quizId = (int) $quizId;

		if ( null === $toplistIds ) {
			return $this->_wpdb->delete( $this->_tableToplist, array( 'quiz_id' => $quizId ), array( '%d' ) );
		}

		$ids = array_map( 'intval', (array) $toplistIds );

		return $this->_wpdb->query( "DELETE FROM {$this->_tableToplist} WHERE quiz_id = {$quizId} AND toplist_id IN(" . implode( ', ', $ids ) . ')' );
	}

}
