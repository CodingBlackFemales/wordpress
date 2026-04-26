<?php
/**
 * This class provides the easy way to operate a user.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models;

use LearnDash\Core\Models\Commerce\Card;
use LearnDash\Core\Modules\Payments\DTO\Card as Card_DTO;
use WP_User;

/**
 * User model class.
 *
 * @since 4.25.0
 */
class User extends Model {
	/**
	 * User.
	 *
	 * @since 4.25.0
	 *
	 * @var WP_User
	 */
	protected $user;

	/**
	 * Creates a model from a user.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_User|int $user User ID or user object.
	 *
	 * @return static
	 */
	public static function create_from_user( $user ): self {
		$model = new static();

		if ( is_int( $user ) ) {
			$user = get_user_by( 'id', $user );
		}

		if ( ! $user instanceof WP_User ) {
			return $model;
		}

		$model->set_user( $user );

		return $model;
	}

	/**
	 * Returns the cards for a user.
	 *
	 * @since 4.25.0
	 *
	 * @return Card[]
	 */
	public function get_cards(): array {
		/**
		 * Filters the user cards.
		 *
		 * @since 4.25.0
		 *
		 * @param array $cards The user cards.
		 * @param User  $user  The user model.
		 *
		 * @return Card_DTO[]
		 */
		$cards = apply_filters( 'learndash_model_user_cards', [], $this );

		if (
			empty( $cards )
			|| ! is_array( $cards )
		) {
			return [];
		}

		return array_map(
			function ( Card_DTO $card ): Card {
				return new Card( $card->to_array() );
			},
			$cards
		);
	}

	/**
	 * Returns a user ID.
	 *
	 * @since 4.25.0
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->user->ID;
	}

	/**
	 * Returns a user display name.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	public function get_display_name(): string {
		return $this->user->display_name;
	}

	/**
	 * Returns a user property.
	 *
	 * @since 4.25.0
	 *
	 * @return WP_User
	 */
	public function get_user(): WP_User {
		return $this->user;
	}

	/**
	 * Sets a user property.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_User $user User.
	 *
	 * @return void
	 */
	protected function set_user( WP_User $user ): void {
		$this->user = $user;
	}
}
