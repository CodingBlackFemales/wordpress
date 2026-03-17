<?php
/**
 * Schedule Posts Actions.
 *
 * @since   2.5.20
 *
 * @package BuddyBossPro
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


add_action( 'bp_nouveau_enqueue_scripts', 'bb_schedule_post_enqueue_scripts' );
add_filter( 'sanitize_option__bb_enable_activity_schedule_posts', 'bb_schedule_posts_prevent_settings_update_when_locked', 10, 3 );

/**
 * Enqueue the scripts for schedule posts.
 *
 * @since 2.5.20
 *
 * @return void
 */
function bb_schedule_post_enqueue_scripts() {
	if (
		(
			! bp_is_activity_component() &&
			! bp_is_group_activity()
		) ||
		! bb_is_enabled_activity_schedule_posts()
	) {
		return;
	}

	if ( bp_nouveau_current_user_can( 'publish_activity' ) ) {
		wp_enqueue_style( 'jquery-datetimepicker' );
		wp_enqueue_script( 'jquery-datetimepicker' );
	}

}

/**
 * Prevent schedule posts settings from being updated when features are locked.
 *
 * @since 2.11.0
 *
 * @param mixed  $value          The new, unserialized option value.
 * @param string $option         The option name.
 * @param mixed  $original_value The original option value.
 *
 * @return mixed The option value (unchanged if locked, otherwise the new value).
 */
function bb_schedule_posts_prevent_settings_update_when_locked( $value, $option, $original_value ) {
	// If features are locked, return the old value to prevent changes.
	if ( function_exists( 'bb_pro_should_lock_features' ) && bb_pro_should_lock_features() ) {
		return $original_value;
	}

	return $value;
}
