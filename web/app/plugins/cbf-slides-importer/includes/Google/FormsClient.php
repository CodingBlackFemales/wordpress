<?php
/**
 * Reads Google Forms through the Forms API.
 *
 * A Form has no file export — Drive cannot turn one into a PPTX, DOCX or PDF —
 * so the structured API response is the source document. It is saved to the
 * job's temp directory as `<id>.gform` so the rest of the pipeline, which
 * re-reads the source file at import time, works unchanged.
 *
 * Talks HTTP through wp_remote_get() rather than the google/apiclient service
 * classes for the same reason DriveClient does: Bedrock's root vendor ships a
 * Guzzle the API client cannot use.
 *
 * @class   Google\FormsClient
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Google;

use CodingBlackFemales\SlidesImporter\Document\ParserFactory;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FormsClient class.
 */
final class FormsClient {

	/** OAuth scope needed to read a form's questions and answer key. */
	const SCOPE = 'https://www.googleapis.com/auth/forms.body.readonly';

	const FORM_URL = 'https://forms.googleapis.com/v1/forms/%s';

	/**
	 * Fetch a form and save the API response next to the job's other files.
	 *
	 * @param  string $form_id  Drive file ID of the form (the same ID Forms uses).
	 * @param  int    $user_id  WP user ID whose credentials to use.
	 * @param  string $dest_dir Absolute path to the destination directory.
	 * @return string|WP_Error  Absolute path to the saved response.
	 */
	public static function fetch_source( string $form_id, int $user_id, string $dest_dir ): string|WP_Error {
		$form = self::get( $form_id, $user_id );
		if ( is_wp_error( $form ) ) {
			return $form;
		}

		$dest_path = trailingslashit( $dest_dir ) . sanitize_file_name( $form_id ) . '.' . ParserFactory::FORMAT_GFORM;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( file_put_contents( $dest_path, wp_json_encode( $form ) ) === false ) {
			return new WP_Error(
				'cbf_si_form_unwritable',
				__( 'The Google Form was read but could not be saved for import.', 'cbf-slides-importer' )
			);
		}

		return $dest_path;
	}


	/**
	 * Read a form over HTTP.
	 *
	 * @param  string $form_id Form ID.
	 * @param  int    $user_id WP user ID whose credentials to use.
	 * @return array|WP_Error  Decoded `forms.get` response, or an editor-facing error.
	 */
	public static function get( string $form_id, int $user_id ): array|WP_Error {
		$token = OAuthClient::get_access_token( $user_id );
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$response = wp_remote_get(
			sprintf( self::FORM_URL, rawurlencode( $form_id ) ),
			array(
				'timeout' => 30,
				'headers' => array( 'Authorization' => 'Bearer ' . $token ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'cbf_si_forms_unreachable',
				__( 'Google Forms could not be reached. Try again in a moment.', 'cbf-slides-importer' )
			);
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( $code !== 200 ) {
			return new WP_Error( 'cbf_si_forms_unreachable', self::describe_error( $code, is_array( $body ) ? $body : array() ) );
		}

		if ( ! is_array( $body ) ) {
			return new WP_Error(
				'cbf_si_forms_unreachable',
				__( 'Google Forms returned a response this plugin could not read.', 'cbf-slides-importer' )
			);
		}

		return $body;
	}


	/**
	 * Turn a Forms API failure into something an editor can act on.
	 *
	 * A 403 has three distinct causes that need three different fixes, and the
	 * response body is the only thing that tells them apart: the connection
	 * predates the Forms scope (reconnect), the Forms API is switched off in the
	 * Cloud project (an administrator's job), or the account simply cannot edit
	 * the form (ask the owner). The answer key is only returned to editors.
	 *
	 * @param  int   $code HTTP status.
	 * @param  array $body Decoded error response, possibly empty.
	 * @return string
	 */
	public static function describe_error( int $code, array $body ): string {
		return self::setup_problem( $code, $body ) ?? self::status_message( $code );
	}


	/**
	 * A failure caused by the connection or the Cloud project, not the form.
	 *
	 * @param  int   $code HTTP status.
	 * @param  array $body Decoded error response, possibly empty.
	 * @return string|null
	 */
	private static function setup_problem( int $code, array $body ): ?string {
		$error = (array) ( $body['error'] ?? array() );
		$blob  = strtolower( (string) ( $error['message'] ?? '' ) . ' ' . wp_json_encode( $error['details'] ?? array() ) );

		if ( $code === 401 || str_contains( $blob, 'scope' ) ) {
			return __( 'Your Google connection does not yet allow reading Forms. Use "Use a different account" or disconnect and reconnect on the Import tab, then try again.', 'cbf-slides-importer' );
		}

		if ( str_contains( $blob, 'service_disabled' ) || str_contains( $blob, 'has not been used' ) ) {
			return __( 'The Google Forms API is not enabled for this plugin\'s Google Cloud project. An administrator needs to enable it.', 'cbf-slides-importer' );
		}

		return null;
	}


	/**
	 * Explain an HTTP status once setup problems are ruled out.
	 *
	 * @param  int $code HTTP status.
	 * @return string
	 */
	private static function status_message( int $code ): string {
		return match ( $code ) {
			403 => __( 'Your Google account cannot read this form. Only people who can edit a form can import its questions and answer key.', 'cbf-slides-importer' ),
			404 => __( 'This form could not be found. It may have been deleted, or it may live in a workspace your Google account cannot reach.', 'cbf-slides-importer' ),
			default => sprintf(
				/* translators: %d: HTTP status code */
				__( 'Google Forms could not be reached (HTTP %d).', 'cbf-slides-importer' ),
				$code
			),
		};
	}
}
