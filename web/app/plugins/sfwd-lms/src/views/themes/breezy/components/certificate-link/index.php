<?php
/**
 * View: Certificate Link.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var string   $certificate_link Certificate link.
 * @var Template $this             Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Template\Template;

$this->template(
	'components/alert',
	array(
		'type'    => 'success ld-alert-certificate',
		'icon'    => 'certificate',
		'message' => __( 'You\'ve earned a certificate!', 'learndash' ),
		'button'  => array(
			'url'    => $certificate_link,
			'icon'   => 'download',
			'label'  => __( 'Download Certificate', 'learndash' ),
			'target' => '_new',
		),
	)
);
