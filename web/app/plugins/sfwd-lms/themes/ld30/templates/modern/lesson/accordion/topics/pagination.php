<?php
/**
 * View: Lesson Accordion Topics - Pagination.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var array{topic: array{paged: int, pages_total: int}} $pagination Pagination data.
 * @var Models\Lesson                                     $lesson     Lesson model object.
 * @var Template                                          $this       Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models;
use LearnDash\Core\Template\Template;

$this->template(
	'modern/lesson/accordion/pagination',
	[
		'label'             => sprintf(
			// translators: %s: Topics custom label.
			__( '%s Pagination', 'learndash' ),
			learndash_get_custom_label( 'topics' ),
		),
		'paged'             => $pagination[ LDLMS_Post_Types::TOPIC ]['paged'],
		'pages_total'       => $pagination[ LDLMS_Post_Types::TOPIC ]['pages_total'],
		'pagination_source' => LDLMS_Post_Types::TOPIC,
		'parent'            => $lesson,
	]
);
