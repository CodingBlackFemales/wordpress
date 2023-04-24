<?php
/**
 * Content to show when a candidate has selected email as their preferred contact method.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-resumes/contact-details-email.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-job-manager-resumes
 * @category    Template
 * @version     1.18.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<p><?php printf( __( 'To contact this candidate email <a class="job_application_email" href="mailto:%1$s%2$s">%1$s</a>', 'wp-job-manager-resumes' ), $email, '?subject=' . rawurlencode( $subject ) ); ?></p>

<p>
	<?php _e( 'Contact using webmail: ', 'wp-job-manager-resumes' ); ?>

	<a href="https://mail.google.com/mail/?view=cm&fs=1&to=<?php echo $email; ?>&su=<?php echo urlencode( $subject ); ?>" target="_blank" class="job_application_email">Gmail</a> /

	<a href="https://mail.aol.com/Mail/ComposeMessage.aspx?to=<?php echo $email; ?>&subject=<?php echo urlencode( $subject ); ?>" target="_blank" class="job_application_email">AOL</a> /

	<a href="https://compose.mail.yahoo.com/?to=<?php echo $email; ?>&subject=<?php echo urlencode( $subject ); ?>" target="_blank" class="job_application_email">Yahoo</a> /

	<a href="https://outlook.live.com/mail/0/deeplink/compose?to=<?php echo $email; ?>&subject=<?php echo urlencode( $subject ); ?>" target="_blank" class="job_application_email">Outlook</a>
</p>
