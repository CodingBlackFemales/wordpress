<?php

namespace BBElementor\Templates\Types;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'BB_Elementor_Structure_Page' ) ) {
	
	/**
	 * Define BB_Elementor_Structure_Page class
	 */
	class BB_Elementor_Structure_Page extends BB_Elementor_Structure_Base {
		
		/**
		 * Get Section id.
		 *
		 * @since  1.4.7
		 * @access public
		 * @return string
		 */
		public function get_id() {
			return 'bb_elementor_pages';
		}
		
		/**
		 * Get Section Label.
		 *
		 * @since  1.4.7
		 * @access public
		 * @return string
		 */
		public function get_single_label() {
			return __( 'Page', 'buddyboss-theme' );
		}
		
		/**
		 * Get Section Plural Label.
		 *
		 * @since  1.4.7
		 * @access public
		 * @return string
		 */
		public function get_plural_label() {
			return __( 'Pages', 'buddyboss-theme' );
		}
		
		/**
		 * Get Sources.
		 *
		 * @since  1.4.7
		 * @access public
		 * @return array
		 */
		public function get_sources() {
			return array( 'bb-elementor-pages-api' );
		}
		
		/**
		 * Get Document Types.
		 *
		 * @since  1.4.7
		 * @access public
		 * @return array
		 */
		public function get_document_type() {
			return array(
				'class' => 'BB_Elementor_Pages_Document',
				'file'  => require ELEMENTOR_BB__DIR__ . '/templates/documents/page.php',
			);
		}
		
		/**
		 * Library settings for current structure.
		 *
		 * @since  1.4.7
		 * @access public
		 * @return array
		 */
		public function library_settings() {
			
			return array(
				'show_title' => false,
			);
			
		}
		
	}
	
}
