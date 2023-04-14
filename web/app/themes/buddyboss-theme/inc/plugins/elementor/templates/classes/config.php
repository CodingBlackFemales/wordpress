<?php

namespace BBElementor\Templates\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // No access of directly access.

if ( ! class_exists( 'BB_Elementor_Templates_Core_Config' ) ) {
	
	/**
	 *  BB_Elementor_Templates Templates Core config.
	 *
	 * Templates core class is responsible for handling templates library.
	 *
	 * @since 1.4.7
	 */
	class BB_Elementor_Templates_Core_Config {
		
		/**
		 * Instance of the class
		 *
		 * @access private
		 * @since  1.4.7
		 */
		private static $instance = null;
		
		/**
		 * Holds config data.
		 *
		 * @access private
		 * @since  1.4.7
		 */
		private $config;
		
		/**
		 * BB_Elementor_Templates_Core_Config constructor.
		 *
		 * Sets config data.
		 *
		 * @since  1.4.7
		 * @access public
		 */
		public function __construct() {
			
			$this->config = array(
				'bb_elementor_temps' => __( 'BuddyBoss Elementor Sections', 'buddyboss-theme' ),
				'api'              => array(
					'enabled'   => true,
					'base'      => 'https://elementor.buddyboss.com/',
					'path'      => 'wp-json/buddyboss-elementor/v1',
					'id'        => 9,
					'endpoints' => array(
						'templates'  => '/templates/',
						'categories' => '/categories/',
						'template'   => '/template/',
					),
				),
			);
			
		}
		
		/**
		 * Get
		 *
		 * Gets a segment of config data.
		 *
		 * @since  1.4.7
		 * @access public
		 *
		 * @param string $key Key.
		 *
		 * @return string|array|false data or false if not set
		 */
		public function get( $key = '' ) {
			
			return isset( $this->config[ $key ] ) ? $this->config[ $key ] : false;
			
		}
		
		/**
		 * Creates and returns an instance of the class.
		 *
		 * @since  1.4.7
		 * @access public
		 *
		 * @return object
		 */
		public static function get_instance() {
			
			if ( null === self::$instance ) {
				
				self::$instance = new self;
				
			}
			
			return self::$instance;
			
		}
		
	}
	
}
