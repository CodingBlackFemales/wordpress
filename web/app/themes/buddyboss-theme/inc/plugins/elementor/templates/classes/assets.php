<?php

namespace BBElementor\Templates\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // No access of directly access.

if ( ! class_exists( 'BB_Elementor_Templates_Assets' ) ) {
	
	/**
	 * BB Elementor Sections Templates Assets.
	 *
	 * BB Elementor Sections Templates Assets class is responsible for enqueuing all required assets for integration templates on the editor page.
	 *
	 * @since 1.4.7
	 */
	class BB_Elementor_Templates_Assets {
		
		/**
		 * Instance of the class.
		 *
		 * @since  1.4.7
		 * @access private
		 */
		private static $instance = null;
		
		/**
		 * BB_Elementor_Templates_Assets constructor.
		 *
		 * Triggers the required hooks to enqueue CSS/JS files.
		 *
		 * @since  1.4.7
		 * @access public
		 */
		public function __construct() {
			
			add_action( 'elementor/preview/enqueue_styles', array( $this, 'enqueue_preview_styles' ) );
			
			add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'editor_scripts' ), 0 );
			
			add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'editor_styles' ) );
			
			add_action( 'elementor/editor/footer', array( $this, 'load_footer_scripts' ) );
			
		}
		
		/**
		 * Preview Styles.
		 *
		 * Enqueue required templates CSS file.
		 *
		 * @since 1.4.7
		 * @access public
		 */
		public function enqueue_preview_styles() {
			
			$is_rtl = is_rtl() ? '-rtl' : '';
			
			wp_enqueue_style(
				'buddyboss-elementor-sections-editor-style',
				get_template_directory_uri() . '/inc/plugins/elementor/assets/editor/templates/css/preview' . $is_rtl . '.css',
				array(),
				1.0,
				'all'
			);
			
		}
		
		/**
		 * Editor Styles
		 *
		 * Enqueue required editor CSS files.
		 *
		 * @since  1.4.7
		 * @access public
		 */
		public function editor_styles() {
			
			$is_rtl = is_rtl() ? '-rtl' : '';
			
			wp_enqueue_style(
				'buddyboss-elementor-sections-editor-style',
				get_template_directory_uri() . '/inc/plugins/elementor/assets/editor/templates/css/editor' . $is_rtl . '.css',
				array(),
				1.0,
				'all'
			);
			
		}
		
		/**
		 * Editor Scripts.
		 *
		 * Enqueue required editor JS files, localize JS with required data.
		 *
		 * @since  1.4.7
		 * @access public
		 */
		public function editor_scripts() {
			wp_enqueue_script(
				'buddyboss-elementor-sections-temps-editor',
				get_template_directory_uri() . '/inc/plugins/elementor/assets/editor/templates/js/editor.js',
				array(
					'jquery',
					'underscore',
					'backbone-marionette'
				),
				1.0,
				true
			);

			wp_localize_script(
				'buddyboss-elementor-sections-temps-editor', 'BBElementorSectionsData',
				apply_filters( 'buddyboss-elementor-sections-templates-core/assets/editor/localize',
					array(
						'modalRegions'      => $this->get_modal_region(),
						'Elementor_Version' => ELEMENTOR_VERSION,
						'icon'              => get_template_directory_uri() . '/inc/plugins/elementor/assets/editor/templates/img/buddyboss.png',
					)
				)
			);

		}
		
		/**
		 * Get Modal Region.
		 *
		 * Get modal region in the editor.
		 *
		 * @since  1.4.7
		 * @access public
		 */
		public function get_modal_region() {
			
			return array(
				'modalHeader'  => '.dialog-header',
				'modalContent' => '.dialog-message',
			);
			
		}
		
		/**
		 * Add Templates Scripts.
		 *
		 * Load required templates for the templates library.
		 *
		 * @since  1.4.7
		 * @access public
		 */
		public function load_footer_scripts() {
			
			$scripts = glob( ELEMENTOR_BB__DIR__ . '/templates/scripts/*.php' );
			array_map( function ( $file ) {
				$name = basename( $file, '.php' );
				ob_start();
				include $file;
				printf( '<script type="text/html" id="tmpl-bbelementor-%1$s">%2$s</script>', $name, ob_get_clean() );
				
			}, $scripts );
			
		}
		
		/**
		 * Get Instance.
		 *
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