<?php

declare( strict_types=1 );

namespace LearnDash\Hub\Component;

use LearnDash\Hub\Framework\Base;

/**
 *
 */
class Projects extends Base {
	/**
	 * Return all the current installed projects with metadata.
	 *
	 * @param array $api_data The get_projects API data.
	 *
	 * @return array
	 */
	public function get_installed_projects( array $api_data ): array {
		foreach ( $api_data as $slug => &$item ) {
			$plugin_slug = $this->get_plugin_slug( $slug, $item['name'] );

			// the learndash-hub always activated.
			if ( ! $plugin_slug && $slug !== 'learndash-hub' ) {
				unset( $api_data[ $slug ] );
				continue;
			}

			// check it is update available.
			$plugin_data = null;
			if ( $slug === 'learndash-hub' ) {
				$mu_path = WPMU_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'learndash-hub-mu.php';
				if ( file_exists( $mu_path ) ) {
					$plugin_data = get_plugin_data( $mu_path );
				}
			}

			$plugin_path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin_slug;
			if ( $plugin_data === null && is_file( $plugin_path ) ) {
				$plugin_data = get_plugin_data( $plugin_path );
			}

			$item['has_update'] = $this->has_newer_version( $plugin_data['Version'], $item['latest_version'], '<' );

			$item['is_active']   = is_plugin_active( $plugin_slug );
			$item['version']     = $plugin_data['Version'];
			$item['folder_slug'] = $plugin_slug;
		}

		return $api_data;
	}

	/**
	 * Return the last check timestamp.
	 *
	 * @return int
	 */
	public function get_project_check_time() {
		$cached = get_option( 'learndash-hub-projects-api' );
		if ( ! isset( $cached['last_check'] ) ) {
			return time();
		}

		return $cached['last_check'];
	}

	/**
	 * Install, activate, deactivate or delete a plugin.
	 *
	 * @param string $action
	 * @param string $slug
	 *
	 * @return bool|int|true|\WP_Error|null
	 */
	public function handle_plugin( string $action, string $slug ) {
		switch ( $action ) {
			case 'install':
				return $this->install_plugin( $slug );
			case 'activate':
				return $this->activate_plugin( $slug );
			case 'deactivate':
				return $this->deactivate_plugin( $slug );
			case 'delete':
				return $this->delete_plugin( $slug );
			case 'update':
				return $this->update_plugin( $slug );
			default:
				return false;
		}
	}

	/**
	 * Delete our plugin.
	 *
	 * @param string $slug The plugin slug.
	 *
	 * @return bool|\WP_Error
	 */
	public function delete_plugin( string $slug ) {
		if ( $slug === 'learndash-hub' && file_exists( WPMU_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'learndash-hub-mu-plugin.php' ) ) {
			WP_Filesystem();
			global $wp_filesystem;

			$wp_filesystem->delete( WPMU_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'learndash-hub', true );
			$wp_filesystem->delete( WPMU_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'learndash-hub-mu-plugin.php' );

			return true;
		}

		$plugin_slug = $this->get_plugin_slug( $slug );
		if ( ! $plugin_slug ) {
			return new \WP_Error( 'err', __( 'Invalid plugin file.', 'learndash-hub' ) );
		}
		$ret = delete_plugins( array( $plugin_slug ) );
		if ( $ret === null ) {
			return new \WP_Error( 'err', __( 'Filesystem credentials are required to proceed', 'learndash-hub' ) );
		}
		// clear cache first.
		wp_cache_delete( 'plugins', 'plugins' );
		delete_site_transient( 'update_plugins' );

		return $ret;
	}

	/**
	 * Activate our plugin.
	 *
	 * @param string $slug The plugin slug.
	 *
	 * @return true|\WP_Error
	 */
	public function activate_plugin( string $slug ) {
		$plugin_slug = $this->get_plugin_slug( $slug );
		if ( ! $plugin_slug ) {
			return new \WP_Error( 'err', __( 'Invalid plugin file.', 'learndash-hub' ) );
		}

		$ret = activate_plugin( $plugin_slug );
		if ( is_wp_error( $ret ) ) {
			return $ret;
		}

		return true;
	}

	/**
	 * Deactivate a plugin.
	 *
	 * @param string $slug
	 *
	 * @return bool|\WP_Error
	 */
	public function deactivate_plugin( string $slug ) {
		$plugin_slug = $this->get_plugin_slug( $slug );
		if ( ! $plugin_slug ) {
			return new \WP_Error( 'err', __( 'Invalid plugin file.', 'learndash-hub' ) );
		}
		deactivate_plugins( $plugin_slug );

		return ! is_plugin_active( $plugin_slug );
	}

	/**
	 * Update our plugin.
	 *
	 * @param string $slug The plugin slug.
	 *
	 * @return true|\WP_Error
	 */
	public function update_plugin( string $slug ) {
		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		delete_site_option( 'learndash_hub_update_plugins_cache' );
		$api = plugins_api(
			'plugin_information',
			array(
				'slug' => $slug,
			)
		);
		if ( is_wp_error( $api ) ) {
			return $api;
		}

		$status = install_plugin_install_status( $api );
		if ( $status['status'] === 'update_available' ) {
			return $this->install( $slug, true );
		}

		return new \WP_Error( 'err', __( 'Plugin update failed.', 'learndash-hub' ) );
	}

	/**
	 * Install our plugin.
	 *
	 * @param string $slug The plugin slug.
	 *
	 * @return true|\WP_Error
	 */
	public function install_plugin( string $slug ) {
		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		$api = plugins_api(
			'plugin_information',
			array(
				'slug' => $slug,
			)
		);

		if ( is_wp_error( $api ) ) {
			return $api;
		}

		$status = install_plugin_install_status( $api );
		if ( $status['status'] === 'install' ) {
			// install it.
			return $this->install( $slug );
		}

		return new \WP_Error( 'err1', __( 'Plugin install failed', 'learndash-hub' ) );
	}

	/**
	 * Fetch the plugins category so we can use it for filter.
	 *
	 * @param array $api_data The get_projects API data.
	 *
	 * @return array
	 */
	public function get_projects_category( array $api_data ): array {
		$categories = array();
		foreach ( $api_data as $slug => $item ) {
			$tags       = explode( ',', $item['tags'] );
			$categories = array_merge( $categories, $tags );
		}

		$categories = array_unique( $categories );
		$categories = array_filter( $categories );

		return array_map( 'trim', $categories );
	}

	/**
	 * @param array $api_data The get_projects API data.
	 *
	 * @return array
	 */
	public function get_premium_projects( array $api_data ): array {
		$installed_projects = $this->get_installed_projects( $api_data );
		foreach ( $api_data as $slug => $item ) {
			if ( $item['product_type'] !== 'premium' ) {
				unset( $api_data[ $slug ] );
				continue;
			}

			// now check if the plugins is installed.
			$api_data[ $slug ]['is_installed'] = isset( $installed_projects[ $slug ] );
		}

		return $api_data;
	}

	/**
	 * @return array
	 */
	public function get_aff_projects() {
		$projects = $this->do_api_request( '/repo/aff_plugins' );
		if ( ! is_array( $projects ) ) {
			return array();
		}

		return $projects;
	}

	/**
	 * Return the projects that has not installed.
	 *
	 * @param array $api_data The get_projects API data.
	 *
	 * @return array
	 */
	public function get_projects( array $api_data ): array {
		$installed_projects = $this->get_installed_projects( $api_data );

		foreach ( $api_data as $slug => $item ) {
			if ( $item['product_type'] !== 'standard' ) {
				unset( $api_data[ $slug ] );
				continue;
			}

			if ( isset( $installed_projects[ $slug ] ) ) {
				unset( $api_data[ $slug ] );
			}
		}

		return $api_data;
	}

	/**
	 * Look up a project object, this is use when pulling the plugin info.
	 *
	 * @param string $slug The project slug, eg: sfwd-lms.
	 * @param array $api_data The plugins data, pulling from the api.
	 *
	 * @return false|array Return the project data as an array, or false if nothing found.
	 */
	public function look_project( string $slug, array $api_data ) {
		$installed = $this->get_installed_projects( $api_data );
		if ( isset( $installed[ $slug ] ) ) {
			return $installed[ $slug ];
		}

		$projects = $this->get_projects( $api_data );

		$project = $projects[ $slug ] ?? false;
		if ( $project === false ) {
			$premiums = $this->get_premium_projects( $api_data );
			$project  = $premiums[ $slug ] ?? false;
		}

		return $project;
	}

	/**
	 * Install a project
	 *
	 * @param string $slug The plugin folder name.
	 * @param bool $is_update Update instead of install.
	 *
	 * @return bool|\WP_Error
	 */
	public function install( string $slug, bool $is_update = false ) {
		// prepare for install.
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		include_once ABSPATH . 'wp-admin/includes/theme-install.php';
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		include_once ABSPATH . 'wp-admin/includes/file.php';

		$skin = new \WP_Ajax_Upgrader_Skin();

		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => sanitize_key( $slug ),
				'fields' => array( 'sections' => false ),
			)
		);

		if ( is_wp_error( $api ) ) {
			return false;
		}

		$upgrader = new \Plugin_Upgrader( $skin );
		if ( $is_update ) {
			$plugin_slug = $this->get_plugin_slug( $slug );
			$result      = $upgrader->upgrade( $plugin_slug );
		} else {
			$result = $upgrader->install( $api->download_url );
		}

		if ( $result === true ) {
			$this->install_translation( $api );
			// delete the update cache.
			delete_site_option( 'learndash_hub_fetch_projects' );
			delete_site_option( 'learndash_hub_update_plugins_cache' );
			if ( $is_update ) {
				$this->activate_plugin( $api->slug );
			}

			return true;
		}

		return new \WP_Error( 'err', implode( PHP_EOL, $upgrader->skin->get_upgrade_messages() ) );
	}

	/**
	 * Pull the translation from glotpress
	 *
	 * @param mixed $api
	 */
	private function install_translation( $api ) {
		// get plugin text domain.
		$slug = $this->get_plugin_slug( $api->slug );

		$wp_installed_languages = get_available_languages();
		if ( ! in_array( 'en_US', $wp_installed_languages, true ) ) {
			$wp_installed_languages = array_merge( array( 'en_US' ), $wp_installed_languages );
		}
		if ( ! empty( $slug ) ) {
			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $slug );
			if ( is_array( $plugin_data ) && isset( $plugin_data['TextDomain'] ) && ! empty( $plugin_data['TextDomain'] ) ) {
				// loop through the languages and download the locale files.
				$text_domain = $plugin_data['TextDomain'];
				$url         = add_query_arg(
					array(
						'ldlms-glotpress' => 1,
						'action'          => 'translation_sets',
						'project'         => $api->slug,
					),
					trailingslashit( 'https://translations.learndash.com/' )
				);
				$response    = wp_remote_get( $url );
				if ( ( is_array( $response ) ) && ( wp_remote_retrieve_response_code( $response ) === 200 ) ) {
					$response_body = wp_remote_retrieve_body( $response );

					if ( ! empty( $response_body ) ) {
						$ld_translation_sets = json_decode( $response_body, true );

						if ( ! $ld_translation_sets || ! isset( $ld_translation_sets[ $api->slug ] ) ) {
							return;
						}

						foreach ( $ld_translation_sets[ $api->slug ] as $key => $translate ) {
							foreach ( $wp_installed_languages as $locale ) {
								if ( $translate['wp_locale'] === $locale ) {
									foreach ( $translate['links'] as $ext => $link ) {
										$tmp_name = download_url( $link );
										if ( ! is_wp_error( $tmp_name ) ) {
											$language_dir = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $api->slug . DIRECTORY_SEPARATOR . 'languages';
											if ( ! is_dir( $language_dir ) ) {
												wp_mkdir_p( $language_dir );
											}
											$path = $language_dir . DIRECTORY_SEPARATOR . $api->slug . '-' . $locale . '.' . $ext;
											rename( $tmp_name, $path );
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Get the plugin slug from folder name.
	 *
	 * @param string $slug The plugin folder.
	 * @param string $plugin_name The plugin name.
	 *
	 * @return string|bool
	 */
	public function get_plugin_slug( string $slug, string $plugin_name = '' ) {
		$plugins = get_plugins();

		// lookup the installed plugins.
		foreach ( $plugins as $folder_slug => $plugin ) {
			$base = dirname( $folder_slug );
			if ( $slug === $base ) {
				return $folder_slug;
			}
		}

		// normally it should not here, however if we are development then it will be chances
		// we use different slug.
		if ( ! defined( 'LEARNDASH_HUB_DEVELOPMENT' ) || empty( $plugin_name ) ) {
			return false;
		}

		// if nothing return, means the plugin is not installed, or use different path.
		foreach ( $plugins as $folder_slug => $plugin ) {
			if ( strtolower( $plugin['Author'] ) !== 'learndash' ) {
				continue;
			}

			if ( strtolower( $plugin_name ) === strtolower( $plugin['Name'] ) ) {
				return $folder_slug;
			}

			if ( $slug === 'learndash-hub' && $plugin['Name'] === 'LearnDash Licensing & Management' ) {
				return $folder_slug;
			}
		}

		return false;
	}

	/**
	 * @param string $plugin_version
	 * @param string $repo_version
	 *
	 * @return bool
	 */
	public function has_newer_version( string $plugin_version, string $repo_version ): bool {
		// since plugin version always sematic.
		$parts = explode( '.', $plugin_version );
		if ( count( $parts ) === 2 ) {
			// this means the plugins not PHP standard.
			$parts[]        = '0';
			$plugin_version = implode( '.', $parts );
		}

		return version_compare( $plugin_version, $repo_version, '<' );
	}
}
