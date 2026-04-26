<?php
/**
 * View: Course Header - Progress.
 *
 * @since 4.21.0
 * @version 4.24.0
 * @deprecated 4.24.0
 *
 * @var bool     $has_access Whether the user has access to the course or not.
 * @var Course   $course     Course model.
 * @var WP_User  $user       Current user.
 * @var Template $this       Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Course;
use LearnDash\Core\Models\Step;
use LearnDash\Core\Template\Template;

_deprecated_file( __FILE__, '4.24.0', 'themes/ld30/templates/modern/components/progress-bar' );

// Bail if user does not have access to the course. We are showing the progress only to users who have access.
if ( ! $has_access ) {
	return;
}

$this->template( 'modern/components/progress-bar' );
