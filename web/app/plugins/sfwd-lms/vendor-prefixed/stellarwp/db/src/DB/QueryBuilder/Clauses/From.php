<?php
/**
 * @license GPL-2.0
 *
 * Modified by learndash on 21-June-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace StellarWP\Learndash\StellarWP\DB\QueryBuilder\Clauses;

use StellarWP\Learndash\StellarWP\DB\QueryBuilder\QueryBuilder;

/**
 * @since 1.0.0
 */
class From {
	/**
	 * @var string|RawSQL
	 */
	public $table;

	/**
	 * @var string
	 */
	public $alias;

	/**
	 * @param  string|RawSQL  $table
	 * @param  string|null  $alias
	 */
	public function __construct( $table, $alias = null ) {
		$this->table = QueryBuilder::prefixTable( $table );
		$this->alias = trim( $alias );
	}
}
