<?php
/**
 * File containing the class Confirmation_Email.
 *
 * @package wp-job-manager-alerts
 */

namespace WP_Job_Manager_Alerts\Emails;

use WP_Job_Manager\Guest_Session;
use WP_Job_Manager\Guest_User;
use WP_Job_Manager_Alerts\Post_Types;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email confirmation for guest users.
 *
 * @since 3.0.0
 */
class Confirmation_Email extends Alerts_Email_Base {

	/**
	 * Identifier for this email.
	 */
	const KEY = 'alert_confirmation';

	/**
	 * Send a confirmation email.
	 *
	 * @param array $args {
	 *    Arguments used in generation of email.
	 *
	 * @type string     $email Email address to send to.
	 * @type \WP_Post   $alert Alert.
	 * @type Guest_User $guest Guest user.
	 * }
	 */
	public static function send( $args ) {
		do_action( 'job_manager_send_notification', self::get_key(), $args );
	}

	/**
	 * Get the unique email notification key.
	 *
	 * @return string
	 */
	public static function get_key() {
		return self::KEY;
	}

	/**
	 * Get the context for where this email notification is used. Used to direct which admin settings to show.
	 *
	 * @return string
	 */
	public static function get_context() {
		return self::CONTEXT;
	}

	/**
	 * Get the friendly name for this email notification.
	 *
	 * @return string
	 */
	public static function get_name() {
		return esc_html__( 'Alert Confirmation E-mail', 'wp-job-manager-alerts' );
	}

	/**
	 * Get the description for this email notification.
	 *
	 * @return string
	 */
	public static function get_description() {
		return esc_html__( 'Send an e-mail to confirm the e-mail address when not using an account.', 'wp-job-manager-alerts' );
	}

	/**
	 * Get the email subject.
	 *
	 * @return string
	 */
	public function get_subject() {
		return apply_filters( 'job_manager_alert_confirmation_subject', __( 'Confirm your Job Alert', 'wp-job-manager-alerts' ) );
	}

	/**
	 * Get array or comma-separated list of email addresses to send message.
	 *
	 * @return string|array|bool
	 */
	public function get_to() {
		$args = $this->get_args();

		$email_to = $args['email'] ?? '';

		if ( ! is_email( $email_to ) ) {
			return false;
		}

		return $email_to;
	}

	/**
	 * Checks the arguments and returns whether the email notification is properly set up.
	 *
	 * @return bool
	 */
	public function is_valid() {
		$args = $this->get_args();

		return isset( $args['email'] )
			&& ! empty( $args['alert'] )
			&& ! empty( $args['guest'] )
			&& ! empty( $args['token'] );
	}

	/**
	 * Force the email notification to be enabled.
	 *
	 * @return bool
	 */
	public static function get_enabled_force_value() {
		return true;
	}

	/**
	 * Get the rich text version of the email content.
	 *
	 * @return string
	 */
	public function get_rich_content() {
		$alert = $this->get_args()['alert'];

		$args = [
			'site_url'          => get_site_url(),
			'site_name'         => get_bloginfo( 'name' ),
			'alert'             => $alert,
			'search_terms'      => Post_Types::get_alert_search_term_names( $alert->ID ),
			'alert_confirm_url' => $this->get_confirm_url(),
		];

		return \WP_Job_Manager_Alerts::get_template( 'emails/email-confirmation.php', $args );

	}

	/**
	 * Get the link to confirm the alert.
	 *
	 * @return string
	 */
	private function get_confirm_url() {

		$args = $this->get_args();

		return add_query_arg(
			[
				'action'                 => 'confirm',
				'alert_id'               => $args['alert']->ID,
				Guest_Session::QUERY_VAR => $args['token'],
			],
			get_permalink( get_option( 'job_manager_alerts_page_id' ) )
		);
	}
}
