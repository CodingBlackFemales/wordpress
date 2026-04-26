<?php

declare( strict_types=1 );

namespace LearnDash\Hub\Framework;

use LearnDash\Hub\Component\API;

defined( 'ABSPATH' ) || exit;

/**
 * Class Controller
 *
 * @package LearnDash\Supports
 */
class Controller {
	/**
	 * The page slug.
	 *
	 * @var string
	 */
	protected $slug;

	/**
	 * @var string
	 */
	protected $layout = '';

	/**
	 * The view render engine.
	 *
	 * @var View
	 */
	protected $render;

	/**
	 * @var string
	 */
	protected $module_dir;

	/**
	 * Controller constructor.
	 */
	public function __construct() {
		$tmp_path         = ( new \ReflectionClass( static::class ) )->getFileName();
		$this->module_dir = trailingslashit(
			substr_replace(
				$tmp_path,
				'',
				intval(
					strpos(
						$tmp_path,
						DIRECTORY_SEPARATOR . 'controller'
					)
				)
			)
		);

		$this->render = new View( $this->module_dir . 'view' );
	}

	/**
	 * A helper to quickly create a page or sub page
	 *
	 * @param $title
	 * @param $slug
	 * @param $callback
	 * @param null     $icon
	 * @param null     $parent_slug
	 */
	public function register_page( $title, $slug, $callback, $parent_slug = null, $icon = null ) {
		$hook     = is_multisite() ? 'network_admin_menu' : 'admin_menu';
		$function = function () use ( $title, $slug, $callback, $hook, $parent_slug, $icon ) {
			$cap = is_multisite() ? 'manage_network_options' : 'manage_options';
			if ( null === $parent_slug ) {
				add_menu_page( $title, $title, $cap, $slug, $callback, $icon );
			} else {
				add_submenu_page( $parent_slug, $title, $title, $cap, $slug, $callback );
			}
		};

		add_action( $hook, $function );
	}

	/**
	 * @param $view_file
	 * @param array     $params
	 * @param bool      $echo
	 *
	 * @return bool|string
	 */
	public function render( string $view_file, array $params = array(), bool $echo = true ) {
		// assign controller to this
		if ( ! isset( $params['controller'] ) ) {
			$params['controller'] = $this;
		}
		$content = $this->render->render( $view_file, $params );
		if ( ! empty( $this->layout ) ) {
			$template = new View( $this->module_dir . DIRECTORY_SEPARATOR . 'layouts' );
			$content  = $template->render(
				$this->layout,
				array_merge(
					$params,
					array(
						'controller' => $this,
						'contents'   => $content,
					)
				)
			);
		}
		if ( false === $echo ) {
			return $content;
		}

		echo $content;
	}

	/**
	 * Return all the data that requires for frontend.
	 *
	 * @return array
	 */
	protected function make_data(): array {
		return array();
	}

	/**
	 * The API instance.
	 *
	 * @var API
	 */
	protected $api;

	/**
	 * Get the API instance.
	 *
	 * @return API
	 */
	protected function get_api() {
		if ( ! $this->api instanceof API ) {
			$this->api = new API();
		}

		return $this->api;
	}
}
