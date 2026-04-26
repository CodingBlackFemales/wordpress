<?php
/**
 * Licensing Projects Controller.
 *
 * @since 4.18.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Hub\Controller;

use Hub\Traits\Time;
use LearnDash\Core\Utilities\Cast;
use LearnDash\Hub\Component\API;
use LearnDash\Hub\Component\Projects;
use LearnDash\Hub\Framework\Controller;
use LearnDash\Hub\Traits\License;
use LearnDash\Hub\Traits\Permission;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the plugin lists, install, activate and more.
 *
 * @since 4.18.0
 *
 * @phpstan-type Translation array{
 *     language: string,
 *     version: string,
 *     updated: string,
 *     package: string,
 *     autoupdate: string
 * }
 *
 * @phpstan-type PluginUpdate array{
 *     id?: string,
 *     slug: string,
 *     version: string,
 *     url: string,
 *     package?: string,
 *     tested?: string,
 *     requires_php?: string,
 *     autoupdate?: bool,
 *     icons?: array<string>,
 *     banners?: array<string>,
 *     banners_rtl?: array<string>,
 *     translations?: array<Translation>
 * }
 */
class Projects_Controller extends Controller {
	use Permission;
	use Time;
	use License;

	/**
	 * The default plugin URI.
	 *
	 * @since 4.21.4
	 *
	 * @var string
	 */
	private const DEFAULT_PLUGIN_URI = 'https://learndash.com';

	/**
	 * The page slug.
	 *
	 * @var string
	 */
	public $slug = 'learndash-hub-projects';

	/**
	 * The service class.
	 *
	 * @var Projects
	 */
	protected $service;

	/**
	 * The API instance.
	 *
	 * @var API
	 */
	protected $api;

	/**
	 * Register all the hooks
	 */
	public function register_hooks() {
		add_action( 'wp_ajax_ld_hub_plugin_action', array( $this, 'plugin_action' ) );
		add_action( 'wp_ajax_ld_hub_refresh_repo', array( $this, 'refresh_repo_data' ) );
		add_action( 'wp_ajax_ld_hub_bulk_action', array( $this, 'bulk_action' ) );
		add_filter( 'plugins_api', array( $this, 'filter_plugins_api' ), 10, 3 );
		add_filter( 'site_transient_update_plugins', array( $this, 'push_update' ) );
		add_action( 'upgrader_process_complete', array( $this, 'process_plugin_update' ), 10, 2 );
	}

	/**
	 * Registers early hooks. Normally, outside of the 'init' or 'plugins_loaded' hooks.
	 *
	 * @since 4.21.4
	 *
	 * @return void
	 */
	public function register_early_hooks() {
		/**
		 * It allows us to inject the auth headers required for the plugin download.
		 *
		 * It must be registered outside of the 'init' or 'plugins_loaded' hooks to work with the WP auto-updates.
		 */
		add_filter( 'http_request_args', [ $this, 'maybe_append_auth_headers' ], 10, 2 );

		// Clear the cache when the automatic updates are complete.

		add_action(
			'automatic_updates_complete',
			function () {
				delete_site_option( API::OPTION_NAME_UPDATE_PLUGINS_CACHE );
			}
		);

		// WP auto-updates hooks.

		add_filter( 'update_plugins_learndash', [ $this, 'update_plugins_learndash' ], 10, 3 );
	}

	/**
	 * Handles the update response for learndash plugins (hostname: learndash)
	 *
	 * @since 4.21.4
	 *
	 * @param PluginUpdate|false   $update           The plugin update data with the latest details. Default false.
	 * @param array<string, mixed> $plugin_data      Plugin headers.
	 * @param string               $plugin_file      Plugin filename.
	 *
	 * @return PluginUpdate|false
	 */
	public function update_plugins_learndash( $update, $plugin_data, $plugin_file ) {
		$projects = $this->get_api()->get_projects();

		if (
			! is_array( $projects )
			|| empty( $projects )
		) {
			return $update;
		}

		$installed_projects = $this->get_service()->get_installed_projects( $projects );
		foreach ( $installed_projects as $project ) {
			$inner_plugin_file = $this->get_service()->get_plugin_slug( $project['slug'], $project['name'] );

			if ( $inner_plugin_file !== $plugin_file ) {
				continue;
			}

			return [
				'id'           => $inner_plugin_file,
				'slug'         => $project['slug'],
				'plugin'       => $inner_plugin_file,
				'version'      => Cast::to_string( $plugin_data['Version'] ),
				'new_version'  => $project['latest_version'],
				'url'          => ! empty( $project['plugin_uri'] ) ? $project['plugin_uri'] : self::DEFAULT_PLUGIN_URI,
				'package'      => $project['download_url'] ?? '',
				'requires'     => $project['requires'],
				'requires_php' => $project['requires_php'],
				'tested'       => $project['tested'],
			];
		}

		return $update;
	}

	/**
	 * Bulk action.
	 */
	public function bulk_action() {
		if ( ! $this->check_permission() ) {
			return;
		}
		if ( ! isset( $_POST['nonce'] ) ) {
			return;
		}

		//phpcs:ignore
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ld_hub_bulk_action' ) ) {
			return;
		}

		$plugins   = $_POST['plugins'] ?? array();
		$intention = $_POST['intention'] ?? '';

		foreach ( $plugins as $slug ) {
			$this->get_service()->handle_plugin( $intention, $slug );
		}

		wp_send_json_success( $this->make_data() );
	}

	/**
	 * Refresh the repo data.
	 */
	public function refresh_repo_data() {
		if ( ! $this->check_permission() ) {
			return;
		}
		if ( ! isset( $_POST['nonce'] ) ) {
			return;
		}

		//phpcs:ignore
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ld_hub_refresh_repo' ) ) {
			return;
		}

		delete_option( 'learndash-hub-projects-api' );

		wp_send_json_success( $this->make_data() );
	}

	/**
	 * When we download a project, the auth header should be added.
	 *
	 * @param array  $parsed_args An array of HTTP request arguments.
	 * @param string $url The request URL.
	 *
	 * @return array
	 */
	public function maybe_append_auth_headers( array $parsed_args, string $url ): array {
		$needle = $this->get_api()->base . '/repo/plugin/';
		if ( strpos( $url, $needle ) !== 0 ) {
			return $parsed_args;
		}
		if ( ! is_array( $parsed_args['headers'] ) ) {
			$parsed_args['headers'] = array();
		}

		$parsed_args['headers'] = array_merge( $parsed_args['headers'], $this->get_auth_headers() );

		return $parsed_args;
	}

	/**
	 * Ajax endpoint for handling plugin task.
	 */
	public function plugin_action() {
		if ( ! $this->check_permission() ) {
			return;
		}
		if ( ! isset( $_POST['nonce'] ) ) {
			return;
		}

		//phpcs:ignore
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ld_hub_plugin_handle' ) ) {
			return;
		}

		$slug = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : null;
		if ( empty( $slug ) ) {
			return;
		}

		$projects = $this->get_api()->get_projects();

		if ( is_wp_error( $projects ) ) {
			return;
		}

		$plugin_data = $this->get_service()->look_project( $slug, $projects );
		if ( empty( $plugin_data ) ) {
			return;
		}
		//phpcs:ignore
		$do = $_POST['intention'] ?? null;

		$do  = sanitize_text_field( wp_unslash( $do ) );
		$ret = $this->get_service()->handle_plugin( $do, $slug );
		if ( is_wp_error( $ret ) ) {
			$error_message = $this->sanitize_error_message( $ret->get_error_message() );

			wp_send_json_error( $error_message );
		}

		if ( true === $ret ) {
			wp_send_json_success( $this->make_data() );
		}
	}

	/**
	 * Alter the transient to push update.
	 *
	 * @param mixed $transient The `update_plugins` transient value.
	 *
	 * @return mixed
	 */
	public function push_update( $transient ) {
		if (
			! is_object( $transient )
			|| ! property_exists(
				$transient,
				'response'
			)
		) {
			return $transient;
		}

		$last_check = get_site_option( 'learndash_hub_update_plugins_cache' );
		if ( is_array( $last_check ) && strtotime( '+1 hour', $last_check['last_check'] ) > time() ) {
			$updates_info = $last_check['updates'];
		} else {
			$projects = $this->get_api()->get_projects();
			if ( ! is_array( $projects ) || empty( $projects ) ) {
				return $transient;
			}
			$installed_projects = $this->get_service()->get_installed_projects( $projects );
			$updates_info       = array();
			foreach ( $installed_projects as $project ) {
				if ( ! $project['has_update'] ) {
					continue;
				}
				$plugin_file = $this->get_service()->get_plugin_slug( $project['slug'], $project['name'] );

				if ( false !== $plugin_file ) {
					$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file );
					if ( version_compare( $plugin_data['Version'], $project['latest_version'], '<' ) ) {
						if ( isset( $transient->response[ $plugin_file ] ) ) {
							$item = $transient->response[ $plugin_file ];
						} else {
							$item         = new \stdClass();
							$item->id     = $plugin_file;
							$item->slug   = $project['slug'];
							$item->plugin = $plugin_file;
						}
						$item->new_version            = $project['latest_version'];
						$item->url                    = ! empty( $project['plugin_uri'] ) ? $project['plugin_uri'] : self::DEFAULT_PLUGIN_URI;
						$item->package                = $project['download_url'] ?? '';
						$item->requires               = $project['requires'];
						$item->requires_php           = $project['requires_php'];
						$item->tested                 = $project['tested'];
						$updates_info[ $plugin_file ] = $item;
					}
				}
			}
			update_site_option(
				'learndash_hub_update_plugins_cache',
				array(
					'last_check' => time(),
					'updates'    => $updates_info,
				)
			);
		}
		if ( count( $updates_info ) ) {
			if ( ! is_array( $transient->response ) ) {
				$transient->response = array();
			}
			$transient->response = array_merge( $transient->response, $updates_info );
		}

		return $transient;
	}

	public function process_plugin_update( $upgrader_object, $options ) {
		if (
			! isset( $options['action'] ) || 'update' !== $options['action'] ||
			! isset( $options['type'] ) || 'plugin' !== $options['type'] ||
			! isset( $options['plugins'] ) || ! is_array( $options['plugins'] )
		) {
			return;
		}

		foreach ( $options['plugins'] as $plugin ) {
			if (
				strpos( $plugin, 'learndash' ) !== false ||
				strpos( $plugin, 'ld' ) !== false ||
				strpos( $plugin, 'sfwd-lms' ) !== false
			) {
				delete_site_option( 'learndash_hub_fetch_projects' );
				delete_site_option( 'learndash_hub_update_plugins_cache' );
				break;
			}
		}
	}

	/**
	 * Add our plugin information so it can be retrieved via the function plugins_api
	 *
	 * @param object $res Default update-info provided by WordPress.
	 * @param string $action What action was requested (theme or plugin?).
	 * @param object $args Details used to build default update-info.
	 *
	 * @return object
	 */
	public function filter_plugins_api( $res, string $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $res;
		}

		$slug     = $args->slug;
		$api_data = $this->get_api()->get_projects();

		if ( ! is_array( $api_data ) ) {
			return $res;
		}

		$learndash_plugin_slugs = get_option( API::OPTION_NAME_PLUGIN_SLUGS, [] );

		if ( ! in_array( $slug, $learndash_plugin_slugs, true ) ) {
			return $res;
		}

		$project = $this->get_service()->look_project( $args->slug, $api_data );

		if ( empty( $project ) ) {
			return $res;
		}

		$project['version'] = $project['latest_version'];

		return (object) $project;
	}

	/**
	 * Render the view.
	 */
	public function display() {
		add_thickbox();
		$this->render(
			'root'
		);
	}

	/**
	 * The array data that we will use on frontend.
	 *
	 * @since 4.18.0
	 * @since 4.18.0 Changed method visibility to public.
	 *
	 * @return array
	 */
	public function make_data(): array {
		$api_data = $this->get_api()->get_projects();

		if ( is_wp_error( $api_data ) ) {
			return array(
				'error_code'    => $api_data->get_error_code(),
				'error_message' => $api_data->get_error_message(),
				'nonces'        => [
					'refresh_repo' => wp_create_nonce( 'ld_hub_refresh_repo' ),
				],
			);
		}

		foreach ( $api_data as $slug => &$plugin ) {
			if ( ! is_array( $plugin ) ) {
				continue;
			}

			$details_url           = self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $slug . '&section=changelog' );
			$details_url           = add_query_arg(
				array(
					'TB_iframe' => 'true',
					'width'     => 772,
					'height'    => 800,
				),
				$details_url
			);
			$plugin['details_url'] = $details_url;

			// inject the ld_compatibility here.
			$plugin['ld_compatibility'] = true === is_learndash_version_compatible( $plugin, $this->get_learndash_core_version() );
		}

		return array(
			'last_check'        => $this->format_date_time( $this->get_service()->get_project_check_time() ),
			'projects'          => $this->get_service()->get_projects( $api_data ),
			'installedProjects' => $this->get_service()->get_installed_projects( $api_data ),
			'categories'        => $this->get_service()->get_projects_category( $api_data ),
			'premiumProjects'   => $this->get_service()->get_premium_projects( $api_data ),
			'affProjects'       => $this->get_service()->get_aff_projects(),
			'nonces'            => array(
				'handle_plugin' => wp_create_nonce( 'ld_hub_plugin_handle' ),
				'refresh_repo'  => wp_create_nonce( 'ld_hub_refresh_repo' ),
				'bulk_action'   => wp_create_nonce( 'ld_hub_bulk_action' ),
			),
			'adminUrl'          => admin_url( 'admin.php?page=learndash-hub' ),
			'externalUrl'       => admin_url( 'plugin-install.php?s=learndash&tab=search&type=tag' ),
		);
	}

	/**
	 * Get service class
	 *
	 * @return Projects
	 */
	private function get_service() {
		if ( ! $this->service instanceof Projects ) {
			$this->service = new Projects();
		}

		return $this->service;
	}

	/**
	 * Sanitizes a given error message so that it can be displayed via a JS Alert by accounting for common HTML tags.
	 *
	 * @since 4.21.5
	 *
	 * @param string $error_message The error message.
	 *
	 * @return string
	 */
	private function sanitize_error_message( string $error_message ): string {
		preg_match_all( '/<a[^>]*?href="([^"]*?)"[^>]*?>([\s\S]*?)<\/a>\.?/i', $error_message, $matches );

		if ( empty( $matches[0] ) ) {
			return $error_message;
		}

		foreach ( $matches[0] as $index => $match ) {
			$error_message = str_replace( $match, "{$matches[2][$index]}: {$matches[1][$index]}", $error_message );
		}

		return $error_message;
	}
}
