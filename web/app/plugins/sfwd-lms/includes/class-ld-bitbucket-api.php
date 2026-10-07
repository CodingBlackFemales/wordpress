<?php
/**
 * Handles API calls to BitBucket
 *
 * @since 2.5.4
 *
 * @package LearnDash
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LearnDash_BitBucket_API' ) ) {
	class LearnDash_BitBucket_API {

		// Production
		private $bb_OAuth = null;

		private $ld_addons_dir = null;

		private $bb_OAuth_sets = array(
			'fUEfEERS8FdprEzNMX' => 'gnBPjny5yummKe6zGv5MMCa5w9tDGstT',     // ld_updates
			'32uKmTweF7WGUgXC2H' => 'qmUveCQvaZXD9LmAE8GFAd6a6H5SMk6U',     // ld_updates2
			'P2xXBphWPZP8Cbr8Rw' => 'qFvD9zrt9RVVpEJ6j2jEWjZJEgvhpzPZ',     // ld_updates3
			'8tvevj3YQQnyFJE5a2' => 'C5BuSGZBanNbmaEE7hjeQEnmPLwma3JE',     // ld_updates4
			'2LdbzTV4u5UhfTPS8t' => 'Y5eMpRVnFAWkvb3dmZuq64jTzNU9x93c',     // ld_updates5
			'jYyJyBctJ6wkxVVk7h' => 'NeKUN87Npyu8tGwN4eLCh5ZFjjazdu4e',     // ld_updates6
			'bn6Phmn3jVyQ2NfdPa' => 'Ct7MbLpuCyr56s8qvy6Eh7VteBEGshDp',     // ld_updates7
			'bTtudTumX8jQ56aJxc' => 'czwT3Cr75sBDTkDzGzwExwDhHcH6wVbL',     // ld_updates8
			'uAPE6cvgWjbHdEYpJg' => 'butXFuD3QNbX3uXrwf49J8vvDNMsPbpA',     // ld_updates9
			'P6xDsLKNTxGdSfUpnC' => 'XFk6PVJCu5bHmcJB7WN3v5afzEsyqAxL',     // ld_updates10

			'YUKnZucTb2pJchuDZS' => 'W6n5vBVz4vsvF4GQ7xu4zKA2JkA2DVHm',     // ld_updates11
			'YFfyNFaLKtU6cMm9c4' => 'WAnwBKqsj4r2YMU2zMVYSLwqDCt8hc4s',     // ld_updates12
			'asHX3Me2k68jf4ALms' => 'EMR6rEtFv8HKfqKhrzkwmYK6knkGTsq6',     // ld_updates13
			'J94b6KjjBaZDG8Awau' => '9b7nMpTSFBXR7mbc7rmF2hFZduRptjaW',     // ld_updates14
			'VGXFPJcysa83QWgGqY' => 'VUYEtkDG6L3jVCmJARyzRYTZpeWckD8B',     // ld_updates15
			'tKqG3xaLt5dawwLYJu' => 'AEdAJEyHUP9UuTEJ38E36rapvgHG9z3B',     // ld_updates16
			'TxNgg8AkcyhQDLmAaA' => 'usAw3tGPWW6k43KmdqKxZcLRbaVyMWvj',     // ld_updates17
			'r62wGrYYDGz2kTMjcN' => 'C4CWjLFeM2WaJg6egVJcFQK6XZSmZjgZ',     // ld_updates18
			'epKULsTE7DqP5SsJFw' => 'HjYQ8G5Npekq92Xf6vrbt8b79su8r2wb',     // ld_updates19
			'APfwmF6D4VaeAxLprB' => 'U7hyxFwX27mWYzgE5x5DzjHyCaCfqFK4',     // ld_updates20
		);

		private $request_method    = 'GET';
		private $repo_url_base     = 'https://api.bitbucket.org/2.0/repositories/learndash';
		private $download_url_base = 'https://bitbucket.org/learndash';
		private $readme_url_base   = '';

		/**
		 * Repository Lock file ref.
		 *
		 * @since 3.1.8
		 *
		 * @var file_pointer $lock_repository_fp;
		 */
		private $lock_repository_fp = null;

		public function __construct() {
			add_filter( 'http_request_args', array( $this, 'http_request_args' ), 50, 2 );
		}

		public function init_oauth_key_set() {
			if ( empty( $this->bb_OAuth ) ) {
				$set_key = array_rand( $this->bb_OAuth_sets, 1 );
				if ( ! empty( $set_key ) ) {
					$this->bb_OAuth = array(
						'consumer_key'    => $set_key,
						'consumer_secret' => $this->bb_OAuth_sets[ $set_key ],
					);
				}
			}
		}

		public function get_bb_nonce() {
			$mt   = microtime();
			$rand = mt_rand();

			return md5( $mt . '_' . $rand );
		}

		public function get_repo_base_url() {
			return $this->repo_url_base;
		}

		public function get_download_base_url() {
			return $this->download_url_base;
		}

		public function get_readme_base_url() {
			return $this->readme_url_base;
		}

		public function setup_url_params( $request_url = '' ) {
			if ( ! empty( $request_url ) ) {
				$this->init_oauth_key_set();

				$request_url_params = array(
					'oauth_consumer_key'     => $this->bb_OAuth['consumer_key'],
					'oauth_nonce'            => $this->get_bb_nonce(),
					'oauth_signature_method' => 'HMAC-SHA1',
					'oauth_timestamp'        => time(),
					'oauth_version'          => '1.0',
					'pagelen'                => 100,
				);
				ksort( $request_url_params );

				//The most complicated part of the request - generating the signature.
				//The string to sign contains the HTTP method, the URL path, and all of
				//our query parameters. Everything is URL encoded. Then we concatenate
				//them with ampersands into a single string to hash.
				$encodedVerb   = urlencode( $this->request_method );
				$encodedUrl    = urlencode( $request_url );
				$encodedParams = urlencode( http_build_query( $request_url_params, '', '&' ) );

				$stringToSign = $encodedVerb . '&' . $encodedUrl . '&' . $encodedParams;

				//Since we only have one OAuth token (the consumer secret) we only have
				//to use it as our HMAC key. However, we still have to append an & to it
				//as if we were using it with additional tokens.
				$secret = urlencode( $this->bb_OAuth['consumer_secret'] ) . '&';

				//The signature is a hash of the consumer key and the base string. Note
				//that we have to get the raw output from hash_hmac and base64 encode
				//the binary data result.
				$request_url_params['oauth_signature'] = base64_encode( hash_hmac( 'sha1', $stringToSign, $secret, true ) );

				$request_url .= '?' . http_build_query( $request_url_params );
			}
			return $request_url;
		}

		/**
		 * Filter the WordPress HTTP Request Args.
		 *
		 * This filter is called just before the download_url()
		 * request. We hook into this filter to control the timeout
		 * on the calls to BitBucket.
		 */
		public function http_request_args( $parsed_args = array(), $url = '' ) {
			if ( ! empty( $url ) ) {
				if ( substr( $url, 0, strlen( $this->download_url_base ) ) === $this->download_url_base ) {
					if ( substr( $url, 0, strlen( $this->download_url_base . '/learndash-add-ons/get/stable.zip' ) ) === $this->download_url_base . '/learndash-add-ons/get/stable.zip' ) {
						$parsed_args['timeout'] = LEARNDASH_HTTP_BITBUCKET_README_DOWNLOAD_TIMEOUT;
					}

					/**
					 * Filter the timeout for LearnDash BitBucket downloads.
					 *
					 * @since 3.1.8
					 *
					 * @param int    $timeout     Current timeout in seconds.
					 * @param array  $parsed_args Array of request args.
					 * @param string $url         URL for request.
					 */
					$parsed_args['timeout'] = apply_filters( 'learndash_bitbucket_request_timeout', absint( $parsed_args['timeout'] ), $parsed_args, $url );

				} elseif ( 'https://support.learndash.com' === substr( $url, 0, strlen( 'https://support.learndash.com' ) ) ) {
					if ( ( isset( $parsed_args['method'] ) ) && ( 'GET' === strtoupper( $parsed_args['method'] ) ) ) {
						$parsed_args['timeout'] = LEARNDASH_HTTP_REMOTE_GET_TIMEOUT;
					} else {
						$parsed_args['timeout'] = LEARNDASH_HTTP_REMOTE_POST_TIMEOUT;
					}

					/**
					 * Filter the timeout for LearnDash Support site connections.
					 *
					 * @since 3.1.8
					 *
					 * @param int    $timeout     Current timeout in seconds.
					 * @param array  $parsed_args Array of request args.
					 * @param string $url         URL for request.
					 */
					$parsed_args['timeout'] = apply_filters( 'learndash_support_request_timeout', absint( $parsed_args['timeout'] ), $parsed_args, $url );
				}
			}

			return $parsed_args;
		}

		/**
		 * Get the local add-on directory path.
		 */
		public function get_addon_directory() {
			if ( empty( $this->ld_addons_dir ) ) {
				$wp_upload_dir       = wp_upload_dir();
				$this->ld_addons_dir = trailingslashit( $wp_upload_dir['basedir'] ) . 'learndash/add-ons';
				if ( ! file_exists( $this->ld_addons_dir ) ) {
					if ( wp_mkdir_p( $this->ld_addons_dir ) !== false ) {
						// To prevent security browsing add an index.php file.
						file_put_contents( trailingslashit( $this->ld_addons_dir ) . 'index.php', '// nothing to see here' );
					}
				}
			}

			return $this->ld_addons_dir;
		}

		/**
		 * Get the BitBucket repositories.
		 */
		public function get_bitbucket_repositories( $override_cache = false ) {
			// Clear out the existiing plugins array.
			$repository_data = array();

			if ( true !== $override_cache ) {
				// Get the number of past errors.

				$repo_error_count = 3;
				if ( ( defined( 'LEARNDASH_REPO_ERROR_THRESHOLD_COUNT' ) ) && ( LEARNDASH_REPO_ERROR_THRESHOLD_COUNT > 1 ) ) {
					$repo_error_count = absint( LEARNDASH_REPO_ERROR_THRESHOLD_COUNT );
				}

				$repo_error_time = 7200;
				if ( ( defined( 'LEARNDASH_REPO_ERROR_THRESHOLD_TIME' ) ) && ( LEARNDASH_REPO_ERROR_THRESHOLD_TIME > 1 ) ) {
					$repo_error_time = absint( LEARNDASH_REPO_ERROR_THRESHOLD_TIME );
				}

				$log_error_count = $this->get_error_count_bitbucket_repository( 'learndash-add-ons' );
				if ( absint( $log_error_count ) >= $repo_error_count ) {
					// Check the last time the log file was updated.
					$log_error_time      = $this->get_error_update_timestamp_bitbucket_repository( 'learndash-add-ons' );
					$log_error_time_diff = time() - $log_error_time;
					if ( intval( $log_error_time_diff ) < $repo_error_time ) {
						return $repository_data;
					}

					// If we are over the wait time we can try again. So we clear the log.
					$this->clear_error_bitbucket_repository( 'learndash-add-ons' );
				}
			} else {
				// f we are in cach orderride mode we clear the log.
				$this->clear_error_bitbucket_repository( 'learndash-add-ons' );
				$this->remove_lock_bitbucket_repository( 'learndash-add-ons' );
			}

			// Check if we can lock the processing file.
			if ( ! $this->lock_bitbucket_repository( 'learndash-add-ons' ) ) {
				return $repository_data;
			}

			$request_url = $this->get_bitbucket_repository_download_url( 'learndash-add-ons' );
			$request_url = $this->setup_url_params( $request_url );
			if ( ! empty( $request_url ) ) {
				if ( ! function_exists( 'download_url' ) ) {
					require_once ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'file.php';
				}
				$download = download_url( $request_url );
				if ( ! is_wp_error( $download ) ) {
					if ( is_file( $download ) ) {
						$addon_dest_file = $this->get_addon_directory() . '/learndash-add-ons.zip';
						$copy_ret        = copy( $download, $addon_dest_file );
						@unlink( $download );
						WP_Filesystem();

						$ld_addons_dir_unzip = $this->get_addon_directory() . '/unzip';

						global $wp_filesystem;
						$wp_filesystem->delete( $ld_addons_dir_unzip, true );
						$unzip_ret = unzip_file( $addon_dest_file, $ld_addons_dir_unzip );
						if ( true === $unzip_ret ) {

							$unzip_files = learndash_scandir_recursive( $ld_addons_dir_unzip );
							if ( ! empty( $unzip_files ) ) {
								foreach ( $unzip_files as $unzip_file ) {
									$base_unzip_file = basename( $unzip_file );
									// See if the filename is 'repositories.txt' or ends in '_readme.txt'.
									if ( ( 'repositories.txt' === $base_unzip_file ) || ( '_readme.txt' === substr( $base_unzip_file, -1 * strlen( '_readme.txt' ), strlen( '_readme.txt' ) ) ) ) {
										$unzip_file_dest_txt = $this->get_addon_directory() . '/' . $base_unzip_file;
										//if ( file_exists( ))
										$copy_ret = copy( $unzip_file, $unzip_file_dest_txt );
									}
								}
							}
							$wp_filesystem->delete( $ld_addons_dir_unzip, true );
						}
					}

					$repositories_file = $this->get_addon_directory() . '/repositories.txt';
					if ( is_file( $repositories_file ) ) {
						$body = file_get_contents( $repositories_file );
						if ( ! empty( $body ) ) {
							$repository_data = $this->parse_repository_txt( $body );
						}
					}

					// Clear the log on success.
					$this->clear_error_bitbucket_repository( 'learndash-add-ons' );
				} else {
					$this->error_log_bitbucket_repository( 'learndash-add-ons', $download );
				}

				// Unlock the lock file for other processes.
				$this->unlock_bitbucket_repository( 'learndash-add-ons' );
			}

			return $repository_data;
		}

		// The following function is used to pull the readme.txt file from BitBucket.
		// But this was too costly from a connection standpoint. Plus made editing difficult
		// since a typo or quick change could not be applied to a new update.
		public function get_bitbucket_repository_readme( $plugin_key = '' ) {
			if ( ! empty( $plugin_key ) ) {
				$body = '';
				$code = 0;

				if ( empty( $body ) ) {
					$plugin_readme_file = ABSPATH . DIRECTORY_SEPARATOR . $plugin_key . '.txt';
					if ( file_exists( $plugin_readme_file ) ) {
						$code = 200;
						$body = file_get_contents( $plugin_readme_file );
					}
				}

				if ( empty( $body ) ) {
					$plugin_readme_file = $this->get_addon_directory() . '/' . $plugin_key . '_readme.txt';
					if ( file_exists( $plugin_readme_file ) ) {
						$code = 200;
						$body = file_get_contents( $plugin_readme_file );
					}
				}

				if ( ( 200 === $code ) && ( ! empty( $body ) ) ) {
					$readme_parser = new LearnDashWPReadmeParser();
					$body_parsed   = $readme_parser->parse_readme_contents( $body );

					$plugin_readme_file = $this->get_addon_directory() . '/' . $plugin_key . '_readme.txt';
					if ( ! file_exists( $plugin_readme_file ) ) {
						file_put_contents( $plugin_readme_file, $body );
					}
					//$body_parsed['file'] = str_replace( ABSPATH, '', $plugin_readme_file );
					return $body_parsed;
				}
			}
		}

		/**
		 * Get the Repository Download URL
		 *
		 * @since 3.1.8
		 *
		 * @param string $plugin_key     Plugin Key.
		 * @param string $tag_or_version Repo version or tag.
		 */
		public function get_bitbucket_repository_download_url( $plugin_key = '', $tag_or_version = 'stable' ) {
			if ( ! empty( $plugin_key ) ) {
				$request_url = $this->download_url_base . '/' . $plugin_key . '/get/' . $tag_or_version . '.zip';
				return $request_url;
			}
		}

		/**
		 * Get the Repository Lock file
		 *
		 * @since 3.1.8
		 *
		 * @param string $plugin_key Plugin Key.
		 */
		protected function get_bitbucket_repository_lock_file( $plugin_key = '' ) {
			if ( ! empty( $plugin_key ) ) {
				return $this->get_addon_directory() . '/' . $plugin_key . '.pid';
			}
		}

		/**
		 * Get the Repository Log file
		 *
		 * @since 3.1.8
		 *
		 * @param string $plugin_key Plugin Key.
		 */
		protected function get_bitbucket_repository_log_file( $plugin_key = '' ) {
			if ( ! empty( $plugin_key ) ) {
				return $this->get_addon_directory() . '/' . $plugin_key . '.log';
			}
		}

		/**
		 * Lock the Repository file
		 *
		 * @since 3.1.8
		 *
		 * @param string $plugin_key Plugin Key.
		 */
		protected function lock_bitbucket_repository( $plugin_key = '' ) {
			if ( ! empty( $plugin_key ) ) {
				$addon_lock_file = $this->get_bitbucket_repository_lock_file( $plugin_key );
				if ( ! empty( $addon_lock_file ) ) {
					$this->lock_repository_fp = fopen( $addon_lock_file, 'w+' );
					if ( $this->lock_repository_fp ) {
						if ( flock( $this->lock_repository_fp, LOCK_EX | LOCK_NB ) ) {
							return true;
						}
					}
				}
			}

			return false;
		}

		/**
		 * Remove Lock Repository file
		 *
		 * @since 3.1.8
		 *
		 * @param string $plugin_key Plugin Key.
		 */
		protected function remove_lock_bitbucket_repository( $plugin_key = '' ) {
			if ( ! empty( $plugin_key ) ) {
				$addon_lock_file = $this->get_bitbucket_repository_lock_file( $plugin_key );
				if ( ( ! empty( $addon_lock_file ) ) && ( file_exists( $addon_lock_file ) ) ) {
					@unlink( $addon_lock_file );
				}
			}

			return false;
		}

		/**
		 * Unlock the Repository file
		 *
		 * @since 3.1.8
		 *
		 * @param string $plugin_key Plugin Key.
		 */
		protected function unlock_bitbucket_repository( $plugin_key = '' ) {
			if ( ! empty( $plugin_key ) ) {
				if ( $this->lock_repository_fp ) {
					flock( $this->lock_repository_fp, LOCK_UN );
					$this->remove_lock_bitbucket_repository( $plugin_key );
				}
			}
		}

		/**
		 * Write to the Repository log file
		 *
		 * @since 3.1.8
		 *
		 * @param string $plugin_key Plugin Key.
		 * @param obj    $error      Error object
		 */
		protected function error_log_bitbucket_repository( $plugin_key, $error ) {
			if ( ! empty( $plugin_key ) ) {
				$log_message = '';
				if ( ( property_exists( $error, 'error_data' ) ) && ( is_array( $error->error_data ) ) ) {
					foreach ( $error->error_data as $error_set ) {
						if ( isset( $error_set['code'] ) ) {
							$log_message .= 'Error: ' . absint( $error_set['code'] );
						}
						if ( isset( $error_set['body'] ) ) {
							$log_message .= ' Message: ' . strip_tags( $error_set['body'] );
						}
					}
				}

				if ( ! empty( $log_message ) ) {
					$addon_log_file = $this->get_bitbucket_repository_log_file( $plugin_key );
					if ( ! empty( $addon_log_file ) ) {
						$log_repository_fp = fopen( $addon_log_file, 'a+' );
						if ( $log_repository_fp ) {
							fwrite( $log_repository_fp, $log_message . "\r\n" );
							fclose( $log_repository_fp );
						}
					}
				}
			}
		}

		/**
		 * Clear the Repository log file
		 *
		 * @since 3.1.8
		 *
		 * @param string $plugin_key Plugin Key.
		 */
		protected function clear_error_bitbucket_repository( $plugin_key = '' ) {
			if ( ! empty( $plugin_key ) ) {
				$addon_log_file = $this->get_bitbucket_repository_log_file( $plugin_key );
				if ( ! empty( $addon_log_file ) ) {
					$log_repository_fp = fopen( $addon_log_file, 'w' );
					if ( $log_repository_fp ) {
						fclose( $log_repository_fp );
					}
				}
			}
		}

		/**
		 * Count entries from the Repository log file
		 *
		 * @since 3.1.8
		 *
		 * @param string $plugin_key Plugin Key.
		 */
		protected function get_error_count_bitbucket_repository( $plugin_key = '' ) {
			$log_entries_count = 0;
			if ( ! empty( $plugin_key ) ) {
				$addon_log_file = $this->get_bitbucket_repository_log_file( $plugin_key );
				if ( ( ! empty( $addon_log_file ) ) && ( file_exists( $addon_log_file ) ) ) {
					$log_entries       = file_get_contents( $addon_log_file );
					$log_entries_array = explode( "\n", $log_entries );
					$log_entries_array = array_filter( $log_entries_array );
					$log_entries_count = count( $log_entries_array );
				}
			}

			return $log_entries_count;
		}

		/**
		 * Get last updated timestamp for the Repository log file
		 *
		 * @since 3.1.8
		 *
		 * @param string $plugin_key Plugin Key.
		 */

		protected function get_error_update_timestamp_bitbucket_repository( $plugin_key = '' ) {
			$log_update_timestamp = 0;
			if ( ! empty( $plugin_key ) ) {
				$addon_log_file = $this->get_bitbucket_repository_log_file( $plugin_key );
				if ( ( ! empty( $addon_log_file ) ) && ( file_exists( $addon_log_file ) ) ) {
					$log_update_timestamp = filemtime( $addon_log_file );
				}
			}

			return $log_update_timestamp;
		}


		/* The calling function get_bitbucket_repositories() connects to bitbucket and retrieves
		 * a file respoitories.txt. Each line of the file represents a repository and contains
		 * three fields separated by '|'.
		 * 1. repository slug
		 * 2. current version
		 * 3. last update YYYY-MM-DD hh:mm:ss
		 *
		 * @ since 2.5.7
		 */
		function parse_repository_txt( $file_contents = '' ) {
			$repositories_array = array();

			$file_contents       = str_replace( array( "\r\n", "\r" ), "\n", $file_contents );
			$file_contents       = trim( $file_contents );
			$file_contents_array = preg_split( "/(\r\n|\n|\r)/", $file_contents );
			if ( ! empty( $file_contents_array ) ) {
				foreach ( $file_contents_array as $repo_item_string ) {
					if ( ! empty( $repo_item_string ) ) {
						list( $tmp_array['slug'], $tmp_array['version'], $tmp_array['updated_on'] ) = explode( '|', $repo_item_string );
						$tmp_array = array_map( 'trim', $tmp_array );
						if ( ( isset( $tmp_array['slug'] ) ) && ( ! empty( $tmp_array['slug'] ) ) ) {
							$repositories_array[ $tmp_array['slug'] ] = (object) $tmp_array;
						}
					}
				}
			}

			return $repositories_array;
		}
	}
}

if ( ! class_exists( 'LearnDashWPReadmeParser' ) ) {

	class LearnDashWPReadmeParser {

		function __construct() {
				// This space intentially blank
		}

		function parse_readme( $file ) {
			$file_contents = @implode( '', @file( $file ) );
			return $this->parse_readme_contents( $file_contents );
		}

		function parse_readme_contents( $file_contents ) {
			global $wpdb;

			$readme_sections = array();

			if ( ! empty( $file_contents ) ) {
				$file_contents = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $file_contents );
			}

			if ( ! empty( $file_contents ) ) {
				$file_contents = str_replace( array( "\r\n", "\r" ), "\n", $file_contents );
				$file_contents = trim( $file_contents );
				if ( 0 === strpos( $file_contents, "\xEF\xBB\xBF" ) ) {
					$file_contents = substr( $file_contents, 3 );
				}

				// Markdown transformations
				$file_contents = preg_replace( "|^###([^#]+)#*?\s*?\n|im", '=$1=' . "\n", $file_contents );
				$file_contents = preg_replace( "|^##([^#]+)#*?\s*?\n|im", '==$1==' . "\n", $file_contents );
				$file_contents = preg_replace( "|^#([^#]+)#*?\s*?\n|im", '===$1===' . "\n", $file_contents );

				$file_contents_array = preg_split( "/(\r\n|\n|\r)/", $file_contents );
				if ( ! empty( $file_contents_array ) ) {
					$section_current       = '';
					$_is_short_description = false;
					foreach ( $file_contents_array as $line ) {
						if ( substr( $line, 0, 3 ) == '===' ) {
							$section_current = 'header';
							if ( preg_match( '|^===(.*)===|', $line, $_name ) ) {
								$readme_sections['name'] = $this->sanitize_text( trim( $_name[1] ) );
							}
							continue;

						} elseif ( substr( $line, 0, 2 ) == '==' ) {
							$section_current = '';
							if ( preg_match( '|^==(.*)==|', $line, $_name ) ) {
								$title                               = $this->sanitize_text( trim( $_name[1] ) );
								$section_current                     = str_replace( ' ', '_', strtolower( $title ) );
								$readme_sections[ $section_current ] = array(
									'title'       => $title,
									'content_raw' => '',
									'content'     => '',
								);
								continue;
							}
						}

						if ( ! empty( $section_current ) ) {
							if ( $section_current == 'header' ) {
								if ( ! empty( $line ) ) {
									if ( $_is_short_description == true ) {
										$short_desc_filtered                  = $this->sanitize_text( $line );
										$short_desc_length                    = strlen( $short_desc_filtered );
										$short_description                    = substr( $short_desc_filtered, 0, 150 );
										$readme_sections['short_description'] = $short_description;

										if ( $short_desc_length > strlen( $short_description ) ) {
											$truncated = true;
										} else {
											$truncated = false;
										}
										$readme_sections['is_truncated'] = $truncated;

									} else {
										$line_parts = explode( ':', $line, 2 );
										if ( count( $line_parts ) > 1 ) {
											$title                     = $this->sanitize_text( trim( $line_parts[0] ) );
											$title                     = str_replace( ' ', '_', strtolower( $title ) );
											$readme_sections[ $title ] = trim( $line_parts[1] );
										}
									}
								} else {
									$_is_short_description = true;
								}
							} elseif ( isset( $readme_sections[ $section_current ]['content_raw'] ) ) {
								if ( ( empty( $line ) ) && ( empty( $readme_sections[ $section_current ]['content_raw'] ) ) ) {
									continue;
								} else {
									$readme_sections[ $section_current ]['content_raw'] .= $line . "\r\n";
								}
							}
						}
					}
				}

				if ( ( isset( $readme_sections['tags'] ) ) && ( ! empty( $readme_sections['tags'] ) ) ) {
					$tags_str                = $readme_sections['tags'];
					$readme_sections['tags'] = array();

					$tags_array = preg_split( '|,[\s]*?|', trim( $tags_str ) );
					if ( count( $tags_array ) > 1 ) {
						//foreach ( array_keys( $tags_array ) as $t ) {
						//	$readme_sections['tags'][$t] = $this->sanitize_text( $tags_array[$t] );
						//}
						$readme_sections['tags'] = array_map( array( $this, 'sanitize_text' ), $tags_array );

					}
				} else {
					$readme_sections['tags'] = array();
				}

				/*
				if ( ( isset( $readme_sections['contributors'] ) ) && ( !empty( $readme_sections['contributors'] ) ) ) {
					$contributors_str = $readme_sections['contributors'];
					$readme_sections['contributors'] = array();

					$contributors_array = preg_split('|,[\s]*?|', trim( $contributors_str ) );
					if ( count( $contributors_array ) > 1 ) {
						//foreach ( array_keys( $contributors_array ) as $t ) {
						//  $readme_sections['contributors'][$t] = $this->user_sanitize( $contributors_array[$t] );
						//}
						$readme_sections['contributors'] = array_map( array( $this, 'user_sanitize'), $contributors_array );
					}
				} else {
				*/
					$readme_sections['contributors'] = array();
				//}

				foreach ( array( 'changelog', 'upgrade_notice', 'upgrade_notice_admin' ) as $section_key ) {
					if ( ( isset( $readme_sections[ $section_key ]['content_raw'] ) ) && ( ! empty( $readme_sections[ $section_key ]['content_raw'] ) ) ) {
						$split                                      = preg_split( '#=(.*?)=#', $readme_sections[ $section_key ]['content_raw'], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
						$readme_sections[ $section_key ]['content'] = array();
						for ( $i = 0; $i < count( $split ); $i += 2 ) {
							$readme_sections[ $section_key ]['content'][ $this->sanitize_text( $split[ $i ] ) ] = $this->filter_text( $this->sanitize_text( $split[ $i + 1 ] ), true );
						}
					}
				}

				foreach ( array( 'icons', 'banners' ) as $section_key ) {
					if ( ( isset( $readme_sections[ $section_key ]['content_raw'] ) ) && ( ! empty( $readme_sections[ $section_key ]['content_raw'] ) ) ) {
						$items = explode( PHP_EOL, $readme_sections[ $section_key ]['content_raw'] );
						if ( ! empty( $items ) ) {
							$readme_sections[ $section_key ] = array();
							foreach ( $items as $item ) {
								$item = trim( $item );
								if ( ! empty( $item ) ) {
									$item_split                                        = explode( ':', $item, 2 );
									$readme_sections[ $section_key ][ $item_split[0] ] = $item_split[1];
								}
							}
						}
					}
				}

				$readme_sections['sections'] = array();

				foreach ( array( 'description', 'installation', 'frequently_asked_questions', 'changelog', 'arbitrary_section' ) as $section_key ) {
					if ( ( isset( $readme_sections[ $section_key ]['content_raw'] ) ) && ( ! empty( $readme_sections[ $section_key ]['content_raw'] ) ) ) {
						$readme_sections[ $section_key ]['content'] = preg_replace( '/^[\s]*=[\s]+(.+?)[\s]+=/m', '<h4>$1</h4>', $readme_sections[ $section_key ]['content_raw'] );
						$readme_sections[ $section_key ]['content'] = $this->filter_text( $readme_sections[ $section_key ]['content'], true );

						$readme_sections['sections'][ $section_key ] = $readme_sections[ $section_key ]['content'];
					}
				}
			}
			return $readme_sections;
		}

		function sanitize_text( $text ) {
			// not fancy
			$text = strip_tags( $text );
			$text = esc_html( $text );
			$text = trim( $text );
			return $text;
		}

		function filter_text( $text, $markdown = false ) {
			// fancy, Markdown
			$text = trim( $text );

				$text = call_user_func( array( __CLASS__, 'code_trick' ), $text, $markdown ); // A better parser than Markdown's for: backticks -> CODE

			//if ( $markdown ) { // Parse markdown.
			//	if ( !function_exists('Markdown') )
			//		require( WORDPRESS_README_MARKDOWN );
			//	$text = Markdown($text);
			//}
			if ( $markdown ) { // Parse markdown.
				//if ( !class_exists('LeanDashParsedown', false) ) {
				//	/** @noinspection PhpIncludeInspection */
				//	require_once(dirname(__FILE__) . '/Parsedown' . (version_compare(PHP_VERSION, '5.3.0', '>=') ? '' : 'Legacy') . '.php');
				//}
				$instance = LeanDashParsedown::instance();
				$text     = $instance->text( $text );
			}

			$allowed = array(
				'a'          => array(
					'href'  => array(),
					'title' => array(),
					'rel'   => array(),
				),
				'blockquote' => array( 'cite' => array() ),
				'br'         => array(),
				'p'          => array(),
				'code'       => array(),
				'pre'        => array(),
				'em'         => array(),
				'strong'     => array(),
				'ul'         => array(),
				'ol'         => array(),
				'li'         => array(),
				'h3'         => array(),
				'h4'         => array(),
			);

			$text = balanceTags( $text );

			$text = wp_kses( $text, $allowed );
			$text = trim( $text );
			return $text;
		}

		function code_trick( $text, $markdown ) {
			// Don't use bbPress native function - it's incompatible with Markdown
			// If doing markdown, first take any user formatted code blocks and turn them into backticks so that
			// markdown will preserve things like underscores in code blocks
			if ( $markdown ) {
				$text = preg_replace_callback( '!(<pre><code>|<code>)(.*?)(</code></pre>|</code>)!s', array( __CLASS__, 'decodeit' ), $text );
			}

			$text = str_replace( array( "\r\n", "\r" ), "\n", $text );
			if ( ! $markdown ) {
				// This gets the "inline" code blocks, but can't be used with Markdown.
				$text = preg_replace_callback( '|(`)(.*?)`|', array( __CLASS__, 'encodeit' ), $text );
				// This gets the "block level" code blocks and converts them to PRE CODE
				$text = preg_replace_callback( "!(^|\n)`(.*?)`!s", array( __CLASS__, 'encodeit' ), $text );
			} else {
				// Markdown can do inline code, we convert bbPress style block level code to Markdown style
				$text = preg_replace_callback( "!(^|\n)([ \t]*?)`(.*?)`!s", array( __CLASS__, 'indent' ), $text );
			}
			return $text;
		}

		function indent( $matches ) {
			$text = $matches[3];
			$text = preg_replace( '|^|m', $matches[2] . '    ', $text );
			return $matches[1] . $text;
		}

		function encodeit( $matches ) {
			if ( function_exists( 'encodeit' ) ) { // bbPress native
				return encodeit( $matches );
			}

			$text = trim( $matches[2] );
			$text = htmlspecialchars( $text, ENT_QUOTES );
			$text = str_replace( array( "\r\n", "\r" ), "\n", $text );
			$text = preg_replace( "|\n\n\n+|", "\n\n", $text );
			$text = str_replace( '&amp;lt;', '&lt;', $text );
			$text = str_replace( '&amp;gt;', '&gt;', $text );
			$text = "<code>$text</code>";
			if ( '`' != $matches[1] ) {
				$text = "<pre>$text</pre>";
			}
			return $text;
		}

		function decodeit( $matches ) {
			if ( function_exists( 'decodeit' ) ) { // bbPress native
				return decodeit( $matches );
			}

			$text        = $matches[2];
			$trans_table = array_flip( get_html_translation_table( HTML_ENTITIES ) );
			$text        = strtr( $text, $trans_table );
			$text        = str_replace( '<br />', '', $text );
			$text        = str_replace( '&#38;', '&', $text );
			$text        = str_replace( '&#39;', "'", $text );
			if ( '<pre><code>' == $matches[1] ) {
				$text = "\n$text\n";
			}
			return "`$text`";
		}
	}
	#
	#
	# LeanDashParsedown
	# http://parsedown.org
	#
	# (c) Emanuil Rusev
	# http://erusev.com
	#
	# For the full license information, view the LICENSE file that was distributed
	# with this source code.
	#
	#

	class LeanDashParsedown {

		# ~

		const version = '1.6.0';

		# ~

		function text( $text ) {
			# make sure no definitions are set
			$this->DefinitionData = array();

			# standardize line breaks
			$text = str_replace( array( "\r\n", "\r" ), "\n", $text );

			# remove surrounding line breaks
			$text = trim( $text, "\n" );

			# split text into lines
			$lines = explode( "\n", $text );

			# iterate through lines to identify blocks
			$markup = $this->lines( $lines );

			# trim line breaks
			$markup = trim( $markup, "\n" );

			return $markup;
		}

		#
		# Setters
		#

		function setBreaksEnabled( $breaksEnabled ) {
			$this->breaksEnabled = $breaksEnabled;

			return $this;
		}

		protected $breaksEnabled;

		function setMarkupEscaped( $markupEscaped ) {
			$this->markupEscaped = $markupEscaped;

			return $this;
		}

		protected $markupEscaped;

		function setUrlsLinked( $urlsLinked ) {
			$this->urlsLinked = $urlsLinked;

			return $this;
		}

		protected $urlsLinked = true;

		#
		# Lines
		#

		protected $BlockTypes = array(
			'#' => array( 'Header' ),
			'*' => array( 'Rule', 'List' ),
			'+' => array( 'List' ),
			'-' => array( 'SetextHeader', 'Table', 'Rule', 'List' ),
			'0' => array( 'List' ),
			'1' => array( 'List' ),
			'2' => array( 'List' ),
			'3' => array( 'List' ),
			'4' => array( 'List' ),
			'5' => array( 'List' ),
			'6' => array( 'List' ),
			'7' => array( 'List' ),
			'8' => array( 'List' ),
			'9' => array( 'List' ),
			':' => array( 'Table' ),
			'<' => array( 'Comment', 'Markup' ),
			'=' => array( 'SetextHeader' ),
			'>' => array( 'Quote' ),
			'[' => array( 'Reference' ),
			'_' => array( 'Rule' ),
			'`' => array( 'FencedCode' ),
			'|' => array( 'Table' ),
			'~' => array( 'FencedCode' ),
		);

		# ~

		protected $unmarkedBlockTypes = array(
			'Code',
		);

		#
		# Blocks
		#

		protected function lines( array $lines ) {
			$CurrentBlock = null;

			foreach ( $lines as $line ) {
				if ( chop( $line ) === '' ) {
					if ( isset( $CurrentBlock ) ) {
						$CurrentBlock['interrupted'] = true;
					}

					continue;
				}

				if ( strpos( $line, "\t" ) !== false ) {
					$parts = explode( "\t", $line );

					$line = $parts[0];

					unset( $parts[0] );

					foreach ( $parts as $part ) {
						$shortage = 4 - mb_strlen( $line, 'utf-8' ) % 4;

						$line .= str_repeat( ' ', $shortage );
						$line .= $part;
					}
				}

				$indent = 0;

				while ( isset( $line[ $indent ] ) and $line[ $indent ] === ' ' ) {
					$indent ++;
				}

				$text = $indent > 0 ? substr( $line, $indent ) : $line;

				# ~

				$Line = array(
					'body'   => $line,
					'indent' => $indent,
					'text'   => $text,
				);

				# ~

				if ( isset( $CurrentBlock['continuable'] ) ) {
					$Block = $this->{'block' . $CurrentBlock['type'] . 'Continue'}( $Line, $CurrentBlock );

					if ( isset( $Block ) ) {
						$CurrentBlock = $Block;

						continue;
					} else {
						if ( $this->isBlockCompletable( $CurrentBlock['type'] ) ) {
							$CurrentBlock = $this->{'block' . $CurrentBlock['type'] . 'Complete'}( $CurrentBlock );
						}
					}
				}

				# ~

				$marker = $text[0];

				# ~

				$blockTypes = $this->unmarkedBlockTypes;

				if ( isset( $this->BlockTypes[ $marker ] ) ) {
					foreach ( $this->BlockTypes[ $marker ] as $blockType ) {
						$blockTypes [] = $blockType;
					}
				}

				#
				# ~

				foreach ( $blockTypes as $blockType ) {
					$Block = $this->{'block' . $blockType}( $Line, $CurrentBlock );

					if ( isset( $Block ) ) {
						$Block['type'] = $blockType;

						if ( ! isset( $Block['identified'] ) ) {
							$Blocks [] = $CurrentBlock;

							$Block['identified'] = true;
						}

						if ( $this->isBlockContinuable( $blockType ) ) {
							$Block['continuable'] = true;
						}

						$CurrentBlock = $Block;

						continue 2;
					}
				}

				# ~

				if ( isset( $CurrentBlock ) and ! isset( $CurrentBlock['type'] ) and ! isset( $CurrentBlock['interrupted'] ) ) {
					$CurrentBlock['element']['text'] .= "\n" . $text;
				} else {
					$Blocks [] = $CurrentBlock;

					$CurrentBlock = $this->paragraph( $Line );

					$CurrentBlock['identified'] = true;
				}
			}

			# ~

			if ( isset( $CurrentBlock['continuable'] ) and $this->isBlockCompletable( $CurrentBlock['type'] ) ) {
				$CurrentBlock = $this->{'block' . $CurrentBlock['type'] . 'Complete'}( $CurrentBlock );
			}

			# ~

			$Blocks [] = $CurrentBlock;

			unset( $Blocks[0] );

			# ~

			$markup = '';

			foreach ( $Blocks as $Block ) {
				if ( isset( $Block['hidden'] ) ) {
					continue;
				}

				$markup .= "\n";
				$markup .= isset( $Block['markup'] ) ? $Block['markup'] : $this->element( $Block['element'] );
			}

			$markup .= "\n";

			# ~

			return $markup;
		}

		protected function isBlockContinuable( $Type ) {
			return method_exists( $this, 'block' . $Type . 'Continue' );
		}

		protected function isBlockCompletable( $Type ) {
			return method_exists( $this, 'block' . $Type . 'Complete' );
		}

		#
		# Code

		protected function blockCode( $Line, $Block = null ) {
			if ( isset( $Block ) and ! isset( $Block['type'] ) and ! isset( $Block['interrupted'] ) ) {
				return;
			}

			if ( $Line['indent'] >= 4 ) {
				$text = substr( $Line['body'], 4 );

				$Block = array(
					'element' => array(
						'name'    => 'pre',
						'handler' => 'element',
						'text'    => array(
							'name' => 'code',
							'text' => $text,
						),
					),
				);

				return $Block;
			}
		}

		protected function blockCodeContinue( $Line, $Block ) {
			if ( $Line['indent'] >= 4 ) {
				if ( isset( $Block['interrupted'] ) ) {
					$Block['element']['text']['text'] .= "\n";

					unset( $Block['interrupted'] );
				}

				$Block['element']['text']['text'] .= "\n";

				$text = substr( $Line['body'], 4 );

				$Block['element']['text']['text'] .= $text;

				return $Block;
			}
		}

		protected function blockCodeComplete( $Block ) {
			$text = $Block['element']['text']['text'];

			$text = htmlspecialchars( $text, ENT_NOQUOTES, 'UTF-8' );

			$Block['element']['text']['text'] = $text;

			return $Block;
		}

		#
		# Comment

		protected function blockComment( $Line ) {
			if ( $this->markupEscaped ) {
				return;
			}

			if ( isset( $Line['text'][3] ) and $Line['text'][3] === '-' and $Line['text'][2] === '-' and $Line['text'][1] === '!' ) {
				$Block = array(
					'markup' => $Line['body'],
				);

				if ( preg_match( '/-->$/', $Line['text'] ) ) {
					$Block['closed'] = true;
				}

				return $Block;
			}
		}

		protected function blockCommentContinue( $Line, array $Block ) {
			if ( isset( $Block['closed'] ) ) {
				return;
			}

			$Block['markup'] .= "\n" . $Line['body'];

			if ( preg_match( '/-->$/', $Line['text'] ) ) {
				$Block['closed'] = true;
			}

			return $Block;
		}

		#
		# Fenced Code

		protected function blockFencedCode( $Line ) {
			if ( preg_match( '/^[' . $Line['text'][0] . ']{3,}[ ]*([\w-]+)?[ ]*$/', $Line['text'], $matches ) ) {
				$Element = array(
					'name' => 'code',
					'text' => '',
				);

				if ( isset( $matches[1] ) ) {
					$class = 'language-' . $matches[1];

					$Element['attributes'] = array(
						'class' => $class,
					);
				}

				$Block = array(
					'char'    => $Line['text'][0],
					'element' => array(
						'name'    => 'pre',
						'handler' => 'element',
						'text'    => $Element,
					),
				);

				return $Block;
			}
		}

		protected function blockFencedCodeContinue( $Line, $Block ) {
			if ( isset( $Block['complete'] ) ) {
				return;
			}

			if ( isset( $Block['interrupted'] ) ) {
				$Block['element']['text']['text'] .= "\n";

				unset( $Block['interrupted'] );
			}

			if ( preg_match( '/^' . $Block['char'] . '{3,}[ ]*$/', $Line['text'] ) ) {
				$Block['element']['text']['text'] = substr( $Block['element']['text']['text'], 1 );

				$Block['complete'] = true;

				return $Block;
			}

			$Block['element']['text']['text'] .= "\n" . $Line['body'];

			return $Block;
		}

		protected function blockFencedCodeComplete( $Block ) {
			$text = $Block['element']['text']['text'];

			$text = htmlspecialchars( $text, ENT_NOQUOTES, 'UTF-8' );

			$Block['element']['text']['text'] = $text;

			return $Block;
		}

		#
		# Header

		protected function blockHeader( $Line ) {
			if ( isset( $Line['text'][1] ) ) {
				$level = 1;

				while ( isset( $Line['text'][ $level ] ) and $Line['text'][ $level ] === '#' ) {
					$level ++;
				}

				if ( $level > 6 ) {
					return;
				}

				$text = trim( $Line['text'], '# ' );

				$Block = array(
					'element' => array(
						'name'    => 'h' . min( 6, $level ),
						'text'    => $text,
						'handler' => 'line',
					),
				);

				return $Block;
			}
		}

		#
		# List

		protected function blockList( $Line ) {
			list($name, $pattern) = $Line['text'][0] <= '-' ? array( 'ul', '[*+-]' ) : array( 'ol', '[0-9]+[.]' );

			if ( preg_match( '/^(' . $pattern . '[ ]+)(.*)/', $Line['text'], $matches ) ) {
				$Block = array(
					'indent'  => $Line['indent'],
					'pattern' => $pattern,
					'element' => array(
						'name'    => $name,
						'handler' => 'elements',
					),
				);

				$Block['li'] = array(
					'name'    => 'li',
					'handler' => 'li',
					'text'    => array(
						$matches[2],
					),
				);

				$Block['element']['text'] [] = & $Block['li'];

				return $Block;
			}
		}

		protected function blockListContinue( $Line, array $Block ) {
			if ( $Block['indent'] === $Line['indent'] and preg_match( '/^' . $Block['pattern'] . '(?:[ ]+(.*)|$)/', $Line['text'], $matches ) ) {
				if ( isset( $Block['interrupted'] ) ) {
					$Block['li']['text'] [] = '';

					unset( $Block['interrupted'] );
				}

				unset( $Block['li'] );

				$text = isset( $matches[1] ) ? $matches[1] : '';

				$Block['li'] = array(
					'name'    => 'li',
					'handler' => 'li',
					'text'    => array(
						$text,
					),
				);

				$Block['element']['text'] [] = & $Block['li'];

				return $Block;
			}

			if ( $Line['text'][0] === '[' and $this->blockReference( $Line ) ) {
				return $Block;
			}

			if ( ! isset( $Block['interrupted'] ) ) {
				$text = preg_replace( '/^[ ]{0,4}/', '', $Line['body'] );

				$Block['li']['text'] [] = $text;

				return $Block;
			}

			if ( $Line['indent'] > 0 ) {
				$Block['li']['text'] [] = '';

				$text = preg_replace( '/^[ ]{0,4}/', '', $Line['body'] );

				$Block['li']['text'] [] = $text;

				unset( $Block['interrupted'] );

				return $Block;
			}
		}

		#
		# Quote

		protected function blockQuote( $Line ) {
			if ( preg_match( '/^>[ ]?(.*)/', $Line['text'], $matches ) ) {
				$Block = array(
					'element' => array(
						'name'    => 'blockquote',
						'handler' => 'lines',
						'text'    => (array) $matches[1],
					),
				);

				return $Block;
			}
		}

		protected function blockQuoteContinue( $Line, array $Block ) {
			if ( $Line['text'][0] === '>' and preg_match( '/^>[ ]?(.*)/', $Line['text'], $matches ) ) {
				if ( isset( $Block['interrupted'] ) ) {
					$Block['element']['text'] [] = '';

					unset( $Block['interrupted'] );
				}

				$Block['element']['text'] [] = $matches[1];

				return $Block;
			}

			if ( ! isset( $Block['interrupted'] ) ) {
				$Block['element']['text'] [] = $Line['text'];

				return $Block;
			}
		}

		#
		# Rule

		protected function blockRule( $Line ) {
			if ( preg_match( '/^([' . $Line['text'][0] . '])([ ]*\1){2,}[ ]*$/', $Line['text'] ) ) {
				$Block = array(
					'element' => array(
						'name' => 'hr',
					),
				);

				return $Block;
			}
		}

		#
		# Setext

		protected function blockSetextHeader( $Line, array $Block = null ) {
			if ( ! isset( $Block ) or isset( $Block['type'] ) or isset( $Block['interrupted'] ) ) {
				return;
			}

			if ( chop( $Line['text'], $Line['text'][0] ) === '' ) {
				$Block['element']['name'] = $Line['text'][0] === '=' ? 'h1' : 'h2';

				return $Block;
			}
		}

		#
		# Markup

		protected function blockMarkup( $Line ) {
			if ( $this->markupEscaped ) {
				return;
			}

			if ( preg_match( '/^<(\w*)(?:[ ]*' . $this->regexHtmlAttribute . ')*[ ]*(\/)?>/', $Line['text'], $matches ) ) {
				$element = strtolower( $matches[1] );

				if ( in_array( $element, $this->textLevelElements ) ) {
					return;
				}

				$Block = array(
					'name'   => $matches[1],
					'depth'  => 0,
					'markup' => $Line['text'],
				);

				$length = strlen( $matches[0] );

				$remainder = substr( $Line['text'], $length );

				if ( trim( $remainder ) === '' ) {
					if ( isset( $matches[2] ) or in_array( $matches[1], $this->voidElements ) ) {
						$Block['closed'] = true;

						$Block['void'] = true;
					}
				} else {
					if ( isset( $matches[2] ) or in_array( $matches[1], $this->voidElements ) ) {
						return;
					}

					if ( preg_match( '/<\/' . $matches[1] . '>[ ]*$/i', $remainder ) ) {
						$Block['closed'] = true;
					}
				}

				return $Block;
			}
		}

		protected function blockMarkupContinue( $Line, array $Block ) {
			if ( isset( $Block['closed'] ) ) {
				return;
			}

			if ( preg_match( '/^<' . $Block['name'] . '(?:[ ]*' . $this->regexHtmlAttribute . ')*[ ]*>/i', $Line['text'] ) ) { # open
				$Block['depth'] ++;
			}

			if ( preg_match( '/(.*?)<\/' . $Block['name'] . '>[ ]*$/i', $Line['text'], $matches ) ) { # close
				if ( $Block['depth'] > 0 ) {
					$Block['depth'] --;
				} else {
					$Block['closed'] = true;
				}
			}

			if ( isset( $Block['interrupted'] ) ) {
				$Block['markup'] .= "\n";

				unset( $Block['interrupted'] );
			}

			$Block['markup'] .= "\n" . $Line['body'];

			return $Block;
		}

		#
		# Reference

		protected function blockReference( $Line ) {
			if ( preg_match( '/^\[(.+?)\]:[ ]*<?(\S+?)>?(?:[ ]+["\'(](.+)["\')])?[ ]*$/', $Line['text'], $matches ) ) {
				$id = strtolower( $matches[1] );

				$Data = array(
					'url'   => $matches[2],
					'title' => null,
				);

				if ( isset( $matches[3] ) ) {
					$Data['title'] = $matches[3];
				}

				$this->DefinitionData['Reference'][ $id ] = $Data;

				$Block = array(
					'hidden' => true,
				);

				return $Block;
			}
		}

		#
		# Table

		protected function blockTable( $Line, array $Block = null ) {
			if ( ! isset( $Block ) or isset( $Block['type'] ) or isset( $Block['interrupted'] ) ) {
				return;
			}

			if ( strpos( $Block['element']['text'], '|' ) !== false and chop( $Line['text'], ' -:|' ) === '' ) {
				$alignments = array();

				$divider = $Line['text'];

				$divider = trim( $divider );
				$divider = trim( $divider, '|' );

				$dividerCells = explode( '|', $divider );

				foreach ( $dividerCells as $dividerCell ) {
					$dividerCell = trim( $dividerCell );

					if ( $dividerCell === '' ) {
						continue;
					}

					$alignment = null;

					if ( $dividerCell[0] === ':' ) {
						$alignment = 'left';
					}

					if ( substr( $dividerCell, - 1 ) === ':' ) {
						$alignment = $alignment === 'left' ? 'center' : 'right';
					}

					$alignments [] = $alignment;
				}

				# ~

				$HeaderElements = array();

				$header = $Block['element']['text'];

				$header = trim( $header );
				$header = trim( $header, '|' );

				$headerCells = explode( '|', $header );

				foreach ( $headerCells as $index => $headerCell ) {
					$headerCell = trim( $headerCell );

					$HeaderElement = array(
						'name'    => 'th',
						'text'    => $headerCell,
						'handler' => 'line',
					);

					if ( isset( $alignments[ $index ] ) ) {
						$alignment = $alignments[ $index ];

						$HeaderElement['attributes'] = array(
							'style' => 'text-align: ' . $alignment . ';',
						);
					}

					$HeaderElements [] = $HeaderElement;
				}

				# ~

				$Block = array(
					'alignments' => $alignments,
					'identified' => true,
					'element'    => array(
						'name'    => 'table',
						'handler' => 'elements',
					),
				);

				$Block['element']['text'] [] = array(
					'name'    => 'thead',
					'handler' => 'elements',
				);

				$Block['element']['text'] [] = array(
					'name'    => 'tbody',
					'handler' => 'elements',
					'text'    => array(),
				);

				$Block['element']['text'][0]['text'] [] = array(
					'name'    => 'tr',
					'handler' => 'elements',
					'text'    => $HeaderElements,
				);

				return $Block;
			}
		}

		protected function blockTableContinue( $Line, array $Block ) {
			if ( isset( $Block['interrupted'] ) ) {
				return;
			}

			if ( $Line['text'][0] === '|' or strpos( $Line['text'], '|' ) ) {
				$Elements = array();

				$row = $Line['text'];

				$row = trim( $row );
				$row = trim( $row, '|' );

				preg_match_all( '/(?:(\\\\[|])|[^|`]|`[^`]+`|`)+/', $row, $matches );

				foreach ( $matches[0] as $index => $cell ) {
					$cell = trim( $cell );

					$Element = array(
						'name'    => 'td',
						'handler' => 'line',
						'text'    => $cell,
					);

					if ( isset( $Block['alignments'][ $index ] ) ) {
						$Element['attributes'] = array(
							'style' => 'text-align: ' . $Block['alignments'][ $index ] . ';',
						);
					}

					$Elements [] = $Element;
				}

				$Element = array(
					'name'    => 'tr',
					'handler' => 'elements',
					'text'    => $Elements,
				);

				$Block['element']['text'][1]['text'] [] = $Element;

				return $Block;
			}
		}

		#
		# ~
		#

		protected function paragraph( $Line ) {
			$Block = array(
				'element' => array(
					'name'    => 'p',
					'text'    => $Line['text'],
					'handler' => 'line',
				),
			);

			return $Block;
		}

		#
		# Inline Elements
		#

		protected $InlineTypes = array(
			'"'  => array( 'SpecialCharacter' ),
			'!'  => array( 'Image' ),
			'&'  => array( 'SpecialCharacter' ),
			'*'  => array( 'Emphasis' ),
			':'  => array( 'Url' ),
			'<'  => array( 'UrlTag', 'EmailTag', 'Markup', 'SpecialCharacter' ),
			'>'  => array( 'SpecialCharacter' ),
			'['  => array( 'Link' ),
			'_'  => array( 'Emphasis' ),
			'`'  => array( 'Code' ),
			'~'  => array( 'Strikethrough' ),
			'\\' => array( 'EscapeSequence' ),
		);

		# ~

		protected $inlineMarkerList = '!"*_&[:<>`~\\';

		#
		# ~
		#

		public function line( $text ) {
			$markup = '';

			# $excerpt is based on the first occurrence of a marker

			while ( $excerpt = strpbrk( $text, $this->inlineMarkerList ) ) {
				$marker = $excerpt[0];

				$markerPosition = strpos( $text, $marker );

				$Excerpt = array(
					'text'    => $excerpt,
					'context' => $text,
				);

				foreach ( $this->InlineTypes[ $marker ] as $inlineType ) {
					$Inline = $this->{'inline' . $inlineType}( $Excerpt );

					if ( ! isset( $Inline ) ) {
						continue;
					}

					# makes sure that the inline belongs to "our" marker

					if ( isset( $Inline['position'] ) and $Inline['position'] > $markerPosition ) {
						continue;
					}

					# sets a default inline position

					if ( ! isset( $Inline['position'] ) ) {
						$Inline['position'] = $markerPosition;
					}

					# the text that comes before the inline
					$unmarkedText = substr( $text, 0, $Inline['position'] );

					# compile the unmarked text
					$markup .= $this->unmarkedText( $unmarkedText );

					# compile the inline
					$markup .= isset( $Inline['markup'] ) ? $Inline['markup'] : $this->element( $Inline['element'] );

					# remove the examined text
					$text = substr( $text, $Inline['position'] + $Inline['extent'] );

					continue 2;
				}

				# the marker does not belong to an inline

				$unmarkedText = substr( $text, 0, $markerPosition + 1 );

				$markup .= $this->unmarkedText( $unmarkedText );

				$text = substr( $text, $markerPosition + 1 );
			}

			$markup .= $this->unmarkedText( $text );

			return $markup;
		}

		#
		# ~
		#

		protected function inlineCode( $Excerpt ) {
			$marker = $Excerpt['text'][0];

			if ( preg_match( '/^(' . $marker . '+)[ ]*(.+?)[ ]*(?<!' . $marker . ')\1(?!' . $marker . ')/s', $Excerpt['text'], $matches ) ) {
				$text = $matches[2];
				$text = htmlspecialchars( $text, ENT_NOQUOTES, 'UTF-8' );
				$text = preg_replace( "/[ ]*\n/", ' ', $text );

				return array(
					'extent'  => strlen( $matches[0] ),
					'element' => array(
						'name' => 'code',
						'text' => $text,
					),
				);
			}
		}

		protected function inlineEmailTag( $Excerpt ) {
			if ( strpos( $Excerpt['text'], '>' ) !== false and preg_match( '/^<((mailto:)?\S+?@\S+?)>/i', $Excerpt['text'], $matches ) ) {
				$url = $matches[1];

				if ( ! isset( $matches[2] ) ) {
					$url = 'mailto:' . $url;
				}

				return array(
					'extent'  => strlen( $matches[0] ),
					'element' => array(
						'name'       => 'a',
						'text'       => $matches[1],
						'attributes' => array(
							'href' => $url,
						),
					),
				);
			}
		}

		protected function inlineEmphasis( $Excerpt ) {
			if ( ! isset( $Excerpt['text'][1] ) ) {
				return;
			}

			$marker = $Excerpt['text'][0];

			if ( $Excerpt['text'][1] === $marker and preg_match( $this->StrongRegex[ $marker ], $Excerpt['text'], $matches ) ) {
				$emphasis = 'strong';
			} elseif ( preg_match( $this->EmRegex[ $marker ], $Excerpt['text'], $matches ) ) {
				$emphasis = 'em';
			} else {
				return;
			}

			return array(
				'extent'  => strlen( $matches[0] ),
				'element' => array(
					'name'    => $emphasis,
					'handler' => 'line',
					'text'    => $matches[1],
				),
			);
		}

		protected function inlineEscapeSequence( $Excerpt ) {
			if ( isset( $Excerpt['text'][1] ) and in_array( $Excerpt['text'][1], $this->specialCharacters ) ) {
				return array(
					'markup' => $Excerpt['text'][1],
					'extent' => 2,
				);
			}
		}

		protected function inlineImage( $Excerpt ) {
			if ( ! isset( $Excerpt['text'][1] ) or $Excerpt['text'][1] !== '[' ) {
				return;
			}

			$Excerpt['text'] = substr( $Excerpt['text'], 1 );

			$Link = $this->inlineLink( $Excerpt );

			if ( $Link === null ) {
				return;
			}

			$Inline = array(
				'extent'  => $Link['extent'] + 1,
				'element' => array(
					'name'       => 'img',
					'attributes' => array(
						'src' => $Link['element']['attributes']['href'],
						'alt' => $Link['element']['text'],
					),
				),
			);

			$Inline['element']['attributes'] += $Link['element']['attributes'];

			unset( $Inline['element']['attributes']['href'] );

			return $Inline;
		}

		protected function inlineLink( $Excerpt ) {
			$Element = array(
				'name'       => 'a',
				'handler'    => 'line',
				'text'       => null,
				'attributes' => array(
					'href'  => null,
					'title' => null,
				),
			);

			$extent = 0;

			$remainder = $Excerpt['text'];

			if ( preg_match( '/\[((?:[^][]|(?R))*)\]/', $remainder, $matches ) ) {
				$Element['text'] = $matches[1];

				$extent += strlen( $matches[0] );

				$remainder = substr( $remainder, $extent );
			} else {
				return;
			}

			if ( preg_match( '/^[(]((?:[^ ()]|[(][^ )]+[)])+)(?:[ ]+("[^"]*"|\'[^\']*\'))?[)]/', $remainder, $matches ) ) {
				$Element['attributes']['href'] = $matches[1];

				if ( isset( $matches[2] ) ) {
					$Element['attributes']['title'] = substr( $matches[2], 1, - 1 );
				}

				$extent += strlen( $matches[0] );
			} else {
				if ( preg_match( '/^\s*\[(.*?)\]/', $remainder, $matches ) ) {
					$definition = strlen( $matches[1] ) ? $matches[1] : $Element['text'];
					$definition = strtolower( $definition );

					$extent += strlen( $matches[0] );
				} else {
					$definition = strtolower( $Element['text'] );
				}

				if ( ! isset( $this->DefinitionData['Reference'][ $definition ] ) ) {
					return;
				}

				$Definition = $this->DefinitionData['Reference'][ $definition ];

				$Element['attributes']['href']  = $Definition['url'];
				$Element['attributes']['title'] = $Definition['title'];
			}

			$Element['attributes']['href'] = str_replace( array( '&', '<' ), array( '&amp;', '&lt;' ), $Element['attributes']['href'] );

			return array(
				'extent'  => $extent,
				'element' => $Element,
			);
		}

		protected function inlineMarkup( $Excerpt ) {
			if ( $this->markupEscaped or strpos( $Excerpt['text'], '>' ) === false ) {
				return;
			}

			if ( $Excerpt['text'][1] === '/' and preg_match( '/^<\/\w*[ ]*>/s', $Excerpt['text'], $matches ) ) {
				return array(
					'markup' => $matches[0],
					'extent' => strlen( $matches[0] ),
				);
			}

			if ( $Excerpt['text'][1] === '!' and preg_match( '/^<!---?[^>-](?:-?[^-])*-->/s', $Excerpt['text'], $matches ) ) {
				return array(
					'markup' => $matches[0],
					'extent' => strlen( $matches[0] ),
				);
			}

			if ( $Excerpt['text'][1] !== ' ' and preg_match( '/^<\w*(?:[ ]*' . $this->regexHtmlAttribute . ')*[ ]*\/?>/s', $Excerpt['text'], $matches ) ) {
				return array(
					'markup' => $matches[0],
					'extent' => strlen( $matches[0] ),
				);
			}
		}

		protected function inlineSpecialCharacter( $Excerpt ) {
			if ( $Excerpt['text'][0] === '&' and ! preg_match( '/^&#?\w+;/', $Excerpt['text'] ) ) {
				return array(
					'markup' => '&amp;',
					'extent' => 1,
				);
			}

			$SpecialCharacter = array(
				'>' => 'gt',
				'<' => 'lt',
				'"' => 'quot',
			);

			if ( isset( $SpecialCharacter[ $Excerpt['text'][0] ] ) ) {
				return array(
					'markup' => '&' . $SpecialCharacter[ $Excerpt['text'][0] ] . ';',
					'extent' => 1,
				);
			}
		}

		protected function inlineStrikethrough( $Excerpt ) {
			if ( ! isset( $Excerpt['text'][1] ) ) {
				return;
			}

			if ( $Excerpt['text'][1] === '~' and preg_match( '/^~~(?=\S)(.+?)(?<=\S)~~/', $Excerpt['text'], $matches ) ) {
				return array(
					'extent'  => strlen( $matches[0] ),
					'element' => array(
						'name'    => 'del',
						'text'    => $matches[1],
						'handler' => 'line',
					),
				);
			}
		}

		protected function inlineUrl( $Excerpt ) {
			if ( $this->urlsLinked !== true or ! isset( $Excerpt['text'][2] ) or $Excerpt['text'][2] !== '/' ) {
				return;
			}

			if ( preg_match( '/\bhttps?:[\/]{2}[^\s<]+\b\/*/ui', $Excerpt['context'], $matches, PREG_OFFSET_CAPTURE ) ) {
				$Inline = array(
					'extent'   => strlen( $matches[0][0] ),
					'position' => $matches[0][1],
					'element'  => array(
						'name'       => 'a',
						'text'       => $matches[0][0],
						'attributes' => array(
							'href' => $matches[0][0],
						),
					),
				);

				return $Inline;
			}
		}

		protected function inlineUrlTag( $Excerpt ) {
			if ( strpos( $Excerpt['text'], '>' ) !== false and preg_match( '/^<(\w+:\/{2}[^ >]+)>/i', $Excerpt['text'], $matches ) ) {
				$url = str_replace( array( '&', '<' ), array( '&amp;', '&lt;' ), $matches[1] );

				return array(
					'extent'  => strlen( $matches[0] ),
					'element' => array(
						'name'       => 'a',
						'text'       => $url,
						'attributes' => array(
							'href' => $url,
						),
					),
				);
			}
		}

		# ~

		protected function unmarkedText( $text ) {
			if ( $this->breaksEnabled ) {
				$text = preg_replace( '/[ ]*\n/', "<br />\n", $text );
			} else {
				$text = preg_replace( '/(?:[ ][ ]+|[ ]*\\\\)\n/', "<br />\n", $text );
				$text = str_replace( " \n", "\n", $text );
			}

			return $text;
		}

		#
		# Handlers
		#

		protected function element( array $Element ) {
			$markup = '<' . $Element['name'];

			if ( isset( $Element['attributes'] ) ) {
				foreach ( $Element['attributes'] as $name => $value ) {
					if ( $value === null ) {
						continue;
					}

					$markup .= ' ' . $name . '="' . $value . '"';
				}
			}

			if ( isset( $Element['text'] ) ) {
				$markup .= '>';

				if ( isset( $Element['handler'] ) ) {
					$markup .= $this->{$Element['handler']}( $Element['text'] );
				} else {
					$markup .= $Element['text'];
				}

				$markup .= '</' . $Element['name'] . '>';
			} else {
				$markup .= ' />';
			}

			return $markup;
		}

		protected function elements( array $Elements ) {
			$markup = '';

			foreach ( $Elements as $Element ) {
				$markup .= "\n" . $this->element( $Element );
			}

			$markup .= "\n";

			return $markup;
		}

		# ~

		protected function li( $lines ) {
			$markup = $this->lines( $lines );

			$trimmedMarkup = trim( $markup );

			if ( ! in_array( '', $lines ) and substr( $trimmedMarkup, 0, 3 ) === '<p>' ) {
				$markup = $trimmedMarkup;
				$markup = substr( $markup, 3 );

				$position = strpos( $markup, '</p>' );

				$markup = substr_replace( $markup, '', $position, 4 );
			}

			return $markup;
		}

		#
		# Deprecated Methods
		#

		function parse( $text ) {
			$markup = $this->text( $text );

			return $markup;
		}

		#
		# Static Methods
		#

		final public static function instance( $name = 'default' ) {
			if ( isset( self::$instances[ $name ] ) ) {
				return self::$instances[ $name ];
			}

			$instance = new self();

			self::$instances[ $name ] = $instance;

			return $instance;
		}

		private static $instances = array();

		#
		# Fields
		#

		protected $DefinitionData;

		#
		# Read-Only

		protected $specialCharacters = array(
			'\\',
			'`',
			'*',
			'_',
			'{',
			'}',
			'[',
			']',
			'(',
			')',
			'>',
			'#',
			'+',
			'-',
			'.',
			'!',
			'|',
		);

		protected $StrongRegex = array(
			'*' => '/^[*]{2}((?:\\\\\*|[^*]|[*][^*]*[*])+?)[*]{2}(?![*])/s',
			'_' => '/^__((?:\\\\_|[^_]|_[^_]*_)+?)__(?!_)/us',
		);

		protected $EmRegex = array(
			'*' => '/^[*]((?:\\\\\*|[^*]|[*][*][^*]+?[*][*])+?)[*](?![*])/s',
			'_' => '/^_((?:\\\\_|[^_]|__[^_]*__)+?)_(?!_)\b/us',
		);

		protected $regexHtmlAttribute = '[a-zA-Z_:][\w:.-]*(?:\s*=\s*(?:[^"\'=<>`\s]+|"[^"]*"|\'[^\']*\'))?';

		protected $voidElements = array(
			'area',
			'base',
			'br',
			'col',
			'command',
			'embed',
			'hr',
			'img',
			'input',
			'link',
			'meta',
			'param',
			'source',
		);

		protected $textLevelElements = array(
			'a',
			'br',
			'bdo',
			'abbr',
			'blink',
			'nextid',
			'acronym',
			'basefont',
			'b',
			'em',
			'big',
			'cite',
			'small',
			'spacer',
			'listing',
			'i',
			'rp',
			'del',
			'code',
			'strike',
			'marquee',
			'q',
			'rt',
			'ins',
			'font',
			'strong',
			's',
			'tt',
			'sub',
			'mark',
			'u',
			'xm',
			'sup',
			'nobr',
			'var',
			'ruby',
			'wbr',
			'span',
			'time',
		);
	}
}
