<?php
/**
 * File containing the class Confirmation_Email.
 *
 * @package wp-job-manager-alerts
 */

namespace WP_Job_Manager_Alerts\Emails;

use WP_Job_Manager\Access_Token;
use WP_Job_Manager\Guest_Session;
use WP_Job_Manager\Guest_User;
use WP_Job_Manager_Alerts\Alert;
use WP_Job_Manager_Alerts\Notifier;
use WP_Job_Manager_Alerts\WP_Job_Manager_Alerts;
use WP_Job_Manager_Alerts\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email confirmation for guest users.
 *
 * @since 3.0.0
 */
class Job_Alert_Email extends Alerts_Email_Base {
	/**
	 * Identifier for this email.
	 */
	const KEY = 'job_alert';

	/**
	 * Send the alert email.
	 *
	 * @param Alert     $alert
	 * @param \WP_Query $jobs
	 * @param \WP_User  $user
	 */
	public static function send( $alert, $jobs, $user ) {
		do_action(
			'job_manager_send_notification',
			self::get_key(),
			[
				'alert' => $alert,
				'jobs'  => $jobs,
				'user'  => $user,
			]
		);
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
		return esc_html__( 'Job Alert E-mail', 'wp-job-manager-alerts' );
	}

	/**
	 * Get the description for this email notification.
	 *
	 * @return string
	 */
	public static function get_description() {
		return esc_html__( 'Send regular e-mails with new job listings matching a search.', 'wp-job-manager-alerts' );
	}

	/**
	 * Get the email subject.
	 *
	 * @return string
	 */
	public function get_subject() {
		$alert = $this->get_args()['alert'];

		if ( ! $this->is_valid() ) {
			return '';
		}

		/**
		 * Filter the e-mail subject.
		 *
		 * @param string   $subject E-mail subject.
		 * @param \WP_Post $alert The job alert.
		 */
		// Translators: Placeholder is the alert name.
		return apply_filters( 'job_manager_alerts_subject', sprintf( __( 'Job Alert Results Matching "%s"', 'wp-job-manager-alerts' ), $alert->get_name() ), $alert );
	}

	/**
	 * Get array or comma-separated list of email addresses to send message.
	 *
	 * @return string|array|bool
	 */
	public function get_to() {
		$args = $this->get_args();

		$email = $args['user']->user_email ?? '';

		if ( ! is_email( $email ) ) {
			return false;
		}

		return $email;
	}

	/**
	 * Checks the arguments and returns whether the email notification is properly set up.
	 *
	 * @return bool
	 */
	public function is_valid() {
		$args = $this->get_args();

		if ( ! isset( $args['alert'] ) || ! $args['alert'] instanceof Alert ) {
			return false;
		}

		if ( ! isset( $args['jobs'] ) || ! $args['jobs'] instanceof \WP_Query ) {
			return false;
		}

		if ( ! isset( $args['user'] ) || ! ( $args['user'] instanceof \WP_User || $args['user'] instanceof Guest_User ) ) {
			return false;
		}

		return true;
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
	 * Get the plain text version of the email content.
	 *
	 * @return string
	 */
	public function get_plain_content() {

		[ 'alert' => $alert, 'jobs' => $jobs, 'user' => $user ] = $this->get_args();

		if ( ! $this->is_valid() ) {
			return '';
		}

		$template = $this->get_customizable_content();

		if ( ! str_contains( $template, '{alert_page_url}' ) ) {
			$template .= "\n\n--\n\n" . __( 'Manage Alerts: {alert_page_url}', 'wp-job-manager-alerts' );
			$template .= "\n" . __( 'Unsubscribe: {alert_unsubscribe_url}', 'wp-job-manager-alerts' ) . "\n";
		}

		$job_content = $this->render_jobs_plaintext( $jobs );

		$replacements           = $this->get_replacements( $alert, $user );
		$replacements['{jobs}'] = $job_content;

		$content = $this->apply_replacements( $template, $replacements );

		/**
		 * Filter the e-mail content.
		 *
		 * @see get_rich_content for documentation.
		 */
		return apply_filters( 'job_manager_alerts_template', $content, $alert, $user, $jobs, false );
	}

	/**
	 * Get the rich text version of the email content.
	 *
	 * @return string
	 */
	public function get_rich_content() {

		[ 'alert' => $alert, 'jobs' => $jobs, 'user' => $user ] = $this->get_args();

		if ( ! $this->is_valid() ) {
			return '';
		}

		$template = $this->get_customizable_content();

		$jobs = $this->get_jobs_content( $jobs );

		$template = wpautop( wptexturize( wp_kses_post( $template ) ) );

		$replacements           = $this->get_replacements( $alert, $user );
		$replacements['{jobs}'] = $jobs;

		$content = $this->apply_replacements( $template, $replacements );

		/**
		 * Filter the e-mail content.
		 *
		 * @param string    $content The e-mail content.
		 * @param \WP_Post  $alert The job alert.
		 * @param \WP_User  $user The alert's user.
		 * @param \WP_Query $jobs The jobs matching the alert.
		 * @param bool      $html Whether the e-mail is HTML or plaintext.
		 */
		$content = apply_filters( 'job_manager_alerts_template', $content, $alert, $user, $jobs, true );

		return WP_Job_Manager_Alerts::get_template(
			'emails/email-job-alert.php',
			[
				'subject'               => $this->get_subject(),
				'content'               => $content,
				'site_url'              => get_site_url(),
				'site_name'             => get_bloginfo( 'name' ),
				'alert_page_url'        => $replacements['{alert_page_url}'],
				'alert_unsubscribe_url' => $replacements['{alert_unsubscribe_url}'],
			]
		);
	}


	/**
	 * Render list of matching jobs for HTML e-mail.
	 *
	 * @param \WP_Query $jobs Jobs query.
	 *
	 * @return false|string
	 */
	private function get_jobs_content( $jobs ) {
		if ( ! $jobs || ! $jobs->have_posts() ) {
			return WP_Job_Manager_Alerts::get_template( 'emails/email-job-alert-no-jobs.php', [] );
		}

		return WP_Job_Manager_Alerts::get_template(
			'emails/email-job-alert-jobs.php',
			[
				'jobs' => $jobs->posts,
			]
		);
	}

	/**
	 * Get the timestamp for when the next alert will be sent.
	 *
	 * @param \WP_Post $alert The job alert.
	 *
	 * @return int
	 */
	private function get_next_alert_date( $alert ) {
		$schedules = Notifier::get_alert_schedules();

		if ( ! empty( $schedules[ $alert->alert_frequency ] ) ) {
			$next = strtotime( '+' . $schedules[ $alert->alert_frequency ]['interval'] . ' seconds' );
		} else {
			$next = strtotime( '+1 day' );
		}

		return $next;
	}

	/**
	 * Get the e-mail content template.
	 *
	 * @return string
	 */
	private function get_customizable_content() {
		$template = get_option( 'job_manager_alerts_email_template_value' );

		if ( ! $template ) {
			$template = Settings::instance()->get_default_email();
		}

		return $template;
	}

	/**
	 * Render list of matching jobs for plain text e-mail.
	 *
	 * @param \WP_Query $jobs Jobs query.
	 *
	 * @return string
	 */
	private function render_jobs_plaintext( $jobs ) {
		if ( ! $jobs || ! $jobs->have_posts() ) {
			return __( 'No jobs were found matching your search. Login to your account to change your alert criteria', 'wp-job-manager-alerts' );
		}

		$job_content = '';

		while ( $jobs->have_posts() ) {
			$jobs->the_post();

			$job_content .= WP_Job_Manager_Alerts::get_template( 'content-email_job_listing.php', [] );
		}

		wp_reset_postdata();

		return $job_content;
	}

	/**
	 * Get replacement values for the e-mail {tags}.
	 *
	 * @param Alert               $alert The job alert.
	 * @param \WP_User|Guest_User $user The alert's user.
	 *
	 * @return array
	 */
	private function get_replacements( Alert $alert, $user ): array {

		$next_date   = $alert->get_next_scheduled();
		$expire_date = $alert->get_expiration_date();

		// Translators: placeholder is the date the alert will stop sending.
		$alert_expiry = $expire_date ? sprintf( __( 'This job alert will automatically stop sending after %s.', 'wp-job-manager-alerts' ), $expire_date ) : '';

		$alert_next = date_i18n( get_option( 'date_format' ), $next_date );

		$manage_url = get_permalink( get_option( 'job_manager_alerts_page_id' ) );

		$unsubscribe_url = add_query_arg(
			[
				'action'   => 'unsubscribe',
				'alert_id' => $alert->ID,
				'user_id'  => $user->ID,
				'token'    => ( new Access_Token( [ $alert->ID, $user->ID ] ) )->create(),
			],
			$manage_url
		);

		if ( $user instanceof Guest_User ) {
			$token      = $user->create_token();
			$manage_url = add_query_arg( [ Guest_Session::QUERY_VAR => $token ], $manage_url );

			$unsubscribe_url = add_query_arg(
				[
					'action'   => 'unsubscribe',
					'alert_id' => $alert->ID,
				],
				$manage_url
			);
		}

		return [
			'{display_name}'          => $user->display_name ?? $user->user_email,
			'{alert_name}'            => $alert->get_name(),
			'{alert_expiry}'          => $alert_expiry,
			'{alert_expirey}'         => $alert_expiry, // Backwards compatibility pre-1.5.3.
			'{alert_next_date}'       => $alert_next,
			'{alert_page_url}'        => $manage_url,
			'{alert_unsubscribe_url}' => $unsubscribe_url,
		];
	}

	/**
	 * Apply replacements for {tags} in the e-mail content.
	 *
	 * @param string $template
	 * @param array  $replacements
	 *
	 * @return array|string|string[]
	 */
	private function apply_replacements( string $template, array $replacements ) {
		$template = str_replace( array_keys( $replacements ), array_values( $replacements ), $template );

		return $template;
	}
}
