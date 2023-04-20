<?php
/**
 * LearnDash `Course Progress` Widget Class.
 *
 * @since 2.1.0
 * @package LearnDash\Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Course Progress Widget
 */
if ( ( ! class_exists( 'LearnDash_Course_Progress_Widget' ) ) && ( class_exists( 'WP_Widget' ) ) ) {

	/**
	 * Class for LearnDash `Course Progress` Widget.
	 *
	 * @since 2.1.0
	 * @uses WP_Widget
	 */
	class LearnDash_Course_Progress_Widget extends WP_Widget {

		/**
		 * Public constructor for Widget Class.
		 *
		 * @since 2.1.0
		 */
		public function __construct() {
			$widget_ops  = array(
				'classname'   => 'widget_ldcourseprogress',
				// translators: placeholder: course.
				'description' => sprintf( esc_html_x( 'LearnDash %s progress bar', 'placeholders: course', 'learndash' ), learndash_get_custom_label_lower( 'course' ) ),
			);
			$control_ops = array();
			// translators: placeholder: Course.
			parent::__construct( 'ldcourseprogress', sprintf( esc_html_x( '%s Progress Bar', 'Course Progress Bar Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ), $widget_ops, $control_ops );
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

			if ( ! is_singular() ) {
				return;
			}

			$progressbar = learndash_course_progress( $args );

			if ( empty( $progressbar ) ) {
				return;
			}

			echo $before_widget; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.

			if ( ! empty( $title ) ) {
				echo $before_title . $title . $after_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
			}

			echo $progressbar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
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
			$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
			$title    = wp_strip_all_tags( $instance['title'] );
			learndash_replace_widgets_alert();
			?>
			<p><label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'learndash' ); ?></label>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>
			<?php
			return '';
		}
	}

	add_action(
		'widgets_init',
		function() {
			return register_widget( 'LearnDash_Course_Progress_Widget' );
		}
	);
}
