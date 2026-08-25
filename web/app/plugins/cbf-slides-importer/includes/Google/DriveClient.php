<?php
/**
 * Google Drive API client wrapper.
 *
 * Provides a single method: download a Google Slides file as PPTX to a
 * local temp path, with retry on 429/5xx.
 *
 * @class   Google\DriveClient
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Google;

use CodingBlackFemales\SlidesImporter\Utils;
use Google\Service\Drive as DriveService;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DriveClient class.
 */
final class DriveClient {

	/** Maximum number of download retry attempts on transient errors. */
	const MAX_RETRIES = 3;

	/** Base backoff delay in seconds between retries. */
	const RETRY_DELAY = 2;

	/**
	 * Export a Google Slides file as PPTX and save it to a local temp file.
	 *
	 * The file is downloaded directly from the Drive API using the authenticated
	 * user's stored credentials. After a successful import job the caller is
	 * responsible for deleting the temp file.
	 *
	 * @param  string $file_id  Google Drive file ID.
	 * @param  int    $user_id  WP user ID (whose credentials to use).
	 * @param  string $dest_dir Absolute path to the destination directory.
	 * @return string|WP_Error  Absolute path to the downloaded PPTX file.
	 */
	public static function export_pptx( string $file_id, int $user_id, string $dest_dir ): string|WP_Error {
		$client = OAuthClient::get_client_for_user( $user_id );
		if ( is_wp_error( $client ) ) {
			return $client;
		}

		$drive     = new DriveService( $client );
		$dest_path = trailingslashit( $dest_dir ) . sanitize_file_name( $file_id ) . '.pptx';

		$attempt = 0;
		$last_error = null;

		while ( $attempt < self::MAX_RETRIES ) {
			$attempt++;
			try {
				// Export as PPTX (Google Slides → Office format).
				$response = $drive->files->export(
					$file_id,
					'application/vnd.openxmlformats-officedocument.presentationml.presentation',
					array( 'alt' => 'media' )
				);

				$body = $response->getBody();
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				$bytes_written = file_put_contents( $dest_path, $body );

				if ( $bytes_written === false || $bytes_written === 0 ) {
					return new WP_Error( 'cbf_si_download_empty', 'Downloaded PPTX file is empty.' );
				}

				Utils::log(
					'PPTX downloaded.',
					array(
						'file_id' => $file_id,
						'size' => $bytes_written,
					)
				);
				return $dest_path;

			} catch ( \Google\Service\Exception $e ) {
				$code = $e->getCode();
				Utils::log(
					'Drive API error.',
					array(
						'file_id' => $file_id,
						'attempt' => $attempt,
						'code' => $code,
					)
				);

				// Retry on 429 (rate limit) or 5xx (server error).
				if ( $attempt < self::MAX_RETRIES && ( $code === 429 || $code >= 500 ) ) {
					sleep( self::RETRY_DELAY * $attempt ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
					continue;
				}

				$last_error = new WP_Error(
					'cbf_si_drive_api_error',
					sprintf( 'Google Drive API error (HTTP %d): %s', $code, $e->getMessage() )
				);
				break;

			} catch ( \Throwable $e ) {
				Utils::log(
					'Unexpected download error.',
					array(
						'file_id' => $file_id,
						'msg' => $e->getMessage(),
					)
				);
				$last_error = new WP_Error( 'cbf_si_download_failed', $e->getMessage() );
				break;
			}
		}

		return $last_error ?? new WP_Error( 'cbf_si_download_failed', 'Unknown download error.' );
	}
}
