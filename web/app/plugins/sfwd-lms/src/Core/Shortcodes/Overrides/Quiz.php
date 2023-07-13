<?php
/**
 * Class for overriding the quiz shortcode output.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

namespace LearnDash\Core\Shortcodes\Overrides;

use LearnDash\Core\Template\Views;
use WpProQuiz_Model_QuizMapper as LegacyMapper;
use WP_Post;

/**
 * Quiz shortcode override class.
 *
 * @since 4.6.0
 */
class Quiz {
	/**
	 * Hijack the quiz shortcode output.
	 *
	 * @since 4.6.0
	 *
	 * @param string               $output The shortcode output.
	 * @param array<string, mixed> $atts   The shortcode attributes.
	 *
	 * @return string
	 */
	public function override_output( $output, $atts ) {
		$quiz_mapper  = new LegacyMapper();
		$quiz         = $quiz_mapper->fetch( $atts['quiz_pro_id'] );
		$quiz_post_id = $quiz->getPostId();
		$quiz_post    = get_post( $quiz_post_id );

		if ( ! $quiz_post instanceof WP_Post ) {
			return $output;
		}

		$view = new Views\Quiz( $quiz_post, [] );

		return $view->get_html();
	}
}
