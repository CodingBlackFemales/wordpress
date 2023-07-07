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
class Union {
	/**
	 * @var QueryBuilder
	 */
	public $builder;

	/**
	 * @var bool
	 */
	public $all = false;

	/**
	 * @param  QueryBuilder  $builder
	 * @param  bool  $all
	 */
	public function __construct( $builder, $all = false ) {
		$this->builder = $builder;
		$this->all     = $all;
	}
}
