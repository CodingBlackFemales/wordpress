<?php
/**
 * LearnDash Admin Filter Post ID.
 *
 * @since 4.2.0
 *
 * @package LearnDash\Filters
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'Learndash_Admin_Filter_Post' ) &&
	! class_exists( 'Learndash_Admin_Filter_Post_ID' )
) {
	/**
	 * Filters by post ID.
	 *
	 * @since 4.2.0
	 */
	class Learndash_Admin_Filter_Post_ID extends Learndash_Admin_Filter_Post {
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
		 * @param string $label Label.
		 * @param array  $ajax_query_data Ajax query data for Select 2.
		 */
		public function __construct( string $label, array $ajax_query_data ) {
			$this->ajax_query_data = $ajax_query_data;

			parent::__construct( 'ID', $label );
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
