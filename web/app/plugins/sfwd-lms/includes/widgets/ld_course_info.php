<?php
/**
 * LearnDash `Course Info` Widget Class.
 *
 * @since 2.1.0
 * @package LearnDash\Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LearnDash_Course_Info_Widget' ) ) && ( class_exists( 'WP_Widget' ) ) ) {

	/**
	 * Class for LearnDash `Course Info` Widget.
	 *
	 * @since 2.1.0
	 * @uses WP_Widget
	 */
	class LearnDash_Course_Info_Widget extends WP_Widget {

		/**
		 * Public constructor for Widget Class.
		 *
		 * @since 2.1.0
		 */
		public function __construct() {
			$widget_ops  = array(
				'classname'   => 'widget_ldcourseinfo',
				// translators: placeholder: Course.
				'description' => sprintf( esc_html_x( 'LearnDash - %s attempt and score information of users. Visible only to users logged in.', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
			);
			$control_ops = array();
			// translators: placeholder: Course.
			parent::__construct( 'ldcourseinfo', sprintf( esc_html_x( '%s Information', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ), $widget_ops, $control_ops );
		}

		/**
		 * Displays widget
		 *
		 * @since 2.1.0
		 *
		 * @param array $args     widget arguments.
		 * @param array $instance widget instance.
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

			$courseinfo = learndash_course_info( $user_id, $instance );

			if ( empty( $courseinfo ) ) {
				return;
			}

			echo $before_widget; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.

			if ( ! empty( $title ) ) {
				echo $before_title . $title . $after_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
			}

			echo $courseinfo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
			echo $after_widget; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.

			$learndash_shortcode_used = true;
		}

		/**
		 * Handles widget updates in admin
		 *
		 * @since 2.1.0
		 *
		 * @param array $new_instance New instance.
		 * @param array $old_instance Old instance.
		 *
		 * @return array $instance
		 */
		public function update( $new_instance, $old_instance ) {
			$instance          = $old_instance;
			$instance['title'] = wp_strip_all_tags( $new_instance['title'] );

			$instance['registered_show_thumbnail'] = esc_attr( $new_instance['registered_show_thumbnail'] );
			if ( '' != $new_instance['registered_num'] ) {
				$instance['registered_num'] = intval( $new_instance['registered_num'] );
			} else {
				$instance['registered_num'] = false;
			}

			$instance['registered_orderby'] = esc_attr( $new_instance['registered_orderby'] );
			$instance['registered_order']   = esc_attr( $new_instance['registered_order'] );

			if ( '' != $new_instance['progress_num'] ) {
				$instance['progress_num'] = intval( $new_instance['progress_num'] );
			} else {
				$instance['progress_num'] = false;
			}

			$instance['progress_orderby'] = esc_attr( $new_instance['progress_orderby'] );
			$instance['progress_order']   = esc_attr( $new_instance['progress_order'] );

			if ( '' != $new_instance['quiz_num'] ) {
				$instance['quiz_num'] = intval( $new_instance['quiz_num'] );
			} else {
				$instance['quiz_num'] = false;
			}

			$instance['quiz_orderby'] = esc_attr( $new_instance['quiz_orderby'] );
			$instance['quiz_order']   = esc_attr( $new_instance['quiz_order'] );

			return $instance;
		}

		/**
		 * Display widget form in admin
		 *
		 * @since 2.1.0
		 *
		 * @param array $instance widget instance.
		 *
		 * @return string Default return is 'noform'.
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

					'progress_num'              => false,
					'progress_orderby'          => '',
					'progress_order'            => '',

					'quiz_num'                  => false,
					'quiz_orderby'              => '',
					'quiz_order'                => '',
				)
			);

			$title = wp_strip_all_tags( $instance['title'] );

			$registered_show_thumbnail = esc_attr( $instance['registered_show_thumbnail'] );

			if ( '' != $instance['registered_num'] ) {
				$registered_num = abs( intval( $instance['registered_num'] ) );
			} else {
				$registered_num = '';
			}

			$registered_orderby = esc_attr( $instance['registered_orderby'] );
			$registered_order   = esc_attr( $instance['registered_order'] );

			if ( '' != $instance['registered_num'] ) {
				$progress_num = abs( intval( $instance['progress_num'] ) );
			} else {
				$progress_num = '';
			}

			$progress_orderby = esc_attr( $instance['progress_orderby'] );
			$progress_order   = esc_attr( $instance['progress_order'] );

			if ( '' != $instance['quiz_num'] ) {
				$quiz_num = abs( intval( $instance['quiz_num'] ) );
			} else {
				$quiz_num = '';
			}

			$quiz_orderby = esc_attr( $instance['quiz_orderby'] );
			$quiz_order   = esc_attr( $instance['quiz_order'] );
			learndash_replace_widgets_alert();
			?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php echo esc_html__( 'Title:', 'learndash' ); ?></label>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo apply_filters( 'the_title', $title, 0 ); ?>" /> <?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound,WordPress.Security.EscapeOutput.OutputNotEscaped -- WP Core Hook ?>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'registered_show_thumbnail' ) ); ?>"><?php echo esc_html__( 'Registered show thumbnail:', 'learndash' ); ?></label>
				<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'registered_show_thumbnail' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'registered_show_thumbnail' ) ); ?>">
					<option value="" <?php selected( $registered_show_thumbnail, '' ); ?>><?php echo esc_html__( 'Yes (default)', 'learndash' ); ?></option>
					<option value="false" <?php selected( $registered_show_thumbnail, 'false' ); ?>><?php echo esc_html__( 'No', 'learndash' ); ?></option>
				</select>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'registered_num' ) ); ?>"><?php echo esc_html__( 'Registered per page:', 'learndash' ); ?></label>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'registered_num' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'registered_num' ) ); ?>" type="number" min="0" value="<?php echo absint( $registered_num ); ?>" />
				<span class="description">
				<?php
				printf(
					// translators: placeholder: default per page.
					esc_html_x( 'Default is %d. Set to zero for no pagination.', 'placeholder: default per page', 'learndash' ),
					esc_html( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' ) )
				);
				?>
				</span>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'registered_orderby' ) ); ?>"><?php echo esc_html__( 'Registered order by:', 'learndash' ); ?></label>
				<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'registered_orderby' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'registered_orderby' ) ); ?>">
					<option value="" <?php selected( $registered_orderby, '' ); ?>><?php echo esc_html__( 'Title (default) - Order by post title', 'learndash' ); ?></option>
					<option value="id" <?php selected( $registered_orderby, 'id' ); ?>><?php echo esc_html__( 'ID - Order by post id', 'learndash' ); ?></option>
					<option value="date" <?php selected( $registered_orderby, 'date' ); ?>><?php echo esc_html__( 'Date - Order by post date', 'learndash' ); ?></option>
					<option value="menu_order" <?php selected( $registered_orderby, 'menuorder' ); ?>><?php echo esc_html__( 'Menu - Order by Page Order Value', 'learndash' ); ?></option>
				</select>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'registered_order' ) ); ?>"><?php echo esc_html__( 'Registered order:', 'learndash' ); ?></label>
				<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'registered_order' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'registered_order' ) ); ?>">
					<option value="" <?php selected( $registered_order, '' ); ?>><?php echo esc_html__( 'ASC (default) - lowest to highest values', 'learndash' ); ?></option>
					<option value="DESC" <?php selected( $registered_order, 'DESC' ); ?>><?php echo esc_html__( 'DESC - highest to lowest values', 'learndash' ); ?></option>
				</select>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'progress_num' ) ); ?>">
				<?php
					echo sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s progress per page:', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
					);
				?>
				</label>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'progress_num' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'progress_num' ) ); ?>" type="number"  min="0" value="<?php echo absint( $progress_num ); ?>" />
				<span class="description">
				<?php
				printf(
					// translators: placeholder: default per page.
					esc_html_x( 'Default is %d. Set to zero for no pagination.', 'placeholder: default per page', 'learndash' ),
					esc_html( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'progress_num' ) )
				);
				?>
				</span>
			</p>
				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'progress_orderby' ) ); ?>"><?php echo esc_html__( 'Progress order by:', 'learndash' ); ?></label>
					<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'progress_orderby' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'progress_orderby' ) ); ?>">
						<option value="" <?php selected( $progress_orderby, '' ); ?>><?php echo esc_html__( 'Title (default) - Order by post title', 'learndash' ); ?></option>
						<option value="id" <?php selected( $progress_orderby, 'id' ); ?>><?php echo esc_html__( 'ID - Order by post id', 'learndash' ); ?></option>
						<option value="date" <?php selected( $progress_orderby, 'date' ); ?>><?php echo esc_html__( 'Date - Order by post date', 'learndash' ); ?></option>
						<option value="menu_order" <?php selected( $progress_orderby, 'menu_order' ); ?>><?php echo esc_html__( 'Menu - Order by Page Order Value', 'learndash' ); ?></option>
					</select>
				</p>
				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'progress_order' ) ); ?>"><?php echo esc_html__( 'Progress order:', 'learndash' ); ?></label>
					<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'progress_order' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'progress_order' ) ); ?>">
						<option value="" <?php selected( $progress_order, '' ); ?>><?php echo esc_html__( 'ASC (default) - lowest to highest values', 'learndash' ); ?></option>
						<option value="DESC" <?php selected( $progress_order, 'DESC' ); ?>><?php echo esc_html__( 'DESC - highest to lowest values', 'learndash' ); ?></option>
					</select>
				</p>

				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'quiz_num' ) ); ?>">
					<?php
					echo sprintf(
						// translators: placeholder: Quizzes.
						esc_html_x( '%s per page:', 'placeholder: Quizzes', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'Quizzes' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
					);
					?>
					</label>
					<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'quiz_num' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'quiz_num' ) ); ?>" type="number"  min="0" value="<?php echo absint( $quiz_num ); ?>" />
					<span class="description">
					<?php
					printf(
						// translators: placeholder: default per page.
						esc_html_x( 'Default is %d. Set to zero for no pagination.', 'placeholder: default per page', 'learndash' ),
						esc_html( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'quiz_num' ) )
					);
					?>
					</span>
				</p>
				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'quiz_orderby' ) ); ?>">
					<?php
					echo sprintf(
						// translators: placeholder: Quizzes.
						esc_html_x( '%s order by:', 'placeholder: Quizzes', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'Quizzes' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
					);
					?>
					</label>
					<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'quiz_orderby' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'quiz_orderby' ) ); ?>">
						<option value="" <?php selected( $quiz_orderby, '' ); ?>><?php echo esc_html__( 'Date Taken (default) - Order by date taken', 'learndash' ); ?></option>
						<option value="title" <?php selected( $quiz_orderby, 'title' ); ?>><?php echo esc_html__( 'Title - Order by post title', 'learndash' ); ?></option>
						<option value="id" <?php selected( $quiz_orderby, 'id' ); ?>><?php echo esc_html__( 'ID - Order by post id', 'learndash' ); ?></option>
						<option value="date" <?php selected( $quiz_orderby, 'date' ); ?>><?php echo esc_html__( 'Date - Order by post date', 'learndash' ); ?></option>
						<option value="menu_order" <?php selected( $quiz_orderby, 'menu_order' ); ?>><?php echo esc_html__( 'Menu - Order by Page Order Value', 'learndash' ); ?></option>
					</select>
				</p>
				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'quiz_order' ) ); ?>">
					<?php
					echo sprintf(
						// translators: placeholder: Quizzes.
						esc_html_x( '%s order:', 'placeholder: Quizzes', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'Quizzes' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
					);
					?>
					</label>
					<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'quiz_order' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'quiz_order' ) ); ?>">
						<option value="" <?php selected( $quiz_order, '' ); ?>><?php echo esc_html__( 'DESC (default) - highest to lowest values', 'learndash' ); ?></option>
						<option value="ASC" <?php selected( $quiz_order, 'ASC' ); ?>><?php echo esc_html__( 'ASC - lowest to highest values', 'learndash' ); ?></option>
					</select>
				</p>
			<?php
			return '';
		}
	}

	add_action(
		'widgets_init',
		function() {
			return register_widget( 'LearnDash_Course_Info_Widget' );
		}
	);
}
