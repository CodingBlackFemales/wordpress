<?php
/**
 * View: Instructors List.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Instructor[] $instructors Instructors.
 * @var Template     $this        Current Instance of template engine rendering this template.
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
use LearnDash\Core\Template\Template;
?>
<ul class="ld-instructors__list">
	<?php foreach ( $instructors as $instructor ) : ?>
		<?php $this->template( 'components/instructors/item', [ 'instructor' => $instructor ] ); ?>
	<?php endforeach; ?>
</ul>
