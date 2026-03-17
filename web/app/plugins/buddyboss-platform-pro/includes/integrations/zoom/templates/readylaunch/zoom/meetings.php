<?php
/**
 * BuddyBoss - Groups Zoom Meetings for ReadyLaunch
 *
 * @package BuddyBossPro/Integration/Zoom/Template/ReadyLaunch
 * @since 1.0.0
 */

global $bp_zoom_current_meeting;
$live_meetings = array();
?>
<div class="bb-rl-topbar-subnav">
	<?php bp_get_template_part( 'zoom/parts/zoom-subnav' ); ?>
</div>
<div id="bp-zoom-meeting-container" class="bp-zoom-meeting-container <?php bp_zoom_meeting_group_classes(); ?> bb-rl-zoom-meeting-container">
	<div class="bb-rl-zoom-panel">
		<div class="bb-rl-zoom-panel-header">
			<div class="bb-rl-zoom-header">
				<?php if ( ( ! empty( $bp_zoom_current_meeting ) && true === $bp_zoom_current_meeting->is_past && false === $bp_zoom_current_meeting->is_live ) || ( 'past-meetings' === bp_zoom_group_current_meeting_tab() ) ) { ?>
					<h4 class="bb-rl-total-text"><?php esc_html_e( 'Past meetings', 'buddyboss-pro' ); ?></h4>
				<?php } else { ?>
					<h4 class="bb-rl-total-text"><?php esc_html_e( 'Zoom meetings', 'buddyboss-pro' ); ?></h4>
				<?php } ?>
			</div>
			<div class="bb-rl-zoom-header-nav">
				<div class="bb-rl-zoom-sync-wrapper">
					<?php if ( bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), bp_get_current_group_id() ) ) { ?>
						<ul class="bb-rl-sync-tab">
							<li id="<?php echo bp_zoom_is_webinars() || bp_zoom_is_create_webinar() ? esc_attr( 'sync-webinars-groups-li' ) : esc_attr( 'sync-meetings-groups-li' ); ?>" class="bp-groups-tab <?php echo bp_zoom_is_webinars() || bp_zoom_is_create_webinar() ? esc_attr( 'sync-webinars' ) : esc_attr( 'sync-meetings' ); ?>">
								<a href="#" id="<?php echo bp_zoom_is_webinars() || bp_zoom_is_create_webinar() ? esc_attr( 'webinars-sync' ) : esc_attr( 'meetings-sync' ); ?>" class="bb-rl-button bb-rl-button--secondaryOutline bb-rl-button--small" data-group-id="<?php echo esc_attr( bp_get_current_group_id() ); ?>" data-bp-tooltip="<?php echo bp_zoom_is_webinars() || bp_zoom_is_create_webinar() ? esc_attr__( 'Sync group webinars with Zoom', 'buddyboss-pro' ) : esc_attr__( 'Sync group meetings with Zoom', 'buddyboss-pro' ); ?>" data-bp-tooltip-pos="left">
									<i class="bb-icons-rl-arrows-clockwise"></i>
									<?php esc_html_e( 'Sync', 'buddyboss-pro' ); ?>
									<i class="bb-icon-l bb-icon-spinner animate-spin"></i>
								</a>
							</li>
						</ul>
					<?php } ?>
				</div>
				<div class="bp-group-message-wrap">
					<?php if ( ( ! empty( $bp_zoom_current_meeting ) && true === $bp_zoom_current_meeting->is_past && false === $bp_zoom_current_meeting->is_live ) || ( 'past-meetings' === bp_zoom_group_current_meeting_tab() ) ) : ?>
						<?php if ( bp_zoom_is_zoom_recordings_enabled() && ! empty( $bp_zoom_current_meeting ) && true === $bp_zoom_current_meeting->is_past ) : ?>
							<div class="bp-zoom-meeting-wrap">
								<input id="bp-zoom-meeting-recorded-switch-checkbox" class="bp-zoom-meeting-recorded-meeting-checkbox bb-input-switch bs-styled-checkbox" type="checkbox">
								<label for="bp-zoom-meeting-recorded-switch-checkbox" class="bp-zoom-recorded-label"><span class="select-recorded-text"><?php esc_html_e( 'Recorded', 'buddyboss-pro' ); ?></span></label>
							</div>
						<?php endif; ?>
					<?php elseif ( bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), bp_get_current_group_id() ) ) : ?>
						<a href="<?php echo esc_url( trailingslashit( bp_get_group_permalink( groups_get_group( bp_get_current_group_id() ) ) . 'zoom/create-meeting/' ) ); ?>" id="bp-zoom-create-meeting-button" class="bb-rl-button bb-rl-button--brandFill bb-rl-button--small" data-group-id="<?php echo esc_attr( bp_get_group_id() ); ?>">
							<i class="bb-icons-rl-plus"></i><?php esc_html_e( 'Create New', 'buddyboss-pro' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="bb-rl-zoom-panel-body">
			<div class="bp-zoom-meeting-left bb-rl-zoom-sidenav <?php if ( empty( $bp_zoom_current_meeting ) && 'past-meetings' === bp_zoom_group_current_meeting_tab() ) { echo 'bp-full'; } ?>">
				<div class="bp-zoom-meeting-left-inner">
					<div class="bb-rl-panel-head">
						<div class="bb-rl-panel-subhead">
							<div id="bp-zoom-dropdown-options-loader" class="bp-zoom-dropdown-options-loader-hide">
								<i class="bb-icons-rl-spinner animate-spin"></i>
							</div>

							<?php bp_get_template_part( 'zoom/parts/zoom-subnav' ); ?>
						</div>
					</div>

					<div class="bp-zoom-meeting-search subnav-search clearfix" role="search">
						<div class="bb-rl-search">
							<form action="" method="get" id="bp_zoom_meeting_search_form" class="bp-zoom-meeting-search-form" data-bp-search="zoom-meeting">
								<label for="bp_zoom_meeting_search" class="bp-screen-reader-text"><?php bp_nouveau_search_default_text( __( 'Search Meetings', 'buddyboss-pro' ), false ); ?></label>
								<input type="search" id="bp_zoom_meeting_search" placeholder="<?php esc_attr_e( 'Search Meetings', 'buddyboss-pro' ); ?>" />
								<button type="submit" id="bp_zoom_meeting_search_submit" class="bb-rl-search-submit">
									<i class="bb-icons-rl-magnifying-glass" aria-hidden="true"></i>
									<span id="button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search Meetings', 'buddyboss-pro' ); ?></span>
								</button>
							</form>
						</div>
					</div>

					<div class="bp-zoom-meeting-members-listing">
						<?php
						if ( ( 'meetings' === bp_zoom_group_current_meeting_tab() || 'zoom' === bp_zoom_group_current_meeting_tab() ) && bp_has_zoom_meetings(
							array(
								'zoom_type' => array( 'meeting', 'meeting_occurrence' ),
								'live'      => true,
							)
						) ) :
							?>
						<ul id="meetings-list" class="item-list bb-rl-list all-meetings">
							<?php
							while ( bp_zoom_meeting() ) {
								bp_the_zoom_meeting();

								$live_meetings[] = bp_get_zoom_meeting_id();
								bp_get_template_part( 'zoom/loop-meeting' );
							}
							?>
							<?php endif; ?>

							<?php
							if ( bp_has_zoom_meetings(
								array(
									'zoom_type' => array( 'meeting', 'meeting_occurrence' ),
									'exclude'   => implode(
										',',
										$live_meetings
									),
								)
							) ) :
								?>
								<?php if ( empty( $live_meetings ) ) : ?>
									<ul id="meetings-list" class="item-list bb-rl-list all-meetings">
								<?php endif; ?>
								<?php
								while ( bp_zoom_meeting() ) {
									bp_the_zoom_meeting();

									bp_get_template_part( 'zoom/loop-meeting' );
								}

								if ( bp_zoom_meeting_has_more_items() ) {
									?>
									<div class="load-more">
										<a class="bb-rl-button bb-rl-button--outline" href="<?php bp_zoom_meeting_load_more_link(); ?>">
											<?php esc_html_e( 'Load More', 'buddyboss-pro' ); ?>
										</a>
									</div>
									<?php
								}
								?>
								<?php if ( empty( $live_meetings ) ) : ?>
									</ul>
								<?php endif; ?>
						<span class="meeting-timezone"><?php echo esc_html__( 'Timezone:', 'buddyboss-pro' ) . ' ' . esc_attr( bp_zoom_get_timezone_label() ); ?></span>
							<?php else : ?>
								<?php if ( ! empty( $live_meetings ) ) : ?>
						</ul>
					<?php endif; ?>
								<?php if ( empty( $live_meetings ) ) : ?>
									<?php bp_nouveau_user_feedback( 'meetings-loop-none' ); ?>
						<?php endif; ?>
					<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="bp-zoom-meeting-right">
				<?php if ( ( ! empty( $bp_zoom_current_meeting ) && false === $bp_zoom_current_meeting->is_past ) || ( 'past-meetings' !== bp_zoom_group_current_meeting_tab() ) ) { ?>
					<form id="bp_zoom_meeting_form" class="bb-rl-form" data-select2-id="bp_zoom_meeting_form">
						<div class="bp-zoom-meeting-right-top">
							<div id="bp-zoom-meeting-content">
								<div id="bp-zoom-single-meeting-wrapper">

									<?php
									if ( bp_zoom_is_single_meeting() ) {
										$args = array(
											'include' => bp_action_variable( 1 ),
										);

										if ( ! empty( $bp_zoom_current_meeting ) && $bp_zoom_current_meeting->recurring && 8 === $bp_zoom_current_meeting->type ) {
											$args['hide_sitewide'] = true;
										}

										if ( bp_has_zoom_meetings( $args ) ) {
											while ( bp_zoom_meeting() ) {
												bp_the_zoom_meeting();

												bp_get_template_part( 'zoom/single-meeting-item' );
											}
										}
									} elseif ( bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), bp_get_current_group_id() ) ) {
										bp_get_template_part( 'zoom/create-meeting' );
									} else {
										bp_get_template_part( 'zoom/no-meetings' );
									}
									?>

								</div>

							</div>
						</div>
					</form>
				<?php 
				} else {
					bp_get_template_part( 'zoom/no-meetings' );
				}
				?>
			</div>
		</div>
	</div>
</div> 