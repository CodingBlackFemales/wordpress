<?php
/**
 * Google Drive API client wrapper.
 *
 * Fetches an importable source document to a local temp path, with retry on
 * 429/5xx. Google Slides and Google Docs files are exported to PPTX and DOCX
 * respectively; files already stored in Drive as PPTX, DOCX or PDF are
 * downloaded as-is.
 *
 * @class   Google\DriveClient
 * @version 1.1.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Google;

use CodingBlackFemales\SlidesImporter\Document\ParserFactory;
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

	/** Drive media endpoint for files that need no format conversion. */
	const MEDIA_DOWNLOAD_URL = 'https://www.googleapis.com/drive/v3/files/%s?alt=media&supportsAllDrives=true';

	/**
	 * Drive metadata endpoint.
	 *
	 * Metadata is read over plain HTTP rather than through the Drive API client
	 * because google/apiclient 2.x requires Guzzle 6 or 7 and refuses to build a
	 * transport against the Guzzle 8 that Bedrock's root autoloader supplies —
	 * see the note on describe(). wp_remote_get() has no such constraint, and
	 * the export path already falls back to it for the same reason.
	 *
	 * `supportsAllDrives` is what lets a file on a shared drive be described at
	 * all; without it Drive answers 404 for files the user can plainly open in a
	 * browser. Bulk migration reads URLs from a spreadsheet rather than from a
	 * folder-restricted picker, so shared drives are expected, not exceptional.
	 */
	const METADATA_URL = 'https://www.googleapis.com/drive/v3/files/%s?fields=id%%2Cname%%2CmimeType%%2Ctrashed&supportsAllDrives=true';

	/**
	 * Fetch a Drive file and save it to a local temp file.
	 *
	 * A Google Slides or Google Docs file is exported to its binary equivalent;
	 * anything else is downloaded byte-for-byte. Export has two paths: the Drive
	 * API's `files.export` (fast, structured errors), falling back to the direct
	 * Docs export URL when a file exceeds the API's ~10 MB export cap.
	 *
	 * The caller is responsible for deleting the temp file after use.
	 *
	 * @param  string $file_id   Google Drive file ID.
	 * @param  string $mime_type Drive MIME type of the file, from the picker. May
	 *                           be empty, in which case it is looked up.
	 * @param  int    $user_id   WP user ID (whose credentials to use).
	 * @param  string $dest_dir  Absolute path to the destination directory.
	 * @return string|WP_Error   Absolute path to the downloaded file.
	 */
	public static function fetch_source( string $file_id, string $mime_type, int $user_id, string $dest_dir ): string|WP_Error {
		$client = OAuthClient::get_client_for_user( $user_id );
		if ( is_wp_error( $client ) ) {
			return $client;
		}

		if ( $mime_type === '' ) {
			$looked_up = self::lookup_mime_type( $file_id, $user_id );
			if ( is_wp_error( $looked_up ) ) {
				return $looked_up;
			}
			$mime_type = $looked_up;
		}

		$format = ParserFactory::format_for_mime( $mime_type );
		if ( $format === null ) {
			return new WP_Error(
				'cbf_si_unsupported_drive_file',
				sprintf(
					/* translators: %s: comma-separated list of supported file extensions */
					__( 'That Drive file is not an importable document. Supported formats: %s.', 'cbf-slides-importer' ),
					ParserFactory::extension_list()
				)
			);
		}

		$dest_path   = trailingslashit( $dest_dir ) . sanitize_file_name( $file_id ) . '.' . $format;
		$export_mime = ParserFactory::export_mime_for( $mime_type );

		// A file already stored in an importable format needs no conversion.
		if ( $export_mime === null ) {
			return self::download_file( $file_id, $client, $dest_path );
		}

		return self::export_file( $file_id, $format, $export_mime, $client, $dest_path );
	}


	/**
	 * Export a Google editor file to a binary format.
	 *
	 * Uses the Drive API first, falling back to the direct export URL when the
	 * file exceeds the API's ~10 MB export cap. The Docs export URL has no such
	 * limit, so a large deck still comes through.
	 *
	 * @param  string         $file_id     Drive file ID.
	 * @param  string         $format      Target format key (pptx, docx).
	 * @param  string         $export_mime MIME type to export to.
	 * @param  \Google\Client $client      Authenticated Google client.
	 * @param  string         $dest_path   Destination file path.
	 * @return string|WP_Error
	 */
	private static function export_file( string $file_id, string $format, string $export_mime, \Google\Client $client, string $dest_path ): string|WP_Error {
		$result = self::try_api_export( $file_id, $export_mime, $client, $dest_path );

		if ( ! is_wp_error( $result ) ) {
			return $result;
		}

		if ( self::is_size_limit_error( $result ) ) {
			Utils::log(
				'File too large for Drive API export — falling back to direct URL.',
				array( 'file_id' => $file_id )
			);
			return self::try_direct_export( $file_id, $format, $client, $dest_path );
		}

		return $result;
	}


	/**
	 * Read a Drive file's metadata over HTTP.
	 *
	 * Deliberately not `DriveService::files->get()`. google/apiclient 2.x accepts
	 * only Guzzle 6 or 7, and Bedrock's root vendor ships Guzzle 8, which wins
	 * the autoloader race against the copy bundled with this plugin — so every
	 * call through the API client raises "Could not find supported version of
	 * Guzzle" before it reaches the network. The download and export paths were
	 * already resilient to this because they fall back to wp_remote_get(); the
	 * metadata path had no fallback, which left bulk pre-flight unable to
	 * resolve any file at all.
	 *
	 * @param  string $file_id Drive file ID.
	 * @param  int    $user_id WP user ID whose credentials to use.
	 * @return array|WP_Error  Decoded metadata, or an editor-facing error.
	 */
	private static function fetch_metadata( string $file_id, int $user_id ): array|WP_Error {
		$token = OAuthClient::get_access_token( $user_id );
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$response = wp_remote_get(
			sprintf( self::METADATA_URL, rawurlencode( $file_id ) ),
			array(
				'timeout' => 30,
				'headers' => array( 'Authorization' => 'Bearer ' . $token ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'cbf_si_drive_unreachable',
				__( 'This Drive file could not be read.', 'cbf-slides-importer' )
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			return new WP_Error( 'cbf_si_drive_unreachable', self::describe_error( $code ) );
		}

		$decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $decoded ) ) {
			return new WP_Error(
				'cbf_si_drive_unreachable',
				__( 'Google Drive returned a response this plugin could not read.', 'cbf-slides-importer' )
			);
		}

		return $decoded;
	}


	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Turn a Drive HTTP status into something an editor can act on.
	 *
	 * A 404 from Drive usually means "you cannot see this", not "it is gone" —
	 * the API hides existence from users without access — so the message covers
	 * both rather than asserting the wrong one.
	 *
	 * @param  int $code HTTP status from the Drive API.
	 * @return string
	 */
	private static function describe_error( int $code ): string {
		switch ( $code ) {
			case 401:
				return __( 'Google Drive access has expired. Reconnect your account and try again.', 'cbf-slides-importer' );
			case 403:
				return __( 'Your Google account does not have permission to read this file. Ask its owner to share it with you.', 'cbf-slides-importer' );
			case 404:
				return __( 'This file could not be found. It may have been deleted, or it may live in a workspace your Google account cannot reach.', 'cbf-slides-importer' );
			default:
				return sprintf(
					/* translators: %d: HTTP status code */
					__( 'Google Drive could not be reached (HTTP %d).', 'cbf-slides-importer' ),
					$code
				);
		}
	}

	/**
	 * Look up a Drive file's MIME type.
	 *
	 * Only needed for jobs queued before the picker started recording the type.
	 *
	 * @param  string $file_id Drive file ID.
	 * @param  int    $user_id WP user ID whose credentials to use.
	 * @return string|WP_Error MIME type.
	 */
	private static function lookup_mime_type( string $file_id, int $user_id ): string|WP_Error {
		$file = self::fetch_metadata( $file_id, $user_id );
		if ( is_wp_error( $file ) ) {
			return $file;
		}

		return (string) ( $file['mimeType'] ?? '' );
	}


	/**
	 * Download a Drive file that is already in an importable format.
	 *
	 * Streams straight to disk so a large PDF never has to be held in memory.
	 *
	 * @param  string         $file_id   Drive file ID.
	 * @param  \Google\Client $client    Authenticated Google client.
	 * @param  string         $dest_path Destination file path.
	 * @return string|WP_Error
	 */
	private static function download_file( string $file_id, \Google\Client $client, string $dest_path ): string|WP_Error {
		$token = $client->getAccessToken();
		if ( empty( $token['access_token'] ) ) {
			return new WP_Error( 'cbf_si_no_token', 'No access token available for download.' );
		}

		$url = sprintf( self::MEDIA_DOWNLOAD_URL, rawurlencode( $file_id ) );

		$response = wp_remote_get(
			$url,
			array(
				'timeout'  => 300,
				'headers'  => array( 'Authorization' => 'Bearer ' . $token['access_token'] ),
				'stream'   => true,
				'filename' => $dest_path,
			)
		);

		return self::validate_streamed_response( $response, $dest_path, $file_id, 'direct download' );
	}


	/**
	 * Attempt export via Drive API files.export (10 MB cap).
	 *
	 * @param  string         $file_id     Drive file ID.
	 * @param  string         $export_mime MIME type to export to.
	 * @param  \Google\Client $client      Authenticated Google client.
	 * @param  string         $dest_path   Destination file path.
	 * @return string|WP_Error
	 */
	private static function try_api_export( string $file_id, string $export_mime, \Google\Client $client, string $dest_path ): string|WP_Error {
		$drive      = new DriveService( $client );
		$attempt    = 0;
		$last_error = new WP_Error( 'cbf_si_download_failed', 'Unknown download error.' );

		while ( $attempt < self::MAX_RETRIES ) {
			$attempt++;
			try {
				$response = $drive->files->export(
					$file_id,
					$export_mime,
					array( 'alt' => 'media' )
				);
				// files.export takes no supportsAllDrives parameter; shared-drive
				// files are reachable through it because the token, not the
				// request, carries the access.
				$body = $response->getBody();
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				$bytes_written = file_put_contents( $dest_path, $body );

				// file_put_contents returns int|false; falsy covers both 0 and false.
				if ( ! $bytes_written ) {
					return new WP_Error( 'cbf_si_download_empty', 'The exported file is empty.' );
				}

				Utils::log(
					'Source file downloaded via API export.',
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
	 * @param  string         $format    Target format key (pptx, docx).
	 * @param  \Google\Client $client    Authenticated Google client.
	 * @param  string         $dest_path Destination file path.
	 * @return string|WP_Error
	 */
	private static function try_direct_export( string $file_id, string $format, \Google\Client $client, string $dest_path ): string|WP_Error {
		$template = ParserFactory::export_url_template( $format );
		if ( $template === '' ) {
			return new WP_Error(
				'cbf_si_no_direct_export',
				'This file is too large to export and has no direct export URL.'
			);
		}

		$token = $client->getAccessToken();
		if ( empty( $token['access_token'] ) ) {
			return new WP_Error( 'cbf_si_no_token', 'No access token available for direct export.' );
		}

		$url = sprintf( $template, rawurlencode( $file_id ) );

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

		return self::validate_streamed_response( $response, $dest_path, $file_id, 'direct export' );
	}


	/**
	 * Check a streamed wp_remote_get() response and confirm the file landed.
	 *
	 * Shared by the direct-download and direct-export paths, both of which write
	 * straight to disk and so cannot report failure through a response body.
	 *
	 * @param  array|WP_Error $response  Result of wp_remote_get().
	 * @param  string         $dest_path Destination file path.
	 * @param  string         $file_id   Drive file ID, for logging.
	 * @param  string         $what      Short description used in log lines.
	 * @return string|WP_Error Destination path on success.
	 */
	private static function validate_streamed_response( $response, string $dest_path, string $file_id, string $what ): string|WP_Error {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		if ( $http_code !== 200 ) {
			// Clean up the (likely partial) destination file.
			@unlink( $dest_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			return new WP_Error(
				'cbf_si_direct_export_failed',
				sprintf( 'Drive %s failed (HTTP %d). The file may require re-exporting.', $what, $http_code )
			);
		}

		if ( ! file_exists( $dest_path ) || filesize( $dest_path ) === 0 ) {
			return new WP_Error( 'cbf_si_download_empty', sprintf( 'Drive %s returned an empty file.', $what ) );
		}

		Utils::log(
			'Source file downloaded from Drive.',
			array(
				'file_id' => $file_id,
				'via'     => $what,
				'size'    => filesize( $dest_path ),
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
