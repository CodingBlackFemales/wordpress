<?php
/**
 * View: Course Enrollment Access King.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var array{king: ?string, subjects: string[]} $access_options Access options.
 * @var Template                                 $this           Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

if ( $access_options['king'] ) {
	$this->template( 'modern/course/enrollment/access/king/' . $access_options['king'] );
}

$this->template(
	'modern/course/enrollment/access/subjects',
	[
		'subjects' => $access_options['subjects'],
	]
);

// Seats Remaining is a special case where it's never a subject. When it's not king, it has a special position.
if ( $access_options['king'] !== 'seats-remaining' ) {
	$this->template( 'modern/course/enrollment/access/seats-remaining' );
}
