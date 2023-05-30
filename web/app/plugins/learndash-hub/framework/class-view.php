<?php
/**
 * Author: Hoang Ngo
 */

namespace LearnDash\Hub\Framework;

/**
 * Class View
 *
 * @package Projects
 */
class View {
	/**
	 * Template file this view should render in
	 *
	 * @var string
	 */
	public $layout = null;

	/**
	 * The folder contains view files, absolute path
	 *
	 * @var string
	 */
	private $base_path;

	/**
	 * View constructor.
	 *
	 * @param string $base_path The path of the views folder.
	 */
	public function __construct( string $base_path ) {
		$this->base_path = trailingslashit( $base_path );
	}

	/**
	 * Render a view file, this will be use to render a whole page, if a layout defined, then we will render layout + view
	 *
	 * @param string $view View filename without extension.
	 * @param array  $params The data that pass to the view.
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function render( string $view, array $params = array() ) {
		$view_file = $this->base_path . $view . '.php';

		if ( is_file( $view_file ) ) {
			return $this->render_php_file( $view_file, $params );
		}

		throw new \Exception( sprintf( 'View %s not found.', $view_file ) );
	}

	/**
	 * Render the php file.
	 *
	 * @param string $file Absolute path to the file.
	 * @param array  $params The data that will be passed to the view.
	 *
	 * @return string
	 */
	private function render_php_file( string $file, array $params = array() ) {
		ob_start();
		ob_implicit_flush( false );
		extract( $params, EXTR_OVERWRITE );
		require $file;

		return ob_get_clean();
	}
}
