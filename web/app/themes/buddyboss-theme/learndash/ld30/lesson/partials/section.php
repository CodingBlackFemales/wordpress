<?php
/**
 * LearnDash LD30 Displays section
 *
 * Available Variables:
 * WIP
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $bb_ld_heading_level_3;

/**
 * Fires before the section title (outside wrapper).
 *
 * @since 3.0.0
 *
 * @param WP_Post $section   `WP_Post` object for section.
 * @param int     $course_id Course ID.
 * @param int     $user_id   User ID.
 */
do_action( 'learndash-before-section-heading', $section, $course_id, $user_id ); ?>
<div class="ld-item-list-section-heading ld-item-section-heading-<?php echo esc_attr( $section->ID ); ?>">
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
	do_action( 'learndash-before-inner-section-heading', $section, $course_id, $user_id );
	if ( empty( $bb_ld_heading_level_3 ) ) {
		$bb_ld_heading_level_3 = true;
		?>
        <h3 class="ld-lesson-section-heading" aria-role="heading" aria-level="3"><?php echo esc_html( $section->post_title ); ?></h3>
		<?php
	} else {
		?>
        <div class="ld-lesson-section-heading" aria-role="heading" aria-level="3"><?php echo esc_html( $section->post_title ); ?></div>
		<?php
	}
	/**
	 * Fires after the section title (inside wrapper).
	 *
	 * @since 3.0.0
	 *
	 * @param WP_Post $section   `WP_Post` object for section.
	 * @param int     $course_id Course ID.
	 * @param int     $user_id   User ID.
	 */
	do_action( 'learndash-after-inner-section-heading', $section, $course_id, $user_id );
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
do_action( 'learndash-after-section-heading', $section, $course_id, $user_id );
