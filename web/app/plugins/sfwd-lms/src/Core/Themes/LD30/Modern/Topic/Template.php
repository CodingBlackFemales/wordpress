<?php
/**
 * LearnDash LD30 Modern Topic Template Tweaks.
 *
 * @since 4.24.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Modern\Topic;

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
 * LearnDash LD30 Modern Topic Template Tweaks.
 *
 * @since 4.24.0
 */
class Template {
	/**
	 * Adds additional context to the topic view.
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
		if ( ! $view instanceof Views\Topic ) {
			return $context;
		}

		$model = $view->get_model();

		$context = array_merge(
			$context,
			[
				'automatic_progression_enabled' => LearnDash_Settings_Section::get_section_setting(
					'LearnDash_Settings_Courses_Management_Display',
					'course_automatic_progression'
				) === 'yes',
				'is_content_visible'            => $model->is_content_visible(),
				'quizzes'                       => $model->get_quizzes(),
			]
		);

		$context['show_header'] = $this->should_show_header( $view, $user, $context );

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
		if ( $post->post_type !== learndash_get_post_type_slug( LDLMS_Post_Types::TOPIC ) ) {
			return $atts;
		}

		if (
			! isset( $atts['button'] )
			|| ! is_array( $atts['button'] )
		) {
			$atts['button'] = [];
		}

		$atts['button']['class'] = 'ld-navigation__progress-mark-complete-button ld-navigation__progress-mark-complete-button--topic ld--ignore-inline-css';

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
		if ( $post->post_type !== learndash_get_post_type_slug( LDLMS_Post_Types::TOPIC ) ) {
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
		if ( $post->post_type !== learndash_get_post_type_slug( LDLMS_Post_Types::TOPIC ) ) {
			return $timer_html;
		}

		return Template_Engine::get_template(
			'modern/topic/navigation/progress/mark-complete-timer',
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
		if ( $post->post_type !== learndash_get_post_type_slug( LDLMS_Post_Types::TOPIC ) ) {
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
	 * Determines whether the header should be shown on the topic page.
	 *
	 * @since 4.24.0
	 *
	 * @param Views\Topic          $view    The topic view.
	 * @param WP_User              $user    The user object.
	 * @param array<string, mixed> $context The view context array. Default is empty array.
	 *
	 * @return bool
	 */
	private function should_show_header( Views\Topic $view, WP_User $user, array $context = [] ): bool {
		$topic       = $view->get_model();
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
				&& $topic->user_has_access( $user )
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
		 * Filters whether the topic header should be shown on the topic page.
		 *
		 * @since 4.24.0
		 *
		 * @param bool          $show_header    Whether the lesson header should be shown. Default is false when Breadcrumbs, the Progress Bar, and Alerts are not visible.
		 * @param Views\Topic   $view           The topic view.
		 * @param WP_User       $user           The user object.
		 * @param Models\Topic  $topic          The topic model object.
		 * @param array<string, mixed> $context The view context array. Default is empty array.
		 *
		 * @return bool
		 */
		return apply_filters(
			'learndash_ld30_modern_topic_show_header',
			$show_header,
			$view,
			$user,
			$topic,
			$context
		);
	}
}
