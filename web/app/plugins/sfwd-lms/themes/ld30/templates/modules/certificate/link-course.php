<?php
/**
 * LearnDash LD30 show certificate link on course.
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LearnDash LD30 Displays the infobar in course context
 *
 * Will have access to same variables as course.php
 *
 * Available Variables:
 * $course_id                  : (int) ID of the course
 * $course                     : (object) Post object of the course
 * $course_settings            : (array) Settings specific to current course
 *
 * $courses_options            : Options/Settings as configured on Course Options page
 * $lessons_options            : Options/Settings as configured on Lessons Options page
 * $quizzes_options            : Options/Settings as configured on Quiz Options page
 *
 * $user_id                    : Current User ID
 * $logged_in                  : User is logged in
 * $current_user               : (object) Currently logged in user object
 *
 * $course_status              : Course Status
 * $has_access                 : User has access to course or is enrolled.
 * $materials                  : Course Materials
 * $has_course_content         : Course has course content
 * $lessons                    : Lessons Array
 * $quizzes                    : Quizzes Array
 * $lesson_progression_enabled : (true/false)
 * $has_topics                 : (true/false)
 * $lesson_topics              : (array) lessons topics
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30\Modules
 */

if ( ! empty( $course_certficate_link ) ) : ?>
	<a href='<?php echo esc_url( $course_certficate_link ); ?>' target="_blank">
		<?php
		/** This filter is documented in includes/ld-certificates.php */
		echo apply_filters( 'ld_certificate_link_label', esc_html__( 'Print Your Certificate', 'learndash' ), $user_id, $post->ID );
		?>
	</a>
<?php endif; ?>
