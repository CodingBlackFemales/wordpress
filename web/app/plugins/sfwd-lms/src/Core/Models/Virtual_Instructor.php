<?php
/**
 * Virtual instructor model class file.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models;

use LDLMS_Post_Types;
use LearnDash\Core\Modules\AI\Virtual_Instructor\Repository;
use LearnDash\Core\Modules\AI\Virtual_Instructor\Settings;
use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\Utilities\Str;
use LearnDash_Settings_Section;

/**
 * Virtual instructor model class.
 *
 * @since 4.13.0
 */
class Virtual_Instructor extends Post {
	/**
	 * Gets allowed post types for this model.
	 *
	 * @since 4.13.0
	 *
	 * @return string[]
	 */
	public static function get_allowed_post_types(): array {
		return [
			learndash_get_post_type_slug( LDLMS_Post_Types::VIRTUAL_INSTRUCTOR ),
		];
	}

	/**
	 * Gets a model instance from an associated course ID.
	 *
	 * @since 4.13.0
	 *
	 * @param int $course_id Course ID.
	 *
	 * @return ?self
	 */
	public static function get_by_course_id( int $course_id ): ?self {
		$post = Repository::get_by_course_id( $course_id );

		if ( ! $post ) {
			return null;
		}

		return self::create_from_post( $post );
	}

	/**
	 * Gets virtual instructor name.
	 *
	 * @since 4.13.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		/**
		 * Filters virtual instructor name.
		 *
		 * @since 4.13.0
		 *
		 * @param string $name               Virtual instructor name.
		 * @param self   $virtual_instructor Virtual instructor model instance.
		 *
		 * @return string Virtual instructor name.
		 */
		return apply_filters(
			'learndash_model_virtual_instructor_name',
			$this->get_title(),
			$this
		);
	}

	/**
	 * Gets virtual instructor avatar ID.
	 *
	 * @since 4.13.0
	 *
	 * @return int
	 */
	private function get_avatar_id(): int {
		return Cast::to_int(
			$this->getAttribute( 'avatar_id' )
		);
	}

	/**
	 * Gets virtual instructor avatar URL.
	 *
	 * @since 4.13.0
	 *
	 * @param string $size Avatar WordPress media size. WordPress default sizes are 'thumbnail', 'medium', 'large', 'full'.
	 *
	 * @return string
	 */
	public function get_avatar_url( string $size = 'thumbnail' ): string {
		/**
		 * Filters virtual instructor avatar URL.
		 *
		 * @since 4.13.0
		 *
		 * @param string $avatar_url         Virtual instructor avatar URL.
		 * @param self   $virtual_instructor Virtual instructor model instance.
		 * @param string $size               Avatar WordPress media size.
		 *
		 * @return string Virtual instructor avatar URL.
		 */
		return apply_filters(
			'learndash_model_virtual_instructor_avatar_url',
			Cast::to_string(
				wp_get_attachment_image_url(
					$this->get_avatar_id(),
					$size
				)
			),
			$this,
			$size
		);
	}

	/**
	 * Gets virtual instructor custom instruction.
	 *
	 * @since 4.13.0
	 *
	 * @return string
	 */
	public function get_custom_instruction(): string {
		$instruction = trim(
			Cast::to_string(
				$this->getAttribute( 'custom_instruction' )
			)
		);

		if ( ! empty( $instruction ) ) {
			$instruction = sprintf(
				// translators: %1$s is the instructor name, %2$s is the custom instruction.
				__( 'You are a helpful tutor named %1$s. %2$s', 'learndash' ),
				$this->get_name(),
				$instruction
			);
		}

		/**
		 * Filters virtual instructor custom instruction.
		 *
		 * @since 4.13.0
		 *
		 * @param string $instruction        Virtual instructor custom instruction.
		 * @param self   $virtual_instructor Virtual instructor model instance.
		 *
		 * @return string Virtual instructor custom instruction.
		 */
		return apply_filters(
			'learndash_model_virtual_instructor_custom_instruction',
			$instruction,
			$this
		);
	}

	/**
	 * Checks if virtual instructor is applied to all courses.
	 *
	 * @since 4.13.0
	 *
	 * @return bool
	 */
	public function is_applied_to_all_courses(): bool {
		/***
		 * Filters virtual instructor is applied to all courses.
		 *
		 * @since 4.13.0
		 *
		 * @param bool $is_applied_to_all_courses True if virtual instructor is applied to all courses, false otherwise.
		 * @param self $virtual_instructor        Virtual instructor model instance.
		 *
		 * @return bool True if virtual instructor is applied to all courses, false otherwise.
		 */
		return apply_filters(
			'learndash_model_virtual_instructor_applied_to_all_courses',
			Cast::to_bool(
				$this->getAttribute( 'apply_to_all_courses' )
			),
			$this
		);
	}

	/**
	 * Gets associated course ids.
	 *
	 * @since 4.13.0
	 *
	 * @return int[]
	 */
	public function get_course_ids(): array {
		$course_ids = $this->getAttribute( 'course_ids' );

		$course_ids = ! empty( $course_ids ) && is_array( $course_ids )
			? $course_ids
			: [];

		/**
		 * Filters virtual instructor course ids.
		 *
		 * @since 4.13.0
		 *
		 * @param int[] $course_ids         Course ids.
		 * @param self  $virtual_instructor Virtual instructor model instance.
		 *
		 * @return int[] Course ids.
		 */
		return apply_filters( 'learndash_model_virtual_instructor_course_ids', $course_ids, $this );
	}

	/**
	 * Checks if virtual instructor is applied to all groups.
	 *
	 * @since 4.13.0
	 *
	 * @return bool
	 */
	public function is_applied_to_all_groups(): bool {
		/**
		 * Filters virtual instructor is applied to all groups.
		 *
		 * @since 4.13.0
		 *
		 * @param bool $is_applied_to_all_groups True if virtual instructor is applied to all groups, false otherwise.
		 * @param self $virtual_instructor       Virtual instructor model instance.
		 *
		 * @return bool True if virtual instructor is applied to all groups, false otherwise.
		 */
		return apply_filters(
			'learndash_model_virtual_instructor_applied_to_all_groups',
			Cast::to_bool(
				$this->getAttribute( 'apply_to_all_groups' )
			),
			$this
		);
	}

	/**
	 * Gets virtual instructor group ids.
	 *
	 * @since 4.13.0
	 *
	 * @return int[]
	 */
	public function get_group_ids(): array {
		$group_ids = $this->getAttribute( 'group_ids' );

		$group_ids = ! empty( $group_ids ) && is_array( $group_ids )
			? $group_ids
			: [];

		/**
		 * Filters virtual instructor group ids.
		 *
		 * @since 4.13.0
		 *
		 * @param int[] $group_ids          Group ids.
		 * @param self  $virtual_instructor Virtual instructor model instance.
		 *
		 * @return int[] Group ids.
		 */
		return apply_filters( 'learndash_model_virtual_instructor_group_ids', $group_ids, $this );
	}

	/**
	 * Gets virtual instructor override banned words value.
	 *
	 * @since 4.13.0
	 *
	 * @return bool
	 */
	private function use_custom_banned_words(): bool {
		return Cast::to_bool(
			$this->getAttribute( 'override_banned_words' )
		);
	}

	/**
	 * Gets virtual instructor banned words.
	 *
	 * @since 4.13.0
	 *
	 * @return string[]
	 */
	public function get_banned_words(): array {
		$banned_words = $this->use_custom_banned_words()
			? $this->getAttribute( 'banned_words' )
			: LearnDash_Settings_Section::get_section_setting( Settings\Page_Section::class, 'banned_words' );

		$banned_words = Cast::to_string( $banned_words );

		// Wrap in array_filter() to remove empty strings from the array.

		$banned_words = array_filter(
			array_map(
				'trim',
				explode( ',', $banned_words )
			)
		);

		/**
		 * Filters virtual instructor banned words.
		 *
		 * @since 4.13.0
		 *
		 * @param string[] $banned_words      Banned words.
		 * @param self     $virtual_instructor Virtual instructor model instance.
		 *
		 * @return string[] Banned words.
		 */
		return apply_filters( 'learndash_model_virtual_instructor_banned_words', $banned_words, $this );
	}

	/**
	 * Gets virtual instructor banned words matching type.
	 *
	 * @since 4.13.0
	 *
	 * @return string
	 */
	private function get_banned_words_matching(): string {
		return Cast::to_string(
			Settings\Page_Section::get_setting( 'banned_words_matching', Settings\Page_Section::BANNED_WORDS_MATCHING_KEY_ANY )
		);
	}

	/**
	 * Checks if virtual instructor uses custom error message.
	 *
	 * @since 4.13.0
	 *
	 * @return bool
	 */
	private function use_custom_error_message(): bool {
		return Cast::to_bool(
			$this->getAttribute( 'override_error_message' )
		);
	}

	/**
	 * Gets virtual instructor custom error message.
	 *
	 * @since 4.13.0
	 *
	 * @return string
	 */
	public function get_error_message(): string {
		$error_message = $this->use_custom_error_message()
			? $this->getAttribute( 'error_message' )
			: LearnDash_Settings_Section::get_section_setting( Settings\Page_Section::class, 'error_message' );

		/**
		 * Filters virtual instructor error message.
		 *
		 * @since 4.13.0
		 *
		 * @param string $error_message      Error message.
		 * @param self   $virtual_instructor Virtual instructor model instance.
		 *
		 * @return string Error message.
		 */
		return apply_filters(
			'learndash_model_virtual_instructor_error_message',
			Cast::to_string( $error_message ),
			$this
		);
	}

	/**
	 * Checks if message contains banned words.
	 *
	 * @since 4.13.0
	 *
	 * @param string $message Message text to check.
	 *
	 * @return bool True if message contains banned words, false otherwise.
	 */
	public function message_contains_banned_words( string $message ): bool {
		$message               = strtolower( $message );
		$banned_words          = array_map(
			'strtolower',
			$this->get_banned_words()
		);
		$contains_banned_words = false;

		if ( $this->get_banned_words_matching() === Settings\Page_Section::BANNED_WORDS_MATCHING_KEY_ANY ) {
			$contains_banned_words = Str::contains( $message, $banned_words );
		} elseif ( $this->get_banned_words_matching() === Settings\Page_Section::BANNED_WORDS_MATCHING_KEY_EXACT ) {
			$contains_banned_words = Str::contains_all( $message, $banned_words );
		}

		/**
		 * Filters virtual instructor message contains banned words.
		 *
		 * @since 4.13.0
		 *
		 * @param bool  $contains_banned_words True if message contains banned words, false otherwise.
		 * @param self  $virtual_instructor    Virtual instructor model instance.
		 *
		 * @return bool True if message contains banned words, false otherwise.
		 */
		return apply_filters(
			'learndash_model_virtual_instructor_message_contains_banned_words',
			! empty( $banned_words ) && $contains_banned_words,
			$this
		);
	}
}
