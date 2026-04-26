<?php
/**
 * LearnDash ProPanel Filtering: Status.
 *
 * @since 4.17.0
 * @version 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;

if (
	! class_exists( 'LearnDash_ProPanel_Reporting_Filter_Status' )
	&& class_exists( 'LearnDash_ProPanel_Filtering' )
) {
	class LearnDash_ProPanel_Reporting_Filter_Status extends LearnDash_ProPanel_Filtering {
		public function __construct() {
			$this->filter_key = 'courseStatus';
			add_filter( 'ld_propanel_filtering_register_filters', array( $this, 'filter_register' ), 30 );
		}

		public function filter_post_args( $post_args_filters = array() ) {
			if (
				isset( $_GET['filters'][ $this->filter_key ] )
				&& ! empty( $_GET['filters'][ $this->filter_key ] )
			) {
				if ( is_string( $_GET['filters'][ $this->filter_key ] ) ) {
					$post_args_filters[ $this->filter_key ][] = esc_attr( $_GET['filters'][ $this->filter_key ] );
				} elseif ( is_array( $_GET['filters'][ $this->filter_key ] ) ) {
					foreach ( $_GET['filters'][ $this->filter_key ] as $idx => $val ) {
						$post_args_filters[ $this->filter_key ][ $idx ] = esc_attr( $val );
					}
				}
			}

			return $post_args_filters;
		}

		/**
		 * Filters display.
		 *
		 * @since 4.17.0
		 *
		 * @return string
		 */
		public function filter_display() {
			// cspell:disable-next-line -- coursestatus is a CSS class name.
			return '<select multiple="multiple" class="filter-coursestatus select2" data-ajax--cache="true" data-allow-clear="true" data-placeholder="' .
			// translators: Course status - All Statuses
			esc_html_x( 'All Statuses', 'Course status - All Statuses', 'learndash' ) . '"></select>';
		}

		/**
		 * Status search.
		 *
		 * @since 4.17.0
		 *
		 * @return array<string, mixed>
		 */
		public function filter_search() {
			$statuses = array(
				array(
					'id'   => 'not-started',
					'text' => esc_html__( 'Not Started', 'learndash' ),
				),
				array(
					'id'   => 'in-progress',
					'text' => esc_html__( 'In Progress', 'learndash' ),
				),
				array(
					'id'   => 'completed',
					'text' => esc_html__( 'Completed', 'learndash' ),
				),
			);

			return array(
				'total' => count( $statuses ),
				'items' => $statuses,
			);      }

		// End of functions
	}
}

add_action(
	'learndash_propanel_filtering_init',
	function () {
		new LearnDash_ProPanel_Reporting_Filter_Status();
	}
);
