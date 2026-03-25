<?php
/**
 * Class BB_SSO_Provider_Dummy
 *
 * This abstract class serves as a template for creating dummy SSO providers.
 * It provides common functionality for handling OAuth redirects, popup dimensions,
 * and access to provider settings and admin classes.
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO
 */

abstract class BB_SSO_Provider_Dummy {

	/**
	 * Defines the way the OAuth redirect is handled.
	 *
	 * Possible values:
	 * - default_redirect: both the App and the Authorization requests accept GET parameters in the redirect URI.
	 * - default_redirect_but_app_has_restriction: the App doesn't allow redirect URLs with GET parameters,
	 *   but the Authorization requests accept it.
	 * - rest_redirect: neither the App nor the Authorization requests allow redirect URLs with GET parameters.
	 *   In these cases, the REST Endpoint of the provider is used:
	 *   e.g., https://example.com/wp-json/bb-social-login/v1/{{provider_id}}/redirect_uri
	 *   that passes the state and code to the login endpoint of the provider.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	public $auth_redirect_behavior = 'default';

	/**
	 * Settings instance associated with the SSO provider.
	 *
	 * @since 2.6.30
	 *
	 * @var BB_Social_Login_Settings The settings instance associated with the SSO provider.
	 */
	public $settings;

	/**
	 * Provider ID.
	 *
	 * @since 2.6.30
	 *
	 * @var string The unique identifier for the SSO provider.
	 */
	protected $id;

	/**
	 * Provider label.
	 *
	 * @since 2.6.30
	 *
	 * @var string The label for the SSO provider displayed in the admin interface.
	 */
	protected $label;

	/**
	 * Provider path.
	 *
	 * @since 2.6.30
	 *
	 * @var string The path to the SSO provider's resources.
	 */
	protected $path;

	/**
	 * Color.
	 *
	 * @since 2.6.30
	 *
	 * @var string The color associated with the SSO provider, typically for UI display.
	 */
	protected $color = '#fff';

	/**
	 * Auth popup width.
	 *
	 * @since 2.6.30
	 *
	 * @var int The width of the popup window used for the SSO provider authentication.
	 */
	protected $popup_width = 600;

	/**
	 * Auth popup height.
	 *
	 * @since 2.6.30
	 *
	 * @var int The height of the popup window used for the SSO provider authentication.
	 */
	protected $popup_height = 600;

	/**
	 * Admin instance.
	 *
	 * @since 2.6.30
	 *
	 * @var BB_SSO_Provider_Admin|null The admin instance associated with this provider.
	 */
	protected $admin = null;

	/**
	 * SVG icon.
	 *
	 * @since 2.6.30
	 *
	 * @var string The SVG icon for the SSO provider.
	 */
	protected $svg = null;

	/**
	 * Get the unique identifier of the SSO provider.
	 *
	 * @since 2.6.30
	 *
	 * @return string The provider ID.
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get the label of the SSO provider.
	 *
	 * @since 2.6.30
	 *
	 * @return string The provider label.
	 */
	public function get_label() {
		return $this->label;
	}

	/**
	 * Enable the SSO provider.
	 *
	 * @since 2.6.30
	 *
	 * @return bool False by default. Override in subclasses for specific behavior.
	 */
	public function enable() {
		return false;
	}

	/**
	 * Check if the SSO provider is enabled.
	 *
	 * @since 2.6.30
	 *
	 * @return bool False by default. Override in subclasses for specific behavior.
	 */
	public function is_enabled() {
		return false;
	}

	/**
	 * Check if the SSO provider has been tested.
	 *
	 * @since 2.6.30
	 *
	 * @return bool False by default. Override in subclasses for specific behavior.
	 */
	public function is_tested() {
		return false;
	}

	/**
	 * Check if the SSO provider is in test mode.
	 *
	 * @since 2.6.30
	 *
	 * @return bool False by default. Override in subclasses for specific behavior.
	 */
	public function is_test() {
		return false;
	}

	/**
	 * Connect to the SSO provider.
	 *
	 * @since 2.6.30
	 *
	 * @return void
	 */
	public function connect() {
		// Override in subclasses for specific behavior.
	}

	/**
	 * Get the current state of the SSO provider.
	 *
	 * @since 2.6.30
	 *
	 * @return string The current state of the provider.
	 */
	public function get_state() {
		return 'pro-only';
	}

	/**
	 * Get the icon URL for the SSO provider.
	 *
	 * @since 2.6.30
	 *
	 * @return string The URL of the provider icon.
	 */
	public function get_icon() {
		return bb_sso_url() . 'providers/' . $this->id . '/' . $this->id . '.png';
	}

	/**
	 * Get the color associated with the SSO provider.
	 *
	 * @since 2.6.30
	 *
	 * @return string The color of the provider.
	 */
	public function get_color() {
		return $this->color;
	}

	/**
	 * Get the width of the popup window.
	 *
	 * @since 2.6.30
	 *
	 * @return int The width of the popup.
	 */
	public function get_popup_width() {
		return $this->popup_width;
	}

	/**
	 * Get the height of the popup window.
	 *
	 * @since 2.6.30
	 *
	 * @return int The height of the popup.
	 */
	public function get_popup_height() {
		return $this->popup_height;
	}

	/**
	 * Get the path to the SSO provider's resources.
	 *
	 * @since 2.6.30
	 *
	 * @return mixed The path to the provider's resources.
	 */
	public function get_path() {
		return $this->path;
	}

	/**
	 * Get the admin instance associated with this SSO provider.
	 *
	 * @since 2.6.30
	 *
	 * @return BB_SSO_Provider_Admin The admin instance.
	 */
	public function get_admin() {
		return $this->admin;
	}

	/**
	 * SSO link label.
	 *
	 * @since 2.6.30
	 *
	 * @return string The label for the SSO link.
	 */
	public function bb_sso_link_label() {
		return apply_filters( 'bb_sso_' . $this->id . '_link_label', esc_html__( 'Connect', 'buddyboss-pro' ) );
	}

	/**
	 * SSO unlink label.
	 *
	 * @since 2.6.30
	 *
	 * @return string The label for the SSO link.
	 */
	public function bb_sso_unlink_label() {
		return apply_filters( 'bb_sso_' . $this->id . '_unlink_label', esc_html__( 'Remove', 'buddyboss-pro' ) );
	}

	/**
	 * Get the SVG icon for the SSO provider.
	 *
	 * @since 2.6.30
	 *
	 * @return string The URL of the provider icon.
	 */
	public function bb_sso_get_svg() {
		return apply_filters( 'bb_sso_' . $this->id . '_get_svg', $this->svg );
	}
}
