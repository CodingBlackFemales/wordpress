<?php

if ( is_user_logged_in() ) {


	// Action - Before buddypress profile menu.
	do_action( THEME_HOOK_PREFIX . 'before_bb_profile_menu' );

	if ( bp_is_active( 'xprofile' ) ) {
		// Profile link.
		$profile_link = trailingslashit( bp_loggedin_user_domain() . bp_get_profile_slug() );

		$is_enable_profile_avatar = true;
		if ( function_exists( 'bp_disable_group_avatar_uploads' ) && bp_disable_avatar_uploads() ) {
			$is_enable_profile_avatar = false;
		}
		?>
		<li id="wp-admin-bar-my-account-xprofile" class="menupop parent">
			<a class="ab-item" aria-haspopup="true" href="<?php echo esc_url( $profile_link ); ?>">
				<i class="bb-icon-l bb-icon-user-avatar"></i>
				<span class="wp-admin-bar-arrow" aria-hidden="true"></span><?php esc_html_e( 'Profile', 'buddyboss-theme' ); ?>
			</a>
			<div class="ab-sub-wrapper wrapper">
				<ul id="wp-admin-bar-my-account-xprofile-default" class="ab-submenu">
					<li id="wp-admin-bar-my-account-xprofile-public">
						<a class="ab-item" href="<?php echo esc_url( $profile_link ); ?>"><?php esc_html_e( 'View', 'buddyboss-theme' ); ?></a>
					</li>
					<li id="wp-admin-bar-my-account-xprofile-edit">
						<a class="ab-item" href="<?php echo esc_url( trailingslashit( $profile_link . 'edit' ) ); ?>"><?php esc_html_e( 'Edit', 'buddyboss-theme' ); ?></a>
					</li>
					<?php if ( $is_enable_profile_avatar && buddypress()->avatar->show_avatars ) { ?>
					<li id="wp-admin-bar-my-account-xprofile-change-avatar">
						<a class="ab-item" href="<?php echo esc_url( trailingslashit( $profile_link . 'change-avatar' ) ); ?>"><?php esc_html_e( 'Profile Photo', 'buddyboss-theme' ); ?></a>
					</li>
					<?php } ?>
					<?php if ( bp_displayed_user_use_cover_image_header() ) { ?>
					<li id="wp-admin-bar-my-account-xprofile-change-cover-image">
						<a class="ab-item" href="<?php echo esc_url( trailingslashit( $profile_link . 'change-cover-image' ) ); ?>"><?php esc_html_e( 'Cover Photo', 'buddyboss-theme' ); ?></a>
					</li>
					<?php } ?>
				</ul>
			</div>
		</li>
		<?php
	}

	// Action - After buddypress xprofile menu.
	do_action( THEME_HOOK_PREFIX . 'after_bb_xprofile_menu' );

	if ( bp_is_active( 'settings' ) ) {
		// Setup the logged in user variables.
		$settings_link = trailingslashit( bp_loggedin_user_domain() . bp_get_settings_slug() );

		?>
		<li id="wp-admin-bar-my-account-settings" class="menupop parent">
			<a class="ab-item" aria-haspopup="true" href="<?php echo esc_url( $settings_link ); ?>">
				<i class="bb-icon-l bb-icon-user"></i>
				<span class="wp-admin-bar-arrow" aria-hidden="true"></span><?php esc_html_e( 'Account', 'buddyboss-theme' ); ?>
			</a>
			<div class="ab-sub-wrapper wrapper">
				<ul id="wp-admin-bar-my-account-settings-default" class="ab-submenu">
					<li id="wp-admin-bar-my-account-settings-general">
						<a class="ab-item" href="<?php echo esc_url( $settings_link ); ?>">
							<?php esc_html_e( 'Login Information', 'buddyboss-theme' ); ?>
						</a>
					</li>
					<?php if ( has_action( 'bp_notification_settings' ) ) { ?>
					<li id="wp-admin-bar-my-account-settings-notifications">
						<a class="ab-item" href="<?php echo esc_url( trailingslashit( $settings_link . 'notifications' ) ); ?>">
							<?php
							if ( function_exists( 'bb_core_notification_preferences_data' ) ) {
								$data = bb_core_notification_preferences_data();
								echo esc_html( $data['menu_title'] );
							} else {
								esc_html_e( 'Email Preferences', 'buddyboss-theme' );
							}
							?>
						</a>
					</li>
					<?php } ?>
					<li id="wp-admin-bar-my-account-settings-profile">
						<a class="ab-item" href="<?php echo esc_url( trailingslashit( $settings_link . 'profile' ) ); ?>">
							<?php esc_html_e( 'Privacy', 'buddyboss-theme' ); ?>
						</a>
					</li>
					<?php if ( bp_is_active( 'moderation' ) && function_exists( 'bp_is_moderation_member_blocking_enable' ) && bp_is_moderation_member_blocking_enable() ) { ?>
						<li id="wp-admin-bar-my-account-settings-blocked-members">
							<a class="ab-item"
							   href="<?php echo esc_url( trailingslashit( $settings_link . 'blocked-members' ) ); ?>">
								<?php esc_html_e( 'Blocked Members', 'buddyboss-theme' ); ?>
							</a>
						</li>
					<?php } ?>
					<?php if ( bp_is_active('groups') && function_exists( 'bp_core_can_edit_settings' ) && bp_core_can_edit_settings() ) { ?>
						<li id="wp-admin-bar-my-account-settings-group-invites">
							<a class="ab-item" href="<?php echo esc_url( trailingslashit( $settings_link . 'invites' ) ); ?>">
								<?php esc_html_e( 'Group Invites', 'buddyboss-theme' ); ?>
							</a>
						</li>
					<?php } ?>
					<li id="wp-admin-bar-my-account-settings-export">
						<a class="ab-item" href="<?php echo esc_url( trailingslashit( $settings_link . 'export/' ) ); ?>">
							<?php esc_html_e( 'Export Data', 'buddyboss-theme' ); ?>
						</a>
					</li>
					<?php if ( ! bp_current_user_can( 'bp_moderate' ) && ! bp_core_get_root_option( 'bp-disable-account-deletion' ) ) { ?>
					<li id="wp-admin-bar-my-account-settings-delete-account">
						<a class="ab-item" href="<?php echo esc_url( trailingslashit( $settings_link . 'delete-account' ) ); ?>">
							<?php esc_html_e( 'Delete Account', 'buddyboss-theme' ); ?>
						</a>
					</li>
					<?php } ?>
				</ul>
			</div>
		</li>
		<?php
	}

	// Action - After buddypress setting menu.
	do_action( THEME_HOOK_PREFIX . 'after_bb_setting_menu' );

	if ( bp_is_active( 'activity' ) ) {
		// Setup the logged in user variables.
		$activity_link = trailingslashit( bp_loggedin_user_domain() . bp_get_activity_slug() );

		?>
		<li id="wp-admin-bar-my-account-activity" class="menupop parent">
			<a class="ab-item" aria-haspopup="true" href="<?php echo esc_url( $activity_link ); ?>">
				<i class="bb-icon-l bb-icon-activity"></i>
				<span class="wp-admin-bar-arrow" aria-hidden="true"></span><?php esc_html_e( 'Timeline', 'buddyboss-theme' ); ?>
			</a>
			<div class="ab-sub-wrapper wrapper">
				<ul id="wp-admin-bar-my-account-activity-default" class="ab-submenu">
					<li id="wp-admin-bar-my-account-activity-personal">
						<a class="ab-item" href="<?php echo esc_url( $activity_link ); ?>"><?php echo function_exists( 'bp_is_activity_tabs_active' ) && bp_is_activity_tabs_active() ? __( 'Personal', 'buddyboss-theme' ) : __( 'Posts', 'buddyboss-theme' ); ?></a>
					</li>
					<?php if ( function_exists( 'bp_is_activity_tabs_active' ) && bp_is_activity_tabs_active() ) : ?>
						<?php if ( bp_is_activity_like_active() ) : ?>
							<li id="wp-admin-bar-my-account-activity-favorites">
								<a class="ab-item" href="<?php echo esc_url( trailingslashit( $activity_link . 'favorites' ) ); ?>"><?php esc_html_e( 'Likes', 'buddyboss-theme' ); ?></a>
							</li>
						<?php endif; ?>
						<?php if ( bp_is_active( 'friends' ) ) : ?>
							<li id="wp-admin-bar-my-account-activity-friends">
								<a class="ab-item" href="<?php echo esc_url( trailingslashit( $activity_link . 'friends' ) ); ?>"><?php esc_html_e( 'Connections', 'buddyboss-theme' ); ?></a>
							</li>
						<?php endif; ?>
						<?php if ( bp_is_active( 'groups' ) ) : ?>
							<li id="wp-admin-bar-my-account-activity-groups">
								<a class="ab-item" href="<?php echo esc_url( trailingslashit( $activity_link . 'groups' ) ); ?>"><?php esc_html_e( 'Groups', 'buddyboss-theme' ); ?></a>
							</li>
						<?php endif; ?>
						<?php if ( bp_activity_do_mentions() ) : ?>
							<li id="wp-admin-bar-my-account-activity-mentions">
								<a class="ab-item" href="<?php echo esc_url( trailingslashit( $activity_link . 'mentions' ) ); ?>"><?php esc_html_e( 'Mentions', 'buddyboss-theme' ); ?></a>
							</li>
						<?php endif; ?>
						<?php if ( function_exists( 'bp_is_activity_follow_active' ) && bp_is_activity_follow_active() ) : ?>
							<li id="wp-admin-bar-my-account-activity-following">
								<a class="ab-item" href="<?php echo esc_url( trailingslashit( $activity_link . 'following' ) ); ?>"><?php esc_html_e( 'Following', 'buddyboss-theme' ); ?></a>
							</li>
						<?php endif; ?>
					<?php endif; ?>
				</ul>
			</div>
		</li>
		<?php
	}

	// Action - After buddypress activity menu.
	do_action( THEME_HOOK_PREFIX . 'after_bb_activity_menu' );

	if ( bp_is_active( 'notifications' ) ) {
		// Setup the logged in user variables.
		$notifications_link = trailingslashit( bp_loggedin_user_domain() . bp_get_notifications_slug() );

		// Pending notification requests.
		$count = bp_notifications_get_unread_notification_count( bp_loggedin_user_id() );
		if ( ! empty( $count ) ) {
			$title = sprintf(
			/* translators: %s: Unread notification count for the current user */
				__( 'Notifications %s', 'buddyboss-theme' ),
				'<span class="count">' . bp_core_number_format( $count ) . '</span>'
			);
			$unread = sprintf(
			/* translators: %s: Unread notification count for the current user */
				__( 'Unread %s', 'buddyboss-theme' ),
				'<span class="count">' . bp_core_number_format( $count ) . '</span>'
			);
		} else {
			$title  = __( 'Notifications', 'buddyboss-theme' );
			$unread = __( 'Unread', 'buddyboss-theme' );
		}

		?>
		<li id="wp-admin-bar-my-account-notifications" class="menupop parent">
			<a class="ab-item" aria-haspopup="true" href="<?php echo esc_url( $notifications_link ); ?>">
				<i class="bb-icon-l bb-icon-bell"></i>
				<span class="wp-admin-bar-arrow" aria-hidden="true"></span><?php echo wp_kses_post( $title ); ?>
			</a>
			<div class="ab-sub-wrapper wrapper">
				<ul id="wp-admin-bar-my-account-notifications-default" class="ab-submenu">
					<li id="wp-admin-bar-my-account-notifications-unread">
						<a class="ab-item" href="<?php echo esc_url( $notifications_link ); ?>"><?php echo $unread; ?></a>
					</li>
					<li id="wp-admin-bar-my-account-notifications-read">
						<a class="ab-item" href="<?php echo esc_url( trailingslashit( $notifications_link . 'read' ) ); ?>"><?php esc_html_e( 'Read', 'buddyboss-theme' ); ?></a>
					</li>
				</ul>
			</div>
		</li>
		<?php
	}

	// Action - After buddypress notifications menu.
	do_action( THEME_HOOK_PREFIX . 'after_bb_notifications_menu' );

	if ( bp_is_active( 'messages' ) ) {
		// Setup the logged in user variables.
		$messages_link = trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() );

		// Unread message count.
		$count = messages_get_unread_count( bp_loggedin_user_id() );
		if ( ! empty( $count ) ) {
			$title = sprintf(
			/* translators: %s: Unread message count for the current user */
				__( 'Messages %s', 'buddyboss-theme' ),
				'<span class="count">' . bp_core_number_format( $count ) . '</span>'
			);
			$inbox = sprintf(
			/* translators: %s: Unread message count for the current user */
				__( 'Messages %s', 'buddyboss-theme' ),
				'<span class="count">' . bp_core_number_format( $count ) . '</span>'
			);
		} else {
			$title = __( 'Messages', 'buddyboss-theme' );
			$inbox = __( 'Messages', 'buddyboss-theme' );
		}

		?>
		<li id="wp-admin-bar-my-account-messages" class="menupop parent wp-admin-bar-my-account-messages-<?php echo esc_attr( bp_loggedin_user_id() ); ?>">
			<a class="ab-item" aria-haspopup="true" href="<?php echo esc_url( $messages_link ); ?>">
				<i class="bb-icon-l bb-icon-inbox"></i>
				<span class="wp-admin-bar-arrow" aria-hidden="true"></span><?php echo wp_kses_post( $title ); ?>
			</a>
			<div class="ab-sub-wrapper wrapper">
				<ul id="wp-admin-bar-my-account-messages-default" class="ab-submenu">
					<li id="wp-admin-bar-my-account-messages-inbox">
						<a class="ab-item" href="<?php echo esc_url( $messages_link ); ?>"><?php esc_html_e( 'Messages', 'buddyboss-theme' ); ?></a>
					</li>
					<li id="wp-admin-bar-my-account-messages-compose">
						<a class="ab-item" href="<?php echo esc_url( trailingslashit( $messages_link . 'compose' ) ); ?>"><?php esc_html_e( 'New Message', 'buddyboss-theme' ); ?></a>
					</li>
					<?php if ( bp_current_user_can( 'bp_moderate' ) ) { ?>
						<li id="wp-admin-bar-my-account-messages-notices">
							<a class="ab-item" href="<?php echo esc_url( admin_url( '/admin.php?page=bp-notices' ) ); ?>"><?php esc_html_e( 'Site Notices', 'buddyboss-theme' ); ?></a>
						</li>
					<?php } ?>
				</ul>
			</div>
		</li>
		<?php
	}

	// Action - After buddypress messages menu.
	do_action( THEME_HOOK_PREFIX . 'after_bb_messages_menu' );

	if ( bp_is_active( 'friends' ) ) {
		// Setup the logged in user variables.
		$friends_link = trailingslashit( bp_loggedin_user_domain() . bp_get_friends_slug() );

		// Pending friend requests.
		$count = count( friends_get_friendship_request_user_ids( bp_loggedin_user_id() ) );
		if ( ! empty( $count ) ) {
			$title = sprintf(
			/* translators: %s: Pending friend request count for the current user */
				__( 'Connections %s', 'buddyboss-theme' ),
				'<span class="count">' . bp_core_number_format( $count ) . '</span>'
			);
			$pending = sprintf(
			/* translators: %s: Pending friend request count for the current user */
				__( 'Pending Requests %s', 'buddyboss-theme' ),
				'<span class="count">' . bp_core_number_format( $count ) . '</span>'
			);
		} else {
			$title   = __( 'Connections', 'buddyboss-theme' );
			$pending = __( 'No Pending Requests', 'buddyboss-theme' );
		}

		?>
		<li id="wp-admin-bar-my-account-friends" class="menupop parent">
			<a class="ab-item" aria-haspopup="true" href="<?php echo esc_url( $friends_link ); ?>">
				<i class="bb-icon-l bb-icon-user-friends"></i>
				<span class="wp-admin-bar-arrow" aria-hidden="true"></span><?php echo wp_kses_post( $title ); ?>
			</a>
			<div class="ab-sub-wrapper wrapper">
				<ul id="wp-admin-bar-my-account-friends-default" class="ab-submenu">
					<li id="wp-admin-bar-my-account-friends-friendships">
						<a class="ab-item" href="<?php echo esc_url( $friends_link ); ?>"><?php esc_html_e( 'My Connections', 'buddyboss-theme' ); ?></a>
					</li>
					<li id="wp-admin-bar-my-account-friends-requests">
						<a class="ab-item" href="<?php echo esc_url( trailingslashit( $friends_link . 'requests' ) ); ?>"><?php echo $pending; ?></a>
					</li>
				</ul>
			</div>
		</li>
		<?php
	}

	// Action - After buddypress friends menu.
	do_action( THEME_HOOK_PREFIX . 'after_bb_friends_menu' );

	if ( bp_is_active( 'groups' ) ) {
		// Setup the logged in user variables.
		$groups_link = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() );

		// Pending group invites.
		$count   = groups_get_invite_count_for_user();
		$title   = __( 'Groups', 'buddyboss-theme' );
		$pending = __( 'No Pending Invites', 'buddyboss-theme' );

		if ( ! empty( $count ) ) {
			$title = sprintf(
			/* translators: %s: Group invitation count for the current user */
				__( 'Groups %s', 'buddyboss-theme' ),
				'<span class="count">' . bp_core_number_format( $count ) . '</span>'
			);

			$pending = sprintf(
			/* translators: %s: Group invitation count for the current user */
				__( 'Pending Invites %s', 'buddyboss-theme' ),
				'<span class="count">' . bp_core_number_format( $count ) . '</span>'
			);
		}

		?>
		<li id="wp-admin-bar-my-account-groups" class="menupop parent">
			<a class="ab-item" aria-haspopup="true" href="<?php echo esc_url( $groups_link ); ?>">
				<i class="bb-icon-l bb-icon-users"></i>
				<span class="wp-admin-bar-arrow" aria-hidden="true"></span><?php echo wp_kses_post( $title ); ?>
			</a>
			<div class="ab-sub-wrapper wrapper">
				<ul id="wp-admin-bar-my-account-groups-default" class="ab-submenu">
					<li id="wp-admin-bar-my-account-groups-memberships">
						<a class="ab-item" href="<?php echo esc_url( $groups_link ); ?>"><?php esc_html_e( 'My Groups', 'buddyboss-theme' ); ?></a>
					</li>
					<li id="wp-admin-bar-my-account-groups-invites">
						<a class="ab-item" href="<?php echo esc_url( trailingslashit( $groups_link . 'invites' ) ); ?>"><?php echo wp_kses_post( $pending ); ?></a>
					</li>
					<?php if ( bp_user_can_create_groups() ) { ?>
						<li id="wp-admin-bar-my-account-groups-create">
							<a class="ab-item" href="<?php echo esc_url( trailingslashit( bp_get_groups_directory_permalink() . 'create' ) ); ?>"><?php esc_html_e( 'Create Group', 'buddyboss-theme' ); ?></a>
						</li>
					<?php } ?>
				</ul>
			</div>
		</li>
		<?php
	}

	// Action - After buddypress groups menu.
	do_action( THEME_HOOK_PREFIX . 'after_bb_groups_menu' );

	if ( bp_is_active( 'forums' ) ) {
		// Setup the logged in user variables.
		$user_domain = bp_loggedin_user_domain();
		$forums_link = trailingslashit( $user_domain . BP_FORUMS_SLUG );

		?>
		<li id="wp-admin-bar-my-account-forums" class="menupop parent">
			<a class="ab-item" aria-haspopup="true" href="<?php echo esc_url( $forums_link ); ?>">
				<i class="bb-icon-l bb-icon-comments-square"></i>
				<span class="wp-admin-bar-arrow" aria-hidden="true"></span><?php esc_html_e( 'Forums', 'buddyboss-theme' ); ?>
			</a>
			<div class="ab-sub-wrapper wrapper">
				<ul id="wp-admin-bar-my-account-forums-default" class="ab-submenu">
					<li id="wp-admin-bar-my-account-forums-topics">
						<a class="ab-item" href="<?php echo esc_url( trailingslashit( $forums_link . bbp_get_topic_archive_slug() ) ); ?>"><?php esc_html_e( 'My Discussions', 'buddyboss-theme' ); ?></a>
					</li>
					<li id="wp-admin-bar-my-account-forums-replies">
						<a class="ab-item" href="<?php echo esc_url( trailingslashit( $forums_link . bbp_get_reply_archive_slug() ) ); ?>"><?php esc_html_e( 'My Replies', 'buddyboss-theme' ); ?></a>
					</li>
					<?php if ( function_exists( 'bbp_is_favorites_active' ) && bbp_is_favorites_active() ) { ?>
					<li id="wp-admin-bar-my-account-forums-favorites">
						<a class="ab-item" href="<?php echo esc_url( trailingslashit( $forums_link . bbp_get_user_favorites_slug() ) ); ?>"><?php esc_html_e( 'My Favorites', 'buddyboss-theme' ); ?></a>
					</li>
					<?php } ?>
					<?php if ( ! function_exists( 'bb_is_enabled_subscription' ) && function_exists( 'bbp_is_subscriptions_active' ) && bbp_is_subscriptions_active() ) { ?>
						<li id="wp-admin-bar-my-account-forums-subscriptions">
							<a class="ab-item" href="<?php echo esc_url( trailingslashit( $forums_link . bbp_get_user_subscriptions_slug() ) ); ?>"><?php esc_html_e( 'Subscriptions', 'buddyboss-theme' ); ?></a>
						</li>
					<?php } ?>
				</ul>
			</div>
		</li>
		<?php
	}

	// Action - After buddypress forums menu.
	do_action( THEME_HOOK_PREFIX . 'after_bb_forums_menu' );

	if ( bp_is_active( 'media' ) && bp_is_profile_media_support_enabled() ) {
		// Setup the logged in user variables.
		$media_link = trailingslashit( bp_loggedin_user_domain() . bp_get_media_slug() );

		?>
		<li id="wp-admin-bar-my-account-media" class="menupop parent">
			<a class="ab-item" aria-haspopup="true" href="<?php echo esc_url( $media_link ); ?>">
				<i class="bb-icon-l bb-icon-images"></i>
				<span class="wp-admin-bar-arrow" aria-hidden="true"></span><?php esc_html_e( 'Photos', 'buddyboss-theme' ); ?>
			</a>
			<div class="ab-sub-wrapper wrapper">
				<ul id="wp-admin-bar-my-account-media-default" class="ab-submenu">
					<li id="wp-admin-bar-my-account-media-my-media">
						<a class="ab-item" href="<?php echo esc_url( $media_link ); ?>"><?php esc_html_e( 'My Photos', 'buddyboss-theme' ); ?></a>
					</li>
					<?php if ( bp_is_profile_albums_support_enabled() ) { ?>
						<li id="wp-admin-bar-my-account-media-albums">
							<a class="ab-item" href="<?php echo esc_url( trailingslashit( $media_link . 'albums' ) ); ?>"><?php esc_html_e( 'My Albums', 'buddyboss-theme' ); ?></a>
						</li>
					<?php } ?>
				</ul>
			</div>
		</li>
		<?php
	}

	// Action - After buddypress media menu.
	do_action( THEME_HOOK_PREFIX . 'after_bb_media_menu' );

	if ( bp_is_active( 'media' ) && function_exists( 'bp_is_profile_document_support_enabled' ) && bp_is_profile_document_support_enabled() ) {
		// Setup the logged in user variables.
		$document_link = trailingslashit( bp_loggedin_user_domain() . bp_get_document_slug() );

		?>
		<li id="wp-admin-bar-my-account-document" class="menupop parent">
			<a class="ab-item" aria-haspopup="true" href="<?php echo esc_url( $document_link ); ?>">
				<i class="bb-icon-l bb-icon-folder-alt"></i>
				<span class="wp-admin-bar-arrow" aria-hidden="true"></span><?php esc_html_e( 'Documents', 'buddyboss-theme' ); ?>
			</a>
			<div class="ab-sub-wrapper wrapper">
				<ul id="wp-admin-bar-my-account-document-default" class="ab-submenu">
					<li id="wp-admin-bar-my-account-document-my-document">
						<a class="ab-item" href="<?php echo esc_url( $document_link ); ?>"><?php esc_html_e( 'My Documents', 'buddyboss-theme' ); ?></a>
					</li>
				</ul>
			</div>
		</li>
		<?php
	}

	/**
	 * Action - After buddypress media menu.
	 *
	 * @since 1.7.0
	 */
	do_action( THEME_HOOK_PREFIX . 'after_bb_document_menu' );

	if ( bp_is_active( 'media' ) && function_exists( 'bp_is_profile_video_support_enabled' ) && bp_is_profile_video_support_enabled() ) {
		// Setup the logged in user variables.
		$video_link = trailingslashit( bp_loggedin_user_domain() . bp_get_video_slug() );

		?>
		<li id="wp-admin-bar-my-account-video" class="menupop parent">
			<a class="ab-item" aria-haspopup="true" href="<?php echo esc_url( $video_link ); ?>">
				<i class="bb-icon-l bb-icon-film"></i>
				<span class="wp-admin-bar-arrow" aria-hidden="true"></span><?php esc_html_e( 'Videos', 'buddyboss-theme' ); ?>
			</a>
			<div class="ab-sub-wrapper wrapper">
				<ul id="wp-admin-bar-my-account-video-default" class="ab-submenu">
					<li id="wp-admin-bar-my-account-video-my-video">
						<a class="ab-item" href="<?php echo esc_url( $video_link ); ?>"><?php esc_html_e( 'My Videos', 'buddyboss-theme' ); ?></a>
					</li>
				</ul>
			</div>
		</li>
		<?php
	}

	/**
	 * Action - After buddypress video menu.
	 *
	 * @since 1.7.0
	 */
	do_action( THEME_HOOK_PREFIX . 'after_bb_video_menu' );

	if ( bp_is_active( 'invites' ) && true === bp_allow_user_to_send_invites() ) {
		// Setup the logged in user variables.
		$invites_link = trailingslashit( bp_loggedin_user_domain() . bp_get_invites_slug() );

		?>
		<li id="wp-admin-bar-my-account-invites" class="menupop parent">
			<a class="ab-item" aria-haspopup="true" href="<?php echo esc_url( $invites_link ); ?>">
				<i class="bb-icon-l bb-icon-envelope"></i>
				<span class="wp-admin-bar-arrow" aria-hidden="true"></span><?php esc_html_e( 'Email Invites', 'buddyboss-theme' ); ?>
			</a>
			<div class="ab-sub-wrapper wrapper">
				<ul id="wp-admin-bar-my-account-invites-default" class="ab-submenu">
					<li id="wp-admin-bar-my-account-invites-invites">
						<a class="ab-item" href="<?php echo esc_url( $invites_link ); ?>"><?php esc_html_e( 'Send Invites', 'buddyboss-theme' ); ?></a>
					</li>
					<li id="wp-admin-bar-my-account-invites-sent">
						<a class="ab-item" href="<?php echo esc_url( trailingslashit( $invites_link . 'sent-invites' ) ); ?>"><?php esc_html_e( 'Sent Invites', 'buddyboss-theme' ); ?></a>
					</li>
				</ul>
			</div>
		</li>
		<?php
	}

	// Action - After buddypress profile menu.
	do_action( THEME_HOOK_PREFIX . 'after_bb_profile_menu' );

	?>
	<li class="logout-link">
		<a href="<?php echo esc_url( wp_logout_url( bp_get_requested_url() ) ); ?>">
			<i class="bb-icon-l bb-icon-sign-out"></i>
			<?php esc_html_e( 'Log Out', 'buddyboss-theme' ); ?>
		</a>
	</li>
	<?php
}
