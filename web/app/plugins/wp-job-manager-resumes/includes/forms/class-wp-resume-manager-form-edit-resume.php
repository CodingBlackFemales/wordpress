<?php
/**
 * File containing the WP_Resume_Manager_Form_Edit_Resume.
 *
 * @package wp-job-manager-resumes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once 'class-wp-resume-manager-form-submit-resume.php';

/**
 * WP_Resume_Manager_Form_Edit_Resume class.
 */
class WP_Resume_Manager_Form_Edit_Resume extends WP_Resume_Manager_Form_Submit_Resume {

	/**
	 * Form name slug.
	 *
	 * @var string
	 */
	public $form_name = 'edit-resume';

	/**
	 * Messaged shown on save.
	 *
	 * @var bool|string
	 */
	private $save_message = false;

	/**
	 * Message shown on error.
	 *
	 * @var bool|string
	 */
	private $save_error = false;

	/**
	 * The single instance of the class.
	 *
	 * @var WP_Resume_Manager_Form_Edit_Resume
	 */
	protected static $instance = null;

	/**
	 * Main Instance
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
		add_action( 'wp', [ $this, 'submit_handler' ] );
		add_action( 'submit_resume_form_start', [ $this, 'output_submit_form_nonce_field' ] );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Input is used safely.
		$this->resume_id = ! empty( $_REQUEST['resume_id'] ) ? absint( $_REQUEST['resume_id'] ) : 0;

		if ( ! resume_manager_user_can_edit_resume( $this->resume_id ) ) {
			$this->resume_id = 0;
		}

		if ( ! empty( $this->resume_id ) ) {
			$published_statuses = [ 'publish', 'hidden' ];
			$post_status        = get_post_status( $this->resume_id );

			if (
				( in_array( $post_status, $published_statuses, true ) && ! resume_manager_user_can_edit_published_submissions() )
				|| ( ! in_array( $post_status, $published_statuses, true ) && ! resume_manager_user_can_edit_pending_submissions() )
			) {
				$this->resume_id = 0;
			}
		}
	}

	/**
	 * Output the edit resume form.
	 *
	 * @param array $atts Attributes passed (ignored).
	 */
	public function output( $atts = [] ) {
		if ( ! empty( $this->save_message ) ) {
			echo '<div class="job-manager-message">' . wp_kses_post( $this->save_message ) . '</div>';
			return;
		}
		if ( ! empty( $this->save_error ) ) {
			echo '<div class="job-manager-error">' . wp_kses_post( $this->save_error ) . '</div>';
		}

		$this->submit();
	}

	/**
	 * Submit step.
	 */
	public function submit() {
		$resume = get_post( $this->resume_id );

		if ( empty( $this->resume_id ) ) {
			echo wp_kses_post( wpautop( __( 'Invalid resume', 'wp-job-manager-resumes' ) ) );
			return;
		}

		$this->init_fields();

		foreach ( $this->fields as $group_key => $group_fields ) {
			foreach ( $group_fields as $key => $field ) {
				if ( ! isset( $this->fields[ $group_key ][ $key ]['value'] ) ) {
					if ( 'candidate_name' === $key ) {
						$this->fields[ $group_key ][ $key ]['value'] = $resume->post_title;

					} elseif ( 'resume_content' === $key ) {
						$this->fields[ $group_key ][ $key ]['value'] = $resume->post_content;

					} elseif ( ! empty( $field['taxonomy'] ) ) {
						$this->fields[ $group_key ][ $key ]['value'] = wp_get_object_terms( $resume->ID, $field['taxonomy'], [ 'fields' => 'ids' ] );

					} elseif ( 'resume_skills' === $key ) {
						$this->fields[ $group_key ][ $key ]['value'] = implode( ', ', wp_get_object_terms( $resume->ID, 'resume_skill', [ 'fields' => 'names' ] ) );

					} else {
						$this->fields[ $group_key ][ $key ]['value'] = get_post_meta( $resume->ID, '_' . $key, true );
					}
				}
			}
		}

		$this->fields = apply_filters( 'submit_resume_form_fields_get_resume_data', $this->fields, $resume );

		$save_button_text   = __( 'Save changes', 'wp-job-manager-resumes' );
		$published_statuses = [ 'publish', 'hidden' ];
		if (
			in_array( get_post_status( $this->resume_id ), $published_statuses, true )
			&& resume_manager_published_submission_edits_require_moderation()
		) {
			$save_button_text = __( 'Submit changes for approval', 'wp-job-manager-resumes' );
		}

		/**
		 * Change button text for submitting changes to a resume.
		 *
		 * @since 1.18.0
		 *
		 * @param string $save_button_text Button text to filter.
		 * @param int    $resume_id        Resume post ID.
		 */
		$save_button_text = apply_filters( 'resume_manager_update_resume_form_submit_button_text', $save_button_text, $this->resume_id );

		get_job_manager_template(
			'resume-submit.php',
			[
				'class'              => $this,
				'form'               => $this->form_name,
				'job_id'             => '',
				'resume_id'          => $this->get_resume_id(),
				'action'             => $this->get_action(),
				'resume_fields'      => $this->get_fields( 'resume_fields' ),
				'step'               => $this->get_step(),
				'submit_button_text' => $save_button_text,
			],
			'wp-job-manager-resumes',
			RESUME_MANAGER_PLUGIN_DIR . '/templates/'
		);
	}

	/**
	 * Submit Step is posted.
	 */
	public function submit_handler() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Check happens later when possible.
		if ( empty( $_POST['submit_resume'] ) ) {
			return;
		}

		$this->check_submit_form_nonce_field();

		try {

			// Init fields.
			$this->init_fields();

			// Get posted values.
			$values = $this->get_posted_fields();

			// Validate required.
			$validation_result = $this->validate_fields( $values );
			if ( is_wp_error( $validation_result ) ) {
				throw new Exception( $validation_result->get_error_message() );
			}

			$original_post_status = get_post_status( $this->resume_id );
			$save_post_status     = $original_post_status;
			if ( resume_manager_published_submission_edits_require_moderation() ) {
				$save_post_status = 'pending';
			}

			// Update the resume.
			$this->save_resume( $values['resume_fields']['candidate_name'], $values['resume_fields']['resume_content'], $save_post_status, $values );
			$this->update_resume_data( $values );

			// Successful.
			$save_message = __( 'Your changes have been saved.', 'wp-job-manager-resumes' );
			$post_status  = get_post_status( $this->resume_id );
			update_post_meta( $this->resume_id, '_resume_edited', time() );
			update_post_meta( $this->resume_id, '_resume_edited_original_status', $original_post_status );

			$published_statuses = [ 'publish', 'hidden' ];
			if ( 'publish' === $post_status ) {
				$save_message = $save_message . ' <a href="' . get_permalink( $this->resume_id ) . '">' . __( 'View &rarr;', 'wp-job-manager-resumes' ) . '</a>';
			} elseif ( in_array( $original_post_status, $published_statuses, true ) && 'pending' === $post_status ) {
				$save_message = __( 'Your changes have been submitted and your resume will be available again once approved.', 'wp-job-manager-resumes' );

				/**
				 * Resets the resume expiration date when a user submits their resume listing edit for re-approval.
				 * Defaults to `false`.
				 *
				 * @since 1.18.0
				 *
				 * @param bool $reset_expiration If true, reset expiration date.
				 */
				if ( apply_filters( 'resume_manager_reset_listing_expiration_on_user_edit', false ) ) {
					delete_post_meta( $this->resume_id, '_resume_expires' );
				}
			}

			/**
			 * Change the message that appears when a user edits a resume.
			 *
			 * @since 1.18.0
			 *
			 * @param string $save_message  Save message to filter.
			 * @param int    $resume_id     Resume ID.
			 * @param array  $values        Submitted values for resume.
			 */
			$this->save_message = apply_filters( 'resume_manager_update_resume_listings_message', $save_message, $this->resume_id, $values );

			// Add the message and redirect to the candidate dashboard if possible.
			if ( WP_Resume_Manager_Shortcodes::add_candidate_dashboard_message( $this->save_message ) ) {
				$candidate_dashboard_page_id = get_option( 'resume_manager_candidate_dashboard_page_id' );
				$candidate_dashboard_url     = get_permalink( $candidate_dashboard_page_id );
				if ( $candidate_dashboard_url ) {
					wp_safe_redirect( $candidate_dashboard_url );
					exit;
				}
			}
		} catch ( Exception $e ) {
			$this->save_error = $e->getMessage();
		}
	}
}
