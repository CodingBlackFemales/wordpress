<?php
/**
 * BuddyBoss Profiles Settings.
 *
 * @package BuddyBossPro/PlatformSettings/Profiles
 *
 * @since 1.2.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup the bb profile settings class.
 *
 * @since 1.2.0
 */
class BB_Pro_Profiles_Settings {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.2.0
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Profile Settings Constructor.
	 *
	 * @since 1.2.0
	 */
	public function __construct() {

		// Include the code.
		$this->setup_actions();
	}

	/**
	 * Get the instance of this class.
	 *
	 * @since 1.2.0
	 *
	 * @return object Instance.
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
		}

		return self::$instance;
	}

	/**
	 * Setup actions for Profile Settings.
	 *
	 * @since 1.2.0
	 */
	public function setup_actions() {
		// Registered profile cover image width.
		add_filter( 'bb_admin_setting_field_bb-cover-profile-width', array( $this, 'bb_admin_register_profile_cover_image_width_field' ) );
		// Registered profile cover image height.
		add_filter( 'bb_admin_setting_field_bb-cover-profile-height', array( $this, 'bb_admin_register_profile_cover_image_height_field' ) );

		// Registered profile header style.
		add_filter( 'bb_admin_setting_field_bb-profile-headers-layout-style', array( $this, 'bb_admin_register_profile_header_style_field' ) );
		// Registered profile header elements.
		add_filter( 'bb_admin_setting_field_bb-profile-headers-layout-elements-online-status', array( $this, 'bb_admin_register_profile_header_elements_field' ) );
		add_filter( 'bb_admin_setting_field_bb-profile-headers-layout-elements-profile-type', array( $this, 'bb_admin_register_profile_header_elements_field' ) );
		add_filter( 'bb_admin_setting_field_bb-profile-headers-layout-elements-member-handle', array( $this, 'bb_admin_register_profile_header_elements_field' ) );
		add_filter( 'bb_admin_setting_field_bb-profile-headers-layout-elements-joined-date', array( $this, 'bb_admin_register_profile_header_elements_field' ) );
		add_filter( 'bb_admin_setting_field_bb-profile-headers-layout-elements-last-active', array( $this, 'bb_admin_register_profile_header_elements_field' ) );
		add_filter( 'bb_admin_setting_field_bb-profile-headers-layout-elements-followers', array( $this, 'bb_admin_register_profile_header_elements_field' ) );
		add_filter( 'bb_admin_setting_field_bb-profile-headers-layout-elements-following', array( $this, 'bb_admin_register_profile_header_elements_field' ) );
		add_filter( 'bb_admin_setting_field_bb-profile-headers-layout-elements-social-networks', array( $this, 'bb_admin_register_profile_header_elements_field' ) );

		// Registered member directories elements.
		add_filter( 'bb_admin_setting_field_bb-member-directory-element-online-status', array( $this, 'bb_admin_register_member_directory_elements_field' ) );
		add_filter( 'bb_admin_setting_field_bb-member-directory-element-online', array( $this, 'bb_admin_register_member_directory_elements_field' ) );
		add_filter( 'bb_admin_setting_field_bb-member-directory-element-profile-type', array( $this, 'bb_admin_register_member_directory_elements_field' ) );
		add_filter( 'bb_admin_setting_field_bb-member-directory-element-followers', array( $this, 'bb_admin_register_member_directory_elements_field' ) );
		add_filter( 'bb_admin_setting_field_bb-member-directory-element-last-active', array( $this, 'bb_admin_register_member_directory_elements_field' ) );
		add_filter( 'bb_admin_setting_field_bb-member-directory-element-joined-date', array( $this, 'bb_admin_register_member_directory_elements_field' ) );

		// Registered member directories profile actions.
		add_filter( 'bb_admin_setting_field_bb-member-profile-action-follow', array( $this, 'bb_admin_register_member_directory_profile_actions_field' ) );
		add_filter( 'bb_admin_setting_field_bb-member-profile-action-connect', array( $this, 'bb_admin_register_member_directory_profile_actions_field' ) );
		add_filter( 'bb_admin_setting_field_bb-member-profile-action-message', array( $this, 'bb_admin_register_member_directory_profile_actions_field' ) );

		// Registered member directories primary action.
		add_filter( 'bb_admin_setting_field_bb-member-profile-primary-action', array( $this, 'bb_admin_register_member_directory_primary_action_field' ) );

		// Save settings.
		add_action( 'bp_admin_tab_setting_save', array( $this, 'bb_admin_registered_profile_setting_fields_save' ), 10, 1 );
	}

	/**
	 * Create field attributes array of profile cover image width field.
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Current field attribute array.
	 *
	 * @return array Field attributes array of profile cover image width.
	 */
	public function bb_admin_register_profile_cover_image_width_field( $args ) {
		$args['name']     = 'bb-pro-cover-profile-width';
		$args['disabled'] = false;

		return $args;
	}

	/**
	 * Create field attributes array of profile cover image height field.
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Current field attribute array.
	 *
	 * @return array Field attributes array of profile cover image height.
	 */
	public function bb_admin_register_profile_cover_image_height_field( $args ) {
		$args['name']     = 'bb-pro-cover-profile-height';
		$args['disabled'] = false;

		return $args;
	}

	/**
	 * Create field attributes array of profile header style field.
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Current field attribute array.
	 *
	 * @return array Field attributes array of profile cover image height.
	 */
	public function bb_admin_register_profile_header_style_field( $args ) {
		$args['name']     = 'bb-pro-profile-headers-layout-style';
		$args['disabled'] = false;
		$args['value']    = bb_platform_pro_profile_headers_style();

		return $args;
	}

	/**
	 * Create field attributes array of profile header elements field.
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Current field attribute array.
	 *
	 * @return array Field attributes array of profile cover image height.
	 */
	public function bb_admin_register_profile_header_elements_field( $args ) {
		$args['name']     = 'bb-pro-profile-headers-layout-elements[]';
		$args['disabled'] = false;
		$args['selected'] = bb_platform_pro_profile_header_element_enable( $args['value'] ) ? $args['value'] : '';

		return $args;
	}

	/**
	 * Create field attributes array of member directories elements field.
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Current field attribute array.
	 *
	 * @return array Field attributes array of member directories elements.
	 */
	public function bb_admin_register_member_directory_elements_field( $args ) {
		$args['name']     = 'bb-pro-member-directory-elements[]';
		$args['disabled'] = false;

		return $args;
	}

	/**
	 * Create field attributes array of member directories profile actions field.
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Current field attribute array.
	 *
	 * @return array Field attributes array of member directories profile actions.
	 */
	public function bb_admin_register_member_directory_profile_actions_field( $args ) {
		$args['name']     = 'bb-pro-member-profile-actions[]';
		$args['disabled'] = false;

		return $args;
	}

	/**
	 * Create field attributes array of member directories primary action field.
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Current field attribute array.
	 *
	 * @return array Field attributes array of member directories primary action.
	 */
	public function bb_admin_register_member_directory_primary_action_field( $args ) {
		$args['name']     = 'bb-pro-member-profile-primary-action';
		$args['disabled'] = false;

		return $args;
	}

	/**
	 * Save registered settings to DB.
	 *
	 * @since 1.2.0
	 *
	 * @param string $current_tab Current setting tab.
	 */
	public function bb_admin_registered_profile_setting_fields_save( $current_tab ) {

		if ( 'bp-xprofile' === $current_tab ) {

			// Profile cover sizes default options.
			$profile_cover_width  = 'default';
			$profile_cover_height = 'small';

			// Group style default options.
			$profile_headers_style    = 'left';
			$profile_headers_elements = array( 'online-status', 'profile-type', 'member-handle', 'joined-date', 'last-active', 'followers', 'following', 'social-networks' );

			// Member directories default options.
			$member_directory_elements        = array( 'online-status', 'profile-type', 'followers', 'last-active', 'joined-date' );
			$member_directory_profile_actions = array( 'follow', 'connect', 'message' );
			$member_directory_primary_action  = '';

			if ( bbp_pro_is_license_valid() ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$profile_cover_width = isset( $_POST['bb-pro-cover-profile-width'] ) ? sanitize_text_field( wp_unslash( $_POST['bb-pro-cover-profile-width'] ) ) : 'default';
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$profile_cover_height = isset( $_POST['bb-pro-cover-profile-height'] ) ? sanitize_text_field( wp_unslash( $_POST['bb-pro-cover-profile-height'] ) ) : 'small';

				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$profile_headers_style = isset( $_POST['bb-pro-profile-headers-layout-style'] ) ? sanitize_text_field( wp_unslash( $_POST['bb-pro-profile-headers-layout-style'] ) ) : 'left';

				$profile_headers_elements = array();
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( isset( $_POST['bb-pro-profile-headers-layout-elements'] ) ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
					$profile_headers_elements = is_array( $_POST['bb-pro-profile-headers-layout-elements'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['bb-pro-profile-headers-layout-elements'] ) ) : sanitize_text_field( wp_unslash( $_POST['bb-pro-profile-headers-layout-elements'] ) );
				}

				$member_directory_elements = array();
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( isset( $_POST['bb-pro-member-directory-elements'] ) ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
					$member_directory_elements = is_array( $_POST['bb-pro-member-directory-elements'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['bb-pro-member-directory-elements'] ) ) : sanitize_text_field( wp_unslash( $_POST['bb-pro-member-directory-elements'] ) );
				}

				$member_directory_profile_actions = array();
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( isset( $_POST['bb-pro-member-profile-actions'] ) ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
					$member_directory_profile_actions = is_array( $_POST['bb-pro-member-profile-actions'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['bb-pro-member-profile-actions'] ) ) : sanitize_text_field( wp_unslash( $_POST['bb-pro-member-profile-actions'] ) );
				}
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$member_directory_primary_action = isset( $_POST['bb-pro-member-profile-primary-action'] ) ? sanitize_text_field( wp_unslash( $_POST['bb-pro-member-profile-primary-action'] ) ) : '';
			}

			bp_update_option( 'bb-pro-cover-profile-width', $profile_cover_width );
			bp_update_option( 'bb-pro-cover-profile-height', $profile_cover_height );

			bp_update_option( 'bb-pro-profile-headers-layout-style', $profile_headers_style );
			bp_update_option( 'bb-pro-profile-headers-layout-elements', $profile_headers_elements );

			bp_update_option( 'bb-pro-member-directory-elements', $member_directory_elements );
			bp_update_option( 'bb-pro-member-profile-actions', $member_directory_profile_actions );
			bp_update_option( 'bb-pro-member-profile-primary-action', $member_directory_primary_action );
		}

	}
}
