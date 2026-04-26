<?php
/**
 * LearnDash ProPanel Shortcodes
 *
 * @since 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LearnDash_ProPanel_Shortcode' ) ) {
	class LearnDash_ProPanel_Shortcode {
		protected static $instance;

		/**
		 * Returns the *Singleton* instance of this class.
		 *
		 * @return LearnDash_ProPanel The *Singleton* instance.
		 */
		public static function get_instance() {
			if ( null === static::$instance ) {
				static::$instance = new static();
			}

			return static::$instance;
		}

		/**
		 * Override class function for 'this'.
		 *
		 * This function handles out Singleton logic in
		 *
		 * @return reference to current instance
		 */
		static function this() {
			return self::$instance;
		}

		/**
		 * LearnDash_ProPanel_Shortcodes constructor.
		 */
		public function __construct() {
			add_shortcode( 'ld_propanel', array( $this, 'do_shortcode' ) );
			add_shortcode( 'ld_reports', array( $this, 'do_shortcode' ) );
		}

		function do_shortcode( $atts = array(), $content = '' ) {
			if (
			( is_user_logged_in() )
			&& ( ( learndash_is_group_leader_user() ) || ( learndash_is_admin_user() ) || ( current_user_can( 'propanel_widgets' ) ) )
			) {
				if ( ( isset( $atts['widget'] ) ) && ( ! empty( $atts['widget'] ) ) ) {
					switch ( $atts['widget'] ) {
						case 'overview':
							$title = __( 'LearnDash Reports Overview', 'learndash' );
							break;

						case 'filtering':
							$title = __( 'LearnDash Report Filters', 'learndash' );
							break;

						case 'reporting':
							$title = __( 'LearnDash Reporting', 'learndash' );
							break;

						case 'activity':
							$title = __( 'LearnDash Activity', 'learndash' );
							break;

						case 'progress_chart':
							$title = __( 'LearnDash Progress Chart', 'learndash' );
							break;

						default:
							$title = '';
							break;
					}

					switch ( $atts['widget'] ) {
						case 'link':
							$shortcode_url = add_query_arg( 'ld_propanel', '1' );
							$shortcode_url = apply_filters( 'ld_propanel_shortcode_url', $shortcode_url );

							$default_atts = array(
								'html_id'    => wp_create_nonce( 'ld-propanel-widget-' . $atts['widget'] . '-' . time() ),
								'html_class' => '',
								'label'      => __( 'Show ProPanel Full Page', 'learndash' ),
							);

							$atts = shortcode_atts( $default_atts, $atts );

							// If $content wasn't passed correctly, check $atts['label'].
							if ( empty( $content ) ) {
								$content = $atts['label'];
							}

							// Ensure the Shortcode renders properly, as it is returning $content.
							$content = sprintf(
								'<a href="%1$s" id="%2$s" class="%3$s">%4$s</a>',
								esc_attr( $shortcode_url ),
								esc_attr( $atts['html_id'] ),
								esc_attr( $atts['html_class'] ),
								$content
							);

							break;

						case 'overview':
						case 'filtering':
							$default_atts = array(
								'widget'     => '',
								'html_id'    => wp_create_nonce( 'ld-propanel-widget-' . $atts['widget'] . '-' . time() ),
								'html_class' => '',
							);

							$atts = shortcode_atts( $default_atts, $atts );

							$widget_key = str_replace( '_', '-', $atts['widget'] );

							// force this these settings
							$atts['template'] = $atts['widget'];

							// At this point we are a go to display something. so we load of the needed JS/CSS
							$ld_propanel = LearnDash_ProPanel::get_instance();
							$ld_propanel->scripts( true );

							$content .= '<h2>' . esc_html( $title ) . '</h2>';

							$content .= '<div id="ld-propanel-widget-' . $widget_key . '-' . esc_html( $atts['html_id'] ) . '" data-ld-widget-type="' . $widget_key . '" class="ld-propanel-widget ld-propanel-widget-' . $widget_key . ' ' . ld_propanel_get_widget_screen_type_class( $widget_key );

							if ( ! empty( $atts['html_class'] ) ) {
								$content .= ' ' . esc_html( $atts['html_class'] );
							}

							$content .= '"';
							// $content .= ' data-filters="'.  htmlspecialchars( json_encode( $atts, JSON_FORCE_OBJECT ) ) .'"';

							$content .= '></div>';
							break;

						case 'reporting':
						case 'activity':
						case 'progress_chart':
							$default_atts = array(
								'widget'         => '',
								'html_id'        => wp_create_nonce( 'ld-propanel-widget-' . $atts['widget'] . '-' . time() ),
								'html_class'     => '',
								'per_page'       => get_option( 'posts_per_page' ),
								'filter_type'    => '',     // (optional) Should be single 'user', 'course' or 'group'
								'filter_id'      => '',     // (optional) The ID of the type to filter on. Will be a course ID or User ID.
																					// The value 'CURRENT_ID' can be used for current User or current Course (if on a course post)
								'filter_groups'  => '',
								'filter_courses' => '',
								'filter_users'   => '',
								'filter_status'  => '',
								'activity_types' => '',
								'orderby_order'  => 'ld_user_activity.activity_updated DESC',
								'export_buttons' => 1,
								'nav_top'        => 1,
								'display_chart'  => '',
							);

							$atts = shortcode_atts( $default_atts, $atts );

							$widget_key = str_replace( '_', '-', $atts['widget'] );

							// force this these settings
							$atts['template'] = $atts['widget'];

							if ( ! empty( $atts['filter_groups'] ) ) {
								$atts['filter_groups'] = explode( ',', $atts['filter_groups'] );
								$atts['filter_groups'] = array_map( 'trim', $atts['filter_groups'] );
								$atts['groups']        = $atts['filter_groups'];
								unset( $atts['filter_groups'] );
							} else {
								$atts['groups'] = array();
							}

							if ( ! empty( $atts['filter_courses'] ) ) {
								$atts['filter_courses'] = explode( ',', $atts['filter_courses'] );
								$atts['filter_courses'] = array_map( 'trim', $atts['filter_courses'] );
								$atts['courses']        = $atts['filter_courses'];
								unset( $atts['filter_courses'] );
							} else {
								$atts['courses'] = array();
							}

							if ( ! empty( $atts['filter_users'] ) ) {
								$atts['filter_users'] = explode( ',', $atts['filter_users'] );
								$atts['filter_users'] = array_map( 'trim', $atts['filter_users'] );
								$atts['users']        = $atts['filter_users'];
								unset( $atts['filter_users'] );
							} else {
								$atts['users'] = array();
							}

							if ( ( ! empty( $atts['filter_type'] ) ) && ( ! empty( $atts['filter_id'] ) ) ) {
								switch ( $atts['filter_type'] ) {
									case 'user':
										$atts['users'][] = $atts['filter_id'];
										break;

									case 'course':
										$atts['courses'][] = $atts['filter_id'];
										break;

									case 'group':
										$atts['groups'][] = $atts['filter_id'];
										break;

									default:
										break;
								}
							} else {
								if ( ! empty( $atts['groups'] ) ) {
									$atts['filter_type'] = 'group';
									$atts['filter_id']   = $atts['groups'][0];

									// For now we only send a single value for filtering
									$atts['groups'] = $atts['groups'][0];
								} else {
									unset( $atts['groups'] );
								}

								if ( ! empty( $atts['courses'] ) ) {
									$atts['filter_type'] = 'course';
									$atts['filter_id']   = $atts['courses'][0];

									// For now we only send a single value for filtering
									$atts['courses'] = $atts['courses'][0];
								} else {
									unset( $atts['courses'] );
								}

								if ( ! empty( $atts['users'] ) ) {
									$atts['filter_type'] = 'user';
									$atts['filter_id']   = $atts['users'][0];

									// For now we only send a single value for filtering
									$atts['users'] = $atts['users'][0];
								} else {
									unset( $atts['users'] );
								}
							}

							if ( ! empty( $atts['filter_status'] ) ) {
								$atts['filter_status'] = explode( ',', $atts['filter_status'] );
								$atts['filter_status'] = array_map( 'trim', $atts['filter_status'] );
								$atts['courseStatus']  = $atts['filter_status'];
								unset( $atts['filter_status'] );
							} else {
								$atts['courseStatus'] = array();
							}

							if ( ! empty( $atts['activity_types'] ) ) {
								$atts['activity_types'] = explode( ',', $atts['activity_types'] );
								$atts['activity_types'] = array_map( 'trim', $atts['activity_types'] );
							}

							if ( ! empty( $atts['per_page'] ) ) {
								$atts['reporting_pager'] = array(
									'current_page' => 1,
									'per_page'     => intval( $atts['per_page'] ),
								);
								unset( $atts['per_page'] );
							}

							foreach ( $atts as $key => $val ) {
								if ( empty( $val ) ) {
									unset( $atts['key'] );
								}
							}

							// At this point we are a go to display something. so we load of the needed JS/CSS
							$ld_propanel = LearnDash_ProPanel::get_instance();
							$ld_propanel->scripts( true );

							$widget_classes_str = '';
							$widget_classes     = array(
								'ld-propanel-widget',
								'ld-propanel-widget-' . $widget_key,
								ld_propanel_get_widget_screen_type_class( $widget_key ),
							);

							if ( ! empty( $atts['html_class'] ) ) {
								$widget_classes[] = $atts['html_class'];
							}

							if ( $atts['widget'] == 'progress_chart' ) {
								if ( ( isset( $atts['display_chart'] ) ) && ( ! empty( $atts['display_chart'] ) ) ) {
									$widget_classes[] = $atts['display_chart'];
								}
							}

							$widget_classes = apply_filters( 'ld_propanel_widget_classes_array', $widget_classes );

							if ( ( ! empty( $widget_classes ) ) && ( is_array( $widget_classes ) ) ) {
								foreach ( $widget_classes as $widget_class ) {
									$widget_class = esc_html( $widget_class );
									if ( ! empty( $widget_class ) ) {
										if ( ! empty( $widget_classes_str ) ) {
											$widget_classes_str .= ' ';
										}

										$widget_classes_str .= esc_html( $widget_class );
									}
								}
							}

							$content .= '<h2>' . esc_html( $title ) . '</h2>';

							$content .= '<div id="ld-propanel-widget-' . $widget_key . '-' . esc_html( $atts['html_id'] ) . '" data-ld-widget-type="' . $widget_key . '" ';

							$widget_classes_str = apply_filters( 'ld_propanel_widget_classes_string', $widget_classes_str );
							if ( ! empty( $widget_classes_str ) ) {
								$content .= ' class="' . $widget_classes_str . '" ';
							}

							if ( ( $atts['widget'] == 'reporting' ) || ( $atts['widget'] == 'progress_chart' ) ) {
								if ( ( empty( $atts['filter_type'] ) ) || ( empty( $atts['filter_id'] ) ) ) {
									$atts = array();
								}
							}

							if ( ! empty( $atts ) ) {
								$atts['filters']['type'] = $atts['filter_type'];
								unset( $atts['filter_type'] );

								$atts['filters']['id'] = $atts['filter_id'];
								unset( $atts['filter_id'] );

								$atts['filters']['users'] = $atts['filter_users'];
								unset( $atts['filter_users'] );

								if ( isset( $atts['groups'] ) ) {
									$atts['filters']['groups'] = $atts
									['groups'];
									unset( $atts['groups'] );
								}

								if ( isset( $atts['courses'] ) ) {
									$atts['filters']['courses'] = $atts
									['courses'];
									unset( $atts['courses'] );
								}

								if ( isset( $atts['courseStatus'] ) ) {
									$atts['filters']['courseStatus'] = $atts
									['courseStatus'];
									unset( $atts['courseStatus'] );
								}

								if ( isset( $atts['reporting_pager'] ) ) {
									$atts['filters']['reporting_pager'] = $atts
									['reporting_pager'];
									unset( $atts['reporting_pager'] );
								}

								$content .= ' data-filters="' . htmlspecialchars( json_encode( $atts, JSON_FORCE_OBJECT ) ) . '"';
							}

							$content .= '></div>';

						default:
							break;
					}
				}
			}

			return $content;
		}
	}
}
