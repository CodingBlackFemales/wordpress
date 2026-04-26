<?php
/**
 * Blocks class.
 *
 * @since 4.21.4
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Course_Grid;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

use LearnDash\Course_Grid;
use LearnDash\Course_Grid\Gutenberg\Blocks\LearnDash_Course_Grid;
use LearnDash\Course_Grid\Gutenberg\Blocks\LearnDash_Course_Grid_Filter;

/**
 * Blocks class.
 */
class Blocks {
	/**
	 * LearnDash_Course_Grid block instance.
	 *
	 * @since 4.21.4
	 *
	 * @var LearnDash_Course_Grid
	 */
	public $learndash_course_grid;

	/**
	 * LearnDash_Course_Grid_Filter block instance.
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
		add_action( 'plugins_loaded', [ $this, 'init_blocks' ] );
	}

	/**
	 * Initializes blocks.
	 *
	 * @since 4.21.4
	 *
	 * @return void
	 */
	public function init_blocks() {
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_editor_assets' ], 20 );

		$blocks = [
			'learndash_course_grid'        => 'LearnDash_Course_Grid',
			'learndash_course_grid_filter' => 'LearnDash_Course_Grid_Filter',
		];

		foreach ( $blocks as $id => $class ) {
			$classname = '\\LearnDash\\Course_Grid\\Gutenberg\\Blocks\\' . $class;
			$this->$id = new $classname();
		}
	}

	/**
	 * Enqueue Block Editor Assets.
	 *
	 * @since 4.21.4
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets() {
		if ( ! is_admin() ) {
			return;
		}

		$asset_file = include LEARNDASH_COURSE_GRID_PLUGIN_PATH . 'includes/gutenberg/assets/js/index.asset.php';

		wp_register_script( 'learndash-course-grid-block-editor-helper', LEARNDASH_COURSE_GRID_PLUGIN_URL . 'assets/js/editor.js', [], LEARNDASH_VERSION, true );

		wp_enqueue_script( 'learndash-course-grid-block-editor', LEARNDASH_COURSE_GRID_PLUGIN_URL . 'includes/gutenberg/assets/js/index.js', array_merge( $asset_file['dependencies'], [ 'learndash-course-grid-block-editor-helper' ] ), $asset_file['version'], true );

		learndash_course_grid_load_inline_script_locale_data();

		wp_localize_script(
			'learndash-course-grid-block-editor',
			'LearnDash_Course_Grid_Block_Editor',
			[
				'post_types'          => Utilities::get_post_types_for_block_editor(),
				'skins'               => Course_Grid::instance()->skins->get_skins(),
				'cards'               => Course_Grid::instance()->skins->get_cards(),
				'editor_fields'       => Course_Grid::instance()->skins->get_default_editor_fields(),
				'image_sizes'         => Utilities::get_image_sizes_for_block_editor(),
				'orderby'             => Utilities::get_orderby_for_block_editor(),
				'taxonomies'          => Utilities::get_taxonomies_for_block_editor(),
				'paginations'         => Utilities::get_paginations_for_block_editor(),
				'is_learndash_active' => defined( 'LEARNDASH_VERSION' ) ? true : false,
			]
		);
	}
}
