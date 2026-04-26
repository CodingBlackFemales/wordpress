<?php
/**
 * Question Admin Edit screen class file.
 *
 * @since 4.21.4
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Quiz\Question\Admin;

use LDLMS_Post_Types;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\AdminNotices\AdminNotices;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;
use WpProQuiz_Model_Question;

/**
 * Question admin edit class.
 *
 * @since 4.21.4
 */
class Edit {
	/**
	 * Returns the matrix sort answer accessibility warning.
	 *
	 * @param bool $with_link Whether to include a link to the LearnDash documentation.
	 *
	 * @since 4.21.4
	 *
	 * @return string
	 */
	public static function get_matrix_sort_answer_accessibility_warning( $with_link = true ): string {
		$warning = sprintf(
				// Translators: %1$s: Question label.
			__(
				'This %1$s type only partially conforms with WCAG AA Accessibility guidelines.',
				'learndash'
			),
			learndash_get_custom_label_lower( 'question' ),
		);

		if ( $with_link ) {
			$warning .= sprintf(
				// Translators: %1$s: Opening link tag, %2$s: Closing link tag.
				__( ' %1$sLearn More%2$s', 'learndash' ),
				'<a href="https://go.learndash.com/vpat" rel="noopener noreferrer" target="_blank">',
				'</a>'
			);
		}

		return $warning;
	}

	/**
	 * Registers admin notices.
	 *
	 * @since 4.21.4
	 *
	 * @return void
	 */
	public function register_admin_notices(): void {
		AdminNotices::show(
			'learndash--matrix-sorting-question-accessibility-notice',
			self::get_matrix_sort_answer_accessibility_warning()
		)
			->on(
				[
					'id'          => learndash_get_post_type_slug( LDLMS_Post_Types::QUESTION ),
					'parent_base' => 'edit',
				]
			)
			->when(
				function () {
					$question_post_id = SuperGlobals::get_get_var( 'post' );

					if ( ! $question_post_id ) {
						return false;
					}

					$question = fetchQuestionModel(
						Cast::to_int(
							get_post_meta(
								Cast::to_int( $question_post_id ),
								'question_pro_id',
								true
							)
						)
					);

					return $question instanceof WpProQuiz_Model_Question
						&& $question->getAnswerType() === 'matrix_sort_answer';
				}
			)
			->autoParagraph()
			->asWarning();
	}
}
