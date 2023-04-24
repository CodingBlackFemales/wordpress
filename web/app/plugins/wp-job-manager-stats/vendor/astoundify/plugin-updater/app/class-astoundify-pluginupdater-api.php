<?php
/**
 * API
 *
 * @since 1.0.0
 *
 * @package PluginUpdater
 * @category Core
 * @author Astoundify
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A plugin to update.
 *
 * @since 1.0.0
 * @version 1.0.0
 * @class Astoundify_PluginUpdater_Api
 */
class Astoundify_PluginUpdater_Api {

	/**
	 * API URL
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $api_url = 'https://astoundify.com';

	/**
	 * Get API URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string $api_url.
	 */
	public function get_api_url() {
		return $this->api_url;
	}

	/**
	 * Make an API request.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Param for URL Request.
	 * @return mixed
	 */
	public function request( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'url' => home_url(),
		) );

		$response = wp_remote_get(
			add_query_arg( $args, $this->get_api_url() ),
			array(
				'timeout'   => 15,
				'sslverify' => false,
			)
		);

		return $response;
	}

}
