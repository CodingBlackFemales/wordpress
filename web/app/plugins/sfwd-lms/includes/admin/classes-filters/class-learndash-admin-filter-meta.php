<?php
/**
 * LearnDash Admin Filter Meta Value.
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
	! class_exists( 'Learndash_Admin_Filter_Meta' )
) {
	/**
	 * Filters posts by meta value.
	 *
	 * @since 4.2.0
	 */
	abstract class Learndash_Admin_Filter_Meta extends Learndash_Admin_Filter {
		/**
		 * Meta key name.
		 *
		 * @since 4.2.0
		 *
		 * @var string
		 */
		protected $meta_key_name;

		/**
		 * Meta value index.
		 *
		 * @since 4.2.0
		 *
		 * @var string
		 */
		protected $meta_value_index;

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
		 * @param string $meta_key_name    Meta key name.
		 * @param string $label            Label.
		 * @param string $meta_value_index Meta value index, if the meta value is an array.
		 */
		public function __construct( string $meta_key_name, string $label, string $meta_value_index = '' ) {
			$this->meta_key_name    = sanitize_key( $meta_key_name );
			$this->label            = $label;
			$this->meta_value_index = sanitize_key( $meta_value_index );
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
			return $this->meta_key_name . '_' . $this->meta_value_index;
		}

		/**
		 * Returns the alias for the postmeta table.
		 *
		 * @since 4.2.0
		 *
		 * @return string
		 */
		protected function get_meta_alias(): string {
			return '`m' . $this->get_parameter_name() . '`';
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
			global $wpdb;

			if ( empty( $this->meta_key_name ) || ( empty( $this->meta_value_index ) && empty( $filter_value ) ) ) {
				return '';
			}

			$meta_alias = $this->get_meta_alias();

			return $wpdb->prepare(
				"JOIN {$wpdb->postmeta} AS $meta_alias ON ( $meta_alias.post_id = {$wpdb->posts}.ID AND $meta_alias.meta_key = %s )", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$this->meta_key_name
			);
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
			global $wpdb;

			if ( empty( $this->meta_key_name ) || ( empty( $this->meta_value_index ) && empty( $filter_value ) ) ) {
				return '';
			}

			$meta_alias = $this->get_meta_alias();

			// simple meta value.
			if ( '' === $this->meta_value_index ) {
				// if the filter value is an array, then we need to build a query that will match all the values.
				if ( is_array( $filter_value ) ) {
					$filter_value = array_map( 'sanitize_text_field', $filter_value );
					$filter_value = "'" . implode( "','", $filter_value ) . "'";
					return "$meta_alias.meta_value IN ($filter_value)";
				}

				// simple case.
				return $wpdb->prepare(
					"$meta_alias.meta_value LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					'%' . $wpdb->esc_like( $filter_value ) . '%'
				);
			}

			// meta value is an array. Search in the index.
			if ( '' !== $filter_value ) {
				// regex explanation:
				// example of array meta_value: 'lessons_lesson_video_enabled";s:2:"on";'.
				// so, we have:
				// meta_value_index followed by '";' + a character that represents the data type + ':'. Ex: 'lessons_course";i:1877;'
				// after the ':' we can have optionals chars, expect ';' followed by optional ':' and also optional '"'.
				// after that, we have the filter value followed by optional '"' and a ';'.
				$regex = $this->meta_value_index . '";.:[^;]*:?"?' . $wpdb->esc_like( $filter_value ) . '"?;';
			} else {
				// regex explanation:
				// example of array meta_value: 'lessons_lesson_video_enabled";s:0:"";'.
				// so, we have:
				// very similar to the previous regex, but the '"' in the end is not optional.
				$regex = $this->meta_value_index . '";.:[^;]*:?"";';
			}

			return $wpdb->prepare( "$meta_alias.meta_value RLIKE %s", $regex ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}
}
