<?php
/**
 * LearnDash Admin file download handler.
 *
 * @since 4.3.0.1
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Admin_File_Download_Handler' ) ) {
	/**
	 * Class LearnDash Admin file download handler.
	 *
	 * @since 4.3.0.1
	 */
	class Learndash_Admin_File_Download_Handler {
		/**
		 *
		 * The file download action name. Deprecated.
		 *
		 * @since 4.3.0.1
		 * @since 4.10.3 Deprecated. It's not used anymore. Replaced by the static property `$file_download_action`.
		 *
		 * @var string
		 */
		const LD_FILE_DOWNLOAD_ACTION = 'learndash_file_download';

		/**
		 * The file download action name. It is used to generate the download URL.
		 *
		 * @since 4.10.3
		 *
		 * @var string
		 */
		protected static $file_download_action = 'learndash_admin_file_download';

		/**
		 * List of file paths to download.
		 *
		 * @since 4.3.0.1
		 *
		 * @var array $file_paths [$file_path_id] => $file_path.
		 */
		private static $file_paths = array();

		/**
		 * Register a file path to download.
		 *
		 * @since 4.3.0.1
		 *
		 * @param string $file_path_id Unique ID for the file path.
		 * @param string $file_path    File path to download.
		 *
		 * @return void
		 */
		public static function register_file_path( string $file_path_id, string $file_path ): void {
			self::$file_paths[ $file_path_id ] = $file_path;
		}

		/**
		 * Returns the URL to download a file.
		 *
		 * @since 4.3.0.1
		 *
		 * @param string $file_path_id The file path ID.
		 * @param string $file_name    The file name.
		 *
		 * @throws InvalidArgumentException If file path ID is not registered.
		 *
		 * @return string The URL to download the file.
		 */
		public static function get_download_url( string $file_path_id, string $file_name ): string {
			if ( ! isset( self::$file_paths[ $file_path_id ] ) ) {
				// translators: placeholder: file path ID.
				throw new InvalidArgumentException( sprintf( __( 'File path "%s" is not registered', 'learndash' ), $file_path_id ) );
			}

			$download_url = add_query_arg(
				array(
					'action'       => static::$file_download_action,
					'nonce'        => wp_create_nonce( static::$file_download_action . $file_path_id . $file_name ),
					'file_path_id' => $file_path_id,
					'file_name'    => $file_name,
				),
				static::get_download_url_base()
			);

			/**
			 * Filters the file download URL.
			 *
			 * @since 4.19.0
			 *
			 * @param string $download_url The file download URL.
			 * @param string $file_path_id The file path ID.
			 * @param string $file_name    The file name.
			 *
			 * @return string
			 */
			return apply_filters(
				'learndash_file_download_url',
				$download_url,
				$file_path_id,
				$file_name
			);
		}

		/**
		 * Tries to protect a file path from being downloaded directly.
		 *
		 * @param string $file_path The file path.
		 *
		 * @return string Empty string if file is protected, protect instructions if not protected.
		 */
		public static function try_to_protect_file_path( string $file_path ): string {
			$htaccess_configured = false;

			learndash_put_directory_index_file( trailingslashit( $file_path ) . 'index.php' );

			$htaccess_path = trailingslashit( $file_path ) . '.htaccess';

			try {
				if ( file_exists( $htaccess_path ) ) {
					$htaccess_configured = true;
				} elseif ( is_dir( $file_path ) && is_writable( $file_path ) ) {
					// write the .htaccess file.
					$htaccess_file = fopen( $htaccess_path, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen

					if ( $htaccess_file ) {
						fwrite( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
							$htaccess_file,
							'Order Allow,Deny' . PHP_EOL . 'Deny from all' . PHP_EOL
						);
						fclose( $htaccess_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose

						$htaccess_configured = true;
					}
				}
			} catch ( Throwable $th ) {
				WP_DEBUG && error_log( $th->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Only if debug is enabled.
				$htaccess_configured = false;
			}

			$server_software      = self::get_current_server_software();
			$protect_instructions = '';

			switch ( $server_software ) {
				case 'apache':
					if ( $htaccess_configured ) {
						return ''; // File path is protected.
					}

					$protect_instructions = sprintf(
						// translators: placeholder: file path, htaccess path, htaccess content.
						esc_html_x(
							'To protect the file path "%1$s" from being downloaded directly, add the following line to the %2$s file: %3$s',
							'placeholder: file path, htaccess path, htaccess content',
							'learndash'
						),
						'<code>' . esc_html( $file_path ) . '</code>',
						'<code>' . esc_html( $htaccess_path ) . '</code>',
						'<br/><br/><code>Order Allow,Deny<br/>Deny from all</code>'
					);
					break;

				case 'nginx':
					$document_root = isset( $_SERVER['DOCUMENT_ROOT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) ) : '';
					$nginx_path    = str_replace( $document_root, '', $file_path );

					$protect_instructions = sprintf(
						// translators: placeholder: file path, nginx config.
						esc_html_x(
							'To protect the file path "%1$s" from being downloaded directly, add the following line to your nginx config: %2$s',
							'placeholder: file path, nginx config',
							'learndash'
						),
						'<code>' . esc_html( $file_path ) . '</code>',
						'<br/><br/><code>location "' . esc_html( $nginx_path ) . '" {<br/>&nbsp;&nbsp;deny all;<br/>&nbsp;&nbsp;return 403;<br/>}</code>'
					);
					break;

				default:
					$protect_instructions = sprintf(
						// translators: placeholder: file path.
						esc_html_x(
							'To protect the file path "%1$s" from being downloaded directly, you need to configure your server to deny access to this path.',
							'placeholder: file path',
							'learndash'
						),
						'<code>' . esc_html( $file_path ) . '</code>'
					);
					break;
			}

			return $protect_instructions . '<br/><br/>' . sprintf(
				// translators: placeholder: LD documentation URL.
				esc_html__( 'For further details, please read this help document: %s.', 'learndash' ),
				'<a target="_blank" href="https://learndash.com/support/developers/protecting-files/">' .
				esc_html__( 'Protecting files', 'learndash' ) .
				'</a>'
			);
		}

		/**
		 * Returns the current server software name.
		 *
		 * @since 4.3.0.1
		 *
		 * @return string The server software name.
		 */
		private static function get_current_server_software(): string {
			if ( ! isset( $_SERVER['SERVER_SOFTWARE'] ) ) {
				return '';
			}

			$server_software = sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) );

			if ( stristr( $server_software, 'apache' ) !== false ) {
				return 'apache';
			}

			if ( stristr( $server_software, 'nginx' ) !== false ) {
				return 'nginx';
			}

			return '';
		}

		/**
		 * Initializes the file download handler.
		 *
		 * @since 4.3.0.1
		 *
		 * @return void
		 */
		public static function init(): void {
			add_action(
				'admin_post_' . static::$file_download_action,
				[ get_called_class(), 'download' ]
			);
		}

		/**
		 * Downloads the file based on set query parameters.
		 *
		 * @since 4.19.0
		 *
		 * @return void
		 */
		public static function download(): void {
			$file_path_id = filter_input( INPUT_GET, 'file_path_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$file_name    = filter_input( INPUT_GET, 'file_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$nonce        = filter_input( INPUT_GET, 'nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

			if ( ! wp_verify_nonce( $nonce, static::$file_download_action . $file_path_id . $file_name ) ) {
				echo esc_html__( 'URL expired. Please refresh the page and try it again.', 'learndash' );
				exit;
			}

			if ( ! isset( self::$file_paths[ $file_path_id ] ) ) {
				echo esc_html__( 'Invalid URL.', 'learndash' );
				exit;
			}

			/**
			 * Filters whether the file can be downloaded.
			 *
			 * @since 4.10.3
			 *
			 * @param bool   $file_can_be_downloaded Whether the file can be downloaded.
			 * @param string $file_path_id           The file path ID.
			 * @param string $file_name              The file name.
			 *
			 * @return bool
			 */
			$file_can_be_downloaded = apply_filters(
				'learndash_file_can_be_downloaded',
				static::can_be_downloaded(),
				$file_path_id,
				$file_name
			);

			if ( ! $file_can_be_downloaded ) {
				echo esc_html__( 'You do not have sufficient permissions to download this file.', 'learndash' );
				exit;
			}

			$file_path = self::$file_paths[ $file_path_id ] . DIRECTORY_SEPARATOR . $file_name;

			/**
			 * Filters the file download path.
			 *
			 * @since 4.19.0
			 *
			 * @param string $file_path    The file path.
			 * @param string $file_path_id The file path ID.
			 * @param string $file_name    The file name.
			 *
			 * @return string
			 */
			$file_path = apply_filters(
				'learndash_file_download_path',
				$file_path,
				$file_path_id,
				$file_name
			);

			if ( ! file_exists( $file_path ) ) {
				echo esc_html__( 'File does not exist.', 'learndash' );
				exit;
			}

			// download the file.
			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: application/octet-stream' );
			header( 'Content-Disposition: attachment; filename=' . basename( $file_path ) );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate' );
			header( 'Pragma: public' );
			header( 'Content-Length: ' . filesize( $file_path ) );
			readfile( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_readfile, WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- readfile is faster
			exit;
		}

		/**
		 * Returns whether the current user can download the file.
		 *
		 * @since 4.10.3
		 *
		 * @return bool
		 */
		protected static function can_be_downloaded(): bool {
			return learndash_is_admin_user();
		}

		/**
		 * Returns the base URL for downloading files.
		 *
		 * @since 4.19.0
		 *
		 * @return string
		 */
		protected static function get_download_url_base(): string {
			return admin_url( 'admin-post.php' );
		}
	}
}
