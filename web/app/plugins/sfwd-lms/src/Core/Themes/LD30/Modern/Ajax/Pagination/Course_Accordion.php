<?php
/**
 * LearnDash LD30 Modern Course Accordion Pagination Ajax.
 *
 * @since 4.21.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Modern\Ajax\Pagination;

use LDLMS_Post_Types;
use LearnDash\Core\Models;
use LearnDash\Core\Template\Template;
use LearnDash\Core\Template\Views\Course;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;

/**
 * LearnDash LD30 Modern Course Accordion Pagination Ajax.
 *
 * @since 4.21.0
 */
class Course_Accordion {
	/**
	 * Pagination callback for course accordion lessons.
	 *
	 * @since 4.21.0
	 *
	 * @return void
	 */
	public function lessons_ajax_callback(): void {
		$nonce = Cast::to_string( SuperGlobals::get_get_var( 'nonce', '' ) );

		if (
			empty( $nonce )
			|| ! wp_verify_nonce(
				$nonce,
				'ld30-modern-course-accordion-pagination'
			)
		) {
			wp_send_json_error( __( 'Invalid nonce.', 'learndash' ) );
		}

		$paged = Cast::to_int( SuperGlobals::get_get_var( 'paged', 1 ) );

		if ( $paged <= 0 ) {
			$paged = 1;
		}

		$course_id = Cast::to_int( SuperGlobals::get_get_var( 'parent_id', 0 ) );

		if ( $course_id <= 0 ) {
			wp_send_json_error(
				sprintf(
					// translators: %s: Course label.
					__( 'Invalid %s ID ("parent_id" parameter).', 'learndash' ),
					learndash_get_custom_label( 'course' )
				)
			);
		}

		$course = Models\Course::find( $course_id );

		if ( ! $course ) {
			wp_send_json_error(
				sprintf(
					// translators: %s: Course label.
					__( '%s not found.', 'learndash' ),
					learndash_get_custom_label( 'course' )
				)
			);
		}

		$view = new Course(
			$course->get_post(),
			[
				'pagination' => [
					LDLMS_Post_Types::LESSON => [
						'paged' => $paged,
					],
				],
			]
		);

		$args = $view->get_context();

		wp_send_json_success(
			[
				'html'   => Template::get_template(
					'modern/course/accordion/lessons',
					$args
				),
				'header' => Template::get_template(
					'modern/course/accordion/header',
					$args
				),
			]
		);
	}
}
