<?php
/**
 * Modern header variant.
 *
 * @since 4.20.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Settings\Header\Variants;

use LDLMS_Post_Types;
use WP_Screen;

/**
 * Modern header variant class.
 *
 * @since 4.20.0
 */
class Modern {
	/**
	 * Variant name.
	 *
	 * @since 4.20.0
	 *
	 * @var string
	 */
	private const NAME = 'modern';

	/**
	 * Supported post types.
	 *
	 * @since 4.20.0
	 *
	 * @var array<string>
	 */
	private const SUPPORTED_POST_TYPES = [
		LDLMS_Post_Types::COURSE,
		LDLMS_Post_Types::QUIZ,
	];

	/**
	 * Enables the header variant.
	 *
	 * We currently want to enable it for the course post type and on post adding/editing screens only.
	 *
	 * @since 4.20.0
	 *
	 * @param string $header_variant Header variant.
	 *
	 * @return string
	 */
	public function enable( $header_variant ) {
		if ( $this->is_adding_or_editing_supported_post_type() ) {
			return self::NAME;
		}

		return $header_variant;
	}

	/**
	 * Returns whether we are adding or editing a post type.
	 *
	 * @since 4.20.0
	 *
	 * @return bool
	 */
	private function is_adding_or_editing_supported_post_type(): bool {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();

		if ( ! $screen instanceof WP_Screen ) {
			return false;
		}

		if (
			! in_array(
				$screen->post_type,
				LDLMS_Post_Types::get_post_type_slug( self::SUPPORTED_POST_TYPES ),
				true
			)
		) {
			return false;
		}

		return $screen->action === 'add' // This is a new post creation screen.
			|| $screen->base === 'post'; // This is an existing post editing screen.
	}
}
