<?php

namespace LearnDash\Hub\Controller;

use Hub\Traits\Time;
use LearnDash\Hub\Component\API;
use LearnDash\Hub\Component\Projects;
use LearnDash\Hub\Framework\Controller;
use LearnDash\Hub\Traits\License;
use LearnDash\Hub\Traits\Permission;

/**
 * Handle the plugin lists, install, activate and more.
 */
class Projects_Controller extends Controller {
	use Permission, Time, License;

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
		add_filter( 'http_request_args', array( $this, 'maybe_append_auth_headers' ), 10, 2 );
		add_action( 'upgrader_process_complete', array( $this, 'process_plugin_update' ), 10, 2 );
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
	 * @param array $parsed_args An array of HTTP request arguments.
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

		$plugin_data = $this->get_service()->look_project( $slug, $this->get_api()->get_projects() );
		if ( empty( $plugin_data ) ) {
			return;
		}
		//phpcs:ignore
		$do = $_POST['intention'] ?? null;

		$do  = sanitize_text_field( wp_unslash( $do ) );
		$ret = $this->get_service()->handle_plugin( $do, $slug );
		if ( is_wp_error( $ret ) ) {
			wp_send_json_error( $ret->get_error_message() );
		}

		if ( $ret === true ) {
			wp_send_json_success( $this->make_data() );
		}
	}

	/**
	 * Alter the transient to push update.
	 *
	 * @param mixed $transient
	 *
	 * @return mixed
	 */
	public function push_update( $transient ) {
		if ( ! is_object( $transient ) ) {
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

				if ( $plugin_file !== false ) {
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
						$item->url                    = ! empty( $project['plugin_uri'] ) ? $project['plugin_uri'] : 'https://learndash.com';
						$item->package                = $project['download_url'];
						$item->requires               = $project['requires'];
						$item->requires_php           = $project['requires_php'];
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
			! isset( $options['action'] ) || $options['action'] !== 'update' ||
			! isset( $options['type'] ) || $options['type'] !== 'plugin' ||
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
		if ( $action !== 'plugin_information' ) {
			return $res;
		}

		$slug = $args->slug;
		if (
			stristr( $slug, 'learndash' ) === false &&
			stristr( $slug, 'ld' ) === false &&
			$slug !== 'sfwd-lms'
		) {
			return $res;
		}
		$api_data = $this->get_api()->get_projects();

		if ( ! is_array( $api_data ) ) {
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
		wp_localize_script(
			'learndash-hub-projects',
			'Hub',
			$this->make_data()
		);
		wp_enqueue_script( 'learndash-hub-projects' );
		wp_enqueue_style( 'learndash-hub' );
		add_thickbox();
		$this->render(
			'root'
		);
	}

	/**
	 * The array data that we will use on frontend.
	 *
	 * @return array
	 */
	protected function make_data(): array {
		$api_data = $this->get_api()->get_projects();
		foreach ( $api_data as $slug => &$plugin ) {
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
		}

		if ( is_wp_error( $api_data ) ) {
			return array(
				'error_code'    => $api_data->get_error_code(),
				'error_message' => $api_data->get_error_message(),
			);
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
}
