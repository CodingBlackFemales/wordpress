<?php
/**
 * File containing the class WP_Resume_Manager_Email_Apply_With_Resume.
 *
 * @package wp-job-manager-resumes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email notification to employers when someone applies to a job listing with their resume.
 *
 * @package wp-job-manager-resumes
 * @since 1.18.0
 */
class WP_Resume_Manager_Email_Apply_With_Resume extends WP_Resume_Manager_Email {
	const SETTING_NOTICE_INCLUDE_DETAILS    = 'include_resume_details';
	const SETTING_NOTICE_USE_LEGACY_MESSAGE = 'use_legacy';

	/**
	 * Get the unique email notification key.
	 *
	 * @return string
	 */
	public static function get_key() {
		return 'apply_with_resume';
	}

	/**
	 * Get the friendly name for this email notification.
	 *
	 * @return string
	 */
	public static function get_name() {
		return esc_html__( 'Employer Notice of Application With Resume', 'wp-job-manager-resumes' );
	}

	/**
	 * Get the description for this email notification.
	 *
	 * @return string
	 */
	public static function get_description() {
		return esc_html__( 'Send a notice to the employer when someone applies to a job listing with a resume when the job listing application method is email.', 'wp-job-manager-resumes' );
	}

	/**
	 * Get the contents of a template.
	 *
	 * @param bool $plain_text
	 * @return string
	 */
	public function get_template( $plain_text = false ) {
		$settings   = $this->get_settings();
		$use_legacy = has_filter( 'apply_with_resume_email_message' ) && ! empty( $settings[ self::SETTING_NOTICE_USE_LEGACY_MESSAGE ] );

		if ( $use_legacy ) {
			$args            = $this->get_args();
			$candidate_email = get_post_meta( $args['resume']->ID, '_candidate_email', true );

			return implode(
				'',
				apply_filters(
					'apply_with_resume_email_message',
					[
						'greeting'      => __( 'Hello', 'wp-job-manager-resumes' ),
						// translators: Placeholder is the job title.
						'position'      => sprintf( "\n\n" . __( 'A candidate has applied online for the position "%s".', 'wp-job-manager-resumes' ), get_the_title( $args['job']->ID ) ),
						'start_message' => "\n\n-----------\n\n",
						'message'       => $args['message'],
						'end_message'   => "\n\n-----------\n\n",
						// translators: Placeholder is the URL to their resume.
						'view_resume'   => sprintf( __( 'You can view their online resume here: %s.', 'wp-job-manager-resumes' ), $args['resume_link'] ),
						// translators: Placeholder is the candidate email address.
						'contact'       => "\n" . sprintf( __( 'Or you can contact them directly at: %s.', 'wp-job-manager-resumes' ), $candidate_email ),
					],
					isset( $args['author'] ) ? $args['author']->ID : 0,
					$args['job']->ID,
					$args['resume']->ID,
					$args['message']
				)
			);
		}

		return parent::get_template( $plain_text );
	}

	/**
	 * Get the email subject.
	 *
	 * @return string
	 */
	public function get_subject() {
		$method = $this->get_application_method_details();

		if ( $method && ! empty( $method->subject ) ) {
			return wp_specialchars_decode( $method->subject, ENT_QUOTES );
		}

		$args = $this->get_args();

		/**
		 * Job object coming from arguments.
		 *
		 * @var WP_Post $job
		 */
		$job = $args['job'];

		// translators: Placeholder is title of resume.
		return sprintf( esc_html__( 'Resume submitted for job listing: %s', 'wp-job-manager-resumes' ), get_the_title( $job ) );
	}

	/**
	 * Get `From:` address header value. Can be simple email or formatted `Firstname Lastname <email@example.com>`.
	 *
	 * @return string|bool Email from value or false to use WordPress' default.
	 */
	public function get_from() {
		$args = $this->get_args();

		$candidate_name  = get_the_title( $args['resume'] );
		$candidate_email = $this->get_candidate_email();

		return $candidate_name . ' <' . $candidate_email . '>';
	}

	/**
	 * Get the base headers for the email. No need to add CC or From headers. Content-type is added when sending rich-text.
	 *
	 * @return array
	 */
	public function get_headers() {
		$headers = parent::get_headers();

		$candidate_email = $this->get_candidate_email();
		if ( $candidate_email ) {
			$headers[] = 'Reply-To: ' . $candidate_email;
		}

		return $headers;
	}

	/**
	 * Get the candidate's email address.
	 *
	 * @return bool|string
	 */
	private function get_candidate_email() {
		$args            = $this->get_args();
		$candidate_email = get_post_meta( $args['resume']->ID, '_candidate_email', true );

		if ( empty( $candidate_email ) && isset( $args['author'] ) && $args['author'] instanceof WP_User ) {
			$candidate_email = $args['author']->user_email;
		}

		if ( empty( $candidate_email ) ) {
			return false;
		}

		return sanitize_email( $candidate_email );
	}

	/**
	 * Get the job application method details.
	 *
	 * @return bool|object
	 */
	private function get_application_method_details() {
		$args = $this->get_args();
		if ( ! isset( $args['job'] ) ) {
			return false;
		}

		$method = get_the_job_application_method( $args['job'] );
		if ( ! is_object( $method ) || empty( $method->raw_email ) ) {
			return false;
		}

		return $method;
	}

	/**
	 * Get array or comma-separated list of email addresses to send message.
	 *
	 * @return string|array|bool
	 */
	public function get_to() {
		$method = $this->get_application_method_details();

		if ( ! $method ) {
			return false;
		}

		return $method->raw_email;
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
	 * Checks if we should show resume details on the email.
	 *
	 * @return bool
	 */
	public function show_resume_details() {
		$settings = $this->get_settings();

		return ! empty( $settings[ self::SETTING_NOTICE_INCLUDE_DETAILS ] );
	}

	/**
	 * Checks the arguments and returns whether the email notification is properly set up.
	 *
	 * @return bool
	 */
	public function is_valid() {
		$args = $this->get_args();

		return isset( $args['resume'] )
				&& isset( $args['job'] )
				&& isset( $args['message'] )
				&& $args['resume'] instanceof WP_Post
				&& $args['job'] instanceof WP_Post
				&& $this->get_to();
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
	 * Get the settings for this email notifications.
	 *
	 * @return array
	 */
	public static function get_setting_fields() {
		$fields = parent::get_setting_fields();

		// Keep support for the legacy message.
		if ( has_filter( 'apply_with_resume_email_message' ) ) {
			$fields[] = [
				'name'     => self::SETTING_NOTICE_USE_LEGACY_MESSAGE,
				'std'      => 1,
				'label'    => esc_html__( 'Legacy Message', 'wp-job-manager-resumes' ),
				'cb_label' => esc_html__( 'Use legacy filter to generate email content', 'wp-job-manager-resumes' ),
				'desc'     => wp_kses_post( __( 'Legacy filter <code>apply_with_resume_email_message</code> has been set with a customization. Disable to use new template.', 'wp-job-manager-resumes' ) ),
				'type'     => 'checkbox',
			];
		}

		$fields[] = [
			'name'     => self::SETTING_NOTICE_INCLUDE_DETAILS,
			'std'      => 0,
			'label'    => esc_html__( 'Resume Details', 'wp-job-manager-resumes' ),
			'cb_label' => esc_html__( 'Include resume details in the content of the email', 'wp-job-manager-resumes' ),
			'type'     => 'checkbox',
		];

		return $fields;
	}

	/**
	 * Expand arguments as necessary for the generation of the email.
	 *
	 * @param array $args Arguments used in generation of email.
	 * @return mixed
	 */
	protected function prepare_args( $args ) {
		$args = parent::prepare_args( $args );

		$args['resume_link'] = null;
		if ( ! empty( $args['resume'] ) ) {
			$args['resume_link'] = get_resume_share_link( $args['resume']->ID );
		}

		return $args;
	}

}
