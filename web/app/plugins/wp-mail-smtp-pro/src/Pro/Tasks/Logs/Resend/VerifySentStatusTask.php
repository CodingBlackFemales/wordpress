<?php

namespace WPMailSMTP\Pro\Tasks\Logs\Resend;

use WPMailSMTP\Pro\Tasks\Logs\VerifySentStatusTaskAbstract;

/**
 * Class VerifySentStatus for the Resend mailer.
 *
 * @since 4.7.0
 */
class VerifySentStatusTask extends VerifySentStatusTaskAbstract {

	/**
	 * Action name for this task.
	 *
	 * @since 4.7.0
	 */
	const ACTION = 'wp_mail_smtp_verify_sent_status_resend';
}
