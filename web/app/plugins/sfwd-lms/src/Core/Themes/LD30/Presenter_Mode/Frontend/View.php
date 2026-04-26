<?php
/**
 * Presenter Mode Frontend view.
 *
 * @since 4.23.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Presenter_Mode\Frontend;

use LDLMS_Post_Types;
use LearnDash\Core\Themes\LD30\Presenter_Mode\Settings;
use LearnDash\Core\Template\Template;

/**
 * Presenter Mode Frontend view.
 *
 * @since 4.23.0
 */
class View {
	/**
	 * Injects the toggle button for presenter mode.
	 *
	 * @since 4.23.0
	 *
	 * @return void
	 */
	public function inject_toggle_button(): void {
		if ( ! $this->is_singular_course_step() ) {
			return;
		}

		Template::show_template(
			'focus/components/presenter-mode',
			[
				'course_id'        => learndash_get_course_id(),
				'icon_position'    => Settings::get()['presenter_mode_icon_position'],
				'sidebar_position' => Settings::get()['focus_mode_sidebar_position'],
			]
		);
	}

	/**
	 * Updates the body class when Presenter Mode is enabled.
	 *
	 * @since 4.23.0
	 *
	 * @param string[] $classes The body classes.
	 *
	 * @return string[] The updated body classes.
	 */
	public function update_body_class( $classes ) {
		if ( ! $this->is_singular_course_step() ) {
			return $classes;
		}

		$classes[] = 'ld-presenter-mode__body';

		return $classes;
	}

	/**
	 * Returns whether the current page is a singular course step.
	 *
	 * @since 4.23.0
	 *
	 * @return bool
	 */
	private function is_singular_course_step(): bool {
		$post_types = LDLMS_Post_Types::get_post_types( 'course_steps' );

		return is_singular( $post_types );
	}
}
