<?php
/**
 * View: Assignment Title.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Assignment $assignment The assignment.
 * @var Template   $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Assignment;
use LearnDash\Core\Template\Template;

?>
<div class="ld-assignments__list-item-title">
	<?php echo esc_html( $assignment->get_uploaded_file_name() ); ?>

	<?php $this->template( 'modern/components/assignments/list/assignment/details' ); ?>
</div>
