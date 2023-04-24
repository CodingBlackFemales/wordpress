<?php
/**
 * File containing the class WP_Resume_Manager_Email_Admin_New_Resume.
 *
 * @package wp-job-manager-resumes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email notification to administrator when a new resume is submitted.
 *
 * @package wp-job-manager-resumes
 * @since 1.18.0
 */
class WP_Resume_Manager_Email_Admin_New_Resume extends WP_Resume_Manager_Email {
	const SETTING_NOTICE_TO = 'to';

	/**
	 * Get the unique email notification key.
	 *
	 * @return string
	 */
	public static function get_key() {
		return 'admin_new_resume';
	}

	/**
	 * Get the friendly name for this email notification.
	 *
	 * @return string
	 */
	public static function get_name() {
		return esc_html__( 'Admin Notice of New Resume', 'wp-job-manager-resumes' );
	}

	/**
	 * Get the description for this email notification.
	 *
	 * @return string
	 */
	public static function get_description() {
		return esc_html__( 'Send a notice to the site administrator when a new resume is submitted on the frontend.', 'wp-job-manager-resumes' );
	}

	/**
	 * Get the email subject.
	 *
	 * @return string
	 */
	public function get_subject() {
		$args = $this->get_args();

		/**
		 * Resume object coming from arguments.
		 *
		 * @var WP_Post $resume
		 */
		$resume = $args['resume'];

		// translators: Placeholder is title of resume.
		return sprintf( esc_html__( 'New Resume Submitted: %s', 'wp-job-manager-resumes' ), $resume->post_title );
	}

	/**
	 * Get `From:` address header value. Can be simple email or formatted `Firstname Lastname <email@example.com>`.
	 *
	 * @return string|bool Email from value or false to use WordPress' default.
	 */
	public function get_from() {
		return false;
	}

	/**
	 * Get array or comma-separated list of email addresses to send message.
	 *
	 * @return string|array
	 */
	public function get_to() {
		$settings = $this->get_settings();
		if ( ! empty( $settings[ self::SETTING_NOTICE_TO ] ) ) {
			$email_addresses = array_filter( array_map( 'sanitize_email', preg_split( '/[,|;]\s?/', $settings[ self::SETTING_NOTICE_TO ] ) ) );
			if ( ! empty( $email_addresses ) ) {
				return $email_addresses;
			}
		}

		return get_option( 'admin_email', false );
	}

	/**
	 * Returns the list of file paths to attach to an email.
	 *
	 * @return array
	 */
	public function get_attachments() {
		$attachments = parent::get_attachments();
		$args        = $this->get_args();

		$files = get_resume_attachments( $args['resume']->ID );
		if ( ! empty( $files['attachments'] ) ) {
			$attachments = array_merge( $attachments, $files['attachments'] );
		}

		return $attachments;
	}

	/**
	 * Checks the arguments and returns whether the email notification is properly set up.
	 *
	 * @return bool
	 */
	public function is_valid() {
		$args = $this->get_args();
		return isset( $args['resume'] )
				&& $args['resume'] instanceof WP_Post
				&& $this->get_to();
	}

	/**
	 * Get the settings for this email notifications.
	 *
	 * @return array
	 */
	public static function get_setting_fields() {
		$fields   = parent::get_setting_fields();
		$fields[] = [
			'name'  => self::SETTING_NOTICE_TO,
			'std'   => get_option( 'admin_email', get_option( 'resume_manager_email_notifications', false ) ),
			'label' => esc_html__( 'Notify Email Address(es)', 'wp-job-manager-resumes' ),
			'desc'  => esc_html__( 'Separate email addresses with commas. Recipients are assumed to have administrator access to this WordPress instance.', 'wp-job-manager-resumes' ),
			'type'  => 'text',
		];

		return $fields;
	}
}
