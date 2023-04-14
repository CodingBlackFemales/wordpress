<?php

namespace BBElementor\Templates\Sources;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

abstract class BB_Elementor_Templates_Source_Base {
	
	/**
	 * get slug.
	 *
	 * @abstract
	 * @since  1.4.7
	 * @access public
	 */
	abstract public function get_slug();
	
	/**
	 * get items.
	 *
	 * @abstract
	 * @since  1.4.7
	 * @access public
	 */
	abstract public function get_items();
	
	/**
	 * get categories.
	 *
	 * @abstract
	 * @since  1.4.7
	 * @access public
	 */
	abstract public function get_categories();
	
	/**
	 * get items.
	 *
	 * @abstract
	 * @since  1.4.7
	 * @access public
	 *
	 * @param string $template_id
	 */
	abstract public function get_item( $template_id );
	
	/**
	 * get transient lifetime.
	 *
	 * @abstract
	 * @since  1.4.7
	 * @access public
	 */
	abstract public function transient_lifetime();
	
	/**
	 * Returns templates transient key for current source
	 * @since  1.4.7
	 * @return string
	 */
	public function templates_key() {
		return 'bb_elementor_templates_' . $this->get_slug();
	}
	
	/**
	 * Returns categories transient key for current source
	 * @since  1.4.7
	 * @return string
	 */
	public function categories_key() {
		return 'bb_elementor_categories_' . $this->get_slug();
	}
	
	/**
	 * Set templates cache.
	 * @since  1.4.7
	 *
	 * @param array $value
	 */
	public function set_templates_cache( $value ) {
		set_transient( $this->templates_key(), $value, $this->transient_lifetime() );
	}
	
	/**
	 * Get templates cache.
	 * @since  1.4.7
	 *
	 * @param array
	 *
	 * @return bool|array
	 */
	public function get_templates_cache() {
		return get_transient( $this->templates_key() );
	}
	
	/**
	 * Delete templates cache
	 * @since  1.4.7
	 */
	public function delete_templates_cache() {
		delete_transient( $this->templates_key() );
	}
	
	/**
	 * Set categories cache.
	 * @since  1.4.7
	 *
	 * @param array $value
	 */
	public function set_categories_cache( $value ) {
		set_transient( $this->categories_key(), $value, $this->transient_lifetime() );
	}
	
	/**
	 * Set categories cache.
	 * @since  1.4.7
	 *
	 * @param array
	 *
	 * @return array|bool
	 */
	public function get_categories_cache() {
		return get_transient( $this->categories_key() );
	}
	
	/**
	 * Delete categories cache.
	 * @since  1.4.7
	 */
	public function delete_categories_cache() {
		delete_transient( $this->categories_key() );
	}
	
	/**
	 * Replace Elements Ids.
	 *
	 * @since  1.4.7
	 * @access protected
	 *
	 * @param string $content
	 *
	 * @return array $element
	 */
	protected function replace_elements_ids( $content ) {
		return \Elementor\Plugin::$instance->db->iterate_data( $content, function ( $element ) {
			$element['id'] = \Elementor\Utils::generate_random_string();
			
			return $element;
		} );
	}
	
	/**
	 * Process content for export/import.
	 *
	 * Process the content and all the inner elements, and prepare all the
	 * elements data for export/import.
	 *
	 * @since  1.4.7
	 * @access protected
	 *
	 * @param array  $content A set of elements.
	 * @param string $method  Accepts either `on_export` to export data or
	 *                        `on_import` to import data.
	 *
	 * @return mixed Processed content data.
	 */
	protected function process_export_import_content( $content, $method ) {
		return \Elementor\Plugin::$instance->db->iterate_data(
			$content, function ( $element_data ) use ( $method ) {
			$element = \Elementor\Plugin::$instance->elements_manager->create_element_instance( $element_data );
			
			// If the widget/element isn't exist, like a plugin that creates a widget but deactivated.
			if ( ! $element ) {
				return null;
			}
			
			return $this->process_element_export_import_content( $element, $method );
		}
		);
	}
	
	/**
	 * Process single element content for export/import.
	 *
	 * Process any given element and prepare the element data for export/import.
	 *
	 * @since  1.4.7
	 * @access protected
	 *
	 * @param object $element
	 * @param string $method
	 *
	 * @return array Processed element data.
	 */
	protected function process_element_export_import_content( $element, $method ) {
		
		$element_data = $element->get_data();
		
		if ( method_exists( $element, $method ) ) {
			$element_data = $element->{$method}( $element_data );
		}
		
		foreach ( $element->get_controls() as $control ) {
			$control_class = \Elementor\Plugin::$instance->controls_manager->get_control( $control['type'] );
			
			// If the control isn't exist, like a plugin that creates the control but deactivated.
			if ( ! $control_class ) {
				return $element_data;
			}
			
			if ( method_exists( $control_class, $method ) ) {
				$element_data['settings'][ $control['name'] ] = $control_class->{$method}( $element->get_settings( $control['name'] ), $control );
			}
		}
		
		return $element_data;
	}
}
