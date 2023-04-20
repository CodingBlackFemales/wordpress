<?php
/**
 * LearnDash Admin Filter Shared Steps.
 *
 * @since 4.2.0
 *
 * @package LearnDash\Filters
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'Learndash_Admin_Filter_Meta' ) &&
	! class_exists( 'Learndash_Admin_Filter_Shared_Steps' )
) {
	/**
	 * Filters posts by meta value.
	 *
	 * @since 4.2.0
	 */
	class Learndash_Admin_Filter_Shared_Steps extends Learndash_Admin_Filter_Meta {
		/**
		 * Ajax query data for Select 2.
		 *
		 * @since 4.2.0
		 *
		 * @var array
		 */
		private $ajax_query_data;

		/**
		 * Construct.
		 *
		 * @since 4.2.0
		 *
		 * @param array $ajax_query_data  Ajax query data for Select 2.
		 */
		public function __construct( array $ajax_query_data ) {
			$this->ajax_query_data = $ajax_query_data;

			parent::__construct( 'ld_course_', LearnDash_Custom_Label::get_label( 'course' ) );
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

			if ( empty( $filter_value ) ) {
				return '';
			}

			$meta_alias = $this->get_meta_alias();
			return "JOIN {$wpdb->postmeta} AS $meta_alias ON $meta_alias.post_id = {$wpdb->posts}.ID";
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

			if ( empty( $filter_value ) ) {
				return '';
			}

			$meta_alias = $this->get_meta_alias();

			if ( ! is_array( $filter_value ) ) {
				$filter_value = array( $filter_value );
			}

			$filter_value = array_map(
				function( $value ) {
					return "'" . $this->meta_key_name . sanitize_key( $value ) . "'";
				},
				$filter_value
			);
			$filter_value = implode( ',', $filter_value );
			return "$meta_alias.meta_key IN ($filter_value)";
		}

		/**
		 * Echoes the input HTML.
		 *
		 * @since 4.2.0
		 *
		 * @return void
		 */
		public function display(): void {
			?>
			<div class="sfwd_option_input">
				<select
					name="<?php echo esc_attr( $this->get_parameter_name() ); ?>"
					class="<?php echo esc_attr( $this->get_input_class() ); ?>"
					multiple="multiple"
					autocomplete="off"
					type="select"
					data-close-on-select="false"
					data-ld-select2="1"
					data-select2-query-data="<?php echo esc_attr( htmlspecialchars( wp_json_encode( $this->ajax_query_data, JSON_FORCE_OBJECT ) ) ); ?>"
				></select>
			</div>
			<?php
		}
	}
}
