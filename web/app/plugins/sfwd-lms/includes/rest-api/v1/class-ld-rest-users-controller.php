<?php
/**
 * LearnDash REST API V1 Users Controller.
 *
 * @since 2.5.8
 * @package LearnDash\REST\V1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Users_Controller_V1' ) ) && ( class_exists( 'WP_REST_Users_Controller' ) ) ) {

	/**
	 * Class LearnDash REST API V1 Users Controller.
	 *
	 * @since 2.5.8
	 */
	class LD_REST_Users_Controller_V1 extends WP_REST_Users_Controller /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {

		/**
		 * Version
		 *
		 * @var string
		 */
		protected $version = 'v1';

		/**
		 * Sub controllers
		 *
		 * @var array
		 */
		protected $sub_controllers = array();

		/**
		 * Taxonomies
		 *
		 * @var array
		 */
		protected $taxonomies = array();

		/**
		 * Public constructor for class
		 *
		 * @since 2.5.8
		 */
		public function __construct() {
			parent::__construct();

			$this->namespace = LEARNDASH_REST_API_NAMESPACE . '/' . $this->version;
			$this->rest_base = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'users' );
		}

		/**
		 * Registers the routes for the objects of the controller.
		 *
		 * @since 2.5.8
		 *
		 * @see register_rest_route() in WordPress core.
		 */
		public function register_routes() {

			$collection_params = $this->get_collection_params();
			$schema            = $this->get_item_schema();

			$get_item_args = array(
				'context' => $this->get_context_param( array( 'default' => 'view' ) ),
			);
		}
	}
}
