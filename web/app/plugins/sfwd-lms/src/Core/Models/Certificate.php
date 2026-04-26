<?php
/**
 * This class provides the easy way to operate a certificate.
 *
 * @since 4.21.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models;

use LDLMS_Post_Types;

/**
 * Certificate model class.
 *
 * @since 4.21.0
 */
class Certificate extends Post {
	/**
	 * Returns allowed post types.
	 *
	 * @since 4.21.0
	 *
	 * @return string[]
	 */
	public static function get_allowed_post_types(): array {
		return [
			LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::CERTIFICATE ),
		];
	}
}
