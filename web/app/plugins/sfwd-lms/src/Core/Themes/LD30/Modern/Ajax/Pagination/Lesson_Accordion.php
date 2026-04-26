<?php
/**
 * LearnDash LD30 Modern Lesson Accordion Pagination Ajax.
 *
 * @since 4.24.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Modern\Ajax\Pagination;

use LDLMS_Post_Types;
use LearnDash\Core\Models;
use LearnDash\Core\Template\Template;
use LearnDash\Core\Template\Views\Lesson;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;

/**
 * LearnDash LD30 Modern Lesson Accordion Pagination Ajax.
 *
 * @since 4.24.0
 */
class Lesson_Accordion {
	/**
	 * Pagination callback for lesson accordion topics.
	 *
	 * @since 4.24.0
	 *
	 * @return void
	 */
	public function topics_ajax_callback(): void {
		$nonce = Cast::to_string( SuperGlobals::get_get_var( 'nonce', '' ) );

		if (
			empty( $nonce )
			|| ! wp_verify_nonce(
				$nonce,
				'ld30-modern-lesson-accordion-pagination'
			)
		) {
			wp_send_json_error( __( 'Invalid nonce.', 'learndash' ) );
		}

		$paged = Cast::to_int( SuperGlobals::get_get_var( 'paged', 1 ) );

		if ( $paged <= 0 ) {
			$paged = 1;
		}

		$lesson_id = Cast::to_int( SuperGlobals::get_get_var( 'parent_id', 0 ) );

		if ( $lesson_id <= 0 ) {
			wp_send_json_error(
				sprintf(
					// translators: %s: Lesson label.
					__( 'Invalid %s ID ("parent_id" parameter).', 'learndash' ),
					learndash_get_custom_label( 'lesson' )
				)
			);
		}

		$lesson = Models\Lesson::find( $lesson_id );

		if ( ! $lesson ) {
			wp_send_json_error(
				sprintf(
					// translators: %s: Lesson label.
					__( '%s not found.', 'learndash' ),
					learndash_get_custom_label( 'lesson' )
				)
			);
		}

		$view = new Lesson(
			$lesson->get_post(),
			[
				'pagination' => [
					LDLMS_Post_Types::TOPIC => [
						'paged' => $paged,
					],
				],
			]
		);

		$args = $view->get_context();

		wp_send_json_success(
			[
				'html'   => Template::get_template(
					'modern/lesson/accordion/topics',
					$args
				),
				'header' => Template::get_template(
					'modern/lesson/accordion/header',
					$args
				),
			]
		);
	}
}
