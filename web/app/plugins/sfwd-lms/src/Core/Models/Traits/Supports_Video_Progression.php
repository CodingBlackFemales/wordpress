<?php
/**
 * Trait for models that support video progression.
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

namespace LearnDash\Core\Models\Traits;

use LearnDash\Core\Utilities\Str;
use Learndash_Course_Video;

/**
 * Trait for models that support video progression.
 *
 * @since 4.6.0
 */
trait Supports_Video_Progression {
	/**
	 * Returns a lesson content.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_content(): string {
		$content = parent::get_content();

		if ( $this->supports_video_progression() ) {
			// By default (legacy template), the video is added before the content. We need to add it after the content (if not set manually).
			if ( ! Str::contains( '[ld_video]', $content ) ) {
				$content .= '[ld_video]';
			}

			$content = Learndash_Course_Video::get_instance()->add_video_to_content(
				$content,
				$this->post,
				$this->get_settings()
			);
		}

		return $content;
	}

	/**
	 * Returns whether a model has a video progression enabled and has a video URL.
	 *
	 * @since 4.6.0
	 *
	 * @return bool
	 */
	public function supports_video_progression(): bool {
		if ( ! defined( 'LEARNDASH_LESSON_VIDEO' ) ) {
			return false;
		}

		// @phpstan-ignore-next-line -- It can be redefined.
		if ( true !== LEARNDASH_LESSON_VIDEO ) {
			return false;
		}

		if ( 'on' !== $this->get_setting( 'lesson_video_enabled' ) ) {
			return false;
		}

		return ! empty( $this->get_setting( 'lesson_video_url' ) );
	}
}
