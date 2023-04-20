<?php
/**
 * LearnDash Admin Filter Post.
 *
 * @since 4.2.0
 *
 * @package LearnDash\Filters
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'Learndash_Admin_Filter' ) &&
	! class_exists( 'Learndash_Admin_Filter_Post' )
) {
	/**
	 * Filters by a post field.
	 *
	 * @since 4.2.0
	 */
	abstract class Learndash_Admin_Filter_Post extends Learndash_Admin_Filter {
		/**
		 * Post field name.
		 *
		 * @since 4.2.0
		 *
		 * @var string
		 */
		protected $post_field_name;

		/**
		 * Label.
		 *
		 * @since 4.2.0
		 *
		 * @var string
		 */
		private $label;

		/**
		 * Construct.
		 *
		 * @since 4.2.0
		 *
		 * @param string $post_field_name Post field name.
		 * @param string $label Label.
		 */
		public function __construct( string $post_field_name, string $label ) {
			$this->post_field_name = sanitize_key( $post_field_name );
			$this->label           = $label;
		}

		/**
		 * Returns the label.
		 *
		 * @since 4.2.0
		 *
		 * @return string
		 */
		public function get_label(): string {
			return __( 'Where', 'learndash' ) . ' ' . $this->label;
		}

		/**
		 * Returns the parameter name.
		 *
		 * @since 4.2.0
		 *
		 * @return string
		 */
		public function get_parameter_name(): string {
			return $this->post_field_name;
		}

		/**
		 * Returns the SQL join clause to apply this filter.
		 *
		 * @since 4.2.0
		 *
		 * @param mixed $filter_value The value of the filter.
		 *
		 * @return string The SQL join clause.
		 */
		public function get_sql_join_clause( $filter_value ): string {
			return ''; // we do not need to join anything.
		}

		/**
		 * Returns the SQL where clause to apply this filter.
		 *
		 * @since 4.2.0
		 *
		 * @param mixed $filter_value The value of the filter.
		 *
		 * @return string The SQL where clause.
		 */
		public function get_sql_where_clause( $filter_value ): string {
			if ( empty( $filter_value ) ) {
				return '';
			}

			global $wpdb;

			// if the filter value is an array, then we need to build a query that will match all the values.
			if ( is_array( $filter_value ) ) {
				$filter_value = array_map( 'sanitize_text_field', $filter_value );
				$filter_value = "'" . implode( "','", $filter_value ) . "'";
				return "{$wpdb->posts}.{$this->get_parameter_name()} IN ($filter_value)";
			}

			// otherwise searching with like.
			return $wpdb->prepare(
				"{$wpdb->posts}.{$this->get_parameter_name()} LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'%' . $wpdb->esc_like( $filter_value ) . '%'
			);
		}
	}
}
