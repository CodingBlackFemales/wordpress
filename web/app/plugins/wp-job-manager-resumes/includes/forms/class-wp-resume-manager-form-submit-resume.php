<?php
/**
 * File containing the WP_Resume_Manager_Form_Submit_Resume.
 *
 * @package wp-job-manager-resumes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Resume_Manager_Form_Submit_Resume class.
 */
class WP_Resume_Manager_Form_Submit_Resume extends WP_Job_Manager_Form {

	/**
	 * Form name slug.
	 *
	 * @var string
	 */
	public $form_name = 'submit-resume';

	/**
	 * Current resume ID.
	 *
	 * @var int
	 */
	protected $resume_id;

	/**
	 * Job ID if we're submitting resume for a specific job.
	 *
	 * @var int
	 */
	protected $job_id;

	/**
	 * The single instance of the class.
	 *
	 * @var WP_Resume_Manager_Form_Submit_Resume
	 */
	protected static $instance = null;

	/**
	 * Main Instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp', [ $this, 'process' ] );
		add_action( 'submit_resume_form_start', [ $this, 'output_submit_form_nonce_field' ] );
		add_action( 'preview_resume_form_start', [ $this, 'output_preview_form_nonce_field' ] );

		if ( $this->use_agreement_checkbox() ) {
			add_action( 'submit_resume_form_resume_fields_end', [ $this, 'display_agreement_checkbox_field' ] );
			add_filter( 'submit_resume_form_validate_fields', [ $this, 'validate_agreement_checkbox' ] );
		}

		if ( class_exists( 'WP_Job_Manager\WP_Job_Manager_Recaptcha' ) ) {
			WP_Job_Manager\WP_Job_Manager_Recaptcha::instance()->maybe_enable_recaptcha(
				'resume_manager_enable_recaptcha_resume_submission',
				[ 'submit_resume_form_resume_fields_end' ],
				[ 'submit_resume_form_validate_fields' ]
			);
		} else {
			// This path will be followed for WPJM versions pre-2.2.0.
			add_action( 'submit_resume_form_resume_fields_end', [ $this, 'display_recaptcha_field' ] );
			add_action( 'submit_resume_form_validate_fields', [ $this, 'validate_recaptcha_field' ] );
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		}

		$this->steps = (array) apply_filters(
			'submit_resume_steps',
			[
				'submit'  => [
					'name'     => __( 'Submit Details', 'wp-job-manager-resumes' ),
					'view'     => [ $this, 'submit' ],
					'handler'  => [ $this, 'submit_handler' ],
					'priority' => 10,
				],
				'preview' => [
					'name'     => __( 'Preview', 'wp-job-manager-resumes' ),
					'view'     => [ $this, 'preview' ],
					'handler'  => [ $this, 'preview_handler' ],
					'priority' => 20,
				],
				'done'    => [
					'name'     => __( 'Done', 'wp-job-manager-resumes' ),
					'view'     => [ $this, 'done' ],
					'handler'  => '',
					'priority' => 30,
				],
			]
		);

		uasort( $this->steps, [ $this, 'sort_by_priority' ] );

		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Input is used safely.
		// Get step/job.
		if ( isset( $_REQUEST['step'] ) ) {
			$this->step = is_numeric( $_REQUEST['step'] ) ? max( absint( $_REQUEST['step'] ), 0 ) : array_search( sanitize_text_field( $_REQUEST['step'] ), array_keys( $this->steps ), true );
		}

		$this->job_id    = ! empty( $_REQUEST['job_id'] ) ? absint( $_REQUEST['job_id'] ) : 0;
		$this->resume_id = ! empty( $_REQUEST['resume_id'] ) ? absint( $_REQUEST['resume_id'] ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

		if ( ! resume_manager_user_can_edit_resume( $this->resume_id ) ) {
			$this->resume_id = 0;
		}

		// Load resume details.
		if ( $this->resume_id ) {
			$resume_status = get_post_status( $this->resume_id );
			if ( 'expired' === $resume_status ) {
				if ( ! resume_manager_user_can_edit_resume( $this->resume_id ) ) {
					$this->resume_id = 0;
					$this->job_id    = 0;
					$this->step      = 0;
				}
			} elseif (
				0 === $this->step
				&& ! in_array( $resume_status, apply_filters( 'resume_manager_valid_submit_resume_statuses', [ 'preview' ] ), true )
				&& empty( $_POST['resume_application_submit_button'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Safe use of input.
			) {
				$this->resume_id = 0;
				$this->job_id    = 0;
				$this->step      = 0;
			}
		}

		// Clear job ID if it isn't a published job.
		if (
			empty( $this->job_id )
			|| 'job_listing' !== get_post_type( $this->job_id )
			|| 'publish' !== get_post_status( $this->job_id )
		) {
			$this->job_id = 0;
		}
	}

	/**
	 * Get the submitted resume ID.
	 *
	 * @return int
	 */
	public function get_resume_id() {
		return absint( $this->resume_id );
	}

	/**
	 * Get the job ID if applying.
	 *
	 * @return int
	 */
	public function get_job_id() {
		return absint( $this->job_id );
	}

	/**
	 * Get a field from either resume manager or job manager. Used by `resume-submit.php`
	 * and `form-fields/repeated-field.php` templates.
	 *
	 * @param string $key   Name of field.
	 * @param array  $field Configuration arguments for the field.
	 */
	public function get_field_template( $key, $field ) {
		switch ( $field['type'] ) {
			case 'repeated':
			case 'education':
			case 'experience':
			case 'links':
				get_job_manager_template(
					'form-fields/repeated-field.php',
					[
						'key'   => $key,
						'field' => $field,
						'class' => $this,
					],
					'wp-job-manager-resumes',
					RESUME_MANAGER_PLUGIN_DIR . '/templates/'
				);
				break;
			default:
				get_job_manager_template(
					'form-fields/' . $field['type'] . '-field.php',
					[
						'key'   => $key,
						'field' => $field,
						'class' => $this,
					]
				);
				break;
		}
	}

	/**
	 * Initialize fields.
	 */
	public function init_fields() {
		if ( $this->fields ) {
			return;
		}

		$max_skills        = get_option( 'resume_manager_max_skills' );
		$max_skills_notice = null;
		if ( $max_skills ) {
			// translators: Placeholder %d is the maximum number of skills a visitor can add.
			$max_skills_notice = ' ' . sprintf( __( 'Maximum of %d.', 'wp-job-manager-resumes' ), $max_skills );
		}

		$this->fields = apply_filters(
			'submit_resume_form_fields',
			[
				'resume_fields' => [
					'candidate_name'       => [
						'label'         => __( 'Your name', 'wp-job-manager-resumes' ),
						'type'          => 'text',
						'required'      => true,
						'placeholder'   => __( 'Your full name', 'wp-job-manager-resumes' ),
						'priority'      => 1,
						'personal_data' => true,
					],
					'candidate_email'      => [
						'label'         => __( 'Your email', 'wp-job-manager-resumes' ),
						'type'          => 'text',
						'required'      => true,
						'placeholder'   => __( 'you@yourdomain.com', 'wp-job-manager-resumes' ),
						'priority'      => 2,
						'personal_data' => true,
					],
					'candidate_title'      => [
						'label'         => __( 'Professional title', 'wp-job-manager-resumes' ),
						'type'          => 'text',
						'required'      => true,
						'placeholder'   => __( 'e.g. "Web Developer"', 'wp-job-manager-resumes' ),
						'priority'      => 3,
						'personal_data' => true,
					],
					'candidate_location'   => [
						'label'         => __( 'Location', 'wp-job-manager-resumes' ),
						'type'          => 'text',
						'required'      => true,
						'placeholder'   => __( 'e.g. "London, UK", "New York", "Houston, TX"', 'wp-job-manager-resumes' ),
						'priority'      => 4,
						'personal_data' => true,
					],
					'candidate_photo'      => [
						'label'              => __( 'Photo', 'wp-job-manager-resumes' ),
						'type'               => 'file',
						'required'           => false,
						'placeholder'        => '',
						'priority'           => 5,
						'ajax'               => true,
						'allowed_mime_types' => [
							'jpg'  => 'image/jpeg',
							'jpeg' => 'image/jpeg',
							'gif'  => 'image/gif',
							'png'  => 'image/png',
						],
						'personal_data'      => true,
					],
					'candidate_video'      => [
						'label'         => __( 'Video', 'wp-job-manager-resumes' ),
						'type'          => 'text',
						'required'      => false,
						'priority'      => 6,
						'placeholder'   => __( 'A link to a video about yourself', 'wp-job-manager-resumes' ),
						'personal_data' => true,
					],
					'resume_category'      => [
						'label'         => __( 'Resume category', 'wp-job-manager-resumes' ),
						'type'          => 'term-multiselect',
						'taxonomy'      => 'resume_category',
						'required'      => true,
						'placeholder'   => '',
						'priority'      => 7,
						'personal_data' => true,
					],
					'resume_content'       => [
						'label'         => __( 'Resume Content', 'wp-job-manager-resumes' ),
						'type'          => 'wp-editor',
						'required'      => true,
						'placeholder'   => '',
						'priority'      => 8,
						'personal_data' => true,
					],
					'resume_skills'        => [
						'label'         => __( 'Skills', 'wp-job-manager-resumes' ),
						'type'          => 'text',
						'required'      => false,
						'placeholder'   => __( 'Comma separate a list of relevant skills', 'wp-job-manager-resumes' ) . $max_skills_notice,
						'priority'      => 9,
						'personal_data' => true,
					],
					'links'                => [
						'label'         => __( 'URL(s)', 'wp-job-manager-resumes' ),
						'add_row'       => __( 'Add URL', 'wp-job-manager-resumes' ),
						'type'          => 'links', // Repeated field.
						'required'      => false,
						'placeholder'   => '',
						'description'   => __( 'Optionally provide links to any of your websites or social network profiles.', 'wp-job-manager-resumes' ),
						'priority'      => 10,
						'fields'        => [
							'name' => [
								'label'       => __( 'Name', 'wp-job-manager-resumes' ),
								'type'        => 'text',
								'required'    => true,
								'placeholder' => '',
								'priority'    => 1,
							],
							'url'  => [
								'label'       => __( 'URL', 'wp-job-manager-resumes' ),
								'type'        => 'text',
								'sanitizer'   => 'esc_url_raw',
								'required'    => true,
								'placeholder' => '',
								'priority'    => 2,
							],
						],
						'personal_data' => true,
					],
					'candidate_education'  => [
						'label'         => __( 'Education', 'wp-job-manager-resumes' ),
						'add_row'       => __( 'Add Education', 'wp-job-manager-resumes' ),
						'type'          => 'education', // Repeated field.
						'required'      => false,
						'placeholder'   => '',
						'priority'      => 11,
						'fields'        => [
							'location'      => [
								'label'       => __( 'Institution', 'wp-job-manager-resumes' ),
								'type'        => 'text',
								'required'    => true,
								'placeholder' => '',
							],
							'qualification' => [
								'label'       => __( 'Certification(s)', 'wp-job-manager-resumes' ),
								'type'        => 'text',
								'required'    => true,
								'placeholder' => '',
							],
							'date'          => [
								'label'       => __( 'Start/end date', 'wp-job-manager-resumes' ),
								'type'        => 'text',
								'required'    => true,
								'placeholder' => '',
							],
							'notes'         => [
								'label'       => __( 'Notes', 'wp-job-manager-resumes' ),
								'type'        => 'textarea',
								'required'    => false,
								'placeholder' => '',
							],
						],
						'personal_data' => true,
					],
					'candidate_experience' => [
						'label'         => __( 'Experience', 'wp-job-manager-resumes' ),
						'add_row'       => __( 'Add Experience', 'wp-job-manager-resumes' ),
						'type'          => 'experience', // Repeated field.
						'required'      => false,
						'placeholder'   => '',
						'priority'      => 12,
						'fields'        => [
							'employer'  => [
								'label'       => __( 'Employer', 'wp-job-manager-resumes' ),
								'type'        => 'text',
								'required'    => true,
								'placeholder' => '',
							],
							'job_title' => [
								'label'       => __( 'Job Title', 'wp-job-manager-resumes' ),
								'type'        => 'text',
								'required'    => true,
								'placeholder' => '',
							],
							'date'      => [
								'label'       => __( 'Start/end date', 'wp-job-manager-resumes' ),
								'type'        => 'text',
								'required'    => true,
								'placeholder' => '',
							],
							'notes'     => [
								'label'       => __( 'Notes', 'wp-job-manager-resumes' ),
								'type'        => 'textarea',
								'required'    => false,
								'placeholder' => '',
							],
						],
						'personal_data' => true,
					],
					'resume_file'          => [
						'label'         => __( 'Resume file', 'wp-job-manager-resumes' ),
						'type'          => 'file',
						'required'      => false,
						'ajax'          => true,
						// translators: Placeholder %s is the maximum file size of the upload.
						'description'   => sprintf( __( 'Optionally upload your resume for employers to view. Max. file size: %s.', 'wp-job-manager-resumes' ), size_format( wp_max_upload_size() ) ),
						'priority'      => 13,
						'placeholder'   => '',
						'personal_data' => true,
					],
				],
			]
		);

		if ( ! get_option( 'resume_manager_enable_resume_upload' ) ) {
			unset( $this->fields['resume_fields']['resume_file'] );
		}

		if ( ! get_option( 'resume_manager_enable_categories' ) || 0 === wp_count_terms( 'resume_category' ) ) {
			unset( $this->fields['resume_fields']['resume_category'] );
		}

		if ( ! get_option( 'resume_manager_enable_skills' ) ) {
			unset( $this->fields['resume_fields']['resume_skills'] );
		}
	}

	/**
	 * Reset the `fields` variable so it gets reinitialized. This should only be
	 * used for testing!
	 */
	public function reset_fields() {
		$this->fields = null;
	}

	/**
	 * Get the value of a repeated fields (e.g. education, links).
	 *
	 * @param string $field_prefix Prefix added to the field names.
	 * @param array  $fields       List of the fields to be repeated.
	 * @return array
	 */
	public function get_repeated_field( $field_prefix, $fields ) {
		$items = [];

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Safe use of input and sanitized below.
		$input_repeated_row = ! empty( $_POST[ 'repeated-row-' . $field_prefix ] ) ? wp_unslash( $_POST[ 'repeated-row-' . $field_prefix ] ) : false;

		if ( $input_repeated_row && is_array( $input_repeated_row ) ) {
			// Sanitize the input "repeated-row-{$field_prefix}" from above.
			$indexes = array_map( 'absint', $input_repeated_row );

			foreach ( $indexes as $index ) {
				$item = [];
				foreach ( $fields as $key => $field ) {
					$field_name = $field_prefix . '_' . $key . '_' . $index;
					// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Input sanitized below. Nonce check in standard edit/submit flows.
					$input_field_value = isset( $_POST[ $field_name ] ) ? wp_unslash( $_POST[ $field_name ] ) : null;

					switch ( $field['type'] ) {
						case 'textarea':
							// Sanitize text area input.
							$item[ $key ] = wp_kses_post( $input_field_value );
							break;
						case 'file':
							try {
								$file = $this->upload_file( $field_name, $field );
							} catch ( Exception $e ) {
								$file = false;
							}

							// Fetch and sanitize file input using `\WP_Job_Manager_Form::get_posted_field()`.
							if ( ! $file ) {
								$file = $this->get_posted_field( 'current_' . $field_name, $field );
							} elseif ( is_array( $file ) ) {
								$file = array_filter( array_merge( $file, (array) $this->get_posted_field( 'current_' . $field_name, $field ) ) );
							}

							$item[ $key ] = $file;
							break;
						default:
							$sanitize_callback = 'sanitize_text_field';

							if ( isset( $field['sanitizer'] ) ) {
								$sanitize_callback = $field['sanitizer'];
							}

							// Fetch and sanitize all other input.
							if ( is_array( $input_field_value ) ) {
								$item[ $key ] = array_filter( array_map( $sanitize_callback, $input_field_value ) );
							} else {
								$item[ $key ] = call_user_func( $sanitize_callback, $input_field_value );
							}
							break;
					}
					if ( empty( $item[ $key ] ) && ! empty( $field['required'] ) ) {
						continue 2;
					}
				}
				$items[] = $item;
			}
		}
		return $items;
	}

	/**
	 * Use reCAPTCHA field on the form?
	 *
	 * @deprecated This method should now be used only by WPJM versions pre-2.2.0. After 2.2.0
	 *             all functionality moved to WP_Job_Manager_Recaptcha.
	 *
	 * @return bool
	 */
	public function use_recaptcha_field() {
		$recaptcha_available = $this->is_recaptcha_available();

		if ( ! $recaptcha_available ) {
			return false;
		}

		return 1 === absint( get_option( 'resume_manager_enable_recaptcha_resume_submission' ) );
	}

	/**
	 * Use agreement checkbox field on the form?
	 *
	 * @since 1.18.5
	 *
	 * @return bool
	 */
	private function use_agreement_checkbox() {
		if ( ! method_exists( $this, 'display_agreement_checkbox_field' ) || ! method_exists( $this, 'validate_agreement_checkbox' ) ) {
			return false;
		}

		return 1 === absint( get_option( 'resume_manager_show_agreement_resume_submission' ) );
	}

	/**
	 * Get the value of a posted repeated field
	 *
	 * @since  1.22.4
	 * @param  string $key
	 * @param  array  $field
	 * @return string
	 */
	public function get_posted_repeated_field( $key, $field ) {
		return apply_filters( 'submit_resume_form_fields_get_repeated_field_data', $this->get_repeated_field( $key, $field['fields'] ) );
	}

	/**
	 * Get the value of a posted file field
	 *
	 * @param  string $key
	 * @param  array  $field
	 * @return string
	 */
	public function get_posted_links_field( $key, $field ) {
		return apply_filters( 'submit_resume_form_fields_get_links_data', $this->get_repeated_field( $key, $field['fields'] ) );
	}

	/**
	 * Get the value of a posted file field.
	 *
	 * @param  string $key
	 * @param  array  $field
	 * @return string
	 */
	public function get_posted_education_field( $key, $field ) {
		return apply_filters( 'submit_resume_form_fields_get_education_data', $this->get_repeated_field( $key, $field['fields'] ) );
	}

	/**
	 * Get the value of a posted file field.
	 *
	 * @param  string $key
	 * @param  array  $field
	 * @return string
	 */
	public function get_posted_experience_field( $key, $field ) {
		return apply_filters( 'submit_resume_form_fields_get_experience_data', $this->get_repeated_field( $key, $field['fields'] ) );
	}

	/**
	 * Validate the posted fields.
	 *
	 * @param array $values Input values submitted.
	 * @return WP_Error|bool
	 * @throws Exception During validation error.
	 */
	protected function validate_fields( $values ) {
		foreach ( $this->fields as $group_key => $fields ) {
			foreach ( $fields as $key => $field ) {
				if ( $field['required'] && empty( $values[ $group_key ][ $key ] ) ) {
					// translators: Placeholder %s is the name of the required field.
					return new WP_Error( 'validation-error', sprintf( __( '%s is a required field', 'wp-job-manager-resumes' ), $field['label'] ) );
				}
				if ( ! empty( $field['taxonomy'] ) && in_array( $field['type'], [ 'term-checklist', 'term-select', 'term-multiselect' ], true ) ) {
					if ( is_array( $values[ $group_key ][ $key ] ) ) {
						foreach ( $values[ $group_key ][ $key ] as $term ) {
							if ( ! term_exists( $term, $field['taxonomy'] ) ) {
								// translators: Placeholder %s is the name of the invalid field.
								return new WP_Error( 'validation-error', sprintf( __( '%s is invalid', 'wp-job-manager-resumes' ), $field['label'] ) );
							}
						}
					} elseif ( ! empty( $values[ $group_key ][ $key ] ) ) {
						if ( ! term_exists( $values[ $group_key ][ $key ], $field['taxonomy'] ) ) {
							// translators: Placeholder %s is the name of the invalid field.
							return new WP_Error( 'validation-error', sprintf( __( '%s is invalid', 'wp-job-manager-resumes' ), $field['label'] ) );
						}
					}
				}

				if ( 'candidate_email' === $key ) {
					if ( ! empty( $values[ $group_key ][ $key ] ) && ! is_email( $values[ $group_key ][ $key ] ) ) {
						throw new Exception( __( 'Please enter a valid email address', 'wp-job-manager-resumes' ) );
					}
				}

				if ( 'resume_skills' === $key ) {
					if ( is_string( $values[ $group_key ][ $key ] ) ) {
						$raw_skills = explode( ',', $values[ $group_key ][ $key ] );
					} else {
						$raw_skills = $values[ $group_key ][ $key ];
					}
					$max = get_option( 'resume_manager_max_skills' );

					if ( $max && count( $raw_skills ) > $max ) {
						// translators: Placeholder %d is the maximum number of skills they can enter.
						return new WP_Error( 'validation-error', sprintf( __( 'Please enter no more than %d skills.', 'wp-job-manager-resumes' ), $max ) );
					}
				}

				if ( 'file' === $field['type'] ) {
					if ( is_array( $values[ $group_key ][ $key ] ) ) {
						$check_value = array_filter( $values[ $group_key ][ $key ] );
					} else {
						$check_value = array_filter( [ $values[ $group_key ][ $key ] ] );
					}
					if ( ! empty( $check_value ) ) {
						foreach ( $check_value as $file_url ) {
							if ( is_numeric( $file_url ) ) {
								continue;
							}
							$file_url = esc_url( $file_url, [ 'http', 'https' ] );
							if ( empty( $file_url ) ) {
								throw new Exception( __( 'Invalid attachment provided.', 'wp-job-manager-resumes' ) );
							}
						}
					}
				}
			}
		}

		return apply_filters( 'submit_resume_form_validate_fields', true, $this->fields, $values );
	}

	/**
	 * Submit Step
	 */
	public function submit() {
		$this->init_fields();

		// Load data if neccessary.
		if ( $this->resume_id ) {
			$resume = get_post( $this->resume_id );
			foreach ( $this->fields as $group_key => $fields ) {
				foreach ( $fields as $key => $field ) {
					switch ( $key ) {
						case 'candidate_name':
							$this->fields[ $group_key ][ $key ]['value'] = $resume->post_title;
							break;
						case 'resume_content':
							$this->fields[ $group_key ][ $key ]['value'] = $resume->post_content;
							break;
						case 'resume_skills':
							$this->fields[ $group_key ][ $key ]['value'] = implode( ', ', wp_get_object_terms( $resume->ID, 'resume_skill', [ 'fields' => 'names' ] ) );
							break;
						case 'resume_category':
							$this->fields[ $group_key ][ $key ]['value'] = wp_get_object_terms( $resume->ID, 'resume_category', [ 'fields' => 'ids' ] );
							break;
						default:
							$this->fields[ $group_key ][ $key ]['value'] = get_post_meta( $resume->ID, '_' . $key, true );
							break;
					}
				}
			}
			$this->fields = apply_filters( 'submit_resume_form_fields_get_resume_data', $this->fields, $resume );
		} elseif (
			is_user_logged_in()
			&& empty( $_POST['submit_resume'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Using input safely.
		) {
			$user = wp_get_current_user();
			foreach ( $this->fields as $group_key => $fields ) {
				foreach ( $fields as $key => $field ) {
					switch ( $key ) {
						case 'candidate_name':
							$this->fields[ $group_key ][ $key ]['value'] = $user->first_name . ' ' . $user->last_name;
							break;
						case 'candidate_email':
							$this->fields[ $group_key ][ $key ]['value'] = $user->user_email;
							break;
					}
				}
			}
			$this->fields = apply_filters( 'submit_resume_form_fields_get_user_data', $this->fields, get_current_user_id() );
		}

		get_job_manager_template(
			'resume-submit.php',
			[
				'class'              => $this,
				'form'               => $this->form_name,
				'resume_id'          => $this->get_resume_id(),
				'job_id'             => $this->get_job_id(),
				'action'             => $this->get_action(),
				'resume_fields'      => $this->get_fields( 'resume_fields' ),
				'step'               => $this->get_step(),
				'submit_button_text' => apply_filters( 'submit_resume_form_submit_button_text', __( 'Preview &rarr;', 'wp-job-manager-resumes' ) ),
			],
			'wp-job-manager-resumes',
			RESUME_MANAGER_PLUGIN_DIR . '/templates/'
		);
	}

	/**
	 * Submit Step is posted.
	 */
	public function submit_handler() {
		try {

			// Init fields.
			$this->init_fields();

			// Get posted values.
			$values = $this->get_posted_fields();

			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Check happens later when possible.
			if ( empty( $_POST['submit_resume'] ) ) {
				return;
			}

			$this->check_submit_form_nonce_field();

			// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce checked above when possible.
			$input_create_account_username        = isset( $_POST['create_account_username'] ) ? sanitize_text_field( wp_unslash( $_POST['create_account_username'] ) ) : false;
			$input_create_account_password        = isset( $_POST['create_account_password'] ) ? sanitize_text_field( wp_unslash( $_POST['create_account_password'] ) ) : false;
			$input_create_account_password_verify = isset( $_POST['create_account_password_verify'] ) ? sanitize_text_field( wp_unslash( $_POST['create_account_password_verify'] ) ) : false;
			$input_create_account_email           = isset( $_POST['candidate_email'] ) ? sanitize_text_field( wp_unslash( $_POST['candidate_email'] ) ) : false;
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			// Validate required.
			$validation_result = $this->validate_fields( $values );
			if ( is_wp_error( ( $validation_result ) ) ) {
				throw new Exception( $validation_result->get_error_message() );
			}

			// Account creation.
			if ( ! is_user_logged_in() ) {
				$create_account = false;

				if ( resume_manager_enable_registration() ) {
					if ( resume_manager_user_requires_account() ) {
						if ( ! resume_manager_generate_username_from_email() && empty( $input_create_account_username ) ) {
							throw new Exception( __( 'Please enter a username.', 'wp-job-manager-resumes' ) );
						}
						if ( ! resume_manager_use_standard_password_setup_email() ) {
							if ( empty( $input_create_account_password ) ) {
								throw new Exception( __( 'Please enter a password.', 'wp-job-manager-resumes' ) );
							}
						}
						if ( empty( $input_create_account_email ) ) {
							throw new Exception( __( 'Please enter your email address.', 'wp-job-manager-resumes' ) );
						}
					}

					if ( ! resume_manager_use_standard_password_setup_email() && ! empty( $input_create_account_password ) ) {
						if ( empty( $input_create_account_password_verify ) || $input_create_account_password_verify !== $input_create_account_password ) {
							throw new Exception( __( 'Passwords must match.', 'wp-job-manager-resumes' ) );
						}
						if ( ! wpjm_validate_new_password( $input_create_account_password ) ) {
							$password_hint = wpjm_get_password_rules_hint();
							if ( $password_hint ) {
								// translators: Placeholder %s is password hint.
								throw new Exception( sprintf( __( 'Invalid Password: %s', 'wp-job-manager-resumes' ), $password_hint ) );
							} else {
								throw new Exception( __( 'Password is not valid.', 'wp-job-manager-resumes' ) );
							}
						}
					}

					if ( ! empty( $input_create_account_email ) ) {
						if ( version_compare( JOB_MANAGER_VERSION, '1.20.0', '<' ) ) {
							$create_account = wp_job_manager_create_account( $input_create_account_email, get_option( 'resume_manager_registration_role', 'candidate' ) );
						} else {
							$create_account = wp_job_manager_create_account(
								[
									'username' => ( resume_manager_generate_username_from_email() || empty( $input_create_account_username ) ) ? '' : $input_create_account_username,
									'password' => ( resume_manager_use_standard_password_setup_email() || empty( $input_create_account_password ) ) ? '' : $input_create_account_password,
									'email'    => $input_create_account_email,
									'role'     => get_option( 'resume_manager_registration_role', 'candidate' ),
								]
							);
						}
					}
				}

				if ( is_wp_error( $create_account ) ) {
					throw new Exception( $create_account->get_error_message() );
				}
			}

			if ( resume_manager_user_requires_account() && ! is_user_logged_in() ) {
				throw new Exception( __( 'You must be signed in to post your resume.', 'wp-job-manager-resumes' ) );
			}

			// Update the job.
			$this->save_resume( $values['resume_fields']['candidate_name'], $values['resume_fields']['resume_content'], $this->resume_id ? '' : 'preview', $values );
			$this->update_resume_data( $values );

			// Successful, show next step.
			$this->step ++;

		} catch ( Exception $e ) {
			$this->add_error( $e->getMessage() );
			return;
		}
	}

	/**
	 * Update or create a job listing from posted data.
	 *
	 * @param string $post_title   Post title.
	 * @param string $post_content Post content.
	 * @param string $status       Post status to save.
	 * @param array  $values       Values from the form.
	 */
	protected function save_resume( $post_title, $post_content, $status = 'preview', $values = [] ) {
		// Get random key.
		if ( $this->resume_id ) {
			$prefix = get_post_meta( $this->resume_id, '_resume_name_prefix', true );

			if ( ! $prefix ) {
				$prefix = wp_generate_password( 10 );
			}
		} else {
			$prefix = wp_generate_password( 10 );
		}

		$resume_slug   = [];
		$resume_slug[] = current( explode( ' ', $post_title ) );
		$resume_slug[] = $prefix;

		if ( ! empty( $values['resume_fields']['candidate_title'] ) ) {
			$resume_slug[] = $values['resume_fields']['candidate_title'];
		}

		if ( ! empty( $values['resume_fields']['candidate_location'] ) ) {
			$resume_slug[] = $values['resume_fields']['candidate_location'];
		}

		$data = [
			'post_title'     => $post_title,
			'post_content'   => $post_content,
			'post_type'      => 'resume',
			'comment_status' => 'closed',
			'post_password'  => '',
			'post_name'      => sanitize_title( implode( '-', $resume_slug ) ),
		];

		if ( $status ) {
			$data['post_status'] = $status;
		}

		$data = apply_filters( 'submit_resume_form_save_resume_data', $data, $post_title, $post_content, $status, $values, $this );

		if ( $this->resume_id ) {
			$data['ID'] = $this->resume_id;
			wp_update_post( $data );
		} else {
			$this->resume_id = wp_insert_post( $data );
			update_post_meta( $this->resume_id, '_resume_name_prefix', $prefix );
			update_post_meta( $this->resume_id, '_public_submission', true );

			// If and only if we're dealing with a logged out user and that is allowed, allow the user to continue a submission after it was started.
			if ( ! is_user_logged_in() && ! resume_manager_user_requires_account() ) {
				$submitting_key = sha1( uniqid() );
				setcookie( 'wp-job-manager-submitting-resume-key-' . $this->resume_id, $submitting_key, 0, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
				update_post_meta( $this->resume_id, '_submitting_key', $submitting_key );
			}

			// Save profile fields.
			$current_user   = wp_get_current_user();
			$candidate_name = explode( ' ', $post_title );

			if ( empty( $current_user->first_name ) && empty( $current_user->last_name ) && count( $candidate_name ) > 1 ) {
				wp_update_user(
					[
						'ID'         => $current_user->ID,
						'first_name' => current( $candidate_name ),
						'last_name'  => end( $candidate_name ),
					]
				);
			}
		}
	}

	/**
	 * Set job meta + terms based on posted values
	 *
	 * @param  array $values
	 */
	protected function update_resume_data( $values ) {
		// Set defaults.
		add_post_meta( $this->resume_id, '_featured', 0, true );
		add_post_meta( $this->resume_id, '_applying_for_job_id', $this->job_id, true );

		// Reset submission lifecycle flag.
		delete_post_meta( $this->resume_id, '_submission_finalized' );

		$maybe_attach = [];

		// Loop fields and save meta and term data.
		foreach ( $this->fields as $group_key => $group_fields ) {
			foreach ( $group_fields as $key => $field ) {
				// Save taxonomies.
				if ( ! empty( $field['taxonomy'] ) ) {
					if ( is_array( $values[ $group_key ][ $key ] ) ) {
						wp_set_object_terms( $this->resume_id, $values[ $group_key ][ $key ], $field['taxonomy'], false );
					} else {
						wp_set_object_terms( $this->resume_id, [ $values[ $group_key ][ $key ] ], $field['taxonomy'], false );
					}

					// Save meta data.
				} else {
					update_post_meta( $this->resume_id, '_' . $key, $values[ $group_key ][ $key ] );
				}

				// Handle attachments.
				if ( 'file' === $field['type'] ) {
					// Must be absolute.
					if ( is_array( $values[ $group_key ][ $key ] ) ) {
						foreach ( $values[ $group_key ][ $key ] as $file_url ) {
							$maybe_attach[] = str_replace( [ WP_CONTENT_URL, site_url() ], [ WP_CONTENT_DIR, ABSPATH ], $file_url );
						}
					} else {
						$maybe_attach[] = str_replace( [ WP_CONTENT_URL, site_url() ], [ WP_CONTENT_DIR, ABSPATH ], $values[ $group_key ][ $key ] );
					}
				}
			}
		}

		if ( get_option( 'resume_manager_enable_skills' ) && isset( $values['resume_fields']['resume_skills'] ) ) {

			$tags     = [];
			$raw_tags = $values['resume_fields']['resume_skills'];

			if ( is_string( $raw_tags ) ) {
				// Explode and clean.
				$raw_tags = array_filter( array_map( 'sanitize_text_field', explode( ',', $raw_tags ) ) );

				if ( ! empty( $raw_tags ) ) {
					foreach ( $raw_tags as $tag ) {
						$term = get_term_by( 'name', $tag, 'resume_skill' );
						if ( $term ) {
							$tags[] = $term->term_id;
						} else {
							$term = wp_insert_term( $tag, 'resume_skill' );

							if ( ! is_wp_error( $term ) ) {
								$tags[] = $term['term_id'];
							}
						}
					}
				}
			} else {
				$tags = array_map( 'absint', $raw_tags );
			}

			wp_set_object_terms( $this->resume_id, $tags, 'resume_skill', false );
		}

		// Handle attachments.
		if ( count( $maybe_attach ) && resume_manager_attach_uploaded_files() ) {
			/** WordPress Administration Image API */
			include_once ABSPATH . 'wp-admin/includes/image.php';

			// Get attachments.
			$attachments     = get_posts( 'post_parent=' . $this->resume_id . '&post_type=attachment&fields=ids&post_mime_type=image&numberposts=-1' );
			$attachment_urls = [];

			// Loop attachments already attached to the job.
			foreach ( $attachments as $attachment_key => $attachment ) {
				$attachment_urls[] = str_replace( [ WP_CONTENT_URL, site_url() ], [ WP_CONTENT_DIR, ABSPATH ], wp_get_attachment_url( $attachment ) );
			}

			foreach ( $maybe_attach as $attachment_url ) {
				$attachment_url = esc_url( $attachment_url, [ 'http', 'https' ] );

				if ( empty( $attachment_url ) ) {
					continue;
				}

				if ( ! in_array( $attachment_url, $attachment_urls, true ) ) {
					$attachment = [
						'post_title'   => get_the_title( $this->resume_id ),
						'post_content' => '',
						'post_status'  => 'inherit',
						'post_parent'  => $this->resume_id,
						'guid'         => $attachment_url,
					];

					$info = wp_check_filetype( $attachment_url );
					if ( $info ) {
						$attachment['post_mime_type'] = $info['type'];
					}

					$attachment_id = wp_insert_attachment( $attachment, $attachment_url, $this->resume_id );

					if ( ! is_wp_error( $attachment_id ) ) {
						wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $attachment_url ) );
					}
				}
			}
		}

		do_action( 'resume_manager_update_resume_data', $this->resume_id, $values );
	}

	/**
	 * Preview Step
	 */
	public function preview() {
		global $post, $resume_preview;

		$this->check_valid_resume();

		wp_enqueue_script( 'wp-resume-manager-resume-submission' );

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Job preview depends on temporary override. Reset below.
		$post           = get_post( $this->resume_id );
		$resume_preview = true;

		setup_postdata( $post );
		get_job_manager_template(
			'resume-preview.php',
			[
				'form' => $this,
			],
			'wp-job-manager-resumes',
			RESUME_MANAGER_PLUGIN_DIR . '/templates/'
		);
		wp_reset_postdata();
	}

	/**
	 * Preview Step Form handler
	 */
	public function preview_handler() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Input is used safely.
		if ( empty( $_POST ) ) {
			return;
		}

		$this->check_preview_form_nonce_field();
		$this->check_valid_resume();

		// Edit = show submit form again.
		if ( ! empty( $_POST['edit_resume'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce was checked above.
			$this->step --;
		}

		// Continue = change job status then show next screen.
		if ( ! empty( $_POST['continue'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce was checked above.
			$resume = get_post( $this->resume_id );

			if ( in_array( $resume->post_status, [ 'preview', 'expired' ], true ) ) {
				// Reset expiry.
				delete_post_meta( $resume->ID, '_resume_expires' );

				// Update listing.
				$update_resume                  = [];
				$update_resume['ID']            = $resume->ID;
				$update_resume['post_date']     = current_time( 'mysql' );
				$update_resume['post_date_gmt'] = current_time( 'mysql', 1 );
				$update_resume['post_author']   = get_current_user_id();
				$update_resume['post_status']   = apply_filters( 'submit_resume_post_status', get_option( 'resume_manager_submission_requires_approval' ) ? 'pending' : 'publish', $resume );

				wp_update_post( $update_resume );
			}

			$this->step ++;

			/**
			 * Do not redirect if WCPL is set to choose package before submitting listing
			 *
			 * By not redirecting, we allow $this->process() (@see abstract-wp-job-manager-form.php) to call the 'wc-process-package'
			 * handler first, instead of view, which does not exist in 'wc-process-package' (and would be called first on redirect).
			 */
			if ( 'before' !== get_option( 'resume_manager_paid_listings_flow' ) ) {
				wp_safe_redirect(
					esc_url_raw(
						add_query_arg(
							[
								'step'      => $this->step,
								'job_id'    => $this->job_id,
								'resume_id' => $this->resume_id,
							]
						)
					)
				);
				exit;
			}
		}
	}

	/**
	 * Done Step.
	 */
	public function done() {
		$this->check_valid_resume();

		get_job_manager_template(
			'resume-submitted.php',
			[
				'resume' => get_post( $this->resume_id ),
				'job_id' => $this->job_id,
			],
			'wp-job-manager-resumes',
			RESUME_MANAGER_PLUGIN_DIR . '/templates/'
		);

		delete_post_meta( $this->resume_id, '_submitting_key' );

		// Allow application.
		if ( $this->job_id ) {
			get_job_manager_template(
				'resume-submitted-application-form.php',
				[
					'resume' => get_post( $this->resume_id ),
					'job_id' => $this->job_id,
				],
				'wp-job-manager-resumes',
				RESUME_MANAGER_PLUGIN_DIR . '/templates/'
			);
		}
	}

	/**
	 * Validate the resume ID passed. Respond with a 400 Bad Request error if an invalid ID is passed.
	 * `self::$resume_id` is already cleared out in the constructor if the user doesn't have
	 * permission to access it, but we still file actions without checking its value.
	 */
	private function check_valid_resume() {
		if (
			! empty( $this->resume_id )
			&& 'resume' === get_post_type( $this->resume_id )
		) {
			return;
		}

		wp_die(
			esc_html__( 'Invalid resume', 'wp-job-manager-resumes' ),
			'',
			[
				'response'  => 400,
				'back_link' => true,
			]
		);
	}

	/**
	 * Output the nonce field on job preview form.
	 *
	 * @access private
	 */
	public function output_preview_form_nonce_field() {
		wp_nonce_field( 'preview-resume-' . $this->resume_id, '_wpjm_nonce' );
	}

	/**
	 * Check the nonce field on the preview form.
	 *
	 * @access private
	 */
	public function check_preview_form_nonce_field() {
		if (
			empty( $_REQUEST['_wpjm_nonce'] )
			|| ! wp_verify_nonce( wp_unslash( $_REQUEST['_wpjm_nonce'] ), 'preview-resume-' . $this->resume_id ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce should not be modified.
		) {
			wp_nonce_ays( 'preview-resume-' . $this->resume_id );
			die();
		}
	}

	/**
	 * Output the nonce field on job submission form.
	 *
	 * @access private
	 */
	public function output_submit_form_nonce_field() {
		wp_nonce_field( 'submit-resume-' . $this->resume_id, '_wpjm_nonce' );
	}

	/**
	 * Check the nonce field on the submit form.
	 *
	 * @access private
	 */
	public function check_submit_form_nonce_field() {
		if (
			empty( $_REQUEST['_wpjm_nonce'] )
			|| ! wp_verify_nonce( wp_unslash( $_REQUEST['_wpjm_nonce'] ), 'submit-resume-' . $this->resume_id ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce should not be modified.
		) {
			wp_nonce_ays( 'submit-resume-' . $this->resume_id );
			die();
		}
	}

	/**
	 * Get the resume fields use on the submission form.
	 *
	 * @return array
	 */
	public static function get_resume_fields() {
		$instance = self::instance();
		$instance->init_fields();

		return $instance->get_fields( 'resume_fields' );
	}
}
