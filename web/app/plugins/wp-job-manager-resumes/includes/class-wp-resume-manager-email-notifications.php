<?php
/**
 * File containing the WP_Resume_Manager_Email_Notifications.
 *
 * @package wp-job-manager-resumes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Resume_Manager_Email_Notifications class.
 */
final class WP_Resume_Manager_Email_Notifications {

	/**
	 * Sets up initial hooks.
	 */
	public static function init() {
		add_action( 'job_manager_email_init', [ __CLASS__, 'lazy_init' ] );
		add_filter( 'job_manager_email_notifications', [ __CLASS__, 'add_resume_manager_notifications' ] );
		add_filter( 'resume_manager_settings', [ __CLASS__, 'add_resume_manager_email_settings' ], 1 );
		add_action( 'resume_manager_resume_submitted', [ __CLASS__, 'send_new_resume_notification' ] );
		add_action( 'resume_manager_apply_with_resume', [ __CLASS__, 'send_apply_with_resume_notification' ], 10, 4 );
		add_action( 'resume_manager_email_resume_details', [ __CLASS__, 'output_resume_details' ], 10, 4 );
		add_filter( 'job_manager_email_is_email_notification_enabled', [ __CLASS__, 'force_apply_with_resume_enabled' ], 10, 2 );
	}

	/**
	 * Include email files.
	 *
	 * Do not call manually.
	 *
	 * @access private
	 */
	public static function lazy_init() {
		include_once RESUME_MANAGER_PLUGIN_DIR . '/includes/emails/class-wp-resume-manager-email-admin-new-resume.php';
		include_once RESUME_MANAGER_PLUGIN_DIR . '/includes/emails/class-wp-resume-manager-email-apply-with-resume.php';

		self::legacy_settings_check();
		self::legacy_templates_check();
	}

	/**
	 * Fire deprecation notice for legacy and unsupported templates.
	 */
	private static function legacy_templates_check() {
		$legacy_template = locate_job_manager_template( 'resume-submitted-notification.php', 'wp-job-manager-resumes', false );
		if ( $legacy_template && file_exists( $legacy_template ) ) {
			_deprecated_file(
				// translators: Placeholder is path to old legacy template.
				esc_html( sprintf( __( 'Template override in theme: %s', 'wp-job-manager-resumes' ), $legacy_template ) ),
				'1.18.0',
				// translators: Placeholder is path to new replacement template.
				esc_html( sprintf( __( 'See the new template here: %s', 'wp-job-manager-resumes' ), 'emails/admin-new-resume.php' ) )
			);
		}
	}

	/**
	 * Check for legacy settings and map to appropriate new setting.
	 */
	private static function legacy_settings_check() {
		// Update legacy admin notice options.
		$settings_updated       = false;
		$admin_notify_email_key = 'job_manager_email_admin_new_resume';
		$email_settings         = get_option( $admin_notify_email_key, [] );
		$admin_notice_to        = get_option( 'resume_manager_email_notifications', null );
		$admin_notice_enabled   = get_option( 'resume_manager_submission_notification', null );

		if ( null !== $admin_notice_enabled ) {
			$settings_updated = true;
			$email_settings[ WP_Job_Manager_Email_Notifications::EMAIL_SETTING_ENABLED ] = $admin_notice_enabled ? 1 : 0;
			delete_option( 'resume_manager_submission_notification' );
		}

		if ( ! empty( $admin_notice_to ) ) {
			$settings_updated = true;
			$email_settings[ WP_Resume_Manager_Email_Admin_New_Resume::SETTING_NOTICE_TO ] = $admin_notice_to;
			delete_option( 'resume_manager_email_notifications' );
		}

		if ( $settings_updated ) {
			update_option( $admin_notify_email_key, $email_settings );
		}
	}

	/**
	 * Add email notification settings for the resume manager context.
	 *
	 * @param array $settings
	 * @return array
	 */
	public static function add_resume_manager_email_settings( $settings ) {
		return WP_Job_Manager_Email_Notifications::add_email_settings( $settings, WP_Resume_Manager_Email::get_context() );
	}

	/**
	 * Adds resume manager's email notifications.
	 *
	 * @param array $notifications
	 * @return array
	 */
	public static function add_resume_manager_notifications( $notifications ) {
		$notifications[] = 'WP_Resume_Manager_Email_Admin_New_Resume';
		$notifications[] = 'WP_Resume_Manager_Email_Apply_With_Resume';

		return $notifications;
	}

	/**
	 * Fire the action to send a new resume notification to the admin.
	 *
	 * @param int $resume_id
	 */
	public static function send_new_resume_notification( $resume_id ) {
		do_action( 'job_manager_send_notification', 'admin_new_resume', [ 'resume_id' => $resume_id ] );
	}

	/**
	 * Enqueue the email notification for when a user applies with a resume.
	 *
	 * @param int    $user_id             User ID of the person who submitted the application.
	 * @param int    $job_id              Job post ID.
	 * @param int    $resume_id           Resume post ID.
	 * @param string $application_message Message that was sent along with resume application.
	 */
	public static function send_apply_with_resume_notification( $user_id, $job_id, $resume_id, $application_message ) {
		do_action(
			'job_manager_send_notification',
			'apply_with_resume',
			[
				'resume_id' => $resume_id,
				'job_id'    => $job_id,
				'message'   => $application_message,
			]
		);
	}

	/**
	 * Show details about the resume listing.
	 *
	 * @param WP_Post              $resume         The resume listing to show details for.
	 * @param WP_Job_Manager_Email $email          Email object for the notification.
	 * @param bool                 $sent_to_admin  True if this is being sent to an administrator.
	 * @param bool                 $plain_text     True if the email is being sent as plain text.
	 */
	public static function output_resume_details( $resume, $email, $sent_to_admin, $plain_text = false ) {
		$template_segment = self::locate_template_file( 'email-resume-details', $plain_text );
		if ( ! file_exists( $template_segment ) ) {
			return;
		}

		$fields = self::get_resume_detail_fields( $resume, $sent_to_admin, $plain_text );

		include $template_segment;
	}

	/**
	 * Locate template file.
	 *
	 * @param string $template_name
	 * @param bool   $plain_text
	 * @return string
	 */
	public static function locate_template_file( $template_name, $plain_text ) {
		return WP_Job_Manager_Email_Notifications::locate_template_file( $template_name, $plain_text, WP_Resume_Manager_Email::get_template_path(), WP_Resume_Manager_Email::get_template_default_path() );
	}

	/**
	 * Get the resume fields to show in email templates.
	 *
	 * @param WP_Post $resume
	 * @param bool    $sent_to_admin
	 * @param bool    $plain_text
	 * @return array
	 */
	private static function get_resume_detail_fields( WP_Post $resume, $sent_to_admin, $plain_text = false ) {
		include_once RESUME_MANAGER_PLUGIN_DIR . '/includes/admin/class-wp-resume-manager-writepanels.php';

		$fields = [];

		$fields['resume_candidate'] = [
			'label' => __( 'Candidate', 'wp-job-manager-resumes' ),
			'value' => $resume->post_title,
		];

		if ( $sent_to_admin || 'publish' === $resume->post_status ) {
			$fields['resume_candidate']['url'] = get_permalink( $resume );
		}

		$resume_expires = get_post_meta( $resume->ID, '_resume_expires', true );
		if ( ! empty( $resume_expires ) ) {
			$resume_expires_str       = date_i18n( get_option( 'date_format' ), strtotime( $resume_expires ) );
			$fields['resume_expires'] = [
				'label' => __( 'Resume expires', 'wp-job-manager-resumes' ),
				'value' => $resume_expires_str,
			];
		}

		$custom_fields = array_diff_key(
			WP_Resume_Manager_Writepanels::resume_fields(),
			[
				'_resume_file'    => '',
				'_resume_expires' => '',
			]
		);

		foreach ( $custom_fields as $meta_key => $field ) {
			if ( empty( $field['type'] ) ) {
				$field['type'] = 'text';
			}
			if ( ! in_array( $field['type'], [ 'text', 'textarea' ], true ) ) {
				continue;
			}

			$meta_value = get_post_meta( $resume->ID, $meta_key, true );
			if ( ! empty( $meta_value ) && is_string( $meta_value ) ) {
				$fields[ 'resume_' . $meta_key ] = [
					'label' => $field['label'],
					'value' => esc_html( $meta_value ),
				];
			}
		}

		$links = get_post_meta( $resume->ID, '_links', true );
		if ( ! empty( $links ) ) {
			foreach ( $links as $key => $item ) {
				$fields[ 'resume_links_' . $key ] = [
					'label' => __( 'Link', 'wp-job-manager-resumes' ),
					'value' => $item['name'],
					'url'   => esc_url( $item['url'] ),
				];
			}
		}

		$education = get_post_meta( $resume->ID, '_candidate_education', true );
		if ( ! empty( $education ) ) {
			$resume_education_str = '';
			foreach ( $education as $key => $item ) {
				// translators: Placeholder is location of education experience.
				$resume_education_str .= sprintf( __( 'Location: %s', 'wp-job-manager-resumes' ), $item['location'] ) . PHP_EOL;
				// translators: Placeholder is date of education experience.
				$resume_education_str .= sprintf( __( 'Date: %s', 'wp-job-manager-resumes' ), $item['date'] ) . PHP_EOL;
				// translators: Placeholder is qualifications/degrees of education experience.
				$resume_education_str .= sprintf( __( 'Certification: %s', 'wp-job-manager-resumes' ), $item['qualification'] ) . PHP_EOL;
				// translators: Placeholder is notes for education experience.
				$resume_education_str .= sprintf( __( 'Notes: %s', 'wp-job-manager-resumes' ), $item['notes'] ) . PHP_EOL;
				$resume_education_str .= PHP_EOL;
			}

			$fields['resume_education'] = [
				'label' => __( 'Education', 'wp-job-manager-resumes' ),
				'value' => trim( $resume_education_str, PHP_EOL ),
			];
		}

		$experience = get_post_meta( $resume->ID, '_candidate_experience', true );
		if ( ! empty( $experience ) ) {
			$resume_experience_str = '';
			foreach ( $experience as $key => $item ) {
				// translators: Placeholder is employer name of experience.
				$resume_experience_str .= sprintf( __( 'Employer: %s', 'wp-job-manager-resumes' ), $item['employer'] ) . PHP_EOL;
				// translators: Placeholder is date of experience.
				$resume_experience_str .= sprintf( __( 'Date: %s', 'wp-job-manager-resumes' ), $item['date'] ) . PHP_EOL;
				// translators: Placeholder is job title of experience.
				$resume_experience_str .= sprintf( __( 'Job Title: %s', 'wp-job-manager-resumes' ), $item['job_title'] ) . PHP_EOL;
				// translators: Placeholder is notes for experience.
				$resume_experience_str .= sprintf( __( 'Notes: %s', 'wp-job-manager-resumes' ), $item['notes'] ) . PHP_EOL;
				$resume_experience_str .= PHP_EOL;
			}

			$fields['resume_experience'] = [
				'label' => __( 'Experience', 'wp-job-manager-resumes' ),
				'value' => trim( $resume_experience_str, PHP_EOL ),
			];
		}

		if ( $sent_to_admin ) {
			$author = get_user_by( 'ID', $resume->post_author );
			if ( $author instanceof WP_User ) {
				$fields['author'] = [
					'label' => esc_html__( 'Posted by', 'wp-job-manager-resumes' ),
					'value' => $author->user_nicename,
					'url'   => 'mailto:' . $author->user_email,
				];
			}
		}

		/**
		 * Modify the fields shown in email notifications in the details summary a resume.
		 *
		 * @since 1.18.0
		 *
		 * @param array   $fields         {
		 *     Array of fields. Each field is keyed with a unique identifier.
		 *     {
		 *          @type string $label Label to show next to field.
		 *          @type string $value Value for field.
		 *          @type string $url   URL to provide with the value (optional).
		 *     }
		 * }
		 * @param WP_Post $resume            resume listing.
		 * @param bool    $sent_to_admin  True if being sent in an admin notification.
		 * @param bool    $plain_text     True if being sent as plain text.
		 */
		return apply_filters( 'resume_manager_emails_resume_detail_fields', $fields, $resume, $sent_to_admin, $plain_text );
	}

	/**
	 * Force the apply with resume notification to be enabled. Not needed on WPJM 1.34.1 and newer.
	 *
	 * @param bool   $is_email_notification_enabled Filtered value for if the email notification is enabled.
	 * @param string $email_notification_key        Unique key for the email notification.
	 * @return bool
	 */
	public static function force_apply_with_resume_enabled( $is_email_notification_enabled, $email_notification_key ) {
		if ( WP_Resume_Manager_Email_Apply_With_Resume::get_key() === $email_notification_key ) {
			return true;
		}

		return $is_email_notification_enabled;
	}
}
