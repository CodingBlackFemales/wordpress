<?php
/**
 * BuddyBoss Platform Pro Profiles Functions.
 *
 * @package BuddyBossPro/Functions
 * @since 1.2.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get profile header layout style setting.
 *
 * @since 1.2.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: left.
 *
 * @return string Profile headers layout style type.
 */
function bb_platform_pro_profile_headers_style( $default = 'left' ) {

	/**
	 * Filters whether specified profile header layout style.
	 *
	 * @since 1.2.0
	 *
	 * @param bool $value Profile header layout style type.
	 */
	return apply_filters( 'bb_pro_profile_headers_layout_style', bp_get_option( 'bb-pro-profile-headers-layout-style', $default ) );
}

/**
 * Checks if default platform profile header element is enabled.
 *
 * @since 1.2.0
 *
 * @param string     $element Profile header element.
 * @param array|null $default Optional. Fallback value if not found in the database.
 *                            Default: array with all elements.
 *
 * @return bool Is profile header element enabled or not
 */
function bb_platform_pro_profile_header_element_enable( $element, $default = array( 'online-status', 'profile-type', 'member-handle', 'joined-date', 'last-active', 'followers', 'following', 'social-networks' ) ) {

	$profile_header_elements = bp_get_option( 'bb-pro-profile-headers-layout-elements', $default );

	/**
	 * Filters whether specified $element should be enabled or not.
	 *
	 * @since 1.2.0
	 *
	 * @param bool $value Whether the profile header element enable or not.
	 */
	return (bool) apply_filters( 'bb_pro_profile_headers_element_' . $element . '_enable', ! empty( $profile_header_elements ) && in_array( $element, $profile_header_elements, true ) );
}

/**
 * Checks if member directory element is enabled or not.
 *
 * @since 1.2.0
 *
 * @param string     $element Member directory element.
 * @param array|null $default Optional. Fallback value if not found in the database.
 *                            Default: array with all elements.
 *
 * @return bool Is member directory element enabled or not.
 */
function bb_platform_pro_enable_member_directory_element( $element, $default = array( 'online-status', 'profile-type', 'followers', 'last-active', 'joined-date' ) ) {

	$member_directory_elements = bp_get_option( 'bb-pro-member-directory-elements', $default );

	/**
	 * Filters whether specified $element should be enabled or not.
	 *
	 * @since 1.2.0
	 *
	 * @param bool $value Whether the member directory element enable or not.
	 */
	return (bool) apply_filters( 'bb_pro_member_directory_element_' . $element . '_enable', ! empty( $member_directory_elements ) && in_array( $element, $member_directory_elements, true ) );
}

/**
 * Checks if member directory profile action is enabled or not.
 *
 * @since 1.2.0
 *
 * @param string     $action  Member directory profile action.
 * @param array|null $default Optional. Fallback value if not found in the database.
 *                            Default: array with all elements.
 *
 * @return bool Is member directory profile action enabled or not.
 */
function bb_platform_pro_enable_member_directory_profile_action( $action, $default = array( 'follow', 'connect', 'message' ) ) {

	$member_directory_profile_actions = bp_get_option( 'bb-pro-member-profile-actions', $default );

	/**
	 * Filters whether specified $action should be enabled or not.
	 *
	 * @since 1.2.0
	 *
	 * @param bool $value Whether the member directory profile action enable or not.
	 */
	return (bool) apply_filters( 'bb_pro_member_directory_profile_action_' . $action . '_enable', ! empty( $member_directory_profile_actions ) && in_array( $action, $member_directory_profile_actions, true ) );
}

/**
 * Get selected profile actions for member directory setting.
 *
 * @since 1.2.0
 *
 * @param array|null $default Optional. Fallback value if not found in the database.
 *                            Default: array with all elements.
 *
 * @return array Enabled profile actions for member directory.
 */
function bb_platform_pro_get_member_directory_profile_actions( $default = array( 'follow', 'connect', 'message' ) ) {
	/**
	 * Filters whether specified profile actions for member directory.
	 *
	 * @since 1.2.0
	 *
	 * @param string $value Whether the member directory profile actions setting.
	 */
	return apply_filters( 'bb_pro_get_member_directory_profile_actions', bp_get_option( 'bb-pro-member-profile-actions', $default ) );
}

/**
 * Get member directory primary action setting.
 *
 * @since 1.2.0
 *
 * @param string|null $default Optional. Fallback value if not found in the database.
 *                             Default: null.
 *
 * @return string Primary action for member directory.
 */
function bb_platform_pro_get_member_directory_primary_action( $default = '' ) {

	/**
	 * Filters whether specified primary action for member directory.
	 *
	 * @since 1.2.0
	 *
	 * @param string $value Whether the member directory primary action setting.
	 */
	return apply_filters( 'bb_pro_get_member_directory_primary_action', bp_get_option( 'bb-pro-member-profile-primary-action', $default ) );
}
