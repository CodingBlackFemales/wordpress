<?php
/**
 * Activity Topic filters.
 *
 * @since   2.7.40
 * @package BuddyBossPro
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'bp_rest_platform_settings', 'bb_rest_group_topic_settings', 10, 1 );

/**
 * Add group topic settings to the REST API settings.
 *
 * @since 2.7.40
 *
 * @param array $settings The existing settings array.
 *
 * @return array The modified settings array.
 */
function bb_rest_group_topic_settings( $settings ) {
	if ( ! bp_is_active( 'groups' ) ) {
		return $settings;
	}

	$settings['bb_enable_group_topics'] = function_exists( 'bb_is_enabled_activity_topics' ) && bb_is_enabled_activity_topics() && function_exists( 'bb_is_enabled_group_activity_topics' ) ? bb_is_enabled_group_activity_topics() : false;
	if ( $settings['bb_enable_group_topics'] ) {
		$settings['bb_group_topics_options'] = function_exists( 'bb_get_group_activity_topic_options' ) ? bb_get_group_activity_topic_options() : '';
	}

	return $settings;
}
