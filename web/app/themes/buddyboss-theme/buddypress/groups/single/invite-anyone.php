<?php

/**
 * This template, which powers the group Send Invites tab when IA is enabled, can be overridden
 * with a template file at groups/single/invite-anyone.php
 *
 * @package Invite Anyone
 * @since 0.8.5
 */
?>

<?php if ( invite_anyone_access_test() && !bp_is_group_create() ) : ?>
	<div class="bp-feedback info bb-ia-feedback">
		<span class="bp-icon" aria-hidden="true"></span>
		<p><?php esc_html_e( 'Want to invite someone to the group who is not yet a member of the site?', 'buddyboss-theme' ) ?> <a href="<?php echo bp_loggedin_user_domain() . BP_INVITE_ANYONE_SLUG . '/invite-new-members/group-invites/' . bp_get_group_id() ?>"><?php esc_html_e( 'Send invitations by email.', 'buddyboss-theme' ) ?></a></p>
	</div>
<?php endif; ?>

<div class="bb-invite-anyone-wrap">

	<?php do_action( 'bp_before_group_send_invites_content' ) ?>

	<?php if ( !bp_get_new_group_id() ) : ?>
		<form action="<?php invite_anyone_group_invite_form_action() ?>" method="post" id="send-invite-form">
	<?php endif; ?>

	<div class="left-menu">
		<h4 class="total-members-text">Members</h4>
		<ul class="first acfb-holder">
			<li>
				<input type="text" name="send-to-input" class="send-to-input" id="send-to-input" placeholder="<?php esc_html_e("Search members", 'buddyboss-theme') ?>" />
			</li>
		</ul>

		<?php wp_nonce_field( 'groups_invite_uninvite_user', '_wpnonce_invite_uninvite_user' ) ?>

		<?php if ( ! invite_anyone_is_large_network( 'users' ) ) : ?>
			<div class="bb-select-members-text"><strong><?php esc_html_e( 'Select members:', 'buddyboss-theme' ) ?></strong></div>

			<div id="invite-anyone-member-list">
				<ul>
					<?php bp_new_group_invite_member_list() ?>
				</ul>
			</div>
		<?php endif ?>
	</div>

	<div class="main-column">

		<div id="message" class="info">
			<p><?php esc_html_e('Select people to invite from your friends list.', 'buddyboss-theme'); ?></p>
		</div>

		<?php do_action( 'bp_before_group_send_invites_list' ) ?>

		<?php /* The ID 'friend-list' is important for AJAX support. */ ?>
		<ul id="invite-anyone-invite-list" class="item-list">
		<?php if ( bp_group_has_invites() ) : ?>

			<?php while ( bp_group_invites() ) : bp_group_the_invite(); ?>

				<li id="<?php bp_group_invite_item_id() ?>">
					<?php bp_group_invite_user_avatar() ?>

					<h4><?php bp_group_invite_user_link() ?></h4>
					<span class="activity"><?php bp_group_invite_user_last_active() ?></span>

					<?php do_action( 'bp_group_send_invites_item' ) ?>

					<div class="action">
						<a class="remove" href="<?php bp_group_invite_user_remove_invite_url() ?>" id="<?php bp_group_invite_item_id() ?>"><?php esc_html_e( 'Remove Invite', 'buddyboss-theme' ) ?></a>

						<?php do_action( 'bp_group_send_invites_item_action' ) ?>
					</div>
				</li>

			<?php endwhile; ?>

		<?php endif; ?>
		</ul>

		<?php do_action( 'bp_after_group_send_invites_list' ) ?>

		<?php if ( !bp_get_new_group_id() ) : ?>
		<div class="submit">
			<input type="submit" name="submit" id="submit" value="<?php esc_html_e( 'Send Invites', 'buddyboss-theme' ) ?>" />
		</div>
		<?php endif; ?>

		<?php wp_nonce_field( 'groups_send_invites', '_wpnonce_send_invites') ?>

			<!-- Don't leave out this sweet field -->
		<?php
		if ( !bp_get_new_group_id() ) {
			?><input type="hidden" name="group_id" id="group_id" value="<?php bp_group_id() ?>" /><?php
		} else {
			?><input type="hidden" name="group_id" id="group_id" value="<?php bp_new_group_id() ?>" /><?php
		}
		?>
	</div>

	<?php if ( !bp_get_new_group_id() ) : ?>
		</form>
	<?php endif; ?>

	<?php do_action( 'bp_after_group_send_invites_content' ) ?>

</div>