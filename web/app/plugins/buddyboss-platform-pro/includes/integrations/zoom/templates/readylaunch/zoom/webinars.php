<?php
/**
 * BuddyBoss - Groups Zoom Webinars for ReadyLaunch
 *
 * @package BuddyBossPro/Integration/Zoom/Template/ReadyLaunch
 * @since 1.0.0
 */

global $bp_zoom_current_webinar;
$live_webinars = array();
?>
<div class="bb-rl-topbar-subnav">
	<?php bp_get_template_part( 'zoom/parts/zoom-subnav' ); ?>
</div>
<div id="bp-zoom-webinar-container" class="bp-zoom-webinar-container <?php bp_zoom_webinar_group_classes(); ?> bb-rl-zoom-webinar-container">
	<div class="bb-rl-zoom-panel">
		<div class="bb-rl-zoom-panel-header">
			<div class="bb-rl-zoom-header">
				<?php if ( ( ! empty( $bp_zoom_current_webinar ) && true === $bp_zoom_current_webinar->is_past && false === $bp_zoom_current_webinar->is_live ) || ( 'past-webinars' === bp_zoom_group_current_tab() ) ) { ?>
					<h4 class="bb-rl-total-text"><?php esc_html_e( 'Past webinars', 'buddyboss-pro' ); ?></h4>
				<?php } else { ?>
					<h4 class="bb-rl-total-text"><?php esc_html_e( 'Zoom webinars', 'buddyboss-pro' ); ?></h4>
				<?php } ?>
			</div>
			<div class="bb-rl-zoom-header-nav">
				<div class="bb-rl-zoom-sync-wrapper">
					<?php if ( bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), bp_get_current_group_id() ) ) { ?>
						<ul class="bb-rl-sync-tab">
							<li id="sync-webinars-groups-li" class="bp-groups-tab sync-webinars">
								<a href="#" id="webinars-sync" class="bb-rl-button bb-rl-button--secondaryOutline bb-rl-button--small" data-group-id="<?php echo esc_attr( bp_get_current_group_id() ); ?>" data-bp-tooltip="<?php echo esc_attr__( 'Sync group webinars with Zoom', 'buddyboss-pro' ); ?>" data-bp-tooltip-pos="left">
									<i class="bb-icons-rl-arrows-clockwise"></i>
									<?php esc_html_e( 'Sync', 'buddyboss-pro' ); ?>
									<i class="bb-icon-l bb-icon-spinner animate-spin"></i>
								</a>
							</li>
						</ul>
					<?php } ?>
				</div>
				<div class="bp-group-message-wrap">
					<?php if ( ( ! empty( $bp_zoom_current_webinar ) && true === $bp_zoom_current_webinar->is_past && false === $bp_zoom_current_webinar->is_live ) || ( 'past-webinars' === bp_zoom_group_current_tab() ) ) : ?>
						<?php if ( bp_zoom_is_zoom_recordings_enabled() && ! empty( $bp_zoom_current_webinar ) && true === $bp_zoom_current_webinar->is_past ) : ?>
							<div class="bp-zoom-webinar-wrap">
								<input id="bp-zoom-webinar-recorded-switch-checkbox" class="bp-zoom-webinar-recorded-webinar-checkbox bb-input-switch bs-styled-checkbox" type="checkbox">
								<label for="bp-zoom-webinar-recorded-switch-checkbox" class="bp-zoom-recorded-label"><span class="select-recorded-text"><?php esc_html_e( 'Recorded', 'buddyboss-pro' ); ?></span></label>
							</div>
						<?php endif; ?>
					<?php elseif ( bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), bp_get_current_group_id() ) ) : ?>
						<a href="<?php echo esc_url( trailingslashit( bp_get_group_permalink( groups_get_group( bp_get_current_group_id() ) ) . 'zoom/create-webinar/' ) ); ?>" id="bp-zoom-create-webinar-button" class="bb-rl-button bb-rl-button--brandFill bb-rl-button--small" data-group-id="<?php echo esc_attr( bp_get_group_id() ); ?>">
							<i class="bb-icons-rl-plus"></i><?php esc_html_e( 'Create New', 'buddyboss-pro' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="bb-rl-zoom-panel-body">
			<div class="bp-zoom-webinar-left bb-rl-zoom-sidenav <?php if ( empty( $bp_zoom_current_webinar ) && 'past-webinars' === bp_zoom_group_current_tab() ) { echo 'bp-full'; } ?>">
				<div class="bp-zoom-webinar-left-inner">
					<div class="bb-rl-panel-head">
						<div class="bb-rl-panel-subhead">
							<div id="bp-zoom-dropdown-options-loader" class="bp-zoom-dropdown-options-loader-hide">
								<i class="bb-icons-rl-spinner animate-spin"></i>
							</div>

							<?php bp_get_template_part( 'zoom/parts/zoom-subnav' ); ?>
						</div>
					</div>

					<div class="bp-zoom-webinar-search subnav-search clearfix" role="search">
						<div class="bb-rl-search">
							<form action="" method="get" id="bp_zoom_webinar_search_form" class="bp-zoom-webinar-search-form" data-bp-search="zoom-webinar">
								<label for="bp_zoom_webinar_search" class="bp-screen-reader-text"><?php bp_nouveau_search_default_text( __( 'Search Webinars', 'buddyboss-pro' ), false ); ?></label>
								<input type="search" id="bp_zoom_webinar_search" placeholder="<?php esc_attr_e( 'Search Webinars', 'buddyboss-pro' ); ?>" />
								<button type="submit" id="bp_zoom_webinar_search_submit" class="bb-rl-search-submit">
									<i class="bb-icons-rl-magnifying-glass" aria-hidden="true"></i>
									<span id="button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search Webinars', 'buddyboss-pro' ); ?></span>
								</button>
							</form>
						</div>
					</div>

					<div class="bp-zoom-webinar-members-listing">
						<?php
						if ( ( 'webinars' === bp_zoom_group_current_tab() || 'zoom' === bp_zoom_group_current_tab() ) && bp_has_zoom_webinars(
							array(
								'zoom_type' => array( 'webinar', 'webinar_occurrence' ),
								'live'      => true,
							)
						) ) :
							?>
						<ul id="webinars-list" class="item-list bb-rl-list all-webinars">
							<?php
							while ( bp_zoom_webinar() ) {
								bp_the_zoom_webinar();

								$live_webinars[] = bp_get_zoom_webinar_id();
								bp_get_template_part( 'zoom/loop-webinar' );
							}
							?>
							<?php endif; ?>

							<?php
							if ( bp_has_zoom_webinars(
								array(
									'zoom_type' => array( 'webinar', 'webinar_occurrence' ),
									'exclude'   => implode(
										',',
										$live_webinars
									),
								)
							) ) :
								?>
								<?php if ( empty( $live_webinars ) ) : ?>
									<ul id="webinars-list" class="item-list bb-rl-list all-webinars">
								<?php endif; ?>
								<?php
								while ( bp_zoom_webinar() ) {
									bp_the_zoom_webinar();

									bp_get_template_part( 'zoom/loop-webinar' );
								}

								if ( bp_zoom_webinar_has_more_items() ) {
									?>
									<div class="load-more">
										<a class="bb-rl-button bb-rl-button--outline" href="<?php bp_zoom_webinar_load_more_link(); ?>">
											<?php esc_html_e( 'Load More', 'buddyboss-pro' ); ?>
										</a>
									</div>
									<?php
								}
								?>
								<?php if ( empty( $live_webinars ) ) : ?>
									</ul>
								<?php endif; ?>
						<span class="webinar-timezone"><?php echo esc_html__( 'Timezone:', 'buddyboss-pro' ) . ' ' . esc_attr( bp_zoom_get_timezone_label() ); ?></span>
							<?php else : ?>
								<?php if ( ! empty( $live_webinars ) ) : ?>
						</ul>
					<?php endif; ?>
								<?php if ( empty( $live_webinars ) ) : ?>
									<?php bp_nouveau_user_feedback( 'webinars-loop-none' ); ?>
						<?php endif; ?>
					<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="bp-zoom-webinar-right">
				<?php if ( ( ! empty( $bp_zoom_current_webinar ) && false === $bp_zoom_current_webinar->is_past ) || ( 'past-webinars' !== bp_zoom_group_current_tab() ) ) { ?>
					<form id="bp_zoom_webinar_form" class="bb-rl-form" data-select2-id="bp_zoom_webinar_form">
						<div class="bp-zoom-webinar-right-top">
							<div id="bp-zoom-webinar-content">
								<div id="bp-zoom-single-webinar-wrapper">

									<?php
									if ( bp_zoom_is_single_webinar() ) {
										$args = array(
											'include' => bp_action_variable( 1 ),
										);

										if ( ! empty( $bp_zoom_current_webinar ) && $bp_zoom_current_webinar->recurring && 9 === $bp_zoom_current_webinar->type ) {
											$args['hide_sitewide'] = true;
										}

										if ( bp_has_zoom_webinars( $args ) ) {
											while ( bp_zoom_webinar() ) {
												bp_the_zoom_webinar();

												bp_get_template_part( 'zoom/single-webinar-item' );
											}
										}
									} elseif ( bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), bp_get_current_group_id() ) ) {
										bp_get_template_part( 'zoom/create-webinar' );
									} else {
										bp_get_template_part( 'zoom/no-webinars' );
									}
									?>

								</div>

							</div>
						</div>
					</form>
				<?php 
				} else {
					bp_get_template_part( 'zoom/no-webinars' );
				}
				?>
			</div>
		</div>
	</div>
</div> 