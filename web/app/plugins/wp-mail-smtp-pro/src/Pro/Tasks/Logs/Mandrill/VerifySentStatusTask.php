<?php

namespace WPMailSMTP\Pro\Tasks\Logs\Mandrill;

use WPMailSMTP\Pro\Tasks\Logs\VerifySentStatusTaskAbstract;

/**
 * Class VerifySentStatusTask.
 *
 * @since 4.6.0
 */
class VerifySentStatusTask extends VerifySentStatusTaskAbstract {

	/**
	 * Action name for this task.
	 *
	 * @since 4.6.0
	 */
	const ACTION = 'wp_mail_smtp_verify_mandrill_sent_status';
}
