<?php
/**
 * The template for displaying activity poll view.
 *
 * @since 2.7.70
 *
 * @package BuddyBossPro
 *
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $activities_template;

$activity = '';
if ( isset( $activities_template->activity ) ) {
	$activity = $activities_template->activity;
}

if ( empty( $activity ) ) {
	return false;
}

// Get activity metas.
$bb_poll_id = bb_poll_get_activity_meta_poll_id( $activity->id );

if ( empty( $bb_poll_id ) ) {
	return false;
}

$get_poll = bb_load_polls()->bb_get_poll( $bb_poll_id );

if ( empty( $get_poll ) ) {
	return false;
}

// Check if the user can see the poll.
if ( ! empty( $get_poll->secondary_item_id ) && ! bb_is_enabled_activity_post_polls( false ) ) {
	return false;
}

$get_poll_options = bb_load_polls()->bb_get_poll_options(
	array(
		'poll_id'  => $bb_poll_id,
		'order_by' => 'option_order',
	)
);

$total_votes = bb_load_polls()->bb_get_poll_option_vote_count(
	array(
		'poll_id' => $bb_poll_id,
	)
);

$poll_end_date_timestamp = isset( $get_poll->vote_disabled_date ) ? strtotime( $get_poll->vote_disabled_date ) : '';
$current_timestamp       = bp_core_current_time( true, 'timestamp' );
$poll_closed             = $poll_end_date_timestamp < $current_timestamp;
?>
<div id="bb-poll-view" class="bb-poll-view">
	<div class="bb-activity-poll_block">
		<div class="bb-activity-poll_header">
			<h3><?php echo esc_html( $get_poll->question ); ?></h3>
		</div>

		<div class="bb-activity-poll_content">
			<div class="bb-activity-poll-options">
				<?php
				if ( ! empty( $get_poll_options ) ) {
					$index = 0;
					foreach ( $get_poll_options as $key => $value ) {
						$more_class = '';
						if ( $index > 4 ) {
							$more_class = 'bb-activity-poll-option-hide';
						}

						$option_percentage = 0;
						if ( ! empty( $value['total_votes'] ) ) {
							$option_percentage = ! empty( $total_votes ) ? round( ( $value['total_votes'] / $total_votes ) * 100, 2 ) : 0;
						}

						$get_poll_vote         = bb_load_polls()->bb_get_poll_votes(
							array(
								'poll_id'   => $bb_poll_id,
								'option_id' => $value['id'],
								'user_id'   => bp_loggedin_user_id(),
								'fields'    => 'option_id',
							)
						);
						$poll_voted_option_ids = ! empty( $get_poll_vote['poll_votes'] ) ? array_map( 'intval', $get_poll_vote['poll_votes'] ) : array();
						?>
						<div class="bb-activity-poll-option <?php echo esc_attr( $more_class ); ?>">
							<?php
							$style = '';
							if ( ! empty( $option_percentage ) ) {
								$style = "style=width:{$option_percentage}%;";
							}
							?>
							<div class="bb-poll-option-fill" <?php echo wp_kses_post( $style ); ?>></div>
							<?php
							$field_name = 'radio';
							if ( bb_poll_allow_multiple_options( $get_poll ) ) {
								$field_name = 'checkbox';
							}
							?>
							<div class="bp-<?php echo esc_attr( $field_name ); ?>-wrap bb-option-field-wrap">
								<?php
								if ( ! $poll_closed ) {
									if ( (int) $value['user_id'] !== (int) $get_poll->user_id ) {
										if ( bp_loggedin_user_id() === (int) $value['user_id'] ) {
											?>
											<span class="bb-activity-poll-option-note"><?php esc_html_e( 'Added by you', 'buddyboss-pro' ); ?></span>
											<?php
										} else {
											$user_name = bp_core_get_user_displayname( $value['user_id'] );
											?>
											<span class="bb-activity-poll-option-note">
												<?php
												printf(
												/* translators: %s: User link */
													__( 'Added by %s', 'buddyboss-pro' ),
													sprintf(
														'<a href="%s" target="_blank">%s</a>',
														esc_url( trailingslashit( bp_core_get_user_domain( $value['user_id'] ) ) ),
														esc_html( $user_name )
													)
												);
												?>
											</span>
											<?php
										}
									}
									if ( is_user_logged_in() ) {
										?>
										<input type="<?php echo esc_attr( $field_name ); ?>"
											class="bs-styled-<?php echo esc_attr( $field_name ); ?> bb-option-input-wrap"
											id="bb-activity-poll-option-<?php echo esc_attr( $bb_poll_id . $key ); ?>"
											name="bb-activity-poll-option-<?php echo esc_attr( $bb_poll_id ); ?>"
											value="<?php echo ! empty( $value['option_title'] ) ? esc_html( $value['option_title'] ) : ''; ?>"
											data-opt_id="<?php echo esc_attr( ! empty( $value['id'] ) ? $value['id'] : $key ); ?>"
											<?php echo in_array( (int) $value['id'], $poll_voted_option_ids, true ) ? 'checked="checked"' : ''; ?>/>
										<?php
									}
								}
								?>
								<label for="bb-activity-poll-option-<?php echo esc_attr( $bb_poll_id . $key ); ?>"><span><?php echo esc_html( $value['option_title'] ); ?></span></label>
							</div>
							<div class="bb-poll-right">
								<span class="bb-poll-option-state">
									<?php echo esc_html( ! empty( $option_percentage ) ? $option_percentage : 0 ); ?>%
								</span>
								<?php
								if ( ! empty( $value['total_votes'] ) ) {
									?>
									<span class="bb-poll-option-state-votes">
										<?php echo '(' . esc_html( $value['total_votes'] ) . ')'; ?>
									</span>
									<?php
								}
								?>
								<a href="#" class="<?php echo ! empty( $option_percentage ) ? esc_attr( 'bb-poll-option-view-state' ) : esc_attr( 'bb-poll-no-vote' ); ?>" data-opt_id="<?php echo ! empty( $value['id'] ) ? esc_html( $value['id'] ) : ''; ?>" aria-label="<?php esc_attr_e( 'View State', 'buddyboss-pro' ); ?>"><i class="bb-icon-angle-right"></i></a>
							</div>
							<?php
							if (
								! $poll_closed &&
								(int) $value['user_id'] !== (int) $get_poll->user_id &&
								bp_loggedin_user_id() === (int) $value['user_id']
							) {
								?>
								<a href="#" class="bb-poll-option_remove" role="button" aria-label="<?php esc_html_e( 'Remove Option', 'buddyboss-pro' ); ?>"><span class="bb-icon-l bb-icon-times"></span></a>
								<?php
							}
							?>
						</div>
						<?php
						++$index;
					}
				}
				$show_hide_new_option = 'bb-activity-poll-option-hide';
				if (
					is_user_logged_in() &&
					! $poll_closed &&
					bb_poll_allow_new_options( $get_poll ) &&
					is_array( $get_poll_options ) &&
					count( $get_poll_options ) < 10
				) {
					$show_hide_new_option = '';
				}
				?>
				<div class="bb-activity-poll-option bb-activity-poll-new-option <?php echo esc_attr( $show_hide_new_option ); ?>">
					<span class="bb-icon-f bb-icon-plus"></span>
					<input type="text" class="bb-activity-poll-new-option-input" placeholder="<?php esc_html_e( 'Add Option', 'buddyboss-pro' ); ?>" maxlength="50"/>
					<a href="#" class="bb-activity-option-submit">
						<span class="bb-icon-f bb-icon-plus"></span>
						<span class="screen-reader-text"><?php esc_html_e( 'Submit option', 'buddyboss-pro' ); ?></span>
					</a>
				</div>
				<div class="bb-poll-error duplicate-error"><?php esc_html_e( 'This is already an option', 'buddyboss-pro' ); ?></div>
				<div class="bb-poll-error limit-error"></div>
				<?php

				if ( count( $get_poll_options ) > 5 ) {
					?>
					<div class="bb-activity-poll-see-more">
						<a href="#" class="bb-activity-poll-see-more-link" role="button">
							<span class="bb-poll-see-more-text"><?php esc_html_e( 'See All', 'buddyboss-pro' ); ?></span>
							<span class="bb-poll-see-less-text"><?php esc_html_e( 'See Less', 'buddyboss-pro' ); ?></span>
						</a>
					</div>
					<?php
				}
				?>

				<div class="bb-activity-poll-footer">
					<span class="bb-activity-poll_votes">
						<?php
						echo 1 === intval( $total_votes ) ? sprintf( esc_html__( '%s vote', 'buddyboss-pro' ), $total_votes ) : sprintf( esc_html__( '%s votes', 'buddyboss-pro' ), $total_votes );
						?>
					</span>
					<span class="bb-activity-poll_duration">
						<?php
						if ( $poll_closed ) {
							esc_html_e( 'Poll Closed', 'buddyboss-pro' );
						} else {
							// Calculate the difference in seconds.
							$difference_in_seconds = $poll_end_date_timestamp - $current_timestamp;

							// Calculate the number of days, hours, minutes, and seconds left.
							$days_left    = floor( $difference_in_seconds / DAY_IN_SECONDS );
							$hours_left   = floor( ( $difference_in_seconds % DAY_IN_SECONDS ) / HOUR_IN_SECONDS );
							$minutes_left = floor( ( $difference_in_seconds % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS );
							$seconds_left = $difference_in_seconds % MINUTE_IN_SECONDS;

							if ( $days_left >= 1 ) {
								if ( 0 == $hours_left && 0 == $minutes_left ) {
									// Display only days if it's exactly an integer number of days.
									printf( esc_html__( '%sd left', 'buddyboss-pro' ), esc_html( $days_left ) );
								} else {
									// Display days, hours, and minutes if it's not an exact integer number of days.
									printf( esc_html__( '%1$sd %2$sh %3$sm left', 'buddyboss-pro' ), esc_html( $days_left ), esc_html( $hours_left ), esc_html( $minutes_left ) );
								}
							} elseif ( $hours_left > 0 ) {
								// Display hours and minutes if no full days are left
								printf( esc_html__( '%1$sh %2$sm left', 'buddyboss-pro' ), esc_html( $hours_left ), esc_html( $minutes_left ) );
							} elseif ( $minutes_left > 0 ) {
								// Display minutes if less than an hour is left
								printf( esc_html__( '%sm left', 'buddyboss-pro' ), esc_html( $minutes_left ) );
							} else {
								// Display seconds if less than a minute is left
								printf( esc_html__( '%ss left', 'buddyboss-pro' ), esc_html( $seconds_left ) );
							}
						}
						?>
					</span>
				</div>
			</div>
		</div>
	</div>

	<div class="bb-action-popup" id="bb-activity-poll-state_modal" style="display: none;">
		<transition name="modal">
			<div class="modal-mask bb-white bbm-model-wrap">
				<div class="modal-wrapper">
					<div class="bb-activity-poll-state_overlay"></div>
					<div class="modal-container">
						<header class="bb-model-header">
							<h4></h4>
							<a class="bb-close-action-popup bb-model-close-button" href="#" aria-label="<?php esc_attr_e( 'Close', 'buddyboss-pro' ); ?>">
								<span class="bb-icon-l bb-icon-times"></span>
							</a>
						</header>
						<div class="bb-action-popup-content">
							<div class="bb-activity-poll-loader">
								<i class="bb-icon-l bb-icon-spinner animate-spin"></i>
							</div>
						</div>
					</div>
				</div>
			</div>
		</transition>
	</div><!-- #b-activity-poll-state_modal -->
</div>
<?php
unset( $get_poll_options, $vote_results, $total_votes, $poll_end_date_timestamp, $current_timestamp, $poll_closed, $bb_poll_id, $activity );
