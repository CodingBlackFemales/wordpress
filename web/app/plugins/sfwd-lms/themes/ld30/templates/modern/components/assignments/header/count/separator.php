<?php
/**
 * View: Assignments Header Count Separator.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Assignment[] $assignments The uploaded assignments.
 * @var Template     $this        Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Assignment;
use LearnDash\Core\Template\Template;

if ( empty( $assignments ) ) {
	return;
}

?>
<span class="ld-assignments__header-count-separator">
	-
</span>
