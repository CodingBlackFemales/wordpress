<?php
/**
 * LearnDash `User_Status` Widget Class.
 *
 * @since 3.0.0
 * @package LearnDash\Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LearnDash_User_Status_Widget' ) ) && ( class_exists( 'WP_Widget' ) ) ) {
	/**
	 * Class for LearnDash `User_Status` Widget.
	 *
	 * @since 3.0.0
	 * @uses WP_Widget
	 */
	class LearnDash_User_Status_Widget extends WP_Widget {

		/**
		 * Setup Course Info Widget
		 */
		public function __construct() {
			$widget_ops  = array(
				'classname'   => 'widget_lduserstatus',
				'description' => sprintf(
					// translators: placeholder: Courses.
					esc_html_x( 'LearnDash - Registered %s and progress information of users. Visible only to users logged in.', 'placeholders: courses', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'courses' )
				),
			);
			$control_ops = array();
			parent::__construct( 'lduserstatus', __( 'User Status', 'learndash' ), $widget_ops, $control_ops );
		}

		/**
		 * Displays widget
		 *
		 * @since 3.0.0
		 *
		 * @param  array $args     Widget arguments.
		 * @param  array $instance Widget instance.
		 *
		 * @return void
		 */
		public function widget( $args, $instance ) {
			global $learndash_shortcode_used;

			extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

			/** This filter is documented in https://developer.wordpress.org/reference/hooks/widget_title/ */
			$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance );

			if ( empty( $user_id ) ) {
				$current_user = wp_get_current_user();
				if ( empty( $current_user->ID ) ) {
					return;
				}

				$user_id = $current_user->ID;
			}

			if ( empty( $args ) ) {
				$args = array(
					'return' => true,
				);
			} elseif ( ! isset( $args['return'] ) ) {
				$args['return'] = true;
			}

			if ( isset( $instance['registered_num'] ) ) {
				$args['registered_num'] = intval( $instance['registered_num'] );
			}

			if ( isset( $instance['registered_orderby'] ) ) {
				$args['registered_orderby'] = sanitize_text_field( $instance['registered_orderby'] );
			}

			if ( isset( $instance['registered_order'] ) ) {
				$args['registered_order'] = sanitize_text_field( $instance['registered_order'] );
			}

			$course_info = SFWD_LMS::get_course_info( $user_id, $args );

			$user_status = SFWD_LMS::get_template(
				'shortcodes/user-status.php',
				array(
					'course_info'    => $course_info,
					'shortcode_atts' => $args,
					'context'        => 'widget',
				),
				false
			);

			if ( empty( $user_status ) ) {
				return;
			}

			echo $before_widget; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML output before widget

			if ( ! empty( $title ) ) {
				echo $before_title . $title . $after_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML output before and after title
			}

			echo $user_status; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML output user status
			echo $after_widget; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML output after widget

			$learndash_shortcode_used = true;
		}


		/**
		 * Handles widget updates in admin
		 *
		 * @since 2.1.0
		 *
		 * @param  array $new_instance New instance values.
		 * @param  array $old_instance Old instance values.
		 * @return array $instance
		 */
		public function update( $new_instance, $old_instance ) {
			$instance          = $old_instance;
			$instance['title'] = wp_strip_all_tags( $new_instance['title'] );

			$instance['registered_show_thumbnail'] = esc_attr( $new_instance['registered_show_thumbnail'] );
			if ( '' !== $new_instance['registered_num'] ) {
				$instance['registered_num'] = intval( $new_instance['registered_num'] );
			} else {
				$instance['registered_num'] = false;
			}

			$instance['registered_orderby'] = esc_attr( $new_instance['registered_orderby'] );
			$instance['registered_order']   = esc_attr( $new_instance['registered_order'] );

			return $instance;
		}


		/**
		 * Display widget form in admin
		 *
		 * @since 2.1.0
		 *
		 * @param array $instance Widget instance.
		 *
		 * @return void
		 */
		public function form( $instance ) {
			$instance = wp_parse_args(
				(array) $instance,
				array(
					'title'                     => '',
					'registered_show_thumbnail' => '',
					'registered_num'            => false,
					'registered_orderby'        => '',
					'registered_order'          => '',
				)
			);

			$title = wp_strip_all_tags( $instance['title'] );

			$registered_show_thumbnail = esc_attr( $instance['registered_show_thumbnail'] );

			if ( '' !== $instance['registered_num'] ) {
				$registered_num = abs( intval( $instance['registered_num'] ) );
			} else {
				$registered_num = '';
			}

			$registered_orderby = esc_attr( $instance['registered_orderby'] );
			$registered_order   = esc_attr( $instance['registered_order'] );
			learndash_replace_widgets_alert();
			?>
				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'learndash' ); ?></label>
					<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
				</p>


				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'registered_show_thumbnail' ) ); ?>"><?php esc_html_e( 'Registered show thumbnail:', 'learndash' ); ?></label>
					<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'registered_show_thumbnail' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'registered_show_thumbnail' ) ); ?>">
						<option value="" <?php selected( $registered_show_thumbnail, '' ); ?>><?php esc_html_e( 'Yes (default)', 'learndash' ); ?></option>
						<option value="false" <?php selected( $registered_show_thumbnail, 'false' ); ?>><?php esc_html_e( 'No', 'learndash' ); ?></option>
					</select>
				</p>

				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'registered_num' ) ); ?>"><?php esc_html_e( 'Registered per page:', 'learndash' ); ?></label>
					<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'registered_num' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'registered_num' ) ); ?>" type="number" min="0" value="<?php echo esc_attr( $registered_num ); ?>" />
					<span class="description">
					<?php
						printf(
							// translators: placeholders: Default amount shown per page.
							esc_html_x( 'Default is %d. Set to zero for no pagination.', 'placeholders: default per page', 'learndash' ),
							esc_html( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' ) )
						);
					?>
					</span>
				</p>

				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'registered_orderby' ) ); ?>"><?php esc_html_e( 'Registered order by:', 'learndash' ); ?></label>
					<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'registered_orderby' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'registered_orderby' ) ); ?>">
						<option value="" <?php selected( $registered_orderby, '' ); ?>><?php esc_html_e( 'Title (default) - Order by post title', 'learndash' ); ?></option>
						<option value="id" <?php selected( $registered_orderby, 'id' ); ?>><?php esc_html_e( 'ID - Order by post id', 'learndash' ); ?></option>
						<option value="date" <?php selected( $registered_orderby, 'date' ); ?>><?php esc_html_e( 'Date - Order by post date', 'learndash' ); ?></option>
						<option value="menu_order" <?php selected( $registered_orderby, 'menuorder' ); ?>><?php esc_html_e( 'Menu - Order by Page Order Value', 'learndash' ); ?></option>
					</select>
				</p>
				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'registered_order' ) ); ?>"><?php esc_html_e( 'Registered order:', 'learndash' ); ?></label>
					<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'registered_order' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'registered_order' ) ); ?>">
						<option value="" <?php selected( $registered_order, '' ); ?>><?php esc_html_e( 'ASC (default) - lowest to highest values', 'learndash' ); ?></option>
						<option value="DESC" <?php selected( $registered_order, 'DESC' ); ?>><?php esc_html_e( 'DESC - highest to lowest values', 'learndash' ); ?></option>
					</select>
				</p>

			<?php
		}
	}

	add_action(
		'widgets_init',
		function() {
			return register_widget( 'LearnDash_User_Status_Widget' );
		}
	);
}
