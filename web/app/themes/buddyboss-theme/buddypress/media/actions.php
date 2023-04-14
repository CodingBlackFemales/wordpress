<?php
/**
 * BuddyBoss - Media Actions
 *
 * @since BuddyBoss 1.0.0
 */

if (
	(
		bp_is_my_profile() ||
		bp_current_user_can( 'bp_moderate' )
	) ||
	(
		bp_is_group() &&
		(
			bp_is_group_media() &&
			(
				groups_can_user_manage_media( bp_loggedin_user_id(), bp_get_current_group_id() ) ||
				groups_is_user_mod( bp_loggedin_user_id(), bp_get_current_group_id() ) ||
				groups_is_user_admin( bp_loggedin_user_id(), bp_get_current_group_id() )
			)
		) ||
		(
			bp_is_group_albums() &&
			(
				groups_can_user_manage_albums( bp_loggedin_user_id(), bp_get_current_group_id() ) ||
				groups_is_user_mod( bp_loggedin_user_id(), bp_get_current_group_id() ) ||
				groups_is_user_admin( bp_loggedin_user_id(), bp_get_current_group_id() )
			)
		)
	)
) : ?>

	<header class="bb-member-media-header bb-photos-actions">
		<div class="bb-media-meta bb-photos-meta">
			<a data-balloon="<?php esc_attr_e( 'Delete', 'buddyboss-theme' ); ?>" data-balloon-pos="up" class="bb-delete" id="bb-delete-media" href="#"><i class="bb-icon-l bb-icon-trash"></i></a>
			<a data-balloon="<?php esc_attr_e( 'Select All', 'buddyboss-theme' ); ?>" data-balloon-pos="up" class="bb-select" id="bb-select-deselect-all-media" href="#"><i class="bb-icon-l bb-icon-check"></i></a>
		</div>
	</header>

<?php endif; ?>
