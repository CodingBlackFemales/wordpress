<?php
/**
 * View: Assignment Status badge.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Assignment $assignment The assignment.
 * @var Template   $this       Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Assignment;
use LearnDash\Core\Template\Template;

?>

<?php if ( $assignment->is_approved() ) : ?>
	<?php $this->template( 'modern/components/assignments/list/assignment/details/status/approved' ); ?>
<?php else : ?>
	<?php $this->template( 'modern/components/assignments/list/assignment/details/status/pending' ); ?>
	<?php
endif;
