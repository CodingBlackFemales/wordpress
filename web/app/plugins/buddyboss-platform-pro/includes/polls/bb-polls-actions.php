<?php
/**
 * Poll Action.
 *
 * @since   2.6.00
 * @package BuddyBossPro
 */

add_action( 'bb_whats_new_toolbar_after', 'bb_polls_whats_new_toolbar' );
add_action( 'update_option__bb_enable_activity_post_polls', 'bb_polls_create_table_when_setting_enable', 10, 3 );
add_action( 'wp_ajax_bb_pro_add_poll', 'bb_pro_add_poll' );
add_action( 'bp_activity_posted_update', 'bb_poll_update_activity_poll_meta', 10, 3 );
add_action( 'bp_groups_posted_update', 'bb_poll_update_group_activity_poll_meta', 10, 4 );
add_action( 'bp_activity_after_delete', 'bb_poll_delete' );
add_action( 'bp_activity_entry_content', 'bb_poll_activity_entry' );
add_action( 'wp_ajax_bb_pro_add_poll_option', 'bb_pro_add_poll_option' );
add_action( 'wp_ajax_bb_pro_remove_poll_option', 'bb_pro_remove_poll_option' );
add_action( 'wp_ajax_bb_pro_add_poll_vote', 'bb_pro_add_poll_vote' );
add_action( 'wp_ajax_bb_pro_poll_vote_state', 'bb_pro_poll_vote_state' );
add_action( 'wp_ajax_bb_pro_remove_poll', 'bb_pro_remove_poll' );

/**
 * Add Poll button to toolbar.
 *
 * @since 2.6.00
 *
 * @return void
 */
function bb_polls_whats_new_toolbar() {
	?>
	<div class="post-elements-buttons-item post-poll bb-post-poll-button <# if ( ! data.can_create_poll_activity ) { #> bp-hide <# } #>">
		<a href="#" id="activity-poll-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Add Poll', 'buddyboss-pro' ); ?>" aria-label="<?php esc_attr_e( 'Add Poll', 'buddyboss-pro' ); ?>">
			<i class="bb-icon-l bb-icon-poll"></i>
		</a>
	</div>
	<?php
}

/**
 * Create table when poll setting enabled.
 *
 * @since 2.6.00
 *
 * @param mixed  $old_value The old option value.
 * @param mixed  $value     The new option value.
 * @param string $option    Option name.
 *
 * @return void
 */
function bb_polls_create_table_when_setting_enable( $old_value = '', $value = '', $option = '' ) {
	if ( '_bb_enable_activity_post_polls' !== $option ) {
		return;
	}

	if ( 1 === (int) $value ) {
		BB_Polls::instance()->create_table();
	}
}

/**
 * Function will add poll data.
 *
 * @since 2.6.00
 * @return void
 */
function bb_pro_add_poll() {
	if ( ! bp_is_post_request() ) {
		wp_send_json_error();
	}

	// Nonce check!
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'bb_add_poll_nonce' ) ) {
		wp_send_json_error();
	}

	// Check if the user is logged in.
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( __( 'You must be logged in to create a poll.', 'buddyboss-pro' ) );
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	$poll_id = isset( $_POST['poll_id'] ) ? filter_var( $_POST['poll_id'], FILTER_VALIDATE_INT ) : 0;

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	$group_id = isset( $_POST['group_id'] ) ? filter_var( $_POST['group_id'], FILTER_VALIDATE_INT ) : 0;
	$args     = array();
	if ( ! empty( $group_id ) ) {
		$args['object']   = 'group';
		$args['group_id'] = $group_id;
	}

	// Check if user can create polls.
	if ( empty( $poll_id ) && ! bb_can_user_create_poll_activity( $args ) ) {
		wp_send_json_error( __( 'You do not have permission to create polls.', 'buddyboss-pro' ) );
	}

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	$poll_question = isset( $_POST['questions'] ) ? sanitize_text_field( wp_unslash( wp_strip_all_tags( $_POST['questions'] ) ) ) : '';

	// Check if the poll question is set.
	if ( empty( $poll_question ) ) {
		wp_send_json_error( __( 'Poll question is required.', 'buddyboss-pro' ) );
	}

	// Check if the poll options are set.
	$poll_options = $_POST['options'] ?? array(); // phpcs:ignore

	$sanitized_poll_options = array();
	$flat_options           = array();
	foreach ( $poll_options as $key => $option ) {
		foreach ( $option as $option_id => $value ) {
			$value = sanitize_text_field( wp_unslash( wp_strip_all_tags( $value ) ) );
			// Flatten the array for validation checks.
			$flat_options[ $key ] = $value;

			// Sanitize the poll options.
			$sanitized_poll_options[ $key ] = array(
				'id'           => $option_id,
				'option_title' => $value,
			);

			// Check the length of each option.
			if ( strlen( $value ) > 50 ) {
				wp_send_json_error( __( 'Poll option must be between 1 and 50 characters long.', 'buddyboss-pro' ) );
			}
		}
	}

	// Check if the poll option is empty.
	if ( in_array( '', $flat_options, true ) ) {
		wp_send_json_error( __( 'Poll options are required.', 'buddyboss-pro' ) );
	} elseif ( ! is_array( $flat_options ) ) {
		wp_send_json_error( __( 'Poll options must be an array.', 'buddyboss-pro' ) );
	} elseif ( count( $flat_options ) < 2 ) {
		wp_send_json_error( __( 'Poll options must be at least 2.', 'buddyboss-pro' ) );
	} elseif ( count( $flat_options ) > 10 ) {
		wp_send_json_error( __( 'Poll options must be at most 10.', 'buddyboss-pro' ) );
	} elseif ( count( $flat_options ) !== count( array_unique( $flat_options ) ) ) {
		wp_send_json_error( __( 'Poll options must be unique.', 'buddyboss-pro' ) );
	}

	$allow_multiple_answer = isset( $_POST['allow_multiple_answer'] ) ? filter_var( $_POST['allow_multiple_answer'], FILTER_VALIDATE_BOOL ) : ''; // phpcs:ignore
	$allow_new_option      = isset( $_POST['allow_new_option'] ) ? filter_var( $_POST['allow_new_option'], FILTER_VALIDATE_BOOL ) : false; // phpcs:ignore
	$duration              = isset( $_POST['duration'] ) ? filter_var( $_POST['duration'], FILTER_VALIDATE_INT ) : 3; // phpcs:ignore
	if ( ! in_array( $duration, array( 1, 3, 7, 14 ), true ) ) {
		wp_send_json_error( __( 'Invalid poll duration.', 'buddyboss-pro' ) );
	}

	$poll_all_settings = array(
		'question' => $poll_question,
		'settings' => array(
			'allow_multiple_options' => $allow_multiple_answer,
			'allow_new_option'       => $allow_new_option,
			'duration'               => $duration,
		),
	);

	if ( ! empty( $poll_id ) ) {
		$activity_id = isset( $_POST['activity_id'] ) ? filter_var( $_POST['activity_id'], FILTER_VALIDATE_INT ) : 0;  // phpcs:ignore
		if ( ! empty( $activity_id ) ) {
			$activity_poll_id = bb_poll_get_activity_meta_poll_id( $activity_id );
			if ( $activity_poll_id === (int) $poll_id ) {
				$activity                    = new BP_Activity_Activity( $activity_id );
				$activity_status             = $activity->status;
				$poll_all_settings['status'] = $activity_status;
			}

			// Update votes for a poll if the setting is disabled to disallow multiple options.
			bb_update_votes_after_disable_allow_multiple_options(
				array(
					'poll_id'                => $poll_id,
					'allow_multiple_options' => $allow_multiple_answer,
				)
			);
		}
		$poll_all_settings['id']      = (int) $poll_id;
		$poll_all_settings['user_id'] = false;
	}

	$poll_data = bb_load_polls()->bb_update_poll( $poll_all_settings );
	if ( ! empty( $poll_data ) ) {
		$fetch_poll_data = array();
		if ( ! empty( $sanitized_poll_options ) ) {
			$poll_options_data['poll_id'] = $poll_data->id;
			if ( '0000-00-00 00:00:00' !== $poll_data->vote_disabled_date ) {
				$vote_disabled_date = strtotime( $poll_data->vote_disabled_date );
			} else {
				$duration           = bb_poll_get_duration( $poll_data );
				$vote_disabled_date = intval( bp_core_current_time( true, 'timestamp' ) ) + ( intval( $duration ) * DAY_IN_SECONDS );                         // Calculate the future timestamp.
			}
			$poll_data->vote_disabled_date = $vote_disabled_date;

			// Remove deleted options.
			$option_ids            = array_column( $sanitized_poll_options, 'id' );
			$existing_poll_options = bb_load_polls()->bb_get_poll_options(
				array(
					'poll_id' => $poll_data->id,
					'fields'  => 'id',
				)
			);
			if ( ! empty( $existing_poll_options ) ) {
				$diff_option_ids = array_diff( $existing_poll_options, $option_ids );
				if ( ! empty( $diff_option_ids ) ) {
					foreach ( $diff_option_ids as $option_id ) {
						bb_load_polls()->bb_remove_poll_options(
							array(
								'id'      => $option_id,
								'poll_id' => $poll_data->id,
							)
						);
					}
				}
			}

			// Add/Update options.
			foreach ( $sanitized_poll_options as $key => $option ) {
				$poll_options_data['id']              = $option['id'];
				$poll_options_data['option_title']    = $option['option_title'];
				$poll_options_data['option_order']    = $key;
				$poll_options_data['user_id']         = ! empty( $poll_id ) ? $poll_data->user_id : bp_loggedin_user_id();
				$updated_poll_option                  = bb_load_polls()->bb_update_poll_option( $poll_options_data );
				$current_poll_option                  = ! empty( $updated_poll_option[0] ) ? current( $updated_poll_option ) : array();
				$fetch_poll_data[ $key ]              = $current_poll_option;
				$fetch_poll_data[ $key ]['user_data'] = array(
					'username'    => bp_core_get_user_displayname( $current_poll_option['user_id'] ),
					'user_domain' => bp_core_get_user_domain( $current_poll_option['user_id'] ),
				);
			}
		}

		if ( ! empty( $fetch_poll_data ) ) {
			$poll_data->options = $fetch_poll_data;
		}

		$poll_data->total_votes = ! empty( $poll_id ) ? bb_load_polls()->bb_get_poll_option_vote_count(
			array(
				'poll_id' => $poll_id,
			)
		) : 0;

		wp_send_json_success( $poll_data );
	} else {
		wp_send_json_error( __( 'Error creating poll.', 'buddyboss-pro' ) );
	}

	unset( $poll_question, $poll_options, $sanitized_poll_options, $flat_options, $poll_all_settings, $poll_data, $fetch_poll_data, $poll_options_data );
}

/**
 * Update activity poll meta.
 *
 * @since 2.6.00
 *
 * @param string $content     Activity content.
 * @param int    $user_id     User ID.
 * @param int    $activity_id Activity ID.
 *
 * @return false|void
 */
function bb_poll_update_activity_poll_meta( $content, $user_id, $activity_id ) {
	global $bp_activity_edit;

	if ( empty( $activity_id ) ) {
		return false;
	}

	$activity = new BP_Activity_Activity( $activity_id );

	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$poll_id = isset( $_POST['poll_id'] ) ? sanitize_text_field( wp_unslash( $_POST['poll_id'] ) ) : '';
	if ( empty( $poll_id ) ) {
		return false;
	}

	$get_poll = bb_load_polls()->bb_get_poll( $poll_id );

	if ( empty( $get_poll ) ) {
		return false;
	}

	$activity_poll_duration = bb_poll_get_duration( $get_poll );
	$activity_recorded      = strtotime( $activity->date_recorded );                                                                         // Get the current timestamp in UTC.
	$future_timestamp       = intval( $activity_recorded ) + ( intval( $activity_poll_duration ) * DAY_IN_SECONDS );                         // Calculate the future timestamp.
	$translated_date        = gmdate( 'Y-m-d H:i:s', $future_timestamp );

	$args = array(
		'id'                 => $poll_id,
		'item_id'            => $activity_id,
		'item_type'          => 'activity',
		'vote_disabled_date' => $translated_date,
		'status'             => $activity->status,
	);
	if ( isset( $activity->component ) && 'groups' === $activity->component ) {
		$args['secondary_item_id'] = $activity->item_id;
	}
	if ( $bp_activity_edit ) {
		$args['user_id'] = $get_poll->user_id;
	}
	bb_load_polls()->bb_update_poll( $args );

	// update poll id here in the activity meta.
	bp_activity_update_meta( $activity_id, 'bb_poll_id', $poll_id );

	unset( $activity, $poll_id, $get_poll, $activity_poll_duration, $activity_recorded, $future_timestamp, $translated_date, $args );
}

/**
 * Update group activity poll meta.
 *
 * @since 2.6.00
 *
 * @param string $content     Activity content.
 * @param int    $user_id     User ID.
 * @param int    $group_id    Group ID.
 * @param int    $activity_id Activity ID.
 *
 * @return void
 */
function bb_poll_update_group_activity_poll_meta( $content, $user_id, $group_id, $activity_id ) {
	bb_poll_update_activity_poll_meta( $content, $user_id, $activity_id );
}

/**
 * Delete a poll when delete activity.
 *
 * @since 2.6.00
 *
 * @param array $activities Array of activities.
 *
 * @return void
 */
function bb_poll_delete( $activities ) {
	if ( empty( $activities ) ) {
		return;
	}

	$activity_ids = array_column( $activities, 'id' );
	if ( empty( $activity_ids ) ) {
		return;
	}

	foreach ( $activity_ids as $activity_id ) {
		$poll_id = bb_poll_get_activity_meta_poll_id( $activity_id );

		if ( ! empty( $poll_id ) ) {
			$deleted = bb_load_polls()->bb_remove_poll( $poll_id );
			if ( $deleted ) {
				bp_activity_delete_meta( $activity_id, 'bb_poll_id' );
			}
			unset( $deleted );
		}
		unset( $poll_id );
	}
	unset( $activity_ids );
}

/**
 * Function to display a poll in activity entry.
 *
 * @since 2.6.00
 * @since 2.7.70
 * Update: Load template for poll view.
 *
 * @return false|void
 */
function bb_poll_activity_entry() {
	bp_get_template_part( 'activity/bb-activity-poll-view' );
}

/**
 * Function to add a poll option.
 *
 * @since 2.6.00
 * @return void
 */
function bb_pro_add_poll_option() {
	if ( ! bp_is_post_request() ) {
		wp_send_json_error();
	}

	// Nonce check!
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'bb_add_poll_option_nonce' ) ) {
		wp_send_json_error();
	}

	// Check if the user is logged in.
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( __( 'You must be logged in to create a option.', 'buddyboss-pro' ) );
	}

	$activity_id = isset( $_POST['activity_id'] ) ? filter_var( $_POST['activity_id'], FILTER_VALIDATE_INT ) : 0; // phpcs:ignore
	if ( empty( $activity_id ) ) {
		wp_send_json_error( __( 'You can not add new option to this activity.', 'buddyboss-pro' ) );
	}

	$poll_id  = isset( $_POST['poll_id'] ) ? filter_var( $_POST['poll_id'], FILTER_VALIDATE_INT ) : 0; // phpcs:ignore
	$get_poll = bb_load_polls()->bb_get_poll( (int) $poll_id );

	$get_poll->vote_disabled_date = strtotime( $get_poll->vote_disabled_date );

	// Check if user can create option.
	if ( ! empty( $get_poll->secondary_item_id ) && ! bb_is_enabled_activity_post_polls( false ) ) {
		wp_send_json_error( __( 'You do not have permission to create option.', 'buddyboss-pro' ) );
	}

	$get_allow_new_option = bb_poll_allow_new_options( $get_poll );
	if ( ! $get_allow_new_option ) {
		wp_send_json_error( __( 'You can not add new option to this poll.', 'buddyboss-pro' ) );
	}

	$new_poll_option = isset( $_POST['poll_option'] ) ? sanitize_text_field( wp_unslash( trim( $_POST['poll_option'] ) ) ) : ''; // phpcs:ignore
	if ( empty( $new_poll_option ) ) {
		wp_send_json_error( __( 'Poll option is required.', 'buddyboss-pro' ) );
	}

	if ( strlen( $new_poll_option ) > 50 ) {
		wp_send_json_error( __( 'Poll option must be between 1 and 50 characters long.', 'buddyboss-pro' ) );
	}

	$poll_options  = bb_load_polls()->bb_get_poll_options(
		array(
			'poll_id' => $poll_id,
		)
	);
	$total_options = ! empty( $poll_options ) ? count( $poll_options ) : 0;
	if ( ! empty( $total_options ) && $total_options >= 10 ) {
		wp_send_json_error( __( 'The new option exceeds the allowed limit of 10 options.', 'buddyboss-pro' ) );
	}

	$last_option  = ! empty( $poll_options ) ? end( $poll_options ) : 0;
	$option_order = ! empty( $last_option ) && ! empty( $last_option['option_order'] ) ? ++ $last_option['option_order'] : 0;

	$updated_poll_option = bb_load_polls()->bb_update_poll_option(
		array(
			'poll_id'      => $poll_id,
			'option_title' => $new_poll_option,
			'option_order' => $option_order,
			'user_id'      => bp_loggedin_user_id(),
		)
	);

	if ( empty( $updated_poll_option ) ) {
		wp_send_json_error( __( 'Error while saving option data.', 'buddyboss-pro' ) );
	}

	$updated_all_poll_options = bb_load_polls()->bb_get_poll_options(
		array(
			'poll_id' => $poll_id,
		)
	);
	$total_options            = ! empty( $updated_all_poll_options ) ? count( $updated_all_poll_options ) : 0;

	$total_votes = bb_load_polls()->bb_get_poll_option_vote_count(
		array(
			'poll_id' => $poll_id,
		)
	);

	$response = array(
		'option_data'   => ! empty( $updated_poll_option ) ? current( $updated_poll_option ) : array(),
		'poll'          => $get_poll,
		'all_options'   => $updated_all_poll_options,
		'total_options' => $total_options,
		'total_votes'   => $total_votes,
	);

	unset( $poll_options, $last_option, $updated_poll_option, $activity_data, $total_options );

	wp_send_json_success( $response );
}

/**
 * Function to remove a poll option.
 *
 * @since 2.6.00
 *
 * @return void
 */
function bb_pro_remove_poll_option() {
	if ( ! bp_is_post_request() ) {
		wp_send_json_error();
	}

	// Nonce check!
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'bb_remove_poll_option_nonce' ) ) {
		wp_send_json_error();
	}

	// Check if the user is logged in.
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( __( 'You must be logged in to delete a option.', 'buddyboss-pro' ) );
	}

	$activity_id = isset( $_POST['activity_id'] ) ? filter_var( $_POST['activity_id'], FILTER_VALIDATE_INT ) : 0; // phpcs:ignore
	if ( empty( $activity_id ) ) {
		wp_send_json_error( __( 'Activity ID is required to remove this poll option.', 'buddyboss-pro' ) );
	}

	$poll_id = isset( $_POST['poll_id'] ) ? filter_var( $_POST['poll_id'], FILTER_VALIDATE_INT ) : 0; // phpcs:ignore
	if ( empty( $poll_id ) ) {
		wp_send_json_error( __( 'Poll ID is required to remove this poll option.', 'buddyboss-pro' ) );
	}

	$get_poll = bb_load_polls()->bb_get_poll( (int) $poll_id );
	if ( empty( $get_poll ) ) {
		wp_send_json_error( __( 'Poll not found.', 'buddyboss-pro' ) );
	}
	$get_poll->vote_disabled_date = strtotime( $get_poll->vote_disabled_date );

	// Check if user can remove option.
	if ( ! empty( $get_poll->secondary_item_id ) && ! bb_is_enabled_activity_post_polls( false ) ) {
		wp_send_json_error( __( 'You do not have permission to remove option.', 'buddyboss-pro' ) );
	}

	$option_id = isset( $_POST['option_id'] ) ? filter_var( $_POST['option_id'], FILTER_VALIDATE_INT ) : 0; // phpcs:ignore
	if ( empty( $option_id ) ) {
		wp_send_json_error( __( 'Option ID is required to remove this poll option.', 'buddyboss-pro' ) );
	}

	$poll_options = bb_load_polls()->bb_get_poll_options(
		array(
			'poll_id' => $poll_id,
		)
	);
	if ( empty( $poll_options ) ) {
		wp_send_json_error( __( 'Poll options empty.', 'buddyboss-pro' ) );
	}

	$deleted_item = null;
	$poll_options = array_filter(
		$poll_options,
		function ( $item ) use ( $option_id, &$deleted_item ) {
			if ( (int) $item['id'] === (int) $option_id ) {
				$deleted_item = $item;

				return false; // Filter out this item
			}

			return true; // Keep other items
		}
	);

	if ( ! empty( $deleted_item ) ) {
		$fetch_option_data = $deleted_item;
		$deleted           = bb_load_polls()->bb_remove_poll_options(
			array(
				'id'      => $fetch_option_data['id'],
				'poll_id' => $fetch_option_data['poll_id'],
			)
		);

		if ( $deleted ) {
			$total_options = ! empty( $poll_options ) ? count( $poll_options ) : 0;

			$total_votes = bb_load_polls()->bb_get_poll_option_vote_count(
				array(
					'poll_id' => $poll_id,
				)
			);

			$response = array(
				'poll'          => $get_poll,
				'all_options'   => $poll_options,
				'option_data'   => $fetch_option_data,
				'total_options' => $total_options,
				'total_votes'   => $total_votes,
			);
			unset( $get_poll, $poll_options, $result, $fetch_option_data, $activity_data, $total_options );
			wp_send_json_success( $response );
		}
	} else {
		wp_send_json_error( __( 'Option not exists in the poll', 'buddyboss-pro' ) );
	}
}

/**
 * Function to add a poll vote.
 *
 * @since 2.6.00
 *
 * @return void
 */
function bb_pro_add_poll_vote() {
	if ( ! bp_is_post_request() ) {
		wp_send_json_error();
	}

	// Nonce check!
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'bb_add_poll_vote_nonce' ) ) {
		wp_send_json_error();
	}

	// Check if the user is logged in.
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( __( 'You must be logged in to vote.', 'buddyboss-pro' ) );
	}

	$activity_id = isset( $_POST['activity_id'] ) ? filter_var( $_POST['activity_id'], FILTER_VALIDATE_INT ) : 0; // phpcs:ignore
	if ( empty( $activity_id ) ) {
		wp_send_json_error( __( 'Activity ID is required to vote this poll.', 'buddyboss-pro' ) );
	}

	$poll_id = isset( $_POST['poll_id'] ) ? filter_var( $_POST['poll_id'], FILTER_VALIDATE_INT ) : 0; // phpcs:ignore
	if ( empty( $poll_id ) ) {
		wp_send_json_error( __( 'Poll ID is required to vote this poll.', 'buddyboss-pro' ) );
	}

	$get_poll = bb_load_polls()->bb_get_poll( (int) $poll_id );
	if ( empty( $get_poll ) ) {
		wp_send_json_error( __( 'Poll not found.', 'buddyboss-pro' ) );
	}
	$get_poll->vote_disabled_date = strtotime( $get_poll->vote_disabled_date );

	// Check if user can add a vote.
	if ( ! empty( $get_poll->secondary_item_id ) && ! bb_is_enabled_activity_post_polls( false ) ) {
		wp_send_json_error( __( 'You do not have permission to add vote.', 'buddyboss-pro' ) );
	}

	$option_id = isset( $_POST['option_id'] ) ? filter_var( $_POST['option_id'], FILTER_VALIDATE_INT ) : 0; // phpcs:ignore
	if ( empty( $option_id ) ) {
		wp_send_json_error( __( 'Option ID is required to vote this poll.', 'buddyboss-pro' ) );
	}

	$updated_poll_vote = bb_load_polls()->update_poll_votes(
		array(
			'poll_id'   => $poll_id,
			'option_id' => $option_id,
			'user_id'   => bp_loggedin_user_id(),
		)
	);
	if ( empty( $updated_poll_vote ) ) {
		wp_send_json_error( __( 'Error while saving option data.', 'buddyboss-pro' ) );
	}
	$current_updated_vote = ! empty( $updated_poll_vote ) && ! empty( $updated_poll_vote['poll_votes'][0] ) ? current( $updated_poll_vote['poll_votes'] ) : array();

	$total_votes = bb_load_polls()->bb_get_poll_option_vote_count(
		array(
			'poll_id' => $poll_id,
		)
	);

	$poll_options = bb_load_polls()->bb_get_poll_options(
		array(
			'poll_id' => $poll_id,
		)
	);

	$response = array(
		'poll'        => $get_poll,
		'all_options' => $poll_options,
		'vote_data'   => $current_updated_vote,
		'total_votes' => $total_votes,
	);
	unset( $get_poll, $poll_options, $current_updated_vote, $updated_poll_vote, $total_votes );

	wp_send_json_success( $response );
}

/**
 * Function to display vote state for poll option.
 *
 * @since 2.6.00
 *
 * @return void
 */
function bb_pro_poll_vote_state() {
	if ( ! bp_is_post_request() ) {
		wp_send_json_error();
	}

	// Nonce check!
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'bb_poll_vote_state_nonce' ) ) {
		wp_send_json_error();
	}

	// Check if the user is logged in.
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( __( 'You must be logged in to see vote state.', 'buddyboss-pro' ) );
	}

	$activity_id = isset( $_POST['activity_id'] ) ? filter_var( $_POST['activity_id'], FILTER_VALIDATE_INT ) : 0; // phpcs:ignore
	if ( empty( $activity_id ) ) {
		wp_send_json_error( __( 'Activity ID is required to see vote state.', 'buddyboss-pro' ) );
	}

	$poll_id = isset( $_POST['poll_id'] ) ? filter_var( $_POST['poll_id'], FILTER_VALIDATE_INT ) : 0; // phpcs:ignore
	if ( empty( $poll_id ) ) {
		wp_send_json_error( __( 'Poll ID is required to see vote state.', 'buddyboss-pro' ) );
	}

	$option_id = isset( $_POST['option_id'] ) ? filter_var( $_POST['option_id'], FILTER_VALIDATE_INT ) : 0; // phpcs:ignore
	if ( empty( $option_id ) ) {
		wp_send_json_error( __( 'Option ID is required to see vote state.', 'buddyboss-pro' ) );
	}

	$paged    = ! empty( $_POST['paged'] ) ? (int) sanitize_text_field( $_POST['paged'] ) : 1; // phpcs:ignore
	$per_page = 20; // Fixed per page.

	$get_poll_vote = bb_load_polls()->bb_get_poll_votes(
		array(
			'poll_id'     => $poll_id,
			'option_id'   => $option_id,
			'paged'       => $paged,
			'fields'      => 'user_id',
			'count_total' => true,
		)
	);

	$total_votes = bb_load_polls()->bb_get_poll_option_vote_count(
		array(
			'poll_id' => $poll_id,
		)
	);

	$all_option_votes   = ! empty( $get_poll_vote['poll_votes'] ) ? $get_poll_vote['poll_votes'] : array();
	$total_option_votes = ! empty( $get_poll_vote['total'] ) ? (int) $get_poll_vote['total'] : array();
	$total_pages        = ceil( $total_option_votes / $per_page );

	$state_html = '';
	if ( ! empty( $all_option_votes ) && 1 === $paged ) {
		$state_html = sprintf(
		/* translators: %1$s: Total votes, %2$s: Percentage */
			__( '%1$s (%2$s%%)', 'buddyboss-pro' ),
			/* translators: %s: Total votes */
			esc_html( sprintf( _n( '%s vote', '%s votes', $total_option_votes, 'buddyboss-pro' ), $total_option_votes ) ),
			esc_html( round( ( $total_option_votes / $total_votes ) * 100, 2 ) )
		);
	}

	$members_data = array();
	if ( ! empty( $all_option_votes ) ) {
		foreach ( $all_option_votes as $user_id ) {
			$type        = function_exists( 'bp_get_member_type_object' ) ? bp_get_member_type( $user_id ) : '';
			$type_obj    = function_exists( 'bp_get_member_type_object' ) && ! empty( $type ) ? bp_get_member_type_object( $type ) : '';
			$color_data  = function_exists( 'bb_get_member_type_label_colors' ) && ! empty( $type ) ? bb_get_member_type_label_colors( $type ) : '';
			$member_type = '';

			// Check if the user has a member type and member type is not hidden.
			if (
				! empty( $type_obj ) &&
				function_exists( 'bp_get_xprofile_member_type_field_id' ) &&
				function_exists( 'bp_xprofile_get_hidden_fields_for_user' ) &&
				! in_array( bp_get_xprofile_member_type_field_id(), bp_xprofile_get_hidden_fields_for_user( $user_id ), true )
			) {
				$member_type = $type_obj->labels['singular_name'];
			}

			$members_data[ $user_id ]['user_name']   = bp_core_get_user_displayname( $user_id );
			$members_data[ $user_id ]['user_avatar'] = bp_core_fetch_avatar(
				array(
					'item_id' => $user_id,
					'object'  => 'user',
					'type'    => 'thumb',
					'html'    => false,
				)
			);
			$members_data[ $user_id ]['user_link']   = bp_core_get_userlink( $user_id, false, true );
			$members_data[ $user_id ]['member_type'] = array(
				'label' => $member_type ?? $type,
				'color' => array(
					'background' => ! empty( $color_data['background-color'] ) ? $color_data['background-color'] : '',
					'text'       => ! empty( $color_data['color'] ) ? $color_data['color'] : '',
				),
			);

			// Get followers count.
			if ( bp_is_active( 'follow' ) ) {
				$followers = bp_get_follower_ids( array( 'user_id' => $user_id ) );
			} elseif ( function_exists( 'bp_get_followers' ) ) {
				$followers = bp_get_followers( array( 'user_id' => $user_id ) );
			}
			$followers_count = 0;
			if ( ! empty( $followers ) ) {
				$followers_count = sprintf(
					/* translators: %d: follower count */
					_n( '%d follower', '%d followers', count( $followers ), 'buddyboss-pro' ),
					count( $followers )
				);
			}
			$members_data[ $user_id ]['followers_count'] = $followers_count;
		}
	}

	$response = array(
		'members' => $members_data,
		'others'  => array(
			'paged'              => $paged,
			'per_page'           => $per_page,
			'total_pages'        => $total_pages,
			'total_option_votes' => $total_option_votes,
			'total_votes'        => $total_votes,
			'stats_html'         => $state_html,
		),
	);

	unset( $get_poll_vote, $vote_results, $all_option_votes, $members_data );

	wp_send_json_success( $response );
}

/**
 * Function to remove a poll
 *
 * @since 2.6.00
 *
 * @return void
 */
function bb_pro_remove_poll() {
	if ( ! bp_is_post_request() ) {
		wp_send_json_error();
	}

	// Nonce check!
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'bb_remove_poll_nonce' ) ) {
		wp_send_json_error();
	}

	// Check if the user is logged in.
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( __( 'You must be logged in to delete a poll.', 'buddyboss-pro' ) );
	}

	$poll_id = isset( $_POST['poll_id'] ) ? filter_var( $_POST['poll_id'], FILTER_VALIDATE_INT ) : 0; // phpcs:ignore
	if ( empty( $poll_id ) ) {
		wp_send_json_error( __( 'Poll ID is required to remove this poll.', 'buddyboss-pro' ) );
	}

	$get_poll = bb_load_polls()->bb_get_poll( (int) $poll_id );
	if ( empty( $get_poll ) ) {
		wp_send_json_error( __( 'Poll not found.', 'buddyboss-pro' ) );
	}

	// Check if user can remove a vote.
	if ( ! empty( $get_poll->secondary_item_id ) && ! bb_is_enabled_activity_post_polls( false ) ) {
		wp_send_json_error( __( 'You do not have permission to remove vote.', 'buddyboss-pro' ) );
	}

	$activity_id = isset( $_POST['activity_id'] ) ? filter_var( $_POST['activity_id'], FILTER_VALIDATE_INT ) : 0; // phpcs:ignore

	if ( ! empty( $activity_id ) ) {
		$activity_poll_id = bb_poll_get_activity_meta_poll_id( $activity_id );
		if ( $activity_poll_id !== $poll_id ) {
			wp_send_json_error( __( 'Incorrect Poll ID.', 'buddyboss-pro' ) );
		}
	}

	$deleted = bb_load_polls()->bb_remove_poll( $poll_id );
	if ( $deleted ) {
		if ( ! empty( $activity_id ) ) {
			bp_activity_delete_meta( $activity_id, 'bb_poll_id' );
		}
		wp_send_json_success();
	} else {
		wp_send_json_error( __( 'Error while deleting poll.', 'buddyboss-pro' ) );
	}

	unset( $poll_id, $activity_id, $activity_poll_id, $deleted );
}
