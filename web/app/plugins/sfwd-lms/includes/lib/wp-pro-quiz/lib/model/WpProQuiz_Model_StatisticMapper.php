<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WpProQuiz_Model_StatisticMapper extends WpProQuiz_Model_Mapper {

	public function fetchAllByRef( $statisticRefId ) {
		$a = array();

		$results = $this->_wpdb->get_results(
			$this->_wpdb->prepare(
				'SELECT
							*
						FROM
							' . $this->_tableStatistic . '
						WHERE
							statistic_ref_id = %d',
				$statisticRefId
			),
			ARRAY_A
		);

		foreach ( $results as $row ) {
			$a[] = new WpProQuiz_Model_Statistic( $row );
		}

		return $a;
	}

	public function isStatisticByQuestionId( $questionId ) {
		return $this->_wpdb->get_var(
			$this->_wpdb->prepare(
				"SELECT
					COUNT(*)
				FROM
					{$this->_tableStatistic}
				WHERE
					question_id = %d",
				$questionId
			)
		);
	}

	/**
	 * Fetch statistics data by passing ref ids.
	 *
	 * @param array $ref_ids Array of ref ids.
	 *
	 * @return WpProQuiz_Model_Statistic[]
	 */
	public function fetchByRefs( array $ref_ids ): array {
		$a       = array();
		$ref_ids = array_map( 'intval', $ref_ids );

		if ( ! empty( $ref_ids ) ) {
			$how_many     = count( $ref_ids );
			$placeholders = array_fill( 0, $how_many, '%d' );
			$format       = implode( ', ', $placeholders );
			$query        = 'SELECT * FROM `' . $this->_tableStatistic . '` WHERE statistic_ref_id IN ( ' . $format . ' )';
			$results      = $this->_wpdb->get_results( $this->_wpdb->prepare( $query, $ref_ids ), ARRAY_A );

			foreach ( $results as $row ) {
				$a[] = new WpProQuiz_Model_Statistic( $row );
			}
		}

		return $a;
	}
}
