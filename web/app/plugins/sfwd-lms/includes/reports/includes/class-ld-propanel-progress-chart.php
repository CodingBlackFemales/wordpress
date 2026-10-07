<?php
/**
 * LearnDash ProPanel Activity
 *
 * @since 4.17.0
 *
 * @package LearnDash
 */

use LearnDash\Core\Utilities\Cast;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LearnDash_ProPanel_Progress_Chart' ) ) {
	#[AllowDynamicProperties]
	class LearnDash_ProPanel_Progress_Chart extends LearnDash_ProPanel_Widget {
		/**
		 * @var string
		 */
		protected $name;

		/**
		 * @var string
		 */
		protected $label;

		/**
		 * Chart info.
		 *
		 * @since 4.17.0
		 *
		 * @var array<string, array<string, array<int|string, array<string, mixed>>>>
		 */
		private $chart_info;

		/**
		 * Previous percentage breakdown. Default is 0.
		 * It's necessary to process in filter handlers.
		 *
		 * @since 4.20.5
		 *
		 * @var int
		 */
		private int $previous_percentage_breakdown = 0;

		/**
		 * Previous percentage breakdown. Default is 0.
		 * It's necessary to process in filter handlers.
		 *
		 * @since 4.20.5
		 *
		 * @var int
		 */
		private int $current_percentage_breakdown = 0;

		/**
		 * LearnDash_ProPanel_Progress_Chart constructor.
		 */
		public function __construct() {
			$this->name  = 'progress-chart';
			$this->label = esc_html__( 'LearnDash Progress Chart', 'learndash' );

			parent::__construct();
			add_filter( 'learndash_propanel_template_ajax', array( $this, 'progress_chart_template' ), 10, 2 );
			add_action( 'wp_ajax_learndash_propanel_get_progress_charts_data', array( $this, 'get_progress_course_data_for_chart' ), 10, 2 );

			$this->chart_info['all_progress']          = array();
			$this->chart_info['all_progress']['query'] = array(
				'not_started' => array(
					// translators: Course status - Not Started
					'label'                => esc_html_x( 'Not Started', 'Course status - Not Started', 'learndash' ),
					'backgroundColor'      => '#2D97C5',
					'hoverBackgroundColor' => '#2D97C5',
					'data'                 => 0,
				),
				'in_progress' => array(
					// translators: Course status - In Progress
					'label'                => esc_html_x( 'In Progress', 'Course status - In Progress', 'learndash' ),
					'backgroundColor'      => '#5BAED2',
					'hoverBackgroundColor' => '#5BAED2',
					'data'                 => 0,
				),
				'completed'   => array(
					// translators: Course status - Completed
					'label'                => esc_html_x( 'Completed', 'Course status - Completed', 'learndash' ),
					'backgroundColor'      => '#8AC5DF',
					'hoverBackgroundColor' => '#8AC5DF',
					'data'                 => 0,
				),
			);

			$this->chart_info['all_progress']['options'] = array(
				'tooltips' => array(
					'backgroundColor'   => '#3B3E44',
					'titleMarginBottom' => 15,
					'titleFontSize'     => 18,
					'cornerRadius'      => 4,
					'bodyFontSize'      => 14,
					'xPadding'          => 10,
					'yPadding'          => 15,
					'bodySpacing'       => 10,
					'fontFamily'        => "'Open Sans',sans-serif",
				),
				'legend'   => array(
					'display' => true,
					'labels'  => array(
						'boxWidth'   => 14,
						'fontFamily' => "'Open Sans',sans-serif",
					),
				),
			);

			$this->chart_info['all_percentages'] = array();

			$this->chart_info['all_percentages']['query'] = array(
				'20'  => array(
					'label'                => __( '< 20%', 'learndash' ),
					'backgroundColor'      => '#2D97C5',
					'hoverBackgroundColor' => '#2D97C5',
					'data'                 => 0,
				),
				'40'  => array(
					'label'                => __( '< 40%', 'learndash' ),
					'backgroundColor'      => '#5BAED2',
					'hoverBackgroundColor' => '#5BAED2',
					'data'                 => 0,
				),
				'60'  => array(
					'label'                => __( '< 60%', 'learndash' ),
					'backgroundColor'      => '#8AC5DF',
					'hoverBackgroundColor' => '#8AC5DF',
					'data'                 => 0,
				),
				'80'  => array(
					'label'                => __( '< 80%', 'learndash' ),
					'backgroundColor'      => '#B9DCEB',
					'hoverBackgroundColor' => '#B9DCEB',
					'data'                 => 0,
				),
				'100' => array(
					'label'                => __( '< 100%', 'learndash' ),
					'backgroundColor'      => '#E7F3F8',
					'hoverBackgroundColor' => '#E7F3F8',
					'data'                 => 0,
				),
			);

			$this->chart_info['all_percentages']['options'] = array(
				'tooltips' => array(
					'backgroundColor'   => '#3B3E44',
					'titleMarginBottom' => 15,
					'titleFontSize'     => 18,
					'cornerRadius'      => 4,
					'bodyFontSize'      => 14,
					'xPadding'          => 10,
					'yPadding'          => 15,
					'bodySpacing'       => 10,
					'fontFamily'        => "'Open Sans',sans-serif",
				),
				'legend'   => array(
					'display' => true,
					'labels'  => array(
						'boxWidth'   => 14,
						'fontFamily' => "'Open Sans',sans-serif",
					),
				),
			);
		}

		function initial_template() {
			?>
			<div class="ld-propanel-widget side-by-side ld-propanel-widget-<?php echo $this->name; ?> <?php echo ld_propanel_get_widget_screen_type_class( $this->name ); ?>" data-ld-widget-type="<?php echo $this->name; ?>"></div>
			<?php
		}


		public function progress_chart_template( $output, $template ) {
			if ( 'progress-chart' == $template ) {
				ob_start();
				include ld_propanel_get_template( 'ld-propanel-reporting-choose-filter.php' );
				$output = ob_get_clean();
			}

			if ( 'progress-chart-data' == $template ) {
				ob_start();
				include ld_propanel_get_template( 'ld-propanel-progress-chart.php' );
				$output = ob_get_clean();
			}

			return $output;
		}

		public function get_progress_course_data_for_chart() {
			check_ajax_referer( 'ld-propanel', 'nonce' );

			if (
				! learndash_is_admin_user()
				&& ! learndash_is_group_leader_user()
				&& ! current_user_can( 'propanel_widgets' ) ) {
				wp_send_json_error( array( 'message' => 'Insufficient permissions.' ), 403 );
			}

			$post_data = ld_propanel_load_post_data();

			$activity_query_args = array(
				'post_types'        => 'sfwd-courses',
				'activity_types'    => 'course',
				'activity_status'   => '',
				'orderby_order'     => 'users.display_name, posts.post_title',
				'date_format'       => 'F j, Y H:i:s',
				'time_start'        => '',
				'time_end'          => '',
				'include_meta'      => false, // Since v4.20.5.
				'return_count_only' => true, // Since v4.20.5.
			);
			$activity_query_args = ld_propanel_load_activity_query_args( $activity_query_args, $post_data );

			// Added in v2.1.3 we remove the pager logic from the chart queries.
			$activity_query_args['paged']    = 1;
			$activity_query_args['per_page'] = 0;

			$activity_query_args = apply_filters( 'ld_propanel_reporting_activity_args', $activity_query_args, $post_data );

			$activity_query_args = ld_propanel_adjust_admin_users( $activity_query_args );
			$activity_query_args = ld_propanel_convert_fewer_users( $activity_query_args );

			$response = $this->get_status_breakdown( $activity_query_args );

			wp_send_json_success( $response );

			die();
		}

		function get_status_breakdown( $activity_query_args ) {
			// Let the outside world change elements as needed BEFORE we run the queries.
			$this->chart_info = apply_filters( 'ld_propanel_chart_info_query', $this->chart_info );

			if (
				! empty( $activity_query_args )
				&& ! empty( $this->chart_info['all_progress']['query'] )
			) {
				foreach ( $this->chart_info['all_progress']['query'] as $chart_key => $chart_data ) {
					$activity_status_from_chart_key = mb_strtoupper( $chart_key );

					if ( ! in_array( $activity_status_from_chart_key, [ 'NOT_STARTED', 'IN_PROGRESS', 'COMPLETED' ] ) ) {
						continue;
					}

					$activity_query_args = array_merge(
						$activity_query_args,
						[
							'activity_status'   => $activity_status_from_chart_key,
						]
					);

					// Populate the distribution data.
					$this->chart_info['all_progress']['query'][ $chart_key ]['data'] = learndash_reports_get_activity( $activity_query_args );

					// Populate the breakdown data (we split the in_progress data into 5 groups).
					if (
						$activity_status_from_chart_key == 'IN_PROGRESS'
						&& $this->chart_info['all_progress']['query'][ $chart_key ]['data'] > 0 // We don't need to run the query if there are no in progress items.
						&& ! empty( $this->chart_info['all_percentages']['query'] ) // The breakdown percentages are set.
					) {
						$breakdowns = array_keys( $this->chart_info['all_percentages']['query'] );

						foreach ( $breakdowns as $breakdown_index => $current_percentage_breakdown ) {
							$previous_percentage_breakdown = $breakdown_index > 0
								? $breakdowns[ $breakdown_index - 1 ]
								: 0;

							$this->previous_percentage_breakdown = Cast::to_int( $previous_percentage_breakdown );
							$this->current_percentage_breakdown  = Cast::to_int( $current_percentage_breakdown );

							add_filter( 'learndash_user_activity_query_joins', [ $this, 'set_join_for_distribution_chart' ] );
							add_filter( 'learndash_user_activity_query_where', [ $this, 'set_where_condition_for_distribution_chart' ] );

							$this->chart_info['all_percentages']['query'][ $current_percentage_breakdown ]['data'] = learndash_reports_get_activity( $activity_query_args );

							remove_filter( 'learndash_user_activity_query_joins', [ $this, 'set_join_for_distribution_chart' ] );
							remove_filter( 'learndash_user_activity_query_where', [ $this, 'set_where_condition_for_distribution_chart' ] );
						}
					}
				}
			}

			// Map the chart data to the correct format for the chart.

			$this->chart_info = apply_filters( 'ld_propanel_chart_info_results', $this->chart_info );

			if ( ! empty( $this->chart_info['all_progress'] ) ) {
				// We want to remove any empty items first.
				foreach ( $this->chart_info['all_progress']['query'] as $key => $data ) {
					if ( empty( $data['data'] ) ) {
						unset( $this->chart_info['all_progress']['query'][ $key ] );
					}
				}

				$this->chart_info['all_progress']['data']             = array();
				$this->chart_info['all_progress']['data']['datasets'] = array();

				// Now we need to reorganize the array into what Chart.js needs.
				if ( ! empty( $this->chart_info['all_progress']['query'] ) ) {
					$this->chart_info['all_progress']['data']['labels'] = wp_list_pluck( $this->chart_info['all_progress']['query'], 'label' );
					if ( ( ! empty( $this->chart_info['all_progress']['data']['labels'] ) ) && ( is_array( $this->chart_info['all_progress']['data']['labels'] ) ) ) {
						$this->chart_info['all_progress']['data']['labels'] = array_values( $this->chart_info['all_progress']['data']['labels'] );
					}

					$chart_data         = array();
					$chart_data['data'] = wp_list_pluck( $this->chart_info['all_progress']['query'], 'data' );
					if ( ( ! empty( $chart_data['data'] ) ) && ( is_array( $chart_data['data'] ) ) ) {
						$chart_data['data'] = array_values( $chart_data['data'] );
					}

					$chart_data['backgroundColor'] = wp_list_pluck( $this->chart_info['all_progress']['query'], 'backgroundColor' );
					if ( ( ! empty( $chart_data['backgroundColor'] ) ) && ( is_array( $chart_data['backgroundColor'] ) ) ) {
						$chart_data['backgroundColor'] = array_values( $chart_data['backgroundColor'] );
					}

					$chart_data['hoverBackgroundColor'] = wp_list_pluck( $this->chart_info['all_progress']['query'], 'hoverBackgroundColor' );
					if ( ( ! empty( $chart_data['hoverBackgroundColor'] ) ) && ( is_array( $chart_data['hoverBackgroundColor'] ) ) ) {
						$chart_data['hoverBackgroundColor'] = array_values( $chart_data['hoverBackgroundColor'] );
					}

					if ( ! empty( $chart_data ) ) {
						$this->chart_info['all_progress']['data']['datasets'][] = $chart_data;
					}
				}

				unset( $this->chart_info['all_progress']['query'] );
			}

			if ( ! empty( $this->chart_info['all_percentages'] ) ) {
				// First we want to remove any empty items
				foreach ( $this->chart_info['all_percentages']['query'] as $key => $data ) {
					if ( empty( $data['data'] ) ) {
						unset( $this->chart_info['all_percentages']['query'][ $key ] );
					}
				}

				$this->chart_info['all_percentages']['data']             = array();
				$this->chart_info['all_percentages']['data']['datasets'] = array();

				// Now we need to reorganize the array into what Chart.js needs.
				if ( ! empty( $this->chart_info['all_percentages']['query'] ) ) {
					$chart_data = array();

					$this->chart_info['all_percentages']['data']['labels'] = wp_list_pluck( $this->chart_info['all_percentages']['query'], 'label' );
					if ( ( ! empty( $this->chart_info['all_percentages']['data']['labels'] ) ) && ( is_array( $this->chart_info['all_percentages']['data']['labels'] ) ) ) {
						$this->chart_info['all_percentages']['data']['labels'] = array_values( $this->chart_info['all_percentages']['data']['labels'] );
					}

					$chart_data['data'] = wp_list_pluck( $this->chart_info['all_percentages']['query'], 'data' );
					if ( ( ! empty( $chart_data['data'] ) ) && ( is_array( $chart_data['data'] ) ) ) {
						$chart_data['data'] = array_values( $chart_data['data'] );
					}

					$chart_data['backgroundColor'] = wp_list_pluck( $this->chart_info['all_percentages']['query'], 'backgroundColor' );
					if ( ( ! empty( $chart_data['backgroundColor'] ) ) && ( is_array( $chart_data['backgroundColor'] ) ) ) {
						$chart_data['backgroundColor'] = array_values( $chart_data['backgroundColor'] );
					}

					$chart_data['hoverBackgroundColor'] = wp_list_pluck( $this->chart_info['all_percentages']['query'], 'hoverBackgroundColor' );
					if ( ( ! empty( $chart_data['hoverBackgroundColor'] ) ) && ( is_array( $chart_data['hoverBackgroundColor'] ) ) ) {
						$chart_data['hoverBackgroundColor'] = array_values( $chart_data['hoverBackgroundColor'] );
					}

					if ( ! empty( $chart_data ) ) {
						$this->chart_info['all_percentages']['data']['datasets'][] = $chart_data;
					}
				}

				unset( $this->chart_info['all_percentages']['query'] );
			}

			return $this->chart_info;
		}

		/**
		 * Sets the JOIN for the distribution chart.
		 *
		 * @since 4.20.5
		 *
		 * @param string $sql_str_joins The SQL JOIN string.
		 *
		 * @return string The modified SQL JOIN string.
		 */
		public function set_join_for_distribution_chart( $sql_str_joins ) {
			global $wpdb;

			$sql_str_joins .= ' LEFT JOIN ' . Cast::to_string( esc_sql( $wpdb->postmeta ) ) . ' AS postmeta ON posts.ID = postmeta.post_id AND postmeta.meta_key = "_ld_course_steps_count" ';
			$sql_str_joins .= ' LEFT JOIN ' . Cast::to_string( esc_sql( LDLMS_DB::get_table_name( 'user_activity_meta' ) ) ) . ' AS ld_user_activity_meta ON ld_user_activity_meta.activity_id = ld_user_activity.activity_id AND ld_user_activity_meta.activity_meta_key = "steps_completed" ';

			return $sql_str_joins;
		}

		/**
		 * Sets the WHERE condition for the distribution chart.
		 *
		 * @since 4.20.5
		 *
		 * @param string $sql_str_where The SQL WHERE string.
		 *
		 * @return string
		 */
		public function set_where_condition_for_distribution_chart( $sql_str_where ) {
			// Higher than or equal to the previous percentage breakdown and less than the current percentage breakdown.
			return $sql_str_where
				. ' AND ld_user_activity_meta.activity_meta_value * 100.0 /postmeta.meta_value >= ' . $this->previous_percentage_breakdown
				. ' AND ld_user_activity_meta.activity_meta_value * 100.0 / postmeta.meta_value < ' . $this->current_percentage_breakdown;
		}

	}
}
