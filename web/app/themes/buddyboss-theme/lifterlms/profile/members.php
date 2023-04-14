<?php
/**
 * File Summary
 *
 * File description.
 *
 * @package LifterLMS/Classes
 *
 * @since 1.0.0-beta.1
 * @version 1.0.0-beta.1
 *
 * @property LLMS_Group        $group   Group object.
 * @property string            $context Card location context, either "sidebar" or "main".
 * @property LLMS_Member_Query $members Group members query object.
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="llms-group-card card--group-profile-members">

	<header class="llms-group-card-header">
		<h3 class="llms-group-card-title">
			<i class="fa fa-users" aria-hidden="true"></i>
			<?php _e( 'Members', 'buddyboss-theme' ); ?>
		</h3>
		<?php if ( current_user_can( 'manage_group_members', $group->get( 'id' ) ) ) : ?>
			<button class="llms-group-button ghost llms-group-card-action invite-members" data-micromodal-trigger="llms-group-invite-modal" id="llms-group-invite-members" type="button">
				<span class="llms-group-text"><?php _e( 'Manage', 'buddyboss-theme' ); ?></span>
				<i class="fa fa-pencil" aria-hidden="true"></i>
			</button>
		<?php endif; ?>
	</header>

	<div class="llms-group-card-main">

		<?php if ( 'main' === $context ) : ?>

			<div class="card--group-profile-members__leaders">

				<h3 class="llms-group-card-main--heading"><?php echo llms_groups()->get_integration()->get_option( 'leader_name_plural' ); ?></h3>

				<div class="card--group-profile-members__vip-blocks">

					<?php foreach ( $leaders->get_members() as $member ) : ?>
						<?php do_action( 'llms_group_member_block', $member, $context ); ?>
					<?php endforeach; ?>

				</div>

			</div>

			<h3 class="llms-group-card-main--heading"><?php _e( 'All Members', 'buddyboss-theme' ); ?></h3>

		<?php endif; ?>

		<?php foreach ( $members->get_members() as $member ) : ?>
			<?php do_action( 'llms_group_member_block', $member, $context ); ?>
		<?php endforeach; ?>
	</div>

	<?php if ( 'sidebar' === $context ) : ?>
		<footer class="llms-group-card-footer content-right">
			<a class="right" href="<?php echo esc_url( LLMS_Groups_Profile::get_tab_url( 'members' ) ); ?>"><?php _e( 'View all &rarr;', 'buddyboss-theme' ); ?></a>
		</footer>
	<?php else : ?>
		<footer class="llms-group-card-footer">
			<?php llms_get_template( 'loop/pagination.php' ); ?>
		</footer>
	<?php endif; ?>

</div>
