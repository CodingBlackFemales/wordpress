<?php
/**
 * REST endpoints for Google Drive integration.
 *
 * GET /drive/picker-config — return data the JS Drive Picker needs to initialise.
 *
 * @class   Api\DriveController
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Api;

use CodingBlackFemales\SlidesImporter\Document\ParserFactory;
use CodingBlackFemales\SlidesImporter\Google\OAuthClient;
use CodingBlackFemales\SlidesImporter\Install;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DriveController class.
 */
final class DriveController {

	/**
	 * Register routes.
	 *
	 * @param string $namespace REST namespace.
	 */
	public static function register_routes( string $namespace ): void {
		register_rest_route(
			$namespace,
			'/drive/picker-config',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'picker_config' ),
				'permission_callback' => array( AuthController::class, 'require_auth' ),
			)
		);
	}


	/**
	 * GET /drive/picker-config
	 *
	 * Returns the data the Google Picker JS API needs:
	 * - access_token : short-lived token from stored credentials
	 * - folder_id    : the shared CBF folder configured in settings
	 * - mime_types   : the Drive MIME types the picker should offer, so the list
	 *                  of importable formats is defined in one place server-side
	 *
	 * The picker works without a developer key in domain-restricted mode, so none
	 * is issued here.
	 *
	 * The access_token is fetched from the decrypted stored credential — it is
	 * never logged and is only transmitted over HTTPS.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function picker_config( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$user_id = get_current_user_id();

		$access_token = OAuthClient::get_access_token( $user_id );
		if ( is_wp_error( $access_token ) ) {
			return new WP_Error(
				'cbf_si_not_authenticated',
				__( 'Google Drive is not connected. Please authorise access first.', 'cbf-slides-importer' ),
				array( 'status' => 401 )
			);
		}

		$folder_id = (string) get_option( Install::FOLDER_ID_OPTION, '' );
		if ( empty( $folder_id ) ) {
			return new WP_Error(
				'cbf_si_no_folder_id',
				__( 'No shared Drive folder is configured. An administrator must set the folder ID in the plugin settings.', 'cbf-slides-importer' ),
				array( 'status' => 503 )
			);
		}

		return new WP_REST_Response(
			array(
				'access_token' => $access_token, // NR6: only in transit, not logged.
				'folder_id'    => $folder_id,
				'mime_types'   => ParserFactory::picker_mime_types(),
			),
			200
		);
	}
}
