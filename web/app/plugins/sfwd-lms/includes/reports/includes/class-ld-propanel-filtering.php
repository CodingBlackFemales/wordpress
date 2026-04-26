<?php
/**
 * LearnDash ProPanel Filtering
 *
 * @since 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;

if (
	! class_exists( 'LearnDash_ProPanel_Filtering' )
	&& class_exists( 'LearnDash_ProPanel_Widget' )
) {
	class LearnDash_ProPanel_Filtering extends LearnDash_ProPanel_Widget {
		/**
		 * @var string
		 */
		protected $name;

		/**
		 * @var string
		 */
		protected $label;

		private $mail_error;
		private $is_debug;
		private $debug_message = '';

		/**
		 * LearnDash_ProPanel_Reporting constructor.
		 */
		public function __construct() {
			$this->name  = 'filtering';
			$this->label = esc_html__( 'LearnDash Report Filters', 'learndash' );

			parent::__construct();

			$this->filters_init();
			add_filter( 'ld_propanel_reporting_filters_register', array( $this, 'filter_register' ), 20 );
			add_action( 'wp_ajax_learndash_propanel_filters_search', array( $this, 'filters_search' ) );

			add_filter( 'learndash_propanel_template_ajax', array( $this, 'filtering_template' ), 10, 2 );
			// add_action( 'wp_ajax_learndash_propanel_reporting_get_result_rows', array( $this, 'get_result_rows' ) );

			add_action( 'wp_ajax_learndash_propanel_email_users', array( $this, 'ajax_email_users' ) );
		}

		function initial_template() {
			?>
			<div class="ld-propanel-widget ld-propanel-widget-<?php echo $this->name; ?> <?php echo ld_propanel_get_widget_screen_type_class( $this->name ); ?>" data-ld-widget-type="<?php echo $this->name; ?>"></div>
			<?php
		}

		function filters_init() {
			do_action( 'learndash_propanel_filtering_init' );
			$this->registered_filters = apply_filters( 'ld_propanel_filtering_register_filters', $this->registered_filters );
		}

		public function filter_register( $registered_filters = array() ) {
			if ( ! array_key_exists( $this->filter_key, $registered_filters ) ) {
				$registered_filters[ $this->filter_key ] = array(
					'class'      => get_class( $this ),
					'instance'   => $this,
					'filter_key' => $this->filter_key,
				);
			}
			return $registered_filters;
		}

		function get_filters() {
			return $this->registered_filters;
		}

		/**
		 * Filter widget display markup (overridden by reporting filter subclasses).
		 *
		 * @since 5.0.3.1
		 *
		 * @return string
		 */
		public function filter_display() {
			return '';
		}

		/**
		 * AJAX search handler (overridden by reporting filter subclasses).
		 *
		 * @since 5.0.3.1
		 *
		 * @return array<string, mixed>
		 */
		public function filter_search() {
			return array();
		}

		function show_filters() {
			if ( ! empty( $this->registered_filters ) ) {
				foreach ( $this->registered_filters as $filter_key => $filter_set ) {
					if (
						! is_array( $filter_set )
						|| ! isset( $filter_set['instance'] )
						|| ! $filter_set['instance'] instanceof LearnDash_ProPanel_Filtering
					) {
						continue;
					}
					$filter_widget = $filter_set['instance'];
					?>
					<div class="select2-selection filter-selection filter-section-<?php echo esc_attr( (string) $filter_key ); ?>" data-filter-key="<?php echo esc_attr( (string) $filter_key ); ?>">
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Intentional HTML from filter widget.
						echo $filter_widget->filter_display();
						?>
					</div>
					<?php
				}
			}
		}

		/**
		 *
		 */
		function filtering_template( $output, $template ) {
			switch ( $template ) {
				case 'filtering':
					ob_start();
					include ld_propanel_get_template( 'ld-propanel-filtering.php' );
					// include ld_propanel_get_template( 'ld-propanel-reporting-choose-filter.php' );
					$output = ob_get_clean();
					break;
			}

			return $output;
		}


		function filters_search() {
			check_ajax_referer( 'ld-propanel', 'nonce' );

			if (
				! learndash_is_admin_user()
				&& ! learndash_is_group_leader_user()
				&& ! current_user_can( 'propanel_widgets' ) ) {
				wp_send_json_error( array( 'message' => 'Insufficient permissions.' ), 403 );
			}

			$filter_results = array();

			$this->post_data           = ld_propanel_load_post_data();
			$this->activity_query_args = ld_propanel_load_activity_query_args( array(), $this->post_data );
			$this->activity_query_args = apply_filters( 'ld_propanel_reporting_activity_args', $this->activity_query_args, $this->post_data );

			if ( isset( $_GET['filter_key'] ) ) {
				$filter_key = esc_attr( $_GET['filter_key'] );
				if ( array_key_exists( $filter_key, $this->registered_filters ) ) {
					$filter_entry = $this->registered_filters[ $filter_key ];
					if (
						is_array( $filter_entry )
						&& isset( $filter_entry['instance'] )
						&& $filter_entry['instance'] instanceof LearnDash_ProPanel_Filtering
					) {
						$filter_widget                      = $filter_entry['instance'];
						$filter_widget->post_data           = $this->post_data;
						$filter_widget->activity_query_args = $this->activity_query_args;
						$filter_results                     = $filter_widget->filter_search();
					}
				}
			}

			wp_send_json_success( $filter_results );
			die();
		}

		/**
		 *
		 */
		// function full_reporting_page_output() {
		// ob_start();
		// $container_type = 'full';
		// include ld_propanel_get_template( 'ld-propanel-full-reporting.php' );
		// echo ob_get_clean();
		// }

		public function filter_activity_args( $activity_args = array(), $post_data = array() ) {
			return $activity_args;
		}


		/**
		 *
		 */
		/*
		function get_result_rows() {
			check_ajax_referer( 'ld-propanel', 'nonce' );

			$this->post_data = ld_propanel_load_post_data();
			$this->activity_query_args = ld_propanel_load_activity_query_args( array(), $this->post_data );

			if ( 'course' == $this->post_data['filters']['type'] ) {
				if ( array_key_exists( 'courses', $this->registered_filters ) ) {
					$this->registered_filters['courses']['instance']->post_data = $this->post_data;
					$this->registered_filters['courses']['instance']->activity_query_args = $this->activity_query_args;

					$response = $this->registered_filters['courses']['instance']->filter_result_rows( $this->post_data['filters']['id'] );
				}

			} else if ( 'user' == $this->post_data['filters']['type'] ) {
				if ( array_key_exists( 'users', $this->registered_filters ) ) {
					$this->registered_filters['users']['instance']->post_data = $this->post_data;
					$this->registered_filters['users']['instance']->activity_query_args = $this->activity_query_args;

					$response = $this->registered_filters['users']['instance']->filter_result_rows( $this->post_data['filters']['id'] );
				}

			} else if ( 'group' == $this->post_data['filters']['type'] ) {
				if ( array_key_exists( 'groups', $this->registered_filters ) ) {
					$this->registered_filters['groups']['instance']->post_data = $this->post_data;
					$this->registered_filters['groups']['instance']->activity_query_args = $this->activity_query_args;

					$response = $this->registered_filters['groups']['instance']->filter_result_rows( $this->post_data['filters']['id'] );
				}
			}

			wp_send_json( $response );
			die();
		}
		*/

		/**
		 * @param array $user_ids
		 * @param $subject
		 * @param $message
		 *
		 * @return bool
		 */
		function email_users( $user_ids = array(), $subject = '', $message = '' ) {
			global $wpdb;

			if ( ! empty( $user_ids ) ) {
				$offset                 = 0;
				$email_users_batch_size = apply_filters( 'ld_propanel_email_users_batch_size', 100 );
				while ( true ) {
					$user_ids_part = array_slice( $user_ids, $offset, $email_users_batch_size );
					if ( empty( $user_ids_part ) ) {
						break;
					} else {
						$mail_args = array(
							'to'          => wp_get_current_user()->user_email,
							'subject'     => $subject,
							'message'     => wpautop( $message ),
							'attachments' => '',
							'headers'     => array(
								'content-type: text/html',
								'From: ' . wp_get_current_user()->user_email,
								'Reply-to: ' . wp_get_current_user()->user_email,
							),
						);

						$mail_ret = false;

						$email_sql_str   = $wpdb->prepare(
							'SELECT user_email FROM ' . $wpdb->users . ' WHERE ID IN (' . implode( ', ', array_fill( 0, count( $user_ids_part ), '%d' ) ) . ')',
							...array_values( $user_ids_part )
						);
						$email_addresses = $wpdb->get_col( $email_sql_str );

						if ( $email_addresses ) {
							$mail_args['headers'][] = 'Bcc: ' . implode( ',', $email_addresses );

							$mail_args = apply_filters( 'ld_propanel_email_users_args', $mail_args );
							if ( ! empty( $mail_args ) ) {
								do_action( 'ld_propanel_email_users_before', $mail_args );

								add_action( 'wp_mail_failed', array( $this, 'ajax_mail_failed' ) );
								$mail_ret = wp_mail( $mail_args['to'], $mail_args['subject'], $mail_args['message'], $mail_args['headers'], $mail_args['attachments'] );

								if ( ! empty( $this->is_debug ) ) {
									$this->debug_message .= 'mail_ret: ' . $mail_ret . "\r\n";
									$this->debug_message .= 'mail_args<pre>' . print_r( $mail_args, true ) . "</pre>\r\n";
								}

								remove_action( 'wp_mail_failed', array( $this, 'ajax_mail_failed' ) );

								do_action( 'ld_propanel_email_users_after', $mail_args, $mail_ret );

								if ( ! $mail_ret ) {
									break;
								}
							} else {
								break;
							}
						} else {
							break;
						}

						$offset += $email_users_batch_size;
					}
				}
			}

			return $mail_ret;
		}

		/**
		 *
		 */
		function ajax_email_users() {
			check_ajax_referer( 'ld-propanel', 'nonce' );

			if (
				! learndash_is_admin_user()
				&& ! learndash_is_group_leader_user()
				&& ! current_user_can( 'propanel_widgets' ) ) {
				wp_send_json_error( array( 'message' => 'Insufficient permissions.' ), 403 );
			}

			$user_ids       = isset( $_POST['user_ids'] ) ? $_POST['user_ids'] : null;
			$filter         = isset( $_POST['filter'] ) ? $_POST['filter'] : null;
			$subject        = isset( $_POST['subject'] ) ? sanitize_text_field( stripslashes( $_POST['subject'] ) ) : '';
			$message        = isset( $_POST['message'] ) ? wp_kses_post( stripslashes( $_POST['message'] ) ) : '';
			$this->is_debug = isset( $_POST['is_debug'] ) ? wp_kses_post( stripslashes( $_POST['is_debug'] ) ) : '';

			$response = array();

			if ( ! empty( $user_ids ) ) {
				$user_ids = array_map( 'intval', explode( ',', $user_ids ) );
			} else {
				$this->post_data           = ld_propanel_load_post_data( array(), $_POST );
				$this->activity_query_args = ld_propanel_load_activity_query_args( array(), $this->post_data );

				$this->activity_query_args = ld_propanel_adjust_admin_users( $this->activity_query_args );
				// $this->activity_query_args = ld_propanel_convert_fewer_users( $this->activity_query_args );

				// if ( ( isset( $this->activity_query_args['user_ids'] ) ) && ( !empty( $this->activity_query_args['user_ids'] ) ) ) {
				// $user_ids = $this->activity_query_args['user_ids'];
				// }

				$this->activity_query_args['per_page'] = 100;
				$this->activity_query_args['paged']    = 1;
				$user_ids                              = array();

				while ( true ) {
					$activities = learndash_reports_get_activity( $this->activity_query_args );
					if (
						isset( $activities['results'] )
						&& ! empty( $activities['results'] )
					) {
						$user_ids                            = array_merge( $user_ids, wp_list_pluck( $activities['results'], 'user_id' ) );
						$this->activity_query_args['paged'] += 1;
					} else {
						break;
					}
				}
			}

			if (
				! empty( $user_ids )
				&& ! empty( $subject )
				&& ! empty( $message )
			) {
				$user_ids = array_unique( $user_ids );
				$result   = $this->email_users( $user_ids, $subject, $message, $filter );
				if ( $result ) {
					wp_send_json_success(
						array(
							'message' => sprintf(
								// translators: placeholder: email count.
								esc_html_x( 'Email sent to %d destinations', 'placeholder: email count', 'learndash' ),
								count( $user_ids )
							),
							'debug'   => $this->debug_message,
						)
					);
				} else {
					$error_string = '';
					if ( is_wp_error( $this->mail_error ) ) {
						$error_string = $this->mail_error->get_error_message();
					}
					wp_send_json_error(
						array(
							'message' => sprintf(
								// translators: placeholder: error messages
								esc_html_x(
									'We could not send the email successfully. Please try again or check with your hosting provider.
Error: %s',
									'placeholder: error messages',
									'learndash'
								),
								$error_string
							),
						)
					);
				}
			} else {
				wp_send_json_error( array( 'message' => esc_html__( 'We do not have any email addresses to send your message to.', 'learndash' ) ) );
			}

			die();
		}

		// Capture the wp_mail() failure. Will then be appended to the json message sent back to the browser.
		function ajax_mail_failed( $mail_error ) {
			$this->mail_error = $mail_error;
		}
	}
}

require_once LD_PP_PLUGIN_DIR . 'templates/reporting-filters/groups/ld-propanel-reporting-filter-group.php';
require_once LD_PP_PLUGIN_DIR . 'templates/reporting-filters/courses/ld-propanel-reporting-filter-course.php';
require_once LD_PP_PLUGIN_DIR . 'templates/reporting-filters/users/ld-propanel-reporting-filter-user.php';
require_once LD_PP_PLUGIN_DIR . 'templates/reporting-filters/status/ld-propanel-reporting-filter-status.php';
