<?php
/**
 * Provider for LD30 Modern Ajax Pagination functionality.
 *
 * @since 4.21.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Modern\Ajax\Pagination;

use LDLMS_Post_Types;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Class Provider for initializing LD30 Modern Ajax Pagination functionality.
 *
 * @since 4.21.0
 */
class Provider extends ServiceProvider {
	/**
	 * Registers the service provider bindings.
	 *
	 * @since 4.21.0
	 *
	 * @return void
	 */
	public function register(): void {
		$this->hooks();
	}

	/**
	 * Register hooks for the provider.
	 *
	 * @since 4.21.0
	 *
	 * @return void
	 */
	private function hooks(): void {
		// Course Accordion.

		$lesson_post_type_key = LDLMS_Post_Types::LESSON;

		add_action(
			"wp_ajax_ld30_modern_course_accordion_{$lesson_post_type_key}_pagination",
			$this->container->callback(
				Course_Accordion::class,
				'lessons_ajax_callback'
			)
		);

		add_action(
			"wp_ajax_nopriv_ld30_modern_course_accordion_{$lesson_post_type_key}_pagination",
			$this->container->callback(
				Course_Accordion::class,
				'lessons_ajax_callback'
			)
		);

		// Lesson Accordion.

		$topic_post_type_key = LDLMS_Post_Types::TOPIC;

		add_action(
			"wp_ajax_ld30_modern_lesson_accordion_{$topic_post_type_key}_pagination",
			$this->container->callback(
				Lesson_Accordion::class,
				'topics_ajax_callback'
			)
		);

		add_action(
			"wp_ajax_nopriv_ld30_modern_lesson_accordion_{$topic_post_type_key}_pagination",
			$this->container->callback(
				Lesson_Accordion::class,
				'topics_ajax_callback'
			)
		);
	}
}
