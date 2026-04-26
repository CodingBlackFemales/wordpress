<?php
/**
 * LearnDash Question Type Enum.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Enums\Models;

use StellarWP\Learndash\MyCLabs\Enum\Enum;

/**
 * LearnDash Question Type enum.
 *
 * @since 5.0.0
 *
 * @extends Enum<Question_Type::*>
 *
 * @method static self SINGLE_CHOICE()
 * @method static self MULTIPLE_CHOICE()
 * @method static self FREE_CHOICE()
 * @method static self SORTING_CHOICE()
 * @method static self MATRIX_SORTING_CHOICE()
 * @method static self FILL_IN_THE_BLANK()
 * @method static self ASSESSMENT()
 * @method static self ESSAY()
 */
class Question_Type extends Enum {
	/**
	 * Question type 'Single choice'.
	 *
	 * @since 5.0.0
	 */
	private const SINGLE_CHOICE = 'single';

	/**
	 * Question type 'Multiple choice'.
	 *
	 * @since 5.0.0
	 */
	private const MULTIPLE_CHOICE = 'multiple';

	/**
	 * Question type 'Free choice'.
	 *
	 * @since 5.0.0
	 */
	private const FREE_CHOICE = 'free_answer';

	/**
	 * Question type 'Sorting choice'.
	 *
	 * @since 5.0.0
	 */
	private const SORTING_CHOICE = 'sort_answer';

	/**
	 * Question type 'Matrix sorting choice'.
	 *
	 * @since 5.0.0
	 */
	private const MATRIX_SORTING_CHOICE = 'matrix_sort_answer';

	/**
	 * Question type 'Fill in the blank'.
	 *
	 * @since 5.0.0
	 */
	private const FILL_IN_THE_BLANK = 'cloze_answer';

	/**
	 * Question type 'Assessment'.
	 *
	 * @since 5.0.0
	 */
	private const ASSESSMENT = 'assessment_answer';

	/**
	 * Question type 'Essay'.
	 *
	 * @since 5.0.0
	 */
	private const ESSAY = 'essay';

	/**
	 * Returns the human-readable label for the question type.
	 *
	 * @since 5.0.0
	 *
	 * @return string The label.
	 */
	public function get_label(): string {
		switch ( $this->getValue() ) {
			case self::SINGLE_CHOICE:
				return esc_html__( 'Single choice', 'learndash' );
			case self::MULTIPLE_CHOICE:
				return esc_html__( 'Multiple choice', 'learndash' );
			case self::FREE_CHOICE:
				return esc_html__( 'Free choice', 'learndash' );
			case self::SORTING_CHOICE:
				return esc_html__( 'Sorting choice', 'learndash' );
			case self::MATRIX_SORTING_CHOICE:
				return esc_html__( 'Matrix Sorting choice', 'learndash' );
			case self::FILL_IN_THE_BLANK:
				return esc_html__( 'Fill in the blank', 'learndash' );
			case self::ASSESSMENT:
				return esc_html__( 'Assessment', 'learndash' );
			case self::ESSAY:
				return esc_html__( 'Essay / Open Answer', 'learndash' );
			default:
				return '';
		}
	}
}
