<?php

namespace WPMailSMTP\Pro\Tasks\Logs\Mailjet;

use WPMailSMTP\Pro\Tasks\Logs\VerifySentStatusTaskAbstract;

/**
 * Class VerifySentStatusTask for the Mailjet mailer.
 *
 * @since 4.2.0
 */
class VerifySentStatusTask extends VerifySentStatusTaskAbstract {

	/**
	 * Action name for this task.
	 *
	 * @since 4.2.0
	 */
	const ACTION = 'wp_mail_smtp_verify_sent_status_mailjet';
}
