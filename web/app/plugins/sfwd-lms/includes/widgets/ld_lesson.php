<?php
/**
 * LearnDash `Lessons` Widget Class.
 *
 * @since 2.1.0
 * @package LearnDash\Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'Lesson_Widget' ) ) && ( class_exists( 'WP_Widget' ) ) ) {

	/**
	 * Class for LearnDash `Lessons` Widget.
	 *
	 * @since 2.1.0
	 * @uses WP_Widget
	 */
	class Lesson_Widget extends WP_Widget /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {

		/**
		 * Post type
		 *
		 *  @var string $post_type.
		 */
		protected $post_type = 'sfwd-lessons';

		/**
		 * Post name
		 *
		 * @var string $post_name.
		 */
		protected $post_name = 'Lesson';

		/**
		 * Post arguments
		 *
		 * @var object $post_args.
		 */
		protected $post_args;

		/**
		 * Public constructor for Widget Class.
		 *
		 * @since 2.1.0
		 */
		public function __construct() {
			$args = array();

			$this->post_name = LearnDash_Custom_Label::get_label( 'lesson' );

			// translators: placeholders: Lessons, Course, Lesson.
			$args['description'] = sprintf( esc_html_x( 'Displays a list of %1$s for a %2$s and tracks %3$s progress.', 'placeholders: Lessons, Course, Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'lessons' ), LearnDash_Custom_Label::get_label( 'course' ), LearnDash_Custom_Label::get_label( 'lesson' ) );

			if ( empty( $this->post_args ) ) {
				$this->post_args = array(
					'post_type'   => $this->post_type,
					'numberposts' => -1,
					'order'       => 'DESC',
					'orderby'     => 'date',
				);
			}

			parent::__construct( "{$this->post_type}-widget", $this->post_name, $args );
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

			extract( $args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

			/* Before Widget content */
			$buf = $before_widget;

			/** This filter is documented in https://developer.wordpress.org/reference/hooks/widget_title/ */
			$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

			if ( ! empty( $title ) ) {
				$buf .= $before_title . $title . $after_title;
			}

			$buf .= '<ul>';

			/* Display Widget Data */
			$course_id = learndash_get_course_id();

			if ( empty( $course_id ) || ! is_single() ) {
				return '';
			}

			$course_lessons_list          = $this->course_lessons_list( $course_id );
			$stripped_course_lessons_list = wp_strip_all_tags( $course_lessons_list );

			if ( empty( $stripped_course_lessons_list ) ) {
				return '';
			}

			$buf .= $course_lessons_list;

			/* After Widget content */
			$buf .= '</ul>' . $after_widget;

			echo $buf; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.

			$learndash_shortcode_used = true;

		}

		/**
		 * Sets up course lesson list HTML
		 *
		 * @since 2.1.0
		 *
		 * @param int $course_id course id.
		 *
		 * @return string $html output
		 */
		public function course_lessons_list( $course_id ) {
			$course = get_post( $course_id );

			if ( empty( $course->ID ) || $course_id != $course->ID ) {
				return '';
			}

			$html                  = '';
			$course_lesson_orderby = learndash_get_setting( $course_id, 'course_lesson_orderby' );
			$course_lesson_order   = learndash_get_setting( $course_id, 'course_lesson_order' );
			$lessons               = sfwd_lms_get_post_options( 'sfwd-lessons' );
			$orderby               = ( empty( $course_lesson_orderby ) ) ? $lessons['orderby'] : $course_lesson_orderby;
			$order                 = ( empty( $course_lesson_order ) ) ? $lessons['order'] : $course_lesson_order;
			$post__in              = '';
			$meta_key              = 'course_id';
			$meta_value            = $course_id;

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
				$course_lessons = learndash_course_get_steps_by_type( $course_id, 'sfwd-lessons' );
				if ( ! empty( $course_lessons ) ) {
					$order      = '';
					$orderby    = 'post__in';
					$post__in   = implode( ',', $course_lessons );
					$meta_key   = '';
					$meta_value = '';
				}
			}

			$shortcode = '[sfwd-lessons meta_key="' . $meta_key . '" meta_value="' . $meta_value . '" order="' . $order . '" orderby="' . $orderby . '" post__in="' . $post__in . '" posts_per_page="' . $lessons['posts_per_page'] . '" wrapper="li"]';

			$lessons = wptexturize( do_shortcode( $shortcode ) );

			$html .= $lessons;
			return $html;
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
			/* Updates widget title value */
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
		 * @return string Default return is 'noform'.
		 */
		public function form( $instance ) {
			if ( $instance ) {
				$title = esc_attr( $instance['title'] );
			} else {
				$title = $this->post_name;
			}
			learndash_replace_widgets_alert();
			?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'learndash' ); ?></label>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
			</p>
			<?php
			return '';
		}
	}

	add_action(
		'widgets_init',
		function() {
			return register_widget( 'Lesson_Widget' );
		}
	);
}
