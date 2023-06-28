<?php
/**
 * View: Course Overview. Used in a sidebar on course step pages.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Course $course Course model.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Models\Course;
?>
<h2 id="ld-course-overview-heading" class="ld-course-overview__heading">
	<?php echo esc_html( $course->get_title() ); ?>
</h2>
