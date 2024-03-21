<?php
/**
 * File containing the class WP_Resume_Manager_Settings.
 *
 * @package wp-job-manager-resumes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Resume_Manager_Settings class.
 */
class WP_Resume_Manager_Settings extends WP_Job_Manager_Settings {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->settings_group = 'wp-job-manager-resumes';
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_action_update', [ $this, 'pre_process_settings_save' ] );
	}

	/**
	 * Init_settings function.
	 *
	 * @access protected
	 * @return void
	 */
	protected function init_settings() {
		// Prepare roles option.
		$roles         = get_editable_roles();
		$account_roles = [];

		foreach ( $roles as $key => $role ) {
			if ( 'administrator' === $key ) {
				continue;
			}
			$account_roles[ $key ] = $role['name'];
		}

		$empty_trash_days = defined( 'EMPTY_TRASH_DAYS ' ) ? EMPTY_TRASH_DAYS : 30;
		if ( empty( $empty_trash_days ) || $empty_trash_days < 0 ) {
			$trash_description = __( 'They will then need to be manually removed from the trash', 'wp-job-manager-resumes' );
		} else {
			// translators: Placeholder %d is the number of days before items are removed from trash.
			$trash_description = sprintf( __( 'They will then be permanently deleted after %d days.', 'wp-job-manager-resumes' ), $empty_trash_days );
		}

		$this->settings = apply_filters(
			'resume_manager_settings',
			[
				'resume_listings'    => [
					__( 'Resume Listings', 'wp-job-manager-resumes' ),
					[
						[
							'name'        => 'resume_manager_per_page',
							'std'         => '10',
							'placeholder' => '',
							'label'       => __( 'Resumes Per Page', 'wp-job-manager-resumes' ),
							'desc'        => __( 'How many resumes should be shown per page by default?', 'wp-job-manager-resumes' ),
							'attributes'  => [],
						],
						[
							'name'       => 'resume_manager_enable_categories',
							'std'        => '0',
							'label'      => __( 'Categories', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Enable resume categories', 'wp-job-manager-resumes' ),
							'desc'       => __( 'Choose whether to enable resume categories. Categories must be setup by an admin for users to choose during job submission.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
						[
							'name'       => 'resume_manager_enable_default_category_multiselect',
							'std'        => '0',
							'label'      => __( 'Multi-select Categories', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Enable category multiselect by default', 'wp-job-manager-resumes' ),
							'desc'       => __( 'If enabled, the category select box will default to a multiselect on the [resumes] shortcode.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
						[
							'name'    => 'resume_manager_category_filter_type',
							'std'     => 'any',
							'label'   => __( 'Category Filter Type', 'wp-job-manager-resumes' ),
							'desc'    => __( 'Choose how to filter resumes when Multi-select Categories option is enabled.', 'wp-job-manager-resumes' ),
							'type'    => 'select',
							'options' => [
								'any' => __( 'Resumes will be shown if within ANY selected category', 'wp-job-manager-resumes' ),
								'all' => __( 'Resumes will be shown if within ALL selected categories', 'wp-job-manager-resumes' ),
							],
						],
						[
							'name'       => 'resume_manager_enable_skills',
							'std'        => '0',
							'label'      => __( 'Skills', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Enable candidate skills', 'wp-job-manager-resumes' ),
							'desc'       => __( 'Choose whether to enable the candidate skills field. Skills can be added by users during resume submission.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
						[
							'name'        => 'resume_manager_max_skills',
							'std'         => '',
							'label'       => __( 'Maximum Skills', 'wp-job-manager-resumes' ),
							'placeholder' => __( 'Unlimited', 'wp-job-manager-resumes' ),
							'desc'        => __( 'Enter the number of skills per resume submission you wish to allow, or leave blank for unlimited skills.', 'wp-job-manager-resumes' ),
							'type'        => 'input',
						],
						[
							'name'       => 'resume_manager_enable_resume_upload',
							'std'        => '0',
							'label'      => __( 'Resume Upload', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Enable resume upload', 'wp-job-manager-resumes' ),
							'desc'       => __( 'Choose whether to allow candidates to upload a resume file.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
						[
							'name'       => 'resume_manager_delete_files_on_resume_deletion',
							'std'        => '0',
							'label'      => __( 'Delete uploaded files', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Delete uploaded files when a resume is deleted', 'wp-job-manager-resumes' ),
							'desc'       => __( 'Choose whether to deleted uploaded files when a resume is deleted and removed from the trash.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
						[
							'name'       => 'resume_manager_erasure_request_removes_resumes',
							'std'        => '0',
							'label'      => __( 'Personal Data Erasure', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Remove resumes on account erasure requests', 'wp-job-manager-resumes' ),
							'desc'       => sprintf(
								// translators: Placeholder %1$s is the URL to the WP Admin page that handles account erasure requests. %2$s is trash notification.
								__( 'If enabled, resumes with a matching email address will be sent to the trash during <a href="%1$s">personal data erasure requests</a>. %2$s', 'wp-job-manager-resumes' ),
								esc_url( admin_url( 'tools.php?page=remove_personal_data' ) ),
								$trash_description
							),
							'type'       => 'checkbox',
							'attributes' => [],
						],
					],
				],
				'resume_submission'  => [
					__( 'Resume Submission', 'wp-job-manager-resumes' ),
					[
						[
							'name'       => 'resume_manager_user_requires_account',
							'std'        => '1',
							'label'      => __( 'Account Required', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Submitting listings requires an account', 'wp-job-manager-resumes' ),
							'desc'       => __( 'If disabled, non-logged in users will be able to submit listings without creating an account. Please note that this will prevent non-registered users from being able to edit their listings at a later date.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
						[
							'name'       => 'resume_manager_enable_registration',
							'std'        => '1',
							'label'      => __( 'Account Creation', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Allow account creation', 'wp-job-manager-resumes' ),
							'desc'       => __( 'If enabled, non-logged in users will be able to create an account by entering their email address on the resume submission form.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
						[
							'name'       => 'resume_manager_generate_username_from_email',
							'std'        => '1',
							'label'      => __( 'Account Username', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Automatically Generate Username from Email Address', 'wp-job-manager-resumes' ),
							'desc'       => __( 'If enabled, a username will be generated from the first part of the user email address. Otherwise, a username field will be shown.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
						[
							'name'       => 'resume_manager_use_standard_password_setup_email',
							'std'        => '1',
							'label'      => __( 'Account Password', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Use WordPress\' default behavior and email new users link to set a password', 'wp-job-manager-resumes' ),
							'desc'       => __( 'If enabled, an email will be sent to the user with their username and a link to set their password. Otherwise, a password field will be shown and their email address won\'t be verified.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
						[
							'name'    => 'resume_manager_registration_role',
							'std'     => 'candidate',
							'label'   => __( 'Account Role', 'wp-job-manager-resumes' ),
							'desc'    => __( 'If you enable registration on your submission form, choose a role for the new user.', 'wp-job-manager-resumes' ),
							'type'    => 'select',
							'options' => $account_roles,
						],
						[
							'name'       => 'resume_manager_submission_requires_approval',
							'std'        => '1',
							'label'      => __( 'Approval Required', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'New submissions require admin approval', 'wp-job-manager-resumes' ),
							'desc'       => __( 'If enabled, new submissions will be inactive, pending admin approval.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
						[
							'name'       => 'resume_manager_user_can_edit_pending_submissions',
							'std'        => '0',
							'label'      => __( 'Allow Pending Edits', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Allow editing of pending resumes', 'wp-job-manager-resumes' ),
							'desc'       => __( 'Users can continue to edit pending resumes until they are approved by an admin.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
						[
							'name'       => 'resume_manager_user_edit_published_submissions',
							'std'        => 'yes',
							'label'      => __( 'Allow Published Edits', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Allow editing of published resumes', 'wp-job-manager-resumes' ),
							'desc'       => __( 'Choose whether published resumes can be edited and if edits require admin approval. When moderation is required, the original resume will be unpublished while edits await admin approval.', 'wp-job-manager-resumes' ),
							'type'       => 'radio',
							'options'    => [
								'no'            => __( 'Users cannot edit', 'wp-job-manager-resumes' ),
								'yes'           => __( 'Users can edit without admin approval', 'wp-job-manager-resumes' ),
								'yes_moderated' => __( 'Users can edit, but edits require admin approval', 'wp-job-manager-resumes' ),
							],
							'attributes' => [],
						],
						[
							'name'        => 'resume_manager_submission_duration',
							'std'         => '',
							'label'       => __( 'Listing Duration', 'wp-job-manager-resumes' ),
							'desc'        => __( 'How many <strong>days</strong> listings are live before expiring. Can be left blank to never expire. Expired listings must be relisted to become visible.', 'wp-job-manager-resumes' ),
							'attributes'  => [],
							'placeholder' => __( 'Never expire', 'wp-job-manager-resumes' ),
						],
						[
							'name'        => 'resume_manager_autohide',
							'std'         => '',
							'label'       => __( 'Auto-hide Resumes', 'wp-job-manager-resumes' ),
							'desc'        => __( 'How many <strong>days</strong> un-modified resumes should be published before being hidden. Can be left blank to never hide resumes automatically. Candidates can re-publish hidden resumes form their dashboard.', 'wp-job-manager-resumes' ),
							'attributes'  => [],
							'placeholder' => __( 'Never auto-hide', 'wp-job-manager-resumes' ),
						],
						[
							'name'        => 'resume_manager_submission_limit',
							'std'         => '',
							'label'       => __( 'Listing Limit', 'wp-job-manager-resumes' ),
							'desc'        => __( 'How many listings are users allowed to post. Can be left blank to allow unlimited listings per account.', 'wp-job-manager-resumes' ),
							'attributes'  => [],
							'placeholder' => __( 'No limit', 'wp-job-manager-resumes' ),
						],
						[
							'name'       => 'resume_manager_show_agreement_resume_submission',
							'std'        => '0',
							'label'      => __( 'Terms and Conditions Checkbox', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Enable required Terms and Conditions checkbox on the form', 'wp-job-manager-resumes' ),
							'desc'       => sprintf(
								// translators: Placeholder %s is the URL to the page in WP Job Manager's settings to set the pages.
								__( 'Require a Terms and Conditions checkbox to be marked before a resume can be submitted. The linked page can be set from the <a href="%s">WP Job Manager\'s settings</a>.', 'wp-job-manager-resumes' ),
								esc_url( admin_url( 'edit.php?post_type=job_listing&page=job-manager-settings#settings-job_pages' ) )
							),
							'type'       => 'checkbox',
							'attributes' => [],
						],
						'recaptcha' => [
							'name'       => 'resume_manager_enable_recaptcha_resume_submission',
							'std'        => '0',
							'label'      => __( 'reCAPTCHA', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Display a reCAPTCHA field on resume submission form.', 'wp-job-manager-resumes' ),
							'desc'       => sprintf(
								// translators: Placeholder %s is the URL to the page in WP Job Manager's settings to make the change.
								__( 'This will help prevent bots from submitting resumes. You must have entered a valid site key and secret key in <a href="%s">WP Job Manager\'s settings</a>.', 'wp-job-manager-resumes' ),
								esc_url( admin_url( 'edit.php?post_type=job_listing&page=job-manager-settings#settings-recaptcha' ) )
							),
							'type'       => 'checkbox',
							'attributes' => [],
						],
					],
				],
				'resume_application' => [
					__( 'Apply with Resume', 'wp-job-manager-resumes' ),
					[
						[
							'name'     => 'resume_manager_enable_application',
							'std'      => '1',
							'label'    => __( 'Email Based Applications', 'wp-job-manager-resumes' ),
							'cb_label' => __( 'Allow candidates to apply to jobs which use the email application method using their online resume', 'wp-job-manager-resumes' ),
							'desc'     => sprintf(
								// translators: Placeholder is link to settings tab which includes email Notifications.
								__( 'The employer will be mailed their message and a private link to the resume. Manage notification from the <a href="%s" class="nav-internal">Email Notifications</a> settings tab.', 'wp-job-manager-resumes' ),
								'#settings-email_notifications'
							),
							'type'     => 'checkbox',
						],
						[
							'name'     => 'resume_manager_enable_application_for_url_method',
							'std'      => '1',
							'label'    => __( 'Website Based Applications', 'wp-job-manager-resumes' ),
							'cb_label' => __( 'Allow candidates to apply to jobs which use the the website URL application method using their online resume', 'wp-job-manager-resumes' ),
							'desc'     => __( 'The application will be stored in the database.', 'wp-job-manager-resumes' ),
							'type'     => 'checkbox',
						],
						[
							'name'       => 'resume_manager_force_resume',
							'std'        => '0',
							'label'      => __( 'Force Resume Creation', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Force candidates to create an online resume before applying to a job', 'wp-job-manager-resumes' ),
							'desc'       => __( 'Candidates without a resume on file will be taken through the resume submission process. Other details, such as the application email address or application forms, will be hidden.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
						[
							'name'       => 'resume_manager_force_application',
							'std'        => '0',
							'label'      => __( 'Force Apply with Resume', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Force candidates to apply through Resume Manager', 'wp-job-manager-resumes' ),
							'desc'       => __( 'If the apply forms are enabled above, they must be used to apply. All other application methods will be hidden.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
					],
				],
				'resume_pages'       => [
					__( 'Pages', 'wp-job-manager-resumes' ),
					[
						[
							'name'  => 'resume_manager_submit_resume_form_page_id',
							'std'   => '',
							'label' => __( 'Submit Resume Page', 'wp-job-manager-resumes' ),
							'desc'  => __( 'Select the page where you have placed the [submit_resume_form] shortcode. This lets the plugin know where the form is located.', 'wp-job-manager-resumes' ),
							'type'  => 'page',
						],
						[
							'name'  => 'resume_manager_candidate_dashboard_page_id',
							'std'   => '',
							'label' => __( 'Candidate Dashboard Page', 'wp-job-manager-resumes' ),
							'desc'  => __( 'Select the page where you have placed the [candidate_dashboard] shortcode. This lets the plugin know where the dashboard is located.', 'wp-job-manager-resumes' ),
							'type'  => 'page',
						],
						[
							'name'  => 'resume_manager_resumes_page_id',
							'std'   => '',
							'label' => __( 'Resume Listings Page', 'wp-job-manager-resumes' ),
							'desc'  => sprintf(
								// translators: Placeholder is link to settings tab which includes resume visibility settings.
								__( 'Select the page where you have placed the [resumes] shortcode. This lets the plugin know where the resume listings page is located. Manage access to this page and resumes from the <a href="%s" class="nav-internal">Resume Visibility</a> tab.', 'wp-job-manager-resumes' ),
								'#settings-resume_visibility'
							),
							'type'  => 'page',
						],
					],
				],
				'resume_visibility'  => [
					__( 'Resume Visibility', 'wp-job-manager-resumes' ),
					[
						[
							'name'  => 'resume_manager_view_name_capability',
							'std'   => 'administrator,employer',
							'label' => __( 'View Resume name Capability', 'wp-job-manager-resumes' ),
							'type'  => 'capabilities',
							// translators: Placeholder %s is the url to the WordPress core documentation for capabilities and roles.
							'desc'  => sprintf( __( 'Enter which <a href="%s">roles or capabilities</a> allow visitors to view resumes names. If no value is selected, everyone (including logged out guests) will be able view candidates full name.', 'wp-job-manager-resumes' ), 'http://codex.wordpress.org/Roles_and_Capabilities' ),
						],
						[
							'name'  => 'resume_manager_browse_resume_capability',
							'std'   => 'administrator,employer',
							'label' => __( 'Browse Resume Capability', 'wp-job-manager-resumes' ),
							'type'  => 'capabilities',
							// translators: Placeholder %s is the url to the WordPress core documentation for capabilities and roles.
							'desc'  => sprintf( __( 'Enter which <a href="%s">roles or capabilities</a> allow visitors to browse resumes. If no value is selected, everyone (including logged out guests) will be able to browse resumes..', 'wp-job-manager-resumes' ), 'http://codex.wordpress.org/Roles_and_Capabilities' ),
						],
						[
							'name'  => 'resume_manager_view_resume_capability',
							'std'   => 'administrator,employer',
							'label' => __( 'View Resume Capability', 'wp-job-manager-resumes' ),
							'type'  => 'capabilities',
							// translators: Placeholder %s is the url to the WordPress core documentation for capabilities and roles.
							'desc'  => sprintf( __( 'Enter which <a href="%s">roles or capabilities</a> allow visitors to view a single resume. If no value is selected, everyone (including logged out guests) will be able to view resumes.', 'wp-job-manager-resumes' ), 'http://codex.wordpress.org/Roles_and_Capabilities' ),
						],
						[
							'name'  => 'resume_manager_contact_resume_capability',
							'std'   => 'administrator,employer',
							'label' => __( 'Contact Details Capability', 'wp-job-manager-resumes' ),
							'type'  => 'capabilities',
							// translators: Placeholder %s is the url to the WordPress core documentation for capabilities and roles.
							'desc'  => sprintf( __( 'Enter which <a href="%s">roles or capabilities</a> allow visitors to view contact details on a resume. If no value is selected, contact details will be publicly available.', 'wp-job-manager-resumes' ), 'http://codex.wordpress.org/Roles_and_Capabilities' ),
						],
						[
							'name'       => 'resume_manager_discourage_resume_search_indexing',
							'std'        => '1',
							'label'      => __( 'Search Engine Visibility', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Discourage search engines from indexing resume listings', 'wp-job-manager-resumes' ),
							'desc'       => __( 'Search engines choose whether to honor this request.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => [],
						],
					],
				],
			]
		);

		if ( ! defined( 'JOB_MANAGER_VERSION' ) || version_compare( '1.30.0', JOB_MANAGER_VERSION, '>' ) ) {
			unset( $this->settings['resume_submission'][1]['recaptcha'] );
		}
		if ( ! class_exists( 'WP_Job_Manager_Applications' ) ) {
			unset( $this->settings['resume_application'][1][1] );
		}
	}

	/**
	 * Outputs the capabilities or roles input field.
	 *
	 * @param array    $option              Option arguments for settings input.
	 * @param string[] $attributes          Attributes on the HTML element. Strings must already be escaped.
	 * @param mixed    $value               Current value.
	 * @param string   $ignored_placeholder We set the placeholder in the method. This is ignored.
	 */
	protected function input_capabilities( $option, $attributes, $value, $ignored_placeholder ) {
		$value                 = self::capabilities_string_to_array( $value );
		$option['options']     = self::get_capabilities_and_roles( $value );
		$option['placeholder'] = esc_html__( 'Everyone (Public)', 'wp-job-manager-resumes' );

		?>
		<select
			id="setting-<?php echo esc_attr( $option['name'] ); ?>"
			class="regular-text settings-role-select"
			name="<?php echo esc_attr( $option['name'] ); ?>[]"
			multiple="multiple"
			data-placeholder="<?php echo esc_attr( $option['placeholder'] ); ?>"
			<?php
			echo implode( ' ', $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		>
			<?php
			foreach ( $option['options'] as $key => $name ) {
				echo '<option value="' . esc_attr( $key ) . '" ' . selected( in_array( $key, $value, true ) ? $key : null, $key, false ) . '>' . esc_html( $name ) . '</option>';
			}
			?>
		</select>
		<?php

		if ( ! empty( $option['desc'] ) ) {
			echo ' <p class="description">' . wp_kses_post( $option['desc'] ) . '</p>';
		}
	}

	/**
	 * Role settings should be saved as a comma-separated list.
	 */
	public function pre_process_settings_save() {
		$screen = get_current_screen();

		if ( ! $screen || 'options' !== $screen->id ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Settings save will handle the nonce check.
		if ( ! isset( $_POST['option_page'] ) || 'wp-job-manager-resumes' !== $_POST['option_page'] ) {
			return;
		}

		$capabilities_fields = [
			'resume_manager_view_name_capability',
			'resume_manager_browse_resume_capability',
			'resume_manager_view_resume_capability',
			'resume_manager_contact_resume_capability',
		];
		foreach ( $capabilities_fields as $capabilities_field ) {
			// phpcs:disable WordPress.Security.NonceVerification.Missing -- Settings save will handle the nonce check.
			if ( isset( $_POST[ $capabilities_field ] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by `WP_Resume_Manager_Settings::capabilities_array_to_string()`
				$input_capabilities_field_value = wp_unslash( $_POST[ $capabilities_field ] );
				if ( is_array( $input_capabilities_field_value ) ) {
					$_POST[ $capabilities_field ] = self::capabilities_array_to_string( $input_capabilities_field_value );
				}
			}
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}
	}

	/**
	 * Convert list of capabilities and roles into array of values.
	 *
	 * @param string $value Comma separated list of capabilities and roles.
	 * @return array
	 */
	private static function capabilities_string_to_array( $value ) {
		return array_filter(
			array_map(
				function( $value ) {
					return trim( sanitize_text_field( $value ) );
				},
				explode( ',', $value )
			)
		);
	}

	/**
	 * Convert array of capabilities and roles into a comma separated list.
	 *
	 * @param array $value Array of capabilities and roles.
	 * @return string
	 */
	private static function capabilities_array_to_string( $value ) {
		if ( ! is_array( $value ) ) {
			return '';
		}

		$caps = array_filter( array_map( 'sanitize_text_field', $value ) );

		return implode( ',', $caps );
	}

	/**
	 * Get the list of roles and capabilities to use in select dropdown.
	 *
	 * @param array $caps Selected capabilities to ensure they show up in the list.
	 * @return array
	 */
	private static function get_capabilities_and_roles( $caps = [] ) {
		$capabilities_and_roles = [];
		$roles                  = get_editable_roles();

		foreach ( $roles as $key => $role ) {
			$capabilities_and_roles[ $key ] = $role['name'];
		}

		// Go through custom user selected capabilities and add them to the list.
		foreach ( $caps as $value ) {
			if ( isset( $capabilities_and_roles[ $value ] ) ) {
				continue;
			}
			$capabilities_and_roles[ $value ] = $value;
		}

		return $capabilities_and_roles;
	}


	/**
	 * Get the logo for the page.
	 *
	 * @return string
	 */
	protected function get_logo(): string {
		return '<img class="job-manager-settings-logo"
					src="' . esc_url( RESUME_MANAGER_PLUGIN_URL . '/assets/images/settings-logo.png' ) . '"
					alt="' . esc_attr__( 'Job Manager', 'wp-job-manager' ) . '" />';
	}

}
