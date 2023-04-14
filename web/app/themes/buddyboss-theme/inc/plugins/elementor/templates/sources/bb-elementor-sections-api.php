<?php

namespace BBElementor\Templates\Sources;

use BBElementor\Templates;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class BB_Elementor_Templates_Source_Api extends BB_Elementor_Templates_Source_Base {
	
	private $_object_cache = array();
	
	/**
	 * Return source slug.
	 *
	 * @since  1.4.7
	 * @access public
	 * @return string
	 */
	public function get_slug() {
		return 'bb-elementor-sections-api';
	}
	
	/**
	 * Return cached items list.
	 *
	 * @since  1.4.7
	 * @access public
	 *
	 * @param string $tab
	 *
	 * @return array
	 */
	public function get_items( $tab = null ) {
		
		if ( ! $tab ) {
			
			return array();
		}
		
		
		$templates = $this->remote_get_templates( $tab );
		
		if ( ! $templates ) {
			return array();
		}
		
		return $templates;
		
	}
	
	/**
	 * Prepare items tab.
	 *
	 * @since  1.4.7
	 *
	 * @param string $tab tab slug
	 *
	 * @return object $result templates data
	 */
	public function prepare_items_tab( $tab = '' ) {
		
		if ( ! empty( $this->_object_cache[ $tab ] ) ) {
			return $this->_object_cache[ $tab ];
		}
		
		$result = array(
			'templates'  => array(),
			'categories' => array(),
		);
		
		$result['templates'] = $this->remote_get_templates( $tab );
		$result['templates'] = $this->remote_get_categories( $tab );
		
		return $result;
	}
	
	/**
	 * Get templates from remote server.
	 * @since  1.4.7
	 *
	 * @param string $tab tab slug.
	 *
	 * @return array|bool
	 */
	public function remote_get_templates( $tab ) {
		
		$api_url = Templates\bb_elementor_templates()->api->api_url( 'templates' );
		
		// Bail out, if empty.
		if ( empty( $api_url ) ) {
			return false;
		}
		
		$response = wp_remote_get( $api_url . $tab, array(
			'timeout'   => 60,
			'sslverify' => false
		) );
		
		$body = wp_remote_retrieve_body( $response );
		
		// Bail out, if not set.
		if ( ! $body ) {
			return false;
		}
		
		$body = json_decode( $body, true );
		
		// Bail out, if not success.
		if ( ! isset( $body['success'] ) || true !== $body['success'] ) {
			return false;
		}
		
		// Bail out, if no templates.
		if ( empty( $body['templates'] ) ) {
			return false;
		}
		
		return $body['templates'];
		
	}
	
	/**
	 * Get categories from remote server.
	 * @since  1.4.7
	 *
	 * @param string $tab tab slug.
	 *
	 * @return array|bool
	 */
	public function remote_get_categories( $tab ) {
		
		$api_url = Templates\bb_elementor_templates()->api->api_url( 'categories' );
		
		// Bail out, if empty.
		if ( empty( $api_url ) ) {
			return false;
		}
		
		$response = wp_remote_get( $api_url . $tab, array(
			'timeout'   => 60,
			'sslverify' => false
		) );
		
		$body = wp_remote_retrieve_body( $response );
		
		if ( ! $body ) {
			return false;
		}
		
		$body = json_decode( $body, true );
		
		// Bail out, if not success.
		if ( ! isset( $body['success'] ) || true !== $body['success'] ) {
			return false;
		}
		
		// Bail out, if not set categories.
		if ( empty( $body['terms'] ) ) {
			return false;
		}
		
		return $body['terms'];
		
	}
	
	/**
	 * Return source item list.
	 * @since  1.4.7
	 *
	 * @param string $tab
	 *
	 * @access public
	 *
	 * @return array
	 */
	public function get_categories( $tab = null ) {
		
		if ( ! $tab ) {
			return array();
		}
		
		$categories = $this->remote_get_categories( $tab );
		
		if ( ! $categories ) {
			return array();
		}
		
		return $this->prepare_categories( $categories );
	}
	
	/**
	 * Prepare categories for response.
	 * @since  1.4.7
	 *
	 * @param array $categories
	 *
	 * @return array
	 */
	public function prepare_categories( $categories ) {
		
		$result = array();
		
		foreach ( $categories as $slug => $title ) {
			$result[] = array(
				'slug'  => $slug,
				'title' => $title,
			);
		}
		
		return $result;
	}
	
	/**
	 * Return single item.
	 *
	 * @since  1.4.7
	 * @access public
	 *
	 * @param bool $tab
	 * @param int  $template_id
	 *
	 * @return array
	 */
	public function get_item( $template_id, $tab = false ) {
		
		$api_url = Templates\bb_elementor_templates()->api->api_url( 'template' );
		
		if ( ! $api_url ) {
			wp_send_json_success( array(
				'licenseError' => true,
			) );
		}
		
		$request = $api_url . $template_id;
		
		$response = wp_remote_get( $request, array(
			'timeout'   => 60,
			'sslverify' => false
		) );
		
		$body = wp_remote_retrieve_body( $response );
		$body = json_decode( $body, true );
		
		if ( ! isset( $body['success'] ) ) {
			wp_send_json_error( array(
				'message' => 'Internal Error',
			) );
		}
		
		$content = isset( $body['content'] ) ? $body['content'] : '';
		$type    = isset( $body['type'] ) ? $body['type'] : '';
		
		if ( ! empty( $content ) ) {
			$content = $this->replace_elements_ids( $content );
			$content = $this->process_export_import_content( $content, 'on_import' );
		}
		
		return array(
			'page_settings' => array(),
			'type'          => $type,
			'content'       => $content
		);
		
	}
	
	/**
	 * Return transient lifetime.
	 *
	 * @since  1.4.7
	 * @access public
	 * @return string
	 */
	public function transient_lifetime() {
		return DAY_IN_SECONDS;
	}
}
