<?php
/**
 * Alerts email base class.
 *
 * @package wp-job-manager-alerts
 */

namespace WP_Job_Manager_Alerts\Emails;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alerts email base class.
 *
 * @since 3.0.0
 */
abstract class Alerts_Email_Base extends \WP_Job_Manager_Email {

	/**
	 * Context for where these email notifications are used. Used to direct which admin settings to show.
	 *
	 * @var string
	 */
	public const CONTEXT = 'job-manager-alerts';

	/**
	 * Get `From:` address header value. Can be simple email or formatted `Firstname Lastname <email@example.com>`.
	 *
	 * @return string|false Email from value or false to use WordPress' default.
	 */
	public function get_from() {

		$name  = $this->get_from_name();
		$email = $this->get_from_email();

		if ( empty( $email ) ) {
			return false;
		}

		if ( ! empty( $name ) ) {
			return sprintf( '%s <%s>', $name, $email );
		}

		return $email;
	}

	/**
	 * Get the 'From' name component.
	 *
	 * @return string
	 */
	public function get_from_name() {
		$name = html_entity_decode( get_bloginfo( 'name' ) );

		/**
		 * Filter the "From Name" header of the e-mail.
		 *
		 * @param string $name From name. Default is the site name.
		 */
		$name = apply_filters( 'job_manager_alerts_mail_from_name', $name );

		return trim( $name );
	}

	/**
	 * Get the 'From' email address component.
	 *
	 * @return string
	 */
	public function get_from_email() {
		$domain = wp_parse_url( network_home_url(), PHP_URL_HOST ) ?? '';

		if ( str_starts_with( $domain, 'www.' ) ) {
			$domain = substr( $domain, 4 );
		}

		$email = 'noreply@' . $domain;

		/**
		 * Filter the "From Email" header of the e-mail.
		 *
		 * @param string $email From email. Default is noreply @ site URL.
		 */
		return trim( sanitize_email( apply_filters( 'job_manager_alerts_mail_from_email', $email ) ) );

	}

}
