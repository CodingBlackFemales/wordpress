<?php

namespace WPForms\Pro\Integrations\Gutenberg;

use WPForms\Helpers\File;

/**
 * Stock photos for Gutenberg block for Pro.
 *
 * @since 1.8.8
 */
class StockPhotos {

	/**
	 * The stock photos installation directory path.
	 *
	 * Relative to `wp-content/uploads/wpforms/` directory.
	 *
	 * @since 1.8.8
	 *
	 * @var string
	 */
	const STOCK_PHOTOS_DIR_PATH = 'themes/stock-photos/';

	/**
	 * The stock photos' JSON file name.
	 *
	 * @since 1.8.8
	 *
	 * @var string
	 */
	const STOCK_PHOTOS_JSON_FILE = 'pictures.json';

	/**
	 * The stock photos source URL path.
	 *
	 * @since 1.8.8
	 *
	 * @var string
	 */
	const STOCK_PHOTOS_URL_PATH = 'https://wpforms.com/wp-content/themes/wpf-theme/images/wallpapers/';

	/**
	 * The stock photos zip files.
	 *
	 * @since 1.8.8
	 *
	 * @var string
	 */
	const STOCK_PHOTOS_ZIP_FILES = [
		'stock-photos-1.zip',
		'stock-photos-2.zip',
	];

	/**
	 * The stock photos directory path.
	 *
	 * @since 1.8.8
	 *
	 * @var string
	 */
	private $dir_path;

	/**
	 * The stock photos URL path.
	 *
	 * @since 1.8.8
	 *
	 * @var string
	 */
	private $url_path;

	/**
	 * The stock photos' pictures.
	 *
	 * @since 1.8.8
	 *
	 * @var array
	 */
	private $pictures;

	/**
	 * Get the stock photos directory path.
	 *
	 * @since 1.8.8
	 *
	 * @return string|bool Directory path OR false in the case of permissions error.
	 */
	public function get_dir_path() {

		// Caching the directory path in the class property.
		if ( $this->dir_path !== null ) {
			return $this->dir_path;
		}

		// Determine custom themes file path.
		$dir = File::get_upload_dir() . self::STOCK_PHOTOS_DIR_PATH;

		// If the directory doesn't exist, create it. Also, check for permissions.
		if ( ! File::mkdir( $dir ) ) {
			return false;
		}

		$this->dir_path = $dir;

		return $dir;
	}

	/**
	 * Get the stock photos URL path.
	 *
	 * @since 1.8.8
	 *
	 * @return string The stock photos URL path.
	 */
	public function get_url_path(): string {

		// Caching the directory path in the class property.
		if ( $this->url_path !== null ) {
			return $this->url_path;
		}

		$upload_dir = wpforms_upload_dir();

		$this->url_path = trailingslashit( $upload_dir['url'] ?? '' ) . self::STOCK_PHOTOS_DIR_PATH;

		return $this->url_path;
	}

	/**
	 * Get zip file URLs filtered array.
	 *
	 * @since 1.8.8
	 *
	 * @return array
	 */
	private function get_zip_urls(): array {

		$urls = [];

		foreach ( self::STOCK_PHOTOS_ZIP_FILES as $file ) {
			$urls[] = self::STOCK_PHOTOS_URL_PATH . $file;
		}

		/**
		 * Allow modifying the list of zip file URLs.
		 *
		 * @since 1.8.8
		 *
		 * @param array $urls A list of zip file URLs.
		 */
		return (array) apply_filters( 'wpforms_pro_integrations_gutenberg_stock_photos_zip_urls', $urls );
	}

	/**
	 * Download and unzip the stock photo archive file.
	 *
	 * @since 1.8.8
	 *
	 * @param string $url The URL of the zip file.
	 *
	 * @return array
	 */
	private function download_and_unzip( string $url ): array {

		// Determine the stock photos directory path.
		$dir = $this->get_dir_path();

		// Download the zip file.
		$response = wp_remote_get( $url );

		// In the case of error.
		if ( is_wp_error( $response ) ) {
			return [
				'error' => esc_html__( 'Can\'t download file.', 'wpforms' ),
			];
		}

		$zip_content   = $response['body'] ?? '';
		$zip_file_path = $dir . basename( $url );

		// Save the .zip file content to the file.
		File::put_contents( $zip_file_path, $zip_content );

		// Unzip the file.
		$unzip = unzip_file( $zip_file_path, $dir );

		// In the case of error.
		if ( is_wp_error( $unzip ) ) {
			return [
				'error' => esc_html__( 'Can\'t unzip file.', 'wpforms' ),
			];
		}

		// Unzipped files directory.
		$unzipped_dir = $dir . pathinfo( $zip_file_path, PATHINFO_FILENAME );

		// Move unzipped files to the stock photos directory.
		if ( ! File::move( $unzipped_dir . '/*.jpg', $dir ) ) {
			return [
				'error' => esc_html__( 'Can\'t move unzipped files.', 'wpforms' ),
			];
		}

		// Remove the zip file and the empty unzipped files' directory.
		File::delete( $zip_file_path );
		File::delete( $unzipped_dir );

		return [];
	}

	/**
	 * Install stock photos.
	 *
	 * @since 1.8.8
	 *
	 * @param bool $force Whether to force the installation.
	 *
	 * @return array The result could contain such keys: `pictures`, `error`.
	 */
	public function install( bool $force = false ): array {

		// Determine the stock photos directory path.
		$dir = $this->get_dir_path();

		// In the case of error.
		if ( ! $dir ) {
			return [
				'error' => esc_html__( 'Can\'t create the stock photos storage directory.', 'wpforms' ),
			];
		}

		$pics = $this->get_pictures();

		if ( ! $force && ! empty( $pics ) ) {
			return [
				'pictures' => $pics,
			];
		}

		$zip_urls = $this->get_zip_urls();

		// Set 5-minute timeout for all files download and unzip.
		set_time_limit( 5 * MINUTE_IN_SECONDS );

		// Download and unzip all the stock photos zip files.
		foreach ( $zip_urls as $url ) {
			$result = $this->download_and_unzip( $url );

			if ( ! empty( $result['error'] ) ) {
				return $result;
			}
		}

		// Get the stock photos filenames.
		$files = $this->get_picture_files();

		// Update the stock photos pictures JSON file.
		$this->save_pictures( $files );

		return [
			'pictures' => $files,
		];
	}

	/**
	 * Get the saved pictures data.
	 *
	 * @since 1.8.8
	 *
	 * @return array The array of the picture filenames.
	 */
	public function get_pictures(): array {

		// Caching the pictures in the class property.
		if ( $this->pictures !== null ) {
			return $this->pictures;
		}

		// Get pictures data from JSON file.
		$pictures_file  = $this->get_dir_path() . self::STOCK_PHOTOS_JSON_FILE;
		$pictures_json  = File::get_contents( $pictures_file ) ?? '{}';
		$this->pictures = json_decode( $pictures_json, true ) ?? [];

		return $this->pictures;
	}

	/**
	 * Save the pictures' data.
	 *
	 * @since 1.8.8
	 *
	 * @param array $pictures Pictures data.
	 *
	 * @return bool
	 *
	 * @noinspection PhpReturnValueOfMethodIsNeverUsedInspection
	 */
	private function save_pictures( array $pictures ): bool {

		$pictures_file = $this->get_dir_path() . self::STOCK_PHOTOS_JSON_FILE;
		$json_data     = ! empty( $pictures ) ? wp_json_encode( $pictures ) : '{}';

		// Save the pictures' data and return the result.
		return File::put_contents( $pictures_file, $json_data );
	}

	/**
	 * Get the picture filenames.
	 *
	 * @since 1.8.8
	 *
	 * @return array The array of the picture filenames.
	 */
	public function get_picture_files(): array {

		$dir     = $this->get_dir_path();
		$dirlist = File::dirlist( $dir );

		if ( empty( $dirlist ) ) {
			return [];
		}

		$files = array_values( wp_list_pluck( $dirlist, 'name' ) );

		return array_filter(
			$files,
			static function ( $file ) {

				return in_array( pathinfo( $file, PATHINFO_EXTENSION ), [ 'jpg', 'jpeg', 'png', 'webp', 'svg' ], true );
			}
		);
	}
}
