<?php
/**
 * BuddyBoss - Groups Zoom Webinars
 *
 * @package BuddyBossPro/Integration/Zoom/Template
 * @since   1.0.9
 */

global $bp_zoom_current_webinar;
$live_webinars = array();
?>
<div id="bp-zoom-webinar-container" class="bp-zoom-webinar-container <?php bp_zoom_webinar_group_classes(); ?>">
	<?php bp_get_template_part( 'groups/single/parts/zoom-subnav' ); ?>

	<div class="bp-zoom-webinar-left
	<?php
	if ( empty( $bp_zoom_current_webinar ) && 'past-webinar' === bp_zoom_group_current_tab() ) {
		echo 'bp-full';
	}
	?>
	">
		<div class="bp-zoom-webinar-left-inner">
			<div class="bb-panel-head">
				<div class="bb-panel-subhead">
					<?php if ( ( ! empty( $bp_zoom_current_webinar ) && true === $bp_zoom_current_webinar->is_past && false === $bp_zoom_current_webinar->is_live ) || ( 'past-webinars' === bp_zoom_group_current_tab() ) ) { ?>
						<h4 class="total-members-text"><?php esc_html_e( 'Past Webinars', 'buddyboss-pro' ); ?></h4>
					<?php } else { ?>
						<h4 class="total-members-text"><?php esc_html_e( 'Webinars', 'buddyboss-pro' ); ?></h4>
					<?php } ?>
					<div id="bp-zoom-dropdown-options-loader" class="bp-zoom-dropdown-options-loader-hide">
						<i class="bb-icon-l bb-icon-spinner animate-spin"></i>
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
							<a href="<?php echo esc_url( trailingslashit( bp_get_group_permalink( groups_get_group( bp_get_current_group_id() ) ) . 'zoom/create-webinar/' ) ); ?>" id="bp-zoom-create-webinar-button" data-group-id="<?php echo esc_attr( bp_get_group_id() ); ?>">
								<i class="bb-icon-l bb-icon-edit"></i><?php esc_html_e( 'Create New', 'buddyboss-pro' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="bp-zoom-webinar-search subnav-search clearfix" role="search">
				<div class="bp-search">
					<form action="" method="get" id="bp_zoom_webinar_search_form" class="bp-zoom-webinar-search-form" data-bp-search="zoom-webinar">
						<label for="bp_zoom_webinar_search" class="bp-screen-reader-text"><?php bp_nouveau_search_default_text( __( 'Search Webinars', 'buddyboss-pro' ), false ); ?></label>
						<input type="search" id="bp_zoom_webinar_search" placeholder="<?php esc_attr_e( 'Search Webinars', 'buddyboss-pro' ); ?>"/>
						<button type="submit" id="bp_zoom_webinar_search_submit" class="nouveau-search-submit">
							<span class="dashicons dashicons-search" aria-hidden="true"></span>
							<span id="button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search Webinars', 'buddyboss-pro' ); ?></span>
						</button>
					</form>
				</div>
			</div>

			<div class="bp-zoom-webinar-members-listing">
				<?php
				if ( ( 'webinars' === bp_zoom_group_current_tab() ) && bp_has_zoom_webinars(
					array(
						'live' => true,
					)
				) ) :
					?>
				<ul id="webinars-list" class="item-list bp-list all-webinars">
					<?php while ( bp_zoom_webinar() ) : ?>
						<?php bp_the_zoom_webinar(); ?>
						<?php $live_webinars[] = bp_get_zoom_webinar_id(); ?>
						<?php bp_get_template_part( 'zoom/loop-webinar' ); ?>
					<?php endwhile; ?>
					<?php endif; ?>

					<?php
					if ( bp_has_zoom_webinars(
						array(
							'exclude' => implode( ',', $live_webinars ),
						)
					) ) :
						?>
						<?php if ( empty( $live_webinars ) ) : ?>
							<ul id="webinars-list" class="item-list bp-list all-webinars">
						<?php endif; ?>

						<?php while ( bp_zoom_webinar() ) : ?>
							<?php bp_the_zoom_webinar(); ?>
							<?php bp_get_template_part( 'zoom/loop-webinar' ); ?>
						<?php endwhile; ?>

						<?php if ( bp_zoom_webinar_has_more_items() ) : ?>
							<div class="load-more">
								<a class="button full outline" href="<?php bp_zoom_webinar_load_more_link(); ?>">
									<?php esc_html_e( 'Load More', 'buddyboss-pro' ); ?>
								</a>
							</div>
						<?php endif; ?>
						<?php if ( empty( $live_webinars ) ) : ?>
							</ul>
						<?php endif; ?>
						<span class="webinar-timezone">
						<?php echo esc_html__( 'Timezone:', 'buddyboss-pro' ) . ' ' . esc_attr( bp_zoom_get_timezone_label() ); ?>
				</span>
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

	<?php if ( ( ! empty( $bp_zoom_current_webinar ) && false === $bp_zoom_current_webinar->is_past ) || ( 'past-webinars' !== bp_zoom_group_current_tab() ) ) { ?>
	<div class="bp-zoom-webinar-right">
		<form id="bp_zoom_webinar_form" class="standard-form" data-select2-id="bp_zoom_webinar_form">
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
						}
						?>
					</div>
				</div>
			</div>
		</form>
	</div>
	<?php } ?>
</div>
