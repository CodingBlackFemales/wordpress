<?php
/**
 * WP Job Manager integration
 * Source: https://wpjobmanager.com/document/extensions/resume-manager/tutorial-remove-the-resume-preview-step/#top
 *
 * @package     CodingBlackFemales/Multisite/Customizations
 * @version     1.0.0
 */

namespace CodingBlackFemales\Multisite\Customizations;

use CodingBlackFemales\Multisite\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom WP Job Manager integration class.
 */
class WP_Job_Manager {
	/**
	 * Remove the preview step when submitting resumes. Code goes in theme functions.php or custom plugin.
	 * @param  array $steps
	 * @return array
	 */
	public static function submit_resume_steps( $steps ) {
		unset( $steps['preview'] );

		return $steps;
	}

	/**
	 * Change button text.
	 */
	public static function submit_resume_form_submit_button_text() {
		return get_option( 'resume_manager_submission_requires_approval' ) ? __( 'Submit Resume', 'wp-job-manager-resumes' ) : __( 'Save Resume', 'wp-job-manager-resumes' );
	}

	/**
	 * Since we removed the preview step and it's handler, we need to manually publish resumes.
	 * @param  int $resume_id
	 */
	public static function resume_manager_update_resume_data( $resume_id ) {
		$resume = get_post( $resume_id );

		if ( in_array( $resume->post_status, array( 'preview', 'expired' ), true ) ) {
			// Reset expiry.
			delete_post_meta( $resume->ID, '_resume_expires' );

			// Update resume listing.
			$update_resume                  = array();
			$update_resume['ID']            = $resume->ID;
			$update_resume['post_status']   = get_option( 'resume_manager_submission_requires_approval' ) ? 'pending' : 'publish';
			$update_resume['post_date']     = current_time( 'mysql' );
			$update_resume['post_date_gmt'] = current_time( 'mysql', 1 );
			wp_update_post( $update_resume );
		}
	}

	/**
	 * Prevent job listing company thumbnails from being deleted during import.
	 *
	 * @param bool   $is_images_to_update
	 * @param array  $article_data
	 * @param string $current_xml_node
	 * @param int    $pid
	 *
	 * @return bool
	 */
	public static function preserve_job_listing_images( $is_images_to_update, $article_data, $current_xml_node, $pid ) {
		if ( $article_data['post_type'] === 'job_listing' ) {
			$is_images_to_update = false;
		}

		return $is_images_to_update;
	}

	/**
	 * Hook in methods.
	 */
	public static function hooks() {
		if ( Utils::is_request( 'admin' ) ) {
			add_filter( 'pmxi_is_images_to_update', array( __CLASS__, 'preserve_job_listing_images' ), 10, 4 );
		}

		if ( Utils::is_request( 'frontend' ) ) {
			add_filter( 'submit_resume_steps', array( __CLASS__, 'submit_resume_steps' ) );
			add_filter( 'submit_resume_form_submit_button_text', array( __CLASS__, 'submit_resume_form_submit_button_text' ) );
			add_action( 'resume_manager_update_resume_data', array( __CLASS__, 'resume_manager_update_resume_data' ) );
		}
	}
}
