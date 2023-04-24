<?php
/**
 * Email content for showing resume details.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-resumes/emails/email-resume-details.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-job-manager-resumes
 * @category    Template
 * @version     1.18.0
 *
 * @var array                $fields         Array of the resume details.
 * @var WP_Post              $resume         The resume listing to show details for.
 * @var WP_Job_Manager_Email $email          Email object for the notification.
 * @var bool                 $sent_to_admin  True if this is being sent to an administrator.
 * @var bool                 $plain_text     True if the email is being sent as plain text.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $fields ) ) {
	return;
}

$text_align = is_rtl() ? 'right' : 'left';
?>
<div class="resume-manager-email-resume-details-container email-container">
	<table border="0" cellpadding="10" cellspacing="0" width="100%" class="resume-manager-email-resume-details details">
		<?php foreach ( $fields as $field ) : ?>
			<tr>
				<td class="detail-label" style="text-align:<?php echo esc_attr( $text_align ); ?>;">
					<?php echo wp_kses_post( $field['label'] ); ?>
				</td>
				<td class="detail-value" style="text-align:<?php echo esc_attr( $text_align ); ?>;">
					<p>
					<?php
					if ( ! empty( $field['url'] ) ) {
						echo sprintf( '<a href="%s">%s</a>', esc_url( $field['url'] ), wp_kses_post( $field['value'] ) );
					} else {
						echo wp_kses_post( nl2br( $field['value'] ) );
					}
					?>
					</p>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
</div>
