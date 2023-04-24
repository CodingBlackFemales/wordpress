<?php
/**
 * File containing the class WP_Resume_Manager_Resume_Lifecycle.
 *
 * @package wp-job-manager-resumes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WP_Resume_Manager_Resume_Lifecycle class. Ensures lifecycle hooks are called
 * at the right time.
 */
class WP_Resume_Manager_Resume_Lifecycle {

	/**
	 * The single instance of the class.
	 *
	 * @var WP_Resume_Manager_Resume_Lifecycle
	 */
	protected static $_instance = null;

	/**
	 * Get singleton instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'initialize_lifecycle_hooks' ] );
	}

	/**
	 * Set up the lifecycle hooks for Resumes.
	 */
	public function initialize_lifecycle_hooks() {
		add_action( 'transition_post_status', [ $this, 'transition_post_status' ], 10, 3 );
		add_action( 'added_post_meta', [ $this, 'added_public_submission_meta' ], 10, 3 );
	}

	/**
	 * Capture the resume post status transition to "publish" or "pending", and
	 * finalize the submission at that point.
	 *
	 * @access private
	 *
	 * @param string  $new_status The new post status.
	 * @param string  $old_status The new post status.
	 * @param WP_Post $post       The Post object.
	 */
	public function transition_post_status( $new_status, $old_status, $post ) {
		if ( 'resume' !== $post->post_type ) {
			return;
		}

		// Finalize public submission.
		$this->finalize_submission( $post->ID, $new_status );
	}

	/**
	 * Trigger finalizing the submission when the `_public_submission` meta is set.
	 *
	 * @access private
	 * @since 1.18.0
	 *
	 * @param int    $meta_id   The meta ID.
	 * @param int    $object_id The Resume ID.
	 * @param string $meta_key  The meta key being updated.
	 */
	public function added_public_submission_meta( $meta_id, $object_id, $meta_key ) {
		if ( '_public_submission' !== $meta_key || 'resume' !== get_post_type( $object_id ) ) {
			return;
		}

		$this->finalize_submission( $object_id );
	}

	/**
	 * Finalize the submission for a public submitted resume.
	 *
	 * @param int         $resume_id  The Resume ID.
	 * @param string|null $new_status The new post_status of the Resume. Defaults to the current status.
	 */
	private function finalize_submission( $resume_id, $new_status = null ) {
		// Only finalize once.
		if ( get_post_meta( $resume_id, '_submission_finalized', true ) ) {
			return;
		}

		// Only finalize if it is a public submission.
		if ( ! get_post_meta( $resume_id, '_public_submission', true ) ) {
			return;
		}

		// Only finalize if the status is "publish" or "pending".
		if ( null === $new_status ) {
			$new_status = get_post_status( $resume_id );
		}

		if ( ! in_array( $new_status, [ 'publish', 'pending' ], true ) ) {
			return;
		}

		/**
		 * Fire action after a resume is submitted.
		 *
		 * @since 1.0.0
		 *
		 * @param int $resume_id Resume ID.
		 */
		do_action( 'resume_manager_resume_submitted', $resume_id );

		// Mark this resume as finalized.
		update_post_meta( $resume_id, '_submission_finalized', true );
	}
}

WP_Resume_Manager_Resume_Lifecycle::instance();
