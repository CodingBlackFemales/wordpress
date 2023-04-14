<?php
/**
 * Platform Groups Functions.
 *
 * @package BuddyBossPro/Platform Groups
 * @since 1.2.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Insert default value to new pro fields.
add_action( 'bbp_pro_update_to_1_2_0', 'bb_groups_pro_update_to_1_2_0' );

// Reset group cover position when change the cover sizes.
add_action( 'update_option_bb-pro-cover-group-width', 'bb_reset_group_cover_position_on_change_sizes', 10, 3 );
add_action( 'update_option_bb-pro-cover-group-height', 'bb_reset_group_cover_position_on_change_sizes', 10, 3 );

add_filter( 'bp_rest_platform_settings', 'bb_rest_group_platform_settings', 10, 1 );

/**
 * Add new pro fields value insert into database.
 *
 * @since 1.2.0
 */
function bb_groups_pro_update_to_1_2_0() {
	$group_grid_style   = 'left';
	$group_header_style = 'left';
	$group_elements     = array( 'cover-images', 'avatars', 'group-privacy', 'group-type', 'last-activity', 'members', 'group-descriptions', 'join-buttons' );
	$header_elements    = array( 'group-type', 'group-activity', 'group-description', 'group-organizers', 'group-privacy' );

	bp_update_option( 'bb-pro-group-directory-layout-grid-style', $group_grid_style );
	bp_update_option( 'bb-pro-group-directory-layout-elements', $group_elements );
	bp_update_option( 'bb-pro-group-single-page-header-style', $group_header_style );
	bp_update_option( 'bb-pro-group-single-page-headers-elements', $header_elements );
}

/**
 * Reset Cover image for Social Groups when changes backend setting(width and height).
 *
 * @since 1.2.0
 *
 * @param mixed  $old_value The old option value.
 * @param mixed  $value     The new option value.
 * @param string $option    Option name.
 */
function bb_reset_group_cover_position_on_change_sizes( $old_value, $value, $option ) {
	if ( bbp_pro_is_license_valid() && ( 'bb-pro-cover-group-width' === $option || 'bb-pro-cover-group-height' === $option ) ) {
		$all_groups = groups_get_groups(
			array(
				'fields'      => 'ids',
				'per_page'    => -1,
				'orderby'     => 'last_activity',
				'meta_query'  => array(
					array(
						'key'     => 'bp_cover_position',
						'compare' => 'EXISTS',
					),
				),
				'show_hidden' => true,
			)
		);

		if ( ! empty( $all_groups ) && ! empty( $all_groups['groups'] ) ) {
			foreach ( $all_groups['groups'] as $group_id ) {
				groups_delete_groupmeta( $group_id, 'bp_cover_position' );
			}
		}
	}
}

/**
 * Adding the group settings into api.
 *
 * @since 2.2.6
 *
 * @param array $settings Array settings.
 *
 * @return mixed
 */
function bb_rest_group_platform_settings( $settings ) {
	if ( ! function_exists( 'bp_is_active' ) || ! bp_is_active( 'groups' ) ) {
		return $settings;
	}

	$settings['group_cover_width']     = bb_get_group_cover_image_width();
	$settings['group_cover_height']    = bb_get_group_cover_image_height();
	$settings['group_header_style']    = bb_platform_pro_group_header_style();
	$settings['group_header_elements'] = bp_get_option( 'bb-pro-group-single-page-headers-elements', array( 'group-type', 'group-activity', 'group-description', 'group-organizers', 'group-privacy' ) );

	return $settings;
}
