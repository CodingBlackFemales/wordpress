<?php

namespace BBElementor\Templates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// If class `BB_Elementor_Templates` not created.
if ( ! class_exists( 'BB_Elementor_Templates' ) ) {
	
	/**
	 * Sets up and initializes the plugin.
	 */
	class BB_Elementor_Templates {
		
		/**
		 * Instance of the class
		 *
		 * @access private
		 * @since  1.4.7
		 *
		 */
		private static $instance = null;
		
		/**
		 * Holds API data
		 *
		 * @access public
		 * @since  1.4.7
		 *
		 */
		public $api;
		
		/**
		 * Holds templates configuration data
		 *
		 * @access public
		 * @since  1.4.7
		 */
		public $config;
		
		/**
		 * Holds templates assets
		 *
		 * @access public
		 * @since  1.4.7
		 */
		public $assets;
		
		/**
		 * Templates Manager
		 *
		 * @access public
		 * @since  1.4.7
		 */
		public $temp_manager;
		
		/**
		 * Holds templates types
		 *
		 * @access public
		 * @since  1.4.7
		 */
		public $types;
		
		/**
		 * Construct
		 *
		 * Class Constructor
		 *
		 * @since  1.4.7
		 * @access public
		 */
		public function __construct() {
			
			add_action( 'init', array( $this, 'init' ) );
			
		}
		
		/**
		 * Init BB Elementor Sections Templates
		 *
		 * @since  1.4.7
		 * @access public
		 *
		 * @return void
		 */
		public function init() {
			
			$this->load_files();
			
			$this->set_config();
			
			$this->set_assets();
			
			$this->set_api();
			
			$this->set_types();
			
			$this->set_templates_manager();
			
		}
		
		/**
		 * Load required files for BB Elementor Sections templates
		 *
		 * @since  1.4.7
		 * @access private
		 *
		 * @return void
		 */
		private function load_files() {
			require ELEMENTOR_BB__DIR__ . '/templates/classes/config.php';
			
			require ELEMENTOR_BB__DIR__ . '/templates/classes/assets.php';
			
			require ELEMENTOR_BB__DIR__ . '/templates/classes/manager.php';
			
			require ELEMENTOR_BB__DIR__ . '/templates/types/manager.php';
			
			require ELEMENTOR_BB__DIR__ . '/templates/classes/api.php';
			
		}
		
		/**
		 * Init `BB_Elementor_Templates_Core_Config`
		 *
		 * @since  1.4.7
		 * @access private
		 *
		 * @return void
		 */
		private function set_config() {
			
			$this->config = new Classes\BB_Elementor_Templates_Core_Config();
			
		}
		
		/**
		 * Init `BB_Elementor_Templates_Assets`
		 *
		 * @since  1.4.7
		 * @access private
		 *
		 * @return void
		 */
		private function set_assets() {
			
			$this->assets = new Classes\BB_Elementor_Templates_Assets();
			
		}
		
		/**
		 * Init `BB_Elementor_Templates_API`
		 *
		 * @since  1.4.7
		 * @access private
		 *
		 * @return void
		 */
		private function set_api() {
			
			$this->api = new Classes\BB_Elementor_Templates_API();
			
		}
		
		/**
		 * Init `BB_Elementor_Templates_Types`
		 *
		 * @since  1.4.7
		 * @access private
		 *
		 * @return void
		 */
		private function set_types() {
			
			$this->types = new Types\BB_Elementor_Templates_Types();
			
		}
		
		/**
		 * Init `BB_Elementor_Templates_Manager`
		 *
		 * @since  1.4.7
		 * @access private
		 *
		 * @return void
		 */
		private function set_templates_manager() {
			
			$this->temp_manager = new Classes\BB_Elementor_Templates_Manager();
			
		}
		
		/**
		 * Get instance.
		 *
		 * Creates and returns an instance of the class.
		 *
		 * @since  1.4.7
		 * @access public
		 *
		 * @return object
		 */
		public static function get_instance() {
			if ( self::$instance == null ) {
				self::$instance = new self;
			}
			
			return self::$instance;
		}
		
	}
	
}

if ( ! function_exists( 'bb_elementor_templates' ) ) {
	
	/**
	 * Triggers `get_instance` method
	 * @since  1.4.7
	 * @access public
	 * @return object
	 */
	function bb_elementor_templates() {
		
		return BB_Elementor_Templates::get_instance();
		
	}
	
}
bb_elementor_templates();