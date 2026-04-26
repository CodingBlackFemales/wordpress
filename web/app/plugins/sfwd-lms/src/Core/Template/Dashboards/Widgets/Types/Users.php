<?php
/**
 * Users based widget.
 *
 * @since 4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template\Dashboards\Widgets\Types;

use LearnDash\Core\Template\Dashboards\Widgets\Traits\Auto_View_Name;
use LearnDash\Core\Template\Dashboards\Widgets\Widget;
use WP_User;

/**
 * Users based widget.
 *
 * @since 4.9.0
 */
abstract class Users extends Widget {
	use Auto_View_Name;

	/**
	 * Users.
	 *
	 * @since 4.9.0
	 *
	 * @var WP_User[]
	 */
	protected $users = [];

	/**
	 * Custom label property. If not empty, it will be used in the view to show a label.
	 * It will be taken from the user object which supports custom properties.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	protected $custom_label_property = '';

	/**
	 * Returns users.
	 *
	 * @since 4.9.0
	 *
	 * @return WP_User[]
	 */
	public function get_users(): array {
		return $this->users;
	}

	/**
	 * Returns a custom label property.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_custom_label_property(): string {
		return $this->custom_label_property;
	}

	/**
	 * Sets users.
	 *
	 * @since 4.9.0
	 *
	 * @param WP_User[] $users Users.
	 *
	 * @return void
	 */
	public function set_users( array $users ): void {
		$this->users = $users;
	}

	/**
	 * Sets a custom label property.
	 *
	 * @since 4.9.0
	 *
	 * @param string $custom_label_property Custom label property.
	 *
	 * @return void
	 */
	public function set_custom_label_property( string $custom_label_property ): void {
		$this->custom_label_property = $custom_label_property;
	}

	/**
	 * Returns a widget empty state text. It is used when there is no data to show.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_empty_state_text(): string {
		return __( 'No users found.', 'learndash' );
	}
}
