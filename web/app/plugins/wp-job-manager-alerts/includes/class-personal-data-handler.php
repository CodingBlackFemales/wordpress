<?php
/**
 * File containing the class Personal_Data_Handler.
 *
 * @package wp-job-manager-alerts
 * @since   3.0.0
 */

namespace WP_Job_Manager_Alerts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class is responsible for deleting user alerts.
 */
class Personal_Data_Handler {
	private const ERASE_PER_PAGE = 100;

	use Singleton;

	/**
	 * Sets up initial hooks.
	 */
	private function __construct() {
		add_filter( 'wp_privacy_personal_data_erasers', [ $this, 'register_data_eraser' ] );
		add_filter( 'wp_privacy_personal_data_exporters', [ $this, 'register_data_exporter' ] );
	}

	/**
	 * Register the user data eraser method.
	 *
	 * @access private
	 *
	 * @param array $erasers The eraser array.
	 *
	 * @return array $erasers The eraser array.
	 */
	public function register_data_eraser( $erasers ) {
		if ( ! isset( $erasers['wp-job-manager-alerts'] ) ) {
			$erasers['wp-job-manager-alerts'] = [
				'eraser_friendly_name' => __( 'WP Job Manager - Alerts', 'wp-job-manager-alerts' ),
				'callback'             => [ $this, 'data_eraser' ],
			];
		}

		return $erasers;
	}

	/**
	 * Register the user data exporter method.
	 *
	 * @access private
	 *
	 * @param array $exporters The exporter array.
	 *
	 * @return array $exporters The exporter array.
	 */
	public function register_data_exporter( $exporters ) {
		if ( ! isset( $exporters['wp-job-manager-alerts'] ) ) {
			$exporters['wp-job-manager-alerts'] = [
				'exporter_friendly_name' => __( 'WP Job Manager - Alerts', 'wp-job-manager-alerts' ),
				'callback'               => [ $this, 'data_exporter' ],
			];
		}

		return $exporters;
	}

	/**
	 * Job alert eraser.
	 *
	 * @access private
	 *
	 * @param string $email_address User email address.
	 * @param int    $page          Page number.
	 *
	 * @return array
	 */
	public function data_eraser( $email_address, $page ) {
		$alert_ids = $this->get_personal_data_ids( $email_address, (int) $page );

		foreach ( $alert_ids as $alert_id ) {
			wp_delete_post( $alert_id, true );
		}

		$done                     = self::ERASE_PER_PAGE > count( $alert_ids ) || 0 === count( $alert_ids );
		$deleted_accountless_user = false;

		if ( $done ) {
			$accountless_user = $this->get_accountless_user( $email_address );

			if ( false !== $accountless_user ) {
				wp_delete_post( $accountless_user, true );
				$deleted_accountless_user = true;
			}
		}

		return [
			'items_removed'  => ! empty( $alert_ids ) || $deleted_accountless_user,
			'items_retained' => false,
			'messages'       => [],
			'done'           => $done,
		];
	}

	/**
	 * Data exporter
	 *
	 * @access private
	 *
	 * @param string $email_address User email address.
	 * @param int    $page          Page number.
	 *
	 * @return array
	 */
	public function data_exporter( $email_address, $page ) {
		$data_to_export = [];

		$alert_ids = $this->get_personal_data_ids( $email_address, (int) $page );

		foreach ( $alert_ids as $alert_id ) {
			$data_to_export[] = [
				'group_id'    => 'wp_job_manager_alerts',
				'group_label' => __( 'Job Alerts', 'wp-job-manager-alerts' ),
				'item_id'     => 'job-alert-' . $alert_id,
				'data'        => $this->get_alert_personal_data( $alert_id ),
			];
		}

		$done = self::ERASE_PER_PAGE > count( $data_to_export ) || 0 === count( $data_to_export );

		return [
			'data' => $data_to_export,
			'done' => $done,
		];
	}

	/**
	 * Returns all the user's job alert post ids.
	 *
	 * @param string $email The user's email.
	 * @param int    $page  Page number.
	 *
	 * @return int[] An array of post ids.
	 */
	private function get_personal_data_ids( $email, $page ) {
		if ( empty( $email ) ) {
			return [];
		}

		$alert_parents = [ 0 ];
		$alert_authors = [ 0 ];

		$accountless_user = $this->get_accountless_user( $email );

		if ( false !== $accountless_user ) {
			$alert_parents[] = $accountless_user;
		}

		$user = get_user_by( 'email', $email );

		if ( ! empty( $user->ID ) ) {
			$alert_authors[] = $user->ID;
		}

		$args = [
			'post_type'       => 'job_alert',
			'numberposts'     => self::ERASE_PER_PAGE,
			'author__in'      => $alert_authors,
			'post_parent__in' => $alert_parents,
			'post_status'     => [ 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash' ],
			'fields'          => 'ids',
			'paged'           => $page,
		];

		return get_posts( $args );
	}

	/**
	 * Get the accountless user for a specific email.
	 *
	 * @param string $email
	 *
	 * @return false|int False if there is no accountless user for this email, the post id otherwise.
	 */
	private function get_accountless_user( $email ) {
		$accountless_user = get_posts(
			[
				'post_type'   => 'job_guest_user',
				'title'       => $email,
				'numberposts' => 1,
				'fields'      => 'ids',
			]
		);

		return empty( $accountless_user ) ? false : $accountless_user[0];
	}

	/**
	 * Get the personal data for an alert.
	 *
	 * @param int $alert_id The alert id.
	 *
	 * @return array
	 */
	private function get_alert_personal_data( $alert_id ) {
		$post_meta     = get_post_meta( $alert_id );
		$personal_data = [];

		if ( ! empty( $post_meta['_alert_permission_approved'][0] ) ) {
			$personal_data[] = [
				'name'  => __( 'Permission Approval', 'wp-job-manager-alerts' ),
				'value' => $post_meta['_alert_permission_approved'][0],
			];
		}
		if ( ! empty( $post_meta['alert_frequency'][0] ) ) {
			$personal_data[] = [
				'name'  => __( 'Alert frequency', 'wp-job-manager-alerts' ),
				'value' => $post_meta['alert_frequency'][0],
			];
		}

		if ( ! empty( $post_meta['alert_keyword'][0] ) ) {
			$personal_data[] = [
				'name'  => __( 'Alert keywords', 'wp-job-manager-alerts' ),
				'value' => $post_meta['alert_keyword'][0],
			];
		}

		if ( ! empty( $post_meta['alert_location'][0] ) ) {
			$personal_data[] = [
				'name'  => __( 'Alert location', 'wp-job-manager-alerts' ),
				'value' => $post_meta['alert_location'][0],
			];
		}

		if ( ! empty( $post_meta['alert_search_terms'][0] ) ) {
			$personal_data[] = [
				'name'  => __( 'Alert search terms', 'wp-job-manager-alerts' ),
				'value' => $post_meta['alert_search_terms'][0],
			];
		}

		return $personal_data;
	}
}
