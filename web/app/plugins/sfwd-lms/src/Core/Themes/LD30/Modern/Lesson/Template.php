<?php
/**
 * LearnDash LD30 Modern Lesson Template Tweaks.
 *
 * @since 4.24.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Modern\Lesson;

use LDLMS_Post_Types;
use LearnDash\Core\Models;
use LearnDash\Core\Template\Template as Template_Engine;
use LearnDash\Core\Template\View as View_Base;
use LearnDash\Core\Template\Views;
use WP_Post;
use WP_User;
use LearnDash\Core\Template\Progression;
use LearnDash\Core\Template\Alerts;
use LearnDash\Core\Template\Breadcrumbs;
use LearnDash_Settings_Section;

/**
 * LearnDash LD30 Modern Lesson Template Tweaks.
 *
 * @since 4.24.0
 */
class Template {
	/**
	 * Lesson model object.
	 *
	 * @since 4.24.0
	 *
	 * @var Models\Lesson|null
	 */
	private ?Models\Lesson $lesson = null;

	/**
	 * Number of topics per page.
	 *
	 * @since 4.24.0
	 *
	 * @var int
	 */
	private int $topics_per_page = 0;

	/**
	 * Adds additional context to the lesson view.
	 *
	 * @since 4.24.0
	 *
	 * @param array<string,mixed> $context   The current context.
	 * @param string              $view_slug The view slug.
	 * @param bool                $is_admin  Whether the view is for an admin page.
	 * @param WP_User             $user      The user object.
	 * @param View_Base           $view      The view object.
	 *
	 * @return array<string, mixed>
	 */
	public function add_additional_context( $context, string $view_slug, bool $is_admin, WP_User $user, View_Base $view ): array {
		if ( ! $view instanceof Views\Lesson ) {
			return $context;
		}

		$this->lesson = $view->get_model();

		$course = $this->lesson->get_course();

		$this->topics_per_page = learndash_get_course_topics_per_page(
			$course ? $course->get_id() : 0,
			$this->lesson->get_id()
		);

		// Add pagination context to the global context.
		$context = $this->add_pagination_context( $context );

		// Map topics.
		$topics = $this->lesson->get_topics(
			$this->topics_per_page,
			$this->topics_per_page * ( $context['pagination'][ LDLMS_Post_Types::TOPIC ]['paged'] - 1 )
		);

		$context = array_merge(
			$context,
			[
				'automatic_progression_enabled' => LearnDash_Settings_Section::get_section_setting(
					'LearnDash_Settings_Courses_Management_Display',
					'course_automatic_progression'
				) === 'yes',
				'is_content_visible'            => $this->lesson->is_content_visible(),
				'quizzes'                       => $this->lesson->get_quizzes(),
				'topics'                        => $topics,
			]
		);

		$context['show_header'] = $this->should_show_header( $view, $user, $context );

		return $context;
	}

	/**
	 * Builds and adds the Pagination Context with properly nested defaults.
	 *
	 * @since 4.24.0
	 *
	 * @param array<string, mixed> $context Context array.
	 *
	 * @return array{pagination:array{topic:array{paged:int,pages_total:int}}}
	 */
	private function add_pagination_context( array $context ): array {
		$context = wp_parse_args(
			$context,
			[
				'pagination' => [],
			]
		);

		$context['pagination'] = wp_parse_args(
			$context['pagination'],
			[
				LDLMS_Post_Types::TOPIC => [],
			]
		);

		$context['pagination'][ LDLMS_Post_Types::TOPIC ] = wp_parse_args(
			$context['pagination'][ LDLMS_Post_Types::TOPIC ],
			[
				'paged'       => 1,
				'pages_total' => $this->lesson instanceof Models\Lesson
					? (int) ceil( $this->lesson->get_topics_number() / $this->topics_per_page )
					: 0,
			]
		);

		/**
		 * Modified View Context to include Pagination Context.
		 *
		 * @var array{pagination:array{topic:array{paged:int,pages_total:int}}} $context
		 */
		return $context;
	}

	/**
	 * Adds the mark complete button attributes.
	 *
	 * @since 4.24.0
	 *
	 * @param array<string,mixed> $atts The attributes.
	 * @param WP_Post             $post The post object.
	 *
	 * @return array<string,mixed>
	 */
	public function add_mark_complete_button_attributes( $atts, $post ) {
		if ( $post->post_type !== learndash_get_post_type_slug( LDLMS_Post_Types::LESSON ) ) {
			return $atts;
		}

		if (
			! isset( $atts['button'] )
			|| ! is_array( $atts['button'] )
		) {
			$atts['button'] = [];
		}

		$atts['button']['class'] = 'ld-navigation__progress-mark-complete-button ld-navigation__progress-mark-complete-button--lesson ld--ignore-inline-css';

		return $atts;
	}

	/**
	 * Adds the mark complete button icon.
	 *
	 * @since 4.24.0
	 *
	 * @param string  $input_button_html The HTML of the mark complete input button.
	 * @param WP_Post $post              WP_Post object being displayed.
	 * @param string  $button_id         The HTML ID attribute of the button.
	 * @param string  $button_class      The HTML class attribute of the button.
	 * @param string  $button_disabled   The HTML disabled attribute of the button.
	 * @param string  $button_label      The label of the button.
	 *
	 * @return string Returns the HTML of the mark complete input button.
	 */
	public function add_mark_complete_button_icon(
		$input_button_html,
		WP_Post $post,
		string $button_id,
		string $button_class,
		string $button_disabled,
		string $button_label
	) {
		if ( $post->post_type !== learndash_get_post_type_slug( LDLMS_Post_Types::LESSON ) ) {
			return $input_button_html;
		}

		$icon = Template_Engine::get_template(
			'components/icons/check-2',
			[
				'is_aria_hidden' => true,
				'classes'        => array_filter(
					[
						'ld-navigation__icon',
						! empty( $button_disabled ) ? 'ld-navigation__icon--disabled' : '',
					]
				),
			]
		);

		return '<button type="submit"' . $button_id . ' ' . $button_disabled . ' ' . $button_class . '>' . $icon . $button_label . '</button>';
	}

	/**
	 * Adds the mark complete timer HTML.
	 *
	 * @since 4.24.0
	 *
	 * @param string  $timer_html The HTML of the timer.
	 * @param WP_Post $post       WP_Post object being displayed.
	 *
	 * @return string Returns the HTML of the timer.
	 */
	public function add_mark_complete_timer_html(
		$timer_html,
		WP_Post $post
	) {
		if ( $post->post_type !== learndash_get_post_type_slug( LDLMS_Post_Types::LESSON ) ) {
			return $timer_html;
		}

		return Template_Engine::get_template(
			'modern/lesson/navigation/progress/mark-complete-timer',
			[
				'timer_html' => $timer_html,
			]
		);
	}

	/**
	 * Adds the mark incomplete button attributes.
	 *
	 * @since 4.24.0
	 *
	 * @param array<string,mixed> $atts The attributes.
	 * @param WP_Post             $post The post object.
	 *
	 * @return array<string,mixed>
	 */
	public function add_mark_incomplete_button_attributes( $atts, $post ) {
		if ( $post->post_type !== learndash_get_post_type_slug( LDLMS_Post_Types::LESSON ) ) {
			return $atts;
		}

		if (
			! isset( $atts['button'] )
			|| ! is_array( $atts['button'] )
		) {
			$atts['button'] = [];
		}

		$atts['button']['class'] = 'ld-navigation__progress-mark-incomplete-button ld--ignore-inline-css';

		return $atts;
	}

	/**
	 * Determines whether the header should be shown on the lesson page.
	 *
	 * @since 4.24.0
	 *
	 * @param Views\Lesson         $view    The lesson view.
	 * @param WP_User              $user    The user object.
	 * @param array<string, mixed> $context The view context array. Default is empty array.
	 *
	 * @return bool
	 */
	private function should_show_header( Views\Lesson $view, WP_User $user, array $context = [] ): bool {
		$lesson      = $view->get_model();
		$show_header = false;

		if (
			(
				isset( $context['breadcrumbs'] )
				&& $context['breadcrumbs'] instanceof Breadcrumbs\Breadcrumbs
				&& ! $context['breadcrumbs']->is_empty()
			)
			|| (
				isset( $context['progress_bar'] )
				&& $context['progress_bar'] instanceof Progression\Bar
				&& $context['progress_bar']->should_show()
				&& $user->exists()
				&& $lesson->user_has_access( $user )
			)
			|| (
				isset( $context['alerts'] )
				&& $context['alerts'] instanceof Alerts\Alerts
				&& ! $context['alerts']->is_empty()
			)
		) {
			$show_header = true;
		}

		/**
		 * Filters whether the lesson header should be shown on the lesson page.
		 *
		 * @since 4.24.0
		 *
		 * @param bool                 $show_header Whether the lesson header should be shown. Default is false when Breadcrumbs, the Progress Bar, and Alerts are not visible.
		 * @param Views\Lesson         $view        The lesson view.
		 * @param WP_User              $user        The user object.
		 * @param Models\Lesson        $lesson      The lesson model object.
		 * @param array<string, mixed> $context     The view context array. Default is empty array.
		 *
		 * @return bool
		 */
		return apply_filters(
			'learndash_ld30_modern_lesson_show_header',
			$show_header,
			$view,
			$user,
			$lesson,
			$context
		);
	}
}
