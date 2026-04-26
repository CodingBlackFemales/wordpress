<?php

namespace StellarWP\Learndash\StellarWP\DB\QueryBuilder;

use StellarWP\Learndash\StellarWP\DB\QueryBuilder\Concerns\WhereClause;

/**
 * @since 1.0.0
 */
class WhereQueryBuilder {
	use WhereClause;

	/**
	 * @return string[]
	 */
	public function getSQL() {
		return $this->getWhereSQL();
	}
}
