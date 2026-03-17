<?php
/**
 * Poll filters.
 *
 * @package BuddyBossPro
 *
 * @since   2.6.00
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'bbp_pro_core_install', 'bbp_pro_core_install_poll_tables' );
add_filter( 'bp_core_get_js_strings', 'bb_polls_localize_scripts', 11 );
add_filter( 'bp_activity_get_edit_data', 'bb_poll_get_edit_activity_data' );
add_filter( 'sanitize_option__bb_enable_activity_post_polls', 'bb_poll_prevent_settings_update_when_locked', 10, 3 );

/**
 * Function to add poll tables when new install.
 *
 * @since 2.6.00
 *
 * @return void
 */
function bbp_pro_core_install_poll_tables() {
	if ( function_exists( 'bp_is_active' ) && bp_is_active( 'activity' ) ) {
		BB_Polls::instance()->create_table();
	}
}

/**
 * Localize the strings needed for the Polls.
 *
 * @since 2.6.00
 *
 * @param array $params Associative array containing the js strings needed by scripts.
 *
 * @return array The same array with specific strings for the Polls if needed.
 */
function bb_polls_localize_scripts( $params ) {

	$activity_params = array(
		'can_create_poll_activity' => bb_can_user_create_poll_activity(),
	);

	$activity_poll_strings = array(
		'DeletePollConfirm' => esc_html__( 'Are you sure you would like to delete this poll?', 'buddyboss-pro' ),
		'addedByYou'        => esc_html__( 'Added by you', 'buddyboss-pro' ),
		'areYouSure'        => esc_html__( 'Are you sure?', 'buddyboss-pro' ),
		'closePopupConfirm' => esc_html__( 'Any options you have chosen will be removed', 'buddyboss-pro' ),
	);

	if ( ! empty( $params['activity_polls']['params'] ) ) {
		$params['activity_polls']['params'] = array_merge( $params['activity_polls']['params'], $activity_params );
	} else {
		$params['activity_polls']['params'] = $activity_params;
	}

	if ( ! empty( $params['activity_polls']['strings'] ) ) {
		$params['activity_polls']['strings'] = array_merge( $params['activity_polls']['strings'], $activity_poll_strings );
	} else {
		$params['activity_polls']['strings'] = $activity_poll_strings;
	}

	unset( $activity_params, $activity_poll_strings );

	return $params;
}

/**
 * Adds activity poll data for the edit activity
 *
 * @param array $activity Activity data.
 *
 * @return array $activity Returns the activity with poll if a poll saved otherwise no poll.
 *
 * @since [BBVERSION}
 */
function bb_poll_get_edit_activity_data( $activity ) {

	if ( ! empty( $activity['id'] ) ) {
		// Fetch a poll id of activity.
		$bb_poll_id = bb_poll_get_activity_meta_poll_id( $activity['id'] );
		if ( ! empty( $bb_poll_id ) ) {
			$get_poll = bb_load_polls()->bb_get_poll( $bb_poll_id );
			if ( ! empty( $get_poll ) ) {
				$edit_poll = false;

				$total_votes = bb_load_polls()->bb_get_poll_option_vote_count(
					array(
						'poll_id' => $bb_poll_id,
					)
				);

				$get_poll_options = bb_load_polls()->bb_get_poll_options(
					array(
						'poll_id'  => $bb_poll_id,
						'order_by' => 'option_order',
					)
				);

				$activity['poll'] = array(
					'id'                     => $bb_poll_id,
					'user_id'                => $get_poll->user_id,
					'vote_disabled_date'     => strtotime( $get_poll->vote_disabled_date ),
					'question'               => $get_poll->question,
					'options'                => $get_poll_options,
					'allow_multiple_options' => bb_poll_allow_multiple_options( $get_poll ),
					'allow_new_option'       => bb_poll_allow_new_options( $get_poll ),
					'duration'               => bb_poll_get_duration( $get_poll ),
					'total_votes'            => $total_votes,
					'item_id'                => $get_poll->item_id,
				);

				if ( 'activity' === $activity['object'] && bp_loggedin_user_id() === (int) $get_poll->user_id ) {
					$edit_poll = true;
				} elseif (
					bp_is_active( 'groups' ) &&
					'groups' === $activity['object'] &&
					bb_can_user_create_poll_activity(
						array(
							'user_id'  => bp_loggedin_user_id(),
							'object'   => 'group',
							'group_id' => $activity['item_id'],
						)
					)
				) {
					$edit_poll = true;
				}
				$activity['edit_poll'] = $edit_poll;
			}
		}
	}

	unset( $bb_poll_id, $get_poll, $vote_results, $get_poll_options, $edit_poll );

	return $activity;
}

/**
 * Prevent poll settings from being updated when features are locked.
 *
 * @since 2.11.0
 *
 * @param mixed  $value          The new, unserialized option value.
 * @param string $option         The option name.
 * @param mixed  $original_value The original option value.
 *
 * @return mixed The option value (unchanged if locked, otherwise the new value).
 */
function bb_poll_prevent_settings_update_when_locked( $value, $option, $original_value ) {
	// If features are locked, return the old value to prevent changes.
	if ( bb_pro_should_lock_features() ) {
		return $original_value;
	}

	return $value;
}
