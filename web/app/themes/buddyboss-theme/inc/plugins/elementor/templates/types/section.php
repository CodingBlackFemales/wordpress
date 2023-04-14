<?php

namespace BBElementor\Templates\Types;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'BB_Elementor_Structure_Section' ) ) {
	
	/**
	 * Define BB_Elementor_Structure_Section class
	 */
	class BB_Elementor_Structure_Section extends BB_Elementor_Structure_Base {
		
		/**
		 * Get Section id.
		 *
		 * @since  1.4.7
		 * @access public
		 * @return string
		 */
		public function get_id() {
			return 'bb_elementor_sections';
		}
		
		/**
		 * Get Section Label.
		 *
		 * @since  1.4.7
		 * @access public
		 * @return string
		 */
		public function get_single_label() {
			return __( 'Section', 'buddyboss-theme' );
		}
		
		/**
		 * Get Section Plural Label.
		 *
		 * @since  1.4.7
		 * @access public
		 * @return string
		 */
		public function get_plural_label() {
			return __( 'Sections', 'buddyboss-theme' );
		}
		
		/**
		 * Get Sources.
		 *
		 * @since  1.4.7
		 * @access public
		 * @return array
		 */
		public function get_sources() {
			return array( 'bb-elementor-sections-api' );
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
				'class' => 'BB_Elementor_Sections_Document',
				'file'  => require ELEMENTOR_BB__DIR__ . '/templates/documents/section.php',
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
