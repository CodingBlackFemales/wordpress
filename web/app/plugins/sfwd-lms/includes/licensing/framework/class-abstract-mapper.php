<?php

declare( strict_types=1 );

namespace LearnDash\Hub\Framework;

defined( 'ABSPATH' ) || exit;

/**
 * Class Mapper
 *
 * @package Hub\Model\Db
 */
abstract class Mapper {
	/**
	 * Singleton, quick way to access the function without create new instance of this class.
	 *
	 * @var Mapper
	 */
	private static $instance;

	/**
	 * Storing db error from $wpdb;
	 *
	 * @var string
	 */
	private $db_error;

	/**
	 * A helper for building sql query.
	 *
	 * @var DB_Builder
	 */
	protected $builder;

	/**
	 * Get an instance of this class.
	 *
	 * @return Mapper
	 */
	public static function instance(): Mapper {
		if ( ! is_object( self::$instance ) ) {
			$class          = static::class;
			self::$instance = new $class();
		}

		return self::$instance;
	}

	/**
	 * Mapper constructor.
	 */
	public function __construct() {
		$this->builder = new DB_Builder( $this->get_table_name() );
	}

	/**
	 * Store a model into database
	 *
	 * @param Model $model The model object.
	 *
	 * @return int The record ID or 0 if it is fail
	 */
	public function save( Model $model ): int {
		global $wpdb;
		if ( 0 === $model->id ) {
			$ret  = $wpdb->insert( $this->get_table_name(), $model->to_array() );
			$case = 'i';
		} else {
			$ret  = $wpdb->update( $this->get_table_name(), $model->to_array(), array( 'id' => $model->id ) );
			$case = 'u';
		}

		if ( false === $ret ) {
			// database error, log and quit.
			$this->db_error = $wpdb->last_error;

			return 0;
		}

		if ( 0 === $ret ) {
			// something wrong, the insert/update not affected.
			// todo separate error.
			$this->db_error = $wpdb->last_error . ' | 0';

			return 0;
		}

		if ( 'i' === $case ) {
			$model->id = $wpdb->insert_id;
		}

		return $model->id;
	}


	/**
	 * @param int $id
	 *
	 * @return bool
	 */
	public function delete( int $id ): bool {
		global $wpdb;
		$ret = $wpdb->delete( $this->get_table_name(), array( 'id' => $id ) );
		return 1 === $ret;
	}

	/**
	 * Return the DB error happen when executing sql.
	 *
	 * @return string
	 */
	public function get_error(): string {
		return $this->db_error;
	}

	/**
	 * Find a record by ID.
	 *
	 * @param int $id The record ID that we need to look for.
	 *
	 * @return Model|null
	 */
	public function find_by_id( int $id ) {
		$cache_key = $this->get_table_name() . '_' . $id;
		$row       = wp_cache_get( $cache_key, 'hub' );
		if ( empty( $row ) ) {
			// query it.
			$row = $this->builder->find_by_id( $id )->first();
		}

		if ( ! empty( $row ) ) {
			wp_cache_set( $cache_key, $row, 'hub', HOUR_IN_SECONDS );

			return $this->populate( $row );
		}

		return null;
	}

	/**
	 * Get the table name from class mapper;
	 *
	 * @return string
	 */
	abstract public function get_table_name(): string;

	/**
	 * Populate the database data into the domain class.
	 *
	 * @param array $data The row data from database, which we use to populate the Model class.
	 *
	 * @return Model
	 */
	abstract public function populate( array $data ): Model;

	/**
	 * Sql setup script should be here.
	 */
	abstract public function install();
}
