<?php
/**
 * BuddyBoss Platform Pro Groups Functions.
 *
 * @package BuddyBossPro/Functions
 * @since 1.2.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


/**
 * Checks if default platform group headers element is enabled.
 *
 * @since 1.2.0
 *
 * @param string $element Group headers element.
 * @param bool   $default Optional. Fallback value if not found in the database.
 *                        Default: array with all elements.
 *
 * @return bool Is group headers element enabled or not
 */
function bb_platform_pro_group_headers_element_enable( $element, $default = array( 'group-type', 'group-activity', 'group-description', 'group-organizers', 'group-privacy' ) ) {

	$header_elements = bp_get_option( 'bb-pro-group-single-page-headers-elements', $default );

	/**
	 * Filters whether specified $element should be enabled or not.
	 *
	 * @since 1.2.0
	 *
	 * @param bool $value Whether or not the group element enable or not.
	 */
	return (bool) apply_filters( 'bb_platform_pro_group_headers_element_' . $element . '_enable', ! empty( $header_elements ) && in_array( $element, $header_elements, true ) );
}

/**
 * Get group header style setting.
 *
 * @since 1.2.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: left.
 *
 * @return string header style for group directory
 */
function bb_platform_pro_group_header_style( $default = 'left' ) {

	/**
	 * Filters whether specified header style for group diretory.
	 *
	 * @since 1.2.0
	 *
	 * @param bool $value Whether or not the group element enable or not.
	 */
	return apply_filters( 'bb_platform_group_header_style', bp_get_option( 'bb-pro-group-single-page-header-style', $default ) );
}

/**
 * Checks if default platform group element is enabled.
 *
 * @since 1.2.0
 *
 * @param string $element Group element.
 * @param bool   $default Optional. Fallback value if not found in the database.
 *                        Default: true.
 *
 * @return bool Is group element enabled or not
 */
function bb_platform_pro_group_element_enable( $element, $default = array( 'cover-images', 'avatars', 'group-privacy', 'group-type', 'last-activity', 'members', 'group-descriptions', 'join-buttons' ) ) {
	$group_elements = bp_get_option( 'bb-pro-group-directory-layout-elements', $default );

	/**
	 * Filters whether specified $element should be enabled or not.
	 *
	 * @since 1.2.0
	 *
	 * @param bool $value Whether or not the group element enable or not.
	 */
	return (bool) apply_filters( 'bb_pro_group_element_' . $element . '_enable', ! empty( $group_elements ) && in_array( $element, $group_elements, true ) );
}

/**
 * Get group grid style setting.
 *
 * @since 1.2.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: left.
 *
 * @return string grid style for group directory
 */
function bb_platform_pro_group_grid_style( $default = 'left' ) {

	/**
	 * Filters whether specified grid style for group diretory.
	 *
	 * @since 1.2.0
	 *
	 * @param bool $value Whether or not the group element enable or not.
	 */
	return apply_filters( 'bb_pro_group_grid_style', bp_get_option( 'bb-pro-group-directory-layout-grid-style', $default ) );
}
