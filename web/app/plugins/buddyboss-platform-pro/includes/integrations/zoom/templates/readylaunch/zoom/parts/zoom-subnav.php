<?php
/**
 * BuddyBoss - Groups Zoom Sub Nav for ReadyLaunch
 *
 * @package BuddyBossPro/Integration/Zoom/Template/ReadyLaunch
 * @since 1.0.0
 */

add_filter( 'bp_nouveau_group_secondary_nav_parent_slug', 'bp_zoom_nouveau_group_secondary_nav_parent_slug' );
add_filter( 'bp_nouveau_get_classes', 'bp_zoom_nouveau_group_secondary_nav_selected_classes', 10, 3 );

// Filter to modify navigation text for ReadyLaunch
add_filter( 'bp_nouveau_get_nav_link_text', function( $text, $nav_item ) {
	if ( isset( $nav_item->slug ) ) {
		if ( 'meetings' === $nav_item->slug || 'webinars' === $nav_item->slug ) {
			return esc_html__( 'Upcoming', 'buddyboss-pro' );
		} elseif ( 'past-meetings' === $nav_item->slug || 'past-webinars' === $nav_item->slug ) {
			return esc_html__( 'Previous', 'buddyboss-pro' );
		}
	}
	return $text;
}, 10, 2 );

if ( bp_nouveau_has_nav( array( 'object' => 'group_zoom' ) ) ) : ?>
	<div class="bb-rl-zoom-tabs item-body-inner <?php echo bp_zoom_groups_is_webinars_enabled( bp_get_current_group_id() ) ? 'bb-rl-zoom-tabs-webinars' : ''; ?>">
		<nav class="bb-rl-tabs-wrapper main-nav zoom-nav-tabs horizontal-tabs">
			<ul class="bb-rl-tabs">
				<?php
				$zoom_link = trailingslashit( bp_get_group_permalink() . 'zoom' );
				while ( bp_nouveau_nav_items() ) :
					bp_nouveau_nav_item();
					$bp_nouveau = bp_nouveau();
					$nav = $bp_nouveau->current_nav_item;
					$nav_link = str_replace( $zoom_link, '', $nav->link );
					$nav_link = $zoom_link . $nav_link;
					?>
					<li id="<?php bp_nouveau_nav_id(); ?>" class="<?php bp_nouveau_nav_classes(); ?>">
						<a href="<?php echo esc_url( $nav_link ); ?>" id="<?php bp_nouveau_nav_link_id(); ?>">
							<?php bp_nouveau_nav_link_text(); ?>
							<?php if ( bp_nouveau_nav_has_count() ) : ?>
								<span class="bb-rl-count"><?php bp_nouveau_nav_count(); ?></span>
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
			</ul>
		</nav>
	</div>
<?php endif; 

remove_filter( 'bp_nouveau_group_nav_get_secondary_parent_slug', 'bp_zoom_nouveau_group_secondary_nav_parent_slug' );
remove_filter( 'bp_nouveau_get_classes', 'bp_zoom_nouveau_group_secondary_nav_selected_classes', 10, 3 );
?> 