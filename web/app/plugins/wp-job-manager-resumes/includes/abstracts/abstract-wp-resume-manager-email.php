<?php
/**
 * File containing the abstract class WP_Resume_Manager_Email.
 *
 * @package wp-job-manager-resumes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract class for an email notification in resumes
 *
 * @package wp-job-manager-resumes
 *
 * @since 1.18.0
 */
abstract class WP_Resume_Manager_Email extends WP_Job_Manager_Email_Template {
	/**
	 * Get the template path.
	 *
	 * @type abstract
	 * @return string
	 */
	public static function get_template_path() {
		return 'wp-job-manager-resumes';
	}

	/**
	 * Get the default template path where WP Job Manager should look for the templates.
	 *
	 * @type abstract
	 * @return string
	 */
	public static function get_template_default_path() {
		return RESUME_MANAGER_PLUGIN_DIR . '/templates/';
	}

	/**
	 * Get the context for where this email notification is used.
	 *
	 * @return string
	 */
	public static function get_context() {
		return 'resume_manager';
	}

	/**
	 * Expand arguments as necessary for the generation of the email.
	 *
	 * @param array $args Arguments used in generation of email.
	 * @return mixed
	 */
	protected function prepare_args( $args ) {
		// Fill in the job details.
		$args = parent::prepare_args( $args );

		// Default object is resume so we want the `author` argument to be just for that.
		if ( isset( $args['author'] ) ) {
			$args['job_author'] = $args['author'];
			unset( $args['author'] );
		}

		if ( isset( $args['resume_id'] ) ) {
			$resume = get_post( $args['resume_id'] );
			if ( $resume instanceof WP_Post ) {
				$args['resume'] = $resume;
			}
		}

		if ( isset( $args['resume'] ) && $args['resume'] instanceof WP_Post ) {
			$author = get_user_by( 'ID', $args['resume']->post_author );
			if ( $author instanceof WP_User ) {
				$args['author'] = $author;
			}
		}

		return $args;
	}
}
