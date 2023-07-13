<?php
/**
 * This class provides the easy way to operate a user.
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

namespace LearnDash\Core\Models;

use LearnDash\Core\Traits\Memoizable;
use WP_User;

// TODO: Add tests.

/**
 * User model class.
 *
 * @since 4.6.0
 */
abstract class User extends Model {
	use Memoizable;

	/**
	 * User.
	 *
	 * @since 4.6.0
	 *
	 * @var WP_User
	 */
	protected $user;

	/**
	 * Creates a model from a post.
	 *
	 * @since 4.6.0
	 *
	 * @param WP_User $user User.
	 *
	 * @return static
	 */
	public static function create_from_user( WP_User $user ): self {
		$model = new static();

		$model->set_user( $user );

		return $model;
	}

	/**
	 * Returns a user ID.
	 *
	 * @since 4.6.0
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->user->ID;
	}

	/**
	 * Returns a user display name.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_display_name(): string {
		return $this->user->display_name;
	}

	/**
	 * Returns a user property.
	 *
	 * @since 4.6.0
	 *
	 * @return WP_User
	 */
	public function get_user(): WP_User {
		return $this->user;
	}

	/**
	 * Sets a user property.
	 *
	 * @since 4.6.0
	 *
	 * @param WP_User $user User.
	 *
	 * @return void
	 */
	protected function set_user( WP_User $user ): void {
		$this->user = $user;
	}
}
