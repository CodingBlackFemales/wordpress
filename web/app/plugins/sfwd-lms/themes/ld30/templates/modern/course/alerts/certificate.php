<?php
/**
 * View: Course Header - Certificate Link.
 *
 * @since 4.21.0
 * @version 4.24.0
 * @deprecated 4.24.0
 *
 * @var Course   $course Course model.
 * @var WP_User  $user   Current user.
 * @var Template $this   Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Course;
use LearnDash\Core\Template\Alerts;
use LearnDash\Core\Template\Template;

_deprecated_file( __FILE__, '4.24.0', 'themes/ld30/templates/modern/components/alerts/alert' );

$certificate_link = $course->get_certificate_link( $user );

if ( empty( $certificate_link ) ) {
	return;
}

$this->template(
	'modern/components/alerts/alert',
	[
		'alert' => Alerts\Alert::parse(
			[
				'id'          => 'course-certificate',
				'action_type' => 'button',
				'button_icon' => 'download-mini',
				'link_target' => '_new',
				'link_text'   => __( 'Download Certificate', 'learndash' ),
				'link_url'    => $certificate_link,
				'message'     => __( "You've earned a certificate!", 'learndash' ),
				'type'        => 'info',
			]
		),
	]
);
