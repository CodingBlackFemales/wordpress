<?php
/**
 * View: Course Accordion Lessons - Pagination.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Course                                             $course     Course model object.
 * @var array{lesson: array{paged: int, pages_total: int}} $pagination Pagination data.
 * @var Template                                           $this       Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Course;
use LearnDash\Core\Template\Template;

$this->template(
	'modern/course/accordion/pagination',
	[
		'label'             => sprintf(
			// translators: %s: Lessons custom label.
			__( '%s Pagination', 'learndash' ),
			learndash_get_custom_label( 'lessons' ),
		),
		'paged'             => $pagination[ LDLMS_Post_Types::LESSON ]['paged'],
		'pages_total'       => $pagination[ LDLMS_Post_Types::LESSON ]['pages_total'],
		'pagination_source' => LDLMS_Post_Types::LESSON,
		'parent'            => $course,
	]
);
