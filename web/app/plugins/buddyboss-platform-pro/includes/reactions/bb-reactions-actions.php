<?php
/**
 * Reaction Action.
 *
 * @since   2.4.50
 * @package BuddyBossPro
 */

// Actions.
add_action( 'bbp_pro_core_install', 'bb_pro_reaction_migration' );

// Load reaction background job class.
add_action( 'bp_init', 'bb_pro_init_reactions_background_process', 50 );
add_action( 'bb_pro_init_reactions_background_process', 'bb_pro_schedule_reactions_background_process' );

// Check the migration before save the settings.
add_action( 'bb_reaction_before_setting_save', 'bb_pro_reaction_check_data_before_save', 10, 1 );

// Register Ajax requests.
// Checks total count as per actions.
add_action( 'wp_ajax_bb_pro_reaction_footer_migration', 'bb_pro_reaction_footer_migration' );
// Check the data previously submitted when delete the emotion.
add_action( 'wp_ajax_bb_pro_reaction_check_delete_emotion', 'bb_pro_reaction_check_delete_emotion' );
// Do later migration.
add_action( 'wp_ajax_bb_pro_reaction_migration_start_conversion', 'bb_pro_reaction_migration_start_conversion' );
// Do later migration.
add_action( 'wp_ajax_bb_pro_reaction_migration_do_later', 'bb_pro_reaction_migration_do_later' );
// Dismiss site-wide notice.
add_action( 'wp_ajax_bb_pro_reaction_dismiss_migration_notice', 'bb_pro_reaction_dismiss_migration_notice' );
// Stop Migration from notice.
add_action( 'wp_ajax_bb_pro_reaction_migration_stop_conversion', 'bb_pro_reaction_migration_stop_conversion' );

// Add site-wide notice.
add_action( 'admin_notices', 'bb_pro_reaction_show_global_notice' );

/**
 * Return to add default reaction data.
 *
 * @since 2.4.50
 *
 * @return void
 */
function bb_pro_reaction_migration() {
	$all_emotions = bb_pro_get_reactions( 'emotions', false );

	if ( empty( $all_emotions ) ) {
		$reactions = bb_pro_get_reaction_default_data();

		if ( class_exists( 'BB_Reaction' ) ) {
			$bb_reaction = BB_Reaction::instance();
			foreach ( $reactions as $reaction ) {
				$bb_reaction->bb_add_reaction( $reaction );
			}

			$bb_reaction->bb_update_reactions_transient();
		}
	}
}

/**
 * Load reaction background class.
 *
 * @since 2.4.50
 *
 * @return void
 */
function bb_pro_init_reactions_background_process() {
	global $bb_reaction_background_process;

	if ( ! class_exists( 'BB_Reactions_Background_Process' ) ) {
		include_once bb_reaction_path( 'includes/class-bb-reactions-background-process.php' );
	}

	if ( class_exists( 'BB_Reactions_Background_Process' ) ) {
		$bb_reaction_background_process = new BB_Reactions_Background_Process();

		/**
		 * Fires inside the 'bb_pro_init_reactions_background_process' function, where BB updates data.
		 *
		 * @since 2.4.50
		 */
		do_action( 'bb_pro_init_reactions_background_process' );
	}
}

/**
 * Check and reschedule the newly added reactions background process if the queue is not empty.
 *
 * @since 2.4.50
 */
function bb_pro_schedule_reactions_background_process() {
	global $bb_reaction_background_process;

	if (
		is_object( $bb_reaction_background_process ) &&
		$bb_reaction_background_process->is_updating()
	) {
		$bb_reaction_background_process->schedule_event();
	}
}

/**
 * Validate migration before save the settings.
 *
 * @since 2.4.50
 *
 * @param string $current_tab Current tab slug.
 */
function bb_pro_reaction_check_data_before_save( $current_tab ) {
	if ( 'bp-reactions' !== $current_tab ) {
		return;
	}

	if ( 'inprogress' === bb_pro_reaction_get_migration_status() ) {
		bp_core_redirect( bp_core_admin_setting_url( $current_tab ) );
	}

	// New values.
	$new_reaction_mode = ! empty( $_POST['bb_reaction_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['bb_reaction_mode'] ) ) : '';

	if ( ! empty( $new_reaction_mode ) ) {

		// Old values.
		$old_reaction_mode = bb_get_reaction_mode();

		$current_action = '';
		if ( 'likes' === $old_reaction_mode && 'emotions' === $new_reaction_mode ) {
			$current_action   = 'like_to_emotions_action';
			$old_likes        = bb_pro_get_reactions();
			$likes            = ! empty( $old_likes ) ? current( $old_likes ) : array();
			$current_from_ids = $likes['id'];
		} elseif ( 'emotions' === $old_reaction_mode && 'likes' === $new_reaction_mode ) {
			$current_action   = 'emotions_to_like_action';
			$old_emotions     = bb_pro_get_reactions( 'emotions', false );
			$current_from_ids = implode( ',', array_filter( array_column( $old_emotions, 'id' ) ) );
		}

		if ( ! empty( $current_action ) && ! empty( $current_from_ids ) ) {

			// Reset migration.
			bb_pro_reaction_delete_migration();

			$reaction_count = bb_load_reaction()->bb_get_user_reactions_count(
				array(
					'reaction_id' => $current_from_ids
				)
			);

			if ( 0 < $reaction_count ) {
				// Set migration action.
				bb_pro_reaction_add_migration_action(
					array(
						'action'          => $current_action,
						'total_reactions' => $reaction_count
					)
				);
			}
		}
	}
}

/**
 * Check migration is available while update the settings.
 *
 * @since 2.4.50
 */
function bb_pro_reaction_footer_migration() {
	if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'bb-pro-check-footer-migration' ) ) {
		wp_send_json_error(
			array(
				'content' => '',
				'message' => esc_html__( 'Unable to submit this form, please refresh and try again.', 'buddyboss-pro' ),
			)
		);
	}

	if ( 'inprogress' === bb_pro_reaction_get_migration_status() ) {
		wp_send_json_error(
			array(
				'content' => '',
				'message' => esc_html__( 'This action can not be proceed while the conversion is in progress.', 'buddyboss-pro' ),
			)
		);
	}

	$content         = '';
	$wizard_sections = bb_pro_reaction_get_migration_wizard(
		array(
			'action_type' => 'footer_wizard',
		)
	);

	if ( ! empty( $wizard_sections['wizard_screen1'] ) ) {
		ob_start();
		?>
		<div class="bbpro_migration_wizard_screens bbpro_migration_wizard_1 active">
			<div class="bbpro-modal-box__body">
				<?php echo $wizard_sections['wizard_screen1']; ?>
			</div>
			<div class="bbpro-modal-box__footer">
				<button type="button" class="button-secondary cancel_migration_wizard"><?php echo __( 'Cancel', 'buddyboss-pro' ); ?></button>
				<button type="button" class="button-primary footer_next_wizard_screen disabled"><?php echo __( 'Continue', 'buddyboss-pro' ); ?></button>
			</div>
		</div>
		<?php
		$content .= ob_get_clean();
	}

	if ( ! empty( $wizard_sections['wizard_screen2'] ) ) {
		ob_start();
		?>
		<div class="bbpro_migration_wizard_screens bbpro_migration_wizard_2">
			<div class="bbpro-modal-box__body">
				<?php echo $wizard_sections['wizard_screen2']; ?>
			</div>
			<div class="bbpro-modal-box__footer">
				<button type="button" class="button-secondary cancel_migration_wizard"><?php echo __( 'Cancel', 'buddyboss-pro' ); ?></button>
				<button type="button" class="button-primary start_migration_wizard"><?php echo __( 'Start conversion', 'buddyboss-pro' ); ?></button>
			</div>
		</div>
		<?php
		$content .= ob_get_clean();
	}

	wp_send_json_success(
		array(
			'content' => $content,
			'message' => '',
		)
	);
}

/**
 * Check the emotion data exists while deleting it.
 *
 * @since 2.4.50
 */
function bb_pro_reaction_check_delete_emotion() {
	if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'bb-pro-check-delete-emotion' ) ) {
		wp_send_json_error(
			array(
				'status'  => 'error',
				'message' => __( 'Unable to processed this request, please refresh and try again.', 'buddyboss-pro' ),
			)
		);
	}

	if ( 'inprogress' === bb_pro_reaction_get_migration_status() ) {
		wp_send_json_error(
			array(
				'content' => '',
				'message' => esc_html__( 'This action can not be proceed while the conversion is in progress.', 'buddyboss-pro' ),
			)
		);
	}

	$emotion_id = (int) ! empty( $_POST['emotion_id'] ) ? sanitize_text_field( wp_unslash( $_POST['emotion_id'] ) ) : 0;

	if ( empty( $emotion_id ) ) {
		wp_send_json_error(
			array(
				'status'  => 'error',
				'message' => __( 'Emotion ID is required.', 'buddyboss-pro' ),
			)
		);
	}

	$emotion  = array();
	$emotions = bb_pro_get_reactions( 'emotions', false );
	if ( ! empty( $emotions ) ) {
		foreach ( $emotions as $item ) {

			if ( (int) $emotion_id === (int) $item['id'] ) {
				$emotion = $item;
				break;
			}
		}
	}

	if ( empty( $emotion ) ) {
		wp_send_json_error(
			array(
				'status'  => 'error',
				'message' => __( 'Provided emotion ID is not correct. please refresh and try again.', 'buddyboss-pro' ),
			)
		);
	}

	$emotion_count = bb_load_reaction()->bb_get_user_reactions_count(
		array(
			'reaction_id' => $emotion_id
		)
	);

	if ( 0 < $emotion_count ) {
		$modal_content = sprintf(
			'<p>%s</p>',
			sprintf(
			/* translators: 1: Emotion name, 2: Emotion count. */
				__( 'You are about to delete the %1$s Emotion, including all %2$s instances of members using this emotion as a reaction.', 'buddyboss-pro' ),
				'<b>' . $emotion['icon_text'] . '</b>',
				'<b>' . bp_core_number_format( $emotion_count ) . '</b>'
			)
		);

		$modal_content .= sprintf(
			'<p>%s</p>',
			__( 'If you want to retain this data, you can:', 'buddyboss-pro' )
		);

		$modal_content .= sprintf(
			'<ul><li>%1$s</li><li>%2$s</li></ul>',
			__( 'Edit or deactivate this Emotion instead of deleting it', 'buddyboss-pro' ),
			__( 'Use the migration wizard to convert this Emotion’s data to a different Emotion before deleting', 'buddyboss-pro' )
		);

		$modal_content .= sprintf(
			'<p>%s</p>',
			__( 'Otherwise, click the button below to proceed with deleting.', 'buddyboss-pro' )
		);

		$modal_content .= sprintf(
			'<p>%s</p>',
			sprintf(
				__( 'This action %s be undone.', 'buddyboss-pro' ),
				sprintf(
					'<b>%s</b>',
					__( 'cannot', 'buddyboss-pro' )
				)
			)
		);
	} else {
		$modal_content = sprintf(
			'<p>%s</p>',
			sprintf(
			/* translators: 1: Emotion name. */
				__( 'Are you sure you want to delete the %s Emotion?', 'buddyboss-pro' ),
				'<b>' . $emotion['icon_text'] . '</b>'
			)
		);

		$modal_content .= sprintf(
			'<p>%s</p>',
			sprintf(
				__( 'This action %s be undone.', 'buddyboss-pro' ),
				sprintf(
					'<b>%s</b>',
					__( 'cannot', 'buddyboss-pro' )
				)
			)
		);
	}

	$modal_content = sprintf( '<div class="bbpro-modal-box__body">%s</div>', $modal_content );

	$modal_content .= sprintf(
		'<div class="bbpro-modal-box__footer">
					<button class="button-secondary bb-pro-reaction-cancel-delete-emotion">%1$s</button>
					<button class="button-primary bb-pro-reaction-delete-emotion">%2$s</button>
				</div>',
		__( 'Cancel', 'buddyboss-pro' ),
		__( 'Confirm', 'buddyboss-pro' )
	);

	wp_send_json_success(
		array(
			'status'  => true,
			'content' => $modal_content,
		)
	);
}

/**
 * Build a pop-up for migration.
 *
 * @since 2.4.50
 */
function bb_pro_reaction_migration_start_conversion() {
	if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'bb-pro-reaction-migration-start-conversion' ) ) {
		wp_send_json_error(
			array(
				'content' => '',
				'message' => esc_html__( 'Unable to submit this form, please refresh and try again.', 'buddyboss-pro' ),
			)
		);
	}

	if ( 'inprogress' === bb_pro_reaction_get_migration_status() ) {
		wp_send_json_error(
			array(
				'content' => '',
				'message' => esc_html__( 'This action can not be proceed while the conversion is in progress.', 'buddyboss-pro' ),
			)
		);
	}

	$is_exists = bb_pro_reaction_get_migration_action();
	if ( empty( $is_exists ) ) {
		wp_send_json_error(
			array(
				'content' => '',
				'message' => __( 'Unable to find any existing data for migration.', 'buddyboss-pro' ),
			)
		);
	}

	if ( 'like_to_emotions_action' === $is_exists['action'] ) {
		$reaction_mode = 'likes';
		$wizard_label  = __( 'Convert Likes', 'buddyboss-pro' );
		$old_likes        = bb_pro_get_reactions();
		$likes            = ! empty( $old_likes ) ? current( $old_likes ) : array();
		$current_from_ids = $likes['id'];
	} else {
		$reaction_mode = 'emotions';
		$wizard_label  = __( 'Convert Reactions', 'buddyboss-pro' );
		$old_emotions     = bb_pro_get_reactions( 'emotions', false );
		$current_from_ids = implode( ',', array_filter( array_column( $old_emotions, 'id' ) ) );
	}

	$content             = '';
	$is_notice_dismissed = false;
	if ( ! empty( $current_from_ids ) ) {
		$reaction_count = bb_load_reaction()->bb_get_user_reactions_count(
			array(
				'reaction_id' => $current_from_ids
			)
		);

		if ( 0 < $reaction_count ) {
			$wizard_sections = bb_pro_reaction_get_migration_wizard(
				array(
					'action_type'   => 'switch_wizard',
					'reaction_mode' => $reaction_mode,
				)
			);

			// Check if count mismatch then update it.
			if ( (int) $is_exists['total_reactions'] !== $reaction_count ) {
				$is_exists['total_reactions'] = $reaction_count;
				bb_pro_reaction_update_migration_action( $is_exists );
			}

		} else {
			// No migration data found.
			$wizard_screen1 = bb_pro_reaction_get_no_data_screen( bb_get_reaction_mode() );

			$wizard_sections = array(
				'wizard_screen1' => $wizard_screen1,
				'wizard_screen2' => '',
			);

			// Reset migration.
			bb_pro_reaction_delete_migration();

			// Dismissed the existing notice.
			$is_notice_dismissed = true;
		}
	}

	if ( ! empty( $wizard_sections['wizard_screen1'] ) ) {
		ob_start();
		?>
		<div class="bbpro_migration_wizard_screens bbpro_migration_wizard_1 active">
			<div class="bbpro-modal-box__body">
				<?php echo $wizard_sections['wizard_screen1']; ?>
			</div>
			<div class="bbpro-modal-box__footer">
				<button type="button" class="button-secondary cancel_migration_wizard"><?php echo __( 'Cancel', 'buddyboss-pro' ); ?></button>
				<button type="button" class="button-primary footer_next_wizard_screen disabled"><?php echo __( 'Continue', 'buddyboss-pro' ); ?></button>
			</div>
		</div>
		<?php
		$content .= ob_get_clean();
	}

	if ( ! empty( $wizard_sections['wizard_screen2'] ) ) {
		ob_start();
		?>
		<div class="bbpro_migration_wizard_screens bbpro_migration_wizard_2">
			<div class="bbpro-modal-box__body">
				<?php echo $wizard_sections['wizard_screen2']; ?>
			</div>
			<div class="bbpro-modal-box__footer">
				<button type="button" class="button-secondary cancel_migration_wizard"><?php echo __( 'Cancel', 'buddyboss-pro' ); ?></button>
				<button type="button" class="button-primary start_migration_wizard"><?php echo __( 'Start conversion', 'buddyboss-pro' ); ?></button>
			</div>
		</div>
		<?php
		$content .= ob_get_clean();
	}

	wp_send_json_success(
		array(
			'content'             => $content,
			'label'               => $wizard_label,
			'total_reactions'     => (int) $reaction_count,
			'is_notice_dismissed' => (bool) $is_notice_dismissed,
		)
	);
}

/**
 * Check the emotion data exists while deleting it.
 *
 * @since 2.4.50
 */
function bb_pro_reaction_migration_do_later() {
	if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'bb-pro-reaction-migration-do-later' ) ) {
		wp_send_json_error(
			array(
				'status'  => 'error',
				'message' => __( 'Unable to processed this request, please refresh and try again.', 'buddyboss-pro' ),
			)
		);
	}

	bb_pro_reaction_update_migration_action(
		array(
			'hide_for' => array( get_current_user_id() ),
		)
	);

	wp_send_json_success(
		array(
			'status' => true
		)
	);
}

/**
 * Dismiss the site-wide migration notice.
 *
 * @since 2.4.50
 */
function bb_pro_reaction_dismiss_migration_notice() {
	if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'bb-pro-reaction-dismiss-migration-notice' ) ) {
		wp_send_json_error(
			array(
				'status'  => 'error',
				'message' => esc_html__( 'Unable dismiss notice, please refresh and try again.', 'buddyboss-pro' ),
			)
		);
	}

	bp_delete_option( 'bb_pro_reaction_migration_notice' );

	wp_send_json_success(
		array(
			'status' => 'success',
		)
	);
}

/**
 * Ajax to stop the migration.
 *
 * @since 2.4.50
 */
function bb_pro_reaction_migration_stop_conversion() {
	if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'bb-pro-reaction-migration-stop-conversion' ) ) {
		wp_send_json_error(
			array(
				'message' => esc_html__( 'Unable to stop migration, please refresh and try again.', 'buddyboss-pro' ),
			)
		);
	}
	global $bb_reaction_background_process;

	if ( $bb_reaction_background_process->is_processing() ) {
		$bb_reaction_background_process->pause();
	}

	if ( $bb_reaction_background_process->is_active() ) {
		$bb_reaction_background_process->kill_process();
		$bb_reaction_background_process->cancel_process();

		// Delete migration data.
		bb_pro_reaction_delete_migration();
		// Delete migration notice.
		bp_delete_option( 'bb_pro_reaction_migration_notice' );

		// Delete after complete migration.
		bp_delete_option( 'bb_pro_reaction_migration_completed' );

		// Delete option for migration.
		bp_delete_option( 'bb_pro_reaction_migration' );

		// delete option for running.
		delete_option( 'is_reaction_migration' );

		//  Flush cache when having external cache.
		wp_cache_flush();

		wp_send_json_success();
	} else {
		wp_send_json_error();
	}

	wp_die();
}

/**
 * Function to show global notice when reaction migration is running.
 *
 * @since 2.4.50
 *
 * @return void
 */
function bb_pro_reaction_show_global_notice() {
	$is_show_notice = bp_get_option( 'bb_pro_reaction_migration_notice' );
	if ( ! empty( $is_show_notice ) && 'inprogress' === bb_pro_reaction_get_migration_status() ) {
		echo sprintf(
			'<div id="bb-pro-reaction-global-notice" class="notice notice-warning is-dismissible">
						<p>%s</p>
					</div>',
			__( 'Reactions are currently being migrated. Once complete, the new reactions will be visible on your site.', 'buddyboss-pro' ),
		);
	}
}
