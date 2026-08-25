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

	/** PPTX MIME type used for Drive export. */
	const PPTX_MIME = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';

	/** Direct Docs export URL — no API size cap, accepts Bearer token. */
	const DOCS_EXPORT_URL = 'https://docs.google.com/presentation/d/%s/export/pptx';

	/**
	 * Export a Google Slides file as PPTX and save it to a local temp file.
	 *
	 * Primary path: Drive API `files.export` (fast, structured errors).
	 * Fallback path: direct Docs export URL (no 10 MB cap, streams to disk).
	 *
	 * The caller is responsible for deleting the temp file after use.
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

		$dest_path = trailingslashit( $dest_dir ) . sanitize_file_name( $file_id ) . '.pptx';

		// ── Primary: Drive API export ──────────────────────────────────────────
		$result = self::try_api_export( $file_id, $client, $dest_path );

		if ( ! is_wp_error( $result ) ) {
			return $result;
		}

		// Fall back to the direct export URL when the file exceeds the API's
		// export size limit (~10 MB). The Docs export URL has no such cap.
		if ( self::is_size_limit_error( $result ) ) {
			Utils::log(
				'File too large for Drive API export — falling back to direct URL.',
				array( 'file_id' => $file_id )
			);
			return self::try_direct_export( $file_id, $client, $dest_path );
		}

		return $result;
	}


	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Attempt export via Drive API files.export (10 MB cap).
	 *
	 * @param  string         $file_id   Drive file ID.
	 * @param  \Google\Client $client    Authenticated Google client.
	 * @param  string         $dest_path Destination file path.
	 * @return string|WP_Error
	 */
	private static function try_api_export( string $file_id, \Google\Client $client, string $dest_path ): string|WP_Error {
		$drive      = new DriveService( $client );
		$attempt    = 0;
		$last_error = new WP_Error( 'cbf_si_download_failed', 'Unknown download error.' );

		while ( $attempt < self::MAX_RETRIES ) {
			$attempt++;
			try {
				$response = $drive->files->export(
					$file_id,
					self::PPTX_MIME,
					array( 'alt' => 'media' )
				);

				$body = $response->getBody();
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				$bytes_written = file_put_contents( $dest_path, $body );

				// file_put_contents returns int|false; falsy covers both 0 and false.
				if ( ! $bytes_written ) {
					return new WP_Error( 'cbf_si_download_empty', 'Downloaded PPTX file is empty.' );
				}

				Utils::log(
					'PPTX downloaded via API export.',
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
				if ( self::should_retry( $attempt, $code ) ) {
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

		return $last_error;
	}


	/**
	 * Fall-back export: stream the direct Docs export URL to disk.
	 *
	 * This bypasses the Drive API's 10 MB export cap by hitting the same
	 * underlying export endpoint the browser uses, authenticated with the
	 * user's Bearer token via wp_remote_get() stream mode.
	 *
	 * @param  string         $file_id   Drive file ID.
	 * @param  \Google\Client $client    Authenticated Google client.
	 * @param  string         $dest_path Destination file path.
	 * @return string|WP_Error
	 */
	private static function try_direct_export( string $file_id, \Google\Client $client, string $dest_path ): string|WP_Error {
		$token = $client->getAccessToken();
		if ( empty( $token['access_token'] ) ) {
			return new WP_Error( 'cbf_si_no_token', 'No access token available for direct export.' );
		}

		$url = sprintf( self::DOCS_EXPORT_URL, rawurlencode( $file_id ) );

		// wp_remote_get with stream=true + filename writes directly to disk,
		// keeping memory usage proportional to the chunk size, not the file size.
		$response = wp_remote_get(
			$url,
			array(
				'timeout'  => 300,
				'headers'  => array(
					'Authorization' => 'Bearer ' . $token['access_token'],
				),
				'stream'   => true,
				'filename' => $dest_path,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		if ( $http_code !== 200 ) {
			// Clean up the (likely partial) destination file.
			@unlink( $dest_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			return new WP_Error(
				'cbf_si_direct_export_failed',
				sprintf( 'Direct PPTX export failed (HTTP %d). The file may require re-exporting.', $http_code )
			);
		}

		if ( ! file_exists( $dest_path ) || filesize( $dest_path ) === 0 ) {
			return new WP_Error( 'cbf_si_download_empty', 'Direct export returned an empty file.' );
		}

		$bytes = filesize( $dest_path );
		Utils::log(
			'PPTX downloaded via direct export URL.',
			array(
				'file_id' => $file_id,
				'size' => $bytes,
			)
		);
		return $dest_path;
	}


	/**
	 * Return true when the attempt count and HTTP code warrant a retry.
	 *
	 * Extracted to reduce cyclomatic complexity of try_api_export().
	 *
	 * @param int $attempt    Current attempt number (1-based).
	 * @param int $http_code  HTTP status code from the Drive API.
	 * @return bool
	 */
	private static function should_retry( int $attempt, int $http_code ): bool {
		return $attempt < self::MAX_RETRIES && ( $http_code === 429 || $http_code >= 500 );
	}


	/**
	 * Return true when a WP_Error represents the Drive API's export-size cap.
	 *
	 * @param WP_Error $error Error to inspect.
	 * @return bool
	 */
	private static function is_size_limit_error( WP_Error $error ): bool {
		$msg = strtolower( $error->get_error_message() );
		return str_contains( $msg, 'exportsizelimitexceeded' )
			|| str_contains( $msg, 'too large to be exported' );
	}
}
