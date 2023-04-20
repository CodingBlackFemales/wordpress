<?php
/**
 * LearnDash LD30 Displays the listing of course row
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course      = get_post( $course_id );
$course_link = get_permalink( $course_id );
/**
 * Filters course list shortcode course CSS class. Used to add CSS class to the wrapper of each course item
 *
 * @since 3.0.0
 *
 * @param string $course_class Course item CSS class
 */
$course_class = apply_filters( 'learndash-course-list-shortcode-course-class', '' ); ?>

<?php
/**
 * Fires before the course row.
 *
 * @since 3.0.0
 *
 * @param int $course_id Course ID.
 * @param int $user_id   User ID.
 */
do_action( 'learndash-course-row-before', $course_id, $user_id );
?>

<div id="course-<?php echo esc_attr( $user_id ) . '-' . esc_attr( $course->ID ); ?>" class="<?php echo esc_attr( $course_class ); ?>">
	<div>

		<?php
		/**
		 * Fires before the course row status.
		 *
		 * @since 3.0.0
		 *
		 * @param int $course_id Course ID.
		 * @param int $user_id   User ID.
		 */
		do_action( 'learndash-course-row-status-before', $course_id, $user_id );
		?>

		<?php
		/**
		 * Fires before the course row link.
		 *
		 * @since 3.0.0
		 *
		 * @param int $course_id Course ID.
		 * @param int $user_id   User ID.
		 */
		do_action( 'learndash-course-row-link-before', $course_id, $user_id );
		?>

		<a href="<?php echo esc_url( $course_link ); ?>"><?php echo wp_kses_post( apply_filters( 'the_title', $course->post_title, $course->ID ) ); ?></a> <?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound ?>

		<?php
		/**
		 * Fires before the course row certificate.
		 *
		 * @since 3.0.0
		 *
		 * @param int $course_id Course ID.
		 * @param int $user_id   User ID.
		 */
		do_action( 'learndash-course-row-certificate-before', $course_id, $user_id );
		?>

		<?php
		/**
		 * Fires before the course row link.
		 *
		 * @since 3.0.0
		 *
		 * @param int $course_id Course ID.
		 * @param int $user_id   User ID.
		 */
		do_action( 'learndash-course-row-expand-before', $course_id, $user_id );
		?>

		<?php
		/**
		 * Fires after the course row link.
		 *
		 * @since 3.0.0
		 *
		 * @param int $course_id Course ID.
		 * @param int $user_id   User ID.
		 */
		do_action( 'learndash-course-row-expand-after', $course_id, $user_id );
		?>

	</div>
</div>
