<?php
/**
 * View: Instructor Avatar.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Instructor $instructor Instructor.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Models\Instructor;
?>
<div class="ld-instructors__avatar">
	<?php echo get_avatar( $instructor->get_id(), 48, '', $instructor->get_display_name() ); ?>
</div>
