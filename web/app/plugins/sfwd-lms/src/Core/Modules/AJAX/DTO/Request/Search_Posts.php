<?php
/**
 * Search Posts Request DTO for AJAX module.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AJAX\DTO\Request;

use Learndash_DTO;
use WP_Query;

/**
 * Search Posts Request DTO class.
 *
 * @since 4.8.0
 */
class Search_Posts extends Learndash_DTO {
	/**
	 * Properties are being cast to the specified type on construction.
	 *
	 * @since 4.8.0
	 *
	 * @var array<string, string>
	 */
	protected $cast = [
		'parent_ids'     => 'array',
		'course_ids'     => 'array',
		'lesson_ids'     => 'array',
		'topic_ids'      => 'array',
		'quiz_ids'       => 'array',
		'has_parent'     => 'bool',
		'keyword'        => 'string',
		'posts_per_page' => 'int',
		'paged'          => 'int',
		'label'          => 'string',
		'post_type'      => 'string',
		'query'          => 'WP_Query',
	];

	/**
	 * Parent IDs.
	 *
	 * @since 4.8.0
	 *
	 * @var array<int>
	 */
	public $parent_ids = [];

	/**
	 * Course IDs.
	 *
	 * @since 4.8.0
	 *
	 * @var array<int>
	 */
	public $course_ids = [];

	/**
	 * Lesson IDs.
	 *
	 * @since 4.8.0
	 *
	 * @var array<int>
	 */
	public $lesson_ids = [];

	/**
	 * Topic IDs.
	 *
	 * @since 4.8.0
	 *
	 * @var array<int>
	 */
	public $topic_ids = [];

	/**
	 * Quiz IDs.
	 *
	 * @since 4.8.0
	 *
	 * @var array<int>
	 */
	public $quiz_ids = [];

	/**
	 * Has parent.
	 *
	 * @since 4.8.0
	 *
	 * @var bool
	 */
	public $has_parent = false;

	/**
	 * Keyword
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public $keyword = '';

	/**
	 * Posts per page.
	 *
	 * @since 4.8.0
	 *
	 * @var int
	 */
	public $posts_per_page = 10;

	/**
	 * Page.
	 *
	 * @since 4.8.0
	 *
	 * @var int
	 */
	public $paged = 1;

	/**
	 * Post type label.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public $label = '';

	/**
	 * Post type slug.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public $post_type = '';

	/**
	 * WP_Query object.
	 *
	 * @since 4.8.0
	 *
	 * @var WP_Query
	 */
	public $query;
}
