<?php
/**
 * Shortcodes class file.
 *
 * @since 4.21.4
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Course_Grid;

use LearnDash\Course_Grid\Shortcodes\LearnDash_Course_Grid;
use LearnDash\Course_Grid\Shortcodes\LearnDash_Course_Grid_Filter;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Shortcodes module class.
 *
 * @since 4.21.4
 */
class Shortcodes {
	/**
	 * LearnDash_Course_Grid shortcode instance.
	 *
	 * @since 4.21.4
	 *
	 * @var LearnDash_Course_Grid
	 */
	public $learndash_course_grid;

	/**
	 * LearnDash_Course_Grid_Filter shortcode instance.
	 *
	 * @since 4.21.4
	 *
	 * @var LearnDash_Course_Grid_Filter
	 */
	public $learndash_course_grid_filter;

	/**
	 * Constructor.
	 *
	 * @since 4.21.4
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'init_shortcodes' ] );
	}

	/**
	 * Initialize shortcodes.
	 *
	 * @since 4.21.4
	 *
	 * @return void
	 */
	public function init_shortcodes() {
		$shortcodes = [
			'learndash_course_grid'        => 'LearnDash_Course_Grid',
			'learndash_course_grid_filter' => 'LearnDash_Course_Grid_Filter',
		];

		foreach ( $shortcodes as $tag => $class ) {
			$classname  = '\\LearnDash\\Course_Grid\\Shortcodes\\' . $class;
			$this->$tag = new $classname();
		}
	}
}
