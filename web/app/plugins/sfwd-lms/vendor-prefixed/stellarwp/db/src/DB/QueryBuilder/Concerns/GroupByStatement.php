<?php
/**
 * @license GPL-2.0
 *
 * Modified by learndash on 21-June-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace StellarWP\Learndash\StellarWP\DB\QueryBuilder\Concerns;

use StellarWP\Learndash\StellarWP\DB\DB;

/**
 * @since 1.0.0
 */
trait GroupByStatement {
	/**
	 * @var array
	 */
	protected $groupByColumns = [];

	/**
	 * @return $this
	 */
	public function groupBy( $tableColumn ) {
		if ( ! in_array( $tableColumn, $this->groupByColumns, true ) ) {
			$this->groupByColumns[] = DB::prepare( '%1s', $tableColumn );
		}

		return $this;
	}

	protected function getGroupBySQL() {
		return ! empty( $this->groupByColumns )
			? [ 'GROUP BY ' . implode( ',', $this->groupByColumns ) ]
			: [];
	}
}
