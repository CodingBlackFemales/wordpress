<?php
/**
 * LearnDash LD30 Displays the course navigation widget section.
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30\Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fires before the section title (outside wrapper).
 *
 * @since 3.0.0
 *
 * @param WP_Post $section   `WP_Post` object for section.
 * @param int     $course_id Course ID.
 * @param int     $user_id   User ID.
 */
do_action( 'learndash-nav-before-section-heading', $section, $course_id, $user_id ); ?>
<div class="ld-lesson-item-section-heading ld-lesson-item-section-heading-<?php echo esc_attr( $section->ID ); ?>">
	<?php
	/**
	 * Fires before the section title (inside wrapper).
	 *
	 * @since 3.0.0
	 *
	 * @param WP_Post $section   `WP_Post` object for section.
	 * @param int     $course_id Course ID.
	 * @param int     $user_id   User ID.
	 */
	do_action( 'learndash-nav-before-inner-section-heading', $section, $course_id, $user_id );
	?>
	<span class="ld-lesson-section-heading" role="heading" aria-level="3"><?php echo esc_html( $section->post_title ); ?></span>
	<?php
	/**
	 * Fires after the section title (inside wrapper).
	 *
	 * @since 3.0.0
	 *
	 * @param WP_Post $section   `WP_Post` object for section.
	 * @param int     $course_id Course ID.
	 * @param int     $user_id   User ID.
	 */
	do_action( 'learndash-nav-after-inner-section-heading', $section, $course_id, $user_id );
	?>
</div>
<?php
/**
 * Fires after the section title (outside wrapper).
 *
 * @since 3.0.0
 *
 * @param WP_Post $section   `WP_Post` object for section.
 * @param int     $course_id Course ID.
 * @param int     $user_id   User ID.
 */
do_action( 'learndash-nav-after-section-heading', $section, $course_id, $user_id );
