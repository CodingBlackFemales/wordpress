<?php
/**
 * View: Topic Alerts.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Models\Topic $topic Topic model.
 * @var Template     $this  Current Instance of template engine rendering this template.
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

if ( $topic->get_time_limit_in_seconds() > 0 ) {
	$this->template(
		'components/alert',
		[
			'type'    => 'timer',
			'icon'    => 'clock',
			'message' => sprintf(
				// translators: placeholder: topic.
				esc_html_x(
					'A minimal amount of time is required to spend on this %s in order to complete it.',
					'placeholder: topic',
					'learndash'
				),
				esc_html( learndash_get_custom_label_lower( 'topic' ) )
			),
			'action'  => [
				'label' => $topic->get_time_limit_formatted(),
			],
		]
	);
}

if ( $topic->supports_video_progression() ) {
	// TODO: Do we show it if the video is not added to the content by Learndash_Course_Video::add_video_to_content?
	$this->template(
		'components/alert',
		[
			'type'    => 'info',
			'icon'    => 'play',
			'message' => sprintf(
				// translators: placeholder: topic.
				esc_html_x(
					'Watch the video below in order to access the contents in this %s.',
					'placeholder: topic',
					'learndash'
				),
				esc_html( learndash_get_custom_label_lower( 'topic' ) )
			),
		]
	);
}
