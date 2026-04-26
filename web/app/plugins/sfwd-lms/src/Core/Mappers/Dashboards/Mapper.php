<?php
/**
 * The dashboard mapper.
 *
 * @since 4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Mappers\Dashboards;

use LearnDash\Core\Template\Dashboards\Sections\Section;
use WP_Post;

/**
 * The dashboard mapper.
 *
 * @phpstan-consistent-constructor
 *
 * @since 4.9.0
 */
abstract class Mapper {
	/**
	 * The model.
	 *
	 * @since 4.9.0
	 *
	 * @var WP_Post
	 */
	protected $post;

	/**
	 * Constructor.
	 *
	 * @since 4.9.0
	 *
	 * @param WP_Post $post The post.
	 */
	public function __construct( WP_Post $post ) {
		$this->post = $post;
	}

	/**
	 * Maps the sections. Returns one section because it's the root section.
	 *
	 * @since 4.9.0
	 *
	 * @return Section
	 */
	abstract public function map(): Section;
}
