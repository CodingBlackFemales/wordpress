<?php
/**
 * LearnDash Admin Filter Meta Select Ajax.
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
	! class_exists( 'Learndash_Admin_Filter_Meta_Select_Ajax' )
) {
	/**
	 * Filters posts by meta value.
	 *
	 * @since 4.2.0
	 */
	class Learndash_Admin_Filter_Meta_Select_Ajax extends Learndash_Admin_Filter_Meta {
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
		 * @param string $meta_key_name    Meta key name.
		 * @param string $label            Label.
		 * @param array  $ajax_query_data  Ajax query data for Select 2.
		 * @param string $meta_value_index Meta value index, if the meta value is an array.
		 */
		public function __construct( string $meta_key_name, string $label, array $ajax_query_data, string $meta_value_index = '' ) {
			$this->ajax_query_data = $ajax_query_data;

			parent::__construct( $meta_key_name, $label, $meta_value_index );
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
