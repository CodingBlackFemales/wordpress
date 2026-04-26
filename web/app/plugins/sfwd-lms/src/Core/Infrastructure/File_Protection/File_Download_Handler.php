<?php
/**
 * File download handler.
 *
 * @since 4.10.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Infrastructure\File_Protection;

use LearnDash\Core\Utilities\Cast;
use Learndash_Admin_File_Download_Handler;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;

/**
 * File download handler.
 *
 * @since 4.10.3
 */
class File_Download_Handler extends Learndash_Admin_File_Download_Handler {
	/**
	 * The file download action name. It is used to generate the download URL.
	 *
	 * @since 4.10.3
	 *
	 * @var string
	 */
	protected static $file_download_action = 'learndash_file_download';

	/**
	 * Initializes the file download handler.
	 *
	 * @since 4.19.0
	 * @deprecated 4.19.0 -- Hooks have been moved to the File Protection service provider.
	 *
	 * @return void
	 */
	public static function init(): void {
		_deprecated_function( __METHOD__, '4.19.0' );
	}

	/**
	 * Downloads the file based on set query parameters.
	 *
	 * @since 4.19.0
	 *
	 * @return void
	 */
	public static function download(): void {
		/**
		 * Ensure we only run this for frontend file downloads.
		 * Additionally, ensure that we check for the correct action to avoid exit() calls on
		 * nonce failure for non-download requests.
		 */
		if (
			is_admin()
			|| Cast::to_string( SuperGlobals::get_get_var( 'action' ) ) !== self::$file_download_action
		) {
			return;
		}

		parent::download();
	}

	/**
	 * Returns whether the current user can download the file.
	 *
	 * @since 4.10.3
	 *
	 * @return bool
	 */
	protected static function can_be_downloaded(): bool {
		return is_user_logged_in();
	}

	/**
	 * Returns the base URL for downloading files.
	 *
	 * @since 4.19.0
	 *
	 * @return string
	 */
	protected static function get_download_url_base(): string {
		return home_url();
	}
}
