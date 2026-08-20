<?php

namespace WPMailSMTP\Pro\Tasks\Logs\SMTP2GO;

use WPMailSMTP\Pro\Tasks\Logs\VerifySentStatusTaskAbstract;

/**
 * Class VerifySentStatusTask for the SMTP2GO mailer.
 *
 * @since 4.1.0
 */
class VerifySentStatusTask extends VerifySentStatusTaskAbstract {

	/**
	 * Action name for this task.
	 *
	 * @since 4.1.0
	 */
	const ACTION = 'wp_mail_smtp_verify_sent_status_smtp2go';
}
