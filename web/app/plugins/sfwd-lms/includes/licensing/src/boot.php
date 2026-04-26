<?php
/**
 * Boot the plugin.
 *
 * @since 4.18.0
 *
 * @package LearnDash
 */

namespace LearnDash\Hub;

use LearnDash\Hub\Controller\CheckPluginsRequirements;
use LearnDash\Hub\Controller\Main_Controller;
use LearnDash\Hub\Controller\Projects_Controller;
use LearnDash\Hub\Controller\RemoteBanners;
use LearnDash\Hub\Controller\Settings_Controller;
use LearnDash\Hub\Controller\Signin_Controller;
use LearnDash\Hub\Traits\License;
use LearnDash\Hub\Traits\Permission;

defined( 'ABSPATH' ) || exit;

/**
 * Everything start from here
 *
 * Class Boot
 *
 * @package Hub
 */
class Boot {
	use Permission;
	use License;

	/**
	 * The projects controller instance.
	 *
	 * @var Projects_Controller
	 */
	private $projects_controller;

	/**
	 * Constructor.
	 *
	 * @since 4.21.4
	 *
	 * @return void
	 */
	public function __construct() {
		$this->projects_controller = new Projects_Controller();
	}

	/**
	 * Run all the triggers in init runtime.
	 */
	public function start() {
		if ( $this->is_signed_on() ) {
			if ( ! $this->is_user_allowed() ) {
				return;
			}

			// later we will check the permissions for each modules.
			( new Main_Controller() );

			$this->projects_controller->register_hooks();

			( new Settings_Controller() );
			( new CheckPluginsRequirements() )->register_hooks();
		} else {
			( new Signin_Controller() );
		}

		( new RemoteBanners() )->register_hooks();
	}

	/**
	 * Registers early hooks.
	 *
	 * It must be registered outside of the 'init' or 'plugins_loaded' hooks.
	 *
	 * @since 4.21.4
	 *
	 * @return void
	 */
	public function register_early_hooks() {
		$this->projects_controller->register_early_hooks();
	}

	/**
	 * Run the setup scripts when the plugin activating.
	 */
	public function install() {
	}

	/**
	 * Clear all the cache
	 */
	public function deactivate() {
		delete_site_option( 'learndash-hub-projects-api' );
		delete_site_option( 'learndash_hub_update_plugins_cache' );
		delete_site_option( $this->get_license_status_option_name() );
	}
}
