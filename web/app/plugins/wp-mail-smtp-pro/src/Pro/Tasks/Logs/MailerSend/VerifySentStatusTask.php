<?php

namespace WPMailSMTP\Pro\Tasks\Logs\MailerSend;

use WPMailSMTP\Pro\Tasks\Logs\VerifySentStatusTaskAbstract;

/**
 * Class VerifySentStatusTask for the Elastic Email mailer.
 *
 * @since 4.5.0
 */
class VerifySentStatusTask extends VerifySentStatusTaskAbstract {

	/**
	 * Action name for this task.
	 *
	 * @since 4.5.0
	 */
	const ACTION = 'wp_mail_smtp_verify_sent_status_mailersend';
}
