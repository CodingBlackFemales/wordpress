<?php
/**
 * View: Lesson Alerts.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Models\Lesson $lesson Lesson model.
 * @var Template      $this   Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Models;
use LearnDash\Core\Template\Template;


// TODO: Add another alert. If the user needs to complete the previous step, display an alert.

if ( $lesson->get_time_limit_in_seconds() > 0 ) {
	$this->template(
		'components/alert',
		[
			'type'    => 'timer',
			'icon'    => 'clock',
			'message' => sprintf(
				// translators: placeholder: lesson.
				esc_html_x(
					'A minimal amount of time is required to spend on this %s in order to complete it.',
					'placeholder: lesson',
					'learndash'
				),
				esc_html( learndash_get_custom_label_lower( 'lesson' ) )
			),
			'action'  => [
				'label' => $lesson->get_time_limit_formatted(),
			],
		]
	);
}

if ( $lesson->supports_video_progression() ) {
	// TODO: Do we show it if the video is not added to the content by Learndash_Course_Video::add_video_to_content?
	$this->template(
		'components/alert',
		[
			'type'    => 'info',
			'icon'    => 'play',
			'message' => sprintf(
				// translators: placeholder: lesson.
				esc_html_x(
					'Watch the video below in order to access the contents in this %s.',
					'placeholder: lesson',
					'learndash'
				),
				esc_html( learndash_get_custom_label_lower( 'lesson' ) )
			),
		]
	);
}

if ( $lesson->can_be_completed() ) {
	$this->template(
		'components/alert',
		[
			'type'    => 'info',
			'icon'    => 'info',
			'message' => __( 'All sections have been completed successfully.', 'learndash' ),
			'action'  => [
				'label' => __( 'Mark Lesson as Complete', 'learndash' ),
				'url'   => '#', // TODO: Not sure how it works $lesson->get_mark_complete_url().
			],
		]
	);
}
