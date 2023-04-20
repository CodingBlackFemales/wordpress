<?php
/**
 * LearnDash Factory Class.
 *
 * This is an abstract class for Course Posts, User Progression, etc.
 *
 * @since 2.5.0
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LDLMS_Factory' ) ) {

	/**
	 * Class for LearnDash LMS Factory.
	 *
	 * @since 2.5.0
	 */
	abstract class LDLMS_Factory {

		/**
		 * Static array of object instances.
		 *
		 * @var array $instances.
		 */
		protected static $instances = array();

		/**
		 * Public constructor for class.
		 *
		 * @since 2.5.0
		 */
		public function __construct() {
		}

		/**
		 * Get the current instance of this class or new.
		 *
		 * @since 2.5.0
		 *
		 * @param string $model        Unique identifier for model.
		 * @param string $key          Unique identifier for instance.
		 * @param bool   $add_instance Optional. Whether to add an instance. Default true.
		 */
		protected static function get_instance( $model = '', $key = null, $add_instance = true ) {
			$model = esc_attr( $model );
			$key   = esc_attr( $key );

			if ( ( ! empty( $model ) ) && ( ! empty( $key ) ) ) {
				if ( isset( self::$instances[ $model ][ $key ] ) ) {
					return self::$instances[ $model ][ $key ];
				} elseif ( true === $add_instance ) {
					return self::add_instance( $model, $key );
				}
			}
		}

		/**
		 * Add Model instance.
		 *
		 * @since 2.5.0
		 *
		 * @param string     $model    Class name to add.
		 * @param int|string $key      Unique key for instance.
		 * @param mixed      ...$args  Args passed to class constructor.
		 */
		protected static function add_instance( $model = '', $key = null, ...$args ) {
			$model = esc_attr( $model );
			$key   = esc_attr( $key );

			if ( ( ! empty( $model ) ) && ( class_exists( $model ) ) && ( ! empty( $key ) ) ) {
				if ( ! isset( self::$instances[ $model ] ) ) {
					self::$instances[ $model ] = array();
				}

				if ( isset( self::$instances[ $model ][ $key ] ) ) {
					return self::$instances[ $model ][ $key ];
				} else {
					try {
						$class                             = new ReflectionClass( $model );
						self::$instances[ $model ][ $key ] = $class->newInstanceArgs( $args );
						return self::$instances[ $model ][ $key ];
					} catch ( LDLMS_Exception_NotFound $e ) {
						return null;
					}
				}
			}
		}

		/**
		 * Remove Model instance.
		 *
		 * @since 2.5.0
		 *
		 * @param string     $model Class name to add.
		 * @param int|string $key   Unique ID for instance.
		 */
		protected static function remove_instance( $model = '', $key = null ) {
			$model = esc_attr( $model );
			$key   = esc_attr( $key );

			if ( ( ! empty( $model ) ) && ( class_exists( $model ) ) && ( ! empty( $key ) ) ) {
				if ( isset( self::$instances[ $model ][ $key ] ) ) {
					unset( self::$instances[ $model ][ $key ] );
					return true;
				}
			}
		}
	}
}

