<?php

declare( strict_types=1 );

namespace LearnDash\Hub\Framework;

defined( 'ABSPATH' ) || exit;

/**
 * This is a data mapper for CRUD
 *
 * Class DB
 *
 * @package LearnDash\Core
 */
class DB_Builder extends Base {
	/**
	 * Where statements
	 *
	 * @var array
	 */
	private $where = array();

	/**
	 * Store the order statement.
	 *
	 * @var string
	 */
	private $order = '';

	/**
	 * Store the limit statement.
	 *
	 * @var string
	 */
	private $limit = '';


	/**
	 * Use to store last query
	 *
	 * @var string
	 */
	public $saved_queries = '';

	/**
	 * The table that will get query on.
	 *
	 * @var string
	 */
	protected $table_name = '';

	/**
	 * DB_Builder constructor.
	 *
	 * @param string $table_name The table name.
	 */
	public function __construct( string $table_name ) {
		$this->table_name = $table_name;
	}

	/**
	 * Define the conditions, for example
	 * where('property','value) This will create an equal statement.
	 * where('property','operator','value') This will create a statement with custom operator.
	 *
	 * @param mixed ...$args The args.
	 *
	 * @return $this
	 */
	public function where( ...$args ) {
		global $wpdb;
		if ( 2 === count( $args ) ) {
			list( $key, $value ) = $args;
			$place_holder        = $this->guess_var_type( $value );
			$this->where[]       = $wpdb->prepare( "`$key` = $place_holder", $value );

			return $this;
		}

		list( $key, $operator, $value ) = $args;
		if ( ! $this->valid_operator( $operator ) ) {
			// prevent this operator.
			return $this;
		}
		if ( in_array( strtolower( $operator ), array( 'in', 'not in' ), true ) ) {
			$tmp           = $key . " {$operator} (" . implode(
				', ',
				array_fill( 0, count( $value ), $this->guess_var_type( $value ) )
			) . ')';
			$sql           = call_user_func_array(
				array(
					$wpdb,
					'prepare',
				),
				array_merge( array( $tmp ), $value )
			);
			$this->where[] = $sql;
		} elseif ( 'between' === strtolower( $operator ) ) {
			$this->where[] = $wpdb->prepare(
				"{$key} {$operator} {$this->guess_var_type($value[0])} AND {$this->guess_var_type($value[1])}",
				$value[0],
				$value[1]
			);
		} else {
			$this->where[] = $wpdb->prepare( "`$key` $operator {$this->guess_var_type($value)}", $value );
		}

		return $this;
	}

	/**
	 * Guess the type of value for correcting placeholder
	 *
	 * @param mixed $value The query value.
	 *
	 * @return string
	 */
	private function guess_var_type( $value ): string {
		if ( filter_var( $value, FILTER_VALIDATE_INT ) ) {
			return '%d';
		}

		if ( filter_var( $value, FILTER_VALIDATE_FLOAT ) ) {
			return '%f';
		}

		return '%s';
	}

	/**
	 * Find a record by it's ID
	 *
	 * @param int $id The record ID.
	 *
	 * @return $this
	 */
	public function find_by_id( int $id ): DB_Builder {
		global $wpdb;
		$this->where[] = $wpdb->prepare( 'id = %d', $id );

		return $this;
	}

	/**
	 * Build order statement.
	 *
	 * @param string $order_by The field that used to order.
	 * @param string $order    Order oriental.
	 *
	 * @return $this
	 */
	public function order_by( string $order_by, string $order = 'asc' ): DB_Builder {
		global $wpdb;
		$order = strtolower( $order );
		if ( ! in_array( $order, array( 'asc', 'desc' ), true ) ) {
			// fall it back.
			$order = 'asc';
		}
		$this->order = str_replace(
			"'",
			'',
			$wpdb->prepare( 'ORDER BY %s %s', $order_by, $order )
		);

		return $this;
	}

	/**
	 * Build the limit statement.
	 *
	 * @param string $offset The offset in format offset,limit.
	 *
	 * @return $this
	 * @throws \Exception
	 */
	public function limit( string $offset ) {
		$trunk = explode( ',', $offset );
		if ( count( $trunk ) === 1 ) {
			$this->limit = 'LIMIT ' . absint( $offset[0] );

			return $this;
		} elseif ( count( $trunk ) === 2 ) {
			$offset      = absint( $trunk[0] );
			$limit       = absint( $trunk[1] );
			$this->limit = "LIMIT {$offset},{$limit}";

			return $this;
		}
		throw new \Exception( 'Invalid parameters' );
	}

	/**
	 * Execute the query and return the first record.
	 *
	 * @return null|array
	 */
	public function first() {
		$this->limit         = 'LIMIT 0,1';
		$sql                 = $this->query_build();
		$this->saved_queries = $sql;
		global $wpdb;
		$data = $wpdb->get_row( $sql, ARRAY_A );
		if ( is_null( $data ) ) {
			return null;
		}

		return $data;
	}

	/**
	 * Execute the query and return the results.
	 *
	 * @return array
	 */
	public function get() {
		$sql                 = $this->query_build();
		$this->saved_queries = $sql;
		global $wpdb;
		$data = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_null( $data ) ) {
			return array();
		}

		return $data;
	}

	/**
	 * Run the query to see if the record is exist or not.
	 *
	 * @return string|null
	 */
	public function exists() {
		global $wpdb;
		$sql                 = $this->query_build();
		$sql                 = "SELECT EXISTS ($sql)";
		$this->saved_queries = $sql;

		return $wpdb->get_var( $sql );
	}

	/**
	 * Execute the query but return count.
	 *
	 * @return int
	 */
	public function count() {
		global $wpdb;
		$sql = $this->query_build( 'COUNT(*)' );

		$count = $wpdb->get_var( $sql );

		return absint( $count );
	}

	/**
	 * Reset all the queries prepare after an action
	 */
	private function clear() {
		$this->where = array();
		$this->order = '';
		$this->limit = '';
	}

	/**
	 * Join the stuff on the table to make a full query statement
	 *
	 * @param string $select The fields return, default all.
	 *
	 * @return string
	 */
	private function query_build( string $select = '*' ): string {
		$table = $this->table_name;
		$where = implode( ' AND ', $this->where );

		$order_by = $this->order;
		$limit    = $this->limit;
		$sql      = "SELECT $select FROM $table WHERE $where $order_by $limit";
		$this->clear();

		return $sql;
	}

	/**
	 * Validate the operator.
	 *
	 * @param string $operator The sql operator.
	 *
	 * @return bool
	 */
	private function valid_operator( string $operator ): bool {
		$operator = strtolower( $operator );
		$allowed  = array(
			'in',
			'not in',
			'>',
			'<',
			'=',
			'<=',
			'>=',
			'like',
			'between',
		);

		return in_array( $operator, $allowed, true );
	}
}
