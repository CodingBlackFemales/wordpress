<?php
/**
 * BuddyPress Single Groups Zoom Navigation
 *
 * @package BuddyBossPro/Integration/Zoom/Template
 * @since 1.0.0
 */

?>

<?php
add_filter( 'bp_nouveau_group_secondary_nav_parent_slug', 'bp_zoom_nouveau_group_secondary_nav_parent_slug' );
add_filter( 'bp_nouveau_get_classes', 'bp_zoom_nouveau_group_secondary_nav_selected_classes', 10, 3 );
?>

<nav class="<?php bp_nouveau_single_item_subnav_classes(); ?>" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Group zoom navigation menu', 'buddyboss-pro' ); ?>">

	<?php if ( bp_nouveau_has_nav( array( 'object' => 'group_zoom' ) ) ) : ?>

		<ul class="subnav">

			<?php
			while ( bp_nouveau_nav_items() ) :
				bp_nouveau_nav_item();
				?>

				<li id="<?php bp_nouveau_nav_id(); ?>" class="<?php bp_nouveau_nav_classes(); ?>">
					<a href="<?php bp_nouveau_nav_link(); ?>" id="<?php bp_nouveau_nav_link_id(); ?>">
						<?php bp_nouveau_nav_link_text(); ?>

						<?php if ( bp_nouveau_nav_has_count() ) : ?>
							<span class="count"><?php bp_nouveau_nav_count(); ?></span>
						<?php endif; ?>
					</a>
				</li>

			<?php endwhile; ?>

			<?php if ( bp_zoom_groups_is_webinars_enabled( bp_get_current_group_id() ) ) : ?>
				<!-- Toggle Button for Webinar and Meetings -->
				<li id="bp-zoom-switch-type" class="bp-groups-tab bp-zoom-switch-type">
					<a href="<?php echo esc_url( bp_zoom_get_groups_meetings_url() ); ?>" class="<?php echo ( bp_zoom_is_webinars() || bp_zoom_is_create_webinar() ) ? '' : 'zoom_active'; ?>"><?php esc_html_e( 'Meetings', 'buddyboss-pro' ); ?></a>
					<a href="<?php echo esc_url( bp_zoom_get_groups_webinars_url() ); ?>" class="<?php echo ( bp_zoom_is_webinars() || bp_zoom_is_create_webinar() ) ? 'zoom_active' : ''; ?>"><?php esc_html_e( 'Webinars', 'buddyboss-pro' ); ?></a>
				</li>
			<?php endif; ?>

			<?php if ( bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), bp_get_current_group_id() ) ) { ?>
				<li id="<?php echo bp_zoom_is_webinars() || bp_zoom_is_create_webinar() ? esc_attr( 'sync-webinars-groups-li' ) : esc_attr( 'sync-meetings-groups-li' ); ?>" class="bp-groups-tab <?php echo bp_zoom_is_webinars() || bp_zoom_is_create_webinar() ? esc_attr( 'sync-webinars' ) : esc_attr( 'sync-meetings' ); ?>">
					<a href="#" id="<?php echo bp_zoom_is_webinars() || bp_zoom_is_create_webinar() ? esc_attr( 'webinars-sync' ) : esc_attr( 'meetings-sync' ); ?>" data-group-id="<?php echo esc_attr( bp_get_current_group_id() ); ?>" data-bp-tooltip="<?php echo bp_zoom_is_webinars() || bp_zoom_is_create_webinar() ? esc_attr__( 'Sync group webinars with Zoom', 'buddyboss-pro' ) : esc_attr__( 'Sync group meetings with Zoom', 'buddyboss-pro' ); ?>" data-bp-tooltip-pos="left">
						<i class="bb-icon-bl bb-icon-sync"></i>
						<?php esc_html_e( 'Sync', 'buddyboss-pro' ); ?>
						<i class="bb-icon-l bb-icon-spinner animate-spin"></i>
					</a>
				</li>
			<?php } ?>

		</ul>

	<?php endif; ?>

</nav><!-- #isubnav -->

<?php
remove_filter( 'bp_nouveau_group_nav_get_secondary_parent_slug', 'bp_zoom_nouveau_group_secondary_nav_parent_slug' );
remove_filter( 'bp_nouveau_get_classes', 'bp_zoom_nouveau_group_secondary_nav_selected_classes', 10, 3 );
?>
